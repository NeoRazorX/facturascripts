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

use FacturaScripts\Core\Lib\UI\UIField;
use FacturaScripts\Core\Tools;

/**
 * Campo numérico (<input type="number">). El valor hidratado es float, int o null.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NumberField extends UIField
{
    protected int $decimals = 2;
    protected ?float $min = null;
    protected ?float $max = null;
    protected ?float $step = null;

    protected function defaultTemplate(): string
    {
        return 'UI/Field/Number.html.twig';
    }

    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;
        return $this;
    }

    public function min(float $min): static
    {
        $this->min = $min;
        $this->rule('min_val:' . $min);
        return $this;
    }

    public function max(float $max): static
    {
        $this->max = $max;
        $this->rule('max_val:' . $max);
        return $this;
    }

    public function step(float $step): static
    {
        $this->step = $step;
        return $this;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    /** Atributo step= del input: explícito, o derivado de los decimales. */
    public function stepAttr(): string
    {
        if ($this->step !== null) {
            return (string)$this->step;
        }
        return $this->decimals > 0 ? '0.' . str_repeat('0', $this->decimals - 1) . '1' : '1';
    }

    public function minAttr(): string
    {
        return $this->min === null ? '' : (string)$this->min;
    }

    public function maxAttr(): string
    {
        return $this->max === null ? '' : (string)$this->max;
    }

    protected function castFromRequest(mixed $raw): mixed
    {
        if ($raw === null || $raw === '' || !is_numeric($raw)) {
            return null;
        }
        return $this->decimals > 0 ? round((float)$raw, $this->decimals) : (int)$raw;
    }

    public function displayValue(): string
    {
        if ($this->value === null) {
            return '-';
        }
        return Tools::number($this->value, $this->decimals);
    }
}
