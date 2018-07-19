<?php
namespace Peanut\Schema\Fields\Group;

class TextField extends \Peanut\Schema\Fields
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
            $input .= sprintf($this->input('text', $name, preg_replace('#\[\]$#', '', $id), $value, $required, true));
        } else {
            $input = sprintf($this->input('text', $name, $id, $value, $required, false));
        }

        $input = <<<EOD
        <span class="input-group-btn">
<input type="text" class="form-control" name="$name" id="$id" value="" aria-invalid="true">
        </span>
EOD;

        return $input;
        return sprintf($this->getStringHtml($label), $label, $input);
    }
}
