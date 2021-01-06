<?php
namespace LaravelShipStation;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class ShipStation
{
    /**
     * @var string The current endpoint for the API. The default endpoint is /orders/
     */
    public $endpoint = '/orders/';

    /**
     * @var \GuzzleHttp\Client The http client used when calling the API.
     */
    public $client = null;

    /**
     * @var array Our list of valid ShipStation endpoints.
     */
    private $endpoints = [
        '/accounts/',
        '/carriers/',
        '/customers/',
        '/fulfillments/',
        '/orders/',
        '/products/',
        '/shipments/',
        '/stores/',
        '/users/',
        '/warehouses/',
        '/webhooks/'
    ];

    /**
     * @var string Base API URL for ShipStation
     */
    private $base_uri = 'https://ssapi.shipstation.com';

    /** @var int */
    private $maxAllowedRequests = 0;

    /** @var int|null */
    private $remainingRequests = null;

    /** @var int */
    private $secondsUntilReset = 0;

    /**
     * ShipStation constructor.
     *
     * @param  string  $apiKey
     * @param  string  $apiSecret
     * @param  string  $apiURL
     * @param  string|null  $partnerApiKey
     * @throws \Exception
     */
    public function __construct($apiKey, $apiSecret, $apiURL, $partnerApiKey = null)
    {
        if (!isset($apiKey, $apiSecret)) {
            throw new \Exception('Your API key and/or private key are not set. Did you run artisan vendor:publish?');
        }

        $this->base_uri = $apiURL ?? $this->base_uri;

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiKey}:{$apiSecret}"),
        ];

        if (! empty($partnerApiKey)) {
            $headers['x-partner'] = $partnerApiKey;
        }

        $this->client = new Client([
            'base_uri' => $this->base_uri,
            'headers'  => $headers,
        ]);
    }

    public function request($method, $uri = '', array $options = [])
    {
        $response = parent::request($method, $uri, $options);

        $this->maxAllowedRequests = max((int)$response->getHeader('X-Rate-Limit-Limit')[0], 0);
        $this->secondsUntilReset  = max((int)$response->getHeader('X-Rate-Limit-Reset')[0], 0);
        $this->remainingRequests  = max((int)$response->getHeader('X-Rate-Limit-Remaining')[0], 0);

        return $response;
    }

    /**
     * Get a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  array  $options
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function get($options = [], $endpoint = '')
    {
        $response = $this->client->request('GET', "{$this->endpoint}{$endpoint}", ['query' => $options]);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Post to a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  array  $options
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function post($options = [], $endpoint = '')
    {
        $response = $this->client->request('POST', "{$this->endpoint}{$endpoint}", ['json' => $options]);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Delete a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function delete($endpoint = '')
    {
        $response = $this->client->request('DELETE', "{$this->endpoint}{$endpoint}");

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Update a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  array  $options
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function update($options = [], $endpoint = '')
    {
        $response = $this->client->request('PUT', "{$this->endpoint}{$endpoint}", ['json' => $options]);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Get the maximum number of requests that can be sent per window
     *
     * @return int
     */
    public function getMaxAllowedRequests()
    {
        return $this->maxAllowedRequests;
    }

    /**
     * Get the remaining number of requests that can be sent in the current window
     *
     * @return int
     */
    public function getRemainingRequests()
    {
        return $this->remainingRequests;
    }

    /**
     * Get the number of seconds remaining until the next window begins
     *
     * @return int
     */
    public function getSecondsUntilReset()
    {
        return $this->secondsUntilReset;
    }

    /**
     * Are we currently rate limited?
     * We are if there are no more requests allowed in the current window
     *
     * @return bool
     */
    public function isRateLimited()
    {
        return $this->remainingRequests !== null && ! $this->remainingRequests;
    }

    /**
     * Set our endpoint by accessing it via a property.
     *
     * @param  string $property
     * @return $this
     */
    public function __get($property)
    {
        if (in_array('/' . $property . '/', $this->endpoints)) {
            $this->endpoint = '/' . $property . '/';
        }

        $className = "LaravelShipStation\\Helpers\\" . ucfirst($property);

        if (class_exists($className)) {
            return new $className($this);
        }

        return $this;
    }
}
