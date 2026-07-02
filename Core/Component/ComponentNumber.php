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
use FacturaScripts\Core\Tools;

/**
 * Input numérico con precisión decimal, límites mínimo/máximo y paso configurables.
 *
 * Registra automáticamente la regla 'numeric' en addDefaultRules(). processRequest()
 * siempre convierte el valor a float, de modo que el modelo recibe un número en lugar
 * de una cadena sin procesar. El formateo decimal se delega a Tools::number().
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ComponentNumber extends FieldComponent
{
    protected int $decimal;
    protected string $max = '';
    protected string $min = '';
    protected bool $showTotals = false;
    protected string $step = 'any';
    protected string $cellAlign = 'end';

    public function __construct(string $fieldname)
    {
        parent::__construct($fieldname);
        $this->decimal = (int) FS_NF0;
    }

    public function setDecimals(int $decimal): static
    {
        $this->decimal = $decimal;
        return $this;
    }

    public function setMax(float|int|string $max): static
    {
        $this->max = (string) $max;
        return $this;
    }

    public function setMin(float|int|string $min): static
    {
        $this->min = (string) $min;
        return $this;
    }

    public function setStep(float|int|string $step): static
    {
        $this->step = (string) $step;
        return $this;
    }

    public function setShowTotals(bool $show = true): static
    {
        $this->showTotals = $show;
        return $this;
    }

    public function decimal(): int
    {
        return $this->decimal;
    }

    public function max(): string
    {
        return $this->max;
    }

    public function min(): string
    {
        return $this->min;
    }

    public function step(): string
    {
        return $this->step;
    }

    public function showTotals(): bool
    {
        return $this->showTotals;
    }

    public function processRequest(Request $request, ?object $model = null): array
    {
        $value = (float) $request->request->get($this->fieldname, 0);
        $errors = $this->validate($value);

        if (empty($errors) && $model !== null) {
            $model->{$this->fieldname} = $value;
        }

        return ['success' => empty($errors), 'errors' => $errors, 'value' => $value];
    }

    public function schema(): array
    {
        return [
            'type'        => 'number',
            'field'       => $this->fieldname,
            'label'       => $this->label,
            'description' => $this->description,
            'required'    => $this->required,
            'readonly'    => $this->readonly,
            'cols'        => $this->cols,
            'decimal'     => $this->decimal,
            'min'         => $this->min,
            'max'         => $this->max,
            'step'        => $this->step,
            'showTotals'  => $this->showTotals,
            'validations' => $this->validationRules,
        ];
    }

    protected function templateDir(): string
    {
        return 'number';
    }

    protected function addDefaultRules(): void
    {
        $this->addRule('numeric');
    }

    protected function inputHtml(): string
    {
        $class = $this->inputCssClass('form-control');
        $min = $this->min !== '' ? ' min="' . $this->min . '"' : '';
        $max = $this->max !== '' ? ' max="' . $this->max . '"' : '';

        return '<input type="number"'
            . ' name="' . $this->fieldname . '"'
            . ' value="' . ($this->value ?? '') . '"'
            . ' class="' . $class . '"'
            . ' step="' . $this->step . '"'
            . $min . $max
            . $this->inputExtraParams()
            . '/>';
    }

    protected function displayValue(): string
    {
        if ($this->value === null) {
            return '-';
        }

        return Tools::number((float) $this->value, $this->decimal);
    }
}
