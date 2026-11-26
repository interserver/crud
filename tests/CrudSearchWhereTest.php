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

    // get_count() reads one row of the count query
    public function next_record($type = null)
    {
        return true;
    }

    public function f($field)
    {
        return 0;
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
        // the existing condition is parenthesized now, so an OR in it cannot swallow the search
        $this->assertSame(' from emails where (x=1) and email_id=42', $crud->append_search_sql(' from emails where x=1', true));
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

    /**
     * the search (the tenant filter is one of its terms) has to land INSIDE the where clause.
     * it used to be appended at the end, so 'group by x' became 'group by x and email_id=42',
     * which mysql runs and which filters nothing.
     *
     * @return array<string, array{0: string, 1: bool, 2: string}> [query, parser hasWhere, expected]
     */
    public static function searchPlacementProvider(): array
    {
        return [
            // GROUP BY, the full list query and the get_count() from-part, with and without a where
            'group by, query with where' => ['select email_id from emails where x=1 group by email_id', true, 'select email_id from emails where (x=1) and email_id=42 group by email_id'],
            'group by, query without where' => ['select email_id from emails group by email_id', false, 'select email_id from emails where email_id=42 group by email_id'],
            'group by, from-part with where' => [' from emails where x=1 group by email_id', true, ' from emails where (x=1) and email_id=42 group by email_id'],
            'group by, from-part without where' => [' from emails group by email_id', false, ' from emails where email_id=42 group by email_id'],
            'crud_customers shape' => ["select account_id,account_lid,account_status, count(domain_custid) as order_count from accounts left join domains on domain_custid=account_id where account_ima != 'admin' group by account_id", true,
                "select account_id,account_lid,account_status, count(domain_custid) as order_count from accounts left join domains on domain_custid=account_id where (account_ima != 'admin') and email_id=42 group by account_id"],
            // the other clause terminators
            'having' => ['select email_id from emails where x=1 having email_id > 0', true, 'select email_id from emails where (x=1) and email_id=42 having email_id > 0'],
            'group by and having, no where' => ['select email_email, count(*) as c from emails group by email_email having c > 1', false, 'select email_email, count(*) as c from emails where email_id=42 group by email_email having c > 1'],
            'order by and limit' => ['select * from emails where x=1 order by email_id limit 5', true, 'select * from emails where (x=1) and email_id=42 order by email_id limit 5'],
            'limit' => ['select * from emails limit 5', false, 'select * from emails where email_id=42 limit 5'],
            'window' => ['select email_id, sum(x) over w from emails where x=1 window w as (order by email_id)', true, 'select email_id, sum(x) over w from emails where (x=1) and email_id=42 window w as (order by email_id)'],
            'into' => ['select email_id from emails where x=1 into @v', true, 'select email_id from emails where (x=1) and email_id=42 into @v'],
            // terminators are only looked for after the FROM
            'into before from' => ['select email_id into @v from emails where x=1 group by email_id', true, 'select email_id into @v from emails where (x=1) and email_id=42 group by email_id'],
            'procedure' => ['select * from emails procedure analyse()', false, 'select * from emails where email_id=42 procedure analyse()'],
            'for update' => ['select * from emails where x=1 for update', true, 'select * from emails where (x=1) and email_id=42 for update'],
            'for share' => ['select * from emails for share', false, 'select * from emails where email_id=42 for share'],
            'lock in share mode' => ["select * from emails lock\n in  share   mode", false, "select * from emails where email_id=42 lock\n in  share   mode"],
            'upper case, any whitespace between the words' => ["select email_id\nFROM emails\nWHERE x = 1\nGROUP\n\tBY email_id", true, "select email_id\nFROM emails\nWHERE (x = 1) and email_id=42 GROUP\n\tBY email_id"],
            // an OR / || / XOR in the existing condition cannot swallow the search
            'top level or' => ['select * from emails where x=1 or y=2', true, 'select * from emails where (x=1 or y=2) and email_id=42'],
            'top level || and xor' => ['select * from emails where x=1 || y=2 xor z=3 order by email_id', true, 'select * from emails where (x=1 || y=2 xor z=3) and email_id=42 order by email_id'],
            // clauses inside a function call, a window or a subquery (depth > 0) are left alone
            'order by inside group_concat' => ["select email_email, group_concat(email_id order by email_id separator ',') as ids from emails group by email_email", false,
                "select email_email, group_concat(email_id order by email_id separator ',') as ids from emails where email_id=42 group by email_email"],
            'over (order by) and a grouped subquery' => ['select email_id, row_number() over (partition by x order by email_id) as rn from emails where x in (select y from z group by y having y > 1 limit 3) order by rn', true,
                'select email_id, row_number() over (partition by x order by email_id) as rn from emails where (x in (select y from z group by y having y > 1 limit 3)) and email_id=42 order by rn'],
            'derived table with its own clauses' => ['select d.email_id from (select email_id from emails where y=2 group by email_id order by email_id limit 9) d where d.email_id > 0 order by d.email_id', true,
                'select d.email_id from (select email_id from emails where y=2 group by email_id order by email_id limit 9) d where (d.email_id > 0) and email_id=42 order by d.email_id'],
            'from inside a select list subquery' => ['select email_id, (select count(*) from z where z.a = emails.email_id group by z.b limit 1) as n from emails group by email_id', false,
                'select email_id, (select count(*) from z where z.a = emails.email_id group by z.b limit 1) as n from emails where email_id=42 group by email_id'],
            'union inside a derived table' => ['select d.email_id from (select email_id from emails union all select email_id from emails2) d group by d.email_id', false,
                'select d.email_id from (select email_id from emails union all select email_id from emails2) d where email_id=42 group by d.email_id'],
            'union inside a where subquery' => ['select * from emails where email_id in (select a from t union select b from u) order by email_id', true,
                'select * from emails where (email_id in (select a from t union select b from u)) and email_id=42 order by email_id'],
            // keywords in literals, quoted identifiers and comments are ignored
            'single and double quoted literals' => [<<<'SQL'
select * from emails where email_email = 'it\'s a ''group by'' x' or email_email = "say \"order by\" ""limit""" group by email_id
SQL, true, <<<'SQL'
select * from emails where (email_email = 'it\'s a ''group by'' x' or email_email = "say \"order by\" ""limit""") and email_id=42 group by email_id
SQL],
            'backtick identifiers' => ['select `group`, `order``by` from emails where `limit` = 1 group by `group`', true, 'select `group`, `order``by` from emails where (`limit` = 1) and email_id=42 group by `group`'],
            'a backslash does not escape in backticks' => ['select `a\` from emails where x=1 group by email_id', true, 'select `a\` from emails where (x=1) and email_id=42 group by email_id'],
            'block comments' => ['select email_id /* from x where y group by z */ from emails /*! where */ where x=1 /*+ limit 1 */ group by email_id', true,
                'select email_id /* from x where y group by z */ from emails /*! where */ where (x=1 /*+ limit 1 */) and email_id=42 group by email_id'],
            '# comment in the where' => ["select * from emails where x=1 # group by x\ngroup by email_id", true, "select * from emails where (x=1 # group by x\n) and email_id=42 group by email_id"],
            'trailing -- comment in the where' => ['select * from emails where x=1 -- group by x', true, "select * from emails where (x=1 -- group by x\n) and email_id=42"],
            '-- comment then a group by' => ["select * from emails where x=1 -- note\ngroup by email_id", true, "select * from emails where (x=1 -- note\n) and email_id=42 group by email_id"],
            '--1 is not a comment' => ['select * from emails where x=1--1 group by email_id', true, 'select * from emails where (x=1--1) and email_id=42 group by email_id'],
            'comment before the insertion point, no where' => ["select * from emails -- all of them\ngroup by email_id", false, "select * from emails -- all of them\nwhere email_id=42 group by email_id"],
            // the second half of a qualified name, or a variable, is not a keyword
            'qualified names' => ['select * from emails e where e.order = 1 and e . limit > 0 and e.group is not null group by e.where', true,
                'select * from emails e where (e.order = 1 and e . limit > 0 and e.group is not null) and email_id=42 group by e.where'],
            'variables' => ['select * from emails where x > @limit and y < @@global.max_connections group by email_id', true,
                'select * from emails where (x > @limit and y < @@global.max_connections) and email_id=42 group by email_id'],
            // a '.' qualifies the next word only after an identifier (whitespace and comments
            // allowed around it): `quoted`, or a word that is not all digits
            'quoted and spaced qualified names' => ['select * from emails t where `t`.order = 1 and `t`.limit = 1 and t . limit > 0 and t /* c */ . having = 2 and 2t.having = 3 group by t.email_id', true,
                'select * from emails t where (`t`.order = 1 and `t`.limit = 1 and t . limit > 0 and t /* c */ . having = 2 and 2t.having = 3) and email_id=42 group by t.email_id'],
            // after a number it is a decimal point, and the next word can be a keyword
            'trailing-dot decimal before group by' => ['select email_id from emails where x = 1. group by email_id', true, 'select email_id from emails where (x = 1.) and email_id=42 group by email_id'],
            'trailing-dot decimal before limit' => ['select * from emails where x = 1. limit 3', true, 'select * from emails where (x = 1.) and email_id=42 limit 3'],
            'trailing-dot decimal before into' => ['select email_id from emails where x = 1. into @v', true, 'select email_id from emails where (x = 1.) and email_id=42 into @v'],
            'decimal before order by' => ['select * from emails where x = 1.5 order by email_id', true, 'select * from emails where (x = 1.5) and email_id=42 order by email_id'],
            'leading-dot decimal before limit' => ['select * from emails where x = .5 limit 3', true, 'select * from emails where (x = .5) and email_id=42 limit 3'],
            '1.e5 before a terminator' => ['select * from emails where x = 1.e5 group by email_id', true, 'select * from emails where (x = 1.e5) and email_id=42 group by email_id'],
            // an index hint's FOR ORDER BY / FOR GROUP BY / FOR JOIN is not a clause terminator
            'index hint for order by' => ['select * from emails force index for order by (i) where x=1 group by email_id', true,
                'select * from emails force index for order by (i) where (x=1) and email_id=42 group by email_id'],
            'index hint for group by, no where' => ['select * from emails use key for group by (i) order by email_id', false, 'select * from emails use key for group by (i) where email_id=42 order by email_id'],
            'index hint for join' => ['select * from emails ignore index for join (i) limit 3', false, 'select * from emails ignore index for join (i) where email_id=42 limit 3'],
            // the scan, not the sql parser's hasWhere, decides between 'where' and 'and'
            'parser says no where but there is one' => [' from emails where x=1', false, ' from emails where (x=1) and email_id=42'],
            'parser says where but it is only in a join' => ['select * from emails e join (select a from t where b=1) s on s.a=e.email_id group by e.email_id', true,
                'select * from emails e join (select a from t where b=1) s on s.a=e.email_id where email_id=42 group by e.email_id'],
        ];
    }

    /**
     * @dataProvider searchPlacementProvider
     */
    public function testSearchLandsInsideTheWhereClause(string $query, bool $hasWhere, string $expected): void
    {
        $crud = $this->crud();
        $crud->search_terms = ['email_id', '=', '42'];
        $this->assertSame($expected, $crud->append_search_sql($query, $hasWhere));
    }

    /**
     * sql the scan cannot read (unbalanced parens, an unterminated literal or comment) is
     * malformed and mysql rejects it however the search is added: it keeps the old append at
     * the end, picking 'where' or 'and' from the parser's hasWhere. (this is today's behaviour
     * on purpose, so it passes with or without the placement fix.)
     *
     * @return array<string, array{0: string, 1: bool, 2: string}>
     */
    public static function unreadableQueryProvider(): array
    {
        return [
            'paren left open' => ['select * from emails where (x=1 group by email_id', true, 'select * from emails where (x=1 group by email_id and email_id=42'],
            // the get_count() from-part of a query whose select list holds a subquery
            'paren closed below depth 0' => [' from z) as n from emails group by email_id', false, ' from z) as n from emails group by email_id where email_id=42'],
            'unterminated literal' => ["select * from emails where x='abc group by email_id", true, "select * from emails where x='abc group by email_id and email_id=42"],
            'unterminated backtick' => ['select * from emails where `x = 1 group by email_id', true, 'select * from emails where `x = 1 group by email_id and email_id=42'],
            'unterminated block comment' => ['select * from emails /* group by email_id', false, 'select * from emails /* group by email_id where email_id=42'],
        ];
    }

    /**
     * @dataProvider unreadableQueryProvider
     */
    public function testUnreadableSqlFallsBackToAppending(string $query, bool $hasWhere, string $expected): void
    {
        $crud = $this->crud();
        $crud->search_terms = ['email_id', '=', '42'];
        $this->assertSame($expected, $crud->append_search_sql($query, $hasWhere));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function topLevelSetOperationProvider(): array
    {
        return [
            'union' => ['select email_id from emails where x=1 union select email_id from emails2'],
            'UNION ALL with a group by' => ['select email_id from emails UNION ALL select email_id from emails2 group by email_id'],
            'except' => ['select email_id from emails except select email_id from emails2'],
            'intersect' => ['select email_id from emails intersect select email_id from emails2'],
            'second branch parenthesized' => ['select email_id from emails union (select email_id from emails2)'],
        ];
    }

    /**
     * one insertion cannot filter every branch of a top level set operation, and filtering
     * only one of them would be the silent leak this placement fix is about: refuse it
     *
     * @dataProvider topLevelSetOperationProvider
     */
    public function testTopLevelSetOperationThrows(string $query): void
    {
        $crud = $this->crud();
        $crud->search_terms = ['email_id', '=', '42'];
        $this->expectException(\RuntimeException::class);
        $crud->append_search_sql($query, true);
    }

    /**
     * @dataProvider topLevelSetOperationProvider
     */
    public function testTopLevelSetOperationWithoutASearchIsLeftAlone(string $query): void
    {
        $crud = $this->crud();
        $crud->search_terms = [];
        $this->assertSame($query, $crud->append_search_sql($query, true));
        $crud->search_terms = ['email_id', '=', 'not-a-number'];
        $this->assertSame($query, $crud->append_search_sql($query, true));
    }

    /**
     * crud_month_payment_totals: the tenant term get_table_details() adds for a limited
     * session has to filter the grouped list query and its count, not trail the group by
     */
    public function testMonthPaymentTotalsListAndCountKeepTheTenantTermInsideWhere(): void
    {
        if (!defined('MYSQL_NUM')) {
            define('MYSQL_NUM', 2);
        }
        $query = 'select invoices_module as module, sum(invoices_amount) as total_paid from invoices where year(invoices.invoices_date) = year(now()) AND month(invoices.invoices_date) = month(now()) AND invoices.invoices_type >= 10 group by invoices_module';
        $where = 'where (year(invoices.invoices_date) = year(now()) AND month(invoices.invoices_date) = month(now()) AND invoices.invoices_type >= 10) and invoices_custid=5 group by invoices_module';
        $crud = $this->crud(true, $query);
        $crud->tables = [
            'invoices' => [
                'invoices_id' => ['Type' => 'int(11) unsigned', 'Key' => 'PRI'],
                'invoices_amount' => ['Type' => 'decimal(10,2)', 'Key' => ''],
                'invoices_custid' => ['Type' => 'int(11)', 'Key' => 'MUL'],
                'invoices_type' => ['Type' => 'int(11)', 'Key' => ''],
                'invoices_date' => ['Type' => 'datetime', 'Key' => ''],
                'invoices_module' => ['Type' => 'varchar(30)', 'Key' => ''],
            ],
        ];
        $crud->fields = ['module', 'total_paid'];
        $crud->primary_key = 'module';
        $crud->order_by = 'module';
        // the shape get_table_details() leaves search_terms in for a limited session
        $crud->search_terms = [['invoices_custid', '=', 5]];

        $crud->run_list_query();
        $this->assertSame("select invoices_module as module, sum(invoices_amount) as total_paid from invoices {$where} order by module desc limit 0, 25", end($crud->db->queries));

        $crud->get_count();
        $this->assertSame("select count(*)  from invoices {$where}", end($crud->db->queries));
        foreach ($crud->db->queries as $sql) {
            $this->assertStringNotContainsString('group by invoices_module and', $sql);
        }
    }
}
