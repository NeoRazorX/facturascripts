<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model;

define('FS_NF0_ART', 2);

/**
 * Clase con todo tipo de funciones para generar datos aleatorios.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class ModelDataGenerator
{
    protected $agentes;
    protected $almacenes;
    protected $db;
    protected $divisas;
    protected $ejercicio;
    protected $empresa;
    protected $formas_pago;
    protected $grupos;
    protected $impuestos;
    protected $paises;
    protected $series;
    protected $users;

    /**
     * Constructor. Inicializamos todo lo necesario y randomizamos.
     * @param Model\Empresa $empresa
     */
    public function __construct($empresa)
    {
        $this->db = new DataBase();
        $this->empresa = $empresa;
        $this->ejercicio = new Model\Ejercicio();
        $this->_loaddata($this->agentes, new Model\Agente(), TRUE);
        $this->_loaddata($this->almacenes, new Model\Almacen(), TRUE);
        $this->_loaddata($this->divisas, new Model\Divisa(), TRUE);
        $this->_loaddata($this->formas_pago, new Model\FormaPago(), TRUE);
        $this->_loaddata($this->grupos, new Model\GrupoClientes(), FALSE);
        $this->_loaddata($this->impuestos, new Model\Impuesto(), TRUE);
        $this->_loaddata($this->paises, new Model\Pais(), TRUE);
        $this->_loaddata($this->series, new Model\Serie(), TRUE);
        $this->_loaddata($this->users, new Model\User(), FALSE);
    }

    /**
     * Metodo de apoyo para el constructor de modelos e inicializacion de datos
     * @param array $variable    -> destino de los datos
     * @param fs_model $modelo   -> modelo de cada uno de los items del array
     * @param boolean $shuffle   -> ordenar aleatoriamente la lista
     */
    private function _loaddata(&$variable, $modelo, $shuffle)
    {
        $variable = $modelo->all();
        if ($shuffle) {
            shuffle($variable);
        }
    }

    /**
     * Acorta un string hasta $len y sustituye caracteres especiales.
     * Devuelve el string acortado.
     * @param string $txt
     * @param int $len
     * @return string
     */
    protected function txt2codigo($txt, $len = 8)
    {
        $result = str_replace(array(' ', '-', '_', '&', 'ó', ':', 'ñ', '"', "'", '*'), array('', '', '', '', 'O', '', 'N', '', '', '-'), strtoupper($txt));

        if (strlen($result) > $len) {
            $result = substr($result, 0, $len - 1) . mt_rand(0, 9);
        }

        return $result;
    }

    /**
     * Devuelve una descripción de producto aleatoria.
     * @return string
     */
    protected function descripcion()
    {
        $prefijos = array(
            'Jet', 'Jex', 'Max', 'Pro', 'FX', 'Neo', 'Maxi', 'Extreme', 'Sub',
            'Ultra', 'Minga', 'Hiper', 'Giga', 'Mega', 'Super', 'Fusion', 'Broken'
        );
        shuffle($prefijos);

        $nombres = array(
            'Motor', 'Engine', 'Generator', 'Tool', 'Oviode', 'Box', 'Proton', 'Neutro',
            'Radeon', 'GeForce', 'nForce', 'Labtech', 'Station', 'Arco', 'Arkam'
        );
        shuffle($nombres);

        $sufijos = array(
            'II', '3', 'XL', 'XXL', 'SE', 'GT', 'GTX', 'Pro', 'NX', 'XP', 'OS', 'Nitro'
        );
        shuffle($sufijos);

        $descripciones1 = array(
            'Una alcachofa', 'Un motor', 'Una targeta gráfica (GPU)', 'Un procesador',
            'Un coche', 'Un dispositivo tecnológico', 'Un magnetofón', 'Un palo',
            'un cubo de basura', "Un objeto pequeño d'or", '"La hostia"'
        );
        shuffle($descripciones1);

        $descripciones2 = array(
            '64 núcleos', 'chasis de fibra de carbono', '8 cilindros en V', 'frenos de berilio',
            '16 ejes', 'pantalla Super AMOLED', '1024 stream processors', 'un núcleo híbrido',
            '32 pistones digitales', 'tecnología digitrónica 4.1', 'cuernos metálicos', 'un palo',
            'memoria HBM', 'taladro matricial', 'Wifi 4G', 'faros de xenon', 'un ambientador de pino',
            'un posavasos', 'malignas intenciones', 'la virginidad intacta', 'malware', 'linux',
            'Windows Vista', 'propiedades psicotrópicas', 'spyware', 'reproductor 4k'
        );
        shuffle($descripciones2);

        $texto = $prefijos[0] . ' ' . $nombres[0] . ' ' . $sufijos[0];

        switch (mt_rand(0, 4)) {
            case 0:
                break;

            case 1:
                $texto .= ': ' . $descripciones1[0] . ' con ' . $descripciones2[0] . '.';
                break;

            case 2:
                $texto .= ': ' . $descripciones1[0] . ' con ' . $descripciones2[0] . ', ' . $descripciones2[1] . ', ' . $descripciones2[2] . ' y ' . $descripciones2[3] . '.';
                break;

            case 3:
                $texto .= ': ' . $descripciones1[0] . " con:\n- " . $descripciones2[0] . "\n- " . $descripciones2[1] . "\n- " . $descripciones2[2] . "\n- " . $descripciones2[3] . '.';
                break;

            default:
                $texto .= ': ' . $descripciones1[0] . ' con ' . $descripciones2[0] . ', ' . $descripciones2[1] . ' y ' . $descripciones2[2] . '.';
                break;
        }

        return $texto;
    }

    /**
     * Devuelve un número aleatorio entre $min y $max1.
     * 1 de cada 10 veces lo devuelve entre $min y $max2.
     * 1 de cada 5 veces lo devuelve con decimales.
     * @param type $min
     * @param type $max1
     * @param type $max2
     * @return type
     */
    protected function cantidad($min, $max1, $max2)
    {
        $cantidad = mt_rand($min, $max1);

        if (mt_rand(0, 9) == 0) {
            $cantidad = mt_rand($min, $max2);
        } else if ($cantidad < $max1 AND mt_rand(0, 4) == 0) {
            $cantidad += round(mt_rand(1, 5) / mt_rand(1, 10), mt_rand(0, 3));
            $cantidad = min(array($max1, $cantidad));
        }

        return $cantidad;
    }

    /**
     * Devuelve un número aleatorio entre $min y $max1.
     * 1 de cada 10 veces lo devuelve entre $min y $max2.
     * 1 de cada 3 veces lo devuelve con decimales.
     * @param type $min
     * @param type $max1
     * @param type $max2
     * @return type
     */
    protected function precio($min, $max1, $max2)
    {
        $precio = mt_rand($min, $max1);

        if (mt_rand(0, 9) == 0) {
            $precio = mt_rand($min, $max2);
        } else if ($precio < $max1 AND mt_rand(0, 2) == 0) {
            $precio += round(mt_rand(1, 5) / mt_rand(1, 10), FS_NF0_ART);
            $precio = min(array($max1, $precio));
        }

        return $precio;
    }

    /**
     * Genera $max fabricantes aleatorios.
     * Devuelve el número de fabricantes generados.
     * @param int $max
     * @return int
     */
    public function fabricantes($max = 50)
    {
        $fabri = new Model\Fabricante();
        for ($num = 0; $num < $max; ++$num) {
            $fabri->nombre = $this->empresa();
            $fabri->codfabricante = $this->txt2codigo($fabri->nombre);
            if (!$fabri->save()) {
                break;
            }
        }

        return $num;
    }

    /**
     * Genera $max familias aleatorias.
     * Devuelve el número de familias creadas.
     * @param int $max
     * @return int
     */
    public function familias($max = 50)
    {
        $fam = new Model\Familia();
        $codfamilia = NULL;

        for ($num = 0; $num < $max; ++$num) {
            $fam->descripcion = $this->empresa();
            $fam->codfamilia = $this->txt2codigo($fam->descripcion);
            $fam->madre = (mt_rand(0, 4) == 0) ? $codfamilia : NULL;
            if (!$fam->save())
                break;

            $codfamilia = $fam->codfamilia;
        }

        return $num;
    }

    /**
     * Genera $max artículos aleatorios.
     * Devuelve el número de artículos generados.
     * @param int $max
     * @return int
     */
    public function articulos($max = 50)
    {
        $fab = new Model\Fabricante();
        $fabricantes = $fab->all();

        $fam = new Model\Familia();
        $familias = $fam->all();

        for ($num = 0; $num < $max; ++$num) {
            if (mt_rand(0, 2) == 0) {
                shuffle($fabricantes);
                shuffle($familias);

                if ($this->impuestos[0]->iva <= 10) {
                    shuffle($this->impuestos);
                }
            }

            $art = new Model\Articulo();
            $art->descripcion = $this->descripcion();
            $art->codimpuesto = $this->impuestos[0]->codimpuesto;
            $art->setPvpIva($this->precio(1, 49, 699));
            $art->costemedio = $art->preciocoste = $this->cantidad(0, $art->pvp, $art->pvp + 1);
            $art->stockmin = mt_rand(0, 10);
            $art->stockmax = mt_rand($art->stockmin + 1, $art->stockmin + 1000);

            switch (mt_rand(0, 2)) {
                case 0:
                    $art->referencia = $art->getNewReferencia();
                    break;

                case 1:
                    $aux = explode(':', $art->descripcion);
                    if ($aux) {
                        $art->referencia = $this->txt2codigo($aux[0], 18);
                    } else {
                        $art->referencia = $art->getNewReferencia();
                    }
                    break;

                default:
                    $art->referencia = $this->random_string(10);
            }

            if (mt_rand(0, 9) > 0) {
                $art->codfabricante = $fabricantes[0]->codfabricante;
                $art->codfamilia = $familias[0]->codfamilia;
            } else {
                $art->codfabricante = NULL;
                $art->codfamilia = NULL;
            }

            $art->publico = (mt_rand(0, 3) == 0);
            $art->bloqueado = (mt_rand(0, 9) == 0);
            $art->nostock = (mt_rand(0, 9) == 0);
            $art->secompra = (mt_rand(0, 9) != 0);
            $art->sevende = (mt_rand(0, 9) != 0);

            if (!$art->save()) {
                break;
            }

            shuffle($this->almacenes);
            if (mt_rand(0, 2) == 0) {
                $art->sumStock($this->almacenes[0]->codalmacen, mt_rand(0, 1000));
            } else {
                $art->sumStock($this->almacenes[0]->codalmacen, mt_rand(0, 20));
            }
        }

        return $num;
    }

    /**
     * Genera $max agentes (empleados) aleatorios.
     * Devuelve el número de agentes generados.
     * @param type $max
     * @return int
     */
    public function agentes($max = 50)
    {
        for ($num = 0; $num < $max; ++$num) {
            $agente = new Model\Agente();
            $agente->f_nacimiento = date(mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(1970, 1997));
            $agente->f_alta = date(mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, 2016));

            if (mt_rand(0, 24) == 0) {
                $agente->f_baja = date('d-m-Y');
            }

            if (mt_rand(0, 9) == 0) {
                $agente->dnicif = '';
            } else {
                $agente->dnicif = mt_rand(0, 99999999);
            }

            $agente->nombre = $this->nombre();
            $agente->apellidos = $this->apellidos();
            $agente->provincia = $this->provincia();
            $agente->ciudad = $this->ciudad();
            $agente->direccion = $this->direccion();
            $agente->codpostal = mt_rand(11111, 99999);

            if (mt_rand(0, 1) == 0) {
                $agente->telefono = mt_rand(555555555, 999999999);
            }

            if (mt_rand(0, 2) > 0) {
                $agente->email = $this->email();
            }

            if (mt_rand(0, 2) > 0) {
                $cargos = array('Gerente', 'CEO', 'Compras', 'Comercial', 'Técnico', 'Freelance', 'Becario', 'Becario Senior');
                shuffle($cargos);
                $agente->cargo = $cargos[0];
            }

            if (mt_rand(0, 1) == 0) {
                $agente->seg_social = mt_rand(111111, 9999999999);
            }

            if (mt_rand(0, 5) == 0) {
                $agente->banco = 'ES' . mt_rand(10, 99) . ' ' . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999) . ' '
                    . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999);
            }

            if (mt_rand(0, 5) == 0) {
                $agente->porcomision = $this->cantidad(0, 5, 20);
            }

            if (!$agente->save()) {
                break;
            }
        }

        return $num;
    }

    /**
     * Genera $max grupos de clientes aleatorios.
     * Devuelve el número de grupos de clientes generados.
     * @param type $max
     * @return int
     */
    public function grupos_clientes($max = 50)
    {
        $nombres = array('Profesionales', 'Profesional', 'Grandes compradores', 'Preferentes', 'Basico', 'Premium', 'Variado', 'Reservado', 'Técnico', 'Elemental');
        $nombres2 = array('VIP', 'PRO', 'NEO', 'XL', 'XXL', '50 aniversario', 'C', 'Z');

        $max_nombres = count($nombres) - 1;
        $max_nombres2 = count($nombres2) - 1;

        $grupo = new Model\GrupoClientes();
        for ($num = 0; $num < $max; ++$num) {
            $grupo->codgrupo = $grupo->getNewCodigo();
            $grupo->nombre = $nombres[mt_rand(0, $max_nombres)] . ' '
                . $nombres2[mt_rand(0, $max_nombres2)] . ' ' . $num;
            if (!$grupo->save()) {
                break;
            }
        }

        return $num;
    }

    /**
     * Genera $max clientes aleatorios.
     * Devuelve el número de clientes generados.
     * @param type $max
     * @return int
     */
    public function clientes($max = 50)
    {
        for ($num = 0; $num < $max; ++$num) {
            $cliente = new Model\Cliente();
            $cliente->fechaalta = date(mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y')));

            if (mt_rand(0, 24) == 0) {
                $cliente->debaja = TRUE;
                $cliente->fechabaja = date('d-m-Y');
            }

            $cliente->cifnif = (mt_rand(0, 14) === 0) ? '' : mt_rand(0, 99999999);

            switch (mt_rand(0, 2)) {
                case 0:
                    $cliente->nombre = $cliente->razonsocial = $this->empresa();
                    $cliente->personafisica = FALSE;
                    break;
                case 1:
                    $cliente->nombre = $this->nombre() . ' ' . $this->apellidos();
                    $cliente->razonsocial = $this->empresa();
                    $cliente->personafisica = FALSE;
                    break;
                default:
                    $cliente->nombre = $cliente->razonsocial = $this->nombre() . ' ' . $this->apellidos();
            }

            switch (mt_rand(0, 2)) {
                case 0:
                    $cliente->telefono1 = mt_rand(555555555, 999999999);
                    break;
                case 1:
                    $cliente->telefono1 = mt_rand(555555555, 999999999);
                    $cliente->telefono2 = mt_rand(555555555, 999999999);
                    break;
                default:
                    $cliente->telefono2 = mt_rand(555555555, 999999999);
            }

            $cliente->email = (mt_rand(0, 2) > 0) ? $this->email() : NULL;
            $cliente->regimeniva = (mt_rand(0, 9) === 0) ? 'Exento' : 'General';

            if (mt_rand(0, 2) > 0) {
                shuffle($this->agentes);
                $cliente->codagente = $this->agentes[0]->codagente;
            } else {
                $cliente->codagente = NULL;
            }

            if (mt_rand(0, 2) > 0 AND $this->grupos) {
                shuffle($this->grupos);
                $cliente->codgrupo = $this->grupos[0]->codgrupo;
            } else {
                $cliente->codgrupo = NULL;
            }

            $cliente->codcliente = $cliente->newCode();
            if (!$cliente->save()) {
                break;
            }

            /*
            /// añadimos direcciones
            $num_dirs = mt_rand(0, 3);
            while ($num_dirs > 0) {
                $dir = new direccion_cliente();
                $dir->codcliente = $cliente->codcliente;
                $dir->codpais = (mt_rand(0, 2) === 0) ? $this->paises[0]->codpais : $this->empresa->codpais;

                $dir->provincia = $this->provincia();
                $dir->ciudad = $this->ciudad();
                $dir->direccion = $this->direccion();
                $dir->codpostal = mt_rand(1234, 99999);
                $dir->apartado = (mt_rand(0, 3) == 0) ? mt_rand(1234, 99999) : NULL;

                $dir->domenvio = (mt_rand(0, 1) === 1);
                $dir->domfacturacion = (mt_rand(0, 1) === 1);

                $dir->descripcion = 'Dirección #' . $num_dirs;
                if (!$dir->save()) {
                    break;
                }

                $num_dirs--;
            }

            /// Añadimos cuentas bancarias
            $num_cuentas = mt_rand(0, 3);
            while ($num_cuentas > 0) {
                $cuenta = new cuenta_banco_cliente();
                $cuenta->codcliente = $cliente->codcliente;
                $cuenta->descripcion = 'Banco ' . mt_rand(1, 999);

                $opcion = mt_rand(0, 2);
                $cuenta->iban = ($opcion != 1) ? 'ES' . mt_rand(10, 99) . ' ' . mt_rand(1000, 9999) . ' '
                    . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999) . ' '
                    . mt_rand(1000, 9999) : '';

                $cuenta->swift = ($opcion != 0) ? $this->random_string(8) : '';

                $cuenta->fmandato = (mt_rand(0, 1) == 0) ? date('d-m-Y', strtotime($cliente->fechaalta . ' +' . mt_rand(1, 30) . ' days')) : NULL;

                if (!$cuenta->save()) {
                    break;
                }

                $num_cuentas--;
            }
             * 
             */
        }

        return $num;
    }

    /**
     * Genera $max proveedores aleatorios.
     * Devuelve el número de proveedores generados.
     * @param type $max
     * @return int
     */
    public function proveedores($max = 50)
    {
        $num = 0;

        while ($num < $max) {
            $proveedor = new Model\Proveedor();
            $proveedor->cifnif = mt_rand(0, 99999999);
            if (mt_rand(0, 14) == 0) {
                $proveedor->cifnif = '';
            }

            $opcion = mt_rand(0, 4);
            $proveedor->nombre = $proveedor->razonsocial = $this->empresa();
            $proveedor->personafisica = FALSE;
            if ($opcion == 0) {
                $proveedor->nombre = $this->nombre() . ' ' . $this->apellidos();
                $proveedor->personafisica = TRUE;
            } else if ($opcion == 1) {
                $proveedor->nombre = $proveedor->razonsocial = $this->empresa();
                $proveedor->acreedor = TRUE;
            }

            $opcion = mt_rand(0, 2);
            if ($opcion == 0) {
                $proveedor->telefono1 = mt_rand(555555555, 999999999);
            } else if ($opcion == 1) {
                $proveedor->telefono1 = mt_rand(555555555, 999999999);
                $proveedor->telefono2 = mt_rand(555555555, 999999999);
            } else {
                $proveedor->telefono2 = mt_rand(555555555, 999999999);
            }

            if (mt_rand(0, 2) > 0) {
                $proveedor->email = $this->email();
            }

            if (mt_rand(0, 9) == 0) {
                $proveedor->regimeniva = 'Exento';
            }

            if (mt_rand(0, 24) == 0) {
                $proveedor->debaja = TRUE;
                $proveedor->fechabaja = date('d-m-Y');
            }

            $proveedor->codproveedor = $proveedor->newCode();
            if ($proveedor->save()) {
                $num++;

                /*
                /// añadimos direcciones
                $num_dirs = mt_rand(0, 3);
                while ($num_dirs) {
                    $dir = new direccion_proveedor();
                    $dir->codproveedor = $proveedor->codproveedor;
                    $dir->codpais = $this->empresa->codpais;

                    if (mt_rand(0, 2) == 0) {
                        $dir->codpais = $this->paises[0]->codpais;
                    }

                    $dir->provincia = $this->provincia();
                    $dir->ciudad = $this->ciudad();
                    $dir->direccion = $this->direccion();
                    $dir->codpostal = mt_rand(1234, 99999);

                    if (mt_rand(0, 3) == 0) {
                        $dir->apartado = mt_rand(1234, 99999);
                    }

                    if (mt_rand(0, 1) == 0) {
                        $dir->direccionppal = FALSE;
                    }

                    $dir->descripcion = 'Dirección #' . $num_dirs;
                    $dir->save();
                    $num_dirs--;
                }

                /// Añadimos cuentas bancarias
                $num_cuentas = mt_rand(0, 3);
                while ($num_cuentas > 0) {
                    $cuenta = new cuenta_banco_proveedor();
                    $cuenta->codproveedor = $proveedor->codproveedor;
                    $cuenta->descripcion = 'Banco ' . mt_rand(1, 999);
                    $cuenta->iban = 'ES' . mt_rand(10, 99) . ' ' . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999) . ' '
                        . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999) . ' ' . mt_rand(1000, 9999);
                    $cuenta->swift = $this->random_string(8);

                    $opcion = mt_rand(0, 2);
                    if ($opcion == 0) {
                        $cuenta->swift = '';
                    } else if ($opcion == 1) {
                        $cuenta->iban = '';
                    }

                    $cuenta->save();
                    $num_cuentas--;
                }
                 * 
                 */
            } else {
                break;
            }
        }

        return $num;
    }

    /**
     * Devuelve un nombre aleatorio.
     * @return string
     */
    protected function nombre()
    {
        $nombres = array(
            'Carlos', 'Pepe', 'Wilson', 'Petra', 'Madonna', 'Justin',
            'Emiliana', 'Jo', 'Penélope', 'Mia', 'Wynona', 'Antonio',
            'Joe', 'Cristiano', 'Mohamed', 'John', 'Ali', 'Pastor',
            'Barak', 'Sadam', 'Donald', 'Jorge', 'Joel', 'Pedro', 'Mariano',
            'Albert', 'Alberto', 'Gorka', 'Cecilia', 'Carmena', 'Pichita',
            'Alicia', 'Laura', 'Riola', 'Wilson', 'Jaume', 'David',
            "D'Ambrosio", '"El nota"', '"El puto amo"'
        );

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve dos apellidos aleatorios.
     * @return type
     */
    protected function apellidos()
    {
        $apellidos = array(
            'García', 'Gómez', 'Ronaldo', 'Suarez', 'Wilson', 'Pacheco',
            'Escobar', 'Mendoza', 'Pérez', 'Cruz', 'Lee', 'Smith', 'Humilde',
            'Hijo de Dios', 'Petrov', 'Maximiliano', 'Nieve', 'Snow', 'Trump',
            'Obama', 'Ali', 'Stark', 'Sanz', 'Rajoy', 'Sánchez', 'Iglesias',
            'Rivera', 'Tumor', 'Lanister', 'Suarez', 'Aznar', 'Botella',
            'Errejón', "D'Ambrosio", 'Ñostromo'
        );

        shuffle($apellidos);
        return $apellidos[0] . ' ' . $apellidos[1];
    }

    /**
     * Devuelve un nombre comercial aleatorio.
     * @return type
     */
    protected function empresa()
    {
        $nombres = array(
            'Tech', 'Motor', 'Pasión', 'Future', 'Max', 'Massive', 'Industrial',
            'Plastic', 'Pro', 'Micro', 'System', 'Light', 'Magic', 'Fake', 'Techno',
            'Miracle', 'NX', 'Smoke', 'Steam', 'Power', 'FX', 'Fusion', 'Bastion',
            'Investments', 'Solutions', 'Neo', 'Ming', 'Tube', 'Pear', 'Apple',
            'Dolphin', 'Chrome', 'Cat', 'Hat', 'Linux', 'Soft', 'Mobile', 'Phone',
            'XL', 'Open', 'Thunder', 'Zero', 'Scorpio', 'Zelda', '10', 'V', 'Q',
            'X', 'Arch', 'Arco', 'Broken', 'Arkam', 'RX', "d'Art", 'Peña', '"La cosa"'
        );

        $separador = array(
            '-', ' & ', ' ', '_', '', '/', '*'
        );

        $tipo = array(
            'S.L.', 'S.A.', 'Inc.', 'LTD', 'Corp.'
        );

        shuffle($nombres);
        shuffle($separador);
        shuffle($tipo);
        return $nombres[0] . $separador[0] . $nombres[1] . ' ' . $tipo[0];
    }

    /**
     * Devuelve un email aleatorio.
     * @return type
     */
    protected function email()
    {
        $nicks = array(
            'neo', 'carlos', 'moko', 'snake', 'pikachu', 'pliskin', 'ocelot', 'samurai',
            'ninja', 'penetrator', 'info', 'compras', 'ventas', 'administracion', 'contacto',
            'contact', 'invoices', 'mail'
        );

        shuffle($nicks);
        return $nicks[0] . '.' . mt_rand(2, 9999) . '@facturascripts.com';
    }

    /**
     * Devuelve una provincia aleatoria.
     * @return string
     */
    protected function provincia()
    {
        $nombres = array(
            'A Coruña', 'Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
            'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Córdoba', 'Cuenca',
            'Girona', 'Granada', 'Guadalajara', 'Guipuzcoa', 'Huelva', 'Huesca', 'Jaen', 'León', 'Lleida', 'La Rioja',
            'Lugo', 'Madrid', 'Málaga', 'Melilla', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Las Palmas', 'Pontevedra',
            'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Tenerife', 'Teruel', 'Toledo', 'Valencia',
            'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'
        );

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve una ciudad aleatoria.
     * @return string
     */
    protected function ciudad()
    {
        $nombres = array(
            'A Coruña', 'Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
            'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Córdoba', 'Cuenca',
            'Girona', 'Granada', 'Guadalajara', 'Guipuzcoa', 'Huelva', 'Huesca', 'Jaen', 'León', 'Lleida', 'La Rioja',
            'Lugo', 'Madrid', 'Málaga', 'Melilla', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Las Palmas', 'Pontevedra',
            'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Tenerife', 'Teruel', 'Toledo', 'Valencia',
            'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza', 'Torrevieja', 'Elche'
        );

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve una dirección aleatoria.
     * @return type
     */
    protected function direccion()
    {
        $tipos = array(
            'Calle', 'Avenida', 'Polígono', 'Carretera'
        );
        $nombres = array(
            'Infante', 'Principal', 'Falsa', '58', '74',
            'Pacheco', 'Baleares', 'Del Pacífico', 'Rue',
            "d'Ambrosio", 'Bañez', '"La calle"'
        );

        shuffle($tipos);
        shuffle($nombres);

        if (mt_rand(0, 2) == 0) {
            return $tipos[0] . ' ' . $nombres[0] . ', nº' . mt_rand(1, 199) . ', puerta ' . mt_rand(1, 99);
        } else {
            return $tipos[0] . ' ' . $nombres[0] . ', ' . mt_rand(1, 99);
        }
    }

    /**
     * Genera $max albaranes de venta aleatorios.
     * Devuelve el número de albaranes generados.
     * @param type $max
     * @return int
     */
    public function albaranescli($max = 25)
    {
        $num = 0;
        $clientes = $this->random_clientes();

        $recargo = FALSE;
        if ($clientes[0]->recargo OR mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $alb = new albaran_cliente();
            $alb->fecha = mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y'));
            $alb->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
            $alb->codpago = $this->formas_pago[0]->codpago;

            if (mt_rand(0, 2) == 0) {
                $alb->coddivisa = $this->divisas[0]->coddivisa;
                $alb->tasaconv = $this->divisas[0]->tasaconv;
            } else {
                foreach ($this->divisas as $div) {
                    if ($div->coddivisa == $this->empresa->coddivisa) {
                        $alb->coddivisa = $div->coddivisa;
                        $alb->tasaconv = $div->tasaconv;
                        break;
                    }
                }
            }

            $alb->codalmacen = $this->empresa->codalmacen;
            if (mt_rand(0, 2) == 0) {
                $alb->codalmacen = $this->almacenes[0]->codalmacen;
            }

            $alb->codserie = $this->empresa->codserie;
            if (mt_rand(0, 2) == 0) {
                if ($this->series[0]->codserie != 'R') {
                    $alb->codserie = $this->series[0]->codserie;
                    $alb->irpf = $this->series[0]->irpf;
                }

                $alb->observaciones = $this->observaciones($alb->fecha);
                $alb->numero2 = mt_rand(10, 99999);
            }

            $alb->codagente = $this->agentes[0]->codagente;
            if (mt_rand(0, 4) == 0) {
                $alb->codagente = NULL;
            }

            $eje = $this->ejercicio->get_by_fecha($alb->fecha);
            if ($eje) {
                $alb->codejercicio = $eje->codejercicio;

                $regimeniva = 'Exento';
                if (mt_rand(0, 14) > 0 AND isset($clientes[$num])) {
                    $alb->codcliente = $clientes[$num]->codcliente;
                    $alb->nombrecliente = $clientes[$num]->razonsocial;
                    $alb->cifnif = $clientes[$num]->cifnif;
                    $regimeniva = $clientes[$num]->regimeniva;

                    foreach ($clientes[$num]->get_direcciones() as $dir) {
                        if ($dir->domfacturacion) {
                            $alb->codpais = $dir->codpais;
                            $alb->provincia = $dir->provincia;
                            $alb->ciudad = $dir->ciudad;
                            $alb->direccion = $dir->direccion;
                            $alb->codpostal = $dir->codpostal;
                            $alb->apartado = $dir->apartado;
                        }

                        if ($dir->domenvio AND mt_rand(0, 2) == 0) {
                            $alb->envio_nombre = $this->nombre();
                            $alb->envio_apellidos = $this->apellidos();
                            $alb->envio_codpais = $dir->codpais;
                            $alb->envio_provincia = $dir->provincia;
                            $alb->envio_ciudad = $dir->ciudad;
                            $alb->envio_codpostal = $dir->codpostal;
                            $alb->envio_direccion = $dir->direccion;
                            $alb->envio_apartado = $dir->apartado;
                        }
                    }
                } else {
                    /// de vez en cuando creamos uno sin cliente asociado para ver si todo peta ;-)
                    $alb->nombrecliente = $this->nombre() . ' ' . $this->apellidos();
                    $alb->cifnif = mt_rand(1111, 999999999) . 'J';
                }

                if ($alb->save()) {
                    $articulos = $this->random_articulos();

                    /// una de cada 15 veces usamos cantidades negativas
                    $modcantidad = 1;
                    if (mt_rand(0, 4) == 0) {
                        $modcantidad = -1;
                    }

                    $numlineas = $this->cantidad(0, 10, 200);
                    while ($numlineas > 0) {
                        $lin = new linea_albaran_cliente();
                        $lin->idalbaran = $alb->idalbaran;
                        $lin->cantidad = $modcantidad * $this->cantidad(1, 3, 19);
                        $lin->descripcion = $this->descripcion();
                        $lin->pvpunitario = $this->precio(1, 49, 699);
                        $lin->codimpuesto = $this->impuestos[0]->codimpuesto;
                        $lin->iva = $this->impuestos[0]->iva;

                        if ($recargo AND mt_rand(0, 2) == 0) {
                            $lin->recargo = $this->impuestos[0]->recargo;
                        }

                        if (isset($articulos[$numlineas])) {
                            if ($articulos[$numlineas]->sevende) {
                                $lin->referencia = $articulos[$numlineas]->referencia;
                                $lin->descripcion = $articulos[$numlineas]->descripcion;
                                $lin->pvpunitario = $articulos[$numlineas]->pvp;
                                $lin->codimpuesto = $articulos[$numlineas]->codimpuesto;
                                $lin->iva = $articulos[$numlineas]->get_iva();
                                $lin->recargo = 0;
                            }
                        }

                        $lin->irpf = $alb->irpf;

                        if ($regimeniva == 'Exento') {
                            $lin->codimpuesto = NULL;
                            $lin->iva = 0;
                            $lin->recargo = 0;
                            $alb->irpf = $lin->irpf = 0;
                        }

                        if (mt_rand(0, 4) == 0) {
                            $lin->dtopor = $this->cantidad(0, 33, 100);
                        }

                        $lin->pvpsindto = ($lin->pvpunitario * $lin->cantidad);
                        $lin->pvptotal = $lin->pvpunitario * $lin->cantidad * (100 - $lin->dtopor) / 100;

                        if ($lin->save()) {
                            if (isset($articulos[$numlineas])) {
                                /// descontamos del stock
                                $articulos[$numlineas]->sum_stock($alb->codalmacen, 0 - $lin->cantidad);
                            }

                            $alb->neto += $lin->pvptotal;
                            $alb->totaliva += ($lin->pvptotal * $lin->iva / 100);
                            $alb->totalirpf += ($lin->pvptotal * $lin->irpf / 100);
                            $alb->totalrecargo += ($lin->pvptotal * $lin->recargo / 100);
                        }

                        $numlineas--;
                    }

                    /// redondeamos
                    $alb->neto = round($alb->neto, FS_NF0);
                    $alb->totaliva = round($alb->totaliva, FS_NF0);
                    $alb->totalirpf = round($alb->totalirpf, FS_NF0);
                    $alb->totalrecargo = round($alb->totalrecargo, FS_NF0);
                    $alb->total = $alb->neto + $alb->totaliva - $alb->totalirpf + $alb->totalrecargo;
                    $alb->save();

                    $num++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $num;
    }

    /**
     * Genera $max albaranes de compra aleatorios.
     * Devuelve el número de albaranes generados.
     * @param type $max
     * @return int
     */
    public function albaranesprov($max = 25)
    {
        $num = 0;
        $proveedores = $this->random_proveedores();

        $recargo = FALSE;
        if (mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $alb = new albaran_proveedor();
            $alb->fecha = mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y'));
            $alb->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
            $alb->codpago = $this->formas_pago[0]->codpago;

            if (mt_rand(0, 2) == 0) {
                $alb->coddivisa = $this->divisas[0]->coddivisa;
                $alb->tasaconv = $this->divisas[0]->tasaconv_compra;
            } else {
                foreach ($this->divisas as $div) {
                    if ($div->coddivisa == $this->empresa->coddivisa) {
                        $alb->coddivisa = $div->coddivisa;
                        $alb->tasaconv = $div->tasaconv_compra;
                        break;
                    }
                }
            }

            $alb->codalmacen = $this->empresa->codalmacen;
            if (mt_rand(0, 2) == 0) {
                $alb->codalmacen = $this->almacenes[0]->codalmacen;
            }

            $alb->codserie = $this->empresa->codserie;
            if (mt_rand(0, 2) == 0) {
                if ($this->series[0]->codserie != 'R') {
                    $alb->codserie = $this->series[0]->codserie;
                    $alb->irpf = $this->series[0]->irpf;
                }

                $alb->observaciones = $this->observaciones($alb->fecha);
                $alb->numproveedor = mt_rand(10, 99999);
            }

            $alb->codagente = $this->agentes[0]->codagente;
            if (mt_rand(0, 4) == 0) {
                $alb->codagente = NULL;
            }

            $eje = $this->ejercicio->get_by_fecha($alb->fecha);
            if ($eje) {
                $alb->codejercicio = $eje->codejercicio;

                $regimeniva = 'Exento';
                if (mt_rand(0, 14) > 0 AND isset($proveedores[$num])) {
                    $alb->codproveedor = $proveedores[$num]->codproveedor;
                    $alb->nombre = $proveedores[$num]->razonsocial;
                    $alb->cifnif = $proveedores[$num]->cifnif;
                    $regimeniva = $proveedores[$num]->regimeniva;
                } else {
                    /// de vez en cuando generamos un sin proveedor, para ver si todo peta ;-)
                    $alb->nombre = $this->empresa();
                    $alb->cifnif = mt_rand(1111111, 9999999999) . 'Z';
                }

                if ($alb->save()) {
                    $articulos = $this->random_articulos();

                    /// una de cada 15 veces usamos cantidades negativas
                    $modcantidad = 1;
                    if (mt_rand(0, 14) == 0) {
                        $modcantidad = -1;
                    }

                    $numlineas = $this->cantidad(0, 10, 400);
                    while ($numlineas > 0) {
                        $lin = new linea_albaran_proveedor();
                        $lin->idalbaran = $alb->idalbaran;
                        $lin->cantidad = $modcantidad * $this->cantidad(1, 3, 19);
                        $lin->descripcion = $this->descripcion();
                        $lin->pvpunitario = $this->precio(1, 49, 699);
                        $lin->codimpuesto = $this->impuestos[0]->codimpuesto;
                        $lin->iva = $this->impuestos[0]->iva;

                        if ($recargo AND mt_rand(0, 2) == 0) {
                            $lin->recargo = $this->impuestos[0]->recargo;
                        }

                        if (isset($articulos[$numlineas])) {
                            if ($articulos[$numlineas]->sevende) {
                                $lin->referencia = $articulos[$numlineas]->referencia;
                                $lin->descripcion = $articulos[$numlineas]->descripcion;
                                $lin->pvpunitario = $articulos[$numlineas]->pvp;
                                $lin->codimpuesto = $articulos[$numlineas]->codimpuesto;
                                $lin->iva = $articulos[$numlineas]->get_iva();
                                $lin->recargo = 0;
                            }
                        }

                        $lin->irpf = $alb->irpf;

                        if ($regimeniva == 'Exento') {
                            $lin->codimpuesto = NULL;
                            $lin->iva = 0;
                            $lin->recargo = 0;
                            $alb->irpf = $lin->irpf = 0;
                        }

                        if (mt_rand(0, 4) == 0) {
                            $lin->dtopor = $this->cantidad(0, 33, 100);
                        }

                        $lin->pvpsindto = ($lin->pvpunitario * $lin->cantidad);
                        $lin->pvptotal = $lin->pvpunitario * $lin->cantidad * (100 - $lin->dtopor) / 100;

                        if ($lin->save()) {
                            if (isset($articulos[$numlineas])) {
                                /// sumamos al stock
                                $articulos[$numlineas]->sum_stock($alb->codalmacen, $lin->cantidad, TRUE);
                            }

                            $alb->neto += $lin->pvptotal;
                            $alb->totaliva += ($lin->pvptotal * $lin->iva / 100);
                            $alb->totalirpf += ($lin->pvptotal * $lin->irpf / 100);
                            $alb->totalrecargo += ($lin->pvptotal * $lin->recargo / 100);
                        }

                        $numlineas--;
                    }

                    /// redondeamos
                    $alb->neto = round($alb->neto, FS_NF0);
                    $alb->totaliva = round($alb->totaliva, FS_NF0);
                    $alb->totalirpf = round($alb->totalirpf, FS_NF0);
                    $alb->totalrecargo = round($alb->totalrecargo, FS_NF0);
                    $alb->total = $alb->neto + $alb->totaliva - $alb->totalirpf + $alb->totalrecargo;
                    $alb->save();

                    $num++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $num;
    }

    /**
     * Genera $max pedidos de venta aleatorios.
     * Devuelve el número de pedidos generados.
     * @param type $max
     * @return int
     */
    public function pedidoscli($max = 25)
    {
        $num = 0;
        $clientes = $this->random_clientes();

        $recargo = FALSE;
        if ($clientes[0]->recargo OR mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $ped = new pedido_cliente();
            $ped->fecha = mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y'));
            $ped->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
            $ped->codpago = $this->formas_pago[0]->codpago;

            if (mt_rand(0, 2) == 0) {
                $ped->coddivisa = $this->divisas[0]->coddivisa;
                $ped->tasaconv = $this->divisas[0]->tasaconv;
            } else {
                foreach ($this->divisas as $div) {
                    if ($div->coddivisa == $this->empresa->coddivisa) {
                        $ped->coddivisa = $div->coddivisa;
                        $ped->tasaconv = $div->tasaconv;
                        break;
                    }
                }
            }

            $ped->codalmacen = $this->empresa->codalmacen;
            if (mt_rand(0, 2) == 0) {
                $ped->codalmacen = $this->almacenes[0]->codalmacen;
            }

            $ped->codserie = $this->empresa->codserie;
            if (mt_rand(0, 2) == 0) {
                if ($this->series[0]->codserie != 'R') {
                    $ped->codserie = $this->series[0]->codserie;
                    $ped->irpf = $this->series[0]->irpf;
                }

                $ped->observaciones = $this->observaciones($ped->fecha);
                $ped->numero2 = mt_rand(10, 99999);
            }

            $ped->codagente = $this->agentes[0]->codagente;
            if (mt_rand(0, 4) == 0) {
                $ped->codagente = NULL;
            }

            if (mt_rand(0, 5) == 0) {
                $ped->status = 2;
            }

            $eje = $this->ejercicio->get_by_fecha($ped->fecha);
            if ($eje) {
                $ped->codejercicio = $eje->codejercicio;

                $regimeniva = 'Exento';
                if (mt_rand(0, 14) > 0 AND isset($clientes[$num])) {
                    $ped->codcliente = $clientes[$num]->codcliente;
                    $ped->nombrecliente = $clientes[$num]->razonsocial;
                    $ped->cifnif = $clientes[$num]->cifnif;
                    $regimeniva = $clientes[$num]->regimeniva;

                    foreach ($clientes[$num]->get_direcciones() as $dir) {
                        if ($dir->domfacturacion) {
                            $ped->codpais = $dir->codpais;
                            $ped->provincia = $dir->provincia;
                            $ped->ciudad = $dir->ciudad;
                            $ped->direccion = $dir->direccion;
                            $ped->codpostal = $dir->codpostal;
                            $ped->apartado = $dir->apartado;
                        }

                        if ($dir->domenvio AND mt_rand(0, 2) == 0) {
                            $ped->envio_nombre = $this->nombre();
                            $ped->envio_apellidos = $this->apellidos();
                            $ped->envio_codpais = $dir->codpais;
                            $ped->envio_provincia = $dir->provincia;
                            $ped->envio_ciudad = $dir->ciudad;
                            $ped->envio_direccion = $dir->direccion;
                            $ped->envio_apartado = $dir->apartado;
                        }
                    }
                } else {
                    /// de vez en cuando creamos uno sin cliente, por joder ;-)
                    $ped->nombrecliente = $this->nombre() . ' ' . $this->apellidos();
                    $ped->cifnif = mt_rand(1, 99999999);
                }

                if (mt_rand(0, 3) == 0) {
                    $ped->fechasalida = date('d-m-Y', strtotime($ped->fecha . ' +' . mt_rand(1, 3) . ' months'));
                }

                if ($ped->save()) {
                    $articulos = $this->random_articulos();

                    $numlineas = $this->cantidad(0, 10, 200);
                    while ($numlineas > 0) {
                        $lin = new linea_pedido_cliente();
                        $lin->idpedido = $ped->idpedido;
                        $lin->cantidad = $this->cantidad(1, 3, 19);
                        $lin->descripcion = $this->descripcion();
                        $lin->pvpunitario = $this->precio(1, 49, 699);
                        $lin->codimpuesto = $this->impuestos[0]->codimpuesto;
                        $lin->iva = $this->impuestos[0]->iva;

                        if ($recargo AND mt_rand(0, 2) == 0) {
                            $lin->recargo = $this->impuestos[0]->recargo;
                        }

                        if (isset($articulos[$numlineas])) {
                            if ($articulos[$numlineas]->sevende) {
                                $lin->referencia = $articulos[$numlineas]->referencia;
                                $lin->descripcion = $articulos[$numlineas]->descripcion;
                                $lin->pvpunitario = $articulos[$numlineas]->pvp;
                                $lin->codimpuesto = $articulos[$numlineas]->codimpuesto;
                                $lin->iva = $articulos[$numlineas]->get_iva();
                                $lin->recargo = 0;
                            }
                        }

                        $lin->irpf = $ped->irpf;

                        if ($regimeniva == 'Exento') {
                            $lin->codimpuesto = NULL;
                            $lin->iva = 0;
                            $lin->recargo = 0;
                            $ped->irpf = $lin->irpf = 0;
                        }

                        if (mt_rand(0, 4) == 0) {
                            $lin->dtopor = $this->cantidad(0, 33, 100);
                        }

                        $lin->pvpsindto = ($lin->pvpunitario * $lin->cantidad);
                        $lin->pvptotal = $lin->pvpunitario * $lin->cantidad * (100 - $lin->dtopor) / 100;

                        if ($lin->save()) {
                            $ped->neto += $lin->pvptotal;
                            $ped->totaliva += ($lin->pvptotal * $lin->iva / 100);
                            $ped->totalirpf += ($lin->pvptotal * $lin->irpf / 100);
                            $ped->totalrecargo += ($lin->pvptotal * $lin->recargo / 100);
                        }

                        $numlineas--;
                    }

                    /// redondeamos
                    $ped->neto = round($ped->neto, FS_NF0);
                    $ped->totaliva = round($ped->totaliva, FS_NF0);
                    $ped->totalirpf = round($ped->totalirpf, FS_NF0);
                    $ped->totalrecargo = round($ped->totalrecargo, FS_NF0);
                    $ped->total = $ped->neto + $ped->totaliva - $ped->totalirpf + $ped->totalrecargo;
                    $ped->save();

                    $num++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $num;
    }

    /**
     * Genera $max pedidos de compra aleatorios.
     * Devuelve el número de pedidos generados.
     * @param type $max
     * @return int
     */
    public function pedidosprov($max = 25)
    {
        $num = 0;
        $proveedores = $this->random_proveedores();

        $recargo = FALSE;
        if (mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $ped = new pedido_proveedor();
            $ped->fecha = mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y'));
            $ped->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
            $ped->codpago = $this->formas_pago[0]->codpago;

            if (mt_rand(0, 2) == 0) {
                $ped->coddivisa = $this->divisas[0]->coddivisa;
                $ped->tasaconv = $this->divisas[0]->tasaconv_compra;
            } else {
                foreach ($this->divisas as $div) {
                    if ($div->coddivisa == $this->empresa->coddivisa) {
                        $ped->coddivisa = $div->coddivisa;
                        $ped->tasaconv = $div->tasaconv_compra;
                        break;
                    }
                }
            }

            $ped->codalmacen = $this->empresa->codalmacen;
            if (mt_rand(0, 2) == 0) {
                $ped->codalmacen = $this->almacenes[0]->codalmacen;
            }

            $ped->codserie = $this->empresa->codserie;
            if (mt_rand(0, 2) == 0) {
                if ($this->series[0]->codserie != 'R') {
                    $ped->codserie = $this->series[0]->codserie;
                    $ped->irpf = $this->series[0]->irpf;
                }

                $ped->observaciones = $this->observaciones($ped->fecha);
                $ped->numproveedor = mt_rand(10, 99999);
            }

            $ped->codagente = $this->agentes[0]->codagente;
            if (mt_rand(0, 4) == 0) {
                $ped->codagente = NULL;
            }

            $eje = $this->ejercicio->get_by_fecha($ped->fecha);
            if ($eje) {
                $ped->codejercicio = $eje->codejercicio;

                $regimeniva = 'Exento';
                if (mt_rand(0, 14) > 0 AND isset($proveedores[$num])) {
                    $ped->codproveedor = $proveedores[$num]->codproveedor;
                    $ped->nombre = $proveedores[$num]->razonsocial;
                    $ped->cifnif = $proveedores[$num]->cifnif;
                    $regimeniva = $proveedores[$num]->regimeniva;
                } else {
                    /// de vez encuendo generamos un pedido son proveedor, para ver si peta todo ;-)
                    $ped->nombre = $this->nombre();
                    $ped->cifnif = mt_rand(111111, 999999999) . 'X';
                }

                if ($ped->save()) {
                    $articulos = $this->random_articulos();

                    $numlineas = $this->cantidad(0, 10, 400);
                    while ($numlineas > 0) {
                        $lin = new linea_pedido_proveedor();
                        $lin->idpedido = $ped->idpedido;
                        $lin->cantidad = $this->cantidad(1, 3, 19);
                        $lin->descripcion = $this->descripcion();
                        $lin->pvpunitario = $this->precio(1, 49, 699);
                        $lin->codimpuesto = $this->impuestos[0]->codimpuesto;
                        $lin->iva = $this->impuestos[0]->iva;

                        if ($recargo AND mt_rand(0, 2) == 0) {
                            $lin->recargo = $this->impuestos[0]->recargo;
                        }

                        if (isset($articulos[$numlineas])) {
                            if ($articulos[$numlineas]->sevende) {
                                $lin->referencia = $articulos[$numlineas]->referencia;
                                $lin->descripcion = $articulos[$numlineas]->descripcion;
                                $lin->pvpunitario = $articulos[$numlineas]->pvp;
                                $lin->codimpuesto = $articulos[$numlineas]->codimpuesto;
                                $lin->iva = $articulos[$numlineas]->get_iva();
                                $lin->recargo = 0;
                            }
                        }

                        $lin->irpf = $ped->irpf;

                        if ($regimeniva == 'Exento') {
                            $lin->codimpuesto = NULL;
                            $lin->iva = 0;
                            $lin->recargo = 0;
                            $ped->irpf = $lin->irpf = 0;
                        }

                        if (mt_rand(0, 4) == 0) {
                            $lin->dtopor = $this->cantidad(0, 33, 100);
                        }

                        $lin->pvpsindto = ($lin->pvpunitario * $lin->cantidad);
                        $lin->pvptotal = $lin->pvpunitario * $lin->cantidad * (100 - $lin->dtopor) / 100;

                        if ($lin->save()) {
                            $ped->neto += $lin->pvptotal;
                            $ped->totaliva += ($lin->pvptotal * $lin->iva / 100);
                            $ped->totalirpf += ($lin->pvptotal * $lin->irpf / 100);
                            $ped->totalrecargo += ($lin->pvptotal * $lin->recargo / 100);
                        }

                        $numlineas--;
                    }

                    /// redondeamos
                    $ped->neto = round($ped->neto, FS_NF0);
                    $ped->totaliva = round($ped->totaliva, FS_NF0);
                    $ped->totalirpf = round($ped->totalirpf, FS_NF0);
                    $ped->totalrecargo = round($ped->totalrecargo, FS_NF0);
                    $ped->total = $ped->neto + $ped->totaliva - $ped->totalirpf + $ped->totalrecargo;
                    $ped->save();

                    $num++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $num;
    }

    /**
     * Genera $max presupuestos de venta aleatorios.
     * Devuelve el número de presupuestos generados.
     * @param type $max
     * @return int
     */
    public function presupuestoscli($max = 25)
    {
        $num = 0;
        $clientes = $this->random_clientes();

        $recargo = FALSE;
        if ($clientes[0]->recargo OR mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $presu = new presupuesto_cliente();
            $presu->fecha = mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y'));
            $presu->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
            $presu->codpago = $this->formas_pago[0]->codpago;

            if (mt_rand(0, 2) == 0) {
                $presu->coddivisa = $this->divisas[0]->coddivisa;
                $presu->tasaconv = $this->divisas[0]->tasaconv;
            } else {
                foreach ($this->divisas as $div) {
                    if ($div->coddivisa == $this->empresa->coddivisa) {
                        $presu->coddivisa = $div->coddivisa;
                        $presu->tasaconv = $div->tasaconv;
                        break;
                    }
                }
            }

            $presu->codalmacen = $this->empresa->codalmacen;
            if (mt_rand(0, 2) == 0) {
                $presu->codalmacen = $this->almacenes[0]->codalmacen;
            }

            $presu->codserie = $this->empresa->codserie;
            if (mt_rand(0, 2) == 0) {
                if ($this->series[0]->codserie != 'R') {
                    $presu->codserie = $this->series[0]->codserie;
                    $presu->irpf = $this->series[0]->irpf;
                }

                $presu->observaciones = $this->observaciones($presu->fecha);
                $presu->numero2 = mt_rand(10, 99999);
            }

            $presu->codagente = $this->agentes[0]->codagente;
            if (mt_rand(0, 4) == 0) {
                $presu->codagente = NULL;
            }

            $eje = $this->ejercicio->get_by_fecha($presu->fecha);
            if ($eje) {
                $presu->codejercicio = $eje->codejercicio;
                $presu->finoferta = date('d-m-Y', strtotime($presu->fecha . ' +' . mt_rand(1, 18) . ' months'));

                $regimeniva = 'Exento';
                if (mt_rand(0, 14) > 0 AND isset($clientes[$num])) {
                    $presu->codcliente = $clientes[$num]->codcliente;
                    $presu->nombrecliente = $clientes[$num]->razonsocial;
                    $presu->cifnif = $clientes[$num]->cifnif;
                    $regimeniva = $clientes[$num]->regimeniva;

                    foreach ($clientes[$num]->get_direcciones() as $dir) {
                        if ($dir->domfacturacion) {
                            $presu->codpais = $dir->codpais;
                            $presu->provincia = $dir->provincia;
                            $presu->ciudad = $dir->ciudad;
                            $presu->direccion = $dir->direccion;
                            $presu->codpostal = $dir->codpostal;
                            $presu->apartado = $dir->apartado;
                        }

                        if ($dir->domenvio AND mt_rand(0, 2) == 0) {
                            $presu->envio_nombre = $this->nombre();
                            $presu->envio_apellidos = $this->apellidos();
                            $presu->envio_codpais = $dir->codpais;
                            $presu->envio_provincia = $dir->provincia;
                            $presu->envio_ciudad = $dir->ciudad;
                            $presu->envio_codpostal = $dir->codpostal;
                            $presu->envio_direccion = $dir->direccion;
                            $presu->envio_apartado = $dir->apartado;
                        }
                    }
                } else {
                    /// de vez en cuando creamos uno sin cliente, por joder ;-)
                    $presu->nombrecliente = $this->empresa();
                    $presu->cifnif = '';
                }

                if ($presu->save()) {
                    $articulos = $this->random_articulos();

                    $numlineas = $this->cantidad(0, 10, 200);
                    while ($numlineas > 0) {
                        $lin = new linea_presupuesto_cliente();
                        $lin->idpresupuesto = $presu->idpresupuesto;
                        $lin->cantidad = $this->cantidad(1, 3, 19);
                        $lin->descripcion = $this->descripcion();
                        $lin->pvpunitario = $this->precio(1, 49, 699);
                        $lin->codimpuesto = $this->impuestos[0]->codimpuesto;
                        $lin->iva = $this->impuestos[0]->iva;

                        if ($recargo AND mt_rand(0, 2) == 0) {
                            $lin->recargo = $this->impuestos[0]->recargo;
                        }

                        if (isset($articulos[$numlineas])) {
                            if ($articulos[$numlineas]->sevende) {
                                $lin->referencia = $articulos[$numlineas]->referencia;
                                $lin->descripcion = $articulos[$numlineas]->descripcion;
                                $lin->pvpunitario = $articulos[$numlineas]->pvp;
                                $lin->codimpuesto = $articulos[$numlineas]->codimpuesto;
                                $lin->iva = $articulos[$numlineas]->get_iva();
                                $lin->recargo = 0;
                            }
                        }

                        $lin->irpf = $presu->irpf;

                        if ($regimeniva == 'Exento') {
                            $lin->codimpuesto = NULL;
                            $lin->iva = 0;
                            $lin->recargo = 0;
                            $presu->irpf = $lin->irpf = 0;
                        }

                        if (mt_rand(0, 4) == 0) {
                            $lin->dtopor = $this->cantidad(0, 33, 100);
                        }

                        $lin->pvpsindto = ($lin->pvpunitario * $lin->cantidad);
                        $lin->pvptotal = $lin->pvpunitario * $lin->cantidad * (100 - $lin->dtopor) / 100;

                        if ($lin->save()) {
                            $presu->neto += $lin->pvptotal;
                            $presu->totaliva += ($lin->pvptotal * $lin->iva / 100);
                            $presu->totalirpf += ($lin->pvptotal * $lin->irpf / 100);
                            $presu->totalrecargo += ($lin->pvptotal * $lin->recargo / 100);
                        }

                        $numlineas--;
                    }

                    /// redondeamos
                    $presu->neto = round($presu->neto, FS_NF0);
                    $presu->totaliva = round($presu->totaliva, FS_NF0);
                    $presu->totalirpf = round($presu->totalirpf, FS_NF0);
                    $presu->totalrecargo = round($presu->totalrecargo, FS_NF0);
                    $presu->total = $presu->neto + $presu->totaliva - $presu->totalirpf + $presu->totalrecargo;
                    $presu->save();

                    $num++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $num;
    }

    /**
     * Genera $max servicios aleatorios.
     * Devuelve el número de servicios generados.
     * @param type $max
     * @return int
     */
    public function servicioscli($max = 25)
    {
        $num = 0;
        $clientes = $this->random_clientes();

        $estado0 = new estado_servicio();
        $estados = $estado0->all();
        shuffle($estados);

        $recargo = FALSE;
        if ($clientes[0]->recargo OR mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $serv = new servicio_cliente();
            $serv->fecha = mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y'));
            $serv->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
            $serv->codalmacen = $this->empresa->codalmacen;
            $serv->codpago = $this->empresa->codpago;
            $serv->codserie = $this->empresa->codserie;

            foreach ($this->divisas as $div) {
                if ($div->coddivisa == $this->empresa->coddivisa) {
                    $serv->coddivisa = $div->coddivisa;
                    $serv->tasaconv = $div->tasaconv;
                    break;
                }
            }

            if (mt_rand(0, 2) == 0) {
                $serv->codagente = $this->agentes[0]->codagente;
                $serv->codalmacen = $this->almacenes[0]->codalmacen;
                $serv->codpago = $this->formas_pago[0]->codpago;
                $serv->coddivisa = $this->divisas[0]->coddivisa;
                $serv->tasaconv = $this->divisas[0]->tasaconv;

                if ($this->series[0]->codserie != 'R') {
                    $serv->codserie = $this->series[0]->codserie;
                    $serv->irpf = $this->series[0]->irpf;
                }

                $serv->observaciones = $this->observaciones($serv->fecha);
                $serv->numero2 = mt_rand(10, 99999);
            }

            $serv->material = $this->observaciones();
            $serv->material_estado = $this->observaciones();
            $serv->accesorios = $this->observaciones();
            $serv->descripcion = $this->observaciones();
            $serv->solucion = $this->observaciones();

            $eje = $this->ejercicio->get_by_fecha($serv->fecha);
            if ($eje) {
                $serv->codejercicio = $eje->codejercicio;
                $serv->fechainicio = Date('d-m-Y H:i', strtotime($serv->fecha . ' +' . mt_rand(1, 18) . ' days'));
                $serv->fechafin = date('Y-m-d H:i', strtotime($serv->fechainicio . ' +' . mt_rand(10, 59) . ' minutes'));
                $serv->idestado = $estados[0]->id;
                $serv->garantia = ( mt_rand(0, 1) == 1 );
                $serv->prioridad = mt_rand(1, 4);

                $regimeniva = 'Exento';
                if (mt_rand(0, 14) > 0 AND isset($clientes[$num])) {
                    $serv->codcliente = $clientes[$num]->codcliente;
                    $serv->nombrecliente = $clientes[$num]->razonsocial;
                    $serv->cifnif = $clientes[$num]->cifnif;
                    $regimeniva = $clientes[$num]->regimeniva;

                    foreach ($clientes[$num]->get_direcciones() as $dir) {
                        $serv->codpais = $dir->codpais;
                        $serv->provincia = $dir->provincia;
                        $serv->ciudad = $dir->ciudad;
                        $serv->direccion = $dir->direccion;
                        $serv->codpostal = $dir->codpostal;
                        if ($dir->domfacturacion) {
                            break;
                        }
                    }
                } else {
                    /// de vez en cuando creamos uno sin cliente asociado
                    $serv->nombrecliente = $this->empresa();
                    $serv->cifnif = '';
                }

                if ($serv->save()) {
                    $articulos = $this->random_articulos();

                    $numlineas = $this->cantidad(0, 10, 200);
                    while ($numlineas > 0) {
                        $lin = new linea_servicio_cliente();
                        $lin->idservicio = $serv->idservicio;
                        $lin->cantidad = $this->cantidad(1, 3, 19);
                        $lin->descripcion = $this->descripcion();
                        $lin->pvpunitario = $this->precio(1, 49, 699);
                        $lin->codimpuesto = $this->impuestos[0]->codimpuesto;
                        $lin->iva = $this->impuestos[0]->iva;

                        if ($recargo AND mt_rand(0, 2) == 0) {
                            $lin->recargo = $this->impuestos[0]->recargo;
                        }

                        if (isset($articulos[$numlineas])) {
                            if ($articulos[$numlineas]->sevende) {
                                $lin->referencia = $articulos[$numlineas]->referencia;
                                $lin->descripcion = $articulos[$numlineas]->descripcion;
                                $lin->pvpunitario = $articulos[$numlineas]->pvp;
                                $lin->codimpuesto = $articulos[$numlineas]->codimpuesto;
                                $lin->iva = $articulos[$numlineas]->get_iva();
                                $lin->recargo = 0;
                            }
                        }

                        $lin->irpf = $serv->irpf;

                        if ($regimeniva == 'Exento') {
                            $lin->codimpuesto = NULL;
                            $lin->iva = 0;
                            $lin->recargo = 0;
                            $serv->irpf = $lin->irpf = 0;
                        }

                        if (mt_rand(0, 4) == 0) {
                            $lin->dtopor = $this->cantidad(0, 33, 100);
                        }

                        $lin->pvpsindto = ($lin->pvpunitario * $lin->cantidad);
                        $lin->pvptotal = $lin->pvpunitario * $lin->cantidad * (100 - $lin->dtopor) / 100;

                        if ($lin->save()) {
                            $serv->neto += $lin->pvptotal;
                            $serv->totaliva += ($lin->pvptotal * $lin->iva / 100);
                            $serv->totalirpf += ($lin->pvptotal * $lin->irpf / 100);
                            $serv->totalrecargo += ($lin->pvptotal * $lin->recargo / 100);
                        }

                        $numlineas--;
                    }

                    /// redondeamos
                    $serv->neto = round($serv->neto, FS_NF0);
                    $serv->totaliva = round($serv->totaliva, FS_NF0);
                    $serv->totalirpf = round($serv->totalirpf, FS_NF0);
                    $serv->totalrecargo = round($serv->totalrecargo, FS_NF0);
                    $serv->total = $serv->neto + $serv->totaliva - $serv->totalirpf + $serv->totalrecargo;
                    $serv->save();

                    $num++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $num;
    }

    /**
     * Devuelve unas observaciones aleatorias.
     * @param type $fecha
     * @return string
     */
    protected function observaciones($fecha = FALSE)
    {
        $observaciones = array(
            'Pagado', 'Faltan piezas', 'No se corresponde con lo solicitado.',
            'Muy caro', 'Muy barato', 'Mala calidad',
            'La parte contratante de la primera parte será la parte contratante de la primera parte.'
        );

        /// añadimos muchos blas como otra opción
        $bla = 'Bla';
        while (mt_rand(0, 29) > 0) {
            $bla .= ', bla';
        }
        $observaciones[] = $bla . '.';

        /// randomizamos (es posible que me haya inventado esta palabra)
        shuffle($observaciones);

        if ($fecha AND mt_rand(0, 2) == 0) {
            $semana = date("D", strtotime($fecha));
            $semanaArray = array(
                "Mon" => "lunes", "Tue" => "martes", "Wed" => "miércoles", "Thu" => "jueves",
                "Fri" => "viernes", "Sat" => "sábado", "Sun" => "domingo",
            );
            $title = urlencode(sprintf('{{Plantilla:Frase-%s}}', $semanaArray[$semana]));
            $sock = @fopen("http://es.wikiquote.org/w/api.php?action=parse&format=php&text=$title", "r");
            if (!$sock) {
                return $observaciones[0];
            } else {
                # Hacemos la peticion al servidor
                $array__ = unserialize(stream_get_contents($sock));
                $texto_final = strip_tags($array__["parse"]["text"]["*"]);
                $texto_final = str_replace("\n\n\n\n", "\n", $texto_final);

                return $texto_final;
            }
        } else {
            return $observaciones[0];
        }
    }

    /**
     * Devuelve un string aleatorio de longitud $length
     * @param type $length la longitud del string
     * @return type la cadena aleatoria
     */
    protected function random_string($length = 30)
    {
        return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    /**
     * Devuelve un array con clientes aleatorios.
     * @param type $recursivo
     * @return \cliente
     */
    protected function random_clientes($recursivo = TRUE)
    {
        $lista = array();

        $sql = "SELECT * FROM clientes ORDER BY random()";
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $sql = "SELECT * FROM clientes ORDER BY RAND()";
        }

        $data = $this->db->select_limit($sql, 100, 0);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new cliente($d);
            }
        } else if ($recursivo) {
            $this->clientes();
            $lista = $this->random_clientes(FALSE);
        }

        return $lista;
    }

    /**
     * Devuelve un array con proveedores aleatorios.
     * @param type $recursivo
     * @return \proveedor
     */
    protected function random_proveedores($recursivo = TRUE)
    {
        $lista = array();

        $sql = "SELECT * FROM proveedores ORDER BY random()";
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $sql = "SELECT * FROM proveedores ORDER BY RAND()";
        }

        $data = $this->db->select_limit($sql, 100, 0);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new proveedor($d);
            }
        } else if ($recursivo) {
            $this->proveedores();
            return $this->random_proveedores(FALSE);
        }

        return $lista;
    }

    /**
     * Devuelve un array con empleados aleatorios.
     * @param type $recursivo
     * @return \agente
     */
    protected function random_agentes($recursivo = TRUE)
    {
        $lista = array();

        $sql = "SELECT * FROM agentes ORDER BY random()";
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $sql = "SELECT * FROM agentes ORDER BY RAND()";
        }

        $data = $this->db->select_limit($sql, 100, 0);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new agente($d);
            }
        } else if ($recursivo) {
            $this->agentes();
            return $this->random_agentes(FALSE);
        }

        return $lista;
    }

    /**
     * Devuelve un array con artículos aleatorios.
     * @param type $recursivo
     * @return \articulo
     */
    protected function random_articulos($recursivo = TRUE)
    {
        $lista = array();

        $sql = "SELECT * FROM articulos ORDER BY random()";
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $sql = "SELECT * FROM articulos ORDER BY RAND()";
        }

        $data = $this->db->select_limit($sql, 100, 0);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new articulo($d);
            }
        } else if ($recursivo) {
            $this->articulos();
            return $this->random_articulos(FALSE);
        }

        return $lista;
    }
}
