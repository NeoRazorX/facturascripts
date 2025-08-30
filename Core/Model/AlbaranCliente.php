<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Dinamic\Model\LineaAlbaranCliente as LineaAlbaran;

/**
 * Customer's delivery note or delivery note. Represents delivery to a customer
 * of a material that has been sold to you. It implies the exit of this material
 * from the company's warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AlbaranCliente extends SalesDocument
{
    use ModelTrait;

    /**
     * Primary key. Integer.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * Returns the lines associated with the delivery note.
     *
     * @return LineaAlbaran[]
     */
    public function getLines(): array
    {
        $where = [new DataBaseWhere('idalbaran', $this->idalbaran)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];
        return LineaAlbaran::all($where, $order, 0, 0);
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
        $newLine->idalbaran = $this->idalbaran;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = $this->getStatus()->actualizastock;
        $newLine->loadFromData($data, $exclude);

        Calculator::calculateLine($this, $newLine);

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
