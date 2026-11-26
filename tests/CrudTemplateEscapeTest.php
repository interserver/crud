<?php

use PHPUnit\Framework\TestCase;

/**
 * Smarty here has no auto-escape, and request values are only pre-escaped for <>& (never
 * quotes), so the crud templates must escape $choice and $extra_url_args themselves where
 * they print them into action="ajax.php?choice=crud&crud=...&action=...<extra>" attributes:
 *  - $choice is one query parameter value: url-encoded (which is also attribute safe);
 *  - $extra_url_args is a pre-built '&k=v' string (crud_table builds it from the request
 *    db/table) already pre-escaped for <>&: html-escaped with double_encode off, so quotes
 *    are escaped while the url the browser reads back stays the same.
 * (the real rendering is covered end to end by core's CrudReflectedXssAndTenantFilterTest.)
 */
class CrudTemplateEscapeTest extends TestCase
{
    private const CHOICE = "{\$choice|escape:'url'}";
    private const EXTRA = "{\$extra_url_args|escape:'html':'UTF-8':false}";

    /**
     * @return array<string, string> template file => source
     */
    private static function templates(): array
    {
        $templates = [];
        foreach (glob(dirname(__DIR__) . '/public_html/templates/crud/*.tpl') as $file) {
            $templates[basename($file)] = (string) file_get_contents($file);
        }
        return $templates;
    }

    public function testChoiceAndExtraUrlArgsArePrintedOnlyThroughTheirEscapes(): void
    {
        $templates = self::templates();
        $this->assertNotEmpty($templates);
        $choices = 0;
        $extras = 0;
        foreach ($templates as $name => $source) {
            // every smarty tag that mentions either variable, in any form ({$choice}, {$choice|...},
            // {if $choice ...}, {$choice nofilter} ...)
            preg_match_all('/\{[^{}]*\$(?:choice|extra_url_args)\b[^{}]*\}/', $source, $matches);
            foreach ($matches[0] as $tag) {
                $this->assertContains($tag, [self::CHOICE, self::EXTRA], "{$name} prints {$tag} unescaped or with another escape");
                $tag === self::CHOICE ? $choices++ : $extras++;
            }
        }
        // the 9 form actions of tableAdminLte.tpl (4), table5.tpl (3) and tableAdminLteMaterial.tpl (2)
        $this->assertSame(9, $choices);
        $this->assertSame(9, $extras);
    }

    public function testEveryFormActionUsesBothEscapes(): void
    {
        foreach (self::templates() as $name => $source) {
            preg_match_all('/action="ajax\.php\?choice=crud&crud=[^"]*"/', $source, $matches);
            foreach ($matches[0] as $action) {
                $this->assertStringContainsString('&crud=' . self::CHOICE . '&action=', $action, $name);
                $this->assertStringEndsWith(self::EXTRA . '"', $action, $name);
            }
        }
    }
}
