<?php
namespace Bootapp\Docker\Compose;

use Symfony\Component\Console\Command\Command;

class Up extends \Bootapp\Command
{
    protected function configure()
    {
        //starts and provisions the docker environment
        $this
            ->setName('docker-compose:up')
            ->setDescription('Create and start containers')
            /*
            ->addArgument(
                'environment',
                InputArgument::REQUIRED,
                'environment [ local | development | staging | production ]'
            )
            */;
    }

    /**
     * @param string $projectName
     * @param array  $serverConfig
     */
    private function setNginxVhost($projectName, $serverConfig = [])
    {
        $template = file_get_contents(getcwd().'/.docker/nginx/bootapp.conf.tmpl');
        $nginx    = '';

        foreach ($serverConfig as $key => $value) {
            $nginx .= strtr($template, [
                '{{project}}'       => $projectName.'-'.$value['server_name'][0],
                '{{document_root}}' => $value['document_root'],
                '{{server_name}}'   => implode(' ', $value['server_name'])
            ]);
        }

        file_put_contents(getcwd().'/.docker/nginx/bootapp.conf', $nginx);
    }

    /**
     * @param string $projectName
     */
    private function setNginxDockerfile($projectName)
    {
        $template   = file_get_contents(getcwd().'/.docker/nginx/Dockerfile.tmpl');
        $dockerfile = strtr($template, [
            '{{php-server}}' => $projectName.'_'.'php'
        ]);
        file_put_contents(getcwd().'/.docker/nginx/Dockerfile', $dockerfile);
    }

    /**
     * @param string $projectName
     * @param array  $environments
     */
    private function setPhpFpmPool($projectName, $environments = [])
    {
        $template = file_get_contents(getcwd().'/.docker/php/bootapp.pool.conf.tmpl');
        $php      = strtr($template, ['{{project}}' => $projectName]);

        foreach ($environments as $key => $value) {
            $php .= PHP_EOL.'env['.$key.'] = '.$value;
        }

        file_put_contents(getcwd().'/.docker/php/bootapp.pool.conf', $php);
    }

    /**
     * @param $projectName
     * @param array          $environments
     */
    private function setElasticMq($projectName)
    {
        $template = file_get_contents(getcwd().'/.docker/elasticmq/custom.conf.tmpl');
        $php      = strtr($template, ['{{host}}' => $projectName.'_elasticmq']);

        file_put_contents(getcwd().'/.docker/elasticmq/custom.conf', $php);
    }

    /**
     * @param string $projectName
     * @param array  $config
     */
    private function setDockerComposeFile($machineName, $projectName, $config)
    {
        $subnetFile = $this->command('echo $HOME')->toString().'/.docker/docker-machine-subnet.yaml';

        if (false === is_file($subnetFile)) {
            $this->command('touch '.$subnetFile);
        }

        $subnets = $this->yamlParseFile($subnetFile);

        if (false === is_array($subnets)) {
            $subnets = [];
        }

        if (true === isset($subnets[$machineName])) {
            $subnet = $subnets[$machineName];
        } else {
            $whichDocker = $this->whichDocker();
            /*
            $network     = $this->command($whichDocker.' network ls')->toArray();
            array_shift($network);

            $networks = [];

            foreach ($network as $networkString) {
                $networkName = preg_replace('#\s(.*)$#', '', $networkString);
                $networks[]  = $this->command($whichDocker." network inspect --format='{{range .IPAM.Config}}{{.Subnet}}{{end}}' ".$networkName)->toString();
            }
            */
            $bridge = $this->command($whichDocker." network inspect --format='{{range .IPAM.Config}}{{.Subnet}}{{end}}' bridge")->toString();

            $subnetIps = array_values($subnets);

            while (1) {
                $subnet = '172.'.rand(0, 255).'.0.0/16';

                if ($subnet == $bridge) {
                    continue;
                }

                if (false == in_array($subnet, $subnetIps)) {
                    break;
                }
            }
        }

        $subnets[$machineName] = $subnet;
        $this->yamlDumpFile($subnetFile, $subnets);

        // init
        {
            $compose = [
                'version'  => '2',
                'networks' => [
                    'default' => [
                        'ipam' => [
                            'config' => [
                                ['subnet' => $subnet]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $services = $config['services'];

        // application
        {
            if (true === isset($services['nginx']) && true === isset($services['php'])) {
                $compose['services'] = [
                    $projectName.'_'.'application' => [
                        'image'   => 'busybox',
                        'volumes' => ['.:/var/www'],
                        'tty'     => true
                    ]
                ];
            }
        }

        // nginx
        {
            if (true === isset($services['nginx'])) {
                $nginx = $services['nginx'];
                unset($services['nginx'], $nginx['environment']);
                $compose['services'][$projectName.'_nginx'] = array_merge([
                    'links'        => [$projectName.'_'.'php'],
                    'volumes_from' => [$projectName.'_'.'application'],
                    'volumes'      => ['./var/log/nginx:/var/log/nginx']
                ], $nginx);
            }
        }

        // mysql
        {
            if (true === isset($services['mysql'])) {
                $mysql = $services['mysql'];
                unset($services['mysql']);
                $mysqlHostName = [];

                foreach ($mysql['environment']['server'] as $name => $dsn) {
                    $tmp = explode(':', $dsn);
                    parse_str(str_replace(';', '&', $tmp[1]), $p);
                    //$p = array_merge(['prefix' => $tmp[0]], $p);

                    $mysqlHostName[] = $projectName.'_mysql_'.$name;

                    $compose['services'][$projectName.'_mysql_'.$name] = array_merge(
                        $mysql, [
                            'environment' => [
                                'MYSQL_ROOT_PASSWORD' => $mysql['environment']['root_password'],
                                'MYSQL_DATABASE'      => $p['dbname'],
                                'MYSQL_USER'          => $mysql['environment']['username'],
                                'MYSQL_PASSWORD'      => $mysql['environment']['password']
                            ]
                        ]
                    );
                }
            }
        }

        // php
        {
            if (true === isset($services['php'])) {
                $php = $services['php'];
                unset($services['php']);

                $phpLinkService = $config['services'];
                unset($phpLinkService['php'], $phpLinkService['nginx'], $phpLinkService['mysql']);

                $servicesNames = array_merge(array_map(function ($serviceName) use ($projectName) {
                    return $projectName.'_'.$serviceName;
                }, array_keys($phpLinkService)), $mysqlHostName);

                $compose['services'][$projectName.'_php'] = array_merge([
                    'build'        => './.docker/php',
                    'expose'       => ['9000'],
                    'volumes_from' => [$projectName.'_'.'application'],
                    'volumes'      => ['./var/log/php:/var/log']
                ], $php);

                if ($servicesNames) {
                    $compose['services'][$projectName.'_php']['links'] = $servicesNames;
                }
            }
        }

        // other
        {
            foreach ($services as $name => $config) {
                $compose['services'][$projectName.'_'.$name] = $config;
            }
        }

        $this->yamlDumpFile(getcwd().'/docker-compose.yml', $compose);
    }

/**
 * @param string $projectName
 * @param array  $serverConfig
 */
    private function setHostsFile($machineName, $projectName, $serverConfig = [])
    {
        $information = $this->getHostInformtion($machineName, $projectName, $serverConfig);

        $message = $this->command('sudo sed -i -e "/## '.$machineName.' '.$projectName.'/d" /etc/hosts');

        foreach ($information as $type => $info) {
            foreach ($info as $ip => $servers) {
                $message = $this->command('sudo sed -i -e "/'.$ip.'/d" /etc/hosts');
            }
        }

        foreach ($information as $type => $info) {
            foreach ($info as $ip => $servers) {
                foreach ($servers as $server) {
                    $message = $this->command('sudo sed -i -e "/'.$server.'/d" /etc/hosts');
                }
            }
        }

        if ($information) {
            $message = $this->command('sudo -- sh -c -e "echo \'## '.$machineName.' '.$projectName.'\' >> /etc/hosts";');

            foreach ($information as $type => $info) {
                foreach ($info as $ip => $servers) {
                    foreach ($servers as $server) {
                        $message = $this->command('sudo -- sh -c -e "echo \''.$ip.' '.$server.'\' >> /etc/hosts";');
                    }
                }
            }

            $hosts  = $this->yamlDump($information);
            $table  = new \Bootapp\Command\Table();
            $output = $table->header('Host Infomation')
                ->addRows(explode(PHP_EOL, $hosts))
                ->render();

            $this->message($output, 'result');
        }

        $this->message('Setting /etc/hosts file', 'ok');
    }

    /**
     * @param $projectName
     */
    private function setPhpComposer($projectName)
    {
        $whichDocker = $this->whichDocker();
        $chk         = $this->command('if [ -d vendor ]; then echo "true"; else echo "false"; fi')->toBool();

        {
            if (true === $chk) {
                //$message = $this->command('docker exec -i $(docker ps -f name=php -q) sh -c  "cd /var/www/ && composer update --prefer-dist -vvv --profile"');
                $this->message("Please run './vendor/bin/bootapp composer update -vvv'", 'ok');
            } else {
                $message = $this->command($whichDocker.' exec -i $(docker ps -f name='.$projectName.'_php -q) sh -c  "cd /var/www/ && composer install --prefer-dist -vvv --profile"');
                $this->message('run composer', 'ok');
            }
        }
    }

    public function process()
    {
        // config
        {
            $this->writeln('<info>Config</info>');
            $this->command('sudo -v');

            $stageName       = 'local'; //$this->input->getArgument('environment');
            $config          = parent::getConfigFile($stageName);
            $machineName     = $config['machine'];
            $projectName     = $config['project'];
            $stage           = $config['stages'][$stageName];
            $safeProjectName = str_replace(['.', '-'], '', strtolower($projectName));
            $safeMachineName = str_replace(['.', '-'], '', strtolower($machineName));

            $serverConfig = [];

            if (false === is_dir(getcwd().'/.docker/')) {
                $this->copyDir('phar://bootapp.phar/src/Dockerfiles/', getcwd().'/.docker/');
            }

            if (false === is_dir(getcwd().'/.template/')) {
                //mkdir(getcwd().'/.template', 0777);
                $this->command('mkdir '.getcwd().'/.template');
                $this->command('chmod 777 '.getcwd().'/.template');
            }
        }

        // brew, docker, vbox, docker-machine, docker-compose 설치 확인
        {
            $this->writeln('');
            $this->writeln('<info>Initializing docker-machine</info>');

            $whichBrew          = $this->whichBrew();
            $whichDockerMachine = $this->whichDockerMachine();
            $whichDocker        = $this->whichDocker();
            $whichDockerCompose = $this->whichDockerCompose();
        }

        // docker
        {
            $this->initDockerMachine($machineName);
        }

        // compose file 생성
        {
            if (true === isset($config['services']['nginx'])) {
                $serverConfig = $this->getNginxServerConfig($stage);
                $this->setNginxVhost($safeProjectName, $serverConfig);
                $this->setNginxDockerfile($safeProjectName);
            }

            if (true === isset($config['services']['php'])) {
                $this->setPhpFpmPool($safeProjectName, [
                    'PROJECT_NAME' => $safeProjectName,
                    'STAGE_NAME'   => $stageName
                ]);
            }

            if (true === isset($config['services']['elasticmq'])) {
                $this->setElasticMq($safeProjectName);
            }

            $stage = array_merge_recursive(['services' => $config['services']], $stage);
            $this->setDockerComposeFile($machineName, $safeProjectName
                , $stage);
        }

        // compose up
        {
            // docker machine ip
            $this->writeln('');
            $this->writeln('<info>Check docker-machine IP</info>');
            $dockerMachineIp = $this->command($whichDockerMachine.' ip '.$machineName);
            $this->writeln('└─ docker-machine IP: '.$dockerMachineIp);

            $this->writeln('');
            $this->writeln('<info>Docker compose</info>');

//            $message = $this->command($whichDockerCompose.' -p '.$machineName.' down');
            /*
            $message = $this->command($whichDockerCompose.' -p '.$machineName.' down');
            putenv('COMPOSE_HTTP_TIMEOUT=0');
            --remove-orphans docker-compose.yml 에 없는 설정 강제로 지우기
            */
            $compose = $this->command($whichDockerCompose.' -p '.$machineName.' up -d --build');

            if (1 === preg_match('#Creating network "([^"]+)" with#', $compose, $match)) {
                $networkName = $match[1];
            } else {
                $networkName = $safeMachineName.'_'.'default';
            }

            // container network
            $containerSubnet = $this->command($whichDocker." network inspect --format='{{range .IPAM.Config}}{{.Subnet}}{{end}}' ".$networkName)->toString();

            // route
            $this->writeln('');
            $this->message('Add static routes', 'title');

            $message = $this->command('sudo route -n delete '.$containerSubnet.' '.$dockerMachineIp);
            $message = $this->command('sudo route -n add '.$containerSubnet.' '.$dockerMachineIp);
            $this->writeln('Setting route', 'ok');
        }

        // compose install
        {
            if (true === isset($stage['services']['php'])) {
                $this->writeln('');
                $this->message('Php composer install', 'title');

                $this->setPhpComposer($safeProjectName);
            }
        }

        // hosts 설정
        {
            $this->writeln('');
            $this->message('Setting hosts', 'title');

            $this->setHostsFile($safeMachineName, $safeProjectName, $serverConfig);
        }
    }
}
