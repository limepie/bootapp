<?php
namespace Bootapp;

class Up extends \Bootapp\Docker\Compose\Up
{
    protected function configure()
    {
        $this
            ->setName('up')
            ->setDescription('Alias docker-compose:up');
    }
}
