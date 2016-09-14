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
			} else {
				$this->table = $table_or_query;
				$this->type = 'table';
				$this->tables[$this->table] = $this->get_table_details($this->table);
			}
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
			$queries = $parser->parse($query);
			//_debug_array($queries);
			add_output('<pre style="text-align: left;">' . print_r($queries, true) . '</pre>');
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
				$db->query("select count(*) from {$this->table}");
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

		public function validate_order() {
			$this->ensure_custid();
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

		public function confirm_order() {
			if ($this->ensure_custid() === false) {
				$this->error('No Client To Register Purchase With');
				return false;
			}
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

		public function check_payment() {
			$this->paid = false;
			if ($this->ensure_custid() === false) {
				$this->error('No Client To Register Purchase With');
				return;
			}
			$payment_method = '';
			if (isset($GLOBALS['tf']->variables->request['payza_token'])) {
				$payment_method = 'payza';
				$payza_token = $GLOBALS['tf']->variables->request['payza_token'];
				$defaultdb = clone $GLOBALS['tf']->db;
				$query = "select * from payza where apc_1='" . $defaultdb->real_escape($payza_token) . "' and ap_status='Success' and payza.order=0 and ap_totalamount='{$this->total_cost}'";
				$defaultdb->query($query, __LINE__, __FILE__);
				$waits = 5;
				if ($defaultdb->num_rows() == 0) {
					while ($waits > 0 && $defaultdb->num_rows() == 0) {
						sleep(1);
						--$waits;
						$defaultdb->query($query, __LINE__, __FILE__);
					}
				}
				if ($defaultdb->num_rows() > 0) {
					$defaultdb->next_record(MYSQL_ASSOC);
					$this->paid = true;
				} else {
					add_output("There was an error processing your Payza order, we never received the payment notification.");
				}
			}
			if (isset($GLOBALS['tf']->variables->request['pp_token']) && $GLOBALS['tf']->variables->request['pp_token'] != '' && isset($GLOBALS['tf']->variables->request['pp_payerid']) && $GLOBALS['tf']->
				variables->request['pp_payerid'] != '') {
				$payment_method = 'paypal';
				require_once (INCLUDE_ROOT.'/billing/paypal/paypal_checkout.functions.php');
				$token = $GLOBALS['tf']->variables->request['pp_token'];
				$payerID = $GLOBALS['tf']->variables->request['pp_payerid'];
				$res = unserialize(base64_decode($GLOBALS['tf']->session->appsession('pp_details')));
				$GLOBALS['tf']->session->delappsession('pp_details');
				$resArray = unserialize(base64_decode($GLOBALS['tf']->session->appsession('pp_confirm')));
				$GLOBALS['tf']->session->delappsession('pp_confirm');
				billingd_log(str_replace("\n", "", print_r($res, true)), __LINE__, __FILE__);
				billingd_log(str_replace("\n", "", print_r($resArray, true)), __LINE__, __FILE__);
				$finalPaymentAmount = $res["AMT"];
				$paymentType = 'Sale';
				$currencyCodeType = $res['CURRENCYCODE'];
				$ack = strtoupper($resArray["ACK"]);
				if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
					$transactionId = $resArray["PAYMENTINFO_0_TRANSACTIONID"]; // Unique transaction ID of the payment.
					$transactionType = $resArray["PAYMENTINFO_0_TRANSACTIONTYPE"]; // The type of transaction Possible values: l  cart l  express-checkout
					$paymentType = $resArray["PAYMENTINFO_0_PAYMENTTYPE"]; // Indicates whether the payment is instant or delayed. Possible values: l  none l  echeck l  instant
					$orderTime = $resArray["PAYMENTINFO_0_ORDERTIME"]; // Time/date stamp of payment
					$amt = $resArray["PAYMENTINFO_0_AMT"]; // The final amount charged, including any  taxes from your Merchant Profile.
					$currencyCode = $resArray["PAYMENTINFO_0_CURRENCYCODE"]; // A three-character currency code for one of the currencies listed in PayPay-Supported Transactional Currencies. Default: USD.
					$feeAmt = $resArray["PAYMENTINFO_0_FEEAMT"]; // PayPal fee amount charged for the transaction
					$taxAmt = $resArray["PAYMENTINFO_0_TAXAMT"]; // Tax charged on the transaction.
					$paymentStatus = $resArray["PAYMENTINFO_0_PAYMENTSTATUS"];
					if ($paymentStatus == 'Completed' && bcsub($amt, $this->total_cost, 2) >= 0) {
						$this->db = get_module_db($this->module);
						$penny_names = array();
						$penny_ids = array();
						$this->db->query("select * from coupons where module='{$this->module}' and onetime=1 and amount=0.01 and type=3 and usable=1 and name != ''");
						if ($this->db->num_rows() > 0) {
							while ($this->db->next_record(MYSQL_ASSOC)) {
								$penny_names[] = strtolower($this->db->Record['name']);
								$penny_ids[] = $this->db->Record['id'];
							}
						}
						if (in_array(strtolower($this->coupon), $penny_names)) {
							$defaultdb = clone $GLOBALS['tf']->db;
							$defaultdb->query("select * from paypal where (payer_id='" . $defaultdb->real_escape($res['PAYERID']) . "' or payer_email='" . $defaultdb->real_escape($res['EMAIL']) . "') and txn_id != '" . $transactionId .
								"'");
							if ($defaultdb->num_rows() == 0) {
								$this->paid = true;
							} else {
								$defaultdb->next_record(MYSQL_ASSOC);
								$GLOBALS['tf']->history->add('paypal', 'coupon_hacker', $transactionId, $defaultdb->Record['txn_id']);
								//billingd_log("Tried using $this->coupon When already have $res[EMAIL] $transactionId " . $defaultdb->Record['txn_id'], __LINE__, __FILE__);
								add_output('The coupon is only usable by new clients.   This paypal has been used to pay already.');
								$this->continue = false;
							}

						} else {
							$this->paid = true;
						}
					} else {
						add_output('The PayPal amount does not seem to match the required amount.  Please contact support@interserver.net');
					}
					$pendingReason = $resArray["PAYMENTINFO_0_PENDINGREASON"];
					$reasonCode = $resArray["PAYMENTINFO_0_REASONCODE"];

				} else {
					$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
					$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
					$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
					$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
					billingd_log("DoExpressCheckoutDetails API call failed. ", __LINE__, __FILE__);
					billingd_log("Detailed Error Message: " . $ErrorLongMsg, __LINE__, __FILE__);
					billingd_log("Short Error Message: " . $ErrorShortMsg, __LINE__, __FILE__);
					billingd_log("Error Code: " . $ErrorCode, __LINE__, __FILE__);
					billingd_log("Error Severity Code: " . $ErrorSeverityCode, __LINE__, __FILE__);
					add_output('There was an error with the PayPal transaction.   Please contact support@interserver.net about this.');
					$this->continue = false;
				}
			}
			if ($this->continue == true) {
				$server = 0;
				$comment = '';
				if ($GLOBALS['tf']->ima == 'admin' && isset($this->set_vars['server']) && is_numeric($this->set_vars['server'])) {
					$server = intval($this->set_vars['server']);
				}
				if ($GLOBALS['tf']->ima == 'admin' && isset($this->set_vars['comment'])) {
					$comment = $this->set_vars['comment'];
				}
				function_requirements('place_buy_vps');
				if ($this->debug == true) {
					echo "Wanted to call place_buy_vps({$this->coupon_code}, {$this->service_cost}, {$this->slice_cost}, {$this->service_type}, {$this->repeat_slice_cost}, {$this->original_slice_cost}, {$this->original_cost}, {$this->repeat_service_cost}, {$this->monthly_service_cost}, {$this->custid}, {$this->values['template']}, {$this->values['slices']}, {$this->values['platform']}, {$this->values['controlpanel']}, {$this->values['period']}, {$this->values['location']}, {$this->values['base_os']}, {$this->values['hostname']}, {$this->coupon}, {$this->values['rootpass']}, {$server}, {$comment}); <br>";
				}
				list($this->total_cost, $this->iid, $this->iids, $this->real_iids, $this->service_id, $invoice_description, $this->cj_params) = place_buy_vps(
					$this->coupon_code, $this->service_cost, $this->slice_cost, $this->service_type, $this->repeat_slice_cost, $this->original_slice_cost,
					$this->original_cost, $this->repeat_service_cost, $this->monthly_service_cost, $this->custid, $this->values['template'], $this->values['slices'], $this->values['platform'], $this->values['controlpanel'], $this->values['period'], $this->values['location'], $this->values['base_os'], $this->values['hostname'], $this->coupon, $this->values['rootpass'], $server, $comment);
				$this->db = get_module_db($this->module);
				$this->db->query("delete from pending_orders where pend_custid={$this->custid}", __LINE__, __FILE__);
				if ($GLOBALS['tf']->ima == 'admin' && isset($this->set_vars['paid'])) {
					if ($this->set_vars['paid'] == 'yes') {
						$this->paid = true;
						mark_invoice_paid($this->iid, 10, $this->module);
					} else {
						$this->paid = false;
					}
				}

				if ($this->total_cost == 0) {
					if (mark_invoice_paid($this->iid, 10, $this->module)) {
						$this->paid = true;
					}
				} elseif ($payment_method == 'payza' && $this->paid === true) {
					$defaultdb->query("update payza set module='{$this->module}',payza.order='{$this->service_id}',custid='{$this->custid}' where apc_1='" . $defaultdb->real_escape($payza_token) . "'", __LINE__, __FILE__);
					handle_payment($this->custid, $this->total_cost, $this->real_iids, 14, $this->module, $payza_token);
				} elseif ($payment_method == 'paypal' && $this->paid === true) {
					handle_payment($this->custid, $this->total_cost, $this->real_iids, 10, $this->module, $resArray['PAYMENTINFO_0_TRANSACTIONID']);
				} elseif ($this->total_cost >= 0.10 && $this->total_cost <= get_prepay_related_amount(array(), $this->module, $this->custid)) {
					billingd_log("Calilng Handle Payment", __LINE__, __FILE__);
					handle_payment($this->custid, $this->total_cost, $this->real_iids, 12, $this->module);
					use_prepay_related_amount($this->real_iids, $this->module, $this->total_cost, $this->custid);
					$this->paid = true;
				} elseif ($this->paid == false && can_use_cc($this->data)) {
					if ($GLOBALS['tf']->ima != 'admin') {
						if (charge_card($this->custid, $this->total_cost, $this->real_iids, $this->module)) {
							$payment_method = 'cc';
							$this->paid = true;
						}
					}
				}
				if ($this->paid == true) {
					if (in_array($this->values['platform'], array('kvm', 'cloudkvm'))) {
						//$db = get_module_db($this->module);
						$this->db->query(make_insert_query('queue_log', array(
							'history_id' => null,
							'history_sid' => $GLOBALS['tf']->session->sessionid,
							'history_timestamp' => mysql_now(),
							'history_creator' => $this->custid,
							'history_owner' => $this->custid,
							'history_section' => $module . 'queue',
							'history_type' => $this->service_id,
							'history_new_value' => 'setup_vnc',
							'history_old_value' => $GLOBALS['tf']->session->getuser_ip())), __LINE__, __FILE__);
					}
					$GLOBALS['tf']->session->appsession($this->module . 'cj_params_' . $this->service_id, base64_encode(serialize($this->cj_params)));
					$GLOBALS['tf']->session->appsession($this->module . 'total_cost_' . $this->service_id, $this->total_cost);
					$GLOBALS['tf']->session->appsession($this->module . 'payment_method_' . $this->service_id, $payment_method);
					$GLOBALS['tf']->redirect($GLOBALS['tf']->link('index.php', 'choice=none.order_completed&utm_nooverride=1&module=' . $this->module . '&order=' . $this->service_id));
				} else {
					$table = new TFTable;
					$table->set_title($this->settings['TBLNAME'] . ' Order');
					$table->set_options('style="max-width: 500px;"');
					$table->set_colspan(2);
					$table->add_field('	Thank you for your order of a ' . $this->settings['TBLNAME'] . '. Your ' . $this->settings['TBLNAME'] . ' is not yet active - ' . 'to complete activation of the ' . $this->settings['TBLNAME'] . ' please send a complete payment of US $' . $this->total_cost . ' through paypal or ' . $table->make_link('choice=update_info', 'login here to setup Credit Card billing') . ' When payment received, your ' . $this->settings['TBLNAME'] . ' will be immediately activated. An email with the paypal ' . 'payment link has been sent as well as included below.<br><br><br><br>Thank you for your order of a ' . $this->settings['TBLNAME'] . '.', 'l');
					$table->add_row();
					if ($GLOBALS['tf']->accounts->data['payment_method'] == 'cc') {
						$table->add_field(get_paypal_link(implode(',', $this->iids), $this->total_cost, $invoice_description, '<img src="' . URL_ROOT . '/images/icons/creditcard_paypal.png" border="0" style="float: none;"><br>Pay via PayPal'));
						$table->add_field($table->make_link('choice=none.pay_balance&amp;module=' . $this->module, '<img src="' . URL_ROOT . '/images/icons/creditcard_visa.png" border="0" style="float: none;"><br>Pay via Credit Card'));
						$table->add_row();
					} else {
						$table->set_colspan(2);
						$table->add_field(get_paypal_link(implode(',', $this->iids), $this->total_cost, $invoice_description, '<img src="' . URL_ROOT . '/images/icons/creditcard_paypal.png" border="0" style="float: none;"><br>Pay via PayPal'));
						$table->add_row();
					}
					add_output($table->get_table());
					$smarty = new TFSmarty;
					$smarty->assign("service_cost", number_format($this->total_cost, 2));
					$smarty->assign("paypal_link", get_paypal_link(implode(',', $this->iids), $this->total_cost, $invoice_description));
					$platformtext = $this->values['platform'];
					$extra = '';
					foreach ($this->fields as $idx => $field) {
						$extra .= $this->label($field) . ': ';
						$value = $this->values[$field];
						if (isset($this->input_types[$field])) {
							$input_type = $this->input_types[$field][0];
							$data = $this->input_types[$field][1];
							switch ($input_type) {
								case 'select':
									$value = $this->input_types[$field][1]['labels'][array_search($this->values[$field], $this->input_types[$field][1]['values'])];
									break;
							}
						}
						$extra .= $value . "<br>\n";
					}
					$extra .= "<br>";
					$smarty->assign('extra', $extra);
					$smarty->assign("settings", $this->settings);
					$msg = $smarty->fetch('email/client_email_service_paytoactivate.tpl');
					$headers = '';
					$headers .= "MIME-Version: 1.0" . EMAIL_NEWLINE;
					$headers .= "Content-Type: text/html; charset=iso-8859-1" . EMAIL_NEWLINE;
					$headers .= "From: " . EMAIL_FROM . EMAIL_NEWLINE;
					//					$headers .= "To: " . (isset($this->data['email']) && $this->data['email'] != '' ? $this->data['email'] : $this->data['account_lid']) . " <" . (isset($this->data['email']) && $this->data['email'] != '' ? $this->data['email'] : $this->data['account_lid']) . ">" . EMAIL_NEWLINE;
					$headers .= "X-Priority: 1" . EMAIL_NEWLINE;
					$headers .= "X-MSMail-Priority: High" . EMAIL_NEWLINE;
					$subject = 'New Pending ' . $this->settings['TBLNAME'] . ' ' . $this->values['hostname'];
					multi_mail((isset($this->data['email']) && $this->data['email'] != '' ? $this->data['email'] : $this->data['account_lid']), $subject, $msg, $headers, 'client_email_service_paytoactivate.tpl');
					$smarty = new TFSmarty;
					$smarty->assign("module", $this->module);
					$smarty->assign("total", $this->total_cost);
					$smarty->assign("orderid", $this->service_id);
					add_output($smarty->fetch('analytics/google.tpl'));
				}
			}
		}

		public function order_form() {
			$this->ensure_custid();
			if ($this->stage == 2) {
				$table = new TFTable;
				$table->hide_table();
				$table->set_options('style=" background-color: #DFEFFF; border: 1px solid #C2D7EF;border-radius: 10px; padding-right: 10px; padding-left: 10px;"');
				//$table->set_options('width="500" cellpadding=5');
				$table->set_form_options('id="orderform" onsubmit="document.getElementsByName(' . "'confirm'" . ')[0].disabled = true; return true;"');
				$table->set_title($this->title);
				if ($GLOBALS['tf']->ima == 'admin') {
					$table->add_hidden('custid', $this->custid);
				}
				$table->csrf('crud_order_form');
				$table->add_hidden('module', $this->module);
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
				$table->add_field('<b>CPU Cores</b>', 'l');
				$table->add_field(ceil($this->values['slices'] / 4), 'l');
				$table->add_field('<b>Memory</b>', 'l');
				$table->add_field(VPS_SLICE_RAM * $this->values['slices'] . ' MB Ram', 'l');
				$table->add_row();
				$table->add_field('<b>HD Space</b>', 'l');
				$table->add_field(VPS_SLICE_HD * $this->values['slices'] . ' GBytes', 'l');
				$table->add_field('<b>Bandwidth</b>', 'l');
				$table->add_field(get_vps_bw_text($this->values['slices']), 'l');
				$table->add_row();
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
				if ($GLOBALS['tf']->ima == 'admin') {
					$table->add_hidden('custid', $this->custid);
				}
				$table->add_hidden('module', $this->module);
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
				if ($this->use_period === true) {
					$table->set_colspan($this->columns - 1);
					$table->set_row_options('style="display: none;padding: 5px;" id="cyclediscountrow"');
					$table->set_col_options('style="padding: 5px;"');
					$table->add_field('<h3>Cycle Discount:</h3>', $this->price_text_align);
					$table->add_field('<h3 id="cyclediscount"></h3>', $this->price_align);
					$table->add_row();
				}
				if ($this->use_coupon === true) {
					$table->set_colspan($this->columns - 1);
					$table->set_row_options('style="display:none; padding: 5px;" id="couponpricerow"');
					$table->set_col_options('style="padding: 5px;"');
					$table->add_field('<h3 id="couponpricetext">Coupon Discount:</h3>', $this->price_text_align);
					$table->add_field('<h3 id="couponprice">$0</h3>', $this->price_align);
					$table->add_row();
				}
				$table->set_row_options('style="display:none; padding: 5px;" id="ssdpricerow"');
				$table->set_col_options('style="padding: 5px;"');
				$table->set_colspan($this->columns - 1);
				$table->add_field('<h3 id="ssdpricetext">SSD Drive:</h3>', $this->price_text_align);
				$table->add_field('<h3 id="ssdprice">$' . (VPS_SLICE_SSD_OVZ_COST - VPS_SLICE_OVZ_COST) . ' per slice</h3>', $this->price_align);
				$table->add_row();
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
				$smarty = new TFSmarty;
				$smarty->assign('module', $this->module);
				$smarty->assign('use_size', $this->use_size);
				if ($this->use_size == true) {
					$smarty->assign('size_field', $this->size_field);
				}
				$smarty->assign('use_service_select', $this->use_service_select);
				if ($this->use_service_select) {
					$smarty->assign('service_type_field', $this->service_select_field);
				} else {
					$smarty->assign('service_type_function', $this->service_select_function);
				}
				$service_prices = array();
				foreach ($this->service_types as $service_type => $service_data)
					$service_prices[$service_type] = $service_data['services_cost'];
				$smarty->assign('service_prices', json_encode($service_prices));
				$GLOBALS['tf']->add_html_head_js($smarty->fetch('buy_service.js.tpl'));
				$GLOBALS['tf']->add_html_head_css('<link rel=stylesheet href="templates/buy_service.css" type="text/css">');
			}
		}
	}
?>
