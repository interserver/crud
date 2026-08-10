# detain/crud — Advanced CRUD Class

## Commands

```bash
composer install                          # install deps
vendor/bin/phpunit tests/ -v   # run tests
phpdbg -qrr vendor/bin/phpunit tests/ -v --coverage-clover coverage.xml --whitelist src/   # coverage
bash test_other_sql_parsers.sh            # test alternative SQL parsers
php bin/crud/make_cruds.php              # generate crud files from JSON form definitions
```

## Architecture

- **Namespace**: `MyCrud\` → `src/` (PSR-4 via `composer.json`)
- **Core**: `src/Crud.php` · `src/CrudFunctionIterator.php`
- **AJAX handler**: `src/ajax/crud.php` — dispatches to named crud functions
- **Crud pages**: `src/crud/crud_*.php` — one function per file, 60+ examples
- **Exports**: `src/Export/` — `array2Csv.php` · `array2Xlsx.php` · `array2Xls.php` · `array2Ods.php` · `array2Pdf.php` · `array2Xml.php` · `phpExcellCommon.php` (PhpSpreadsheet)
- **Templates**: `public_html/templates/crud/` — `tableAdminLte.tpl` · `tableAdminLteMaterial.tpl` · `table.tpl` · `table1-5.tpl`
- **JS**: `public_html/js/crud.js` — pagination, sort, search, export, AJAX `crud_load_page()`
- **CSS**: `public_html/css/crud_table5.css`
- **Generator**: `bin/crud/make_cruds.php` — reads JSON form defs, writes `src/crud/crud_*.php`
- **DB**: `detain/db_abstraction` · SQL parser: `crodas/sql-parser`
- **IDE**: `.idea/` — PhpStorm project config including `crud.iml`, `deployment.xml`, and `inspectionProfiles/`

## Fluent API Patterns

### Basic SQL query
```php
use \MyCrud\Crud;
Crud::init('select * from table_name', 'module')
    ->set_title(_('Page Title'))
    ->set_order('field', 'asc')
    ->disable_delete()->disable_edit()
    ->go();
```

### Module service list (standard pattern in `src/crud/crud_*_list.php`)
```php
$module = 'vps';
$settings = \get_module_settings($module);  // returns PREFIX, TABLE, TBLNAME, TITLE
Crud::init("select {$settings['TABLE']}.{$settings['PREFIX']}_id, ... from {$settings['TABLE']} left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice", $module)
    ->set_limit_custid_role('list_all')
    ->set_order($settings['PREFIX'].'_status', 'asc')
    ->set_title(_($settings['TITLE']).' '._('List'))
    ->enable_labels()
    ->set_labels([$settings['PREFIX'].'_id' => _('ID'), ...])
    ->add_header_button($GLOBALS['tf']->link('index.php', 'choice=none.order_'.$settings['PREFIX']), _('Order'), 'primary', 'shopping-cart', ...)
    ->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], _('Active'), 'info active')
    ->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
    ->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], _('Expired'), 'info')
    ->add_title_search_button([], _('All'), 'info')
    ->disable_delete()->disable_edit()->enable_fluid_container()
    ->add_row_button('none.view_'.$settings['PREFIX'].'&id=%id%', _('View').' '._($settings['TITLE']), 'primary', 'cog')
    ->go();
```

### Function-backed data source
```php
Crud::init('my_function_name', 'module', 'function')
    ->set_title(_('Title'))
    ->go();
```

## Conventions

- One crud function per file in `src/crud/`, named `crud_<name>()`, file named `crud_<name>.php`
- Always `use \MyCrud\Crud;` at top of each crud file
- Admin guard pattern: `if ($GLOBALS['tf']->ima != 'admin' || !has_acl('acl_key')) { dialog(...); return false; }`
- Use `$db->real_escape()` on any user input before SQL interpolation
- Exports via `src/Export/phpExcellCommon.php` using PhpSpreadsheet — supports Xlsx, Xls, Ods, Pdf
- Templates selected by `$GLOBALS['tf']->default_theme` — `'adminlte'` uses `tableAdminLte.tpl`
- i18n: wrap strings in `_()`; `page_title()` for page heading
- `->set_return_output(true)` + capture `->go()` return when embedding in widget context
- `->add_filter($field, $callable, 'function')` for per-cell value decoration
- `CrudFunctionIterator` in `src/CrudFunctionIterator.php` wraps PHP functions as DB-like result sets
- `binary(16)` columns named `*_uuid` are auto-detected by `parse_tables()` (tracked in `$uuid_fields`), validated/stored via `uuid_to_bin()`/`UUID_TO_BIN()`, and converted back to hyphenated strings on read via `convert_uuid_fields()`/`bin_to_uuid()`

## Tests

- `tests/CrudTest.php` — PHPUnit
- Coverage whitelist: `src/`

<!-- caliber:managed:pre-commit -->
## Before Committing

Run `caliber refresh` before creating git commits to keep docs in sync with code changes.
After it completes, stage any modified doc files before committing:

```bash
caliber refresh && git add CLAUDE.md .claude/ .cursor/ .github/copilot-instructions.md AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null
```
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:model-config -->
## Model Configuration

Recommended default: `claude-sonnet-4-6` with high effort (stronger reasoning; higher cost and latency than smaller models).
Smaller/faster models trade quality for speed and cost — pick what fits the task.
Pin your choice (`/model` in Claude Code, or `CALIBER_MODEL` when using Caliber with an API provider) so upstream default changes do not silently change behavior.

<!-- /caliber:managed:model-config -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
