<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;

/**
 * Widget de autocompletar: input de texto con sugerencias asíncronas desde un origen
 * de datos (modelo o tabla). A diferencia de WidgetSelect, no precarga la lista de
 * valores: el controlador resuelve las coincidencias bajo demanda mientras el usuario
 * escribe (ver autocompleteAction en BaseController).
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WidgetAutocomplete extends WidgetSelect
{
    /** Texto descriptivo del valor seleccionado (lo que se muestra en el input). */
    protected $selected = null;

    /**
     * Si es true, el usuario solo puede elegir un valor existente en la lista de
     * sugerencias. Si es false, se admite cualquier texto libre como valor.
     *
     * @var bool
     */
    public $strict = true;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->strict = isset($data['strict']) ? ($data['strict'] == 'true') : true;
    }

    /**
     * @param object $model
     * @param string $title
     * @param string $description
     * @param string $titleurl
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . Tools::trans($description) . '</small>';
        $inputHtml = $this->inputHtml();
        $labelHtml = '<label class="mb-0">' . $this->onclickHtml(Tools::trans($title), $titleurl) . '</label>';

        if ('' === $this->value || null === $this->value) {
            return '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>'
                . '<div class="mb-3">'
                . $labelHtml
                . '<div class="input-group">'
                . '<span class="input-group-text"><i class="fa-solid fa-search fa-fw"></i></span>'
                . $inputHtml
                . '</div>'
                . $descriptionHtml
                . '</div>';
        }

        return '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>'
            . '<div class="mb-3">'
            . $labelHtml
            . '<div class="input-group">'
            . $this->inputGroupClearBtn()
            . $inputHtml
            . '</div>'
            . $descriptionHtml
            . '</div>';
    }

    /** Fija el texto descriptivo que se mostrará en el input para el valor seleccionado. */
    public function setSelected($text)
    {
        $this->selected = $text;
    }

    /**
     * Devuelve el texto descriptivo del valor seleccionado. Si no se ha fijado
     * uno explícito con setSelected(), lo resuelve consultando el CodeModel
     * (busca el fieldtitle correspondiente al fieldcode actual).
     */
    protected function getSelected()
    {
        return empty($this->selected) ? static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle) : $this->selected;
    }

    /** Registra los assets de jQuery UI y el JS del widget en el AssetManager. */
    protected function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::addCss($route . '/node_modules/jquery-ui-dist/jquery-ui.min.css?v=' . Tools::date(), 2);
        AssetManager::addJs($route . '/node_modules/jquery-ui-dist/jquery-ui.min.js?v=' . Tools::date(), 2);
        AssetManager::addJs($route . '/Dinamic/Assets/JS/WidgetAutocomplete.js?v=' . Tools::date());
    }

    /**
     * Devuelve el HTML del botón que aparece a la izquierda del input: una lupa
     * decorativa si el widget es de solo lectura, o un botón para limpiar el
     * valor seleccionado y reenviar el formulario en otro caso.
     */
    protected function inputGroupClearBtn()
    {
        if ($this->readonly()) {
            return '<span class="input-group-text"><i class="fa-solid fa-search fa-fw"></i></span>';
        }

        return '<button class="btn btn-spin-action btn-warning" type="button" onclick="this.form.' . $this->fieldname
            . '.value = \'\'; this.form.onsubmit(); this.form.submit();">'
            . '<i class="fa-solid fa-times" aria-hidden="true"></i>'
            . '</button>';
    }

    /**
     * Genera el <input> visible del widget. Vuelca en data-* todo lo que el JS de
     * autocompletar necesita para consultar al servidor (source, fieldcode,
     * fieldtitle, fieldfilter y strict).
     */
    protected function inputHtml($type = 'text', $extraClass = 'widget-autocomplete')
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);
        return '<input type="' . $type . '" value="' . $this->getSelected() . '" class="' . $class . '"'
            . ' data-field="' . $this->fieldname . '"'
            . ' data-source="' . $this->source . '"'
            . ' data-fieldcode="' . $this->fieldcode . '"'
            . ' data-fieldtitle="' . $this->fieldtitle . '"'
            . ' data-fieldfilter="' . $this->fieldfilter . '"'
            . ' data-strict="' . $this->strictStr() . '"'
            . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     * Configura el origen de datos. Fuerza $loadData = false al delegar en el
     * padre: el autocompletar no precarga la lista de valores, los resuelve
     * bajo demanda el controlador a partir de lo que escribe el usuario.
     */
    protected function setSourceData(array $child, bool $loadData = true)
    {
        parent::setSourceData($child, false);
    }

    /** Serializa el flag strict como '1' / '0' para el atributo data-strict. */
    protected function strictStr()
    {
        return $this->strict ? '1' : '0';
    }
}
