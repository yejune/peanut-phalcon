<?php
namespace Peanut\Schema\Fields;

class ObjectField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        if (true === isset($this->schema->properties)) {
            $s = '';
            foreach ($this->schema->properties as $propKey => $propValues) {
                $type  = $this->getType($propValues->type);
                $path  = array_merge($this->path, [$propKey]);
                $field = new $type($propValues, $path, $this->data[$propKey] ?? null, $this->lang);
                $s .= $field->fetch();
            }

            return sprintf($this->getObjectHtml($this->getLabel()), $this->getLabel(), $s);
        }
    }
}
