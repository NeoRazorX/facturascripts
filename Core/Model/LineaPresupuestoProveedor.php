<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2020  Carlos Garcia Gomez       <carlos@facturascripts.com>
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   <shawe.ewahs@gmail.com>
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
 * Supplier order line.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaPresupuestoProveedor extends Base\PurchaseDocumentLine
{

    use Base\ModelTrait;

    /**
     * Order ID.
     *
     * @var integer
     */
    public $idpresupuesto;

    /**
     * 
     * @return string
     */
    public function documentColumn()
    {
        return 'idpresupuesto';
    }

    /**
     * 
     * @return PresupuestoProveedor
     */
    public function getDocument()
    {
        $presupuesto = new PresupuestoProveedor();
        $presupuesto->loadFromCode($this->idpresupuesto);
        return $presupuesto;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependency
        new PresupuestoProveedor();

        return parent::install();
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineaspresupuestosprov';
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
        if (null !== $this->idpresupuesto) {
            return 'EditPresupuestoProveedor?code=' . $this->idpresupuesto;
        }

        return parent::url($type, $list);
    }
}
