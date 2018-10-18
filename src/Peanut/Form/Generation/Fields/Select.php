<?php declare(strict_types=1);

namespace Peanut\Form\Generation\Fields;

class Select extends \Peanut\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        if(strlen($value) == 0) {
            $value = $property['default'] ?? '';
        }

        $value = \htmlspecialchars((string) $value);

        $option = '';

        if (true === isset($property['items'])) {
            foreach ($property['items'] as $itemValue => $itemText) {
                $itemValue = \htmlspecialchars((string) $itemValue);

                if ($value === $itemValue) {
                    $option .= '<option value="' . $itemValue . '" selected="selected">' . $itemText . '</option>';
                } else {
                    $option .= '<option value="' . $itemValue . '">' . $itemText . '</option>';
                }
            }
        } else {
            $option = '<option value="">select</option>';
        }

        $html = <<<EOT
        <select class="form-control" name="{$key}">{$option}</select>

EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        if(strlen($value) == 0) {
            $value = $property['default'] ?? '';
        }

        $value = \htmlspecialchars((string) $value);

        $option = '';

        if (true === isset($property['items'])) {
            foreach ($property['items'] as $itemValue => $itemText) {
                $itemValue = \htmlspecialchars((string) $itemValue);

                if ($value === $itemValue) {
                    $option .= $itemText;
                }
            }
        }

        $html = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
