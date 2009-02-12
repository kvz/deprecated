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
 * @param array $recurse    Wether or not to recurse
 * @param array &$errors    Stores errors
 *
 * @return array
 */

function expandTree(&$data = null, $allOptionsList = null, $recurse = false, &$errors = null)
{
    if (empty($data)) {
        return array();
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
        if ($recurse === true) {
            $recurse = 0;
        }
        $myOptionsList = &$allOptionsList[$recurse];
    } else {
        $myOptionsList = &$allOptionsList;
    }

    //    // Debug
    //    if (!function_exists('toVal')) {
    //        function toVal($s) {
    //            if (is_array($s)) {
    //                $k = key($s);
    //                $v = $s[$k];
    //                $s = $k . ' => '.$v. ' ...';
    //            }
    //
    //            return $s;
    //        }
    //    }


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

        // Expand
        while (list(, $doKey) = each($keys)) {
            //            //Debug:
            //            $errors[] = str_repeat(' ', 4*$recurse) .' processing: '. $operator. $doKey . ' : '. toVal($val);
            
            switch ($operator){
                case '-':
                    if (isset($data[$doKey])) {
                        unset($data[$doKey]);
                    }
                    break;
                case '=':
                    $data = array();
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
            if (is_array($data[$doKey]) && $recurse !== false) {
                $before = $data[$doKey];
                expandTree($data[$doKey], $allOptionsList, ($recurse + 1), $errors);
                $after = $data[$doKey];
            }
        }

        // Clean up Symbol keys
        if ($expanded) {
            if (isset($data[$origKey])) unset($data[$origKey]);
        }
    }
}
?>