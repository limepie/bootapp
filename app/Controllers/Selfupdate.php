<?php
namespace App\Controllers;

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;

class Selfupdate extends Command
{
    /**
     * @var string
     */
    public $command = 'self-update';

    /**
     * @param \Peanut\Console\Application $app
     */
    public function configuration(\Peanut\Console\Application $app)
    {
    }
    /**
     * @param \Peanut\Console\Application $app
     */
    public function execute(\Peanut\Console\Application $app)
    {
        $manager = new Manager($manifest = Manifest::loadFile(
            'https://raw.githubusercontent.com/yejune/bootapp/master/manifest.json'
        ));
        $result = $manager->update(
            $app->getApplicationVersion(),
            true, //$this->input->getOption('major'),
            true  //$this->input->getOption('pre')
        );

        if ($result) {
            $this->message('Update successful!');
        } else {
            $this->message('Already up-to-date.');
        }
    }
}
