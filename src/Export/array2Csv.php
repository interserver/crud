<?php
/**
 * Converts an Array to CSV
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category XML
 */

/**
 * converts an array into a CSV
 *
 * 		http://php.net/manual/en/function.fputcsv.php
 * 		http://stackoverflow.com/questions/13108157/php-array-to-csv
 * 		https://coderwall.com/p/zvzwwa/array-to-comma-separated-string-in-php
 *
 * @param array $fields array of rows/fields
 * @param string $delimiter character to separate columns
 * @param string $enclosure how to quote a column
 * @param bool $encloseAll optionally true to quote all columns regardless of content, false for default only quote whats needed
 * @param bool $nullToMysqlNull
 * @return string the CSV
 */
function array2Csv(array &$fields, $delimiter = ',', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false)
{
    $out = fopen('php://output', 'wb');
    fputcsv($out, $row);
    fclose($out);
    return '';

    $delimiter_esc = preg_quote($delimiter, '/');
    $enclosure_esc = preg_quote($enclosure, '/');
    $output = '';
    /*
    if (isset($fields[0]) && is_array($fields[0])) {
        $labeled_fields = true;
        $labels = array_keys($fields[0]);
        foreach ($labels as $key => $field ) {
            if ($field === null && $nullToMysqlNull) {
                $output = '';
                continue;
            }
            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ($encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field))
                $output .= $key.$delimiter.$enclosure.str_replace($enclosure, $enclosure . $enclosure,     $field).$enclosure.PHP_EOL;
            else
                $output .= $key.$delimiter.$field.PHP_EOL;
        }
    }*/
    foreach ($fields as $index => $field) {
        if ($field === null && $nullToMysqlNull) {
            $output = '';
            continue;
        }
        // Enclose fields containing $delimiter, $enclosure or whitespace
        if ($encloseAll || preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field)) {
            $output .= $index.$delimiter.$enclosure.str_replace($enclosure, $enclosure . $enclosure, $field).$enclosure.PHP_EOL;
        } else {
            $output .= $index.$delimiter.$field.PHP_EOL;
        }
    }
    return $output;
}
