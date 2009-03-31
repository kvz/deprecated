<?php
/**
 * Executes multiple queries in a 'bulk' to achieve better
 * performance and integrity.
 *
 * @param array  $queries
 * @param string $method
 * @param array  $options
 * 
 * @return float
 */
function mysqlBulk(&$queries, $table, $method = 'concatenation', $options = array()) {
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

    // Validation
    if (!is_array($queries)) {
        if ($options['trigger_notices']) {
            trigger_error('First argument "queries" must be an array',
                E_USER_NOTICE);
        }
        return false;
    }
    if (count($queries) > 1000) {
        if ($options['trigger_notices']) {
            trigger_error('It\'s recommended to use < 1000 queries/bulk',
                E_USER_NOTICE);
        }
    }
    if (empty($queries)) {
        return 0;
    }

    if (!function_exists('__c')) {
        function __c(){
            list($num, $res) = queryS('SELECT COUNT(id) as cnt FROM benchmark_data');
            $row = mysql_fetch_assoc($res);
            return $row['cnt'];
        }
    }

    if (!function_exists('__execute')) {
        function __execute($sql, $options) {
            extract($options);
            
            if (!call_user_func($query_handler, $sql)) {
                if ($trigger_errors) {
                    trigger_error('Query failed.' .mysql_error(),
                        E_USER_ERROR);
                    echo $sql."\n";
                    return false;
                }
            }
            
            return true;
        }
    }

    // Make options local
    extract($options);

    // Start timer
    $start = microtime(true);
    $count = count($queries);

    // Choose bulk method
    switch ($method) {
        case 'loaddata':
            // Max 1200% gain
            echo '-'.$method.'-'.$query_handler.'-'.count($queries).__LINE__."\n";
            echo '    '.__c()." : ";
            
            if (!__execute(implode(';', $queries), $options)) {
                return false;
            }

            echo '    '.__c()."\n";
            break;
        case 'delayed':
            // MyISAM, MEMORY, ARCHIVE, and BLACKHOLE tables only!
            echo '-'.$method.'-'.$query_handler.'-'.count($queries).__LINE__."\n";
            echo '    '.__c()." : ";

            if (!__execute(preg_replace('/$INSERT/', 'INSERT DELAYED',
                        implode(';', $queries)), $options)) {
                return false;
            }
            
            echo '    '.__c()."\n";
            break;
        case 'transaction':
            // Max 26% gain, but good for data integrity
            echo '-'.$method.'-'.$query_handler.'-'.count($queries).__LINE__."\n";
            echo '    '.__c()." : ";
            
            if (!__execute('START TRANSACTION', $options)) {
                return false;
            }

            foreach ($queries as $query) {
                if (!__execute($query, $options)) {
                    __execute('ROLLBACK', $options);
                    return false;
                }
            }
            
            if (!__execute('COMMIT', $options)) {
                return false;
            }

            echo '    '.__c()."\n";
            break;
            echo '-'.$method.'-'.$query_handler.'-'.count($queries).__LINE__."\n";
            echo '    '.__c()." : ";


            if (!__execute('START TRANSACTION', $options)) {
                return false;
            }

            if (!__execute(implode(';', $queries), $options)) {
                __execute('ROLLBACK', $options);
                return false;
            }

            if (!__execute('COMMIT', $options)) {
                return false;
            }
            
            echo '    '.__c()."\n";
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
        $queries = array();
    }

    // Return queries per second
    return $qps;
}
?>