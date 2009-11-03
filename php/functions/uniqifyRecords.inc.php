<?php
/**
 * When you can't do a GROUP BY, Use this function to remove
 * records with a number of identical fields.
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $input = array(0 => array('name' => 'Kevin van Zonneveld', 'age' => 26), 1 => array('name' => 'Someone else', 'age' => 26));
 *
 * // Execute //
 * $output = uniqifyRecords($input, array('age'), false);
 *
 * // Show //
 * echo $output[0]['name'];
 *
 * // expects:
 * // Kevin van Zonneveld
 * </code>
 *
 * @param array $data Two Dimensional array. Typically a dataset
 * @param array $byFields List of fields that make a record unique
 * @param boolean $overwrite Whether to just skip a similar new record, or to overwrite the original one
 *
 * @return array
 */
function uniqifyRecords($data, $byFields, $overwrite=true) {
    if (empty($data)) {
        return $data;
    }

    $byFields = (array)$byFields;

    $del = '---';
    $log = array();
    foreach($data as $i=>$fields) {
        $gotRecord = false;
        $uniquestr = '';
        foreach($byFields as $ukey) {
            $uniquestr .= $del . $fields[$ukey];
        }

        if (isset($log[$uniquestr])) {
            // Overwrite the original with latest
            if ($overwrite) {
                $data[$log[$uniquestr]] = $fields;
            }
            // And then in any case skip the new & identical one
            unset($data[$i]);
        } else {
            // Record a new identical record!
            $log[$uniquestr] = $i;
        }
    }

    return $data;
}
?>