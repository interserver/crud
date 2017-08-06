<?php
/**
 * Converts an Array to XLS
 * Last Changed: $LastChangedDate: 2016-10-05 01:12:58 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @copyright 2017
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
function array2Xls(array $fields, $headers) {
	function_requirements('phpExcellCommon');
	return phpExcellCommon($fields, 'Excel5', $headers);
}
