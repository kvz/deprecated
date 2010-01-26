<?php
/**
 * Wrapper for the nmap network scanner
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $ips   = array('74.125.67.100');
 * $ports = array(21, 80, 443);
 *
 * // Execute //
 * $output[0] = nmap($ips, $ports);
 * $output[1] = nmap($ips, $ports, array('output_hostnames' => true, 'output_state' => 1));
 * $output[2] = nmap($ips, $ports, array('output' => 'dec'));
 *
 * // Show //
 * print_r($output[0]);
 * print_r($output[1]);
 * print_r($output[2]);
 *
 * // expects:
 * // Array
 * // (
 * //     [74.125.67.100] => Array
 * //         (
 * //             [21] => down
 * //             [80] => up
 * //             [443] => up
 * //         )
 * //
 * // )
 * // Array
 * // (
 * //     [gw-in-f100.1e100.net] => Array
 * //         (
 * //             [21] => 0
 * //             [80] => 1
 * //             [443] => 1
 * //         )
 * //
 * // )
 * // Array
 * // (
 * //     [74.125.67.100] => 3
 * // )
 * </code>
 *
 * @param array $argIps
 * @param array $argPorts
 * @param array $options
 *
 * @return array
 */

function nmap($argIps, $argPorts = array(21, 25, 22, 110, 143, 80, 443, 1433, 3306, 3389), $options = array()) {
    // Defaults
    $defaults = array(
        'aggro' => 5,
        'parallel_hosts' => 8,
        'parallel_probes' => 4,
        'input_hostnames' => false,
        'output_hostnames' => false,
        'output_state' => true,
        'output' => 'tree',
    );
    foreach ($defaults as $key=>$val) {
        if (!array_key_exists($key, $options)) {
            $options[$key] = $val;
        }
    }

    if ($options['output'] === 'dec') {
        // Force binary states if you want a hosts with
        // decimal output
        $options['output_state'] = 1;
    }

    // Init Params
    if (is_string($argIps)) {
        $argIps = str_replace(' ', '', $argIps);
        if (false !== strpos($argIps, ',')) {
            $argIps = explode(",", $argIps);
        } else {
            $argIps = explode("\n", $argIps);
        }
    }
    if (is_string($argPorts)) {
        $argPorts = str_replace(' ', '', $argPorts);
        $argPorts = explode(',', $argPorts);
    }
    $states = array(
        'closed' => 'closed',
        'filtered' => 'filtered',
        'open' => 'open',
    );
    if ($options['output_state'] === true) {
        $states = array(
            'closed' => 'down',
            'filtered' => 'down',
            'open' => 'up',
        );
    } elseif ($options['output_state'] == 1) {
        $states = array(
            'closed' => '0',
            'filtered' => '0',
            'open' => '1',
        );
    }

    // We Process with Raw numbers
    // So you can input hostnames, but then we first want to convert them
    if ($options['input_hostnames']) {
        foreach ($argIps as $i=>$ip) {
            $argIps[$i] = gethostbyname($ip);
        }
    }

    // Scan
    $nmap    = 'echo "%s" | /usr/bin/nmap -T%s -sT -n --min_parallelism %s --min_hostgroup %s -oX - -iL - -PS%s -p%s';
    $nmap   .= '|egrep -v \'^#\'';
    $cmd     = sprintf($nmap,
        join("\n", $argIps),
        $options['aggro'],
        $options['parallel_hosts'],
        $options['parallel_probes'],
        join(',', $argPorts),
        join(',', $argPorts));

    $output   = array();
    $lastline = exec($cmd, $output, $return_var);
    if ($return_var !== 0) {
        trigger_error(sprintf('Command %s failed: %s', $cmd, $lastline),
            E_USER_WARNING);
        return false;
    }

    if (!($Xml = simplexml_load_string(join("\n", $output)))) {
        trigger_error(sprintf('Could not parse XML: %s', $output),
            E_USER_WARNING);
        return false;
    }

    // Save Results
    $onlineIps = array();
    $scan      = array();
    foreach($Xml->host as $Host) {
        foreach($Host->ports->port as $Port) {
            $ip = (string)$Host->address['addr'];
            $onlineIps[] = $ip;
            $scan[$ip][(string)$Port['portid']] = $states[(string)$Port->state['state']];
        }
    }

    // Save Offline Hosts
    $offlineIps = array_diff($argIps, $onlineIps);
    foreach ($offlineIps as $i=>$ip) {
        // All ports are just down,
        // that's how we treat them
        foreach ($argPorts as $j=>$port) {
            $scan[$ip][$port] = $states['closed'];
        }
    }

    // Resolve IPs ourselves. Slow but steady & more flexible.
    // shouldn't use resolving anyway.
    if ($options['output_hostnames']) {
        foreach ($scan as $ip=>$ports) {
            #pr($ip);
            $host = gethostbyaddr($ip);
            unset($scan[$ip]);
            $scan[$host] = $ports;
        }
    }

    // Change output
    $output = array();
    switch ($options['output']) {
        case 'text_rows':
            foreach ($scan as $ip=>$ports) {
                // Use argPorts to maintain order. May be important for
                // end-user
                foreach ($argPorts as $j=>$port) {
                    $state = $ports[$port];
                    $output[] = $ip . ' ' . $port . ' ' . $state;
                }
            }
            break;
        case 'dec':
            foreach ($scan as $ip=>$ports) {
                // Use argPorts to maintain order. May be important for
                // end-user
                $onezeros = '';
                foreach ($argPorts as $j=>$port) {
                    $state     = $ports[$port];
                    $onezeros .= $state;
                }

                $output[$ip] = bindec($onezeros);
            }
            break;
        default:
            $output = $scan;
            break;
    }

    return $output;
}
?>