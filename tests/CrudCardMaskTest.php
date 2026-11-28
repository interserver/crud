<?php

use MyCrud\Crud;
use PHPUnit\Framework\TestCase;

if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', 1);
}
// core's include/sanitizers.php htmlspecial(), which decorate_field() calls (the crud tests
// load only the autoloader)
if (!function_exists('htmlspecial')) {
    function htmlspecial($text)
    {
        return is_null($text) ? $text : htmlspecialchars($text, ENT_COMPAT | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }
}

class CardMaskTestCrud extends Crud
{
    public $logged = [];

    public function log($message, $line = '', $file = '', $level = 'info')
    {
        $this->logged[] = $message;
    }

    // the edit handler's csrf check needs a MyAdmin session; CrudEditGuardTest covers it
    protected function verify_write_csrf()
    {
        return true;
    }
}

class CardMaskTestDb
{
    public $queries = [];
    public $Record = [];
    /** @var array[] the rows the next query returns */
    public $rows = [];

    public function real_escape($value)
    {
        return addslashes((string) $value);
    }

    public function query($query, $line = '', $file = '')
    {
        $this->queries[] = $query;
        return true;
    }

    public function next_record($type = null)
    {
        if (count($this->rows) == 0) {
            return false;
        }
        $this->Record = array_shift($this->rows);
        return true;
    }
}

/**
 * cc_log.cc_request_card_num holds full card numbers (most rows from 2013 to 2026-09-25).
 * crud_cc_log masked them only in its table cells (a decorate_cc filter), so the inline
 * crud_rows json of the page, the ajax list and every export carried the whole number, the
 * crud_table browser showed the raw table, and the column could be searched. it is a secret
 * column now, shown as mask_cc()'s stars plus the last 4, and crud_cc_log masks it in the sql.
 */
class CrudCardMaskTest extends TestCase
{
    // test card numbers (the standard visa / amex test numbers), never real ones
    private const VISA = '4111111111111111';
    private const VISA_MASKED = '************1111';

    /** the package under test (the clone or the old copy, whichever the bootstrap loads) */
    private static function packageDir(): string
    {
        return dirname((new ReflectionClass(Crud::class))->getFileName(), 2);
    }

    private function crud(): CardMaskTestCrud
    {
        $rc = new ReflectionClass(CardMaskTestCrud::class);
        /** @var CardMaskTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        $crud->db = new CardMaskTestDb();
        $crud->type = 'table';
        $crud->table = 'cc_log';
        $crud->tables = [
            'cc_log' => [
                'cc_id' => ['Type' => 'int(11) unsigned', 'Key' => 'PRI'],
                'cc_request_card_num' => ['Type' => 'varchar(40)', 'Key' => ''],
                'cc_request_card_code' => ['Type' => 'varchar(10)', 'Key' => ''],
                'cc_result_reason_text' => ['Type' => 'text', 'Key' => ''],
            ],
        ];
        $crud->fields = ['cc_id', 'cc_request_card_num', 'cc_request_card_code', 'cc_result_reason_text'];
        $crud->primary_key = 'cc_id';
        $crud->order_by = 'cc_id';
        $crud->order_dir = 'desc';
        $crud->page_limit = 25;
        $crud->page_offset = 0;
        return $crud;
    }

    private static function rawRow(): array
    {
        return ['cc_id' => 9, 'cc_request_card_num' => self::VISA, 'cc_request_card_code' => '123', 'cc_result_reason_text' => 'This transaction has been approved.'];
    }

    public function testTheCardNumberIsASecretColumn(): void
    {
        $crud = $this->crud();
        foreach (['cc_request_card_num', 'cc_log.cc_request_card_num', '`cc_request_card_num`', 'CC_REQUEST_CARD_NUM'] as $field) {
            $this->assertTrue($crud->is_secret_field($field), $field);
        }
        $this->assertSame('last4', Crud::$secret_column_masks['cc_request_card_num']);
        // never searched (the log can no longer be searched by card number) or sorted on
        $this->assertSame('', $crud->json_search_tosql('cc_request_card_num', '=', '4111', true));
        $this->assertSame('', $crud->json_search_tosql('cc_log.cc_request_card_num', 'in', [self::VISA]));
        $crud->order_by = 'cc_request_card_num';
        $crud->run_list_query();
        $this->assertSame('select * from cc_log order by cc_id desc limit 0, 25', end($crud->db->queries));
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}> [value, mask_cc() output]
     */
    public static function last4Provider(): array
    {
        return [
            'visa 16' => [self::VISA, self::VISA_MASKED],
            'amex 15' => ['378282246310005', '***********0005'],
            '7 characters' => ['1234567', '***4567'],
            '6 characters, shown as is' => ['123456', '123456'],
            'last 4 only' => ['1111', '1111'],
            'empty' => ['', ''],
            'already masked' => [self::VISA_MASKED, self::VISA_MASKED],
            'multibyte counted in characters' => ['ééééééé1234', '*******1234'],
        ];
    }

    /** @dataProvider last4Provider */
    public function testTheLast4MaskIsMaskCcs(string $value, string $expected): void
    {
        $this->assertSame($expected, Crud::mask_last4($value));
        // core's mask_cc() (myadmin-authorizenet-payments), when it is installed next to the crud
        $vendor = dirname((new ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName(), 2);
        $ccInc = $vendor . '/detain/myadmin-authorizenet-payments/src/cc.inc.php';
        if (!function_exists('mask_cc') && is_file($ccInc)) {
            require_once $ccInc;
        }
        if (function_exists('mask_cc')) {
            $this->assertSame(mask_cc($value), Crud::mask_last4($value));
        }
    }

    public function testRecordsShowTheLast4AndTheCodeStaysFullyMasked(): void
    {
        $crud = $this->crud();
        $out = $crud->mask_secret_fields(self::rawRow());
        $this->assertSame(self::VISA_MASKED, $out['cc_request_card_num']);
        $this->assertSame('********', $out['cc_request_card_code']);
        $this->assertSame('This transaction has been approved.', $out['cc_result_reason_text']);
        $empty = $crud->mask_secret_fields(['cc_request_card_num' => '', 'cc_request_card_code' => null]);
        $this->assertSame('', $empty['cc_request_card_num']);
        $this->assertNull($empty['cc_request_card_code']);
    }

    /** the crud_table browser lists the raw cc_log table (db=domains is the my database) */
    public function testARawTableRowShowsOnlyTheLast4(): void
    {
        $crud = $this->crud();
        $crud->db->Record = self::rawRow();
        $record = $crud->get_record();
        $this->assertSame(self::VISA_MASKED, $record['cc_request_card_num']);
        $this->assertSame('********', $record['cc_request_card_code']);
    }

    public function testTheAjaxListAndTheExportsCarryOnlyTheLast4(): void
    {
        $crud = $this->crud();
        $crud->db->rows = [self::rawRow()];
        ob_start();
        $crud->ajax_list_handler();
        $ajax = (string) ob_get_clean();
        $this->assertStringNotContainsString(self::VISA, $ajax);
        $this->assertStringContainsString(self::VISA_MASKED, $ajax);

        $crud->db->rows = [self::rawRow()];
        $crud->get_all_rows();
        $this->assertSame(self::VISA_MASKED, $crud->rows[0]['cc_request_card_num']);
        $this->assertStringNotContainsString(self::VISA, $crud->export_json([]));
        $this->assertStringNotContainsString(self::VISA, $crud->export_php([]));
    }

    public function testAnEditNeverWritesTheMaskedNumberBack(): void
    {
        $crud = $this->crud();
        $crud->edit_row = true;
        $crud->admin = true; // the edit handler is admin-only
        $crud->query_fields = ['cc_id' => 'cc_id', 'cc_request_card_num' => 'cc_request_card_num', 'cc_result_reason_text' => 'cc_result_reason_text'];
        $crud->validations = ['cc_id' => ['int'], 'cc_request_card_num' => [], 'cc_result_reason_text' => []];
        $crud->query_where = ['cc_log' => []];
        $savedPost = $_POST;
        try {
            // what the edit form posts back: the masked number from crud_rows
            $_POST = ['cc_id' => '9', 'cc_request_card_num' => self::VISA_MASKED, 'cc_result_reason_text' => 'edited'];
            ob_start();
            $crud->ajax_edit_handler();
            ob_end_clean();
            $this->assertSame(["update cc_log set cc_result_reason_text='edited' where cc_id='9'"], $crud->db->queries);

            // a number that was typed in is still written
            $crud->db->queries = [];
            $_POST = ['cc_id' => '9', 'cc_request_card_num' => '5555555555554444'];
            ob_start();
            $crud->ajax_edit_handler();
            ob_end_clean();
            $this->assertSame(["update cc_log set cc_request_card_num='5555555555554444' where cc_id='9'"], $crud->db->queries);
        } finally {
            $_POST = $savedPost;
        }
        $this->assertTrue($crud->is_secret_mask_value('cc_request_card_num', '***4567'));
        $this->assertFalse($crud->is_secret_mask_value('cc_request_card_num', '5555555555554444'));
        // the plain mask columns still only skip the plain mask
        $this->assertTrue($crud->is_secret_mask_value('cc_request_card_code', '********'));
        $this->assertFalse($crud->is_secret_mask_value('cc_request_card_code', '*12'));
    }

    /**
     * crud_cc_log's decorate_cc filter handed a NULL card number (a request logged without one)
     * to core's mask_cc(), whose mb_strlen() deprecates NULL in php 8.1: on every such row of the
     * page and, since the ajax list decorates 'function' filtered columns, of every refill. the
     * cell still shows nothing (it was NULL, which the table and the ajax cell both print as
     * nothing); a number is masked as before
     */
    public function testDecorateCcTakesANullCardNumberWithoutADeprecation(): void
    {
        $vendor = dirname((new ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName(), 2);
        $ccInc = $vendor . '/detain/myadmin-authorizenet-payments/src/cc.inc.php';
        if (!function_exists('mask_cc') && is_file($ccInc)) {
            require_once $ccInc;
        }
        if (!function_exists('mask_cc')) {
            $this->markTestSkipped('core\'s mask_cc() (myadmin-authorizenet-payments) is not installed');
        }
        require_once self::packageDir() . '/src/crud/crud_cc_log.php';
        $source = (string) file_get_contents(self::packageDir() . '/src/crud/crud_cc_log.php');
        $this->assertStringContainsString("->add_filter('cc_request_card_num', 'decorate_cc', 'function')", $source);

        $crud = $this->crud();
        $crud->filters['cc_request_card_num'][] = ['type' => 'function', 'value' => 'decorate_cc', 'acl' => false];
        $this->assertTrue($crud->has_server_decoration('cc_request_card_num'));
        $cell = 'cc_request_card_num' . Crud::DECORATED_CELL_SUFFIX;
        // [card number as selected, the cell]: NULL and '' show nothing, a number its mask
        $values = [[null, ''], ['', ''], [self::VISA_MASKED, self::VISA_MASKED], ['123456', '123456'], ['1234567', '***4567']];
        foreach ($values as [$value, $expected]) {
            $row = ['cc_id' => 9, 'cc_request_card_num' => $value, 'cc_request_card_code' => null, 'cc_result_reason_text' => 'declined'];
            $diagnostics = [];
            set_error_handler(static function ($errno, $errstr, $errfile = '', $errline = 0) use (&$diagnostics) {
                $diagnostics[] = "[{$errno}] {$errstr} at " . basename((string) $errfile) . ":{$errline}";
                return true;
            }, E_DEPRECATED | E_USER_DEPRECATED | E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE);
            try {
                $decorated = $crud->decorate_field('cc_request_card_num', $row);
                $cells = $crud->ajax_cells($row);
                // the ajax list: the record as fetched (get_record() masks the secret column first)
                $crud->db->rows = [$row];
                ob_start();
                try {
                    $crud->ajax_list_handler();
                } finally {
                    $json = json_decode((string) ob_get_clean(), true);
                }
            } finally {
                restore_error_handler();
            }
            $label = var_export($value, true);
            $this->assertSame([], $diagnostics, $label);
            $this->assertSame($expected, $decorated, $label);
            $this->assertSame($expected, $cells[$cell] ?? null, $label);
            $this->assertSame($expected, $json['cells'][0][$cell] ?? null, $label);
        }
    }

    /** crud_cc_log's query, as it is in the page (the first string literal passed to init()) */
    private static function ccLogQuery(): string
    {
        $tokens = token_get_all((string) file_get_contents(self::packageDir() . '/src/crud/crud_cc_log.php'));
        $seenInit = false;
        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_STRING && $token[1] === 'init') {
                $seenInit = true;
            } elseif ($seenInit && is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                // a single quoted literal: only \\ and \' are escapes
                return strtr(substr($token[1], 1, -1), ['\\\\' => '\\', "\\'" => "'"]);
            }
        }
        return '';
    }

    public function testCcLogMasksTheNumberInItsSql(): void
    {
        $query = self::ccLogQuery();
        $this->assertStringStartsWith("select\ncc_id, cc_timestamp, cc_custid,", $query);
        // mask_cc() in sql: more than 6 characters, a '*' each but the last 4
        $mask = "if(char_length(ifnull(cc_request_card_num,''))>6,concat(repeat('*',char_length(cc_request_card_num)-4),right(cc_request_card_num,4)),cc_request_card_num) as cc_request_card_num";
        $this->assertStringContainsString($mask, $query);
        $this->assertStringNotContainsString('cc_request_card_num', str_replace($mask, '', $query), 'the raw column is selected only inside the mask');
        // the alias maps back to its own (secret) column, so it is still listed, edited and
        // refused for search and sort under that name
        $crud = $this->crud();
        $crud->type = 'query';
        $crud->queries = (new \SQLParser())->parse($query);
        $crud->parse_query_fields();
        $this->assertSame('cc_request_card_num', $crud->query_fields['cc_request_card_num']);
        $this->assertSame('', $crud->json_search_tosql('cc_request_card_num', '=', '1111', true));
    }
}
