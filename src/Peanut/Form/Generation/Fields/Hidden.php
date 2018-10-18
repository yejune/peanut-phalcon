<?php declare(strict_types=1);

namespace Peanut\Form\Generation\Fields;

class Hidden extends \Peanut\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value) || '0';
        $value = 0;
        $html  = <<<EOT
    <input type="text" class="form-control" readonly="readonly" name="{$key}" value="{$value}" />
EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        return $html = '';
    }
}
