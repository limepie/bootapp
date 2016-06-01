<?php
namespace Bootapp;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Console\Output\OutputInterface;

trait Phinx
{
    /**
     * @var string
     */
    public $defaultEnvironment = 'local';

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stageName = 'local';
        //parent::configure();

        try {
            $array = Yaml::parse(file_get_contents(getcwd().'/env.yml'));
        } catch (ParseException $e) {
            throw new \RuntimeException('Unable to parse the YAML string: %s', $e->getMessage());
        }
        $mysql = $array['stages'][$stageName]['services']['mysql']['environment'];
        parse_str(str_replace(';', '&', $mysql['server']['master']), $out);

        $options                                = [];
        $options[\Pdo::ATTR_ERRMODE]            = \Pdo::ERRMODE_EXCEPTION;
        $options[\Pdo::ATTR_EMULATE_PREPARES]   = false;
        $options[\Pdo::ATTR_STRINGIFY_FETCHES]  = false;
        $options[\Pdo::ATTR_DEFAULT_FETCH_MODE] = \Pdo::FETCH_ASSOC;

        $this->setConfig(new \Phinx\Config\Config([
            'paths'        => [
                'migrations' => 'app/migrations'
                //'seeds'      => 'app/seeds'
            ],
            'environments' => [
                'default_migration_table' => 'migration_log',
                'default_database'        => 'local',
                'local'                   => [
                    'name'       => $out['dbname'],
                    'connection' => new \Pdo($mysql['server']['master'], $mysql['username'], $mysql['password'], $options)
                ]
            ]
        ]));

        parent::execute($input, $output);
    }
}
