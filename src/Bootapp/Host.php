<?php
namespace Bootapp;

class Host extends \Bootapp\Docker\Compose\Host
{
    protected function configure()
    {
        $this
            ->setName('host')
            ->setDescription('Alias docker-compose:host');
    }
}
