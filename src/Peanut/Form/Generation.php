<?php declare(strict_types=1);

namespace Peanut\Form;

class Generation
{
    public function __construct()
    {
    }

    public function write(array $spec, array $data = []) : string
    {
        $method = __NAMESPACE__ . '\\Generation\\Fields\\' . \ucfirst($spec['type']);
        $html = '';
        if(true === isset($spec['label'][\Peanut\get_language()] )) {
            $title = $spec['label'][\Peanut\get_language()] ;
        } else if(true === isset($spec['label'])) {
            $title = $spec['label'];
        } else {
            $title = 'Form';
        }
        $html = '<label>'.$title.'</label>';

        if(true === isset($spec['description'][\Peanut\get_language()] )) {
            $description = $spec['description'][\Peanut\get_language()] ;
        } else if(true === isset($spec['description'])) {
            $description = $spec['description'];
        } else {
            $description = '';
        }
        if($description) {
            $html .= '<p>'.$description.'</p>';
        }

        $elements = $method::write($spec['key'] ?? '', $spec, $data);

        $innerhtml = <<<EOT
<div>
{$html}
{$elements}
</div>
EOT;

        return $innerhtml;
    }

    public function read(array $spec, array $data = []) : string
    {
        $method = __NAMESPACE__ . '\\Generation\\Fields\\' . \ucfirst($spec['type']);

        if(true === isset($spec['label'][\Peanut\get_language()] )) {
            $title = $spec['label'][\Peanut\get_language()] ;
        } else if(true === isset($spec['label'])) {
            $title = $spec['label'];
        } else {
            $title = 'Form';
        }

        $elements = $method::read($spec['key'] ?? '', $spec, $data);

        $innerhtml = <<<EOT
<div>
<label>{$title}</label>
{$elements}
</div>
EOT;

        return $innerhtml;
    }
}
