<?php
/**
 * SearchBox partial controller plugin.
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
 * @package  Controller_Plugins
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace Finna\Controller\Plugin\Partial;

use Zend\View\Model\ViewModel;

/**
 * SearchBox partial controller plugin.
 *
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class SearchBox extends AbstractPartial
{
    /**
     * Returns variables for the partial template if the partial is applicable
     * to the current controller, action and context.
     *
     * @param ViewModel|array $context Optional; if specified, the context.
     *
     * @return array|null Variables, or null if not applicable.
     */
    public function getVariables($context = null)
    {
        $controller = $this->getControllerName();
        $action = $this->getActionName();
        $layout = $this->getController()->layout();

        if ('search' === $controller && 'results' === $action) {
            $params = $context->params;
            $results = $context->results;

            $lookfor = $results->getUrlQuery()->isQuerySuppressed()
                ? ''
                : $params->getDisplayQuery();
            return [
                'results' => $results,
                'lookfor' => $lookfor,
                'searchIndex' => $params->getSearchHandler(),
                'searchType' => $params->getSearchType(),
                'searchId' => $results->getSearchId(),
                'searchClassId' => $params->getSearchClassId(),
                'checkboxFilters' => $params->getCheckboxFacets(),
                'filterList' => $params->getFilterList(true),
                'hasDefaultsApplied' => $params->hasDefaultsApplied(),
                'selectedShards' => $params->getSelectedShards(),
                'savedSearches' => $layout->savedTabs,
                'ignoreHiddenFiltersInRequest' => isset($context->ignoreHiddenFiltersInRequest) ? $context->ignoreHiddenFiltersInRequest : false,
                'ignoreHiddenFilterMemory' => isset($context->ignoreHiddenFilterMemory) ? $context->ignoreHiddenFilterMemory : false,
            ];
        }

        if ('combined' === $controller && 'results' === $action) {
            $params = $context->params;
            $results = $context->results;

            $lookfor = $params->getDisplayQuery();
            return [
                    'lookfor' => $lookfor,
                    'searchIndex' => $params->getSearchHandler(),
                    'searchType' => $params->getSearchType(),
                    'searchId' => $results->getSearchId(),
                    'searchClassId' => $params->getSearchClassId(),
                    'checkboxFilters' => $params->getCheckboxFacets(),
                    'filterList' => $params->getFilterList(true),
                    'hasDefaultsApplied' => $params->hasDefaultsApplied(),
                    'selectedShards' => $params->getSelectedShards(),
                    'savedSearches' => $layout->savedTabs
            ];
        }

        $searchMemory = $this->getController()->searchMemory();
        $url = $searchMemory->getLastSearchUrl();
        if ('record' === $controller || 'collection' === $controller || !$url) {
            return [
                'ignoreHiddenFilterMemory' => false
            ];
        } else {
            $searchType = $searchMemory->getLastSearchType();
            $lookfor = $searchMemory->getLastSearchLookfor();

            //parse querystring to variables
            parse_str($url, $parsed);

            if ($searchType === 'advanced') {
                return [
                    'searchType' => 'advanced',
                    'ignoreHiddenFilterMemory' => false
                ];
            } else {
                $filters = [];
                foreach ($parsed as $key => $filter) {
                    if (strpos($key, 'filter') !== false) {
                        //used filters to filterArray
                        foreach ($filter as $value) {
                            $filterArray = explode(':', $value, 2);
                            $filterKey = $filterArray[0];
                            $filterValue = trim($filterArray[1], '"');
                            if (array_key_exists($filterKey, $filters)) {
                                array_push($filters[$filterKey], $filterValue);
                            } else {
                                $filters[$filterKey] = [$filterValue];
                            }
                        }
                    } elseif (strpos($key, 'type') !== false) {
                        $type = explode('#', $filter)[0];
                    }
                }

                return [
                    'lookfor' => $lookfor ?? '',
                    'filterList' => $filters ?? [],
                    'ignoreHiddenFilterMemory' => false,
                    'searchIndex' => $type ?? ''
                ];
            }
        }
    }
}
