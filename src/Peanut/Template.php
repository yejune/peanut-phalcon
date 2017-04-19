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
     * @var mixed
     */
    public $tpl_;// = [];

    /**
     * @var mixed
     */
    public $var_;// = [];

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
    public $permission      = 0777;

    /**
     * @var bool
     */
    public $phpengine       = true;

    /**
     * @var array
     */
    public $relativePath    = [];

    /**
     * @var string
     */
    public $ext             = '.php';

    public $notice          = false;

    public $noticeReporting = 0;

    public function __construct()
    {
        $this->tpl_ = [];
        $this->var_ = [];
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
        // define 되어있으나 값이 없을때
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
    public function cplPath($fid)
    {
        return $this->compileRoot.DIRECTORY_SEPARATOR.ltrim($this->tpl_[$fid], './').$this->ext;
    }

    /**
     * @param  $fid
     * @return mixed
     */
    public function tplPath($fid)
    {
        $path      = '';
        $addFolder = rtrim($this->templateRoot, '/').'/';

        if (true === isset($this->tpl_[$fid])) {
            $path = $this->tpl_[$fid];
        } else {
            trigger_error('template id "'.$fid.'" is not defined', E_USER_ERROR);
        }
        if (1 === preg_match('#^/#', $path)) {
            trigger_error('Only relative paths are allowed. '.$path, E_USER_ERROR);
        }
        $path = str_replace(__BASE__.'/'.$addFolder, '', $path);

        $skinFolder = trim($this->skin, '/');

        if ($skinFolder) {
            $addFolder .= '/'.$skinFolder.'/';
        }

        $tplPath = $addFolder.ltrim($path, './');
        //trigger_error(print_r([$path, $tplPath], true), E_USER_ERROR);

        if (false === stream_resolve_include_path($tplPath)) {
            trigger_error('cannot find defined template "'.$tplPath.'"', E_USER_ERROR);
        }

        return  stream_resolve_include_path($tplPath);
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
        $this->tpl_[$fid] = $path;
    }
    /**
     * @param  $fid
     * @return mixed
     */
    private function getCompilePath($fid)
    {
        $tplPath = ($this->tplPath($fid));
        $cplPath = $this->cplPath($fid);
        if (false === $this->compileCheck) {
            return $cplPath;
        }

        if (false === $tplPath) {
            trigger_error('cannot find defined template "'.$tplPath.'"', E_USER_ERROR);
        }

        $cplHead = '<?php /* Peanut\Template '.date('Y/m/d H:i:s', filemtime($tplPath)).' '.$tplPath.' ';

        if ('dev' !== $this->compileCheck && false !== $cplPath) {
            $fp   = fopen($cplPath, 'rb');
            $head = fread($fp, strlen($cplHead) + 9);
            fclose($fp);

            if (strlen($head) > 9
                && substr($head, 0, -9) == $cplHead && filesize($cplPath) == (int) substr($head, -9)) {
                return $cplPath;
            }
        }

        $compiler = new \Peanut\Template\Compiler();
        $compiler->execute($this, $fid, $tplPath, $cplPath, $cplHead);
        $cplPath = stream_resolve_include_path($cplPath);

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
