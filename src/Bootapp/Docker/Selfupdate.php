<?php
namespace Bootapp\Docker;

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Selfupdate extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('selfupdate')
            ->setDescription('Updates the application.');

        $this->addOption(
            'pre',
            'p',
            InputOption::VALUE_NONE,
            'Allow pre-release updates.'
        );
        $this->addOption(
            'redo',
            'r',
            InputOption::VALUE_NONE,
            'Redownload update if already using current version.'
        );

        $this->disableUpgrade = false;

        if (false === $this->disableUpgrade) {
            $this->addOption(
                'upgrade',
                'u',
                InputOption::VALUE_NONE,
                'Upgrade to next major release, if available.'
            );
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function process()
    {
        $manager = new Manager($k = Manifest::loadFile(
            'https://raw.githubusercontent.com/yejune/bootapp/master/manifest.json'
        ));

//        $result = $manager->update($this->getApplication()->getVersion(), true, true);

        $result = $manager->update(
            $this->getApplication()->getVersion(),
            $this->disableUpgrade ?: (false === $this->input->getOption('upgrade')),
            $this->input->getOption('pre')
        );

        if ($result) {
            $this->output->writeln('<info>Update successful!</info>');
        } else {
            $this->output->writeln('<comment>Already up-to-date.</comment>');
        }
    }
}
