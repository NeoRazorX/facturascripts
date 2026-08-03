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

use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Dinamic\Model\Proveedor;

interface PurchasesModalInterface
{
    public function apply(PurchaseDocument &$model, array $formData): void;

    public function applyBefore(PurchaseDocument &$model, array $formData): void;

    public function assets(): void;

    /**
     * Devuelve los nombres de las columnas a añadir al modal de búsqueda de producto.
     *
     * @return string[]
     */
    public function newProductFields(): array;

    /**
     * Devuelve los nombres de las columnas a añadir al modal de selección de proveedor.
     *
     * @return string[]
     */
    public function newSupplierFields(): array;

    /**
     * Renderiza la celda de una columna añadida por el mod.
     * - En el modal de proveedores $row es un objeto Proveedor.
     * - En el modal de productos $row es un array con los campos de variantes y productos.
     * - Debe devolver el HTML completo de la celda, incluyendo las etiquetas <td>.
     * - Debe devolver null si el campo no pertenece a este mod.
     *
     * @param array|Proveedor $row
     * @param string $field
     * @return string|null
     */
    public function renderField($row, string $field): ?string;

    /**
     * Renderiza la cabecera de una columna añadida por el mod al modal de productos.
     * - Debe devolver el HTML completo de la cabecera, incluyendo las etiquetas <th>.
     * - Debe devolver null si el campo no pertenece a este mod.
     *
     * @param string $field
     * @return string|null
     */
    public function renderFieldHead(string $field): ?string;
}
