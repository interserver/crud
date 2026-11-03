<?php

use MyCrud\Crud;
use PHPUnit\Framework\TestCase;

class SearchWhereTestCrud extends Crud
{
    public $logged = [];

    public function log($message, $line = '', $file = '', $level = 'info')
    {
        $this->logged[] = $message;
    }
}

class SearchWhereTestDb
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

class SearchWhereTestParsedQuery
{
    private $where;

    public function __construct($where)
    {
        $this->where = $where;
    }

    public function hasWhere()
    {
        return $this->where;
    }
}

/**
 * When every search term is dropped (a value that does not fit the column type,
 * e.g. an email searched against the int email_id) the list and count queries
 * must not get a bare "where" / "and" appended, which was a mysql 1064 error:
 * "... from emails where  order by email_id desc limit 0, 25".
 */
class CrudSearchWhereTest extends TestCase
{
    private function crud($hasWhere = false, $query = 'select emails.email_id, emails.email_email from emails'): SearchWhereTestCrud
    {
        $rc = new ReflectionClass(SearchWhereTestCrud::class);
        /** @var SearchWhereTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        $crud->db = new SearchWhereTestDb();
        $crud->type = 'query';
        $crud->query = $query;
        $crud->queries = [new SearchWhereTestParsedQuery($hasWhere)];
        $crud->tables = [
            'emails' => [
                'email_id' => ['Type' => 'int(11)', 'Key' => 'PRI'],
                'email_email' => ['Type' => 'varchar(255)', 'Key' => ''],
            ],
        ];
        $crud->fields = ['email_id', 'email_email'];
        $crud->primary_key = 'email_id';
        $crud->order_by = 'email_id';
        $crud->order_dir = 'desc';
        $crud->page_limit = 25;
        $crud->page_offset = 0;
        return $crud;
    }

    public function testEmailSearchedAgainstIntIdDoesNotAppendABareWhere(): void
    {
        $crud = $this->crud();
        $crud->search_terms = ['email_id', '=', 'jake@nxtphase.net'];
        $crud->run_list_query();
        $this->assertSame('select emails.email_id, emails.email_email from emails order by email_id desc limit 0, 25', end($crud->db->queries));
    }

    public function testDroppedSearchDoesNotAppendABareAndToAnExistingWhere(): void
    {
        $crud = $this->crud(true, 'select emails.email_id, emails.email_email from emails where email_id > 0');
        $crud->search_terms = ['email_id', '=', 'jake@nxtphase.net'];
        $crud->run_list_query();
        $this->assertSame('select emails.email_id, emails.email_email from emails where email_id > 0 order by email_id desc limit 0, 25', end($crud->db->queries));
    }

    public function testDroppedSearchOnTableTypeDoesNotAppendABareWhere(): void
    {
        $crud = $this->crud();
        $crud->type = 'table';
        $crud->table = 'emails';
        $crud->search_terms = ['email_id', '=', 'not-a-number'];
        $crud->run_list_query();
        $this->assertSame('select * from emails order by email_id desc limit 0, 25', end($crud->db->queries));
    }

    public function testAppendSearchSql(): void
    {
        $crud = $this->crud();
        $crud->search_terms = [];
        $this->assertSame(' from emails', $crud->append_search_sql(' from emails', false));
        $crud->search_terms = ['email_id', '=', 'jake@nxtphase.net'];
        $this->assertSame(' from emails', $crud->append_search_sql(' from emails', false));
        $this->assertSame(' from emails where x=1', $crud->append_search_sql(' from emails where x=1', true));
        $crud->search_terms = ['email_id', '=', '42'];
        $this->assertSame(' from emails where email_id=42', $crud->append_search_sql(' from emails', false));
        $this->assertSame(' from emails where x=1 and email_id=42', $crud->append_search_sql(' from emails where x=1', true));
        $crud->search_terms = ['email_email', '=', 'jake@nxtphase.net'];
        $this->assertSame(" from emails where email_email='jake@nxtphase.net'", $crud->append_search_sql(' from emails', false));
    }

    public function testValidSearchStillFilters(): void
    {
        $crud = $this->crud();
        $crud->search_terms = ['email_id', '=', '42'];
        $crud->run_list_query();
        $this->assertSame('select emails.email_id, emails.email_email from emails where email_id=42 order by email_id desc limit 0, 25', end($crud->db->queries));
    }
}
