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
     * @param \SimpleXMLElement|\SimpleXMLElement[] $button
     *
     * @return WidgetButton
     */
    public static function newFromXML($button)
    {
        $widget = new WidgetButton();
        $widget->loadFromXML($button);
        return $widget;
    }

    /**
     * Create and load element structure from JSON file
     *
     * @param array $button
     *
     * @return WidgetButton
     */
    public static function newFromJSON($button)
    {
        $widget = new WidgetButton();
        $widget->loadFromJSON($button);
        return $widget;
    }

    /**
     * WidgetButton constructor.
     */
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

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $button
     */
    public function loadFromXML($button)
    {
        $widget_atributes = $button->attributes();
        $this->type = (string)$widget_atributes->type;
        $this->label = (string)$widget_atributes->label;
        $this->icon = (string)$widget_atributes->icon;
        $this->action = (string)$widget_atributes->action;
        $this->hint = (string)$widget_atributes->hint;

        if (!empty($widget_atributes->color)) {
            $this->color = (string)$widget_atributes->color;
        }

        if (!empty($widget_atributes->onclick)) {
            $this->onClick = (string)$widget_atributes->onclick;
        }
    }

    /**
     * Loads the attributes structure from a JSON file
     *
     * @param array $column
     */
    public function loadFromJSON($column)
    {
        $this->type = (string)$column['button']['type'];
        $this->label = (string)$column['button']['label'];
        $this->icon = (string)$column['button']['icon'];
        $this->action = (string)$column['button']['action'];
        $this->hint = (string)$column['button']['hint'];
        $this->color = (string)$column['button']['color'];
        $this->onClick = (string)$column['button']['onClick'];
    }

    /**
     * Returns the HTML code for the icon
     *
     * @return string
     */
    private function getIconHTML()
    {
        return empty($this->icon) ? '' : '<i class="fa ' . $this->icon . '"></i>&nbsp;&nbsp;';
    }

    /**
     * Returns the HTML code for the onclick event
     *
     * @return string
     */
    private function getOnClickHTML()
    {
        return empty($this->onClick) ? '' : ' onclick="' . $this->onClick . '"';
    }

    /**
     * Returns the HTML code to display a statistic button
     *
     * @param string $label
     * @param string $value
     * @param string $hint
     *
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
     * @param string $indexView
     * @param string $hint
     * @param string $class
     *
     * @return string
     */
    private function getActionHTML($label, $indexView, $hint, $class = 'col-sm-auto')
    {
        $active = '<input type="hidden" name="active" value="' . $indexView . '"/>';
        $action = '<input type="hidden" name="action" value="' . $this->action . '"/>';
        $button = '<button class="' . $class . ' btn btn-' . $this->color . '" type="submit"'
            . ' onclick="this.disabled = true; this.form.submit();" ' . $hint . '>'
            . $this->getIconHTML()
            . $label
            . '</button>';

        $html = '<form action="#" method="post" style="display:inline-block">'
            . $active
            . $action
            . $button
            . '</form>';

        return $html;
    }

    /**
     * Returns the HTML code to display a button that links to a modal form
     *
     * @param string $label
     * @param string $class
     *
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
     * @param string $class
     *
     * @return string
     */
    public function getHTML($label, $value = '', $hint = '', $class = 'col-sm-auto')
    {
        switch ($this->type) {
            case 'calculate':
                return $this->getCalculateHTML($label, $value, $hint);

            case 'action':
                return $this->getActionHTML($label, $value, $hint, $class);

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
     *
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
