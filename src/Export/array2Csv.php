<?php
/**
 * Converts an Array to CSV
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category XML
 */

/**
 * writes one csv row to php://output: the values of $fields, in order, through fputcsv() with
 * its default ',' delimiter and '"' enclosure. nothing is returned, the row is printed.
 *
 * 		http://php.net/manual/en/function.fputcsv.php
 * 		http://stackoverflow.com/questions/13108157/php-array-to-csv
 * 		https://coderwall.com/p/zvzwwa/array-to-comma-separated-string-in-php
 *
 * every value goes through \MyCrud\Crud::csv_safe_cell() first: one a spreadsheet would run as
 * a formula (=, +, -, @, tab or cr first, not a number) gets a leading single quote.
 *
 * the other parameters only belonged to the older string builder kept (unreachable) after the
 * return below; the row written ignores them.
 *
 * @param array $fields the values of one row
 * @param string $delimiter not used (the unreachable builder's column separator)
 * @param string $enclosure not used (the unreachable builder's quote character)
 * @param bool $encloseAll not used (the unreachable builder quoted every column when true)
 * @param bool $nullToMysqlNull not used
 * @return string always '' (the row goes to php://output)
 */
function array2Csv(array &$fields, $delimiter = ',', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false)
{
    // writes $fields as one csv row (Crud::export_csv() used to call this once per record). it
    // wrote an undefined $row, which fputcsv() refuses with a TypeError
    $row = [];
    foreach ($fields as $index => $field) {
        $row[$index] = \MyCrud\Crud::csv_safe_cell($field);
    }
    $out = fopen('php://output', 'wb');
    fputcsv($out, $row);
    fclose($out);
    return '';

    // unreachable since the return above: the older builder of a "key<delimiter>value" line
    // per field, returned as a string. kept as it was (with the csv_safe_cell() calls) for the
    // owner to decide whether it goes.
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
            $field = \MyCrud\Crud::csv_safe_cell($field);
            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ($encloseAll || preg_match( "/(?:{$delimiter_esc}|{$enclosure_esc}|\s)/", $field))
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
        $field = \MyCrud\Crud::csv_safe_cell($field);
        // Enclose fields containing $delimiter, $enclosure or whitespace
        if ($encloseAll || preg_match("/(?:{$delimiter_esc}|{$enclosure_esc}|\s)/", $field)) {
            $output .= $index.$delimiter.$enclosure.str_replace($enclosure, $enclosure . $enclosure, $field).$enclosure.PHP_EOL;
        } else {
            $output .= $index.$delimiter.$field.PHP_EOL;
        }
    }
    return $output;
}
