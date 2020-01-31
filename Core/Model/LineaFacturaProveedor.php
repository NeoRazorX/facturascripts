<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
class LineaFacturaProveedor extends Base\PurchaseDocumentLine
{

    use Base\ModelTrait;

    /**
     * Invoice ID of this line.
     *
     * @var int
     */
    public $idfactura;

    /**
     * 
     * @return string
     */
    public function documentColumn()
    {
        return 'idfactura';
    }

    /**
     * 
     * @return FacturaProveedor
     */
    public function getDocument()
    {
        $factura = new FacturaProveedor();
        $factura->loadFromCode($this->idfactura);
        return $factura;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependency
        new FacturaProveedor();

        return parent::install();
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasfacturasprov';
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        if (null !== $this->idfactura) {
            return 'EditFacturaProveedor?code=' . $this->idfactura;
        }

        return parent::url($type, $list);
    }
}
