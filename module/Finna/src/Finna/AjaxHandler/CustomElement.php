<?php
/**
 * AJAX handler for processing custom elements.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2021.
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
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\Session\Settings as SessionSettings;

/**
 * Server-side render a custom element via AJAX.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class CustomElement extends AbstractBase
{
    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss       Session settings
     * @param RendererInterface $renderer View renderer
     */
    public function __construct(SessionSettings $ss, RendererInterface $renderer)
    {
        $this->sessionSettings = $ss;
        $this->renderer = $renderer;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        $queryParams = $params->fromQuery();
        $name = $queryParams['element'] ?? null;
        if (empty($name)) {
            return $this->formatResponse(
                'Missing element name',
                self::STATUS_HTTP_BAD_REQUEST
            );
        }
        unset($queryParams['element']);

        // Render custom element:
        $viewHelper = $this->renderer->plugin('customElement');
        return $this->formatCustomElementResponse($viewHelper($name, $queryParams));
    }

    /**
     * Format a response array.
     *
     * @param mixed $response Response data
     *
     * @return array
     */
    protected function formatCustomElementResponse($response)
    {
        return parent::formatResponse(['html' => $response]);
    }
}
