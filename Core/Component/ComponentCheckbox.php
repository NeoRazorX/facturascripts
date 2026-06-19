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
 * Interruptor booleano renderizado como un form-switch de Bootstrap.
 *
 * Los checkboxes HTML no se envían cuando están desmarcados, por lo que este
 * componente interpreta la ausencia de la clave en el POST como false y su
 * presencia (con cualquier valor) como true. En modo solo lectura el valor se
 * preserva mediante un input oculto para que sobreviva el POST. renderEdit()
 * está completamente sobreescrito — inputHtml() no se utiliza.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentCheckbox extends BaseComponent
{
    public function processRequest(Request $request, ?object $model = null): array
    {
        if ($this->isReadOnly()) {
            // hidden input carries '1' or '0'
            $raw = $request->request->get($this->fieldname);
            $value = $raw !== null ? (bool)(int)$raw : (bool)$this->value;
        } else {
            // checkbox: present in POST → true, absent → false
            $value = $request->request->get($this->fieldname) !== null;
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

        $readonly = $this->isReadOnly() ? ' disabled' : '';
        $checked  = $this->value ? ' checked' : '';

        // cuando está deshabilitado el navegador no envía el valor → se preserva con un input oculto
        $hidden = $this->isReadOnly()
            ? '<input type="hidden" name="' . $this->fieldname . '" value="' . ($this->value ? '1' : '0') . '">'
            : '';

        $desc = $this->description
            ? '<small class="form-text text-muted">' . htmlspecialchars($this->description) . '</small>'
            : '';

        return '<div class="mb-3">'
            . $hidden
            . '<div class="form-check form-switch">'
            . '<input type="checkbox" class="form-check-input" id="chk_' . $this->fieldname . '"'
            . ' name="' . $this->fieldname . '" value="1"'
            . $checked . $readonly . '>'
            . '<label class="form-check-label" for="chk_' . $this->fieldname . '">'
            . htmlspecialchars($this->label)
            . '</label>'
            . '</div>'
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
            . '<label class="mb-0 small fw-semibold">' . htmlspecialchars($this->label) . '</label>'
            . '<p class="form-control-plaintext py-0">' . $icon . '</p>'
            . '</div>';
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
