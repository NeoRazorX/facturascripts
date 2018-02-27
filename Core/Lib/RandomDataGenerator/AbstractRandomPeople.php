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
 * Abstract class to randomly populate tables containing data of natural 
 * and legal persons (name, telephone numbers, addresses, etc.)
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
abstract class AbstractRandomPeople extends AbstractRandom
{

    /**
     * List of agents.
     *
     * @var Model\Agente[]
     */
    protected $agentes;

    /**
     * List of client groups.
     *
     * @var Model\GrupoClientes[]
     */
    protected $grupos;

    /**
     * List of countries.
     *
     * @var Model\Pais[]
     */
    protected $paises;

    /**
     * AbstractRandomPeople constructor.
     *
     * @param $model
     */
    public function __construct($model)
    {
        parent::__construct($model);

        $this->shuffle($this->agentes, new Model\Agente());
        $this->shuffle($this->grupos, new Model\GrupoClientes());
        $this->shuffle($this->paises, new Model\Pais());
    }

    /**
     * Return a random CIF.
     *
     * @return string
     */
    protected function cif()
    {
        return (mt_rand(0, 9) == 0) ? '' : (string) mt_rand(0, 99999999);
    }

    /**
     * Return a random phone number.
     *
     * @return string
     */
    protected function telefono()
    {
        return (string) mt_rand(555555555, 999999999);
    }

    /**
     * Return a random
     *
     * @return string
     */
    protected function seguridadSocial()
    {
        return (string) mt_rand(10000, 99999) . mt_rand(10000, 99999);
    }

    /**
     * Returns a random name
     *
     * @return string
     */
    public function nombre()
    {
        $nombres = [
            'Carlos', 'Pepe', 'Wilson', 'Petra', 'Madonna', 'Justin',
            'Emiliana', 'Jo', 'Penélope', 'Mia', 'Wynona', 'Antonio',
            'Joe', 'Cristiano', 'Mohamed', 'John', 'Ali', 'Pastor',
            'Barak', 'Sadam', 'Donald', 'Jorge', 'Joel', 'Pedro', 'Mariano',
            'Albert', 'Alberto', 'Gorka', 'Cecilia', 'Carmena', 'Pichita',
            'Alicia', 'Laura', 'Riola', 'Wilson', 'Jaume', 'David',
            "D'Ambrosio", '"Licenciado"', '"El master"',
        ];
        return $this->getOneItem($nombres);
    }

    /**
     * Returns two random surnames
     *
     * @return string
     */
    public function apellidos()
    {
        $apellidos = [
            'García', 'Gómez', 'Ronaldo', 'Suarez', 'Wilson', 'Pacheco',
            'Escobar', 'Mendoza', 'Pérez', 'Cruz', 'Lee', 'Smith', 'Humilde',
            'Hijo de Dios', 'Petrov', 'Maximiliano', 'Nieve', 'Snow', 'Trump',
            'Obama', 'Ali', 'Stark', 'Sanz', 'Rajoy', 'Sánchez', 'Iglesias',
            'Rivera', 'Tudor', 'Lanister', 'Suarez', 'Aznar', 'Botella',
            'Errejón', "D'Ambrosio", 'Peña', '"Márquez"',
        ];
        return $this->getOneItem($apellidos) . ' ' . $this->getOneItem($apellidos);
    }

    /**
     * Returns a job position
     *
     * @return string
     */
    public function cargo()
    {
        $cargos = ['Gerente', 'CEO', 'Compras', 'Comercial', 'Técnico', 'Freelance', 'Becario', 'Becario Senior'];

        return $this->getOneItem($cargos);
    }

    /**
     * Returns a random commercial name
     *
     * @return string
     */
    public function empresa()
    {
        $nombres = [
            'Tech', 'Motor', 'Pasión', 'Future', 'Max', 'Massive', 'Industrial',
            'Plastic', 'Pro', 'Micro', 'System', 'Light', 'Magic', 'Fake', 'Techno',
            'Miracle', 'NX', 'Smoke', 'Steam', 'Power', 'FX', 'Fusion', 'Bastion',
            'Investments', 'Solutions', 'Neo', 'Ming', 'Tube', 'Pear', 'Apple',
            'Dolphin', 'Chrome', 'Cat', 'Hat', 'Linux', 'Soft', 'Mobile', 'Phone',
            'XL', 'Open', 'Thunder', 'Zero', 'Scorpio', 'Zelda', '10', 'V', 'Q',
            'X', 'Arch', 'Arco', 'Broken', 'Arkam', 'RX', "d'Art", 'Peña', '"La cosa"',
        ];

        $separador = ['-', ' & ', ' ', '_', '', '/', '*'];
        $tipo = ['S.L.', 'S.A.', 'Inc.', 'LTD', 'Corp.'];

        return $this->getOneItem($nombres) . $this->getOneItem($separador) .
            $this->getOneItem($nombres) . ' ' . $this->getOneItem($tipo);
    }

    /**
     * Returns a random email
     *
     * @return string
     */
    public function email()
    {
        $nicks = [
            'neo', 'carlos', 'mokko', 'snake', 'pikachu', 'pliskin', 'ocelot', 'samurai',
            'ninja', 'infiltrator', 'info', 'compras', 'ventas', 'administracion', 'contacto',
            'contact', 'invoices', 'mail',
        ];

        return $this->getOneItem($nicks) . '.' . mt_rand(2, 9999) . '@facturascripts.com';
    }

    /**
     * Returns a random province
     *
     * @return string
     */
    public function provincia()
    {
        $nombres = [
            'A Coruña', 'Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
            'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Córdoba', 'Cuenca',
            'Girona', 'Granada', 'Guadalajara', 'Guipuzcoa', 'Huelva', 'Huesca', 'Jaen', 'León', 'Lleida', 'La Rioja',
            'Lugo', 'Madrid', 'Málaga', 'Melilla', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Las Palmas', 'Pontevedra',
            'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Tenerife', 'Teruel', 'Toledo', 'Valencia',
            'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza',
        ];

        return $this->getOneItem($nombres);
    }

    /**
     * Returns a random city
     *
     * @return string
     */
    public function ciudad()
    {
        $nombres = [
            'A Coruña', 'Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
            'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Córdoba', 'Cuenca',
            'Girona', 'Granada', 'Guadalajara', 'Guipuzcoa', 'Huelva', 'Huesca', 'Jaen', 'León', 'Lleida', 'La Rioja',
            'Lugo', 'Madrid', 'Málaga', 'Melilla', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Las Palmas', 'Pontevedra',
            'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Tenerife', 'Teruel', 'Toledo', 'Valencia',
            'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza', 'Torrevieja', 'Elche',
        ];

        return $this->getOneItem($nombres);
    }

    /**
     * Returns a random address
     *
     * @return string
     */
    public function direccion()
    {
        $tipos = ['Calle', 'Avenida', 'Polígono', 'Carretera'];
        $nombres = [
            'Infante', 'Principal', 'Falsa', '58', '74', 'Pacheco', 'Baleares',
            'Del Pacífico', 'Rue', "d'Ambrosio", 'Bañez', '"La calle"',
        ];

        $tipo = $this->getOneItem($tipos);
        $nombre = $this->getOneItem($nombres);

        $ret = "$tipo $nombre, nº" . mt_rand(1, 199);

        if (mt_rand(0, 2) == 0) {
            $ret .= ', puerta ' . mt_rand(1, 99);
        }

        return $ret;
    }

    /**
     * Rellena un cliente con datos aleatorios.
     *
     * @param Model\Cliente|Model\Proveedor $clipro
     */
    protected function fillCliPro(&$clipro)
    {
        $clipro->cifnif = (mt_rand(0, 14) === 0) ? '' : mt_rand(0, 99999999);

        if (mt_rand(0, 24) == 0) {
            $clipro->debaja = true;
            $clipro->fechabaja = date('d-m-Y');
        }

        switch (mt_rand(0, 2)) {
            case 0:
                $clipro->nombre = $clipro->razonsocial = $this->empresa();
                $clipro->personafisica = false;
                break;
            case 1:
                $clipro->nombre = $this->nombre() . ' ' . $this->apellidos();
                $clipro->razonsocial = $this->empresa();
                $clipro->personafisica = false;
                break;
            default:
                $clipro->nombre = $clipro->razonsocial = $this->nombre() . ' ' . $this->apellidos();
        }

        switch (mt_rand(0, 2)) {
            case 0:
                $clipro->telefono1 = mt_rand(555555555, 999999999);
                break;
            case 1:
                $clipro->telefono1 = mt_rand(555555555, 999999999);
                $clipro->telefono2 = mt_rand(555555555, 999999999);
                break;
            default:
                $clipro->telefono2 = mt_rand(555555555, 999999999);
        }

        $clipro->email = (mt_rand(0, 2) > 0) ? $this->email() : null;
    }
}
