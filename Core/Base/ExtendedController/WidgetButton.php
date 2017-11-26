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
 * Description of WidgetButton
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetButton implements VisualItemInterface
{

    /**
     * Tipo de botón
     * @var string
     */
    public $type;

    /**
     * Código adicional asociado al botón
     * @var string
     */
    public $hint;

    /**
     * Icono asociado al botón
     * @var string
     */
    public $icon;

    /**
     * Acción JS asociada al botón
     * @var string
     */
    public $onClick;

    /**
     * Texto asociado al botón
     * @var string
     */
    public $label;

    /**
     * Acción asociada al botón
     * @var string
     */
    public $action;

    /**
     * Color asociado al botón
     * @var string
     */
    public $color;

    /**
     * Crea y carga la estructura de atributos en base a un archivo XML
     *
     * @param \SimpleXMLElement $button
     * @return WidgetButton
     */
    public static function newFromXML($button)
    {
        $widget = new WidgetButton();
        $widget->loadFromXML($button);
        return $widget;
    }

    public static function newFromJSON($button)
    {
        $widget = new WidgetButton();
        $widget->loadFromJSON($button);
        return $widget;
    }

    public function __construct()
    {
        $this->type = 'action';
        $this->label = '';
        $this->icon = '';
        $this->action = '';
        $this->onClick = '#';
        $this->color = 'light';
        $this->hint = '';
    }

    /**
     * Array with list of personalization functions of the column
     */
    public function columnFunction()
    {
        return ['ColumnClass', 'ColumnHint', 'ColumnDescription'];
    }

    public function loadFromXML($button)
    {
        $widget_atributes = $button->attributes();
        $this->type = (string) $widget_atributes->type;
        $this->label = (string) $widget_atributes->label;
        $this->icon = (string) $widget_atributes->icon;
        $this->action = (string) $widget_atributes->action;
        $this->hint = (string) $widget_atributes->hint;

        if (!empty($widget_atributes->color)) {
            $this->color = (string) $widget_atributes->color;
        }

        if (!empty($widget_atributes->onclick)) {
            $this->onClick = (string) $widget_atributes->onclick;
        }
    }

    public function loadFromJSON($column)
    {
        $this->type = (string) $column['button']['type'];
        $this->label = (string) $column['button']['label'];
        $this->icon = (string) $column['button']['icon'];
        $this->action = (string) $column['button']['action'];
        $this->hint = (string) $column['button']['hint'];
        $this->color = (string) $column['button']['color'];
        $this->onClick = (string) $column['button']['onClick'];
    }

    /**
     * Returns the HTML code for the icon
     *
     * @return string
     */
    private function getIconHTML()
    {
        $html = empty($this->icon) ? '' : '<i class="fa ' . $this->icon . '"></i>&nbsp;&nbsp;';
        return $html;
    }

    /**
     * Returns the HTML code for the onclick event
     *
     * @return string
     */
    private function getOnClickHTML()
    {
        $html = empty($this->onClick) ? '' : ' onclick="' . $this->onClick . '"';
        return $html;
    }

    /**
     * Returns the HTML code to display a statistic button
     *
     * @param string $label
     * @param string $value
     * @param string $hint
     * @return string
     */
    private function getCalculateHTML($label, $value, $hint)
    {
        $html = '<button type="button" class="btn btn-' . $this->color . '" '
            . $this->getOnClickHTML() . ' style="margin-right: 5px;" ' . $hint . '>'
            . $this->getIconHTML()
            . '<span class="cust-text">' . $label . ' ' . $value . '</span></button>';

        return $html;
    }

    /**
     * Returns the HTML code to display an action button
     *
     * @param string $label
     * @param string $hint
     * @param string $formName
     * @param string $class
     * @return string
     */
    private function getActionHTML($label, $hint, $formName = 'main_form', $class = 'col-sm-auto')
    {
        $html = '<button class="' . $class . ' btn btn-' . $this->color . '"'
            . ' onclick="execActionForm(\'' . $formName . '\',\'' . $this->action . '\');" ' . $hint . '>'
            . $this->getIconHTML()
            . $label
            . '</button>';

        return $html;
    }

    /**
     * Returns the HTML code to display a button that links to a modal form
     *
     * @param string $label
     * @return string
     */
    private function getModalHTML($label, $class = 'col-sm-auto')
    {
        $html = '<button type="button" class="' . $class . ' btn btn-' . $this->color . '"'
            . ' data-toggle="modal" data-target="#' . $this->action . '">'
            . $this->getIconHTML()
            . $label
            . '</button>';
        return $html;
    }

    /**
     * Returns the HTML code to display a button
     *
     * @param string $label
     * @param string $value
     * @param string $hint
     * @return string
     */
    public function getHTML($label, $value = '', $hint = '', $class = 'col-sm-auto')
    {
        switch ($this->type) {
            case 'calculate':
                return $this->getCalculateHTML($label, $value, $hint);

            case 'action':
                return $this->getActionHTML($label, $hint, $value, $class);

            case 'modal':
                return $this->getModalHTML($label, $class);

            default:
                return '';
        }
    }

    /**
     * Generate the html code to visualize the visual element header
     *
     * @param string $value
     * @return string
     */
    public function getHeaderHTML($value)
    {
        return '';
    }

    /**
     * Devuelve el código HTML para la visualización de un popover
     * con el texto indicado.
     *
     * @param string $hint
     *
     * @return string
     */
    public function getHintHTML($hint)
    {
        return empty($hint) ? '' : ' data-toggle="popover" data-placement="auto" data-trigger="hover" data-content="' . $hint . '" ';
    }
}
