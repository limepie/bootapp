<?php
namespace App\Controllers;

use Symfony\Component\Process\Process;

class Command extends \Peanut\Console\Command
{
    /**
     * @var string
     */
    public $configFileName = 'Bootfile.yml';

    /**
     * @var string
     */
    public $color = '';

    /**
     * @var mixed
     */
    public $verbose = false;
    /**
     * @param $command
     * @param $option
     */
    public function process($command, array $option = null)
    {
        if (true === is_array($command)) {
            $command = implode(' ', $command);
        }

        if (true === isset($option['timeout'])) {
            $timeout = $option['timeout'];
        } else {
            $timeout = null;
        }

        if (true === isset($option['tty'])) {
            $tty = $option['tty'];
        } else {
            $tty = false;
        }

        if ($this->verbose) {
            $print = true;
            $this->message('IN >> '.$command);
        } else {
            if (true === isset($option['print'])) {
                $print = $option['print'];
            } else {
                $print = true;
                $this->message($command);
            }
        }

        $process = new Process($command);
        $process->setTty($tty);
        $process->setTimeout($timeout);
        $process->run(function ($type, $buf) use ($print) {
            if (true == $print) {
                $buffers = explode(PHP_EOL, trim($buf, PHP_EOL));

                foreach ($buffers as $buffer) {
                    if (Process::ERR === $type) {
                        echo 'ERR > '.$buffer.PHP_EOL;
                    } else {
                        if ('reach it successfully.' == $buffer) {
                            print_r($command);
                        }

                        echo \Peanut\Console\Color::text('OUT > ', 'black').$buffer.PHP_EOL;
                    }
                }
            }
        });

        if (!$process->isSuccessful() && $process->getErrorOutput()) {
            throw new \Peanut\Console\Exception(trim($process->getErrorOutput()));
        }

        return new \Peanut\Console\Result($process->getErrorOutput().$process->getOutput());
    }

    /**
     * @param $name
     * @param $command
     */
    public function childProcess($name, $command)
    {
        $process = new \React\ChildProcess\Process($command);

        $process->on('exit', function ($exitCode, $termSignal) {
            // ...
            $this->message($exitCode, 'exit');
            $this->message($termSignal, 'exit');
        });

        if (!$this->color) {
            $tmp = \Peanut\Console\Color::$foregroundColors;
            unset($tmp['black'], $tmp['default'], $tmp['white'], $tmp['light_gray']);
            $this->color = array_keys($tmp);
        }

        $color = array_shift($this->color);

        $this->loop->addTimer(0.001, function ($timer) use ($process, $name, $color) {
            $process->start($timer->getLoop());

            $callback = function ($output) use ($name, $color) {
                $lines = explode(PHP_EOL, $output);
                $i     = 0;

                foreach ($lines as $line) {
                    if ($line) {
                        $tmp = '';

                        if ($name) {
                            $tmp .= \Peanut\Console\Color::text(str_pad($name, 16, ' ', STR_PAD_RIGHT).' | ', $color);
                        }

                        $tmp .= \Peanut\Console\Color::text($line, 'light_gray');

                        $this->message($tmp);
                    }

                    $i++;
                }
            };
            $process->stdout->on('data', $callback);
            $process->stderr->on('data', $callback);
        });
    }

    /**
     * @param $message
     * @param $name
     */
    public function message($message = '', $name = '')
    {
        $this->log($message, $name);
    }

    /**
     * @param $m
     */
    public function log($message = '', $name = '')
    {
        if (true === is_array($message)) {
            foreach ($message as $k => $v) {
                $this->log($v, $k);
            }
        } else {
            if ($name && false === is_numeric($name)) {
                echo $name;
                echo ' = ';
            }

            echo trim($message).PHP_EOL;
        }
    }

    /**
     * @param  $data
     * @return mixed
     */
    public function table($data)
    {
        // Find longest string in each column
        $columns = [];

        foreach ($data as $row_key => $row) {
            $i = -1;

            foreach ($row as $cell) {
                $i++;
                $length = strlen($cell);

                if (empty($columns[$i]) || $columns[$i] < $length) {
                    $columns[$i] = $length;
                }
            }
        }

        $ret = [];

        foreach ($data as $row_key => $row) {
            $i     = -1;
            $table = '';

            foreach ($row as $cell) {
                $i++;
                $table .= str_pad($cell, $columns[$i]).'   ';
            }

            $ret[] = $table;
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getStageName()
    {
        if (true === isset($this->config['stage_name'])) {
            return $this->config['stage_name'];
        } else {
            return 'local';
        }
    }

    /**
     * @return string
     */
    public function getMachineName()
    {
        // todo: 서로 다른 machine의 bridge network가 같은 대역 subnet일 경우 /etc/hosts/에 간섭이 일어나므로 수정할것

        //return 'bootapp-docker-machine';

        if (true === isset($this->config['machine_name'])) {
            return $this->config['machine_name'];
        } else {
            return 'bootapp-docker-machine';
        }
    }

    /**
     * @return string
     */
    public function getProjectName()
    {
        if (true === isset($this->config['project_name'])) {
            return $this->config['project_name'];
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getContainerName($name)
    {
        return $this->getProjectName().'-'.$name;
    }

    /**
     * @param  $message
     * @return string
     */
    public function ask($message)
    {
        echo $message;
        $handle = fopen('php://stdin', 'r');
        $line   = fgets($handle);
        fclose($handle);

        return trim($line);
    }

    /**
     * @param  \Peanut\Console\Application $app
     * @return mixed
     */
    public function execute(\Peanut\Console\Application $app)
    {
        $this->config  = $this->getConfig();
        $this->verbose = $app->getOption('verbose');

        return $this->exec($app, $this->config);
    }

    /**
     * @return array
     */
    public function setConfig()
    {
        $this->config = \App\Helpers\Yaml::parseFile($this->configFileName);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        if (false === isset($this->config)) {
            $this->setConfig();
        }

        return $this->config;
    }

    /**
     * @return string
     */
    public function getMachineIp()
    {
        return $this->process([
            'docker-machine',
            'ip',
            $this->getMachineName(),
        ], ['print' => false]); //parse_url(getenv('DOCKER_HOST'), PHP_URL_HOST);
    }
}
