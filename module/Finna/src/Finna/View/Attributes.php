<?php
/**
 * Class for storing and processing HTML tag attributes.
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
 * @package  View
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View;

use ArrayObject;
use Laminas\Escaper\Escaper;

/**
 * Class for storing and processing HTML tag attributes.
 *
 * @category VuFind
 * @package  View
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Attributes extends ArrayObject
{
    /**
     * HTML escaper
     *
     * @var Escaper
     */
    protected $htmlEscaper;

    /**
     * HTML attribute escaper
     *
     * @var Escaper
     */
    protected $htmlAttrEscaper;

    /**
     * Constructor.
     *
     * @param Escaper           $htmlEscaper     HTML escaper
     * @param Escaper           $htmlAttrEscaper Attribute escaper
     * @param array|Traversable $attribs         Attributes
     */
    public function __construct($htmlEscaper, $htmlAttrEscaper, $attribs = [])
    {
        parent::__construct();
        $this->htmlEscaper = $htmlEscaper;
        $this->htmlAttrEscaper = $htmlAttrEscaper;
        foreach ($attribs as $name => $value) {
            $this->offsetSet($name, $value);
        }
    }

    /**
     * Add a value to an attribute.
     *
     * Sets the attribute if it does not exist.
     *
     * @param $name  string       Name
     * @param $value string|array Value
     *
     * @return Attributes
     */
    public function add($name, $value)
    {
        $this->offsetSet(
            $name,
            $this->offsetExists($name)
                ? array_merge((array)$this->offsetGet($name), (array)$value)
                : $value
        );
    }

    /**
     * Merge attributes with existing attributes.
     *
     * @param $attribs array|Traversable Attributes
     *
     * @return $this
     */
    public function merge($attribs)
    {
        foreach ($attribs as $name => $value) {
            $this->add($name, $value);
        }
        return $this;
    }

    /**
     * Does a specific attribute with a specific value exist?
     *
     * @param $name  string Name
     * @param $value string Value
     *
     * @return bool
     */
    public function hasValue($name, $value)
    {
        if (! $this->offsetExists($name)) {
            return false;
        }

        $storeValue = $this->offsetGet($name);
        if (is_array($storeValue)) {
            return in_array($value, $storeValue);
        }

        return $value === $storeValue;
    }

    /**
     * Return a string of tag attributes.
     *
     * @return string
     */
    public function __toString()
    {
        $xhtml = '';

        foreach ($this->getArrayCopy() as $key => $val) {
            $key = $this->htmlEscaper->escapeHtml($key);

            if ((0 === strpos($key, 'on') || ('constraints' === $key))
                && ! is_scalar($val)
            ) {
                // Don't escape event attributes; _do_ substitute double quotes
                // with singles non-scalar data should be cast to JSON first
                $val = json_encode(
                    $val,
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                );
            }

            if (0 !== strpos($key, 'on')
                && 'constraints' !== $key
                && is_array($val)
            ) {
                // Non-event keys and non-constraints keys with array values
                // should have values separated by whitespace
                $val = implode(' ', $val);
            }

            $val    = $this->htmlAttrEscaper->escapeHtmlAttr($val);
            $quote  = strpos($val, '"') !== false ? "'" : '"';
            $xhtml .= sprintf(' %2$s=%1$s%3$s%1$s', $quote, $key, $val);
        }

        return $xhtml;
    }
}
