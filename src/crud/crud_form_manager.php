<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_form_manager()
 * @return void
 */
function crud_form_manager()
{
    Crud::init('select * from forms')
        ->set_title(_('Form Manager'))
        ->go();
}
