<?php
namespace Peanut;

class Text
{
    public static function pluralize($singular)
    {
        $last = strtolower($singular[strlen($singular) - 1]);
        switch ($last) {
            case 'y':
                return substr($singular, 0, -1).'ies';
            case 's':
                return $singular.'es';
            default:
                return $singular.'s';
        }
    }
    public static function camelize($value)
    {
        return \Phalcon\Text::camelize($value);
    }

    public static function lcfirstCamelize($value)
    {
        return lcfirst(\Phalcon\Text::camelize($value));
    }
}
