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

namespace FacturaScripts\Core\Contract;

use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Partida;

interface AccountingLineModInterface
{
    /**
     * Aplica el mod después de procesar las partidas recibidas desde el formulario.
     * Puede modificar el asiento y sus partidas; los totales se recalculan después de ejecutar todos los mods.
     *
     * @param Partida[] $lines
     * @param array $formData Datos recibidos desde el formulario.
     */
    public function apply(Asiento &$model, array &$lines, array $formData): void;

    /**
     * Aplica los datos adicionales del mod a una partida recibida desde el formulario.
     * El identificador corresponde al ID de la partida o al identificador temporal de una partida nueva.
     *
     * @param array $formData Datos recibidos desde el formulario.
     */
    public function applyToLine(array $formData, Partida &$line, string $id): void;

    /**
     * Registra los recursos CSS y JavaScript que necesita el mod.
     */
    public function assets(): void;

    /**
     * Devuelve los identificadores de las columnas adicionales de cada partida.
     * Cada identificador se enviará posteriormente a renderField().
     *
     * @return string[]
     */
    public function newFields(): array;

    /**
     * Devuelve los identificadores de los campos adicionales del modal de cada partida.
     * Cada identificador se enviará posteriormente a renderField().
     *
     * @return string[]
     */
    public function newModalFields(): array;

    /**
     * Renderiza un campo estándar o adicional de una partida.
     * Devuelve null cuando el mod no gestiona el campo para permitir que otro mod lo renderice.
     */
    public function renderField(string $idlinea, Partida $line, Asiento $model, string $field): ?string;
}
