<?php
	/**
	 * CRUD Class
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
		public $debug = false;
		public $module;
		public $choice;
		public $table;
		public $query;
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

		public function __construct($table_or_query, $module = 'default') {
			add_js('bootstrap');
			add_js('font-awesome');
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
			$this->set_title();
			if (strpos($table_or_query, ' ')) {
				$this->query = $table_or_query;
				$this->type = 'query';
				//$this->load_tables();
				$this->get_tables_from_query();
				$this->parse_query();
			} else {
				$this->table = $table_or_query;
				$this->type = 'table';
				$this->tables[$this->table] = $this->get_table_details($this->table);
			}
			$this->parse_tables();
			$this->choice = $GLOBALS['tf']->variables->request['choice'];
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
				} else {
					$table = false;
					$orig_field = $field_arr[0];
				}
				if (sizeof($col_arr) > 1) {
					$field = $col_arr[1];
				} else {
					$field = $orig_field;
				}
				$fields[$orig_field] = $field;
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

		public function go() {
			$this->list_records();
			$this->order_form();
			$this->stage = 2;
			$this->order_form();
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
				foreach ($db->Record as $field =>$value)
					$table->add_field($value);
				$table->add_row();
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
			add_output($table->get_table());
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
					$this->add_field($field, $data['Comment'], false, $validations, $input_type, $input_data);
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
				if ($this->use_coupon === true && isset($this->set_vars[$this->coupon_field])) {
					$this->coupon = $this->set_vars[$this->coupon_field];
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
				$table = new TFTable;
				$table->set_options('style=" background-color: #DFEFFF; border: 1px solid #C2D7EF;border-radius: 10px; padding-right: 10px; padding-left: 10px;"');
				$table->hide_table();
				$table->hide_title();
				foreach ($this->fields as $idx => $field) {
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
								if (is_array($data)) {
									$func = $data['data'];
								} else {
									$func = $data;
								}
								$field_text = $this->$func();
								break;
						}
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
					}
				}
				$table->set_row_options('');
				$table->set_colspan($this->columns - 1);
				$table->add_field('<h3>Total</h3>', $this->price_text_align);
				$table->add_field('<h3 id="totalcost">$6</h3>', $this->price_align);
				$table->add_row();
				$table->set_colspan($this->columns);
				$table->add_field($table->make_submit('Continue to next step', false, true));
				$table->add_row();
				$table->set_method('get');
				add_output($table->get_table());
				$GLOBALS['tf']->accounts->restore_db();
				$GLOBALS['tf']->history->restore_db();
				$GLOBALS['tf']->add_html_head_js('<script src="js/g_a.js" type="text/javascript" ' . (WWW_TYPE == 'HTML5' ? '' : 'language="javascript"') . '></script>');
				$GLOBALS['tf']->add_html_head_js('<script src="js/customSelect/jquery.customSelect.min.js"></script>');
			}
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
			$payment_method_table_fields = array($this->custid, $this->coupon);
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
			if ($this->coupon != '') {
				$table->add_field('<b>Coupon</b>', 'l');
				$table->add_field($this->coupon, 'l');
				$table->add_row();
			}
			$table->add_field('<b>CPU Cores</b>', 'l');
			$table->add_field(ceil($this->values['slices'] / 4), 'l');
			$table->add_row();
			$table->add_field('<b>Memory</b>', 'l');
			$table->add_field(VPS_SLICE_RAM * $this->values['slices'] . ' MB Ram', 'l');
			$table->add_row();
			$table->add_field('<b>HD Space</b>', 'l');
			$table->add_field(VPS_SLICE_HD * $this->values['slices'] . ' GBytes', 'l');
			$table->add_row();
			$table->add_field('<b>Bandwidth</b>', 'l');
			$table->add_field(get_vps_bw_text($this->values['slices']), 'l');
			$table->add_row();

			$table->add_field('<b>Total Cost</b>', 'l');
			$table->add_field('<b>$' . number_format($this->total_cost, 2) . '<b>', 'l');
			$table->add_row();
			add_output(order_payment_methods_table_new($table, 2, $this->data, $this->returnURL, $this->total_cost, $this->checkout_items, $this->choice, $payment_method_table_fields, $this->values['period'], $this->repeat_service_cost, $this->module));
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
