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
function mysqlBulk(&$queries, $method = 'concatenation', $options = array()) {
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
            trigger_error('It\'s recommended to use < 1000 queries at once',
                E_USER_NOTICE);
        }
    }
    if (empty($queries)) {
        return 0;
    }

    // Start timer
    $start = microtime(true);
    $count = count($queries);

    // Choose bulk method
    switch ($method) {
        case 'concatenation':
            // max 1200% gain
            call_user_func($options['query_handler'],
                implode(';', $queries));
            break;
        case 'delayed':
            // MyISAM, MEMORY, ARCHIVE, and BLACKHOLE tables only!
            call_user_func($options['query_handler'],
                str_replace(';INSERT', ';INSERT DELAYED',
                    implode(';', $queries)));
            break;
        case 'transaction':
            // max 26% gain, but good for data integrity
            call_user_func($options['query_handler'], 
                'START TRANSACTION');

            foreach ($queries as $query) {
                if (!call_user_func($options['query_handler'], $query)) {
                    if ($method === 'commit') {
                        call_user_func($options['query_handler'],
                            'ROLLBACK');
                    }
                    if ($options['trigger_errors']) {
                        trigger_error('Query failed. Transaction cancelled.',
                            E_USER_WARNING);
                    }
                    return false;
                }
            }

            call_user_func($options['query_handler'],
                'COMMIT');
            break;
        case 'concat_trans':
            // max 26% gain, but good for data integrity
            call_user_func($options['query_handler'],
                'START TRANSACTION');

            call_user_func($options['query_handler'],
                implode(';', $queries));

            call_user_func($options['query_handler'],
                'COMMIT');
            break;
        default:
            // Unknown bulk method
            if ($options['trigger_errors']) {
                trigger_error('Unknown bulk method: "'.$method.'"',
                    E_USER_ERROR);
            }
            return false;
            break;
    }

    // Stop timer
    $duration = microtime(true) - $start;
    $qps      = round ($count / $duration, 2);

    if ($options['eat_away']) {
        $queries = array();
    }

    // Return queries per second
    return $qps;
}
?>