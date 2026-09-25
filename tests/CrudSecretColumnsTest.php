<?php

use MyCrud\Crud;
use PHPUnit\Framework\TestCase;

/**
 * A Crud that can be built without the MyAdmin runtime (no constructor, no
 * database), records what it would have logged, and captures queries.
 */
class SecretColumnsTestCrud extends Crud
{
    public $logged = [];

    public function log($message, $line = '', $file = '', $level = 'info')
    {
        $this->logged[] = $message;
    }
}

class SecretColumnsTestDb
{
    public $queries = [];
    public $Record = [];

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
 * Secret columns (service/root/ipmi passwords, card security codes) are shown
 * masked, can never be searched or sorted on, and an edit never writes the
 * mask back over the real value.
 */
class CrudSecretColumnsTest extends TestCase
{
    private function crud(): SecretColumnsTestCrud
    {
        $rc = new ReflectionClass(SecretColumnsTestCrud::class);
        /** @var SecretColumnsTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        $crud->db = new SecretColumnsTestDb();
        $crud->tables = [
            'domains' => [
                'domain_id' => ['Type' => 'int(11)', 'Key' => 'PRI'],
                'domain_hostname' => ['Type' => 'varchar(255)', 'Key' => ''],
                'domain_password' => ['Type' => 'varchar(30)', 'Key' => ''],
            ],
        ];
        $crud->fields = ['domain_id', 'domain_hostname', 'domain_password'];
        $crud->primary_key = 'domain_id';
        return $crud;
    }

    public function testSecretFieldDetection(): void
    {
        $crud = $this->crud();
        foreach (['domain_password', 'domains.domain_password', '`vps_rootpass`', 'qs_rootpass', 'assets.asset_password', 'ipmi_admin_password', 'ipmi_client_password', 'ipmi_admin_pass', 'cc_request_card_code', 'VPS_ROOTPASS'] as $field) {
            $this->assertTrue($crud->is_secret_field($field), $field);
        }
        foreach (['domain_hostname', 'domain_username', 'cc_result_card_code', 'cc_request_card_num', '', 'password_expire'] as $field) {
            $this->assertFalse($crud->is_secret_field($field), $field);
        }
    }

    public function testRecordsAreMaskedButEmptyValuesStayEmpty(): void
    {
        $crud = $this->crud();
        $out = $crud->mask_secret_fields(['domain_id' => 5, 'domain_password' => 'RealPass123', 'vps_rootpass' => '', 'qs_rootpass' => null, 'domain_hostname' => 'example.com']);
        $this->assertSame('********', $out['domain_password']);
        $this->assertSame('', $out['vps_rootpass']);
        $this->assertNull($out['qs_rootpass']);
        $this->assertSame('example.com', $out['domain_hostname']);
        $this->assertSame(5, $out['domain_id']);
    }

    public function testGetRecordMasksTheRowFromTheDatabase(): void
    {
        $crud = $this->crud();
        $crud->type = 'table';
        $crud->db->Record = ['domain_id' => 5, 'domain_hostname' => 'example.com', 'domain_password' => 'RealPass123'];
        $record = $crud->get_record();
        $this->assertSame('********', $record['domain_password']);
        $this->assertSame('example.com', $record['domain_hostname']);
    }

    public function testSearchingASecretFieldProducesNoClause(): void
    {
        $crud = $this->crud();
        $this->assertSame('', $crud->json_search_tosql('domain_password', '=', 'guess', true));
        $this->assertSame('', $crud->json_search_tosql('domains.domain_password', '=', 'guess', true));
        $this->assertSame('', $crud->json_search_tosql('domain_password', 'in', ['a', 'b']));
        // an alias that maps onto a secret column is refused as well
        $crud->query_fields = ['pw' => 'domains.domain_password'];
        $this->assertSame('', $crud->json_search_tosql('pw', '=', 'guess', true));
        $this->assertSame("domain_hostname like '%exam%'", $crud->json_search_tosql('domain_hostname', '=', 'exam', true));
    }

    public function testSearchAllFieldsSkipsSecretColumns(): void
    {
        $crud = $this->crud();
        $crud->search_terms = [['', '=', 'guess']];
        $sql = $crud->search_to_sql();
        $this->assertStringContainsString("domains.domain_hostname like '%guess%'", $sql);
        $this->assertStringNotContainsString('domain_password', $sql);
    }

    public function testSearchOnTheSecretFieldAloneSearchesNothing(): void
    {
        $crud = $this->crud();
        $crud->search_terms = [['domain_password', '=', 'guess']];
        $this->assertSame('', $crud->search_to_sql());
    }

    public function testSortingOnASecretFieldFallsBackToThePrimaryKey(): void
    {
        $crud = $this->crud();
        $crud->type = 'table';
        $crud->table = 'domains';
        $crud->order_by = 'domain_password';
        $crud->order_dir = 'asc';
        $crud->page_limit = 10;
        $crud->page_offset = 0;
        $crud->run_list_query();
        $this->assertSame('domain_id', $crud->order_by);
        $this->assertStringNotContainsString('domain_password', end($crud->db->queries));
    }

    public function testMaskedAliasMapsBackToItsOwnColumn(): void
    {
        $crud = $this->crud();
        $query = "select domain_id,domain_username,if(ifnull(domain_password,'')='','','********') as domain_password,domain_type from domains";
        $parser = new \SQLParser();
        $crud->queries = $parser->parse($query);
        $crud->parse_query_fields();
        $this->assertSame('domain_password', $crud->query_fields['domain_password']);
        $this->assertSame('domain_username', $crud->query_fields['domain_username']);
    }

    public function testEditNeverWritesTheMaskBack(): void
    {
        $crud = $this->crud();
        $crud->edit_row = true;
        $crud->query_fields = ['domain_id' => 'domain_id', 'domain_hostname' => 'domain_hostname', 'domain_password' => 'domain_password'];
        $crud->validations = ['domain_id' => ['int'], 'domain_hostname' => [], 'domain_password' => []];
        $crud->query_where = ['domains' => []];
        $savedPost = $_POST;
        try {
            $_POST = ['domain_id' => '5', 'domain_hostname' => 'new.example.com', 'domain_password' => '********'];
            ob_start();
            $crud->ajax_edit_handler();
            ob_end_clean();
            $this->assertCount(1, $crud->db->queries);
            $this->assertStringContainsString("domain_hostname='new.example.com'", $crud->db->queries[0]);
            $this->assertStringNotContainsString('domain_password', $crud->db->queries[0]);

            // a value that was actually typed in is still written
            $crud->db->queries = [];
            $_POST = ['domain_id' => '5', 'domain_password' => 'NewPass456'];
            ob_start();
            $crud->ajax_edit_handler();
            ob_end_clean();
            $this->assertCount(1, $crud->db->queries);
            $this->assertStringContainsString("domain_password='NewPass456'", $crud->db->queries[0]);
        } finally {
            $_POST = $savedPost;
        }
    }
}
