<?php
/**
 * Partial controller plugin interface.
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
 * Partial controller plugin interface.
 *
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
interface PartialInterface
{
    /**
     * Returns variables for the partial template if the partial is applicable
     * to the current controller, action and context.
     *
     * @param ViewModel|array $context Optional; if specified, the context.
     *
     * @return array|null Variables, or null if not applicable.
     */
    public function getVariables($context = null);

    /**
     * Returns a view model for the partial if the partial is applicable to the
     * current controller, action and context.
     *
     * @param ViewModel|array $context Optional; if specified, the context.
     *
     * @return ViewModel|null View model, or null if not applicable.
     */
    public function getModel($context = null);

    /**
     * Conditionally adds the partial to the "root" or "layout" view model if
     * applicable to the current controller, action and context.
     *
     * @param ViewModel|array $context Optional; if specified, the context.
     *
     * @return string|null The "capture to" value, or null if not applicable.
     */
    public function addToLayout($context = null);
}
