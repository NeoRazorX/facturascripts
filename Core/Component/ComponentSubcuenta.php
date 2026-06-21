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
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Selector modal de subcuenta contable.
 *
 * Extiende ComponentModalPicker con la lógica específica de Subcuenta:
 *  - Filtro de ejercicio contable en el modal.
 *  - Búsqueda por codsubcuenta y descripción.
 *  - Tabla con columnas: enlace, código, descripción, saldo.
 *  - Opciones de ordenación por código, descripción, debe, haber y saldo.
 *  - Filtrado opcional por empresa (idempresa) para los ejercicios disponibles.
 *  - Campo match configurable (guarda codsubcuenta o idsubcuenta según configuración).
 *
 * Corresponde al WidgetSubcuenta del sistema antiguo de vistas XML.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentSubcuenta extends ComponentModalPicker
{
    /** Empresa con la que se filtran los ejercicios disponibles en el modal. */
    private ?int $idempresa = null;

    /** Campo de Subcuenta que se guarda al seleccionar (codsubcuenta por defecto). */
    private string $match = 'codsubcuenta';

    public function setIdempresa(?int $idempresa): static
    {
        $this->idempresa = $idempresa;
        return $this;
    }

    public function setMatch(string $match): static
    {
        $this->match = $match;
        return $this;
    }

    // ─── ComponentModalPicker: identidad ─────────────────────────────────────

    protected function widgetActionName(): string
    {
        return 'widget-subcuenta-search';
    }

    protected function jsFunctionPrefix(): string
    {
        return 'widgetSubaccount';
    }

    protected function defaultIcon(): string
    {
        return 'fa-solid fa-book';
    }

    // ─── ComponentModalPicker: búsqueda AJAX ─────────────────────────────────

    protected function jsonSearch(Request $request): string
    {
        $query = $request->request->get('query', '');
        $codej = $request->request->get('codejercicio', '');
        $sort  = $request->request->get('sort', 'cod-asc');

        $data = [];
        foreach ($this->searchSubcuentas($query, $codej, $sort) as $s) {
            $data[] = [
                'codsubcuenta' => $s->codsubcuenta,
                'descripcion'  => $s->descripcion,
                'saldo'        => $s->saldo,
                'url'          => $s->url(),
            ];
        }

        return json_encode($data);
    }

    // ─── ComponentModalPicker: modal ─────────────────────────────────────────

    protected function sortOptions(): array
    {
        return [
            'cod-asc'    => 'sort-by-code-asc',
            'cod-desc'   => 'sort-by-code-desc',
            'desc-asc'   => 'sort-by-description-asc',
            'desc-desc'  => 'sort-by-description-desc',
            'debe-asc'   => 'sort-by-debit-asc',
            'debe-desc'  => 'sort-by-debit-desc',
            'haber-asc'  => 'sort-by-credit-asc',
            'haber-desc' => 'sort-by-credit-desc',
            'saldo-asc'  => 'sort-by-balance-asc',
            'saldo-desc' => 'sort-by-balance-desc',
        ];
    }

    protected function renderExtraFilters(): string
    {
        return '<div class="col">' . $this->renderExerciseFilter() . '</div>';
    }

    protected function renderResultList(): string
    {
        $id   = $this->widgetId();
        $lang = Tools::lang();

        $rows = '';
        foreach ($this->searchSubcuentas('', '', 'cod-asc') as $s) {
            $matchVal    = htmlspecialchars((string) $s->{$this->match});
            $code        = htmlspecialchars($s->codsubcuenta);
            $description = htmlspecialchars($s->descripcion ?? '');
            $url         = htmlspecialchars($s->url());
            $saldoClass  = $s->saldo < 0 ? ' text-danger' : '';

            $rows .= '<tr class="clickableRow widget-subaccount-option"'
                . ' data-widget-subaccount-id="' . $id . '"'
                . ' data-widget-subaccount-value="' . $matchVal . '">'
                . '<td class="text-center">'
                . '<a href="' . $url . '" target="_blank" class="widget-subaccount-link">'
                . '<i class="fa-solid fa-external-link-alt fa-fw"></i>'
                . '</a>'
                . '</td>'
                . '<td><b>' . $code . '</b></td>'
                . '<td>' . $description . '</td>'
                . '<td class="text-end' . $saldoClass . '">' . Tools::number($s->saldo) . '</td>'
                . '</tr>';
        }

        return '<div class="table-responsive">'
            . '<table class="table table-hover mb-0">'
            . '<thead><tr>'
            . '<th class="text-center"></th>'
            . '<th>' . $lang->trans('subaccount')  . '</th>'
            . '<th>' . $lang->trans('description') . '</th>'
            . '<th class="text-end">' . $lang->trans('balance') . '</th>'
            . '</tr></thead>'
            . '<tbody id="list_' . $id . '">' . $rows . '</tbody>'
            . '</table>'
            . '</div>';
    }

    protected function renderNewBtn(): string
    {
        $s = new Subcuenta();
        return '<a href="' . htmlspecialchars($s->url('new')) . '" target="_blank" class="btn btn-success">'
            . '<i class="fa-solid fa-plus me-1"></i> ' . Tools::lang()->trans('new-subaccount')
            . '</a>';
    }

    // ─── ComponentModalPicker: solo lectura ──────────────────────────────────

    protected function readOnlyUrl(): string
    {
        $s = new Subcuenta();
        if (!empty($this->value)) {
            $s->loadWhere([Where::eq($this->match, $this->value)]);
        }
        return $s->url();
    }

    // ─── ComponentModalPicker: assets ────────────────────────────────────────

    public function registerAssets(): void
    {
        $route = Tools::config('route');
        AssetManager::addJs($route . '/Core/Assets/JS/WidgetSubcuenta.js?v=' . Tools::date());
    }

    // ─── Helpers internos ────────────────────────────────────────────────────

    /**
     * @return Subcuenta[]
     */
    private function searchSubcuentas(string $query, string $codejercicio, string $sort): array
    {
        $list  = [];
        $where = [];

        if (empty($codejercicio)) {
            $ejWhere  = $this->idempresa ? [Where::eq('idempresa', $this->idempresa)] : [];
            $ejercicios = Ejercicio::all($ejWhere, ['codejercicio' => 'DESC'], 0, 1);
            if (!empty($ejercicios)) {
                $codejercicio = $ejercicios[0]->codejercicio;
            }
        }

        $model = new Subcuenta();
        if (!empty($this->value) && $model->loadWhere([Where::eq($this->match, $this->value)])) {
            $list[]  = clone $model;
            $where[] = Where::notEq($model->primaryColumn(), $model->id());
        }

        if ($query) {
            $where[] = Where::like('codsubcuenta|descripcion', $query);
        }
        if ($codejercicio) {
            $where[] = Where::eq('codejercicio', $codejercicio);
        }

        $orderBy = match ($sort) {
            'cod-desc'   => ['codsubcuenta' => 'DESC'],
            'desc-asc'   => ['descripcion'  => 'ASC'],
            'desc-desc'  => ['descripcion'  => 'DESC'],
            'debe-asc'   => ['debe'         => 'ASC'],
            'debe-desc'  => ['debe'         => 'DESC'],
            'haber-asc'  => ['haber'        => 'ASC'],
            'haber-desc' => ['haber'        => 'DESC'],
            'saldo-asc'  => ['saldo'        => 'ASC'],
            'saldo-desc' => ['saldo'        => 'DESC'],
            default      => ['codsubcuenta' => 'ASC'],
        };

        foreach ($model->all($where, $orderBy, 0, 50) as $item) {
            $list[] = $item;
        }

        return $list;
    }

    private function renderExerciseFilter(): string
    {
        $id      = $this->widgetId();
        $prefix  = $this->jsFunctionPrefix();
        $ejWhere = $this->idempresa ? [Where::eq('idempresa', $this->idempresa)] : [];
        $ejercicios = Ejercicio::all($ejWhere, ['codejercicio' => 'DESC']);

        $options = '';
        $first   = true;
        foreach ($ejercicios as $ej) {
            $sel      = $first ? ' selected' : '';
            $options .= '<option value="' . htmlspecialchars($ej->codejercicio) . '"' . $sel . '>'
                . htmlspecialchars($ej->nombre) . '</option>';
            $first = false;
        }

        return '<select class="form-select mb-2" id="modal_' . $id . '_ej"'
            . ' onchange="' . $prefix . 'Search(\'' . $id . '\');" required>'
            . $options
            . '</select>';
    }
}
