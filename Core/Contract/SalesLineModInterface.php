<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;

/**
 * Define los puntos de extensión de las líneas del formulario de documentos de venta.
 */
interface SalesLineModInterface
{
    /**
     * Aplica el mod después de procesar las líneas recibidas desde el formulario.
     *
     * @param SalesDocumentLine[] $lines
     * @param array $formData Datos recibidos desde el formulario.
     */
    public function apply(SalesDocument &$model, array &$lines, array $formData): void;

    /**
     * Aplica los datos adicionales del mod a una línea recibida desde el formulario.
     * El identificador corresponde al ID de la línea o al identificador temporal de una línea nueva.
     *
     * @param array $formData Datos recibidos desde el formulario.
     */
    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id): void;

    /**
     * Registra los recursos CSS y JavaScript que necesita el mod.
     */
    public function assets(): void;

    /**
     * Intenta crear una línea para la entrada rápida cuando la búsqueda estándar no encuentra un producto.
     * Devuelve null cuando el mod no puede resolver la entrada.
     *
     * @param array $formData Datos recibidos desde el formulario.
     */
    public function getFastLine(SalesDocument $model, array $formData): ?SalesDocumentLine;

    /**
     * Devuelve valores adicionales de las líneas para actualizar el formulario mediante JavaScript.
     *
     * @param SalesDocumentLine[] $lines
     */
    public function map(array $lines, SalesDocument $model): array;

    /**
     * Devuelve los identificadores de las columnas adicionales de cada línea.
     *
     * @return string[]
     */
    public function newFields(): array;

    /**
     * Devuelve los identificadores de los campos adicionales del modal de cada línea.
     *
     * @return string[]
     */
    public function newModalFields(): array;

    /**
     * Devuelve los identificadores de las cabeceras adicionales de la lista de líneas.
     *
     * @return string[]
     */
    public function newTitles(): array;

    /**
     * Renderiza un campo estándar o adicional de una línea.
     * Devuelve null cuando el mod no gestiona el campo para permitir que otro mod lo renderice.
     */
    public function renderField(
        string $idlinea,
        SalesDocumentLine $line,
        SalesDocument $model,
        string $field
    ): ?string;

    /**
     * Renderiza una cabecera estándar o adicional de la lista de líneas.
     * Devuelve null cuando el mod no gestiona la cabecera para permitir que otro mod la renderice.
     */
    public function renderTitle(SalesDocument $model, string $field): ?string;
}
