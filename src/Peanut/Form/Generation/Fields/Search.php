<?php declare(strict_types=1);

namespace Peanut\Form\Generation\Fields;

class Search extends \Peanut\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);
        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }

        $html = <<<EOT
        <input type="text" class="form-control form-control-search" readonly="readonly" value="{$value}" />
        <input type="hidden" name="{$key}" value="{$value}" />
EOT;

        $button = <<<EOT
        <button class="btn btn-primary btn-search" type="button"><span class="glyphicon glyphicon-search"></span></button>
EOT;

        return [$html, $button];
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
