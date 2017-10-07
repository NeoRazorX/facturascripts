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

define('FS_NF0', 2);
define('FS_NF0_ART', 2);
define('FS_STOCK_NEGATIVO', true);

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
        $this->loadData($this->agentes, new Model\Agente(), TRUE);
        $this->loadData($this->almacenes, new Model\Almacen(), TRUE);
        $this->loadData($this->divisas, new Model\Divisa(), TRUE);
        $this->loadData($this->formas_pago, new Model\FormaPago(), TRUE);
        $this->loadData($this->grupos, new Model\GrupoClientes(), FALSE);
        $this->loadData($this->impuestos, new Model\Impuesto(), TRUE);
        $this->loadData($this->paises, new Model\Pais(), TRUE);
        $this->loadData($this->series, new Model\Serie(), TRUE);
        $this->loadData($this->users, new Model\User(), FALSE);
    }

    /**
     * Metodo de apoyo para el constructor de modelos e inicializacion de datos
     * @param array $variable    -> destino de los datos
     * @param fs_model $modelo   -> modelo de cada uno de los items del array
     * @param boolean $shuffle   -> ordenar aleatoriamente la lista
     */
    private function loadData(&$variable, $modelo, $shuffle)
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
        $result = str_replace([' ', '-', '_', '&', 'ó', ':', 'ñ', '"', "'", '*'], ['', '', '', '', 'O', '', 'N', '', '', '-'], strtoupper($txt));

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
        $prefijos = [
            'Jet', 'Jex', 'Max', 'Pro', 'FX', 'Neo', 'Maxi', 'Extreme', 'Sub',
            'Ultra', 'Minga', 'Hiper', 'Giga', 'Mega', 'Super', 'Fusion', 'Broken'
        ];
        shuffle($prefijos);

        $nombres = [
            'Motor', 'Engine', 'Generator', 'Tool', 'Oviode', 'Box', 'Proton', 'Neutro',
            'Radeon', 'GeForce', 'nForce', 'Labtech', 'Station', 'Arco', 'Arkam'
        ];
        shuffle($nombres);

        $sufijos = [
            'II', '3', 'XL', 'XXL', 'SE', 'GT', 'GTX', 'Pro', 'NX', 'XP', 'OS', 'Nitro'
        ];
        shuffle($sufijos);

        $descripciones1 = [
            'Una alcachofa', 'Un motor', 'Una targeta gráfica (GPU)', 'Un procesador',
            'Un coche', 'Un dispositivo tecnológico', 'Un magnetofón', 'Un palo',
            'un cubo de basura', "Un objeto pequeño d'or", '"La hostia"'
        ];
        shuffle($descripciones1);

        $descripciones2 = [
            '64 núcleos', 'chasis de fibra de carbono', '8 cilindros en V', 'frenos de berilio',
            '16 ejes', 'pantalla Super AMOLED', '1024 stream processors', 'un núcleo híbrido',
            '32 pistones digitales', 'tecnología digitrónica 4.1', 'cuernos metálicos', 'un palo',
            'memoria HBM', 'taladro matricial', 'Wifi 4G', 'faros de xenon', 'un ambientador de pino',
            'un posavasos', 'malignas intenciones', 'la virginidad intacta', 'malware', 'linux',
            'Windows Vista', 'propiedades psicotrópicas', 'spyware', 'reproductor 4k'
        ];
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
     * @param int $min
     * @param int $max1
     * @param int $max2
     * @return float
     */
    protected function cantidad($min, $max1, $max2)
    {
        $cantidad = mt_rand($min, $max1);

        if (mt_rand(0, 9) == 0) {
            $cantidad = mt_rand($min, $max2);
        } else if ($cantidad < $max1 && mt_rand(0, 4) == 0) {
            $cantidad += round(mt_rand(1, 5) / mt_rand(1, 10), mt_rand(0, 3));
            $cantidad = min([$max1, $cantidad]);
        }

        return $cantidad;
    }

    /**
     * Devuelve un número aleatorio entre $min y $max1.
     * 1 de cada 10 veces lo devuelve entre $min y $max2.
     * 1 de cada 3 veces lo devuelve con decimales.
     * @param int $min
     * @param int $max1
     * @param int $max2
     * @return float
     */
    protected function precio($min, $max1, $max2)
    {
        $precio = mt_rand($min, $max1);

        if (mt_rand(0, 9) == 0) {
            $precio = mt_rand($min, $max2);
        } else if ($precio < $max1 && mt_rand(0, 2) == 0) {
            $precio += round(mt_rand(1, 5) / mt_rand(1, 10), FS_NF0_ART);
            $precio = min([$max1, $precio]);
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
     * @param int $max
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
                $cargos = ['Gerente', 'CEO', 'Compras', 'Comercial', 'Técnico', 'Freelance', 'Becario', 'Becario Senior'];
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
     * @param int $max
     * @return int
     */
    public function gruposClientes($max = 50)
    {
        $nombres = [
            'Profesionales', 'Profesional', 'Grandes compradores', 'Preferentes',
            'Basico', 'Premium', 'Variado', 'Reservado', 'Técnico', 'Elemental'
        ];
        $nombres2 = ['VIP', 'PRO', 'NEO', 'XL', 'XXL', '50 aniversario', 'C', 'Z'];

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
     * @param int $max
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

            if (mt_rand(0, 2) > 0 && $this->grupos) {
                shuffle($this->grupos);
                $cliente->codgrupo = $this->grupos[0]->codgrupo;
            } else {
                $cliente->codgrupo = NULL;
            }

            $cliente->codcliente = $cliente->newCode();
            if (!$cliente->save()) {
                break;
            }

            /// añadimos direcciones
            $num_dirs = mt_rand(0, 3);
            while ($num_dirs > 0) {
                $dir = new Model\DireccionCliente();
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
                $cuenta = new Model\CuentaBancoCliente();
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
        }

        return $num;
    }

    /**
     * Genera $max proveedores aleatorios.
     * Devuelve el número de proveedores generados.
     * @param int $max
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

                /// añadimos direcciones
                $num_dirs = mt_rand(0, 3);
                while ($num_dirs) {
                    $dir = new Model\DireccionProveedor();
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
                    $cuenta = new Model\CuentaBancoProveedor();
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
        $nombres = [
            'Carlos', 'Pepe', 'Wilson', 'Petra', 'Madonna', 'Justin',
            'Emiliana', 'Jo', 'Penélope', 'Mia', 'Wynona', 'Antonio',
            'Joe', 'Cristiano', 'Mohamed', 'John', 'Ali', 'Pastor',
            'Barak', 'Sadam', 'Donald', 'Jorge', 'Joel', 'Pedro', 'Mariano',
            'Albert', 'Alberto', 'Gorka', 'Cecilia', 'Carmena', 'Pichita',
            'Alicia', 'Laura', 'Riola', 'Wilson', 'Jaume', 'David',
            "D'Ambrosio", '"El nota"', '"El master"'
        ];

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve dos apellidos aleatorios.
     * @return type
     */
    protected function apellidos()
    {
        $apellidos = [
            'García', 'Gómez', 'Ronaldo', 'Suarez', 'Wilson', 'Pacheco',
            'Escobar', 'Mendoza', 'Pérez', 'Cruz', 'Lee', 'Smith', 'Humilde',
            'Hijo de Dios', 'Petrov', 'Maximiliano', 'Nieve', 'Snow', 'Trump',
            'Obama', 'Ali', 'Stark', 'Sanz', 'Rajoy', 'Sánchez', 'Iglesias',
            'Rivera', 'Tumor', 'Lanister', 'Suarez', 'Aznar', 'Botella',
            'Errejón', "D'Ambrosio", 'Peña'
        ];

        shuffle($apellidos);
        return $apellidos[0] . ' ' . $apellidos[1];
    }

    /**
     * Devuelve un nombre comercial aleatorio.
     * @return type
     */
    protected function empresa()
    {
        $nombres = [
            'Tech', 'Motor', 'Pasión', 'Future', 'Max', 'Massive', 'Industrial',
            'Plastic', 'Pro', 'Micro', 'System', 'Light', 'Magic', 'Fake', 'Techno',
            'Miracle', 'NX', 'Smoke', 'Steam', 'Power', 'FX', 'Fusion', 'Bastion',
            'Investments', 'Solutions', 'Neo', 'Ming', 'Tube', 'Pear', 'Apple',
            'Dolphin', 'Chrome', 'Cat', 'Hat', 'Linux', 'Soft', 'Mobile', 'Phone',
            'XL', 'Open', 'Thunder', 'Zero', 'Scorpio', 'Zelda', '10', 'V', 'Q',
            'X', 'Arch', 'Arco', 'Broken', 'Arkam', 'RX', "d'Art", 'Peña', '"La cosa"'
        ];

        $separador = ['-', ' & ', ' ', '_', '', '/', '*'];
        $tipo = ['S.L.', 'S.A.', 'Inc.', 'LTD', 'Corp.'];

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
        $nicks = [
            'neo', 'carlos', 'moko', 'snake', 'pikachu', 'pliskin', 'ocelot', 'samurai',
            'ninja', 'penetrator', 'info', 'compras', 'ventas', 'administracion', 'contacto',
            'contact', 'invoices', 'mail'
        ];

        shuffle($nicks);
        return $nicks[0] . '.' . mt_rand(2, 9999) . '@facturascripts.com';
    }

    /**
     * Devuelve una provincia aleatoria.
     * @return string
     */
    protected function provincia()
    {
        $nombres = [
            'A Coruña', 'Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
            'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Córdoba', 'Cuenca',
            'Girona', 'Granada', 'Guadalajara', 'Guipuzcoa', 'Huelva', 'Huesca', 'Jaen', 'León', 'Lleida', 'La Rioja',
            'Lugo', 'Madrid', 'Málaga', 'Melilla', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Las Palmas', 'Pontevedra',
            'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Tenerife', 'Teruel', 'Toledo', 'Valencia',
            'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'
        ];

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve una ciudad aleatoria.
     * @return string
     */
    protected function ciudad()
    {
        $nombres = [
            'A Coruña', 'Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
            'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Córdoba', 'Cuenca',
            'Girona', 'Granada', 'Guadalajara', 'Guipuzcoa', 'Huelva', 'Huesca', 'Jaen', 'León', 'Lleida', 'La Rioja',
            'Lugo', 'Madrid', 'Málaga', 'Melilla', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Las Palmas', 'Pontevedra',
            'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Tenerife', 'Teruel', 'Toledo', 'Valencia',
            'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza', 'Torrevieja', 'Elche'
        ];

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve una dirección aleatoria.
     * @return type
     */
    protected function direccion()
    {
        $tipos = ['Calle', 'Avenida', 'Polígono', 'Carretera'];
        $nombres = [
            'Infante', 'Principal', 'Falsa', '58', '74', 'Pacheco', 'Baleares',
            'Del Pacífico', 'Rue', "d'Ambrosio", 'Bañez', '"La calle"'
        ];

        shuffle($tipos);
        shuffle($nombres);

        if (mt_rand(0, 2) == 0) {
            return $tipos[0] . ' ' . $nombres[0] . ', nº' . mt_rand(1, 199) . ', puerta ' . mt_rand(1, 99);
        }

        return $tipos[0] . ' ' . $nombres[0] . ', ' . mt_rand(1, 99);
    }

    private function randomizeDocument(&$doc)
    {
        $doc->fecha = mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y'));
        $doc->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
        $doc->codpago = $this->formas_pago[0]->codpago;

        if (mt_rand(0, 2) == 0) {
            $doc->coddivisa = $this->divisas[0]->coddivisa;
            $doc->tasaconv = $this->divisas[0]->tasaconv;
        } else {
            foreach ($this->divisas as $div) {
                if ($div->coddivisa == $this->empresa->coddivisa) {
                    $doc->coddivisa = $div->coddivisa;
                    $doc->tasaconv = $div->tasaconv;
                    break;
                }
            }
        }

        $doc->codalmacen = $this->empresa->codalmacen;
        if (mt_rand(0, 2) == 0) {
            $doc->codalmacen = $this->almacenes[0]->codalmacen;
        }

        $doc->codserie = $this->empresa->codserie;
        if (mt_rand(0, 2) == 0) {
            if ($this->series[0]->codserie != 'R') {
                $doc->codserie = $this->series[0]->codserie;
                $doc->irpf = $this->series[0]->irpf;
            }

            $doc->observaciones = $this->observaciones($doc->fecha);
        }

        if (isset($doc->numero2) && mt_rand(0, 4) == 0) {
            $doc->numero2 = mt_rand(10, 99999);
        } else if (isset($doc->numproveedor) && mt_rand(0, 4) == 0) {
            $alb->numproveedor = mt_rand(10, 99999);
        }

        if (isset($doc->status) && mt_rand(0, 5) == 0) {
            $doc->status = 2;
        }

        $doc->codagente = $this->agentes[0]->codagente;
        if (mt_rand(0, 4) == 0) {
            $doc->codagente = NULL;
        }
    }

    private function randomizeDocumentCompra(&$doc, $eje, $proveedores, $num)
    {
        $doc->codejercicio = $eje->codejercicio;

        $regimeniva = 'Exento';
        if (mt_rand(0, 14) > 0 && isset($proveedores[$num])) {
            $doc->codproveedor = $proveedores[$num]->codproveedor;
            $doc->nombre = $proveedores[$num]->razonsocial;
            $doc->cifnif = $proveedores[$num]->cifnif;
            $regimeniva = $proveedores[$num]->regimeniva;
        } else {
            /// de vez en cuando generamos un sin proveedor, para ver si todo peta ;-)
            $doc->nombre = $this->empresa();
            $doc->cifnif = mt_rand(1111111, 9999999999) . 'Z';
        }

        return $regimeniva;
    }

    private function randomizeDocumentVenta(&$doc, $eje, $clientes, $num)
    {
        $doc->codejercicio = $eje->codejercicio;

        $regimeniva = 'Exento';
        if (mt_rand(0, 14) > 0 && isset($clientes[$num])) {
            $doc->codcliente = $clientes[$num]->codcliente;
            $doc->nombrecliente = $clientes[$num]->razonsocial;
            $doc->cifnif = $clientes[$num]->cifnif;
            $regimeniva = $clientes[$num]->regimeniva;

            foreach ($clientes[$num]->getDirecciones() as $dir) {
                if ($dir->domfacturacion) {
                    $doc->codpais = $dir->codpais;
                    $doc->provincia = $dir->provincia;
                    $doc->ciudad = $dir->ciudad;
                    $doc->direccion = $dir->direccion;
                    $doc->codpostal = $dir->codpostal;
                    $doc->apartado = $dir->apartado;
                }

                if ($dir->domenvio && mt_rand(0, 2) == 0) {
                    $doc->envio_nombre = $this->nombre();
                    $doc->envio_apellidos = $this->apellidos();
                    $doc->envio_codpais = $dir->codpais;
                    $doc->envio_provincia = $dir->provincia;
                    $doc->envio_ciudad = $dir->ciudad;
                    $doc->envio_codpostal = $dir->codpostal;
                    $doc->envio_direccion = $dir->direccion;
                    $doc->envio_apartado = $dir->apartado;
                }
            }
        } else {
            /// de vez en cuando creamos uno sin cliente asociado para ver si todo peta ;-)
            $doc->nombrecliente = $this->nombre() . ' ' . $this->apellidos();
            $doc->cifnif = mt_rand(1111, 999999999) . 'J';
        }

        return $regimeniva;
    }

    private function randomLineas(&$doc, $iddoc = 'idalbaran', $lineaClass = 'FacturaScripts\Dinamic\Model\LineaAlbaranCliente', $regimeniva, $recargo, $modStock = 0)
    {
        $articulos = $this->randomArticulos();

        /// una de cada 15 veces usamos cantidades negativas
        $modcantidad = 1;
        if (mt_rand(0, 4) == 0) {
            $modcantidad = -1;
        }

        $numlineas = $this->cantidad(0, 10, 200);
        while ($numlineas > 0) {
            $lin = new $lineaClass();
            $lin->{$iddoc} = $doc->{$iddoc};
            $lin->cantidad = $modcantidad * $this->cantidad(1, 3, 19);
            $lin->descripcion = $this->descripcion();
            $lin->pvpunitario = $this->precio(1, 49, 699);
            $lin->codimpuesto = $this->impuestos[0]->codimpuesto;
            $lin->iva = $this->impuestos[0]->iva;

            if ($recargo && mt_rand(0, 2) == 0) {
                $lin->recargo = $this->impuestos[0]->recargo;
            }

            if (isset($articulos[$numlineas]) && $articulos[$numlineas]->sevende) {
                $lin->referencia = $articulos[$numlineas]->referencia;
                $lin->descripcion = $articulos[$numlineas]->descripcion;
                $lin->pvpunitario = $articulos[$numlineas]->pvp;
                $lin->codimpuesto = $articulos[$numlineas]->codimpuesto;
                $lin->iva = $articulos[$numlineas]->getIva();
                $lin->recargo = 0;
            }

            $lin->irpf = $doc->irpf;

            if ($regimeniva == 'Exento') {
                $lin->codimpuesto = NULL;
                $lin->iva = 0;
                $lin->recargo = 0;
                $doc->irpf = $lin->irpf = 0;
            }

            if (mt_rand(0, 4) == 0) {
                $lin->dtopor = $this->cantidad(0, 33, 100);
            }

            $lin->pvpsindto = ($lin->pvpunitario * $lin->cantidad);
            $lin->pvptotal = $lin->pvpunitario * $lin->cantidad * (100 - $lin->dtopor) / 100;

            if ($lin->save()) {
                if (isset($articulos[$numlineas])) {
                    /// descontamos del stock
                    $articulos[$numlineas]->sumStock($doc->codalmacen, $lin->cantidad * $modStock);
                }

                $doc->neto += $lin->pvptotal;
                $doc->totaliva += ($lin->pvptotal * $lin->iva / 100);
                $doc->totalirpf += ($lin->pvptotal * $lin->irpf / 100);
                $doc->totalrecargo += ($lin->pvptotal * $lin->recargo / 100);
            }

            $numlineas--;
        }

        /// redondeamos
        $doc->neto = round($doc->neto, FS_NF0);
        $doc->totaliva = round($doc->totaliva, FS_NF0);
        $doc->totalirpf = round($doc->totalirpf, FS_NF0);
        $doc->totalrecargo = round($doc->totalrecargo, FS_NF0);
        $doc->total = $doc->neto + $doc->totaliva - $doc->totalirpf + $doc->totalrecargo;
        $doc->save();
    }

    /**
     * Genera $max albaranes de venta aleatorios.
     * Devuelve el número de albaranes generados.
     * @param int $max
     * @return int
     */
    public function albaranesCliente($max = 25)
    {
        $num = 0;
        $clientes = $this->randomClientes();

        $recargo = FALSE;
        if ($clientes[0]->recargo || mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $alb = new Model\AlbaranCliente();
            $this->randomizeDocument($alb);

            $eje = $this->ejercicio->getByFecha($alb->fecha);
            if ($eje) {
                $regimeniva = $this->randomizeDocumentVenta($alb, $eje, $clientes, $num);

                if ($alb->save()) {
                    $this->randomLineas($alb, 'idalbaran', 'FacturaScripts\Dinamic\Model\LineaAlbaranCliente', $regimeniva, $recargo, -1);
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
     * @param int $max
     * @return int
     */
    public function albaranesProveedor($max = 25)
    {
        $num = 0;
        $proveedores = $this->randomProveedores();

        $recargo = FALSE;
        if (mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $alb = new Model\AlbaranProveedor();
            $this->randomizeDocument($alb);

            $eje = $this->ejercicio->getByFecha($alb->fecha);
            if ($eje) {
                $regimeniva = $this->randomizeDocumentCompra($alb, $eje, $proveedores, $num);

                if ($alb->save()) {
                    $this->randomLineas($alb, 'idalbaran', 'FacturaScripts\Dinamic\Model\LineaAlbaranProveedor', $regimeniva, $recargo, 1);
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
     * @param int $max
     * @return int
     */
    public function pedidosCliente($max = 25)
    {
        $num = 0;
        $clientes = $this->randomClientes();

        $recargo = FALSE;
        if ($clientes[0]->recargo || mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $ped = new Model\PedidoCliente();
            $this->randomizeDocument($ped);

            $eje = $this->ejercicio->getByFecha($ped->fecha);
            if ($eje) {
                $regimeniva = $this->randomizeDocumentVenta($ped, $eje, $clientes, $num);
                if (mt_rand(0, 3) == 0) {
                    $ped->fechasalida = date('d-m-Y', strtotime($ped->fecha . ' +' . mt_rand(1, 3) . ' months'));
                }

                if ($ped->save()) {
                    $this->randomLineas($ped, 'idpedido', 'FacturaScripts\Dinamic\Model\LineaPedidoCliente', $regimeniva, $recargo);
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
     * @param int $max
     * @return int
     */
    public function pedidosProveedor($max = 25)
    {
        $num = 0;
        $proveedores = $this->randomProveedores();

        $recargo = FALSE;
        if (mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $ped = new Model\PedidoProveedor();
            $this->randomizeDocument($ped);

            $eje = $this->ejercicio->getByFecha($ped->fecha);
            if ($eje) {
                $regimeniva = $this->randomizeDocumentCompra($ped, $eje, $proveedores, $num);

                if ($ped->save()) {
                    $this->randomLineas($ped, 'idpedido', 'FacturaScripts\Dinamic\Model\LineaPedidoProveedor', $regimeniva, $recargo);
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
     * @param int $max
     * @return int
     */
    public function presupuestosCliente($max = 25)
    {
        $num = 0;
        $clientes = $this->randomClientes();

        $recargo = FALSE;
        if ($clientes[0]->recargo || mt_rand(0, 4) == 0) {
            $recargo = TRUE;
        }

        while ($num < $max) {
            $presu = new Model\PresupuestoCliente();
            $this->randomizeDocument($presu);

            $eje = $this->ejercicio->getByFecha($presu->fecha);
            if ($eje) {
                $regimeniva = $this->randomizeDocumentVenta($presu, $eje, $clientes, $num);
                $presu->finoferta = date('d-m-Y', strtotime($presu->fecha . ' +' . mt_rand(1, 18) . ' months'));

                if ($presu->save()) {
                    $this->randomLineas($presu, 'idpresupuesto', 'FacturaScripts\Dinamic\Model\LineaPresupuestoCliente', $regimeniva, $recargo);
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
        $observaciones = [
            'Pagado', 'Faltan piezas', 'No se corresponde con lo solicitado.',
            'Muy caro', 'Muy barato', 'Mala calidad',
            'La parte contratante de la primera parte será la parte contratante de la primera parte.'
        ];

        /// añadimos muchos blas como otra opción
        $bla = 'Bla';
        while (mt_rand(0, 29) > 0) {
            $bla .= ', bla';
        }
        $observaciones[] = $bla . '.';

        /// randomizamos (es posible que me haya inventado esta palabra)
        shuffle($observaciones);

        if ($fecha && mt_rand(0, 2) == 0) {
            $semana = date("D", strtotime($fecha));
            $semanaArray = [
                "Mon" => "lunes", "Tue" => "martes", "Wed" => "miércoles", "Thu" => "jueves",
                "Fri" => "viernes", "Sat" => "sábado", "Sun" => "domingo",
            ];
            $title = urlencode(sprintf('{{Plantilla:Frase-%s}}', $semanaArray[$semana]));
            $sock = @fopen("http://es.wikiquote.org/w/api.php?action=parse&format=php&text=$title", "r");
            if (!$sock) {
                return $observaciones[0];
            }

            # Hacemos la peticion al servidor
            $array__ = unserialize(stream_get_contents($sock));
            $texto_final = strip_tags($array__["parse"]["text"]["*"]);
            $texto_final = str_replace("\n\n\n\n", "\n", $texto_final);

            return $texto_final;
        }

        return $observaciones[0];
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
     * @param boolean $recursivo
     * @return \cliente
     */
    protected function randomClientes($recursivo = TRUE)
    {
        $lista = [];

        $sql = "SELECT * FROM clientes ORDER BY random()";
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $sql = "SELECT * FROM clientes ORDER BY RAND()";
        }

        $data = $this->db->selectLimit($sql, 100, 0);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new Model\Cliente($d);
            }
        } else if ($recursivo) {
            $this->clientes();
            $lista = $this->randomClientes(FALSE);
        }

        return $lista;
    }

    /**
     * Devuelve un array con proveedores aleatorios.
     * @param boolean $recursivo
     * @return \proveedor
     */
    protected function randomProveedores($recursivo = TRUE)
    {
        $lista = [];

        $sql = "SELECT * FROM proveedores ORDER BY random()";
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $sql = "SELECT * FROM proveedores ORDER BY RAND()";
        }

        $data = $this->db->selectLimit($sql, 100, 0);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new Model\Proveedor($d);
            }
        } else if ($recursivo) {
            $this->proveedores();
            return $this->randomProveedores(FALSE);
        }

        return $lista;
    }

    /**
     * Devuelve un array con empleados aleatorios.
     * @param boolean $recursivo
     * @return \agente
     */
    protected function randomAgentes($recursivo = TRUE)
    {
        $lista = [];

        $sql = "SELECT * FROM agentes ORDER BY random()";
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $sql = "SELECT * FROM agentes ORDER BY RAND()";
        }

        $data = $this->db->selectLimit($sql, 100, 0);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new Model\Agente($d);
            }
        } else if ($recursivo) {
            $this->agentes();
            return $this->randomAgentes(FALSE);
        }

        return $lista;
    }

    /**
     * Devuelve un array con artículos aleatorios.
     * @param boolean $recursivo
     * @return \articulo
     */
    protected function randomArticulos($recursivo = TRUE)
    {
        $lista = [];

        $sql = "SELECT * FROM articulos ORDER BY random()";
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $sql = "SELECT * FROM articulos ORDER BY RAND()";
        }

        $data = $this->db->selectLimit($sql, 100, 0);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new Model\Articulo($d);
            }
        } else if ($recursivo) {
            $this->articulos();
            return $this->randomArticulos(FALSE);
        }

        return $lista;
    }
}
