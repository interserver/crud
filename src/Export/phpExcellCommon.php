<?php
/**
 * Converts an Array to PHP Excell
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category PHP Excell
 */

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * writes rows into a sheet from A1 on, the same cells fromArray($rows) filled, but a string
 * that is not a number is always stored as a string.
 *
 * fromArray() ran every value through the default value binder, which stores a string
 * starting with '=' as a live formula: a customer's hostname or comment of '=HYPERLINK(...)'
 * or '=WEBSERVICE(...)' became a formula in the xlsx/xls/ods (and phpspreadsheet's own
 * calculation engine evaluated it when it wrote the file and the pdf). numbers, numeric
 * strings, booleans still go through setCellValue() and come out as before.
 *
 * @param Worksheet $sheet
 * @param array $rows array of rows (or one row)
 * @return void
 */
function phpExcellFillSheet(Worksheet $sheet, array $rows)
{
    // fromArray() turned a single row into a list of one row
    if (!is_array(end($rows))) {
        $rows = [$rows];
    }
    $rowNumber = 1;
    foreach ($rows as $rowData) {
        if (!is_array($rowData)) {
            // fromArray() could not loop over it either: an empty row
            $rowNumber++;
            continue;
        }
        $column = 1;
        foreach ($rowData as $cellValue) {
            // fromArray()'s loose test against its null value: '', null, false and 0 leave the
            // cell empty, as they did
            if ($cellValue != null) {
                $coordinate = Coordinate::stringFromColumnIndex($column).$rowNumber;
                if (is_string($cellValue) && !is_numeric($cellValue)) {
                    $sheet->setCellValueExplicit($coordinate, StringHelper::sanitizeUTF8($cellValue), DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($coordinate, $cellValue);
                }
            }
            $column++;
        }
        $rowNumber++;
    }
}

/**
 * converts an array into a PHP Excell
 *
 * @param array  $rows array of rows/fields
 * @param string $type can be OpenDocument, Excel5, Excel2007, PDF
 * @param        $headers
 * @return string the XLS
 * @throws \PHPExcel_Exception
 * @throws \PHPExcel_Reader_Exception
 * @throws \PHPExcel_Writer_Exception
 */
function phpExcellCommon(array &$rows, $type, $headers = [])
{
    $spreadsheet = new Spreadsheet();

    // Set document properties
    $spreadsheet->getProperties()->setCreator('InterServer, Inc')
        ->setLastModifiedBy('InterServer, Inc')
        ->setTitle("{$type} Table Export")
        ->setSubject("{$type} Table Export")
        ->setDescription('Here is the exported information you requested.')
        ->setKeywords('table interserver myadmin export')
        ->setCategory('Exports');
    $spreadsheet->setActiveSheetIndex(0);
    $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(false);
    $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
    $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
    $spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);
    phpExcellFillSheet($spreadsheet->getActiveSheet(), $rows);
    if ($type == 'Pdf') {
        $spreadsheet->getActiveSheet()->setShowGridlines(false);
    }
    $spreadsheet->setActiveSheetIndex(0);
    if ($type == 'Pdf') {
        IOFactory::registerWriter('Pdf', \PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf::class);
    }
    foreach ($headers as $header) {
        header($header);
    }
    $objWriter = IOFactory::createWriter($spreadsheet, $type);
    ob_end_clean();
    $objWriter->save('php://output');
    die();
}
