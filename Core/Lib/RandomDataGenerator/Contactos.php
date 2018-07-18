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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model;

/**
 * Generate random data for the contact
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class Contactos extends AbstractRandomPeople
{

    /**
     * Contactos constructor.
     */
    public function __construct()
    {
        parent::__construct(new Model\Contacto());
    }

    /**
     * Generate random data.
     *
     * @param int $num
     *
     * @return int
     */
    public function generate($num = 50): int
    {
        $contacto = $this->model;

        // start transaction
        $this->dataBase->beginTransaction();

        // main save process
        try {
            for ($i = 0; $i < $num; ++$i) {
                $contacto->clear();
                $this->fillContacto($contacto);

                if (!$contacto->save()) {
                    break;
                }
            }
            // confirm data
            $this->dataBase->commit();
        } catch (\Exception $e) {
            $this->miniLog->alert($e->getMessage());
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }

        return $i;
    }

    /**
     * Fill with random data a contact.
     *
     * @param Model\Contacto $contacto
     */
    protected function fillContacto(&$contacto)
    {
        $agentes = $this->randomAgentes();
        $totalAgentes = count($agentes)-1;
        $randomAgente = (int) $this->cantidad(0, $totalAgentes, $totalAgentes);
        $clientes = $this->randomClientes();
        $totalClientes = count($clientes)-1;
        $randomCliente= (int) $this->cantidad(0, $totalClientes, $totalClientes);
        $paises = [];
        $this->shuffle($paises, new Model\Pais());
        $timeStamp = random_int(0, 1) > 0 ? random_int(time()/2, time()) : time();

        $contacto->admitemarketing = random_int(0, 100 > 50);
        $contacto->apellidos = random_int(0, 1) > 0 ? $this->apellidos() : null;
        $contacto->cargo = random_int(0, 1) > 0 ? $this->cargo() : null;
        $contacto->cifnif = random_int(0, 1) > 0 ? $this->cif() : null;
        $contacto->ciudad = random_int(0, 1) > 0 ? $this->ciudad() : null;
        $contacto->codagente = $agentes[$randomAgente]->codagente;
        $contacto->codcliente = $clientes[$randomCliente]->codcliente;
        $contacto->codpais = random_int(0, 100 > 50) === 0 ? $paises[0]->codpais : AppSettings::get('default', 'codpais');
        $contacto->codpostal = (string) random_int(1234, 99999);
        $contacto->direccion = random_int(0, 1) > 0 ? $this->direccion() : null;
        $contacto->email = $this->email();
        $contacto->empresa = random_int(0, 1) > 0 ? $this->empresa() : null;
        $contacto->fechaalta = $this->fecha();
        $contacto->lastactivity = date('d-m-Y H:i:s', $timeStamp);
        $contacto->lastip = random_int(0, 1) > 0 ?  : '::1';
        $contacto->logkey = Utils::randomString(99);
        $contacto->nombre = $this->nombre();
        $contacto->observaciones = random_int(0, 1) > 0 ? $this->observaciones() : null;
        $contacto->password = '';
        if (random_int(0, 1) > 0) {
            $planPass = Utils::randomString(10);
            $contacto->setPassword($planPass);
        }
        $contacto->personafisica = random_int(0, 1) > 0;
        $contacto->provincia = random_int(0, 1) > 0 ? $this->provincia() : null;
        $contacto->puntos = (int) $this->cantidad(0, 10, 200);
        $contacto->telefono1 = random_int(0, 1) > 0 ? $this->telefono() : null;
        $contacto->telefono2 = random_int(0, 1) > 0 ? $this->telefono() : null;
        $contacto->verificado = random_int(0, 1) > 0;
        $contacto->cifnif = random_int(0, 14) === 0 ? '' : random_int(0, 99999999);
    }
}
