<?php
namespace Peanut\Schema\Fields;

class FileField extends \Peanut\Schema\Fields
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
<input type="%s"  class="form-control file" name="%s" id="%s" value="%s" %s %s />
%s
%s
</span>
EOT;

        if (false === is_array($value)) {
            $value = [$value];
        }
        if(0 === count($value)) {
            $value = [null];
        }
        $input = '';

        $j     = -1;
        $count = count($value);

        foreach ($value as $i => $data) {
            $j ++;
            $isLast                      = false;
            $type = 'text';
            if($j == 0 && 1 == $count) { // create empty
                if (!$data) {
                    $type = 'file';
                }
                $isLast = 0;
            } elseif ($j + 1 == $count) {
                $isLast = true;
            }
            $dynamic                     = '';
            $class                       = '';
            if (isset($this->schema->size)) {
                $dynamic = $this->getDynamic($isLast);
                $class   ='entry input-group';
            }
            if (false !== strpos($name, '[]')) {
                $cname=rtrim($name, '[]').'['.$i.']';
            } else {
                $cname = $name;
            }
            $src = '';
            if(0 === strpos($data['type'], 'image/')) {
                $src = '<img class="preview" src="'.$data['url'].'" width="100">';
            }
            $value = $data['url'];
            //pr($select, $class, 'type', $name, rtrim($id, '[]').'_'.$i, $required ? 'required' : '', $dynamic);
            $input .= sprintf($select, $class, $type, $cname, rtrim($id, '[]').'_'.$i, $value, $required ? 'required' : '', $placeholder ? 'placeholder="'.$placeholder.'"' : '', $src, $dynamic);
        }

        return sprintf($this->getStringHtml($label), $label, $input);
    }
}
