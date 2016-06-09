<?php
namespace Bootapp;

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Selfupdate extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('selfupdate')
            ->setDescription('Updates the application.');
        /*
        $this->addOption(
            'pre',
            'p',
            InputOption::VALUE_NONE,
            'Allow pre-release updates.'
        );

        $this->addOption(
            'major',
            'm',
            InputOption::VALUE_NONE,
            'Upgrade to next major release, if available.'
        );
        */
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function process()
    {
        $manager = new Manager($manifest = Manifest::loadFile(
            'https://raw.githubusercontent.com/yejune/bootapp/master/manifest.json'
        ));

        $result = $manager->update(
            $this->getApplication()->getVersion(),
            true, //$this->input->getOption('major'),
            true  //$this->input->getOption('pre')
        );

        if ($result) {
            $this->output->writeln('<info>Update successful!</info>');
        } else {
            $this->output->writeln('<comment>Already up-to-date.</comment>');
        }
    }
}
