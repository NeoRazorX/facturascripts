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
 * Description of RowItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class RowItem implements VisualItemInterface
{
    /**
     * Tipo de row que se visualiza
     *
     * @var string
     */
    public $type;

    /**
     * Constructor dinámico de la clase.
     * Crea un objeto RowItem del tipo informado
     *
     * @param string $type
     */
    private static function rowItemFromType($type)
    {
        switch ($type) {
            case 'status':
                return new RowItemStatus();

            case 'header':
                return new RowItemHeader();

            case 'footer':
                return new RowItemFooter();

            default:
                return NULL;
        }
    }
    
    /**
     * Crea y carga la estructura del row en base a un archivo XML
     *
     * @param \SimpleXMLElement $row
     * @return RowItem
     */
    public static function newFromXMLRow($row)
    {
        $rowAtributes = $row->attributes();
        $type = (string) $rowAtributes->type;
        $result = self::rowItemFromType($type);
        $result->loadFromXML($row);
        return $result;
    }

    /**
     * Crea y carga la estructura del row en base a la base de datos
     *
     * @param array $row
     * @return RowItem
     */
    public static function newFromJSONRow($row)
    {
        $type = (string) $row['type'];
        $result = self::rowItemFromType($type);
        $result->loadFromJSON($row);
        return $result;
    }
    
    /**
     * RowItem constructor.
     */
    public function __construct($type)
    {
        $this->type = $type;
    }
    
    protected function getAttributesFromXML($item)
    {
        $result = [];
        foreach ($item->attributes() as $key => $value) {
            $result[$key] = (string) $value;
        }
        $result['value'] = trim((string) $item);
        return $result;
    }

    protected function loadButtonsFromXML($buttonsXML)
    {
        $buttons = [];
        foreach ($buttonsXML as $item) {
            $values = $this->getAttributesFromXML($item);
            $buttons[] = new WidgetButton($values);
            unset($values);
        }
        return $buttons;
    }
    
    /**
     * Carga la estructura de atributos en base a un archivo XML
     *
     * @param \SimpleXMLElement $row
     */
    abstract public function loadFromXML($row);

    /**
     * Carga la estructura de atributos en base un archivo JSON
     *
     * @param array $row
     */
    abstract public function loadFromJSON($row);


    /**
     * Genera el código html para visualizar la cabecera del elemento visual
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
