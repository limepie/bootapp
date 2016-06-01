<?php
namespace Bootapp;

use Symfony\Component\Console\Input\InputOption;

class Ps extends \Bootapp\Docker\Ps
{
    protected function configure()
    {
        $this
            ->setName('ps')
            ->setDescription('Alias docker:ps')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Format the output using the given go template'
            );
    }
}
