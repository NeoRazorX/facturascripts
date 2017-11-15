<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of RowItemFooter
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class RowItemFooter extends RowItem
{
    public $panels;
    public $buttons;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct('footer');
        $this->panels = [];
        $this->buttons = [];
    }

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
            $this->buttons[$values['name']] = $this->loadButtonsFromXML($item->button);
            unset($values);
        }
    }

    public function loadFromJSON($items)
    {

    }
    
    public function getButtons($key)
    {
        return $this->buttons[$key];
    }
}
