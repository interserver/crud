<?php

use MyCrud\Crud;
use PHPUnit\Framework\TestCase;

// has_acl() is core's (include/admin_rbac.php), not loaded here: guard-defined reading
// $GLOBALS['_test_has_acl'], the suite's convention
if (!function_exists('has_acl')) {
    function has_acl($acl)
    {
        return $GLOBALS['_test_has_acl'] ?? true;
    }
}
// slugify() is core's too (include/decorators.php): ajax_export_handler() names the download
// file with it
if (!function_exists('slugify')) {
    function slugify($string)
    {
        return strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', (string) $string), '-'));
    }
}
if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', 1);
}
if (!defined('MYSQL_NUM')) {
    define('MYSQL_NUM', 2);
}

/**
 * the session the crud reads (App::ima() from tf->ima, App::session()->account_id), and
 * function_requirements(), which asks App::tf()
 */
class TenantLimitTestTf
{
    public $ima = 'client';

    public function function_requirements($function)
    {
        return true;
    }
}

class TenantLimitTestSession implements \MyAdmin\App\Contracts\SessionInterface
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
        return '';
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

class TenantLimitTestCrud extends Crud
{
    public $logged = [];
    /** @var array<int, string> the level of each $logged message, by its index */
    public $logged_levels = [];

    public function log($message, $line = '', $file = '', $level = 'info')
    {
        $this->logged[] = $message;
        $this->logged_levels[count($this->logged) - 1] = $level;
    }

    // the edit handler's csrf check needs a MyAdmin session; CrudEditGuardTest covers it
    protected function verify_write_csrf()
    {
        return true;
    }
}

/**
 * records every query (get_table_details() runs on a clone, so the log is static) and answers
 * 'show full columns' with the vps columns
 */
class TenantLimitTestDb
{
    /** @var string[] */
    public static $queries = [];
    /** @var array<string, array[]> regex => rows */
    public static $results = [];
    public $Record = [];
    private $pending = [];

    public function real_escape($value)
    {
        return addslashes((string) $value);
    }

    public function query($query, $line = '', $file = '')
    {
        self::$queries[] = $query;
        $this->pending = [];
        foreach (self::$results as $regex => $rows) {
            if (preg_match($regex, $query)) {
                $this->pending = $rows;
                break;
            }
        }
        return true;
    }

    public function next_record($type = null)
    {
        if (count($this->pending) == 0) {
            return false;
        }
        $this->Record = array_shift($this->pending);
        return true;
    }

    public function f($field)
    {
        return $this->Record[$field] ?? 0;
    }

    public function num_rows()
    {
        return count($this->pending);
    }
}

/**
 * set_limit_custid_role() was dead code: pages chain it after init(), and the only place that
 * read the role was apply_request_data(), which init() runs first; the *_custid tenant term
 * was pushed into the user's search_terms at the same time, where set_default_search()
 * replaced it and an all fields search ORed it away. so an admin without list_all saw every
 * account's rows on the 30 role pages. the decision is now remade whenever the role is set,
 * and the tenant terms are built per query, ANDed after the user's terms.
 */
class CrudTenantLimitTest extends TestCase
{
    /** @var array */
    private $savedRequest;

    /** @var \Psr\Container\ContainerInterface|null */
    private $savedContainer;

    protected function setUp(): void
    {
        $this->savedRequest = $_REQUEST;
        try {
            $this->savedContainer = \MyAdmin\App::container();
        } catch (\RuntimeException $e) {
            $this->savedContainer = null;
        }
        TenantLimitTestDb::$queries = [];
        TenantLimitTestDb::$results = [
            '/^show full columns from vps$/' => [
                ['Field' => 'vps_id', 'Type' => 'int(11) unsigned', 'Key' => 'PRI', 'Comment' => ''],
                ['Field' => 'vps_custid', 'Type' => 'int(11)', 'Key' => 'MUL', 'Comment' => ''],
                ['Field' => 'vps_hostname', 'Type' => 'varchar(255)', 'Key' => '', 'Comment' => ''],
                ['Field' => 'vps_status', 'Type' => 'varchar(20)', 'Key' => '', 'Comment' => ''],
            ],
            // crud_paypal_history's table: its account column is plain custid, which the default
            // custid_match (/_custid$/) does not find, so with that match it has no account
            // column to limit an account to
            '/^show full columns from paypal$/' => [
                ['Field' => 'id', 'Type' => 'int(11) unsigned', 'Key' => 'PRI', 'Comment' => ''],
                ['Field' => 'custid', 'Type' => 'int(10) unsigned', 'Key' => 'MUL', 'Comment' => ''],
                ['Field' => 'txn_id', 'Type' => 'varchar(50)', 'Key' => '', 'Comment' => ''],
                ['Field' => 'lid', 'Type' => 'varchar(255)', 'Key' => '', 'Comment' => ''],
                ['Field' => 'payment_gross', 'Type' => 'decimal(10,2)', 'Key' => '', 'Comment' => ''],
            ],
            '/^select count/' => [[0 => 0, 'count(*)' => 0]],
        ];
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->savedRequest;
        unset($GLOBALS['_test_has_acl']);
        if ($this->savedContainer !== null) {
            \MyAdmin\App::setContainer($this->savedContainer);
        } else {
            \MyAdmin\App::resetContainer();
        }
    }

    /**
     * a 'table' crud of vps set up the way init() does it: apply_request_data() reads the
     * session (admin or client, account id) and the request, then the table's columns
     */
    private function crud(bool $admin, int $custid, array $request = [], string $table = 'vps'): TenantLimitTestCrud
    {
        $_REQUEST = $request + ['choice' => 'none.crud_vps'];
        $tf = new TenantLimitTestTf();
        $tf->ima = $admin ? 'admin' : 'client';
        $session = new TenantLimitTestSession();
        $session->account_id = $custid;
        \MyAdmin\App::setContainer(
            \MyAdmin\App\Testing\TestContainerBuilder::make()
                ->withTf($tf)
                ->withSession($session)
                ->withVariables(new \MyAdmin\Variables())
                ->build()
        );
        $rc = new ReflectionClass(TenantLimitTestCrud::class);
        /** @var TenantLimitTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        $crud->db = new TenantLimitTestDb();
        $crud->page_limit = 25;
        $crud->apply_request_data();
        $this->assertSame($admin, $crud->admin);
        $crud->type = 'table';
        $crud->table = $table;
        $crud->tables[$table] = $crud->get_table_details($table);
        $crud->fields = array_keys($crud->tables[$table]);
        $crud->primary_key = $crud->fields[0];
        $crud->order_by = $crud->fields[0];
        $crud->order_dir = 'desc';
        $crud->page_offset = 0;
        return $crud;
    }

    /** the list query and count query the crud runs now */
    private function listAndCount(TenantLimitTestCrud $crud): array
    {
        TenantLimitTestDb::$queries = [];
        $crud->run_list_query();
        $list = end(TenantLimitTestDb::$queries);
        $count = $crud->get_count();
        return [$list, end(TenantLimitTestDb::$queries), $count];
    }

    public function testAnAdminWithoutTheRoleIsLimitedToTheirOwnAccount(): void
    {
        $crud = $this->crud(true, 7);
        // the page chains the role after init()
        $GLOBALS['_test_has_acl'] = false;
        $crud->set_limit_custid_role('list_all');

        [$list, $count] = $this->listAndCount($crud);

        $this->assertSame("select * from vps where vps_custid=7 order by vps_id desc limit 0, 25", $list);
        // the count (and so the pages and total_rows) follows the same limit
        $this->assertSame('select count(*) from vps where vps_custid=7', $count);
    }

    public function testAnAdminHoldingTheRoleIsNotLimited(): void
    {
        $crud = $this->crud(true, 7);
        $GLOBALS['_test_has_acl'] = true;
        $crud->set_limit_custid_role('list_all');

        [$list, $count] = $this->listAndCount($crud);

        $this->assertSame('select * from vps order by vps_id desc limit 0, 25', $list);
        $this->assertSame('select count(*) from vps', $count);
    }

    public function testAnAdminOnAPageWithoutARoleIsNotLimited(): void
    {
        $crud = $this->crud(true, 7);
        $GLOBALS['_test_has_acl'] = false;

        [$list] = $this->listAndCount($crud);

        $this->assertSame('select * from vps order by vps_id desc limit 0, 25', $list);
    }

    public function testTheRequestCustomerWinsOverTheRole(): void
    {
        foreach ([true, false] as $hasAcl) {
            $GLOBALS['_test_has_acl'] = $hasAcl;
            $crud = $this->crud(true, 7, ['custid' => '1234']);
            $crud->set_limit_custid_role('list_all');

            [$list, $count] = $this->listAndCount($crud);

            $this->assertSame('select * from vps where vps_custid=1234 order by vps_id desc limit 0, 25', $list, $hasAcl ? 'with list_all' : 'without list_all');
            $this->assertSame('select count(*) from vps where vps_custid=1234', $count);
        }
    }

    public function testAClientsQueriesAreUnchanged(): void
    {
        foreach ([true, false] as $hasAcl) {
            $GLOBALS['_test_has_acl'] = $hasAcl;
            $crud = $this->crud(false, 1234, ['search' => json_encode([['vps_status', '=', 'active']])]);
            $crud->set_limit_custid_role('list_all');

            [$list] = $this->listAndCount($crud);

            // the same sql as before: the user's terms, then the tenant term
            $this->assertSame("select * from vps where vps_status='active' and vps_custid=1234 order by vps_id desc limit 0, 25", $list);

            $crud = $this->crud(false, 1234);
            [$list] = $this->listAndCount($crud);
            $this->assertSame('select * from vps where vps_custid=1234 order by vps_id desc limit 0, 25', $list);
        }
    }

    public function testAnAllFieldsSearchCannotOrTheTenantTermAway(): void
    {
        $GLOBALS['_test_has_acl'] = true;
        // an admin viewing one customer, searching every field (the term with no field name)
        $crud = $this->crud(true, 7, ['custid' => '1234', 'search' => json_encode([['', '=', 'web1']])]);
        $crud->set_limit_custid_role('list_all');

        [$list, $count] = $this->listAndCount($crud);

        $where = "where (vps.vps_hostname like '%web1%' or vps.vps_status like '%web1%') and vps_custid=1234";
        $this->assertSame("select * from vps {$where} order by vps_id desc limit 0, 25", $list);
        $this->assertSame("select count(*) from vps {$where}", $count);
    }

    public function testSetDefaultSearchAfterInitCannotDropTheTenantTerm(): void
    {
        $crud = $this->crud(false, 1234);
        $crud->set_default_search([['vps_status', '=', 'active']]);

        [$list] = $this->listAndCount($crud);

        $this->assertSame("select * from vps where vps_status='active' and vps_custid=1234 order by vps_id desc limit 0, 25", $list);
    }

    public function testTheRoleDecisionDoesNotDependOnTheChainOrder(): void
    {
        $GLOBALS['_test_has_acl'] = false;
        // the role set, then a custid_match set after it: both apply when the query runs
        $crud = $this->crud(true, 7);
        $crud->set_limit_custid_role('list_all');
        $crud->set_custid_match('/^vps_custid$/');
        [$list] = $this->listAndCount($crud);
        $this->assertSame('select * from vps where vps_custid=7 order by vps_id desc limit 0, 25', $list);

        // the role cleared again (false: no role) lifts the limit
        $crud->set_limit_custid_role(false);
        [$list] = $this->listAndCount($crud);
        $this->assertSame('select * from vps order by vps_id desc limit 0, 25', $list);
    }

    public function testTheTenantTermsAreNotUserSearchTerms(): void
    {
        $crud = $this->crud(false, 1234);
        $this->assertSame([], $crud->search_terms);
        $this->assertSame([['vps_custid', '=', 1234]], $crud->tenant_search_terms());
        // a crud whose account id is not a number shows nothing, it does not drop the limit
        $crud->custid = 'not-a-number';
        $this->assertStringContainsString('0=1', $crud->search_to_sql());
    }

    public function testTheEditOfAnAdminWithoutTheRoleIsLimitedToTheirOwnAccount(): void
    {
        $GLOBALS['_test_has_acl'] = false;
        $crud = $this->crud(true, 7);
        $crud->set_limit_custid_role('list_all');
        $crud->edit_row = true;
        $crud->query_fields = ['vps_id' => 'vps_id', 'vps_hostname' => 'vps_hostname'];
        $crud->validations = ['vps_id' => ['int'], 'vps_hostname' => []];
        $crud->query_where = ['vps' => []];
        $savedPost = $_POST;
        try {
            $_POST = ['vps_id' => '5', 'vps_hostname' => 'web1.example.com'];
            TenantLimitTestDb::$queries = [];
            ob_start();
            $crud->ajax_edit_handler();
            ob_end_clean();
        } finally {
            $_POST = $savedPost;
        }
        $this->assertSame(["update vps set vps_hostname='web1.example.com' where vps_id='5' and vps_custid='7'"], TenantLimitTestDb::$queries);
    }

    /**
     * runs the edit handler on $_POST = $post the way the edit modal posts, returns [what the
     * handler printed, the queries it ran]
     *
     * @return array{0: string, 1: string[]}
     */
    private function postEdit(TenantLimitTestCrud $crud, array $post): array
    {
        $savedPost = $_POST;
        try {
            $_POST = $post;
            TenantLimitTestDb::$queries = [];
            ob_start();
            $crud->ajax_edit_handler();
            $out = (string) ob_get_clean();
        } finally {
            $_POST = $savedPost;
        }
        return [$out, TenantLimitTestDb::$queries];
    }

    /**
     * posts an edit of vps row 5 on a list whose query selects the account column (select
     * vps.vps_id, vps.vps_custid, vps.vps_hostname, vps.vps_status from vps: the parsed query
     * maps each field to its table column; $accountField is the name the list selects
     * vps.vps_custid as), $post holding the other fields' values (the edit form posts every
     * field), $stored answering the read the handler may make. returns [printed, queries]
     *
     * @return array{0: string, 1: string[]}
     */
    private function editVpsFields(TenantLimitTestCrud $crud, array $post, ?array $stored = null, string $accountField = 'vps_custid'): array
    {
        if ($stored !== null) {
            TenantLimitTestDb::$results = ['/^select `/' => [$stored]] + TenantLimitTestDb::$results;
        }
        $crud->edit_row = true;
        $crud->query_fields = ['vps_id' => 'vps.vps_id', $accountField => 'vps.vps_custid', 'vps_hostname' => 'vps.vps_hostname', 'vps_status' => 'vps.vps_status'];
        $crud->validations = ['vps_id' => ['int'], $accountField => [], 'vps_hostname' => [], 'vps_status' => []];
        $crud->query_where = ['vps' => []];
        return $this->postEdit($crud, ['vps_id' => '5'] + $post);
    }

    /** the warnings the edit handler logged for a posted account column holding another account */
    private static function accountColumnWarnings(TenantLimitTestCrud $crud): array
    {
        $warnings = [];
        foreach ($crud->logged as $index => $message) {
            if (strpos((string) $message, 'would move the row off account') !== false && ($crud->logged_levels[$index] ?? null) === 'warning') {
                $warnings[] = $message;
            }
        }
        return $warnings;
    }

    /** what the edit handler answers an edit that posts another account in an account column */
    private const ACCOUNT_REFUSAL = 'The account of this row cannot be changed here.';

    /**
     * the tenant term pins only the edit's where: every posted field the query maps went into
     * the set, so a client posting another account in the account column moved the row to it
     * (update vps set vps_custid='9', ... where vps_id='5' and vps_custid='1234'). such an edit
     * is refused whole now, with a warning: nothing is written (writing the other fields and
     * answering ok told the user the account had changed). the row's own account posted back
     * (as the edit form does on every save) is left out of the set, without one, and the rest
     * of the edit is written
     */
    public function testAClientsEditCannotMoveTheRowToAnotherAccount(): void
    {
        $crud = $this->crud(false, 1234);
        $crud->allow_client_edit();

        [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => '9', 'vps_hostname' => 'web1.example.com']);
        $this->assertSame(self::ACCOUNT_REFUSAL, $out);
        $this->assertSame([], $queries);
        $this->assertCount(1, self::accountColumnWarnings($crud));

        // selected under an alias (vps.vps_custid as owner): the column it maps to decides
        $crud->logged = [];
        [$out, $queries] = $this->editVpsFields($crud, ['owner' => '9', 'vps_hostname' => 'web1.example.com'], null, 'owner');
        $this->assertSame(self::ACCOUNT_REFUSAL, $out, 'an alias');
        $this->assertSame([], $queries, 'an alias');
        $this->assertCount(1, self::accountColumnWarnings($crud));

        // nothing but another account: refused the same way
        $crud->logged = [];
        [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => '9']);
        $this->assertSame(self::ACCOUNT_REFUSAL, $out);
        $this->assertSame([], $queries);
        $this->assertCount(1, self::accountColumnWarnings($crud));

        // the row's own account posted back: left out, no warning, the rest written
        $crud->logged = [];
        [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => '1234', 'vps_hostname' => 'web1.example.com']);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame(["update vps set vps_hostname='web1.example.com' where vps_id='5' and vps_custid='1234'"], $queries);
        $this->assertSame([], self::accountColumnWarnings($crud));

        // nothing but the row's own account: nothing to write
        [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => '1234']);
        $this->assertSame('There was nothing to update', $out);
        $this->assertSame([], $queries);
    }

    /**
     * an admin lacking the page's role is pinned to their own account or to the requested
     * customer the same way, and could move the row out of it by posting the account column
     * (on vps and on the paypal lists' plain custid column alike). such an edit is refused
     * whole; the pinned account posted back is left out of the set, and a field posted back as
     * the U+FFFD form of its stored value is still left out too (the two leave-outs keep each
     * other's fields apart)
     */
    public function testARoleLimitedAdminsEditCannotMoveTheRowToAnotherAccount(): void
    {
        $GLOBALS['_test_has_acl'] = false;
        foreach (['no custid' => [[], 7], 'a requested customer' => [['custid' => '1234'], 1234]] as $case => [$request, $pinned]) {
            $crud = $this->crud(true, 7, $request);
            $crud->set_limit_custid_role('list_all');

            [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => '9', 'vps_hostname' => 'web1.example.com']);

            $this->assertSame(self::ACCOUNT_REFUSAL, $out, $case);
            $this->assertSame([], $queries, $case);
            $this->assertCount(1, self::accountColumnWarnings($crud), $case);

            // the pinned account posted back: the rest is written
            $crud->logged = [];
            [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => (string) $pinned, 'vps_hostname' => 'web1.example.com']);
            $this->assertStringStartsWith('ok', $out, $case);
            $this->assertSame(["update vps set vps_hostname='web1.example.com' where vps_id='5' and vps_custid='{$pinned}'"], $queries, $case);
            $this->assertSame([], self::accountColumnWarnings($crud), $case);
        }

        // the account column first (the pinned account, left out), then a field posted back
        // as stored (left out), then a change
        $crud = $this->crud(true, 7);
        $crud->set_limit_custid_role('list_all');
        $stored = ['vps_hostname' => "web\xC3(1"];
        $shown = json_decode((string) json_encode($stored, JSON_INVALID_UTF8_SUBSTITUTE), true);
        [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => '7', 'vps_hostname' => $shown['vps_hostname'], 'vps_status' => 'active'], $stored);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame([
            "select `vps_hostname` from vps where vps_id='5' and vps_custid='7' limit 1",
            "update vps set vps_status='active' where vps_id='5' and vps_custid='7'",
        ], $queries);

        // the account column holding U+FFFD itself is another account: refused before the
        // stored values are read
        [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => "9\u{FFFD}", 'vps_hostname' => $shown['vps_hostname'], 'vps_status' => 'active'], $stored);
        $this->assertSame(self::ACCOUNT_REFUSAL, $out);
        $this->assertSame([], $queries);

        // crud_paypal_history / crud_paypal_transactions: the account column is paypal's custid
        $crud = $this->crud(true, 7, ['custid' => '1234'], 'paypal');
        $crud->set_limit_custid_role('list_all');
        $crud->set_custid_match('/^custid$/');
        $crud->edit_row = true;
        $crud->query_fields = ['id' => 'id', 'custid' => 'custid', 'txn_id' => 'txn_id'];
        $crud->validations = ['id' => ['int'], 'custid' => [], 'txn_id' => []];
        $crud->query_where = ['paypal' => []];
        [$out, $queries] = $this->postEdit($crud, ['id' => '5', 'custid' => '9', 'txn_id' => 'TX-1']);
        $this->assertSame(self::ACCOUNT_REFUSAL, $out);
        $this->assertSame([], $queries);
        $this->assertCount(1, self::accountColumnWarnings($crud));

        $crud->logged = [];
        [$out, $queries] = $this->postEdit($crud, ['id' => '5', 'custid' => '1234', 'txn_id' => 'TX-1']);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame(["update paypal set txn_id='TX-1' where id='5' and custid='1234'"], $queries);
        $this->assertSame([], self::accountColumnWarnings($crud));
    }

    /**
     * posts an edit of vps row 5 on a list whose query is $sql, its fields mapped by the real
     * parse_query() (as init() maps a page's query), each selected field validated as nothing
     * but vps_id (an int). returns [printed, queries]
     *
     * @return array{0: string, 1: string[]}
     */
    private function editParsedVps(TenantLimitTestCrud $crud, string $sql, array $post): array
    {
        $crud->query = $sql;
        $crud->parse_query();
        $crud->edit_row = true;
        $crud->validations = array_fill_keys(array_keys($crud->query_fields), []);
        $crud->validations['vps_id'] = ['int'];
        $crud->query_where = ['vps' => []];
        return $this->postEdit($crud, ['vps_id' => '5'] + $post);
    }

    /**
     * mysql resolves a column name in any letter case to the same column, and the edit writes
     * the column as the page's query spells it, but custid_match (/_custid$/) was matched only
     * as spelled: a query selecting vps.VPS_CUSTID (or vps.Vps_Custid as owner, or
     * `vps_custİd` with U+0130, which mysql folds to i like any capital) let a client or a role
     * limited admin move the row (update vps set VPS_CUSTID='9', ... where vps_id='5' and
     * vps_custid='1234'). the queries go through the real parse_query(), as a page's do; an
     * unlimited admin still moves the row, the column written as spelled
     */
    public function testAnAccountColumnTheQuerySpellsInAnotherLetterCaseIsStillLocked(): void
    {
        $sqls = [
            'upper case' => ['select vps.vps_id, vps.VPS_CUSTID, vps.vps_hostname, vps.vps_status from vps', 'VPS_CUSTID', 'VPS_CUSTID'],
            'mixed case under an alias' => ['select vps.vps_id, vps.Vps_Custid as owner, vps.vps_hostname, vps.vps_status from vps', 'owner', 'Vps_Custid'],
            'quoted, with U+0130' => ['select vps.vps_id, `vps`.`vps_custİd`, vps.vps_hostname, vps.vps_status from vps', 'vps_custİd', 'vps_custİd'],
            'upper case, no table, under an alias' => ['select vps_id, VPS_CUSTID as owner, vps_hostname, vps_status from vps', 'owner', 'VPS_CUSTID'],
        ];
        $post = ['vps_hostname' => 'web1.example.com', 'vps_status' => 'active'];
        foreach ($sqls as $case => [$sql, $field, $column]) {
            foreach (['a client' => [false, 1234], 'a role limited admin' => [true, 7]] as $who => [$admin, $pinned]) {
                $GLOBALS['_test_has_acl'] = false;
                $crud = $this->crud($admin, $pinned);
                if ($admin) {
                    $crud->set_limit_custid_role('list_all');
                } else {
                    $crud->allow_client_edit();
                }

                // the row's own account posted back: left out of the set (it was written, as spelled)
                [$out, $queries] = $this->editParsedVps($crud, $sql, [$field => (string) $pinned] + $post);

                // parse_query() mapped the posted field to the column as the query spells it
                $this->assertSame($column, preg_replace('/^.*\./', '', $crud->query_fields[$field]), "{$case}, {$who}");
                $this->assertSame(["update vps set vps_hostname='web1.example.com', vps_status='active' where vps_id='5' and vps_custid='{$pinned}'"], $queries, "{$case}, {$who}");
                $this->assertStringStartsWith('ok', $out, "{$case}, {$who}");
                $this->assertSame([], self::accountColumnWarnings($crud), "{$case}, {$who}");

                // another account: refused whole (the set moved the row to it)
                [$out, $queries] = $this->editParsedVps($crud, $sql, [$field => '9'] + $post);
                $this->assertSame([], $queries, "{$case}, {$who}");
                $this->assertSame(self::ACCOUNT_REFUSAL, $out, "{$case}, {$who}");
                $this->assertCount(1, self::accountColumnWarnings($crud), "{$case}, {$who}");
            }

            $GLOBALS['_test_has_acl'] = true;
            $crud = $this->crud(true, 7);
            $crud->set_limit_custid_role('list_all');
            [$out, $queries] = $this->editParsedVps($crud, $sql, [$field => '9'] + $post);
            $this->assertStringStartsWith('ok', $out, "{$case}, an unlimited admin");
            $this->assertSame(["update vps set {$column}='9', vps_hostname='web1.example.com', vps_status='active' where vps_id='5'"], $queries, "{$case}, an unlimited admin");
        }
    }

    /**
     * the list's and the edit's tenant terms decide which columns are account columns the same
     * way as the edit's lock: a schema that spells its account column in capitals (show full
     * columns answering VPS_CUSTID) got no tenant term, so a client's list showed every
     * account's rows and the client's edit was refused as on a table with no account column
     */
    public function testAnAccountColumnTheSchemaSpellsInCapitalsStillPinsTheListAndTheEdit(): void
    {
        TenantLimitTestDb::$results['/^show full columns from vps$/'] = [
            ['Field' => 'vps_id', 'Type' => 'int(11) unsigned', 'Key' => 'PRI', 'Comment' => ''],
            ['Field' => 'VPS_CUSTID', 'Type' => 'int(11)', 'Key' => 'MUL', 'Comment' => ''],
            ['Field' => 'vps_hostname', 'Type' => 'varchar(255)', 'Key' => '', 'Comment' => ''],
        ];
        $crud = $this->crud(false, 1234);
        [$list, $count] = $this->listAndCount($crud);
        $this->assertSame('select * from vps where VPS_CUSTID=1234 order by vps_id desc limit 0, 25', $list);
        $this->assertSame('select count(*) from vps where VPS_CUSTID=1234', $count);

        $crud->allow_client_edit();
        [$out, $queries] = $this->editParsedVps($crud, 'select vps.vps_id, vps.VPS_CUSTID, vps.vps_hostname from vps', ['VPS_CUSTID' => '1234', 'vps_hostname' => 'web1.example.com']);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame(["update vps set vps_hostname='web1.example.com' where vps_id='5' and VPS_CUSTID='1234'"], $queries);

        [$out, $queries] = $this->editParsedVps($crud, 'select vps.vps_id, vps.VPS_CUSTID, vps.vps_hostname from vps', ['VPS_CUSTID' => '9', 'vps_hostname' => 'web1.example.com']);
        $this->assertSame(self::ACCOUNT_REFUSAL, $out);
        $this->assertSame([], $queries);
    }

    /**
     * an invalid post reports its validation errors, as before the account column lock: a post
     * whose only written field was the account column, left out by the lock, answered 'There
     * was nothing to update' and lost its validation message. with another account posted
     * next to an invalid field the validation errors come first too; nothing is written
     */
    public function testAnInvalidEditOfTheAccountColumnStillReportsItsValidationErrors(): void
    {
        foreach (['a client' => [false, 1234], 'a role limited admin' => [true, 7]] as $who => [$admin, $pinned]) {
            $GLOBALS['_test_has_acl'] = false;
            $crud = $this->crud($admin, $pinned);
            if ($admin) {
                $crud->set_limit_custid_role('list_all');
            } else {
                $crud->allow_client_edit();
            }
            $crud->edit_row = true;
            $crud->query_fields = ['vps_id' => 'vps.vps_id', 'vps_custid' => 'vps.vps_custid', 'vps_hostname' => 'vps.vps_hostname'];
            $crud->validations = ['vps_id' => ['int'], 'vps_custid' => ['int'], 'vps_hostname' => ['notags']];
            $crud->query_where = ['vps' => []];

            // only the account column, invalid
            [$out, $queries] = $this->postEdit($crud, ['vps_id' => '5', 'vps_custid' => 'abc']);
            $this->assertSame('There was an error with validation:<br>Invalid Vps Custid &quot;abc&quot; with the fields vps_custid', $out, $who);
            $this->assertSame([], $queries, $who);

            // the row's own account and an invalid field
            [$out, $queries] = $this->postEdit($crud, ['vps_id' => '5', 'vps_custid' => (string) $pinned, 'vps_hostname' => '<b>x</b>']);
            $this->assertStringStartsWith('There was an error with validation:<br>Invalid ', $out, $who);
            $this->assertStringEndsWith(' with the fields vps_hostname', $out, $who);
            $this->assertSame([], $queries, $who);

            // another account and an invalid field: the validation errors, not the refusal (the
            // account warning is still logged)
            $crud->logged = [];
            [$out, $queries] = $this->postEdit($crud, ['vps_id' => '5', 'vps_custid' => '9', 'vps_hostname' => '<b>x</b>']);
            $this->assertStringStartsWith('There was an error with validation:<br>Invalid ', $out, $who);
            $this->assertStringEndsWith(' with the fields vps_hostname', $out, $who);
            $this->assertSame([], $queries, $who);
            $this->assertCount(1, self::accountColumnWarnings($crud), $who);
        }
    }

    /**
     * the other side: an admin who is not role limited keeps changing the account column
     * (moving a service to another account), unlimited or viewing a requested customer, holding
     * the page's role or on a page with no role
     */
    public function testAnAdminWhoIsNotRoleLimitedCanStillMoveARowToAnotherAccount(): void
    {
        $cases = [
            'holding the role, a requested customer' => [true, 'list_all', ['custid' => '1234'], " and vps_custid='1234'"],
            'holding the role, no custid' => [true, 'list_all', [], ''],
            'a page with no role, a requested customer' => [false, false, ['custid' => '1234'], " and vps_custid='1234'"],
            'a page with no role, no custid' => [false, false, [], ''],
        ];
        foreach ($cases as $case => [$hasAcl, $role, $request, $tenant]) {
            $GLOBALS['_test_has_acl'] = $hasAcl;
            $crud = $this->crud(true, 7, $request);
            $crud->set_limit_custid_role($role);

            [$out, $queries] = $this->editVpsFields($crud, ['vps_custid' => '9', 'vps_hostname' => 'web1.example.com']);

            $this->assertStringStartsWith('ok', $out, $case);
            $this->assertSame(["update vps set vps_custid='9', vps_hostname='web1.example.com' where vps_id='5'{$tenant}"], $queries, $case);
            $this->assertSame([], self::accountColumnWarnings($crud), $case);
        }
    }

    /**
     * posts an edit of the paypal row 5 the way the edit modal does, returns [what the
     * handler printed, the queries it ran]
     *
     * @return array{0: string, 1: string[]}
     */
    private function editPaypalRow(TenantLimitTestCrud $crud): array
    {
        $crud->edit_row = true;
        $crud->query_fields = ['id' => 'id', 'txn_id' => 'txn_id'];
        $crud->validations = ['id' => ['int'], 'txn_id' => []];
        $crud->query_where = ['paypal' => []];
        $savedPost = $_POST;
        try {
            $_POST = ['id' => '5', 'txn_id' => 'TX-1'];
            TenantLimitTestDb::$queries = [];
            ob_start();
            $crud->ajax_edit_handler();
            $out = (string) ob_get_clean();
        } finally {
            $_POST = $savedPost;
        }
        return [$out, TenantLimitTestDb::$queries];
    }

    /**
     * runs the add and delete handlers (stubs that write nothing yet) the way the modals post
     * to them, returns what each printed
     *
     * @return array{0: string, 1: string}
     */
    private function addAndDelete(TenantLimitTestCrud $crud): array
    {
        $savedPost = $_POST;
        try {
            $_POST = ['id' => '5'];
            ob_start();
            $crud->ajax_add_handler();
            $add = (string) ob_get_clean();
            ob_start();
            $crud->ajax_delete_handler();
            $delete = (string) ob_get_clean();
        } finally {
            $_POST = $savedPost;
        }
        return [$add, $delete];
    }

    /**
     * the list, count and export of a role limited admin on tables with no account column are
     * '0=1', the edit, add and delete refused, one warning logged
     */
    private function assertRoleLimitFailsClosed(array $request): void
    {
        $GLOBALS['_test_has_acl'] = false;
        $crud = $this->crud(true, 7, $request, 'paypal');
        $crud->set_limit_custid_role('list_all');

        [$list, $count] = $this->listAndCount($crud);

        $this->assertSame('select * from paypal where 0=1 order by id desc limit 0, 25', $list);
        $this->assertSame('select count(*) from paypal where 0=1', $count);
        // the exports read get_all_rows()
        TenantLimitTestDb::$queries = [];
        $crud->get_all_rows();
        $this->assertSame(['select * from paypal where 0=1 order by id desc limit 0, 25'], TenantLimitTestDb::$queries);

        [$out, $queries] = $this->editPaypalRow($crud);
        $this->assertSame('Editing Disabled', $out);
        $this->assertSame([], $queries, 'no update ran');
        $this->assertSame(['Adding Disabled', 'Deleting Disabled'], $this->addAndDelete($crud));

        // the list's warning is logged once for the crud, not per query
        $warnings = array_values(array_filter($crud->logged, static function ($message) {
            return strpos((string) $message, 'lists no rows: the admin lacks the list_all role') !== false;
        }));
        $this->assertCount(1, $warnings);
    }

    /**
     * a page whose role the admin lacks (crud_paypal_history: list_all), on tables with no
     * account column: no tenant term could be built, so the admin saw every account's rows.
     * now nothing: '0=1' in the list, the count and the export, the edit, add and delete
     * refused, a warning
     */
    public function testARoleLimitOnTablesWithNoAccountColumnFailsClosed(): void
    {
        $this->assertRoleLimitFailsClosed([]);
    }

    /**
     * a custid in the request lifted the role limit (is_role_limited() was false with one), so
     * an admin lacking the role who added &custid=N to such a page got every row again, in the
     * list, the count and the export, and an update by any id: it fails closed the same way
     */
    public function testARequestedCustomerDoesNotLiftTheRoleLimitOnTablesWithNoAccountColumn(): void
    {
        $this->assertRoleLimitFailsClosed(['custid' => '1234']);
    }

    /**
     * the other side: a role holder (with or without a requested customer) and a client list
     * and edit such tables as before (a client's catalogs keep working), and add and delete
     * are not refused for them
     */
    public function testTablesWithNoAccountColumnAreAsBeforeForEveryoneElse(): void
    {
        $GLOBALS['_test_has_acl'] = true;
        $crud = $this->crud(true, 7, [], 'paypal');
        $crud->set_limit_custid_role('list_all');
        [$list, $count] = $this->listAndCount($crud);
        $this->assertSame('select * from paypal order by id desc limit 0, 25', $list, 'a role holder');
        $this->assertSame('select count(*) from paypal', $count);
        [$out, $queries] = $this->editPaypalRow($crud);
        $this->assertSame(["update paypal set txn_id='TX-1' where id='5'"], $queries);
        $this->assertSame(['', ''], $this->addAndDelete($crud));

        // a role holder viewing a requested customer: still nothing to pin the rows to (the
        // default match finds no column), as before
        $crud = $this->crud(true, 7, ['custid' => '1234'], 'paypal');
        $crud->set_limit_custid_role('list_all');
        [$list] = $this->listAndCount($crud);
        $this->assertSame('select * from paypal order by id desc limit 0, 25', $list, 'a role holder viewing a requested customer');
        [$out, $queries] = $this->editPaypalRow($crud);
        $this->assertSame(["update paypal set txn_id='TX-1' where id='5'"], $queries);

        $GLOBALS['_test_has_acl'] = false;
        $crud = $this->crud(false, 1234, [], 'paypal');
        $crud->set_limit_custid_role('list_all');
        [$list, $count] = $this->listAndCount($crud);
        $this->assertSame('select * from paypal order by id desc limit 0, 25', $list, 'a client (a catalog)');
        $this->assertSame('select count(*) from paypal', $count);
        $this->assertSame([], array_values(array_filter($crud->logged, static function ($message) {
            return strpos((string) $message, 'lacks the') !== false;
        })));
    }

    /**
     * crud_paypal_history / crud_paypal_transactions set their account column match to paypal's
     * plain custid column: a requested customer pins the list, count, export and edit to that
     * customer (role holder or not), staff without list_all get their own rows without one,
     * and a role holder without one still lists every row
     */
    public function testTheAccountColumnMatchPinsThePaypalListsToTheCustomer(): void
    {
        $cases = [
            'lacking the role, no custid' => [false, [], 7],
            'lacking the role, a requested customer' => [false, ['custid' => '1234'], 1234],
            'holding the role, a requested customer' => [true, ['custid' => '1234'], 1234],
            'holding the role, no custid' => [true, [], null],
        ];
        foreach ($cases as $case => [$hasAcl, $request, $pinned]) {
            $GLOBALS['_test_has_acl'] = $hasAcl;
            $crud = $this->crud(true, 7, $request, 'paypal');
            // the pages' chain: the role, then the match
            $crud->set_limit_custid_role('list_all');
            $crud->set_custid_match('/^custid$/');

            [$list, $count] = $this->listAndCount($crud);
            TenantLimitTestDb::$queries = [];
            $crud->get_all_rows();
            $export = TenantLimitTestDb::$queries;
            [$out, $queries] = $this->editPaypalRow($crud);

            if ($pinned === null) {
                $this->assertSame('select * from paypal order by id desc limit 0, 25', $list, $case);
                $this->assertSame('select count(*) from paypal', $count, $case);
                $this->assertSame(["update paypal set txn_id='TX-1' where id='5'"], $queries, $case);
            } else {
                $this->assertSame("select * from paypal where custid={$pinned} order by id desc limit 0, 25", $list, $case);
                $this->assertSame("select count(*) from paypal where custid={$pinned}", $count, $case);
                $this->assertSame(["select * from paypal where custid={$pinned} order by id desc limit 0, 25"], $export, $case);
                $this->assertSame(["update paypal set txn_id='TX-1' where id='5' and custid='{$pinned}'"], $queries, $case);
            }
            $this->assertSame([], array_values(array_filter($crud->logged, static function ($message) {
                return strpos((string) $message, 'lacks the') !== false;
            })), "{$case}: a tenant term was built, nothing refused");
        }
    }

    /**
     * posts an edit of paypal row 5 with $post as the field values (the edit form posts every
     * field), $stored answering the read the handler may make (null: no row), returns [printed,
     * queries]
     *
     * @return array{0: string, 1: string[]}
     */
    private function editPaypalFields(TenantLimitTestCrud $crud, array $post, ?array $stored): array
    {
        TenantLimitTestDb::$results = ['/^select `/' => $stored === null ? [] : [$stored]] + TenantLimitTestDb::$results;
        $crud->edit_row = true;
        $crud->query_fields = ['id' => 'id', 'txn_id' => 'txn_id', 'lid' => 'lid', 'payment_gross' => 'payment_gross'];
        $crud->validations = ['id' => ['int'], 'txn_id' => [], 'lid' => [], 'payment_gross' => []];
        $crud->query_where = ['paypal' => []];
        $savedPost = $_POST;
        try {
            $_POST = ['id' => '5'] + $post;
            TenantLimitTestDb::$queries = [];
            ob_start();
            $crud->ajax_edit_handler();
            $out = (string) ob_get_clean();
        } finally {
            $_POST = $savedPost;
        }
        return [$out, TenantLimitTestDb::$queries];
    }

    /**
     * crud_rows carries U+FFFD in place of a stored value's invalid utf-8 bytes, and the edit
     * form posts every field back: a field posted as the substituted form of its stored value
     * was not touched and is not written over the stored bytes. the stored row is read by the
     * primary key under the same tenant terms as the update
     */
    public function testAnEditLeavesOutAFieldPostedBackAsTheSubstitutedFormOfItsStoredValue(): void
    {
        $GLOBALS['_test_has_acl'] = false;
        $crud = $this->crud(true, 7, [], 'paypal');
        $crud->set_limit_custid_role('list_all');
        $crud->set_custid_match('/^custid$/');
        $stored = ['txn_id' => "TX\xC3(1", 'lid' => "a\xFFb@example.com"];
        // what the page's crud_rows (and the ajax rows) hand the edit form
        $shown = json_decode((string) json_encode($stored, JSON_INVALID_UTF8_SUBSTITUTE), true);
        $this->assertSame("TX\u{FFFD}(1", $shown['txn_id']);

        // txn_id untouched, lid changed by the user (to a value still holding U+FFFD), the amount typed in
        [$out, $queries] = $this->editPaypalFields($crud, ['txn_id' => $shown['txn_id'], 'lid' => "b\u{FFFD}@example.com", 'payment_gross' => '9.99'], $stored);

        $this->assertStringStartsWith('ok', $out);
        $this->assertSame([
            // the stored values of the fields posted with U+FFFD, under the update's own where
            "select `txn_id`, `lid` from paypal where id='5' and custid='7' limit 1",
            "update paypal set lid='b\u{FFFD}@example.com', payment_gross='9.99' where id='5' and custid='7'",
        ], $queries);

        // every field posted back as stored: nothing to write
        [$out, $queries] = $this->editPaypalFields($crud, ['txn_id' => $shown['txn_id'], 'lid' => $shown['lid']], $stored);
        $this->assertSame('There was nothing to update', $out);
        $this->assertSame(["select `txn_id`, `lid` from paypal where id='5' and custid='7' limit 1"], $queries);
    }

    /** a stored value that is valid utf-8 (U+FFFD itself included) and a normal edit are written as before */
    public function testAnEditWritesValidUtf8AndNormalValuesAsBefore(): void
    {
        $GLOBALS['_test_has_acl'] = true;
        $crud = $this->crud(true, 7, [], 'paypal');
        $crud->set_limit_custid_role('list_all');

        // a stored value that really holds U+FFFD, posted unchanged: written, as any field is
        [$out, $queries] = $this->editPaypalFields($crud, ['txn_id' => "TX\u{FFFD}1"], ['txn_id' => "TX\u{FFFD}1"]);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame(["select `txn_id` from paypal where id='5' limit 1", "update paypal set txn_id='TX\u{FFFD}1' where id='5'"], $queries);

        // no U+FFFD posted: no read, the update alone
        [$out, $queries] = $this->editPaypalFields($crud, ['txn_id' => 'TX-2', 'lid' => 'c@example.com'], ['txn_id' => "TX\xC3(1"]);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame(["update paypal set txn_id='TX-2', lid='c@example.com' where id='5'"], $queries);

        // the row not found under the where (another account's, a deleted one): written as
        // before, the update's where decides
        [$out, $queries] = $this->editPaypalFields($crud, ['txn_id' => "TX\u{FFFD}(1"], null);
        $this->assertSame(["select `txn_id` from paypal where id='5' limit 1", "update paypal set txn_id='TX\u{FFFD}(1' where id='5'"], $queries);
    }

    /**
     * what the edit handler answers an edit whose U+FFFD primary key names no row under the
     * edit's where: no cause, since the key may be the substituted form of one that is not
     * valid utf-8 or really hold U+FFFD with its row excluded by query_where or the tenant terms
     */
    private const KEY_REFUSAL = 'This row cannot be edited here.';

    /**
     * a 'table' crud of 'blobs', a table keyed by a binary string (blob_key) with an account
     * column (blob_custid), and the edit of $post, the stored row found ($found) or not by the
     * row lookup and $stored answering the stored values read. returns [printed, queries]
     *
     * @return array{0: string, 1: string[]}
     */
    private function editBlob(TenantLimitTestCrud $crud, array $post, bool $found, ?array $stored = null): array
    {
        TenantLimitTestDb::$results = [
            '/^select 1 from /' => $found ? [[1 => '1']] : [],
            '/^select `/' => $stored === null ? [] : [$stored],
        ] + TenantLimitTestDb::$results;
        $crud->edit_row = true;
        $crud->query_fields = ['blob_key' => 'blob_key', 'blob_custid' => 'blob_custid', 'blob_note' => 'blob_note', 'blob_label' => 'blob_label'];
        $crud->validations = ['blob_key' => [], 'blob_custid' => [], 'blob_note' => ['notags'], 'blob_label' => []];
        $crud->query_where = ['blobs' => ["blob_label!='locked'"]];
        return $this->postEdit($crud, $post);
    }

    /** a 'table' crud of 'blobs' (see editBlob()) */
    private function blobCrud(bool $admin, int $custid): TenantLimitTestCrud
    {
        TenantLimitTestDb::$results['/^show full columns from blobs$/'] = [
            ['Field' => 'blob_key', 'Type' => 'varbinary(16)', 'Key' => 'PRI', 'Comment' => ''],
            ['Field' => 'blob_custid', 'Type' => 'int(11)', 'Key' => 'MUL', 'Comment' => ''],
            ['Field' => 'blob_note', 'Type' => 'varchar(255)', 'Key' => '', 'Comment' => ''],
            ['Field' => 'blob_label', 'Type' => 'varchar(255)', 'Key' => '', 'Comment' => ''],
        ];
        $crud = $this->crud($admin, $custid, [], 'blobs');
        $this->assertSame('blob_key', $crud->primary_key);
        return $crud;
    }

    /** the warnings the edit handler logged for a U+FFFD primary key that names no row */
    private static function keyWarnings(TenantLimitTestCrud $crud): array
    {
        $warnings = [];
        foreach ($crud->logged as $index => $message) {
            if (strpos((string) $message, 'holds U+FFFD') !== false && ($crud->logged_levels[$index] ?? null) === 'warning') {
                $warnings[] = $message;
            }
        }
        return $warnings;
    }

    /**
     * a row whose primary key holds bytes that are not valid utf-8 is listed with U+FFFD in
     * their place (JSON_INVALID_UTF8_SUBSTITUTE), and the edit form posts that back: the update
     * named no row (where blob_key='ab\u{FFFD}cd'), wrote nothing, and answered 'ok updated'.
     * such an edit looks the row up first, under the update's whole where (the key, the table's
     * query_where, the tenant terms): not found, nothing is written, a warning is logged and
     * the user is told
     */
    public function testAnEditWhoseSubstitutedKeyNamesNoRowIsRefused(): void
    {
        $stored = "ab\xFFcd";
        $shown = json_decode((string) json_encode(['blob_key' => $stored], JSON_INVALID_UTF8_SUBSTITUTE), true)['blob_key'];
        $this->assertSame("ab\u{FFFD}cd", $shown);

        // a client, limited to their own account
        $crud = $this->blobCrud(false, 1234);
        $crud->allow_client_edit();
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $shown, 'blob_custid' => '1234', 'blob_note' => 'new note', 'blob_label' => 'L1'], false);
        $this->assertSame(self::KEY_REFUSAL, $out);
        $this->assertSame(["select 1 from blobs where blob_key='ab\u{FFFD}cd' and blob_label!='locked' and blob_custid='1234' limit 1"], $queries, 'the lookup alone, no update');
        $this->assertCount(1, self::keyWarnings($crud));

        // an admin with no limit: the key and the table's where
        $crud = $this->blobCrud(true, 7);
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $shown, 'blob_note' => 'new note'], false);
        $this->assertSame(self::KEY_REFUSAL, $out);
        $this->assertSame(["select 1 from blobs where blob_key='ab\u{FFFD}cd' and blob_label!='locked' limit 1"], $queries);
        $this->assertCount(1, self::keyWarnings($crud));
    }

    /**
     * a key that really stores U+FFFD (valid utf-8) is found by the lookup and saved as before;
     * a key without U+FFFD runs no lookup; an invalid post still reports its validation errors
     * first, and an edit moving the row to another account its refusal, with nothing read; a
     * field posted back as the substituted form of its stored value is still left out
     */
    public function testAnEditWhoseSubstitutedKeyNamesARowIsWritten(): void
    {
        $key = "ab\u{FFFD}cd";
        $where = "blob_key='ab\u{FFFD}cd' and blob_label!='locked' and blob_custid='1234'";
        $crud = $this->blobCrud(false, 1234);
        $crud->allow_client_edit();

        // found: written
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $key, 'blob_custid' => '1234', 'blob_note' => 'new note'], true);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame(["select 1 from blobs where {$where} limit 1", "update blobs set blob_note='new note' where {$where}"], $queries);
        $this->assertSame([], self::keyWarnings($crud));

        // no U+FFFD in the key: no lookup, the update alone
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => 'abcd', 'blob_custid' => '1234', 'blob_note' => 'new note'], false);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame(["update blobs set blob_note='new note' where blob_key='abcd' and blob_label!='locked' and blob_custid='1234'"], $queries);

        // an invalid post: its validation errors, nothing read or written
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $key, 'blob_custid' => '1234', 'blob_note' => '<b>x</b>'], false);
        $this->assertStringStartsWith('There was an error with validation:', $out);
        $this->assertSame([], $queries);

        // another account posted: the account refusal, nothing read or written
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $key, 'blob_custid' => '9', 'blob_note' => 'new note'], false);
        $this->assertSame(self::ACCOUNT_REFUSAL, $out);
        $this->assertSame([], $queries);
        // the row's own account and nothing else: nothing to write, nothing read
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $key, 'blob_custid' => '1234'], false);
        $this->assertSame('There was nothing to update', $out);
        $this->assertSame([], $queries);

        // found, the note posted back as the substituted form of its stored bytes: left out
        $stored = ['blob_note' => "n\xFFo"];
        $shown = json_decode((string) json_encode($stored, JSON_INVALID_UTF8_SUBSTITUTE), true);
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $key, 'blob_custid' => '1234', 'blob_note' => $shown['blob_note'], 'blob_label' => 'L2'], true, $stored);
        $this->assertStringStartsWith('ok', $out);
        $this->assertSame([
            "select 1 from blobs where {$where} limit 1",
            "select `blob_note` from blobs where {$where} limit 1",
            "update blobs set blob_label='L2' where {$where}",
        ], $queries);
        $this->assertSame([], self::keyWarnings($crud));
    }

    /**
     * a key that really stores U+FFFD (valid utf-8) finds no row either when the table's
     * query_where or the tenant terms exclude its row: another account's row for a client, a
     * 'locked' row the page's where leaves out. the answer and the log line must hold there
     * too: they said the key could not be read and was the substituted form of a stored key
     * that is not valid utf-8, which was false here. they name no cause now
     */
    public function testAnEditWhoseRealU0FFFDKeyTheWhereExcludesIsRefusedWithoutNamingACause(): void
    {
        $key = "ab\u{FFFD}cd";
        $this->assertTrue(mb_check_encoding($key, 'UTF-8'), 'a real U+FFFD, valid utf-8');
        $expectedLog = "refused crud crud_vps edit of blobs: no row matched the edit's where, whose primary key blob_key holds U+FFFD";

        // a client: the row is another account's, so the tenant term excludes it
        $crud = $this->blobCrud(false, 1234);
        $crud->allow_client_edit();
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $key, 'blob_custid' => '1234', 'blob_note' => 'new note'], false);
        $this->assertSame('This row cannot be edited here.', $out);
        $this->assertSame(["select 1 from blobs where blob_key='ab\u{FFFD}cd' and blob_label!='locked' and blob_custid='1234' limit 1"], $queries, 'the lookup alone, no update');
        $this->assertSame([$expectedLog], self::keyWarnings($crud));

        // an unlimited admin: the row is 'locked', so the page's query_where excludes it
        $crud = $this->blobCrud(true, 7);
        [$out, $queries] = $this->editBlob($crud, ['blob_key' => $key, 'blob_note' => 'new note'], false);
        $this->assertSame('This row cannot be edited here.', $out);
        $this->assertSame(["select 1 from blobs where blob_key='ab\u{FFFD}cd' and blob_label!='locked' limit 1"], $queries);
        $this->assertSame([$expectedLog], self::keyWarnings($crud));
        foreach ([$out, $expectedLog] as $text) {
            $this->assertStringNotContainsString('could not be read', $text);
            $this->assertStringNotContainsString('not valid utf-8', $text);
            $this->assertStringNotContainsString('substituted', $text);
        }
    }

    /**
     * the list an admin opened with a custid is limited to that customer, and its ajax urls
     * (paging, searching, exporting: the list action; the edit and delete actions) carry the
     * same custid, as requested (a uuid too), so they stay on that customer
     */
    public function testTheUrlArgsCarryTheCustomerAnAdminOpenedTheListWith(): void
    {
        $uuid = '0196bd0a-5b4c-7e2a-91d3-f1a4c7b90e55';
        $crud = $this->crud(true, 7, ['custid' => $uuid]);
        $this->assertSame('&custid=' . $uuid, $crud->get_extra_url_args());
        // after the page's own args
        $crud->set_extra_url_args('&db=domains&table=currencies');
        $this->assertSame('&db=domains&table=currencies&custid=' . $uuid, $crud->get_extra_url_args());
        // a page whose own args name the custid keeps them, not doubled
        $crud->set_extra_url_args('&custid=55&id=3');
        $this->assertSame('&custid=55&id=3', $crud->get_extra_url_args());
        // url encoded
        $crud = $this->crud(true, 7, ['custid' => '12 34+5']);
        $this->assertSame('&custid=12%2034%2B5', $crud->get_extra_url_args());
        // not a string (custid[]=1)
        $crud = $this->crud(true, 7, ['custid' => ['1']]);
        $this->assertSame('', $crud->get_extra_url_args());
        // an admin without one
        $crud = $this->crud(true, 7);
        $crud->set_extra_url_args('&id=3');
        $this->assertSame('&id=3', $crud->get_extra_url_args());
    }

    /** a client's custid is ignored, as for the list itself: a client is always their own account */
    public function testAClientsRequestCustidIsNotCarried(): void
    {
        $crud = $this->crud(false, 1234, ['custid' => '999']);
        $this->assertSame('', $crud->get_extra_url_args());
        [$list] = $this->listAndCount($crud);
        $this->assertSame('select * from vps where vps_custid=1234 order by vps_id desc limit 0, 25', $list);
    }

    /**
     * runs $fn with its output buffered under an error handler that records every warning,
     * notice and deprecation; a throwable is caught and named. returns [printed, diagnostics,
     * throwable or null]
     *
     * @return array{0: string, 1: string[], 2: ?string}
     */
    private static function diagnosed(callable $fn): array
    {
        $diagnostics = [];
        set_error_handler(static function ($errno, $errstr, $errfile = '', $errline = 0) use (&$diagnostics) {
            $diagnostics[] = "[{$errno}] {$errstr} at " . basename((string) $errfile) . ":{$errline}";
            return true;
        });
        $level = ob_get_level();
        ob_start();
        $thrown = null;
        try {
            $fn();
        } catch (\Throwable $e) {
            $thrown = get_class($e) . ': ' . $e->getMessage();
        } finally {
            $out = '';
            while (ob_get_level() > $level) {
                $out = (string) ob_get_clean() . $out;
            }
            restore_error_handler();
        }
        return [$out, $diagnostics, $thrown];
    }

    /**
     * the crud of crud() for $request, built and run by $run under diagnosed(): [the crud or
     * null when building it threw, printed, diagnostics, throwable or null]
     *
     * @return array{0: ?TenantLimitTestCrud, 1: string, 2: string[], 3: ?string}
     */
    private function diagnosedCrud(bool $admin, array $request, callable $run): array
    {
        $crud = null;
        [$out, $diagnostics, $thrown] = self::diagnosed(function () use (&$crud, $admin, $request, $run) {
            $crud = $this->crud($admin, $admin ? 7 : 1234, $request);
            TenantLimitTestDb::$queries = [];
            $run($crud);
        });
        return [$crud, $out, $diagnostics, $thrown];
    }

    /** the ajax request crud.js sends for the list (paging, sorting, searching) */
    private const AJAX_LIST = ['choice' => 'crud', 'crud' => 'crud_vps', 'action' => 'list'];

    /**
     * search[]=x comes through core's request filter as an array (MyAdmin\Variables::
     * inner_loop() recurses into arrays), and apply_request_data() handed it to
     * html_entity_decode(): a TypeError, so every crud page and every ajax call of a list
     * answered a fatal error for that request. a search that is not a string is malformed now,
     * no terms, the same as a string that is not json
     */
    public function testASearchThatIsNotAStringIsAMalformedSearch(): void
    {
        foreach ([['x'], [], ['a' => ['b' => 'c']], [['vps_status', '=', 'active']]] as $search) {
            foreach ([false, true] as $admin) {
                $label = json_encode($search) . ($admin ? ' admin' : ' client');
                $plain = $admin ? 'select * from vps order by vps_id desc limit 0, 25' : 'select * from vps where vps_custid=1234 order by vps_id desc limit 0, 25';
                $count = $admin ? 'select count(*) from vps' : 'select count(*) from vps where vps_custid=1234';

                // the page (the list and its count)
                [$crud, $out, $diagnostics, $thrown] = $this->diagnosedCrud($admin, ['search' => $search], function (TenantLimitTestCrud $crud) {
                    $crud->run_list_query();
                    $crud->get_count();
                });
                $this->assertNull($thrown, $label);
                $this->assertSame([], $diagnostics, $label);
                $this->assertSame([], $crud->search_terms, $label);
                $this->assertSame([$plain, $count], TenantLimitTestDb::$queries, $label);

                // the ajax list
                [$crud, $out, $diagnostics, $thrown] = $this->diagnosedCrud($admin, ['search' => $search] + self::AJAX_LIST, function (TenantLimitTestCrud $crud) {
                    $crud->go();
                });
                $this->assertNull($thrown, $label . ' ajax');
                $this->assertSame([], $diagnostics, $label . ' ajax');
                $this->assertSame([], $crud->search_terms, $label . ' ajax');
                $this->assertSame([$plain], TenantLimitTestDb::$queries, $label . ' ajax');
                $this->assertSame(['rows' => [], 'cells' => []], json_decode($out, true), $label . ' ajax');
            }
        }
        // a string that is not json: no terms, as before
        $crud = $this->crud(false, 1234, ['search' => 'x']);
        $this->assertSame([], $crud->search_terms);
        // a json string: its terms, as before
        $crud = $this->crud(false, 1234, ['search' => json_encode([['vps_status', '=', 'active']])]);
        [$list] = $this->listAndCount($crud);
        $this->assertSame("select * from vps where vps_status='active' and vps_custid=1234 order by vps_id desc limit 0, 25", $list);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function listRequestValueProvider(): array
    {
        $values = [];
        foreach (['search', 'order_by', 'order_dir', 'offset', 'limit', 'choice', 'crud', 'custid'] as $name) {
            $values[$name] = [$name];
        }
        return $values;
    }

    /**
     * every request value apply_request_data() reads for the list, sent as an array: no error,
     * warning or deprecation on the page (list and count) or the ajax list, for a client and an
     * admin, and the list still runs. search was the one that threw (above); the rest already
     * fell back: run_list_query() resets an order_by that is not a field, an order_dir that is
     * not asc or desc is not taken, (int) reads an array without a warning, a choice or crud
     * that is not a string is '' (choice_function_name()), and an admin's custid that is not a
     * string resolves to an id (core's resolve_account_id() gives 0) and is not carried into
     * the urls (get_extra_url_args()). pinned so a change to any of them shows up here
     *
     * @dataProvider listRequestValueProvider
     */
    public function testAListRequestValueSentAsAnArrayRaisesNoError(string $name): void
    {
        foreach ([false, true] as $admin) {
            $who = $admin ? 'admin' : 'client';
            // crud() sets the table's default order and offset after apply_request_data(): it
            // runs again here, so the request's own values are the ones the list reads
            [$crud, , $diagnostics, $thrown] = $this->diagnosedCrud($admin, [$name => ['x']], function (TenantLimitTestCrud $crud) {
                $crud->apply_request_data();
                $crud->run_list_query();
                $crud->get_count();
            });
            $this->assertNull($thrown, "{$name}[] page {$who}");
            $this->assertSame([], $diagnostics, "{$name}[] page {$who}");
            $this->assertCount(2, TenantLimitTestDb::$queries, "{$name}[] page {$who}");
            $this->assertMatchesRegularExpression('/^select \* from vps( where vps_custid=\d+)? order by vps_id (desc|asc) limit \d+, \d+$/', TenantLimitTestDb::$queries[0], "{$name}[] page {$who}");
            $this->assertSame([], $crud->search_terms);
            $this->assertSame('vps_id', $crud->order_by);
            $this->assertSame('desc', $crud->order_dir);
            $this->assertIsInt($crud->page_offset);
            $this->assertIsInt($crud->page_limit);
            $this->assertStringNotContainsString('custid', $crud->get_extra_url_args());

            if ($name === 'choice') {
                continue;
            }
            [$crud, $out, $diagnostics, $thrown] = $this->diagnosedCrud($admin, [$name => ['x']] + self::AJAX_LIST, function (TenantLimitTestCrud $crud) {
                $crud->apply_request_data();
                $crud->go();
            });
            $this->assertNull($thrown, "{$name}[] ajax {$who}");
            $this->assertSame([], $diagnostics, "{$name}[] ajax {$who}");
            $this->assertCount(1, TenantLimitTestDb::$queries, "{$name}[] ajax {$who}");
            $this->assertSame(['rows' => [], 'cells' => []], json_decode($out, true), "{$name}[] ajax {$who}");
        }
    }

    /**
     * format[]=x on the export call: isset($formats[$format]) with an array offset threw a
     * TypeError. a format that is not a scalar is answered 'Error' like an unknown one, with
     * nothing read
     */
    public function testAnExportFormatThatIsNotAScalarAnswersError(): void
    {
        foreach ([['csv'], [], 'nope'] as $format) {
            foreach ([false, true] as $admin) {
                $label = json_encode($format) . ($admin ? ' admin' : ' client');
                [, $out, $diagnostics, $thrown] = $this->diagnosedCrud($admin, ['action' => 'export', 'format' => $format] + self::AJAX_LIST, function (TenantLimitTestCrud $crud) {
                    $crud->go();
                });
                $this->assertNull($thrown, $label);
                $this->assertSame([], $diagnostics, $label);
                $this->assertSame('Error', $out, $label);
                $this->assertSame([], TenantLimitTestDb::$queries, $label);
            }
        }
    }

    /**
     * disable_export_button() set export_button to true, as enable_export_button() does, and
     * ajax_export_handler() never read the flag, which only hid the button: the export url
     * still sent every row of the list (review crud-r2 N4). disabling clears the flag now, and
     * a disabled list answers every export request 'Error', reads nothing and logs one warning
     */
    public function testADisabledExportIsRefused(): void
    {
        foreach ([false, true] as $admin) {
            foreach (['csv', 'json', 'xlsx', 'nope', ['csv']] as $format) {
                $label = json_encode($format) . ($admin ? ' admin' : ' client');
                [$crud, $out, $diagnostics, $thrown] = $this->diagnosedCrud($admin, ['action' => 'export', 'format' => $format] + self::AJAX_LIST, function (TenantLimitTestCrud $crud) {
                    $this->assertTrue($crud->export_button, 'export is on by default');
                    $this->assertSame($crud, $crud->disable_export_button());
                    $this->assertFalse($crud->export_button);
                    $crud->go();
                });
                $this->assertNull($thrown, $label);
                $this->assertSame([], $diagnostics, $label);
                $this->assertSame('Error', $out, $label);
                $this->assertSame([], TenantLimitTestDb::$queries, $label . ': nothing read');
                $this->assertSame(['refused crud crud_vps export, the export is disabled on this list'], $crud->logged, $label);
                $this->assertSame(['warning'], $crud->logged_levels, $label);
            }
        }
    }

    /**
     * the default (export on), and a list that enables it again, still export: the client's
     * own rows (the tenant term), as before
     */
    public function testAnEnabledExportStillSendsTheList(): void
    {
        $row = ['vps_id' => 5, 'vps_custid' => 1234, 'vps_hostname' => 'web1', 'vps_status' => 'active'];
        TenantLimitTestDb::$results = ['/^select \\* from vps /' => [$row]] + TenantLimitTestDb::$results;
        foreach (['default' => false, 'enabled again' => true] as $label => $reenable) {
            [$crud, $out, $diagnostics, $thrown] = $this->diagnosedCrud(false, ['action' => 'export', 'format' => 'json'] + self::AJAX_LIST, function (TenantLimitTestCrud $crud) use ($reenable) {
                if ($reenable) {
                    $crud->disable_export_button();
                    $this->assertSame($crud, $crud->enable_export_button());
                }
                $this->assertTrue($crud->export_button);
                $crud->go();
            });
            $this->assertNull($thrown, $label);
            // export_json() sends its headers, which the cli refuses once phpunit has printed
            $this->assertSame([], array_values(array_filter($diagnostics, static function ($d) {
                return strpos($d, 'Cannot modify header information') === false;
            })), $label);
            $this->assertSame([$row], json_decode($out, true), $label);
            $this->assertCount(1, TenantLimitTestDb::$queries, $label);
            $this->assertStringStartsWith('select * from vps where vps_custid=1234 ', TenantLimitTestDb::$queries[0], $label);
            $this->assertSame([], $crud->logged, $label);
        }
    }

    /**
     * an action ajax_handler() does not know (action[]=x, or any unknown string) went to its
     * default branch, which logged an undefined $action: a php warning for every such call and
     * no action in the log line. it logs the action now (a string, else its type), still as a
     * warning, and runs nothing
     */
    public function testAnUnknownActionIsLoggedWithoutAWarning(): void
    {
        foreach ([[['list'], '"array"'], ['bogus', '"bogus"'], [str_repeat('a', 80), '"' . str_repeat('a', 64) . '"']] as [$action, $logged]) {
            foreach ([false, true] as $admin) {
                $label = json_encode($action) . ($admin ? ' admin' : ' client');
                [$crud, $out, $diagnostics, $thrown] = $this->diagnosedCrud($admin, ['action' => $action] + self::AJAX_LIST, function (TenantLimitTestCrud $crud) {
                    $crud->title = 'Vps';
                    $crud->go();
                });
                $this->assertNull($thrown, $label);
                $this->assertSame([], $diagnostics, $label);
                $this->assertSame('', $out, $label);
                $this->assertSame([], TenantLimitTestDb::$queries, $label);
                $this->assertSame(['Invalid Crud Vps Action ' . $logged], $crud->logged, $label);
                $this->assertSame(['warning'], $crud->logged_levels, $label);
            }
        }
    }
}
