<?php
/**
 * CRUD Class
 *
 * .. because designing a webpage should be as easy as writing an sql query
 *
 * its got a very customizable output and customizable field handling ,  but aiming for it to automatically
 * generate an optimal page w/out the need for customizing in most cases
 *
 * i figure most all pages in my are based around a single query/table , each page just having basically a
 * different query, but essentially all doing the same basic things with it, building a list of records, a
 * form to add or edit them, or simply display a record, or a page might be a group of several of these
 * things...
 *
 * so my goal w this is to automatically generate as much as possible for each page using only the given
 * query, and where customized handling for something is needed make that process as simple as possible as
 * well, so pages can be reworked and code reduced to just the fewest bits of information unique to that
 * page and get improved layouts+validation/form-handling as a side effect
 *
 * CRUD classes themselves are very popular and used in most all big frameworks, but from extensive
 * research (over the past few years of trying to decide which one to use) they all seem rather hard
 * to implement or at least a lot of code just to setup a page using it , my approach is i think the
 * ideal way to do it taking the best of what ive found in other CRUD classes but requiring less code
 * to set it up.
 *
 * since the HTML part is all handled within the templates and the class generates things like validations
 * and field information, its easy to setup several alternate layouts and easily choose an alternate one
 * when you don't want to use the default layout.  it also makes it easy to setup things like alternate
 * interfaces such as a CLI or ANSI Terminal GUI, Windows/OS Native programs, and even various API
 * interfaces with relative ease only having to add the code for basic components of each once. Although
 * I do have several table alternate layouts already, I have no plans to setup additional templates
 * until everything else with it is working and getting widely implemented.
 *
 * @author Joe Huss <detain@interserver.net>
 * @version $Revision$
 * @copyright 2025
 * @package MyAdmin
 * @category Billing
 * @TODO Add API Interface to this
 * @TODO Add Console/ANSI Interface to this
 * @TODO Add order summary includable by login page
 */
namespace MyCrud;

use sqlparser;
use TFSmarty;
use TFTable;
use MyCrud\CrudFunctionIterator;
use MyAdmin\Form;
use Punic\Currency;

/**
 * Class Crud
 *
 * @package MyCrud
 */
class Crud extends Form
{
    /**
     * name of the synthetic row field holding the query string fragment that
     * identifies a row's service in a generated link - 'uuid=<uuid>' for a row that
     * carries a real uuid, 'id=<id>' for one that does not. added to every record by
     * add_service_param() once a list has opted in through use_uuid_links(), and
     * hidden from the visible columns.
     *
     * it is a whole `name=value` fragment rather than just the uuid so the id
     * fallback can change the parameter NAME too: a uuid that never got stamped must
     * fall back to `id=`, and putting an integer in a `uuid=` slot is not the same
     * url (uuid_resolve_request() resolves `uuid=` as a uuid). that also keeps the
     * fallback in exactly one place - here - rather than once in php for the
     * server rendered links and again in javascript for the row buttons.
     */
    const SERVICE_PARAM_FIELD = 'service_param';

    /**
     * suffix of the ajax list cell that carries a column's decorated html, for the columns
     * whose filters cannot be filled in from a template (see has_server_decoration()). the row
     * template holds '%<field>:html%' in that column, and ajax_cells() sends the decorated value
     * under '<field>:html', next to the plain escaped '<field>' value the other columns' string
     * filters paste in. ':' never occurs in an unquoted column name or alias.
     */
    const DECORATED_CELL_SUFFIX = ':html';

    /**
     * U+FFFD in utf-8: what JSON_INVALID_UTF8_SUBSTITUTE puts in place of an invalid byte in the
     * crud_rows json (the page's inline json and the ajax list's rows) the edit form fills its
     * inputs from. see unchanged_substituted_fields()
     */
    const UTF8_REPLACEMENT_CHAR = "\xEF\xBF\xBD";

    /**
     * the json_encode() flags for json printed inside the templates' inline <script> blocks
     * (crud_rows, crud_search_terms; see inline_json()): < > & ' " as \u escapes, so no stored
     * or requested value can close or derail the script block ('</script>', '<!--<script'),
     * and an invalid utf-8 byte becomes U+FFFD instead of json_encode() failing and printing
     * nothing. javascript reads the same values.
     */
    const INLINE_JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;

    public $limit_custid_role = false;
    public $limit_custid = false;
    public $custid_match = '/_custid$/m';
    public $ajax = false;
    public $debug = false;
    public $refresh_button = true;
    public $export_button = true;
    public $print_button = true;
    public $use_html_fitering = true;
    public $module;
    public $choice;
    /* @var TFTable */
    public $table;
    public $query;
    public $primary_key = '';
    public $type = '';
    public $title = '';
    public $columns = 3;
    public $price_align = 'r';
    public $price_text_align = 'r';
    public $stage = 1;
    public $rows = [];
    public $page_limits = [10, 25, 50, 100, -1];
    public $page_limit = 100;
    public $page_offset = 0;
    public $insert_over_update = false;
    public $total_pages = 1;
    public $page_links = null;
    public $total_rows;
    public $order_by = '';
    public $order_dir = 'desc';
    public $all_fields = false;
    public $initial_populate = true;
    public $select_multiple = false;
    public $delete_row = true;
    public $edit_row = true;
    public $add_row = true;
    /**
     * whether a non-admin session may use the ajax edit/add/delete handlers. off by
     * default: they write by the posted primary key, so a client reaching them could
     * change any row of the table (any customer's vps or domain, any host). a crud page
     * that really needs client writes opts in with allow_client_edit(); an edit is then
     * pinned to the client's own rows by a tenant term on each account column of the table
     * (custid_match, *_custid by default; a table with none refuses the edit), and an edit
     * posting another account into an account column, however the page's query spells its
     * letter case, is refused whole (see locks_account_columns(), is_account_column()).
     * @var bool
     */
    public $client_edit = false;
    /** session csrf token name the edit/add/delete forms carry and the handlers check */
    const CSRF_NAME = 'crud_edit';
    public $query_where = [];
    public $admin_confirm_fields = [];
    public $query_fields = [];
    public $search_terms = [];
    /**
     * columns that hold secrets (service/root/ipmi passwords, card security codes).
     * wherever one of these shows up in a list, a table browser or an export its value
     * is replaced by $secret_mask, it can never be searched or sorted on (a LIKE search
     * would let anyone test guesses against the real value), and an edit only writes it
     * when a new value was actually typed in. matched on the bare column name, with or
     * without a table. prefix.
     */
    public static $secret_columns = [
        'domain_password',
        'vps_rootpass',
        'qs_rootpass',
        'asset_password',
        'ipmi_admin_password',
        'ipmi_client_password',
        'ipmi_admin_pass',
        'cc_request_card_code',
        // per-host queue psk: whoever has it can act as that host on public_html/*queue.php
        'vps_queue_key',
        'qs_queue_key',
        // account login password hashes (core plan_hash.md §6.7, R2-9): masked so the crud_table
        // browser cannot show, export, LIKE-search or sort on them, and an edit cannot store an
        // arbitrary string there (it would read as an unknown format and lock the user out)
        'account_passwd',
        'account_password_hash',
        // full card numbers in cc_log (most rows from 2013 to 2026-09-25). it used to be masked
        // only by crud_cc_log's table cell filter, so the inline crud_rows json, the ajax list and
        // every export still carried the whole number; shown as stars plus the last 4 (see
        // $secret_column_masks), never searchable or sortable (a LIKE search would test guesses)
        'cc_request_card_num',
    ];
    /**
     * secret columns that show more than $secret_mask: column => mask style. 'last4' is
     * mask_cc()'s output (core myadmin-authorizenet-payments cc.inc.php): a value longer than 6
     * characters becomes a '*' per character but the last 4, followed by those last 4; a shorter
     * one is shown as it is. staff identify a card by its last 4, so that much stays visible.
     * matched on the bare column name like $secret_columns.
     *
     * @var array<string, string>
     */
    public static $secret_column_masks = [
        'cc_request_card_num' => 'last4',
    ];
    /**
     * the module databases the crud_table browser is linked with (core's menu links db=domains,
     * cruds.php lists every table of these two). crud_table() refuses any other db value.
     *
     * @var string[]
     */
    public static $table_browser_modules = ['domains', 'pdns'];
    public $secret_mask = '********';
    /**
     * columns whose value is html the page built itself from trusted constants (an <img> picked by
     * a status code, say) and that decorate_field() therefore does not escape while html filtering
     * is on; every other column is escaped. see set_raw_html_fields().
     *
     * @var string[]
     */
    public $raw_html_fields = [];
    // temp fields maybe from buy service class i think
    public $disabled_fields = [];
    public $filters = [];
    // field => swap flag, populated by parse_tables() for binary(16) uuid columns
    // (see bin_to_uuid()/uuid_to_bin()). the flag is null - auto detect - so test
    // membership with array_key_exists(), NOT isset().
    public $uuid_fields = [];
    /**
     * the row field holding this list's service uuid as a hyphenated string, set by
     * use_uuid_links(). '' means the list has not opted into uuid links and every
     * link it builds keeps addressing rows by their sequential id.
     * @var string
     */
    public $uuid_key = '';
    /**
     * fields that stay in the row payload - so decorate_field() can substitute them
     * into a link and the crud_rows json can hand them to the row button js - but are
     * not rendered as visible table columns. see hide_field().
     * @var string[]
     */
    public $hidden_fields = [];
    public $use_labels = false;
    public $column_templates = [];
    public $tables = [];
    // from the SQLParser or CrudFunctionIterator
    public $queries;
    /* @var db */
    public $db;
    public $settings;
    public $buttons = [];
    public $header = '';
    public $header_buttons = [];
    public $title_buttons = [];
    public $fluid_container = false;
    public $edit_button = '';
    public $delete_button = '';
    public $extra_url_args = '';
    public $request = [];
    public $admin = false;
    public $output = '';
    public $return_output = false;
    /**
     * @var FALSE|int $auto_update FALSE to disable, or frequency in seconds to update the list of records automatically
     */
    public $auto_update = false;
    /**
     * whether the 'list limited by a role the admin lacks, on tables with no account column'
     * warning was logged for this crud already (see tenant_search_sql())
     * @var bool
     */
    protected $role_limit_warned = false;
    /**
     * whether the session is an admin lacking the page's limit role (set_limit_custid_role()),
     * whatever customer the request names; decided by apply_custid_limit(), read by
     * is_role_limited()
     * @var bool
     */
    protected $lacks_limit_role = false;
    /**
    * Layout/Theme variable
    */
    public $theme;

    /**
     * constructor class

     */
    public function __construct()
    {
        $this->edit_button = '<button type="button" class="btn btn-primary btn-xs" onclick="crud_edit_form(this);" title="'._('Edit').'"><i class="fa fa-fw fa-pencil"></i></button>';
        $this->delete_button = '<button type="button" class="btn btn-danger btn-xs" onclick="crud_delete_form(this);" title="'._('Delete').'"><i class="fa fa-fw fa-trash"></i></button>';
        $this->theme = DEFAULT_THEME;
        // $this->module is still null here, and get_module_name() uses it as an array offset,
        // which php 8.5 deprecates; '' is the key null was, so the result is the same
        // (the $_REQUEST['module'] fallback included)
        $this->module = get_module_name($this->module ?? '');
        $this->settings = get_module_settings($this->module);
    }

    /**
     * initializes the crud system around the given query table or function.
     *
     * @param string $table_or_query the table name or sql query or function to use in the result
     * @param string $module optional module to associate w/ this query
     * @param string $type optional parameter to specify the type of data we're dealing with , can be sql (default) or function,
     *                     or table: $table_or_query is then always a table name, never run as a query (crud_table passes
     *                     the requested table name this way)
     * @param string $account_match optional regex to amtch custid type field names
     * @return MyCrud\Crud an instance of the crud system.
     */
    public static function init($table_or_query, $module = 'default', $type = 'sql', $account_match = '')
    {
        // @codingStandardsIgnoreStart
        if (isset($this) && $this instanceof self) {
            $crud = &$this;
        } else {
            $crud = new self();
        }
        // @codingStandardsIgnoreEnd
        $crud->apply_module_info($module);
        $crud->column_templates[] = ['text' => '<h3>%title%</h3>', 'align' => 'r'];
        $crud->column_templates[] = ['text' => '%field%', 'align' => 'r'];
        $crud->column_templates[] = ['text' => '', 'align' => 'r'];
        $crud->set_title();
        $crud->apply_request_data();
        if ($account_match != '') {
            $crud->set_custid_match($account_match);
        }
        if ($type == 'function') {
            $crud->all_fields = true;
            $crud->type = $type;
            $crud->table = $table_or_query;
            $crud->query = $table_or_query;
        } elseif ($type !== 'table' && mb_strpos($table_or_query, ' ')) {
            $crud->all_fields = false;
            $crud->query = $crud->decorate_query($table_or_query);
            $crud->type = 'query';
            $crud->parse_query();
            $crud->get_tables_from_query();
        } else {
            $crud->all_fields = true;
            //$crud->load_tables();
            $crud->table = $table_or_query;
            $crud->type = 'table';
            $crud->tables[$crud->table] = $crud->get_table_details($crud->table);
        }
        $crud->parse_tables();
        $crud->default_filters();
        return $crud;
    }

    public function add_search_param($field, $var) {
        if (isset($_GET[$var])) {
            $this->search_terms = [$field, '=', $_GET[$var]];
        }
        return $this;
    }

    /**
    * controls whether or not the output is returned or sent to the normal output handler
    *
    * @param bool $return_output
    * @return Crud
    */
    public function set_return_output($return_output)
    {
        $this->return_output = $return_output;
        return $this;
    }

    public function add_output($output)
    {
        if ($this->return_output == true) {
            $this->output .= $output;
        } else {
            add_output($output);
        }
    }

    public function set_use_html_filtering($enable = true)
    {
        $this->use_html_fitering = $enable;
        return $this;
    }

    /**
     * names the columns whose value is html the page builds itself, from trusted constants or
     * values no customer controls (an <img> chosen by an integer type column, say), so
     * decorate_field() and the ajax list leave them unescaped while every other column of the
     * row stays escaped. it replaces turning html filtering off for the whole list, which also
     * printed the customer controlled columns of the same rows (descriptions, hostnames) raw.
     * a column whose html mixes in customer data does not belong here: build that html in a
     * 'function' filter that escapes what it puts in.
     *
     * @param string[] $fields
     * @return Crud
     */
    public function set_raw_html_fields($fields)
    {
        $this->raw_html_fields = [];
        foreach ((array) $fields as $field) {
            if (is_string($field)) {
                $this->raw_html_fields[] = $field;
            }
        }
        return $this;
    }

    /**
     * whether a field's value is printed as html (see set_raw_html_fields())
     *
     * @param string $field
     * @return bool
     */
    public function is_raw_html_field($field)
    {
        return in_array($field, $this->raw_html_fields, true);
    }

    public function set_custid_match($match = true)
    {
        $this->custid_match = $match;
        return $this;
    }

    public function set_header($header = true)
    {
        $this->header = $header;
        return $this;
    }

    public function set_page_limit($page_limit = true)
    {
        $this->page_limit = $page_limit;
        return $this;
    }

    /**
     * the acl that lets an admin list every account's rows. an admin without it is limited to
     * their own account, unless the request names a customer (custid). pages chain this after
     * init(), when apply_request_data() has already decided limit_custid, so the decision is
     * made again here; the tenant terms are only built when a query runs (see
     * tenant_search_terms()), so the list, its count, the ajax list, the exports and the
     * edit handler all follow it.
     *
     * @param string|false $role the acl name, or false for no role (admins are unlimited)
     * @return Crud
     */
    public function set_limit_custid_role($role = false)
    {
        $this->limit_custid_role = $role;
        $this->apply_custid_limit();
        return $this;
    }

    /**
     * applies the module info setting up the local module, settings, and db variables
     *
     * @param string $module module name to apply info with
     * @return void
     */
    public function apply_module_info($module = 'default')
    {
        if ($module != 'default') {
            if (isset($GLOBALS['modules'][$module])) {
                $this->module = get_module_name($module);
                $this->settings = get_module_settings($this->module);
                $this->db = get_module_db($this->module);
            } elseif (isset($GLOBALS[$module.'_dbh'])) {
                $this->module = $module;
                $this->settings = get_module_settings(get_module_name($module));
                $this->db = get_module_db($this->module);
            } else {
                $this->module = get_module_name($module);
                $this->settings = get_module_settings($this->module);
                $this->db = get_module_db($this->module);
            }
        } else {
            $this->module = get_module_name($module);
            $this->settings = get_module_settings($this->module);
            $this->db = get_module_db($this->module);
        }
    }

    /**
     * sets default search terms
     *
     * @param array $search
     * @return Crud
     */
    public function set_default_search($search)
    {
        if ($this->ajax == false && !isset($this->request['search'])) {
            $this->search_terms = is_array($search) ? $search : [];
        }
        return $this;
    }

    /**
     * reduces a `choice` (or, on the ajax path, `crud`) request value to the page function
     * name the dispatcher actually ran. core's dispatchers (index.php, iframe.php, widget.php,
     * admin/dc.php) do `[$class, $function] = explode('.', $choice)` when the value has a dot
     * and run the second segment whatever the prefix ('none.' by convention, the class part is
     * never used), so this keeps everything after the first dot (any prefix), then only the
     * leading php identifier of that: 'none.crud_x' and 'foo.crud_x' give crud_x, 'a.b.c' gives
     * b, '.crud_x' gives crud_x, 'crud_x.' and 'none.' give ''; a value without a dot (the
     * ajax crud= value) is only cut to its leading identifier. so
     * 'none.crud_vps_list." onfocus="..." x="' still ran crud_vps_list - and the whole value
     * used to come back out in the templates' action="..." attributes (request values are only
     * pre-escaped for <>&, never for quotes).
     *
     * @param mixed $choice the raw request value
     * @return string|null the function name, '' when the dispatched segment has no leading
     *                     identifier or the value is not a string (choice[]=x), or null when it
     *                     was missing (null)
     */
    protected static function choice_function_name($choice)
    {
        if ($choice === null) {
            return null;
        }
        if (!is_string($choice)) {
            return '';
        }
        $dot = strpos($choice, '.');
        if ($dot !== false) {
            $choice = (string) substr($choice, $dot + 1);
        }
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*/', $choice, $matches) ? $matches[0] : '';
    }

    /**
     * checks for various request fields and applies them to their various crud settings
     *
     * @return void
     */
    public function apply_request_data()
    {
        if (\MyAdmin\App::has(\MyAdmin\tf::class)) {
            $this->request = \MyAdmin\App::variables()->request;
            $this->admin = (\MyAdmin\App::ima() == 'admin');
            $this->custid = \MyAdmin\App::session()->account_id;
        } else {
            $this->request = $_REQUEST;
        }
        $this->choice = $this->request['choice'];
        if ($this->choice == 'crud') {
            $this->ajax = $this->request['action'];
            $this->choice = $this->request['crud'];
        }
        if (isset($this->request['order_by'])) {
            $this->order_by = $this->request['order_by'];
        }
        if (isset($this->request['order_dir']) && in_array($this->request['order_dir'], ['asc', 'desc'])) {
            $this->order_dir = $this->request['order_dir'];
        }
        if (isset($this->request['search'])) {
            // an empty or malformed search param decodes to null (or a scalar), which would fatal count() later.
            // a search that is not a string (search[]=x: core's request filter keeps an array an
            // array) is malformed too: html_entity_decode() threw a TypeError on it, a fatal
            // error for the page and for every ajax call of the list
            $search = is_string($this->request['search']) ? json_decode(html_entity_decode($this->request['search'])) : null;
            $this->search_terms = is_array($search) ? $search : [];
        }
        if (isset($this->request['offset'])) {
            $this->page_offset = (int)$this->request['offset'];
        }
        if (isset($this->request['limit'])) {
            $this->page_limit = (int)$this->request['limit'];
        }
        // keeps only the function name the dispatcher ran (the segment after the first dot,
        // whatever the prefix, cut to its leading identifier); the templates, the logs and
        // pending_orders.pend_choice all get this normalized value
        $this->choice = self::choice_function_name($this->choice);
        if ($this->page_limit < 1) {
            $this->page_limit = 500;
        }
        if ($this->page_offset < 0) {
            $this->page_offset = 0;
        }
        // the record count, total_pages and page_links used to be worked out here, before init()
        // knew the table or query (so the count was always 0), and before the page had set its
        // limit role. list_records() works them out once the list query has run, with the final
        // limit
        if ($this->admin == true && isset($this->request['custid'])) {
            // may be an account uuid; unresolved, mysql would coerce '0195f3d2-...' to 195
            $this->custid = function_exists('resolve_account_id') ? resolve_account_id($this->request['custid']) : (int)$this->request['custid'];
        }
        $this->apply_custid_limit();
    }

    /**
     * decides whether this list is limited to one account ($this->limit_custid, the account
     * being $this->custid), the same way core's legacy forms do (js.render_form.inc.php,
     * list_all_acl):
     *  - a client: always, to their own account;
     *  - an admin whose request names a customer (custid): to that customer;
     *  - an admin, no custid, on a page that set a limit role they lack: to their own account;
     *  - an admin who holds the role, or on a page with no role: not limited.
     * run by apply_request_data() and again by set_limit_custid_role(), which pages call after
     * init(). the limit itself is applied where it is used: tenant_search_terms() for the list,
     * count and export queries, tenant_where() for the edit handler, confirm_order() for the
     * order form's custid.
     *
     * whether an admin lacks the page's role is kept apart ($lacks_limit_role, see
     * is_role_limited()), custid or not: a requested customer only says whose rows to show,
     * and on tables with no account column it cannot pin anything, so it must not lift the
     * role limit there.
     *
     * @return void
     */
    protected function apply_custid_limit()
    {
        $this->lacks_limit_role = false;
        if ($this->admin == true && $this->limit_custid_role !== false) {
            function_requirements('has_acl');
            $this->lacks_limit_role = !has_acl($this->limit_custid_role);
        }
        if ($this->admin != true || isset($this->request['custid'])) {
            $this->limit_custid = true;
        } else {
            $this->limit_custid = $this->lacks_limit_role;
        }
    }

    /**
     * whether the list is under a role limit: an admin, on a page that set a limit role
     * (set_limit_custid_role()) the admin lacks, whether or not the request names a customer.
     * a client's limit and the requested customer of an admin holding the role (or on a page
     * with no role) are not role limits. such a list always needs a tenant term, and fails
     * closed where none can be built (tables with no column custid_match finds): the role
     * exists to keep the admin from every other account's rows, and without a column to pin
     * the rows to their own account or to the requested customer the list would show them
     * all. so the list, count and exports get '0=1' (tenant_search_sql()) and the edit, add and
     * delete handlers refuse (ajax_edit_handler(), refuse_role_limited_write()). a custid in
     * the request used to lift this, which gave such an admin every row of crud_paypal_history
     * and crud_paypal_transactions (and an update by any id) for adding &custid=N.
     *
     * @return bool
     */
    protected function is_role_limited()
    {
        return $this->admin == true && $this->limit_custid_role !== false && $this->lacks_limit_role === true;
    }

    /**
     * whether a write is pinned to one account and must not move a row out of it: a client's
     * (always limited to their own account) or a role limited admin's (is_role_limited(), with
     * or without a requested customer). the tenant term pins only the edit's where, so a posted
     * account column (is_account_column()) in the set moved the row to another account (update
     * vps set vps_custid='9', ... where vps_id='5' and vps_custid='7'): for them the edit
     * handler refuses an edit posting any other account there, and leaves the row's own account
     * (which the edit form posts back on every save) out of the set. an unlimited admin, and an
     * admin holding the page's role (or on a page with no role) who opened the list for a
     * requested customer, still change the account column (moving a service to another account).
     *
     * @return bool
     */
    protected function locks_account_columns()
    {
        return $this->limit_custid == true && ($this->admin !== true || $this->is_role_limited());
    }

    /**
     * whether $column names an account column: custid_match (*_custid by default) matches it
     * as spelled or in its simple lower case form. mysql resolves a column name in any letter
     * case to the same column (VPS_CUSTID, Vps_Custid, even vps_custİd with U+0130, all write
     * vps_custid; accented letters, the dotless ı and the long ſ do not fold), and the edit
     * writes the column as the page's query spells it, so a pattern matched only as spelled let
     * a query selecting vps.VPS_CUSTID (or vps.Vps_Custid as owner) take a posted account into
     * the set. the page patterns are lower case; matching the spelling too keeps any other
     * pattern matching what it did. the edit handler, tenant_where() and tenant_search_terms()
     * all decide with this, so the column an edit may not move is the one the where pins.
     *
     * @param string $column a column name, without its table
     * @return bool
     */
    protected function is_account_column($column)
    {
        if (!is_string($this->custid_match) || !is_string($column)) {
            return false;
        }
        if (preg_match($this->custid_match, $column) === 1) {
            return true;
        }
        // mb simple lower case maps one code point to one, as mysql's identifier compare does
        // (strtolower() leaves U+0130 alone); php before 7.3 has only the ascii fold
        $lower = defined('MB_CASE_LOWER_SIMPLE') ? mb_convert_case($column, MB_CASE_LOWER_SIMPLE, 'UTF-8') : strtolower($column);
        return $lower !== $column && preg_match($this->custid_match, $lower) === 1;
    }

    /**
     * refuses an add or delete of a role limited admin (is_role_limited()) on a crud whose
     * tables have no account column to pin the write to (no tenant term), as the edit handler
     * and the list do. prints the refusal and logs a warning.
     *
     * @param string $action add or delete
     * @param string $refusal what the js handler is answered
     * @return bool true when refused
     */
    protected function refuse_role_limited_write($action, $refusal)
    {
        if ($this->type == 'function' || !$this->is_role_limited() || count($this->tenant_search_terms()) > 0) {
            return false;
        }
        $this->log("refused crud {$this->choice} {$action}: the admin lacks the {$this->limit_custid_role} role and its tables have no account column", __LINE__, __FILE__, 'warning');
        echo $refusal;
        return true;
    }

    /**
     * the extra_url_args every crud url of the page carries (the list/paging/search/export
     * action and the edit and delete form actions, all built by the templates from this):
     * the page's own set_extra_url_args(), plus the request custid an admin opened the list
     * with, as it was requested (it may be a uuid; url encoded here, the templates html escape
     * the whole string). apply_request_data() limits the list to that customer, and the ajax
     * calls (paging, sorting, searching, exporting, editing) go to ajax.php with only these
     * args, so without it page 2, a search, an export or an edit lost the customer: an admin
     * holding the limit role got every account's rows, one lacking it their own (none).
     * a client's request custid is ignored (a client is always limited to their own account),
     * and a page whose own args already name a custid keeps it as it is.
     *
     * @return string
     */
    public function get_extra_url_args()
    {
        $args = (string) $this->extra_url_args;
        if ($this->admin != true || !isset($this->request['custid'])) {
            return $args;
        }
        $custid = $this->request['custid'];
        if (!is_string($custid) && !is_int($custid)) {
            return $args;
        }
        parse_str(ltrim($args, '?&'), $own);
        if (array_key_exists('custid', $own)) {
            return $args;
        }
        return $args.'&custid='.rawurlencode((string) $custid);
    }

    /**
     * inserts the javascript sources required for the crud system into the html header
     *
     * @return void
     */
    public function add_js_headers()
    {
        add_js('bootstrap');
        add_js('font-awesome');
        add_js('tempusdominus');
        \MyAdmin\App::output()->addHeadJsFile('/lib/twbs-pagination/jquery.twbsPagination.js');
        \MyAdmin\App::output()->addHeadJsFile('/js/crud.js');
    }

    /**
     * starts/displays the crud interface handler
     *
     * @param string $view optional default view, this defaults to the list view if not specified.  alternatively you can pass 'add' for the add interface
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function go($view = 'list')
    {
        if ($this->ajax !== false) {
            $view = 'ajax';
        }
        switch ($view) {
            case 'ajax':
                $this->ajax_handler();
                break;
            case 'list':
                $this->list_records();
                break;
            case 'add':
                $this->order_form();
                break;
        }
        \Tracy\Debugger::barDump($this, 'Crud');
        if ($this->return_output == true) {
            return $this->output;
        } else {
            return $this;
        }
    }

    /**
     * if called via an ajax request the processing is passed off to this handler, which takes care of ajax listing updates, adding, editing, deleting, searching, and exporting records
     *
     */
    public function ajax_handler()
    {
        //$this->log("CRUD {$this->title} {$action} Handling", __LINE__, __FILE__, 'debug');
        // generic data to get us here is in _GET, while the specific fields are all in _POST
        //$this->log(print_r($_GET, TRUE), __LINE__, __FILE__, 'debug');
        //$this->log(print_r($_POST, TRUE), __LINE__, __FILE__, 'debug');
        switch ($this->ajax) {
            case 'edit':
                $this->ajax_edit_handler();
                break;
            case 'list':
                $this->ajax_list_handler();
                break;
            case 'add':
                $this->ajax_add_handler();
                break;
            case 'delete':
                $this->ajax_delete_handler();
                break;
            case 'export':
                $this->ajax_export_handler();
                break;
            default:
                // $action was never set here, a php warning on every unknown action (action[]=x
                // too) and no action in the log. the request's action: a string (first 64
                // bytes), else its type (an array for action[]=x, NULL when missing)
                $this->log('Invalid Crud '.$this->title.' Action '.json_encode(is_string($this->ajax) ? substr($this->ajax, 0, 64) : gettype($this->ajax), JSON_INVALID_UTF8_SUBSTITUTE), __LINE__, __FILE__, 'warning');
                break;
        }
    }

    /**
     * gate for every ajax write (edit/add/delete). the handlers write by the posted
     * primary key, so they are admin-only unless the page opted in with
     * allow_client_edit(), and the post must carry the session's crud csrf token
     * (the edit/delete modals in the crud templates send it as csrf_token).
     *
     * @param string $action edit, add or delete
     * @return bool
     */
    protected function authorize_write($action)
    {
        if ($this->admin !== true && $this->client_edit !== true) {
            $this->log("refused crud {$this->choice} {$action} for a non-admin session", __LINE__, __FILE__, 'warning');
            echo 'Not Authorized';
            return false;
        }
        if ($this->verify_write_csrf() !== true) {
            $this->log("refused crud {$this->choice} {$action}, missing or invalid csrf token", __LINE__, __FILE__, 'warning');
            echo 'Invalid or expired form token, please reload the page and try again';
            return false;
        }
        return true;
    }

    /**
     * checks the posted csrf_token against the session one (fails closed when the
     * MyAdmin csrf helper is not loaded).
     *
     * @return bool
     */
    protected function verify_write_csrf()
    {
        if (!function_exists('verify_csrf')) {
            return false;
        }
        return verify_csrf(self::CSRF_NAME) === true;
    }

    /**
     * the session csrf token for the edit/delete forms, '' when there is no session helper.
     * not one-time, so several edits from one list page all pass.
     *
     * @return string
     */
    public function get_write_csrf_token()
    {
        if (!function_exists('get_csrf')) {
            return '';
        }
        return (string) get_csrf(self::CSRF_NAME);
    }

    /**
     * where terms pinning a write on $table to the session's account: one per column
     * of the table that is an account column (is_account_column(): $custid_match, *_custid
     * by default, in any letter case).
     *
     * @param string $table
     * @return string[]
     */
    protected function tenant_where($table)
    {
        $where = [];
        if (!isset($this->tables[$table]) || !is_array($this->tables[$table]) || !is_string($this->custid_match)) {
            return $where;
        }
        $custid = $this->db->real_escape((string) (int) $this->custid);
        foreach (array_keys($this->tables[$table]) as $column) {
            if ($this->is_account_column((string) $column)) {
                $where[] = "{$column}='{$custid}'";
            }
        }
        return $where;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function escape_html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * the edit form fills its inputs from crud_rows, json that carries U+FFFD in place of each
     * invalid utf-8 byte of a stored value (JSON_INVALID_UTF8_SUBSTITUTE), and posts every
     * field back, so saving such a row wrote the substituted text over stored bytes the user
     * never touched. given the written fields whose posted value holds U+FFFD, this reads
     * their stored values (the row of $where: the primary key, the table's query_where and the
     * tenant terms, exactly what the update is limited to) and returns those whose stored
     * value is not valid utf-8 and whose posted value is its substituted form,
     * json_decode(json_encode($stored, JSON_INVALID_UTF8_SUBSTITUTE)): they are unchanged.
     * a stored value that is valid utf-8, a changed value, or a row the read does not find
     * leaves the field to be written as before. only called when a posted value holds U+FFFD,
     * so a normal edit runs no extra query.
     *
     * @param string $table the table the edit writes
     * @param string[] $where the update's where terms
     * @param array<int, array{column: string, posted: string, query: int, insert: int}> $candidates
     * @return array<int, array{column: string, posted: string, query: int, insert: int}> the unchanged ones
     */
    protected function unchanged_substituted_fields($table, array $where, array $candidates)
    {
        $columns = [];
        foreach ($candidates as $candidate) {
            // only real columns of the table (from its 'show full columns'), quoted
            if (is_string($candidate['column']) && isset($this->tables[$table][$candidate['column']])) {
                $columns[$candidate['column']] = '`'.str_replace('`', '``', $candidate['column']).'`';
            }
        }
        if (count($columns) == 0 || count($where) == 0) {
            return [];
        }
        $this->db->query('select '.implode(', ', $columns).' from '.$table.' where '.implode(' and ', $where).' limit 1', __LINE__, __FILE__);
        if (!$this->db->next_record(MYSQL_ASSOC) || !is_array($this->db->Record)) {
            return [];
        }
        $stored = $this->db->Record;
        $unchanged = [];
        foreach ($candidates as $candidate) {
            $value = $stored[$candidate['column']] ?? null;
            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')
                && json_decode((string) json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE)) === $candidate['posted']) {
                $unchanged[] = $candidate;
            }
        }
        return $unchanged;
    }

    /**
     * whether a row of $table matches $where (the edit's whole where: the primary key, the
     * table's query_where and the tenant terms). the edit handler asks only when the posted
     * primary key holds U+FFFD: a stored key that is not valid utf-8 comes back from the list
     * substituted, and the update would name no row. a key that really holds U+FFFD finds no
     * row either when the query_where or the tenant terms exclude it. a where with no terms,
     * or a query that fails, finds none.
     *
     * @param string $table the table the edit writes
     * @param string[] $where the update's where terms
     * @return bool
     */
    protected function row_exists($table, array $where)
    {
        if (count($where) == 0) {
            return false;
        }
        $this->db->query('select 1 from '.$table.' where '.implode(' and ', $where).' limit 1', __LINE__, __FILE__);
        return $this->db->next_record(MYSQL_ASSOC) && is_array($this->db->Record);
    }

    /**
     * handler function to process the ajax edit requests
     */
    public function ajax_edit_handler()
    {
        if ($this->edit_row == false) {
            echo 'Editing Disabled';
            return false;
        }
        if (!$this->authorize_write('edit')) {
            return false;
        }
        $fields = $_POST;
        $query_fields = [];
        $insert_fields = [];
        $insert_values = [];
        $query_where = [];
        $query_table = null;
        $has_primary_key = false;
        $valid = true;
        // the written fields whose posted value holds U+FFFD, see unchanged_substituted_fields()
        $substituted = [];
        // the written fields that are account columns (is_account_column()), see locks_account_columns()
        $account_fields = [];
        // whether a posted primary key holds U+FFFD, see row_exists()
        $key_substituted = false;
        $this->errors = [];
        $this->error_fields = [];
        foreach ($fields as $field => $value) {
            // a secret column comes back from the edit form as the mask (or empty); only a
            // value that was actually typed in is written, the masked one never is
            if ($this->is_secret_field($field) && ($value === '' || $value === null || $this->is_secret_mask_value($field, $value))) {
                continue;
            }
            $posted = $value;
            // match up fields
            if (isset($this->query_fields[$field])) {
                $origField = $field;
                $field = $this->query_fields[$field];
                if (preg_match('/^((?P<table>[^\.]+)\.){0,1}(?P<field>[^\.]+)$/m', $field, $matches)) {
                    $field = $matches['field'];
                    if (isset($matches['table']) && $matches['table'] != '') {
                        $tables = [$matches['table'] => $this->tables[$matches['table']]];
                        $query_table = $matches['table'];
                    } else {
                        $tables = $this->tables;
                    }
                    $field = $matches['field'];
                } else {
                    $tables = $this->tables;
                }
                foreach ($tables as $t_table => $t_fields) {
                    if (isset($t_fields[$field])) {
                        $query_table = $t_table;
                        break;
                    }
                }
                $already_safe = false;
                // validate fields
                foreach ($this->validations[$origField] as $validation) {
                    if (!is_array($validation)) {
                        switch ($validation) {
                            case 'abs':
                                $value = abs($value);
                                break;
                            case 'int':
                                // TODO / FIXME _ check the isset() part here, if its not set i probably should fail it.
                                if (isset($value) && $value != (int)$value) {
                                    $this->errors[] = 'Invalid '.$this->label($field).' "'.$value.'"';
                                    $this->error_fields[] = $field;
                                    $valid = false;
                                }
                                break;
                            case 'notags':
                                if ($value != strip_tags($value)) {
                                    $this->errors[] = 'Invalid '.$this->label($field).' "'.$value.'"';
                                    $this->error_fields[] = $field;
                                    $valid = false;
                                }
                                break;
                            case 'uuid':
                                // convert the hyphenated uuid string from the form back to the raw
                                // binary(16) form it's stored in (see uuid_to_bin()/bin_to_uuid()).
                                // no swap flag: uuid_to_bin() swaps only for v1, and these columns
                                // hold v7, which stores in natural order. forcing the swap here
                                // wrote a mangled uuid for every edit.
                                if (isset($value) && $value !== '') {
                                    $binary = uuid_to_bin($value);
                                    if ($binary === false) {
                                        $this->errors[] = 'Invalid '.$this->label($field).' "'.$value.'"';
                                        $this->error_fields[] = $field;
                                        $valid = false;
                                    } else {
                                        $value = $binary;
                                    }
                                }
                                break;
                            case 'trim':
                                if (isset($value)) {
                                    $value = trim($value);
                                }
                                break;
                            case 'lower':
                                if (isset($value)) {
                                    $value = strtolower($value);
                                }
                                break;
                            case 'in_array':
                                if (isset($value)) {
                                    $values = [];
                                    if (!is_array($value)) {
                                        $value = [$value];
                                    }
                                    foreach ($value as $t_value) {
                                        if (!in_array($t_value, $this->labels[$field])) {
                                            $this->errors[] = 'Invalid '.$this->label($field).' "'.$t_value.'"';
                                            $this->error_fields[] = $field;
                                            $valid = false;
                                        }
                                        $values[] = $this->db->real_escape($t_value);
                                    }
                                    $value = implode("','", $values);
                                    unset($values);
                                    $already_safe = true;
                                }
                                break;
                        }
                    } else {
                        if (isset($validation['in_array'])) {
                            if (isset($value)) {
                                $values = [];
                                if (!is_array($value)) {
                                    $value = [$value];
                                }
                                foreach ($value as $t_value) {
                                    if (!in_array($t_value, $validation['in_array'])) {
                                        $this->errors[] = 'Invalid '.$this->label($field).' "'.$t_value.'"';
                                        $this->error_fields[] = $field;
                                        $valid = false;
                                    }
                                    $values[] = $this->db->real_escape($t_value);
                                }
                                $value = implode(',', $values);
                                unset($values);
                                $already_safe = true;
                            }
                        }
                    }
                }
                // build query
                if ($already_safe != true) {
                    $safe_value = $this->db->real_escape($value);
                } else {
                    $safe_value = $value;
                }
                if ($field == $this->primary_key) {
                    $has_primary_key = true;
                    if (is_string($posted) && strpos($posted, self::UTF8_REPLACEMENT_CHAR) !== false) {
                        $key_substituted = true;
                    }
                    $query_where[] = "{$field}='{$safe_value}'";
                    $insert_fields[] = $field;
                    $insert_values[] = "'{$safe_value}'";
                } else {
                    // see which fields are editable
                    if (!in_array($field, $this->disabled_fields)) {
                        if (is_string($posted) && strpos($posted, self::UTF8_REPLACEMENT_CHAR) !== false) {
                            $substituted[] = ['column' => $field, 'posted' => $posted, 'query' => count($query_fields), 'insert' => count($insert_fields)];
                        }
                        // the column the posted field maps to, whatever name the list selects it
                        // as and however its query spells the column's letter case
                        if ($this->is_account_column($field)) {
                            $account_fields[] = ['column' => $field, 'posted' => $posted, 'query' => count($query_fields), 'insert' => count($insert_fields)];
                        }
                        $query_fields[] = "{$field}='{$safe_value}'";
                        $insert_fields[] = $field;
                        $insert_values[] = "'{$safe_value}'";
                    }
                }
            }
        }
        if (count($query_fields) > 0) {
            // never a where-less (every row) write: the row has to be named by its primary
            // key, and that key has to be a column of the table being written
            if ($query_table === null || $has_primary_key !== true
                || (isset($this->tables[$query_table]) && is_array($this->tables[$query_table]) && !isset($this->tables[$query_table][$this->primary_key]))) {
                $this->log("refused crud {$this->choice} edit with no primary key term for table ".var_export($query_table, true), __LINE__, __FILE__, 'warning');
                echo 'There was nothing to update';
                return false;
            }
            //$this->log("Query Table {$query_table} Where " . implode(',', $query_where).' Class Where '.implode(',' ,$this->query_where[$query_table]), __LINE__, __FILE__, 'debug');
            // only tables named first in a join condition get a query_where entry
            $query_where = array_merge($query_where, $this->query_where[$query_table] ?? []);
            // a list limited to the session's account writes only that account's rows
            if ($this->limit_custid == true) {
                $tenant_where = $this->tenant_where($query_table);
                if (count($tenant_where) == 0 && $this->admin !== true) {
                    $this->log("refused crud {$this->choice} client edit of {$query_table}, it has no account column", __LINE__, __FILE__, 'warning');
                    echo 'Editing Disabled';
                    return false;
                }
                // an admin limited by a role they lack (custid requested or not) can not be
                // pinned to their own account or to the requested customer on a table with no
                // account column either: refused like the list, which shows them no rows (see
                // tenant_search_sql(), is_role_limited())
                if (count($tenant_where) == 0 && $this->is_role_limited()) {
                    $this->log("refused crud {$this->choice} edit of {$query_table}: the admin lacks the {$this->limit_custid_role} role and the table has no account column", __LINE__, __FILE__, 'warning');
                    echo 'Editing Disabled';
                    return false;
                }
                $query_where = array_merge($query_where, $tenant_where);
                // the where pins the row to the account; for a client or a role limited admin
                // the set must not move it to another: an edit posting another account in an
                // account column is refused whole, and the row's own account posted back is
                // left out of the set (an unlimited admin's, or a role holder's on a requested
                // customer, is written)
                if ($this->locks_account_columns() && count($account_fields) > 0) {
                    $pinned = (string) (int) $this->custid;
                    $dropped = [];
                    $moves_account = false;
                    foreach ($account_fields as $entry) {
                        // unset by index, not reindexed: the U+FFFD entries below address theirs
                        unset($query_fields[$entry['query']], $insert_fields[$entry['insert']], $insert_values[$entry['insert']]);
                        $dropped[$entry['query']] = true;
                        // the edit form posts the row's own account back on every save: no warning.
                        // any other account refuses the edit (here, or by its validation errors)
                        if (!is_scalar($entry['posted']) || (string) $entry['posted'] !== $pinned) {
                            $this->log("refused crud {$this->choice} edit of {$query_table}: the posted account column {$entry['column']} would move the row off account {$pinned}", __LINE__, __FILE__, 'warning');
                            $moves_account = true;
                        }
                    }
                    $kept = [];
                    foreach ($substituted as $entry) {
                        if (!isset($dropped[$entry['query']])) {
                            $kept[] = $entry;
                        }
                    }
                    $substituted = $kept;
                    // an invalid post reports its validation errors first, as before (below)
                    if ($valid == true && $moves_account) {
                        // writing the other fields and answering ok would tell the user the
                        // account changed: nothing is written
                        echo _('The account of this row cannot be changed here.');
                        return false;
                    }
                    if ($valid == true && count($query_fields) == 0) {
                        $this->log("crud {$this->choice} edit of {$query_table} has nothing left to write: its account column is left out", __LINE__, __FILE__, 'info');
                        echo 'There was nothing to update';
                        return false;
                    }
                }
            }
            // a primary key posted with U+FFFD is the substituted form of a stored key that is
            // not valid utf-8 (the list's json carries it so), unless the key really holds
            // U+FFFD: the update's where matched no row, wrote nothing, and the edit still
            // answered ok. only then is the row looked up first, under the update's whole where
            // (a normal edit runs no extra query); when it is not found nothing is written and
            // the user is told. no row matching can also mean the key really holds U+FFFD and
            // the table's query_where or the tenant terms exclude its row, so neither the answer
            // nor the log line names a cause. an invalid post reports its validation errors
            // first (below)
            if ($valid == true && $key_substituted && !$this->row_exists($query_table, $query_where)) {
                $this->log("refused crud {$this->choice} edit of {$query_table}: no row matched the edit's where, whose primary key {$this->primary_key} holds U+FFFD", __LINE__, __FILE__, 'warning');
                echo _('This row cannot be edited here.');
                return false;
            }
            // a field the form posted back as the U+FFFD substituted form of a stored value
            // that is not valid utf-8 was not touched: it is left out, not written over the
            // stored bytes (see unchanged_substituted_fields())
            if ($valid == true && count($substituted) > 0) {
                $unchanged = $this->unchanged_substituted_fields($query_table, $query_where, $substituted);
                foreach ($unchanged as $entry) {
                    unset($query_fields[$entry['query']], $insert_fields[$entry['insert']], $insert_values[$entry['insert']]);
                }
                if (count($unchanged) > 0) {
                    $query_fields = array_values($query_fields);
                    $insert_fields = array_values($insert_fields);
                    $insert_values = array_values($insert_values);
                }
                if (count($query_fields) == 0) {
                    $this->log("crud {$this->choice} edit of {$query_table} changed nothing: every field was posted back as stored", __LINE__, __FILE__, 'info');
                    echo 'There was nothing to update';
                    return false;
                }
            }
            // update database
            if ($this->insert_over_update == true) {
                $query = 'insert into '.$query_table.' ('.implode(', ', $insert_fields).') values ('.implode(', ', $insert_values).' where '.implode(' and ', $query_where);
            } else {
                $query = 'update '.$query_table.' set '.implode(', ', $query_fields).' where '.implode(' and ', $query_where);
            }
            if ($valid == true) {
                //$this->log("i want to run query {$query}", __LINE__, __FILE__, 'info');
                $this->db->query($query, __LINE__, __FILE__);
                // send response for js handler (the sql is not echoed back)
                echo 'ok';
                echo '<br>updated';
            } else {
                $this->log("error validating so could not run query {$query}", __LINE__, __FILE__, 'warning');
                // send response for js handler (crud.js puts it in the modal with .html())
                echo 'There was an error with validation:<br>'.implode('<br>', array_map([$this, 'escape_html'], $this->errors)).' with the fields '.implode(', ', array_map([$this, 'escape_html'], $this->error_fields));
            }
        } else {
            $this->log('crud error nothing to update ', __LINE__, __FILE__, 'warning');
            // send response for js handler
            echo 'There was nothing to update';
        }
    }

    /**
     * handler function to process the ajax add requests
     */
    public function ajax_add_handler()
    {
        if ($this->add_row == false) {
            echo 'Adding Disabled';
            return false;
        }
        if (!$this->authorize_write('add')) {
            return false;
        }
        if ($this->refuse_role_limited_write('add', 'Adding Disabled')) {
            return false;
        }
        // nothing is written here yet. when it is: for a client or a role limited admin
        // (locks_account_columns()) every account column (is_account_column()) of the new row
        // is the pinned account ($this->custid), never a posted value, as the edit handler
        // refuses an edit posting another account there; an unlimited admin or a role holder
        // may post one
        // generic data to get us here is in _GET, while the specific fields are all in _POST
        // match up fields
        // see which fields are editable
        // validate fields
        // build query
        // update database
        // send response for js handler
    }

    /**
     * handler function to process the ajax delete requests
     */
    public function ajax_delete_handler()
    {
        if ($this->delete_row == false) {
            echo 'Deleting Disabled';
            return false;
        }
        if (!$this->authorize_write('delete')) {
            return false;
        }
        if ($this->refuse_role_limited_write('delete', 'Deleting Disabled')) {
            return false;
        }
        // match up row
        // build query
        // update db
        // send response for js handler
    }

    /**
     * handler function to process the ajax export requests
     *
     * Export in these formats:
     * 		JSON, XML, SQL, PHP,
     * 		Markdown, Wiki markup, BBcode,
     * 		BIFF XLS, Excel XLSX, PDF,
     * 		ODS, CSV, TXT
     *
     * Print
     * 		http://stackoverflow.com/questions/10174412/print-php-table-with-print-function-via-printer
     * 		http://jsfiddle.net/hBCgA/
     * 		https://www.sitepoint.com/community/t/printing-in-javascript-and-php/42638/2
     */
    public function ajax_export_handler()
    {
        // get export type
        $format = $this->request['format'];
        $formats = $this->get_export_formats();
        // get data
        // convert data
        // send data
        // a format that is not a scalar (format[]=x) is no format: isset() with an array
        // offset threw a TypeError
        if (!is_scalar($format) || !isset($formats[$format])) {
            echo 'Error';
            return false;
        }
        $info = $formats[$format];
        $filename = slugify($this->title).'_'.date('Y-m-d').'.'.$format;
        $function = 'export_'.$format;
        $headers = [];
        // Redirect output to a client’s web browser (OpenDocument)
        $headers[] = 'Content-Type: '.$info['type'];
        $headers[] = 'Content-Disposition: '.$info['disposition'].';filename="'.$filename.'"';
        $headers[] = 'Cache-Control: max-age=0';
        // If you're serving to IE 9, then the following may be needed
        //$headers[] = 'Cache-Control: max-age=1';
        // If you're serving to IE over SSL, then the following may be needed
        $headers[] = 'Expires: Mon, 26 Jul 1997 05:00:00 GMT'; // Date in the past
        $headers[] = 'Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'; // always modified
        $headers[] = 'Cache-Control: cache, must-revalidate'; // HTTP/1.1
        $headers[] = 'Pragma: public'; // HTTP/1.0
        $this->get_all_rows();
        echo $this->{$function}($headers);
        return true;
    }

    /**
     * runs through all the query rows and builds up an array for use with other functions like export
     *
     * @param int $resultType the result type, can pass MYSQL_ASSOC, MYSQL_NUM, and other stuff
     * @return void
     */
    public function get_all_rows($resultType = MYSQL_ASSOC)
    {
        $this->run_list_query();
        $this->rows = [];
        while ($this->next_record($resultType)) {
            $record = $this->get_record();
            // the hidden fields only exist to be pasted into a link, so they are
            // dropped here - the exports built off $this->rows key their columns off
            // the record itself, and they keep exactly the columns they had before.
            foreach ($this->hidden_fields as $field) {
                unset($record[$field]);
            }
            $this->rows[] = $record;
        }
    }

    /**
     * handles the ajax request to get a list of records
     *
     */
    public function ajax_list_handler()
    {
        // apply pagination
        // apply sorting
        $this->run_list_query();
        $rows = [];
        $cells = [];
        // $this->next_record(), as list_records() and get_all_rows() use: a 'function' crud's
        // rows come from its CrudFunctionIterator, not the db handle (whose next_record() gave
        // a function list no rows, or the rows of whatever query that handle ran last, so
        // sorting, paging or searching one emptied it)
        while ($this->next_record(MYSQL_ASSOC)) {
            //$json[] = $this->db->Record;
            $record = $this->get_record();
            $rows[] = $record;
            $cells[] = $this->ajax_cells($record);
        }
        // send response for js handler. crud.js pastes each field into the empty row
        // template's %field% placeholders and appends the result as html, so it takes those
        // values from 'cells' (escaped here the same way decorate_field() escapes the rows
        // rendered with the page). the plain 'rows' it keeps as crud_rows, which the edit form
        // fills its inputs from with .val(): escaped values there would be saved back escaped.
        // JSON_INVALID_UTF8_SUBSTITUTE: one invalid utf-8 byte in any stored value (the rows
        // carry them as stored) made json_encode() return false, an empty answer, and the
        // whole page of the list blank; it becomes U+FFFD instead
        header('Content-type: application/json');
        echo json_encode(['rows' => $rows, 'cells' => $cells], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * one row of the ajax list's 'cells': html_safe_record() (every value escaped for pasting
     * into the row template), plus, for each visible column whose filters can not be filled in
     * from the template (has_server_decoration()), the column's decorated html under
     * '<field>:html' (DECORATED_CELL_SUFFIX), which is what the row template holds in that
     * column. so a 'function' or 'simple' filtered column shows the same html after a refill
     * as on the page it was rendered with (decorate_field() with the row; a function filter
     * escapes what it puts in), and the other columns' string filters still paste the plain
     * escaped value of that field.
     *
     * @param array $record a fetched record (get_record())
     * @return array
     */
    public function ajax_cells($record)
    {
        $cells = $this->html_safe_record($record);
        if (!is_array($record)) {
            return $cells;
        }
        foreach (array_keys($record) as $field) {
            if (!is_string($field) || $this->is_hidden_field($field) || !$this->has_server_decoration($field)) {
                continue;
            }
            $html = $this->decorate_field($field, $record);
            $cells[$field.self::DECORATED_CELL_SUFFIX] = is_scalar($html) || $html === null ? (string) $html : '';
        }
        return $cells;
    }

    /**
     * whether a column's filters have to run on the server for every row: any filter that is
     * not a 'string' filter. a string filter's output is a template (its %field% placeholders
     * are filled in from the ajax cells, see crud_fill_row() in crud.js), so the row template
     * can hold it; a 'simple' filter (value replacements) or a 'function' filter (any php)
     * decides its output from the value itself, so run on a placeholder for the template it
     * gave the placeholder back unreplaced (licenses' paid icons came back as 1/2 after a
     * refill) or worse. such a column's template cell is '%<field>:html%' and its decorated
     * html comes with every ajax row (ajax_cells()).
     *
     * @param string $field
     * @return bool
     */
    public function has_server_decoration($field)
    {
        if (!isset($this->filters[$field]) || !is_array($this->filters[$field])) {
            return false;
        }
        foreach ($this->filters[$field] as $filter) {
            // decorate_field() applies these two besides 'string' (any other type is ignored
            // there, so the plain template placeholder shows the same)
            if (isset($filter['type']) && ($filter['type'] === 'simple' || $filter['type'] === 'function')) {
                return true;
            }
        }
        return false;
    }

    /**
     * a fetched record with every value made safe to paste into the row template's html (see
     * ajax_list_handler() and crud_fill_row() in crud.js): escaped while html filtering is on,
     * except the raw html columns (set_raw_html_fields()); null becomes ''. with filtering off
     * the values go out as they are, as the page asked.
     *
     * @param array $record
     * @return array
     */
    public function html_safe_record($record)
    {
        if (!is_array($record)) {
            return $record;
        }
        foreach ($record as $field => $value) {
            if ($value === null) {
                $record[$field] = '';
            } elseif (is_scalar($value) && $this->use_html_fitering == true && !$this->is_raw_html_field((string) $field)) {
                $record[$field] = self::escape_cell($value);
            }
        }
        return $record;
    }

    /**
     * html escapes one value for an element's text or a quoted attribute (single or double):
     * htmlspecialchars with ENT_QUOTES, and ENT_SUBSTITUTE so a stray invalid utf-8 byte
     * becomes U+FFFD instead of htmlspecialchars() returning '' for the whole value. it only
     * covers the escaped value: the ajax list also sends the stored rows, which still hold the
     * byte, and its answer survives that because ajax_list_handler() json encodes with
     * JSON_INVALID_UTF8_SUBSTITUTE.
     *
     * @param mixed $value
     * @return string
     */
    public static function escape_cell($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * json for a javascript value inside an inline <script> block (INLINE_JSON_FLAGS): the
     * templates printed {$rows|json_encode} with no flags, so one invalid utf-8 byte in a stored
     * value printed 'var crud_rows = ;' (a syntax error that broke the edit and delete buttons),
     * and a stored '<!--<script' could derail the script block. the value javascript reads is
     * the same. $fallback is printed when json_encode() still fails (a nan, inf or depth error).
     *
     * @param mixed $value
     * @param string $fallback
     * @return string
     */
    public static function inline_json($value, $fallback = 'null')
    {
        $json = json_encode($value, self::INLINE_JSON_FLAGS);
        return $json === false ? $fallback : $json;
    }

    /**
     * loads all the table schemas into an array
     *
     */
    public function load_tables()
    {
        $db = clone $this->db;
        $db->query("show full tables where Table_Type = 'BASE TABLE'", __LINE__, __FILE__);
        while ($db->next_record(MYSQL_NUM)) {
            $this->tables[$db->f(0)] = null;
            $this->tables[$db->f(0)] = $this->get_table_details($db->f(0));
        }
    }

    /**
     * parses a query using crodas/sql-parser giving structured detailed information about the query
     * and then parses that information to use in the crud system
     *
     * @param bool|false|string $query optional query to parse, if FALSE or not passed it uses the one associated w/ the crud request
     */
    public function parse_query($query = false)
    {
        if ($query == false) {
            $query = $this->query;
        }
        //require_once(INCLUDE_ROOT.'/../vendor/autoload.php');
        //require_once(INCLUDE_ROOT.'/../vendor/crodas/sql-parser/src/SQLParser.php');
        //require_once INCLUDE_ROOT.'/../vendor/crodas/sql-parser/src/autoload.php';
        $parser = new \SQLParser;
        $this->queries = $parser->parse($query);
        $this->parse_query_fields();
        //_debug_array($queries);
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries, TRUE).'</pre>');
    }

    /**
     * handles joins from the query parser results determining what fields and such are used in the join
     *
     * @param string $table the main table from the query
     * @param mixed $joinArray the join array
     */
    public function join_handler($table, $joinArray)
    {
        $condition_type = $joinArray->getType();					// AND, =
        if ($condition_type == 'AND') {
            foreach ($joinArray->GetMembers() as $member => $memberArray) {
                $this->join_handler($table, $memberArray);
            }
        } elseif ($condition_type == 'EXPR') {
            // expr should be statements to wrap around (   )  i think
            foreach ($joinArray->GetMembers() as $member => $memberArray) {
                $this->join_handler($table, $memberArray);
            }
        } elseif ($condition_type == 'OR') {
            // expr should be statements to wrap around (   )  i think
            foreach ($joinArray->GetMembers() as $member => $memberArray) {
                $this->join_handler($table, $memberArray);
            }
        } elseif ($condition_type == '=') {
            //echo print_r($memberArray,true)."<br>";
                //echo "Type:$type<br>";
                //echo print_r($memberArray->getMembers(), TRUE)."<br>";
                $member1Type = $joinArray->getMembers()[0]->getType();			// COLUMN
                $member1Members = $joinArray->getMembers()[0]->getMembers();		// array('accounts', 'account_id') or array('account_key')
                if ($member1Type == 'COLUMN') {
                    if (count($member1Members) == 1) {
                        $member1Table = $table;
                        $member1Field = $member1Members[0];
                    } else {
                        $member1Table = $member1Members[0];
                        $member1Field = $member1Members[1];
                    }
                    //$this->add_output("adding table {$member1Table}");
                    if (!isset($this->query_where[$member1Table])) {
                        $this->query_where[$member1Table] = [];
                    }
                }
            $member2Type = $joinArray->getMembers()[1]->getType();			// COLUMN or VALUE
                $member2Members = $joinArray->getMembers()[1]->getMembers();		// array('accounts_ext', 'account_id') or array('roles', '2')
                if ($member2Type == 'COLUMN') {
                    if (count($member2Members) == 1) {
                        $member2Table = $table;
                        $member2Field = $member2Members[0];
                    } else {
                        $member2Table = $member2Members[0];
                        $member2Field = $member2Members[1];
                    }
                } elseif ($member2Type == 'VALUE') {
                    $member2Value = $member2Members[0];
                    //$this->query_where[$member1Table][] =  "{$member1Table}.{$member1Field} {$type} '{$member2Value}'";
                    $this->query_where[$member1Table][] =  "{$member1Field}{$condition_type}'{$member2Value}'";
                }
        } else {
            $this->log("Don't know how to handle Type {$condition_type} in Join Array " . print_r($joinArray, true), __LINE__, __FILE__, 'warning');
        }
        //echo _debug_array($joinArray->getCondition()->getType(), TRUE)."<br>";
            //echo _debug_array($joinArray->getCondition(), TRUE)."<br>";
            //echo _debug_array($joinArray->getCondition()->getMembers(), TRUE)."<br>";
    }

    /**
     * parses the query fields from the SQLParser response to use in the crud system
     *
     * @param mixed $queries optional queries to parse, if left blank/false uses the crud associated parsed queries
     */
    public function parse_query_fields($queries = false)
    {
        if ($queries == false) {
            $queries = $this->queries;
        }
        ///echo _debug_array($this->queries, TRUE);
        //echo _debug_array($queries[0]->getJoins(), TRUE);
        $joins = $queries[0]->getJoins();
        if (is_array($joins) && count($joins) > 0) {
            foreach ($joins as $join => $joinArray) {
                $table = $joinArray->getTable();											// accounts_ext, vps_masters
                $table_alias = $joinArray->getAlias();
                //var_export($table_alias);
                $join_type = $joinArray->getType();										// LEFT JOIN
                //echo "Table {$table} Join Type {$join_type}<br>";
                if (!in_array($join_type, ['INNER JOIN', 'LEFT JOIN', 'LEFT OUTER JOIN'])) {
                    $this->log("Don't know how to handle Join Type {$join_type}", __LINE__, __FILE__, 'warning');
                } else {
                    $this->join_handler($table, $joinArray->getCondition());
                }
            }
        }
        // accounts_ext
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getTable(), TRUE).'</pre>');
        // LEFT JOIN
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getType(), TRUE).'</pre>');
        // =
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getType(), TRUE).'</pre>');
        // COLUMN
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[0]->getType(), TRUE).'</pre>');
        // array('accounts', 'account_id')
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[0]->getMembers(), TRUE).'</pre>');
        // COLUMN
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[1]->getType(), TRUE).'</pre>');
        // array('accounts_ext', 'account_id')
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[1]->getMembers(), TRUE).'</pre>');
        // =
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getType(), TRUE).'</pre>');
        // COLUMN
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[0]->getType(), TRUE).'</pre>');
        // array('accounts_ext', 'account_key')
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[0]->getMembers(), TRUE).'</pre>');
        // VALUE
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[1]->getType(), TRUE).'</pre>');
        // array('roles', '2')
        //$this->add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[1]->getMembers(), TRUE).'</pre>');
        /*
        $columns = $queries[0]->getColumns();
        echo '<pre style="text-align: left;">';
        echo "<br>Columns:";var_dump($columns, TRUE);
        echo "<br>Columns[0][0]:";var_dump($columns[0][0]);
        echo "<br>Type:";var_dump($columns[0][0]->getType());
        $members = $columns[0][0]->getMembers();
        echo "<br>Members:";var_dump($members);
        if (is_object($members[0])) {
            echo "<br>Type:"._debug_array($members[0]->getType(), TRUE);
            echo "<br>Members:"._debug_array($members[0]->getMembers(), TRUE);
        } else {
            echo "<br>Members:"._debug_array($members, TRUE);
        }
        echo '</pre>';
        */
        foreach ($queries[0]->getColumns() as $col => $colArray) {
            $c_type = $colArray[0]->getType();
            $fieldArray = $colArray[0]->getMembers();
            if ($c_type == 'COLUMN') {
                if (is_object($fieldArray[0])) {
                    $fType = $fieldArray[0]->getType();
                    $fMembers = $fieldArray[0]->getMembers();
                    if ($fType != 'ALL') {
                        $this->log("Don't know how to handle Field Type {$fType}, only ALL", __LINE__, __FILE__, 'warning');
                    } else {
                        // Setup all the columns
                        $this->all_fields = true;
                    }
                } else {
                    if (count($fieldArray) > 1) {
                        $table = $fieldArray[0];
                        $origField = $fieldArray[1];
                    //$origField = $table.'.'.$origField;
                    } else {
                        $table = false;
                        $origField = $fieldArray[0];
                    }
                    if (count($colArray) > 1) {
                        $field = $colArray[1];
                    } else {
                        $field = $origField;
                    }
                    $fields[$field] = ($table === false ? $origField : $table.'.'.$origField);
                }
            } elseif ($c_type == 'CALL') {
                // if sizeof colArray is 2  then [0] expr  and [1] is  the alias for field, like 'field as name'
                $call = $fieldArray[0];
                $exprs = $fieldArray[1]->getExprs();
                foreach ($exprs as $e_idx => $expr) {
                    $e_type = $expr->getType();
                    $eMembers = $expr->getMembers();
                    if (is_object($eMembers[0])) {
                        $fType = $eMembers[0]->getType();
                        $fMembers = $eMembers[0]->getMembers();
                    } else {
                        if (isset($fMembers) && count($fMembers) > 1) {
                            $fTable = $fMembers[0];
                            $fOrigField = $fMembers[1];
                        //$origField = $table.'.'.$origField;
                        } else {
                            $fTable = false;
                            $fOrigField = isset($fMembers) ? $fMembers[0] : false;
                        }
                        if (count($colArray) > 1) {
                            $field = $colArray[1];
                        } else {
                            $field = $origField;
                        }
                        if ((!isset($table) || $table === false) && isset($origField)) {
                            $fields[$field] = $origField;
                        } elseif (isset($table) && isset($origField)) {
                            $fields[$field] = $table.'.'.$origField;
                        }
                        //$fields[$field] = $table === false ? $origField : $table.'.'.$origField;
                    }
                }
                //echo '<pre style="text-align: left;">';var_dump($exprs);echo '</pre>';
            } else {
                $this->log("Don't know how to handle Type {$c_type}, only COLUMN", __LINE__, __FILE__, 'warning');
            }
        }
        if (isset($fields)) {
            // a secret column selected through an expression (the masked
            // "if(ifnull(col,''),'','********') as col" form) is mapped above to whatever
            // column came before it; map it back to itself so an edit or search can
            // never land on a different column
            foreach ($fields as $alias => $target) {
                if ($this->is_secret_field($alias) && !$this->is_secret_field($target)) {
                    $fields[$alias] = $alias;
                }
            }
            $this->query_fields = $fields;
        }
        //$this->add_output('<pre style="text-align: left;">'.print_r($fields, TRUE).'</pre>');
    }

    /**
     * gets the sql tables associated with the sql query.
     *
     * @param bool|false|string $query optional query to use to get information from,if blank or FALSE it uses the query associated with the crud instance
     */
    public function get_tables_from_query($query = false)
    {
        if ($query == false) {
            $query = $this->query;
        }
        $this->db->query("explain {$query}", __LINE__, __FILE__);
        $tables = [];
        $table = false;
        if ($this->db->num_rows() > 0) {
            while ($this->db->next_record(MYSQL_ASSOC)) {
                if ($table === false) {
                    $table = $this->db->Record['table'];
                }
                if (!isset($tables[$this->db->Record['table']])) {
                    $tables[$this->db->Record['table']] = null;
                    $tables[$this->db->Record['table']] = $this->get_table_details($this->db->Record['table']);
                }
            }
        }
        $this->table = $table;
        $this->tables = $tables;
    }

    /**
     * gets detailed information about the sql table.
     *
     * @param string $table the table name to get information about.
     * @return array an array of information about the table
     */
    public function get_table_details($table)
    {
        $db = clone $this->db;
        $db->query("show full columns from {$table}", __LINE__, __FILE__);
        $fields = [];
        while ($db->next_record(MYSQL_ASSOC)) {
            if ($db->Record['Comment'] == '') {
                $db->Record['Comment'] = ucwords(str_replace(
                    ['ssl_', 'vps_', '_id', '_lid', '_ip', '_'],
                    ['SSL_', 'VPS_', ' ID', ' Login Name', ' IP', ' '],
                    $db->Record['Field']
                ));
            }
            // the *_custid columns (custid_match) no longer get a tenant term pushed into
            // search_terms here: that ran at init(), before a page could set its limit role, and
            // put the term among the user's search terms, where set_default_search() replaced it
            // and an all fields search ORed it away. tenant_search_terms() builds it per query.
            $fields[$db->Record['Field']] = $db->Record;
        }
        return $fields;
    }

    /**
     * gets the total record count associated w/ the cruds table/query to be used w/ pagination
     *
     * @return int the number of total records for the query
     */
    public function get_count()
    {
        $db = $this->db;
        $count = 0;
        if ($this->type == 'function') {
            if (!method_exists('CrudFunctionIterator', 'run') || $this->queries->ran == false) {
                $count = 0;
            } else {
                $count = $this->queries->size;
            }
        } elseif ($this->type == 'table') {
            // the same search (and tenant terms) as the list query, so the count and the pages
            // match the rows listed; with neither it is the plain 'select count(*) from <table>'
            $from = $this->append_search_sql(" from {$this->table}", false);
            $db->query("select count(*){$from}", __LINE__, __FILE__);
            $db->next_record(MYSQL_NUM);
            $count = $db->f(0);
        } else {
            if (!is_null($this->query) && preg_match('/^.*( from .*)$/iU', str_replace("\n", ' ', $this->query), $matches)) {
                $from = $this->append_search_sql($matches[1], $this->queries[0]->hasWhere());
                $db->query("select count(*) {$from}", __LINE__, __FILE__);
                $db->next_record(MYSQL_NUM);
                $count = $db->f(0);
            }
        }
        //$this->log("Count {$count} Page Limit {$this->page_limit} Offset {$this->page_offset}", __LINE__, __FILE__, 'debug');
        return $count;
    }

    /**
     * @param string $order_by_field
     * @param string $order_direct
     * @return $this
     */
    public function set_order($order_by_field = '', $order_direct = '')
    {
        $this->order_by = !isset($this->request['order_by']) && $order_by_field ? $order_by_field : $this->order_by;
        $this->order_dir = !isset($this->request['order_dir']) && $order_direct ? $order_direct : $this->order_dir;
        return $this;
    }

    /** the upper cased keyword of scan_search_slot()'s token $k, or null (not a keyword, or no such token) */
    private static function scan_token_word(array $tokens, $k)
    {
        return $tokens[$k][0] ?? null;
    }

    /**
     * lexes a query (or the ' from ...' part of one) once to find where append_search_sql()
     * has to put the search terms so they land inside the WHERE clause.
     *
     * skipped: '...' and "..." literals (backslash escapes and doubled quotes), `...`
     * identifiers (doubled backticks), '-- ' comments (a double dash followed by whitespace, a
     * control character or the end of the query), '#' comments and slash-star comments (the
     * slash-star-! and slash-star-+ forms too). the paren depth is tracked, and only a word at
     * depth 0 that is not the second half of a qualified name or a variable is read as a
     * keyword, case-insensitively. the second half of a qualified name is a word after a '.'
     * that follows an identifier - a word that is not all digits, or a `quoted` name - with
     * any whitespace or comments around the dot (t.order, `t`.order, t . limit); a '.' after a
     * number is a decimal point (x = 1. group by, x = 1.5 order by, x = .5 limit 3). a variable
     * is a word right after an '@' (@limit, @@global.max_connections):
     *  - the first FROM;
     *  - after it the first WHERE;
     *  - after that (or after FROM when there is no WHERE, or from the start when there is no
     *    FROM) the first clause terminator: GROUP BY, HAVING, WINDOW, ORDER BY, LIMIT,
     *    PROCEDURE, INTO, FOR UPDATE, FOR SHARE or LOCK IN SHARE MODE, with any whitespace or
     *    comments between the words. the FOR ORDER BY / FOR GROUP BY / FOR JOIN of an index
     *    hint (USE|FORCE|IGNORE INDEX|KEY FOR ...) is not a terminator.
     *
     * @param string $query the query to scan
     * @return array|null null when the scan cannot read the query (a ')' with no open paren, or
     *                    the query ends inside a literal, a slash-star comment or an open paren);
     *                    otherwise ['where' => offset of the WHERE keyword or null, 'where_end' =>
     *                    offset just past it or null, 'end' => offset of the clause terminator or
     *                    the query length, 'line_comments' => [[start, stop], ...] for every -- and
     *                    # comment, stop being the offset of the newline ending it or the length]
     * @throws \RuntimeException on a top level UNION, EXCEPT or INTERSECT: one insertion cannot
     *                           filter every branch, and filtering just one is a silent leak
     */
    protected function scan_search_slot($query)
    {
        static $wordChars = null;
        if ($wordChars === null) {
            // unquoted identifier/keyword/number characters; bytes >= 0x80 are the utf-8
            // letters mysql also allows in an unquoted identifier
            $wordChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_$'.implode('', array_map('chr', range(128, 255)));
        }
        $query = (string) $query;
        $len = strlen($query);
        $depth = 0;
        // every token at depth 0, in order: [upper cased keyword, or null for anything else, start, end]
        $tokens = [];
        $lineComments = [];
        // the last token was a '.' after an identifier, so a word now is the second half of a
        // qualified name
        $qualified = false;
        // the last token (whitespace and comments aside) was an identifier: a word that is not
        // all digits, or a `quoted` name. a '.' after anything else (1. 1.5) is a decimal point
        $identifier = false;
        $i = 0;
        while ($i < $len) {
            $char = $query[$i];
            if ($char === "'" || $char === '"' || $char === '`') {
                $j = $i + 1;
                $closed = false;
                while ($j < $len) {
                    if ($char !== '`' && $query[$j] === '\\') {
                        $j += 2;
                        continue;
                    }
                    if ($query[$j] === $char) {
                        if ($j + 1 < $len && $query[$j + 1] === $char) {
                            $j += 2;
                            continue;
                        }
                        $closed = true;
                        $j++;
                        break;
                    }
                    $j++;
                }
                if (!$closed) {
                    return null;
                }
                if ($depth === 0) {
                    $tokens[] = [null, $i, $j];
                }
                $qualified = false;
                $identifier = $char === '`';
                $i = $j;
                continue;
            }
            if ($char === '#' || ($char === '-' && $i + 1 < $len && $query[$i + 1] === '-'
                && ($i + 2 >= $len || ord($query[$i + 2]) <= 32 || ord($query[$i + 2]) === 127))) {
                $stop = strpos($query, "\n", $i);
                $stop = $stop === false ? $len : $stop;
                $lineComments[] = [$i, $stop];
                $i = $stop;
                continue;
            }
            if ($char === '/' && $i + 1 < $len && $query[$i + 1] === '*') {
                $stop = strpos($query, '*/', $i + 2);
                if ($stop === false) {
                    return null;
                }
                $i = $stop + 2;
                continue;
            }
            if (ctype_space($char)) {
                $i++;
                continue;
            }
            $wordLen = strspn($query, $wordChars, $i);
            if ($wordLen > 0) {
                if ($depth === 0) {
                    $keyword = $qualified || ($i > 0 && $query[$i - 1] === '@') ? null : strtoupper(substr($query, $i, $wordLen));
                    $tokens[] = [$keyword, $i, $i + $wordLen];
                }
                $qualified = false;
                $identifier = !ctype_digit(substr($query, $i, $wordLen));
                $i += $wordLen;
                continue;
            }
            if ($char === '(') {
                if ($depth === 0) {
                    $tokens[] = [null, $i, $i + 1];
                }
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth < 0) {
                    return null;
                }
                if ($depth === 0) {
                    $tokens[] = [null, $i, $i + 1];
                }
            } elseif ($depth === 0) {
                $tokens[] = [null, $i, $i + 1];
            }
            $qualified = $char === '.' && $identifier;
            $identifier = false;
            $i++;
        }
        if ($depth !== 0) {
            return null;
        }
        $start = 0;
        foreach ($tokens as $k => $token) {
            if (in_array($token[0], ['UNION', 'EXCEPT', 'INTERSECT'], true)) {
                throw new \RuntimeException("append_search_sql() cannot add a search to a query with a top level {$token[0]}");
            }
            if ($token[0] === 'FROM' && $start === 0) {
                $start = $k + 1;
            }
        }
        $where = null;
        $end = $len;
        for ($k = $start, $count = count($tokens); $k < $count; $k++) {
            $keyword = $tokens[$k][0];
            if ($keyword === 'WHERE') {
                if ($where === null) {
                    $where = $k;
                }
                continue;
            }
            if (in_array($keyword, ['HAVING', 'WINDOW', 'LIMIT', 'PROCEDURE', 'INTO'], true)) {
                $terminator = true;
            } elseif ($keyword === 'GROUP' || $keyword === 'ORDER') {
                $indexHint = self::scan_token_word($tokens, $k - 1) === 'FOR' && in_array(self::scan_token_word($tokens, $k - 2), ['INDEX', 'KEY'], true) && in_array(self::scan_token_word($tokens, $k - 3), ['USE', 'FORCE', 'IGNORE'], true);
                $terminator = self::scan_token_word($tokens, $k + 1) === 'BY' && !$indexHint;
            } elseif ($keyword === 'FOR') {
                $terminator = in_array(self::scan_token_word($tokens, $k + 1), ['UPDATE', 'SHARE'], true);
            } elseif ($keyword === 'LOCK') {
                $terminator = self::scan_token_word($tokens, $k + 1) === 'IN' && self::scan_token_word($tokens, $k + 2) === 'SHARE' && self::scan_token_word($tokens, $k + 3) === 'MODE';
            } else {
                $terminator = false;
            }
            if ($terminator) {
                $end = $tokens[$k][1];
                break;
            }
        }
        return [
            'where' => $where === null ? null : $tokens[$where][1],
            'where_end' => $where === null ? null : $tokens[$where][2],
            'end' => $end,
            'line_comments' => $lineComments,
        ];
    }

    /**
     * adds the search terms to a query's WHERE clause. search_to_sql() returns '' when every
     * term was dropped (a value that does not fit the column type, like an email searched
     * against an int id, a secret column, an unknown field), and adding a bare ' where ' then
     * made the list and count queries a syntax error, so then the query comes back unchanged.
     *
     * the search used to be appended at the END of the query. the tenant filter
     * (tenant_search_terms(): <*_custid field> = <custid> when limit_custid) is part of the
     * search, so on a query ending in GROUP BY it became 'group by x and invoices_custid=5'
     * - which mysql runs (without ONLY_FULL_GROUP_BY) and which filters nothing. now
     * scan_search_slot() finds the WHERE clause and the clause that ends it, and the search
     * goes in between:
     *  - with a WHERE:    '... where (<existing condition>) and <search> <rest>'; the parens
     *    stop an OR/XOR/|| in the existing condition from swallowing the search, and when the
     *    condition holds a -- or # comment the paren is closed on a new line so the comment
     *    cannot eat the added text;
     *  - without a WHERE: '... where <search> <rest>' (on a new line when the text before it
     *    ends in a -- or # comment);
     * where <rest> is the query from the terminator (GROUP BY, HAVING, ORDER BY, LIMIT, ...)
     * on, or nothing. so a query with no terminator still just gets the search at the end, with
     * the existing condition in parens. a subquery, function call or window (depth > 0) never
     * counts, nor does anything in a literal, a quoted identifier or a comment.
     *
     * $hasWhere (the sql parser's answer) is only used when the scan cannot read the query
     * (unbalanced parens, an unterminated literal or comment): such sql is malformed and mysql
     * rejects it however the search is added, so it keeps the old append at the end.
     *
     * @param string $query the query (or the from part of it) to add the search to
     * @param bool $hasWhere whether the query already has a where clause, used only when the
     *                       query cannot be scanned
     * @return string the query with the search added, or unchanged when there is none
     * @throws \RuntimeException when there is a search to add and the query has a top level
     *                           UNION, EXCEPT or INTERSECT (see scan_search_slot())
     */
    public function append_search_sql($query, $hasWhere)
    {
        // no early return on empty search_terms: the tenant terms are not among them (see
        // tenant_search_terms()), search_to_sql() returns '' when there is neither
        $search = $this->search_to_sql();
        if (trim($search) === '') {
            return $query;
        }
        $slot = $this->scan_search_slot($query);
        if ($slot === null) {
            return $query.($hasWhere ? ' and ' : ' where ').$search;
        }
        $rest = (string) substr($query, $slot['end']);
        $rest = $rest === '' ? '' : ' '.$rest;
        if ($slot['where'] !== null) {
            $condition = substr($query, $slot['where_end'], $slot['end'] - $slot['where_end']);
            $commented = false;
            foreach ($slot['line_comments'] as [$start, $stop]) {
                if ($start >= $slot['where_end'] && $start < $slot['end']) {
                    $commented = true;
                }
            }
            return substr($query, 0, $slot['where_end']).' ('.trim($condition).($commented ? "\n" : '').') and '.$search.$rest;
        }
        $head = rtrim(substr($query, 0, $slot['end']));
        $headEnd = strlen($head);
        $commented = false;
        foreach ($slot['line_comments'] as [$start, $stop]) {
            if ($start < $headEnd && $headEnd <= $stop) {
                $commented = true;
            }
        }
        return $head.($commented ? "\n" : ' ').'where '.$search.$rest;
    }

    /**
     * builds up the query and sends it to the sql server to run i.  if there are any search terms
     * setup then they are automatically added onto the query as well as current order field, order
     * direction, result limit, and result offset.
     *
     * @return void
     */
    public function run_list_query()
    {
        //$this->log("Order by {$this->order_by} Direction {$this->order_dir}", __LINE__, __FILE__, 'debug');
        if (!in_array($this->order_by, $this->fields) || $this->is_secret_field($this->order_by)) {
            $this->order_by = $this->primary_key;
        }
        if ($this->type == 'function') {
            //$this->log("Running Function as Query: {$this->query}", __LINE__, __FILE__, 'debug');
            function_requirements($this->query);
            $this->queries = new CrudFunctionIterator($this->query);
        } else {
            if ($this->type == 'table') {
                $query = $this->append_search_sql("select * from {$this->table}", false);
            } else {
                $query = $this->append_search_sql($this->query, $this->queries[0]->hasWhere());
            }
            if ($this->page_limit > 0) {
                $query .= " order by {$this->order_by} {$this->order_dir} limit {$this->page_offset}, {$this->page_limit}";
            }
            //$this->log("Running Query: {$query}", __LINE__, __FILE__, 'debug');
            $this->db->query($query, __LINE__, __FILE__);
        }
    }

    /**
     * resolves the column type from $this->tables for a given field reference.
     * accepts a bare column name or qualified table.column.
     *
     * @param string $field field reference
     * @return array{type:string,values:array} normalized info: type is one of int,float,date,enum,set,string; values populated for enum/set
     */
    public function get_field_type($field)
    {
        $info = ['type' => 'string', 'values' => []];
        $table = null;
        $column = $field;
        if (strpos($field, '.') !== false) {
            [$table, $column] = explode('.', $field, 2);
        }
        $details = null;
        if ($table !== null && isset($this->tables[$table][$column]['Type'])) {
            $details = $this->tables[$table][$column];
        } else {
            foreach ($this->tables as $tbl => $fields) {
                if (isset($fields[$column]['Type'])) {
                    $details = $fields[$column];
                    break;
                }
            }
        }
        if ($details === null) {
            return $info;
        }
        $sqlType = strtolower((string) $details['Type']);
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|bit|year)\b/', $sqlType)) {
            $info['type'] = 'int';
        } elseif (preg_match('/^(decimal|numeric|float|double|real)\b/', $sqlType)) {
            $info['type'] = 'float';
        } elseif (preg_match('/^(date|datetime|timestamp|time)\b/', $sqlType)) {
            $info['type'] = 'date';
        } elseif (preg_match("/^(enum|set)\((.*)\)$/", $sqlType, $m)) {
            $info['type'] = $m[1];
            if (preg_match_all("/'((?:[^']|'')*)'/", $m[2], $vmatches)) {
                foreach ($vmatches[1] as $v) {
                    $info['values'][] = str_replace("''", "'", $v);
                }
            }
        // array_key_exists() and not isset(): $uuid_fields holds null (auto detect
        // swap) as its value, and isset() reports false for a null value, which
        // would drop the 'uuid' type and send the column back to being an
        // unparseable raw binary blob.
        } elseif (preg_match('/^binary\(16\)$/', $sqlType) && array_key_exists($column, $this->uuid_fields)) {
            $info['type'] = 'uuid';
        }
        return $info;
    }

    /**
     * json_search_tosql()
     * converts a JSON search request to an sql query, dropping clauses whose value
     * cannot be coerced to the column type (so a free-text search does not blow up
     * against TIMESTAMP / INT / ENUM columns under MySQL strict mode).
     *
     * @param string $field field name
     * @param string $oper search operation
     * @param string $val search string
     * @param bool $useLike when true, string columns use LIKE '%val%' instead of '=' (used for the search-all-fields path)
     * @return string the mysql safe search tag, or '' when the value is incompatible with the column type
     */
    public function json_search_tosql($field, $oper, $val, $useLike = false)
    {
        //$this->log("called json_search_tosql({$field}, {$oper}, ".var_export($val,true).")", __LINE__, __FILE__, 'debug');
        // secret columns are never searchable, or a LIKE search would test guesses against the real value
        if ($this->is_secret_field($field)) {
            return '';
        }
        if (isset($this->query_fields[$field])) {
            $field = $this->query_fields[$field];
        }
        if ($this->is_secret_field($field)) {
            return '';
        }
        if (isset($this->validations[$field]) && in_array('int', $this->validations[$field])) {
            $type = 'int';
            $values = [];
        } elseif (isset($this->validations[$field]) && in_array('float', $this->validations[$field])) {
            $type = 'float';
            $values = [];
        } else {
            $info = $this->get_field_type($field);
            $type = $info['type'];
            $values = $info['values'];
        }
        switch ($oper) {
            case '=':
                return $this->build_eq_clause($field, $val, $type, $values, $useLike);
            case 'in':
                if (!is_array($val)) {
                    $val = [$val];
                }
                $valArray = [];
                foreach ($val as $value) {
                    $coerced = $this->coerce_for_type($value, $type, $values);
                    if ($coerced !== null) {
                        $valArray[] = $coerced;
                    }
                }
                if (count($valArray) === 0) {
                    return '';
                }
                return $field.' '.$oper.' ('.implode(',', $valArray).')';
            default:
                $this->log("Don't know how to handle oper {$oper} in json_search_tosql({$field}, {$oper}, ".var_export($val, true).')', __LINE__, __FILE__, 'warning');
                break;
        }
        return '';
    }

    /**
     * builds a single equality (or LIKE) clause, returning '' when the value is
     * incompatible with the column type.
     *
     * @param string $field qualified field reference
     * @param mixed $val raw search value
     * @param string $type normalized type from get_field_type()
     * @param array $values enum/set allowed values
     * @param bool $useLike use LIKE '%val%' for string columns
     * @return string SQL fragment or ''
     */
    protected function build_eq_clause($field, $val, $type, array $values, $useLike)
    {
        switch ($type) {
            case 'int':
                if (!is_numeric($val)) {
                    return '';
                }
                return $field.'='.(int) $val;
            case 'float':
                if (!is_numeric($val)) {
                    return '';
                }
                return $field.'='.(float) $val;
            case 'date':
                if (!preg_match('/^[0-9 \\/:\\-]+$/', (string) $val)) {
                    return '';
                }
                return $field."='".$this->db->real_escape($val)."'";
            case 'enum':
            case 'set':
                if (!in_array((string) $val, $values, true)) {
                    return '';
                }
                return $field."='".$this->db->real_escape($val)."'";
            case 'uuid':
                // no swap flag on either side: these columns hold v7, stored in
                // natural order, so UUID_TO_BIN($x, 1) built the wrong 16 bytes and
                // searching by a row's real uuid never matched it.
                if (uuid_to_bin($val) === false) {
                    return '';
                }
                return $field."=UUID_TO_BIN('".$this->db->real_escape($val)."')";
            case 'string':
            default:
                $escaped = $this->db->real_escape($val);
                if ($useLike) {
                    return $field." like '%".$escaped."%'";
                }
                return $field."='".$escaped."'";
        }
    }

    /**
     * coerces a value for use inside an IN(...) list, returning null when the
     * value is incompatible with the column type so the caller can drop it.
     *
     * @param mixed $value raw value
     * @param string $type normalized type from get_field_type()
     * @param array $values enum/set allowed values
     * @return string|int|float|null SQL-ready literal or null to skip
     */
    protected function coerce_for_type($value, $type, array $values)
    {
        switch ($type) {
            case 'int':
                return is_numeric($value) ? (int) $value : null;
            case 'float':
                return is_numeric($value) ? (float) $value : null;
            case 'date':
                if (!preg_match('/^[0-9 \\/:\\-]+$/', (string) $value)) {
                    return null;
                }
                return "'".$this->db->real_escape($value)."'";
            case 'enum':
            case 'set':
                if (!in_array((string) $value, $values, true)) {
                    return null;
                }
                return "'".$this->db->real_escape($value)."'";
            case 'uuid':
                // see the matching case in the search clause builder: v7 is stored
                // unswapped, so no swap flag here either
                if (uuid_to_bin($value) === false) {
                    return null;
                }
                return "UUID_TO_BIN('".$this->db->real_escape($value)."')";
            case 'string':
            default:
                return "'".$this->db->real_escape($value)."'";
        }
    }

    /**
     * converts the searches setup into an sql string to be appended to the normal query
     *
     * @return string the sql string to add to the query
     */
    public function search_to_sql()
    {
        $search = [];
        $valid_opers = ['=', 'in'];
        $implode_type = 'and';
        //$this->log('Search Terms: '.json_encode($this->search_terms), __LINE__, __FILE__, 'debug');
        if (count($this->search_terms) > 0) {
            if (!is_array($this->search_terms[0])) {
                $this->search_terms = [$this->search_terms];
            }
            foreach ($this->search_terms as $search_term) {
                //$this->log("Processing search " . json_encode($search_term), __LINE__, __FILE__, 'debug');
                [$field, $oper, $value] = $search_term;
                $found = false;
                foreach ($this->tables as $table => $fields) {
                    if (isset($fields[$field])) {
                        $found = true;
                    }
                }
                if ($found == false && $field == '') {
			if ($this->admin !== true) {        continue;    }
                    //$this->log("Searching All Fields", __LINE__, __FILE__);
                    foreach ($this->tables as $table => $fields) {
                        foreach ($fields as $field_name => $field_data) {
                            if (in_array($field_name, $this->fields)) {
                                $searchString = $this->json_search_tosql($table.'.'.$field_name, $oper, $value, true);
                                if (!empty(trim($searchString))) {
                                    $search[] = $searchString;
                                }

                            }
                        }
                    }
                    $implode_type = 'or';
                } elseif ($found == false && !in_array($field, $this->fields)) {
                    $this->log("Invalid Search Field {$field}", __LINE__, __FILE__, 'warning');
                } elseif (!in_array($oper, $valid_opers)) {
                    $this->log("Invalid Search Operator {$oper}", __LINE__, __FILE__, 'warning');
                } else {
                    $searchString = $this->json_search_tosql($field, $oper, $value);
                    if (!empty(trim($searchString))) {
                        $search[] = $searchString;
                    }
                }
            }
        }
        if (count($search) == 0) {
            $search = '';
        } elseif ($implode_type == 'and') {
            $search = implode(' and ', $search);
        } else {
            $search = '('.implode(' or ', $search).')';
        }
        // the tenant terms come last, as when they were appended to search_terms, and are
        // always ANDed outside the user's terms: an all fields search ORs its own terms
        // together, '(<or group>) and <tenant terms>', and can no longer OR the account limit away
        $tenant = $this->tenant_search_sql();
        if (count($tenant) > 0) {
            $search = ($search === '' ? '' : $search.' and ').implode(' and ', $tenant);
        }
        //$this->log("search_to_sql() got {$search}", __LINE__, __FILE__, 'debug');
        return $search;
    }

    /**
     * the terms pinning a limited list (limit_custid) to $this->custid: one
     * [<column>, '=', <custid>] for every column of the query's tables that is an account
     * column (is_account_column(): $custid_match, *_custid by default, in any letter case),
     * in table then column order. they are kept out of
     * the user modifiable $search_terms and built when a query runs, so a limit role or a
     * custid_match set after init() applies, and set_default_search() / add_search_param()
     * cannot replace them. a function backed crud has no table columns to filter.
     *
     * @return array<int, array{0: string, 1: string, 2: mixed}>
     */
    public function tenant_search_terms()
    {
        $terms = [];
        if ($this->limit_custid != true || $this->type == 'function' || !is_string($this->custid_match) || !is_array($this->tables)) {
            return $terms;
        }
        foreach ($this->tables as $table => $fields) {
            if (!is_array($fields)) {
                continue;
            }
            foreach (array_keys($fields) as $column) {
                if (is_string($column) && $this->is_account_column($column)) {
                    $terms[] = [$column, '=', $this->custid];
                }
            }
        }
        return $terms;
    }

    /**
     * the sql of each tenant term (see tenant_search_terms()). a term whose sql cannot be built
     * (no numeric account id to compare with) is '0=1': a limited list then shows no rows,
     * where dropping the term would have shown every account's rows.
     *
     * a list limited by a role the admin lacks (is_role_limited(), custid requested or not)
     * whose tables have no account column gets no term at all, which listed every account's
     * rows: it fails closed, '0=1' for the list, the count and the exports (they all build their
     * where here), and a warning is logged once. a client's list of such tables (catalogs:
     * packages, templates, ...) and the list of an admin holding the role (a requested customer
     * or not) stay unfiltered, as before.
     *
     * @return string[]
     */
    protected function tenant_search_sql()
    {
        $sql = [];
        foreach ($this->tenant_search_terms() as $term) {
            $clause = $this->json_search_tosql($term[0], $term[1], $term[2]);
            $sql[] = trim($clause) === '' ? '0=1' : $clause;
        }
        if (count($sql) == 0 && $this->type != 'function' && $this->is_role_limited()) {
            if ($this->role_limit_warned !== true) {
                $this->role_limit_warned = true;
                $this->log("crud {$this->choice} lists no rows: the admin lacks the {$this->limit_custid_role} role and its tables have no account column to limit it to their own account or the requested customer", __LINE__, __FILE__, 'warning');
            }
            $sql[] = '0=1';
        }
        return $sql;
    }

    /**
     * adds a button to the header of the table.
     *
     * @param string            $link   optional text label for the button
     * @param string            $label  optional text label for the button
     * @param string            $status optional bootstrap status such as default,primary,success,info,warning or leave blank for default
     * @param bool|false|string $icon   optional fontawesome icon name or FALSE to disable also can have like icon<space>active  to have the button pressed
     * @param bool              $title
     * @param bool              $ima
     * @return MyCrud\Crud an instance of the crud system.
     *                                  an instance of the crud system.
     *                                  an instance of the crud system.
     */
    public function add_header_button($link, $label = '', $status = 'default', $icon = false, $title = false, $ima = false)
    {
        if ($ima == false || \MyAdmin\App::ima() == $ima || (\MyAdmin\App::ima() == 'admin' && $ima == 'client' && isset($this->request['custid']))) {
            if ($status === 'primary') {
                $btnStyle = ''
                    .'display:inline-flex !important;'
                    .'align-items:center !important;'
                    .'gap:8px !important;'
                    .'padding:7px 14px !important;'
                    .'min-height:32px !important;'
                    .'font-size:0.82rem !important;'
                    .'font-weight:600 !important;'
                    .'letter-spacing:0.01em !important;'
                    .'line-height:1.1 !important;'
                    .'border-radius:8px !important;'
                    .'border:0 !important;'
                    .'color:#ffffff !important;'
                    .'background:linear-gradient(135deg,#2aa5f3 0%,#118fdd 50%,#0c79c4 100%) !important;'
                    .'box-shadow:0 1px 0 rgba(255,255,255,0.22) inset,0 2px 6px -1px rgba(17,143,221,0.45),0 1px 2px rgba(17,143,221,0.16) !important;'
                    .'text-decoration:none !important;'
                    .'transition:transform .18s cubic-bezier(0.34,1.56,0.64,1),box-shadow .18s ease,filter .18s ease !important;'
                    .'height:auto !important;'
                    .'cursor:pointer !important;'
                    .'position:relative !important;';
                $iconBadgeStyle = ''
                    .'display:inline-flex !important;'
                    .'align-items:center !important;'
                    .'justify-content:center !important;'
                    .'width:18px !important;'
                    .'height:18px !important;'
                    .'border-radius:6px !important;'
                    .'background:rgba(255,255,255,0.18) !important;'
                    .'box-shadow:0 1px 0 rgba(255,255,255,0.22) inset !important;'
                    .'flex-shrink:0 !important;';
                $iconStyle = 'font-size:0.72rem !important;color:#ffffff !important;line-height:1 !important;margin:0 !important;';
                $iconHtml = $icon !== false
                    ? "<span style='{$iconBadgeStyle}'><i class='fas fa-{$icon}' style='{$iconStyle}'></i></span>"
                    : '';
                $tooltip = $title !== false ? ' data-toggle="tooltip" title="'.htmlspecialchars($title, ENT_QUOTES).'"' : '';
                $labelStyle = 'color:#ffffff !important;font-weight:600 !important;letter-spacing:0.01em !important;padding-right:6px !important;';
                $hoverIn = "this.style.setProperty('transform','translateY(-2px)','important');"
                    ."this.style.setProperty('box-shadow','0 1px 0 rgba(255,255,255,0.26) inset,0 5px 12px -2px rgba(17,143,221,0.55),0 2px 4px rgba(17,143,221,0.22)','important');"
                    ."this.style.setProperty('filter','brightness(1.04) saturate(1.05)','important');";
                $hoverOut = "this.style.setProperty('transform','translateY(0)','important');"
                    ."this.style.setProperty('box-shadow','0 1px 0 rgba(255,255,255,0.22) inset,0 2px 6px -1px rgba(17,143,221,0.45),0 1px 2px rgba(17,143,221,0.16)','important');"
                    ."this.style.setProperty('filter','none','important');";
                $this->header_buttons[] = "<a class='crud-deploy-btn printer-hidden' href='".$link."' style='{$btnStyle}' onmouseover=\"{$hoverIn}\" onmouseout=\"{$hoverOut}\"{$tooltip}>{$iconHtml}<span style='{$labelStyle}'>{$label}</span></a>";
            } else {
                $iconHtml = $icon !== false ? "<i class='fas fa-{$icon}'></i> " : '';
                $tooltip = $title !== false ? ' data-toggle="tooltip" title="'.htmlspecialchars($title, ENT_QUOTES).'"' : '';
                $this->header_buttons[] = "<a class='btn btn-{$status} btn-sm printer-hidden' href='".$link."'{$tooltip}>{$iconHtml}{$label}</a>";
            }
        }
        return $this;
    }

    /**
     * adds a quick-search button to the header of the table.
     *
     * @param array $terms array of search terms in the form of array($field, $operator, $value)
     * @param string $label optional text label for the button
     * @param string $status optional bootstrap status such as default,primary,success,info,warning or leave blank for default
     * @param bool|false|string $icon optional fontawesome icon name or FALSE to disable also can have like icon<space>active  to have the button pressed
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function add_title_search_button($terms, $label = '', $status = 'default', $icon = false)
    {
        if ($this->theme == 'adminlte') {
            $this->title_buttons[] = "<a class='btn btn-{$status} btn-sm' onclick='crud_search(this, ".json_encode($terms).");'>" . ($icon != false ? "<i class='fa fa-{$icon}'></i> " : '') . "{$label}</a>";
        } else {
            $this->title_buttons[] = "<a class='btn btn-{$status}' onclick='crud_search(this, ".json_encode($terms).");'>" . ($icon != false ? "<i class='fa fa-{$icon}'></i> " : '') . "{$label}</a>";
        }
        return $this;
    }

    /**
     * adds additional parameters to the URL string used by the various ajax requests
     *
     * @param string $args additional string to add to the urls in the form of like  '&who=detain&what=rocks'
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function set_extra_url_args($args)
    {
        $this->extra_url_args = $args;
        return $this;
    }

    /**
     * sets the interval in which the list of records will automatically update itself
     *
     * @param bool|false|int $auto_update FALSE to disable, or frequency in seconds to update the list of records automatically
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function set_auto_update($auto_update = false)
    {
        $this->auto_update = $auto_update;
        return $this;
    }

    /**
     * enables the fluid table view which is a 100% wide table
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_fluid_container()
    {
        $this->fluid_container = true;
        return $this;
    }

    /**
     * disables the fluid table view which is a 100% wide table
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_fluid_container()
    {
        $this->fluid_container = true;
        return $this;
    }

    /**
     * enables the refresh button on the list view
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_refresh_button()
    {
        $this->refresh_button = true;
        return $this;
    }

    /**
     * disables the refresh button on the list view
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_refresh_button()
    {
        $this->refresh_button = true;
        return $this;
    }

    /**
     * enables the labels over field comment
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_labels()
    {
        $this->use_labels = true;
        return $this;
    }

    /**
     * disables the labels over field comment
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_labels()
    {
        $this->use_labels = true;
        return $this;
    }

    /**
     * disables the refresh button on the list view
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_export_button()
    {
        $this->export_button = true;
        return $this;
    }

    /**
     * enables the refresh button on the list view
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_export_button()
    {
        $this->export_button = true;
        return $this;
    }

    /**
     * enables the refresh button on the list view
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_print_button()
    {
        $this->print_button = true;
        return $this;
    }

    /**
     * disables the refresh button on the list view
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_print_button()
    {
        $this->print_button = true;
        return $this;
    }

    /**
     * keeps a field in the row payload but out of the visible table columns.
     *
     * the field still reaches decorate_field() (so a link template can substitute
     * %field% from it) and still lands in the crud_rows json the row button
     * javascript reads, it just gets no <th>/<td> of its own. exports drop it, see
     * get_all_rows().
     *
     * @param string $field field name to hide
     * @return MyCrud\Crud
     */
    public function hide_field($field)
    {
        if (!in_array($field, $this->hidden_fields, true)) {
            $this->hidden_fields[] = $field;
        }
        return $this;
    }

    /**
     * whether a field is hidden from the visible table columns
     *
     * @param string $field field name
     * @return bool
     */
    public function is_hidden_field($field)
    {
        return in_array($field, $this->hidden_fields, true);
    }

    /**
     * opts this list into addressing its service rows by uuid instead of by their
     * sequential id in the links it generates.
     *
     * the query must select the row's uuid in its hyphenated string form under
     * $uuidField, which is what `bin_to_uuid(<prefix>_uuid) as service_uuid` in the
     * select list gives you - the raw binary(16) column is not valid utf8 and would
     * take the whole crud_rows json_encode() down with it.
     *
     * both identifier forms load the same page (uuid_resolve_request() translates a
     * `uuid=` parameter back into the `id=` every page already reads), so this only
     * decides which one the generated urls carry. nothing about the id path changes.
     *
     * @param string $uuidField the row field holding the hyphenated uuid
     * @return MyCrud\Crud
     */
    public function use_uuid_links($uuidField = 'service_uuid')
    {
        $this->uuid_key = $uuidField;
        // neither the uuid nor the fragment computed from it is something to show in
        // a column, they exist purely to be pasted into links
        $this->hide_field($uuidField);
        $this->hide_field(self::SERVICE_PARAM_FIELD);
        // default_filters() already ran (from init(), before this call) and baked the
        // TITLE_FIELD "View <service>" link as `...&id=%<prefix>_id%`. rewrite that
        // exact fragment to the identifier fragment.
        //
        // matching on the leading '&id='/'?id=' and on the row field name is deliberately
        // narrow: it cannot touch a link keyed on some other entity - '&custid=',
        // '&customer=', '&service=', '&r=' - whose id is NOT a service id and would
        // not resolve from a service uuid.
        //
        // both separators have to be covered because add_filter_link() runs the url
        // through make_link_url() first: with SEO links on the id is the first parameter
        // of a '<function>?id=...' url, with them off it is still '...&id=...'.
        if (isset($this->settings['PREFIX'])) {
            $needles = ['&id=%'.$this->settings['PREFIX'].'_id%', '?id=%'.$this->settings['PREFIX'].'_id%'];
            $replacements = ['&%'.self::SERVICE_PARAM_FIELD.'%', '?%'.self::SERVICE_PARAM_FIELD.'%'];
            foreach ($this->filters as $field => $filters) {
                foreach ($filters as $idx => $filter) {
                    if ($filter['type'] == 'string' && is_string($filter['value'])) {
                        $this->filters[$field][$idx]['value'] = str_replace($needles, $replacements, $filter['value']);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * adds the SERVICE_PARAM_FIELD identifier fragment to a fetched record.
     *
     * a no-op until the list has called use_uuid_links(), so a list that has not
     * opted in keeps exactly the row payload it had before.
     *
     * @param array $record the record as read from the db / function iterator
     * @return array the record, with the identifier fragment added when applicable
     */
    protected function add_service_param($record)
    {
        if ($this->uuid_key === '' || !is_array($record)) {
            return $record;
        }
        // prefer the module's own <prefix>_id: that is the id the pages this links to
        // read, and it is what the `id=%<prefix>_id%` links said before. primary_key
        // is the fallback for a query that did not select it.
        $idField = isset($this->settings['PREFIX']) && array_key_exists($this->settings['PREFIX'].'_id', $record)
            ? $this->settings['PREFIX'].'_id'
            : $this->primary_key;
        $id = array_key_exists($idField, $record) ? $record[$idField] : '';
        $uuid = array_key_exists($this->uuid_key, $record) ? $record[$this->uuid_key] : null;
        // uuid_is_blank() rather than empty()/isset(): a row the uuid backfill never
        // reached holds 16 zero bytes, which bin_to_uuid() hands back as the NON empty
        // string '00000000-0000-0000-0000-000000000000'. that sails past empty() and
        // past is_uuid() (a plain hex shape regex), and uuid_resolve_request() refuses
        // the nil uuid - so emitting it would produce a dead link. fall back to the id.
        if (!is_string($uuid) || uuid_is_blank($uuid) || !is_uuid($uuid)) {
            $record[self::SERVICE_PARAM_FIELD] = 'id='.$id;
        } else {
            $record[self::SERVICE_PARAM_FIELD] = 'uuid='.rawurlencode($uuid);
        }
        return $record;
    }

    /**
     * converts an internal 'index.php?choice=none.<function>&...' url into the pretty
     * form the rest of the app links with ('<function>?...'), by handing it to the very
     * same tf::link() that make_link() uses - so a crud link and a make_link() link to
     * the same page come out identical, and the SEO_LINKS setting keeps deciding for
     * both of them at once.
     *
     * the links this class builds are only finished off in the browser: a row button
     * pastes the row's identifier onto the end of the url, and a filter link's %token%
     * placeholders are substituted by crud_load_page(). so the conversion has to happen
     * while the url is still a template. that is safe because the only part tf::link()
     * reads is the choice/function name, which is already known here - everything past
     * it, tokens included, is copied through untouched.
     *
     * @param string $url the url to convert, eg 'index.php?choice=none.view_vps&id=%id%'
     * @return string the converted url, or $url unchanged when there is no app to ask
     */
    public function make_link_url($url)
    {
        return self::link_url($url);
    }

    /**
     * make_link_url() without an instance, for the 'function' filters of the crud pages that
     * build a link in php (crud_domains_list_screenshot(), crud_coupons_customer()), so their
     * links come out as the ones convert_choice_links() made of the sql built html before.
     *
     * @param string $url the url to convert, eg 'index.php?choice=none.view_domain&id=5'
     * @return string the converted url, or $url unchanged when there is no app to ask
     */
    public static function link_url($url)
    {
        if (!\MyAdmin\App::has(\MyAdmin\tf::class)) {
            return $url;
        }
        $parts = explode('?', $url, 2);
        return \MyAdmin\App::link($parts[0], isset($parts[1]) ? $parts[1] : '');
    }

    /**
     * adds a button to the list of buttons shown with each record
     *
     * the link may carry either identifier token. both are resolved in the browser
     * from the crud_rows json payload, since one button's html is rendered once and
     * reused for every row:
     *
     *   %id%    - the row's primary key,               eg '&id=%id%'
     *   %uuid%  - the whole identifier fragment from
     *             SERVICE_PARAM_FIELD, ie 'uuid=<uuid>'
     *             or 'id=<id>' for a row with no
     *             usable uuid,                         eg '&%uuid%'
     *
     * note %uuid% replaces the parameter name as well as its value, so it is written
     * without an `id=`/`uuid=` prefix of its own. see get_crud_row_uuid() in crud.js.
     *
     * @param $link
     * @param string $title
     * @param string $level
     * @param string $icon
     * @param string $page
     * @return MyCrud\Crud
     * @internal param string $button the html for the button to add
     */
    public function add_row_button($link, $title = '', $level = 'primary', $icon = 'cog', $page = 'index.php')
    {
        //$this->log("called add_row_button({$link}, {$title}, {$level}, {$icon}, {$page})", __LINE__, __FILE__, 'debug');
        // convert to the pretty '<function>?...' form BEFORE the tokens are swapped out
        // for javascript, while the url is still a plain string tf::link() can read
        $url = $this->make_link_url($page.'?choice='.$link);
        $js = str_replace(['%id%', '%uuid%', '+\'\''], ['\'+get_crud_row_id(this)', '\'+get_crud_row_uuid(this)', ''], $url);
        // a token becomes a string concatenation that closes the javascript string
        // literal itself ("'+get_crud_row_uuid(this)"); a link carrying no token has to
        // close its own quote instead
        $location = $js === $url ? '\''.$js.'\';' : '\''.$js.';';
        //$button = '<a href="'.$url.'" class="btn btn-'.$level.' btn-xs"';
        $button = '<button type="button" alt="'.$title.'" class="btn btn-'.$level.' btn-xs printer-hidden" onclick="window.location='.$location.'"';
        if ($title != '') {
            $button .= ' title="'.$title.'" data-toggle="tooltip" tooltip="'.$title.'">';
        }
        if ($icon != '') {
            $button .= '<i class="fa fa-fw fa-'.$icon.'"></i>';
        }
        //$button .= '</a>';
        $button .= '</button>';
        $this->buttons[] = $button;
        return $this;
    }

    /**
     * gets the sort icon html for the given field applying current order field and direction
     *
     * @param string $field the field to generate the icon html for
     * @return string the html of the icon to place with the header field names
     */
    public function get_sort_icon($field)
    {
        if ($field == $this->order_by) {
            $opacity = 1;
            $icon = 'sort-'.$this->order_dir;
        } else {
            $opacity = 0.3;
            $icon = 'sort';
        }
        return "<i class=\"sort-arrow fa fa-{$icon}\" style=\"padding-left: 5px; opacity: {$opacity}; position: absolute;\"></i>";
    }

    /**
     * gets the current page number for paginated results based on offset and limit
     *
     * @return int the page number you are currently on
     */
    public function get_page()
    {
        return ($this->page_offset / $this->page_limit) + 1;
    }

    /**
     * gets the total number of pages for paginated results based on the current page limit and record count
     *
     * @param int $count number of total records/rows
     * @return int total number of pages of results
     */
    public function get_total_pages($count)
    {
        return ceil($count / $this->page_limit);
    }

    /**
     * gets the pagination links for the current page
     *
     * @param int $page current page we're on
     * @param int $total_pages totalnumber of pages
     * @return array an array of page numbers to link to for the paginatoin
     */
    public function get_page_links($page, $total_pages)
    {
        $page_links = [1];
        $first = $page - 2;
        if ($first < 2) {
            $first = 2;
        }
        for ($x = 0; $x < 4; $x++) {
            if (!in_array($first + $x, $page_links) && $first + $x < $total_pages) {
                $page_links[] = $first + $x;
            }
        }
        if (!in_array($total_pages, $page_links)) {
            $page_links[] = $total_pages;
        }
        return $page_links;
    }

    /**
     * runs the list query and builds up the interface to listing the records and sends it to the user
     *
     */
    public function list_records()
    {
        /*$this->tables = Array(
            [accounts] => Array(
                [account_id] => Array(
                    [Field] => account_id
                    [Type] => int(11) unsigned
                    [Collation] =>
                    [Null] => NO
                    [Key] => PRI
                    [Default] =>
                    [Extra] => auto_increment
                    [Privileges] => select,insert,update,references
                    [Comment] => Account ID
        )))*/
        $smarty = new \TFSmarty();
        $table = new \TFTable;
        if ($this->title == false) {
            $table->set_title($this->table.' Records');
        } else {
            $table->set_title($this->title);
        }
        $this->run_list_query();
        $header_shown = false;
        $idx = 0;
        $rows = [];
        while ($this->next_record(MYSQL_ASSOC)) {
            $record = $this->get_record();
            if ($header_shown == false) {
                $header_shown = true;
                $empty_record = [];
                // NOTE the hidden fields stay in $empty_record and in $record - they are
                // what decorate_field() substitutes into a link template - they are only
                // skipped where a column would be emitted for them.
                if ($this->type == 'function' || $this->type == 'table') {
                    foreach (array_keys($this->tables[$this->table]) as $field) {
                        $empty_record[$field] = "%{$field}%";
                    }
                    foreach ($this->tables[$this->table] as $field => $field_data) {
                        if ($this->is_hidden_field($field)) {
                            continue;
                        }
                        $table->set_col_options('data-order-dir="asc" data-order-by="'.$field.'" class=""');
                        //$table->add_header_field($field_data['Comment'].$this->get_sort_icon($field));
                        $table->add_header_field($this->label($field).$this->get_sort_icon($field));
                    }
                } else {
                    foreach (array_keys($record) as $field) {
                        $empty_record[$field] = "%{$field}%";
                    }
                    foreach (array_keys($record) as $field) {
                        if ($this->is_hidden_field($field)) {
                            continue;
                        }
                        $table->set_col_options('data-order-dir="asc" data-order-by="'.$field.'" class=""');
                        if (isset($this->tables[$this->table][$field]) && $this->use_labels == false) {
                            $table->add_header_field($this->tables[$this->table][$field]['Comment'].$this->get_sort_icon($field));
                        } else {
                            $table->add_header_field($this->label($field).$this->get_sort_icon($field));
                        }
                    }
                }
                $table->set_col_options('');
                $table->set_row_options('id="itemrowheader"');
                $table->add_header_row();
                $table->set_row_options('id="itemrowempty" style="display: none;"');
                foreach ($empty_record as $field => $value) {
                    if ($this->is_hidden_field($field)) {
                        continue;
                    }
                    // a column whose filters can only run on a real value (a 'function' or
                    // 'simple' filter) gets the placeholder of its decorated ajax cell, which
                    // ajax_cells() fills with decorate_field() of each row; string filters stay
                    // a template filled in from the plain cells
                    if ($this->has_server_decoration($field)) {
                        $table->add_field('%'.$field.self::DECORATED_CELL_SUFFIX.'%');
                        continue;
                    }
                    $table->add_field($this->decorate_field($field, $empty_record));
                }
                $table->add_row();
            }
            $table->set_row_options('id="itemrow'.$idx.'"');
            foreach ($record as $field =>$value) {
                if (!$this->is_hidden_field($field)) {
                    $table->add_field($this->decorate_field($field, $record));
                }
                if (isset($this->input_types[$field]) && $this->input_types[$field][0] == 'select_multiple') {
                    $record[$field] = explode(',', $value);
                }
            }
            $rows[] = $record;
            $table->add_row();
            $idx++;
        }
        $count = $this->get_count();
        $table->set_template_dir('/templates/crud/');
        //$table->set_filename('crud/table.tpl');
        //$table->set_filename('crud/table1.tpl');
        //$table->set_filename('crud/table2.tpl');
        //$table->set_filename('crud/table3.tpl');
        //$table->set_filename('crud/table4.tpl');
        if ($this->theme == 'adminlte') {
            if ($this->module == 'backups') {
                page_heading('Storage / Backup List');
                breadcrums(['home' => 'Home', 'view_'.$this->module.'_list' => 'Storage']);
            } else {
                page_heading(''.ucfirst($this->settings['TITLE']).' List');
                breadcrums(['home' => 'Home', 'view_'.$this->module.'_list' => ucfirst($this->module)]);
            }
        }
        if ($this->theme == 'adminlte') {
            $table->set_filename('crud/tableAdminLte.tpl');
        } else {
            $table->set_filename('crud/table5.tpl');
        }
        if ($this->admin == true) {
            $debug = $this;
            unset($debug->db);
            // table5.tpl prints the dump through smarty's escape, htmlspecialchars() without
            // ENT_SUBSTITUTE, which prints '' for a string holding one invalid utf-8 byte: the
            // rows of a 'function' list (remote or feed data, kept in the dumped crud) or a
            // request parameter name could blank the whole dump. each invalid byte becomes
            // the substitute character ('?') here; this is what mb_scrub() does (php 7.2+)
            $table->smarty->assign('debug_output', mb_convert_encoding(print_r($debug, true), 'UTF-8', 'UTF-8'));
        }
        $table->hide_form();
        $page = $this->get_page();
        $page_count = ceil($count / $this->page_limit);
        //$total_pages = $this->get_total_pages($count);
        //$total_pages = $page_count;
        //echo 'pages'.$total_pages;
        // the count ran after the list query, with the final account limit and search
        $this->total_rows = $count;
        $this->total_pages = $this->get_total_pages($count);
        $this->page_links = $this->get_page_links($page, $this->total_pages);
        $assign = [
            'label_rep' => [
                'active' => 'success',
                'pending' => 'info',
                'locked' => 'danger',
                'suspended' => 'warning',
                'canceled' => 'warning',
                'expired' => 'danger',
                'terminated' => 'danger'
            ],
            // printed as 'var crud_search_terms = ...' in the inline script: the request's search,
            // html_entity_decode()d, so it is encoded for the script block (see inline_json())
            'search_terms' => self::inline_json($this->search_terms, '[]'),
            'primary_key' => $this->primary_key,
            // the row field the row button javascript reads the identifier fragment
            // out of, or '' when this list has not opted into uuid links - in which
            // case the templates emit no crud_uuid_key at all and get_crud_row_uuid()
            // falls back to the primary key. see use_uuid_links().
            'uuid_key' => $this->uuid_key === '' ? '' : self::SERVICE_PARAM_FIELD,
            'choice' => $this->choice,
            'admin' => $this->admin,
            'fluid_container' => $this->fluid_container,
            'refresh_button' => $this->refresh_button,
            'export_button' => $this->export_button,
            'print_button' => $this->print_button,
            'page_links' => $this->page_links,
            'total_rows' => $count,
            'total_pages' => $this->total_pages,
            'page_limits' => $this->page_limits,
            'page' => $page,
            'page_limit' => $this->page_limit,
            'page_offset' => $this->page_offset,
            'order_by' => $this->order_by,
            'order_dir' => $this->order_dir,
            'edit_form' => $this->order_form(),
            'select_multiple' => $this->select_multiple,
            'header' => $this->header,
            'header_buttons' => $this->header_buttons,
            'title_buttons' => $this->title_buttons,
            // the page's args plus an admin's requested custid (see get_extra_url_args()):
            // every ajax.php url the templates build (list/export, edit, delete) carries them
            'extra_url_args' => $this->get_extra_url_args(),
            'module' => $this->module,
            'add_row' => $this->add_row,
            'labels' => $this->labels,
            'rows' => $rows,
            // the same rows as the json the templates print as 'var crud_rows = ...' (core's
            // invoices.js and repeat_invoices.js read crud_rows[row][field] too), encoded for the
            // inline script (see inline_json())
            'rows_json' => self::inline_json($rows, '[]'),
            // posted back as csrf_token by the edit/delete modal forms, see authorize_write()
            'crud_csrf_token' => ($this->edit_row == true || $this->delete_row == true || $this->add_row == true) ? $this->get_write_csrf_token() : '',
        ];
        if ($this->export_button == true) {
            $assign['export_formats'] = $this->get_export_formats();
        }
        if ($this->edit_row == true) {
            $this->buttons[] = $this->edit_button;
        }
        if ($this->delete_row == true) {
            $this->buttons[] = $this->delete_button;
        }
        if (count($this->buttons) > 0) {
            $assign['row_buttons'] = $this->buttons;
        }
        \Tracy\Debugger::barDump($assign, 'Crud '.$this->title.' Smarty Assign');
        \Tracy\Debugger::barDump($table, 'Crud '.$this->title.' TFTable');
        //\MyAdmin\App::output()->addHeadJsString('let assign'.str_replace('-','_',slugify($this->title)).' = '.json_encode($assign).';');
        $table->smarty->assign($assign);
        $this->add_js_headers();
        $this->add_output($table->get_table());
        //$this->add_output('<pre style="text-align: left;">'. print_r($this->tables, TRUE).'</pre>');
    }

    /**
     * goes to the next record in the result set
     *
     * @param int $resultType the result type, can pass MYSQL_ASSOC, MYSQL_NUM, and other stuff
     * @return bool returns TRUE if it was able to get a record and we have an array result, otherwise returns FALSE
     */
    public function next_record($resultType)
    {
        if ($this->type == 'function') {
            if (!isset($this->tables[$this->query])) {
                $this->tables[$this->query] = [];
            }
            //$this->log('ran is '.var_export($this->queries->ran, TRUE), __LINE__, __FILE__, 'debug');
            $ran = $this->queries->ran;
            $return = $this->queries->next_record($resultType);
            // a function that returned no rows leaves Record null (foreach warned on the page
            // and on every ajax refill); its columns are then simply not known
            if ($ran == false && is_array($this->queries->Record)) {
                //$this->log('queries->Record is '.var_export($this->queries->Record, TRUE), __LINE__, __FILE__, 'debug');
                foreach ($this->queries->Record as $field => $value) {
                    $comment = ucwords(str_replace(
                        ['ssl_', 'vps_', '_id', '_lid', '_ip', '_'],
                        ['SSL_', 'VPS_', ' ID', ' Login Name', ' IP', ' '],
                        $field
                    ));
                    $this->add_field($field, $comment, false, [], 'input');
                    $this->tables[$this->query] = $this->queries->Record;
                    if (isset($this->tables[$this->query]['Comment'])) {
                        $this->tables[$this->query]['Comment'] = $comment;
                    }
                }
            }
        } else {
            $return = $this->db->next_record($resultType);
            //$this->log(json_encode($this->db->Record), __LINE__, __FILE__, 'debug');
        }
        return $return;
    }

    /**
     * whether a field (bare or table.field) is one of the secret columns
     *
     * @param string $field the field name, optionally prefixed with its table
     * @return bool
     */
    public function is_secret_field($field)
    {
        if (!is_string($field) || $field === '') {
            return false;
        }
        $pos = strrpos($field, '.');
        if ($pos !== false) {
            $field = substr($field, $pos + 1);
        }
        return in_array(strtolower(trim($field, '` ')), self::$secret_columns, true);
    }

    /**
     * replaces the value of every secret column in a record with the mask; empty
     * values stay empty so "is a password set" is still visible.
     *
     * @param array $record the record
     * @return array the record with secrets masked
     */
    public function mask_secret_fields($record)
    {
        if (!is_array($record)) {
            return $record;
        }
        foreach ($record as $field => $value) {
            if (is_string($field) && $value !== '' && $value !== null && $this->is_secret_field($field)) {
                $record[$field] = $this->secret_display_value($field, $value);
            }
        }
        return $record;
    }

    /**
     * the mask style of a secret column (see $secret_column_masks), '' for the plain mask
     *
     * @param string $field the field name, optionally prefixed with its table
     * @return string
     */
    protected static function secret_mask_style($field)
    {
        if (!is_string($field)) {
            return '';
        }
        $pos = strrpos($field, '.');
        if ($pos !== false) {
            $field = substr($field, $pos + 1);
        }
        return self::$secret_column_masks[strtolower(trim($field, '` '))] ?? '';
    }

    /**
     * what a secret column shows instead of its value: $secret_mask, or for a 'last4' column
     * the stars plus last 4 of mask_last4()
     *
     * @param string $field
     * @param mixed $value a non-empty value
     * @return string
     */
    public function secret_display_value($field, $value)
    {
        if (self::secret_mask_style($field) === 'last4') {
            return self::mask_last4($value);
        }
        return $this->secret_mask;
    }

    /**
     * mask_cc()'s default output, without needing core's function loaded: a value longer than 6
     * characters becomes a '*' for each character but the last 4, then the last 4; a shorter
     * one comes back as it is. counted in characters (mb_*), like mask_cc(). applying it to its
     * own output gives the same string, so a value masked in the sql (crud_cc_log) comes out of
     * mask_secret_fields() unchanged.
     *
     * @param mixed $value
     * @return string
     */
    public static function mask_last4($value)
    {
        $value = (string) $value;
        $len = mb_strlen($value);
        if ($len > 6) {
            return str_repeat('*', $len - 4).mb_substr($value, $len - 4);
        }
        return $value;
    }

    /**
     * whether a value posted for a secret column is what the list showed rather than something
     * typed in: the plain mask, or for a 'last4' column anything holding a '*' (its stars, or a
     * row stored already masked) - a real card number never contains one. the edit handler
     * never writes such a value.
     *
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    public function is_secret_mask_value($field, $value)
    {
        if ($value === $this->secret_mask) {
            return true;
        }
        return self::secret_mask_style($field) === 'last4' && is_string($value) && strpos($value, '*') !== false;
    }

    /**
     * returns the record for the current row whether its an sql or function type
     *
     * @return array the result row
     */
    public function get_record()
    {
        //$this->log(__FUNCTION__ . " called with type {$this->type} = " . json_encode($this->db->Record), __LINE__, __FILE__, 'debug');
        if ($this->type == 'function') {
            return $this->mask_secret_fields($this->convert_choice_links($this->add_service_param($this->convert_uuid_fields($this->queries->Record))));
        } else {
            if (!empty($this->db->Record['cost'])) {
                $temp = explode(' ', $this->db->Record['cost']);
                $this->db->Record['cost'] = Currency::getSymbol($temp[0]).$temp[1];
            }
            return $this->mask_secret_fields($this->convert_choice_links($this->add_service_param($this->convert_uuid_fields($this->db->Record))));
        }
    }

    /**
     * rewrites any 'index.php?choice=none.<function>&...' href inside a fetched record
     * into the pretty '<function>?...' form.
     *
     * a handful of lists built their own <a href> html inside the sql itself (crud_domains_list()
     * and crud_coupons() did; they now select plain values and build the html in a 'function'
     * filter that escapes what it puts in), and those values are handed to the browser
     * raw in the crud_rows json - they never pass through add_filter_link(), so this is
     * the only place they can be converted. kept for any list (core's too) that still does.
     *
     * only the contents of an href attribute are rewritten, deliberately: a list whose
     * DATA happens to contain such a url - the request log, the query log - has to keep
     * showing it exactly as it was logged.
     *
     * @param array $record the fetched record
     * @return array the record with any links inside it converted
     */
    protected function convert_choice_links($record)
    {
        if (!is_array($record)) {
            return $record;
        }
        foreach ($record as $field => $value) {
            if (!is_string($value) || strpos($value, 'choice=none.') === false || stripos($value, 'href=') === false) {
                continue;
            }
            $record[$field] = preg_replace_callback(
                '/href=(["\'])([^"\']*choice=none\.[^"\']*)\1/i',
                function ($matches) {
                    return 'href='.$matches[1].$this->make_link_url($matches[2]).$matches[1];
                },
                $value
            );
        }
        return $record;
    }

    /**
     * converts any binary(16) uuid columns (see $this->uuid_fields, populated by parse_tables())
     * in a fetched record from their raw bytes into the hyphenated string form, in place. raw
     * uuid bytes are not valid utf8, which breaks json_encode() (used for the crud_rows JS
     * payload) and the CSV/XLSX/JSON/etc export formats.
     *
     * @param array $record the record as read from the db or function iterator
     * @return array the record with any uuid fields converted to strings
     */
    protected function convert_uuid_fields($record)
    {
        foreach ($this->uuid_fields as $field => $swapFlag) {
            if (array_key_exists($field, $record) && $record[$field] !== null) {
                $uuid = bin_to_uuid($record[$field], $swapFlag);
                if ($uuid !== false) {
                    $record[$field] = $uuid;
                }
            }
        }
        return $record;
    }

    /**
     * sets the title for the crud page setting both the web page title and the table title
     *
     * @param bool|string $title text of the title
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function set_title($title = false)
    {
        if ($title === false) {
            $title = 'View '.$this->settings['TITLE'];
        }
        $this->title = $title;
        return $this;
    }


    /**
     * adds an admin confirmation field
     *
     * @param mixed $field
     * @param mixed $label
     * @param mixed $default
     * @param mixed $type
     * @param mixed $data
     */
    public function add_admin_confirmation_field($field, $label, $default, $type, $data = false)
    {
        $this->admin_confirm_fields[$field] = [
            'label' => $label,
            'value' => $default,
            'type' => $type,
            'data' => $data
        ];
    }

    /**
     * parse the table results looking at each field and getting useful information from it and creating input types based on what it finds.
     *
     */
    public function parse_tables()
    {
        $first_field = false;
        foreach ($this->tables as $table => $fields) {
            foreach ($fields as $field => $data) {
                $input_type = 'input';
                $input_data = false;
                $validations = [];
                if (preg_match("/^(?P<type>decimal|longtext|tinyint|smallint|mediumint|bigint|int|float|double|datetime|timestamp|char|varchar|mediumtext|text|enum|binary|varbinary)(\((?P<size>\d*){0,1},{0,1}(?P<size2>\d*){0,1}(?P<types>'.*'){0,1}\)){0,1} *(?P<signed>unsigned){0,1}/m", $data['Type'], $matches)) {
                    $type = $matches['type'];
                    switch ($type) {
                        case 'enum':
                            if (isset($matches['types']) && $matches['types'] != '') {
                                if (preg_match_all("/('(?P<types>[^']*)',{0,1})/m", $matches['types'], $types)) {
                                    $types = $types['types'];
                                }
                            }
                            $input_type = 'select';
                            $validations[] = ['in_array' => $types];
                            $input_data = [
                                'values' => $types,
                                'labels' => $types,
                                'default' => false
                            ];
                            break;
                        case 'tinyint':
                            if (isset($matches['signed']) && $matches['signed'] == 'unsigned') {
                                $min = 0;
                                $max = 255;
                            } else {
                                $min = -128;
                                $max = 127;
                            }
                            $validations[] = 'int';
                            break;
                        case 'smallint':
                            if (isset($matches['signed']) && $matches['signed'] == 'unsigned') {
                                $min = 0;
                                $max = 65535;
                            } else {
                                $min = -32768;
                                $max = 32767;
                            }
                            $validations[] = 'int';
                            break;
                        case 'mediumint':
                            if (isset($matches['signed']) && $matches['signed'] == 'unsigned') {
                                $min = 0;
                                $max = 16777215;
                            } else {
                                $min = -8388608;
                                $max = 8388607;
                            }
                            $validations[] = 'int';
                            break;
                        case 'bigint':
                            if (isset($matches['signed']) && $matches['signed'] == 'unsigned') {
                                $min = 0;
                                $max = 18446744073709551615;
                            } else {
                                $min = -9223372036854775808;
                                $max = 9223372036854775807;
                            }
                            $validations[] = 'int';
                            break;
                        case 'int':
                            if (isset($matches['signed']) && $matches['signed'] == 'unsigned') {
                                $min = 0;
                                $max = 4294967295;
                            } else {
                                $min = -2147483648;
                                $max = 2147483647;
                            }
                            if (isset($matches['size']) && $matches['size'] != '') {
                            }
                            $validations[] = 'int';
                            break;
                        case 'double':
                            if (isset($matches['size']) && $matches['size'] != '') {
                            }
                            if (isset($matches['signed']) && $matches['signed'] == 'unsigned') {
                                $unsigned = true;
                            } else {
                                $unsigned = false;
                            }
                            break;
                        case 'float':
                        case 'decimal':
                            if (isset($matches['size']) && $matches['size'] != '') {
                            }
                            if (isset($matches['signed']) && $matches['signed'] == 'unsigned') {
                                $unsigned = true;
                            } else {
                                $unsigned = false;
                            }
                            break;
                        case 'char':
                            if (isset($matches['size']) && $matches['size'] != '') {
                            }
                            break;
                        case 'varchar':
                            if (isset($matches['size']) && $matches['size'] != '') {
                            }
                            break;
                        case 'mediumtext':
                        case 'longtext':
                        case 'text':
                        case 'datetime':
                            break;
                        case 'timestamp':
                            $validations[] = 'timestamp';
                            break;
                        case 'binary':
                            // <prefix>_uuid columns hold uuid v7, whose 48 bit millisecond
                            // clock is already big endian, so they are stored in natural
                            // order and must NOT get the group swap that only ever applied
                            // to v1. null = let bin_to_uuid()/uuid_to_bin() auto detect, so
                            // any row left in the legacy swapped v1 layout still reads back
                            // correctly. NOTE: the value is null on purpose, so every test
                            // for "is this a uuid field" must use array_key_exists() and not
                            // isset(), which is false for a null value.
                            if (isset($matches['size']) && (int)$matches['size'] === 16 && preg_match('/_uuid$/i', $field)) {
                                $validations[] = 'uuid';
                                $this->uuid_fields[$field] = null;
                            }
                            break;
                        case 'varbinary':
                            break;
                        default:
                            $this->log("CRUD class Found Field Type '{$type}' from {$data['Type']} it does not Understand", __LINE__, __FILE__, 'warning');
                            break;
                    }
                } else {
                    $this->log("CRUD class Found Field Type {$data['Type']} it could not Parse", __LINE__, __FILE__, 'warning');
                }
                if ($first_field == false) {
                    $first_field = $field;
                }
                //$this->log(print_r($this->query_fields, TRUE), __LINE__, __FILE__, 'debug');
                if ($this->type == 'table' || $this->all_fields == true || isset($this->query_fields[$field]) || isset($this->query_fields[$table.'.'.$field])) {
                    if ($data['Key'] == 'PRI') {
                        $this->primary_key = $field;
                        if ($this->order_by == '') {
                            $this->order_by = $this->primary_key;
                        }
                        $input_type = 'label';
                    } elseif ($data['Key'] == 'MUL') {
                        //$input_type = 'label';
                    }

                    $this->add_field($field, $data['Comment'], false, $validations, $input_type, $input_data);
                }
            }
            if ($this->primary_key == '') {
                //$this->log("Generating Primary Key to {$first_field}", __LINE__, __FILE__, 'debug');
                $this->primary_key = $first_field;
            }
        }
    }

    /**
     * old carried over function used to validate a form submission
     *
     */
    public function validate_order()
    {
        parent::validate_order();
        if ($this->continue == true && !verify_csrf('crud_order_form')) {
            $this->continue = false;
        }
    }

    /**
     * displays an add/order form for the crud
     *
     */
    public function order_form()
    {
        $edit_form = '';
        if ($this->stage == 2) {
            $table = new \TFTable;
            $table->hide_table();
            $table->set_options('style=" background-color: #DFEFFF; border: 1px solid #C2D7EF;border-radius: 10px; padding-right: 10px; padding-left: 10px;"');
            $table->set_form_options('id="orderform" onsubmit="document.getElementsByName('."'confirm'".')[0].disabled = TRUE; return TRUE;"');
            $table->set_title($this->title);
            $table->csrf('crud_order_form');
            $table_pos = 0;
            foreach ($this->fields as $idx => $field) {
                if (isset($this->set_vars[$field]) && !in_array($field, $this->error_fields) && $this->values[$field] != '') {
                    $value = $this->values[$field];
                    if (isset($this->labels[$field.'_a']) && isset($this->labels[$field.'_a'][$value])) {
                        $value = $this->labels[$field.'_a'][$value];
                    }
                    if (isset($this->input_types[$field])) {
                        $input_type = $this->input_types[$field][0];
                        switch ($input_type) {
                            case 'select_multiple':
                            case 'select':
                                $value = $this->input_types[$field][1]['labels'][array_search($this->values[$field], $this->input_types[$field][1]['values'])];
                                break;
                        }
                    }
                    $table->add_hidden($field, $this->values[$field]);
                    $table->add_field('<b>'.$this->label($field).'</b>');
                    $table_pos++;
                    $table->add_field($value);
                    $table_pos++;
                } else {
                    if (isset($this->input_types[$field])) {
                        $input_type = $this->input_types[$field][0];
                        $data = $this->input_types[$field][1];
                        $label = $this->label($field);
                        switch ($input_type) {
                            case 'input':
                                $value = $this->values[$field];
                                $fieldText = ($data['prefixhtml'] ?? '') . $table->make_input($field, $value, ($data['length'] ?? 30), false, ($data['extra'] ?? '')) . ($data['extrahtml'] ?? '');
                                break;
                            case 'select_multiple':
                            case 'select':
                                $fieldText = make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], ($this->set_vars[$field] ?? $data['default']), 'id="'.$field.'" class="customsel" onChange="if (typeof update_service_choices != \'undefined\') { update_service_choices(); }" '.($data['extra'] ?? '') . ($input_type == 'select_multiple' ? ' multiple' : ''));
                                break;
                            case 'raw':
                                $fieldText = $data;
                                break;
                            case 'func':
                                if (is_array($data)) {
                                    $func = $data['data'];
                                } else {
                                    $func = $data;
                                }
                                $fieldText = $this->$func();
                                break;
                        }
                        //for ($x = 0; $x < $this->columns; ++$x)
                        /*
                        for ($x = 0; $x < 2; ++$x) {
                            $text = '';
                            $align = 'c';
                            if (isset($this->column_templates[$x]) && is_array($this->column_templates[$x])) {
                                if (isset($this->column_templates[$x]['fields']) && isset($this->column_templates[$x]['fields'][$field])) {
                                    if (isset($this->column_templates[$x]['fields'][$field]['text'])) {
                                        $text = $this->column_templates[$x]['fields'][$field]['text'];
                                        if ($this->debug === TRUE)
                                            //echo "this->column_templates[$x]['fields'][$field]['text'] set to "  .var_dump($text, TRUE) . "<br>";
                                    }
                                    if (isset($this->column_templates[$x]['fields'][$field]['align'])) {
                                        $align = $this->column_templates[$x]['fields'][$field]['align'];
                                        if ($this->debug === TRUE)
                                            //echo "this->column_templates[$x]['fields'][$field]['align'] set to "  .var_dump($align, TRUE) . "<br>";
                                    }
                                } else {
                                    if (isset($this->column_templates[$x]['text'])) {
                                        $text = $this->column_templates[$x]['text'];
                                        if ($this->debug === TRUE)
                                            //echo "this->column_templates[$x]['text'] set to "  .var_dump($text, TRUE) . "<br>";
                                    }
                                    if (isset($this->column_templates[$x]['align'])) {
                                        $align = $this->column_templates[$x]['align'];
                                        if ($this->debug === TRUE)
                                            //echo "this->column_templates[$x]['align'] set to "  .var_dump($align, TRUE) . "<br>";
                                    }
                                }
                            }
                            if ($this->debug === TRUE) {
                                //echo "Working on field $field<br>";
                                //echo "Label:";
                                //var_dump($label);
                                //echo "<br>";
                                //echo "Field Text:";
                                //var_dump($fieldText);
                                //echo "<br>";
                            }
                            if (!isset($fieldText))
                                $this->log("field $field Field Text: " . print_r($fieldText, TRUE), __LINE__, __FILE__, 'debug');
                            $text = str_replace(array('%title%','%field%'), array($label, $fieldText), $text);
                            $table->add_field($text, $align);
                            $table_pos++;
                        }
                        */
                        $this->add_output($fieldText);
                    }
                }
                if ($table_pos >= 4) {
                    $table_pos = 0;
                    $table->add_row();
                }
            }
            if ($table_pos > 0) {
                $table->set_colspan(4 - $table_pos);
                $table->add_field();
                $table->add_row();
                $table_pos = 0;
            }
            $table->set_colspan(4);
            $table->add_field($table->make_submit('Continue'));
            $table->add_row();
            $this->add_output($table->get_table());
            \MyAdmin\App::output()->addHeadJsFile('js/g_a.js');
        } else {
            foreach ($this->fields as $idx => $field) {
                if (isset($this->input_types[$field])) {
                    $input_type = $this->input_types[$field][0];
                    if (in_array($field, $this->disabled_fields)) {
                        $input_type = 'label';
                    }
                    $data = $this->input_types[$field][1];
                    $label = $this->label($field);
                    if (!isset($this->values[$field])) {
                        $this->values[$field] = '';
                    }
                    switch ($input_type) {
                        case 'label':
                            $value = $this->values[$field];
                            // $fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), FALSE, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
                            $fieldText = ($data['prefixhtml'] ?? '').'
<div class="form-group row">
<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
<div class="form-group input-group col-md-6">
	<div class="input-group-prepend"><div class="input-group-text"><i class="fa fa-fw fa-info"></i></div></div>
	<input type="text" class="form-control" disabled="disabled" name="'.$field.'" id="'.$field.'" onchange="update_inputs(\"'.$field.'\", this);" value="'.$value.'" placeholder="'.$label.'" autocomplete="off" style="width: 100%;">
</div>
</div>
'.($data['extrahtml'] ?? '');
                            break;
                        case 'input':
                            $value = $this->values[$field];
                            // $fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), FALSE, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
                            $fieldText = ($data['prefixhtml'] ?? '').'
<div class="form-group row">
<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
<div class="form-group input-group col-md-6">
	<div class="input-group-prepend"><div class="input-group-text"><i class="fa fa-fw fa-info"></i></div></div>
	<input type="text" class="form-control" name="'.$field.'" id="'.$field.'" onchange="update_inputs(\"'.$field.'\", this);" value="'.$value.'" placeholder="'.$label.'" autocomplete="off" style="width: 100%;">
</div>
</div>
'.($data['extrahtml'] ?? '');
                            break;
                        case 'textarea':
                            $value = $this->values[$field];
                            // $fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), FALSE, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
                            $fieldText = ($data['prefixhtml'] ?? '').'
<div class="form-group row">
<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
<div class="form-group input-group col-md-6">
	<textarea rows="2" class="form-control" placeholder="'.$label.'">'.$value.'</textarea>
</div>
</div>
'.($data['extrahtml'] ?? '');
                            break;
                        case 'select_multiple':
                        case 'select':
                            // $fieldText = make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="'.$field.'" class="customsel" onChange="if (typeof update_service_choices != \'undefined\') { update_service_choices(); }" '.(isset($data['extra']) ? $data['extra'] : '') . ($input_type == 'select_multiple' ? ' multiple' : ''));
                            $fieldText = ($data['prefixhtml'] ?? '').'
<div class="form-group row">
<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
<div class="form-group input-group col-md-6">
	<div class="input-group-prepend"><div class="input-group-text"><i class="fa fa-fw fa-info"></i></div></div>
	'.make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], ($this->set_vars[$field] ?? $data['default']), 'id="'.$field.'" class="form-control customsel" onChange="if (typeof update_service_choices != \'undefined\') { update_service_choices(); }" '.($data['extra'] ?? '') . ($input_type == 'select_multiple' ? ' multiple style="height: ' .(14+(17*count($data['values']))). 'px;"' : '')).'
</div>
</div>
'.($data['extrahtml'] ?? '');
                            break;
                        case 'raw':
                            $fieldText = $data;
                            break;
                        case 'func':
                            if (is_array($data)) {
                                $func = $data['data'];
                            } else {
                                $func = $data;
                            }
                            $fieldText = $this->$func();
                            break;
                    }
                    /*
                    for ($x = 0; $x < $this->columns; ++$x) {
                        $text = '';
                        $align = 'c';
                        if (isset($this->column_templates[$x]) && is_array($this->column_templates[$x])) {
                            if (isset($this->column_templates[$x]['fields']) && isset($this->column_templates[$x]['fields'][$field])) {
                                if (isset($this->column_templates[$x]['fields'][$field]['text'])) {
                                    $text = $this->column_templates[$x]['fields'][$field]['text'];
                                    if ($this->debug === TRUE)
                                        //echo "this->column_templates[$x]['fields'][$field]['text'] set to "  .var_dump($text, TRUE) . "<br>";
                                }
                                if (isset($this->column_templates[$x]['fields'][$field]['align'])) {
                                    $align = $this->column_templates[$x]['fields'][$field]['align'];
                                    if ($this->debug === TRUE)
                                        //echo "this->column_templates[$x]['fields'][$field]['align'] set to "  .var_dump($align, TRUE) . "<br>";
                                }
                            } else {
                                if (isset($this->column_templates[$x]['text'])) {
                                    $text = $this->column_templates[$x]['text'];
                                    if ($this->debug === TRUE)
                                        //echo "this->column_templates[$x]['text'] set to "  .var_dump($text, TRUE) . "<br>";
                                }
                                if (isset($this->column_templates[$x]['align'])) {
                                    $align = $this->column_templates[$x]['align'];
                                    if ($this->debug === TRUE)
                                        //echo "this->column_templates[$x]['align'] set to "  .var_dump($align, TRUE) . "<br>";
                                }
                            }
                        }
                        if ($this->debug === TRUE) {
                            //echo "Working on field $field<br>";
                            //echo "Label:";
                            //var_dump($label);
                            //echo "<br>";
                            //echo "Field Text:";
                            //var_dump($fieldText);
                            //echo "<br>";
                        }
                        if (!isset($fieldText))
                            $this->log("field $field Field Text: " . print_r($fieldText, TRUE), __LINE__, __FILE__, 'debug');
                        $text = str_replace(array('%title%','%field%'), array($label, $fieldText), $text);
                        $table->add_field($text, $align);
                    }
                    $table->set_row_options('id="'.$field.'row"');
                    $table->add_row();
                    $table->set_row_options();
                    */
                    $edit_form .= $fieldText;
                }
            }
            /*
            $table->set_colspan($this->columns);
            $table->add_field($table->make_submit('Continue to next step', FALSE, TRUE));
            $table->add_row();
            $table->set_method('get');
            $this->add_output($table->get_table());
            \MyAdmin\App::output()->addHeadJsFile('js/g_a.js');
            \MyAdmin\App::output()->addHeadJsFile('js/customSelect/jquery.customSelect.min.js');
            */
        }
        return $edit_form;
    }

    /**
     * disables initially populating the table with results and loads it from ajax instead
     *
     */
    public function disable_initial_populate()
    {
        $this->initial_populate = false;
        return $this;
    }

    /**
     * disables the delete button next to each row
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_delete()
    {
        $this->delete_row = false;
        return $this;
    }

    /**
     * disables the checkboxes to the left of the rows for bulk actions
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_select_multiple()
    {
        $this->select_multiple = false;
        return $this;
    }

    /**
     * lets a non-admin session use the ajax edit handler on this crud. every client
     * write is then limited to rows whose *_custid column(s) hold the client's own
     * account id, and refused outright on a table that has no such column.
     *
     * @param bool $enable
     * @return Crud
     */
    public function allow_client_edit($enable = true)
    {
        $this->client_edit = (bool) $enable;
        return $this;
    }

    /**
     * disables the edit button next to each row
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_edit()
    {
        $this->edit_row = false;
        return $this;
    }

    /**
     * disables the add record button
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_add()
    {
        $this->add_row = false;
        return $this;
    }

    /**
     * disables the delete button next to each row
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_initial_populate()
    {
        $this->initial_populate = true;
        return $this;
    }

    /**
     * disables the delete button next to each row
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_delete()
    {
        $this->delete_row = true;
        return $this;
    }

    /**
     * enables the checkboxes to the left of the rows for bulk actions
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_select_multiple()
    {
        $this->select_multiple = true;
        return $this;
    }

    /**
     * enables the edit button next to each row
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_edit()
    {
        $this->edit_row = true;
        return $this;
    }

    /**
     * enables the add record button
     *
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function enable_add()
    {
        $this->add_row = true;
        return $this;
    }

    /**
     * disables a field from being edited
     *
     * @param string $field field name
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_field($field)
    {
        if (!in_array($field, $this->disabled_fields)) {
            $this->disabled_fields[] = $field;
        }
        return $this;
    }

    /**
     * disables an array of fields from the edit function
     *
     * @param array $fields an array of fields
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function disable_fields($fields)
    {
        foreach ($fields as $field) {
            $this->disable_field($field);
        }
        return $this;
    }

    /**
     * enables using insert over update
     *
     * @param bool $enable defaults to true
     * @return MyCrud\Crud an instance of the crud system.
     */
    public function set_insert_over_update($enable = true)
    {
        $this->insert_over_update = $enable;
        return $this;
    }

    /**
     * displays a confirmation type page for the add/order form
     *
     */
    public function confirm_order()
    {
        $this->confirm = true;
        $this->add_output('Order not yet completed.  Click on one of the payment options below to complete the order.<br><br>');
        $table = new \TFTable;
        $table->hide_table();
        $table->set_method('get');
        $table->set_options('width="500" cellpadding=5');
        $table->set_form_options('id="orderform" onsubmit="document.getElementsByName('."'confirm'".')[0].disabled = TRUE; return TRUE;"');
        $table->set_title($this->settings['TITLE'].' '._('Order Summary'));
        if ($this->admin == true && $this->limit_custid == true) {
            $table->add_hidden('custid', $this->custid);
        }
        $table->add_hidden('module', $this->module);
        $table->add_hidden('pp_token', '');
        $table->add_hidden('pp_payerid', '');
        $this->returnURL = 'choice='.urlencode($this->choice);
        $payment_method_table_fields = [$this->custid];
        foreach ($this->set_vars as $field => $value) {
            $this->returnURL .= '&'.$field.'='.urlencode($value);
            $table->add_hidden($field, $value);
            $label = $value;
            if (is_numeric($value) && isset($this->labels[$field.'_i']) && isset($this->labels[$field.'_i'][$value])) {
                $label = $this->labels[$field.'_i'][$value];
            } elseif (isset($this->labels[$field.'_a']) && isset($this->labels[$field.'_a'][$value])) {
                $label = $this->labels[$field.'_a'][$value];
            }
            if (isset($this->input_types[$field])) {
                $input_type = $this->input_types[$field][0];
                switch ($input_type) {
                    case 'select_multiple':
                    case 'select':
                        $label = $this->input_types[$field][1]['labels'][array_search($this->values[$field], $this->input_types[$field][1]['values'])];
                        break;
                }
            }
            if ($label != '') {
                $table->add_field('<b>'.$this->label($field).'</b>', 'l');
                $table->add_field($label, 'l');
                $table->add_row();
            }
        }
        if (SESSION_COOKIES == false) {
            $this->returnURL .= '&sessionid='.urlencode(\MyAdmin\App::session()->sessionid);
        }
        if ($this->admin == true) {
            foreach ($this->admin_confirm_fields as $field => $data) {
                switch ($data['type']) {
                    case 'select_multiple':
                    case 'select':
                        $fieldText = make_select(($data['type'] == 'select_multiple' ? $field.'[]' : $field), $data['data']['values'], $data['data']['labels'], ($this->set_vars[$field] ?? $data['data']['default']), 'id="'.$field.'" class="customsel" onChange="if (typeof update_service_choices != \'undefined\') { update_service_choices(); }" '.($data['data']['extra'] ?? '') . ($data['type'] == 'select_multiple' ? ' multiple' : ''));
                        $table->add_field('<b>'.$data['label'].'</b>', 'l');
                        $table->add_field($fieldText, 'l');
                        $table->add_row();
                        break;
                    case 'input':
                        $table->add_field('<b>'.$data['label'].'</b>', 'l');
                        $table->add_field($table->make_input($field, $data['value'], ($data['data']['length'] ?? 30)), 'l');
                        $table->add_row();
                        break;
                    case 'func':
                        $table->add_field('<b>'.$data['label'].'</b>', 'l');
                        $func = $data['data'];
                        $table->add_field($this->$func(), 'l');
                        $table->add_row();
                        break;
                }
            }
        }
        $this->db = get_module_db($this->module);
        $this->db->query(make_insert_query('pending_orders', [
            'pend_id' => null,
            'pend_choice' => $this->choice,
            'pend_timestamp' => mysql_now(),
            'pend_custid' => $this->custid,
            'pend_data' => myadmin_stringify($this->set_vars)
        ]), __LINE__, __FILE__);
        //				\MyAdmin\App::output()->addHeadJsFile('js/g_a.js');
        $this->continue = false;
    }

    /**
     * performs standard string replacements in queries replacing things like __MODULE__ with the module name
     *
     * @param string $query the sql query to change
     * @return string the modified sql query
     */
    public function decorate_query($query)
    {
        return str_replace(
            [
                '__MODULE__',
                '__TITLE__',
                '__CUSTID__',
                '__LOGIN__',
                '__TBLNAME__',
                '__TABLE__',
                '__PREFIX__',
                '__TITLE_FIELD__'
            ],
            [
                $this->module,
                $this->settings['TITLE'],
                $this->custid,
                \MyAdmin\App::accounts()->cross_reference($this->custid),
                $this->settings['TBLNAME'],
                $this->settings['TABLE'],
                $this->settings['PREFIX'],
                $this->settings['TITLE_FIELD']
            ],
            $query
        );
    }

    /**
     * handles any type of special formatting that has been setup for that field , such as
     * displaying a specific field as a link to edit_customer or something like that
     *
     * a 'function' filter is called as fn($field, $value, $row): $value is the field's value
     * (htmlspecial() escaped while html filtering is on and the field is not a raw html field,
     * or what the previous filter made of it), $row the whole record as fetched, unescaped, so a
     * filter can build its html from several columns; whatever it puts in from $row it escapes
     * itself (see crud_domains_list_screenshot()). a filter written for ($field, $value) just
     * does not take the third argument.
     *
     * @param string $field the name of the field
     * @param array $row associative array of field =>value pairs
     * @return string the $value formatted for display
     */
    public function decorate_field($field, $row)
    {
        $value = $row[$field];
        if (is_array($value)) {
            $this->log("Field {$field} has array value " . json_encode($value), __LINE__, __FILE__, 'info');
            return $value;
        }
        $filtering = $this->use_html_fitering == true;
        if ($filtering && !$this->is_raw_html_field($field)) {
            $value = htmlspecial($value);
        }
        // the %row_field% values a 'string' filter pastes into its html are escaped too while
        // filtering is on (they used to go in raw), and every placeholder is replaced in one
        // pass: str_replace() went through the placeholders one after another, so a value that
        // itself held '%other_field%' had that placeholder replaced by the other field's raw
        // value. %field% and %value% win over row fields of the same name, as before.
        $pairs = [];
        foreach ($row as $row_field => $row_value) {
            if ($filtering && is_scalar($row_value) && !$this->is_raw_html_field((string) $row_field)) {
                $row_value = self::escape_cell($row_value);
            }
            $pairs['%'.$row_field.'%'] = $row_value;
        }
        $pairs['%field%'] = $field;
        $pairs['%value%'] = $value;
        if (isset($this->filters[$field])) {
            foreach ($this->filters[$field] as $idx => $filter) {
                if ($filter['type'] == 'string') {
                    $value = strtr((string) $filter['value'], $pairs);
                } elseif ($filter['type'] == 'simple') {
                    // a NULL cell (a LEFT JOIN with no match, such as crud_licenses_list's
                    // invoices_paid) is '' as str_replace() made it, without its php 8.1
                    // deprecation on every row of every refill; any other scalar is the string
                    // str_replace() converted it to anyway. a 'function' filter still gets NULL
                    $value = str_replace(array_keys($filter['value']), array_values($filter['value']), (string) $value);
                } elseif ($filter['type'] == 'function') {
                    $value = call_user_func_array($filter['value'], [$field, $value, $row]);
                }
            }
        }
        return $value;
    }

    /**
     * adds a field display filter
     *
     * @param string $field the name of the field
     * @param mixed $value the string pattern to use to replace
     * @param string $type type of filter, can be string, simple (an array of value =>
     *                     replacement), function (the name of a function called as
     *                     fn($field, $value, $row), see decorate_field()). simple and function
     *                     filters run on the server for every ajax row (has_server_decoration())
     * @param bool|false|string $acl optional acl rule required for this filter, such as 'view_customer'
     * @param string $bad_acl_test
     * @return $this
     * @internal param string $bad_acl_text same as the $text field but meant to be used to specify what is displayed instead of a link when the acl check is failed
     */
    public function add_filter($field, $value = '%value%', $type = 'string', $acl = false, $bad_acl_test = '%value%')
    {
        //$this->log("add_filter({$field}, {$value}, {$type}, {$acl}, {$bad_acl_test}) called", __LINE__, __FILE__, 'debug');
        function_requirements('has_acl');
        if (!isset($this->filters[$field])) {
            $this->filters[$field] = [];
        }
        if ($acl !== false && !has_acl($acl)) {
            $type = 'string';
            $value = $bad_acl_test;
        }
        $output = [
            'type' => $type,
            'value' => $value,
            'acl' => $acl
        ];
        $this->filters[$field][] = $output;
        return $this;
    }

    /**
     * adds a field filter that replaces the value with an a href link and optional tooltip title.
     * if you specify an acl permission, then when the user fails that permission, they will just
     * be shown the normal value instead of wrapping it in a link.   You can also optionally specify
     * a failed acl string so when it fails the acl check instead of just displaying the plain value,
     * you can specify a string filter to get applied to the value if it fails instead
     * The filters have special strings that are automatically replaced with data, the current
     * fields supported are:
     *     %field%          - replaced with the field name, ie account_lid
     *       %value%          - replaced with the fields value, ie username@email.com
     * you can also include any field names to have them automatically replaced w/ their value, ie:
     *     %account_id%   - if there is a field in the result row called 'account_id', then
     *                      this is replaced w/ the value of that field
     *
     * @param string $field the field name
     * @param string $link url, it can be a full url or just like a 'choice=none.blah' type url
     * @param bool|false|string $title optionally specify a title/tooltip to be shown when you hover the link, defaults to FALSE , or no title/tooltip
     * @param bool|false|string $acl optional acl rule required for this filter, such as 'view_customer'
     * @param string $bad_acl_test
     * @internal param string $bad_acl_text same as the $text field but meant to be used to specify what is displayed instead of a link when the acl check is failed
     */
    public function add_filter_link($field, $link, $title = false, $acl = false, $bad_acl_test = '%value%')
    {
        //$this->log("add_filter_link({$field}, {$link}, {$title}, {$acl}, {$bad_acl_test}) called", __LINE__, __FILE__, 'debug');
        // $link = 'choice=none.edit_customer&customer=%field%'
        // the %token%s are substituted in the browser, so the url has to be put in its
        // pretty form here, while it is still a template - see make_link_url()
        $link = $this->make_link_url($link);
        $this->add_filter($field, '<a href="'.$link.'" data-container="body"'.($title !== false ? ' data-toggle="tooltip" title="'.$title.'"' : '').'>%value%</a>', 'string', $acl, $bad_acl_test);
    }

    /**
     * proceeds the standard/default set of filters, either adding all the filters or adding
     * them for the specific fields you tell it to
     *
     * @param array|bool|false|string $fields
     */
    public function default_filters($fields = false)
    {
        if ($fields == false) {
            $fields = array_values($this->query_fields);
        } elseif (!is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $field) {
            //$this->log($field, __LINE__, __FILE__, 'debug');
            if ($field == 'account_lid') {
                $this->add_filter_link($field, 'index.php?choice=none.edit_customer&customer=%account_id%', 'Edit Customer', 'view_customer');
            /*} elseif ($field == 'invoices_paid') {
                $this->add_filter($field, array('1' => '<i class="fa fa-fw fa-check"></i>', '2' => '<i class="fa fa-fw fa-times"></i>'), 'simple');*/
            } elseif ($field == $this->settings['PREFIX'].'_name' && $this->admin == true) {
                $this->add_filter_link($field, "index.php?choice=none.view_host_server&module={$this->module}&name=%{$this->settings['PREFIX']}_name%", 'View Host Server', 'view_service');
            /*} elseif ($field == $this->settings['PREFIX'].'_id') {
                // @TODO distinguish between like vps_masters.vps_id and vps.vps_id type fields before doing this*/
            } elseif ($field == $this->settings['TITLE_FIELD'] || (isset($this->settings['TITLE_FIELD2']) && $field == $this->settings['TITLE_FIELD2'])) {
                $this->add_filter_link($field, 'index.php?choice=none.view_'.$this->settings['PREFIX'].($this->module == 'webhosting' ? ($this->theme == 'adminlte' || $this->admin == true ? '' : '4') : '').'&id=%'.$this->settings['PREFIX'].'_id%', 'View '.$this->settings['TITLE'], 'view_service');
            }
        }
    }

    /**
     * gets a list of all the export formats along with information like content type to send across the header
     *
     * @return array an array of the various formats and their information
     */
    public function get_export_formats()
    {
        $formats = [
            'xlsx' => [
                'name' => 'Excel 2007+',
                'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'read' => 'row',
                'disposition' => 'attachment'
            ],
            'xls' => [
                'name' => 'Excel 2003/BIFF',
                'type' => 'application/vnd.ms-excel',
                'read' => 'row',
                'disposition' => 'attachment'
            ],
            'ods' => [
                'name' => 'OpenDocument SpreadSheet',
                'type' => 'application/vnd.oasis.opendocument.spreadsheet',
                'read' => 'row',
                'disposition' => 'attachment'
            ],
            'pdf' => [
                'name' => 'Adobe Portable Document Format',
                'type' => 'application/pdf',
                'read' => 'row',
                'disposition' => 'attachment'
            ],
            'xml' => [
                'name' => 'Extensible Markup Language',
                'type' => 'application/xml',
                'read' => 'all',
                'disposition' => 'attachment'
            ],
            'php' => [
                'name' => 'PHP Array',
                'type' => 'text/x-php',
                'read' => 'all',
                'disposition' => 'inline'
            ],
            /*'sql' => array(
                'name' => 'SQL Query',
                'type' => 'text/x-sql',
                'read' => 'all',
                'disposition' => 'inline',
            ),*/
            'csv' => [
                'name' => 'Comma-Seperated Values',
                'type' => 'text/csv',
                'read' => 'all',
                'disposition' => 'inline'
            ],
            'json' => [
                'name' => 'JSON',
                'type' => 'application/json',
                'read' => 'all',
                'disposition' => 'inline'
            ],
            'bbcode' => [
                'name' => 'BBcode',
                'type' => 'text/x-bbcode',
                'read' => 'all',
                'disposition' => 'inline'
            ],
            'wiki' => [
                'name' => 'WikiCode',
                'type' => 'text/x-wikicode',
                'read' => 'all',
                'disposition' => 'inline'
            ],
            'markdown' => [
                'name' => 'MarkDown',
                'type' => 'text/x-markdown',
                'read' => 'all',
                'disposition' => 'inline'
            ]
        ];
        return $formats;
    }

    /**
     * Exports the table data in xlsx format
     *        https://github.com/PHPOffice/PHPExcel
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_xlsx($headers)
    {
        function_requirements('array2Xlsx');
        return array2Xlsx($this->rows, $headers);
    }

    /**
     * Exports the table data in xls format
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_xls($headers)
    {
        function_requirements('array2Xls');
        return array2Xls($this->rows, $headers);
    }

    /**
     * Exports the table data in ods format
     *        https://github.com/PHPOffice/PhpSpreadsheet#want-to-contribute
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_ods($headers)
    {
        function_requirements('array2Ods');
        return array2Ods($this->rows, $headers);
    }

    /**
     * Exports the table data in pdf format
     *        http://stackoverflow.com/questions/7673056/how-to-generate-pdf-in-php-with-mysql-while-getting-a-array-of-values-by-get-or
     *        http://php.net/manual/en/ref.pdf.php
     *        http://phptopdf.com/
     *        https://github.com/tecnickcom/tcpdf
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_pdf($headers)
    {
        function_requirements('array2Pdf');
        return array2Pdf($this->rows, $headers);
    }

    /**
     * Exports the table data in xml format
     *        http://www.viper007bond.com/2011/06/29/easily-create-xml-in-php-using-a-data-array/
     *        http://www.redips.net/php/convert-array-to-xml/
     *        http://snipplr.com/view/3491/convert-php-array-to-xml-or-simple-xml-object-if-you-wish/
     *        https://www.kerstner.at/2011/12/php-array-to-xml-conversion/
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_xml($headers)
    {
        function_requirements('array2Xml');
        foreach ($headers as $header) {
            header($header);
        }
        return array2Xml($this->rows);
    }

    /**
     * Exports the table data in csv format
     *        http://php.net/manual/en/function.fputcsv.php
     *        http://stackoverflow.com/questions/13108157/php-array-to-csv
     *        https://coderwall.com/p/zvzwwa/array-to-comma-separated-string-in-php
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_csv($headers)
    {
        function_requirements('array2Csv');
        foreach ($headers as $header) {
            header($header);
        }
        $csv = '';
        $out = fopen('php://output', 'wb');
        foreach ($this->rows as $idx => $Record) {
            if ($idx == 0) {
                fputcsv($out, array_keys($Record));
            }
            // a cell a spreadsheet would read as a formula (=, +, -, @, tab, cr first) gets a
            // leading ' (customer text such as hostnames and comments ends up here)
            $safe = [];
            foreach ($Record as $field => $value) {
                $safe[$field] = self::csv_safe_cell($value);
            }
            fputcsv($out, $safe);
            //$csv .= array2Csv($Record);
        }
        fclose($out);
        return $csv;
    }

    /**
     * neutralizes csv formula injection: a value that is not a number and starts with '=',
     * '+', '-', '@', a tab or a carriage return is what excel / libreoffice / sheets run as a
     * formula when the csv is opened, so it gets a leading single quote, which they show as
     * text. numbers (-5.00, +1, 1e3) and every other value are returned unchanged.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function csv_safe_cell($value)
    {
        if (!is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }
        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }
        return $value;
    }

    /**
     * Exports the table data in json format
     *        http://php.net/manual/en/function.json-encode.php
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_json($headers)
    {
        foreach ($headers as $header) {
            header($header);
        }
        // an invalid utf-8 byte in any stored value made this false, an empty download
        return json_encode($this->rows, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Exports the table data in php format
     *        http://php.net/manual/en/function.var-export.php
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_php($headers)
    {
        foreach ($headers as $header) {
            header($header);
        }
        return '<'.'?'.'php'.PHP_EOL.'$data = '.var_export($this->rows, true).";\n";
    }

    /**
     * Exports the table data in sql format
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_sql($headers)
    {
        return '';
    }

    /**
     * Exports the table data in markdown format
     *        https://en.wikipedia.org/wiki/Markdown
     *        http://www.tablesgenerator.com/markdown_tables
     *        https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet
     *        https://github.com/erusev/parsedown
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_markdown($headers)
    {
        $return = '';
        foreach ($headers as $header) {
            header($header);
        }
        $first = true;
        foreach ($this->rows as $Record) {
            if ($first == true) {
                echo implode(' | ', array_keys($Record)) . PHP_EOL;
                $size = count($Record);
                $row = [];
                for ($x = 0; $x < $size; $x++) {
                    $row[] = '---';
                }
                echo implode(' | ', $row) . PHP_EOL;
                $first = false;
            }
            echo implode(' | ', array_values($Record)) . PHP_EOL;
        }
        return $return;
    }

    /**
     * Exports the table data in bbcode format
     *        https://en.wikipedia.org/wiki/BBCode
     *        https://xenforo.com/community/resources/cta-table-bb-code.2847/
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_bbcode($headers)
    {
        $return = '';
        foreach ($headers as $header) {
            header($header);
        }
        $first = true;
        echo "[table]\n";
        foreach ($this->rows as $Record) {
            if ($first == true) {
                echo '  [tr][td]'.implode('[/td][td]', array_keys($Record)) . "[/td][/tr]\n";
                $first = false;
            }
            echo '  [tr][td]'.implode('[/td][td]', array_values($Record)) . "[/td][/tr]\n";
        }
        echo "[/table]\n";
        return $return;
    }

    /**
     * Exports the table data in wiki format
     *        https://en.wikipedia.org/wiki/Help:Wiki_markup
     *        https://www.mediawiki.org/wiki/Help:Tables
     *
     * @param $headers
     * @return string the exported data stored as a string
     */
    public function export_wiki($headers)
    {
        $return = '';
        foreach ($headers as $header) {
            header($header);
        }
        $first = true;
        echo "{|\n";
        foreach ($this->rows as $Record) {
            if ($first == true) {
                echo '!'.implode('!!', array_keys($Record)).PHP_EOL;
                $first = false;
            }
            echo "|-\n";
            echo '|'.implode('||', array_values($Record)).PHP_EOL;
        }
        echo "|}\n";
        return $return;
    }
}
