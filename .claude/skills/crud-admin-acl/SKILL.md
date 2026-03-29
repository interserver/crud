---
name: crud-admin-acl
description: Creates admin-only CRUD pages with proper ACL guard boilerplate (function_requirements('has_acl'), $GLOBALS['tf']->ima check, dialog() error, return false). Use when user says 'admin only page', 'add ACL check', 'restrict to admins', or creates any crud_*.php page visible only to admins. Do NOT use for pages that use set_limit_custid_role() for customer-scoped access.
---
# crud-admin-acl

## Critical

- **Always call `function_requirements('has_acl')` before the `if` check** — `has_acl()` is lazy-loaded and will fatal if called without this.
- **Always `return false` after `dialog()`** — never let execution fall through to `Crud::init()`.
- **Never skip `dialog()`** when the function is called from a top-level page (only omit it when the function accepts a `$custid` param and is embedded, as in `crud_cc_log`).
- The ACL name passed to `has_acl()` must be a valid permission string (e.g. `'client_billing'`, `'admins_control'`). If unsure, use `'admins_control'` for general admin pages.

## Instructions

1. **Create the file** in `src/crud/`, e.g. `src/crud/crud_query_log.php` for a `crud_query_log` function, with the standard header:
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
   ```
   Verify the file does not already exist before writing.

2. **Declare the function** matching the filename:
   ```php
   function crud_<name>()
   {
       page_title(_('Page Title'));
   ```
   `page_title()` is optional but recommended for standalone pages.

3. **Insert the ACL guard block** immediately after `page_title()` (or as the first statement if no title):
   ```php
       function_requirements('has_acl');
       if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
           dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
           return false;
       }
   ```
   - Replace `'client_billing'` with the appropriate ACL string for the page.
   - For admin-only with no specific ACL: `if ($GLOBALS['tf']->ima != 'admin') {`

4. **Add the `Crud::init()` chain** after the guard:
   ```php
       Crud::init("select ... from table_name")
           ->set_title(_('Page Title'))
           ->set_order('id', 'desc')
           ->disable_delete()
           ->disable_edit()
           ->enable_fluid_container()
           ->go();
   }
   ```
   Verify `->go()` is the final call and the function closes with `}`.

## Examples

**User says:** "Create an admin-only CRUD page for the `query_log` table, restricted to billing admins."

**File created:** `src/crud/crud_query_log.php`

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
 * crud_query_log()
 * @return void
 */
function crud_query_log()
{
    page_title(_('Query Log'));
    function_requirements('has_acl');
    if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    Crud::init('select id, timestamp, custid, query, duration from query_log')
        ->set_title(_('Query Log'))
        ->set_order('id', 'desc')
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        ->set_page_limit(25)
        ->go();
}
```

**User says:** "Add an admin-only guard to an existing crud function."

Insert after any `page_title()` call and before `Crud::init()`:
```php
    function_requirements('has_acl');
    if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
```

## Common Issues

- **`Fatal error: Call to undefined function has_acl()`** — `function_requirements('has_acl')` is missing or placed after the `if` check. Move it to immediately before the `if`.
- **Page renders for non-admins** — `return false` is missing after `dialog()`. The guard block must have all three lines: `function_requirements`, `if` check, and `return false`.
- **`dialog()` shows but page also renders** — execution is not returning. Confirm `return false` is inside the `if` block, not after it.
- **Wrong ACL name causes all admins to be blocked** — verify the ACL string matches an entry in the permissions table. Use `'admins_control'` as the safe fallback for general admin pages; `'client_billing'` for billing-related data.
