<?php
/**
 * "Get Search Tabs Recommendations" AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2018.
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
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use VuFind\Db\Table\Search as SearchTable;
use VuFind\Search\Results\PluginManager as ResultsManager;
use VuFind\Search\SearchRunner;
use VuFind\Session\Settings as SessionSettings;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\View\Renderer\RendererInterface;
use Zend\Config\Config;

/**
 * "Get Search Tabs Recommendations" AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetSearchTabsRecommendations extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * Search table
     *
     * @var SearchTable
     */
    protected $searchTable;

    /**
     * Results plugin manager
     *
     * @var ResultsManager
     */
    protected $resultsManager;

    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * Search runner
     *
     * @var SearchRunner
     */
    protected $searchRunner;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss       Session settings
     * @param Config            $config   Main config
     * @param SearchTable       $st       Search table
     * @param ResultsManager    $results  Results manager
     * @param RendererInterface $renderer View renderer
     * @param SearchRunner      $sr       Search runner
     */
    public function __construct(SessionSettings $ss, Config $config,
        SearchTable $st, ResultsManager $results, RendererInterface $renderer,
        SearchRunner $sr
    ) {
        $this->sessionSettings = $ss;
        $this->config = $config;
        $this->searchTable = $st;
        $this->resultsManager = $results;
        $this->renderer = $renderer;
        $this->searchRunner = $sr;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, internal status code, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        if (empty($this->config->SearchTabsRecommendations->recommendations)) {
            return $this->formatResponse('', self::STATUS_OK);
        }
        $recommendationsConfig
            = $this->config->SearchTabsRecommendations->recommendations;

        $id = $params->fromPost('searchId', $params->fromQuery('searchId'));
        $limit = $params->fromPost('limit', $params->fromQuery('limit', null));

        $search = $this->searchTable->select(['id' => $id])->current();
        if (empty($search)) {
            return $this->formatResponse(
                'Search not found', self::STATUS_ERROR, 400
            );
        }

        $minSO = $search->getSearchObject();
        $savedSearch = $minSO->deminify($this->resultsManager);
        $searchParams = $savedSearch->getParams();
        $searchClass = $searchParams->getSearchClassId();
        // Don't return recommendations if not configured or for combined view
        // or for search types other than basic search.
        if (empty($recommendationsConfig[$searchClass])
            || $searchClass == 'Combined'
            || $searchParams->getSearchType() != 'basic'
        ) {
            return $this->formatResponse('', self::STATUS_OK);
        }

        $query = $searchParams->getQuery();
        if (!($query instanceof \VuFindSearch\Query\Query)) {
            return $this->formatResponse('', self::STATUS_OK);
        }
        $lookfor = $query->getString();
        if (!$lookfor) {
            return $this->formatResponse('', self::STATUS_OK);
        }

        $view = $this->renderer;
        $view->results = $savedSearch;
        $searchTabsHelper = $view->plugin('searchtabs');
        $searchTabsHelper->setView($view);
        $tabs = $searchTabsHelper->getTabConfig(
            $searchClass,
            $lookfor,
            $searchParams->getQuery()->getHandler()
        );

        $html = '';
        $recommendations = array_map(
            'trim',
            explode(',', $recommendationsConfig[$searchClass])
        );
        foreach ($recommendations as $recommendation) {
            if ($searchClass == $recommendation) {
                // Who would want this?
                continue;
            }
            foreach ($tabs['tabs'] as $tab) {
                if ($tab['id'] == $recommendation) {
                    $uri = new \Zend\Uri\Uri($tab['url']);
                    $count = $this->config->SearchTabsRecommendations->count ?? 2;
                    $otherResults = $this->searchRunner->run(
                        $uri->getQueryAsArray(),
                        $tab['class'],
                        function ($runner, $params, $searchId) use ($count) {
                            $params->setLimit($count);
                            $params->setPage(1);
                            $params->resetFacetConfig();
                            $options = $params->getOptions();
                            $options->disableHighlighting();
                        }
                    );
                    if ($otherResults instanceof \VuFind\Search\EmptySet\Results) {
                        continue;
                    }

                    if (null !== $limit) {
                        $tab['url'] .= '&limit=' . urlencode($limit);
                    }
                    $html .= $view->partial(
                        'Recommend/SearchTabs.phtml',
                        [
                            'tab' => $tab,
                            'lookfor' => $lookfor,
                            'handler' => $searchParams->getQuery()->getHandler(),
                            'results' => $otherResults,
                            'params' => $searchParams
                        ]
                    );
                }
            }
        }

        return $this->formatResponse(['html' => $html], self::STATUS_OK);
    }
}
