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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;

/**
 * Abstract class to randomly populate tables containing data of natural 
 * and legal persons (name, telephone numbers, addresses, etc.)
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
abstract class AbstractRandomPeople extends AbstractRandom
{
    protected $paises;
    protected $agente;
    protected $grupos;

    public function __construct($model)
    {
        parent::__construct($model);
        
        $this->shuffle($this->paises, new Model\Pais());
        $this->shuffle($this->agentes, new Model\Agente());
        $this->shuffle($this->grupos, new Model\GrupoClientes());
    }
    
    protected function cif() {
        return (mt_rand(0, 9) == 0) ? '' : (string) mt_rand(0, 99999999);
    }
    
    protected function telefono() {
        return (string) mt_rand(555555555, 999999999);
    }
    
    protected function seguridadSocial() {
        return (string) mt_rand(10000, 99999).mt_rand(10000, 99999);
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
        return $this->getOneItem($apellidos).' '.$this->getOneItem($apellidos);
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
        
        $tipo=$this->getOneItem($tipos);
        $nombre=$this->getOneItem($nombres);

        $ret="$tipo $nombre, nº" . mt_rand(1, 199);
        
        if (mt_rand(0, 2) == 0) {
            $ret.=', puerta ' . mt_rand(1, 99);
        }

        return $ret;
    }

    /**
     * Rellena un cliente con datos aleatorios.
     *
     * @param Model\Cliente|Model\Proveedor $cliente
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
    
private function calcularIBAN($ccc, $codpais = '')
    {
        $pais = substr($codpais, 0, 2);
        $pesos = ['A' => '10', 'B' => '11', 'C' => '12', 'D' => '13', 'E' => '14', 'F' => '15',
            'G' => '16', 'H' => '17', 'I' => '18', 'J' => '19', 'K' => '20', 'L' => '21', 'M' => '22',
            'N' => '23', 'O' => '24', 'P' => '25', 'Q' => '26', 'R' => '27', 'S' => '28', 'T' => '29',
            'U' => '30', 'V' => '31', 'W' => '32', 'X' => '33', 'Y' => '34', 'Z' => '35',
        ];

        $dividendo = $ccc . $pesos[$pais[0]] . $pesos[$pais[1]] . '00';
        $digitoControl = 98 - \bcmod($dividendo, '97');

        if (strlen($digitoControl) === 1) {
            $digitoControl = '0' . $digitoControl;
        }

        return $pais . $digitoControl . $ccc;
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

            $ccc=mt_rand(1000, 9999).mt_rand(1000, 9999).mt_rand(1000, 9999).mt_rand(1000, 9999).mt_rand(1000, 9999);
            $cuenta->iban = $this->calcularIBAN($ccc, 'ES');

            $cuenta->swift = (mt_rand(0, 2) != 0) ? $this->randomString(8) : '';
            $cuenta->fmandato = (mt_rand(0, 1) == 0) ? date('d-m-Y', strtotime($cliente->fechaalta . ' +' . mt_rand(1, 30) . ' days')) : null;

            if (!$cuenta->save()) {
                break;
            }

            --$max;
        }
    }
    
    /**
     * Rellena direcciones de un proveedor con datos aleatorios.
     *
     * @param Model\Proveedor $proveedor
     * @param int             $max
     */
    protected function direccionesProveedor($proveedor, $max = 3)
    {
        while ($max) {
            $dir = new Model\DireccionProveedor();
            $dir->codproveedor = $proveedor->codproveedor;
            $dir->codpais = AppSettings::get('default', 'codpais');

            if (mt_rand(0, 2) == 0) {
                $dir->codpais = $this->paises[0]->codpais;
            }

            $dir->provincia = $this->provincia();
            $dir->ciudad = $this->ciudad();
            $dir->direccion = $this->direccion();
            $dir->codpostal = (string) mt_rand(1234, 99999);

            if (mt_rand(0, 3) == 0) {
                $dir->apartado = (string) mt_rand(1234, 99999);
            }

            if (mt_rand(0, 1) == 0) {
                $dir->direccionppal = false;
            }

            $dir->descripcion = 'Dirección #' . $max;
            $dir->save();
            --$max;
        }
    }

    /**
     * Rellena cuentas bancarias de un proveedor con datos aleatorios.
     *
     * @param Model\Proveedor $proveedor
     * @param int             $max
     */
    protected function cuentasBancoProveedor($proveedor, $max = 3)
    {
        while ($max > 0) {
            $cuenta = new Model\CuentaBancoProveedor();
            $cuenta->codproveedor = $proveedor->codproveedor;
            $cuenta->descripcion = 'Banco ' . mt_rand(1, 999);

            $ccc=mt_rand(1000, 9999).mt_rand(1000, 9999).mt_rand(1000, 9999).mt_rand(1000, 9999).mt_rand(1000, 9999);
            $cuenta->iban = $this->calcularIBAN($ccc, 'ES');

            $cuenta->swift = $this->randomString(8);

            $opcion = mt_rand(0, 2);
            if ($opcion == 0) {
                $cuenta->swift = '';
            } elseif ($opcion == 1) {
                $cuenta->iban = '';
            }

            $cuenta->save();
            --$max;
        }
    }

}