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

use FacturaScripts\Dinamic\Model\Asiento;

interface AccountingModInterface
{
    /**
     * Ejecuta la fase posterior del mod, una vez procesados los campos estándar cuando corresponda.
     *
     * @param array $formData Datos recibidos desde el formulario.
     */
    public function apply(Asiento &$model, array $formData): void;

    /**
     * Ejecuta la fase previa del mod antes de procesar los campos estándar del formulario.
     *
     * @param array $formData Datos recibidos desde el formulario.
     */
    public function applyBefore(Asiento &$model, array $formData): void;

    /**
     * Registra los recursos CSS y JavaScript que necesita el mod.
     */
    public function assets(): void;

    /**
     * Devuelve los identificadores de los botones adicionales de la cabecera o el pie.
     * Cada identificador se enviará posteriormente a renderField().
     *
     * @return string[]
     */
    public function newBtnFields(): array;

    /**
     * Devuelve los identificadores de los campos adicionales de la cabecera o el pie.
     * Cada identificador se enviará posteriormente a renderField().
     *
     * @return string[]
     */
    public function newFields(): array;

    /**
     * Renderiza un campo estándar o adicional del formulario.
     * Devuelve null cuando el mod no gestiona el campo para permitir que otro mod lo renderice.
     */
    public function renderField(Asiento $model, string $field): ?string;
}
