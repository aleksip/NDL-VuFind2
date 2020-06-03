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
     * Ontology constructor.
     *
     * @param Finto $finto Finto connection class
     */
    public function __construct(Finto $finto)
    {
        $this->finto = $finto;
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

        $queries = explode(' ', $this->lookfor);
        foreach ($queries as $query) {
            if ($fintoResults = $this->finto->search($query, $this->language)) {
                $this->processFintoResults($fintoResults, $query);
            }
        }
    }

    /**
     * Processes results of a single Finto search query.
     *
     * @param array  $fintoResults Finto results
     * @param string $query        The term searched for
     *
     * @return void
     */
    protected function processFintoResults($fintoResults, $query)
    {
        foreach ($fintoResults['results'] as $fintoResult) {
            // Check for non-descriptor results.
            if (true === $this->nonDescriptorMaxLimit
                || $this->resultTotal <= $this->nonDescriptorMaxLimit
            ) {
                if ($fintoResult['altLabel'] === $query) {
                    $this->addOntologyResult(
                        $fintoResult, $this->nonDescriptorResults, $query
                    );
                    continue;
                }
            }

            // Check for specifier results.
            if (true === $this->specifierMinLimit
                || $this->resultTotal >= $this->specifierMinLimit
            ) {
                if ($fintoResult['hiddenLabel'] === $query
                    && count($fintoResults['results']) > 1
                ) {
                    $this->addOntologyResult(
                        $fintoResult, $this->specifierResults, $query
                    );
                    continue;
                }
            }

            // Check for hyponym results.
            if (true == $this->hyponymMinLimit
                || $this->resultTotal >= $this->hyponymMinLimit
            ) {
            }
        }
    }

    /**
     * Adds an ontology result to a specified array.
     *
     * @param $fintoResult
     * @param $resultsArray
     * @param $query
     *
     * @return void
     */
    protected function addOntologyResult($fintoResult, &$resultsArray, $query)
    {
        $ontologyResult = [
            'label' => $fintoResult['prefLabel'],
            'href' => '',
            'result' => $fintoResult,
        ];
        $resultsArray[$query] = $resultsArray[$query] ?? [];
        $resultsArray[$query][] = $ontologyResult;
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
