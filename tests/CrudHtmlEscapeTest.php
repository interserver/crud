<?php

use MyCrud\Crud;
use PHPUnit\Framework\TestCase;

// core's include/sanitizers.php htmlspecial(), which decorate_field() calls (the crud tests
// load only the autoloader)
if (!function_exists('htmlspecial')) {
    function htmlspecial($text)
    {
        return is_null($text) ? $text : htmlspecialchars($text, ENT_COMPAT | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', 1);
}

// a 'function' filter of the kind the crud pages add (a named function): builds its html from
// the row decorate_field() hands it, escaping what it puts in
if (!function_exists('html_escape_test_link_filter')) {
    function html_escape_test_link_filter($field, $value, $row = [])
    {
        return '<a href="view_invoice?id=' . rawurlencode((string) ($row['invoices_id'] ?? '')) . '">' . Crud::escape_cell($row[$field] ?? '') . '</a>';
    }
}
// the data function of a 'function' crud (CrudFunctionIterator runs it by name)
if (!function_exists('html_escape_test_function_rows')) {
    function html_escape_test_function_rows()
    {
        return [
            ['id' => 5, 'link' => '<a href="view_invoice?id=5">5</a>', 'description' => '<img src=x onerror=alert(1)>'],
            ['id' => 6, 'link' => '<a href="view_invoice?id=6">6</a>', 'description' => 'plain & simple'],
        ];
    }
}

// the data function of a 'function' crud that has nothing to list
if (!function_exists('html_escape_test_no_rows')) {
    function html_escape_test_no_rows()
    {
        return [];
    }
}

// the data function of a 'function' list of remote or feed data (crud_forum_rss lists a feed):
// a row holding an invalid utf-8 byte and markup
if (!function_exists('html_escape_test_feed_rows')) {
    function html_escape_test_feed_rows()
    {
        return [
            ['id' => 7, 'title' => "Forum post caf\xE9 news", 'note' => '<script>alert(1)</script>'],
            ['id' => 8, 'title' => 'plain', 'note' => 'a & b'],
        ];
    }
}

/** the session double the crud pages' link building and function_requirements() reach */
class HtmlEscapeTestTf
{
    public $ima = 'admin';
    /** TFTable::get_table() reads it (core's tf: pdf table capture, off) */
    public $_capture_tables = false;

    public function function_requirements($function)
    {
        return true;
    }

    /** App::output()->isPlainpage(), which TFTable's constructor asks */
    public function is_plainpage()
    {
        return false;
    }

    /** App::output()->addHeadJsFile(), which Crud::add_js_headers() calls (page chrome, ignored) */
    public function add_html_head_js_file($file)
    {
    }

    /** the pretty (SEO) link form: 'index.php', 'choice=none.view_domain&id=5' -> 'view_domain?id=5' */
    public function link($url, $extravars = '')
    {
        if (preg_match('/^choice=none\.([A-Za-z0-9_]+)&?(.*)$/', $extravars, $m)) {
            return $m[1] . ($m[2] !== '' ? '?' . $m[2] : '');
        }
        return $url . ($extravars !== '' ? '?' . $extravars : '');
    }
}

/** the session core's TFTable reads (app_theme, shell_login, xml_login: none set) */
class HtmlEscapeTestSession implements \MyAdmin\App\Contracts\SessionInterface
{
    public $account_id = 0;

    public function verify()
    {
        return true;
    }

    public function create($accountId, $ima, $browser = true, $expire = 0, $dir = false, $sessionid = false, $sudo = false)
    {
    }

    public function destroy($sessionid = false)
    {
        return true;
    }

    public function update_dla()
    {
    }

    public function appsession($location = 'default', $value = '##NOTHING##')
    {
        return null;
    }

    public function appnocache($location, $data)
    {
    }

    public function delappsession($location)
    {
    }

    public function appcache()
    {
        return [];
    }

    public function get_csrf($unique_form_name, $onetime = false)
    {
        return 'test-csrf';
    }

    public function verify_csrf($unique_form_name, $token_value)
    {
        return true;
    }
}

/**
 * core's TFSmarty (include/Rendering/TFSmarty.php) for the one test that renders a page through
 * core's TFTable, in a process of its own (class_alias()ed to TFSmarty there): the same plugin
 * dir and the modifiers the crud templates use, but the templates of the package under test
 * first. core's finds crud/*.tpl through include/templates/crud, the installed package's copy
 */
class HtmlEscapeTestSmarty extends Smarty
{
    /** @var string[] */
    public static $templateDirs = [];
    /** @var string */
    public static $compileDir = '';
    /** @var string */
    public static $pluginsDir = '';

    public function __construct()
    {
        parent::__construct();
        $this->setTemplateDir(self::$templateDirs)
            ->setCompileDir(self::$compileDir)
            ->addPluginsDir(self::$pluginsDir)
            ->setCaching(false);
        foreach (['in_array', 'sizeof', 'strtoupper'] as $function) {
            $this->registerPlugin('modifier', $function, $function);
        }
        // the template variables TFTable leaves unset (table_headers on an empty list and the
        // like) read as null, as with the error reporting core runs under
        $this->muteUndefinedOrNullWarnings();
    }
}

class HtmlEscapeTestCrud extends Crud
{
    public $logged = [];

    public function log($message, $line = '', $file = '', $level = 'info')
    {
        $this->logged[] = $message;
    }
}

class HtmlEscapeTestDb
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
 * stored xss through the crud lists:
 *  - crud_request_log turned html filtering off so its decorator could json_decode the raw
 *    value, and printed the logged api responses (text the far end chose) as html;
 *  - crud_view_invoices turned filtering off for its two <img> columns, which printed the
 *    customer's invoice description and service name raw too;
 *  - a 'string' filter pasted the other %row_field% values in raw, one placeholder after
 *    another (a value holding '%other%' pulled in that field raw);
 *  - crud.js built every row of the ajax list (paging, sorting, searching) from the raw
 *    record values, pasted into the row template's html;
 *  - table5.tpl printed the admin debug dump (print_r of the crud: request, search terms,
 *    rows) unescaped.
 */
class CrudHtmlEscapeTest extends TestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(1)>';

    /** the package under test (the clone or the old copy, whichever the bootstrap loads) */
    private static function packageDir(): string
    {
        return dirname((new ReflectionClass(Crud::class))->getFileName(), 2);
    }

    /** core's vendor dir (the autoloader the tests run with) */
    private static function vendorDir(): string
    {
        return dirname((new ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName(), 2);
    }

    private function crud(): HtmlEscapeTestCrud
    {
        $rc = new ReflectionClass(HtmlEscapeTestCrud::class);
        /** @var HtmlEscapeTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        $crud->db = new HtmlEscapeTestDb();
        $crud->type = 'table';
        $crud->table = 'invoices';
        $crud->tables = [
            'invoices' => [
                'invoices_id' => ['Type' => 'int(11)', 'Key' => 'PRI'],
                'invoices_type' => ['Type' => 'varchar(255)', 'Key' => ''],
                'invoices_description' => ['Type' => 'varchar(255)', 'Key' => ''],
                'invoices_note' => ['Type' => 'text', 'Key' => ''],
            ],
        ];
        $crud->fields = ['invoices_id', 'invoices_type', 'invoices_description', 'invoices_note'];
        $crud->primary_key = 'invoices_id';
        $crud->order_by = 'invoices_id';
        $crud->order_dir = 'desc';
        $crud->page_limit = 25;
        $crud->page_offset = 0;
        return $crud;
    }

    /** the filter add_filter() would store, without needing has_acl() */
    private static function filter(Crud $crud, string $field, $value, string $type = 'string'): void
    {
        $crud->filters[$field][] = ['type' => $type, 'value' => $value, 'acl' => false];
    }

    private static function loadRequestLog(): void
    {
        require_once self::packageDir() . '/src/crud/crud_request_log.php';
    }

    /**
     * @return array<string, array{0: string}> the raw stored value and the htmlspecial()
     *                                         escaped one the crud hands its filters
     */
    public static function requestLogJsonProvider(): array
    {
        $json = '{"result":[{"statusmsg":"' . self::PAYLOAD . '","note":"a & b \\"quoted\\" \'single\'"}]}';
        return [
            'as stored' => [$json],
            'as decorate_field() passes it (htmlspecial escaped)' => [htmlspecial($json)],
        ];
    }

    /** @dataProvider requestLogJsonProvider */
    public function testRequestLogPrettyPrintsTheJsonEscaped(string $value): void
    {
        self::loadRequestLog();
        $json = htmlspecialchars_decode($value, ENT_COMPAT | ENT_HTML5);

        $out = request_log_decorate('request_result', $value);

        $this->assertStringStartsWith('<pre style="text-align: left;">', $out);
        $this->assertStringEndsWith('</pre>', $out);
        $this->assertStringNotContainsString('<img', $out);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $out);
        // the same pretty printed json as before, only escaped for the page
        $inner = substr($out, strlen('<pre style="text-align: left;">'), -strlen('</pre>'));
        $this->assertSame(json_encode(json_decode($json, true), JSON_PRETTY_PRINT), html_entity_decode($inner, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function testRequestLogEscapesAValueThatIsNotJson(): void
    {
        self::loadRequestLog();
        $raw = '<script>alert(1)</script> not json & "quoted"';
        $expected = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->assertSame($expected, request_log_decorate('request_request', $raw));
        $this->assertSame($expected, request_log_decorate('request_request', htmlspecial($raw)));
    }

    public function testRequestLogKeepsHtmlFilteringOn(): void
    {
        $source = (string) file_get_contents(self::packageDir() . '/src/crud/crud_request_log.php');
        $this->assertStringNotContainsString('set_use_html_filtering(false)', $source);
        $this->assertStringContainsString("add_filter('request_result', 'request_log_decorate', 'function')", $source);
    }

    public function testRawHtmlFieldsKeepTheirHtmlAndEveryOtherFieldIsEscaped(): void
    {
        $crud = $this->crud();
        $img = '<img src="images/myadmin/paypal.png" border=0 alt="10" style="width: 24px;">';
        $row = ['invoices_id' => 5, 'invoices_type' => $img, 'invoices_description' => self::PAYLOAD];

        $crud->set_raw_html_fields(['invoices_type', 7, null]);

        $this->assertSame(['invoices_type'], $crud->raw_html_fields, 'only field names are kept');
        $this->assertTrue($crud->is_raw_html_field('invoices_type'));
        $this->assertFalse($crud->is_raw_html_field('invoices_description'));
        $this->assertSame($img, $crud->decorate_field('invoices_type', $row));
        $this->assertSame('&lt;img src=x onerror=alert(1)&gt;', $crud->decorate_field('invoices_description', $row));
        // a list with filtering off prints every field as it is, as before
        $crud->set_use_html_filtering(false);
        $this->assertSame(self::PAYLOAD, $crud->decorate_field('invoices_description', $row));
    }

    public function testViewInvoicesMarksOnlyItsImgColumnsAsHtml(): void
    {
        $source = (string) file_get_contents(self::packageDir() . '/src/crud/crud_view_invoices.php');
        $this->assertStringNotContainsString('set_use_html_filtering(false)', $source);
        $this->assertStringContainsString("->set_raw_html_fields(['invoices_type', 'invoices_paid'])", $source);
    }

    public function testStringFilterEscapesTheRowValuesItPastesIn(): void
    {
        $crud = $this->crud();
        self::filter($crud, 'invoices_description', '<a href="view_invoice?id=%invoices_id%" title="%invoices_note%">%value%</a>');
        $row = ['invoices_id' => 5, 'invoices_description' => 'a<b>', 'invoices_note' => '"><img src=x onerror=alert(1)>'];

        $out = $crud->decorate_field('invoices_description', $row);

        $this->assertSame('<a href="view_invoice?id=5" title="&quot;&gt;&lt;img src=x onerror=alert(1)&gt;">a&lt;b&gt;</a>', $out);
    }

    public function testStringFilterReplacesThePlaceholdersInOnePass(): void
    {
        $crud = $this->crud();
        self::filter($crud, 'invoices_id', '<b>%invoices_note%</b> %invoices_description% %field%');
        // a value that is itself a placeholder is not filled in again
        $row = ['invoices_id' => 5, 'invoices_note' => '%invoices_description%', 'invoices_description' => self::PAYLOAD];

        $out = $crud->decorate_field('invoices_id', $row);

        $this->assertSame('<b>%invoices_description%</b> &lt;img src=x onerror=alert(1)&gt; invoices_id', $out);
    }

    public function testAStringFilterOnARawHtmlFieldKeepsItsHtml(): void
    {
        $crud = $this->crud();
        $crud->set_raw_html_fields(['invoices_type']);
        self::filter($crud, 'invoices_id', '<span>%invoices_type%</span>');
        $row = ['invoices_id' => 5, 'invoices_type' => '<img src="images/myadmin/budget.png">'];

        $this->assertSame('<span><img src="images/myadmin/budget.png"></span>', $crud->decorate_field('invoices_id', $row));
    }

    public function testTheAjaxListSendsEscapedCellsBesideTheStoredRows(): void
    {
        $crud = $this->crud();
        // (the property set_raw_html_fields() fills, so the old code answers too)
        $crud->raw_html_fields = ['invoices_type'];
        $img = '<img src="images/myadmin/paypal.png">';
        $crud->db->rows = [['invoices_id' => 5, 'invoices_type' => $img, 'invoices_description' => self::PAYLOAD, 'invoices_note' => null]];

        ob_start();
        $crud->ajax_list_handler();
        $json = json_decode((string) ob_get_clean(), true);

        $this->assertIsArray($json);
        $this->assertSame(['rows', 'cells'], array_keys($json));
        // rows: the records as stored, what the edit form fills its inputs from
        $this->assertSame([['invoices_id' => 5, 'invoices_type' => $img, 'invoices_description' => self::PAYLOAD, 'invoices_note' => null]], $json['rows']);
        // cells: escaped for pasting into the row html, raw html columns kept, null as ''
        $this->assertSame([['invoices_id' => '5', 'invoices_type' => $img, 'invoices_description' => '&lt;img src=x onerror=alert(1)&gt;', 'invoices_note' => '']], $json['cells']);
    }

    public function testHtmlSafeRecordEscapesQuotesForEitherAttributeQuote(): void
    {
        $crud = $this->crud();
        $this->assertSame(['a' => '&#039;&quot;&lt;&gt;&amp;', 'b' => '1.5', 'c' => ''], $crud->html_safe_record(['a' => '\'"<>&', 'b' => 1.5, 'c' => null]));
        $crud->set_use_html_filtering(false);
        $this->assertSame(['a' => '\'"<>&', 'c' => ''], $crud->html_safe_record(['a' => '\'"<>&', 'c' => null]));
    }

    /**
     * runs crud.js in node against a stub jQuery: crud_load_page() with $input['template'] as
     * the empty row and $input['response'] as the ajax answer, and crud_fill_row() of
     * $input['fill_template'] / $input['fill_cells']. skips when node is not installed.
     *
     * @return array{appended: string[], crud_rows: mixed, fill: ?string}
     */
    private function runCrudJs(array $input): array
    {
        $node = trim((string) shell_exec('command -v node 2>/dev/null'));
        if ($node === '') {
            $this->markTestSkipped('node is not installed');
        }
        $harness = <<<'JS'
const fs = require('fs'), vm = require('vm');
const input = JSON.parse(fs.readFileSync(0, 'utf8'));
const appended = [];
const chain = new Proxy(function () {}, {
    get: (target, prop) => prop === 'append' ? (html) => { appended.push(html); return chain; } : chain,
    apply: () => chain,
});
const jq = new Proxy(function () { return chain; }, {
    get: (target, prop) => prop === 'getJSON' ? (url, data, cb) => cb(input.response) : (prop === 'fn' ? {} : chain),
});
const sandbox = {
    jQuery: jq, $: jq, console: { log() {} },
    document: { getElementById: (id) => id === 'itemrowempty' ? { innerHTML: input.template } : null, location: { pathname: '/' } },
};
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(input.file, 'utf8'), sandbox);
sandbox.get_crud_url = () => 'ajax.php';
sandbox.crud_update_pager = () => {};
sandbox.sortTable = () => {};
vm.runInContext('crud_load_page();', sandbox);
const fill = typeof sandbox.crud_fill_row === 'function' ? sandbox.crud_fill_row(input.fill_template, input.fill_cells) : null;
process.stdout.write(JSON.stringify({ appended: appended.slice(1), crud_rows: sandbox.crud_rows, fill: fill }));
JS;
        $proc = proc_open([$node, '-e', $harness], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proc);
        fwrite($pipes[0], json_encode($input));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($proc), $stderr);
        $out = json_decode((string) $stdout, true);
        $this->assertIsArray($out);
        return $out;
    }

    /**
     * crud.js's crud_load_page(), run in node against a stub jQuery: the rows it appends are
     * built from the server's escaped cells (crud_fill_row()), crud_rows keeps the stored rows
     */
    public function testCrudJsBuildsTheAjaxRowsFromTheEscapedCells(): void
    {
        $raw = ['invoices_id' => 5, 'invoices_description' => self::PAYLOAD];
        $input = [
            'file' => self::packageDir() . '/public_html/js/crud.js',
            'template' => '<td>%invoices_id%</td><td>%invoices_description%</td>',
            'response' => ['rows' => [$raw], 'cells' => [['invoices_id' => '5', 'invoices_description' => '&lt;img src=x onerror=alert(1)&gt;']]],
            'fill_template' => '<td>%id%</td><td>%id_extra%</td><td>%note%</td><td>%missing%</td>',
            'fill_cells' => ['id' => '5', 'id_extra' => 'e', 'note' => '%id% $& $1 $`', 'missing' => null],
        ];
        $out = $this->runCrudJs($input);

        $this->assertSame(['<tr id="itemrow0"><td>5</td><td>&lt;img src=x onerror=alert(1)&gt;</td></tr>'], $out['appended']);
        $this->assertSame([$raw], $out['crud_rows'], 'crud_rows keeps the stored values for the edit form');
        // one pass, a value pasted as given ($ patterns are not replacement patterns), null as ''
        $this->assertSame('<td>5</td><td>e</td><td>%id% $& $1 $`</td><td></td>', $out['fill']);
    }

    /** crud.js changed, so the templates load it under a new cache-busting version */
    public function testTheTemplatesLoadTheNewCrudJs(): void
    {
        foreach (['table5.tpl', 'tableAdminLte.tpl', 'tableAdminLteMaterial.tpl'] as $template) {
            $source = (string) file_get_contents(self::packageDir() . '/public_html/templates/crud/' . $template);
            $this->assertStringContainsString('<script src="/js/crud.js?v=20261127"></script>', $source, $template);
        }
    }

    public function testTheAdminDebugDumpIsEscaped(): void
    {
        foreach (glob(self::packageDir() . '/public_html/templates/crud/*.tpl') as $file) {
            preg_match_all('/\{[^{}]*\$debug_output\b[^{}]*\}/', (string) file_get_contents($file), $matches);
            foreach ($matches[0] as $tag) {
                $this->assertSame('{$debug_output|escape}', $tag, basename($file));
            }
        }
        $this->assertStringContainsString('{$debug_output|escape}', (string) file_get_contents(self::packageDir() . '/public_html/templates/crud/table5.tpl'));
    }

    /**
     * table5.tpl prints the admin debug dump (print_r of the crud) through smarty's escape,
     * htmlspecialchars() without ENT_SUBSTITUTE, which prints '' for a string holding one
     * invalid utf-8 byte. a 'function' list keeps its rows (remote or feed data) in the dumped
     * crud, so one such byte in a row blanked the whole dump (unescaped, before this batch, it
     * showed, raw). list_records() hands the template the dump as valid utf-8 (the byte as
     * '?'), which it prints escaped. the real list_records(), core's TFTable and the package's
     * table5.tpl, in a process of its own: TFTable renders through TFSmarty, which is
     * HtmlEscapeTestSmarty here (the package's templates first, see there)
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testTheAdminDebugDumpOfAListHoldingAnInvalidUtf8ByteIsShownEscaped(): void
    {
        $core = dirname(self::vendorDir());
        $this->assertFalse(class_exists('TFSmarty', false), 'no TFSmarty is loaded in this process yet');
        HtmlEscapeTestSmarty::$templateDirs = [self::packageDir() . '/public_html/templates', $core . '/public_html/templates', $core . '/include/templates'];
        HtmlEscapeTestSmarty::$compileDir = sys_get_temp_dir() . '/crud-debug-dump-' . bin2hex(random_bytes(6));
        HtmlEscapeTestSmarty::$pluginsDir = self::vendorDir() . '/smarty-gettext/smarty-gettext';
        class_alias(HtmlEscapeTestSmarty::class, 'TFSmarty');
        require_once $core . '/include/Rendering/TFTable.php';
        // what core defines before a page runs: Crud renders crud/table5.tpl for every theme but
        // adminlte (the admin panel's 'my'), TFTable's form target
        if (!defined('DEFAULT_THEME')) {
            define('DEFAULT_THEME', 'my');
        }
        if (!defined('POST_LOCATION')) {
            define('POST_LOCATION', '/index.php');
        }
        // add_js_headers() calls core's add_js() (include/functions.inc.php)
        if (!function_exists('add_js')) {
            function add_js(...$args)
            {
            }
        }
        \MyAdmin\App::setContainer(
            \MyAdmin\App\Testing\TestContainerBuilder::make()
                ->withTf(new HtmlEscapeTestTf())
                ->withSession(new HtmlEscapeTestSession())
                ->withVariables(new \MyAdmin\Variables())
                ->build()
        );
        $crud = $this->crud();
        $crud->admin = true;
        $crud->theme = DEFAULT_THEME;
        $crud->type = 'function';
        $crud->query = 'html_escape_test_feed_rows';
        $crud->table = 'html_escape_test_feed_rows';
        $crud->tables = ['html_escape_test_feed_rows' => [
            'id' => ['Type' => 'int(11)', 'Key' => 'PRI', 'Comment' => ''],
            'title' => ['Type' => 'varchar(255)', 'Key' => '', 'Comment' => ''],
            'note' => ['Type' => 'varchar(255)', 'Key' => '', 'Comment' => ''],
        ]];
        $crud->fields = ['id', 'title', 'note'];
        $crud->primary_key = 'id';
        $crud->order_by = 'id';
        $crud->title = 'Feed';
        $crud->set_return_output(true);
        try {
            $crud->list_records();
        } finally {
            \MyAdmin\App::resetContainer();
            if (is_dir(HtmlEscapeTestSmarty::$compileDir)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(HtmlEscapeTestSmarty::$compileDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                }
                rmdir(HtmlEscapeTestSmarty::$compileDir);
            }
        }
        $html = (string) $crud->output;

        $this->assertSame(1, preg_match('#id="debugModalLabel".*?<pre[^>]*>(.*?)</pre>#s', $html, $match), 'the admin debug modal is rendered');
        $pre = $match[1];
        $this->assertNotSame('', $pre, 'the dump is shown');
        $this->assertTrue(mb_check_encoding($pre, 'UTF-8'));
        // the dump of this crud, its rows among it: the invalid byte as '?', the markup escaped
        $this->assertStringContainsString('[query] =&gt; html_escape_test_feed_rows', $pre);
        $this->assertStringContainsString('[title] =&gt; Forum post caf? news', $pre);
        $this->assertStringContainsString('[note] =&gt; &lt;script&gt;alert(1)&lt;/script&gt;', $pre);
        $this->assertStringContainsString('[note] =&gt; a &amp; b', $pre);
        $this->assertStringNotContainsString('<script>alert(1)', $html, 'the row markup runs nowhere on the page');
    }

    /** a container whose tf builds pretty links and answers function_requirements() */
    private static function withTf(): void
    {
        \MyAdmin\App::setContainer(\MyAdmin\App\Testing\TestContainerBuilder::make()->withTf(new HtmlEscapeTestTf())->build());
    }

    /**
     * a 'function' filter gets the row (decorate_field() handed it only the field and its
     * escaped value, so a filter could not build a link from another column), and the ajax
     * list sends its html with every row under '<field>:html', the placeholder the row
     * template holds for that column: the column looks the same after a refill as on the page
     */
    public function testAFunctionFilterGetsTheRowAndItsHtmlComesWithEveryAjaxRow(): void
    {
        $crud = $this->crud();
        self::filter($crud, 'invoices_description', 'html_escape_test_link_filter', 'function');
        $row = ['invoices_id' => 5, 'invoices_type' => '1', 'invoices_description' => self::PAYLOAD, 'invoices_note' => null];
        $html = '<a href="view_invoice?id=5">&lt;img src=x onerror=alert(1)&gt;</a>';

        // the page: decorate_field() of the row
        $this->assertSame($html, $crud->decorate_field('invoices_description', $row));

        $crud->db->rows = [$row];
        ob_start();
        $crud->ajax_list_handler();
        $json = json_decode((string) ob_get_clean(), true);

        $this->assertIsArray($json);
        $this->assertSame($html, $json['cells'][0]['invoices_description:html'] ?? null, 'the decorated html of the column');
        // the plain value stays in the cells escaped (other columns' string filters paste it)
        $this->assertSame('&lt;img src=x onerror=alert(1)&gt;', $json['cells'][0]['invoices_description']);
        $this->assertSame([$row], $json['rows']);
    }

    /** simple and function filters run on the server for every ajax row, string filters stay a template */
    public function testOnlySimpleAndFunctionFilteredColumnsAreDecoratedOnTheServer(): void
    {
        $crud = $this->crud();
        self::filter($crud, 'invoices_id', '<a href="view_invoice?id=%invoices_id%">%value%</a>');
        self::filter($crud, 'invoices_type', ['1' => '<i class="fa fa-fw fa-check"></i>'], 'simple');
        self::filter($crud, 'invoices_description', 'html_escape_test_link_filter', 'function');

        $this->assertFalse($crud->has_server_decoration('invoices_id'), 'a string filter is filled in from the template');
        $this->assertTrue($crud->has_server_decoration('invoices_type'));
        $this->assertTrue($crud->has_server_decoration('invoices_description'));
        $this->assertFalse($crud->has_server_decoration('invoices_note'), 'no filter');

        $record = ['invoices_id' => 5, 'invoices_type' => '1', 'invoices_description' => 'a<b', 'invoices_note' => 'n'];
        $this->assertSame([
            'invoices_id' => '5',
            'invoices_type' => '1',
            'invoices_description' => 'a&lt;b',
            'invoices_note' => 'n',
            'invoices_type:html' => '<i class="fa fa-fw fa-check"></i>',
            'invoices_description:html' => '<a href="view_invoice?id=5">a&lt;b</a>',
        ], $crud->ajax_cells($record));

        // a hidden field has no column, so no decorated cell
        $crud->hide_field('invoices_type');
        $this->assertArrayNotHasKey('invoices_type:html', $crud->ajax_cells($record));
    }

    /** crud.js fills the row template's '%<field>:html%' placeholder from the decorated cell */
    public function testCrudJsFillsTheDecoratedCellIntoTheRowTemplate(): void
    {
        $crud = $this->crud();
        self::filter($crud, 'invoices_description', 'html_escape_test_link_filter', 'function');
        $crud->db->rows = [['invoices_id' => 5, 'invoices_description' => self::PAYLOAD]];
        ob_start();
        $crud->ajax_list_handler();
        $response = json_decode((string) ob_get_clean(), true);

        $out = $this->runCrudJs([
            'file' => self::packageDir() . '/public_html/js/crud.js',
            // what list_records() puts in the empty row for these two columns
            'template' => '<td>%invoices_id%</td><td>%invoices_description:html%</td>',
            'response' => $response,
            'fill_template' => '',
            'fill_cells' => [],
        ]);

        $this->assertSame(['<tr id="itemrow0"><td>5</td><td><a href="view_invoice?id=5">&lt;img src=x onerror=alert(1)&gt;</a></td></tr>'], $out['appended']);
    }

    /**
     * crud_domains_list's screenshot column: the linked thumbnail is built in php from the
     * row's id and hostname (the sql concat()ed it, hostname unescaped), the hostname url
     * encoded into the image url, everything escaped, the link the one the sql html made
     */
    public function testDomainsListScreenshotIsBuiltEscapedFromTheRow(): void
    {
        require_once self::packageDir() . '/src/crud/crud_domains_list.php';
        $hadModules = array_key_exists('modules', $GLOBALS);
        $savedModules = $GLOBALS['modules'] ?? null;
        $GLOBALS['modules'] = ['domains' => ['TABLE' => 'domains', 'PREFIX' => 'domain', 'TITLE' => 'Domains', 'TITLE_FIELD' => 'domain_hostname']];
        $row = ['screenshot' => 5, 'domain_id' => 5, 'domain_hostname' => '"><img src=x onerror=alert(1)> a&b.example.com'];
        try {
            $raw = crud_domains_list_screenshot('screenshot', '5', $row);
            self::withTf();
            $pretty = crud_domains_list_screenshot('screenshot', '5', $row);
        } finally {
            \MyAdmin\App::resetContainer();
            if ($hadModules) {
                $GLOBALS['modules'] = $savedModules;
            } else {
                unset($GLOBALS['modules']);
            }
        }
        $img = '<img src="https://shot.sh?w=300&amp;h=100&amp;img=%22%3E%3Cimg%20src%3Dx%20onerror%3Dalert%281%29%3E%20a%26b.example.com">';
        $this->assertSame('<a href="index.php?choice=none.view_domain&amp;id=5">' . $img . '</a>', $raw);
        // with the app, the link is made the way Crud::make_link_url() makes it (SEO_LINKS)
        $this->assertSame('<a href="view_domain?id=5">' . $img . '</a>', $pretty);

        $source = (string) file_get_contents(self::packageDir() . '/src/crud/crud_domains_list.php');
        $this->assertStringNotContainsString("concat('<a", $source, 'the sql no longer builds the html');
        $this->assertStringContainsString("{\$settings['PREFIX']}_id as screenshot", $source);
        $this->assertStringContainsString("->add_filter('screenshot', 'crud_domains_list_screenshot', 'function')", $source);
    }

    /**
     * crud_coupons' customer column: the login linked to edit_customer when the coupon's
     * account exists, else the label or the id, all escaped (the sql concat()ed the link with
     * the login unescaped)
     */
    public function testCouponsCustomerColumnIsBuiltEscapedFromTheRow(): void
    {
        require_once self::packageDir() . '/src/crud/crud_coupons.php';
        self::withTf();
        try {
            $linked = crud_coupons_customer('customer', 'x', ['customer' => 'a"><svg onload=alert(2)>@example.com', 'customer_link_id' => '1234']);
            $label = crud_coupons_customer('customer', 'x', ['customer' => 'All Customers', 'customer_link_id' => '0']);
            $id = crud_coupons_customer('customer', 'x', ['customer' => '777', 'customer_link_id' => 0]);
            $text = crud_coupons_customer('customer', 'x', ['customer' => '<b>x</b>', 'customer_link_id' => null]);
        } finally {
            \MyAdmin\App::resetContainer();
        }
        $this->assertSame('<a href="edit_customer?customer=1234">a&quot;&gt;&lt;svg onload=alert(2)&gt;@example.com</a>', $linked);
        $this->assertSame('All Customers', $label);
        $this->assertSame('777', $id);
        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', $text);

        $source = (string) file_get_contents(self::packageDir() . '/src/crud/crud_coupons.php');
        $this->assertStringNotContainsString('IF(account_lid is not null,concat(', $source, 'the sql no longer builds the link');
        $this->assertStringContainsString('IF(account_lid is not null,account_lid,customer))) as customer,', $source);
        $this->assertStringContainsString('as customer_link_id FROM coupons', $source);
        $this->assertStringContainsString("->hide_field('customer_link_id')", $source);
        $this->assertStringContainsString("->add_filter('customer', 'crud_coupons_customer', 'function')", $source);
    }

    /**
     * a 'function' crud's ajax list read the db handle instead of its CrudFunctionIterator, so
     * sorting, paging or searching it emptied the list: it answers with the function's rows,
     * escaped in the cells but for its raw html columns
     */
    public function testAFunctionListAnswersTheAjaxListWithItsRows(): void
    {
        self::withTf();
        try {
            $crud = $this->crud();
            $crud->type = 'function';
            $crud->query = 'html_escape_test_function_rows';
            $crud->table = 'html_escape_test_function_rows';
            $crud->tables = [];
            $crud->set_raw_html_fields(['link']);
            ob_start();
            $crud->ajax_list_handler();
            $json = json_decode((string) ob_get_clean(), true);
        } finally {
            \MyAdmin\App::resetContainer();
        }
        $this->assertIsArray($json);
        $this->assertSame(html_escape_test_function_rows(), $json['rows']);
        $this->assertSame([
            ['id' => '5', 'link' => '<a href="view_invoice?id=5">5</a>', 'description' => '&lt;img src=x onerror=alert(1)&gt;'],
            ['id' => '6', 'link' => '<a href="view_invoice?id=6">6</a>', 'description' => 'plain &amp; simple'],
        ], $json['cells']);
    }

    /**
     * a 'function' list whose function returns no rows: next_record() ran foreach over the
     * iterator's null Record, a warning on the page and on every ajax refill
     */
    public function testAFunctionListWithNoRowsRaisesNoWarning(): void
    {
        $warnings = [];
        set_error_handler(static function ($errno, $errstr) use (&$warnings) {
            $warnings[] = "[{$errno}] {$errstr}";
            return true;
        });
        self::withTf();
        try {
            $crud = $this->crud();
            $crud->type = 'function';
            $crud->query = 'html_escape_test_no_rows';
            $crud->table = 'html_escape_test_no_rows';
            $crud->tables = [];
            ob_start();
            $crud->ajax_list_handler();
            $json = json_decode((string) ob_get_clean(), true);
            // the page's loop (list_records(), get_all_rows()) reads the same next_record()
            $crud->run_list_query();
            $rows = 0;
            while ($crud->next_record(MYSQL_ASSOC)) {
                $rows++;
            }
        } finally {
            \MyAdmin\App::resetContainer();
            restore_error_handler();
        }
        $this->assertSame([], $warnings);
        $this->assertSame(['rows' => [], 'cells' => []], $json);
        $this->assertSame(0, $rows);
    }

    /**
     * the json the templates print inside their inline <script> (crud_rows, crud_search_terms):
     * no < > & ' " a stored or requested value could close or derail the script block with,
     * an invalid utf-8 byte as U+FFFD (json_encode() without flags failed, and the template
     * printed 'var crud_rows = ;'), and the same value for javascript
     */
    public function testInlineJsonIsSafeInsideAScriptBlockAndReadsTheSame(): void
    {
        $rows = [
            ['invoices_id' => 5, 'invoices_description' => "</script><!--<script>alert(1)//&'\"", 'invoices_note' => null, 'list' => ['a', 'b']],
            ['invoices_id' => 6, 'invoices_description' => "bad\xC3(x", 'invoices_note' => 1.5, 'list' => []],
        ];

        $json = Crud::inline_json($rows, '[]');

        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
        $this->assertStringNotContainsString('&', $json);
        $this->assertStringNotContainsString("'", $json);
        // the escapes javascript reads back: < > & ' and a " inside a value (only the json's own
        // string quotes stay), each as a backslash-u escape
        $bs = chr(92);
        $esc = static function (string $char) use ($bs): string {
            return $bs . 'u' . sprintf('%04X', ord($char));
        };
        $this->assertStringContainsString($esc('<') . $bs . '/script' . $esc('>') . $esc('<') . '!--' . $esc('<') . 'script' . $esc('>') . 'alert(1)' . $bs . '/' . $bs . '/' . $esc('&') . $esc("'") . $esc('"') . '"', $json);
        // what javascript gets: the rows as stored, an invalid byte as U+FFFD
        $expected = $rows;
        $expected[1]['invoices_description'] = "bad\u{FFFD}(x";
        $this->assertSame($expected, json_decode($json, true));
        // the plain encoding of the same rows (what {$rows|json_encode} printed when it could)
        $this->assertSame(json_decode((string) json_encode($expected), true), json_decode($json, true));
        $this->assertSame('[]', Crud::inline_json([], '[]'));
        // a value json_encode() still cannot encode prints the fallback, never nothing
        $this->assertSame('[]', Crud::inline_json([NAN], '[]'));
        $this->assertSame('null', Crud::inline_json([INF]));
    }

    /** the templates print the pre-encoded json, no json_encode modifier without flags */
    public function testTheTemplatesPrintThePreEncodedInlineJson(): void
    {
        foreach (glob(self::packageDir() . '/public_html/templates/crud/*.tpl') as $file) {
            $source = (string) file_get_contents($file);
            $this->assertStringNotContainsString('json_encode', $source, basename($file));
        }
        foreach (['table5.tpl', 'tableAdminLte.tpl', 'tableAdminLteMaterial.tpl'] as $template) {
            $source = (string) file_get_contents(self::packageDir() . '/public_html/templates/crud/' . $template);
            $this->assertSame(1, substr_count($source, 'var crud_rows = {$rows_json};'), $template);
            $this->assertSame(1, substr_count($source, 'var crud_search_terms = {$search_terms};'), $template);
        }
        $crud = (string) file_get_contents(self::packageDir() . '/src/Crud.php');
        $this->assertStringContainsString("'rows_json' => self::inline_json(\$rows, '[]'),", $crud);
        $this->assertStringContainsString("'search_terms' => self::inline_json(\$this->search_terms, '[]'),", $crud);
    }

    /**
     * runs $fn under an error handler that records every deprecation, warning and notice (php
     * 8.1 deprecates NULL passed to a string parameter of a builtin); [what $fn returned, the
     * diagnostics]
     *
     * @return array{0: mixed, 1: string[]}
     */
    private static function withDiagnostics(callable $fn): array
    {
        $diagnostics = [];
        set_error_handler(static function ($errno, $errstr, $errfile = '', $errline = 0) use (&$diagnostics) {
            $diagnostics[] = "[{$errno}] {$errstr} at " . basename((string) $errfile) . ":{$errline}";
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED | E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE);
        try {
            $result = $fn();
        } finally {
            restore_error_handler();
        }
        return [$result, $diagnostics];
    }

    /**
     * a field's decorated html three ways: decorate_field() of the row (the page), its ajax
     * cell (ajax_cells()) and the cell the ajax list answered with (ajax_list_handler())
     *
     * @return array{0: mixed, 1: ?string, 2: ?string}
     */
    private static function decoratedThreeWays(Crud $crud, string $field, array $row): array
    {
        $crud->db->rows = [$row];
        ob_start();
        try {
            $crud->ajax_list_handler();
        } finally {
            $json = json_decode((string) ob_get_clean(), true);
        }
        return [
            $crud->decorate_field($field, $row),
            $crud->ajax_cells($row)[$field . Crud::DECORATED_CELL_SUFFIX] ?? null,
            $json['cells'][0][$field . Crud::DECORATED_CELL_SUFFIX] ?? null,
        ];
    }

    /**
     * a 'simple' filter on a NULL cell (crud_licenses_list's invoices_paid is a LEFT JOIN, NULL
     * for a licence with no type-1 invoice) handed NULL to str_replace(), a php 8.1 deprecation
     * for every such cell of the page and, since the ajax list decorates these columns, of
     * every refill. the cell is '' as it was; every other value is replaced as before
     */
    public function testASimpleFilterTakesANullCellWithoutADeprecation(): void
    {
        $icons = ['1' => '<i class="fa fa-fw fa-check"></i>', '2' => '<i class="fa fa-fw fa-times"></i>'];
        foreach (['filtering on' => true, 'filtering off' => false] as $case => $filtering) {
            $crud = $this->crud();
            $crud->set_use_html_filtering($filtering);
            self::filter($crud, 'invoices_type', $icons, 'simple');
            $row = ['invoices_id' => 5, 'invoices_type' => null, 'invoices_description' => 'a&b', 'invoices_note' => null];

            [$out, $diagnostics] = self::withDiagnostics(static function () use ($crud, $row) {
                return self::decoratedThreeWays($crud, 'invoices_type', $row);
            });

            $this->assertSame([], $diagnostics, $case);
            $this->assertSame(['', '', ''], $out, $case);

            // any other value as before: str_replace() converted a scalar to the same string
            $values = [['1', $icons['1']], [2, $icons['2']], ['3', '3'], [0, '0'], ['', ''], ['x1y', 'x' . $icons['1'] . 'y']];
            foreach ($values as [$value, $expected]) {
                $row['invoices_type'] = $value;
                [$out, $diagnostics] = self::withDiagnostics(static function () use ($crud, $row) {
                    return self::decoratedThreeWays($crud, 'invoices_type', $row);
                });
                $this->assertSame([], $diagnostics, $case . ' ' . var_export($value, true));
                $this->assertSame([$expected, $expected, $expected], $out, $case . ' ' . var_export($value, true));
            }
        }
    }

    /**
     * the crud package's other 'function' filters (crud_coupons, crud_domains_list,
     * crud_request_log) take a NULL cell without a deprecation, through decorate_field() and
     * the ajax cells alike: each casts or defaults what it reads (decorate_cc is in
     * CrudCardMaskTest). a pin: they needed no change
     */
    public function testTheCrudPagesOtherFunctionFiltersTakeANullCell(): void
    {
        require_once self::packageDir() . '/src/crud/crud_coupons.php';
        require_once self::packageDir() . '/src/crud/crud_domains_list.php';
        self::loadRequestLog();
        $hadModules = array_key_exists('modules', $GLOBALS);
        $savedModules = $GLOBALS['modules'] ?? null;
        $GLOBALS['modules'] = ['domains' => ['TABLE' => 'domains', 'PREFIX' => 'domain', 'TITLE' => 'Domains', 'TITLE_FIELD' => 'domain_hostname']];
        // the link the filters make without the app (see testDomainsListScreenshotIsBuiltEscapedFromTheRow)
        \MyAdmin\App::resetContainer();
        $cases = [
            // a coupon whose customer column is NULL (customer itself NULL): no link
            'crud_coupons_customer' => ['customer', ['id' => 3, 'customer' => null, 'customer_link_id' => 0], ''],
            // a domain with no hostname: the thumbnail of nothing, linked to the domain
            'crud_domains_list_screenshot' => ['screenshot', ['screenshot' => 5, 'domain_id' => 5, 'domain_hostname' => null],
                '<a href="index.php?choice=none.view_domain&amp;id=5"><img src="https://shot.sh?w=300&amp;h=100&amp;img="></a>'],
            'request_log_decorate' => ['request_result', ['request_timestamp' => '2026-11-27 00:00:00', 'request_request' => null, 'request_result' => null], ''],
        ];
        try {
            foreach ($cases as $function => [$field, $row, $expected]) {
                $crud = $this->crud();
                self::filter($crud, $field, $function, 'function');
                [$out, $diagnostics] = self::withDiagnostics(static function () use ($crud, $field, $row) {
                    return self::decoratedThreeWays($crud, $field, $row);
                });
                $this->assertSame([], $diagnostics, $function);
                $this->assertSame([$expected, $expected, $expected], $out, $function);
            }
        } finally {
            if ($hadModules) {
                $GLOBALS['modules'] = $savedModules;
            } else {
                unset($GLOBALS['modules']);
            }
        }
    }

    /**
     * runs crud.js's get_crud_url() and crud_export() in node against a stub jQuery whose
     * #paginationForm action is $input['action'], with crud_search_terms = $input['terms'].
     * skips when node is not installed.
     *
     * @return array{list: string, export: string, json: string}
     */
    private function runCrudJsUrls(array $input): array
    {
        $node = trim((string) shell_exec('command -v node 2>/dev/null'));
        if ($node === '') {
            $this->markTestSkipped('node is not installed');
        }
        $harness = <<<'JS'
const fs = require('fs'), vm = require('vm');
const input = JSON.parse(fs.readFileSync(0, 'utf8'));
const chain = new Proxy(function () {}, { get: () => chain, apply: () => chain });
// jQuery('#paginationForm').attr('action'), and crud_export()'s jQuery(that).parent().attr('data-type')
const jq = new Proxy(function (sel) {
    if (sel === '#paginationForm')
        return { attr: (name) => name === 'action' ? input.action : undefined };
    if (sel !== null && typeof sel === 'object' && 'exportFormat' in sel)
        return { parent: () => ({ attr: (name) => name === 'data-type' ? sel.exportFormat : undefined }) };
    return chain;
}, { get: (target, prop) => prop === 'fn' ? {} : chain });
const sandbox = {
    jQuery: jq, $: jq, console: { log() {} }, window: {},
    document: { getElementById: () => null, location: { pathname: '/', search: '' } },
    // what the crud templates declare inline
    crud_order_by: 'invoices_id', crud_order_dir: 'desc', crud_page_offset: 0, crud_page_limit: 25,
};
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(input.file, 'utf8'), sandbox);
sandbox.crud_search_terms = input.terms;
const list = vm.runInContext('get_crud_url();', sandbox);
sandbox.crud_export({ exportFormat: 'csv' });
process.stdout.write(JSON.stringify({ list: list, export: String(sandbox.window.location), json: JSON.stringify(input.terms) }));
JS;
        $proc = proc_open([$node, '-e', $harness], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proc);
        fwrite($pipes[0], json_encode($input));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($proc), $stderr);
        $out = json_decode((string) $stdout, true);
        $this->assertIsArray($out);
        return $out;
    }

    /**
     * crud.js put the search terms' json into the list and export urls unencoded: a '&' (AT&T)
     * split it into another parameter and cut the json, a '+' came back a space, a '#' cut the
     * url (the server got no search, or a broken one) and a '%' could be read as an escape.
     * the decoded search parameter is the json now, and the crud reads the terms back from it
     * through core's request escaping (< > & as entities)
     */
    public function testTheSearchTermsAreUrlEncodedIntoTheListAndExportUrls(): void
    {
        $terms = [
            ['invoices_description', '=', 'AT&T'],
            ['invoices_note', 'in', ['a+b c', '100%', '#5', '50%25', '<b>"x"</b> é', 'k=v&limit=1']],
        ];
        $out = $this->runCrudJsUrls([
            'file' => self::packageDir() . '/public_html/js/crud.js',
            'action' => 'ajax.php?choice=crud&crud=crud_test&action=list',
            'terms' => $terms,
        ]);
        $this->assertSame($terms, json_decode($out['json'], true));

        foreach (['list' => [], 'export' => ['format' => 'csv']] as $case => $extra) {
            $url = $out[$case];
            $this->assertNull(parse_url($url, PHP_URL_FRAGMENT), $case . ': no # cuts the url');
            parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
            $this->assertSame([
                'choice' => 'crud',
                'crud' => 'crud_test',
                'action' => $case,
                'order_by' => 'invoices_id',
                'order_dir' => 'desc',
                'offset' => '0',
                'limit' => '25',
                'search' => $out['json'],
            ] + $extra, $params, $case);

            // the crud reads the terms back from the request as core hands it over: the decoded
            // parameter with < > & escaped (MyAdmin\Variables)
            $savedRequest = $_REQUEST;
            try {
                \MyAdmin\App::resetContainer();
                $_REQUEST = (new \MyAdmin\Variables())->inner_loop($params);
                $crud = $this->crud();
                $crud->apply_request_data();
            } finally {
                $_REQUEST = $savedRequest;
            }
            $this->assertSame($terms, $crud->search_terms, $case);
            $this->assertSame('crud_test', $crud->choice, $case);
        }

        // no search terms: no search parameter, as before
        $out = $this->runCrudJsUrls([
            'file' => self::packageDir() . '/public_html/js/crud.js',
            'action' => 'ajax.php?choice=crud&crud=crud_test&action=list',
            'terms' => [],
        ]);
        $this->assertSame('ajax.php?choice=crud&crud=crud_test&action=list&order_by=invoices_id&order_dir=desc&offset=0&limit=25', $out['list']);
        $this->assertSame('ajax.php?choice=crud&crud=crud_test&action=export&order_by=invoices_id&order_dir=desc&offset=0&limit=25&format=csv', $out['export']);
    }

    /** one invalid utf-8 byte in a stored value made json_encode() fail and the ajax answer empty */
    public function testAnInvalidUtf8ByteDoesNotBlankTheAjaxList(): void
    {
        $crud = $this->crud();
        $crud->db->rows = [
            ['invoices_id' => 5, 'invoices_description' => "bad\xC3(x"],
            ['invoices_id' => 6, 'invoices_description' => 'fine'],
        ];
        ob_start();
        $crud->ajax_list_handler();
        $json = json_decode((string) ob_get_clean(), true);

        $this->assertIsArray($json, 'the answer is json');
        $this->assertCount(2, $json['rows']);
        $this->assertSame("bad\u{FFFD}(x", $json['rows'][0]['invoices_description']);
        $this->assertSame("bad\u{FFFD}(x", $json['cells'][0]['invoices_description']);
        $this->assertSame('fine', $json['cells'][1]['invoices_description']);
    }
}
