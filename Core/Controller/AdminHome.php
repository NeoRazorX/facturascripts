<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;

/**
 * Description of admin_home
 *
 * @author Carlos García Gómez
 */
class AdminHome extends Base\Controller
{
    /**
     * TODO
     * @var Model\AgenciasTrans
     */
    public $agenciaTransporte;

    /**
     * TODO
     * @var Model\Agente
     */
    public $agente;

    /**
     * TODO
     * @var Model\AlbaranCliente
     */
    public $albaranCliente;

    /**
     * TODO
     * @var Model\AlbaranProveedor
     */
    public $albaranProveedor;

    /**
     * TODO
     * @var Model\Almacen
     */
    public $almacen;

    /**
     * TODO
     * @var Model\Articulo
     */
    public $articulo;

    /**
     * TODO
     * @var Model\ArticuloCombinacion
     */
    public $articuloCombinacion;

    /**
     * TODO
     * @var Model\ArticuloPropiedad
     */
    public $articuloPropiedad;

    /**
     * TODO
     * @var Model\ArticuloProveedor
     */
    public $articuloProveedor;

    /**
     * TODO
     * @var Model\ArticuloTraza
     */
    public $articuloTraza;

    /**
     * TODO
     * @var Model\Asiento
     */
    public $asiento;

    /**
     * TODO
     * @var Model\AsientoFactura
     */
    public $asientoFactura;

    /**
     * TODO
     * @var Model\Atributo
     */
    public $atributo;

    /**
     * TODO
     * @var Model\AtributoValor
     */
    public $atributoValor;

    /**
     * TODO
     * @var Model\Balance
     */
    public $balance;

    /**
     * TODO
     * @var Model\Caja
     */
    public $caja;

    /**
     * TODO
     * @var Model\Cliente
     */
    public $cliente;

    /**
     * TODO
     * @var Model\ClientePropiedad
     */
    public $clientePropiedad;

    /**
     * TODO
     * @var Model\ConceptoPartida
     */
    public $conceptoPartida;

    /**
     * TODO
     * @var Model\Cuenta
     */
    public $cuenta;

    /**
     * TODO
     * @var Model\CuentaBancoCliente
     */
    public $cuentaBancoCliente;

    /**
     * TODO
     * @var Model\CuentaBancoProveedor
     */
    public $cuentaBancoProveedor;

    /**
     * TODO
     * @var Model\CuentaEspecial
     */
    public $cuentaEspecial;

    /**
     * TODO
     * @var Model\DireccionCliente
     */
    public $direccionCliente;

    /**
     * TODO
     * @var Model\DireccionProveedor
     */
    public $direccionProveedor;

    /**
     * TODO
     * @var Model\Divisa
     */
    public $divisa;

    /**
     * TODO
     * @var Model\Ejercicio
     */
    public $ejercicio;

    /**
     * TODO
     * @var Model\FormaPago
     */
    public $formaPago;

    /**
     * TODO
     * @var Model\GrupoClientes
     */
    public $grupoClientes;

    /**
     * TODO
     * @var Model\Impuesto
     */
    public $impuesto;

    /**
     * TODO
     * @var Model\LineaAlbaranCliente
     */
    public $lineaAlbaranCliente;

    /**
     * TODO
     * @var Model\LineaAlbaranProveedor
     */
    public $lineaAlbaranProveedor;

    /**
     * TODO
     * @var Model\LineaFacturaCliente
     */
    public $lineaFacturaCliente;

    /**
     * TODO
     * @var Model\LineaFacturaProveedor
     */
    public $lineaFacturaProveedor;

    /**
     * TODO
     * @var Model\LineaIvaFacturaCliente
     */
    public $lineaIvaFacturaCliente;

    /**
     * TODO
     * @var Model\LineaIvaFacturaProveedor
     */
    public $lineaIvaFacturaProveedor;

    /**
     * TODO
     * @var Model\LineaTransferenciaStock
     */
    public $lineaTransferenciaStock;

    /**
     * TODO
     * @var Model\Page
     */
    public $page;

    /**
     * TODO
     * @var Model\PageRule
     */
    public $pageRule;

    /**
     * TODO
     * @var Model\Pais
     */
    public $pais;

    /**
     * TODO
     * @var Model\Partida
     */
    public $partida;

    /**
     * TODO
     * @var Model\Proveedor
     */
    public $proveedor;

    /**
     * TODO
     * @var Model\RecalcularStock
     */
    public $recalcularStock;

    /**
     * TODO
     * @var Model\RegularizacionIva
     */
    public $regularizacionIva;

    /**
     * TODO
     * @var Model\RegularizacionStock
     */
    public $regularizacionStock;

    /**
     * TODO
     * @var Model\Rol
     */
    public $rol;

    /**
     * TODO
     * @var Model\RolAccess
     */
    public $rolAccess;

    /**
     * TODO
     * @var Model\RolUser
     */
    public $rolUser;

    /**
     * TODO
     * @var Model\Secuencia
     */
    public $secuencia;

    /**
     * TODO
     * @var Model\Serie
     */
    public $serie;

    /**
     * TODO
     * @var Model\Stock
     */
    public $stock;

    /**
     * TODO
     * @var Model\Subcuenta
     */
    public $subcuenta;

    /**
     * TODO
     * @var Model\SubcuentaCliente
     */
    public $subcuentaCliente;

    /**
     * TODO
     * @var Model\SubcuentaProveedor
     */
    public $subcuentaProveedor;

    /**
     * TODO
     * @var Model\Tarifa
     */
    public $tarifa;

    /**
     * TODO
     * @var Model\TerminalCaja
     */
    public $terminalCaja;

    /**
     * TODO
     * @var Model\TransferenciaStock
     */
    public $transferenciaStock;

    /**
     * AdminHome constructor.
     * @param Base\Cache $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog $miniLog
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->agenciaTransporte = new Model\AgenciaTransporte();
        $this->agente = new Model\Agente();
        // $this->albaranCliente = new Model\AlbaranCliente();
        // $this->albaranProveedor = new Model\AlbaranProveedor();
        $this->almacen = new Model\Almacen();
        // $this->articulo = new Model\Articulo();
        // $this->articuloCombinacion = new Model\ArticuloCombinacion();
        // $this->articuloPropiedad = new Model\ArticuloPropiedad();
        // $this->articuloProveedor = new Model\ArticuloProveedor();
        // $this->articuloTraza = new Model\ArticuloTraza();
        // $this->asiento = new Model\Asiento();
        // $this->asientoFactura = new Model\AsientoFactura();
        // $this->atributo = new Model\Atributo();
        // $this->atributoValor = new Model\AtributoValor();
        // $this->balance = new Model\Balance();
        // $this->caja = new Model\Caja();
        // $this->cliente = new Model\Cliente();
        // $this->clientePropiedad = new Model\ClientePropiedad();
        // $this->conceptoPartida = new Model\ConceptoPartida();
        // $this->cuenta = new Model\Cuenta();
        // $this->cuentaBancoCliente = new Model\CuentaBancoCliente();
        // $this->cuentaBancoProveedor = new Model\CuentaBancoProveedor();
        // $this->cuentaEspecial = new Model\CuentaEspecial();
        // $this->direccionCliente = new Model\DireccionCliente();
        // $this->direccionProveedor = new Model\DireccionProveedor();
        $this->divisa = new Model\Divisa();
        $this->ejercicio = new Model\Ejercicio();
        // $this->epigrafe = new Model\Epigrafe();
        // $this->fabricante = new Model\Fabricante();
        // $this->facturaCliente = new Model\FacturaCliente();
        // $this->facturaProveedor = new Model\FacturaProveedor();
        // $this->familia = new Model\Familia();
        // $this->formaPago = new Model\FormaPago();
        // $this->grupoClientes = new Model\GrupoClientes();
        // $this->impuesto = new model\impuesto();
        // $this->lineaAlbaranCliente = new Model\LineaAlbaranCliente();
        // $this->lineaAlbaranProveedor = new Model\LineaAlbaranProveedor();
        // $this->lineaFacturaCliente = new Model\LineaFacturaCliente();
        // $this->lineaFacturaProveedor = new Model\LineaFacturaProveedor();
        // $this->lineaIvaFacturaCliente = new Model\LineaIvaFacturaCliente();
        // $this->lineaIvaFacturaProveedor = new Model\LineaIvaFacturaProveedor();
        // $this->lineaTransferenciaStock = new Model\LineaTransferenciaStock();
        // $this->page = new Model\Page();
        // $this->pageRule = new Model\PageRule();
        $this->pais = new Model\Pais();
        // $this->partida = new Model\Partida();
        // $this->proveedor = new Model\Proveedor();
        // $this->recalcularStock = new Model\RecalcularStock();
        // $this->regularizacionIva = new Model\RegularizacionIva();
        // $this->regularizacionStock = new Model\RegularizacionStock();
        // $this->rol = new Model\Rol();
        // $this->rolAccess = new Model\RolAccess();
        // $this->rolUser = new Model\RolUser();
        // $this->secuencia = new Model\Secuencia();
        $this->serie = new Model\Serie();
        // $this->stock = new Model\Stock();
        // $this->subcuenta = new Model\Subcuenta();
        // $this->subcuentaCliente = new Model\SubcuentaCliente();
        // $this->subcuentaProveedor = new Model\SubcuentaProveedor();
        // $this->tarifa = new Model\Tarifa();
        // $this->terminalCaja = new Model\TerminalCaja();
        // $this->transferenciaStock = new Model\TransferenciaStock();
    }
    
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'Panel de control';
        
        return $pageData;
    }

}
