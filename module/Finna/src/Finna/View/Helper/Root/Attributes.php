<?php
/**
 * Helper for processing HTML tag attributes
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
 * @package  View_Helpers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

use Laminas\View\Helper\AbstractHelper;

/**
 * Helper for processing HTML tag attributes
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Attributes extends AbstractHelper
{
    /**
     * Array of tag attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Returns the helper, optionally setting the the stored attributes array.
     *
     * @param array $attributes Array of tag attributes
     *
     * @return \Finna\View\Helper\Root\Attributes
     */
    public function __invoke($attributes = null)
    {
        if (is_array($attributes)) {
            $this->setAttributes($attributes);
        }
        return $this;
    }

    /**
     * Returns a string of the tag attributes stored in the helper.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->createAttributesString($this->attributes);
    }

    /**
     * Sets (replaces) the stored attributes array.
     *
     * @param $attributes array Array of tag attributes
     *
     * @return \Finna\View\Helper\Root\Attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Creates a string of tag attributes.
     *
     * @param $attributes array Array of tag attributes
     *
     * @return string
     */
    public function createAttributesString($attributes)
    {
        $escapeHtml = $this->getView()->plugin('escapehtml');
        $escapeHtmlAttr = $this->getView()->plugin('escapehtmlattr');
        $html = '';
        foreach ($attributes as $key => $value) {
            $key = $escapeHtml($key);
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $value = $escapeHtmlAttr($value);
            if (false !== strpos($value, '"')) {
                $html .= " $key='$value'";
            } else {
                $html .= " $key=\"$value\"";
            }
        }
        return $html;
    }
}
