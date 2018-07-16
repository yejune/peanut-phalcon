<?php
namespace Peanut\Schema\Fields;

class SelectField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $label    = $this->getLabel();
        $name     = $this->getName();
        $value    = $this->getValue();
        $id       = $this->getId();
        $required = $this->getRequired();
        $readonly = $this->getReadonly();

        // true일때는 무조건 readonly
        // exist일때는 값이 있을 경우에만
        if ($readonly == 'exist' && !$value) {
            $readonly = false;
        }
        $relation = $this->getRelation();
        $data     = $this->getData();
        if ($relation) {
            $this->schema->items  = [
                '' => 'select',
            ];
            $condition = $bind = [];
            foreach ($relation->keys as $key) {
                $condition[] = $key.' = :'.$key.':';
                $bind[$key]  = $data[$key];
            }
            $modelName     = $relation->model;
            $conditions    = [
                'conditions' => implode(' AND ', $condition),
                'bind'       => $bind,
            ];
            $lang                 = $this->lang;
            $relationModels       = $modelName::find($conditions);
            $items                = [
                '' => $relation->message->$lang ?? 'select',
            ];
            foreach ($relationModels as $model) {
                $tmp = '';
                foreach ($relation->fields as $key => $field) {
                    $arr = ($model->toArray());
                    if ($arr[$field]) {
                        if ($tmp) {
                            $tmp .= ' ';
                        }
                        $tmp .= str_replace($field, $arr[$field], $relation->templates[$key]);
                    }
                }
                $items[$model->getSeq()] = $tmp;
            }
            $this->schema->items = $items;
        }
        $select = <<<EOT
<span class="input %s">
<select class="form-control" name="%s" id="%s" %s>
%s
</select>
%s
</span>
EOT;

        $option = <<<OPT
<option value="%s" %s>%s</option>
OPT;

        if (false === is_array($value)) {
            $value = [$value];
        }

        $input = '';

        $j     = -1;
        $count = count($value);

        foreach ($value as $i => $data) {
            $j ++;
            $isLast                      = false;
            if ($j == 0 && 1 == $count) { // create empty
                $isLast = 0;
            } elseif ($j + 1 == $count) {
                $isLast = true;
            }
            $opt = '';
            //pr($this);
            foreach ($this->schema->items as $enumValue => $enumLabel) {
                if (is_object($enumLabel)) {
                    $opt .= '<optgroup label="'.$enumValue.'">';
                    foreach ($enumLabel as $key2 => $data2) {
                        if ($key2 == $data) {
                            $selected = 'selected';
                        } else {
                            $selected = '';
                        }
                        $opt .= sprintf($option, $key2, $selected, $enumValue.' '.$data2);
                    }
                    $opt .= '</optgroup>';
                } else {
                    if ($enumValue == $data) {
                        $selected = 'selected';
                    } else {
                        $selected = '';
                    }
                    $opt .= sprintf($option, $enumValue, $selected, $enumLabel);
                }
            }

            $dynamic = '';
            $class   = '';
            if (isset($this->schema->size)) {
                $dynamic = $this->getDynamic($isLast);
                $class   ='entry input-group';
            }

            $input .= sprintf($select, $class, $name, rtrim($id, '[]').'_'.$i, $readonly ? "readonly onFocus='this.initialSelect = this.selectedIndex;' onChange='this.selectedIndex = this.initialSelect;'" : '', $opt, $dynamic);
        }

        return sprintf($this->getStringHtml($label), $label, $input);
    }
}
