<?php
/**
 * Field group builder for record driver data formatting view helper
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
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\View\Helper\Root\RecordDataFormatter;

/**
 * Field group builder for record driver data formatting view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class FieldGroupBuilder
{
    /**
     * Groups.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * Highest position value so far.
     *
     * @var int
     */
    protected $maxPos = 0;

    /**
     * FieldGroupBuilder constructor.
     *
     * @param array $groups Existing field groups (optional).
     */
    public function __construct($groups = [])
    {
        $this->groups = is_array($groups) ? $groups : [];
        foreach ($groups as $current) {
            if (isset($current['pos']) && $current['pos'] > $this->maxPos) {
                $this->maxPos = $current['pos'];
            }
        }
    }

    /**
     * Set a group.
     *
     * @param string $key      Label to associate with this group.
     * @param array  $lines    Lines belonging to the group.
     * @param string $template Template used to render the lines in the group.
     * @param array  $options  Additional options.
     *
     * @return void
     */
    public function setGroup($key, $lines, $template, $options = [])
    {
        $options['spec'] = $lines;
        $options['template'] = $template;
        if (!isset($options['context'])) {
            $options['context'] = [];
        }
        if (!isset($options['pos'])) {
            $this->maxPos += 100;
            $options['pos'] = $this->maxPos;
        }
        $this->groups[$key] = $options;
    }

    /**
     * Get the group spec.
     *
     * @return array
     */
    public function getArray()
    {
        return $this->groups;
    }
}
