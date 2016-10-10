<?php
	/**
	 * CRUD Class
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
	 * Last Changed: $LastChangedDate$
	 * @author detain
	 * @version $Revision$
	 * @copyright 2016
	 * @package MyAdmin
	 * @category Billing
	 * @TODO Add API Interface to this
	 * @TODO Add Console/ANSI Interface to this
	 * @TODO Add order summary includable by login page
	 */

	class Crud
	{
		public $custid;
		public $ajax = false;
		public $debug = false;
		public $module;
		public $choice;
		public $table;
		public $query;
		public $primary_key = '';
		public $type = '';
		public $title = '';
		public $columns = 3;
		public $price_align = 'r';
		public $price_text_align = 'r';
		public $stage = 1;
		public $page_limits = array(10, 25, 100, -1);
		public $page_limit = 10;
		public $page_offset = 0;
		public $order_by = '';
		public $order_dir = 'desc';
		public $all_fields = false;
		public $initial_populate = true;
		public $select_multiple = false;
		public $delete_row = true;
		public $edit_row = true;
		public $add_row = true;
		public $query_where = array();
		public $admin_confirm_fields = array();
		public $fields = array();
		public $query_fields = array();
		public $search_terms = array();
		// temp fields maybe from buy service class i think
		public $disabled_fields = array();
		public $filters = array();
		public $values = array();
		public $labels = array();
		public $defaults = array();
		public $validations = array();
		public $input_types = array();
		public $column_templates = array();
		public $tables = array();
		// from the sql query parser
		public $queries = null;
		public $db;
		public $settings;
		public $buttons = array();
		public $header_buttons = array();
		public $fluid_container = false;
		public $edit_button = '<button type="button" class="btn btn-primary btn-xs" onclick="crud_edit_form(this);" title="Edit"><i class="fa fa-fw fa-pencil"></i></button>';
		public $delete_button = '<button type="button" class="btn btn-danger btn-xs" onclick="crud_delete_form(this);" title="Delete"><i class="fa fa-fw fa-trash"></i></button>';
		/**
		 * @var false|int $auto_update false to disable, or frequency in seconds to update the list of records automatically
		 */
		public $auto_update = false;

		public function __construct() {
		}

		/**
		 * initializes the crud system around the given query table or function.
		 *
		 * @param string $table_or_query the table name or sql query or function to use in the result
		 * @param string $module optional module to associate w/ this query
		 * @param string $type optional parameter to specify the type of data we're dealing with , can be sql (default) or function
		 * @return {Crud|crud} an instance of the crud system.
		 */
		public static function init($table_or_query, $module = 'default', $type = 'sql') {
			// @codingStandardsIgnoreStart
			if (isset($this) && $this instanceof self)
				$crud = &$this;
			else
				$crud = new crud();
			// @codingStandardsIgnoreEnd
			add_js('bootstrap');
			add_js('font-awesome');
			if ($module != 'default') {
				if (isset($GLOBALS['modules'][$module])) {
					$crud->module = get_module_name($module);
					$crud->settings = get_module_settings($crud->module);
					$crud->db = get_module_db($crud->module);
				} elseif (isset($GLOBALS[$module.'_dbh'])) {
					$crud->module = $module;
					$crud->settings = null;
					$crud->db = get_module_db($crud->module);
				} else {
					$crud->module = get_module_name($module);
					$crud->settings = get_module_settings($crud->module);
					$crud->db = get_module_db($crud->module);
				}
			} else {
				$crud->module = get_module_name($module);
				$crud->settings = get_module_settings($crud->module);
				$crud->db = get_module_db($crud->module);
			}
			$crud->column_templates[] = array('text' => '<h3>%title%</h3>', 'align' => 'r');
			$crud->column_templates[] = array('text' => '%field%', 'align' => 'r');
			$crud->column_templates[] = array('text' => '', 'align' => 'r');
			$crud->set_title();
			$crud->choice = $GLOBALS['tf']->variables->request['choice'];
			if ($crud->choice == 'crud') {
				$crud->ajax = $GLOBALS['tf']->variables->request['action'];
				$crud->choice = $GLOBALS['tf']->variables->request['crud'];
			}
			if (isset($GLOBALS['tf']->variables->request['order_by']))
				$crud->order_by = $GLOBALS['tf']->variables->request['order_by'];
			if (isset($GLOBALS['tf']->variables->request['order_dir']) && in_array($GLOBALS['tf']->variables->request['order_dir'], array('asc','desc')))
				$crud->order_dir = $GLOBALS['tf']->variables->request['order_dir'];
			if (isset($GLOBALS['tf']->variables->request['search']))
				$crud->search_terms = json_decode(html_entity_decode($GLOBALS['tf']->variables->request['search']));
			if (isset($GLOBALS['tf']->variables->request['offset']))
				$crud->page_offset = (int)$GLOBALS['tf']->variables->request['offset'];
			if (isset($GLOBALS['tf']->variables->request['limit']))
				$crud->page_limit = (int)$GLOBALS['tf']->variables->request['limit'];
			if (substr($crud->choice, 0, 5) == 'none.')
				$crud->choice = substr($crud->choice, 5);
			if ($GLOBALS['tf']->ima == 'admin' && isset($GLOBALS['tf']->variables->request['custid']))
				$crud->custid = $GLOBALS['tf']->variables->request['custid'];
			else
				$crud->custid = $GLOBALS['tf']->session->account_id;
			if ($type == 'function') {
				$crud->all_fields = true;
				$crud->type = $type;
				$crud->table = $table_or_query;
				$crud->query = $table_or_query;
			} elseif (strpos($table_or_query, ' ')) {
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

		/**
		 * starts/displays the crud interface handler
		 *
		 * @param string $view optional default view, this defaults to the list view if not specified.  alternatively you can pass 'add' for the add interface
		 * @return Crud
		 */
		public function go($view = 'list') {
			if ($this->ajax !== false)
				$view = 'ajax';
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
		public function ajax_handler() {
			//$this->log("CRUD {$this->title} {$action} Handling", __LINE__, __FILE__);
			// generic data to get us here is in _GET, while the specific fields are all in _POST
			//$this->log(print_r($_GET, true), __LINE__, __FILE__);
			//$this->log(print_r($_POST, true), __LINE__, __FILE__);
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
					$this->log("Invalid Crud {$this->title} Action {$action}", __LINE__, __FILE__);
					break;
			}
		}

		/**
		 * handler function to process the ajax edit requests
		 */
		public function ajax_edit_handler() {
			$fields = $_POST;
			$query_fields = array();
			$query_where = array();
			$valid = true;
			$errors = array();
			$error_fields = array();
			foreach ($fields as $field => $value) {
				// match up fields
				if (isset($this->query_fields[$field])) {
					$orig_field = $field;
					$field = $this->query_fields[$field];
					if (preg_match('/^((?P<table>[^\.]+)\.){0,1}(?P<field>[^\.]+)$/m', $field, $matches)) {
						$field = $matches['field'];
						if (isset($matches['table']) && $matches['table'] != '') {
							$tables = array($matches['table'] => $this->tables[$matches['table']]);
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
					foreach ($this->validations[$orig_field] as $validation) {
						if (!is_array($validation)) {
							switch ($validation) {
								case 'abs':
									$value = abs($value);
									break;
								case 'int':
									// TODO / FIXME _ check the isset() part here, if its not set i probably should fail it.
									if (isset($value) && $value != (int)$value) {
										$errors[] = 'Invalid ' . $this->label($field) . ' "' . $value . '"';
										$error_fields[] = $field;
										$valid = false;
									}
									break;
								case 'notags':
									if ($value != strip_tags($value)) {
										$errors[] = 'Invalid ' . $this->label($field) . ' "' . $value . '"';
										$error_fields[] = $field;
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
										$values = array();
										if (!is_array($value))
											$value = array($value);
										foreach ($value as $t_value) {
											if (!in_array($t_value, $this->labels[$field])) {
												$errors[] = 'Invalid ' . $this->label($field) . ' "' . $t_value . '"';
												$error_fields[] = $field;
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
									$values = array();
									if (!is_array($value))
										$value = array($value);
									foreach ($value as $t_value) {
										if (!in_array($t_value, $validation['in_array'])) {
											$errors[] = 'Invalid ' . $this->label($field) . ' "' . $t_value . '"';
											$error_fields[] = $field;
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
					if ($already_safe != true)
						$safe_value = $this->db->real_escape($value);
					else
						$safe_value = $value;
					if ($field == $this->primary_key)
						$query_where[] = "{$field}='{$safe_value}'";
					else {
						// see which fields are editable
						if (!in_array($field, $this->disabled_fields))
							$query_fields[] = "{$field}='{$safe_value}'";
					}
				}
			}
			if (count($query_fields) > 0) {
				//billingd_log("Query Table {$query_table} Where " . implode(',', $query_where) . ' Class Where ' . implode(',' ,$this->query_where[$query_table]));
				$query_where = array_merge($query_where, $this->query_where[$query_table]);
				// update database
				$query = "update " . $query_table . " set " . implode(', ', $query_fields) . " where " . implode(' and ', $query_where);
				if ($valid == true) {
					$this->log("i want to run query {$query}", __LINE__, __FILE__);
					//$this->db->query($query, __LINE__, __FILE__);
					// send response for js handler
					echo "ok";
					echo "<br>validation successfull<br>i want to run query<div class='well'>{$query}</div>";
				} else {
					$this->log("error validating so could not run query {$query}", __LINE__, __FILE__);
					// send response for js handler
					echo "There was an error with validation:<br>" . implode('<br>', $errors) . " with the fields " . impode(", ", $error_fields);
				}
			} else {
				$this->log("crud error nothing to update ", __LINE__, __FILE__);
				// send response for js handler
				echo "There was nothing to update";
			}
		}

		/**
		 * handler function to process the ajax add requests
		 */
		public function ajax_add_handler() {
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
		public function ajax_delete_handler() {
			// match up row
			// build query
			// update db
			// send response for js handler
		}

		/**
		 * handler function to process the ajax export requests
		 */
		public function ajax_export_handler() {
			// get export type
			// get data
			// convert data
			// send data
		}

		/**
		 * handles the ajax request to get a list of records
		 *
		 */
		public function ajax_list_handler() {
			// apply pagination
			// apply sorting
			$this->run_list_query();
			$json = array();
			while ($this->db->next_record(MYSQL_ASSOC)) {
				$json[] = $this->db->Record;
			}
			// send response for js handler
			header("Content-type: application/json");
			echo json_encode($json);
		}

		public function load_tables() {
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
		 * @param false|string $query optional query to parse, if false or not passed it uses the one associated w/ the crud request
		 */
		public function parse_query($query = false) {
			if ($query == false)
				$query = $this->query;
			//require_once(INCLUDE_ROOT . '/../vendor/autoload.php');
			//require_once(INCLUDE_ROOT . '/../vendor/crodas/sql-parser/src/SQLParser.php');
			require_once(INCLUDE_ROOT . '/../vendor/crodas/sql-parser/src/autoload.php');
			$parser = new SQLParser;
			$this->queries = $parser->parse($query);
			$this->parse_query_fields();
			//_debug_array($queries);
			//add_output('<pre style="text-align: left;">' . print_r($queries, true) . '</pre>');
		}

		/**
		 * handles joins from the query parser results determining what fields and such are used in the join
		 *
		 * @param string $table the main table from the query
		 * @param mixed $join_arr the join array
		 */
		public function join_handler($table, $join_arr) {
				$condition_type = $join_arr->getType();					// AND, =
				if ($condition_type == 'AND') {
					foreach ($join_arr->GetMembers() as $member => $member_arr) {
						$this->join_handler($table, $member_arr);
					}
				} elseif ($condition_type == '=') {
					//echo print_r($member_arr,true)."<br>";
					//echo "Type:$type<br>";
					//echo print_r($member_arr->getMembers(), true)."<br>";
					$member_1_type = $join_arr->getMembers()[0]->getType();			// COLUMN
					$member_1_members = $join_arr->getMembers()[0]->getMembers();		// array('accounts', 'account_id') or array('account_key')
					if ($member_1_type == 'COLUMN') {
						if (count($member_1_members) == 1) {
							$member_1_table = $table;
							$member_1_field = $member_1_members[0];
						} else {
							$member_1_table = $member_1_members[0];
							$member_1_field = $member_1_members[1];
						}
						//add_output("adding table {$member_1_table}");
						if (!isset($this->query_where[$member_1_table]))
							$this->query_where[$member_1_table] = array();
					}
					$member_2_type = $join_arr->getMembers()[1]->getType();			// COLUMN or VALUE
					$member_2_members = $join_arr->getMembers()[1]->getMembers();		// array('accounts_ext', 'account_id') or array('roles', '2')
					if ($member_2_type == 'COLUMN') {
						if (count($member_2_members) == 1) {
							$member_2_table = $table;
							$member_2_field = $member_2_members[0];
						} else {
							$member_2_table = $member_2_members[0];
							$member_2_field = $member_2_members[1];
						}
					} elseif ($member_2_type == 'VALUE') {
						$member_2_value = $member_2_members[0];
						//$this->query_where[$member_1_table][] =  "{$member_1_table}.{$member_1_field} {$type} '{$member_2_value}'";
						$this->query_where[$member_1_table][] =  "{$member_1_field}{$condition_type}'{$member_2_value}'";
					}
				} else {
					$this->log("Dont know how to handle Type {$condition_type} in Join Array " . print_r($join_arr, true), __LINE__, __FILE__);
				}
				//echo _debug_array($join_arr->getCondition()->getType(), true)."<br>";
				//echo _debug_array($join_arr->getCondition(), true)."<br>";
				//echo _debug_array($join_arr->getCondition()->getMembers(), true)."<br>";
		}

		/**
		 * parses the query fields from the SQLParser response to use in the crud system
		 *
		 * @param mixed $queries optional queries to parse, if left blank/false uses the crud associated parsed queries
		 */
		public function parse_query_fields($queries = false) {
			if ($queries == false)
				$queries = $this->queries;
			//echo _debug_array($this->queries, true);
			//echo _debug_array($queries[0]->getJoins(), true);
			$joins = $queries[0]->getJoins();
			if (sizeof($joins) > 0)
				foreach ($joins as $join => $join_arr) {
					$table = $join_arr->getTable();											// accounts_ext, vps_masters
					$join_type = $join_arr->getType();										// LEFT JOIN
					//echo "Table {$table} Join Type {$join_type}<br>";
					if ($join_type != 'LEFT JOIN') {
						$this->log("Dont know how to handle Join Type {$join_type}", __LINE__, __FILE__);
					} else {
						$this->join_handler($table, $join_arr->getCondition());
					}
				}
			// accounts_ext
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getTable(), true) . '</pre>');
			// LEFT JOIN
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getType(), true) . '</pre>');
			// =
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getType(), true) . '</pre>');
			// COLUMN
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[0]->getType(), true) . '</pre>');
			// array('accounts', 'account_id')
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[0]->getMembers(), true) . '</pre>');
			// COLUMN
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[1]->getType(), true) . '</pre>');
			// array('accounts_ext', 'account_id')
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[0]->getMembers()[1]->getMembers(), true) . '</pre>');
			// =
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getType(), true) . '</pre>');
			// COLUMN
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[0]->getType(), true) . '</pre>');
			// array('accounts_ext', 'account_key')
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[0]->getMembers(), true) . '</pre>');
			// VALUE
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[1]->getType(), true) . '</pre>');
			// array('roles', '2')
			//add_output('<pre style="text-align: left;">' . print_r($queries[0]->getJoins()[0]->getCondition()->getMembers()[1]->getMembers()[1]->getMembers(), true) . '</pre>');
			/*
			$columns = $queries[0]->getColumns();
			echo '<pre style="text-align: left;">';
			echo "<br>Columns:";var_dump($columns, true);
			echo "<br>Columns[0][0]:";var_dump($columns[0][0]);
			echo "<br>Type:";var_dump($columns[0][0]->getType());
			$members = $columns[0][0]->getMembers();
			echo "<br>Members:";var_dump($members);
			if (is_object($members[0])) {
				echo "<br>Type:"._debug_array($members[0]->getType(), true);
				echo "<br>Members:"._debug_array($members[0]->getMembers(), true);
			} else {
				echo "<br>Members:"._debug_array($members, true);
			}
			echo '</pre>';
			*/
			foreach ($queries[0]->getColumns() as $col => $col_arr) {
				$c_type = $col_arr[0]->getType();
				$field_arr = $col_arr[0]->getMembers();
				if ($c_type == 'COLUMN') {
					if (is_object($field_arr[0])) {
						$f_type = $field_arr[0]->getType();
						$f_members = $field_arr[0]->getMembers();
						if ($f_type != 'ALL') {
							billingd_log("Dont know how to handle Field Type {$f_type}, only ALL", __LINE__, __FILE__);
						} else {
							// Setup all the columns
							$this->all_fields = true;
						}
					} else {
						if (count($field_arr) > 1) {
							$table = $field_arr[0];
							$orig_field = $field_arr[1];
							//$orig_field = $table.'.'.$orig_field;
						} else {
							$table = false;
							$orig_field = $field_arr[0];
						}
						if (count($col_arr) > 1) {
							$field = $col_arr[1];
						} else {
							$field = $orig_field;
						}
						$fields[$field] = ($table === false ? $orig_field : $table . '.' . $orig_field);
					}
				} elseif ($c_type == 'CALL') {
					// if sizeof col_arr is 2  then [0] expr  and [1] is  the alias for field, like 'field as name'
					$call = $field_arr[0];
					$exprs = $field_arr[1]->getExprs();
					foreach ($exprs as $e_idx => $expr) {
						$e_type = $expr->getType();
						$e_members = $expr->getMembers();
						if (is_object($e_members[0])) {
							$f_type = $e_members[0]->getType();
							$f_members = $e_members[0]->getMembers();
						} else {
							if (count($f_members) > 1) {
								$f_table = $f_members[0];
								$f_orig_field = $f_members[1];
								//$orig_field = $table.'.'.$orig_field;
							} else {
								$f_table = false;
								$f_orig_field = $f_members[0];
							}
							if (count($col_arr) > 1) {
								$field = $col_arr[1];
							} else {
								$field = $orig_field;
							}
							$fields[$field] = ($table === false ? $orig_field : $table . '.' . $orig_field);
						}
					}
					//echo '<pre style="text-align: left;">';var_dump($exprs);echo '</pre>';
				} else {
					billingd_log("Dont know how to handle Type {$c_type}, only COLUMN", __LINE__, __FILE__);
				}
			}
			if (isset($fields))
				$this->query_fields = $fields;
			//add_output('<pre style="text-align: left;">' . print_r($fields, true) . '</pre>');
		}

		/**
		 * gets the sql tables associated with the sql query.
		 *
		 * @param false|string $query optional query to use to get information from,if blank or false it uses the query associated with the crud instance
		 */
		public function get_tables_from_query($query = false) {
			if ($query == false)
				$query = $this->query;
			$this->db->query("explain {$query}", __LINE__, __FILE__);
			$tables = array();
			$table = false;
			if ($this->db->num_rows() > 0) {
				while ($this->db->next_record(MYSQL_ASSOC)) {
					if ($table === false)
						$table = $this->db->Record['table'];
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
		 * @param string $table the table name to get informatoin about.
		 * @return array an array of information about the table
		 */
		public function get_table_details($table) {
			$db = clone $this->db;
			$db->query("show full columns from {$table}", __LINE__, __FILE__);
			$fields = array();
			while ($db->next_record(MYSQL_ASSOC)) {
				if ($db->Record['Comment'] == '')
					$db->Record['Comment'] = ucwords(str_replace(
						array('ssl_', 'vps_', '_id', '_lid', '_ip', '_'),
						array('SSL_', 'VPS_', ' ID', ' Login Name', ' IP', ' '),
						$db->Record['Field']));
				$fields[$db->Record['Field']] = $db->Record;
			}
			return $fields;
		}

		/**
		 * gets the total record count associated w/ the cruds table/query to be used w/ pagination
		 *
		 * @return int the number of total records for the query
		 */
		function get_count() {
			$db = $this->db;
			if ($this->type == 'table') {
				$db->query("select count(*) from {$this->table}", __LINE__, __FILE__);
				$db->next_record(MYSQL_NUM);
				$count = $db->f(0);
			} else {
				if (preg_match('/^.*( from .*)$/iU', str_replace("\n", " ", $this->query), $matches)) {
					$from = $matches[1];
					$db->query("select count(*) {$from}", __LINE__, __FILE__);
					$db->next_record(MYSQL_NUM);
					$count = $db->f(0);
				}
			}
			//$this->log("Count {$count} Page Limit {$this->page_limit} Offset {$this->page_offset}", __LINE__, __FILE__);
			return $count;
		}

		/**
		 * builds up the query and sends it to the sql server to run i.  if there are any search terms
		 * setup then they are automatically added onto the query as well as current order field, order
		 * direction, result limit, and result offset.
		 *
		 */
		public function run_list_query() {
			//billingd_log("Order by {$this->order_by} Direction {$this->order_dir}", __LINE__, __FILE__);
			if (!in_array($this->order_by, $this->fields))
				$this->order_by = $this->primary_key;
			if ($this->type == 'table') {
				$query = "select * from {$this->table}";
				if (sizeof($this->search_terms) > 0)
					$query .= " where " . $this->search_to_sql();
			} else {
				$query = $this->query;
				if (sizeof($this->search_terms) > 0)
					if ($this->queries[0]->hasWhere() == false)
						$query .= " where " . $this->search_to_sql();
					else
						$query .= " and " . $this->search_to_sql();
			}
			if ($this->page_limit > 0)
				$query .= " order by {$this->order_by} {$this->order_dir} limit {$this->page_offset}, {$this->page_limit}";
			//$this->log("Running Query: {$query}", __LINE__, __FILE__);
			$this->db->query($query, __LINE__, __FILE__);
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
		public function json_search_tosql($field, $oper, $val) {
			//$this->log("called json_search_tosql({$field}, {$oper}, ".var_export($val,true).")", __LINE__, __FILE__);
			switch ($oper) {
				case '=':
					return $field.$oper."'".$GLOBALS['tf']->db->real_escape($val)."'";
					break;
				case 'in':
					$val_arr = array();
					foreach ($val as $value) {
						$val_arr[] = "'".$GLOBALS['tf']->db->real_escape($value)."'";
					}
					return $field.' '.$oper.' ('.implode(',', $val_arr).')';
					break;
				default:
					$this->log("Dont konw how to handle oper {$oper} in json_search_tosql({$field}, {$oper}, ".var_export($val,true).")", __LINE__, __FILE__);
					break;
			}
		}

		/**
		 * converts the searchs setup into an sql string to be appened to the normal query
		 *
		 * @return string the sql string to add to the query
		 */
		public function search_to_sql() {
			$search = array();
			$valid_opers = array('=', 'in');
			if (sizeof($this->search_terms) > 0) {
				foreach ($this->search_terms as $search_term) {
					list($field, $oper, $value) = $search_term;
					if (!in_array($field, $this->fields)) {
						$this->log("Invalid Search Field {$field}", __LINE__, __FILE__);
					} elseif (!in_array($oper, $valid_opers)) {
						$this->log("Invalid Search Operator {$oper}", __LINE__, __FILE__);
					} else {
						$search[] = $this->json_search_tosql($field, $oper, $value);
					}
				}
			}
			$search = implode(" and ", $search);
			//$this->log("search_to_sql() got {$search}", __LINE__, __FILE__);
			return $search;
		}

		/**
		 * adds a quick-search button to the header of the table.
		 *
		 * @param array $terms array of search terms earch term an array in the form of array($field, $operator, $value)
		 * @param string $label optional text label for the button
		 * @param string $status optional bootstrap status such as default,primary,success,info,warning or leave blank for default
		 * @param false|string $icon optional fontawesome icon name or false to disable also can have like icon<space>active  to have the button pressed
		 */
		public function add_header_button($terms, $label = '', $status = 'default', $icon = false) {
			$this->header_buttons[] = "<a class='btn btn-{$status} btn-sm' onclick='crud_search(this, ".json_encode($terms).");'>" . ($icon != false ? "<i class='fa fa-{$icon}'></i> " : "") . "{$label}</a>";
			return $this;
		}

		/**
		 * sets the interval in which the list of records will automatically update itself
		 *
		 * @param false|int $auto_update false to disable, or frequency in seconds to update the list of records automatically
		 */
		public function set_auto_update($auto_update = false) {
			$this->auto_update = $auto_update;
		}

		/**
		 * enables the fluid table view which is a 100% wide table
		 * @return Crud
		 */
		public function enable_fluid_container() {
			$this->fluid_container = true;
			return $this;
		}

		/**
		 * disables the fluid table view which is a 100% wide table
		 * @return Crud
		 */
		public function disable_fluid_container() {
			$this->fluid_container = true;
			return $this;
		}

		/**
		 * adds a button to the list of buttons shown with each record
		 *
		 * @param string $button the html for the button to add
		 * @return Crud
		 */
		public function add_row_button($button) {
			$this->buttons[] = $button;
			return $this;
		}

		/**
		 * gets the sort icon html for the given field applying current order field and direction
		 *
		 * @param string $field the field to generate the icon html for
		 * @return string the html of the icon to place with the header field names
		 */
		public function get_sort_icon($field) {
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
		public function get_page() {
			$page = ($this->page_offset / $this->page_limit) + 1;
			return $page;
		}

		/**
		 * gets the total number of pages for paginated results based on the current page limit and record count
		 *
		 * @param int $count number of total records/rows
		 * @return int total number of pages of results
		 */
		public function get_total_pages($count) {
			$total_pages = ceil($count / $this->page_limit);
			return $total_pages;
		}

		/**
		 * gets the pagination links for the current page
		 *
		 * @param int $page current page we're on
		 * @param int $total_pages totalnumber of pages
		 * @return array an array of page numbers to link to for the paginatoin
		 */
		public function get_page_links($page, $total_pages) {
			$page_links = array(1);
			$first = $page - 2;
			if ($first < 2)
				$first = 2;
			for ($x = 0; $x < 4; $x++) {
				if (!in_array($first + $x, $page_links) && $first + $x < $total_pages) {
					$page_links[] = $first + $x;
				}
			}
			if (!in_array($total_pages, $page_links))
				$page_links[] = $total_pages;
			return $page_links;
		}

		/**
		 * runs the list query and builds up the interface to listing the records and sends it to the user
		 *
		 */
		public function list_records() {
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
			$smarty = new TFSmarty();
			$table = new TFTable;
			if ($this->title == false)
				$table->set_title($this->table . ' Records');
			else
				$table->set_title($this->title);
			$count = $this->get_count();
			$this->run_list_query();
			$header_shown = false;
			$idx = 0;
			while ($this->db->next_record(MYSQL_ASSOC)) {
				if ($header_shown == false) {
					$header_shown = true;
					$empty_record = array();
					if ($this->type == 'table') {
						foreach (array_keys($this->tables[$this->table]) as $field)
							$empty_record[$field] = "%{$field}%";
						foreach ($this->tables[$this->table] as $field => $field_data) {
							$table->set_col_options('data-order-dir="asc" data-order-by="'.$field.'" class=""');
							$table->add_header_field($field_data['Comment'].$this->get_sort_icon($field));
						}
					} else {
						foreach (array_keys($this->db->Record) as $field)
							$empty_record[$field] = "%{$field}%";
						foreach (array_keys($this->db->Record) as $field) {
							$table->set_col_options('data-order-dir="asc" data-order-by="'.$field.'" class=""');
							if (isset($this->tables[$this->table][$field]))
								$table->add_header_field($this->tables[$this->table][$field]['Comment'].$this->get_sort_icon($field));
							else
								$table->add_header_field($this->label($field).$this->get_sort_icon($field));
						}
					}
					$table->set_col_options('');
					$table->set_row_options('id="itemrowheader"');
					$table->add_header_row();
					$table->set_row_options('id="itemrowempty" style="display: none;"');
					foreach ($empty_record as $field => $value)
						$table->add_field($this->decorate_field($field, $empty_record));
					$table->add_row();
				}
				$table->set_row_options('id="itemrow'.$idx.'"');
				foreach ($this->db->Record as $field =>$value) {
					$table->add_field($this->decorate_field($field, $this->db->Record));
					if ($this->input_types[$field][0] == 'select_multiple')
						$this->db->Record[$field] = explode(',', $value);
				}
				$rows[] = $this->db->Record;
				$table->add_row();
				$idx++;
			}
			$table->smarty->assign('label_rep', array(
				'active' => 'success',
				'pending' => 'info',
				'locked' => 'danger',
				'suspended' => 'warning',
				'canceled' => 'warning',
				'expired' => 'danger',
				'terminated' => 'danger',
			));
			$table->set_template_dir('/templates/crud/');
			//$table->set_filename('../crud/table.tpl');
			//$table->set_filename('../crud/table1.tpl');
			//$table->set_filename('../crud/table2.tpl');
			//$table->set_filename('../crud/table3.tpl');
			//$table->set_filename('../crud/table4.tpl');
			$table->set_filename('../crud/table5.tpl');
			$table->smarty->assign('primary_key', $this->primary_key);
			$table->smarty->assign('choice', $this->choice);
			$table->smarty->assign('ima', $GLOBALS['tf']->ima);
			if ($GLOBALS['tf']->ima == 'admin') {
				$debug = $this;
				unset($debug->db);
				$table->smarty->assign('debug_output', print_r($debug, true));
			}
			$table->hide_form();
			$page = $this->get_page();
			$total_pages = $this->get_total_pages($count);
			$page_links = $this->get_page_links($page, $total_pages);
			$table->smarty->assign('fluid_container', $this->fluid_container);
			$table->smarty->assign('page_links', $page_links);
			$table->smarty->assign('total_rows', $count);
			$table->smarty->assign('total_pages', $total_pages);
			$table->smarty->assign('page_limits', $this->page_limits);
			$table->smarty->assign('page', $page);
			$table->smarty->assign('page_limit', $this->page_limit);
			$table->smarty->assign('page_offset', $this->page_offset);
			$table->smarty->assign('order_by', $this->order_by);
			$table->smarty->assign('order_dir', $this->order_dir);
			$table->smarty->assign('edit_form', $this->order_form());
			$table->smarty->assign('select_multiple', $this->select_multiple);
			$table->smarty->assign('header_buttons', $this->header_buttons);
			if ($this->edit_row == true)
				$this->buttons[] = $this->edit_button;
			if ($this->delete_row == true)
				$this->buttons[] = $this->delete_button;
			if (sizeof($this->buttons) > 0)
				$table->smarty->assign('row_buttons', $this->buttons);
			$table->smarty->assign('add_row', $this->add_row);
			$table->smarty->assign('labels', $this->labels);
			$table->smarty->assign('rows', $rows);
			add_output($table->get_table());
			$GLOBALS['tf']->add_html_head_js('<script type="text/javascript" src="/js/crud.js"></script>');
			//add_output('<pre style="text-align: left;">'. print_r($this->tables, true) . '</pre>');
			//$smarty->assign('')
		}

		/**
		 * displays an error message ot the user
		 *
		 * @param string $message the text of the erro rmessage
		 */
		public function error($message) {
			dialog('Error', $message);
		}

		/**
		 * logs a message
		 *
		 * @param string $message message to log
		 * @param false|int $line optional line your calling from to track down where the log messages originates easily to send w/ the log message
		 * @param false|string $file optional file your calling from to track down where the log messages originates easily to send w/ the log message
		 */
		public function log($message, $line = false, $file = false) {
			if ($line !== false && $file !== false)
				billingd_log($message, $line, $file);
			elseif ($line !== false && $file == false)
				billingd_log($message, $line, __FILE__);
			elseif ($line == false && $file !== false)
				billingd_log($message, false, $file);
			else
				billingd_log($message, __LINE__, __FILE__);
		}

		/**
		 * sets the title for the crud page setting both the web page title and the table title
		 *
		 * @param bool|string $title text of the title
		 * @return Crud
		 */
		public function set_title($title = false) {
			if ($title === false) {
				$title = 'View ' . $this->settings['TITLE'];
			}
			$this->title = $title;
			return $this;
		}

		/**
		 * adds validations to the given field
		 *
		 * @param string $field name of the field to associate these validations with
		 * @param array $validations an array of validations to apply
		 */
		public function add_field_validations($field, $validations) {
			if (!isset($this->validations[$field])) {
				$this->validations[$field] = array();
			}
			foreach ($validations as $validation)
				if (!in_array($validation, $this->validations[$field]))
					$this->validations[$field] = array_merge($this->validations[$field], $validations);
		}

		/**
		 * adds validations for multiple fields
		 *
		 * @param array $validations an array with each element containing a $field => $validations  where $validatoins is an array of validatoins to apply and $field is the field name
		 */
		public function add_validations($validations) {
			foreach ($validations as $field => $field_validations) {
				$this->add_field_validations($field, $field_validations);
			}
		}

		/**
		 * adds an input type fieeld into the array of input types
		 *
		 * @param string $field the field name
		 * @param string $input_type the input type to use for the field
		 * @param false|array $data optoinal data to use along with the input type
		 */
		public function add_input_type_field($field, $input_type, $data = false) {
			//echo "Got here $field $input_type <pre>" . print_r($data, true) . "</pre><br>\n";
			// FIXME get in_array working properly / add validations based on this
			$this->input_types[$field] = array($input_type, $data);
			if (in_array($this->input_types[$field][0], array('select', 'select_multiple'))) {
				$this->add_field_validations($field, array('in_array' => $this->input_types[$field][1]['values']));
			}
		}

		/**
		 * directs adds an array of input types
		 *
		 * @param mixed $fields
		 */
		public function add_input_type_fields($fields) {
			foreach ($fields as $field => $data) {
				$this->input_types[$field] = $data;
			}
		}

		/**
		 * adds a field to the system
		 *
		 * @param string $field the field name
		 * @param false|string $label label for the field
		 * @param mixed $default default value
		 * @param mixed $validations validations to apply
		 * @param string $input_type type of input
		 * @param mixed $input_data data to use forpopulating theinput type
		 * @return Crud
		 */
		public function add_field($field, $label = false, $default = false, $validations = false, $input_type = false, $input_data = false) {
			if (!in_array($field, $this->fields))
				$this->fields[] = $field;
			if ($label !== false)
				$this->set_label($field, $label);
			if ($default !== false)
				$this->set_default($field, $default);
			if ($validations !== false)
				$this->add_field_validations($field, $validations);
			if ($input_type !== false)
				$this->add_input_type_field($field, $input_type, $input_data);
			return $this;
		}

		/**
		 * adds multiple fields to the sytsem
		 *
		 * @param array $fields an array of fields t oadd
		 */
		public function add_fields($fields) {
			foreach ($fields as $field) {
				$this->add_field($field);
			}
		}

		/**
		 * sets the default value for a field
		 *
		 * @param string $field the field name to set the default value of
		 * @param string $value the default value for the field
		 */
		public function set_default($field, $value) {
			$this->defaults[$field] = $value;
		}

		/**
		 * sets default values for multiple fields
		 *
		 * @param array $defaults an array of    field => value
		 */
		public function set_defaults($defaults) {
			foreach ($defaults as $field => $value) {
				$this->set_default($field, $value);
			}
		}

		/**
		 * sets the label for a field
		 *
		 * @param string $field field name
		 * @param string $label label to apply to the field
		 */
		public function set_label($field, $label) {
			$this->labels[$field] = $label;
		}

		/**
		 * sets the labels for an array of fields
		 *
		 * @param array $labels array with elements in the form of  field => label
		 */
		public function set_labels($labels) {
			foreach ($labels as $field => $label) {
				$this->set_label($field, $label);
			}
		}

		/**
		 * alias function for label()
		 *
		 * @param string $field field to get the labe for
		 * @return string the label
		 */
		public function get_label($field) {
			return $this->label($field);
		}

		/**
		 * gets the label for a field
		 *
		 * @param string $field field to get the labe for
		 * @return string the label
		 */
		public function label($field) {
			if (isset($this->labels[$field])) {
				return $this->labels[$field];
			} else {
				return ucwords(str_replace(array(
					'_'
				), array(
					' '
				), $field));
			}
		}

		/**
		 * adds an admin confirmatoin field
		 *
		 * @param mixed $field
		 * @param mixed $label
		 * @param mixed $default
		 * @param mixed $type
		 * @param mixed $data
		 */
		public function add_admin_confirmation_field($field, $label, $default, $type, $data = false) {
			$this->admin_confirm_fields[$field] = array(
				'label' => $label,
				'value' => $default,
				'type' => $type,
				'data' => $data,
			);
		}

		/**
		 * parse the table results looking at each field and getting usefulr information from it and creating input types fbasedonwhat it finds.
		 *
		 */
		public function parse_tables() {
			$first_field = false;
			foreach ($this->tables as $table => $fields) {
				foreach ($fields as $field => $data) {
					$input_type = 'input';
					$input_data = false;
					$validations = array();
					if (preg_match("/^(?P<type>tinyint|smallint|mediumint|bigint|int|float|double|timestamp|char|varchar|text|enum)(\((?P<size>\d*){0,1}(?P<types>'.*'){0,1}\)){0,1} *(?P<signed>unsigned){0,1}/m", $data['Type'], $matches)) {
						$type = $matches['type'];
						switch ($type) {
							case 'enum':
								if (isset($matches['types']) && $matches['types'] != '') {
									if (preg_match_all("/('(?P<types>[^']*)',{0,1})/m", $matches['types'], $types)) {
										$types = $types['types'];
									}
								}
								$input_type = 'select';
								$validations[] = array('in_array' => $types);
								$input_data = array(
									'values' => $types,
									'labels' => $types,
									'default' => false,
								);
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
								if (isset($matches['signed']) && $matches['signed'] == 'unsigned')
									$unsigned = true;
								else
									$unsigned = false;
								break;
							case 'float':
								if (isset($matches['size']) && $matches['size'] != '') {

								}
								if (isset($matches['signed']) && $matches['signed'] == 'unsigned')
									$unsigned = true;
								else
									$unsigned = false;
								break;
							case 'char':
								if (isset($matches['size']) && $matches['size'] != '') {

								}
								break;
							case 'varchar':
								if (isset($matches['size']) && $matches['size'] != '') {

								}
								break;
							case 'text':
								break;
							case 'timestamp':
								$validations[] = 'timestamp';
								break;
							default:
								$this->log("CRUD class Found Field Type '{$type}' from {$data['Type']} it does not Understand", __LINE__, __FILE__);
								break;
						}
					} else {
						$this->log("CRUD class Found Field Type {$data['Type']} it could not Parse", __LINE__, __FILE__);
					}
					if ($first_field == false)
						$first_field = $field;
					//billingd_log(print_r($this->query_fields, true));
					if ($this->type == 'table' || $this->all_fields == true || isset($this->query_fields[$field]) || isset($this->query_fields[$table.'.'.$field])) {
						if ($data['Key'] == 'PRI') {
							$this->primary_key = $field;
							if ($this->order_by == '')
								$this->order_by = $this->primary_key;
							$input_type = 'label';
						} elseif ($data['Key'] == 'MUL') {
							//$input_type = 'label';
						}

						$this->add_field($field, $data['Comment'], false, $validations, $input_type, $input_data);
					}
				}
				if ($this->primary_key == '') {
					//billingd_log("Genreatig Primary Key to {$first_field}", __LINE__, __FILE__);
					$this->primary_key = $first_field;
				}
			}
		}

		/**
		 * old carried over function used to validate a form submissoin
		 *
		 */
		public function validate_order() {
			$this->continue = true;
			$anything_set = false;
			foreach ($this->fields as $idx => $field) {
				if (isset($this->defaults[$field])) {
					$this->values[$field] = $this->defaults[$field];
				}
				if (isset($GLOBALS['tf']->variables->request[$field])) {
					$this->values[$field] = $GLOBALS['tf']->variables->request[$field];
					$this->set_vars[$field] = $this->values[$field];
					$anything_set = true;
				}
				if (isset($this->validations[$field])) {
					foreach ($this->validations[$field] as $validation) {
						if (!is_array($validation)) {
							switch ($validation) {
								case 'abs':
									$this->values[$field] = abs($this->values[$field]);
									break;
								case 'int':
									// TODO / FIXME _ check the isset() part here, if its not set i probably should fail it.
									if (isset($this->values[$field]) && $this->values[$field] != (int)$this->values[$field]) {
										$this->errors[] = 'Invalid ' . $this->label($field) . ' "' . $this->values[$field] . '"';
										$this->error_fields[] = $field;
										$this->values[$field] = (int)$this->values[$field];
										$this->continue = false;
									}
									break;
								case 'notags':
									if ($this->values[$field] != strip_tags($this->values[$field])) {
										$this->errors[] = 'Invalid ' . $this->label($field) . ' "' . $this->values[$field] . '"';
										$this->error_fields[] = $field;
										$this->values[$field] = strip_tags($this->values[$field]);
										$this->continue = false;
									}
									break;
								case 'trim':
									if (isset($this->values[$field])) {
										$this->values[$field] = trim($this->values[$field]);
									}
									break;
								case 'lower':
									if (isset($this->values[$field])) {
										$this->values[$field] = strtolower($this->values[$field]);
									}
									break;
								case 'in_array':
									if (isset($this->values[$field]) && !in_array($this->values[$field], $this->labels[$field])) {
										$this->errors[] = 'Invalid ' . $this->label($field) . ' "' . $this->values[$field] . '"';
										$this->error_fields[] = $field;
										$this->continue = false;
										$this->values[$field] = $this->defaults[$field];
									}
									break;
							}
						} else {
							if (isset($validation['in_array'])) {
								if (isset($this->values[$field]) && !in_array($this->values[$field], $validation['in_array'])) {
									$this->errors[] = 'Invalid ' . $this->label($field) . ' "' . $this->values[$field] . '"';
									$this->error_fields[] = $field;
									$this->continue = false;
									$this->values[$field] = $this->defaults[$field];
								}
							}
						}
					}
				}
			}
			if ($anything_set === false) {
				$this->continue = false;
			}
			if ($this->continue == true && !verify_csrf('crud_order_form'))
				$this->continue = false;
		}

		/**
		 * displays an add/order form for the crud
		 *
		 */
		public function order_form() {
			$edit_form = '';
			if ($this->stage == 2) {
				$table = new TFTable;
				$table->hide_table();
				$table->set_options('style=" background-color: #DFEFFF; border: 1px solid #C2D7EF;border-radius: 10px; padding-right: 10px; padding-left: 10px;"');
				$table->set_form_options('id="orderform" onsubmit="document.getElementsByName(' . "'confirm'" . ')[0].disabled = true; return true;"');
				$table->set_title($this->title);
				$table->csrf('crud_order_form');
				$table_pos = 0;
				foreach ($this->fields as $idx => $field) {
					if (isset($this->set_vars[$field]) && !in_array($field, $this->error_fields) && $this->values[$field] != '') {
						$value = $this->values[$field];
						if (isset($this->labels[$field . '_a']) && isset($this->labels[$field . '_a'][$value])) {
							$value = $this->labels[$field . '_a'][$value];
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
						$table->add_field('<b>' . $this->label($field) . '</b>');
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
									$field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), false, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
									break;
								case 'select_multiple':
								case 'select':
									$field_text = make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="' . $field . '" class="customsel" onChange="update_service_choices();" ' . (isset($data['extra']) ? $data['extra'] : '') . ($input_type == 'select_multiple' ? ' multiple' : ''));
									break;
								case 'raw':
									$field_text = $data;
									break;
								case 'func':
									if (is_array($data))
										$func = $data['data'];
									else
										$func = $data;
									$field_text = $this->$func();
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
											if ($this->debug === true) {
												//echo "this->column_templates[$x]['fields'][$field]['text'] set to "  .var_dump($text, true) . "<br>";
											}
										}
										if (isset($this->column_templates[$x]['fields'][$field]['align'])) {
											$align = $this->column_templates[$x]['fields'][$field]['align'];
											if ($this->debug === true) {
												//echo "this->column_templates[$x]['fields'][$field]['align'] set to "  .var_dump($align, true) . "<br>";
											}
										}
									} else {
										if (isset($this->column_templates[$x]['text'])) {
											$text = $this->column_templates[$x]['text'];
											if ($this->debug === true) {
												//echo "this->column_templates[$x]['text'] set to "  .var_dump($text, true) . "<br>";
											}
										}
										if (isset($this->column_templates[$x]['align'])) {
											$align = $this->column_templates[$x]['align'];
											if ($this->debug === true) {
												//echo "this->column_templates[$x]['align'] set to "  .var_dump($align, true) . "<br>";
											}
										}
									}
								}
								if ($this->debug === true) {
									//echo "Working on field $field<br>";
									//echo "Label:";
									//var_dump($label);
									//echo "<br>";
									//echo "Field Text:";
									//var_dump($field_text);
									//echo "<br>";
								}
								if (!isset($field_text)) {
									$this->log("field $field Field Text: " . print_r($field_text, true), __LINE__, __FILE__);
								}
								$text = str_replace(array('%title%','%field%'), array($label, $field_text), $text);
								$table->add_field($text, $align);
								$table_pos++;
							}
							*/
							add_output($field_text);
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
				$GLOBALS['tf']->add_html_head_js('<script src="js/g_a.js" type="text/javascript" ' . (WWW_TYPE == 'HTML5' ? '' : 'language="javascript"') . '></script>');
			} else {
				foreach ($this->fields as $idx => $field) {
					if (isset($this->input_types[$field])) {
						$input_type = $this->input_types[$field][0];
						if (in_array($field, $this->disabled_fields))
							$input_type = 'label';
						$data = $this->input_types[$field][1];
						$label = $this->label($field);
						if (!isset($this->values[$field]))
							$this->values[$field] = '';
						switch ($input_type) {
							case 'label':
								$value = $this->values[$field];
								// $field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), false, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								$field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . '
<div class="form-group">
	<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
	<div class="form-group input-group col-md-6">
		<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
		<input type="text" class="form-control" disabled="disabled" name="'.$field.'" id="'.$field.'" onchange="update_inputs(\"'.$field.'\", this);" value="' . $value . '" placeholder="'.$label.'" autocomplete="off" style="width: 100%;">
	</div>
</div>
' . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								break;
							case 'input':
								$value = $this->values[$field];
								// $field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), false, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								$field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . '
<div class="form-group">
	<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
	<div class="form-group input-group col-md-6">
		<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
		<input type="text" class="form-control" name="'.$field.'" id="'.$field.'" onchange="update_inputs(\"'.$field.'\", this);" value="'.$value.'" placeholder="'.$label.'" autocomplete="off" style="width: 100%;">
	</div>
</div>
' . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								break;
							case 'textarea':
								$value = $this->values[$field];
								// $field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), false, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								$field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . '
<div class="form-group">
	<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
	<div class="form-group input-group col-md-6">
		<textarea rows="2" class="form-control" placeholder="'.$label.'">' . $value . '</textarea>
	</div>
</div>
' . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								break;
							case 'select_multiple':
							case 'select':
								// $field_text = make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="' . $field . '" class="customsel" onChange="update_service_choices();" ' . (isset($data['extra']) ? $data['extra'] : '') . ($input_type == 'select_multiple' ? ' multiple' : ''));
								$field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . '
<div class="form-group">
	<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
	<div class="form-group input-group col-md-6">
		<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
		'.make_select(($input_type == 'select_multiple' ? $field.'[]' : $field), $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="' . $field . '" class="form-control customsel" onChange="update_service_choices();" ' . (isset($data['extra']) ? $data['extra'] : '') . ($input_type == 'select_multiple' ? ' multiple style="height: ' .(14+(17*sizeof($data['values']))). 'px;"' : '')).'
	</div>
</div>
' . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								break;
							case 'raw':
								$field_text = $data;
								break;
							case 'func':
								if (is_array($data)) {
									$func = $data['data'];
								} else {
									$func = $data;
								}
								$field_text = $this->$func();
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
										if ($this->debug === true) {
											//echo "this->column_templates[$x]['fields'][$field]['text'] set to "  .var_dump($text, true) . "<br>";
										}
									}
									if (isset($this->column_templates[$x]['fields'][$field]['align'])) {
										$align = $this->column_templates[$x]['fields'][$field]['align'];
										if ($this->debug === true) {
											//echo "this->column_templates[$x]['fields'][$field]['align'] set to "  .var_dump($align, true) . "<br>";
										}
									}
								} else {
									if (isset($this->column_templates[$x]['text'])) {
										$text = $this->column_templates[$x]['text'];
										if ($this->debug === true) {
											//echo "this->column_templates[$x]['text'] set to "  .var_dump($text, true) . "<br>";
										}
									}
									if (isset($this->column_templates[$x]['align'])) {
										$align = $this->column_templates[$x]['align'];
										if ($this->debug === true) {
											//echo "this->column_templates[$x]['align'] set to "  .var_dump($align, true) . "<br>";
										}
									}
								}
							}
							if ($this->debug === true) {
								//echo "Working on field $field<br>";
								//echo "Label:";
								//var_dump($label);
								//echo "<br>";
								//echo "Field Text:";
								//var_dump($field_text);
								//echo "<br>";
							}
							if (!isset($field_text)) {
								$this->log("field $field Field Text: " . print_r($field_text, true), __LINE__, __FILE__);
							}
							$text = str_replace(array('%title%','%field%'), array($label, $field_text), $text);
							$table->add_field($text, $align);
						}
						$table->set_row_options('id="' . $field . 'row"');
						$table->add_row();
						$table->set_row_options();
						*/
						$edit_form .= $field_text;
					}
				}
				/*
				$table->set_colspan($this->columns);
				$table->add_field($table->make_submit('Continue to next step', false, true));
				$table->add_row();
				$table->set_method('get');
				add_output($table->get_table());
				$GLOBALS['tf']->add_html_head_js('<script src="js/g_a.js" type="text/javascript" ' . (WWW_TYPE == 'HTML5' ? '' : 'language="javascript"') . '></script>');
				$GLOBALS['tf']->add_html_head_js('<script src="js/customSelect/jquery.customSelect.min.js"></script>');
				*/
			}
			return $edit_form;
		}

		/**
		 * disables initially populating the table with results and loads it from ajax instead
		 *
		 */
		public function disable_initial_populate() {
			$this->initial_populate = false;
			return $this;
		}

		/**
		 * disables the delete button next to each row
		 *
		 * @return Crud
		 */
		public function disable_delete() {
			$this->delete_row = false;
			return $this;
		}

		/**
		 * disables the checkboxs to the left of the rows for bulk actions
		 *
		 * @return Crud
		 */
		public function disable_select_multiple() {
			$this->select_multiple = false;
			return $this;
		}

		/**
		 * disables the edit button next to each row
		 *
		 * @return Crud
		 */
		public function disable_edit() {
			$this->edit_row = false;
			return $this;
		}

		/**
		 * disables the add record button
		 *
		 * @return Crud
		 */
		public function disable_add() {
			$this->add_row = false;
			return $this;
		}

		/**
		 * disables the delete button next to each row
		 *
		 * @return Crud
		 */
		public function enable_initial_populate() {
			$this->initial_populate = true;
			return $this;
		}

		/**
		 * disables the delete button next to each row
		 *
		 * @return Crud
		 */
		public function enable_delete() {
			$this->delete_row = true;
			return $this;
		}

		/**
		 * enables the checkboxs to the left of the rows for bulk actions
		 *
		 * @return Crud
		 */
		public function enable_select_multiple() {
			$this->select_multiple = true;
			return $this;
		}

		/**
		 * enables the edit button next to each row
		 *
		 * @return Crud
		 */
		public function enable_edit() {
			$this->edit_row = true;
			return $this;
		}

		/**
		 * enables the add record button
		 *
		 * @return Crud
		 */
		public function enable_add() {
			$this->add_row = true;
			return $this;
		}

		/**
		 * disables a field from being edited
		 *
		 * @param string $field field name
		 * @return Crud
		 */
		public function disable_field($field) {
			if (!in_array($field, $this->disabled_fields))
				$this->disabled_fields[] = $field;
			return $this;
		}

		/**
		 * disables an array of fields from the edit function
		 *
		 * @param array $fields an array of fields
		 * @return Crud
		 */
		public function disable_fields($fields) {
			foreach ($fields as $field)
				$this->disable_field($field);
			return $this;
		}

		/**
		 * displays a confirmatoin type page for the add/order form
		 *
		 */
		public function confirm_order() {
			$this->confirm = true;
			add_output('Order not yet completed.  Click on one of the payment options below to complete the order.<br><br>');
			$table = new TFTable;
			$table->hide_table();
			$table->set_method('get');
			$table->set_options('width="500" cellpadding=5');
			$table->set_form_options('id="orderform" onsubmit="document.getElementsByName(' . "'confirm'" . ')[0].disabled = true; return true;"');
			$table->set_title($this->settings['TITLE'] . ' Order Summary');
			if ($GLOBALS['tf']->ima == 'admin') {
				$table->add_hidden('custid', $this->custid);
			}
			$table->add_hidden('module', $this->module);
			$table->add_hidden('pp_token', '');
			$table->add_hidden('pp_payerid', '');
			$this->returnURL = 'choice=' . urlencode($this->choice);
			$payment_method_table_fields = array($this->custid);
			foreach ($this->set_vars as $field => $value) {
				$this->returnURL .= '&' . $field . '=' . urlencode($value);
				$table->add_hidden($field, $value);
				$label = $value;
				if (is_numeric($value) && isset($this->labels[$field . '_i']) && isset($this->labels[$field . '_i'][$value])) {
					$label = $this->labels[$field . '_i'][$value];
				} elseif (isset($this->labels[$field . '_a']) && isset($this->labels[$field . '_a'][$value])) {
					$label = $this->labels[$field . '_a'][$value];
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
					$table->add_field('<b>' . $this->label($field) . '</b>', 'l');
					$table->add_field($label, 'l');
					$table->add_row();
				}
			}
			if (SESSION_COOKIES == false) {
				$this->returnURL .= '&sessionid=' . urlencode($GLOBALS['tf']->session->sessionid);
			}
			if ($GLOBALS['tf']->ima == 'admin') {
				foreach ($this->admin_confirm_fields as $field => $data) {
					switch ($data['type']) {
						case 'select_multiple':
						case 'select':
							$field_text = make_select(($data['type'] == 'select_multiple' ? $field.'[]' : $field), $data['data']['values'], $data['data']['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['data']['default']), 'id="' . $field . '" class="customsel" onChange="update_service_choices();" ' . (isset($data['data']['extra']) ? $data['data']['extra'] : '') . ($data['type'] == 'select_multiple' ? ' multiple' : ''));
							$table->add_field('<b>' . $data['label'] . '</b>', 'l');
							$table->add_field($field_text, 'l');
							$table->add_row();
							break;
						case 'input':
							$table->add_field('<b>' . $data['label'] . '</b>', 'l');
							$table->add_field($table->make_input($field, $data['value'], (isset($data['data']['length']) ? $data['data']['length'] : 30)), 'l');
							$table->add_row();
							break;
						case 'func':
							$table->add_field('<b>' . $data['label'] . '</b>', 'l');
							$func = $data['data'];
							$table->add_field($this->$func(), 'l');
							$table->add_row();
							break;
					}
				}
			}
			$this->db = get_module_db($this->module);
			$this->db->query(make_insert_query('pending_orders', array(
				'pend_id' => null,
				'pend_choice' => $this->choice,
				'pend_timestamp' => mysql_now(),
				'pend_custid' => $this->custid,
				'pend_data' => serialize($this->set_vars))), __LINE__, __FILE__);
			//				$GLOBALS['tf']->add_html_head_js('<script src="js/g_a.js" type="text/javascript" ' . (WWW_TYPE == 'HTML5' ? '' : 'language="javascript"') . '></script>');
			$this->continue = false;
		}

		/**
		 * performs standard string replacements in queries replacing things like __MODULE__ with the module name
		 *
		 * @param string $query the sql query to change
		 * @return string the modified sql query
		 */
		public function decorate_query($query) {
			return str_replace(
				array(
					'__MODULE__',
					'__TITLE__',
					'__CUSTID__',
					'__LOGIN__',
					'__TBLNAME__',
					'__TABLE__',
					'__PREFIX__',
					'__TITLE_FIELD__',
				),
				array(
					$this->module,
					$this->settings['TITLE'],
					$this->custid,
					$GLOBALS['tf']->accounts->cross_reference($this->custid),
					$this->settings['TBLNAME'],
					$this->settings['TABLE'],
					$this->settings['PREFIX'],
					$this->settings['TITLE_FIELD'],
				),
				$query
			);
		}

		/**
		 * handles any type of special formatting that has been setup for that field , such as
		 * displaying a specific field as a link to edit_customer3 or something like that
		 *
		 * @param string $field the name of the field
		 * @param array $row associative array of field =>value pairs
		 * @return string the $value formatted for display
		 */
		public function decorate_field($field, $row) {
			$value = $row[$field];
			$value = htmlspecialchars($value);
			$search = array('%field%', '%value%');
			$replace = array($field, $value);
			foreach ($row as $row_field => $row_value) {
				$search[] = '%'.$row_field.'%';
				$replace[] = $row_value;
			}
			if (isset($this->filters[$field])) {
				foreach ($this->filters[$field] as $idx => $filter) {
					if ($filter['type'] == 'string') {
						$value = str_replace($search, $replace, $filter['value']);
					} elseif ($filter['type'] == 'function') {
						eval('$value = '.$filter['value'].'($field, $value);');
					}
				}
			}
			return $value;
		}

		/**
		 * adds a field display fitler
		 *
		 * @param string $field the name of the field
		 * @param string $type type of filter, can be string, function,
		 * @param mixed $value the string pattern to use to replace
		 * @param false|string $acl optional acl rule required for this filter, such as 'view_customer'
		 * @param string $bad_acl_text same as the $text field but meant to be used to specify what is displayed instead of a link when the acl check is failed
		 */
		public function add_filter($field, $value = '%value%', $type = 'string', $acl = false, $bad_acl_test = '%value%') {
			//billingd_log("add_filter({$field}, {$value}, {$type}, {$acl}, {$bad_acl_test}) called", __LINE__, __FILE__);
			function_requirements('has_acl');
			if (!isset($this->filters[$field]))
				$this->filters[$field] = array();
			if (!has_acl($acl)) {
				$type = 'string';
				$value = $bad_acl_test;
			}
			$output = array(
				'type' => $type,
				'value' => $value,
				'acl' => $acl,
			);
			$this->filters[$field][] = $output;
		}

		/**
		 * adds a field filter that replaces the value with an a href link and optional tooltip title.
		 * if you specify an acl permission, then when the user fails that permission, they will just
		 * be shown the normal value instead of wrapping it in a link.   You can also optionally specify
		 * a failed acl string so when it fails the acl check instead of just displaying the plain value,
		 * you can specify a string filter to get applied to the value if it fails instead
		 *
		 * The filters have special strings that are automatically replaced with data, the current
		 * fields supported are:

		 *     %field%	      - replaced with the field name, ie account_lid
		 * 	   %value%	      - replaced with the fields value, ie username@email.com
		 *
		 * you can also include any field names to have them automatically replaced w/ their value, ie:
		 *
		 *     %account_id%   - if there is a field in the result row called 'account_id', then
		 *                      this is replaced w/ the value of that field
		 *
		 * @param string $field the field name
		 * @param string $link url, it can be a full url or just like a 'choice=none.blah' type url
		 * @param false|string $title optionally specify a title/tooltip to be shown when you hover the link, defauls to false , or no title/tooltip
		 * @param false|string $acl optional acl rule required for this filter, such as 'view_customer'
		 * @param string $bad_acl_text same as the $text field but meant to be used to specify what is displayed instead of a link when the acl check is failed
		 */
		public function add_filter_link($field, $link, $title = false, $acl = false, $bad_acl_test = '%value%') {
			//billingd_log("add_filter_link({$field}, {$link}, {$title}, {$acl}, {$bad_acl_test}) called", __LINE__, __FILE__);
			// $link = 'choice=none.edit_customer&customer=%field%'
			$this->add_filter($field, '<a href="' . $link . '" data-container="body"'.($title !== false ? ' data-toggle="tooltip" title="'.$title.'"' : '').'>%value%</a>', 'string', $acl, $bad_acl_test);
		}

		/**
		 * processeds the standard/default set of filters, either adding all the filters or adding
		 * them for the specific fields you tell it to
		 *
		 * @param false|string|array $fields
		 */
		public function default_filters($fields = false) {
			if ($fields == false)
				$fields = array_values($this->query_fields);
			elseif (!is_array($fields))
				$fields = array($fields);
			foreach ($fields as $field) {
				//billingd_log($field);
				switch ($field) {
					case 'account_lid':
						$this->add_filter_link($field, '?choice=none.edit_customer3&customer=%account_id%', 'Edit Customer', 'view_customer');
						break;
					case $this->settings['PREFIX'].'_name':
						$this->add_filter_link($field, "?choice=none.view_host_server&module={$this->module}&name=%{$this->settings['PREFIX']}_name%", 'View Host Server', 'view_service');
						break;
					// @TODO distinguish between like vps_masters.vps_id and vps.vps_id type fields before doin this
					//case $this->settings['PREFIX'].'_id':
					case $this->settings['TITLE_FIELD']:
						if ($this->module != 'webhosting')
							$this->add_filter_link($field, '?choice=none.view_'.$this->settings['PREFIX'].'&id=%'.$this->settings['PREFIX'].'_id%', 'View '.$this->settings['TITLE'], 'view_service');
						elseif ($GLOBALS['tf']->ima == 'admin')
							$this->add_filter_link($field, '?choice=none.view_'.$this->settings['PREFIX'].'2&id=%'.$this->settings['PREFIX'].'_id%', 'View '.$this->settings['TITLE'], 'view_service');
						else
							$this->add_filter_link($field, '?choice=none.view_'.$this->settings['PREFIX'].'4&id=%'.$this->settings['PREFIX'].'_id%', 'View '.$this->settings['TITLE'], 'view_service');
						break;
				}
			}

		}

	}
