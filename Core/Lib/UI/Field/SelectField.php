<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\UI\Field;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Lib\UI\Contract\HandlesQueries;
use FacturaScripts\Core\Lib\UI\UIField;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;

/**
 * Selector potenciado por select2, con tres modos de datos:
 *
 *  - options([...])                          → opciones estáticas declaradas en PHP.
 *  - fromCodeModel($source, $code, $title)   → precarga todas las filas al renderizar.
 *  - searchable($source, $code, $title)      → select2 remoto: busca contra el
 *    endpoint _ui_query del propio componente (HandlesQueries).
 *
 * Cascadas: dependsOn('pais') declara que este select depende de otro campo del
 * mismo form. En modo precargado, el padre re-renderiza este fragmento al
 * cambiar (evento builtin _refresh); en modo remoto, el valor del padre viaja
 * como parámetro 'parent' de cada búsqueda.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class SelectField extends UIField implements HandlesQueries
{
    /** @var array<array{value: mixed, title: string}> */
    protected array $options = [];

    protected string $source = '';
    protected string $fieldcode = 'id';
    protected string $fieldtitle = '';

    /** true → select2 con búsqueda AJAX contra _ui_query. */
    protected bool $remote = false;

    protected bool $addEmpty = true;
    protected bool $translate = false;

    /** Nombre lógico del campo del mismo form del que depende este select. */
    protected string $parentField = '';

    /** Columna de BD por la que filtra el valor del padre. Vacío = mismo nombre que el campo padre. */
    protected string $filterColumn = '';

    protected function defaultTemplate(): string
    {
        return 'UI/Field/Select.html.twig';
    }

    // ------------------------------------------------------------------
    // Modos de datos
    // ------------------------------------------------------------------

    /**
     * Opciones estáticas: array asociativo valor => título, o lista de
     * ['value' => ..., 'title' => ...].
     */
    public function options(array $options): static
    {
        $this->options = [];
        foreach ($options as $key => $item) {
            if (is_array($item)) {
                $this->options[] = ['value' => $item['value'] ?? '', 'title' => (string)($item['title'] ?? '')];
            } else {
                $this->options[] = ['value' => $key, 'title' => (string)$item];
            }
        }
        return $this;
    }

    /** Precarga todas las filas de la tabla/modelo al renderizar. */
    public function fromCodeModel(string $source, string $fieldcode, string $fieldtitle = '', bool $translate = false): static
    {
        $this->source = $source;
        $this->fieldcode = $fieldcode;
        $this->fieldtitle = $fieldtitle ?: $fieldcode;
        $this->translate = $translate;
        $this->remote = false;
        return $this;
    }

    /** select2 remoto: busca contra el endpoint _ui_query de este componente. */
    public function searchable(string $source, string $fieldcode, string $fieldtitle = ''): static
    {
        $this->source = $source;
        $this->fieldcode = $fieldcode;
        $this->fieldtitle = $fieldtitle ?: $fieldcode;
        $this->remote = true;
        return $this;
    }

    /** Este select depende de otro campo del mismo form (cascada). */
    public function dependsOn(string $parentField, string $filterColumn = ''): static
    {
        $this->parentField = $parentField;
        $this->filterColumn = $filterColumn ?: $parentField;
        return $this;
    }

    public function allowEmpty(bool $addEmpty = true): static
    {
        $this->addEmpty = $addEmpty;
        return $this;
    }

    // ------------------------------------------------------------------
    // Datos resueltos para el render
    // ------------------------------------------------------------------

    /** @return array<array{value: mixed, title: string}> opciones a renderizar */
    public function values(): array
    {
        if ($this->source === '') {
            return $this->options;
        }

        if ($this->remote) {
            // solo la opción seleccionada; el resto llega por AJAX
            if ($this->value === null || $this->value === '') {
                return $this->addEmpty ? [['value' => '', 'title' => '------']] : [];
            }
            $description = (new CodeModel())->getDescription(
                $this->source, $this->fieldcode, $this->value, $this->fieldtitle
            );
            return [['value' => $this->value, 'title' => $description]];
        }

        $result = [];
        foreach (CodeModel::all($this->source, $this->fieldcode, $this->fieldtitle, $this->addEmpty, $this->parentWhere()) as $row) {
            $result[] = [
                'value' => $row->code,
                'title' => $this->translate ? Tools::lang()->trans($row->description) : $row->description,
            ];
        }
        return $result;
    }

    public function isSelected(mixed $optionValue): bool
    {
        if ($this->value === null || $this->value === '') {
            return $optionValue === '' || $optionValue === null;
        }
        return (string)$optionValue === (string)$this->value;
    }

    public function isRemote(): bool
    {
        return $this->remote;
    }

    /** name= HTML del campo padre, para que el JS lea su valor en las búsquedas remotas. */
    public function parentInputName(): string
    {
        if ($this->parentField === '') {
            return '';
        }
        return $this->form()?->field($this->parentField)?->inputName() ?? '';
    }

    /**
     * Paths de los selects del mismo form que dependen de este campo. Cuando no
     * está vacío, la plantilla añade el trigger de cambio que re-renderiza los
     * hijos (evento builtin _refresh).
     */
    public function dependentTargets(): string
    {
        $form = $this->form();
        if ($form === null) {
            return '';
        }
        $targets = [];
        foreach ($form->fields() as $field) {
            if ($field instanceof self && $field->parentField === $this->name && !$field->isRemote()) {
                $targets[] = $field->path();
            }
        }
        return implode(',', $targets);
    }

    public function displayValue(): string
    {
        foreach ($this->values() as $option) {
            if ($this->isSelected($option['value'])) {
                return $option['title'];
            }
        }
        return $this->value === null ? '-' : (string)$this->value;
    }

    // ------------------------------------------------------------------
    // Endpoint de datos (_ui_query)
    // ------------------------------------------------------------------

    public function handleQuery(string $action, Request $request): array
    {
        if ($action !== 'search' || $this->source === '') {
            return ['results' => []];
        }

        $term = $request->queryOrInput('term', '') ?? '';
        $rows = CodeModel::search($this->source, $this->fieldcode, $this->fieldtitle, $term, $this->parentWhere($request));

        $results = [];
        foreach ($rows as $row) {
            $results[] = ['id' => $row->code, 'text' => $row->description];
        }
        return ['results' => $results];
    }

    /** @return DataBaseWhere[] filtro por el valor del campo padre, si lo hay */
    protected function parentWhere(?Request $request = null): array
    {
        if ($this->parentField === '') {
            return [];
        }

        $parentValue = $request !== null
            ? $request->queryOrInput('parent', '')
            : $this->form()?->value($this->parentField);

        if ($parentValue === null || $parentValue === '') {
            return [];
        }
        return [new DataBaseWhere($this->filterColumn, $parentValue)];
    }

    public function registerAssets(): void
    {
        $route = Tools::config('route');
        AssetManager::addCss($route . '/node_modules/select2/dist/css/select2.min.css', 2);
        AssetManager::addCss($route . '/node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css', 2);
        AssetManager::addJs($route . '/node_modules/select2/dist/js/select2.min.js', 2);
    }
}
