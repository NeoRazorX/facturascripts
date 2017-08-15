<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
 * Description of ColumnItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ColumnItem extends VisualItem implements VisualItemInterface
{

    /**
     * Texto adicional que explica el campo al usuario
     * @var string
     */
    public $description;

    /**
     * Configuración del estado y alineamiento de la visualización
     * (left|right|center|none)
     * @var string
     */
    public $display;

    /**
     * Configuración del objeto de visualización del campo
     * @var WidgetItem
     */
    public $widget;

    /**
     * Construye e inicializa la clase.
     */
    public function __construct()
    {
        parent::__construct();

        $this->description = '';
        $this->display = 'none';
        $this->widget = new WidgetItem();
    }

    /**
     * Carga la estructura de atributos en base a un archivo XML
     * @param SimpleXMLElement $column
     */
    public function loadFromXML($column)
    {
        parent::loadFromXML($column);

        $column_atributes = $column->attributes();
        $this->description = (string) $column_atributes->description;
        $this->display = (string) $column_atributes->display;
        $this->widget->loadFromXMLColumn($column);
    }

    /**
     * Carga la estructura de atributos en base a la base de datos
     * @param SimpleXMLElement $column
     */
    public function loadFromJSON($column)
    {
        parent::loadFromJSON($column);
        $this->description = (string) $column['description'];
        $this->display = (string) $column['display'];
    }

    /**
     * Carga un grupo de columnas en base a la base de datos
     * @param type $columns
     * @return array
     */
    public function columnsFromJSON($columns)
    {
        $result = [];
        foreach ($columns as $data) {
            $columnItem = new ColumnItem();
            $columnItem->loadFromJSON($data);
            $columnItem->widget->loadFromJSONColumn($data);
            $result[] = $columnItem;
        }
        return $result;
    }

    /**
     * Genera el código html para visualizar la cabecera del elemento visual
     * @param string $value
     */
    public function getHeaderHTML($value)
    {
        $html = parent::getHeaderHTML($value);

        if (!empty($this->description)) {
            $html .= '<span title="' . $this->i18n->trans($this->description) . '"></span>';
        }
        return $html;
    }

    /**
     * Genera el código html para visualizar el dato del modelo
     * para controladores List
     * @param string $value
     */
    public function getListHTML($value)
    {
        return $this->widget->getListHTML($value);
    }

    /**
     * Genera el código html para visualizar el dato del modelo
     * para controladores Edit
     * @param string $value
     */
    public function getEditHTML($value)
    {
        $columnClass = ($this->numColumns < 12) ? (' col-md-' . $this->numColumns) : '';
        $input = $this->widget->getEditHTML($value);
        $header = $this->getHeaderHTML($this->title);
        $hint = empty($this->widget->hint) ? '' : ' title="' . $this->i18n->trans($this->widget->hint) . '"';
        $description = empty($this->description) ? '' : '<span class="help-block">' . $this->i18n->trans($this->description) . '</span>';

        switch ($this->widget->type) {
            case "checkbox-inline":
            case "checkbox":
                $html = '<div class="' . $this->widget->type . $columnClass . '">'
                    . '<label class="checkbox-inline"' . $hint . '>'
                    . $input . $header
                    . '</label>'
                    . $description
                    . '</div>';
                break;

            default:
                $html = '<div class="form-group' . $columnClass . '">'
                    . '<label for="' . $this->widget->fieldName . '"' . $hint . '>' . $header . '</label>'
                    . $input
                    . $description
                    . '</div>';
                break;
        }
        return $html;
    }
}
