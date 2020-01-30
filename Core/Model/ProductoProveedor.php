<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of ProductoProveedor
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProductoProveedor extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $actualizado;

    /**
     *
     * @var string
     */
    public $coddivisa;

    /**
     *
     * @var string
     */
    public $codproveedor;

    /**
     *
     * @var float
     */
    public $dtopor;

    /**
     *
     * @var float
     */
    public $dtopor2;

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var float
     */
    public $precio;

    /**
     *
     * @var string
     */
    public $referencia;

    /**
     *
     * @var string
     */
    public $refproveedor;

    public function clear()
    {
        parent::clear();
        $this->actualizado = \date(self::DATETIME_STYLE);
        $this->dtopor = 0.0;
        $this->dtopor2 = 0.0;
        $this->precio = 0.0;
    }

    /**
     * 
     * @return Variante
     */
    public function getVariant()
    {
        $variant = new Variante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        $variant->loadFromCode('', $where);
        return $variant;
    }

    /**
     * 
     * @return Proveedor
     */
    public function getSupplier()
    {
        $supplier = new Proveedor();
        $supplier->loadFromCode($this->codproveedor);
        return $supplier;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'productosprov';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        if (empty($this->refproveedor) && !empty($this->referencia)) {
            $this->refproveedor = $this->referencia;
        }

        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $this->getVariant()->url();
    }
}
