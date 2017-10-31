<?php
namespace Peanut\Schema;

abstract class Fields
{
    public $schema;
    public $path   = [];
    public $data   = [];
    public $lang   = '';
    public function __construct(\stdClass $schema, array $path = [], $data, $lang)
    {
        $this->schema = $schema;
        if ($path) {
            $this->path = $path;
        }
        $this->data = $data;
        $this->lang = $lang;
    }
    public function getDynamic($i=0)
    {
        if ($i == 0) {
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
    public function getLayoutHtml($s1=null, $s2=null)
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
    public function getStringHtml($s1=null, $s2=null)
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
    public function getObjectHtml($s1=null, $s2=null)
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
    public function getGrouptHtml($s1=null, $s2=null)
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
        $path = $this->path;
        $var  = array_shift($path);
        if ($path) {
//            $var .= '['.implode('][', $path).']';
            $var .= '_'.implode('_', $path);
        }

        return $var;
    }
    public function getId()
    {
        $path = $this->path;
        $var  = array_shift($path);
        $var  = str_replace(['[', ']'], ['_', ''], $var);

        if ($path) {
            $var .= '_'.implode('_', $path);
        }
        //$var = preg_replace('#[_]{2,}#', '_', $var);
        return trim($var, '_');
    }
    public function getValue()
    {
        $path = $this->path;
        $data = $this->data;

        while (1) {
            $p = array_shift($path);
            if (strlen($p) && true === isset($data[$p])) {
                $data = $data[$p];
                continue;
            }
            break;
        }

        return $data;
    }
    public function getLabel()
    {
        $label = $this->schema->label ?? null;
        if (true === is_object($label)) {
            $lang = $this->lang;
            if (true === isset($label->{$lang})) {
                return $label->{$lang};
            }

            return current($label);
        }

        return $label;
    }
    public function getDescription()
    {
        $description = $this->schema->description ?? null;
        if (true === is_object($description)) {
            $lang = $this->lang;
            if (true === isset($description->{$lang})) {
                return $description->{$lang};
            }

            return current($description);
        }

        return $description;
    }
    public function getType($type, $isGroup = false) : string
    {
        if ($isGroup) {
            return '\\Peanut\\Schema\\Fields\\Group\\'.ucfirst($type).'Field';
        }

        return '\\Peanut\\Schema\\Fields\\'.ucfirst($type).'Field';
    }
    public function input($type, $name, $id, $value='', $required = false, $size)
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
            $class   ='entry input-group';
        }
        //pr($type, $name, $id, $value, $required, $size);

        return sprintf($html, $class, $type, $name, $id, $value, $required ? 'required' : '', $dynamic);
    }
    public function phone($type, $name, $id, $value='', $required = false, $size)
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
            $class   ='entry input-group';
        }

        return sprintf($html, $class, $type, $name, $id, $value, $required ? 'required' : '', $dynamic);
    }
    public function textarea($type, $name, $id, $value='', $required = false, $size)
    {
        $html = <<<EOT
<span class="input %s">
<textarea class="form-control" name="%s" id="%s" %s />%s</textarea>
%s
</span>
EOT;

        $dynamic = '';
        $class   = '';
        if ($size) {
            $dynamic = $this->getDynamic();
            $class   ='entry input-group';
        }

        return sprintf($html, $class, $name, $id, $required ? 'required' : '', $value, $dynamic);
    }
    public function getRequired()
    {
        return '';
        return $this->schema->rules->required ?? '';
    }

    abstract protected function fetch();
}
