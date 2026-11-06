<?php

use PHPUnit\Framework\TestCase;

/**
 * Every crud page in src/crud/ that lists explicit columns of an sql query must keep
 * the ajax edit handler off unless it is reviewed below.
 *
 * Until the edit guard landed, Crud::ajax_edit_handler() fataled (array_merge with a
 * null query_where entry) for any query whose written table was not the first member
 * of a join condition, so most explicit-column cruds never had a working edit. The
 * handler now falls back to [] for that lookup, which would quietly turn each of them
 * into a raw admin editor of its table (crud_customers rewrote accounts.account_lid,
 * and a modal save wrote the order count into account_status). They are list only:
 * nothing that ever worked is removed.
 *
 * Not affected, and so not required to disable edit: 'select *' queries and bare table
 * names (no query_fields, the handler has nothing to write) and function backed cruds.
 */
class CrudEditSweepTest extends TestCase
{
    /**
     * explicit-column cruds whose edit is reviewed and kept on (admin only, csrf).
     * crud => why
     */
    const REVIEWED_EDIT = [
        // qualified joins give swtickets a query_where entry, so staff edits worked before the guard
        'crud_admin_tickets' => 'admin, swtickets edit worked before',
        'crud_admin_tickets_widget' => 'admin, swtickets edit worked before',
        // Crud::init($table, $module) with an admin-chosen bare table name: no query_fields
        'crud_table' => 'bare table name, nothing writable',
    ];

    /**
     * @return array<string, array{0:string, 1:int}> "file#n" => [file, index of the init() call]
     */
    public function initCalls(): array
    {
        $out = [];
        foreach (glob(__DIR__.'/../src/crud/*.php') as $file) {
            $toks = token_get_all((string) file_get_contents($file));
            $n = 0;
            foreach ($toks as $i => $t) {
                if (is_array($t) && $t[1] === 'init' && isset($toks[$i - 1]) && is_array($toks[$i - 1]) && $toks[$i - 1][0] === T_DOUBLE_COLON) {
                    $out[basename($file).'#'.(++$n)] = [$file, $i];
                }
            }
        }
        return $out;
    }

    /**
     * @dataProvider initCalls
     */
    public function testExplicitColumnCrudKeepsEditOffUnlessReviewed(string $file, int $at): void
    {
        $name = basename($file, '.php');
        if (isset(self::REVIEWED_EDIT[$name])) {
            $this->assertTrue(true);
            return;
        }
        $toks = token_get_all((string) file_get_contents($file));
        [$first, $args] = $this->args($toks, $at + 1);
        $isFunction = isset($args[2]) && trim($args[2], " \t\n'\"") === 'function';
        $isLiteral = $first !== null;
        $sql = $isLiteral ? trim($first) : '';
        $nothingWritable = $isFunction
            || ($isLiteral && !preg_match('/\s/', $sql))
            || ($isLiteral && preg_match('/^select\s+\*\s+from\s/i', $sql));
        if ($nothingWritable) {
            $this->assertTrue(true);
            return;
        }
        $this->assertTrue(
            $this->editDisabledUnconditionally($toks, $at),
            "{$name}: an explicit-column crud must call ->disable_edit() on every path (or be reviewed in REVIEWED_EDIT)"
        );
    }

    public function testCrudCustomersIsListOnly(): void
    {
        $src = (string) file_get_contents(__DIR__.'/../src/crud/crud_customers.php');
        $this->assertStringContainsString('->disable_edit()', $src);
    }

    /**
     * the literal sql of init()'s first argument (null when it is not a plain string,
     * interpolation counts as literal text) and the raw text of every argument
     *
     * @return array{0:?string, 1:string[]}
     */
    private function args(array $toks, int $open): array
    {
        $depth = 0;
        $args = [''];
        $first = '';
        $literal = true;
        for ($j = $open + 1, $n = count($toks); $j < $n; $j++) {
            $t = $toks[$j];
            $s = is_array($t) ? $t[1] : $t;
            if ($s === '(' || $s === '[' || $s === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                $depth++;
            } elseif ($s === ')' || $s === ']' || $s === '}') {
                if ($depth === 0) {
                    break;
                }
                $depth--;
            } elseif ($s === ',' && $depth === 0) {
                $args[] = '';
                continue;
            }
            $args[count($args) - 1] .= $s;
            if (count($args) === 1) {
                if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
                    $first .= stripcslashes(substr($s, 1, -1));
                } elseif (is_array($t) && $t[0] === T_ENCAPSED_AND_WHITESPACE) {
                    $first .= $s;
                } elseif ((is_array($t) && $t[0] === T_WHITESPACE) || $s === '.' || $s === '"') {
                    // concatenation / quotes
                } elseif (is_array($t) && $t[0] === T_VARIABLE && trim($first) === '' && $depth === 0) {
                    $literal = false;
                } else {
                    $first .= 'x';
                }
            }
        }
        return [$literal ? $first : null, $args];
    }

    /**
     * true when ->disable_edit() is part of the init() statement itself, or of a later
     * statement in the same block (not inside an if/else nested deeper)
     */
    private function editDisabledUnconditionally(array $toks, int $at): bool
    {
        // brace depth at the init() call
        $depth = 0;
        for ($i = 0; $i < $at; $i++) {
            $s = is_array($toks[$i]) ? $toks[$i][1] : $toks[$i];
            if ($s === '{' || (is_array($toks[$i]) && in_array($toks[$i][0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                $depth++;
            } elseif ($s === '}') {
                $depth--;
            }
        }
        $cur = $depth;
        for ($i = $at, $n = count($toks); $i < $n; $i++) {
            $t = $toks[$i];
            $s = is_array($t) ? $t[1] : $t;
            if ($s === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                $cur++;
            } elseif ($s === '}') {
                $cur--;
                if ($cur < $depth) {
                    return false; // end of the block the crud was built in
                }
            } elseif ($cur === $depth && is_array($t) && $t[0] === T_STRING && $s === 'disable_edit') {
                return true;
            }
        }
        return false;
    }
}
