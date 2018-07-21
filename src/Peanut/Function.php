<?php
namespace Peanut;

/**
 * debug용 print_r
 *
 * @return void
 */
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
    echo print_x($args);
    echo PHP_EOL.str_repeat('=', 100).PHP_EOL;
    echo '</pre>';
}

/**
 * beautify print_r
 *
 * @param  mixed $args
 * @return string
 */
function print_x($args)
{
    $a = [
        'Object'.PHP_EOL.' \*RECURSION\*' => '#RECURSION',
        '    '                            => '  ',
        PHP_EOL.PHP_EOL                   => PHP_EOL,
        ' \('                             => '(',
        ' \)'                             => ')',
        '\('.PHP_EOL.'\s+\)'              => '()',
        'Array\s+\(\)'                    => 'Array()',
        '\s+(Array|Object)\s+\('          => ' $1(',
    ];
    $args = htmlentities(print_r($args, true));
    foreach ($a as $key => $val) {
        $args = preg_replace('#'.$key.'#X', $val, $args);
    }

    return $args;
}

/**
 * 배열을 html table로 반환
 * @param mixed $in
 *
 * @return string
 */
function html_encode(array $in) : string
{
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

/**
 * 배열의 키가 숫자가 아닌 경우를 판별
 *
 * @param array $array
 *
 * @return boolean
 */
function is_assoc($array)
{
    if (true === is_array($array)) {
        $keys = array_keys($array);

        return $keys !== array_keys($keys);
    }

    return false;
}

/**
 * file을 읽어 확장자에 따라 decode하여 리턴
 *
 * @param  string $filename
 *
 * @return string
 */
function decode_file(string $filename) : array
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

/**
 * recursive array를 unique key로 merge
 * @param array $array1  초기 배열
 * @param array $array2  병합할 배열
 *
 * @return array
 */
function array_merge_recursive_distinct(array $array1, array $array2) : array
{
    $merged = $array1;
    foreach ($array2 as $key => &$value) {
        if (true === is_array($value) && true === isset($merged[$key]) && true === is_array($merged[$key])) {
            $merged[$key] = array_merge_recursive_distinct($merged [$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }

    return $merged;
}

/**
 * time으로부터 지난 시간을 문자열로 반환
 *
 * @param string|int $time   시간으로 표현가능한 문자열이나 숫자
 * @param int        $depth  표현 깊이
 *
 * @return string
 */
function time_ago($time, int $depth = 1) : string
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
    $parts = [];
    foreach ($tokens as $unit => $text) {
        if ($time < $unit) {
            continue;
        }
        $numberOfUnits   = floor($time / $unit);
        $parts[]         = $numberOfUnits.' '.$text.(($numberOfUnits > 1) ? 's' : '');
        if (count($parts) == $depth) {
            return implode(' ', $parts);
        }
        $time -= ($unit * $numberOfUnits);
    }

    return implode(' ', $parts);
}

/**
 * 숫자를 읽기쉬운 문자열로 변환
 *
 * @param $bytes
 * @param $decimals
 *
 * @return string
 */
function readable_size($bytes, $decimals = 2) : string
{
    $size   = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$size[$factor];
}

/**
 * formatting ISO8601MICROSENDS date
 *
 * @param  float  $float  microtime
 *
 * @return string
 */
function iso8601micro(float $float) : string
{
    $date = \DateTime::createFromFormat('U.u', $float);
    $date->setTimezone(new \DateTimeZone('Asia/Seoul'));

    return $date->format('Y-m-d\TH:i:s.uP');
}

/**
 * Generate a unique ID
 *
 * @param int $length
 *
 * @return string
 */
function uniqid(int $length = 13) : string
{
    if (function_exists('random_bytes')) {
        $bytes = random_bytes(ceil($length / 2));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
    } else {
        $bytes = md5(mt_rand());
    }

    return substr(bin2hex($bytes), 0, $length);
}

/**
 * env to array
 */
function env_to_array(string $envPath) : array
{
    $variables = [];
    $lines     = explode("\n", trim(file_get_contents($envPath)));
    if ($lines) {
        foreach ($lines as $line) {
            if ($line) {
                list($key, $value) = explode('=', $line, 2);
                $variables[$key]   = trim($value, '"\'');
            }
        }
    }

    return $variables;
}
