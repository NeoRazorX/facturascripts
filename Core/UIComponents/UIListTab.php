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
use FacturaScripts\Core\Component\FieldComponent;
use FacturaScripts\Core\Request;

/**
 * Representa una pestaña dentro de un UIListController multi-pestaña.
 *
 * Cada pestaña tiene su propio modelo, columnas, búsqueda, ordenación y
 * registros cargados. El controlador solo carga los registros de la pestaña activa.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIListTab
{
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    private string $name;
    private string $modelClassName;
    private string $titleKey;
    private string $icon;

    /** @var FieldComponent[] keyed by fieldname */
    private array $columns = [];
    private array $searchFields = [];
    private array $orderOptions = [];
    private array $colorConditions = [];

    private array $records = [];
    private int $count = 0;
    private int $offset = 0;
    private int $limit = 50;
    private string $query = '';
    private int $resolvedOrderIndex = -1;

    private string $newUrlValue = '';
    /** @var callable|null */
    private $rowUrlCallback = null;

    private function __construct(string $name, string $modelClassName, string $titleKey, string $icon)
    {
        $this->name = $name;
        $this->modelClassName = $modelClassName;
        $this->titleKey = $titleKey;
        $this->icon = $icon;
    }

    public static function make(
        string $name,
        string $modelClassName,
        string $titleKey,
        string $icon = 'fa-solid fa-list'
    ): self {
        return new self($name, $modelClassName, $titleKey, $icon);
    }

    public function addColumn(FieldComponent $component): FieldComponent
    {
        $this->columns[$component->fieldname()] = $component;
        return $component;
    }

    public function addSearchField(string ...$fields): static
    {
        foreach ($fields as $field) {
            $this->searchFields[] = $field;
        }
        return $this;
    }

    public function addOrderBy(array $fields, string $label, int $default = 0): static
    {
        $this->orderOptions[] = [
            'fields'  => $fields,
            'label'   => $label,
            'default' => $default,
        ];
        return $this;
    }

    public function addColor(string $field, mixed $value, string $color, string $title = ''): static
    {
        $this->colorConditions[] = [
            'field' => $field,
            'value' => $value,
            'color' => $color,
            'title' => $title,
        ];
        return $this;
    }

    public function setNewUrl(string $url): static
    {
        $this->newUrlValue = $url;
        return $this;
    }

    public function setRowUrlCallback(callable $fn): static
    {
        $this->rowUrlCallback = $fn;
        return $this;
    }

    public function loadRecords(Request $request, int $limit, array $extraWhere = []): void
    {
        $modelClass = self::MODEL_NAMESPACE . $this->modelClassName;
        if (!class_exists($modelClass)) {
            return;
        }

        $this->limit = $limit;
        $model = new $modelClass();
        $where = $extraWhere;

        $this->query = $request->inputOrQuery('query', '');
        if (!empty($this->query) && !empty($this->searchFields)) {
            $where[] = new DataBaseWhere(
                implode('|', $this->searchFields),
                $this->query,
                'LIKE'
            );
        }

        $this->offset = max(0, (int)$request->inputOrQuery('offset', 0));

        $rawIndex = (int)$request->inputOrQuery('order', -1);
        $this->resolvedOrderIndex = isset($this->orderOptions[$rawIndex]) ? $rawIndex : -1;
        $order = $this->resolveOrder();

        $this->count = $model->count($where);
        $this->records = $model->all($where, $order, $this->offset, $limit);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function modelClassName(): string
    {
        return $this->modelClassName;
    }

    public function title(): string
    {
        return $this->titleKey;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function records(): array
    {
        return $this->records;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function query(): string
    {
        return $this->query;
    }

    public function orderOptions(): array
    {
        return $this->orderOptions;
    }

    public function searchFields(): array
    {
        return $this->searchFields;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function orderIndex(): int
    {
        return $this->resolvedOrderIndex;
    }

    public function rowClass(object $record): string
    {
        foreach ($this->colorConditions as $cond) {
            $field = $cond['field'];
            if (property_exists($record, $field) && (string)$record->{$field} === (string)$cond['value']) {
                return $cond['color'];
            }
        }
        return '';
    }

    public function rowUrl(object $record): string
    {
        if ($this->rowUrlCallback !== null) {
            return ($this->rowUrlCallback)($record);
        }
        return '';
    }

    public function newUrl(): string
    {
        return $this->newUrlValue;
    }

    private function resolveOrder(): array
    {
        if ($this->resolvedOrderIndex >= 0 && isset($this->orderOptions[$this->resolvedOrderIndex])) {
            $opt = $this->orderOptions[$this->resolvedOrderIndex];
            $dir = ($this->resolvedOrderIndex % 2 === 0) ? 'ASC' : 'DESC';
            $order = [];
            foreach ($opt['fields'] as $field) {
                $order[$field] = $dir;
            }
            return $order;
        }

        foreach ($this->orderOptions as $opt) {
            if ($opt['default'] > 0) {
                $dir = $opt['default'] === 2 ? 'DESC' : 'ASC';
                $order = [];
                foreach ($opt['fields'] as $field) {
                    $order[$field] = $dir;
                }
                return $order;
            }
        }

        return [];
    }
}
