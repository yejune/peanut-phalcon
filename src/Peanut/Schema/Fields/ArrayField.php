<?php declare(strict_types=1);

namespace Peanut\Schema\Fields;

class ArrayField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $label = $this->getLabel();

        if (true === isset($this->schema->properties)) {
            $s = '';

            foreach ($this->schema->properties as $propKey => $propValues) {
                $type  = $this->getType($propValues->type);
                $path  = \array_merge($this->path, [$propKey]);
                $field = new $type($propValues, $path, $this->data[$propKey] ?? null, $this->lang, $this->data);
                $s .= $field->fetch();
            }

            return \sprintf($this->getObjectHtml($this->getLabel()), $this->getLabel().' + -', $s);
        }

    }
}
