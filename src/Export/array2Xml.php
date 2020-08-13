<?php
/**
 * Converts an Array to XML
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category XML
 */

/**
 * converts an array into a XML
 *
 * @param array $array the array to be converted
 * @param string? $rootElement if specified will be taken as root element, otherwise defaults to  <root>
 * @param SimpleXMLElement? if specified content will be appended, used for recursion
 * @return string XML version of $array
 */
function array2Xml($array, $rootElement = null, $xml = null)
{
	$_xml = $xml;
	if ($_xml === null) {
		$_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
	}
	foreach ($array as $k => $v) {
		if (is_array($v)) {
			array2Xml($v, $k, $_xml->addChild($k));
		} else {
			$_xml->addChild($k, $v);
		}
	}
	return $_xml->asXML();
}
