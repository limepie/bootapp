<?php
namespace App\Traits;

trait Machine
{
    /**
     * @var string
     */
    public $machineIP = '';
    /**
     * @var string
     */
    public $uuid = '';
    /**
     * @var array
     */
    public $hostOnlyAdapters = [];
    /**
     * @var array
     */
    public $sharedFolders = [];

    public function machineHalt()
    {
        $machineName = $this->getMachineName();

        $this->message('Machine "'.$machineName.'" HALT');
        $this->message('');
        $command = [
            'docker-machine',
            'stop',
            $machineName,
            '2>&1',
        ];
        $this->process($command, ['print' => true]);
        $this->removeNetwork();
    }

    private function checkMachineInfo()
    {
        $machineName = $this->getMachineName();

        $command = [
            'vboxmanage',
            'showvminfo',
            $machineName,
            '--details',
            '--machinereadable',
        ];
        $raw              = $this->process($command, ['print' => false])->toArray();
        $uuid             = '';
        $hostOnlyAdapters = [];
        $sharedFolders    = [];
        $status           = '';
        $rawDatas         = [];

        foreach ($raw as $str) {
            list($key, $value)         = explode('=', $str, 2);
            $rawDatas[trim($key, '"')] = trim($value, '"');
        }

        foreach ($rawDatas as $key => $value) {
            if ('UUID' == $key) {
                $uuid = $value;
            }

            if (1 === preg_match('#hostonlyadapter(\d+)#i', $key, $match)) {
                $hostOnlyAdapters[$match[1]] = $value;
            }

            if (1 === preg_match('#SharedFolderNameMachineMapping(\d+)#i', $key, $match)) {
                $sharedFolders[$value] = $rawDatas['SharedFolderPathMachineMapping'.$match[1]];
            }

            if ('VMState' == $key) {
                $status = $value;
            }
        }

        $this->uuid             = $uuid;
        $this->hostOnlyAdapters = $hostOnlyAdapters;
        $this->sharedFolders    = $sharedFolders;
    }

    private function setDocerHost()
    {
        $machineName = $this->getMachineName();

        if (true === isset($config['docker_host']) && $config['docker_host']) {
            putenv('DOCKER_HOST='.$config['docker_host']);

            if (true === isset($config['docker_cert']) && $config['docker_cert']) {
                putenv('DOCKER_TLS_VERIFY=1');
                putenv('DOCKER_CERT_PATH='.$config['docker_cert']);
            } else {
                putenv('DOCKER_TLS_VERIFY');
                putenv('DOCKER_CERT_PATH');
            }
        } else {
            $count = 0;

            while (true) {
                $count++;

                if (10 === $count) {
                    throw new \Peanut\Console\Exception('Error checking TLS connection: Host is not running');
                }

                $command = [
                    'docker-machine',
                    'env',
                    $machineName,
                    '2>&1',
                ];
                $env = $this->process($command, ['print' => false]);

                //Error checking and/or regenerating the certs
                if (false !== strpos($env->toString(), 'regenerate-certs')
                    || false !== strpos($env->toString(), 'Could not read CA certificate')) {
                    $this->certsDockerMachine();
                } elseif (false === strpos($env->toString(), 'Error checking TLS connection:')) {
                    break;
                }

                sleep(1);
            }

            foreach ($env->toArray() as $export) {
                if (1 === preg_match('/export (?P<key>.*)="(?P<value>.*)"/', $export, $match)) {
                    putenv($match['key'].'='.$match['value']);
                    $_ENV[$match['key']] = $match['value'];
                }
            }
        }

        if (!getenv('DOCKER_HOST')) {
            throw new \Peanut\Console\Exception('docker-host not found');
        }

        $this->message(\Peanut\Console\Color::text('docker  | ', 'white').getenv('DOCKER_HOST'));
    }

    private function removeNetwork()
    {
        $machineName = $this->getMachineName();

        foreach ($this->hostOnlyAdapters as $name) {
            $command = [
                'VBoxManage',
                'list',
                'hostonlyifs',
                '|',
                'grep',
                $name,
                '2>&1',
            ];
            $chk = $this->process($command, ['print' => false])->toString();

            if ($chk) {
                $command = [
                    'VBoxManage',
                    'hostonlyif',
                    'remove',
                    $name,
                ];
                $this->process($command, ['print' => false]);
            }
        }
    }

    private function xxreloadNetwork()
    {
        foreach ($this->hostOnlyAdapters as $name) {
            $command = [
                'VBoxManage',
                'list',
                'hostonlyifs',
                '|',
                'grep',
                $name,
                '2>&1',
            ];
            $chk = $this->process($command, ['print' => false])->toString();

            if ($chk) {
                $command = [
                    'sudo',
                    'ifconfig',
                    $name,
                    'down',
                    '&&',
                    'sudo',
                    'ifconfig',
                    $name,
                    '2>&1',
                ];
                $this->process($command, ['print' => false]);
            }
        }
    }

    /**
     * @return string
     */
    private function getMachineStatus()
    {
        $machineName = $this->getMachineName();
        $command     = [
            'docker-machine',
            'status',
            $machineName,
            '2>&1',
        ];

        return strtolower($this->process($command, ['print' => false])->toString());
    }

    /**
     * @return null
     */
    public function initMachine()
    {
        $machineStatus = $this->getMachineStatus();
        $this->message('docker  | machine status "'.$machineStatus.'"');

        if ('running' === $machineStatus) {
            $this->startDockerMachine();
        } elseif ('stopped' === $machineStatus) {
            $this->removeNetwork();
            $this->startDockerMachine();
        } elseif ('paused' === $machineStatus) {
            $this->resumeDockerMachine();
        } elseif ('saved' === $machineStatus) {
            $this->discardDockerMachine();
            $this->removeNetwork();
            $this->startDockerMachine();
        } elseif ('error' === $machineStatus) {
            $this->deleteDockerMachine();
            $this->createDockerMachine();
            $this->startDockerMachine();
        } else {
            $this->createDockerMachine();
            $this->startDockerMachine();
        }

        /*
        //$this->reloadNetwork($machineName);
        $msg = $this->process('docker-machine env '.$machineName, ['print' => true]);

        if ('Error checking TLS connection: Host is not running' === $msg) {
            throw new \Exception('Error checking TLS connection: Host is not running');
        }
        */
    }

    private function resumeDockerMachine()
    {
        $machineName = $this->getMachineName();

        $command = [
            'vboxmanage',
            'controlvm',
            $machineName,
            'resume',
        ];
        $this->process($command, ['print' => true]);
    }

    private function discardDockerMachine()
    {
        $machineName = $this->getMachineName();
        $a           = $this->ask('Machine Saved, discardstate? [y/N]: ');

        if (false === in_array($a, ['y', 'Y'])) {
            throw new \Peanut\Console\Exception('machine status saved. please check.');
        }

        $command = [
            'vboxmanage',
            'discardstate',
            $machineName,
        ];
        $this->process($command, ['print' => true]);
    }

    private function deleteDockerMachine()
    {
        $machineName = $this->getMachineName();
        $a           = $this->ask('Machine Error, delete? [y/N]: ');

        if (false === in_array($a, ['y', 'Y'])) {
            throw new \Peanut\Console\Exception('machine status error. please check.');
        }

        $command = [
            'docker-machine',
            'rm',
            '-f',
            $machineName,
        ];
        $this->process($command, ['print' => true]);
    }

    private function startMachine()
    {
        $machineName = $this->getMachineName();
        $this->message('Starting docker-machine');

        $command = [
            'docker-machine',
            'start',
            $machineName,
        ];
        $this->process($command, ['print' => true]);
    }

    private function certsDockerMachine()
    {
        $machineName = $this->getMachineName();
        $command     = [
            'docker-machine',
            'regenerate-certs',
            '-f',
            $machineName,
        ];
        $this->process($command, ['print' => true]);
    }

    private function createDockerMachine()
    {
        $machineName = $this->getMachineName();
        $this->message('Creating docker-machine');
        $command = [
            'docker-machine',
            'create',
            '--driver=virtualbox',
            '--virtualbox-memory=2048',
            '--virtualbox-disk-size=200000',
            '--virtualbox-cpu-count=2',
            $machineName,
        ];
        $this->process($command, ['print' => true]);
    }

    /**
     * @param $machineName
     */
    public function startDockerMachine()
    {
        $this->checkMachineInfo();

        $this->setSharedFolder();
        $status = $this->getMachineStatus();

        if ('stopped' === $status) {
            $this->startMachine();
        }

        $this->setMount();

        $this->setDocerHost();

        echo 'docker  | engine ready .';
        $i = 0;

        while (true) {
            $i++;

            if (10 == $i) {
                throw new \Peanut\Console\Exception('Please check your docker connection and try again.');
            }

            $command = [
                'docker',
                'info',
                '2>&1',
            ];

            $tmp = $this->process($command, ['print' => false])->toString();
            echo '.';

            if (1 === preg_match('#^Containers#', $tmp)) {
                //sleep(1);
                echo ' ok'.PHP_EOL;
                break;
            }

            sleep(1);
        }
    }

    public function setSharedFolder()
    {
        $machineName = $this->getMachineName();
        // 검사할것 없이 /Volumes만 mount하면 됨, 딱한번만 하면 됨
        list($first, $name, $third) = explode(DIRECTORY_SEPARATOR, getcwd(), 3);
        $path                       = implode(DIRECTORY_SEPARATOR, [$first, $name]);

        if (false === in_array($name, array_keys($this->sharedFolders))) {
            $status = $this->getMachineStatus();

            if ('running' == $status) {
                $command = [
                    'VBoxManage',
                    'controlvm',
                    $machineName,
                    'acpipowerbutton',
                ];
                $this->process($command, ['print' => false]);
                echo 'stop vm ';

                while (true) {
                    $command = [
                        'VBoxManage',
                        'showvminfo',
                        $machineName,
                        '--machinereadable',
                        '|',
                        'grep',
                        'VMState=',
                        '2>&1',
                    ];
                    $tmp = $this->process($command, ['print' => false])->toString();
                    echo '.';

                    if ('VMState="poweroff"' == $tmp) {
                        //sleep(1);
                        echo ' ok'.PHP_EOL;
                        break;
                    }

                    sleep(3);
                }
            }

            echo 'sharedfolder add'.PHP_EOL;

            $command = [
                'VBoxManage',
                'sharedfolder',
                'add',
                $machineName,
                '--name',
                $name,
                '--hostpath',
                $path,
                '--automount',
            ];
            $this->process($command, ['print' => false]);
        }
    }

    public function setMount()
    {
        $machineName                = $this->getMachineName();
        list($first, $name, $third) = explode(DIRECTORY_SEPARATOR, getcwd(), 3);
        $path                       = implode(DIRECTORY_SEPARATOR, [$first, $name]);

        /*
        $command = [
            'docker-machine',
            'ssh',
            $machineName,
            '"',
            'sudo',
            'mkdir',
            '-p',
            $path,
            '"'
        ];

        $this->process($command, ['print' => false]);

        $command = [
            'docker-machine',
            'ssh',
            $machineName,
            '"',
            'sudo',
            'mount',
            '-t',
            'vboxsf',
            '-o',
            'uid=0,gid=0',
            $name,
            $path,
            '"'
        ];
        $this->process($command, ['print' => false]);
        */
        $command = [
            'docker-machine',
            'ssh',
            $machineName,
            'echo',
            "'",
            '"',
            'if mount|grep '.$path.' > /dev/null 2>&1; then',
            PHP_EOL,
            'umount',
            $path,
            PHP_EOL,
            'fi',
            '"',
            '|',
            'sudo',
            'tee',
            '/mnt/sda1/var/lib/boot2docker/bootlocal.sh',
            "'",
        ];

        $this->process($command, ['print' => false]);

        $command = [
            'docker-machine',
            'ssh',
            $machineName,
            'echo',
            "'",
            '"',
            'mkdir',
            '-p',
            $path,
            '"',
            '|',
            'sudo',
            'tee',
            '-a',
            '/mnt/sda1/var/lib/boot2docker/bootlocal.sh',
            "'",
        ];
        $this->process($command, ['print' => false]);
        $command = [
            'docker-machine',
            'ssh',
            $machineName,
            'echo',
            "'",
            '"',
            'mount',
            '-t',
            'vboxsf',
            '-o',
            // 'umask=0022,gid=50,uid=1000',
            'uid=1000,gid=50,dmode=0777,fmode=0777',
            // 'uid=`id -u docker`,gid=`id -g docker`,dmode=0777,fmode=0777',
            $name,
            $path,
            '"',
            '|',
            'sudo',
            'tee',
            '-a',
            '/mnt/sda1/var/lib/boot2docker/bootlocal.sh',
            "'",
        ];
        $this->process($command, ['print' => false]);

        $command = [
            'docker-machine',
            'ssh',
            $machineName,
            "'",
            'sudo',
            'sh',
            '/mnt/sda1/var/lib/boot2docker/bootlocal.sh',
            "'",
        ];

        $this->process($command, ['print' => false]);
    }

    public function setRoute()
    {
        $machineName = $this->getMachineName();

        /*
        $docker0 = $this->process('docker-machine ssh '.$machineName.' ifconfig docker0 2>&1', ['print' => false]);

        if (1 === preg_match('#inet addr:(.*)\sBcast#', $docker0, $match)) {
            $containerSubnet = trim($match[1]).'/16';
        } else {
            throw new \Peanut\Console\Exception('guest subnet ip not found');
        }
        */

        foreach ($this->networkName as $networkName) {
            $command = [
                'docker',
                'network',
                'inspect',
                $networkName,
            ];
            $networks        = $this->process($command, ['print' => false])->jsonToArray();
            $containerSubnet = '';

            foreach ($networks as $network) {
                if (true === isset($network['IPAM']['Config'])) {
                    foreach ($network['IPAM']['Config'] as $config) {
                        $containerSubnet = $config['Subnet'];
                    }
                }
            }

            if (!$containerSubnet) {
                throw new \Peanut\Console\Exception('guest subnet ip not found');
            }

            /*
            $eth1 = $this->process('docker-machine ssh '.$machineName.' ifconfig eth1 2>&1', ['print' => false]);

            if (1 === preg_match('#inet addr:(.*)\sBcast#', $eth1, $match)) {
                $dockerMachineIp = trim($match[1]);
            } else {
                throw new \Peanut\Console\Exception('guest ip not found');
            }
            */
            $dockerMachineIp = $this->getMachineIp();

            if (!$dockerMachineIp) {
                throw new \Peanut\Console\Exception('guest ip not found');
            }

            $this->message(\Peanut\Console\Color::text('route   | ', 'white').'add '.$containerSubnet.' '.$dockerMachineIp);

            $command = [
                'sudo',
                'route',
                '-n',
                'delete',
                $containerSubnet,
                $dockerMachineIp,
            ];
            $message = $this->process($command, ['print' => false]);

            $command = [
                'sudo',
                'route',
                '-n',
                'add',
                $containerSubnet,
                $dockerMachineIp,
            ];
            $message = $this->process($command, ['print' => false]);
        }
    }

    /**
     * @param $config
     */
    public function setHost()
    {
        $stageName   = $this->getStageName();

        $serviceList = [];

        $stageService = isset($this->config['stages'][$stageName]['services']) ? $this->config['stages'][$stageName]['services'] : [];

        if (false === is_array($this->config['services'])) {
            $this->config['services'] = [];
        }
        foreach ($this->config['services'] + $stageService as $key => $value) {
            $serviceList[] = $this->getContainerName($key);
        }

        $command = [
            'docker',
            'inspect',
            '--format="name={{.Name}}&ip={{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}&service={{index .Config.Labels \"com.docker.bootapp.service\"}}&id={{.Id}}&env={{json .Config.Env}}"',
            '$(docker ps -q)',
        ];

        $inspectList = $this->process($command, ['print' => false])->toArray();

        //print_r($inspectList);
        //exit();
        $machineName = $this->getMachineName();
        $projectName = $this->getProjectName();

        $id      = $machineName.'_'.$projectName;
        $command = [
            'sudo',
            'sed',
            '-i',
            '-e',
            '"/## '.$id.'/d"',
            '/etc/hosts',
        ];

        //echo PHP_EOL.implode(' ', $command).PHP_EOL.PHP_EOL.PHP_EOL;
        $this->process($command, ['print' => false]);

        $domainList = [];

        foreach ($inspectList as $str) {
            $split = explode('&env=', $str, 2);

            parse_str($split[0], $b);

            $envs = json_decode($split[1], true);

            $name = ltrim($b['name'], '/');

            if (true === in_array($name, $serviceList)) {
                foreach ($envs as $env) {
                    list($key, $domain) = explode('=', $env, 2);

                    if ('DOMAIN' == $key) {
                        $domainList[$b['ip']] = $domain;
                    }
                }
            }
        }

        foreach ($domainList as $ip => $domain) {
            $command = [
                'sudo',
                'sed',
                '-i',
                '-e',
                '"/^'.$ip.' /d"',
                '/etc/hosts',
                '2>&1',
            ];
            //echo PHP_EOL.implode(' ', $command).PHP_EOL;

            $this->process($command, ['print' => false]);
        }

        foreach ($domainList as $ip => $domain) {
            $domains = explode(' ', $domain);
            foreach ($domains as $domainName) {
                $command = [
                    'sudo',
                    'sed',
                    '-i',
                    '-e',
                    '"/ '.$domainName.' /d"',
                    '/etc/hosts',
                    '2>&1',
                ];
                //echo PHP_EOL.implode(' ', $command).PHP_EOL;

                $this->process($command, ['print' => false]);
            }
        }

        foreach ($domainList as $ip => $domain) {
            $str = '';
            $str .= str_pad($ip, 15, ' ', STR_PAD_RIGHT);
            $str .= ' ';
            $str .= str_pad($domain, 20, ' ', STR_PAD_RIGHT);

            $command = [
                'sudo',
                '--',
                'sh',
                '-c',
                '-e',
                '"echo \''.$str.'          ## '.$id.'\' >> /etc/hosts";',
            ];
            //echo PHP_EOL.implode(' ', $command).PHP_EOL;

            $this->process($command, ['print' => false]);
        }
    }
    public function getCertHashByDomain($domain)
    {
        $tmp = $this->process('sudo /usr/bin/security find-certificate -a -Z -c "'.$domain.'"', ['print' => false]);

        $items = explode('SHA-1 ', $tmp);
        $hash  = '';
        foreach ($items as $item) {
            if (false !== strpos($item, '"'.$domain.'"')) {
                if (preg_match("#hash: ([^\n]+)#", $item, $m)) {
                    $hash = $m['1'];
                }
            }
        }

        return $hash;
    }
    public function setCert()
    {
        $stageName   = $this->getStageName();
        $machineName = $this->getMachineName();
        $projectName = $this->getProjectName();

        $compose = \App\Helpers\Yaml::parseFile(getcwd().'/docker-compose.'.$stageName.'.yml');
        $domainList = [];
        if (true === isset($compose['services'])) {
            foreach ($compose['services'] as $name => $service) {
                $env = isset($service['environment']) ? $service['environment'] : [];

                if (isset($env['DOMAIN']) && isset($env['USE_SSL'])) {
                    $domains = explode(' ', $env['DOMAIN']);
                    foreach ($domains as $domain) {
                        $domainList[] = $domain;
                    }
                }
            }
        }

        if ($domainList) {
            $SSL_DIR = getcwd().'/var/certs';
            if (false === is_dir(getcwd().'/var')) {
                mkdir(getcwd().'/var');
            }
            if (false === is_dir(getcwd().'/var/certs')) {
                mkdir(getcwd().'/var/certs');
            }

            shell_exec('mkdir -p '.$SSL_DIR);

            foreach ($domainList as $ip => $domain) {
                $sslname = $SSL_DIR.'/'.$domain;

                $this->message(\Peanut\Console\Color::text('cert    | ', 'white').'domain '.$domain);
                //$this->message(\Peanut\Console\Color::text('        | ', 'white').'key     ./var/certs/'.$domain.'.key');

                $certfile = $SSL_DIR.'/'.$domain.'.crt';
                $certfile2 = $SSL_DIR.'/'.$domain.'.key';
                if (file_exists($certfile)) {
                    @unlink($certfile);
                }

                if (file_exists($certfile2)) {
                    @unlink($certfile2);
                }
                if (false === file_exists($certfile)) {
                    $command = [
                        'openssl',
                        'genrsa',
                        '-out',
                        $sslname.'.key',
                        '4096',
                    ];
                    $this->process($command, ['print' => false]);

                    $command = [
                        'rm -rf',
                        '/tmp/openssl.cnf'
                    ];
                    $this->process($command, ['print' => false]);

                    $command = [
                        'cp',
                        '/System/Library/OpenSSL/openssl.cnf',
                        '/tmp/openssl.cnf'
                    ];
                    $this->process($command, ['print' => false]);

                    $command = [
                        'echo',
                        '"[SAN]\nsubjectAltName=DNS:'.$domain.'"',
                        '>>',
                        '/tmp/openssl.cnf'
                    ];
                    $this->process($command, ['print' => false]);

                    $command = [
                        'openssl',
                        'req',
                        '-new',
                        '-x509',
                        '-key',
                        $sslname.'.key',
                        '-out',
                        $sslname.'.crt',
                        '-sha256',
                        '-days',
                        '3650',
                        '-subj',
                        '/C=US/ST=CA/L=MV/CN='.$domain,
                        '-reqexts SAN',
                        '-extensions SAN',
                        '-config',
                        '/tmp/openssl.cnf',
                    ];

                    $this->process($command, ['print' => false]);
                } else {
                }
                if (true === file_exists($certfile)) {
                    if ($hash = $this->getCertHashByDomain($domain)) {
                        $this->process('sudo security delete-certificate -Z '.$hash.' /Library/Keychains/System.keychain', ['print' => false]);
                    }

                    // mount 된 경로에 project forlder가 있을 경우 파일 위치 못찾는 현상 수정
                    $tmpCertFile = '/tmp/'.md5($domain.'.crt');

                    $this->process('rm -rf '.$tmpCertFile, ['print' => false]);
                    $this->process('cp '.$certfile.' '.$tmpCertFile, ['print' => false]);

                    $this->process('sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain '.$tmpCertFile, ['print' => false]);

                    $this->process('rm -rf '.$tmpCertFile, ['print' => false]);

                    $this->message(\Peanut\Console\Color::text('        | ', 'white').'trusted ./var/certs/'.$domain.'.crt');
                } else {
                    //error
                }
            }
        }
    }
}
