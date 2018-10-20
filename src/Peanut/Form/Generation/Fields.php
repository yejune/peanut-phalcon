<?php declare(strict_types=1);

namespace Peanut\Form\Generation;

class Fields
{
    public static function getMultipleHtml($key)
    {
        return '<span class="input-group-btn wrap-btn-plus" data-uniqid="' . $key . '"><button class="btn btn-success btn-plus" type="button"><span class="glyphicon glyphicon-plus"></span></button></span>';
    }

    public static function getKey(string $key, string $id) : string
    {
        return \str_replace('[]', '[' . $id . ']', $key);
        // return \preg_replace_callback('#\[\]#', function($match) {
        //     return '[' . static::getUniqueId() . ']';
        // }, $key);
    }

    public static function getUniqueId()
    {
        return '__' . \uniqid() . '__';
    }

    // arr[arr[]] 형태를 arr[arr][]로 교정
    public static function fixKey(string $key) : string
    {
        $arrCount = \substr_count($key, '[]');

        return '[' . \str_replace('[]', '', $key) . ']' . \str_repeat('[]', $arrCount);
    }

    public static function fixKey2(string $key) : string
    {
        return '[' . \str_replace('[]', '', $key) . ']';
    }

    public static function getLanguage()
    {
        return \getLanguage();
    }

    public static function getValue($data, $key)
    {
        $keys  = \explode('[', \str_replace([']'], '', \str_replace('[]', '', $key)));
        $value = $data;
        //pr($key, $value);
        foreach ($keys as $id) {
            if (true === isset($value[$id])) {
                $value = $value[$id];

                continue;
            }

            return '';
        }

        return $value;
    }

    public static function addElement($html, int $index = 1)
    {
        if (1 < $index) {
            $html .= '<span class="input-group-btn clone"><button class="btn btn-danger btn-minus" type="button"><span class="glyphicon glyphicon-minus"></span></button></span>';
        }
        $html = '<div class="wrap-element ' . (1 < $index ? 'clone-element' : '') . '">' . $html . '</div>';

        return $html;
    }

    public static function readElement($html, int $index = 1)
    {
        $html = '<div class="wrap-element ' . (1 < $index ? 'clone-element' : '') . '">' . $html . '</div>';

        return $html;
    }
}
