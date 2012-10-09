<?php

/**
 * VuFind proxy service class file.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category Proxy
 * @package  Service
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/dmj/vf2-proxy
 */

namespace VuFindProxy;

/**
 * VuFind proxy service.
 *
 * @category Search
 * @package  Service
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/dmj/vf2-search-subsystem
 */
class ProxyService
{

    /**
     * Regular expression matching a request to localhost.
     *
     * @var string
     */
    const LOCAL_ADDRESS_RE = '@^(localhost|127(\.\d+){3}|\[::1\])@';

    /**
     * Default adapter.
     *
     * @var string|\Zend\Http\Client\Adapter\AdapterInterface
     */
    protected $adapter = 'Zend\Http\Client\Adapter\Socket';

    /**
     * Proxy configuration.
     *
     * @see \Zend\Http\Client\Adapter\Proxy::$config
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param array $config Proxy configuration
     *
     * @return void
     */
    public function __construct (array $config = array())
    {
        $this->config = $config;
    }

    /**
     * Proxy a request of an existing client.
     *
     * Returns the client given as argument with appropriate proxy setup.
     *
     * @param Zend\Http\Client $client  HTTP client
     * @param array            $options ZF2 ProxyAdapter options
     *
     * @return Zend\Http\Client
     */
    public function proxify (\Zend\Http\Client $client, array $options = array())
    {
        if ($this->config) {
            $host = $client->getUri()->getHost();
            if (!$this->isLocal($host)) {
                $adapter = new \Zend\Http\Client\Adapter\Proxy();
                $options = array_replace($this->config, $options);
                $adapter->setOptions($options);
                $client->setAdapter($adapter);
            }
        }
        return $client;
    }

    /**
     * Perform a GET request.
     *
     * @param string $url     Request URL
     * @param array  $params  Request parameters
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Zend\Http\Response
     */
    public function get ($url, array $params = array(), $timeout = null)
    {
        if ($params) {
            $query = $this->createQueryString($params);
            if (strpos($url, '?') !== false) {
                $url .= '&' . $query;
            } else {
                $url .= '?' . $query;
            }
        }
        $client = new \Zend\Http\Client();
        $client->setMethod(\Zend\Http\Request::METHOD_GET);
        $client->setUri($url);
        $client->setAdapter($this->adapter);
        if ($timeout) {
            $client->setOptions(array('timeout' => $timeout));
        }
        return $this->send($client);
    }

    /**
     * Perform a POST request.
     *
     * @param string $url     Request URL
     * @param mixed  $body    Request body document
     * @param string $type    Request body content type
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Zend\Http\Response
     */
    public function post ($url, $body = null, $type = 'application/octet-stream', $timeout = null)
    {
        $client = new \Zend\Http\Client();
        $client->setMethod(\Zend\Http\Request::METHOD_POST);
        $client->setUri($url);
        $client->setAdapter($this->adapter);
        if ($timeout) {
            $client->setOptions(array('timeout' => $timeout));
        }
        $client->setRawBody($body);
        $client->setHeaders(array('Content-Type' => $type, 'Content-Length' => strlen($body)));
        return $this->send($client);
    }

    /**
     * Post form data.
     *
     * @param string $url     Request URL
     * @param array  $params  Form data
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Zend\Http\Response
     */
    public function postForm ($url, array $params = array(), $timeout = null)
    {
        $body = $this->createQueryString($params);
        return $this->post($url, $body, \Zend\Http\Client::ENC_URLENCODED, $timeout);
    }

    /**
     * Set the default adapter.
     *
     * @param mixed $adapter Default adapter
     *
     * @return void
     */
    public function setDefaultAdapter ($adapter)
    {
        $this->adapter = $adapter;
    }

    /// Internal API

    /**
     * Return query string based on params.
     *
     * @param array $params Parameters
     *
     * @return string
     */
    protected function createQueryString (array $params = array())
    {
        if ($this->isAssocParams($params)) {
            return http_build_query($params);
        } else {
            return implode('&', $params);
        }
    }

    /**
     * Send HTTP request and return response.
     *
     * @param \Zend\Http\Client $client HTTP client to use
     *
     * @throws Exception\RuntimeException
     * @return \Zend\Http\Response
     *
     * @todo Catch more exceptions, maybe?
     * @todo Check for potential problems re-using the client
     * @todo Check if we need to clone() the default adapter
     */
    protected function send (\Zend\Http\Client $client)
    {
        $client->setAdapter($this->adapter);
        $client = $this->proxify($client);
        try {
            $response = $client->send();
        } catch (\Zend\Http\Client\Exception\RuntimeException $e) {
            throw new Exception\RuntimeException(
                sprintf('Zend HTTP Client exception: %s', $e),
                -1,
                $e
            );
        }
        return $response;
    }

    /**
     * Return TRUE if argument is an associative array.
     *
     * @param array $array Array to test
     *
     * @return boolean
     */
    protected function isAssocParams (array $array)
    {
        foreach ($array as $key => $value) {
            return !is_numeric($key);
            // @codeCoverageIgnoreStart
        }
    }
    // @codeCoverageIgnoreEnd

    /**
     * Return TRUE if argument refers to localhost.
     *
     * @param string $host Host to check
     *
     * @return boolean
     */
    protected function isLocal ($host)
    {
        return preg_match(self::LOCAL_ADDRESS_RE, $host);
    }

}