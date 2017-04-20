<?php
namespace Peanut;

class Template
{
    /**
     * @var bool|string
     */
    public $compileCheck = true;
    /**
     * @var string
     */
    public $compileRoot = '.';
    /**
     * @var string
     */
    public $templateRoot = '.';
    /**
     * @var array
     */
    public $tpl_ = [];
    /**
     * @var array
     */
    public $var_ = [];
    /**
     * @var string
     */
    public $skin;
    /**
     * @var string
     */
    public $tplPath;
    /**
     * @var int
     */
    public $permission = 0777;
    /**
     * @var bool
     */
    public $phpengine = true;
    /**
     * @var array
     */
    public $relativePath = [];
    /**
     * @var string
     */
    public $ext = '.php';

    public $notice = false;

    public $noticeReporting = 0;

    public function __construct()
    {
    }

    /**
     * @param $key
     * @param $value
     */
    public function assign($key, $value = false)
    {
        if (true === is_array($key)) {
            $this->var_ = array_merge($this->var_, $key);
        } else {
            $this->var_[$key] = $value;
        }
    }

    /**
     * @param $fid
     * @param $path
     */
    public function define($fid, $path = false)
    {
        if (true === is_array($fid)) {
            foreach ($fid as $subFid => $subPath) {
                $this->_define($subFid, $subPath);
            }
        } else {
            $this->_define($fid, $path);
        }
    }

    /**
     * @param  $fid
     * @param  $print
     * @return mixed
     */
    public function show($fid, $print = false)
    {
        if (true === $print) {
            $this->render($fid);
        } else {
            return $this->fetch($fid);
        }
    }

    /**
     * @param  $fid
     * @return mixed
     */
    public function fetch($fid)
    {
        ob_start();
        $this->render($fid);
        $fetched = ob_get_contents();
        ob_end_clean();

        return $fetched;
    }

    /**
     * @param  $fid
     * @return null
     */
    public function render($fid)
    {
        if (true === isset($this->tpl_[$fid]) && !$this->tpl_[$fid]) {
            return;
        }

        $this->noticeReporting = error_reporting();
        if ($this->notice) {
            error_reporting($this->noticeReporting | E_NOTICE);
            set_error_handler([$this, 'templateNoticeHandler']);
            $this->requireFile($this->getCompilePath($fid));
            restore_error_handler();
        } else {
            error_reporting($this->noticeReporting & ~E_NOTICE);
            $this->requireFile($this->getCompilePath($fid));
        }
        error_reporting($this->noticeReporting);
    }

    public function compilePath($filename)
    {
        $this->_define('*', $filename);

        return $this->getCompilePath('*');
    }

    /**
     * @param  $fid
     * @return mixed
     */
    public function getCplPath($fid)
    {
        return $this->compileRoot.$this->tpl_[$fid].$this->ext;
    }
    public function setTemplateRoot($path)
    {
        $this->templateRoot = rtrim($path, '/').'/';
    }
    public function setCompileRoot($path)
    {
        $this->compileRoot = rtrim($path, '/').'/';
    }
    public function setSkin($name)
    {
        $this->skin = trim($name, '/').'/';
    }
    public function setPhpEngine($switch = true)
    {
        $this->phpengine = $switch;
    }
    public function setNotice($switch = true)
    {
        $this->notice = $switch;
    }
    public function setCompileCheck($switch = true)
    {
        $this->compileCheck = $switch;
    }
    /**
     * @param  $fid
     * @return mixed
     */
    public function getTplPath($fid)
    {
        $path = '';

        if (true === isset($this->tpl_[$fid])) {
            $path = $this->tpl_[$fid];
        } else {
            trigger_error('template id "'.$fid.'" is not defined', E_USER_ERROR);
        }

        if (0 === strpos($path, '/')) {
            $tplPath = $path;
        } else {
            $addFolder = $this->templateRoot;
            if ($this->skin) {
                $addFolder .= $this->skin;
            }
            $tplPath = $addFolder.$path;
        }

        if (false === ($tplPath = stream_resolve_include_path($tplPath))) {
            trigger_error('cannot find defined template "'.$tplPath.'"', E_USER_ERROR);
        }

        return $tplPath;
    }

    public function templateNoticeHandler($type, $msg, $file, $line)
    {
        $msg .= " in <b>$file</b> on line <b>$line</b>";
        switch ($type) {
            case E_NOTICE:$msg = "'".'"><span style="font:12px tahoma,arial;color:green;background:white">template Notice #1: '.$msg.'</span>';break;
            case E_WARNING:
            case E_USER_WARNING:$msg = '<b>Warning</b>: '.$msg; break;
            case E_USER_NOTICE:$msg  = '<b>Notice</b>: '.$msg; break;
            case E_USER_ERROR:$msg   = '<b>Fatal</b>: '.$msg; break;
            default:$msg             = '<b>Unknown</b>: '.$msg; break;
        }
        echo "<br />\n".$msg."<br />\n";
    }

    /**
     * @param $fid
     * @param $path
     */
    private function _define($fid, $path)
    {
        $this->tpl_[$fid] = ltrim($path, $this->templateRoot);
    }
    /**
     * @param  $fid
     * @return mixed
     */
    private function getCompilePath($fid)
    {
        $tplPath = $this->getTplPath($fid);
        $cplPath = $this->getCplPath($fid);

        if (false === $this->compileCheck) {
            return $cplPath;
        }

        if (false === $tplPath) {
            trigger_error('cannot find defined template "'.$tplPath.'"', E_USER_ERROR);
        }

        $cplHead = '<?php /* Peanut\Template '.sha1_file($tplPath, true).' '.date('Y/m/d H:i:s', filemtime($tplPath)).' '.$tplPath.' ';

        if ('dev' !== $this->compileCheck && false !== $cplPath) {
            $fp   = fopen($cplPath, 'rb');
            $head = fread($fp, strlen($cplHead) + 9);
            fclose($fp);

            if (strlen($head) > 9
                && substr($head, 0, 46) == substr($cplHead, 0, 46) && filesize($cplPath) == (int) substr($head, -9)) {
                return $cplPath;
            }
        }

        $compiler = new \Peanut\Template\Compiler();
        $compiler->execute($this, $fid, $tplPath, $cplPath, $cplHead);

        if (false === ($cplPath = stream_resolve_include_path($cplPath))) {
            trigger_error('cannot find defined template compile "'.$cplPath.'"', E_USER_ERROR);
        }

        return $cplPath;
    }

    /**
     * @param $tplPath
     */
    private function requireFile($tplPath)
    {
        extract($this->var_);
        require $tplPath;
    }
}
