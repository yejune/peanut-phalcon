<?php
namespace Peanut\Parser;

class Yml
{
    public static function parse($path, $mix = [])
    {
        $arr = yaml_parse(file_get_contents($path));

        $arr = static::_parse($arr);
        if ($mix) {
            $arr = static::mix($arr, $mix);
        }

        return $arr;
    }
    public static function mix(array $array1 = [], array $array2 = [])
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = static::mix($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
    private static function _parse($arr = []) : array
    {
        $return = [];
        foreach ($arr as $key => $value) {
            if ($key === '$ref') {
                if (false === is_array($value)) {
                    $value = [$value];
                }
                $data = [];
                foreach ($value as $path) {
                    $path =  __BASE__.'/app/Specs/'.$path.'';
                    $yml  = static::parse($path);
                    if (true === isset($yml['properties'])) {
                        $data = array_merge($data, $yml['properties']);
                    }
                }
                $yml = static::_parse($data);

                $return = array_merge($return, $yml);
            } elseif (true === is_array($value)) {
                $return[$key] = static::_parse($value);
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }
}
