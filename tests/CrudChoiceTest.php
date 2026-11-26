<?php

use MyCrud\Crud;
use PHPUnit\Framework\TestCase;

class ChoiceTestCrud extends Crud
{
    public static function functionName($choice)
    {
        return self::choice_function_name($choice);
    }
}

/**
 * $this->choice is printed into the templates' action="ajax.php?choice=crud&crud=..."
 * attributes. core's dispatchers (index.php, iframe.php, widget.php, admin/dc.php) run only
 * the segment between the first and second dot, whatever the prefix, so
 * 'none.crud_vps_list." onfocus="..." x="' still ran crud_vps_list while the whole value
 * came back out unescaped (request values are pre-escaped for <>& only, never quotes).
 * apply_request_data() now keeps just the function name the dispatcher ran.
 */
class CrudChoiceTest extends TestCase
{
    /** @var array */
    private $savedRequest;

    protected function setUp(): void
    {
        $this->savedRequest = $_REQUEST;
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->savedRequest;
    }

    /**
     * @return array<string, array{0: mixed, 1: ?string}> [request value, normalized choice]
     */
    public static function choiceProvider(): array
    {
        return [
            'plain function name' => ['crud_vps_list', 'crud_vps_list'],
            'none. is stripped' => ['none.crud_vps_list', 'crud_vps_list'],
            'payload after a second dot' => ['none.crud_vps_list." tabindex="1" autofocus onfocus="alert(1)" x="', 'crud_vps_list'],
            'payload straight after the name' => ['crud_vps_list" onfocus="alert(1)', 'crud_vps_list'],
            'pre-escaped payload' => ['none.crud_vps_list&lt;script&gt;', 'crud_vps_list'],
            'mixed case and digits kept' => ['none.View_VPS2_list', 'View_VPS2_list'],
            'no leading identifier' => ['" onfocus="alert(1)', ''],
            'none. then no identifier' => ['none." x="', ''],
            'leading digit' => ['none.1crud', ''],
            // the dispatchers run the second segment whatever the prefix
            'any prefix' => ['foo.crud_x', 'crud_x'],
            'payload after another prefix' => ['foo.crud_vps_list." onfocus="alert(document.domain)" x="', 'crud_vps_list'],
            'second of three segments' => ['a.b.c', 'b'],
            'empty prefix' => ['.crud_x', 'crud_x'],
            'nothing after the dot' => ['crud_x.', ''],
            'none. alone' => ['none.', ''],
            'empty' => ['', ''],
            'array (choice[]=x)' => [['crud_vps_list'], ''],
            'integer' => [5, ''],
            'missing' => [null, null],
        ];
    }

    /**
     * @dataProvider choiceProvider
     * @param mixed $value
     */
    public function testChoiceFunctionName($value, ?string $expected): void
    {
        $this->assertSame($expected, ChoiceTestCrud::functionName($value));
    }

    /**
     * @return array<string, array{0: string, 1: string}> the string cases of choiceProvider()
     */
    public static function stringChoiceProvider(): array
    {
        return array_filter(self::choiceProvider(), static function (array $row): bool {
            return is_string($row[0]);
        });
    }

    /**
     * the same split public_html/index.php (and iframe.php, widget.php, admin/dc.php) does
     * before it dispatches, cut to the leading identifier
     *
     * @dataProvider stringChoiceProvider
     */
    public function testMatchesTheCoreDispatchersSplit(string $value): void
    {
        if (preg_match('/\./', $value)) {
            [$class, $function] = explode('.', $value);
        } else {
            $function = $value;
        }
        $expected = preg_match('/^[A-Za-z_][A-Za-z0-9_]*/', $function, $matches) ? $matches[0] : '';
        $this->assertSame($expected, ChoiceTestCrud::functionName($value));
    }

    private function applyRequest(array $request): ChoiceTestCrud
    {
        /** @var ChoiceTestCrud $crud */
        $crud = (new ReflectionClass(ChoiceTestCrud::class))->newInstanceWithoutConstructor();
        // no MyAdmin app container here, so apply_request_data() reads $_REQUEST
        $_REQUEST = $request;
        $crud->apply_request_data();
        return $crud;
    }

    public function testPageRequestKeepsOnlyTheDispatchedFunction(): void
    {
        $crud = $this->applyRequest(['choice' => 'none.crud_vps_list." tabindex="1" autofocus onfocus="alert(1)" x="']);
        $this->assertSame('crud_vps_list', $crud->choice);
        $this->assertFalse($crud->ajax);
    }

    public function testPageRequestDropsAnyPrefixLikeTheDispatchers(): void
    {
        $crud = $this->applyRequest(['choice' => 'foo.crud_vps_list." onfocus="alert(document.domain)" x="']);
        $this->assertSame('crud_vps_list', $crud->choice);
    }

    public function testAjaxRequestKeepsOnlyTheCrudFunction(): void
    {
        $crud = $this->applyRequest(['choice' => 'crud', 'crud' => 'crud_vps_list" onfocus="alert(1)', 'action' => 'list']);
        $this->assertSame('crud_vps_list', $crud->choice);
        $this->assertSame('list', $crud->ajax);
    }

    public function testArrayChoiceNeverReachesMbSubstr(): void
    {
        $this->assertSame('', $this->applyRequest(['choice' => ['none.crud_vps_list']])->choice);
        $this->assertSame('', $this->applyRequest(['choice' => 'crud', 'crud' => ['crud_vps_list'], 'action' => 'list'])->choice);
    }
}
