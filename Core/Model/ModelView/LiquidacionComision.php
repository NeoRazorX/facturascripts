<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\ModelView;

use FacturaScripts\Core\Model\Base\ModelView;
use FacturaScripts\Dinamic\Model\LiquidacionComision as ParentModel;

/**
 * Description of Settled Commission list view
 *
 * @author Artex Trading s.a. <jcuello@artextrading.com>
 */
class LiquidacionComision extends ModelView
{

    /**
     * Constructor and class initializer.
     *
     * @param array $data
     */
    public function __construct($data = array())
    {
        parent::__construct($data);

        $this->setMasterModel(new ParentModel());
    }

    /**
     * List of fields or columns to select clausule
     */
    protected function getFields(): array
    {
        return [
            'fecha' => 'liquidacionescomisiones.fecha',
            'idliquidacion' => 'liquidacionescomisiones.idliquidacion',
            'codagente' => 'liquidacionescomisiones.codagente',
            'codejercicio' => 'liquidacionescomisiones.codejercicio',
            'observaciones' => 'liquidacionescomisiones.observaciones',
            'total' => 'liquidacionescomisiones.total',
            'agente' => 'agentes.nombre',
            'ejercicio' => 'ejercicios.nombre',
            'idempresa' => 'ejercicios.idempresa',
            'empresa' => 'empresas.nombrecorto',
            'factura' => 'facturasprov.codigo',
            'fechafactura' => 'facturasprov.fecha',
        ];
    }

    /**
     * List of tables related to from clausule
     */
    protected function getSQLFrom(): string
    {
        return 'liquidacionescomisiones'
            . ' INNER JOIN agentes ON agentes.codagente = liquidacionescomisiones.codagente'
            . ' INNER JOIN ejercicios ON ejercicios.codejercicio = liquidacionescomisiones.codejercicio'
            . ' INNER JOIN empresas ON empresas.idempresa = ejercicios.idempresa'
            . ' LEFT JOIN facturasprov ON facturasprov.idfactura = liquidacionescomisiones.idfactura';
    }

    /**
     * List of tables required for the execution of the view.
     */
    protected function getTables(): array
    {
        return [
            'liquidacionescomisiones',
            'agentes',
            'ejercicios',
            'empresas',
            'facturasprov'
        ];
    }
}
