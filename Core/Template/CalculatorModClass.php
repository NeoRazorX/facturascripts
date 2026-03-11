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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;

abstract class CalculatorModClass
{
    const CONTINUE = 'continue';
    const STOP_ALL = 'stop-all';
    const STOP_MODS = 'stop-mods';

    protected function done(): string
    {
        return self::CONTINUE;
    }

    protected function stopAll(): string
    {
        return self::STOP_ALL;
    }

    protected function stopMods(): string
    {
        return self::STOP_MODS;
    }

    public function accumulateSubtotals(array &$subtotals, BusinessDocument $doc, array &$lines): string
    {
        return $this->done();
    }

    public function apply(BusinessDocument $doc, array &$lines): string
    {
        return $this->done();
    }

    public function calculate(BusinessDocument $doc, array &$lines): string
    {
        return $this->done();
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine $line): string
    {
        return $this->done();
    }

    public function clear(BusinessDocument $doc, array &$lines): string
    {
        return $this->done();
    }

    public function save(BusinessDocument $doc, array &$lines): string
    {
        return $this->done();
    }

    public function updateSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): string
    {
        return $this->done();
    }
}
