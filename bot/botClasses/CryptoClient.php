<?php

namespace CryptoBot;

class CryptoClient
{
    /**
     * The cURL client instance
     *
     * @var CurlClient $curl_client
     */
    protected $curl_client;

    /**
     * The Crypto client options
     *
     * @var array $options
     */
    protected $options = [
        'api' => null,
        'endpoint' => null,
        'params' => []
    ];

    /**
     * Constructor, initialization
     *
     * @param CurlClient $curl_client
     * @param array $options
     * @throws CryptoClientException if no API and/or API Endpoint is specified in options
     */
    public function __construct(CurlClient $curl_client, array $options = []) {
        $this->curl_client = $curl_client;

        $this->options = array_merge($this->options, $options);

        if (empty($this->options['api']) || !isset($this->options['endpoint'])) {
            throw new CryptoClientException('No API and/or API Endpoint specified', 1);
        }
    }

    /**
     * Get Crypto data
     *
     * @return array
     */
    public function getData() : array {
        return $this->curl_client->get($this->getRequestUrl())->json();
    }

    /**
     * Get request URL for Crypto API call
     *
     * @return string
     * @throws CryptoClientException if no API and/or API Endpoint is specified
     */
    protected function getRequestUrl() : string {
        if (empty($this->options['api']) || !isset($this->options['endpoint'])) {
            throw new CryptoClientException('No API and/or API Endpoint specified', 1);
        }

        $url = $this->options['api'] . $this->options['endpoint'];

        if (empty($this->options['params'])) {
            return $url;
        }

        $first_param = true;

        foreach ($this->options['params'] as $key => $value) {
            if (isset($this->options['params'][$key])) {
                if ($first_param) {
                    $first_param = false;
                } else {
                    $url .= urlencode('&');
                }

                $url .= "?{$key}={$value}";
            }
        }

        return $url;
    }
}
