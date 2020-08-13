<?php
/**
 * Converts an Array to XLSX
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category XML
 */

/**
 * converts an array into a XLSX
 *
 * @param array $fields array of rows/fields
 * @param $headers
 * @return string the XLSX
 */
function array2Xlsx(array $fields, $headers)
{
	function_requirements('phpExcellCommon');
	return phpExcellCommon($fields, 'Xlsx', $headers);
}
