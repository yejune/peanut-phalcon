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
        $accept      = $this->getAccept();

        $span = '<span class="input %s">%s</span';

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
<input type="%s"  class="form-control file" name="%s" id="%s" value="%s" %s %s %s />
%s
%s
</span>
EOT;

        if (false === is_array($value)) {
            $value = [$value];
        }
        if (0 === count($value)) {
            $value = [null];
        }
        $input = '';

        $j     = -1;
        $count = count($value);

        foreach ($value as $i => $data) {
            $j ++;
            $isLast                      = false;
            $type = 'text';
            if ($j == 0 && 1 == $count) { // create empty
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
            // if (false !== strpos($name, '[]')) {
            //     $cname = preg_replace('#\[\]$#','['.$i.']',$name);//.'[]';//rtrim($name, '[]').'['.$i.']';
            // } else {
            //     $cname = $name;
            // }
            $cname = preg_replace('#\[\]$#', '['.$i.']', $name);//.'[]';//rtrim($name, '[]').'['.$i.']';
            $cid = preg_replace('#\[\]$#', '_', $id).$i;//.'[]';//rtrim($name, '[]').'['.$i.']';

            $src = '';
            if (true === isset($data['type'])) {
                if (0 === strpos($data['type'], 'image/')) {
                    $src = '<a class="preview" href="'.$data['url'].'" target="_blank"><img src="'.$data['url'].'" width="100"></a>';
                } else {
                    $src = '<a class="preview" href="'.$data['url'].'" target="_blank">'.$data['url'].'</a>';
                }
            }
            $value = $data['name'];

            $placeholderCode = $placeholder ? 'placeholder="'.$placeholder.'"' : '';
            $acceptCode = $accept ? 'accept="'.$accept.'"' : '';

            $input .= sprintf($select, $class, $type, $cname, $cid, $value, $required ? 'required' : '', $placeholderCode, $acceptCode, $src, $dynamic);
        }

        return sprintf($this->getStringHtml($label), $label, $input);
    }
}
