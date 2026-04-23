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

/**
 * Permite a un plugin añadir campos y lógica personalizada a la cabecera
 * de los documentos de venta (presupuestos, pedidos, albaranes y facturas
 * de cliente) sin modificar el código del core.
 *
 * Registro en Init.php del plugin:
 *   SalesHeaderHTML::addMod(new Mod\MiSalesMod());
 */
interface SalesModInterface
{
    /**
     * Persiste en el modelo los valores de los campos personalizados
     * recibidos desde el formulario, una vez que el core ya ha asignado
     * los campos estándar.
     *
     * @param SalesDocument $model    Documento de venta a modificar (por referencia).
     * @param array         $formData Datos del formulario HTML (equivalente a $_POST).
     */
    public function apply(SalesDocument &$model, array $formData): void;

    /**
     * Ejecuta lógica antes de que el core asigne los valores estándar del
     * formulario al modelo. Útil cuando la lógica depende del estado previo
     * del documento (p. ej., detectar un cambio de cliente o de serie).
     *
     * @param SalesDocument $model    Documento de venta a modificar (por referencia).
     * @param array         $formData Datos del formulario HTML (equivalente a $_POST).
     */
    public function applyBefore(SalesDocument &$model, array $formData): void;

    /**
     * Registra los archivos CSS y JS que necesita este mod mediante
     * AssetManager::addCss() y AssetManager::addJs().
     */
    public function assets(): void;

    /**
     * Nombres de los campos adicionales que deben aparecer en la barra
     * de botones secundaria de la cabecera del documento.
     *
     * @return array<string> P. ej., ['campo1', 'campo2'].
     */
    public function newBtnFields(): array;

    /**
     * Nombres de los campos adicionales que deben aparecer en la sección
     * principal de la cabecera del documento.
     *
     * @return array<string> P. ej., ['portes', 'observaciones2'].
     */
    public function newFields(): array;

    /**
     * Nombres de los campos adicionales que deben aparecer en el modal
     * de edición avanzada de la cabecera del documento.
     *
     * @return array<string>
     */
    public function newModalFields(): array;

    /**
     * Devuelve el HTML del campo indicado, o null si este mod no lo gestiona.
     *
     * @param SalesDocument $model Documento de venta con los datos actuales.
     * @param string        $field Nombre del campo a renderizar.
     *
     * @return string|null HTML del campo, o null si este mod no lo gestiona.
     */
    public function renderField(SalesDocument $model, string $field): ?string;
}
