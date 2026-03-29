---
name: crud-function-source
description: Creates a CRUD list page backed by a PHP function instead of a SQL query, using Crud::init('function_name', 'module', 'function'). Use when user says 'crud from function', 'non-SQL data source', 'function-backed list', 'API-backed list', or when the data comes from a computed/external source. Do NOT use when data comes from a direct SQL query — use the SQL-backed crud pattern instead.
---
# crud-function-source

## Critical

- The third argument to `Crud::init()` **must** be the string `'function'` — omitting it defaults to SQL mode and will break.
- The backing function **must** return a plain PHP array of associative arrays (rows). `CrudFunctionIterator` calls `call_user_func($name)` and iterates the result with `count()` / `array_keys()` — no objects, no generators.
- The backing function is loaded via `function_requirements($name)` — the function file must be discoverable by that loader (registered in the MyAdmin plugin/function map).
- The crud file must be placed in `src/crud/`, e.g. `src/crud/crud_renewals.php`. The PHP function inside must match the filename.

## Instructions

1. **Create the backing data function** in the appropriate plugin or functions directory. It must return `array` of associative arrays:
   ```php
   function get_<slug>() {
       // fetch / compute data
       return [
           ['col1' => 'val', 'col2' => 'val'],
           // ...
       ];
   }
   ```
   Verify `function_requirements('get_<slug>')` can locate this file before proceeding.

2. **Create the crud file** in `src/crud/`, e.g. `src/crud/crud_renewals.php`, using the exact boilerplate below (match the header comment block from all sibling files):
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
    * crud_<slug>()
    * @return void
    */
   function crud_<slug>()
   {
       Crud::init('get_<slug>', 'default', 'function')
           ->set_title(_('Page Title'))
           ->go();
   }
   ```
   Replace `<slug>`, the function name, and the title string. Module is `'default'` unless the data belongs to a specific module (e.g. `'vps'`, `'ssl'`).

3. **Add optional fluent modifiers** after `set_title()` and before `->go()` as needed:
   ```php
   ->disable_delete()->disable_edit()          // read-only list
   ->enable_fluid_container()                  // full-width layout
   ->set_labels(['col1' => _('Label 1'), ...]) // human-readable headers
   ->enable_labels()
   ->set_order('col1', 'asc')                  // default sort
   ->add_row_button('none.view_item&id=%id%', _('View'), 'primary', 'cog')
   ```

4. **Register the crud function** in `src/crud/cruds.php` (the dispatcher) so the AJAX handler can reach it. Verify the entry follows the same pattern as existing entries in that file.

5. **Test** by navigating to the page or calling `crud_<slug>()` directly. Confirm the table renders with correct column names from the returned array keys.

## Examples

**User says:** "Add a crud page that lists renewal data from `get_renewals()`"

**Actions taken:**
- Ensure `get_renewals()` exists and returns `array` of associative arrays.
- Create `src/crud/crud_renewals.php`:
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
   * crud_renewals()
   * @return void
   */
  function crud_renewals()
  {
      Crud::init('get_renewals', 'default', 'function')
          ->set_title(_('Renewals'))
          ->go();
  }
  ```
- Register `crud_renewals` in `src/crud/cruds.php`.

**Result:** A paginated, sortable, exportable table populated from `get_renewals()` output.

## Common Issues

- **Blank page / PHP fatal — "Call to undefined function get_\<slug\>"**: `function_requirements()` could not find the backing function. Verify the function file is registered in the function loader map and the filename matches what the loader expects.
- **"count(): Argument must be of type Countable|array"**: `get_<slug>()` returned `null` or a non-array. Ensure the function always returns `[]` on empty results, never `null` or `false`.
- **Columns show numeric keys (0, 1, 2…) instead of names**: The backing function returned indexed arrays instead of associative arrays. Use `array_combine($headers, $row)` or build each row as `['col' => $val]`.
- **Third `'function'` argument missing**: Without it, Crud treats the first argument as a SQL query and tries to parse `'get_renewals'` as SQL, producing a parser error. Always pass `'function'` as the third argument.
- **Data not updating**: `CrudFunctionIterator` calls the function fresh on each page load — no caching. If the backing function is slow, add caching inside `get_<slug>()` itself.
