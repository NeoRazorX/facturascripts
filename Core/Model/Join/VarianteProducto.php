<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Join;

use FacturaScripts\Dinamic\Model\Base\JoinModel;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;

/**
 * Model Variante with Producto data
 *
 * @author Raul Jimenez                     <raul.jimenez@nazcanetworks.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 * @author Carlos García Gómez              <carlos@facturascripts.com>
 */
class VarianteProducto extends JoinModel
{

    /**
     * Class constructor.
     * Set master model for controller actions.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new DinProducto());
    }

    /**
     * List of fields or columns to select clausule.
     * 
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'codbarras' => 'variantes.codbarras',
            'coste' => 'variantes.coste',
            'idatributovalor1' => 'variantes.idatributovalor1',
            'idatributovalor2' => 'variantes.idatributovalor2',
            'idatributovalor3' => 'variantes.idatributovalor3',
            'idatributovalor4' => 'variantes.idatributovalor4',
            'idproducto' => 'variantes.idproducto',
            'idvariante' => 'variantes.idvariante',
            'margen' => 'variantes.margen',
            'precio' => 'variantes.precio',
            'referencia' => 'variantes.referencia',
            'stockfis' => 'variantes.stockfis',
            'descripcion' => 'productos.descripcion'
        ];
    }

    /**
     * List of tables related to from clausule.
     * 
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return 'variantes LEFT JOIN productos ON productos.idproducto = variantes.idproducto';
    }

    /**
     * List of tables required for the execution of the view.
     * 
     * @return array
     */
    protected function getTables(): array
    {
        return ['productos', 'variantes'];
    }
}
