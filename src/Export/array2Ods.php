<?php
/**
 * Converts an Array to ODS
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category ODS
 */

/**
 * converts an array into a ODS
 *
 * @param array $fields array of rows/fields
 * @param $headers
 * @return string the XLS
 */
function array2Ods(array $fields, $headers)
{
	function_requirements('phpExcellCommon');
	return phpExcellCommon($fields, 'Ods', $headers);
}
