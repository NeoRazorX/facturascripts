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
 * Description of RowItemButtons
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class RowItemButtons extends RowItem
{

    /**
     * Buttons list.
     *
     * @var array
     */
    public $buttons;

    /**
     * RowItemButtons constructor.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        parent::__construct($type);
        $this->buttons = [];
    }

    /**
     * Creates the attributes structure from a JSON file
     *
     * @param array $row
     */
    public function loadFromJSON($row)
    {
        $this->type = (string) $row['type'];
        foreach ($row['buttons'] as $button) {
            $widgetButton = WidgetButton::newFromJSON($button);
            $this->buttons[] = $widgetButton;
        }
    }

    /**
     * Creates the attributes structure from a XML file
     *
     * @param \SimpleXMLElement[] $row
     */
    public function loadFromXML($row)
    {
        $this->buttons = $this->loadButtonsFromXML($row);
    }
}
