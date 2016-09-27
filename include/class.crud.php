<?php
	/**
	 * CRUD Class
	 *
	 * its got a very customizable output and customizable field handling ,  but aiming for it to automatically
	 * generate an optiomal page w/out the need for customizing in most cases
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
		public $ajax = false;
		public $debug = false;
		public $module;
		public $choice;
		public $table;
		public $query;
		public $primary_key;
		public $db;
		public $settings;
		public $tables = array();
		public $type = '';
		public $title = '';
		public $columns = 3;
		public $column_templates = array();
		public $fields = array();
		public $values = array();
		public $labels = array();
		public $defaults = array();
		public $validations = array();
		public $input_types = array();
		public $admin_confirm_fields = array();
		public $price_align = 'r';
		public $price_text_align = 'r';
		// from the sql query parser
		public $queries = null;
		public $query_fields = array();
		// temp fields maybe from buy service class i think
		public $stage = 1;

		public function __construct() {
			return $this;
		}

		public static function init($table_or_query, $module = 'default') {
			$static = !(isset($this) && $this instanceof self);
			if ($static == true)
				$crud = new crud();
			else
				$crud = &$this;
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
				$crud->ajax = true;
				$crud->choice = $GLOBALS['tf']->variables->request['crud'];
			}
			if (substr($crud->choice, 0, 5) == 'none.')
				$crud->choice = substr($crud->choice, 5);
			if (strpos($table_or_query, ' ')) {
				$crud->query = $table_or_query;
				$crud->type = 'query';
				//$crud->load_tables();
				$crud->parse_query();
				$crud->get_tables_from_query();
			} else {
				$crud->table = $table_or_query;
				$crud->type = 'table';
				$crud->tables[$crud->table] = $crud->get_table_details($crud->table);
			}
			$crud->parse_tables();
			return $crud;
		}

		public function go() {
			if ($this->ajax == true) {
				$this->ajax_handler();
			} else {
				$this->list_records();
				//$this->order_form();
				//$this->stage = 2;
				//$this->order_form();
			}
			return $this;
		}

		/**
		 * if called via an ajax request the processing is passed off to this handler, which takes care of ajax listing updates, adding, editing, deleting, searching, and exporting records
		 *
		 */
		public function ajax_handler() {
			$action = $GLOBALS['tf']->variables->request['action'];
			billingd_log("CRUD {$this->title} {$action} Handling", __LINE__, __FILE__);
			switch ($action) {
				case 'edit':
					// generic data to get us here is in _GET, while the specific fields are all in _POST
					//billingd_log(print_r($_GET, true), __LINE__, __FILE__);
					//billingd_log(print_r($_POST, true), __LINE__, __FILE__);
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
							// validate fields
							foreach ($this->validations[$orig_field] as $validation) {
								if (!is_array($validation)) {
									switch ($validation) {
										case 'abs':
											$value = abs($value);
											break;
										case 'int':
											// TODO / FIXME _ check the isset() part here, if its not set i probably should fail it.
											if (isset($value) && $value != intval($value)) {
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
											if (isset($value) && !in_array($value, $this->labels[$field])) {
												$errors[] = 'Invalid ' . $this->label($field) . ' "' . $value . '"';
												$error_fields[] = $field;
												$valid = false;
											}
											break;
									}
								} else {
									if (isset($validation['in_array'])) {
										if (isset($value) && !in_array($value, $validation['in_array'])) {
											$errors[] = 'Invalid ' . $this->label($field) . ' "' . $value . '"';
											$error_fields[] = $field;
											$valid = false;
										}
									}
								}

							}
							// build query
							$safe_value = $this->db->real_escape($value);
							if ($field == $this->primary_key)
								$query_where[] = "{$field}='{$safe_value}'";
							else {
								// see which fields are editable
								$query_fields[] = "{$field}='{$safe_value}'";
							}
						}
					}
					// update database
					$query = "update " . $query_table . " set " . implode(', ', $query_fields) . " where " . implode(', ', $query_where);
					if ($valid == true) {
						billingd_log("i want to run query {$query}", __LINE__, __FILE__);
						//$this->db->query($query, __LINE__, __FILE__);
						// send response for js handler
						echo "ok";
						echo "i want to run query {$query}";
					} else {
						billingd_log("error validating so couldnt run query {$query}", __LINE__, __FILE__);
						// send response for js handler
						echo "There was an error with validation";
					}
					break;
				case 'list':
					// apply pagination
					// apply sorting
					// send reseponse for js handler
					break;
				case 'add':
					// generic data to get us here is in _GET, while the specific fields are all in _POST
					// match up fields
					// see which fields are editable
					// validate fields
					// build query
					// update database
					// send response for js handler
					break;
				case 'delete':
					// match up row
					// build query
					// update db
					// send resposne for js handler
					break;
				case 'search':
					// get fields
					// validate data
					// build search query
					// run query
					// send response for js handler
					break;
				case 'export':
					// get export type
					// get data
					// convert data
					// send data
					break;
				default:
					billingd_log("Invalid Crud {$this->title} Action {$action}", __LINE__, __FILE__);
					break;
			}
		}

		public function load_tables() {
			$db = clone $this->db;
			$db->query("show full tables where Table_Type = 'BASE TABLE'", __LINE__, __FILE__);
			while ($db->next_record(MYSQL_NUM)) {
				$this->tables[$db->f(0)] = null;
				$this->tables[$db->f(0)] = $this->get_table_details($db->f(0));
			}
		}

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

		public function parse_query_fields($queries = false) {
			if ($queries == false)
				$queries = $this->queries;
			foreach ($queries[0]->getColumns() as $col => $col_arr) {
				$field_arr = $col_arr[0]->getMembers();
				if (sizeof($field_arr) > 1) {
					$table = $field_arr[0];
					$orig_field = $field_arr[1];
					//$orig_field = $table.'.'.$orig_field;
				} else {
					$table = false;
					$orig_field = $field_arr[0];
				}
				if (sizeof($col_arr) > 1) {
					$field = $col_arr[1];
				} else {
					$field = $orig_field;
				}
				$fields[$field] = ($table === false ? $orig_field : $table . '.' . $orig_field);
			}
			$this->query_fields = $fields;
			//add_output('<pre style="text-align: left;">' . print_r($fields, true) . '</pre>');
		}

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

		public function get_table_details($table) {
			$db = clone $this->db;
			$db->query("show full columns from {$table}", __LINE__, __FILE__);
			$fields = array();
			while ($db->next_record(MYSQL_ASSOC)) {
				if ($db->Record['Comment'] == '')
					$db->Record['Comment'] = ucwords(str_replace(
						array('ssl_', 'vps_', '_id', '_lid', '_ip', '_'),
						array('SSL_', 'VPS_', ' ID', ' Login Name',' IP', ' '),
						$db->Record['Field']));
				$fields[$db->Record['Field']] = $db->Record;
			}
			return $fields;
		}

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
			$db = $this->db;
			if ($this->type == 'table') {
				$db->query("select count(*) from {$this->table}", __LINE__, __FILE__);
				$db->next_record(MYSQL_NUM);
				$count = $db->f(0);
			} else {
				if (preg_match('/^.*( from .*)$/', $this->query, $matches)) {
					$from = $matches[1];
					$db->query("select count(*) {$from}", __LINE__, __FILE__);
					$db->next_record(MYSQL_NUM);
					$count = $db->f(0);
				}
			}
			$page_limit = 10;
			$page_offset = 0;
			if ($this->type == 'table')
				$db->query("select * from {$this->table} limit {$page_offset}, {$page_limit}", __LINE__, __FILE__);
			else
				$db->query("{$this->query} limit {$page_offset}, {$page_limit}", __LINE__, __FILE__);
			$header_shown = false;
			$idx = 0;
			while ($db->next_record(MYSQL_ASSOC)) {
				if ($header_shown == false) {
					$header_shown = true;
					if ($this->type == 'table') {
						foreach ($this->tables[$this->table] as $field => $field_data)
							$table->add_header_field($field_data['Comment']);
					} else {
						foreach (array_keys($db->Record) as $field)
							if (isset($this->tables[$this->table][$field]))
								$table->add_header_field($this->tables[$this->table][$field]['Comment']);
							else
								$table->add_header_field($this->label($field));
					}
					$table->add_header_row();
				}
				$rows[] = $db->Record;
				$table->set_row_options('id="itemrow'.$idx.'"');
				foreach ($db->Record as $field =>$value) {
					$table->add_field($value);
				}
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
			$table->hide_form();
			$table->smarty->assign('edit_form', $this->order_form());
			add_output($table->get_table());
			$GLOBALS['tf']->add_html_head_js('
<script type="text/javascript">
var crud_rows = ' . json_encode($rows) . ';
var primary_key = "' . $this->primary_key . '";
</script>');
			$GLOBALS['tf']->add_html_head_js('<script type="text/javascript" src="/js/crud.js"></script>');
			//add_output('<pre style="text-align: left;">'. print_r($this->tables, true) . '</pre>');
			//$smarty->assign('')
		}

		public function error($message) {
			dialog('Error', $message);
		}

		public function log($message, $line = false, $file = false) {
			if (!$line !== false && $file !== false)
				billingd_log($message, $line, $file);
			elseif ($line !== false)
				billingd_log($message, $line);
			elseif ($file !== false)
				billingd_log($message, false, $file);
			else
				billingd_log($message, __LINE__, __FILE__);
		}

		public function set_title($title = false) {
			if ($title === false) {
				$title = 'Purchase ' . $this->settings['TITLE'];
			}
			$this->title = $title;
			return $this;
		}

		public function add_field_validations($field, $validations) {
			if (!isset($this->validations[$field])) {
				$this->validations[$field] = array();
			}
			$this->validations[$field] = array_merge($this->validations[$field], $validations);
		}

		public function add_validations($validations) {
			foreach ($validations as $field => $field_validations) {
				$this->add_field_validations($field, $field_validations);
			}
		}

		public function add_input_type_field($field, $input_type, $data = false) {
			//echo "Got here $field $input_type <pre>" . print_r($data, true) . "</pre><br>\n";
			// FIXME get in_arary working properly / add validations based on this
			$this->input_types[$field] = array($input_type, $data);
			if ($this->input_types[$field][0] == 'select') {
				$this->add_field_validations($field, array('in_array' => $this->input_types[$field][1]['values']));
			}
		}

		public function add_input_type_fields($fields) {
			foreach ($fields as $field => $data) {
				$this->input_types[$field] = $data;
			}
		}

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

		public function add_fields($fields) {
			foreach ($fields as $field) {
				$this->add_field($field);
			}
		}

		public function set_default($field, $value) {
			$this->defaults[$field] = $value;
		}

		public function set_defaults($defaults) {
			foreach ($defaults as $field => $value) {
				$this->set_default($field, $value);
			}
		}

		public function set_label($field, $label) {
			$this->labels[$field] = $label;
		}

		public function set_labels($labels) {
			foreach ($labels as $field => $label) {
				$this->set_label($field, $label);
			}
		}

		public function get_label($field) {
			return $this->label($field);
		}

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

		public function add_admin_confirmation_field($field, $label, $default, $type, $data = false) {
			$this->admin_confirm_fields[$field] = array(
				'label' => $label,
				'value' => $default,
				'type' => $type,
				'data' => $data,
			);
		}

		public function parse_tables() {
			foreach ($this->tables as $table => $fields) {
				foreach ($fields as $field => $data) {
					$input_type = 'input';
					$input_data = false;
					$validations = array();
					if (preg_match("/^(?P<type>tinyint|smallint|mediumint|bigint|int|char|varchar|text|enum)(\((?P<size>\d*){0,1}(?P<types>'.*'){0,1}\)){0,1} *(?P<signed>unsigned){0,1}/m", $data['Type'], $matches)) {
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
							default:
								billingd_log("CRUD class Found Field Type '{$type}' from {$data['Type']} it Doesnt Understand", __LINE__, __FILE__);
								break;
						}
					} else {
						$type = $data['Type'];
						billingd_log("CRUD class Found Field Type {$data['Type']} it Couldnt Parse", __LINE__, __FILE__);
					}
					if ($this->type == 'table' || isset($this->query_fields[$field]) || isset($this->query_fields[$table.'.'.$field])) {
						if ($data['Key'] == 'PRI') {
							$this->primary_key = $field;
							$input_type = 'label';
						} elseif ($data['Key'] == 'MUL') {
							//$input_type = 'label';
						}
						$this->add_field($field, $data['Comment'], false, $validations, $input_type, $input_data);
					}
				}
			}
		}

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
									if (isset($this->values[$field]) && $this->values[$field] != intval($this->values[$field])) {
										$this->errors[] = 'Invalid ' . $this->label($field) . ' "' . $this->values[$field] . '"';
										$this->error_fields[] = $field;
										$this->values[$field] = intval($this->values[$field]);
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

		public function order_form() {
			$edit_form = '';
			if ($this->stage == 2) {
				$table = new TFTable;
				$table->hide_table();
				$table->set_options('style=" background-color: #DFEFFF; border: 1px solid #C2D7EF;border-radius: 10px; padding-right: 10px; padding-left: 10px;"');
				//$table->set_options('width="500" cellpadding=5');
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
							$data = $this->input_types[$field][1];
							switch ($input_type) {
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
								case 'select':
									$field_text = make_select($field, $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="' . $field . '" class="customsel" onChange="update_service_choices();" ' . (isset($data['extra']) ? $data['extra'] : ''));
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
									billingd_log("field $field Field Text: " . print_r($field_text, true), __LINE__, __FILE__);
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
						$data = $this->input_types[$field][1];
						$label = $this->label($field);
						switch ($input_type) {
							case 'label':
								$value = $this->values[$field];
								// $field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . $table->make_input($field, $value, (isset($data['length']) ? $data['length'] : 30), false, (isset($data['extra']) ? $data['extra'] : '')) . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								$field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . '
<div class="form-group">
	<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
	<div class="form-group input-group col-md-6">
		<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
		<input type="text" class="form-control" disabled="disabled" name="'.$field.'" id="'.$field.'" onchange="update_inputs(\"'.$field.'\", this);" value="" placeholder="'.$label.'" autocomplete="off" style="width: 100%;">
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
		<input type="text" class="form-control" name="'.$field.'" id="'.$field.'" onchange="update_inputs(\"'.$field.'\", this);" value="" placeholder="'.$label.'" autocomplete="off" style="width: 100%;">
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
		<textarea rows="2" class="form-control" placeholder="'.$label.'"></textarea>
	</div>
</div>
' . (isset($data['extrahtml']) ? $data['extrahtml'] : '');
								break;

							case 'select':
								// $field_text = make_select($field, $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="' . $field . '" class="customsel" onChange="update_service_choices();" ' . (isset($data['extra']) ? $data['extra'] : ''));
								$field_text = (isset($data['prefixhtml']) ? $data['prefixhtml'] : '') . '
<div class="form-group">
	<label class="col-md-offset-1 col-md-4 control-label" for="'.$field.'">'.$label.'</label>
	<div class="form-group input-group col-md-6">
		<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
		'.make_select($field, $data['values'], $data['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['default']), 'id="' . $field . '" class="form-control customsel" onChange="update_service_choices();" ' . (isset($data['extra']) ? $data['extra'] : '')).'
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
								billingd_log("field $field Field Text: " . print_r($field_text, true), __LINE__, __FILE__);
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
				$olabel = "";
				if (is_numeric($value) && isset($this->labels[$field . '_i']) && isset($this->labels[$field . '_i'][$value])) {
					$label = $this->labels[$field . '_i'][$value];
					$olabel = $label;
				} elseif (isset($this->labels[$field . '_a']) && isset($this->labels[$field . '_a'][$value])) {
					$label = $this->labels[$field . '_a'][$value];
					$olabel = $label;
				}
				if (isset($this->input_types[$field])) {
					$input_type = $this->input_types[$field][0];
					$data = $this->input_types[$field][1];
					switch ($input_type) {
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
				if (!in_array($field, array('custid'))) {
					$payment_method_table_fields[] = $value;
				}
			}
			if (SESSION_COOKIES == false) {
				$this->returnURL .= '&sessionid=' . urlencode($GLOBALS['tf']->session->sessionid);
			}
			if ($GLOBALS['tf']->ima == 'admin') {
				foreach ($this->admin_confirm_fields as $field => $data) {
					switch ($data['type']) {
						case 'select':
							$field_text = make_select($field, $data['data']['values'], $data['data']['labels'], (isset($this->set_vars[$field]) ? $this->set_vars[$field] : $data['data']['default']), 'id="' . $field . '" class="customsel" onChange="update_service_choices();" ' . (isset($data['data']['extra']) ? $data['data']['extra'] : ''));
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
				'pend_id' => NULL,
				'pend_choice' => $this->choice,
				'pend_timestamp' => mysql_now(),
				'pend_custid' => $this->custid,
				'pend_data' => serialize($this->set_vars))), __LINE__, __FILE__);
			//				$GLOBALS['tf']->add_html_head_js('<script src="js/g_a.js" type="text/javascript" ' . (WWW_TYPE == 'HTML5' ? '' : 'language="javascript"') . '></script>');
			$this->continue = false;
		}

	}
?>
