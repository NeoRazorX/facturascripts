<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\JoinModel;
use FacturaScripts\Core\Where;
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
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new DinProducto());
    }

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
            'iva' => 'impuestos.iva',
            'margen' => 'variantes.margen',
            'precio' => 'variantes.precio',
            'precio_iva' => '(variantes.precio * (100 + impuestos.iva) / 100)',
            'referencia' => 'variantes.referencia',
            'stockfis' => 'variantes.stockfis',
            'descripcion' => 'productos.descripcion'
        ];
    }

    protected function getSQLFrom(): string
    {
        return 'variantes'
            . ' LEFT JOIN productos ON productos.idproducto = variantes.idproducto'
            . ' LEFT JOIN impuestos ON impuestos.codimpuesto = productos.codimpuesto';
    }

    protected function getTables(): array
    {
        return ['productos', 'variantes', 'impuestos'];
    }

    /**
     * El campo precio_iva es una expresión matemática: (variantes.precio * (100 + impuestos.iva) / 100).
     * El método base no puede envolverla en SUM() porque confunde la expresión con una función agregada.
     * Aquí la gestionamos explícitamente.
     *
     * @param Where[] $where
     */
    public function totalSum(string $field, array $where = []): float
    {
        if ($field === 'precio_iva') {
            $sql = 'SELECT SUM(variantes.precio * (100 + impuestos.iva) / 100) AS total_sum'
                . ' FROM ' . $this->getSQLFrom()
                . Where::multiSqlLegacy($where);
            $data = self::db()->select($sql);
            return count($data) === 1 ? (float)$data[0]['total_sum'] : 0.0;
        }

        return parent::totalSum($field, $where);
    }
}
