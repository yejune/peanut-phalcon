<?php
namespace Peanut\Schema\Fields;

class TextField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $label       = $this->getLabel();
        $name        = $this->getName();
        $value       = $this->getValue();
        $id          = $this->getId();
        $required    = $this->getRequired();
        $placeholder = $this->getPlaceholder();
        /*
                $input = '';
                if (isset($this->schema->size)) {
                    $input .= sprintf($this->input('text', $name, rtrim($id, '[]'), $value, $required, true));
                } else {
                    $input = sprintf($this->input('text', $name, $id, $value, $required, false));
                }
        */
        $select = <<<EOT
<span class="input %s">
<input type="%s"  class="form-control" name="%s" id="%s" value="%s" %s %s />
%s
</span>
EOT;

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
            $dynamic = '';
            $class   = '';
            if (isset($this->schema->size)) {
                $dynamic = $this->getDynamic($isLast);
                $class   ='entry input-group';
            }
            //pr($select, $class, 'type', $name, rtrim($id, '[]').'_'.$i, $required ? 'required' : '', $dynamic);
            $input .= sprintf($select, $class, 'type', $name, rtrim($id, '[]').'_'.$i, $data, $required ? 'required' : '', $placeholder ? 'placeholder="'.$placeholder.'"' : '', $dynamic);
        }

        return sprintf($this->getStringHtml($label), $label, $input);
    }
}
