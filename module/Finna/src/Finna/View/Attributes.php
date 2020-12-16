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
 * Helper for creating Attributes objects
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
     * Escaper
     *
     * @var Escaper
     */
    protected $escaper;

    /**
     * Constructor.
     *
     * @param Escaper           $escaper Escaper
     * @param array|Traversable $attribs Attributes
     */
    public function __construct($escaper, $attribs = [])
    {
        parent::__construct();
        $this->escaper = $escaper;
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
        if ($this->offsetExists($name)) {
            $this->offsetSet(
                $name, array_merge((array)$this->offsetGet($name), (array)$value)
            );
        } else {
            $this->offsetSet($name, $value);
        }
        return $this;
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
        if ($this->offsetExists($name)) {
            $storeValue = $this->offsetGet($name);
            if (is_array($storeValue)) {
                return in_array($value, $storeValue);
            } else {
                return $value === $storeValue;
            }
        }
        return false;
    }

    /**
     * Return a string of tag attributes.
     *
     * @return string
     */
    public function __toString()
    {
        $xhtml          = '';

        foreach ($this->getArrayCopy() as $key => $val) {
            $key = $this->escaper->escapeHtml($key);

            if (0 === strpos($key, 'on') || ('constraints' == $key)) {
                // Don't escape event attributes; _do_ substitute double quotes
                // with singles
                if (! is_scalar($val)) {
                    // non-scalar data should be cast to JSON first
                    $val = \Laminas\Json\Json::encode($val);
                }
            } else {
                if (is_array($val)) {
                    $val = implode(' ', $val);
                }
            }

            $val = $this->escaper->escapeHtmlAttr($val);

            if (strpos($val, '"') !== false) {
                $xhtml .= " $key='$val'";
            } else {
                $xhtml .= " $key=\"$val\"";
            }
        }

        return $xhtml;
    }
}
