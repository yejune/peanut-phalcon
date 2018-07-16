<?php
namespace Peanut\Schema\Fields;

class ArrayField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $label    = $this->getLabel();
        if (true === isset($this->schema->items)) {
            $type  = $this->getType($this->schema->items->type);

            $size  = $this->schema->size ?? 1;
            if ($size > 1) {
                $html  = '';
                for ($i=0;$i < $size;$i++) {
                    $path  = array_merge($this->path, [$i]);
                    $field = new $type($this->schema->items, $path, $this->value[$i] ?? null, $this->lang);
                    $html .= $field->fetch();
                }
            } else {
                $path  = $this->path;
                $field = new $type($this->schema->items, $path, $this->value, $this->lang);
                $html  = $field->fetch();
            }

            return sprintf($this->getLayoutHtml($label), $label, $input);
        }
    }
}
