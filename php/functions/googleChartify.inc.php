<?php
/**
 * Takes fixed width formatted data, uses 1st numeric column for X-axis
 * value. Uses following numeric colums for multiple lines on Y-axis
 * and returns a valid Google Chart URL.
 *
 * So data basically needs to be:
 * [] = array(x, y, y, y, y);
 * [] = array(x, y, y, y, y);
 * [] = array(x, y, y, y, y);
 * [] = array(x, y, y, y, y);
 * [] = array(x, y, y, y, y);
 * (In your head, turn this data 90 degrees anti-clokwise; and you get the picture)
 *
 * @param string $data
 * @param array $options
 * 
 * @return string
 */
function googleChartify($data, $options = array()) {
    if (!isset($options['scale'])) $options['scale'] = null;
    if (!isset($options['debug'])) $options['debug'] = false;
    if (!isset($options['urlencode'])) $options['urlencode'] = true;
    if (!isset($options['needColumnCnt'])) $options['needColumnCnt'] = null;
    if (!isset($options['takeColumns'])) $options['takeColumns'] = '*';
    if (!isset($options['cht'])) $options['cht'] = 'lc';
    if (!isset($options['chtt'])) $options['chtt'] = 'Comparison';
    #if (!isset($options['chco'])) $options['chco'] = '76A4FB,800080,4FFF88,FF5FDE,5FFFB1,FFA65F';
    if (!isset($options['chco'])) $options['chco'] = '22FF22,0022FF,FF0000,00AAAA,FF00FF,FFA500,CC0000,0000CC,0080C0,8080C0,FF0080,800080,688E23,408080,808000,000000';
    if (!isset($options['chs'])) $options['chs'] = '570x250';
    if (!isset($options['chdl'])) $options['chdl'] = null;
    if (!isset($options['chxl'])) $options['chxl'] = null;
    if (!isset($options['chxt'])) $options['chxt'] = 'x,y,t,r';

    extract($options);

    // Hack: Convert array to plaintext
    if (is_array($data)) {
        foreach($data as $k=>$v) {
            if (is_array($v)) {
                $data[$k] = join('   ', $v);
            }
        }
        $data = join("\n", $data)."\n";
    }

    // Parse data, build matrix
    $rows   = explode("\n", $data);
    $lines  = array();
    $maxVal = 0;
    foreach ($rows as $row) {
        $row = preg_replace('/[^\d\.\,\-]/', ' ', $row);
        if (!trim($row)) continue;

        if ($debug) {
            echo $row."\n";
        }

        $columns = preg_split('/\s\s+/si', $row, -1, PREG_SPLIT_NO_EMPTY);
        $gotColumnCnt = count($columns);
        if ($needColumnCnt === null) {
            $needColumnCnt = $gotColumnCnt;
        } elseif ($gotColumnCnt !== $needColumnCnt) {
            echo 'Column count incorrect for row: '.$row."\n";
            continue;
        }

        // Take time part
        $key = array_shift($columns);
        foreach($columns as $i=>$column) {
            // Take data colums we want
            if ($takeColumns == '*' || in_array(($i+1), $takeColums)) {
                $lines[$key][] = $column;
                if ($column > $maxVal) {
                    $maxVal = $column;
                }
            }
        }
    }

    if ($debug) {
        print_r($lines);
    }

    // Calculate scale
    if ($scale === null) {
        // Highest value
        $scale = $maxVal;

        // Round up with zeros
        $scale = (substr($scale, 0, 1)+1). str_repeat('0', strlen($scale) - 1);
        if ($debug) {
            echo 'AutoSet scale to: '.$scale."\n";
        }
    }

    // Squash data (chd)
    $chd   = array();
    $modes = array();
    $xcount = count($lines);
    foreach($lines as $x => $columns) {
        foreach ($columns as $mode=>$column) {
            if (empty($chd[$mode])) $chd[$mode] = array();
            $modes[$mode] = chr(65+$mode);
            $chd[$mode][] = round($column / $scale * 100, 2);
        }
    }

    foreach($chd as $mode=>$columns) {
        $chd[$mode] = implode(',', $columns);
    }
    $chd = 't:'.implode('|', $chd);

    // chdl
    if ($chdl === null) {
        $chdl = array();
        foreach($modes as $modeInt=>$modeAlpha) {
            $chdl[] = 'Mode '.$modeAlpha;
        }
        $chdl = implode('|', $chdl);
    }

    // chxl
    if ($chxl === null) {
        $chxl = array();

        // 0
        $chxl[0] = array_keys($lines);
        foreach($chxl[0] as $k=>&$v) {
            if (strlen($v) >= 5) {
                $v = substr($v, 0, strlen($v) -3).'k';
            }
        }
        unset($v);
        $chxl[0] = implode('|', $chxl[0]);

        // 1
        $chxl[1] = array();

        $scaleStepSize = $scale/10;

        $cnt = 0;
        for ($i = 0; $i<=$scale; $i=$i+$scaleStepSize) {
            $cnt++;
            if ($i === 0 || $cnt % 2 === 0) {
                $chxl[1][] = '';
            } else {
                $chxl[1][] = $i;
            }
        }
        $chxl[1] = implode('|', $chxl[1]);

        // 2
        $l = floor($xcount/2);
        $r = ceil($xcount/2);
        if (isset($chxl2) && substr_count($chxl2, '|') > 2) {
            $chxl[2] = $chxl2;
        } else {
            $chxl[2] = str_repeat('|', $l) . (isset($chxl2) ? $chxl2 : 'Y line') . str_repeat('|', $r);
        }

        // 3
        if (isset($chxl3) && substr_count($chxl3, '|') > 3) {
            $chxl[3] = $chxl3;
        } else {
            $chxl[3] = str_repeat('|', $l) . (isset($chxl3) ? $chxl3 : 'X line') . str_repeat('|', $r);
        }

        $chxl = '0:|'.$chxl[0].'|'.'1:|'.$chxl[1].'|'.'2:|'.$chxl[2].'|'.'3:|'.$chxl[3].'|';
    }

    // Paremeterize
    $params = compact('chd', 'chdl', 'chxl');
    foreach ($options as $key=>$val) {
        if (substr($key, 0, 2) === 'ch' && !isset($params[$key])) {
            $params[$key] = $val;
        }
    }

    $url = 'http://chart.apis.google.com/chart?';
    if ($urlencode) {
        $url .= http_build_query($params, null, "\n".'&');
    } else {
        $url .= urldecode(http_build_query($params, null, "\n".'&'));
    }

    return $url."\n";
}
?>