<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Model\Base\ProductRelationTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Divisa as DinDivisa;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;
use FacturaScripts\Dinamic\Model\Proveedor as DinProveedor;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * Description of ProductoProveedor
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProductoProveedor extends ModelClass
{
    use ModelTrait;
    use ProductRelationTrait;

    /** @var string Fecha y hora de la última actualización de los datos del proveedor. */
    public $actualizado;

    /** @var string Código de la divisa utilizada por el proveedor. */
    public $coddivisa;

    /** @var string Código del proveedor asociado al producto. */
    public $codproveedor;

    /** @var float Primer porcentaje de descuento aplicado por el proveedor. */
    public $dtopor;

    /** @var float Segundo porcentaje de descuento aplicado por el proveedor. */
    public $dtopor2;

    /** @var int Identificador único de la relación con el proveedor. */
    public $id;

    /** @var float Precio neto tras aplicar los descuentos. */
    public $neto;

    /** @var float Precio neto convertido a euros. */
    public $netoeuros;

    /** @var float Precio del producto indicado por el proveedor antes de descuentos. */
    public $precio;

    /** @var string Referencia interna de la variante del producto. */
    public $referencia;

    /** @var string Referencia utilizada por el proveedor para el producto. */
    public $refproveedor;

    /** @var float Stock del producto informado por el proveedor. */
    public $stock;

    public function __get($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        if ($key == 'descripcion') {
            return $this->getVariant()->getProducto()->descripcion;
        }

        return null;
    }

    public function __isset(string $key): bool
    {
        if (isset($this->attributes[$key])) {
            return true;
        }

        if ($key === 'descripcion') {
            return true;
        }

        return false;
    }

    public function clear(): void
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
        $variant->loadWhereEq('referencia', $this->referencia);
        return $variant;
    }

    public function getSupplier(): DinProveedor
    {
        $supplier = new DinProveedor();
        $supplier->load($this->codproveedor);
        return $supplier;
    }

    public function install(): string
    {
        // needed dependencies
        new DinDivisa();
        new DinProveedor();

        return parent::install();
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

        // no permitimos fechas de actualización en el futuro, ya que bloquearían
        // futuras actualizaciones del precio de coste
        if (empty($this->actualizado) || strtotime($this->actualizado) > time()) {
            $this->actualizado = Tools::dateTime();
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


}
