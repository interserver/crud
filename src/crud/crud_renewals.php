<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_renewals()
 * @return false|void
 */
function crud_renewals()
{
    // admin only: this lists every customer's rows (the table has no *_custid column for
    // limit_custid to filter on) and index.php/ajax.php reach any registered function
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    Crud::init('get_renewals', 'default', 'function')
        ->set_title(_('Renewals'))
        // every column core's get_renewals() (include/admin/invoicing/view_renewals.php)
        // returns is the service link it builds from App::link() and the service id/uuid,
        // around a value it escapes (the hostname and the login are customer text). shown as
        // html on the page and after an ajax refill; with filtering on and no raw fields the
        // links printed as escaped markup
        ->set_raw_html_fields(['id', 'type', 'field', 'cost', 'status', 'ordered', 'email', 'payments', 'last_payment'])
        ->go();
}
