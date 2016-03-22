<?php namespace Firebase;

use Closure;
use Firebase\Event\RequestsBatchedEvent;
use Firebase\Normalizer\NormalizerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Query;

class Firebase implements FirebaseMethods
{

    use Configurable;

    /**
     * Helper for handling single argument setters
     */
    const NULL_ARGUMENT = -1;

    /**
     * HTTP Request Client
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     *
     * @var array
     */
    protected $normalizers;

    /**
     *
     * @var \Firebase\Normalizer\NormalizerInterface
     */
    protected $normalizer;

    /**
     * @var Closure
     */
    public static $clientResolver;

    /**
     * Default method to initialize a Firebase client, will set the ClientInterface dependency for you if not already set
     * @param string $url
     * @param null|string $token
     * @param array $options
     */
    public static function initialize($url, $token = null, $options = [], $normalizers = [])
    {
        //strap guzzle client if it is not set in as resolver property
        if (!isset(static::$clientResolver)) {
            static::setClientResolver(function ($options) {
                $clientOptions = isset($options['client']) ? $options['client'] : [];
                return new Client($clientOptions);
            });
        }

        return new static(array_merge($options, ['base_url' => $url, 'token' => $token]), null, $normalizers);
    }

    /**
     * @param array $options
     * @param null|ClientInterface $client
     * @param array $normalizers
     */
    public function __construct($options = array(), ClientInterface $client = null, $normalizers = array())
    {
        $this->setOptions($options);
        $this->setNormalizers($normalizers);
        is_null($client) ? $this->resolveClient() : $this->setClient($client);
    }

    /**
     * @param Closure $resolver
     */
    public static function setClientResolver(Closure $resolver)
    {
        static::$clientResolver = $resolver;
    }

    /**
     * Read data from path
     * @param $path
     * @param Criteria $crtiera
     * @return mixed
     */
     public function get($path = '', Criteria $criteria = null)
     {
         list($path, $value) = $this->evaluatePathValueArguments(array($path, $criteria));
         $response = $this->client->get($this->buildUrl($path), $this->buildOptions($value));
         return $this->handleResponse($response);
     }

     /**
      * Read data from path
      * @param $path
      * @param Criteria $crtiera
      * @return mixed
      */
      public function getBatch($path = '', Criteria $criteria = null)
      {
          list($path, $value) = $this->evaluatePathValueArguments(array($path, $criteria));
          return $this->client->getAsync($this->buildUrl($path), $this->buildOptions($value));
      }

    /**
     * Set data in path
     * @param $path
     * @param $value
     * @return mixed
     */
    public function set($path, $value = self::NULL_ARGUMENT)
    {
        list($path, $value) = $this->evaluatePathValueArguments(array($path, $value));
        $response = $this->client->put($this->buildUrl($path), $this->buildOptions($value));
        return $this->handleResponse($response);
    }

    /**
     * Set data in path
     * @param $path
     * @param $value
     * @return mixed
     */
    public function setBatch($path, $value = self::NULL_ARGUMENT)
    {
        list($path, $value) = $this->evaluatePathValueArguments(array($path, $value));
        return $this->client->putAsync($this->buildUrl($path), $this->buildOptions($value));
    }

    /**
     * Update exising data in path
     * @param $path
     * @param $value
     * @return mixed
     */
    public function update($path, $value = self::NULL_ARGUMENT)
    {
        list($path, $value) = $this->evaluatePathValueArguments(array($path, $value));
        $response = $this->client->patch($this->buildUrl($path), $this->buildOptions($value));
        return $this->handleResponse($response);
    }

    /**
     * Update exising data in path
     * @param $path
     * @param $value
     * @return mixed
     */
    public function updateBatch($path, $value = self::NULL_ARGUMENT)
    {
        list($path, $value) = $this->evaluatePathValueArguments(array($path, $value));
        return $this->client->patchAsync($this->buildUrl($path), $this->buildOptions($value));
    }

    /**
     * Delete item in path
     * @param $path
     * @return mixed
     */
    public function delete($path = '')
    {
        list($path, $value) = $this->evaluatePathValueArguments(array($path));
        $response = $this->client->delete($this->buildUrl($path), $this->buildOptions($value));
        return $this->handleResponse($response);
    }

    /**
     * Delete item in path
     * @param $path
     * @return mixed
     */
    public function deleteBatch($path = '')
    {
        list($path, $value) = $this->evaluatePathValueArguments(array($path));
        return $this->client->deleteAsync($this->buildUrl($path), $this->buildOptions($value));
    }

    /**
     * Push item to path
     * @param $path
     * @param $value
     * @return mixed
     */
    public function push($path, $value = self::NULL_ARGUMENT)
    {
        list($path, $value) = $this->evaluatePathValueArguments(array($path, $value));
        $request = $this->post($this->buildUrl($path), $this->buildOptions($value));
        return $this->handleResponse($request);
    }

    /**
     * Push item to path
     * @param $path
     * @param $value
     * @return mixed
     */
    public function pushBatch($path, $value = self::NULL_ARGUMENT)
    {
        list($path, $value) = $this->evaluatePathValueArguments(array($path, $value));
        return $this->postAsync($this->buildUrl($path), $this->buildOptions($value));
    }

    /**
     * Stores requests when batching, sends request
     * @param Response $request
     * @return mixed
     */
    protected function handleResponse(ResponseInterface $response)
    {
        return $this->normalizeResponse($response);
    }

    /**
     * Set a normalizer by string or a normalizer instance
     * @param string|NormalizerInterface $normalizer
     * @return $this
     */
    public function normalize($normalizer)
    {
        if (is_string($normalizer) && isset($this->normalizers[$normalizer])) {
            $this->normalizer = $this->normalizers[$normalizer];
        } else if ($normalizer instanceof NormalizerInterface) {
            $this->normalizer = $normalizer;
        }

        return $this;
    }

    /**
     * Normalizes the HTTP Request Client response
     * @param ResponseInterface $response
     * @return mixed
     */
     protected function normalizeResponse(ResponseInterface $response)
     {
         if (!is_null($this->normalizer)) {
             return $this->normalizer->normalize($response);
         }

         //default responsen is decoded json
         return json_decode($response->getBody()->__toString(), true);
     }

    /**
     * Set normalizers in an associative array
     * @param $normalizers
     * @return $this
     */
    public function setNormalizers($normalizers)
    {
        foreach ($normalizers as $normalizer) {
            $this->normalizers[$normalizer->getName()] = $normalizer;
        }
        return $this;
    }

    /**
     * @param ClientInterface $client
     * @return $this
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Prefix url with a base_url if present
     * @param string $path
     * @return string
     */
    protected function buildUrl($path)
    {
        $baseUrl = $this->getOption('base_url');

        //add trailing slash to the url if not supplied in the base_url setting nor supplied in path #6
        $url = $baseUrl . ((substr($baseUrl, -1) == '/' || substr($path, 0, 1) == '/') ? '' : '/') . $path;

        if (strpos($url, '.json') === false) {
            $url .= '.json';
        }

        return $url;
    }

    /**
     * Build Query parameters for HTTP Request Client
     * @param $data
     * @return array
     */
    protected function buildQuery($data = null)
    {
        $params = array();

        if ($data instanceof Criteria) {
            $params = array_merge($params, $data->getParams());
            $params['orderBy'] = $data->getOrderBy();
        }

        if ($token = $this->getOption('token', false)) {
            $params['auth'] = $token;
        }

        return $params;
    }

    /**
     * Build options array for HTTP Request Client
     * @param mixed $data
     * @return array
     */
    protected function buildOptions($data = null)
    {
        $options = array(
            'query' => $this->buildQuery($data),
            'debug' => $this->getOption('debug', false),
            'timeout' => $this->getOption('timeout', 0)
        );

        if (!is_null($data) && !($data instanceof Criteria)) {
            $options['json'] = $data;
        }

        return $options;
    }

    public function batch($callable, $options = [])
    {
        //gather requests
        $requests = call_user_func_array($callable, array($this));
        return Pool::batch($this->client, $requests, $options);
    }

    /**
     * Handle single argument calls to set/update/push methods #7
     * @param $args
     * @return array
     */
    protected function evaluatePathValueArguments($args)
    {
        $hasSecondArgument = $args[1] !== self::NULL_ARGUMENT;
        return array(!$hasSecondArgument ? '' : $args[0], !$hasSecondArgument ? $args[0] : $args[1]);
    }

    /**
     * Inject client dependency
     */
    protected function resolveClient()
    {
        if (!isset($this->client)) {
            $this->setClient(call_user_func(static::$clientResolver, $this->getOptions()));
        }
    }
}
