<?php
namespace Bootapp\Project;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('project:create')
            ->setDescription('Display detailed network information')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'project name'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function process()
    {
        // config
        {
            $this->message('Config', 'title');
            $stageName   = 'local'; //$this->input->getArgument('enviroment');
            $projectName = $this->input->getArgument('name');

            if (!preg_match('#^([0-9a-zA-Z\-\.]+)$#', $projectName)) {
                throw new \RuntimeException('Allowed project name chars are: 0-9a-z');
            }

            $safeProjectName = str_replace(['.', '-'], '', strtolower($projectName));
        }

        {
            $this->writeln('');
            $this->message('Run', 'title');

            $messages = $this->command('git clone https://github.com/yejune/skeleton '.$projectName);
            $this->writeln($messages, OutputInterface::VERBOSITY_QUIET);
            $messages = $this->command('rm -rf '.$projectName.'/.git ');

            $envFile = getcwd().'/'.$projectName.'/env.yml';
            $this->message('create folder '.$projectName, '>result');
            $config            = $this->yamlParseFile($envFile);
            $config['project'] = $projectName;

            $services = $config['stages'][$stageName]['services'];

            foreach ($config['stages'][$stageName]['services']['nginx']['environment']['vhosts'] as &$vhost) {
                $vhost['server_name'] = $safeProjectName.'.local.com';
            }

            $config['stages'][$stageName]['services']['mysql']['environment']['username'] = $safeProjectName.'_user';
            $config['stages'][$stageName]['services']['mysql']['environment']['password'] = $safeProjectName.'_pass';

            foreach ($config['stages'][$stageName]['services']['mysql']['environment']['server'] as $name => &$dsn) {
                $tmp = explode(':', $dsn);
                parse_str(str_replace(';', '&', $tmp[1]), $p);
                //$p         = array_merge(['prefix' => $tmp[0]], $p);
                $p['host']   = $safeProjectName.'_mysql_'.$name;
                $p['dbname'] = $safeProjectName;
                $dsn         = $tmp[0].':'.http_build_query($p, null, ';');
            }

            $this->yamlDumpFile($envFile, $config);
            $this->message('project name update', '>result');
        }
    }
}
