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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;

define('FS_NF0', 2);
define('FS_NF0_ART', 2);
define('FS_STOCK_NEGATIVO', true);

/**
 * Clase con las funciones para generar datos aleatorios.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ModelDataGenerator
{

    /**
     * Contiene agentes generados
     * @var Model\Agente[]
     */
    protected $agentes;

    /**
     * Contiene almacenes generados
     * @var Model\Almacen[]
     */
    protected $almacenes;

    /**
     * Proporciona acceso directo a la base de datos.
     * @var Base\DataBase
     */
    protected $db;

    /**
     * Contiene divisas generadas
     * @var Model\Divisa[]
     */
    protected $divisas;

    /**
     * Contiene ejercicios generados
     * @var Model\Ejercicio
     */
    protected $ejercicio;

    /**
     * Contiene empresas generadas
     * @var Model\Empresa
     */
    protected $empresa;

    /**
     * Contiene formas de pago generadas
     * @var Model\FormaPago[]
     */
    protected $formas_pago;

    /**
     * Contiene grupos de clientes generados
     * @var Model\GrupoClientes[]
     */
    protected $grupos;

    /**
     * Contiene impuestos generados
     * @var Model\Impuesto[]
     */
    protected $impuestos;

    /**
     * Contiene países generados
     * @var Model\Pais[]
     */
    protected $paises;

    /**
     * Contiene series generadas
     * @var Model\Serie[]
     */
    protected $series;

    /**
     * Proporciona acceso al generador de datos
     * @var DataGeneratorTools
     */
    protected $tools;

    /**
     * Contiene usuarios generados
     * @var Model\User[]
     */
    protected $users;

    /**
     * Constructor. Inicializamos lo necesario y randomizamos.
     * @param Model\Empresa $empresa
     */
    public function __construct($empresa)
    {
        $this->db = new Base\DataBase();
        $this->empresa = $empresa;
        $this->ejercicio = new Model\Ejercicio();
        $this->tools = new DataGeneratorTools();

        $this->tools->loadData($this->agentes, new Model\Agente(), true);
        $this->tools->loadData($this->almacenes, new Model\Almacen(), true);
        $this->tools->loadData($this->divisas, new Model\Divisa(), true);
        $this->tools->loadData($this->formas_pago, new Model\FormaPago(), true);
        $this->tools->loadData($this->grupos, new Model\GrupoClientes(), false);
        $this->tools->loadData($this->impuestos, new Model\Impuesto(), true);
        $this->tools->loadData($this->paises, new Model\Pais(), true);
        $this->tools->loadData($this->series, new Model\Serie(), true);
        $this->tools->loadData($this->users, new Model\User(), false);
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
            $fabri->nombre = $this->tools->empresa();
            $fabri->codfabricante = $this->tools->txt2codigo($fabri->nombre);
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
        $codfamilia = null;

        for ($num = 0; $num < $max; ++$num) {
            $fam->descripcion = $this->tools->empresa();
            $fam->codfamilia = $this->tools->txt2codigo($fam->descripcion);
            $fam->madre = (mt_rand(0, 4) == 0) ? $codfamilia : null;
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
            $art->descripcion = $this->tools->descripcion();
            $art->codimpuesto = $this->impuestos[0]->codimpuesto;
            $art->setPvpIva($this->tools->precio(1, 49, 699));
            $art->costemedio = $art->preciocoste = $this->tools->cantidad(0, $art->pvp, $art->pvp + 1);
            $art->stockmin = mt_rand(0, 10);
            $art->stockmax = mt_rand($art->stockmin + 1, $art->stockmin + 1000);

            switch (mt_rand(0, 2)) {
                case 0:
                    $art->referencia = $art->getNewReferencia();
                    break;

                case 1:
                    $aux = explode(':', $art->descripcion);
                    if ($aux) {
                        $art->referencia = $this->tools->txt2codigo($aux[0], 18);
                    } else {
                        $art->referencia = $art->getNewReferencia();
                    }
                    break;

                default:
                    $art->referencia = $this->tools->randomString(10);
            }

            if (mt_rand(0, 9) > 0) {
                $art->codfabricante = $fabricantes[0]->codfabricante;
                $art->codfamilia = $familias[0]->codfamilia;
            } else {
                $art->codfabricante = null;
                $art->codfamilia = null;
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
     * Genera $max artículos de proveedor aleatorios.
     * Devuelve el número de artículos generados.
     * @param int $max
     * @return int
     */
    public function articulosProveedor($max = 50)
    {
        $proveedores = $this->randomProveedores();

        for ($num = 0; $num < $max; ++$num) {
            if (mt_rand(0, 2) == 0 && $this->impuestos[0]->iva <= 10) {
                shuffle($this->impuestos);
            }

            $art = new Model\ArticuloProveedor();
            $art->referencia = mt_rand(1, 99999999);
            $art->refproveedor = mt_rand(1, 99999999);
            $art->descripcion = $this->tools->descripcion();
            $art->codimpuesto = $this->impuestos[0]->codimpuesto;
            $art->codproveedor = $proveedores[$num]->codproveedor;
            $art->precio = $this->tools->precio(1, 49, 699);
            $art->dto = mt_rand(0, 80);
            $art->nostock = (mt_rand(0, 2) == 0);
            $art->stock = mt_rand(0, 10);

            if (!$art->save()) {
                break;
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

            $agente->nombre = $this->tools->nombre();
            $agente->apellidos = $this->tools->apellidos();
            $agente->provincia = $this->tools->provincia();
            $agente->ciudad = $this->tools->ciudad();
            $agente->direccion = $this->tools->direccion();
            $agente->codpostal = mt_rand(11111, 99999);

            if (mt_rand(0, 1) == 0) {
                $agente->telefono = mt_rand(555555555, 999999999);
            }

            if (mt_rand(0, 2) > 0) {
                $agente->email = $this->tools->email();
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
                $agente->porcomision = $this->tools->cantidad(0, 5, 20);
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
                $cliente->debaja = true;
                $cliente->fechabaja = date('d-m-Y');
            }

            $cliente->cifnif = (mt_rand(0, 14) === 0) ? '' : mt_rand(0, 99999999);

            switch (mt_rand(0, 2)) {
                case 0:
                    $cliente->nombre = $cliente->razonsocial = $this->tools->empresa();
                    $cliente->personafisica = false;
                    break;
                case 1:
                    $cliente->nombre = $this->tools->nombre() . ' ' . $this->tools->apellidos();
                    $cliente->razonsocial = $this->tools->empresa();
                    $cliente->personafisica = false;
                    break;
                default:
                    $cliente->nombre = $cliente->razonsocial = $this->tools->nombre() . ' ' . $this->tools->apellidos();
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

            $cliente->email = (mt_rand(0, 2) > 0) ? $this->tools->email() : null;
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
            $num_dirs = mt_rand(0, 3);
            while ($num_dirs > 0) {
                $dir = new Model\DireccionCliente();
                $dir->codcliente = $cliente->codcliente;
                $dir->codpais = (mt_rand(0, 2) === 0) ? $this->paises[0]->codpais : $this->empresa->codpais;
                $dir->provincia = $this->tools->provincia();
                $dir->ciudad = $this->tools->ciudad();
                $dir->direccion = $this->tools->direccion();
                $dir->codpostal = mt_rand(1234, 99999);
                $dir->apartado = (mt_rand(0, 3) == 0) ? (string) mt_rand(1234, 99999) : null;
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

                $cuenta->swift = ($opcion != 0) ? $this->tools->randomString(8) : '';
                $cuenta->fmandato = (mt_rand(0, 1) == 0) ? date('d-m-Y', strtotime($cliente->fechaalta . ' +' . mt_rand(1, 30) . ' days')) : null;

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
            $proveedor->nombre = $proveedor->razonsocial = $this->tools->empresa();
            $proveedor->personafisica = false;
            if ($opcion == 0) {
                $proveedor->nombre = $this->tools->nombre() . ' ' . $this->tools->apellidos();
                $proveedor->personafisica = true;
            } elseif ($opcion == 1) {
                $proveedor->nombre = $proveedor->razonsocial = $this->tools->empresa();
                $proveedor->acreedor = true;
            }

            $opcion = mt_rand(0, 2);
            if ($opcion == 0) {
                $proveedor->telefono1 = mt_rand(555555555, 999999999);
            } elseif ($opcion == 1) {
                $proveedor->telefono1 = mt_rand(555555555, 999999999);
                $proveedor->telefono2 = mt_rand(555555555, 999999999);
            } else {
                $proveedor->telefono2 = mt_rand(555555555, 999999999);
            }

            if (mt_rand(0, 2) > 0) {
                $proveedor->email = $this->tools->email();
            }

            if (mt_rand(0, 9) == 0) {
                $proveedor->regimeniva = 'Exento';
            }

            if (mt_rand(0, 24) == 0) {
                $proveedor->debaja = true;
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

                    $dir->provincia = $this->tools->provincia();
                    $dir->ciudad = $this->tools->ciudad();
                    $dir->direccion = $this->tools->direccion();
                    $dir->codpostal = mt_rand(1234, 99999);

                    if (mt_rand(0, 3) == 0) {
                        $dir->apartado = (string) mt_rand(1234, 99999);
                    }

                    if (mt_rand(0, 1) == 0) {
                        $dir->direccionppal = false;
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
                    $cuenta->swift = $this->tools->randomString(8);

                    $opcion = mt_rand(0, 2);
                    if ($opcion == 0) {
                        $cuenta->swift = '';
                    } elseif ($opcion == 1) {
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
     * Genera un documento aleatorio.
     *
     * @param $doc
     */
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

            $doc->observaciones = $this->tools->observaciones($doc->fecha);
        }

        if (isset($doc->numero2) && mt_rand(0, 4) == 0) {
            $doc->numero2 = mt_rand(10, 99999);
        } elseif (isset($doc->numproveedor) && mt_rand(0, 4) == 0) {
            $doc->numproveedor = mt_rand(10, 99999);
        }

        if (isset($doc->status) && mt_rand(0, 5) == 0) {
            $doc->status = 2;
        }

        $doc->codagente = $this->agentes[0]->codagente;
        if (mt_rand(0, 4) == 0) {
            $doc->codagente = null;
        }
    }

    /**
     * Genera un documento de compra aleatorio
     *
     * @param $doc
     * @param Model\Ejercicio $eje
     * @param Model\Proveedor[] $proveedores
     * @param int $num
     *
     * @return string
     */
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
            /// de vez en cuando generamos un sin proveedor, para ver si peta ;-)
            $doc->nombre = $this->tools->empresa();
            $doc->cifnif = mt_rand(1111111, 9999999999) . 'Z';
        }

        return $regimeniva;
    }

    /**
     * Genera un documento de venta aleatorio
     *
     * @param $doc
     * @param Model\Ejercicio $eje
     * @param Model\Cliente[] $clientes
     * @param int $num
     *
     * @return string
     */
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
                    $doc->envio_nombre = $this->tools->nombre();
                    $doc->envio_apellidos = $this->tools->apellidos();
                    $doc->envio_codpais = $dir->codpais;
                    $doc->envio_provincia = $dir->provincia;
                    $doc->envio_ciudad = $dir->ciudad;
                    $doc->envio_codpostal = $dir->codpostal;
                    $doc->envio_direccion = $dir->direccion;
                    $doc->envio_apartado = $dir->apartado;
                }
            }
        } else {
            /// de vez en cuando creamos uno sin cliente asociado para ver si peta ;-)
            $doc->nombrecliente = $this->tools->nombre() . ' ' . $this->tools->apellidos();
            $doc->cifnif = mt_rand(1111, 999999999) . 'J';
        }

        return $regimeniva;
    }

    /**
     * Genera líneas  aleatorias
     *
     * @param $doc
     * @param string $iddoc
     * @param string $lineaClass
     * @param string $regimeniva
     * @param bool $recargo
     * @param int $modStock
     */
    private function randomLineas(&$doc, $iddoc = 'idalbaran', $lineaClass = 'FacturaScripts\Dinamic\Model\LineaAlbaranCliente', $regimeniva, $recargo, $modStock = 0)
    {
        $articulos = $this->randomArticulos();

        /// una de cada 15 veces usamos cantidades negativas
        $modcantidad = 1;
        if (mt_rand(0, 4) == 0) {
            $modcantidad = -1;
        }

        $numlineas = (int) $this->tools->cantidad(0, 10, 200);
        while ($numlineas > 0) {
            $lin = new $lineaClass();
            $lin->{$iddoc} = $doc->{$iddoc};
            $lin->cantidad = $modcantidad * $this->tools->cantidad(1, 3, 19);
            $lin->descripcion = $this->tools->descripcion();
            $lin->pvpunitario = $this->tools->precio(1, 49, 699);
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
                $lin->codimpuesto = null;
                $lin->iva = 0;
                $lin->recargo = 0;
                $doc->irpf = $lin->irpf = 0;
            }

            if (mt_rand(0, 4) == 0) {
                $lin->dtopor = $this->tools->cantidad(0, 33, 100);
            }

            $lin->pvpsindto = $lin->pvpunitario * $lin->cantidad;
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

        $recargo = false;
        if ($clientes[0]->recargo || mt_rand(0, 4) === 0) {
            $recargo = true;
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

        $recargo = false;
        if (mt_rand(0, 4) == 0) {
            $recargo = true;
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

        $recargo = false;
        if ($clientes[0]->recargo || mt_rand(0, 4) == 0) {
            $recargo = true;
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

        $recargo = false;
        if (mt_rand(0, 4) == 0) {
            $recargo = true;
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

        $recargo = false;
        if ($clientes[0]->recargo || mt_rand(0, 4) === 0) {
            $recargo = true;
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
    
    private function randomModel($modelName, $tableName, $functionName, $recursivo = true)
    {
        $lista = [];

        $sql = 'SELECT * FROM '.$tableName.' ORDER BY ';
        $sql .= strtolower(FS_DB_TYPE) == 'mysql' ? 'RAND()' : 'random()';

        $data = $this->db->selectLimit($sql, 100, 0);
        if (!empty($data)) {
            foreach ($data as $d) {
                $lista[] = new $modelName($d);
            }
        } elseif ($recursivo) {
            $this->{$functionName}();
            $lista = $this->randomModel($modelName, $tableName, $functionName, false);
        }

        return $lista;
    }

    /**
     * Devuelve un array con clientes aleatorios.
     * @param bool $recursivo
     * @return Model\Cliente[]
     */
    protected function randomClientes($recursivo = true)
    {
        return $this->randomModel('FacturaScripts\Core\Model\Cliente', 'clientes', 'clientes', $recursivo);
    }

    /**
     * Devuelve un array con proveedores aleatorios.
     * @param bool $recursivo
     * @return Model\Proveedor[]
     */
    protected function randomProveedores($recursivo = true)
    {
        return $this->randomModel('FacturaScripts\Core\Model\Proveedor', 'proveedores', 'proveedores', $recursivo);
    }

    /**
     * Devuelve un array con empleados aleatorios.
     * @param bool $recursivo
     * @return Model\Agente[]
     */
    protected function randomAgentes($recursivo = true)
    {
        return $this->randomModel('FacturaScripts\Core\Model\Agente', 'agentes', 'agentes', $recursivo);
    }

    /**
     * Devuelve un array con artículos aleatorios.
     * @param bool $recursivo
     * @return Model\Articulo[]
     */
    protected function randomArticulos($recursivo = true)
    {
        return $this->randomModel('FacturaScripts\Core\Model\Articulo', 'articulos', 'articulos', $recursivo);
    }
}
