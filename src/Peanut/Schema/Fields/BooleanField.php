<?php
namespace Peanut\Schema\Fields;

class BooleanField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $label       = $this->getLabel();
        $name        = $this->getName();
        $value       = $this->getValue();
        $id          = $this->getId();
        $required    = $this->getRequired();
        $description = $this->getDescription();

        $select = <<<EOT
<label class="group">
<input type=checkbox name="%s" id="%s" value=1 %s %s />
%s
</label>
EOT;
        if (strlen($value) && 1 == $value) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        $input = sprintf($select, $name, $id, $checked, $required, $description);

        return sprintf($this->getStringHtml($label), $label, $input);
    }
}
