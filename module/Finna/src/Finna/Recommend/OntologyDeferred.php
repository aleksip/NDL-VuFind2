<?php
/**
 * OntologyDeferred Recommendations Module.
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

use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\Recommend\RecommendInterface;

/**
 * OntologyDeferred Recommendations Module.
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class OntologyDeferred implements RecommendInterface, TranslatorAwareInterface
{
    use OntologyTrait;

    /**
     * Called after the Search Results object has performed its main search.  This
     * may be used to extract necessary information from the Search Results object
     * or to perform completely unrelated processing.
     *
     * @param \VuFind\Search\Base\Results $results Search results object
     *
     * @return void
     */
    public function process($results)
    {
        $this->resultTotal = $results->getResultTotal();
    }

    /**
     * Get the URL parameters needed to make the AJAX recommendation request.
     *
     * @return string
     */
    public function getUrlParams()
    {
        return 'mod=Ontology&params='
            . urlencode(
                implode(':', [$this->maxApiCalls, $this->maxRecommendations])
            )
            . '&lookfor=' . urlencode($this->lookfor)
            . '&language=' . urldecode($this->language)
            . '&resultTotal=' . urldecode($this->resultTotal);
    }
}
