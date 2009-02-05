<?php
/**
 * Will look for semantic characters (like '*', or '-action') in an array
 * and try to explode it to a full blown array.
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
 *     'edit,list' => 1
 * );
 *
 * $allOptions[0] = array('index', 'list', 'add', 'edit', 'view');
 * $allOptions[1] = array('employee_id', 'is_update', 'task_id', 'created', 'modified');
 * 
 * // Execute //
 * expandKeys($data, $allOptions, true);
 * 
 * // Show //
 * print_r($data);
 * 
 * // expects:
 * // Array
 * // (
 * //     [0] => Kevin and Max go for walk in the park.
 * // )
 * </code>
 * 
 * @param array $data
 * @param array $allOptions list to use when '*' is encountered
 *
 * @return array
 */


function expandKeys(&$data = null, $allOptionsList = null, $recurse = false)
{
    if (empty($data)) {
        return array();
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

    foreach($data as $key=>$val) {
        $expanded = false;
        $origKey  = $key;

        // Determine mutation: add, delete, replace
        $operator   = substr($key, 0, 1);
        if (isset($operators[$operator])) {
            $key = substr($key, 1, strlen($key));
            $expanded = true;
        } else {
            // No mutation character defaults to: add
            $operator = '+';
        }

        // Determine selection
        $keys = array();
        if ($key == '*') {
            $expanded = true;
            $keys = $myOptionsList;
        } else if (false !== strpos($key, ',')) {
            $expanded = true;
            $keys = explode(',', $key);
        } else {
            $keys[] = $key;
        }

        // Mutate data according to selection
        foreach($keys as $doKey) {
            switch($operator){
                case '-':
                    indent($recurse, 'REMOVING KEY: '.$doKey." in: ".print_r($data, true));
                    if (isset($data[$doKey])) unset($data[$doKey]);
                    indent($recurse, 'done: '.print_r($data, true));
                    break;
                case '=':
                    $data = array();
                case '+':
                    $data[$doKey] = $val;
                    break;
            }
        }

        // Clean up Symbol keys
        if ($expanded) {
            indent($recurse, 'REMOVING OLD KEY: '.$origKey." in: ".print_r($data, true));
            if (isset($data[$origKey])) unset($data[$origKey]);
            indent($recurse, 'done: '.print_r($data, true));
        }

        // Recurse
        if (is_array($data[$doKey]) && $recurse !== false) {
            indent($recurse, 'recursing: '.($recurse+1)." for $key: ".print_r($data[$doKey], true));
            expandKeys($data[$doKey], $allOptionsList, ($recurse + 1));
            indent($recurse, 'done: '.print_r($data[$doKey], true));
        }
    }
}

function indent($indent, $what) {
    $lines       = explode("\n", $what);
    $result      = "";
    $indentation = str_repeat('    ', $indent);
    foreach($lines as $line) {
        $result .= $indentation.$line."\n";
    }
    echo $result;
}
?>