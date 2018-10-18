<?php declare(strict_types=1);

namespace Peanut\Form\Generation\Fields;

class Textarea extends \Peanut\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);
        $html  = <<<EOT
        <textarea class="form-control" name="{$key}">{$value}</textarea>

EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = \nl2br((string) $value);
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
