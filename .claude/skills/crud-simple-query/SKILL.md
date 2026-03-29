---
name: crud-simple-query
description: Creates a Crud::init() page backed by a raw SQL query with title, optional labels, sort order, ACL guard, and row actions. Use when user says 'add a crud page', 'create a table view', 'new query page', or needs a SQL-backed list. Do NOT use when a module service list with get_module_settings() is more appropriate — use the crud-module-list skill instead.
---
# crud-simple-query

## Critical

- **Never** interpolate raw `$_GET`/`$_POST` into the SQL string — use `$db->real_escape()` first.
- Every new file **must** be placed in `src/crud/`, e.g. `src/crud/crud_traffic_log.php`, and contain exactly one top-level function named after the file.
- Always end the fluent chain with `->go();` — omitting it produces no output.
- Wrap the function body with an ACL guard (`has_acl`) whenever the page is admin-only (see Step 4).
- Use `_('...')` for all user-visible strings (gettext i18n).

## Instructions

1. **Create the file** in `src/crud/`, e.g. `src/crud/crud_traffic_log.php` for a `crud_traffic_log` function.
   Add the standard header, the `use` statement, and the function skeleton:
   ```php
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
    * crud_<name>()
    * @return void
    */
   function crud_<name>()
   {
       // body here
   }
   ```
   Verify the file does not already exist before creating it.

2. **Write the SQL** as the first argument to `Crud::init()`. Select only the columns you need; alias with `AS` where the raw column name would be confusing. The second argument is the module string (omit if not module-scoped):
   ```php
   Crud::init("SELECT col_a, col_b, DATE_FORMAT(ts, '%Y-%m-%d') AS date FROM my_table WHERE active = 1")
   ```

3. **Chain the required methods** in this order:
   - `->set_title(_('Human Title'))` — always required.
   - `->set_order('column', 'asc'|'desc')` — set a sensible default sort.
   - `->set_limit_custid_role('list_all')` — restricts rows to the current customer unless the viewer has the `list_all` role. Add this whenever the table has a custid column.
   - `->enable_fluid_container()` — use for wide tables (more than ~5 columns).

4. **Add an ACL guard** if the page is admin-only (place it before `Crud::init`):
   ```php
   function_requirements('has_acl');
   if ($GLOBALS['tf']->ima != 'admin' || !has_acl('required_acl_key')) {
       dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
       return false;
   }
   ```
   Verify `required_acl_key` exists in the ACL table before using it.

5. **Disable editing/deleting** for read-only views:
   ```php
   ->disable_delete()->disable_edit()
   ```

6. **Add labels** when column names are not self-explanatory:
   ```php
   ->enable_labels()
   ->set_labels(['col_a' => _('Column A'), 'col_b' => _('Column B')])
   ```

7. **Add row buttons** to link to detail pages (use `%id%` as the primary-key placeholder):
   ```php
   ->add_row_button('none.view_item&id=%id%', _('View'), 'primary', 'cog')
   ```

8. **Add search filter buttons** (optional, placed after `set_title`):
   ```php
   ->add_title_search_button([['status', '=', 'active']], _('Active'), 'info active')
   ->add_title_search_button([], _('All'), 'info')
   ```

9. **Set page limit** when the table is large:
   ```php
   ->set_page_limit(25)   // default is 50
   ```

10. **Close** the chain with `->go();`.

## Examples

**User says:** "Add a crud page that shows the traffic_log table, admin-only, with a View button per row."

**Result** (`src/crud/crud_traffic_log.php`):
```php
<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

function crud_traffic_log()
{
    page_title(_('Traffic Log'));
    function_requirements('has_acl');
    if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    Crud::init("select id, timestamp, duration, method, status, custid, sessionid, client_ip, substring(uri, 1, 75) as uri from traffic_log")
        ->set_title(_('Traffic Log'))
        ->enable_labels()
        ->set_order('id', 'desc')
        ->disable_delete()
        ->disable_edit()
        ->add_row_button('none.traffic_log&id=%id%', _('View'), 'primary', 'cog')
        ->enable_fluid_container()
        ->set_page_limit(25)
        ->go();
}
```

**User says:** "Quick read-only page for session_log, no ACL needed."
```php
function crud_session_log()
{
    Crud::init('select * from session_log')
        ->set_limit_custid_role('list_all')
        ->set_title(_('Session Log'))
        ->go();
}
```

## Common Issues

- **Blank page / no table rendered:** `->go()` is missing. Add it as the final call in the chain.
- **All customers' rows visible to a regular user:** `->set_limit_custid_role('list_all')` is missing. Add it when the table has a custid column.
- **`Call to undefined function has_acl()`:** Add `function_requirements('has_acl');` before calling `has_acl()`.
- **HTML tags appear escaped in cells:** call `->set_use_html_filtering(false)` when the SQL produces HTML (image tags, links, etc.).
- **`->add_row_button()` shows literal `%id%`:** The primary key column must be included in the SELECT. Add it to the query.
- **ACL key not found / access always denied:** Verify the key exists with `SELECT acl_key FROM acl_keys WHERE acl_key = 'your_key'` before wiring it up.
