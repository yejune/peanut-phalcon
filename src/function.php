<?php

function pr()
{
    $trace = debug_backtrace()[0];
    echo '<pre xstyle="font-size:9px;font: small monospace;">';
    echo PHP_EOL.str_repeat('=', 100).PHP_EOL;
    echo 'file '.$trace['file'].' line '.$trace['line'];
    echo PHP_EOL.str_repeat('-', 100).PHP_EOL;

    if (1 === func_num_args()) {
        $args = func_get_arg(0);
    } else {
        $args = func_get_args();
    }

    echo prx($args);
    echo PHP_EOL.str_repeat('=', 100).PHP_EOL;
    echo '</pre>';
}

function prx($s)
{
    $a = [
        'Object'.PHP_EOL.' \*RECURSION\*'     => '#RECURSION',
        '    '                                => '  ',
        PHP_EOL.PHP_EOL                       => PHP_EOL,
        ' \('                                 => '(',
        ' \)'                                 => ')',
        '\('.PHP_EOL.'\s+\)'                  => '()',
        'Array\s+\(\)'                        => 'Array()',
        //' (=> Array|Object)'.PHP_EOL.'\s+\(' => ' $1(',
    ];

    $s = htmlentities(print_r($s, true));
    foreach ($a as $key => $val) {
        $s = preg_replace('#'.$key.'#', $val, $s);
    }

    return $s;
}

function html_encode($in)
{
    if (true === is_array($in)) {
        if (0 < count($in)) {
            $t = '<table border=1 cellspacing="0" cellpadding="0">';
            foreach ($in as $key => $value) {
                if (true === is_assoc($in)) {
                    if (true === is_array($value)) {
                        $t .= '<tr><td>'.$key.'</td><td>'.html_encode($value).'</td></tr>';
                    } else {
                        $t .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
                    }
                } else {
                    if (true === is_array($value)) {
                        $t .= '<tr><td>'.html_encode($value).'</td></tr>';
                    } else {
                        $t .= '<tr><td>'.$value.'</td></tr>';
                    }
                }
            }

            return $t.'</table>';
        }

        return '';
    }

    return $in;
}

function xobject2array($object)
{
    return json_decode(json_encode($object), true);
}

function object2array($data, $visited = [])
{
    if (!is_array($data) and !is_object($data)) {
        return $data;
    }
    if (is_object($data)) {
        // Detect object cycles, overwise recursion occurs.
        $hash = spl_object_hash($data);
        if (isset($visited[$hash])) {
            return '** RECURSION **';
        }
        $visited[$hash] = true;
        $data           = (array) $data;
    }
    $ret = [];
    foreach ($data as $key => $value) {
        if (is_object($value) || is_array($value)) {
            $value = object2array($value, $visited);
        }
        // Remove private and protected properties NULL delimited prefix.
        if ($key[0] === "\x00") {
            //$propertyName = substr($key, strpos($key, "\x0", 1));

            $propertyName = substr($key, 3);
        } else {
            $propertyName = $key;
        }
        $ret[$propertyName] = $value;
    }

    return $ret;
}

function decode_file($filename)
{
    if (false === file_exists($filename)) {
        throw new Exception($filename.' file not exists');
    }
    $contents = file_get_contents($filename);
    $ext      = pathinfo($filename, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'yaml':
        case 'yml':
            $result = yaml_parse($contents);
            break;
        case 'json':
            $result = json_decode($contents, true);
            if ($type = json_last_error()) {
                switch ($type) {
                    case JSON_ERROR_DEPTH:
                        $message = 'Maximum stack depth exceeded';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $message = 'Unexpected control character found';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $message = 'Syntax error, malformed JSON';
                        break;
                    case JSON_ERROR_NONE:
                        $message = 'No errors';
                        break;
                    case JSON_ERROR_UTF8:
                        $message = 'Malformed UTF-8 characters';
                        break;
                    default:
                        $message = 'Invalid JSON syntax';
                }
                throw new \Exception($filename.' '.$message);
            }
            break;
        default:
            throw new \Exception($ext.' not support');
            break;
    }

    return $result;
}

function is_assoc(array $array = [])
{
    $keys = array_keys($array);

    return $keys !== array_keys($keys);
}

function array_merge_recursive_distinct(array &$array1, array &$array2)
{
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
        if (is_array($value) && isset($merged [$key]) && is_array($merged [$key])) {
            $merged [$key] = array_merge_recursive_distinct($merged [$key], $value);
        } else {
            $merged [$key] = $value;
        }
    }

    return $merged;
}

function time_ago($time, $step = 1)
{
    if (true === is_string($time)) {
        $time = strtotime($time);
    }
    $time   = time() - $time;
    $time   = ($time < 1) ? 1 : $time;
    $tokens = [
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'min', //ute
        1        => 'sec', //ond
    ];

    $str = [];
    foreach ($tokens as $unit => $text) {
        if ($time < $unit) {
            continue;
        }
        $numberOfUnits = floor($time / $unit);

        $str[] = $numberOfUnits.' '.$text.(($numberOfUnits > 1) ? 's' : '');
        if (count($str) == $step) {
            return implode(' ', $str);
        }
        $time -= ($unit * $numberOfUnits);
    }

    return implode(' ', $str);
}

/**
 * @param $bytes
 * @param $decimals
 */
function readable_size($bytes, $decimals = 2)
{
    $size   = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$size[$factor];
}

function getDatetime($float)
{
    if (true === is_float($float)) {
        $date = \DateTime::createFromFormat('U.u', $float);
        if ($date) {
            $date->setTimezone(new \DateTimeZone('Asia/Seoul'));

            return $date->format('Y-m-d\TH:i:s.uP');
        }

        //return $date->format('Y-m-d\TH:i:s.u\Z');
    }

    return '';
}
