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
 * Description of ProductoVariante
 *
 * @author raul
 */
class ProductoVariante extends JoinModel {

    public function __construct($data = []) {
        parent::__construct($data);
        $this->setMasterModel(new Producto());
    }

    protected function getFields(): array {
        return [
            'idproducto' => 'productos.idproducto',
            'referencia' => 'variantes.referencia',
            'descripcion' => 'productos.descripcion',
            'stockfis' => 'variantes.stockfis',
            'codbarras' => 'variantes.codbarras',
            'coste' => 'variantes.coste',
            'margen' => 'variantes.margen',
            'precio' => 'variantes.precio',
            'idatributovalor1' => 'variantes.idatributovalor1',
            'idatributovalor2' => 'variantes.idatributovalor2',
            'idatributovalor3' => 'variantes.idatributovalor3',
            'idatributovalor4' => 'variantes.idatributovalor4',
        ];
    }

    protected function getSQLFrom(): string {
        return 'productos'
                . ' left JOIN variantes ON variantes.idproducto = productos.idproducto';
    }

    protected function getTables(): array {
        return ['productos', 'variantes'];
    }

    public function primaryColumnValue() {
        return $this->idproducto;
    }

}
