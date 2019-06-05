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
            'creationdate' => 'liquidacioncomision.fecha',
            'idsettled' => 'liquidacioncomision.idliquidacion',
            'idagent' => 'liquidacioncomision.codagente',
            'idexercise' => 'liquidacioncomision.codejercicio',
            'note' => 'liquidacioncomision.observaciones',
            'total' => 'liquidacioncomision.total',
            'agent' => 'agentes.nombre',
            'exercise' => 'ejercicios.nombre',
            'idcompany' => 'ejercicios.idempresa',
            'company' => 'empresas.nombre',
            'invoicenumber' => 'facturasprov.codigo',
            'invoicedate' => 'facturasprov.fecha',
        ];
    }

    /**
     * List of tables related to from clausule
     */
    protected function getSQLFrom(): string
    {
        return 'liquidacioncomision'
            . ' INNER JOIN agentes ON agentes.codagente = liquidacioncomision.codagente'
            . ' INNER JOIN ejercicios ON ejercicios.codejercicio = liquidacioncomision.codejercicio'
            . ' INNER JOIN empresas ON empresas.idempresa = ejercicios.idempresa'
            . ' LEFT JOIN facturasprov ON facturasprov.idfactura = liquidacioncomision.idfactura';
    }

    /**
     * List of tables required for the execution of the view.
     */
    protected function getTables(): array
    {
        return [
            'liquidacioncomision',
            'agentes',
            'ejercicios',
            'empresas',
            'facturasprov'
        ];
    }
}
