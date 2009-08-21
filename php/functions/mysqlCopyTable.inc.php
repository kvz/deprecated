<?php
/**
 * Copies a MySQL Table
 *
 * @param <type> $source
 * @param <type> $dest
 * @param <type> $options
 * 
 * @return <type>
 */
function mysqlCopyTable($source, $dest, $options) {
    if (!isset($options['data'])) $options['data'] = false;
    if (!isset($options['dropfirst'])) $options['dropfirst'] = false;
    if (!isset($source['connection'])) $source['connection'] = null;
    if (!isset($dest['connection'])) $dest['connection'] = null;

    $sql = sprintf('SHOW CREATE TABLE %s', $source['tableName']);
    if ($source['connection']) {
        if (!($res = mysql_query($sql, $source['connection']))) return false;
    } else {
        if (!($res = mysql_query($sql))) return false;
    }
    
    $row      = mysql_fetch_assoc($res);
    $destSqls = array();

    if ($options['dropfirst'] === true) {
        $destSqls[] = sprintf("DROP TABLE IF EXISTS %s;",
            $dest['tableName']);
    }

    $destSqls[] = str_replace('`'.$row['Table'].'`', $dest['tableName'],
        $row['Create Table']).";";

    if ($options['data'] === true) {
        $destSqls[] = sprintf("INSERT INTO %s SELECT * FROM %s;",
            $dest['tableName'], $source['tableName']);
    }

    $start = microtime(true);
    foreach($destSqls as $destSql) {
        if ($dest['connection']) {
            if (!mysql_query($destSql, $dest['connection'])) return false;
        } else {
            if (!mysql_query($destSql)) return false;
        }
    }

    return round(microtime(true) - $start, 4);
}
?>