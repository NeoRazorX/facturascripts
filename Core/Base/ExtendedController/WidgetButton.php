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
class WidgetButton
{
    public $type;
    public $icon;
    public $label;
    public $action;
    public $onClick;
    public $color;
    public $hint;

    /**
     *
     * @param array $values
     */
    public function __construct($values)
    {
        $this->type = $values['type'];
        $this->label = $values['label'];
        $this->icon = isset($values['icon']) ? $values['icon'] : '';
        $this->action = isset($values['action']) ? $values['action'] : '';
        $this->onClick = isset($values['onclick']) ? $values['onclick'] : '#';
        $this->color = isset($values['color']) ? $values['color'] : 'light';
        $this->hint = isset($values['hint']) ? $values['hint'] : '';
    }

    /**
     * Devuelve el código html para el icono
     *
     * @return string
     */
    private function getIconHTML()
    {
        $html = empty($this->icon)
            ? ''
            : '<i class="fa ' . $this->icon . '"></i>&nbsp;&nbsp;';
        return $html;
    }

    /**
     * Devuelve el código html para el evento onclick
     *
     * @return string
     */
    private function getOnClickHTML()
    {
        $html = empty($this->onClick)
            ? ''
            : ' onclick="' . $this->onClick . '"';
        return $html;
    }

    /**
     * Devuelve el código html para el pintado de un botón estadístico
     *
     * @param string $label
     * @param string $value
     * @param string $hint
     * @return string
     */
    private function getCalculateHTML($label, $value, $hint)
    {
        $html = '<button type="button" class="btn btn-' . $this->color . '"'
            . $this->getOnClickHTML() . ' style="margin-right: 5px;" ' . $hint . '>'
            . $this->getIconHTML()
            . '<span class="cust-text">' . $label . ' ' . $value . '</span></button>';

        return $html;
    }

    /**
     * Devuelve el código html para el pintado de un botón de acción
     *
     * @param string $label
     * @param string $indexView
     * @param string $hint
     * @return string
     */
    private function getActionHTML($label, $indexView, $hint)
    {
        $active = '<input type="hidden" name="active" value="' . $indexView . '">';
        $action = '<input type="hidden" name="action" value="' . $this->action . '">';
        $button = '<button class="btn btn-' . $this->color . '" type="submit"'
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
     * Devuelve el código html para el pintado de un botón que llama a un
     * formulario modal
     *
     * @param string $label
     * @return string
     */
    private function getModalHTML($label)
    {
        $html = '<button type="button" class="btn btn-' . $this->color . '"'
            . ' data-toggle="modal" data-target="#' . $this->action . '">'
            . $this->getIconHTML()
            . $label
            . '</button>';
        return $html;
    }

    /**
     * Devuelve el código html para el pintado de un botón
     *
     * @param string $label
     * @param string $value
     * @param string $hint
     * @return string
     */
    public function getHTML($label, $value = '', $hint = '')
    {
        switch ($this->type) {
            case 'calculate':
                return $this->getCalculateHTML($label, $value, $hint);

            case 'action':
                return $this->getActionHTML($label, $value, $hint);

            case 'modal':
                return $this->getModalHTML($label);

            default:
                return '';
        }
    }
}
