<?php
/**
 * Pipe queries to this program and get returned the table names used,
 * comma seperated.
 */
if (!include_once('SQL/Parser.php')) {
    echo "You need to: \n";
    echo "    aptitude install php-pear\n";
    echo "    pear install -f SQL_Parser\n";
    exit(1);
}


$stdin = fopen('php://stdin', 'r');
$sql   = '';
while(!feof($stdin)) {
    $sql .= fgets($stdin);
}
fclose($stdin);


$sql        = str_replace('`', '', $sql);
$SQL_Parser = new SQL_Parser($sql);
$parsed     = $SQL_Parser->parse();
echo join(', ', $parsed['table_names'])."\n";
?>