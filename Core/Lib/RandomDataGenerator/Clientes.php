<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model;

/**
 * Generate random data for the customers (clientes) file
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class Clientes extends AbstractRandomPeople
{

    /**
     * Clientes constructor.
     */
    public function __construct()
    {
        parent::__construct(new Model\Cliente());
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
        $cliente = $this->model;
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

    /**
     * Rellena cuentas bancarias de un cliente con datos aleatorios.
     *
     * @param Model\Cliente $cliente
     * @param int           $max
     */
    protected function cuentasBancoCliente($cliente, $max = 3)
    {
        while ($max > 0) {
            $cuenta = new Model\CuentaBancoCliente();
            $cuenta->codcliente = $cliente->codcliente;
            $cuenta->descripcion = 'Banco ' . mt_rand(1, 999);
            $cuenta->iban = $this->iban();
            $cuenta->swift = (mt_rand(0, 2) != 0) ? $this->randomString(8) : '';
            $cuenta->fmandato = (mt_rand(0, 1) == 0) ? date('d-m-Y', strtotime($cliente->fechaalta . ' +' . mt_rand(1, 30) . ' days')) : null;

            if (!$cuenta->save()) {
                break;
            }

            --$max;
        }
    }

    /**
     * Rellena direcciones de un cliente con datos aleatorios.
     *
     * @param Model\Cliente $cliente
     * @param int           $max
     */
    protected function direccionesCliente($cliente, $max = 3)
    {
        while ($max > 0) {
            $dir = new Model\DireccionCliente();
            $dir->codcliente = $cliente->codcliente;
            $dir->codpais = (mt_rand(0, 2) === 0) ? $this->paises[0]->codpais : AppSettings::get('default', 'codpais');

            $dir->provincia = $this->provincia();
            $dir->ciudad = $this->ciudad();
            $dir->direccion = $this->direccion();
            $dir->codpostal = (string) mt_rand(1234, 99999);
            $dir->apartado = (mt_rand(0, 3) == 0) ? (string) mt_rand(1234, 99999) : null;
            $dir->domenvio = (mt_rand(0, 1) === 1);
            $dir->domfacturacion = (mt_rand(0, 1) === 1);
            $dir->descripcion = 'Dirección #' . $max;
            if (!$dir->save()) {
                break;
            }

            --$max;
        }
    }
}
