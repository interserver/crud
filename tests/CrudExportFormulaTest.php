<?php

use MyCrud\Crud;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;

class ExportFormulaTestCrud extends Crud
{
    public function log($message, $line = '', $file = '', $level = 'info')
    {
    }
}

/**
 * formula injection through the exports: customer text (hostnames, comments, names,
 * descriptions) went into the csv as it is, where excel / libreoffice / sheets run a cell
 * starting with = + - @ (or a tab or cr; libreoffice drops a leading nul first) as a formula,
 * and into the xlsx / xls / ods / pdf
 * through fromArray(), whose value binder stores a string starting with '=' as a live formula
 * (phpspreadsheet's own calculation engine evaluated it while writing the file).
 */
class CrudExportFormulaTest extends TestCase
{
    /** the package under test (the clone or the old copy, whichever the bootstrap loads) */
    private static function packageDir(): string
    {
        return dirname((new ReflectionClass(Crud::class))->getFileName(), 2);
    }

    /** core's vendor dir (the autoloader the tests run with) */
    private static function vendorDir(): string
    {
        return dirname((new ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName(), 2);
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}> [value, csv value]
     */
    public static function csvCellProvider(): array
    {
        return [
            'formula' => ['=HYPERLINK("http://example.invalid","x")', '\'=HYPERLINK("http://example.invalid","x")'],
            'plus' => ['+cmd|\' /C calc\'!A0', '\'+cmd|\' /C calc\'!A0'],
            'minus text' => ['-2+3', '\'-2+3'],
            'at' => ['@SUM(A1:A2)', '\'@SUM(A1:A2)'],
            'tab' => ["\t=1+1", "'\t=1+1"],
            'carriage return' => ["\r=1+1", "'\r=1+1"],
            // libreoffice drops a leading nul and runs the rest as a formula (review crud-r2 F2a)
            'nul' => ["\0=1+1", "'\0=1+1"],
            'two nuls' => ["\0\0=1+1", "'\0\0=1+1"],
            'nul alone' => ["\0", "'\0"],
            'nul later' => ["a\0=1", "a\0=1"],
            'negative number string' => ['-5.00', '-5.00'],
            'positive number string' => ['+1', '+1'],
            'exponent' => ['1e3', '1e3'],
            'int' => [-7, -7],
            'float' => [-1.5, -1.5],
            'null' => [null, null],
            'bool' => [true, true],
            'empty' => ['', ''],
            'equals later' => ['a=b', 'a=b'],
            'leading space' => [' =1', ' =1'],
            'hostname' => ['web1.example.com', 'web1.example.com'],
        ];
    }

    /**
     * @dataProvider csvCellProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testCsvSafeCell($value, $expected): void
    {
        $this->assertSame($expected, Crud::csv_safe_cell($value));
    }

    public function testExportCsvQuotesFormulaCells(): void
    {
        $rc = new ReflectionClass(ExportFormulaTestCrud::class);
        /** @var ExportFormulaTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        // export_csv() asks function_requirements() for array2Csv, which it does not call
        require_once self::packageDir() . '/src/Export/array2Csv.php';
        $crud->rows = [
            ['vps_id' => 5, 'vps_hostname' => '=HYPERLINK("http://example.invalid","x")', 'cost' => '-5.00', 'vps_comment' => '-cmd', 'vps_ip' => '192.0.2.10'],
        ];
        $tf = new class {
            public function function_requirements($function)
            {
                return true;
            }
        };
        \MyAdmin\App::setContainer(\MyAdmin\App\Testing\TestContainerBuilder::make()->withTf($tf)->build());
        try {
            ob_start();
            $crud->export_csv([]);
            $csv = (string) ob_get_clean();
        } finally {
            \MyAdmin\App::resetContainer();
        }
        $lines = array_values(array_filter(explode("\n", $csv), 'strlen'));
        // read as a spreadsheet reads it: rfc 4180, no escape character
        $this->assertSame(['vps_id', 'vps_hostname', 'cost', 'vps_comment', 'vps_ip'], str_getcsv($lines[0], ',', '"', ''), 'the header row is unchanged');
        $this->assertSame(['5', '\'=HYPERLINK("http://example.invalid","x")', '-5.00', "'-cmd", '192.0.2.10'], str_getcsv($lines[1], ',', '"', ''));
    }

    /** runs the real export_csv() over $rows and returns what it printed */
    private static function exportCsv(array $rows): string
    {
        $rc = new ReflectionClass(ExportFormulaTestCrud::class);
        /** @var ExportFormulaTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        // export_csv() asks function_requirements() for array2Csv, which it does not call
        require_once self::packageDir() . '/src/Export/array2Csv.php';
        $crud->rows = $rows;
        $tf = new class {
            public function function_requirements($function)
            {
                return true;
            }
        };
        \MyAdmin\App::setContainer(\MyAdmin\App\Testing\TestContainerBuilder::make()->withTf($tf)->build());
        try {
            ob_start();
            $crud->export_csv([]);
            return (string) ob_get_clean();
        } finally {
            \MyAdmin\App::resetContainer();
        }
    }

    /**
     * the records of a csv as excel / libreoffice / sheets read it: rfc 4180, where '"' is
     * escaped only by doubling it (a backslash is an ordinary character), and a quoted field
     * may hold line breaks
     *
     * @return list<list<string|null>>
     */
    private static function readCsv(string $csv): array
    {
        $fp = fopen('php://memory', 'w+');
        fwrite($fp, $csv);
        rewind($fp);
        $records = [];
        while (($record = fgetcsv($fp, null, ',', '"', '')) !== false) {
            $records[] = $record;
        }
        fclose($fp);
        return $records;
    }

    /**
     * @return array<string, array{0: string}> values a customer can type that held '\"'
     */
    public static function backslashQuoteProvider(): array
    {
        return [
            // the reviewer's payload: the field closed early and '=1+1' became a cell of its own
            'comma breakout' => ['web1\\",=1+1,\\"'],
            // the same through a line break: '=1+1' started the next row
            'row breakout' => ["web1\\\"\n=1+1"],
            'two backslashes' => ['web1\\\\",=2+2,\\\\"'],
            'backslash first' => ['\\",=3+3,\\"'],
        ];
    }

    /**
     * fputcsv()'s default escape '\' left a '"' after a backslash undoubled, so a spreadsheet
     * (rfc 4180 has no escape character) closed the quoted field there and read the text after
     * the next comma or line break as a new cell: one starting with '=' ran as a formula, past
     * csv_safe_cell(), which only sees the first character of the whole value. the export
     * passes escape '' now: every '"' is doubled and the value stays one cell
     *
     * @dataProvider backslashQuoteProvider
     */
    public function testExportCsvKeepsABackslashQuoteInItsCell(string $payload): void
    {
        $this->assertSame($payload, Crud::csv_safe_cell($payload), 'csv_safe_cell() leaves it alone (it does not start with a formula character)');
        $csv = self::exportCsv([['vps_id' => 5, 'vps_hostname' => $payload, 'vps_ip' => '192.0.2.10']]);
        $records = self::readCsv($csv);
        foreach ($records as $record) {
            foreach ($record as $cell) {
                $this->assertSame($cell, Crud::csv_safe_cell($cell), 'no cell a spreadsheet would run as a formula: ' . json_encode($cell));
            }
        }
        $this->assertSame([['vps_id', 'vps_hostname', 'vps_ip'], ['5', $payload, '192.0.2.10']], $records, 'the payload stays in its one cell of its one row');
    }

    /** the reviewer's row, byte for byte: '"' doubled after the backslash too */
    public function testExportCsvWritesTheBackslashQuoteRowAsRfc4180(): void
    {
        $csv = self::exportCsv([['vps_id' => 5, 'vps_hostname' => 'web1\\",=1+1,\\"', 'vps_ip' => '192.0.2.10']]);
        $this->assertSame("vps_id,vps_hostname,vps_ip\n5,\"web1\\\"\",=1+1,\\\"\"\",192.0.2.10\n", $csv);
    }

    /**
     * a row with no backslash is written byte for byte as before the escape changed (spaces,
     * commas, quotes, a line break, a tab, a formula cell, numbers, null and true)
     */
    public function testExportCsvBytesOfOrdinaryRowsAreUnchanged(): void
    {
        $csv = self::exportCsv([
            ['vps_id' => 5, 'vps_hostname' => 'web1.example.com', 'cost' => '6.00', 'vps_comment' => 'say "hi", then go', 'vps_ip' => '192.0.2.10'],
            ['vps_id' => 6, 'vps_hostname' => 'a b', 'cost' => '-5.00', 'vps_comment' => "two\nlines\tand a tab", 'vps_ip' => null],
            ['vps_id' => 7, 'vps_hostname' => '=1+1', 'cost' => 1.5, 'vps_comment' => '', 'vps_ip' => true],
        ]);
        $this->assertSame(
            "vps_id,vps_hostname,cost,vps_comment,vps_ip\n"
            . "5,web1.example.com,6.00,\"say \"\"hi\"\", then go\",192.0.2.10\n"
            . "6,\"a b\",-5.00,\"two\nlines\tand a tab\",\n"
            . "7,'=1+1,1.5,,1\n",
            $csv
        );
    }

    /**
     * a value starting with a nul byte: fputcsv() does not quote a nul, and libreoffice dropped
     * it and ran the rest ("\0=1+1" imported as the formula =1+1 where formulas are evaluated),
     * past csv_safe_cell(), which saw only the nul first (review crud-r2 F2a). it gets the leading '
     * now, through export_csv() and array2Csv() alike; a nul later in a value changes nothing
     */
    public function testALeadingNulIsWrittenAsText(): void
    {
        $csv = self::exportCsv([
            ['vps_id' => 5, 'vps_hostname' => "\0=1+1", 'vps_comment' => "\0\0=HYPERLINK(\"http://x.invalid\")", 'vps_ip' => "192.0.2.10\0"],
        ]);
        $this->assertSame(
            "vps_id,vps_hostname,vps_comment,vps_ip\n5,'\0=1+1,\"'\0\0=HYPERLINK(\"\"http://x.invalid\"\")\",192.0.2.10\0\n",
            $csv
        );
        $this->assertSame([['vps_id', 'vps_hostname', 'vps_comment', 'vps_ip'], ['5', "'\0=1+1", "'\0\0=HYPERLINK(\"http://x.invalid\")", "192.0.2.10\0"]], self::readCsv($csv));

        require_once self::packageDir() . '/src/Export/array2Csv.php';
        $fields = ["\0=1+1", "a\0=1"];
        ob_start();
        try {
            array2Csv($fields);
        } finally {
            $row = (string) ob_get_clean();
        }
        $this->assertSame("'\0=1+1,a\0=1\n", $row);
    }

    /** array2Csv() writes with the same escape: the payload stays one cell there too */
    public function testArray2CsvKeepsABackslashQuoteInItsCell(): void
    {
        require_once self::packageDir() . '/src/Export/array2Csv.php';
        $fields = ['web1\\",=1+1,\\"', '=1'];
        ob_start();
        try {
            array2Csv($fields);
        } finally {
            $csv = (string) ob_get_clean();
        }
        $this->assertSame("\"web1\\\"\",=1+1,\\\"\"\",'=1\n", $csv);
        $this->assertSame([['web1\\",=1+1,\\"', "'=1"]], self::readCsv($csv));
    }

    public function testArray2CsvWritesOneSafeRow(): void
    {
        require_once self::packageDir() . '/src/Export/array2Csv.php';
        $fields = ['=1+1', 'web1.example.com', -3, '-3', '@x'];
        ob_start();
        try {
            $return = array2Csv($fields);
        } finally {
            $csv = (string) ob_get_clean();
        }
        $this->assertSame('', $return);
        $this->assertSame("'=1+1,web1.example.com,-3,-3,'@x\n", $csv);
    }

    /**
     * array2Csv()'s docblock described the unreachable string builder ("@return string the
     * CSV", $fields "array of rows/fields"): it writes one row to php://output and returns ''.
     * the builder after the return stays, marked unreachable
     */
    public function testArray2CsvDocumentsWhatItDoes(): void
    {
        require_once self::packageDir() . '/src/Export/array2Csv.php';
        $doc = (string) (new ReflectionFunction('array2Csv'))->getDocComment();
        $this->assertStringNotContainsString('@return string the CSV', $doc);
        $this->assertStringContainsString("@return string always '' (the row goes to php://output)", $doc);
        $this->assertStringContainsString('@param array $fields the values of one row', $doc);
        $source = (string) file_get_contents(self::packageDir() . '/src/Export/array2Csv.php');
        $this->assertStringContainsString('// unreachable since the return above', $source);
        $this->assertStringContainsString('$delimiter_esc = preg_quote($delimiter', $source, 'the legacy builder is kept');
    }

    /** one invalid utf-8 byte made the json export json_encode() false, an empty download */
    public function testExportJsonSurvivesAnInvalidUtf8Byte(): void
    {
        $rc = new ReflectionClass(ExportFormulaTestCrud::class);
        /** @var ExportFormulaTestCrud $crud */
        $crud = $rc->newInstanceWithoutConstructor();
        $crud->rows = [['vps_id' => 5, 'vps_comment' => "bad\xC3(x"]];
        $json = $crud->export_json([]);
        $this->assertIsString($json);
        $this->assertSame([['vps_id' => 5, 'vps_comment' => "bad\u{FFFD}(x"]], json_decode($json, true));
    }

    /** the cells of a sheet: coordinate => [value, data type] */
    private static function cells(Worksheet $sheet): array
    {
        $cells = [];
        foreach ($sheet->getCoordinates() as $coordinate) {
            $cell = $sheet->getCell($coordinate);
            $cells[$coordinate] = [$cell->getValue(), $cell->getDataType()];
        }
        ksort($cells);
        return $cells;
    }

    /**
     * @return array<string, array{0: array}> rows without anything a formula could start with
     */
    public static function benignRowsProvider(): array
    {
        return [
            'list of rows' => [[
                ['Date', 'Type', 'Service', 'Description', 'Amount', 'Paid', 'ID'],
                ['2026-11-01', 'PayPal', 'web1.example.com', 'KVM VPS', '6.00', 'Yes', 12345],
                ['date' => '2026-11-02', 'type' => 'Card', 'service' => '', 'description' => null, 'amount' => '-5.00', 'paid' => 0, 'id' => '0'],
                [1.5, true, false, '007', '1e3', 'a=b', ' =1'],
            ]],
            'one row' => [['a', 'b', 3, '4']],
        ];
    }

    /**
     * phpExcellFillSheet() fills the same cells as fromArray() did, with the same values and
     * types, for every value that is not a formula
     *
     * @dataProvider benignRowsProvider
     */
    public function testFillSheetMatchesFromArrayForOrdinaryValues(array $rows): void
    {
        require_once self::packageDir() . '/src/Export/phpExcellCommon.php';
        $old = new Spreadsheet();
        $old->getActiveSheet()->fromArray($rows);
        $new = new Spreadsheet();
        phpExcellFillSheet($new->getActiveSheet(), $rows);
        $this->assertSame(self::cells($old->getActiveSheet()), self::cells($new->getActiveSheet()));
    }

    public function testFillSheetStoresAFormulaAsText(): void
    {
        require_once self::packageDir() . '/src/Export/phpExcellCommon.php';
        $sheet = (new Spreadsheet())->getActiveSheet();
        phpExcellFillSheet($sheet, [['=1+1', '=HYPERLINK("http://example.invalid","x")', '-5', 7]]);
        $this->assertSame(['=1+1', DataType::TYPE_STRING], [$sheet->getCell('A1')->getValue(), $sheet->getCell('A1')->getDataType()]);
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B1')->getDataType());
        $this->assertTrue($sheet->getStyle('A1')->getQuotePrefix(), 'shown as typed, never run');
        $this->assertSame([-5, DataType::TYPE_NUMERIC], [$sheet->getCell('C1')->getValue(), $sheet->getCell('C1')->getDataType()]);
        $this->assertSame([7, DataType::TYPE_NUMERIC], [$sheet->getCell('D1')->getValue(), $sheet->getCell('D1')->getDataType()]);
    }

    /**
     * the whole export: phpExcellCommon() writes the xlsx to php://output and exits, so it runs
     * in a php of its own; the file it wrote is read back
     */
    public function testTheXlsxExportHoldsTheFormulaAsText(): void
    {
        $script = <<<'PHP'
<?php
require $argv[1];
require_once $argv[2];
// function_requirements() is core's loader; the export functions are already required
$rows = [['vps_id', 'vps_hostname', 'cost'], [5, '=1+1', '6.00'], [6, '=SUM(1,2)', '-5.00']];
ob_start();
phpExcellCommon($rows, 'Xlsx');
PHP;
        $dir = sys_get_temp_dir() . '/crud-export-' . bin2hex(random_bytes(6));
        mkdir($dir, 0700);
        $runner = $dir . '/run.php';
        $xlsx = $dir . '/out.xlsx';
        file_put_contents($runner, $script);
        try {
            $cmd = [PHP_BINARY, $runner, self::vendorDir() . '/autoload.php', self::packageDir() . '/src/Export/phpExcellCommon.php'];
            $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', $xlsx, 'w'], 2 => ['pipe', 'w']], $pipes);
            $this->assertIsResource($proc);
            fclose($pipes[0]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($proc), $stderr);

            $sheet = IOFactory::load($xlsx)->getActiveSheet();
            $this->assertSame(['vps_id', 'vps_hostname', 'cost'], [$sheet->getCell('A1')->getValue(), $sheet->getCell('B1')->getValue(), $sheet->getCell('C1')->getValue()]);
            $this->assertSame(['=1+1', DataType::TYPE_STRING], [$sheet->getCell('B2')->getValue(), $sheet->getCell('B2')->getDataType()]);
            $this->assertSame(['=SUM(1,2)', DataType::TYPE_STRING], [$sheet->getCell('B3')->getValue(), $sheet->getCell('B3')->getDataType()]);
            // numbers stay numbers
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('A2')->getDataType());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('C3')->getDataType());
            $this->assertEquals(-5, $sheet->getCell('C3')->getValue());
        } finally {
            foreach ([$runner, $xlsx] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
    }
}
