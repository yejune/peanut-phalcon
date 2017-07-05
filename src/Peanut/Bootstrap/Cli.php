<?php
namespace Peanut\Bootstrap;

class Cli extends \Peanut\Bootstrap
{
    public $arguments = [];
    /**
     * @param  \Phalcon\Cli\Console   $app
     * @return \Phalcon\Cli\Console
     */
    public function __invoke(\Phalcon\Cli\Console $app)
    {
        return $this->run($app);
    }

    /**
     * @return string
     */
    public function getHttpHost()
    {
        return $this->getDi()->get('request')->getHttpHost();
    }

    public function arguments(array $argv)
    {
        $arguments = [
            'task'   => 'Index',
            'action' => 'index',
        ];
        foreach ($argv as $k => $arg) {
            switch ($k) {
                case 0: break;
                case 1:
                    $arguments['task'] = $arg;
                    break;
                case 2:
                    $arguments['action'] = $arg;
                    break;
                default:
                    $arguments['params'][] = $arg;
           }
        }

        if (!array_key_exists('task', $arguments)) {
            $this->die('task required! usage: php public/cli.php [TASK NAME]');
        }
        $task = array_reduce(explode('/', $arguments['task']), function ($carry, $value) {
            return $carry.'\\'.ucfirst($value);
        }, '');
        $arguments['task'] = '\\App\\Tasks'.$task;
//        pr($arguments);
        return $arguments;
    }

    /**
     * @param $config
     */
    protected function initialize(\Phalcon\Cli\Console $app)
    {
    }

    /**
     * @param  \Phalcon\Cli\Console   $app
     * @return \Phalcon\Cli\Console
     */
    private function run(\Phalcon\Cli\Console $app)
    {
        $app->setDi($this->di);
        $this->initialize($app, $this->di);

        return $app;
    }
}
