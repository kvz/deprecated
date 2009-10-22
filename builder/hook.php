#!/usr/bin/php
<?php
// General CLI arguments
$script = array_shift($argv);
$class  = array_shift($argv);
$method = array_shift($argv);

$buildFile = dirname(__FILE__).'/build.php';

switch($class) {
    case 'User':
        switch($method) {
            case 'setPassword':
                // Specific CLI arguments
                $user = array_shift($argv);
                $pass = array_shift($argv);

                $cmd = sprintf('sudo %s --setpassword %s:%s',
                    $buildFile,
                    $user,
                    $pass);

                echo $cmd."\n";
                exec($cmd);
                
                break;
            default:
                trigger_error(sprintf('No handler for class: "%s", method: "%s"', $class, $method), E_USER_ERROR);
                exit(1);
                break;
        }
        break;
    default:
        trigger_error(sprintf('No handler for class: "%s"', $class), E_USER_ERROR);
        exit(1);
        break;
}
?>