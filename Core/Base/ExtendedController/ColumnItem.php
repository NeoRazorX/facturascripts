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
 * Description of ColumnItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ColumnItem extends VisualItem implements VisualItemInterface
{

    /**
     * Texto adicional que explica el campo al usuario
     *
     * @var string
     */
    public $description;

    /**
     * Configuración del estado y alineamiento de la visualización
     * (left|right|center|none)
     *
     * @var string
     */
    public $display;

    /**
     * Configuración del objeto de visualización del campo
     *
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
        $this->display = 'left';
        $this->widget = NULL;
    }

    /**
     * Carga la estructura de atributos en base a un archivo XML
     *
     * @param \SimpleXMLElement $column
     */
    public function loadFromXML($column)
    {
        parent::loadFromXML($column);

        if (empty($this->title)) {
            $this->title = $this->name;
        }

        $column_atributes = $column->attributes();
        $this->description = (string) $column_atributes->description;

        if (!empty($column_atributes->display)) {
            $this->display = (string) $column_atributes->display;
        }

        $this->widget = WidgetItem::newFromXMLColumn($column);
    }

    /**
     * Carga la estructura de atributos en base un archivo JSON
     *
     * @param array $column
     */
    public function loadFromJSON($column)
    {
        parent::loadFromJSON($column);
        $this->description = (string) $column['description'];
        $this->display = (string) $column['display'];

        if (!empty($this->widget)) {
            unset($this->widget);
        }
        $this->widget = WidgetItem::newFromJSONColumn($column);
    }

    /**
     * Carga un grupo de columnas en base a la base de datos
     *
     * @param array $columns
     *
     * @return array
     */
    public function columnsFromJSON($columns)
    {
        $result = [];
        foreach ($columns as $data) {
            $columnItem = new self();
            $columnItem->loadFromJSON($data);
            $result[] = $columnItem;
        }

        return $result;
    }

    /**
     * Genera el código html para visualizar la cabecera del elemento visual
     *
     * @param string $value
     *
     * @return string
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
     *
     * @param string $value
     *
     * @return string
     */
    public function getListHTML($value)
    {
        return $this->widget->getListHTML($value);
    }

    /**
     * Genera el código html para visualizar el dato del modelo
     * para controladores Edit
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $header = $this->getHeaderHTML($this->title);
        $input = $this->widget->getEditHTML($value);
        $data = $this->getColumnData(['ColumnClass', 'ColumnHint', 'ColumnRequired', 'ColumnDescription']);

        switch ($this->widget->type) {
            case 'checkbox':
                $html = $this->checkboxHTMLColumn($header, $input, $data);
                break;

            case 'radio':
                $html = $this->radioHTMLColumn($header, $input, $data, $value);
                break;

            default:
                $html = $this->standardHTMLColumn($header, $input, $data);
                break;
        }

        return $html;
    }

    /**
     * Devuelve el código HTML para el visionado de un campo no especial
     *
     * @param string $header
     * @param string $input
     * @param array $data
     *
     * @return string
     */
    private function standardHTMLColumn($header, $input, $data)
    {
        $label = ($header != null)
            ? '<label for="' . $this->widget->fieldName . '" ' . $data['ColumnHint'] . '>' . $header . '</label>'
            : '';

        return '<div class="form-group' . $data['ColumnClass'] . '">'
            . $label . $input . $data['ColumnDescription'] . $data['ColumnRequired']
            . '</div>';
    }

    /**
     * Devuelve el código HTML para el visionado de un campo checkbox
     *
     * @param string $header
     * @param string $input
     * @param array $data
     *
     * @return string
     */
    private function checkboxHTMLColumn($header, $input, $data)
    {
        $label = ($header != null)
            ? '<label class="form-check-label custom-control custom-checkbox mb-2 mr-sm-2 mb-sm-0" ' . $data['ColumnHint'] . '>' . $input . '&nbsp;' . $header . '</label>'
            : '';

        $result = '<div class="form-row align-items-center' . $data['ColumnClass'] . '">'
            . '<div class="form-check col">' . $label . $data['ColumnDescription'] . '</div>'
            . $data['ColumnRequired']
            . '</div>';

        return $result;
    }

    /**
     * Devuelve el código HTML para el visionado de una lista de opciones
     *
     * @param string $header
     * @param string $input
     * @param array $data
     * @param string $value
     *
     * @return string
     */
    private function radioHTMLColumn($header, $input, $data, $value)
    {
        $html = '';
        $index = 0;
        $template_var = ['"sufix%', '"value%', '"checked%'];

        $result = '<div class="' . $data['ColumnClass'] . '">'
            . '<label>' . $header . '</label>';

        foreach ($this->widget->values as $optionValue) {
            $checked = ($optionValue['value'] == $value) ? ' checked="checked"' : '';
            ++$index;
            $values = [($index . '"'), $optionValue['value'], $checked];
            $html .= '<div class="form-check">'
                . '<label class="form-check-label custom-control custom-checkbox mb-2 mr-sm-2 mb-sm-0" ' . $data['ColumnHint'] . '>'
                . str_replace($template_var, $values, $input)
                . '&nbsp;' . $optionValue['title']
                . '</label>'
                . '</div>';
        }

        $result .= $html . $data['ColumnRequired'] . '</div>';
        return $result;
    }

    /**
     * Ejecuta la lista de funciones ($properties)
     * para obtener las propiedades de la columna
     *
     * @param string[] $properties
     *
     * @return array
     */
    private function getColumnData($properties)
    {
        $result = [];
        foreach ($properties as $value) {
            $function = 'get' . $value;
            $result[$value] = $this->$function();
        }

        return $result;
    }

    /**
     * Devuelve la clase de la columna
     *
     * @return string
     */
    private function getColumnClass()
    {
        return ($this->numColumns > 0) ? (' col-md-' . $this->numColumns) : ' col';
    }

    /**
     * Devuelve el código HTML para la visualización de un popover
     * con el texto indicado.
     *
     * @return string
     */
    private function getColumnHint()
    {
        return $this->widget->getHintHTML($this->i18n->trans($this->widget->hint));
    }

    /**
     * Devuelve el código HTML para la visualización de si es una columna
     * requerida o no.
     *
     * @return string
     */
    private function getColumnRequired()
    {
        return $this->widget->required ? '<div class="invalid-feedback">' . $this->i18n->trans('please-enter-value') . '</div>' : '';
    }

    /**
     * Devuelve el código HTML para la visualización de una descripción.
     *
     * @return string
     */
    private function getColumnDescription()
    {
        return empty($this->description) ? '' : '<small class="form-text text-muted">' . $this->i18n->trans($this->description) . '</small>';
    }
}
