<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;


class Wialon
{
    private string $sid;
    private string $base_api_url;
    private array $default_params;

    /**
     * @param string $scheme
     * @param string $host
     * @param string $port
     * @param string $sid
     * @param array $extra_params
     */
    public function __construct(string $scheme = 'http',
                                string $host = 'go.gps.az',
                                string $port = '',
                                string $sid = '',
                                array  $extraParams = array())
    {
        $this->sid = '';
        $this->host = $host;
        $this->defaultParams = array_replace(array(), (array)$extraParams);
        $this->baseApiUrl = sprintf('%s://%s%s/wialon/ajax.html?', $scheme, $host, mb_strlen($port) > 0 ? ':' . $port : '');
    }

    /**
     * @param $sid
     *
     * @return void
     */
    public function setSid($sid): void
    {
        $this->sid = $sid;
    }

    /**
     * @return string
     */
    public function getSid(): string
    {
        return $this->sid;
    }

    /**
     * @param $extra_params
     *
     * @return void
     */
    public function updateExtraParams($extra_params): void
    {
        $this->default_params = array_replace($this->default_params, $extra_params);
    }

    /** RemoteAPI request performer
     * action - RemoteAPI command name
     * args - JSON string with request parameters
     * @throws JsonException|JsonException
     */
    public function call($action, $args)
    {
        $url = $this->baseApiUrl;

        if (stripos($action, 'unit_group') === 0) {
            $svc = $action;
            $svc[mb_strlen('unit_group')] = '/';
        } else {
            $svc = preg_replace('\'_\'', '/', $action, 1);
        }


        $params = array(
            'svc' => $svc,
            'params' => $args,
            'sid' => $this->sid
        );
        $all_params = array_replace($this->defaultParams, $params);
        $query_params = [];
        foreach ($all_params as $k => $v) {
            $query_params[$k] = is_object($v) || is_array($v) ? json_encode($v, JSON_THROW_ON_ERROR) : $v;
        }


        try {
            $client = new Client();
            $response = $client->request('POST', $url, ['query' => $query_params]);
            $result = $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            $result = '{"error":-1,"message":' . $e->getMessage() . '}';
        }

        return $result;
    }



    /**
     * @param $token
     *
     * @return mixed
     * @throws JsonException
     */
    public function login($token)
    {
        $data = array(
            'token' => urlencode($token),
        );

        $result = $this->token_login(json_encode($data, JSON_THROW_ON_ERROR));

        $json_result = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        if (isset($json_result['eid'])) {
            $this->sid = $json_result['eid'];
        }

        return $result;
    }


    /**
     * @return mixed
     *
     * @throws JsonException
     */
    public function logout()
    {
        $result = $this->core_logout();
        $json_result = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        if ($json_result && $json_result['error'] == 0) {
            $this->sid = '';
        }
        return $result;
    }

    /**
     * @param $name
     * @param $args
     *
     * @return bool|string
     * @throws JsonException
     */
    public function __call($name, $args)
    {
        return $this->call($name, count($args) === 0 ? '{}' : $args[0]);
    }
}
