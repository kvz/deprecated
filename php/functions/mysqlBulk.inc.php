<?php
/**
 * Executes multiple queries in a 'bulk' to achieve better
 * performance and integrity.
 *
 * @param array  $data
 * @param string $method
 * @param array  $options
 * 
 * @return float
 */
function mysqlBulk(&$data, $table, $method = 'concatenation', $options = array()) {
    // Default options
    if (!isset($options['query_handler'])) {
        $options['query_handler'] = 'mysql_query';
    }
    if (!isset($options['trigger_errors'])) {
        $options['trigger_errors'] = true;
    }
    if (!isset($options['trigger_notices'])) {
        $options['trigger_notices'] = true;
    }
    if (!isset($options['eat_away'])) {
        $options['eat_away'] = false;
    }
    if (!isset($options['eat_away'])) {
        $options['safe'] = true;
    }

    // Validation
    if (!is_array($data)) {
        if ($options['trigger_notices']) {
            trigger_error('First argument "queries" must be an array',
                E_USER_NOTICE);
        }
        return false;
    }
    if (count($data) > 1000) {
        if ($options['trigger_notices']) {
            trigger_error('It\'s recommended to use < 1000 queries/bulk',
                E_USER_NOTICE);
        }
    }
    if (empty($data)) {
        return 0;
    }

    if (!function_exists('__exe')) {
        function __exe($sql, $options) {
            extract($options);
            if (!call_user_func($query_handler, $sql)) {
                if ($trigger_errors) {
                    trigger_error('Query failed.' .mysql_error() .'[sql: '.$sql.']',
                        E_USER_ERROR);
                    echo $sql."\n";
                    return false;
                }
            }
            
            return true;
        }
    }

    if (!function_exists('__sql2array')) {
        function __sql2array($sql, $options) {
            extract($options);  
            if (substr(strtoupper(trim($sql)), 0, 6) !== 'INSERT') {
                if ($trigger_errors) {
                    trigger_error('Magic sql2array conversion only works for inserts',
                        E_USER_ERROR);
                }
                return false;
            }

            $parts   = preg_split("/[,\(\)] ?(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $sql);
            $process = 'keys';
            $data    = array();

            foreach ($parts as $k=>$part) {
                $tpart = strtoupper(trim($part));
                if (substr($tpart, 0, 6) === 'INSERT') {
                    continue;
                } else if (substr($tpart, 0, 6) === 'VALUES') {
                    $process = 'values';
                    continue;
                } else if (substr($tpart, 0, 1) === ';') {
                    continue;
                }
                
                if (!isset($data[$process])) $data[$process] = array();
                $data[$process][] = $part;
            }

            return array_combine($data['keys'], $data['values']);
        }
    }


    // Make options local
    extract($options);

    // Start timer
    $start = microtime(true);
    $count = count($data);

    // Choose bulk method
    switch ($method) {
        case 'loaddata':
        case 'loaddata_unsafe':
            // Inserts data only
            // Use array instead of queries
            $buf    = '';
            foreach($data as $i=>$row) {
                if (!is_array($row)) {
                    $row = __sql2array($row, $options);
                }
                $buf .= implode(':::,', $row)."^^^\n";
            }
            
            $fields = implode(', ', array_keys($row));
            
            file_put_contents('/dev/shm/infile.txt', $buf);

            if ($method === 'loaddata_unsafe') {
                if (!__exe("SET UNIQUE_CHECKS=0", $options)) return false;
                if (!__exe("set foreign_key_checks=0", $options)) return false;
                if (!__exe("set sql_log_bin=0", $options)) return false;
                if (!__exe("set unique_checks=0", $options)) return false;
            }

            if (!__exe("
                LOAD DATA
                CONCURRENT LOCAL INFILE '/dev/shm/infile.txt'
                INTO TABLE ${table}
                FIELDS TERMINATED BY ':::,'
                LINES TERMINATED BY '^^^\\n'
                (${fields})
            ", $options)) return false;

            break;
        case 'delayed':
            // MyISAM, MEMORY, ARCHIVE, and BLACKHOLE tables only!
            if (!__exe(preg_replace('/$INSERT/', 'INSERT DELAYED',
                implode(';', $data)), $options)) return false;
            break;
        case 'transaction':
        case 'transaction_lock':
        case 'transaction_nokeys':
            // Max 26% gain, but good for data integrity
            if ($method == 'transaction_lock') {
                if (!__exe('SET autocommit = 0', $options)) return false;
                if (!__exe('LOCK TABLES '.$table.' READ', $options)) return false;
            } else if ($method == 'transaction_keys') {
                if (!__exe('ALTER TABLE '.$table.' DISABLE KEYS', $options)) return false;
            }

            if (!__exe('START TRANSACTION', $options)) return false;

            foreach ($data as $query) {
                if (!__exe($query, $options)) {
                    __exe('ROLLBACK', $options);
                    if ($method == 'transaction_lock') {
                        __exe('UNLOCK TABLES '.$table.'', $options);
                    }
                    return false;
                }
            }

            __exe('COMMIT', $options);

            if ($method == 'transaction_lock') {
                if (!__exe('UNLOCK TABLES', $options)) return false;
            } else if ($method == 'transaction_keys') {
                if (!__exe('ALTER TABLE '.$table.' ENABLE KEYS', $options)) return false;
            }
            break;
        case 'none':
            foreach ($data as $query) {
                if (!__exe($query, $options)) return false;
            }
            
            break;
        case 'concatenation':
        case 'concat_trans':
            // Unknown bulk method
            if ($trigger_errors) {
                trigger_error('Deprecated bulk method: "'.$method.'"',
                    E_USER_ERROR);
            }
            return false;
            break;
        default:
            // Unknown bulk method
            if ($trigger_errors) {
                trigger_error('Unknown bulk method: "'.$method.'"',
                    E_USER_ERROR);
            }
            return false;
            break;
    }

    // Stop timer
    $duration = microtime(true) - $start;
    $qps      = round ($count / $duration, 2);

    if ($eat_away) {
        $data = array();
    }

    // Return queries per second
    return $qps;
}
?>