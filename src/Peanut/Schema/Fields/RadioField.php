<?php declare(strict_types=1);

namespace Peanut\Schema\Fields;

class RadioField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $label    = $this->getLabel();
        $name     = $this->getName();
        $value    = $this->getValue();
        $id       = $this->getId();
        $required = $this->getRequired();

        $select = <<<EOT
<select class="form-control" name="%s" id="%s">
%s
</select>
EOT;

        $option = <<<OPT
<option value="%s" %s>%s</option>
OPT;
        $opt = '';

        foreach ($this->schema->items as $enumValue => $enumLabel) {
            if ($enumValue === $value) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            $opt .= \sprintf($option, $enumValue, $selected, $enumLabel);
        }

        $input = \sprintf($select, $name, $id, $opt);

        return \sprintf($this->getStringHtml($label), $label, $input);
    }
}
