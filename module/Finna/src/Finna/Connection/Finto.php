<?php
/**
 * Finto connection class.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Connection
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\Connection;

use VuFind\Log\LoggerAwareTrait;
use Zend\Config\Config;
use Zend\Http\Client;
use Zend\Log\LoggerAwareInterface;

/**
 * Finto connection class.
 *
 * @category VuFind
 * @package  Connection
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Finto implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Finto configuration.
     *
     * @var \Zend\Config\Config
     */
    protected $config;

    /**
     * HTTP client.
     *
     * @var \Zend\Http\Client
     */
    protected $client;

    /**
     * Finto constructor.
     *
     * @param Config $config Finto configuration
     * @param Client $client HTTP client
     */
    public function __construct($config, Client $client)
    {
        $this->config = $config ?? new Config([]);
        $this->client = $client;

        // Set options
        $this->client->setOptions(
            [
                'timeout' => $this->config->get('http_timeout', 30),
                'useragent' => 'VuFind',
                'keepalive' => true
            ]
        );

        // Set Accept header
        $this->client->getRequest()->getHeaders()->addHeaderLine(
            'Accept', 'application/json'
        );
    }

    /**
     * Is the language supported by Finto.
     *
     * Can be used to determine whether to make an API call or not.
     *
     * @param string $lang Language code, e.g. "en" or "fi"
     *
     * @return boolean
     */
    public function isSupportedLanguage($lang)
    {
        return in_array($lang, ['fi', 'sv', 'en']);
    }

    /**
     * Search concepts and collections by query term.
     *
     * @param string $query The term to search for
     * @param string $lang  Language of labels to match, e.g. "en" or "fi"
     * @param array  $other Keyed array of other parameters accepted by Finto
     *                      API's search method
     *
     * @return array|bool Results or false if none
     * @throws \Exception
     */
    public function search($query, $lang = null, $other = [])
    {
        // Set query and default values for parameters
        $params = [
            'query' => urlencode(trim($query)),
            'vocab' => 'yso',
        ];

        // Override defaults if other values provided
        if ($lang) {
            $params['lang'] = $lang;
        }
        if (is_array($other)) {
            $params = array_merge($params, $other);
        }

        // Make request
        $response = $this->makeRequest(['search'], $params);

        return !empty($response['results']) ? $response : false;
    }

    /**
     * Make Request.
     *
     * Makes a request to the Finto REST API
     *
     * @param array       $hierarchy Array of values to embed in the URL path of
     *                               the request
     * @param array|false $params    A keyed array of query data
     * @param string      $method    The http request method to use (Default is
     *                               GET)
     *
     * @return mixed JSON response decoded to an associative array or null on
     *               authentication error.
     *
     * @throws \Exception
     */
    protected function makeRequest($hierarchy, $params = false, $method = 'GET')
    {
        // Set up the request
        $apiUrl = $this->config->get('base_url', 'https://api.finto.fi/rest/v1');

        // Add hierarchy
        foreach ($hierarchy as $value) {
            $apiUrl .= '/' . urlencode($value);
        }

        $client = $this->client->setUri($apiUrl);

        // Add params
        if ($method == 'GET') {
            $client->setParameterGet($params);
        } else {
            if (is_string($params)) {
                $client->getRequest()->setContent($params);
            } else {
                $client->setParameterPost($params);
            }
        }

        // Send request and retrieve response
        $startTime = microtime(true);
        $response = $client->setMethod($method)->send();
        $result = $response->getBody();

        $this->debug(
            '[' . round(microtime(true) - $startTime, 4) . 's]'
            . " $method request $apiUrl" . PHP_EOL . 'response: ' . PHP_EOL
            . $result
        );

        // Handle errors as complete failures only if the API call didn't return
        // valid JSON that the caller can handle
        $decodedResult = json_decode($result, true);
        if (!$response->isSuccess() && null === $decodedResult) {
            $params = $method == 'GET'
                ? $client->getRequest()->getQuery()->toString()
                : $client->getRequest()->getPost()->toString();
            $this->logError(
                "$method request for '$apiUrl' with params '$params' and contents '"
                . $client->getRequest()->getContent() . "' failed: "
                . $response->getStatusCode() . ': ' . $response->getReasonPhrase()
                . ', response content: ' . $response->getBody()
            );
            throw new \Exception('Problem with Finto REST API.');
        }

        return $decodedResult;
    }
}
