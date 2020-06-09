<?php
/**
 * Trait for Ontology and OntologyDeferred Recommendations Modules.
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

use VuFind\I18n\Translator\TranslatorAwareTrait;

/**
 * Trait for Ontology and OntologyDeferred Recommendations Modules.
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
trait OntologyTrait
{
    use TranslatorAwareTrait;

    /**
     * Raw configuration parameters.
     *
     * @var string
     */
    protected $rawParams;

    /**
     * Maximum API calls to make.
     *
     * @var int
     */
    protected $maxApiCalls;

    /**
     * Maximum recommendations to show.
     *
     * @var int
     */
    protected $maxRecommendations;

    /**
     * Maximum total for determining if the result set is small.
     *
     * @var int
     */
    protected $maxSmallResultTotal;

    /**
     * Minimum total for determining if the result set is large.
     *
     * @var int
     */
    protected $minLargeResultTotal;

    /**
     * Current search query.
     *
     * @var string
     */
    protected $lookfor;

    /**
     * Search language.
     *
     * @var string
     */
    protected $language;

    /**
     * Total count of records in the result set.
     *
     * @var int
     */
    protected $resultTotal;

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
     * @param \Zend\StdLib\Parameters    $request Parameter object representing user
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

        // Collect the best possible search term(s):
        $this->lookfor = $request->get('lookfor', '');
        if (empty($this->lookfor) && is_object($params)) {
            // TODO: Filter out possible advanced search NOT terms.
            $this->lookfor = $params->getQuery()->getAllTerms();
        }
        $this->lookfor = trim($this->lookfor);

        // Get the language.
        $this->language = $request->get('language', '');
        if (empty($this->language)) {
            $this->language = $this->getLanguage();
            // Check for possible advanced search language selection.
            if (is_object($params)) {
                $filters = $params->getRawFilters();
                if (isset($filters['~language'])) {
                    $this->language = $filters['~language'][0];
                }
            }
        }

        // Get the resultTotal if set in an AJAX request.
        $this->resultTotal = $request->get('resultTotal', null);
    }

    /**
     * Returns the interface language.
     *
     * @return string
     */
    protected function getLanguage()
    {
        $language = $this->getTranslatorLocale();
        if (!in_array($language, ['en', 'sv', 'fi'])) {
            $language = 'en';
        }
        return $language;
    }
}
