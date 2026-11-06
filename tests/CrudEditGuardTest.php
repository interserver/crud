<?php

use MyCrud\Crud;
use PHPUnit\Framework\TestCase;

class EditGuardTestCrud extends Crud
{
    public $logged = [];
    public $csrfOk = true;
    public $csrfChecked = 0;

    public function log($message, $line = '', $file = '', $level = 'info')
    {
        $this->logged[] = $message;
    }

    protected function verify_write_csrf()
    {
        $this->csrfChecked++;
        return $this->csrfOk;
    }
}

class EditGuardTestDb
{
    public $queries = [];

    public function real_escape($value)
    {
        return addslashes((string) $value);
    }

    public function query($query, $line = '', $file = '')
    {
        $this->queries[] = $query;
        return true;
    }
}

/**
 * The ajax edit handler writes by the posted primary key. It used to have no role
 * check, no csrf check and no tenant term, and echoed the sql back; on crud_vps /
 * crud_domains only an accidental array_merge(null) TypeError stopped a client from
 * posting vps_id=<victim>&vps_custid=<me>.
 */
class CrudEditGuardTest extends TestCase
{
    private $post;

    protected function setUp(): void
    {
        $this->post = $_POST;
    }

    protected function tearDown(): void
    {
        $_POST = $this->post;
    }

    /**
     * a crud over "select vps_id, vps_custid, vps_hostname from vps": a single table,
     * so (as in production) there is no query_where['vps'] entry.
     */
    private function crud(bool $admin, bool $limitCustid = false): EditGuardTestCrud
    {
        $rc = new ReflectionClass(EditGuardTestCrud::class);
        /** @var EditGuardTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        $crud->db = new EditGuardTestDb();
        $crud->admin = $admin;
        $crud->limit_custid = $limitCustid;
        $crud->custid = 1234;
        $crud->choice = 'crud_vps';
        $crud->primary_key = 'vps_id';
        $crud->tables = [
            'vps' => [
                'vps_id' => ['Type' => 'int(11)', 'Key' => 'PRI'],
                'vps_custid' => ['Type' => 'int(11)', 'Key' => ''],
                'vps_hostname' => ['Type' => 'varchar(255)', 'Key' => ''],
            ],
        ];
        $crud->query_fields = ['vps_id' => 'vps_id', 'vps_custid' => 'vps_custid', 'vps_hostname' => 'vps_hostname'];
        $crud->validations = ['vps_id' => [], 'vps_custid' => [], 'vps_hostname' => []];
        $crud->query_where = [];
        $crud->disabled_fields = [];
        return $crud;
    }

    private function edit(EditGuardTestCrud $crud, array $post): string
    {
        $_POST = $post;
        ob_start();
        $crud->ajax_edit_handler();
        return (string) ob_get_clean();
    }

    public function testClientIsRefusedBeforeAnyWrite(): void
    {
        $crud = $this->crud(false, true);
        $out = $this->edit($crud, ['vps_id' => '99', 'vps_custid' => '1234']);
        $this->assertSame('Not Authorized', $out);
        $this->assertSame([], $crud->db->queries);
        $this->assertSame(0, $crud->csrfChecked, 'the role check comes first');
    }

    public function testAdminWithoutValidCsrfIsRefused(): void
    {
        $crud = $this->crud(true);
        $crud->csrfOk = false;
        $out = $this->edit($crud, ['vps_id' => '99', 'vps_hostname' => 'x']);
        $this->assertStringContainsString('form token', $out);
        $this->assertSame([], $crud->db->queries);
    }

    public function testAdminWithTokenUpdatesByPrimaryKeyAndDoesNotEchoSql(): void
    {
        $crud = $this->crud(true);
        $out = $this->edit($crud, ['vps_id' => '99', 'vps_hostname' => 'host.example.com', 'csrf_token' => 't']);
        $this->assertSame(["update vps set vps_hostname='host.example.com' where vps_id='99'"], $crud->db->queries);
        $this->assertStringStartsWith('ok', $out);
        $this->assertStringNotContainsString('update', strtolower(str_replace('updated', '', $out)));
    }

    public function testUpdateWithoutPrimaryKeyIsRefused(): void
    {
        $crud = $this->crud(true);
        $out = $this->edit($crud, ['vps_hostname' => 'everything']);
        $this->assertSame('There was nothing to update', $out);
        $this->assertSame([], $crud->db->queries);
    }

    public function testPrimaryKeyMustBeAColumnOfTheWrittenTable(): void
    {
        // crud_pending_vps_list: vps.vps_id and vps_masters.vps_name, the write resolved to vps_masters
        $crud = $this->crud(true);
        $crud->tables['vps_masters'] = ['vps_name' => ['Type' => 'varchar(255)', 'Key' => '']];
        $crud->query_fields['vps_name'] = 'vps_masters.vps_name';
        $crud->validations['vps_name'] = [];
        $out = $this->edit($crud, ['vps_id' => '5', 'vps_name' => 'pwned']);
        $this->assertSame('There was nothing to update', $out);
        $this->assertSame([], $crud->db->queries);
    }

    public function testClientOptInIsPinnedToItsOwnRows(): void
    {
        $crud = $this->crud(false, true);
        $crud->allow_client_edit();
        $this->edit($crud, ['vps_id' => '99', 'vps_hostname' => 'mine']);
        $this->assertSame(["update vps set vps_hostname='mine' where vps_id='99' and vps_custid='1234'"], $crud->db->queries);
    }

    public function testClientOptInOnATableWithoutAnAccountColumnIsRefused(): void
    {
        $crud = $this->crud(false, true);
        $crud->allow_client_edit();
        $crud->tables['vps'] = ['vps_id' => ['Type' => 'int(11)', 'Key' => 'PRI'], 'vps_hostname' => ['Type' => 'varchar(255)', 'Key' => '']];
        unset($crud->query_fields['vps_custid']);
        $out = $this->edit($crud, ['vps_id' => '99', 'vps_hostname' => 'x']);
        $this->assertSame('Editing Disabled', $out);
        $this->assertSame([], $crud->db->queries);
    }

    public function testLimitedAdminListAlsoGetsTheTenantTerm(): void
    {
        $crud = $this->crud(true, true);
        $this->edit($crud, ['vps_id' => '99', 'vps_hostname' => 'h']);
        $this->assertSame(["update vps set vps_hostname='h' where vps_id='99' and vps_custid='1234'"], $crud->db->queries);
    }

    public function testValidationErrorsAreHtmlEscaped(): void
    {
        $crud = $this->crud(true);
        $crud->validations['vps_hostname'] = ['notags'];
        $out = $this->edit($crud, ['vps_id' => '99', 'vps_hostname' => '<img src=x onerror=alert(1)>']);
        $this->assertStringNotContainsString('<img', $out);
        $this->assertSame([], $crud->db->queries);
    }

    public function testDisabledEditStillShortCircuits(): void
    {
        $crud = $this->crud(true);
        $crud->disable_edit();
        $this->assertSame('Editing Disabled', $this->edit($crud, ['vps_id' => '1', 'vps_hostname' => 'x']));
        $this->assertSame(0, $crud->csrfChecked);
    }

    public function testAddAndDeleteNeedAdminAndCsrfToo(): void
    {
        foreach (['ajax_add_handler', 'ajax_delete_handler'] as $handler) {
            $crud = $this->crud(false);
            ob_start();
            $crud->{$handler}();
            $this->assertSame('Not Authorized', ob_get_clean(), $handler);
            $crud = $this->crud(true);
            $crud->csrfOk = false;
            ob_start();
            $crud->{$handler}();
            $this->assertStringContainsString('form token', ob_get_clean(), $handler);
        }
    }

    public function testRealCsrfCheckFailsClosedWithoutTheHelper(): void
    {
        if (function_exists('verify_csrf')) {
            $this->markTestSkipped('verify_csrf() is loaded in this process');
        }
        $rc = new ReflectionClass(Crud::class);
        $crud = $rc->newInstanceWithoutConstructor();
        $m = new ReflectionMethod(Crud::class, 'verify_write_csrf');
        $m->setAccessible(true);
        $this->assertFalse($m->invoke($crud));
    }

    public function testEditAndDeleteTemplatesCarryTheToken(): void
    {
        foreach (['tableAdminLte.tpl', 'table5.tpl'] as $tpl) {
            $src = (string) file_get_contents(__DIR__.'/../public_html/templates/crud/'.$tpl);
            $this->assertSame(2, substr_count($src, 'name="csrf_token" value="{$crud_csrf_token|escape}"'), $tpl);
        }
    }
}
