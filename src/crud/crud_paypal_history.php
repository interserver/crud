<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_paypal_history()
 * @return void
 */
function crud_paypal_history()
{
	Crud::init('SELECT accounts.account_id AS owner, paypal.txn_id AS txn_id, paypal.payment_status AS payment_status, paypal.`when` AS history_timestamp, paypal.payer_email AS payer_email, paypal.payment_gross AS payment_gross FROM paypal INNER JOIN accounts ON paypal.lid = accounts.account_lid')
		->set_title(_('Paypal History'))
		->set_labels(['txn_id' => 'Transaction ID', 'payment_status' => 'Status', 'history_timestamp' => 'When', 'payer_email' => 'Email', 'payment_gross' => 'Amount'])
		->set_order('history_timestamp', 'desc')
		->go();
}
