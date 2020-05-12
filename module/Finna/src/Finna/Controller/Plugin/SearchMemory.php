<?php
/**
 * SearchMemory controller plugin.
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
namespace Finna\Controller\Plugin;

use VuFind\Search\Memory;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * SearchMemory controller plugin.
 *
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class SearchMemory extends AbstractPlugin
{
    /**
     * Search memory
     *
     * @var Memory
     */
    protected $memory;

    /**
     * Constructor
     *
     * @param Memory $memory Search memory
     */
    public function __construct(Memory $memory)
    {
        $this->memory = $memory;
    }

    /**
     * Retrieve the last search id
     *
     * @return string
     */
    public function getLastSearchId()
    {
        $searchData = $this->memory->retrieveLastSearchData();
        return $searchData ? $searchData->id : '';
    }

    /**
     * Retrieve the last search type
     *
     * @return string
     */
    public function getLastSearchType()
    {
        $searchData = $this->memory->retrieveLastSearchData();
        return $searchData ? $searchData->type : '';
    }

    /**
     * Retrieve the last search lookfor
     *
     * @return string
     */
    public function getLastSearchLookfor()
    {
        $searchData = $this->memory->retrieveLastSearchData();
        return $searchData ? $searchData->lookfor : '';
    }

    /**
     * Retrieve the last search url
     *
     * @return string
     */
    public function getLastSearchUrl()
    {
        return $this->memory->retrieveSearch();
    }
}
