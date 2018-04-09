<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base;

/**
 * Basic/common structure for the visual header
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class VisualItem
{

    /**
     * Translation engine
     *
     * @var Base\Translator
     */
    protected $i18n;

    /**
     * Column identifier
     *
     * @var string
     */
    public $name;

    /**
     * Number of columns that it occupies on display
     * ([1, 2, 4, 6, 8, 10, 12])
     *
     * @var int
     */
    public $numColumns;

    /**
     * Position to render ( from lowes to highest )
     *
     * @var int
     */
    public $order;

    /**
     * Group tag or title
     *
     * @var string
     */
    public $title;

    /**
     * Title link URL
     *
     * @var string
     */
    public $titleURL;

    /**
     * Check and apply special operations on the items
     */
    abstract public function applySpecialOperations();

    /**
     * Class construct and initialization
     */
    public function __construct()
    {
        $this->i18n = new Base\Translator();
        $this->name = 'root';
        $this->numColumns = 0;
        $this->order = 100;
        $this->title = '';
        $this->titleURL = '';
    }

    /**
     * Generates the HTML code to display the header for the visual element
     *
     * @param string $value
     *
     * @return string
     */
    public function getHeaderHTML($value)
    {
        $html = $this->i18n->trans($value);

        if (!empty($this->titleURL)) {
            $target = ($this->titleURL[0] !== '?') ? "target='_blank'" : '';
            $html = '<a href="' . $this->titleURL . '" ' . $target . '>' . $html . '</a>';
        }

        return $html;
    }

    /**
     * Loads the attributes structure from a JSON file
     *
     * @param array $items
     */
    public function loadFromJSON($items)
    {
        $this->name = (string) $items['name'];
        $this->title = (string) $items['title'];
        $this->titleURL = (string) $items['titleURL'];
        $this->numColumns = (int) $items['numColumns'];
        $this->order = (int) $items['order'];
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $items
     */
    public function loadFromXML($items)
    {
        $items_atributes = $items->attributes();
        if (!empty($items_atributes->name)) {
            $this->name = (string) $items_atributes->name;
        }
        $this->title = (string) $items_atributes->title;
        $this->titleURL = (string) $items_atributes->titleurl;

        if (!empty($items_atributes->numcolumns)) {
            $this->numColumns = (int) $items_atributes->numcolumns;
        }

        if (!empty($items_atributes->order)) {
            $this->order = (int) $items_atributes->order;
        }
    }
}
