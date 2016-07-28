<?php
namespace App\Traits\Docker;

trait Compose
{
    public function fileGenerate()
    {
        $config = $this->getConfig();

        if (true === isset($config['stages'])) {
            $stages = $config['stages'];
        } else {
            $stages = [
                'local' => [
                    'services' => []
                ]
            ];
        }

        foreach ($stages as $stageName => $stage) {
            $compose = [
                'version' => '2'
            ];

            if (true === isset($config['volumes']) || true === isset($stage['volumes'])) {
                if (false === isset($config['volumes'])) {
                    $config['volumes'] = [];
                }

                if (false === isset($stage['volumes'])) {
                    $stage['volumes'] = [];
                }

                $compose['volumes'] = array_merge_recursive($config['volumes'], $stage['volumes']);
            }

            if (true === isset($config['services']) || true === isset($stage['services'])) {
                if (false === isset($config['services'])) {
                    $config['services'] = [];
                }

                if (false === isset($stage['services'])) {
                    $stage['services'] = [];
                }

                $services = array_merge_recursive($config['services'], $stage['services']);

                $links = [];

                foreach ($services as $key => $value) {
                    $links[$key] = isset($value['links']) ? $value['links'] : [];
                }

                try {
                    $result = \App\Helpers\Dependency::sort($links);
                } catch (\Exception $e) {
                    throw new \Console\Exception($e);
                }

                $compose['services'] = [];

                foreach ($result as $serviceName) {
                    $compose['services'][$serviceName] = $services[$serviceName];
                }
            }

            if (true === isset($config['networks']) || true === isset($stage['networks'])) {
                if (false === isset($config['networks'])) {
                    $config['networks'] = [];
                }

                if (false === isset($stage['networks'])) {
                    $stage['networks'] = [];
                }

                $compose['networks'] = array_merge_recursive($config['networks'], $stage['networks']);
            }

            // custom key, environment_from 처리
            {
                foreach ($compose['services'] as $service_name => $service) {
                    if (true === isset($service['environment_from'])) {
                        foreach ($service['environment_from'] as $from_name => $from) {
                            foreach ($from as $env_name) {
                                $env_alias = preg_split('/:/D', $env_name);

                                if (true === isset($env_alias[1])) {
                                    $compose['services'][$service_name]['environment'][$env_alias[1]] = $compose['services'][$from_name]['environment'][$env_alias[0]];
                                } else {
                                    $compose['services'][$service_name]['environment'][$name] = $compose['services'][$env_alias[0]]['environment'][$env_alias[0]];
                                }
                            }
                        }

                        unset($compose['services'][$service_name]['environment_from']);
                    }
                }
            }

            \App\Helpers\Yaml::dumpFile(getcwd().'/docker-compose.'.$stageName.'.yml', $compose);
        }
    }
}
