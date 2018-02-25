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
 * Abstract class that contains the methods that generate random documents
 * for clients and suppliers, such as orders, delivery notes and invoices. 
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
abstract class AbstractRandomDocuments extends AbstractRandomPeople
{

    /**
     * List of warehouses.
     *
     * @var Model\Almacen[]
     */
    protected $almacenes;

    /**
     * List of currencies.
     *
     * @var Model\Divisa[]
     */
    protected $divisas;

    /**
     * Exercice to use.
     *
     * @var Model\Ejercicio
     */
    protected $ejercicio;

    /**
     * List of payment methods.
     *
     * @var Model\FormaPago[]
     */
    protected $formasPago;

    /**
     * List of taxes.
     *
     * @var Model\Impuesto[]
     */
    protected $impuestos;

    /**
     * List of series.
     *
     * @var Model\Serie[]
     */
    protected $series;

    /**
     * AbstractRandomDocuments constructor.
     *
     * @param $model
     */
    public function __construct($model)
    {
        parent::__construct($model);
        $this->ejercicio = new Model\Ejercicio();
        $this->shuffle($this->almacenes, new Model\Almacen());
        $this->shuffle($this->divisas, new Model\Divisa());
        $this->shuffle($this->formasPago, new Model\FormaPago());
        $this->shuffle($this->impuestos, new Model\Impuesto());
        $this->shuffle($this->series, new Model\Serie());
    }

    /**
     * Generates a random document
     *
     * @param mixed $doc
     */
    protected function randomizeDocument(&$doc)
    {
        $doc->fecha = $this->fecha();
        $doc->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
        $doc->codpago = $this->formasPago[0]->codpago;
        $doc->codalmacen = (mt_rand(0, 2) == 0) ? $this->almacenes[0]->codalmacen : AppSettings::get('default', 'codalmacen');
        $doc->idempresa = AppSettings::get('default', 'idempresa');

        foreach ($this->divisas as $div) {
            if ($div->coddivisa == AppSettings::get('default', 'coddivisa')) {
                $doc->coddivisa = $div->coddivisa;
                $doc->tasaconv = $div->tasaconv;
                break;
            }
        }

        if (mt_rand(0, 2) == 0) {
            $doc->coddivisa = $this->divisas[0]->coddivisa;
            $doc->tasaconv = $this->divisas[0]->tasaconv;
        }

        $doc->codserie = AppSettings::get('default', 'codserie');
        if (!isset($doc->codserie) || $doc->codserie == "---null---") {
            $doc->codserie = 'A';
            $doc->irpf = 0;
        }
        if (mt_rand(0, 2) == 0) {
            if ($this->series[0]->codserie != 'R') {
                $doc->codserie = $this->series[0]->codserie;
                $doc->irpf = $this->series[0]->irpf;
            }

            $doc->observaciones = $this->observaciones($doc->fecha);
        }

        if (isset($doc->numero2) && mt_rand(0, 4) == 0) {
            $doc->numero2 = mt_rand(10, 99999);
        } elseif (isset($doc->numproveedor) && mt_rand(0, 4) == 0) {
            $doc->numproveedor = mt_rand(10, 99999);
        }

        $doc->codagente = mt_rand(0, 4) ? $this->agentes[0]->codagente : null;
    }

    /**
     * Generates a random purchase document
     *
     * @param $doc
     * @param Model\Ejercicio   $eje
     * @param Model\Proveedor[] $proveedores
     * @param int               $num
     *
     * @return string
     */
    protected function randomizeDocumentCompra(&$doc, $eje, $proveedores, $num)
    {
        $doc->codejercicio = $eje->codejercicio;

        $regimeniva = 'Exento';
        if (mt_rand(0, 14) > 0 && isset($proveedores[$num])) {
            $doc->setProveedor($proveedores[$num]);
            $regimeniva = $proveedores[$num]->regimeniva;
        } else {
            /// Every once in a while, generate one without provider, to check if it breaks ;-)
            $doc->nombre = $this->empresa();
            $doc->cifnif = mt_rand(1111111, 99999999) . 'Z';
        }

        return $regimeniva;
    }

    /**
     * Generates a random sale document
     *
     * @param $doc
     * @param Model\Ejercicio $eje
     * @param Model\Cliente[] $clientes
     * @param int             $num
     *
     * @return string
     */
    protected function randomizeDocumentVenta(&$doc, $eje, $clientes, $num)
    {
        $doc->codejercicio = $eje->codejercicio;

        $regimeniva = 'Exento';
        if (mt_rand(0, 14) > 0 && isset($clientes[$num])) {
            $doc->setCliente($clientes[$num]);
            $regimeniva = $clientes[$num]->regimeniva;
        } else {
            /// Every once in a while, generate one without the client, to check if it breaks ;-)
            $doc->nombrecliente = $this->nombre() . ' ' . $this->apellidos();
            $doc->cifnif = mt_rand(1111, 999999999) . 'J';
        }

        return $regimeniva;
    }

    /**
     * Generates random document lines
     *
     * @param $doc
     * @param string $iddoc
     * @param string $lineaClass
     * @param string $regimeniva
     * @param bool   $recargo
     * @param int    $modStock
     */
    protected function randomLineas(&$doc, $iddoc, $lineaClass, $regimeniva, $recargo, $modStock = 0)
    {
        $imp = new Model\Impuesto();

        $articulos = $this->randomArticulos();

        /// 1 out of 15 times we use negative quantities
        $modcantidad = 1;
        if (mt_rand(0, 4) == 0) {
            $modcantidad = -1;
        }

        $numlineas = (int) $this->cantidad(0, 10, 200);
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
                $lin->iva = $imp->get($articulos[$numlineas]->codimpuesto)->iva;
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
                $lin->dtopor = $this->cantidad(0, 33, 100);
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

            --$numlineas;
        }

        /// redondeamos
        $doc->neto = round($doc->neto, FS_NF0);
        $doc->totaliva = round($doc->totaliva, FS_NF0);
        $doc->totalirpf = round($doc->totalirpf, FS_NF0);
        $doc->totalrecargo = round($doc->totalrecargo, FS_NF0);
        $doc->total = $doc->neto + $doc->totaliva - $doc->totalirpf + $doc->totalrecargo;
        $doc->save();
    }
}
