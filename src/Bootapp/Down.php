<?php
namespace Bootapp;

class Down extends \Bootapp\Docker\Compose\Down
{
    protected function configure()
    {
        $this
            ->setName('down')
            ->setDescription('Alias docker-compose:down');
    }
}
