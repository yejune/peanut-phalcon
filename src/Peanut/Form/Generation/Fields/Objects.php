<?php declare(strict_types=1);

namespace Peanut\Form\Generation\Fields;

class Objects extends \Peanut\Form\Generation\Fields
{
    public static function write(string $key, array $specs, $data)
    {
        //pr($key, $data);

        $innerhtml = '';
        //pr($data);

        foreach ($specs['properties'] as $propertyKey => $propertyValue) {
            if(false === isset($propertyValue['type'])) {
                pr($propertyValue);
            }
            $method   = __NAMESPACE__ . '\\' . \ucfirst($propertyValue['type']);
            $elements = '';
            $index    = 0;

            $fixPropertyKey = $propertyKey;
            $isArray        = false;

            if (false !== \strpos($fixPropertyKey, '[]')) {
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
            }
            $propertyName = $fixPropertyKey;

            if ($key) {
                $propertyName = $key . '[' . $fixPropertyKey . ']';
            }
            $aData = $data[$fixPropertyKey] ?? '';

            if ($aData) {
                if (false === $isArray) { // 배열일때
                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }
                    $elements .= static::addElement(
                        $method::write($propertyName, $propertyValue, $aData),
                        $index
                    );
                } else {
                    foreach ($aData as $aKey => $aValue) {
                        $index++;

                        if (false === isset($parentId)) {
                            $parentId = $aKey;
                        }
                        $elements .= static::addElement(
                            $method::write($propertyName . '[' . $aKey . ']', $propertyValue, $aData[$aKey]),
                            $index
                        );
                    }
                }
            } else {
                if (false === isset($parentId)) {
                    $parentId = static::getUniqueId();
                }

                if (false === $isArray) {
                    $elements .= static::addElement(
                        $method::write($propertyName, $propertyValue, $aData),
                        $index
                    );
                } else {
                    $index++;

                    $elements .= static::addElement(
                        $method::write($propertyName . '[' . $parentId . ']', $propertyValue, $aData),
                        $index
                    );
                }
            }

            $multipleHtml = true === isset($propertyValue['multiple']) ? static::getMultipleHtml($parentId) : '';

            if(true === isset($propertyValue['label'])) {
                if(true === isset($propertyValue['label'][static::getLanguage()])) {
                    $title = $propertyValue['label'][static::getLanguage()];
                } else  {
                    $title = $propertyValue['label'];
                }
            } else {
                $title = '';
            }

            if(true === isset($propertyValue['description'])) {
                if(true === isset($propertyValue['description'][static::getLanguage()])) {
                    $description = $propertyValue['description'][static::getLanguage()];
                } else  {
                    $description = $propertyValue['description'];
                }
            } else {
                $description = '';
            }
            $collapse = '';
            if(true === isset($propertyValue['collapse'])) {
                $collapse = 'hide';
            }
            $objects = <<<EOT
            <div class="objects {$collapse}">
            {$elements}
            {$multipleHtml}
            </div>
EOT;

            $collapse1 = '';
            if(true === isset($propertyValue['collapse'])) {
                $collapse1 = ' <i class="button-collapse glyphicon glyphicon-triangle-right"></i>';
            }

            $titleHtml    = '';

            $collapse2 = '';
            if(true === isset($propertyValue['collapse'])) {
                $collapse2 = 'label-collapse';
            }

            if ($title) {
                $titleHtml .= '<label class="'.$collapse2.'">' . $title .$collapse1. '</label>';
            }

            if ($description) {
                $titleHtml .= '<p>' . $description . '</p>';
            }

            $hide = '';
            if(true === isset($propertyValue['hide'])) {
                $hide = ' hide';
            }
            if ('hidden' === $propertyValue['type']) {
                $innerhtml .= <<<EOT
                <div class="form-group row x-hidden">
                    {$titleHtml}
                    {$objects}
                </div>
EOT;
            } else {
                $innerhtml .= <<<EOT
                <div class="form-group row {$hide}">
                    {$titleHtml}
                    {$objects}
                </div>
EOT;
            }
            unset($parentId);
        }

        $html = <<<EOT
<fieldset>
    {$innerhtml}
</fieldset>

EOT;

        return $html;
    }

    public static function read(string $key, array $specs, $data)
    {
        //pr($key, $data);

        $innerhtml = '';
        //pr($data);

        foreach ($specs['properties'] as $propertyKey => $propertyValue) {
            $method   = __NAMESPACE__ . '\\' . \ucfirst($propertyValue['type']);
            $elements = '';
            $index    = 0;

            $fixPropertyKey = $propertyKey;
            $isArray        = false;

            if (false !== \strpos($fixPropertyKey, '[]')) {
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
            }
            $propertyName = $fixPropertyKey;

            if ($key) {
                $propertyName = $key . '[' . $fixPropertyKey . ']';
            }
            $aData = $data[$fixPropertyKey] ?? '';

            if ($aData) {
                if (false === $isArray) { // 배열일때
                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }
                    $elements .= static::readElement(
                        $method::read($propertyName, $propertyValue, $aData),
                        $index
                    );
                } else {
                    foreach ($aData as $aKey => $aValue) {
                        $index++;

                        if (false === isset($parentId)) {
                            $parentId = $aKey;
                        }
                        $elements .= static::readElement(
                            $method::read($propertyName . '[' . $aKey . ']', $propertyValue, $aData[$aKey]),
                            $index
                        );
                    }
                }
            } else {
                if (false === $isArray) {
                    $elements .= static::readElement(
                        $method::read($propertyName, $propertyValue, $aData),
                        $index
                    );
                } else {
                    $index++;

                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }

                    $elements .= static::readElement(
                        $method::read($propertyName . '[' . $parentId . ']', $propertyValue, $aData),
                        $index
                    );
                }
            }

            $language     = $propertyValue['label'][static::getLanguage()] ?? $key;
            $multipleHtml = true === isset($propertyValue['multiple']) ? static::getMultipleHtml($parentId) : '';
            $titleHtml    = '<label>' . $language . '</label>';

            if ('hidden' === $propertyValue['type']) {
                $innerhtml .= <<<EOT
                    {$elements}
EOT;
            } else {
                $innerhtml .= <<<EOT
                <div class="form-group row">
                    {$titleHtml}
                    {$elements}
                </div>
EOT;
            }
            unset($parentId);
        }

        $html = <<<EOT
<fieldset>
    {$innerhtml}
</fieldset>

EOT;

        return $html;
    }
}
