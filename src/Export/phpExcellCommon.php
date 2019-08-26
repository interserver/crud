<?php
/**
 * Converts an Array to PHP Excell
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category PHP Excell
 */

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
function phpExcellCommon(array &$rows, $type, $headers)
{
	require_once(__DIR__.'/../../../../phpoffice/phpspreadsheet/src/Bootstrap.php');
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
	$spreadsheet->getActiveSheet()->fromArray($rows);
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
	$objWriter->save('php://output');
}
