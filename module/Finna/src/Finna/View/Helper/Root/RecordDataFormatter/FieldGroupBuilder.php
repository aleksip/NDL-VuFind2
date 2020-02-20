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
     * Do nothing to provided lines array.
     */
    const UNUSED_DO_NOTHING = 0;

    /**
     * Remove lines used in groups from provided lines array.
     */
    const UNUSED_REMOVE_USED = 1;

    /**
     * Set unused lines from provided lines array as the first group.
     */
    const UNUSED_SET_FIRST = 2;

    /**
     * Set unused lines from provided lines array as the last group.
     */
    const UNUSED_SET_LAST = 3;

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
     * Add a group.
     *
     * @param string $label    Label for this group or false for no label.
     * @param array  $lines    Lines belonging to the group.
     * @param string $template Template used to render the lines in the group.
     * @param array  $options  Additional options (optional).
     *
     * @return void
     */
    public function addGroup($label, $lines, $template, $options = [])
    {
        $options['label'] = $label;
        $options['lines'] = $lines;
        $options['template'] = $template;
        if (!isset($options['context'])) {
            $options['context'] = [];
        }
        if (!isset($options['pos'])) {
            $this->maxPos += 100;
            $options['pos'] = $this->maxPos;
        }
        $this->groups[] = $options;
    }

    /**
     * Convenience method for setting multiple groups at once.
     *
     * The $lines array is passed as a reference and may be modified depending
     * on the value of $unused (see FieldGroupBuilder::UNUSED_REMOVE_USED).
     *
     * @param array  $groups        Array specifying the groups.
     * @param array  $lines         All lines used in the groups.
     * @param string $template      Default group template to use if not
     *                              specified for a group.
     * @param array  $options       Additional options to use if not specified
     *                              for a group (optional).
     * @param int    $unused        What to do to unused lines (optional).
     * @param array  $unusedOptions Additional options for unused lines
     *                              (optional).
     *
     * @return void
     */
    public function setGroups($groups, &$lines, $template, $options = [],
        $unused = self::UNUSED_DO_NOTHING, $unusedOptions = []
    ) {
        $allUsed = [];
        foreach ($groups as $group) {
            if (!isset($group['lines'])) {
                continue;
            }
            $groupLabel = $group['label'] ?? false;
            $groupTemplate = $group['template'] ?? $template;
            $groupOptions = $group['options'] ?? $options;
            $groupLines = [];
            if (isset($groupOptions['order'])
                && $groupOptions['order'] === 'array'
            ) {
                $pos = 0;
                foreach ($group['lines'] as $key) {
                    $groupLine = $lines[$key];
                    $pos += 100;
                    $groupLine['pos'] = $pos;
                    $groupLines[$key] = $groupLine;
                }
            } else {
                $groupLines
                    = array_intersect_key($lines, array_flip($group['lines']))
                    ?? $groupLines;
            }
            $allUsed = array_merge($allUsed, $groupLines);
            $this->addGroup($groupLabel, $groupLines, $groupTemplate, $groupOptions);
        }
        if ($unused === self::UNUSED_DO_NOTHING) {
            return;
        }
        $allUnused = array_diff_key($lines, $allUsed);
        if ($unused === self::UNUSED_REMOVE_USED) {
            $lines = $allUnused;
        } else {
            $unusedTemplate = $unusedOptions['template'] ?? $template;
            if ($unused === self::UNUSED_SET_FIRST && !empty($this->groups)) {
                $unusedOptions['pos'] = reset($this->groups)['pos'] - 100;
            }
            $this->addGroup(
                false, $allUnused, $unusedTemplate, $unusedOptions
            );
        }
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
