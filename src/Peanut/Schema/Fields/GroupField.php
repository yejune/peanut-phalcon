<?php
namespace Peanut\Schema\Fields;

class GroupField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        if (true === isset($this->schema->properties)) {
            $s = '';
            foreach ($this->schema->properties as $propKey => $propValues) {
                if ($propKey == '$ref') {
                    $yml    = yaml_parse(file_get_contents(__BASE__.'/app/Specs/Site/'.$propValues));
                    $json   = json_decode(json_encode($yml));

                    foreach ($json->properties as $propKey2 => $propValues2) {
                        if ($propKey2 == '$ref') {
                        } else {
                            $type  = $this->getType($propValues2->type, true);
                            $path  = array_merge($this->path, [$propKey2]);

                            $field = new $type($propValues2, $path, $this->data[$propKey2] ?? null, $this->lang);
                            $s .= $field->fetch();
                        }
                    }
                } else {
                    $type  = $this->getType($propValues->type, true);
                    $path  = array_merge($this->path, [$propKey]);

                    $field = new $type($propValues, $path, $this->data[$propKey] ?? null, $this->lang);
                    $s .= $field->fetch();
                }
            }

            return sprintf($this->getGrouptHtml($this->getLabel()), $this->getLabel(), $s);
        }
    }
}
