<?php
namespace Bootapp\Docker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class Ps extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('ps')
            ->setDescription('List containers')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Format the output using the given go template'
            );
    }

    protected function process()
    {
        // config
        {
            $this->message('Config', 'title');
            $stageName   = 'local'; //$this->input->getArgument('enviroment');
            $config      = parent::getConfigFile($stageName);
            $machineName = $config['machine'];
            $projectName = $config['project'];
        }

        // docker
        {
            $this->initDockerMachine($machineName);
        }

        $whichDocker = $this->whichDocker();

        $formatString = '';
        $format       = $this->input->getOption('format');

        if ('all' === $format) {
            $formatString = ' --format=\'ID={{.ID}}&SERVICE={{.Label "com.docker.compose.service"}}&IMAGE={{.Image}}&COMMAND={{.Command}}&CREATED={{.CreatedAt}}&RUNNING={{.RunningFor}}&PORTS={{.Ports}}&STATUS={{.Status}}&SIZE={{.Size}}&NAMES={{.Names}}\'';

            $ps = $this->command($whichDocker.' ps -aq'.$formatString)->toArray();

            if ($ps) {
                $table = [];

                foreach ($ps as $container) {
                    parse_str($container, $tmp);

                    if (true === isset($tmp['ID'])) {
                        $table[] = $tmp;
                    }
                }

                usort($table, function ($a, $b) {
                    return strcmp($a['NAMES'], $b['NAMES']);
                });

                if ($table) {
                    array_unshift($table, ['CONTAINER ID', 'SERVICE', 'IMAGE', 'COMMAND', 'CREATED', 'RUNNING', 'PORTS', 'STATUS', 'SIZE', 'NAMES']);
                    $this->message(table($table), '>result');
                } else {
                }
            }
        } elseif ($format) {
            if (false !== strpos($format, '\"')) {
                $formatString = ' --format="'.$format.'"';
            } elseif (false !== strpos($format, "\'")) {
                $formatString = " --format='".$format."'";
            } elseif (false !== strpos($format, "'")) {
                $formatString = ' --format="'.$format.'"';
            } else {
                $formatString = " --format='".$format."'";
            }

            $ps = $this->command($whichDocker.' ps -aq'.$formatString)->toArray();
            $this->message($ps, 'result');
        } else {
            $formatString = ' --filter="name='.$machineName.'_'.$projectName.'_" --format=\'ID={{.ID}}&SERVICE={{.Label "com.docker.compose.service"}}&COMMAND={{.Command}}&RUNNING={{.RunningFor}}&STATUS={{.Status}}&NAMES={{.Names}}\'';

            $ps = $this->command($whichDocker.' ps -aq'.$formatString)->toArray();

            if ($ps) {
                $table = [];

                foreach ($ps as $container) {
                    parse_str($container, $tmp);

                    if (true === isset($tmp['ID'])) {
                        $table[] = $tmp;
                    }
                }

                usort($table, function ($a, $b) {
                    return strcmp($a['NAMES'], $b['NAMES']);
                });

                if ($table) {
                    array_unshift($table, ['CONTAINER ID', 'SERVICE', 'COMMAND', 'RUNNING', 'STATUS', 'NAMES']);
                    $this->message(table($table), '>result');
                } else {
                }
            }
        }
    }
}

/**
 * @param  $data
 * @return mixed
 */
function table($data)
{
    // Find longest string in each column
    $columns = [];

    foreach ($data as $row_key => $row) {
        $i = -1;

        foreach ($row as $cell) {
            $i++;
            $length = strlen($cell);

            if (empty($columns[$i]) || $columns[$i] < $length) {
                $columns[$i] = $length;
            }
        }
    }

    $ret = [];

    foreach ($data as $row_key => $row) {
        $i     = -1;
        $table = '';

        foreach ($row as $cell) {
            $i++;
            $table .= str_pad($cell, $columns[$i]).'   ';
        }

        $ret[] = $table;
    }

    return $ret;
}

//CONTAINER ID        IMAGE                         COMMAND                  CREATED             STATUS              PORTS                                NAMES
