<?php

namespace Peanut\Parser;

class Spec
{
    public static $baseUrl = '';

    public static function parse($path, $mix = [], $mix2 = [])
    {
        if (true === \is_array($path)) {
            $arr = $path;
        } else {
            if (true === \file_exists($path)) {
                $yml = \file_get_contents($path);
            } else {
                $yml = $path;
            }

            if (!static::$baseUrl) {
                static::$baseUrl = \dirname($path) . '/';
            }

            $arr = \yaml_parse($yml."\n");

            $arr = static::_parse($arr);

            if ($mix) {
                $arr = static::mix($arr, $mix);
            }

            if ($mix2) {
                $arr = static::mix($arr, $mix2);
            }
        }

        return $arr;
    }

    public static function mix(array $array1 = [], array $array2 = [])
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (\is_array($value) && isset($merged[$key]) && \is_array($merged[$key])) {
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
            if ('$ref' === $key) {
                if (false === \is_array($value)) {
                    $value = [$value];
                }
                $data = [];

                foreach ($value as $path) {
                    if (0 !== \strpos($path, '/')) {
                        $path = static::$baseUrl . $path . '';
                    }
                    $yml = static::parse($path);

                    if (true === isset($yml['properties'])) {
                        $data = \array_merge($data, $yml['properties']);
                    }
                }
                $yml = static::_parse($data);

                $return = \array_merge($return, $yml);
            } elseif (true === \is_array($value)) {
                $return[$key] = static::_parse($value);
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }
}
