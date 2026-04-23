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

use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\PurchaseDocument;

/**
 * Permite a un plugin añadir columnas, campos modales y lógica personalizada
 * a las líneas de documentos de compra (presupuestos, pedidos, albaranes y
 * facturas de proveedor) sin modificar el código del core.
 *
 * Equivalente a SalesLineModInterface pero para el flujo de compras. Usa
 * BusinessDocumentLine porque las líneas de compra no tienen subclase propia.
 *
 * Registro en Init.php del plugin:
 *   PurchasesLineHTML::addMod(new Mod\MiPurchasesLineMod());
 */
interface PurchasesLineModInterface
{
    /**
     * Aplica lógica sobre el conjunto completo de líneas del documento.
     * Útil cuando la operación requiere ver todas las líneas a la vez
     * (p. ej., recalcular totales globales o aplicar descuentos encadenados).
     *
     * @param PurchaseDocument $model    Documento de compra (por referencia).
     * @param array            $lines    Líneas del documento (por referencia).
     * @param array            $formData Datos del formulario HTML (equivalente a $_POST).
     */
    public function apply(PurchaseDocument &$model, array &$lines, array $formData): void;

    /**
     * Persiste en la línea el valor del campo personalizado recibido desde
     * el formulario. El valor se localiza en $formData con el patrón
     * $formData['micampo_' . $id].
     *
     * @param array                $formData Datos del formulario HTML.
     * @param BusinessDocumentLine $line     Línea a modificar (por referencia).
     * @param string               $id       Identificador de la línea en el formulario.
     */
    public function applyToLine(array $formData, BusinessDocumentLine &$line, string $id): void;

    /**
     * Registra los archivos CSS y JS que necesita este mod mediante
     * AssetManager::addCss() y AssetManager::addJs().
     */
    public function assets(): void;

    /**
     * Crea y devuelve una línea nueva a partir de datos especiales del formulario
     * (p. ej., un código de barras o referencia de proveedor). Devuelve null si
     * este mod no reconoce los datos y debe ceder el turno al siguiente mod.
     *
     * @param PurchaseDocument $model    Documento de compra al que se añadirá la línea.
     * @param array            $formData Datos del formulario con la información de entrada rápida.
     *
     * @return BusinessDocumentLine|null Nueva línea, o null si este mod no la gestiona.
     */
    public function getFastLine(PurchaseDocument $model, array $formData): ?BusinessDocumentLine;

    /**
     * Devuelve un mapa campo => valor con los valores actualizados de las líneas
     * para refrescar el formulario vía AJAX sin recargar la página.
     * Las claves deben coincidir con los atributos id de los inputs HTML
     * (p. ej., ['costeproveedor_1' => 12.5]).
     *
     * @param array            $lines Líneas del documento.
     * @param PurchaseDocument $model Documento de compra al que pertenecen.
     *
     * @return array<string, mixed>
     */
    public function map(array $lines, PurchaseDocument $model): array;

    /**
     * Nombres de los campos adicionales que deben aparecer como columnas
     * visibles en la tabla de líneas.
     *
     * @return array<string> P. ej., ['unidades'].
     */
    public function newFields(): array;

    /**
     * Nombres de los campos adicionales que deben aparecer en el modal
     * de edición avanzada de cada línea.
     *
     * @return array<string>
     */
    public function newModalFields(): array;

    /**
     * Nombres de los campos para los que este mod renderiza el título
     * de columna mediante renderTitle().
     *
     * @return array<string>
     */
    public function newTitles(): array;

    /**
     * Devuelve el HTML del campo indicado para la línea dada, o null si
     * este mod no lo gestiona.
     *
     * @param string               $idlinea Identificador de la línea en el formulario HTML.
     * @param BusinessDocumentLine $line    Línea con los datos actuales.
     * @param PurchaseDocument     $model   Documento al que pertenece la línea.
     * @param string               $field   Nombre del campo a renderizar.
     *
     * @return string|null HTML del campo, o null si este mod no lo gestiona.
     */
    public function renderField(string $idlinea, BusinessDocumentLine $line, PurchaseDocument $model, string $field): ?string;

    /**
     * Devuelve el HTML de la cabecera de columna para el campo indicado,
     * o null si este mod no lo gestiona.
     *
     * @param PurchaseDocument $model Documento de compra actual.
     * @param string           $field Nombre del campo cuyo título se quiere renderizar.
     *
     * @return string|null HTML del título, o null si este mod no lo gestiona.
     */
    public function renderTitle(PurchaseDocument $model, string $field): ?string;
}
