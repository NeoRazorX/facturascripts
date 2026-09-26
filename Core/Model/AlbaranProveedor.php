<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Dinamic\Model\LineaAlbaranProveedor as LineaAlbaran;

/**
 * Albarán de proveedor. Representa la recepción del material que se ha comprado y, por
 * tanto, implica la entrada de ese material en el almacén. Se almacena en la tabla
 * `albaranesprov` y hereda de `PurchaseDocument` la cabecera común de los documentos de
 * compra: proveedor, almacén, serie, divisa, fechas y totales.
 *
 * Sus líneas son objetos `LineaAlbaranProveedor` relacionados por `idalbaran`. Al crear una
 * línea nueva se hereda de la cabecera el estado del documento (que determina si se
 * actualiza el stock), la excepción de IVA del proveedor y el IRPF, y se calculan los
 * importes con `Calculator`. Suele proceder de un pedido de compra y puede agruparse
 * después en una factura de proveedor.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AlbaranProveedor extends PurchaseDocument
{
    use ModelTrait;

    /** @var int Identificador único del albarán de proveedor. */
    public $idalbaran;

    /**
     * Returns the lines associated with the delivery note.
     *
     * @return LineaAlbaran[]
     */
    public function getLines(): array
    {
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];
        return LineaAlbaran::allWhereEq('idalbaran', $this->idalbaran, $order);
    }

    /**
     * Returns a new line for the document.
     *
     * @param array $data
     * @param array $exclude
     *
     * @return LineaAlbaran
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idalbaran', 'servido'])
    {
        $newLine = new LineaAlbaran();
        $newLine->actualizastock = $this->getStatus()->actualizastock;
        $newLine->excepcioniva = $this->getSubject()->excepcioniva;
        $newLine->idalbaran = $this->idalbaran;
        $newLine->irpf = $this->irpf;
        $newLine->loadFromData($data, $exclude);

        // si no viene de getNewProductLine(), calculamos la línea
        if (empty($data['referencia'] ?? '')) {
            Calculator::calculateLine($this, $newLine);
        }

        // allow extensions
        $this->pipe('getNewLine', $newLine, $data, $exclude);

        return $newLine;
    }

    public static function primaryColumn(): string
    {
        return 'idalbaran';
    }

    public static function tableName(): string
    {
        return 'albaranesprov';
    }
}
