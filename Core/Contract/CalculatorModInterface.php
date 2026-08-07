<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Contract;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Template\CalculatorModClass;

/**
 * Contrato heredado para los mods de cálculo de documentos comerciales.
 *
 * Para migrar, extienda CalculatorModClass y devuelva done(), stopMods() o stopAll() según deba continuar,
 * detener los demás mods de la fase actual o cancelar completamente el cálculo.
 * Un resultado false detiene los mods restantes de la fase actual, pero no cancela todo el cálculo.
 *
 * @deprecated since version 2026. Extienda CalculatorModClass en su lugar.
 * @see CalculatorModClass
 */
interface CalculatorModInterface
{
    /**
     * Ajusta el documento y sus líneas después de aplicar las reglas internas previas al cálculo.
     *
     * @param BusinessDocumentLine[] $lines
     */
    public function apply(BusinessDocument &$doc, array &$lines): bool;

    /**
     * Ajusta los totales del documento después de calcular y asignar sus subtotales.
     *
     * @param BusinessDocumentLine[] $lines
     */
    public function calculate(BusinessDocument &$doc, array &$lines): bool;

    /**
     * Ajusta los importes calculados de una línea después del cálculo interno.
     */
    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool;

    /**
     * Reinicia los valores adicionales del documento y sus líneas antes de iniciar el cálculo.
     *
     * @param BusinessDocumentLine[] $lines
     */
    public function clear(BusinessDocument &$doc, array &$lines): bool;

    /**
     * Ajusta los subtotales acumulados antes de asignarlos al documento.
     *
     * @param array $subtotals Subtotales calculados, indexados por su nombre.
     * @param BusinessDocumentLine[] $lines
     */
    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool;
}
