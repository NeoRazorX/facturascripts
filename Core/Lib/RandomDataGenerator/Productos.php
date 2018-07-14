<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\RandomDataGenerator;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model;

/**
 * Generate random data for the products (Productos) file
 *
 * @author Rafael San José <info@rsanjoseo.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Productos extends AbstractRandom
{

    /**
     * List of warehouses.
     *
     * @var Model\Almacen[]
     */
    protected $almacenes;

    /**
     *
     * @var Model\AtributoValor[]
     */
    protected $atributoValores;

    /**
     * List of manufacturers.
     *
     * @var Model\Fabricante[]
     */
    protected $fabricantes;

    /**
     * List of families.
     *
     * @var Model\Familia[]
     */
    protected $familias;

    /**
     * List of taxes.
     *
     * @var Model\Impuesto[]
     */
    protected $impuestos;

    /**
     * Productos constructor.
     */
    public function __construct()
    {
        parent::__construct(new Model\Producto());
        $this->shuffle($this->almacenes, new Model\Almacen());
        $this->shuffle($this->atributoValores, new Model\AtributoValor());
        $this->shuffle($this->fabricantes, new Model\Fabricante());
        $this->shuffle($this->familias, new Model\Familia());
        $this->shuffle($this->impuestos, new Model\Impuesto());
    }

    /**
     * Generate random data.
     *
     * @param int $num
     *
     * @return int
     */
    public function generate($num = 50)
    {
        $product = $this->model;

        // start transaction
        $this->dataBase->beginTransaction();

        // main save process
        $generated = 0;
        for (; $generated < $num; ++$generated) {
            $product->clear();
            $this->setProductoData($product);
            if (!$product->save()) {
                break;
            }

            $variants = $this->setVariants($product);
            $this->setStock($variants);
        }

        // confirm data
        $this->dataBase->commit();

        return $generated;
    }

    private function setProductoData(Model\Producto &$product)
    {
        $product->descripcion = $this->descripcion();
        $product->codimpuesto = $this->impuestos[0]->codimpuesto;

        switch (mt_rand(0, 2)) {
            case 0:
                $variante = new Model\Variante();
                $product->referencia = $variante->newCode('referencia');
                break;

            case 1:
                $aux = explode(':', $product->descripcion);
                $product->referencia = empty($aux) ? $product->newCode() : $this->txt2codigo($aux[0], 25);
                break;

            default:
                $product->referencia = $this->randomString(10);
        }

        if (mt_rand(0, 9) > 0) {
            $product->codfabricante = $this->getOneItem($this->fabricantes)->codfabricante;
            $product->codfamilia = $this->getOneItem($this->familias)->codfamilia;
        }

        $product->publico = (mt_rand(0, 3) == 0);
        $product->bloqueado = (mt_rand(0, 9) == 0);
        $product->nostock = (mt_rand(0, 9) == 0);
        $product->secompra = (mt_rand(0, 9) != 0);
        $product->sevende = (mt_rand(0, 9) != 0);
    }

    private function setVariants(Model\Producto &$product): array
    {
        $variants = [];
        $variant = new Model\Variante();
        if (!$variant->loadFromCode('', [new DataBaseWhere('idproducto', $product->idproducto)])) {
            return $variants;
        }

        $variant->codbarras = (0 === mt_rand(0, 2)) ? '' : $this->randomString(10);
        $variant->coste = $this->precio(1, 49, 699);
        $variant->precio = $variant->coste + $this->precio(1, 49, 699);
        if (!$variant->save()) {
            return $variants;
        }

        $variants[] = $variant;
        if (mt_rand(0, 2) > 0) {
            return $variants;
        }

        for ($num = mt_rand(1, 9); $num > 0; $num--) {
            $newVariant = new Model\Variante();
            $newVariant->idproducto = $product->idproducto;
            $newVariant->codbarras = (0 === mt_rand(0, 2)) ? '' : $this->randomString(10);
            $newVariant->coste = $this->precio(1, 49, 699);
            $newVariant->precio = $newVariant->coste + $this->precio(1, 49, 699);
            $newVariant->referencia = (0 === mt_rand(0, 1)) ? $newVariant->newCode('referencia') : $this->randomString(10);

            if (count($this->atributoValores) > 1) {
                $newVariant->idatributovalor1 = $this->getOneItem($this->atributoValores)->id;
                $newVariant->idatributovalor2 = $this->getOneItem($this->atributoValores)->id;
            }

            if (!$newVariant->save()) {
                break;
            }

            $variants[] = $newVariant;
        }

        return $variants;
    }

    /**
     * 
     * @param Model\Variante[] $variants
     */
    private function setStock($variants)
    {
        foreach ($variants as $variant) {
            if (mt_rand(0, 2) === 0) {
                continue;
            }

            $newStock = new Model\Stock();
            $newStock->codalmacen = $this->getOneItem($this->almacenes)->codalmacen;
            $newStock->idproducto = $variant->idproducto;
            $newStock->referencia = $variant->referencia;
            $newStock->cantidad = $this->cantidad(-10, 90, 1200);
            $newStock->save();
        }
    }
}
