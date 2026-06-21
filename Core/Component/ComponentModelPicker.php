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

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Selector modal genérico para cualquier modelo de FacturaScripts.
 *
 * No contiene lógica de dominio: el modelo, las columnas, los campos de búsqueda,
 * el icono y las opciones de ordenación se configuran mediante setters.
 *
 * Uso mínimo:
 *   ComponentModelPicker::make('codsubcuenta')
 *       ->setModel(\FacturaScripts\Dinamic\Model\Subcuenta::class)
 *       ->setMatch('codsubcuenta')
 *       ->setIcon('fa-solid fa-book')
 *       ->setColumns(['codsubcuenta' => 'subaccount', 'descripcion' => 'description'])
 *       ->setSearchFields('codsubcuenta|descripcion')
 *
 * Opciones de ordenación (formato completo con ORDER BY):
 *   ->setSortOptions([
 *       'cod-asc'  => ['sort-by-code-asc',  ['codsubcuenta' => 'ASC']],
 *       'cod-desc' => ['sort-by-code-desc', ['codsubcuenta' => 'DESC']],
 *   ])
 *
 * Filtros extra (p. ej. filtro de ejercicio para subcuentas):
 *   ->setExtraFilters(function(string $widgetId, string $jsPrefix): string { ... })
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentModelPicker extends ComponentModalPicker
{
    private string $modelClass = '';
    private string $match = 'id';

    /** @var array<string, string> fieldname → trans-key */
    private array $columns = [];

    /** Campos de búsqueda en formato pipe: 'field1|field2'. */
    private string $searchFields = '';

    private string $pickerIcon = 'fa-solid fa-list';

    /**
     * Opciones de ordenación: ['sort-value' => ['trans-key', ['field' => 'ASC|DESC']]].
     * @var array<string, array{0: string, 1: array<string, string>}>
     */
    private array $customSortOptions = [];

    /** @var callable|null fn(string $widgetId, string $jsPrefix): string */
    private $extraFiltersRenderer = null;

    /** URL del botón "Nuevo" del modal. Si está vacía, el botón no se renderiza. */
    private string $newUrl = '';

    /** @var callable|null fn(Request): Where[] — condiciones adicionales para el AJAX. */
    private $extraWhereCallback = null;

    // ─── Setters ─────────────────────────────────────────────────────────────

    /** Clase del modelo a buscar (FQCN). */
    public function setModel(string $modelClass): static
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    /** Campo del modelo cuyo valor se guarda al seleccionar. */
    public function setMatch(string $match): static
    {
        $this->match = $match;
        return $this;
    }

    /**
     * Columnas a mostrar en la tabla del modal.
     *
     * @param array<string, string> $columns ['fieldname' => 'trans-key']
     */
    public function setColumns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /** Campos de búsqueda en formato pipe de FacturaScripts: 'field1|field2'. */
    public function setSearchFields(string $fields): static
    {
        $this->searchFields = $fields;
        return $this;
    }

    /** Clase CSS de FontAwesome para el icono del botón y del modal. */
    public function setIcon(string $icon): static
    {
        $this->pickerIcon = $icon;
        return $this;
    }

    /**
     * Opciones del selector de ordenación con su ORDER BY asociado.
     *
     * @param array<string, array{0: string, 1: array<string, string>}> $options
     */
    public function setSortOptions(array $options): static
    {
        $this->customSortOptions = $options;
        return $this;
    }

    /**
     * Callback para renderizar filtros extra en el modal (p. ej. filtro de ejercicio).
     *
     * Firma: fn(string $widgetId, string $jsPrefix): string
     * El resultado debe incluir uno o más <div class="col">…</div>.
     */
    public function setExtraFilters(callable $fn): static
    {
        $this->extraFiltersRenderer = $fn;
        return $this;
    }

    /** URL del botón "Nuevo" del pie del modal. */
    public function setNewUrl(string $url): static
    {
        $this->newUrl = $url;
        return $this;
    }

    /**
     * Callback para añadir condiciones Where al buscar en el AJAX.
     *
     * Firma: fn(Request $request): array — debe devolver un array de Where.
     * Útil para filtros dependientes del dominio (p. ej. codejercicio en subcuentas).
     */
    public function setExtraWhere(callable $fn): static
    {
        $this->extraWhereCallback = $fn;
        return $this;
    }

    // ─── ComponentModalPicker: identidad ─────────────────────────────────────

    protected function widgetActionName(): string
    {
        return 'widget-model-picker';
    }

    protected function jsFunctionPrefix(): string
    {
        return 'widgetModelPicker';
    }

    protected function defaultIcon(): string
    {
        return $this->pickerIcon;
    }

    // ─── ComponentModalPicker: búsqueda AJAX ─────────────────────────────────

    protected function jsonSearch(Request $request): string
    {
        $query = $request->request->get('query', '');
        $sort  = $request->request->get('sort', '');

        $model = new $this->modelClass();
        $where = [];
        $list  = [];

        if (!empty($this->value) && $model->loadWhere([Where::eq($this->match, $this->value)])) {
            $list[]  = clone $model;
            $where[] = Where::notEq($model->primaryColumn(), $model->id());
        }

        if ($query && $this->searchFields) {
            $where[] = Where::like($this->searchFields, $query);
        }

        if ($this->extraWhereCallback !== null) {
            array_push($where, ...($this->extraWhereCallback)($request));
        }

        $data = array_map([$this, 'itemToArray'], $list);
        foreach ($model->all($where, $this->resolveOrderBy($sort), 0, 50) as $item) {
            $data[] = $this->itemToArray($item);
        }

        return json_encode($data);
    }

    // ─── ComponentModalPicker: modal ─────────────────────────────────────────

    protected function sortOptions(): array
    {
        $result = [];
        foreach ($this->customSortOptions as $value => [$transKey]) {
            $result[$value] = $transKey;
        }
        return $result;
    }

    protected function renderExtraFilters(): string
    {
        if ($this->extraFiltersRenderer !== null) {
            return ($this->extraFiltersRenderer)($this->widgetId(), $this->jsFunctionPrefix());
        }
        return '';
    }

    protected function renderResultList(): string
    {
        $id   = $this->widgetId();
        $lang = Tools::lang();

        $headers = '<th class="text-center"></th>';
        foreach ($this->columns as $transKey) {
            $headers .= '<th>' . $lang->trans($transKey) . '</th>';
        }

        $model = new $this->modelClass();
        $rows  = '';
        foreach ($model->all([], [], 0, 50) as $item) {
            $rows .= $this->renderRow($item);
        }

        $colKeys = htmlspecialchars(json_encode(array_keys($this->columns)));

        return '<div class="table-responsive">'
            . '<table class="table table-hover mb-0">'
            . '<thead><tr>' . $headers . '</tr></thead>'
            . '<tbody id="list_' . $id . '"'
            . ' data-widget-model-picker-id="' . $id . '"'
            . ' data-columns="' . $colKeys . '">'
            . $rows
            . '</tbody>'
            . '</table>'
            . '</div>';
    }

    protected function renderNewBtn(): string
    {
        if (empty($this->newUrl)) {
            return '';
        }

        return '<a href="' . htmlspecialchars($this->newUrl) . '" target="_blank" class="btn btn-success">'
            . '<i class="fa-solid fa-plus me-1"></i> ' . Tools::lang()->trans('new')
            . '</a>';
    }

    // ─── ComponentModalPicker: solo lectura ──────────────────────────────────

    protected function readOnlyUrl(): string
    {
        $model = new $this->modelClass();
        if (!empty($this->value)) {
            $model->loadWhere([Where::eq($this->match, $this->value)]);
        }
        return method_exists($model, 'url') ? $model->url() : '#';
    }

    // ─── Assets ──────────────────────────────────────────────────────────────

    public function registerAssets(): void
    {
        $route = Tools::config('route');
        AssetManager::addJs($route . '/Core/Assets/JS/ComponentModelPicker.js?v=' . Tools::date());
    }

    // ─── Helpers internos ────────────────────────────────────────────────────

    private function itemToArray(object $item): array
    {
        $row = [
            '_match' => (string) ($item->{$this->match} ?? ''),
            '_url'   => method_exists($item, 'url') ? $item->url() : '#',
        ];
        foreach (array_keys($this->columns) as $field) {
            $row[$field] = $item->{$field} ?? null;
        }
        return $row;
    }

    private function renderRow(object $item): string
    {
        $id       = $this->widgetId();
        $matchVal = htmlspecialchars((string) ($item->{$this->match} ?? ''));
        $url      = htmlspecialchars(method_exists($item, 'url') ? $item->url() : '#');

        $cells = '<td class="text-center">'
            . '<a href="' . $url . '" target="_blank" class="widget-model-picker-link">'
            . '<i class="fa-solid fa-external-link-alt fa-fw"></i>'
            . '</a></td>';

        foreach (array_keys($this->columns) as $field) {
            $cells .= '<td>' . htmlspecialchars((string) ($item->{$field} ?? '')) . '</td>';
        }

        return '<tr class="clickableRow widget-model-picker-option"'
            . ' data-widget-model-picker-id="' . $id . '"'
            . ' data-widget-model-picker-value="' . $matchVal . '">'
            . $cells
            . '</tr>';
    }

    private function resolveOrderBy(string $sort): array
    {
        if (isset($this->customSortOptions[$sort][1])) {
            return $this->customSortOptions[$sort][1];
        }

        $firstField = array_key_first($this->columns) ?? 'id';
        return [$firstField => 'ASC'];
    }
}
