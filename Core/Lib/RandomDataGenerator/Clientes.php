<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\RandomDataGenerator;

use FacturaScripts\Core\Model;

/**
 *  Generate random data for the customers (clientes) file
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class Clientes extends AbstractRandomPeople
{
    
    public function __construct()
    {
        parent::__construct(new Model\Cliente());
    }
    
    public function generate($num = 50) {
        $cliente=$this->model;
        for ($i = 0; $i < $num; ++$i) {
            $cliente->clear();
            $this->fillCliPro($cliente);

            $cliente->fechaalta = date(mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y')));
            $cliente->regimeniva = (mt_rand(0, 9) === 0) ? 'Exento' : 'General';

            if (mt_rand(0, 2) > 0) {
                shuffle($this->agentes);
                $cliente->codagente = $this->agentes[0]->codagente;
            } else {
                $cliente->codagente = null;
            }

            if (mt_rand(0, 2) > 0 && !empty($this->grupos)) {
                shuffle($this->grupos);
                $cliente->codgrupo = $this->grupos[0]->codgrupo;
            } else {
                $cliente->codgrupo = null;
            }

            $cliente->codcliente = $cliente->newCode();
            if (!$cliente->save()) {
                break;
            }

            /// añadimos direcciones
            $numDirs = mt_rand(0, 3);
            $this->direccionesCliente($cliente, $numDirs);

            /// Añadimos cuentas bancarias
            $numCuentas = mt_rand(0, 3);
            $this->cuentasBancoCliente($cliente, $numCuentas);
        }

        return $i;
    }
}