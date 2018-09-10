<?php
/**
 * Converts an Array to PHP Excell
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category PHP Excell
 */

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
	/** Include PHPExcel */
	require_once INCLUDE_ROOT.'/../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();
	// Set document properties
	$objPHPExcel->getProperties()->setCreator('InterServer, Inc')
		->setLastModifiedBy('InterServer, Inc')
		->setTitle("{$type} Table Export")
		->setSubject("{$type} Table Export")
		->setDescription('Here is the exported information you requested.')
		->setKeywords('table interserver myadmin export')
		->setCategory('Exports');
	/*
	// Add some data
	foreach ($rows as $index => $Record) {
		if ($index == 0) {

		}
	}
	*/
	$objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(false);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
	$objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);
	//$objPHPExcel->getActiveSheet()->getColumnDimension()->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->fromArray($rows);
	/*
		->setCellValue('A1', 'Hello')
		->setCellValue('B2', 'world!')
		->setCellValue('C1', 'Hello')
		->setCellValue('D2', 'world!');
	// Miscellaneous glyphs, UTF-8
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A4', 'Miscellaneous glyphs')
		->setCellValue('A5', 'éàèùâêîôûëïüÿäöüç');
	*/
	// Rename worksheet
	//$objPHPExcel->getActiveSheet()->setTitle('Simple');
	if ($type == 'PDF') {
		$objPHPExcel->getActiveSheet()->setShowGridlines(false);
	}
	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$objPHPExcel->setActiveSheetIndex(0);
	if ($type == 'PDF') {
		//	Change these values to select the Rendering library that you wish to use and its directory location on your server
		$rendererName = PHPExcel_Settings::PDF_RENDERER_TCPDF;
		//$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
		//$rendererName = PHPExcel_Settings::PDF_RENDERER_DOMPDF;
		$rendererLibrary = 'tcPDF5.9';
		//$rendererLibrary = 'tcpdf';
		//$rendererLibrary = 'mPDF5.4';
		//$rendererLibrary = 'domPDF0.6.0beta3';
		$rendererLibraryPath = INCLUDE_ROOT.'/../vendor/tecnickcom/tcpdf/';
		//$rendererLibraryPath = __DIR__.'/../../../libraries/PDF/'.$rendererLibrary;
		if (!PHPExcel_Settings::setPdfRenderer(
		$rendererName,
		$rendererLibraryPath
		)) {
			die(
				'NOTICE: Please set the $rendererName and $rendererLibraryPath values' .
				'<br />' .
				'at the top of this script as appropriate for your directory structure'
			);
		}
	}
	foreach ($headers as $header) {
		header($header);
	}
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $type);
	$objWriter->save('php://output');
}
