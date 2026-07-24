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

use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;

/**
 * Clase base para los widgets de formularios y tablas.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class BaseWidget extends VisualItem
{
    /** @var bool Indica si el navegador puede autocompletar el campo. */
    public $autocomplete;

    /** @var string Nombre del campo del modelo asociado al widget. */
    public $fieldname;

    /** @var string Campo alternativo usado para generar enlaces. */
    public $fieldclick;

    /** @var string Clase CSS del icono mostrado junto al widget. */
    public $icon;

    /** @var string Controlador o URL usado al hacer clic en el valor. */
    public $onclick;

    /** @var array Opciones configuradas para el widget. */
    public $options = [];

    /** @var string Modo de solo lectura del widget. */
    public $readonly;

    /** @var bool Indica si el campo es obligatorio. */
    public $required;

    /** @var int Orden de tabulación del campo. */
    public $tabindex;

    /** @var string Tipo del widget. */
    private $type;

    /** @var mixed Valor actual del widget. */
    protected $value;

    /** @var mixed Valor alternativo usado en el enlace del widget. */
    protected $valueOnClick = null;

    /** @param array $data Configuración del widget. */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->autocomplete = false;
        $this->fieldname = $data['fieldname'];
        $this->fieldclick = $data['fieldclick'] ?? '';
        $this->icon = $data['icon'] ?? '';
        $this->onclick = $data['onclick'] ?? '';
        $this->readonly = $data['readonly'] ?? 'false';
        $this->tabindex = intval($data['tabindex'] ?? '-1');
        $this->required = isset($data['required']) && strtolower($data['required']) === 'true';
        $this->type = $data['type'];
        $this->loadOptions($data['children']);
        $this->assets();
    }

    /**
     * @param object $model Modelo que contiene el valor del campo.
     * @param string $title Clave de traducción del título.
     * @param string $description Clave de traducción de la descripción.
     * @param string $titleurl URL opcional para enlazar el título.
     *
     * @return string HTML del widget en modo edición.
     */
    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . Tools::trans($description) . '</small>';
        $labelHtml = '<label class="mb-0">' . $this->onclickHtml(Tools::trans($title), $titleurl) . '</label>';

        if (empty($this->icon)) {
            return '<div class="mb-3">'
                . $labelHtml
                . $this->inputHtml()
                . $descriptionHtml
                . '</div>';
        }

        return '<div class="mb-3">'
            . $labelHtml
            . '<div class="input-group">'
            . '<span class="input-group-text"><i class="' . $this->icon . ' fa-fw"></i></span>'
            . $this->inputHtml()
            . '</div>'
            . $descriptionHtml
            . '</div>';
    }

    /**
     * Devuelve el tipo de widget.
     *
     * @return string Tipo del widget.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array Formato del widget para vistas en cuadrícula.
     */
    public function gridFormat()
    {
        return [];
    }

    /**
     * @param object $model Modelo que contiene el valor del campo.
     *
     * @return string Campo oculto con el valor actual.
     */
    public function inputHidden($model)
    {
        $this->setValue($model);
        return '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>';
    }

    /**
     * @param object $model Modelo que contiene el valor del campo.
     *
     * @return string Representación en texto plano del valor.
     */
    public function plainText($model)
    {
        $this->setValue($model);
        return $this->show();
    }

    public function textOnly($model, $title = '')
    {
        $this->setValue($model);
        $labelHtml = Tools::trans($title) . ': ';

        return '<div>'
            . $labelHtml
            . '<strong>' . $this->plainText($model) . '</strong> '
            . '</div>';
    }

    /**
     * @param object $model Modelo donde se guarda el valor enviado.
     * @param Request $request Petición con los datos del formulario.
     */
    public function processFormData(&$model, $request)
    {
        $model->{$this->fieldname} = $request->request->get($this->fieldname);
    }

    /**
     * Asigna un valor fijo personalizado al widget.
     *
     * @param mixed $value Valor personalizado.
     */
    public function setCustomValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return bool Indica si el widget muestra totales en tablas.
     */
    public function showTableTotals(): bool
    {
        return false;
    }

    /**
     * @param object $model Modelo que contiene el valor del campo.
     * @param string $display Alineación del contenido.
     *
     * @return string HTML de la celda de tabla.
     */
    public function tableCell($model, $display = 'left')
    {
        $this->setValue($model);
        $class = $this->combineClasses($this->tableCellClass('text-' . $display), $this->class);
        return '<td class="' . $class . '">' . $this->onclickHtml($this->show()) . '</td>';
    }

    /**
     * Añade los recursos necesarios al gestor de assets.
     */
    protected function assets()
    {
        ;
    }

    /**
     * Normaliza entidades HTML existentes y escapa el valor para insertarlo en contexto HTML.
     *
     * @param mixed $value Valor a escapar.
     * @return string Valor escapado.
     */
    protected function escapeHtml($value): string
    {
        $decoded = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return htmlspecialchars($decoded, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param string $type Tipo de input HTML.
     * @param string $extraClass Clases CSS adicionales.
     *
     * @return string HTML del input.
     */
    protected function inputHtml($type = 'text', $extraClass = '')
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);
        return '<input type="' . $type . '" name="' . $this->fieldname . '" value="' . $this->value
            . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     * @return string Parámetros HTML adicionales del input.
     */
    protected function inputHtmlExtraParams()
    {
        $params = $this->required ? ' required=""' : '';
        $params .= $this->readonly() ? ' readonly=""' : '';
        $params .= $this->autocomplete ? '' : ' autocomplete="off"';
        $params .= $this->tabindex >= 0 ? ' tabindex="' . $this->tabindex . '"' : '';

        return $params;
    }

    /**
     * @param array $children Nodos hijos de configuración del widget.
     */
    protected function loadOptions($children): void
    {
        foreach ($children as $child) {
            if ($child['tag'] === 'option') {
                $child['text'] = html_entity_decode($child['text'] ?? '');
                $this->options[] = $child;
            }
        }
    }

    /**
     * @param string $inside Contenido HTML del enlace.
     * @param string $titleurl URL alternativa para el título.
     *
     * @return string Contenido envuelto en enlace cuando corresponde.
     */
    protected function onclickHtml($inside, $titleurl = '')
    {
        $value = empty($this->valueOnClick) ? $this->value : $this->valueOnClick;
        if (empty($this->onclick) || is_null($value)) {
            return empty($titleurl) ? $inside : '<a href="' . $titleurl . '">' . $inside . '</a>';
        }

        $params = str_contains($this->onclick, '?') ? '&' : '?';
        return '<a href="' . Tools::config('route') . '/' . $this->onclick . $params . 'code=' . rawurlencode($value)
            . '" class="cancelClickable">' . $inside . '</a>';
    }

    /**
     * @return bool Indica si el widget está en modo solo lectura.
     */
    protected function readonly(): bool
    {
        if ($this->readonly === 'dinamic') {
            return !empty($this->value);
        }

        return $this->readonly === 'true';
    }

    /**
     * @param object $model Modelo que contiene el valor del campo.
     */
    protected function setValue($model)
    {
        $this->value = $model->{$this->fieldname} ?? null;
        if (false === empty($this->fieldclick)) {
            $this->valueOnClick = $model->{$this->fieldclick} ?? null;
        }
    }

    /**
     * @return string Valor mostrado por el widget.
     */
    protected function show()
    {
        return is_null($this->value) ? '-' : (string)$this->value;
    }

    /**
     * @param string $initialClass Clase CSS inicial.
     * @param string $alternativeClass Clase CSS alternativa.
     *
     * @return string Clases CSS de la celda.
     */
    protected function tableCellClass($initialClass = '', $alternativeClass = '')
    {
        foreach ($this->options as $opt) {
            $textClass = $this->getColorFromOption($opt, $this->value, 'text-');
            if ($textClass) {
                $alternativeClass = $textClass;
                break;
            }
        }

        $class = [trim($initialClass)];
        if ($alternativeClass) {
            $class[] = $alternativeClass;
        } elseif (is_null($this->value)) {
            $class[] = $this->colorToClass('warning', 'text-');
        }

        return implode(' ', $class);
    }
}
