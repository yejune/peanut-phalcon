<?php
namespace Peanut\Phalcon\Mvc;

class Model extends \Phalcon\Mvc\Model
{
    public function setRelated($alias, $properties)
    {
        $this->_related[$alias][] = $properties;
    }
    public function beforeSave()
    {
        $metaData   = $this->getModelsMetaData();
        $attributes = $metaData->getNotNullAttributes($this);

        // Set all not null fields to their default value.
        foreach ($attributes as $field) {
            if (false === isset($this->{$field}) || true === is_null($this->{$field})) {
                $this->{$field} = new \Phalcon\Db\RawValue('default');
            }
        }
    }
}
