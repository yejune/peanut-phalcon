<?php declare(strict_types=1);

namespace Peanut\Form\Generation\Fields;

class File extends \Peanut\Form\Generation\Fields
{
    public static function write($key, $property, $data)
    {
        if (true === \Peanut\is_file_array($data, false)) {
            $value  = \htmlspecialchars((string) $data['name']);
            $accept = $property['rules']['accept'] ?? '';
            $html   = <<<EOT
            <input type="text" class="form-control file" name="{$key}" value="{$value}" accept="{$accept}" readonly="readonly" />

EOT;
        } else {
            $value  = '';
            $accept = $property['rules']['accept'] ?? '';
            $html   = <<<EOT
            <input type="text" class='form-control btn-text' value="선택된 파일 없음" readonly="readonly" />
            <input type="file" name="{$key}" value="{$value}" accept="{$accept}" />

            <span class="input-group-btn xbtn-default btn-file">
                <button class="btn btn-primary type="button"><span class="glyphicon glyphicon-search"></span></button>
            </span>
EOT;
        }

        return $html;
    }

    public static function read($key, $property, $data)
    {
        $html = '';

        if (true === \Peanut\is_file_array($data, false)) {
            $value = \str_replace(__PUBLIC__, '', (string) $data['path']);
            $html  = <<<EOT
            <img src="{$value}" />

EOT;
        }

        return $html;
    }
}
