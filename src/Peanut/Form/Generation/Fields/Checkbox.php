<?php declare(strict_types=1);

namespace Peanut\Form\Generation\Fields;

class Checkbox extends \Peanut\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $checked = ((bool) $value) ? 'checked' : '';

        $showhide = '';
        if(true === isset($property['showhide'])) {
            $showhide = <<<EOT
onclick="showhide(this, '{$property['showhide']}')"
EOT;
        }
        $html  = <<<EOT
        <input type="checkbox" class="form-control" name="{$key}" value="1" {$checked} ${showhide} />

EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (bool) $value;
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
