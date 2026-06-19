<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
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

/**
 * Área de texto multilínea.
 *
 * Los valores mostrados en renderCell y renderReadOnly se truncan a 80 caracteres
 * con puntos suspensivos para mantener las tablas legibles. El valor completo
 * siempre se escribe en el modelo al guardar.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentTextarea extends BaseComponent
{
    protected int $rows = 3;

    public function setRows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    public function rows(): int
    {
        return $this->rows;
    }

    public function schema(): array
    {
        return [
            'type'        => 'textarea',
            'field'       => $this->fieldname,
            'label'       => $this->label,
            'description' => $this->description,
            'required'    => $this->required,
            'readonly'    => $this->readonly,
            'cols'        => $this->cols,
            'rows'        => $this->rows,
            'icon'        => $this->icon,
            'validations' => $this->validationRules,
        ];
    }

    protected function templateDir(): string
    {
        return 'textarea';
    }

    protected function inputHtml(): string
    {
        $class = $this->inputCssClass('form-control');

        return '<textarea'
            . ' name="' . $this->fieldname . '"'
            . ' class="' . $class . '"'
            . ' rows="' . $this->rows . '"'
            . $this->inputExtraParams()
            . '>'
            . htmlspecialchars((string) ($this->value ?? ''))
            . '</textarea>';
    }

    protected function displayValue(): string
    {
        if ($this->value === null) {
            return '-';
        }

        $text = (string) $this->value;
        if (mb_strlen($text) > 80) {
            return mb_substr($text, 0, 80) . '…';
        }

        return $text;
    }
}
