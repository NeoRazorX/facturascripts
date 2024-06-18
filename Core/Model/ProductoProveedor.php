<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Model\Base\ModelOnChangeClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Model\Base\ProductRelationTrait;
use FacturaScripts\Core\Tools;
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
class ProductoProveedor extends ModelOnChangeClass
{
    use ModelTrait;
    use ProductRelationTrait;

    /** @var string */
    public $actualizado;

    /** @var string */
    public $coddivisa;

    /** @var string */
    public $codproveedor;

    /** @var float */
    public $dtopor;

    /** @var float */
    public $dtopor2;

    /** @var int */
    public $id;

    /** @var float */
    public $neto;

    /** @var float */
    public $netoeuros;

    /** @var float */
    public $precio;

    /** @var string */
    public $referencia;

    /** @var string */
    public $refproveedor;

    /** @var float */
    public $stock;

    public function __get($name)
    {
        if ($name == 'descripcion') {
            return $this->getVariant()->getProducto()->descripcion;
        }
    }

    public function clear()
    {
        parent::clear();
        $this->actualizado = Tools::dateTime();
        $this->coddivisa = Tools::settings('default', 'coddivisa');
        $this->dtopor = 0.0;
        $this->dtopor2 = 0.0;
        $this->neto = 0.0;
        $this->netoeuros = 0.0;
        $this->precio = 0.0;
        $this->stock = 0.0;
    }

    /**
     * Returns the Equivalent Unified Discount.
     *
     * @return float
     */
    public function getEUDiscount(): float
    {
        $eud = 1.0;
        foreach ([$this->dtopor, $this->dtopor2] as $dto) {
            $eud *= 1 - $dto / 100;
        }

        return $eud;
    }

    public function getVariant(): DinVariante
    {
        $variant = new DinVariante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        $variant->loadFromCode('', $where);
        return $variant;
    }

    public function getSupplier(): DinProveedor
    {
        $supplier = new DinProveedor();
        $supplier->loadFromCode($this->codproveedor);
        return $supplier;
    }

    public function install(): string
    {
        // needed dependencies
        new DinDivisa();
        new DinProveedor();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'productosprov';
    }

    public function test(): bool
    {
        $this->referencia = Tools::noHtml($this->referencia);
        $this->refproveedor = Tools::noHtml($this->refproveedor);

        if (empty($this->referencia)) {
            Tools::log()->warning('field-can-not-be-null', [
                '%fieldName%' => 'referencia',
                '%tableName%' => static::tableName()
            ]);
            return false;
        } elseif (empty($this->refproveedor)) {
            $this->refproveedor = $this->referencia;
        }

        if (empty($this->idproducto)) {
            $this->idproducto = $this->getVariant()->idproducto;
        }

        $this->neto = round($this->precio * $this->getEUDiscount(), DinProducto::ROUND_DECIMALS);

        $tasaConv = Divisas::get($this->coddivisa)->tasaconvcompra;
        $this->netoeuros = empty($tasaConv) ? 0 : round($this->neto / $tasaConv, 5);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $this->getVariant()->url($type);
    }

    /**
     * This method is called after a record is deleted on the database (delete).
     */
    protected function onDelete()
    {
        CostPriceTools::update($this->getVariant());
        parent::onDelete();
    }

    /**
     * This method is called after a new record is saved on the database (saveInsert).
     */
    protected function onInsert()
    {
        CostPriceTools::update($this->getVariant());
        parent::onInsert();
    }

    /**
     * This method is called after a record is updated on the database (saveUpdate).
     */
    protected function onUpdate()
    {
        if ($this->previousData['neto'] !== $this->neto) {
            CostPriceTools::update($this->getVariant());
        }
        parent::onUpdate();
    }

    protected function setPreviousData(array $fields = [])
    {
        parent::setPreviousData(array_merge(['neto'], $fields));
    }
}
