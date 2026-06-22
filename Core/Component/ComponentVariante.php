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
use FacturaScripts\Dinamic\Model\Fabricante;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Join\VarianteProducto;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Selector modal de variante de producto.
 *
 * Extiende ComponentModalPicker con la lógica específica de Variante:
 *  - Filtros de fabricante y familia en el modal.
 *  - Búsqueda por referencia y descripción; excluye productos bloqueados.
 *  - Tabla con columnas: enlace, referencia+descripción, precio, stock.
 *  - Opciones de ordenación por referencia, precio y stock.
 *  - Campo match configurable (guarda referencia o idvariante).
 *
 * Corresponde al WidgetVariante del sistema antiguo de vistas XML.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentVariante extends ComponentModalPicker
{
    /** Campo de Variante que se guarda al seleccionar (referencia por defecto). */
    private string $match = 'referencia';

    public function setMatch(string $match): static
    {
        $this->match = $match;
        return $this;
    }

    protected function widgetActionName(): string
    {
        return 'widget-variante-search';
    }

    protected function jsFunctionPrefix(): string
    {
        return 'widgetVariante';
    }

    protected function defaultIcon(): string
    {
        return 'fa-solid fa-cubes';
    }

    protected function jsonSearch(Request $request): string
    {
        $query          = $request->request->get('query', '');
        $codfabricante  = $request->request->get('codfabricante', '');
        $codfamilia     = $request->request->get('codfamilia', '');
        $sort           = $request->request->get('sort', 'ref-asc');

        $data = [];
        foreach ($this->searchVariantes($query, $codfabricante, $codfamilia, $sort) as $v) {
            $matchVal = $v->{$this->match} ?? $v->referencia;
            $data[] = [
                'match'       => $matchVal,
                'referencia'  => $v->referencia,
                'descripcion' => Tools::textBreak($v->description(), 300),
                'precio'      => $v->precio,
                'precio_str'  => Tools::money($v->precio),
                'stock'       => $v->stockfis,
                'stock_str'   => Tools::number($v->stockfis, 0),
                'url'         => $v->url(),
            ];
        }

        return json_encode($data);
    }

    protected function sortOptions(): array
    {
        return [
            'ref-asc'    => 'sort-by-ref-asc',
            'ref-desc'   => 'sort-by-ref-desc',
            'price-asc'  => 'sort-by-price-asc',
            'price-desc' => 'sort-by-price-desc',
            'stock-asc'  => 'sort-by-stock-asc',
            'stock-desc' => 'sort-by-stock-desc',
        ];
    }

    protected function renderExtraFilters(): string
    {
        return '<div class="col">' . $this->renderManufacturerFilter() . '</div>'
            . '<div class="col">' . $this->renderFamilyFilter() . '</div>';
    }

    protected function renderResultList(): string
    {
        $id   = $this->widgetId();
        $lang = Tools::lang();

        $rows = '';
        foreach ($this->searchVariantes('', '', '', 'ref-asc') as $v) {
            $matchVal    = htmlspecialchars((string) ($v->{$this->match} ?? $v->referencia));
            $reference   = htmlspecialchars($v->referencia);
            $description = htmlspecialchars(Tools::textBreak($v->description(), 300));
            $url         = htmlspecialchars($v->url());

            $priceClass = $v->precio < 0 ? ' text-danger' : ($v->precio == 0 ? ' text-warning' : '');
            $stockClass = $v->stockfis < 0 ? ' text-danger' : ($v->stockfis == 0 ? ' text-warning' : '');

            $rows .= '<tr class="clickableRow widget-variante-option"'
                . ' data-widget-variante-id="' . $id . '"'
                . ' data-widget-variante-value="' . $matchVal . '">'
                . '<td class="text-center">'
                . '<a href="' . $url . '" target="_blank" class="widget-variante-link">'
                . '<i class="fa-solid fa-external-link-alt fa-fw"></i>'
                . '</a>'
                . '</td>'
                . '<td><b>' . $reference . '</b> ' . $description . '</td>'
                . '<td class="text-end text-nowrap' . $priceClass . '">' . Tools::money($v->precio) . '</td>'
                . '<td class="text-end text-nowrap' . $stockClass . '">' . Tools::number($v->stockfis, 0) . '</td>'
                . '</tr>';
        }

        return '<div class="table-responsive">'
            . '<table class="table table-hover mb-0">'
            . '<thead><tr>'
            . '<th class="text-center"></th>'
            . '<th>' . $lang->trans('product')     . '</th>'
            . '<th class="text-end">' . $lang->trans('price') . '</th>'
            . '<th class="text-end">' . $lang->trans('stock') . '</th>'
            . '</tr></thead>'
            . '<tbody id="list_' . $id . '">' . $rows . '</tbody>'
            . '</table>'
            . '</div>';
    }

    protected function renderNewBtn(): string
    {
        $p = new Producto();
        return '<a href="' . htmlspecialchars($p->url('new')) . '" target="_blank" class="btn btn-success">'
            . '<i class="fa-solid fa-plus me-1"></i> ' . Tools::lang()->trans('new-product')
            . '</a>';
    }

    protected function readOnlyUrl(): string
    {
        $v = new Variante();
        if (!empty($this->value)) {
            $v->loadWhere([Where::eq($this->match, $this->value)]);
        }
        return $v->url();
    }

    public function registerAssets(): void
    {
        $route = Tools::config('route');
        AssetManager::addJs($route . '/Core/Assets/JS/WidgetVariante.js?v=' . Tools::date());
    }

    /**
     * @return Variante[]
     */
    private function searchVariantes(
        string $query,
        string $codfabricante,
        string $codfamilia,
        string $sort
    ): array {
        $list  = [];
        $where = [Where::eq('productos.bloqueado', false)];

        $model = new Variante();
        if (!empty($this->value) && $model->loadWhere([Where::eq($this->match, $this->value)])) {
            $list[]  = clone $model;
            $where[] = Where::notEq('variantes.referencia', $model->referencia);
        }

        if ($query) {
            $where[] = Where::like('variantes.referencia|productos.descripcion', $query);
        }
        if ($codfabricante) {
            $where[] = Where::eq('codfabricante', $codfabricante);
        }
        if ($codfamilia) {
            $where[] = Where::eq('codfamilia', $codfamilia);
        }

        $orderBy = match ($sort) {
            'ref-desc'   => ['referencia' => 'DESC'],
            'price-asc'  => ['precio'     => 'ASC'],
            'price-desc' => ['precio'     => 'DESC'],
            'stock-asc'  => ['stockfis'   => 'ASC'],
            'stock-desc' => ['stockfis'   => 'DESC'],
            default      => ['referencia' => 'ASC'],
        };

        $joinModel = new VarianteProducto();
        foreach ($joinModel->all($where, $orderBy, 0, 50) as $item) {
            $v = $model->get($item->idvariante);
            if ($v) {
                $list[] = $v;
            }
        }

        return $list;
    }

    private function renderManufacturerFilter(): string
    {
        $id     = $this->widgetId();
        $prefix = $this->jsFunctionPrefix();

        $options = '<option value="">' . Tools::lang()->trans('manufacturer') . '</option>'
            . '<option value="">------</option>';
        foreach (Fabricante::all([], ['nombre' => 'ASC']) as $f) {
            $options .= '<option value="' . htmlspecialchars($f->codfabricante) . '">'
                . htmlspecialchars($f->nombre) . '</option>';
        }

        return '<select class="form-select mb-2" id="modal_' . $id . '_fab"'
            . ' onchange="' . $prefix . 'Search(\'' . $id . '\');">'
            . $options
            . '</select>';
    }

    private function renderFamilyFilter(): string
    {
        $id     = $this->widgetId();
        $prefix = $this->jsFunctionPrefix();

        $options = '<option value="">' . Tools::lang()->trans('family') . '</option>'
            . '<option value="">------</option>';
        foreach (Familia::all([], ['descripcion' => 'ASC']) as $f) {
            $options .= '<option value="' . htmlspecialchars($f->codfamilia) . '">'
                . htmlspecialchars($f->descripcion) . '</option>';
        }

        return '<select class="form-select mb-2" id="modal_' . $id . '_fam"'
            . ' onchange="' . $prefix . 'Search(\'' . $id . '\');">'
            . $options
            . '</select>';
    }
}
