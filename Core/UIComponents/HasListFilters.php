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

namespace FacturaScripts\Core\UIComponents;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;

/**
 * Trait que añade el sistema de filtros de columna a UIListController y UIListTab.
 *
 * Uso:
 *   $tab->addFilterSelect('idempresa', 'company', 'idempresa', $opciones);
 *   $tab->addFilterCheckbox('pagado', 'paid', 'pagado');
 *   $tab->addFilterPeriod('fecha', 'date', 'fecha');
 *   $tab->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre');
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
trait HasListFilters
{
    /** @var array<string, array{type: string, label: string, field: string, ...}> */
    private array $filterDefs = [];

    /** @var array<string, mixed> Valores actuales leídos de la request. */
    private array $filterValues = [];

    /**
     * Declara un filtro de selección (desplegable con valores estáticos).
     *
     * @param string $key    Identificador único del filtro.
     * @param string $label  Clave de traducción para la etiqueta.
     * @param string $field  Columna de la BD sobre la que aplica el WHERE.
     * @param array  $options Array de opciones: [['value' => ..., 'title' => ...], ...] o [value => title, ...].
     */
    public function addFilterSelect(string $key, string $label, string $field, array $options): static
    {
        $normalized = [];
        foreach ($options as $k => $v) {
            if (is_array($v)) {
                $normalized[] = $v;
            } else {
                $normalized[] = ['value' => $k, 'title' => $v];
            }
        }

        $this->filterDefs[$key] = [
            'type'    => 'select',
            'label'   => $label,
            'field'   => $field,
            'options' => $normalized,
        ];
        return $this;
    }

    /**
     * Declara un filtro booleano (checkbox).
     * Cuando está marcado aplica WHERE field = 1; desmarcado no filtra.
     */
    public function addFilterCheckbox(string $key, string $label, string $field): static
    {
        $this->filterDefs[$key] = [
            'type'  => 'checkbox',
            'label' => $label,
            'field' => $field,
        ];
        return $this;
    }

    /**
     * Declara un filtro de rango de fechas (desde/hasta).
     * Aplica WHERE field >= desde AND field <= hasta según los valores presentes.
     */
    public function addFilterPeriod(string $key, string $label, string $field): static
    {
        $this->filterDefs[$key] = [
            'type'  => 'period',
            'label' => $label,
            'field' => $field,
        ];
        return $this;
    }

    /**
     * Declara un filtro de autocompletar (select2 con búsqueda AJAX).
     *
     * @param string $source     Tabla de origen para CodeModel::search().
     * @param string $fieldcode  Campo clave de la tabla.
     * @param string $fieldtitle Campo título de la tabla (visible al usuario).
     */
    public function addFilterAutocomplete(
        string $key,
        string $label,
        string $field,
        string $source,
        string $fieldcode = 'id',
        string $fieldtitle = ''
    ): static {
        $this->filterDefs[$key] = [
            'type'       => 'autocomplete',
            'label'      => $label,
            'field'      => $field,
            'source'     => $source,
            'fieldcode'  => $fieldcode,
            'fieldtitle' => $fieldtitle ?: $fieldcode,
        ];
        return $this;
    }

    /** Indica si se ha declarado algún filtro. */
    public function hasFilters(): bool
    {
        return !empty($this->filterDefs);
    }

    /** Indica si algún filtro tiene valor activo en la request actual. */
    public function hasActiveFilters(): bool
    {
        foreach ($this->filterValues as $val) {
            if ($val !== null && $val !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Lee los valores de los filtros de la request y los almacena en $filterValues.
     * Debe llamarse en loadRecords() antes de buildFilterWhere().
     */
    protected function readFilterValues(Request $request): void
    {
        foreach ($this->filterDefs as $key => $def) {
            if ($def['type'] === 'period') {
                $from = $request->inputOrQuery('filter_' . $key . '_from', '');
                $to   = $request->inputOrQuery('filter_' . $key . '_to', '');
                $this->filterValues[$key . '_from'] = $from !== '' ? $from : null;
                $this->filterValues[$key . '_to']   = $to !== '' ? $to : null;
            } else {
                $val = $request->inputOrQuery('filter_' . $key, '');
                $this->filterValues[$key] = $val !== '' ? $val : null;
            }
        }
    }

    /**
     * Construye el array de DataBaseWhere a partir de los valores de filtro actuales.
     * Llamar readFilterValues() antes de este método.
     *
     * @return DataBaseWhere[]
     */
    protected function buildFilterWhere(): array
    {
        $where = [];

        foreach ($this->filterDefs as $key => $def) {
            if ($def['type'] === 'period') {
                $from = $this->filterValues[$key . '_from'] ?? null;
                $to   = $this->filterValues[$key . '_to'] ?? null;
                if ($from !== null) {
                    $where[] = new DataBaseWhere($def['field'], $from, '>=');
                }
                if ($to !== null) {
                    $where[] = new DataBaseWhere($def['field'], $to, '<=');
                }
            } else {
                $val = $this->filterValues[$key] ?? null;
                if ($val === null || $val === '') {
                    continue;
                }

                if ($def['type'] === 'checkbox') {
                    $where[] = new DataBaseWhere($def['field'], 1, '=');
                } else {
                    $where[] = new DataBaseWhere($def['field'], $val, '=');
                }
            }
        }

        return $where;
    }

    /**
     * Genera el HTML del row de filtros para inyectarlo en la plantilla Twig.
     *
     * Devuelve cadena vacía si no hay filtros declarados.
     *
     * @param string $formName ID del formulario padre (para el onchange submit).
     */
    public function renderFiltersHtml(string $formName): string
    {
        if (empty($this->filterDefs)) {
            return '';
        }

        $html = '';
        foreach ($this->filterDefs as $key => $def) {
            $html .= $this->renderSingleFilter($key, $def, $formName);
        }
        return $html;
    }

    private function renderSingleFilter(string $key, array $def, string $formName): string
    {
        $label   = htmlspecialchars(Tools::lang()->trans($def['label']));
        $name    = 'filter_' . $key;
        $submit  = 'document.getElementById(\'' . htmlspecialchars($formName) . '\').submit();';

        switch ($def['type']) {
            case 'select':
                return $this->renderFilterSelect($name, $label, $def['options'], $this->filterValues[$key] ?? null, $submit);

            case 'checkbox':
                return $this->renderFilterCheckbox($name, $label, !empty($this->filterValues[$key]), $submit);

            case 'period':
                return $this->renderFilterPeriod($name, $label, $this->filterValues[$key . '_from'] ?? null, $this->filterValues[$key . '_to'] ?? null);

            case 'autocomplete':
                return $this->renderFilterAutocomplete($name, $label, $def, $this->filterValues[$key] ?? null, $submit);

            default:
                return '';
        }
    }

    private function renderFilterSelect(string $name, string $label, array $options, mixed $current, string $submit): string
    {
        $html = '<div class="col-sm-auto mb-2">'
            . '<select name="' . $name . '" class="form-select form-select-sm" onchange="' . $submit . '">'
            . '<option value="">' . $label . '</option>';

        foreach ($options as $opt) {
            $val   = htmlspecialchars((string) ($opt['value'] ?? ''));
            $title = htmlspecialchars((string) ($opt['title'] ?? $val));
            $sel   = (string) ($opt['value'] ?? '') === (string) ($current ?? '') && $current !== null ? ' selected' : '';
            $html .= '<option value="' . $val . '"' . $sel . '>' . $title . '</option>';
        }

        $html .= '</select></div>';
        return $html;
    }

    private function renderFilterCheckbox(string $name, string $label, bool $checked, string $submit): string
    {
        $chk = $checked ? ' checked' : '';
        return '<div class="col-sm-auto mb-2">'
            . '<div class="form-check form-check-inline mt-1">'
            . '<input type="checkbox" class="form-check-input" name="' . $name . '" id="' . $name . '" value="1"' . $chk
            . ' onchange="' . $submit . '">'
            . '<label class="form-check-label" for="' . $name . '">' . $label . '</label>'
            . '</div></div>';
    }

    private function renderFilterPeriod(string $name, string $label, ?string $from, ?string $to): string
    {
        $fromVal = htmlspecialchars($from ?? '');
        $toVal   = htmlspecialchars($to ?? '');
        return '<div class="col-sm-auto mb-2">'
            . '<div class="input-group input-group-sm">'
            . '<span class="input-group-text">' . $label . '</span>'
            . '<input type="date" class="form-control form-control-sm" name="' . $name . '_from" value="' . $fromVal . '">'
            . '<input type="date" class="form-control form-control-sm" name="' . $name . '_to" value="' . $toVal . '">'
            . '</div></div>';
    }

    private function renderFilterAutocomplete(string $name, string $label, array $def, mixed $current, string $submit): string
    {
        $html = '<div class="col-sm-auto mb-2">'
            . '<div class="input-group input-group-sm">'
            . '<span class="input-group-text">' . $label . '</span>'
            . '<select class="form-select form-select-sm select2" name="' . $name . '"'
            . ' data-source="' . htmlspecialchars($def['source']) . '"'
            . ' data-fieldcode="' . htmlspecialchars($def['fieldcode']) . '"'
            . ' data-fieldtitle="' . htmlspecialchars($def['fieldtitle']) . '"'
            . ' onchange="' . $submit . '">'
            . '<option value="">' . $label . '</option>';

        if ($current !== null && $current !== '') {
            $html .= '<option value="' . htmlspecialchars((string) $current) . '" selected>'
                . htmlspecialchars((string) $current) . '</option>';
        }

        $html .= '</select></div></div>';
        return $html;
    }
}
