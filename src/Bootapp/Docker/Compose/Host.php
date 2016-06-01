<?php
namespace Bootapp\Docker\Compose;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Host extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('docker-compose:host')
            ->setDescription('Host Infomation');
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
            $stageName   = 'local'; //$this->input->getArgument('environment');
            $config      = parent::getConfigFile($stageName);
            $machineName = $config['machine'];
            $projectName = $config['project'];
            $stage       = $config['stages'][$stageName];

            $serverConfig = [];
        }

        // docker
        {
            $this->initDockerMachine($machineName);
        }

        if (true === isset($config['services']['nginx'])) {
            $serverConfig = $this->getNginxServerConfig($stage);
        }

        $information = $this->getHostInformtion($machineName, $projectName, $serverConfig);

        if ($information) {
            $hosts  = $this->yamlDump($information);
            $table  = new \Bootapp\Command\Table();
            $output = $table->header('Host Infomation')
                ->addRows(explode(PHP_EOL, $hosts))
                ->render();

            $this->writeln($output, OutputInterface::VERBOSITY_QUIET, false);
        }
    }
}
