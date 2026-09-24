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

/**
 * Input de texto de una sola línea con icono, placeholder y longitud máxima opcionales.
 *
 * Renderiza un <input type="text"> envuelto en un input-group cuando se define un icono.
 * Los errores de validación en línea se muestran mediante la clase CSS is-invalid y un
 * div invalid-feedback inyectado por inputCssClass() y renderInlineErrors().
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentText extends FieldComponent
{
    protected int $maxlength = 0;
    protected string $placeholder = '';

    public function setMaxLength(int $max): static
    {
        $this->maxlength = $max;
        return $this;
    }

    public function setPlaceholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function maxlength(): int
    {
        return $this->maxlength;
    }

    public function placeholder(): string
    {
        return $this->placeholder;
    }

    public function schema(): array
    {
        return [
            'type'        => 'text',
            'field'       => $this->fieldname,
            'label'       => $this->label,
            'description' => $this->description,
            'required'    => $this->required,
            'readonly'    => $this->readonly,
            'cols'        => $this->cols,
            'maxlength'   => $this->maxlength,
            'placeholder' => $this->placeholder,
            'icon'        => $this->icon,
            'validations' => $this->validationRules,
        ];
    }

    protected function templateDir(): string
    {
        return 'text';
    }

    protected function inputHtml(): string
    {
        $class = $this->inputCssClass('form-control');
        $maxlength = $this->maxlength > 0 ? ' maxlength="' . $this->maxlength . '"' : '';
        $placeholder = $this->placeholder
            ? ' placeholder="' . htmlspecialchars($this->placeholder) . '"'
            : '';

        return '<input type="text"'
            . ' name="' . $this->fieldname . '"'
            . ' value="' . htmlspecialchars((string) ($this->value ?? '')) . '"'
            . ' class="' . $class . '"'
            . $maxlength
            . $placeholder
            . $this->inputExtraParams()
            . '/>';
    }
}
