<?php declare(strict_types=1);

namespace Peanut\Schema\Fields;

class ObjectField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        if (true === isset($this->schema->properties)) {
            $s = '';

            foreach ($this->schema->properties as $propKey => $propValues) {
                $type  = $this->getType($propValues->type);

                $path  = \array_merge($this->path, [$propKey]);

                //pr($type, $path, $propValues, $this->data, $this->data[$propKey] ?? null);

                $value = $this->data[$propKey] ?? null;
                // $value2 = [
                //     'name[]' => [
                //         1
                //     ],
                //     'value[]' => [
                //         1
                //     ],
                // ];
                $field = new $type($propValues, $path, $value, $this->lang, $this->data);
                $s .= $field->fetch();
            }

            return \sprintf($this->getObjectHtml($this->getLabel()), $this->getLabel(), $s);
        }

    }
}
