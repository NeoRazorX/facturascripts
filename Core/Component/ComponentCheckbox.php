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

/**
 * Campo booleano renderizado como checkbox estándar de Bootstrap.
 *
 * Los checkboxes HTML no se envían cuando están desmarcados, por lo que este
 * componente interpreta la ausencia de la clave en el POST como false y su
 * presencia con value="TRUE" como true, igual que WidgetCheckbox. En modo solo
 * lectura el valor se preserva mediante un input oculto. renderEdit() está
 * completamente sobreescrito — inputHtml() no se utiliza.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentCheckbox extends FieldComponent
{
    public function processRequest(Request $request, ?object $model = null): array
    {
        if ($this->isReadOnly()) {
            // input oculto lleva 'TRUE' o 'FALSE'
            $raw = $request->request->get($this->fieldname);
            $value = ($raw === 'TRUE');
        } else {
            // checkbox: presente en POST con value="TRUE" → true, ausente → false
            $value = $request->request->get($this->fieldname) === 'TRUE';
        }

        if ($model !== null) {
            $model->{$this->fieldname} = $value;
        }

        return ['success' => true, 'errors' => [], 'value' => $value];
    }

    public function schema(): array
    {
        return [
            'type'     => 'checkbox',
            'field'    => $this->fieldname,
            'label'    => $this->label,
            'required' => $this->required,
            'readonly' => $this->readonly,
            'cols'     => $this->cols,
        ];
    }

    public function renderEdit(mixed $value = null): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        $id       = 'checkbox_' . $this->fieldname;
        $checked  = $this->value ? ' checked=""' : '';
        $readonly = $this->isReadOnly() ? ' onclick="return false;"' : '';
        $tabindex = $this->tabindex >= 0 ? ' tabindex="' . $this->tabindex . '"' : '';
        $class    = $this->inputCssClass('form-check-input');

        $hidden = $this->isReadOnly()
            ? '<input type="hidden" name="' . $this->fieldname . '" value="' . ($this->value ? 'TRUE' : 'FALSE') . '">'
            : '';

        $desc = $this->description
            ? '<div class="form-text text-muted">' . htmlspecialchars($this->description) . '</div>'
            : '';

        return '<div class="form-check pe-3 mb-3">'
            . $hidden
            . '<input type="checkbox" name="' . $this->fieldname . '" value="TRUE"'
            . ' id="' . $id . '" class="' . $class . '"'
            . $checked . $readonly . $tabindex . '/>'
            . '<label for="' . $id . '">' . htmlspecialchars($this->label) . '</label>'
            . $desc
            . '</div>';
    }

    public function renderCell(mixed $value = null, string $align = 'center'): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        $icon = $this->value
            ? '<i class="fa-solid fa-check text-success"></i>'
            : '<i class="fa-solid fa-xmark text-muted"></i>';

        return '<td class="text-' . $align . '">' . $icon . '</td>';
    }

    public function renderReadOnly(mixed $value = null): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        $icon = $this->value
            ? '<i class="fa-solid fa-check text-success"></i>'
            : '<i class="fa-solid fa-xmark text-muted"></i>';

        return '<div class="mb-3">'
            . '<label class="mb-0">' . htmlspecialchars($this->label) . '</label>'
            . '<p class="form-control-plaintext py-0">' . $icon . '</p>'
            . '</div>';
    }

    public function renderHidden(): string
    {
        return '<input type="hidden" name="' . $this->fieldname . '" value="' . ($this->value ? 'TRUE' : 'FALSE') . '">';
    }

    public function colClass(): string
    {
        return $this->cols <= 0 ? 'col-sm-auto' : parent::colClass();
    }

    protected function templateDir(): string
    {
        return 'checkbox';
    }

    protected function inputHtml(): string
    {
        return ''; // renderEdit is fully overridden
    }

    protected function displayValue(): string
    {
        return $this->value ? '✓' : '✗';
    }
}
