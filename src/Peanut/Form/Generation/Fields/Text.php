<?php declare(strict_types=1);

namespace Peanut\Form\Generation\Fields;

class Text extends \Peanut\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $html = <<<EOT
        <input type="text" class="form-control" name="{$key}" value="{$value}" />

EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (string) $value;
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
