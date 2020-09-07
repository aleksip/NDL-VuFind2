<?php
/**
 * Ontology Recommendations Module.
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
 * @package  Recommendations
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace Finna\Recommend;

use Finna\Connection\Finto;
use Finna\Cookie\RecommendationMemory;
use VuFind\Cookie\CookieManager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Recommend\RecommendInterface;
use VuFind\View\Helper\Root\Url;

/**
 * Ontology Recommendations Module.
 *
 * This class provides ontology based recommendations.
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class Ontology implements RecommendInterface, TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * Name of the cookie used to store the times shown total value.
     */
    public const COOKIE_NAME = 'ontologyRecommend';

    public const TYPE_NONDESCRIPTOR = 'nondescriptor';

    public const TYPE_SPECIFIER = 'specifier';

    public const TYPE_HYPONYM = 'hyponym';

    /**
     * Finto connection class.
     *
     * @var Finto
     */
    protected $finto;

    /**
     * Cookie manager.
     *
     * @var CookieManager
     */
    protected $cookieManager;

    /**
     * Url helper.
     *
     * @var Url
     */
    protected $urlHelper;

    /**
     * Recommendation memory.
     *
     * @var RecommendationMemory
     */
    protected $recommendationMemory;

    /**
     * Raw configuration parameters.
     *
     * @var string
     */
    protected $rawParams;

    /**
     * Maximum API calls to make. A value of false indicates an unlimited number.
     *
     * @var int|bool
     */
    protected $maxApiCalls;

    /**
     * Maximum recommendations to show. A value of false indicates an unlimited
     * number.
     *
     * @var int|bool
     */
    protected $maxRecommendations;

    /**
     * Maximum total for determining if the result set is small. A value of false
     * indicates that all result sets should be considered small.
     *
     * @var int|bool
     */
    protected $maxSmallResultTotal;

    /**
     * Minimum total for determining if the result set is large. A value of false
     * indicates that all result should be considered large.
     *
     * @var int|bool
     */
    protected $minLargeResultTotal;

    /**
     * Maximum number of times ontology recommendations can be shown per
     * browser session. A value of false indicates an unlimited number.
     *
     * @var int|bool
     */
    protected $maxTimesShownPerSession;

    /**
     * Parameter object representing user request.
     *
     * @var \Laminas\StdLib\Parameters
     */
    protected $request;

    /**
     * Current search query.
     *
     * @var string
     */
    protected $lookfor;

    /**
     * Search results object.
     *
     * @var \VuFind\Search\Base\Results
     */
    protected $results;

    /**
     * Search ID.
     *
     * @var int
     */
    protected $searchId;

    /**
     * Total count of records in the result set.
     *
     * @var int
     */
    protected $resultTotal;

    /**
     * Ontology results.
     *
     * @var array
     */
    protected $ontologyResults;

    /**
     * Total number of API calls made.
     *
     * @var int
     */
    protected $apiCallTotal = 0;

    /**
     * Total number of recommendations.
     *
     * @var int
     */
    protected $recommendationTotal = 0;

    /**
     * Total number of ontology results.
     *
     * @var int
     */
    protected $ontologyResultTotal = 0;

    /**
     * Ontology constructor.
     *
     * @param Finto                $finto                Finto connection class
     * @param CookieManager        $cookieManager        Cookie manager
     * @param Url                  $urlHelper            Url helper
     * @param RecommendationMemory $recommendationMemory Recommendation memory
     */
    public function __construct(
        Finto $finto, CookieManager $cookieManager, Url $urlHelper,
        RecommendationMemory $recommendationMemory
    ) {
        $this->finto = $finto;
        $this->cookieManager = $cookieManager;
        $this->urlHelper = $urlHelper;
        $this->recommendationMemory = $recommendationMemory;
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        $this->rawParams = $settings;
    }

    /**
     * Called at the end of the Search Params objects' initFromRequest() method.
     * This method is responsible for setting search parameters needed by the
     * recommendation module and for reading any existing search parameters that may
     * be needed.
     *
     * @param \VuFind\Search\Base\Params $params  Search parameter object
     * @param \Laminas\StdLib\Parameters $request Parameter object representing user
     *                                            request.
     *
     * @return void
     */
    public function init($params, $request)
    {
        // Parse out parameters:
        $settings = explode(':', $this->rawParams);
        $this->maxApiCalls = empty($settings[0]) ? false : $settings[0];
        $this->maxRecommendations = empty($settings[1]) ? false : $settings[1];
        $this->maxSmallResultTotal = empty($settings[2]) ? false : $settings[2];
        $this->minLargeResultTotal = empty($settings[3]) ? false : $settings[3];
        $this->maxTimesShownPerSession = empty($settings[4]) ? false : $settings[4];

        $this->request = $request;

        // Collect the best possible search term(s):
        $this->lookfor = $request->get('lookfor');
        if (empty($this->lookfor) && is_object($params)) {
            $this->lookfor = $params->getQuery()->getAllTerms();
        }
        $this->lookfor = trim($this->lookfor);
    }

    /**
     * Called after the Search Results object has performed its main search.  This
     * may be used to extract necessary information from the Search Results object
     * or to perform completely unrelated processing.
     *
     * @param \VuFind\Search\Base\Results $results Search results object
     *
     * @return void
     * @throws \Exception
     */
    public function process($results)
    {
        // Processing is done at a later stage to get the search ID when not
        // running as deferred.
        $this->results = $results;
    }

    /**
     * Get all ontology results grouped by type.
     *
     * @return array|false
     */
    public function getOntologyResults()
    {
        // Just return the results if we already have them.
        if (isset($this->ontologyResults)) {
            return $this->ontologyResults;
        }

        // Do nothing if lookfor is empty.
        if (empty($this->lookfor)) {
            return false;
        }

        // Get language, do nothing if it is not supported.
        $language = $this->getTranslatorLocale();
        $language = (0 === strpos($language, 'en-')) ? 'en' : $language;
        if (!$this->finto->isSupportedLanguage($language)) {
            return false;
        }

        // Check cookie to find out how many times ontology recommendations have
        // already been shown in the current browser session. Do nothing if a
        // maximum value is set in configuration and it has been reached.
        $cookieValue = $this->cookieManager->get(self::COOKIE_NAME);
        $timesShownTotal = is_numeric($cookieValue) ? $cookieValue : 0;
        if (is_numeric($this->maxTimesShownPerSession)
            && $timesShownTotal > $this->maxTimesShownPerSession
        ) {
            return false;
        }

        // Set up results array.
        $this->ontologyResults = [
            self::TYPE_NONDESCRIPTOR => [],
            self::TYPE_SPECIFIER => [],
            self::TYPE_HYPONYM => []
        ];

        // Get searchId and resultTotal.
        $this->searchId = $this->request->get('searchId')
            ?? $this->results->getSearchId()
            ?? null;
        $this->resultTotal = $this->request->get('resultTotal')
            ?? $this->results->getResultTotal();

        // Create search terms array with quoted words as one search term.
        $terms = str_getcsv($this->lookfor, ' ');

        foreach ($terms as $term) {
            if (!($this->canMakeApiCall() && $this->canAddRecommendation())) {
                break;
            }
            if ($this->skipFromFintoSearch($term)) {
                continue;
            }
            if ($fintoResults = $this->finto->search($term, $language)) {
                $this->processFintoSearchResults($fintoResults, $term);
            }
            $this->apiCallTotal += 1;
        }

        if ($this->recommendationTotal > 0) {
            // There are recommendations, so set a new cookie value.
            $this->cookieManager->set(self::COOKIE_NAME, $timesShownTotal + 1);
        }

        return $this->ontologyResults;
    }

    /**
     * Can another API call be made.
     *
     * @return bool
     */
    protected function canMakeApiCall()
    {
        return is_numeric($this->maxApiCalls)
            ? $this->apiCallTotal < $this->maxApiCalls
            : false === $this->maxApiCalls;
    }

    /**
     * Can another recommendation be added.
     *
     * @return bool
     */
    protected function canAddRecommendation()
    {
        return is_numeric($this->maxRecommendations)
            ? $this->recommendationTotal < $this->maxRecommendations
            : false === $this->maxRecommendations;
    }

    /**
     * Should the search term be skipped from Finto search.
     *
     * @param string $term Search term
     *
     * @return bool
     */
    protected function skipFromFintoSearch($term)
    {
        return 0 === strpos($term, 'topic_uri_str_mv:');
    }

    /**
     * Processes results of a single Finto search query.
     *
     * @param array  $fintoResults Finto results
     * @param string $term         The term searched for
     *
     * @return void
     */
    protected function processFintoSearchResults($fintoResults, $term)
    {
        if (count($fintoResults['results']) > 1) {
            // If there are multiple Finto results they are considered to be
            // specifier results.
            foreach ($fintoResults['results'] as $fintoResult) {
                $this->addOntologyResult(
                    $fintoResult, self::TYPE_SPECIFIER, $term
                );
            }
        } elseif (count($fintoResults['results']) === 1) {
            // There is only one Finto result.
            $fintoResult = reset($fintoResults['results']);

            if (((isset($fintoResult['altLabel'])
                && $fintoResult['altLabel'] === $term)
                || (isset($fintoResult['hiddenLabel'])
                && $fintoResult['hiddenLabel'] === $term))
            ) {
                // The Finto result has an altLabel or hiddenLabel so it is
                // considered to be a non-descriptor result.
                $this->addOntologyResult(
                    $fintoResult, self::TYPE_NONDESCRIPTOR, $term
                );

            } elseif ((false === $this->minLargeResultTotal
                || $this->resultTotal >= $this->minLargeResultTotal)
            ) {
                // The Finto result is not a non-descriptor and there is a
                // large number of search results so we will try to make an
                // additional Finto call to see if there are narrower
                // concepts.
                if (!$this->canMakeApiCall()) {
                    return;
                }
                if ($hyponymResults = $this->finto->narrower(
                    $fintoResult['vocab'], $fintoResult['uri'],
                    $fintoResult['lang'], true
                )
                ) {
                    $termUri = $fintoResult['uri'] ?? null;
                    foreach ($hyponymResults['narrower'] as $hyponymResult) {
                        $this->addOntologyResult(
                            $hyponymResult, self::TYPE_HYPONYM, $term, $termUri
                        );
                    }
                }
                $this->apiCallTotal += 1;
            }
        }
    }

    /**
     * Adds an ontology result to the specified array.
     *
     * @param array       $fintoResult Finto result
     * @param string      $resultType  Result type
     * @param string      $term        The term searched for
     * @param string|null $termUri     URI of the searched term if applicable
     *
     * @return void
     */
    protected function addOntologyResult(
        $fintoResult, $resultType, $term, $termUri = null
    ) {
        $this->ontologyResultTotal += 1;

        // Recommendation memory cookie key and value.
        $key = $this->ontologyResultTotal;
        if ($this->searchId) {
            $key = $this->searchId . '-' . $key;
        }
        $value = $this->recommendationMemory->getDataString(
            'Ontology', $fintoResult['prefLabel'], $term, $resultType
        );

        // Recommendation link.
        $base = $this->urlHelper->__invoke('search-results');
        $uriField = 'topic_uri_str_mv:' . $fintoResult['uri'];
        $recommendedTerm = $fintoResult['prefLabel'];
        if (preg_match('/\s/', $recommendedTerm)) {
            $recommendedTerm = '"' . $recommendedTerm . '"';
        }
        $replace = $uriField . ' ' . $recommendedTerm;
        $recommend = str_replace($term, $replace, $this->lookfor);
        if ($termUri) {
            $recommend = str_replace('topic_uri_str_mv:' . $termUri, '', $recommend);
            $recommend = preg_replace('/\s+/', ' ', $recommend);
        }
        $params = [
            'lookfor' => $recommend,
            RecommendationMemory::PARAMETER_NAME => $key
        ];
        $params = array_merge($this->request->toArray(), $params);
        unset(
            $params['mod'], $params['params'], $params['searchId'],
            $params['resultTotal']
        );
        $href = $base . '?' . http_build_query($params);

        // Create result array.
        $ontologyResult = [
            'label' => $fintoResult['prefLabel'],
            'href' => $href,
            'key' => $key,
            'value' => $value,
            'result' => $fintoResult,
        ];

        // Add result and increase counter if the result is for a new term.
        if (!isset($this->ontologyResults[$resultType][$term])) {
            $this->ontologyResults[$resultType][$term] = [];
            $this->recommendationTotal += 1;
        }
        $this->ontologyResults[$resultType][$term][] = $ontologyResult;
    }
}
