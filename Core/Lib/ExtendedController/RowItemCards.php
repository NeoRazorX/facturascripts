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

/**
 * Description of RowItemCards
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class RowItemCards extends RowItem
{

    /**
     * Buttons lists.
     *
     * @var WidgetButton[]
     */
    public $buttons;

    /**
     * Panels lists.
     *
     * @var array
     */
    public $panels;

    /**
     * Class constructor
     *
     * @param mixed $type
     */
    public function __construct($type)
    {
        parent::__construct($type);
        $this->buttons = [];
        $this->panels = [];
    }

    /**
     * Return the buttons for the received key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getButtons($key)
    {
        return $this->buttons[$key];
    }

    /**
     * Load the row structurefrom a JSON file.
     *
     * @param array $items
     */
    public function loadFromJSON($items)
    {
        $this->panels = $items['panels'];
        foreach ($items['buttons'] as $key => $buttons) {
            $this->buttons[$key] = $this->loadButtonsFromJSON($buttons);
        }
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $row
     */
    public function loadFromXML($row)
    {
        $groupCount = 1;
        foreach ($row->group as $item) {
            $values = $this->getAttributesFromXML($item);
            if (!isset($values['name'])) {
                $values['name'] = 'basic' . $groupCount;
                ++$groupCount;
            }

            $this->panels[$values['name']] = $values;
            $this->buttons[$values['name']] = $this->loadButtonsFromXML($item);
        }
    }
}
