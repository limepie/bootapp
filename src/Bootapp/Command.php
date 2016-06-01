<?php
namespace Bootapp;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends \Symfony\Component\Console\Command\Command
{
    const V  = OutputInterface::VERBOSITY_VERBOSE;
    const VV = OutputInterface::VERBOSITY_VERY_VERBOSE;
    const D  = OutputInterface::VERBOSITY_DEBUG;
    const N  = OutputInterface::VERBOSITY_NORMAL;

    /**
     * @var InputInterface
     */
    public $input;

    /**
     * @var OutputInterface
     */
    public $output;

    /**
     * @var string
     */
    public $whichBrew = '';

    /**
     * @var string
     */
    public $whichDockerMachine = '';

    /**
     * @var string
     */
    public $whichDocker = '';

    /**
     * @var string
     */
    public $whichDockerCompose = '';

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return mixed
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        return $this->process();
    }

    /**
     * @param $string
     */
    public function bold($string)
    {
        return "\e[1m".$string."\e[0m";
    }

    /**
     * @param  string  $environment
     * @return array
     */
    public function getConfigFile($environment)
    {
        if (false == is_file(getcwd().'/env.yml')) {
            throw new \RuntimeException('env.yml 파일이 위치한 곳에서 실행해주세요.');
        }

        if (posix_getpwuid(posix_getuid())['name'] == 'root') {
            throw new \RuntimeException('Non-root users to run Bootapp');
        }

        // osx
        $uname = $this->command('uname -a')->toString();

        if (false === strpos($uname, 'Darwin')) {
            throw new \RuntimeException('The operating system not supported.');
        }

        $config = $this->yamlParseFile(getcwd().'/env.yml');

        if (false === isset($config['stages'][$environment])) {
            throw new \RuntimeException($environment.'에 해당하는 stages가 없습니다.');
        }

        if (true === isset($config['project'])) {
            if (!preg_match('#^([0-9a-zA-Z\-\.]+)$#', $config['project'])) {
                throw new \RuntimeException('Allowed project name chars are: 0-9a-z');
            }
        } else {
            $config['project'] = 'example';
        }

        if (true === isset($config['machine'])) {
            if (!preg_match('#^([0-9a-zA-Z\-\.]+)$#', $config['machine'])) {
                throw new \RuntimeException('Allowed machine name chars are: 0-9a-z');
            }
        } else {
            $config['machine'] = 'default';
        }

        return $config;
    }

    /**
     * @param  $command
     * @param  $input
     * @throws \RuntimeException
     */
    public function command($command, $input = null)
    {
        $timeout = null;
        $command = str_replace(PHP_EOL, '', $command);

        $this->message($command, 'run');

        $process = new Process($command);
        $process->setTimeout($timeout);

        if ($input) {
            $process->setInput($input);
        }

        $callback = function ($type, $buffer) {
            if (Process::ERR === $type) {
                $msg = implode(PHP_EOL, array_map(function ($line) {
                    return "<fg=red>></fg=red> \033[1;30m".$line."\033[0m";
                }, explode(PHP_EOL, trim($buffer))));
            } else {
                $msg = implode(PHP_EOL, array_map(function ($line) {
                    return "\033[1;30m> ".$line."\033[0m";
                }, explode(PHP_EOL, trim($buffer))));
            }

            $this->message($msg, 'debug');
        };
        $callback = $callback->bindTo($this);
        $process->run($callback);

        if (!$process->isSuccessful() && $process->getErrorOutput()) {
            throw new \Bootapp\RuntimeException($process->getErrorOutput());
        }

        return new \Bootapp\Command\Result($process->getErrorOutput().$process->getOutput());
    }

    /**
     * @param  $file
     * @return bool
     */
    public function isFile($file)
    {
        return $this->runLocal('if [ -f '.$file.' ]; then echo "true"; else echo "false"; fi')->toBool();
    }

    /**
     * @param  $s
     * @return mixed
     */
    public function writeln($messages, $options = OutputInterface::VERBOSITY_VERBOSE, $prefix = true)
    {
        if (true === is_string($messages)) {
            $messages = explode(PHP_EOL, $messages);
        }

        if (true === $prefix && OutputInterface::VERBOSITY_NORMAL === $options) {
            $messages = array_map(function ($s) {return '<info>></info> '.$s;}, $messages);
        }

        return $this->output->writeln($messages, $options);
    }

    /**
     * @param string|array $message
     * @param string       $type
     */
    public function message($messages, $type)
    {
        if (true === is_string($messages)) {
            $messages = explode(PHP_EOL, $messages);
        }

        switch ($type) {
            case 'normal':
                $type     = OutputInterface::VERBOSITY_NORMAL;
                $messages = array_map(function ($s) {return '[  <info>OK</info>  ] '.$s;}, $messages);
                break;
            case 'ok':
                $type     = OutputInterface::VERBOSITY_NORMAL;
                $messages = array_map(function ($s) {return '[  <info>OK</info>  ] '.$s;}, $messages);
                break;
            case 'title':
                $type     = OutputInterface::VERBOSITY_NORMAL;
                $messages = array_map(function ($s) {return '<info>'.$s.'</info>';}, $messages);
                break;
            case 'result':
                $type = OutputInterface::VERBOSITY_QUIET;
                break;
            case '>result':
                $type     = OutputInterface::VERBOSITY_QUIET;
                $messages = array_map(function ($s) {return '<info>></info> '.$s;}, $messages);
                break;
            case 'run':
                $type     = OutputInterface::VERBOSITY_VERY_VERBOSE;
                $messages = array_map(function ($s) {return '$ '."\e[1m".$s."\e[0m";}, $messages);
                break;
            case 'debug':
                $type = OutputInterface::VERBOSITY_DEBUG;
                break;
            case 'quiet':
                $type = OutputInterface::VERBOSITY_QUIET;
                break;
            default:
                break;
        }

        return $this->output->writeln($messages, $type);
    }

    /**
     * @return string
     */
    public function whichBrew()
    {
        if ($this->whichBrew) {
            return $this->whichBrew;
        }

        $whichBrew = $this->command('which brew')->toString();

        if (!$whichBrew) {
            $this->command('/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"');
            $whichBrew = $this->command('which brew')->toString();
        }

        $this->whichBrew = $whichBrew;
        $this->message('Brew Ready!', 'normal');

        return $whichBrew;
    }

    /**
     * @return string
     */
    public function whichDockerMachine()
    {
        if ($this->whichDockerMachine) {
            return $this->whichDockerMachine;
        }

        $whichDockerMachine = $this->command('which docker-machine')->toString();

        if (!$whichDockerMachine) {
            $this->command($this->whichBrew().' uninstall --force docker');
            $this->command($this->whichBrew().' uninstall --force docker-compose');
            $this->command($this->whichBrew().' uninstall --force docker-machine');

            $this->command($this->whichBrew().' cask install dockertoolbox');
            $whichDockerMachine = $this->command('which docker-machine')->toString();
        }

        $this->whichDockerMachine = $whichDockerMachine;
        $this->message('Docker toolbox Ready!', 'ok');

        return $whichDockerMachine;
    }

    /**
     * @return string
     */
    public function whichDocker()
    {
        if ($this->whichDocker) {
            return $this->whichDocker;
        }

        $whichDocker = $this->command('which docker')->toString();

        if (!$whichDocker) {
            $this->command($this->whichBrew().' install docker');
            $whichDocker = $this->command('which docker')->toString();
        }

        $this->whichDocker = $whichDocker;

        return $whichDocker;
    }

    /**
     * @return mixed
     */
    public function whichDockerCompose()
    {
        if ($this->whichDockerCompose) {
            return $this->whichDockerCompose;
        }

        $whichDockerCompose = $this->command('which docker-compose')->toString();

        if (!$whichDockerCompose) {
            $this->command($this->whichBrew().' install docker-compose');
            $whichDockerCompose = $this->command('which docker-compose')->toString();
        }

        $this->whichDockerCompose = $whichDockerCompose;

        return $whichDockerCompose;
    }

    /**
     * @param $machineName
     */
    public function initDockerMachine($machineName)
    {
        $whichDockerMachine = $this->whichDockerMachine();
        $dockerExists       = $this->command($whichDockerMachine.' status '.$machineName.' 2> /dev/null || echo ""')->toString();

        if ('Stopped' === $dockerExists) {
            $this->message('Docker is exist!', 'ok');
            $this->writeln('');
            $this->message('Starting docker-machine', 'title');
            $this->command($whichDockerMachine.' start '.$machineName);
            $this->command($whichDockerMachine.' regenerate-certs '.$machineName, 'y');
            $this->message('Docker is up and running!', 'ok');
        } elseif ('Saved' === $dockerExists) {
            $this->message('Docker is exist!', 'ok');
            $this->writeln('');
            $this->message('Starting docker-machine', 'title');
            $this->command($whichDockerMachine.' start '.$machineName);
            $this->command($whichDockerMachine.' regenerate-certs '.$machineName, 'y');
            $this->message('Docker is up and running!', 'ok');
        } elseif ('Error' === $dockerExists) {
            $this->message('Docker is exist!');
            $this->writeln('');
            $this->message('Starting docker-machine', 'title');
            $this->command($whichDockerMachine.' rm -f '.$machineName);
            $this->command($whichDockerMachine.' create --driver virtualbox --virtualbox-memory 2048 '.$machineName);
            $this->message('Docker is up and running!', 'ok');
        } elseif ('Running' === $dockerExists) {
            $this->message('Docker is up and running!', 'ok');
        } else {
            $this->writeln('');
            $this->message('Creating docker-machine', 'title');
            $this->command($whichDockerMachine.' create --driver virtualbox --virtualbox-memory 2048 '.$machineName);
            $this->message('Docker is up and running!', 'ok');
        }

        foreach ($this->command($whichDockerMachine.' env '.$machineName)->toArray() as $export) {
            if (1 === preg_match('/export (?P<key>.*)="(?P<value>.*)"/', $export, $match)) {
                putenv($match['key'].'='.$match['value']);
                $this->message('export '.$match['key'].'='.$match['value'], 'run');
            }
        }
    }

    /**
     * @param  string  $fileName
     * @return array
     */
    public function yamlParseFile($fileName)
    {
        $this->message('yaml parse file '.$fileName, 'ok');

        return $this->yamlParse(file_get_contents($fileName));
    }

    /**
     * @param  string  $yaml
     * @return array
     */
    public function yamlParse($yaml)
    {
        try {
            $array = Yaml::parse($yaml);
        } catch (ParseException $e) {
            throw new \RuntimeException('Unable to parse the YAML string: %s', $e->getMessage());
        }

        return $array;
    }

    /**
     * @param  $fileName
     * @param  $array
     * @return bool
     */
    public function yamlDumpFile($fileName, $array)
    {
        $this->message('yaml dump file '.$fileName, 'ok');

        return file_put_contents($fileName, $this->yamlDump($array));
    }

    /**
     * @param  array  $array
     * @return yaml
     */
    public function yamlDump($array)
    {
        return trim(Yaml::dump($array, 10, 2));
    }

    /**
     * @param  $src
     * @param  $dest
     * @return mixed
     */
    public function copyDir($src, $dst)
    {
        if (true === is_link($src)) {
            symlink(readlink($src), $dst);
        } elseif (true === is_dir($src)) {
            if (false === is_dir($dst)) {
                mkdir($dst);
            }

            foreach (scandir($src) as $file) {
                if ('.' != $file && '..' != $file) {
                    $this->copyDir("$src/$file", "$dst/$file");
                }
            }
        } elseif (true === is_file($src)) {
            copy($src, $dst);
        } else {
            throw new \RuntimeException("WARNING: Cannot copy $src (unknown file type)");
        }
    }

    /**
     * @param  $machineName
     * @param  $projectName
     * @param  array          $serverConfig
     * @return mixed
     */
    public function getHostInformtion($machineName, $projectName, $serverConfig = [])
    {
        $whichDocker = $this->whichDocker();
        $svrs        = [];

        foreach ($serverConfig as $v) {
            foreach ($v['server_name'] as $server) {
                $svrs[] = $server;
            }
        }

        $containerIp = $this->command($whichDocker." inspect --format 'name={{.Name}}&ip={{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}&service={{index .Config.Labels \"com.docker.compose.service\"}}' $(docker ps -aq)")->toArray();

        $information = [
        ];

        foreach ($containerIp as $value) {
            parse_str($value, $vars);

            if (!$vars['ip']
                || false === strpos($vars['name'], '/'.$machineName.'_'.$projectName.'_')
            ) {
                continue;
            }

            $ip = $vars['ip'];

            if (false !== strpos($value, 'nginx')) {
                $information['nginx'][$ip] = $svrs;
            } elseif (false !== strpos($value, 'mysql')) {
                $information['mysql'][$ip] = [$vars['service']];
            } else {
                $information[str_replace($projectName.'_', '', $vars['service'])][$ip] = [$vars['service']];
            }
        }

        return $information;
    }

    /**
     * @param array $config
     */
    protected function getNginxServerConfig($config)
    {
        $serverConfig = [];

        foreach ($config['services']['nginx']['environment']['vhosts'] as $key => $value) {
            if (isset($value['server_alias']) && is_array($value['server_alias'])) {
                $alias = $value['server_alias'];
            } else {
                $alias = [];
            }

            $serverConfig[] = [
                'server_name'   => array_merge([$value['server_name']], $alias),
                'document_root' => $value['document_root'],
                'port'          => $value['port']
            ];
        }

        return $serverConfig;
    }
}
