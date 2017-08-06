<?php
/**
 * Converts an Array to XLSX
 * Last Changed: $LastChangedDate: 2016-10-05 01:12:58 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @copyright 2017
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
function array2Xlsx(array $fields, $headers) {
	function_requirements('phpExcellCommon');
	return phpExcellCommon($fields, 'Excel2007', $headers);
}
