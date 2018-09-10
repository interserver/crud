<?php
/**
 * Converts an Array to XLS
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category XML
 */

/**
 * converts an array into a XLS
 *
 * @param array $fields array of rows/fields
 * @param $headers
 * @return string the XLS
 */
function array2Xls(array $fields, $headers)
{
	function_requirements('phpExcellCommon');
	return phpExcellCommon($fields, 'Excel5', $headers);
}
