<?php

class VultrDNS
{
    protected string $apiKey;
    protected array $domains = [];
    protected string $vultrDomain;
    protected array $output = [];
    protected $call_data;

    public function __construct()
    {
        if (!file_exists('vultrdns.config.json'))
            throw new \Exception('Config file was not found');

        $config = json_decode(file_get_contents('vultrdns.config.json'));

        if (is_null($config))
            throw new \Exception('Invalid config file');

        $this->apiKey = $config->api_key;
        $this->vultrDomain = $config->domain;
        $this->domains = $config->dynamic_records;
    }

    public function process()
    {
        $ip = file_get_contents('https://api.ipify.org', false, stream_context_create([
            'http' => ['ignore_errors' => true],
        ]));

        $res = json_decode($this->listDomainDnsRecords($this->vultrDomain), true);
        foreach ($res['records'] AS $record) {
            if (
                !in_array($record['name'], $this->domains) ||
                $record['type'] !== 'A'
            )
                continue;

            if($ip === $record['data']) {
                $this->output[] = 'No change for ' . $record['name'];
                continue;
            }

            $this->updateDnsRecord($this->vultrDomain, $record['id'], $record['name'], $ip);
            $this->output[] = 'Updated ' . $record['name'];
        }
        return implode(', ', $this->output)."\r\n";
    }

    protected function updateDnsRecord(string $domain, string $record_id, string $name, string $data)
    {
        $post = array("name" => $name, "data" => $data);
        return $this->callApi("v2/domains/$domain/record/$record_id", 'PATCH', true, $this->apiKeyHeader(), $post);
    }

    protected function listDomainDnsRecords($domain)
    {
        return $this->callApi("v2/domains/$domain/records", 'GET', false, $this->apiKeyHeader());
    }

    protected function apiKeyHeader(): array
    {
        return ["Authorization: Bearer " . $this->apiKey, "Content-Type: application/json"];
    }

    protected function callApi(string $url, string $type = 'GET', bool $return_http_code = false, array $headers = [], array $post_fields = [])
    {
        $ch = curl_init('https://api.vultr.com/' . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        if ($type === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($post_fields)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
            }
        } elseif ($type === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $call_response = curl_exec($ch);
        $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($return_http_code) {
            return $http_response_code;
        }
        if ($http_response_code === 200 || $http_response_code === 201 || $http_response_code === 202) {
            return $this->call_data = $call_response;//Return data
        }
        var_dump($call_response);
        return $this->call_data = array('http_response_code' => $http_response_code);//Call failed
    }
}

echo (new VultrDNS)->process();
