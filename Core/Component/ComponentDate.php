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

use FacturaScripts\Core\Tools;

/**
 * Input de fecha (y opcionalmente fecha+hora) nativo del navegador.
 *
 * Genera <input type="date"> por defecto. Con setDatetime(true) genera
 * <input type="datetime-local">. El valor se formatea automáticamente al
 * formato que requiere cada tipo (Y-m-d / Y-m-d\TH:i).
 *
 * Para campos de solo lectura, displayValue() usa Tools::date() y
 * Tools::dateTime() para mostrar la fecha en el formato local configurado.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentDate extends FieldComponent
{
    private bool $isDatetime = false;

    public function setDatetime(bool $datetime = true): static
    {
        $this->isDatetime = $datetime;
        return $this;
    }

    public function schema(): array
    {
        return [
            'type'     => $this->isDatetime ? 'datetime' : 'date',
            'field'    => $this->fieldname,
            'label'    => $this->label,
            'required' => $this->required,
            'readonly' => $this->readonly,
            'cols'     => $this->cols,
        ];
    }

    protected function templateDir(): string
    {
        return 'date';
    }

    protected function inputHtml(): string
    {
        $type = $this->isDatetime ? 'datetime-local' : 'date';

        return '<input type="' . $type . '"'
            . ' name="' . $this->fieldname . '"'
            . ' value="' . htmlspecialchars($this->formatValueForInput()) . '"'
            . ' class="' . $this->inputCssClass('form-control') . '"'
            . $this->inputExtraParams()
            . '/>';
    }

    protected function displayValue(): string
    {
        if ($this->value === null || $this->value === '') {
            return '-';
        }

        return $this->isDatetime
            ? Tools::dateTime((string) $this->value)
            : Tools::date((string) $this->value);
    }

    private function formatValueForInput(): string
    {
        if (empty($this->value)) {
            return '';
        }

        $ts = strtotime((string) $this->value);
        if ($ts === false) {
            return (string) $this->value;
        }

        return $this->isDatetime
            ? date('Y-m-d\TH:i', $ts)
            : date('Y-m-d', $ts);
    }
}
