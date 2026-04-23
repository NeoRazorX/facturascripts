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
 * Permite a un plugin añadir columnas, campos modales y lógica personalizada
 * a las líneas de documentos de venta (presupuestos, pedidos, albaranes y
 * facturas de cliente) sin modificar el código del core.
 *
 * Registro en Init.php del plugin:
 *   SalesLineHTML::addMod(new Mod\MiSalesLineMod());
 */
interface SalesLineModInterface
{
    /**
     * Aplica lógica sobre el conjunto completo de líneas del documento.
     * Útil cuando la operación requiere ver todas las líneas a la vez
     * (p. ej., recalcular comisiones globales o totales cruzados).
     *
     * @param SalesDocument $model    Documento de venta (por referencia).
     * @param array         $lines    Líneas del documento (por referencia).
     * @param array         $formData Datos del formulario HTML (equivalente a $_POST).
     */
    public function apply(SalesDocument &$model, array &$lines, array $formData): void;

    /**
     * Persiste en la línea el valor del campo personalizado recibido desde
     * el formulario. El valor se localiza en $formData con el patrón
     * $formData['micampo_' . $id].
     *
     * @param array             $formData Datos del formulario HTML.
     * @param SalesDocumentLine $line     Línea a modificar (por referencia).
     * @param string            $id       Identificador de la línea en el formulario.
     */
    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id): void;

    /**
     * Registra los archivos CSS y JS que necesita este mod mediante
     * AssetManager::addCss() y AssetManager::addJs().
     */
    public function assets(): void;

    /**
     * Crea y devuelve una línea nueva a partir de datos especiales del formulario
     * (p. ej., un código de barras o QR). Devuelve null si este mod no reconoce
     * los datos y debe ceder el turno al siguiente mod.
     *
     * @param SalesDocument $model    Documento de venta al que se añadirá la línea.
     * @param array         $formData Datos del formulario con la información de entrada rápida.
     *
     * @return SalesDocumentLine|null Nueva línea, o null si este mod no la gestiona.
     */
    public function getFastLine(SalesDocument $model, array $formData): ?SalesDocumentLine;

    /**
     * Devuelve un mapa campo => valor con los valores actualizados de las líneas
     * para refrescar el formulario vía AJAX sin recargar la página.
     * Las claves deben coincidir con los atributos id de los inputs HTML
     * (p. ej., ['porcomision_1' => 5.5]).
     *
     * @param array         $lines Líneas del documento.
     * @param SalesDocument $model Documento de venta al que pertenecen.
     *
     * @return array<string, mixed>
     */
    public function map(array $lines, SalesDocument $model): array;

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
     * @return array<string> P. ej., ['porcomision'].
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
     * @param string            $idlinea Identificador de la línea en el formulario HTML.
     * @param SalesDocumentLine $line    Línea con los datos actuales.
     * @param SalesDocument     $model   Documento al que pertenece la línea.
     * @param string            $field   Nombre del campo a renderizar.
     *
     * @return string|null HTML del campo, o null si este mod no lo gestiona.
     */
    public function renderField(string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string;

    /**
     * Devuelve el HTML de la cabecera de columna para el campo indicado,
     * o null si este mod no lo gestiona.
     *
     * @param SalesDocument $model Documento de venta actual.
     * @param string        $field Nombre del campo cuyo título se quiere renderizar.
     *
     * @return string|null HTML del título, o null si este mod no lo gestiona.
     */
    public function renderTitle(SalesDocument $model, string $field): ?string;
}
