---
name: crud-list-page
description: Creates a standard module service-list CRUD page using get_module_settings() with Active/Pending/Expired/All status filter buttons, header order button, and per-row view button. Use when adding a list page for a known module (e.g. 'add a list page', 'create a service list', 'new module list', adding a file to src/crud/ named crud_*_list.php). Do NOT use for raw-SQL pages with no module, admin-only data tables, or pages that don't follow the PREFIX/TABLE/TITLE settings pattern.
---
# crud-list-page

## Critical

- **Never** build the SQL without `{$settings['TABLE']}` and `{$settings['PREFIX']}` — always call `\get_module_settings($module)` first.
- **Always** call `->go()` as the final chain call — omitting it produces no output.
- **Always** include all four status filter buttons (Active, Pending, Expired, All) in that order.
- Files follow the naming convention `src/crud/crud_{module}_list.php`, e.g. `src/crud/crud_backups_list.php` for the `backups` module, and export exactly one function matching the filename.
- Wrap all user-visible strings in `_()`  for i18n — e.g. `_('List')`, `_('View')`.

## Instructions

1. **Determine the module slug** (e.g. `vps`, `webhosting`, `ssl`, `licenses`, `mail`).  
   Create the module list file in `src/crud/`, e.g. `src/crud/crud_backups_list.php` for the `backups` module. Verify no existing file conflicts before writing.

2. **Add the file header and import.**  
   ```php
   <?php
   /**
    * {Title} List
    * @author Joe Huss <detain@interserver.net>
    * @copyright 2025
    * @package MyAdmin
    * @category {Title}
    */
   use \MyCrud\Crud;
   ```

3. **Define the function and load settings.**  
   ```php
   function crud_{module}_list()
   {
       $module = '{module}';
       $settings = \get_module_settings($module);
       page_title(_($settings['TITLE']).' '._('List'));
   ```
   Verify `get_module_settings()` is available (it is a global helper — no import needed).

4. **Write the SELECT query** joining `repeat_invoices` for cost and `services` for package name.  
   Standard join pattern (adapt columns to what the module's table actually has):
   ```php
   Crud::init(
       "select {$settings['TABLE']}.{$settings['PREFIX']}_id,
        CONCAT(repeat_invoices_currency, ' ', repeat_invoices_cost) AS cost,
        {$settings['PREFIX']}_hostname, {$settings['PREFIX']}_status,
        services_name, {$settings['PREFIX']}_comment
        from {$settings['TABLE']}
        left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice
          and repeat_invoices_module='{$module}'
        left join services on services_id={$settings['TABLE']}.{$settings['PREFIX']}_type",
       $module
   )
   ```
   This step uses `$settings` from Step 3.

5. **Chain the standard configuration methods** in this exact order:
   ```php
       ->set_limit_custid_role('list_all')
       ->set_order($settings['PREFIX'].'_status', 'asc')
       ->set_title(_($settings['TITLE']).' '._('List'))
       ->enable_labels()
       ->set_labels([
           $settings['PREFIX'].'_id'       => _('ID'),
           $settings['PREFIX'].'_hostname' => _('Hostname'),
           'repeat_invoices_cost'          => _('Cost'),
           $settings['PREFIX'].'_status'   => _('Status'),
           'services_name'                 => _('Package'),
           $settings['PREFIX'].'_comment'  => _('Comments'),
       ])
   ```
   Adjust label keys to match only the columns actually selected in Step 4.

6. **Add the Order header button** (theme-aware):
   ```php
       ->add_header_button(
           $GLOBALS['tf']->default_theme == 'adminlte'
               ? $GLOBALS['tf']->link('index.php', 'choice=none.order_'.$settings['PREFIX'])
               : $GLOBALS['tf']->link('index.php', 'choice=none.buy_'.$settings['PREFIX']),
           _('Order'), 'primary', 'shopping-cart',
           _('Order').' '._($settings['TITLE']), 'client'
       )
   ```

7. **Add the four status filter buttons** (Active → Pending → Expired → All):
   ```php
       ->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], _('Active'), 'info active')
       ->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
       ->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], _('Expired'), 'info')
       ->add_title_search_button([], _('All'), 'info')
   ```

8. **Disable edit/delete, enable fluid container, add row view button, and close:**
   ```php
       ->disable_delete()
       ->disable_edit()
       ->enable_fluid_container()
       ->add_row_button(
           'none.view_'.$settings['PREFIX'].'&id=%id%',
           _('View').' '._($settings['TITLE']), 'primary', 'cog'
       )
       ->go();
   }
   ```
   Verify `->go()` is the last call with a semicolon after the closing `)`.

## Examples

**User says:** "Add a list page for the `backups` module."

**Actions taken:**
- Create `src/crud/crud_backups_list.php`
- `$module = 'backups'` → `$settings = \get_module_settings('backups')` provides `PREFIX=bu`, `TABLE=backups`, `TITLE=Backup`
- SELECT includes `bu_id`, `bu_hostname`, `bu_status`, `cost`, `services_name`, `bu_comment`
- All four status buttons added; row button links to `none.view_bu&id=%id%`

**Result:**
```php
<?php
/**
 * Backups List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Backups
 */
use \MyCrud\Crud;

function crud_backups_list()
{
    $module = 'backups';
    $settings = \get_module_settings($module);
    page_title(_($settings['TITLE']).' '._('List'));
    Crud::init("select {$settings['TABLE']}.{$settings['PREFIX']}_id, CONCAT(repeat_invoices_currency, ' ', repeat_invoices_cost) AS cost, {$settings['PREFIX']}_hostname, {$settings['PREFIX']}_status, services_name, {$settings['PREFIX']}_comment from {$settings['TABLE']} left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice and repeat_invoices_module='{$module}' left join services on services_id={$settings['TABLE']}.{$settings['PREFIX']}_type", $module)
        ->set_limit_custid_role('list_all')
        ->set_order($settings['PREFIX'].'_status', 'asc')
        ->set_title(_($settings['TITLE']).' '._('List'))
        ->enable_labels()
        ->set_labels([$settings['PREFIX'].'_id' => _('ID'), $settings['PREFIX'].'_hostname' => _('Hostname'), 'repeat_invoices_cost' => _('Cost'), $settings['PREFIX'].'_status' => _('Status'), 'services_name' => _('Package'), $settings['PREFIX'].'_comment' => _('Comments')])
        ->add_header_button($GLOBALS['tf']->default_theme == 'adminlte' ? $GLOBALS['tf']->link('index.php', 'choice=none.order_'.$settings['PREFIX']) : $GLOBALS['tf']->link('index.php', 'choice=none.buy_'.$settings['PREFIX']), _('Order'), 'primary', 'shopping-cart', _('Order').' '._($settings['TITLE']), 'client')
        ->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], _('Active'), 'info active')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], _('Expired'), 'info')
        ->add_title_search_button([], _('All'), 'info')
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        ->add_row_button('none.view_'.$settings['PREFIX'].'&id=%id%', _('View').' '._($settings['TITLE']), 'primary', 'cog')
        ->go();
}
```

## Common Issues

- **Blank page / no output:** `->go()` is missing or not last in the chain. Ensure the final chain call ends with `->go();`.
- **"Unknown column 'PREFIX_invoice'" SQL error:** The `repeat_invoices` join uses `{$settings['PREFIX']}_invoice` — verify the module's table actually has that column (`SHOW COLUMNS FROM {table}`). If not, remove the `repeat_invoices` join and the `cost` column.
- **All rows show for all customers:** `->set_limit_custid_role('list_all')` is missing. Add it immediately after `Crud::init(...)` opens.
- **Labels show raw column names:** `->enable_labels()` must appear before `->set_labels([...])` in the chain.
- **`get_module_settings()` returns null/undefined key:** The module slug passed to `Crud::init()` and `get_module_settings()` must be identical and match a registered plugin. Check `include/config/plugins.json` in the parent MyAdmin project.
- **Row button points to wrong route:** The `add_row_button` choice string must be `'none.view_{PREFIX}&id=%id%'` — use `$settings['PREFIX']`, not `$module`, as the suffix.
