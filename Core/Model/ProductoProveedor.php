<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\CostPriceTools;
use FacturaScripts\Dinamic\Model\Divisa as DinDivisa;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;
use FacturaScripts\Dinamic\Model\Proveedor as DinProveedor;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * Description of ProductoProveedor
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProductoProveedor extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;
    use Base\ProductRelationTrait;

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
    public $neto;

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
        $this->neto = 0.0;
        $this->precio = 0.0;
    }

    /**
     * Returns the Equivalent Unified Discount.
     * 
     * @return float
     */
    public function getEUDiscount()
    {
        $eud = 1.0;
        foreach ([$this->dtopor, $this->dtopor2] as $dto) {
            $eud *= 1 - $dto / 100;
        }

        return $eud;
    }

    /**
     * 
     * @return DinVariante
     */
    public function getVariant()
    {
        $variant = new DinVariante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        $variant->loadFromCode('', $where);
        return $variant;
    }

    /**
     * 
     * @return DinProveedor
     */
    public function getSupplier()
    {
        $supplier = new DinProveedor();
        $supplier->loadFromCode($this->codproveedor);
        return $supplier;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new DinDivisa();
        new DinProveedor();

        return parent::install();
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
        if (empty($this->referencia)) {
            $this->toolBox()->i18nLog()->warning('field-can-not-be-null', ['%fieldName%' => 'referencia', '%tableName%' => static::tableName()]);
            return false;
        } elseif (empty($this->refproveedor)) {
            $this->refproveedor = $this->referencia;
        }

        if (empty($this->idproducto)) {
            $this->idproducto = $this->getProducto()->idproducto;
        }

        $this->neto = \round($this->precio * $this->getEUDiscount(), DinProducto::ROUND_DECIMALS);
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
        return $this->getVariant()->url($type);
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'neto':
                CostPriceTools::update($this->getVariant());
                return true;

            default:
                return parent::onChange($field);
        }
    }

    protected function onInsert()
    {
        CostPriceTools::update($this->getVariant());
        parent::onInsert();
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        parent::setPreviousData(\array_merge(['neto'], $fields));
    }
}
