<?php declare(strict_types=1);

namespace Peanut\Schema;

abstract class Fields
{
    public $schema;

    public $path = [];

    public $value = [];

    public $lang = '';

    public $data = [];

    public function __construct(\stdClass $schema, array $path = [], $value, $lang, &$data = [])
    {
        $this->schema = $schema;

        if ($path) {
            $this->path = $path;
        }
        $this->data  = $data;
        $this->value = $value;
        $this->lang  = $lang;
    }

    public function getDynamic($isLast = false)
    {
        if ($isLast) {
            return <<<EOT
            <span class="input-group-btn">
                <button class="btn btn-success btn-add" type="button">
                    <span class="glyphicon glyphicon-plus"></span>
                </button>
                <button class="btn btn-danger btn-remove" type="button">
                    <span class="glyphicon glyphicon-minus"></span>
                </button>
            </span>
EOT;
        } elseif (0 === $isLast) {
            return <<<EOT
            <span class="input-group-btn">
                <button class="btn btn-success btn-add" type="button">
                    <span class="glyphicon glyphicon-plus"></span>
                </button>
            </span>
EOT;
        }

        return <<<EOT
            <span class="input-group-btn">
                <button class="btn btn-danger btn-remove" type="button">
                    <span class="glyphicon glyphicon-minus"></span>
                </button>
            </span>
EOT;
    }

    public function getLayoutHtml($s1 = null, $s2 = null)
    {
        if ($s1) {
            return <<<EOT
<div class="form-group">
<label class="control-label">%s</label>
    %s
</div>
EOT;
        }

        return <<<EOT
<div class="form-group">
    <div class="col-sm-12">
    %s%s
    </div>
</div>
EOT;
    }

    public function getStringHtml($s1 = null, $s2 = null)
    {
        if ($s1) {
            return <<<EOT
<div class="form-group">
    <label class="control-label">%s</label>
    <div class="col-sm-12">
    %s
    </div>
</div>
EOT;
        }

        return <<<EOT
<div class="form-group">
    <div class="col-sm-12">
    %s%s
    </div>
</div>
EOT;
    }

    public function getObjectHtml($s1 = null, $s2 = null)
    {
        if ($s1) {
            return <<<EOT
<div class="form-group">
    <h4 class="div-title">%s</h4>
    <div class="col-sm-12">
    %s
    </div>
</div>
EOT;
        }

        return <<<EOT
<div class="form-group">
    <div class="col-sm-12">
    %s%s
    </div>
</div>
EOT;
    }

    public function getGrouptHtml($s1 = null, $s2 = null)
    {
        if ($s1) {
            return <<<EOT
<div class="form-group">
    <h4 class="div-title">%s</h4>
    <div class="col-sm-12">
    <span class="input entry input-group">
    %s
    </span>
    </div>
</div>
EOT;
        }

        return <<<EOT
<div class="form-group">
    <div class="col-sm-12">
    %s%s
    </div>
</div>
EOT;
    }

    public function getName()
    {
        // $path = $this->path;
        // $var  = \array_shift($path);

        // if ($path) {
        //     $var .= '.' . \implode('', $path);
        // }
        // pr($this->path, $path, $var);
        // return $var;

        $path = $this->path;
        $first  = \array_shift($path);

        $newPath = $first;
        foreach($path as $v) {
            if(false === strpos( $v, '[]')) {
                $newPath .= '['.$v.']';
            } else {
                $v = str_replace('[]', '', $v);
                $newPath .= '['.$v.'][]';
            }
        }
        //pr($newPath);
        return $newPath;
    }

    public function getId()
    {

        $path = $this->path;
        $first  = \array_shift($path);
        $first  = rtrim(\str_replace(['[', ']'], ['_', ''], $first),'_');

        $newPath = $first;
        foreach($path as $v) {
            if(false === strpos( $v, '[]')) {
                $newPath .= '_'.$v;
            } else {
                $v = str_replace('[]', '', $v);
                $newPath .= '_'.$v;
            }
        }
        // pr($newPath);
        return $newPath;

        // $path = $this->path;
        // $var  = \array_shift($path);
        // $var  = \str_replace(['[', ']'], ['_', ''], $var);

        // if ($path) {
        //     $var .= '_' . \implode('_', $path);
        // }
        // //$var = preg_replace('#[_]{2,}#', '_', $var);
        // return \trim($var, '_');
    }

    public function getValue()
    {
        $path  = $this->path;
        $value = $this->value;

        while (1) {
            $p = \array_shift($path);

            if (\strlen($p) && true === isset($value[$p])) {
                $value = $value[$p];

                continue;
            }

            break;
        }

        return $value;
    }

    public function getLabel()
    {
        $label = $this->schema->label ?? null;

        if (true === \is_object($label)) {
            $lang = $this->lang;

            if (true === isset($label->{$lang})) {
                return $label->{$lang};
            }

            return \current($label);
        }

        return $label;
    }

    public function getReadonly()
    {
        $readonly = $this->schema->readonly ?? null;

        return $readonly;
    }

    public function getRelation()
    {
        $relation = $this->schema->relation ?? null;

        return $relation;
    }

    public function getData()
    {
        return $this->data ?? [];
    }

    public function getDescription()
    {
        $description = $this->schema->description ?? null;

        if (true === \is_object($description)) {
            $lang = $this->lang;

            if (true === isset($description->{$lang})) {
                return $description->{$lang};
            }

            return \current($description);
        }

        return $description;
    }

    public function getType($type, $isGroup = false) : string
    {
        if ($isGroup) {
            return '\\Peanut\\Schema\\Fields\\Group\\' . \ucfirst($type) . 'Field';
        }

        return '\\Peanut\\Schema\\Fields\\' . \ucfirst($type) . 'Field';
    }

    public function input($type, $name, $id, $value = '', $required = false, $size)
    {
        $html = <<<EOT
<span class="input %s">
<input type="%s"  class="form-control" name="%s" id="%s" value="%s" %s />
%s
</span>
EOT;
        $dynamic = '';
        $class   = '';

        if ($size) {
            $dynamic = $this->getDynamic();
            $class   = 'entry input-group';
        }
        //pr($type, $name, $id, $value, $required, $size);

        return \sprintf($html, $class, $type, $name, $id, \htmlspecialchars((string)$value), $required ? 'required' : '', $dynamic);
    }

    public function phone($type, $name, $id, $value = '', $required = false, $size)
    {
        $html = <<<EOT
<span class="input %s">
<input type="%s"  class="form-control" name="%s" id="%s" value="%s" %s />
%s
</span>
add
EOT;
        $dynamic = '';
        $class   = '';

        if ($size) {
            $dynamic = $this->getDynamic();
            $class   = 'entry input-group';
        }

        return \sprintf($html, $class, $type, $name, $id, $value, $required ? 'required' : '', $dynamic);
    }

    public function textarea($type, $name, $id, $value = '', $required = false, $size)
    {
        $html = <<<EOT
<span class="input %s">
<textarea class="form-control" name="%s" id="%s" %s rows="%s" />%s</textarea>
%s
</span>
EOT;
        $count = \substr_count((string) $value, \PHP_EOL) + 1;

        if (3 > $count) {
            $count = 3;
        }
        $dynamic = '';
        $class   = '';

        if ($size) {
            $dynamic = $this->getDynamic();
            $class   = 'entry input-group';
        }

        return \sprintf($html, $class, $name, $id, $required ? 'required' : '', $count, $value, $dynamic);
    }

    public function getRequired()
    {
        return $this->schema->rules->required ?? '';
    }

    public function getAccept()
    {
        return $this->schema->rules->accept ?? '';
    }

    public function getPlaceholder()
    {
        return $this->schema->placeholder ?? '';
    }

    abstract protected function fetch();
}
