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

/**
 * Selector simple o múltiple potenciado por select2.
 *
 * Las opciones pueden proporcionarse de forma estática mediante la familia de métodos
 * setValues*(), o de forma perezosa a través de setOptionsResolver() (un callable
 * evaluado en tiempo de renderizado). Para selectores con carga AJAX, usa setSource()
 * para configurar los atributos data-* que lee WidgetSelect.js al consultar el servidor.
 *
 * La selección múltiple serializa los valores elegidos como una cadena separada por
 * comas en el campo del modelo. El estado solo lectura se comunica mediante un input
 * oculto para que el valor se envíe igualmente cuando el <select> visible está deshabilitado.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentSelect extends BaseComponent
{
    protected string $fieldcode = 'id';
    protected string $fieldfilter = '';
    protected string $fieldtitle = 'id';
    protected int $limit = 0;
    protected bool $multiple = false;
    protected string $parent = '';
    /** @var callable|null */
    protected $optionsResolver = null;
    protected string $source = '';
    protected bool $translate = false;
    protected array $values = [];

    public function setMultiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function setParent(string $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function setSource(string $source, string $fieldcode = 'id', string $fieldtitle = ''): static
    {
        $this->source = $source;
        $this->fieldcode = $fieldcode;
        $this->fieldtitle = $fieldtitle ?: $fieldcode;
        return $this;
    }

    public function setTranslate(bool $translate = true): static
    {
        $this->translate = $translate;
        return $this;
    }

    public function setValues(array $values): static
    {
        $this->values = $values;
        return $this;
    }

    public function setValuesFromArray(array $items, bool $translate = false, bool $addEmpty = false): static
    {
        $this->values = [];

        if ($addEmpty && !$this->multiple) {
            $this->values[] = ['value' => null, 'title' => '------'];
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                $this->values[] = ['value' => $item, 'title' => $item];
                continue;
            }

            if (isset($item['value'])) {
                $this->values[] = [
                    'value' => $item['value'],
                    'title' => $item['title'] ?? $item['value'],
                    'group' => $item['group'] ?? '',
                ];
            }
        }

        if ($translate) {
            $this->applyTranslations();
        }

        return $this;
    }

    public function setValuesFromArrayKeys(array $values, bool $translate = false, bool $addEmpty = false): static
    {
        $this->values = [];

        if ($addEmpty && !$this->multiple) {
            $this->values[] = ['value' => null, 'title' => '------'];
        }

        foreach ($values as $key => $value) {
            $this->values[] = ['value' => $key, 'title' => $value, 'group' => ''];
        }

        if ($translate) {
            $this->applyTranslations();
        }

        return $this;
    }

    public function setValuesFromCodeModel(array $rows, bool $translate = false): static
    {
        $this->values = [];

        foreach ($rows as $codeModel) {
            $this->values[] = [
                'value' => $codeModel->code,
                'title' => $codeModel->description,
                'group' => '',
            ];
        }

        if ($translate) {
            $this->applyTranslations();
        }

        return $this;
    }

    public function setValuesFromRange(int $start, int $end, int $step = 1): static
    {
        $this->values = [];

        foreach (range($start, $end, $step) as $val) {
            $this->values[] = ['value' => $val, 'title' => $val, 'group' => ''];
        }

        return $this;
    }

    public function setOptionsResolver(callable $fn): static
    {
        $this->optionsResolver = $fn;
        return $this;
    }

    public function multiple(): bool
    {
        return $this->multiple;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function values(): array
    {
        if ($this->optionsResolver !== null) {
            $resolved = ($this->optionsResolver)();
            if (is_array($resolved)) {
                return $resolved;
            }
        }

        return $this->values;
    }

    public function processRequest(Request $request, ?object $model = null): array
    {
        $value = $request->request->get($this->fieldname, '');

        if ($value === '') {
            $value = null;
        } elseif ($this->multiple && !$this->isReadOnly()) {
            $value = implode(',', (array) $value);
        }

        $errors = $this->validate($value);

        if (empty($errors) && $model !== null) {
            $model->{$this->fieldname} = $value;
        }

        return ['success' => empty($errors), 'errors' => $errors, 'value' => $value];
    }

    public function schema(): array
    {
        return [
            'type'        => 'select',
            'field'       => $this->fieldname,
            'label'       => $this->label,
            'description' => $this->description,
            'required'    => $this->required,
            'readonly'    => $this->readonly,
            'cols'        => $this->cols,
            'multiple'    => $this->multiple,
            'source'      => $this->source,
            'fieldcode'   => $this->fieldcode,
            'fieldtitle'  => $this->fieldtitle,
            'parent'      => $this->parent,
            'values'      => $this->values(),
            'validations' => $this->validationRules,
        ];
    }

    protected function templateDir(): string
    {
        return 'select';
    }

    protected function inputHtml(): string
    {
        AssetManager::addCss(Tools::config('route') . '/node_modules/select2/dist/css/select2.min.css?v=5');
        AssetManager::addCss(Tools::config('route') . '/node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css?v=5');
        AssetManager::addJs(Tools::config('route') . '/node_modules/select2/dist/js/select2.min.js?v=5', 2);
        AssetManager::addJs(Tools::config('route') . '/Dinamic/Assets/JS/WidgetSelect.js?v=5');

        $class = $this->inputCssClass('form-select', 'select2');
        if ($this->parent) {
            $class .= ' parentSelect';
        }

        $hiddenInput = '';
        $nameAttr = '';
        if ($this->isReadOnly()) {
            $hiddenInput = '<input type="hidden" name="' . $this->fieldname . '" value="' . htmlspecialchars((string) ($this->value ?? '')) . '">';
        } else {
            $nameAttr = $this->multiple
                ? ' name="' . $this->fieldname . '[]"'
                : ' name="' . $this->fieldname . '"';
        }

        $html = $hiddenInput . '<select'
            . $nameAttr
            . ' class="' . $class . '"'
            . ' value="' . htmlspecialchars((string) ($this->value ?? '')) . '"'
            . ' data-field="' . $this->fieldname . '"'
            . ' data-source="' . $this->source . '"'
            . ' data-fieldcode="' . $this->fieldcode . '"'
            . ' data-fieldtitle="' . $this->fieldtitle . '"'
            . ' data-fieldfilter="' . $this->fieldfilter . '"'
            . ' parent="' . $this->parent . '"'
            . ($this->multiple ? ' multiple' : '')
            . ($this->isReadOnly() ? ' disabled' : '')
            . ($this->required ? ' required=""' : '')
            . '>';

        $allValues = $this->values();
        $found = false;

        foreach ($allValues as $option) {
            $optValue = $option['value'] ?? '';
            $optTitle = $option['title'] ?? $optValue;
            $group = $option['group'] ?? '';

            $selected = $this->valuesMatch($optValue, $this->value) && (!$found || $this->multiple);
            if ($selected) {
                $found = true;
            }

            $html .= '<option value="' . htmlspecialchars((string) $optValue) . '"'
                . ($selected ? ' selected' : '')
                . '>' . htmlspecialchars((string) $optTitle) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    protected function displayValue(): string
    {
        if ($this->value === null) {
            return '-';
        }

        foreach ($this->values() as $option) {
            if ($this->valuesMatch($option['value'] ?? '', $this->value)) {
                return (string) ($option['title'] ?? $this->value);
            }
        }

        return (string) $this->value;
    }

    private function applyTranslations(): void
    {
        foreach ($this->values as $key => $value) {
            if (!empty($value['title']) && $value['title'] !== '------') {
                $this->values[$key]['title'] = Tools::lang()->trans($value['title']);
            }
        }
    }

    private function valuesMatch(mixed $a, mixed $b): bool
    {
        if (is_bool($a)) {
            $a = $a ? '1' : '0';
        }
        if (is_bool($b)) {
            $b = $b ? '1' : '0';
        }

        return (string) $a === (string) $b;
    }
}
