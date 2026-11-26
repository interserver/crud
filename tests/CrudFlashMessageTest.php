<?php

use PHPUnit\Framework\TestCase;

/**
 * core's adminlte layout prints a flash message unescaped inside a js string
 * (Swal.fire({html: "{$error_msg}"})), and request values are only pre-escaped for <>&
 * (never quotes or backslashes), so no crud page may put a raw request value into one.
 * crud_dns_manager's 'Invalid IP ...' did (new=1&ip=1"+alert(document.domain)+"); it echoes
 * only the ip shaped characters of the value now. (core's CrudReflectedXssAndTenantFilterTest
 * runs the page and checks the stored message.)
 */
class CrudFlashMessageTest extends TestCase
{
    /**
     * @return array<string, string> "file:line" => the source of that flash_message() call's arguments
     */
    private static function flashMessageCalls(): array
    {
        $calls = [];
        foreach (glob(dirname(__DIR__) . '/src/crud/*.php') as $file) {
            $tokens = token_get_all((string) file_get_contents($file));
            $count = count($tokens);
            for ($i = 0; $i < $count; $i++) {
                if (!is_array($tokens[$i]) || ltrim(strtolower($tokens[$i][1]), '\\') !== 'flash_message'
                    || !in_array($tokens[$i][0], array_filter([T_STRING, defined('T_NAME_FULLY_QUALIFIED') ? T_NAME_FULLY_QUALIFIED : null]), true)) {
                    continue;
                }
                $j = $i + 1;
                while ($j < $count && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $j++;
                }
                if ($j >= $count || $tokens[$j] !== '(') {
                    continue;
                }
                $depth = 0;
                $args = '';
                for (; $j < $count; $j++) {
                    $text = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                    if ($text === '(') {
                        $depth++;
                    } elseif ($text === ')' && --$depth === 0) {
                        break;
                    }
                    $args .= $text;
                }
                $calls[basename($file) . ':' . $tokens[$i][2]] = $args;
            }
        }
        return $calls;
    }

    public function testNoFlashMessageCarriesARawRequestValue(): void
    {
        $calls = self::flashMessageCalls();
        $this->assertNotEmpty($calls, 'the crud pages flash messages (crud_dns_manager does)');
        foreach ($calls as $where => $args) {
            $this->assertDoesNotMatchRegularExpression('/->\s*request\s*\[|\b(?:request|post|get)Raw\s*\(|\$_(?:REQUEST|GET|POST|COOKIE|SERVER)\b/', $args, "{$where} puts a raw request value into a flash message");
        }
    }
}
