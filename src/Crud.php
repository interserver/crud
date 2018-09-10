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
 * @copyright 2018
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

/**
 * Class Crud
 *
 * @package MyCrud
 */
class Crud extends Form
{
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
	public $query_where = [];
	public $admin_confirm_fields = [];
	public $query_fields = [];
	public $search_terms = [];
	// temp fields maybe from buy service class i think
	public $disabled_fields = [];
	public $filters = [];
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
	public $edit_button = '<button type="button" class="btn btn-primary btn-xs" onclick="crud_edit_form(this);" title="Edit"><i class="fa fa-fw fa-pencil"></i></button>';
	public $delete_button = '<button type="button" class="btn btn-danger btn-xs" onclick="crud_delete_form(this);" title="Delete"><i class="fa fa-fw fa-trash"></i></button>';
	public $extra_url_args = '';
	public $request = [];
	public $admin = false;
	/**
	 * @var FALSE|int $auto_update FALSE to disable, or frequency in seconds to update the list of records automatically
	 */
	public $auto_update = false;

	/**
	 * constructor class

	 */
	public function __construct()
	{
	}

	/**
	 * initializes the crud system around the given query table or function.
	 *
	 * @param string $table_or_query the table name or sql query or function to use in the result
	 * @param string $module optional module to associate w/ this query
	 * @param string $type optional parameter to specify the type of data we're dealing with , can be sql (default) or function
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
		} elseif (mb_strpos($table_or_query, ' ')) {
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

	public function set_use_html_filtering($enable = true)
	{
		$this->use_html_fitering = $enable;
		return $this;
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
				$this->settings = null;
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
			$this->search_terms = $search;
		}
		return $this;
	}

	/**
	 * checks for various request fields and applies them to their various crud settings
	 *
	 * @return void
	 */
	public function apply_request_data()
	{
		if (isset($GLOBALS['tf'])) {
			$this->request = $GLOBALS['tf']->variables->request;
			$this->admin = ($GLOBALS['tf']->ima == 'admin');
			$this->custid = $GLOBALS['tf']->session->account_id;
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
			$this->search_terms = json_decode(html_entity_decode($this->request['search']));
		}
		if (isset($this->request['offset'])) {
			$this->page_offset = (int)$this->request['offset'];
		}
		if (isset($this->request['limit'])) {
			$this->page_limit = (int)$this->request['limit'];
		}
		if (mb_substr($this->choice, 0, 5) == 'none.') {
			$this->choice = mb_substr($this->choice, 5);
		}
		$this->limit_custid = true;
		if ($this->page_limit < 1) {
			$this->page_limit = 500;
		}
		if ($this->page_offset < 0) {
			$this->page_offset = 0;
		}

		$count = $this->get_count();
		$this->total_pages = $this->get_total_pages($count);
		$page = $this->get_page();
		$this->page_links = $this->get_page_links($page, $this->total_pages);
		$this->total_rows = $count;

		if ($this->admin == true) {
			if (isset($this->request['custid'])) {
				$this->custid = $this->request['custid'];
			//$this->log("Setting Custid to {$this->custid} and limiting", __LINE__, __FILE__, 'debug');
			} else {
				$this->limit_custid = false;
				//$this->log("Disabling CustID Limiting", __LINE__, __FILE__, 'debug');
			}
		}
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
		$GLOBALS['tf']->add_html_head_js_file('/js/crud.js');
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
		return $this;
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
				$this->log("Invalid Crud {$this->title} Action {$action}", __LINE__, __FILE__, 'warning');
				break;
		}
	}

	/**
	 * handler function to process the ajax edit requests
	 */
	public function ajax_edit_handler()
	{
		$fields = $_POST;
		$query_fields = [];
		$insert_fields = [];
		$insert_values = [];
		$query_where = [];
		$valid = true;
		$this->errors = [];
		$this->error_fields = [];
		foreach ($fields as $field => $value) {
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
					$query_where[] = "{$field}='{$safe_value}'";
					$insert_fields[] = $field;
					$insert_values[] = "'{$safe_value}'";
				} else {
					// see which fields are editable
					if (!in_array($field, $this->disabled_fields)) {
						$query_fields[] = "{$field}='{$safe_value}'";
						$insert_fields[] = $field;
						$insert_values[] = "'{$safe_value}'";
					}
				}
			}
		}
		if (count($query_fields) > 0) {
			//$this->log("Query Table {$query_table} Where " . implode(',', $query_where).' Class Where '.implode(',' ,$this->query_where[$query_table]), __LINE__, __FILE__, 'debug');
			$query_where = array_merge($query_where, $this->query_where[$query_table]);
			// update database
			if ($this->insert_over_update == true) {
				$query = 'insert into '.$query_table.' ('.implode(', ', $insert_fields).') values ('.implode(', ', $insert_values).' where '.implode(' and ', $query_where);
			} else {
				$query = 'update '.$query_table.' set '.implode(', ', $query_fields).' where '.implode(' and ', $query_where);
			}
			if ($valid == true) {
				//$this->log("i want to run query {$query}", __LINE__, __FILE__, 'info');
				$this->db->query($query, __LINE__, __FILE__);
				// send response for js handler
				echo 'ok';
				echo "<br>updated<br>ran query<div class='well'>{$query}</div>";
			} else {
				$this->log("error validating so could not run query {$query}", __LINE__, __FILE__, 'warning');
				// send response for js handler
				echo 'There was an error with validation:<br>'.implode('<br>', $this->errors).' with the fields '.implode(', ', $this->error_fields);
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
		if (!isset($formats[$format])) {
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
			$this->rows[] = $this->get_record();
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
		$json = [];
		while ($this->db->next_record(MYSQL_ASSOC)) {
			$json[] = $this->db->Record;
		}
		// send response for js handler
		header('Content-type: application/json');
		echo json_encode($json);
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
		require_once INCLUDE_ROOT.'/../vendor/crodas/sql-parser/src/autoload.php';
		$parser = new SQLParser;
		$this->queries = $parser->parse($query);
		$this->parse_query_fields();
		//_debug_array($queries);
		//add_output('<pre style="text-align: left;">'.print_r($queries, TRUE).'</pre>');
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
					//add_output("adding table {$member1Table}");
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
		if (count($joins) > 0) {
			foreach ($joins as $join => $joinArray) {
				$table = $joinArray->getTable();											// accounts_ext, vps_masters
				$table_alias = $joinArray->getAlias();
				//var_export($table_alias);
				$join_type = $joinArray->getType();										// LEFT JOIN
				//echo "Table {$table} Join Type {$join_type}<br>";
				if (!in_array($join_type, ['LEFT JOIN', 'LEFT OUTER JOIN'])) {
					$this->log("Don't know how to handle Join Type {$join_type}", __LINE__, __FILE__, 'warning');
				} else {
					$this->join_handler($table, $joinArray->getCondition());
				}
			}
		}
		// accounts_ext
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getTable(), TRUE).'</pre>');
		// LEFT JOIN
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getType(), TRUE).'</pre>');
		// =
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getType(), TRUE).'</pre>');
		// COLUMN
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[0]->getType(), TRUE).'</pre>');
		// array('accounts', 'account_id')
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[0]->getMembers(), TRUE).'</pre>');
		// COLUMN
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[1]->getType(), TRUE).'</pre>');
		// array('accounts_ext', 'account_id')
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[1]->getMembers(), TRUE).'</pre>');
		// =
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getType(), TRUE).'</pre>');
		// COLUMN
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[0]->getType(), TRUE).'</pre>');
		// array('accounts_ext', 'account_key')
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[0]->getMembers(), TRUE).'</pre>');
		// VALUE
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[1]->getType(), TRUE).'</pre>');
		// array('roles', '2')
		//add_output('<pre style="text-align: left;">'.print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[1]->getMembers(), TRUE).'</pre>');
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
						if (count($fMembers) > 1) {
							$fTable = $fMembers[0];
							$fOrigField = $fMembers[1];
						//$origField = $table.'.'.$origField;
						} else {
							$fTable = false;
							$fOrigField = $fMembers[0];
						}
						if (count($colArray) > 1) {
							$field = $colArray[1];
						} else {
							$field = $origField;
						}
						$fields[$field] = ($table === false ? $origField : $table.'.'.$origField);
					}
				}
				//echo '<pre style="text-align: left;">';var_dump($exprs);echo '</pre>';
			} else {
				$this->log("Don't know how to handle Type {$c_type}, only COLUMN", __LINE__, __FILE__, 'warning');
			}
		}
		if (isset($fields)) {
			$this->query_fields = $fields;
		}
		//add_output('<pre style="text-align: left;">'.print_r($fields, TRUE).'</pre>');
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
					$db->Record['Field']));
			}
			if (preg_match($this->custid_match, $db->Record['Field'])) {
				//$this->log("Found CustID type field: {$db->Record['Field']}", __LINE__, __FILE__, 'info');
				if ($this->limit_custid == true) {
					if (count($this->search_terms) > 0) {
						if (!is_array($this->search_terms[0])) {
							$this->search_terms = [$this->search_terms];
						}
					}

					//$this->log("Old: " . json_encode($this->search_terms), __LINE__, __FILE__, 'debug');
					$this->search_terms[] = [$db->Record['Field'], '=', $this->custid];
					//$this->log("New: " . json_encode($this->search_terms), __LINE__, __FILE__, 'debug');
				}
			}

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
			$db->query("select count(*) from {$this->table}", __LINE__, __FILE__);
			$db->next_record(MYSQL_NUM);
			$count = $db->f(0);
		} else {
			if (preg_match('/^.*( from .*)$/iU', str_replace("\n", ' ', $this->query), $matches)) {
				$from = $matches[1];
				if (count($this->search_terms) > 0) {
					if ($this->queries[0]->hasWhere() == false) {
						$from .= ' where '.$this->search_to_sql();
					} else {
						$from .= ' and '.$this->search_to_sql();
					}
				}
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
		$this->order_by = $order_by_field ? $order_by_field : $this->order_by;
		$this->order_dir = $order_direct ? $order_direct : $this->order_dir;
		return $this;
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
		if (!in_array($this->order_by, $this->fields)) {
			$this->order_by = $this->primary_key;
		}
		if ($this->type == 'function') {
			//$this->log("Running Function as Query: {$this->query}", __LINE__, __FILE__, 'debug');
			function_requirements($this->query);
			$this->queries = new CrudFunctionIterator($this->query);
		} else {
			if ($this->type == 'table') {
				$query = "select * from {$this->table}";
				if (count($this->search_terms) > 0) {
					$query .= ' where '.$this->search_to_sql();
				}
			} else {
				$query = $this->query;
				if (count($this->search_terms) > 0) {
					if ($this->queries[0]->hasWhere() == false) {
						$query .= ' where '.$this->search_to_sql();
					} else {
						$query .= ' and '.$this->search_to_sql();
					}
				}
			}
			if ($this->page_limit > 0) {
				$query .= " order by {$this->order_by} {$this->order_dir} limit {$this->page_offset}, {$this->page_limit}";
			}
			//$this->log("Running Query: {$query}", __LINE__, __FILE__, 'debug');
			$this->db->query($query, __LINE__, __FILE__);
		}
	}

	/**
	 * json_search_tosql()
	 * converts a JSON search request to an sql query.
	 *
	 * @todo we need here more advanced checking using the type of the field - i.e. integer, string, float
	 * @param string $field field name
	 * @param string $oper search operation
	 * @param string $val search string
	 * @return string the mysql safe search tag
	 */
	public function json_search_tosql($field, $oper, $val)
	{
		//$this->log("called json_search_tosql({$field}, {$oper}, ".var_export($val,true).")", __LINE__, __FILE__, 'debug');
		if (isset($this->query_fields[$field])) {
			$field = $this->query_fields[$field];
		}
		switch ($oper) {
			case '=':
				if (isset($this->validations[$field]) && in_array('int', $this->validations[$field])) {
					return $field.$oper. (int)$val;
				} elseif (isset($this->validations[$field]) && in_array('float', $this->validations[$field])) {
					return $field.$oper. (float)$val;
				} else {
					return $field.$oper."'".$this->db->real_escape($val)."'";
				}
				break;
			case 'in':
				$valArray = [];
				foreach ($val as $value) {
					if (isset($this->validations[$field]) && in_array('int', $this->validations[$field])) {
						$valArray[] = (int)$value;
					} elseif (isset($this->validations[$field]) && in_array('float', $this->validations[$field])) {
						$valArray[] = (float)$value;
					} else {
						$valArray[] = "'".$this->db->real_escape($value)."'";
					}
				}
				return $field.' '.$oper.' ('.implode(',', $valArray).')';
				break;
			default:
				$this->log("Don't know how to handle oper {$oper} in json_search_tosql({$field}, {$oper}, ".var_export($val, true).')', __LINE__, __FILE__, 'warning');
				break;
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
				list($field, $oper, $value) = $search_term;
				$found = false;
				foreach ($this->tables as $table => $fields) {
					if (isset($fields[$field])) {
						$found = true;
					}
				}
				if ($found == false && $field == '') {
					//$this->log("Searching All Fields", __LINE__, __FILE__);
					foreach ($this->tables as $table => $fields) {
						foreach ($fields as $field_name => $field_data) {
							if (in_array($field_name, $this->fields)) {
								$search[] = $this->json_search_tosql($table.'.'.$field_name, $oper, $value);
							}
						}
					}
					$implode_type = 'or';
				} elseif ($found == false && !in_array($field, $this->fields)) {
					$this->log("Invalid Search Field {$field}", __LINE__, __FILE__, 'warning');
				} elseif (!in_array($oper, $valid_opers)) {
					$this->log("Invalid Search Operator {$oper}", __LINE__, __FILE__, 'warning');
				} else {
					$search[] = $this->json_search_tosql($field, $oper, $value);
				}
			}
		}
		if ($implode_type == 'and') {
			$search = implode(' and ', $search);
		} else {
			$search = '('.implode(' or ', $search).')';
		}
		//$this->log("search_to_sql() got {$search}", __LINE__, __FILE__, 'debug');
		return $search;
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
		if ($ima == false || $GLOBALS['tf']->ima == $ima || ($GLOBALS['tf']->ima == 'admin' && $ima == 'client' && isset($this->request['custid']))) {
			$this->header_buttons[] = "<a class='btn btn-{$status} btn-sm printer-hidden' href='".$link."');'" . ($title != false ? ' data-toggle="tooltip" title="'.$title.'"' : '') . '>' . ($icon != false ? "<i class='fa fa-{$icon}'></i> " : '') . "{$label}</a>";
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
		$this->title_buttons[] = "<a class='btn btn-{$status}' onclick='crud_search(this, ".json_encode($terms).");'>" . ($icon != false ? "<i class='fa fa-{$icon}'></i> " : '') . "{$label}</a>";
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
	 * adds a button to the list of buttons shown with each record
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
		$link = str_replace(['%id%', '+\'\''], ['\'+get_crud_row_id(this)', ''], $link);
		//$button = '<a href="'.$page.'?choice='.$link.'" class="btn btn-'.$level.' btn-xs"';
		$button = '<button type="button" class="btn btn-'.$level.' btn-xs printer-hidden" onclick="window.location=\''.$page.'?choice='.$link.';"';
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
				if ($this->type == 'function' || $this->type == 'table') {
					foreach (array_keys($this->tables[$this->table]) as $field) {
						$empty_record[$field] = "%{$field}%";
					}
					foreach ($this->tables[$this->table] as $field => $field_data) {
						$table->set_col_options('data-order-dir="asc" data-order-by="'.$field.'" class=""');
						//$table->add_header_field($field_data['Comment'].$this->get_sort_icon($field));
						$table->add_header_field($this->label($field).$this->get_sort_icon($field));
					}
				} else {
					foreach (array_keys($record) as $field) {
						$empty_record[$field] = "%{$field}%";
					}
					foreach (array_keys($record) as $field) {
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
					$table->add_field($this->decorate_field($field, $empty_record));
				}
				$table->add_row();
			}
			$table->set_row_options('id="itemrow'.$idx.'"');
			foreach ($record as $field =>$value) {
				$table->add_field($this->decorate_field($field, $record));
				if ($this->input_types[$field][0] == 'select_multiple') {
					$record[$field] = explode(',', $value);
				}
			}
			$rows[] = $record;
			$table->add_row();
			$idx++;
		}
		$count = $this->get_count();
		$table->smarty->assign('label_rep', [
			'active' => 'success',
			'pending' => 'info',
			'locked' => 'danger',
			'suspended' => 'warning',
			'canceled' => 'warning',
			'expired' => 'danger',
			'terminated' => 'danger'
										  ]
		);
		$table->set_template_dir('/templates/crud/');
		//$table->set_filename('crud/table.tpl');
		//$table->set_filename('crud/table1.tpl');
		//$table->set_filename('crud/table2.tpl');
		//$table->set_filename('crud/table3.tpl');
		//$table->set_filename('crud/table4.tpl');
		$table->set_filename('crud/table5.tpl');
		$table->smarty->assign('primary_key', $this->primary_key);
		$table->smarty->assign('choice', $this->choice);
		$table->smarty->assign('admin', $this->admin);
		if ($this->admin == true) {
			$debug = $this;
			unset($debug->db);
			$table->smarty->assign('debug_output', print_r($debug, true));
		}
		$table->hide_form();
		$page = $this->get_page();
		$page_count = ceil($count / $this->page_limit);
		//$total_pages = $this->get_total_pages($count);
		//$total_pages = $page_count;
		//echo 'pages'.$total_pages;
		$this->total_pages = $this->get_total_pages($count);
		$this->page_links = $this->get_page_links($page, $this->total_pages);
		$table->smarty->assign('fluid_container', $this->fluid_container);
		$table->smarty->assign('refresh_button', $this->refresh_button);
		$table->smarty->assign('export_button', $this->export_button);
		if ($this->export_button == true) {
			$table->smarty->assign('export_formats', $this->get_export_formats());
		}
		$table->smarty->assign('print_button', $this->print_button);
		$table->smarty->assign('page_links', $this->page_links);
		$table->smarty->assign('total_rows', $count);
		$table->smarty->assign('total_pages', $this->total_pages);
		$table->smarty->assign('page_limits', $this->page_limits);
		$table->smarty->assign('page', $page);
		$table->smarty->assign('page_limit', $this->page_limit);
		$table->smarty->assign('page_offset', $this->page_offset);
		$table->smarty->assign('order_by', $this->order_by);
		$table->smarty->assign('order_dir', $this->order_dir);
		$table->smarty->assign('edit_form', $this->order_form());
		$table->smarty->assign('select_multiple', $this->select_multiple);
		$table->smarty->assign('header', $this->header);
		$table->smarty->assign('header_buttons', $this->header_buttons);
		$table->smarty->assign('title_buttons', $this->title_buttons);
		$table->smarty->assign('extra_url_args', $this->extra_url_args);
		if ($this->edit_row == true) {
			$this->buttons[] = $this->edit_button;
		}
		if ($this->delete_row == true) {
			$this->buttons[] = $this->delete_button;
		}
		if (count($this->buttons) > 0) {
			$table->smarty->assign('row_buttons', $this->buttons);
		}
		$table->smarty->assign('add_row', $this->add_row);
		$table->smarty->assign('labels', $this->labels);
		$table->smarty->assign('rows', $rows);
		$this->add_js_headers();
		add_output($table->get_table());
		//add_output('<pre style="text-align: left;">'. print_r($this->tables, TRUE).'</pre>');
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
			if ($ran == false) {
				//$this->log('queries->Record is '.var_export($this->queries->Record, TRUE), __LINE__, __FILE__, 'debug');
				foreach ($this->queries->Record as $field => $value) {
					$comment = ucwords(str_replace(
										   ['ssl_', 'vps_', '_id', '_lid', '_ip', '_'],
										   ['SSL_', 'VPS_', ' ID', ' Login Name', ' IP', ' '],
										   $field));
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
	 * returns the record for the current row whether its an sql or function type
	 *
	 * @return array the result row
	 */
	public function get_record()
	{
		//$this->log(__FUNCTION__ . " called with type {$this->type} = " . json_encode($this->db->Record), __LINE__, __FILE__, 'debug');
		if ($this->type == 'function') {
			return $this->queries->Record;
		} else {
			return $this->db->Record;
		}
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
				if (preg_match("/^(?P<type>tinyint|smallint|mediumint|bigint|int|float|double|timestamp|char|varchar|mediumtext|text|enum)(\((?P<size>\d*){0,1}(?P<types>'.*'){0,1}\)){0,1} *(?P<signed>unsigned){0,1}/m", $data['Type'], $matches)) {
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
						case 'text':
							break;
						case 'timestamp':
							$validations[] = 'timestamp';
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
								$fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), false, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								break;
							case 'select_multiple':
							case 'select':
								$fieldText = make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="'.$field.'" class="customsel" onChange="if (typeof update_service_choices != \'undefined\') { update_service_choices(); }" '.(isset($data['extra']) ? $data['extra'] : '') . ($input_type == 'select_multiple' ? ' multiple' : ''));
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
						add_output($fieldText);
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
			add_output($table->get_table());
			$GLOBALS['tf']->add_html_head_js_file('js/g_a.js');
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
							$fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '').'
<div class="form-group">
<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
<div class="form-group input-group col-md-6">
	<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
	<input type="text" class="form-control" disabled="disabled" name="'.$field.'" id="'.$field.'" onchange="update_inputs(\"'.$field.'\", this);" value="'.$value.'" placeholder="'.$label.'" autocomplete="off" style="width: 100%;">
</div>
</div>
'.(isset($data['extrahtml']) ? $data['extrahtml'] : '');
							break;
						case 'input':
							$value = $this->values[$field];
							// $fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), FALSE, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
							$fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '').'
<div class="form-group">
<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
<div class="form-group input-group col-md-6">
	<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
	<input type="text" class="form-control" name="'.$field.'" id="'.$field.'" onchange="update_inputs(\"'.$field.'\", this);" value="'.$value.'" placeholder="'.$label.'" autocomplete="off" style="width: 100%;">
</div>
</div>
'.(isset($data['extrahtml']) ? $data['extrahtml'] : '');
							break;
						case 'textarea':
							$value = $this->values[$field];
							// $fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), FALSE, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
							$fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '').'
<div class="form-group">
<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
<div class="form-group input-group col-md-6">
	<textarea rows="2" class="form-control" placeholder="'.$label.'">'.$value.'</textarea>
</div>
</div>
'.(isset($data['extrahtml']) ? $data['extrahtml'] : '');
							break;
						case 'select_multiple':
						case 'select':
							// $fieldText = make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="'.$field.'" class="customsel" onChange="if (typeof update_service_choices != \'undefined\') { update_service_choices(); }" '.(isset($data['extra']) ? $data['extra'] : '') . ($input_type == 'select_multiple' ? ' multiple' : ''));
							$fieldText = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '').'
<div class="form-group">
<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
<div class="form-group input-group col-md-6">
	<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
	'.make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="'.$field.'" class="form-control customsel" onChange="if (typeof update_service_choices != \'undefined\') { update_service_choices(); }" '.(isset($data['extra']) ? $data['extra'] : '') . ($input_type == 'select_multiple' ? ' multiple style="height: ' .(14+(17*count($data['values']))). 'px;"' : '')).'
</div>
</div>
'.(isset($data['extrahtml']) ? $data['extrahtml'] : '');
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
			add_output($table->get_table());
			$GLOBALS['tf']->add_html_head_js_file('js/g_a.js');
			$GLOBALS['tf']->add_html_head_js_file('js/customSelect/jquery.customSelect.min.js');
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
		add_output('Order not yet completed.  Click on one of the payment options below to complete the order.<br><br>');
		$table = new \TFTable;
		$table->hide_table();
		$table->set_method('get');
		$table->set_options('width="500" cellpadding=5');
		$table->set_form_options('id="orderform" onsubmit="document.getElementsByName('."'confirm'".')[0].disabled = TRUE; return TRUE;"');
		$table->set_title($this->settings['TITLE'].' Order Summary');
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
			$this->returnURL .= '&sessionid='.urlencode($GLOBALS['tf']->session->sessionid);
		}
		if ($this->admin == true) {
			foreach ($this->admin_confirm_fields as $field => $data) {
				switch ($data['type']) {
					case 'select_multiple':
					case 'select':
						$fieldText = make_select(($data['type'] == 'select_multiple' ? $field.'[]' : $field), $data['data']['values'], $data['data']['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['data']['default']), 'id="'.$field.'" class="customsel" onChange="if (typeof update_service_choices != \'undefined\') { update_service_choices(); }" '.(isset($data['data']['extra']) ? $data['data']['extra'] : '') . ($data['type'] == 'select_multiple' ? ' multiple' : ''));
						$table->add_field('<b>'.$data['label'].'</b>', 'l');
						$table->add_field($fieldText, 'l');
						$table->add_row();
						break;
					case 'input':
						$table->add_field('<b>'.$data['label'].'</b>', 'l');
						$table->add_field($table->make_input($field, $data['value'], (isset($data['data']['length']) ? $data['data']['length'] : 30)), 'l');
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
		]
						 ), __LINE__, __FILE__);
		//				$GLOBALS['tf']->add_html_head_js_file('js/g_a.js');
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
				$GLOBALS['tf']->accounts->cross_reference($this->custid),
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
		if ($this->use_html_fitering == true) {
			$value = htmlspecial($value);
		}
		$search = ['%field%', '%value%'];
		$replace = [$field, $value];
		foreach ($row as $row_field => $row_value) {
			$search[] = '%'.$row_field.'%';
			$replace[] = $row_value;
		}
		if (isset($this->filters[$field])) {
			foreach ($this->filters[$field] as $idx => $filter) {
				if ($filter['type'] == 'string') {
					$value = str_replace($search, $replace, $filter['value']);
				} elseif ($filter['type'] == 'simple') {
					$value = str_replace(array_keys($filter['value']), array_values($filter['value']), $value);
				} elseif ($filter['type'] == 'function') {
					eval('$value = '.$filter['value'].'($field, $value);');
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
	 * @param string $type type of filter, can be string, function,
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
				$this->add_filter_link($field, 'index.php?choice=none.view_'.$this->settings['PREFIX'].($this->module == 'webhosting' ? ($this->admin == true ? '2' : '4') : '').'&id=%'.$this->settings['PREFIX'].'_id%', 'View '.$this->settings['TITLE'], 'view_service');
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
			fputcsv($out, $Record);
			//$csv .= array2Csv($Record);
		}
		fclose($out);
		return $csv;
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
		return json_encode($this->rows, JSON_PRETTY_PRINT);
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
