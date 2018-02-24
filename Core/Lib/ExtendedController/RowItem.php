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
namespace FacturaScripts\Core\Lib\ExtendedController;

/**
 * This RowItem class modelises the common data and method of a RowItem element.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class RowItem implements VisualItemInterface
{

    /**
     * Displayed row type
     *
     * @var string
     */
    public $type;

    /**
     * Dynamic class constructor. Creates a RowItem objec of the given type.
     *
     * @param string $type
     *
     * @return RowItem|null
     */
    private static function rowItemFromType($type)
    {
        switch ($type) {
            case 'status':
                return new RowItemStatus();

            case 'actions':
            case 'statistics':
                return new RowItemButtons($type);

            case 'header':
            case 'footer':
                return new RowItemCards($type);

            default:
                return null;
        }
    }

    /**
     * Creates and loads the row structure from an XML file
     *
     * @param \SimpleXMLElement $row
     *
     * @return RowItem
     */
    public static function newFromXML($row)
    {
        $rowAtributes = $row->attributes();
        $type = (string) $rowAtributes->type;
        $result = self::rowItemFromType($type);
        $result->loadFromXML($row);

        return $result;
    }

    /**
     * Creates and loads the row structure from the database
     *
     * @param array $row
     *
     * @return RowItem
     */
    public static function newFromJSON($row)
    {
        $type = (string) $row['type'];
        $result = self::rowItemFromType($type);
        $result->loadFromJSON($row);

        return $result;
    }

    /**
     * RowItem constructor.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Return the attributes of an element from the XML.
     *
     * @param \SimpleXMLElement $item
     *
     * @return array
     */
    protected function getAttributesFromXML($item)
    {
        $result = [];
        foreach ($item->attributes() as $key => $value) {
            $result[$key] = (string) $value;
        }
        $result['value'] = trim((string) $item);

        return $result;
    }

    /**
     * Return a list of WidgetButtons from the XML.
     *
     * @param \SimpleXMLElement|\SimpleXMLElement[] $buttonsXML
     *
     * @return WidgetButton[]
     */
    protected function loadButtonsFromXML($buttonsXML)
    {
        $buttons = [];
        foreach ($buttonsXML->button as $item) {
            $widgetButton = WidgetButton::newFromXML($item);
            $buttons[] = $widgetButton;
            unset($widgetButton);
        }

        return $buttons;
    }

    /**
     * Returns a list of WidgetButton loaded from JSON.
     *
     * @param $buttonsJSON
     *
     * @return WidgetButton[]
     */
    protected function loadButtonsFromJSON($buttonsJSON)
    {
        $buttons = [];
        foreach ($buttonsJSON as $button) {
            $widgetButton = WidgetButton::newFromJSON($button);
            $buttons[] = $widgetButton;
            unset($widgetButton);
        }

        return $buttons;
    }

    /**
     * Creates and loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $row
     */
    abstract public function loadFromXML($row);

    /**
     * Creates and loads the attributes structure from JSON file
     *
     * @param array $row
     */
    abstract public function loadFromJSON($row);

    /**
     * Generates the HTML code to display the header for the visual element
     *
     * @param string $value
     *
     * @return string
     */
    public function getHeaderHTML($value)
    {
        return $value;
    }
}
