<?php
namespace Peanut\Phalcon\Mvc;

class Model extends \Phalcon\Mvc\Model
{
    public function setRelated($alias, $properties) : void
    {
        $this->_related[$alias][] = $properties;
    }
    public function beforeSave() : void
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
    public function save($data = null, $whiteList = null) : bool
    {
        $result = parent::save($data, $whiteList);
        if (false === $result || parent::getMessages()) {
            foreach (parent::getMessages() as $message) {
                throw new \Exception($message->getMessage());
            }
        }

        return $result;
    }
    public function create($data = null, $whiteList = null) : bool
    {
        $result = parent::create($data, $whiteList);
        if (false === $result || parent::getMessages()) {
            foreach (parent::getMessages() as $message) {
                throw new \Exception($message->getMessage());
            }
        }

        return $result;
    }
    public function update($data = null, $whiteList = null) : bool
    {
        $result = parent::update($data, $whiteList);
        if (false === $result || parent::getMessages()) {
            foreach (parent::getMessages() as $message) {
                throw new \Exception($message->getMessage());
            }
        }

        return $result;
    }
}
