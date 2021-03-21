<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FacturaScripts\Core\Model\Join;

use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Description of ProductoStock
 *
 * @author raul
 */
class ProductoStock extends JoinModel {

    public function __construct($data = []) {
        parent::__construct($data);
        $this->setMasterModel(new Producto());
    }

    protected function getFields(): array {
        return [
            'referencia' => 'productos.referencia',
            'codalmacen' => 'stocks.codalmacen',
            'descripcion' => 'productos.descripcion',
            'cantidad' => 'stocks.cantidad',
            'stockmin' => 'stocks.stockmin',
            'stockmax' => 'stocks.stockmax',
            'reservada' => 'stocks.reservada',
            'pterecibir' => 'stocks.pterecibir',
            'disponible' => 'stocks.disponible',
        ];
    }

    protected function getSQLFrom(): string {
        return 'productos'
                . ' left join stocks on stocks.idproducto=productos.idproducto'
        ;
    }

    protected function getTables(): array {
        return ['productos', 'stocks'];
    }

    public function primaryColumnValue() {
        return $this->idproducto;
    }

}
