<?php
/**
 * Dispatch aware controller trait.
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
 * @package  Controller
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace Finna\Controller;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

/**
 * Dispatch aware controller trait.
 *
 * PHP version 7
 *
 * @category VuFind
 * @package  Controller
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
trait DispatchAwareControllerTrait
{
    /**
     * Execute the request.
     *
     * @param MvcEvent $e Event.
     *
     * @return mixed|ViewModel
     */
    public function onDispatch(MvcEvent $e)
    {
        // Add common partial view models to the "root" or "layout" view model.
        $actionResponse = parent::onDispatch($e);
        if ($actionResponse instanceof ViewModel) {
            $this->searchBox()->addToLayout($actionResponse);
        }
        return $actionResponse;
    }
}
