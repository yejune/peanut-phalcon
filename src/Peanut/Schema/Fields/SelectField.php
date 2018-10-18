<?php declare(strict_types=1);

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
        if ('exist' === $readonly && !$value) {
            $readonly = false;
        }
        $relation = $this->getRelation();
        $data     = $this->getData();

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

        if (false === \is_array($value)) {
            $value = [$value];
        }

        $input = '';

        $j     = -1;
        $count = \count($value);

        foreach ($value as $i => $data) {
            $j++;
            $isLast = false;

            if (0 === $j && 1 === $count) { // create empty
                $isLast = 0;
            } elseif ($j + 1 === $count) {
                $isLast = true;
            }
            $opt = '';
            //pr($this);
            if (true === isset($this->schema->items)) {
                foreach ($this->schema->items as $enumValue => $enumLabel) {
                    if (\is_object($enumLabel)) {
                        $opt .= '<optgroup label="' . $enumValue . '">';

                        foreach ($enumLabel as $key2 => $data2) {
                            if ((string)$key2 === (string)$data) {
                                $selected = 'selected';
                            } else {
                                $selected = '';
                            }
                            $opt .= \sprintf($option, $key2, $selected, $enumValue . ' ' . $data2);
                        }
                        $opt .= '</optgroup>';
                    } else {
                        if ((string)$enumValue === (string)$data) {
                            $selected = 'selected';
                        } else {
                            $selected = '';
                        }
                        $opt .= \sprintf($option, $enumValue, $selected, $enumLabel);
                    }
                }
            }

            $dynamic = '';
            $class   = '';

            if (true === isset($this->schema->size)) {
                $dynamic = $this->getDynamic($isLast);
                $class   = 'entry input-group';
            }

            $input .= \sprintf($select, $class, $name, \preg_replace('#\[\]$#', '', $id) . '_' . $i, $readonly ? "readonly onFocus='this.initialSelect = this.selectedIndex;' onChange='this.selectedIndex = this.initialSelect;'" : '', $opt, $dynamic);
        }

        return \sprintf($this->getStringHtml($label), $label, $input);
    }
}
