#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/init.inc.php';
extract($config);

// General CLI arguments
$script = array_shift($argv);
$class  = array_shift($argv);
$method = array_shift($argv);

switch($class) {
    case 'User':
        switch($method) {
            case 'setPassword':
                // Specific CLI arguments
                $user = array_shift($argv);
                $pass = array_shift($argv);
                
                exe('sudo %s --setpassword %s:%s',
                    $config['buildFile'],
                    $user,
                    $pass);
                
                break;
            default:
                err('No handler for class: %s, method: %s', $class, $method);
                exit(1);
                break;
        }
        break;
    default:
        err('No handler for class: %s', $class);
        exit(1);
        break;
}
?>