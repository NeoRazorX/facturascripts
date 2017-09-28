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
        $this->widget = new WidgetItem();
    }

    /**
     * Carga la estructura de atributos en base a un archivo XML
     *
     * @param SimpleXMLElement $column
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

        $this->widget->loadFromXMLColumn($column);
    }

    /**
     * Carga la estructura de atributos en base a la base de datos
     *
     * @param array $column
     */
    public function loadFromJSON($column)
    {
        parent::loadFromJSON($column);
        $this->description = (string) $column['description'];
        $this->display = (string) $column['display'];
        $this->widget->loadFromJSONColumn($column);
    }

    /**
     * Carga un grupo de columnas en base a la base de datos
     *
     * @param type $columns
     *
     * @return array
     */
    public function columnsFromJSON($columns)
    {
        $result = [];
        foreach ($columns as $data) {
            $columnItem = new self();
            $columnItem->loadFromJSON($data);
            $columnItem->widget->loadFromJSONColumn($data);
            $result[] = $columnItem;
        }

        return $result;
    }

    /**
     * Genera el código html para visualizar la cabecera del elemento visual
     *
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
     *
     * @param string $value
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
     */
    public function getEditHTML($value)
    {
        $columnClass = $this->getColumnClass();
        $input = $this->widget->getEditHTML($value);
        $header = $this->getHeaderHTML($this->title);
        $hint = $this->getColumnHint();
        $required = $this->getColumnRequired();
        $description = $this->getColumnDescription();

        switch ($this->widget->type) {
            case 'checkbox':
                $html = '<div class="form-row align-items-center' . $columnClass . '">'
                    . $this->checkboxHTMLColumn($header, $input, $hint, $description)
                    . $required
                    . '</div>';
                break;

            case 'radio':
                $html = '<div class="' . $columnClass . '">'
                    . '<label>' . $header . '</label>'
                    . $this->radioHTMLColumn($input, $hint, $value)
                    . $required
                    . '</div>';
                break;

            default:
                $html = $this->standardHTMLColumn($header, $input, $hint, $description, $columnClass, $required);
                break;
        }

        return $html;
    }

    /**
     * Devuelve el código HTML para el visionado de un campo no especial
     *
     * @param string $header
     * @param string $input
     * @param string $hint
     * @param string $description
     * @param string $columnClass
     * @param mixed $required
     *
     * @return string
     */
    private function standardHTMLColumn($header, $input, $hint, $description, $columnClass, $required)
    {
        return '<div class="form-group' . $columnClass . '">'
            . '<label for="' . $this->widget->fieldName . '"' . $hint . '>' . $header . '</label>'
            . $input
            . $description
            . $required
            . '</div>';
    }

    /**
     * Devuelve el código HTML para el visionado de un campo checkbox
     *
     * @param string $header
     * @param string $input
     * @param string $hint
     * @param string $description
     *
     * @return string
     */
    private function checkboxHTMLColumn($header, $input, $hint, $description)
    {
        return '<div class="form-check col">'
            . '<label class="form-check-label"' . $hint . '>'
            . $input . '&nbsp;' . $header
            . '</label>'
            . $description
            . '</div>';
    }

    /**
     * Devuelve el código HTML para el visionado de una lista de opciones
     *
     * @param string $input
     * @param string $hint
     * @param string $value
     *
     * @return string
     */
    private function radioHTMLColumn($input, $hint, $value)
    {
        $html = '';
        $index = 0;
        $template_var = ['"sufix%', '"value%', '"checked%'];
        foreach ($this->widget->values as $optionValue) {
            $checked = ($optionValue['value'] == $value) ? ' checked="checked"' : '';
            ++$index;
            $values = [($index . '"'), $optionValue['value'], $checked];
            $html .= '<div class="form-check"><label class="form-check-label"' . $hint . '>'
                . str_replace($template_var, $values, $input)
                . '&nbsp;' . $optionValue['title']
                . '</label></div>';
        }

        return $html;
    }

    private function getColumnClass()
    {
        return ($this->numColumns > 0) ? (' col-md-' . $this->numColumns) : ' col';
    }

    private function getColumnHint()
    {
        return $this->widget->getHintHTML($this->i18n->trans($this->widget->hint));
    }

    private function getColumnRequired()
    {
        return $this->widget->required ? '<div class="invalid-feedback">' . $this->i18n->trans('Por favor, introduzca un valor para el campo') . '</div>' : '';
    }

    private function getColumnDescription()
    {
        return empty($this->description) ? '' : '<small class="form-text text-muted">' . $this->i18n->trans($this->description) . '</small>';
    }
}
