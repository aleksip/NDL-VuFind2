<?php
/**
 * Finna-tabs custom element
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
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace Finna\View\CustomElement;

/**
 * Finna-tabs custom element
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class FinnaTabs extends AbstractBase
{
    /**
     * FinnaTabs constructor.
     *
     * @param string $name    Element name
     * @param array  $options Options
     */
    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options, true);

        $responsiveMax = $this->attributes['responsive-max'] ?? null;
        if ($responsiveMax && in_array($responsiveMax, ['xxs', 'xs', 'sm', 'md'])) {
            // The 'attributes' variable may already be set if provided in options.
            $attributes = $this->getVariable('attributes', []);
            $attributes['class'] = $attributes['class'] ?? [];
            $attributes['class'][] = 'finna-tabs-responsive-max-' . $responsiveMax;
            $this->setVariable('attributes', $attributes);
        }

        if ($this->dom) {
            $items = [];
            $labelElements = $this->dom->find('[slot="label"]');
            foreach ($labelElements as $i => $label) {
                $items[$i] = [
                    'active' => $label->getAttribute('data-active') ?? false,
                    'id' => $label->getAttribute('data-id') ?? uniqid('tab-'),
                    'label' => strip_tags($label->innerHTML())
                ];
            }
            $contentElements = $this->dom->find('[slot="content"]');
            foreach ($contentElements as $i => $content) {
                if (isset($items[$i])) {
                    $items[$i]['content'] = $content->innerHTML();
                }
            }
            if (!empty($items)) {
                $this->setVariable('items', $items);
            }
        }

        $this->setTemplate('components/molecules/containers/finna-tabs/finna-tabs');
    }

    /**
     * Get information about the element.
     *
     * @return array
     */
    public static function getInfo(): array
    {
        return [
            self::TYPE => 'Block',
            self::CONTENTS => 'Flow',
            self::ATTR_COLLECTIONS => 'Common',
            self::ATTRIBUTES => ['responsive-max' => 'CDATA']
        ];
    }

    /**
     * Get information about child elements supported by the element.
     *
     * @return array Array containing element names as keys and arrays of
     *     HTMLPurifier_HTMLDefinition::addElement() arguments excluding the name of
     *     the element as values.
     */
    public static function getChildInfo(): array
    {
        $hArgs = [
            self::TYPE => 'Heading',
            self::CONTENTS => 'Inline',
            self::ATTR_COLLECTIONS => 'Common',
            self::ATTRIBUTES => [
                'data-active' => 'CDATA',
                'data-id' => 'CDATA',
                'slot' => 'CDATA'
            ]
        ];
        return [
            'h1' => $hArgs,
            'h2' => $hArgs,
            'h3' => $hArgs,
            'h4' => $hArgs,
            'h5' => $hArgs,
            'h6' => $hArgs,
            'div' => [
                self::TYPE => 'Block',
                self::CONTENTS => 'Flow',
                self::ATTR_COLLECTIONS => 'Common',
                self::ATTRIBUTES => ['slot' => 'CDATA']
            ]
        ];
    }
}
