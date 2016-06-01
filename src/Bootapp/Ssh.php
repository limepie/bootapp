<?php
namespace Bootapp;

class Ssh extends \Bootapp\Docker\Machine\Ssh
{
    protected function configure()
    {
        $this
            ->setName('ssh')
            ->setDescription('Alias docker-machine:ssh');
    }
}
