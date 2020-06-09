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
use VuFind\I18n\Translator\TranslatorAwareInterface;
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
    use OntologyTrait;

    /**
     * Finto connection class.
     *
     * @var Finto
     */
    protected $finto;

    /**
     * Url helper.
     *
     * @var Url
     */
    protected $urlHelper;

    /**
     * Non-descriptor results.
     *
     * @var array
     */
    protected $nonDescriptorResults = [];

    /**
     * Specifier results.
     *
     * @var array
     */
    protected $specifierResults = [];

    /**
     * Hyponym results.
     *
     * @var array
     */
    protected $hyponymResults = [];

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
     * Ontology constructor.
     *
     * @param Finto $finto     Finto connection class
     * @param Url   $urlHelper Url helper
     */
    public function __construct(Finto $finto, Url $urlHelper)
    {
        $this->finto = $finto;
        $this->urlHelper = $urlHelper;
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
        // Do nothing if language is not supported.
        if (!$this->finto->isSupportedLanguage($this->language)) {
            return;
        }

        // Get the resultTotal if not set in an AJAX request.
        $this->resultTotal = $this->resultTotal ?? $results->getResultTotal();

        $terms = explode(' ', $this->lookfor);
        foreach ($terms as $term) {
            if (!($this->canMakeApiCall() && $this->canAddRecommendation())) {
                break;
            }
            if ($fintoResults = $this->finto->search($term, $this->language)) {
                $this->processFintoResults($fintoResults, $term);
            }
            $this->apiCallTotal += 1;
        }
    }

    /**
     * Processes results of a single Finto search query.
     *
     * @param array  $fintoResults Finto results
     * @param string $term         The term searched for
     *
     * @return void
     */
    protected function processFintoResults($fintoResults, $term)
    {
        foreach ($fintoResults['results'] as $fintoResult) {
            // Check for non-descriptor results.
            if ((false === $this->maxSmallResultTotal
                || $this->resultTotal <= $this->maxSmallResultTotal)
                && ((isset($fintoResult['altLabel'])
                && $fintoResult['altLabel'] === $term)
                || (isset($fintoResult['hiddenLabel'])
                && $fintoResult['hiddenLabel'] === $term
                && count($fintoResults['results']) === 1))
            ) {
                $this->addOntologyResult(
                    $fintoResult, $this->nonDescriptorResults, $term
                );
            }

            // Check for specifier results.
            if (isset($fintoResult['hiddenLabel'])
                && $fintoResult['hiddenLabel'] === $term
                && count($fintoResults['results']) > 1
            ) {
                $this->addOntologyResult(
                    $fintoResult, $this->specifierResults, $term
                );
            }

            // Check for hyponym results.
            if ((false === $this->minLargeResultTotal
                || $this->resultTotal >= $this->minLargeResultTotal)
                && count($fintoResults['results']) === 1
            ) {
                if (!$this->canMakeApiCall()) {
                    continue;
                }
                if ($hyponymResults = $this->finto->narrower(
                    $fintoResult['vocab'], $fintoResult['uri'],
                    $fintoResult['lang'], true
                )
                ) {
                    foreach ($hyponymResults['narrower'] as $hyponymResult) {
                        $this->addOntologyResult(
                            $hyponymResult, $this->hyponymResults, $term
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
     * @param array  $fintoResult  Finto result
     * @param array  $resultsArray Array to place result data into
     * @param string $term         The term searched for
     *
     * @return void
     */
    protected function addOntologyResult($fintoResult, &$resultsArray, $term)
    {
        // Create link.
        $base = $this->urlHelper->__invoke('search-results');
        $uriField = 'topic_uri_str_mv:' . $fintoResult['uri'];
        $replace = $uriField . ' ' . $fintoResult['prefLabel'];
        $recommend = str_replace($term, $replace, $this->lookfor);
        $query = http_build_query(
            [
                'lookfor' => $recommend,
                'lang' => $this->language,
                'ontologyTerm' => $term
            ]
        );
        $href = $base . '?' . $query;

        // Create result array.
        $ontologyResult = [
            'label' => $fintoResult['prefLabel'],
            'href' => $href,
            'result' => $fintoResult,
        ];

        // Add result and increase counter if the result is for a new term.
        if (!isset($resultsArray[$term])) {
            $resultsArray[$term] = [];
            $this->recommendationTotal += 1;
        }
        $resultsArray[$term][] = $ontologyResult;
    }

    /**
     * Get all ontology results grouped by type.
     *
     * @return array
     */
    public function getOntologyResults()
    {
        return [
            'nondescriptor' => $this->nonDescriptorResults,
            'specifier' => $this->specifierResults,
            'hyponym' => $this->hyponymResults,
        ];
    }
}
