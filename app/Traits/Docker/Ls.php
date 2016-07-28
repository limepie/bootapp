<?php
namespace App\Traits\Docker;

trait Ls
{
    /**
     * @param $isAll
     */
    public function dockerLs($isAll = false)
    {
//--filter "label=com.docker.bootapp.project="

        $command = [
            'docker',
            'ps',
            '--no-trunc',
            '--format=\'id={{.ID}}&service={{.Label "com.docker.compose.service"}}&image={{.Image}}&command={{.Command}}&created={{.CreatedAt}}&running={{.RunningFor}}&ports={{.Ports}}&status={{.Status}}&size={{.Size}}&names={{.Names}}\'',
            '2>&1'
        ];
        $psList = $this->process($command, ['print' => false])->toArray();

        if ($psList) {
            $command = [
                'docker',
                'inspect',
                '--format="name={{.Name}}&ip={{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}&service={{index .Config.Labels \"com.docker.bootapp.service\"}}&id={{.Id}}&env={{json .Config.Env}}"',
                '$(docker ps -q)',
                '2>&1'
            ];
            $inspectList = $this->process($command, ['print' => false])->toArray();

            $ilist       = [];
            $serviceList = [];

            foreach ($this->config['services'] as $key => $value) {
                $serviceList[] = $this->getContainerName($key);
            }

            foreach ($inspectList as $str) {
                parse_str($str, $b);
                $b['domain'] = '';

                $envs = json_decode($b['env'], true);

                foreach ($envs as $env) {
                    list($key, $e) = explode('=', $env, 2);

                    if ('DOMAIN' == $key) {
                        $b['domain'] = $e;
                    }
                }

                unset($b['env']);

                $name = ltrim($b['name'], '/');

                if (true === in_array($name, $serviceList)) {
                    $ilist[$b['id']] = $b;
                }
            }
        }

        $docker = [];

        if ($isAll) {
            foreach ($psList as $str) {
                parse_str($str, $a);

                if (false === isset($ilist[$a['id']])) {
                    continue;
                }

                $tmp  = array_merge($a, $ilist[$a['id']]);
                $name = trim($tmp['name'], '/');

                foreach ($tmp as $k1 => $v1) {
                    $docker[] = [
                        'key'   => $k1,
                        'value' => $v1
                    ];
                }

                $docker[] = [];
                $docker[] = [];
            }

            $table = $this->table($docker);
        } else {
            foreach ($psList as $str) {
                parse_str($str, $a);

                if (false === isset($ilist[$a['id']])) {
                    continue;
                }

                $tmp  = array_merge($a, $ilist[$a['id']]);
                $name = trim($tmp['name'], '/');

                $docker[$tmp['id']] = [
                    'service' => $tmp['service'],
                    //'name'    => $name,
                    'image'   => $tmp['image'],
                    'status'  => $tmp['status'],
                    'ip'      => $tmp['ip'],
                    'domain'  => $tmp['domain'],
                    'ports'   => $tmp['ports']
                ];
            }

            usort($docker, function ($a, $b) {
                return strcmp($a['ip'], $b['ip']);
            });

            array_unshift($docker, ['SERVICE', /*'NAME',*/'IMAGE', 'STATUS', 'IP', 'DOMAIN', 'PORTS']);
            $table = $this->table($docker);
        }

        $this->message($table);
    }
}
