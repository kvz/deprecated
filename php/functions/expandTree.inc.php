<?php
/**
 * Will look for operator characters (like '*', or '-action') in an array
 * and try to expand it to a full blown array, making use of predefined lists
 * of all possbile options per recursion level.
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $data = array(
 *     '*' => array(
 *         '*' => 1
 *     ),
 *     'add' => array(
 *         'employee_id,modified' => 0,
 *         '-is_update' => 1
 *     ),
 *     'edit,list' => array(
 *         '-is_update' => 1
 *     )
 * );
 *
 * $allOptions[0] = array('index', 'list', 'add', 'edit', 'view');
 * $allOptions[1] = array('employee_id', 'is_update', 'task_id', 'created', 'modified');
 *
 * // Execute //
 * expandTree($data, $allOptions, true, $errors);
 *
 * // Show //
 * print_r($data);
 *
 * // expects:
 * // Array
 * // (
 * //     [add] => Array
 * //         (
 * //             [employee_id] => 0
 * //             [task_id] => 1
 * //             [created] => 1
 * //             [modified] => 0
 * //         )
 * //
 * //     [index] => Array
 * //         (
 * //             [employee_id] => 1
 * //             [is_update] => 1
 * //             [task_id] => 1
 * //             [created] => 1
 * //             [modified] => 1
 * //         )
 * //
 * //     [list] => Array
 * //         (
 * //             [employee_id] => 1
 * //             [task_id] => 1
 * //             [created] => 1
 * //             [modified] => 1
 * //         )
 * //
 * //     [edit] => Array
 * //         (
 * //             [employee_id] => 1
 * //             [task_id] => 1
 * //             [created] => 1
 * //             [modified] => 1
 * //         )
 * //
 * //     [view] => Array
 * //         (
 * //             [employee_id] => 1
 * //             [is_update] => 1
 * //             [task_id] => 1
 * //             [created] => 1
 * //             [modified] => 1
 * //         )
 * //
 * // )
 * </code>
 * 
 * @param array &$data      Data to process
 * @param array $allOptions Lists to use when '*' is encountered
 * @param array $recurse    Wether or not to recurse. If true, allOptions should contain an array of lists. 1 for each recursion level
 * @param array &$errors    Stores errors
 *
 * @return array
 */

function expandTree(&$data = null, $allOptionsList = null, $recurse = false, &$errors = null)
{
    if (empty($data)) {
        return $data;
    }

    if (!is_array($errors)) {
        $errors = array();
    }

    $operators = array(
        '-' => true,
        '+' => true,
        '=' => true
    );

    // Determine level of recursion
    // and set active allOptions
    if ($recurse !== false) {
        if (!is_numeric($recurse)) {
            $recurse = 0;
        }

        // Pick options from recursion level
        if (empty($allOptionsList[$recurse]) || !is_array($allOptionsList[$recurse])) {
            // No error, but this is just where we stop.
            // It could be that userdata is going deeper than our
            // optionlist.
            return true;
        }
        
        $myOptionsList = &$allOptionsList[$recurse];
    } else {
        $myOptionsList = &$allOptionsList;
    }

    // Sanitize $myOptionsList
    // Detect too many dimensions
    foreach($myOptionsList as $val) {
        if (is_array($val)) {
            $errors[] = 'Current option list has more than 1 dimension';
            return false; // we could already be recursed so this is not a final break
        }
    }

    // Go through original, unexpanded keys
    while (list($key, $val) = each($data)) {
        $expanded = false;
        $origKey  = $key;

        // Determine mutation: add, delete, replace
        $operator = substr($key, 0, 1);
        if (isset($operators[$operator])) {
            $key      = substr($key, 1, strlen($key));
            $expanded = true;
        } else {
            // No mutation character defaults to: add
            $operator = '+';
        }

        // Determine selection
        $keys = array();
        if ($key == '*') {
            $expanded = true;
            $keys     = $myOptionsList;
        } else if (false !== strpos($key, ',')) {
            $expanded = true;
            $keys     = explode(',', $key);
        } else {
            $keys[] = $key;
        }

        // Go through expanded keys
        while (list(, $doKey) = each($keys)) {
            // //Debug:
            // $errors[] = str_repeat(' ', 4*$recurse) .' processing: '. $operator. $doKey . ' : '. (is_array($val) ? $k = key($val) . ' => ' . $val[$k]. '...' : $val) ;
            
            switch ($operator){
                case '-':
                    if (isset($data[$doKey])) {
                        unset($data[$doKey]);
                    }
                    break;
                case '=':
                    // Clean up all previous keys,
                    $data = array();

                    // and:
                case '+':
                    if (isset($data[$doKey])) {
                        // Item Already exists
                        if (is_array($data[$doKey]) && is_array($val)) {

                            $data[$doKey] = array_merge($val, $data[$doKey]);
                        } else if (is_array($val)) {
                            $errors[] = 'overwritting non-array: '.$doKey.' with array '.print_r($val, true);
                            $data[$doKey] = $val;
                        } else if (is_array($data[$doKey])) {
                            $errors[] = 'NOT overwritting array: '.$doKey.' with non-array '.print_r($val, true);
                        } else {
                            $data[$doKey] = $val;
                        }
                    } else {
                        // New item
                        $data[$doKey] = $val;
                    }
                    break;
            }
            
            // Recurse Expand
            if ($recurse !== false && isset($data[$doKey]) && is_array($data[$doKey])) {
                expandTree($data[$doKey], $allOptionsList, ($recurse + 1), $errors);
            }
        }

        // Clean up Symbol keys
        if ($expanded) {
            if (isset($data[$origKey])) unset($data[$origKey]);
        }
    }

    // Return false on errors
    return (count($errors) == 0);
}
?>