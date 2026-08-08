<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Component;

use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;

/**
 * Selector modal genérico con búsqueda AJAX.
 *
 * Captura el patrón compartido por todos los widgets de selección de
 * FacturaScripts (WidgetSubcuenta, WidgetVariante, WidgetLibrary…):
 *
 *  - Un <input type="hidden"> que almacena el valor seleccionado.
 *  - Un botón Bootstrap que abre un modal.
 *  - Un modal con filtros y tabla/cuadrícula de resultados.
 *  - Búsqueda AJAX: el JS envía action=widget-XXX-search al servidor;
 *    la respuesta JSON se dibuja en la tabla via widgetXxxDraw().
 *  - En modo solo-lectura muestra un <a> a la ficha del registro.
 *
 * Las subclases implementan los métodos abstractos que definen:
 *  - La acción AJAX y el prefijo de las funciones JS globales.
 *  - El icono, las opciones de ordenación y los filtros extra del modal.
 *  - La tabla inicial de resultados y el botón "Nuevo".
 *  - La lógica de búsqueda que genera el JSON AJAX.
 *  - La URL de la ficha en modo solo-lectura.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class ComponentModalPicker extends FieldComponent
{
    /** Contador estático para generar IDs únicos sin usar Date.now() ni rand(). */
    private static int $instanceCount = 0;

    /** ID único de esta instancia; usado como base de todos los IDs del DOM. */
    private string $widgetId;

    public function __construct(string $fieldname)
    {
        parent::__construct($fieldname);
        self::$instanceCount++;
        $this->widgetId = 'picker_' . $fieldname . '_' . self::$instanceCount;
    }

    /**
     * Nombre de la acción AJAX que el JS envía en el parámetro 'action'.
     * Ejemplo: 'widget-subcuenta-search', 'widget-variante-search'.
     */
    abstract protected function widgetActionName(): string;

    /**
     * Prefijo de las funciones JS globales del widget.
     * Ejemplo: 'widgetSubaccount' → llama a widgetSubaccountSearch(), widgetSubaccountSelect()…
     */
    abstract protected function jsFunctionPrefix(): string;

    /** Clase CSS de FontAwesome para el icono del botón del modal. */
    abstract protected function defaultIcon(): string;

    /**
     * Realiza la búsqueda y devuelve el cuerpo JSON que se enviará como respuesta AJAX.
     *
     * Recibe la Request completa para leer query, sort y cualquier filtro extra.
     */
    abstract protected function jsonSearch(Request $request): string;

    /**
     * Opciones del select de ordenación: ['valor' => 'clave-de-traducción', …].
     *
     * El primer elemento se marcará como `selected`.
     */
    abstract protected function sortOptions(): array;

    /**
     * Tabla (o cuadrícula) de resultados inicial del modal.
     *
     * Debe incluir un <tbody id="list_{widgetId}"> (o equivalente) donde el
     * JS inyecta las filas tras cada búsqueda AJAX.
     */
    abstract protected function renderResultList(): string;

    /**
     * Botón "Nuevo" del pie del modal.
     *
     * Típicamente un <a href="...url('new')..." target="_blank" class="btn btn-success">.
     */
    abstract protected function renderNewBtn(): string;

    /**
     * URL de la ficha del registro actualmente seleccionado.
     *
     * Se usa en el <a href="..."> que se muestra cuando el campo está en readonly.
     * Devuelve '#' si no hay valor o el modelo no existe.
     */
    abstract protected function readOnlyUrl(): string;

    // registerAssets() se hereda de FieldComponent (no-op) y cada subclase
    // concreta la sobreescribe para registrar su propio JS en AssetManager.

    /**
     * Texto que se muestra en el span del botón para el valor actual.
     *
     * La implementación base devuelve el valor tal cual (útil cuando el valor
     * almacenado ya es un código legible). La subclase puede sobreescribir para
     * mostrar, por ejemplo, la descripción en lugar del código.
     */
    protected function displayLabel(): string
    {
        return (string) ($this->value ?? '');
    }

    public function widgetId(): string
    {
        return $this->widgetId;
    }

    /**
     * Responde a la acción AJAX si coincide con widgetActionName() y col_name.
     *
     * UIController::dispatchWidgetAction() itera todos los componentes y llama
     * a este método; el primer componente que devuelve un string no nulo gana.
     */
    public function handleWidgetAction(string $action, Request $request): ?string
    {
        if ($action !== $this->widgetActionName()) {
            return null;
        }

        if ($request->request->get('col_name') !== $this->fieldname) {
            return null;
        }

        return $this->jsonSearch($request);
    }

    /**
     * Renderiza el selector completo: input hidden + etiqueta + botón + modal.
     *
     * Sobreescribe renderEdit() de FieldComponent porque la estructura HTML
     * del picker es completamente distinta a la de un input de texto estándar.
     */
    public function renderEdit(mixed $value = null): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        $id   = $this->widgetId;
        $icon = $this->defaultIcon();

        $labelText  = htmlspecialchars($this->label);
        $labelInner = $this->labelUrl
            ? '<a href="' . htmlspecialchars($this->labelUrl) . '">' . $labelText . '</a>'
            : $labelText;
        $label = '<label class="mb-0">' . $labelInner . '</label>';

        $safeValue   = htmlspecialchars((string) ($this->value ?? ''));
        $displayText = ($this->value !== null && $this->value !== '')
            ? htmlspecialchars($this->displayLabel())
            : Tools::lang()->trans('select');

        $hidden = '<input type="hidden" id="' . $id . '" name="' . $this->fieldname . '" value="' . $safeValue . '">';
        $errors = $this->renderInlineErrors();

        if ($this->isReadOnly()) {
            $btnClass = $this->hasValidationErrors() ? 'btn btn-outline-danger' : 'btn btn-outline-secondary';
            $btn = '<a href="' . htmlspecialchars($this->readOnlyUrl()) . '" class="' . $btnClass . '">'
                . '<i class="' . $icon . ' fa-fw"></i> ' . $displayText
                . '</a>';
            return '<div class="mb-3 d-grid">'
                . $hidden . $label . $btn . $errors
                . '</div>';
        }

        $btnClass = $this->hasValidationErrors() ? 'btn btn-outline-danger' : 'btn btn-outline-secondary';
        $btn = '<a href="#" class="' . $btnClass . '"'
            . ' data-bs-toggle="modal" data-bs-target="#modal_' . $id . '">'
            . '<i class="' . $icon . ' fa-fw"></i> '
            . '<span id="modal_span_' . $id . '">' . $displayText . '</span>'
            . '</a>';

        return '<div class="mb-3 d-grid">'
            . $hidden . $label . $btn . $errors
            . '</div>'
            . $this->renderModal($icon, $labelText);
    }

    public function renderHidden(): string
    {
        return '<input type="hidden" name="' . $this->fieldname
            . '" value="' . htmlspecialchars((string) ($this->value ?? '')) . '">';
    }

    /**
     * Envoltorio Bootstrap del modal; delega el contenido en renderModalBody()
     * y renderModalFooter().
     */
    protected function renderModal(string $icon, string $label): string
    {
        $id = $this->widgetId;
        return '<div class="modal fade" id="modal_' . $id . '" tabindex="-1"'
            . ' aria-labelledby="modal_' . $id . '_label" aria-hidden="true">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title" id="modal_' . $id . '_label">'
            . '<i class="' . $icon . ' me-1"></i> ' . $label
            . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
            . '</div>'
            . $this->renderModalBody()
            . $this->renderModalFooter()
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Cuerpo del modal: fila de filtros + lista de resultados.
     *
     * La fila de filtros incluye el buscador de texto (siempre), los filtros
     * extra específicos de cada widget (vía renderExtraFilters()) y el
     * selector de ordenación (siempre).
     */
    protected function renderModalBody(): string
    {
        return '<div class="modal-body">'
            . '<div class="row g-3">'
            . '<div class="col">' . $this->renderQueryFilter() . '</div>'
            . $this->renderExtraFilters()
            . '<div class="col">' . $this->renderSortFilter() . '</div>'
            . '</div>'
            . '</div>'
            . $this->renderResultList();
    }

    /**
     * Filtros adicionales específicos del widget (ejercicio, fabricante, familia…).
     *
     * Implementación base vacía. La subclase sobreescribe para añadir sus propios
     * filtros envueltos en <div class="col">…</div>.
     */
    protected function renderExtraFilters(): string
    {
        return '';
    }

    /**
     * Pie del modal: botón "Nuevo" + botón "Ninguno" (si no es obligatorio).
     */
    protected function renderModalFooter(): string
    {
        return '<div class="modal-footer p-3">'
            . '<div class="w-100 d-flex gap-2">'
            . $this->renderNewBtn()
            . $this->renderNoneBtn()
            . '</div>'
            . '</div>';
    }

    /**
     * Input de búsqueda de texto libre con botón de lupa.
     *
     * Las funciones JS se construyen a partir de jsFunctionPrefix():
     *   widgetSubaccountSearchKp, widgetSubaccountSearch, etc.
     */
    protected function renderQueryFilter(): string
    {
        $id     = $this->widgetId;
        $prefix = $this->jsFunctionPrefix();
        return '<div class="input-group mb-2">'
            . '<input type="text" id="modal_' . $id . '_q" class="form-control"'
            . ' placeholder="' . Tools::lang()->trans('search') . '"'
            . ' oninput="' . $prefix . 'SearchKp(\'' . $id . '\', event);"'
            . ' onkeydown="if(event.key===\'Enter\'){event.preventDefault();' . $prefix . 'Search(\'' . $id . '\');}" autofocus>'
            . '<button type="button" class="btn btn-primary"'
            . ' onclick="' . $prefix . 'Search(\'' . $id . '\');">'
            . '<i class="fa-solid fa-search"></i>'
            . '</button>'
            . '</div>';
    }

    /**
     * Select de ordenación construido a partir de sortOptions().
     *
     * El onChange llama a widgetXxxSearch(id) vía jsFunctionPrefix().
     */
    protected function renderSortFilter(): string
    {
        $id      = $this->widgetId;
        $prefix  = $this->jsFunctionPrefix();
        $options = '';
        $first   = true;
        foreach ($this->sortOptions() as $val => $transKey) {
            $selected = $first ? ' selected' : '';
            $options .= '<option value="' . htmlspecialchars($val) . '"' . $selected . '>'
                . Tools::lang()->trans($transKey)
                . '</option>';
            $first = false;
        }
        return '<select class="form-select mb-2" id="modal_' . $id . '_s"'
            . ' onchange="' . $prefix . 'Search(\'' . $id . '\');">'
            . $options
            . '</select>';
    }

    /**
     * Botón "Ninguno" para desseleccionar el valor actual.
     *
     * Solo se renderiza si el campo no es obligatorio.
     */
    protected function renderNoneBtn(): string
    {
        if ($this->required) {
            return '';
        }
        $id     = $this->widgetId;
        $prefix = $this->jsFunctionPrefix();
        return '<button type="button" class="btn btn-secondary ms-auto"'
            . ' onclick="' . $prefix . 'Select(\'' . $id . '\', \'\'); return false;">'
            . '<i class="fa-solid fa-times me-1"></i> ' . Tools::lang()->trans('none')
            . '</button>';
    }

    public function schema(): array
    {
        return [
            'type'     => 'modal-picker',
            'field'    => $this->fieldname,
            'label'    => $this->label,
            'required' => $this->required,
            'readonly' => $this->readonly,
            'cols'     => $this->cols,
            'action'   => $this->widgetActionName(),
        ];
    }

    /** No se usa: renderEdit() controla el HTML completo. */
    protected function inputHtml(): string
    {
        return '';
    }

    protected function templateDir(): string
    {
        return 'modal-picker';
    }
}
