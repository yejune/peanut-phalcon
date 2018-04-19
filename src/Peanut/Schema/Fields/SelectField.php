<?php
namespace Peanut\Schema\Fields;

class SelectField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $label    = $this->getLabel();
        $name     = $this->getName();
        $value    = $this->getValue();
        $id       = $this->getId();
        $required = $this->getRequired();

        $select = <<<EOT
<span class="input %s">
<select class="form-control" name="%s" id="%s">
%s
</select>
%s
</span>
EOT;

        $option = <<<OPT
<option value="%s" %s>%s</option>
OPT;

        if (false === is_array($value)) {
            $value = [$value];
        }

        $input = '';

        $j     = -1;
        $count = count($value);

        foreach ($value as $i => $data) {
            $j ++;
            $isLast                      = false;
            if($j == 0 && 1 == $count) { // create empty
                $isLast = 0;
            } elseif ($j + 1 == $count) {
                $isLast = true;
            }
            $opt = '';
            //pr($this);
            foreach ($this->schema->items as $enumValue => $enumLabel) {
                if ($enumValue == $data) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }
                $opt .= sprintf($option, $enumValue, $selected, $enumLabel);
            }

            $dynamic = '';
            $class   = '';
            if (isset($this->schema->size)) {
                $dynamic = $this->getDynamic($isLast);
                $class   ='entry input-group';
            }

            $input .= sprintf($select, $class, $name, rtrim($id, '[]').'_'.$i, $opt, $dynamic);
        }

        return sprintf($this->getStringHtml($label), $label, $input);
    }
}
