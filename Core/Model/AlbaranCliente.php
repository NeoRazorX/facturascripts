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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Dinamic\Model\LineaAlbaranCliente as LineaAlbaran;

/**
 * Albarán de cliente. Representa la entrega al cliente del material que se le ha vendido
 * y, por tanto, implica la salida de ese material del almacén de la empresa. Se almacena
 * en la tabla `albaranescli` y hereda de `SalesDocument` toda la cabecera común de los
 * documentos de venta: cliente, almacén, serie, divisa, fechas y totales.
 *
 * Sus líneas son objetos `LineaAlbaranCliente` relacionados por `idalbaran`. Al crear una
 * línea nueva se hereda de la cabecera el estado del documento (que determina si se
 * actualiza el stock), la excepción de IVA del cliente y el IRPF, y se calculan los
 * importes con `Calculator`. Normalmente procede de un pedido y puede agruparse
 * posteriormente en una factura de cliente.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AlbaranCliente extends SalesDocument
{
    use ModelTrait;

    /** @var int Identificador único del albarán de cliente. */
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
        return 'albaranescli';
    }
}
