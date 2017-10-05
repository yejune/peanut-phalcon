<?php
namespace Peanut\Schema\Fields;

class HiddenField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $label    = $this->getLabel();
        $name     = $this->getName();
        $value    = $this->getValue();
        $id       = $this->getId();
        $required = $this->getRequired();

        $input = '';
        if (isset($this->schema->size)) {
            $input .= sprintf($this->input('hidden', $name, rtrim($id, '[]'), $value, $required, true));
        } else {
            $input = sprintf($this->input('hidden', $name, $id, $value, $required, false));
        }

        return $input;
        return sprintf($this->getStringHtml($label), $label, $input);
    }
}
