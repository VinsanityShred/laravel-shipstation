<?php
namespace LaravelShipStation;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class ShipStation extends Client
{
    protected static $rateLimitLimit = null;
    protected static $rateLimitRemaining = null;
    protected static $rateLimitReset = null;

    public static function rateLimited(): bool
    {
        return !is_null(static::$rateLimitRemaining) && (static::$rateLimitRemaining > 0);
    }

    public static function rateLimitReset(): int
    {
        return !is_null(static::$rateLimitReset) ? static::$rateLimitReset : 0;
    }

    /**
     * @var string The current endpoint for the API. The default endpoint is /orders/
     */
    public $endpoint = '/orders/';

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

    /**
     * ShipStation constructor.
     *
     * @param  string  $apiKey
     * @param  string  $apiSecret
     * @throws \Exception
     */
    public function __construct($apiKey, $apiSecret, $apiURL)
    {
        if (!isset($apiKey, $apiSecret)) {
            throw new \Exception('Your API key and/or private key are not set. Did you run artisan vendor:publish?');
        }

        $this->base_uri = $apiURL ?? $this->base_uri;

        parent::__construct([
            'base_uri' => $this->base_uri,
            'headers'  => [
                'Authorization' => 'Basic ' . base64_encode("{$apiKey}:{$apiSecret}"),
            ]
        ]);
    }

    public function request($method, $uri = '', array $options = [])
    {
        $response = parent::request($method, $uri, $options);

        static::$rateLimitLimit     = max((int)$response->getHeader('X-Rate-Limit-Limit')[0], 0);
        static::$rateLimitReset     = max((int)$response->getHeader('X-Rate-Limit-Reset')[0], 0);
        static::$rateLimitRemaining = max((int)$response->getHeader('X-Rate-Limit-Remaining')[0], 0);

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
        return json_decode($this->request('GET', "{$this->endpoint}{$endpoint}", ['query' => $options])->getBody()->getContents());
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
        return json_decode($this->request('POST', "{$this->endpoint}{$endpoint}", ['json' => $options])->getBody()->getContents());
    }

    /**
     * Delete a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function delete($endpoint = '')
    {
        return json_decode($this->request('DELETE', "{$this->endpoint}{$endpoint}")->getBody()->getContents());
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
        return json_decode($this->request('PUT', "{$this->endpoint}{$endpoint}", ['json' => $options])->getBody()->getContents());
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
