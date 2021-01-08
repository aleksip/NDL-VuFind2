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
use VuFind\Config\PluginManager;
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
     *
     * @var string
     */
    public const COOKIE_NAME = 'ontologyRecommend';

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
     * Configuration loader
     *
     * @var PluginManager
     */
    protected $configLoader;

    /**
     * Maximum number of API calls to make per search. Setting to null indicates
     * an unlimited number of API calls.
     *
     * @var int|null
     */
    protected $maxApiCalls = null;

    /**
     * Maximum number of recommendations to show per search. Setting to null
     * indicates an unlimited number of recommendations.
     *
     * @var int|null
     */
    protected $maxRecommendations = null;

    /**
     * Maximum total number for determining if the result set is small. Setting
     * to null indicates that all result sets should be considered small.
     *
     * @var int|null
     */
    protected $maxSmallResultTotal = null;

    /**
     * Minimum total number for determining if the result set is large. Setting
     * to null indicates that all result sets should be considered large.
     *
     * @var int|null
     */
    protected $minLargeResultTotal = null;

    /**
     * Maximum number of times ontology recommendations can be shown per browser
     * session. Setting to null indicates an unlimited number of ontology
     * recommendations shown.
     *
     * @var int|null
     */
    protected $maxTimesShownPerSession = null;

    /**
     * Parameter object representing user request.
     *
     * @var \Laminas\StdLib\Parameters
     */
    protected $request = null;

    /**
     * Current search query.
     *
     * @var string
     */
    protected $lookfor = null;

    /**
     * Search results object.
     *
     * @var \VuFind\Search\Base\Results
     */
    protected $results = null;

    /**
     * Search ID.
     *
     * @var int
     */
    protected $searchId = null;

    /**
     * Total count of records in the result set.
     *
     * @var int
     */
    protected $resultTotal = null;

    /**
     * Ontology recommendations.
     *
     * @var array|null
     */
    protected $recommendations = null;

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
     * @param PluginManager        $configLoader         Configuration loader
     */
    public function __construct(
        Finto $finto, CookieManager $cookieManager, Url $urlHelper,
        RecommendationMemory $recommendationMemory, PluginManager $configLoader
    ) {
        $this->finto = $finto;
        $this->cookieManager = $cookieManager;
        $this->urlHelper = $urlHelper;
        $this->recommendationMemory = $recommendationMemory;
        $this->configLoader = $configLoader;
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * Ontology:[ini section]:[ini name]
     *       Provides ontology based recommendations as configured in the specified
     *       section of the specified ini file; if [ini name] is left out, it
     *       defaults to "searches" and if [ini section] is left out, it defaults to
     *       "OntologyModuleRecommendations".
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        $settings = explode(':', $settings);
        $sectionName = empty($settings[0])
            ? 'OntologyModuleRecommendations' : $settings[0];
        $iniName = $settings[1] ?? 'searches';

        $config = $this->configLoader->get($iniName)->get($sectionName);

        $this->maxApiCalls = $config->get('maxApiCalls');
        $this->maxRecommendations = $config->get('maxRecommendations');
        $this->maxSmallResultTotal = $config->get('maxSmallResultTotal');
        $this->minLargeResultTotal = $config->get('minLargeResultTotal');
        $this->maxTimesShownPerSession = $config->get('maxTimesShownPerSession');
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
     * Get all ontology recommendations grouped by type.
     *
     * @return array|null
     * @throws \Exception
     */
    public function getRecommendations(): ?array
    {
        // Just return the results if we already have them.
        if (isset($this->recommendations)) {
            return $this->recommendations;
        }

        // Do nothing if lookfor is empty.
        if (empty($this->lookfor)) {
            return null;
        }

        // Get language, do nothing if it is not supported.
        $language = $this->getTranslatorLocale();
        $language = (0 === strpos($language, 'en-')) ? 'en' : $language;
        if (!$this->finto->isSupportedLanguage($language)) {
            return null;
        }

        // Check cookie to find out how many times ontology recommendations have
        // already been shown in the current browser session. Do nothing if a
        // maximum value is set in configuration and it has been reached.
        $cookieValue = $this->cookieManager->get(self::COOKIE_NAME);
        $timesShownTotal = is_numeric($cookieValue) ? $cookieValue : 0;
        if (is_numeric($this->maxTimesShownPerSession)
            && $timesShownTotal > $this->maxTimesShownPerSession
        ) {
            return null;
        }

        // Get searchId and resultTotal.
        $this->searchId = $this->request->get('searchId')
            ?? $this->results->getSearchId()
            ?? null;
        $this->resultTotal = $this->request->get('resultTotal')
            ?? $this->results->getResultTotal();

        // Set up recommendations array.
        $this->recommendations = [
            Finto::TYPE_NONDESCRIPTOR => [],
            Finto::TYPE_SPECIFIER => [],
            Finto::TYPE_HYPONYM => []
        ];

        // Set up search terms array with quoted words as one search term.
        $terms = str_getcsv($this->lookfor, ' ');

        // Process each term and make API calls if applicable.
        foreach ($terms as $term) {
            // Determine if the term can or should be searched for.
            if (!($this->canMakeApiCalls() && $this->canAddRecommendation())) {
                break;
            }
            if ($this->skipFromFintoSearch($term)) {
                continue;
            }

            // Determine if narrower concepts should be looked for if applicable.
            $narrower = ((null === $this->minLargeResultTotal
                || $this->resultTotal >= $this->minLargeResultTotal))
                && $this->canMakeApiCalls(2);

            // Make the Finto API call(s).
            $fintoResults
                = $this->finto->extendedSearch($term, $language, [], $narrower);
            $this->apiCallTotal += 1;

            // Continue to next term if no results or "other" results.
            if (!$fintoResults
                || Finto::TYPE_OTHER === $fintoResults[Finto::RESULT_TYPE]
            ) {
                continue;
            }

            // Process and add Finto results.
            if (Finto::TYPE_HYPONYM === $fintoResults[Finto::RESULT_TYPE]) {
                // Hyponym results have required an additional API call.
                $this->apiCallTotal += 1;
                // The term uri parameter is from the original results.
                $termUri = $fintoResults[Finto::RESULTS]['results'][0]['uri'];
                // Narrower results are used for hyponym recommendations.
                foreach ($fintoResults[Finto::NARROWER_RESULTS] as $fintoResult) {
                    $this->addOntologyResult(
                        $term, $fintoResult, $fintoResults[Finto::RESULT_TYPE],
                        $termUri
                    );
                }
            } else {
                foreach ($fintoResults[Finto::RESULTS]['results'] as $fintoResult) {
                    $this->addOntologyResult(
                        $term, $fintoResult, $fintoResults[Finto::RESULT_TYPE]
                    );
                }
            }
        }

        if ($this->recommendationTotal > 0) {
            // There are recommendations, so set a new cookie value.
            $this->cookieManager->set(self::COOKIE_NAME, $timesShownTotal + 1);
        }

        return $this->recommendations;
    }

    /**
     * Can more API calls be made.
     *
     * @param int $count Number of API calls needed, defaults to 1.
     *
     * @return bool
     */
    protected function canMakeApiCalls(int $count = 1): bool
    {
        return is_numeric($this->maxApiCalls)
            ? ($this->apiCallTotal + $count) <= $this->maxApiCalls
            : true;
    }

    /**
     * Can another recommendation be added.
     *
     * @return bool
     */
    protected function canAddRecommendation(): bool
    {
        return is_numeric($this->maxRecommendations)
            ? $this->recommendationTotal < $this->maxRecommendations
            : true;
    }

    /**
     * Should the search term be skipped from Finto search.
     *
     * @param string $term Search term
     *
     * @return bool
     */
    protected function skipFromFintoSearch(string $term): bool
    {
        return 0 === strpos($term, 'topic_uri_str_mv:');
    }

    /**
     * Adds an ontology result to the recommendations array.
     *
     * @param string      $term        The term searched for
     * @param array       $fintoResult Finto result
     * @param string      $resultType  Result type
     * @param string|null $termUri     URI of the searched term if applicable
     *
     * @return void
     */
    protected function addOntologyResult(
        string $term, array $fintoResult, string $resultType, ?string $termUri = null
    ): void {
        $this->ontologyResultTotal += 1;

        // Recommendation memory cookie key and value.
        $cookieKey = $this->ontologyResultTotal;
        if ($this->searchId) {
            $cookieKey = $this->searchId . '-' . $cookieKey;
        }
        $cookieValue = $this->recommendationMemory->getDataString(
            'Ontology', $fintoResult['prefLabel'], $term, $resultType
        );

        // Recommendation link.
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
            RecommendationMemory::PARAMETER_NAME => $cookieKey
        ];
        $params = array_merge($this->request->toArray(), $params);
        unset(
            $params['mod'], $params['params'], $params['searchId'],
            $params['resultTotal']
        );
        $href = $this->urlHelper->__invoke(
            'search-results', [], ['query' => $params]
        );

        // Create result array.
        $ontologyResult = [
            'label' => $fintoResult['prefLabel'],
            'href' => $href,
            'cookieKey' => $cookieKey,
            'cookieValue' => $cookieValue,
            'result' => $fintoResult,
        ];

        // Add result and increase counter if the result is for a new term.
        if (!isset($this->recommendations[$resultType][$term])) {
            $this->recommendations[$resultType][$term] = [];
            $this->recommendationTotal += 1;
        }
        $this->recommendations[$resultType][$term][] = $ontologyResult;
    }
}
