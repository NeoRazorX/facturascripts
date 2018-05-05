<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Line of a supplier invoice.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaFacturaProveedor extends Base\BusinessDocumentLine
{

    use Base\ModelTrait;

    /**
     * Delivery note line ID, if any.
     *
     * @var int
     */
    public $idlineaalbaran;

    /**
     * Invoice ID of this line.
     *
     * @var int
     */
    public $idfactura;

    /**
     * Delivery note ID related to the invoice, if any.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasfacturasprov';
    }
}
