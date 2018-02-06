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

namespace FacturaScripts\Core\Lib\RandomDataGenerator\Model;

use FacturaScripts\Core\Model;

/**
 * Class that contains the functions to generate random data
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class Agente extends Model\Base\ModelDataGeneratorClass
{
    /**
     * Contains generated agentes
     *
     * @var Model\Agente[]
     */
    protected $agentes;

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function newModel()
    {
        return new Model\Agente();
    }

    /**
     * Returns a list of items.
     *
     * @return Model\Agente[]
     */
    public function getList()
    {
        return $this->agentes;
    }

    /**
     * Generates $max random Agente.
     * Returns how many agentes were generated.
     *
     * @param int $max
     *
     * @return int
     */
    public function generate($max = 50)
    {
        for ($num = 0; $num < $max; ++$num) {
            $agente = new Model\Agente();
            $agente->fechanacimiento = date(random_int(1, 28) . '-' . random_int(1, 12) . '-' . random_int(1970, 1997));
            $agente->fechaalta = date(random_int(1, 28) . '-' . random_int(1, 12) . '-' . random_int(2013, 2016));
            $agente->cifnif = (random_int(0, 9) === 0) ? '' : (string) random_int(0, 99999999);
            $agente->nombre = $this->tools->nombre();
            $agente->apellidos = $this->tools->apellidos();
            $agente->provincia = $this->tools->provincia();
            $agente->ciudad = $this->tools->ciudad();
            $agente->direccion = $this->tools->direccion();
            $agente->codpostal = (string) random_int(11111, 99999);
            $agente->fechabaja = (random_int(0, 24) === 0) ? date('d-m-Y') : null;
            $agente->telefono1 = (random_int(0, 1) === 0) ? (string) random_int(555555555, 999999999) : '';
            $agente->email = (random_int(0, 2) > 0) ? $this->tools->email() : '';
            $agente->cargo = (random_int(0, 2) > 0) ? $this->tools->cargo() : '';
            $agente->seg_social = (random_int(0, 1) === 0) ? (string) random_int(111111, 9999999999) : '';
            $agente->porcomision = $this->tools->cantidad(0, 5, 20);

            if (random_int(0, 5) === 0) {
                $agente->banco = 'ES' . random_int(10, 99) . ' ' . random_int(1000, 9999) . ' ' . random_int(1000, 9999)
                    . ' ' . random_int(1000, 9999) . ' ' . random_int(1000, 9999) . ' ' . random_int(1000, 9999);
            }

            if (!$agente->save()) {
                break;
            }
        }

        return $num;
    }


    /**
     * Returns an array with random Agente.
     *
     * @param bool $recursivo
     *
     * @return Model\Agente[]
     */
    protected function getRandom($recursivo = true)
    {
        return $this->randomModel('\FacturaScripts\Dinamic\Model\Agente', 'agentes', 'generate', $recursivo);
    }
}
