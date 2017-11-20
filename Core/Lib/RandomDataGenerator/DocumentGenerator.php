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
use FacturaScripts\Core\Model;

/**
 * Description of DocumentGenerator
 *
 * @author carlos
 */
class DocumentGenerator extends ModelDataGenerator
{

    /**
     * Generates a random document
     *
     * @param $doc
     */
    private function randomizeDocument(&$doc)
    {
        $doc->fecha = mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand(2013, date('Y'));
        $doc->hora = mt_rand(10, 20) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);
        $doc->codpago = $this->formasPago[0]->codpago;
        $doc->codalmacen = (mt_rand(0, 2) == 0) ? $this->almacenes[0]->codalmacen : AppSettings::get('default', 'codalmacen');

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
     * Generates a random purchase document
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
            /// Every once in a while, generate one without provider, to check if it breaks ;-)
            $doc->nombre = $this->tools->empresa();
            $doc->cifnif = mt_rand(1111111, 9999999999) . 'Z';
        }

        return $regimeniva;
    }

    /**
     * Generates a random sale document
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
            /// Every once in a while, generate one without the client, to check if it breaks ;-)
            $doc->nombrecliente = $this->tools->nombre() . ' ' . $this->tools->apellidos();
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
     * @param bool $recargo
     * @param int $modStock
     */
    private function randomLineas(&$doc, $iddoc = 'idalbaran', $lineaClass = 'FacturaScripts\Dinamic\Model\LineaAlbaranCliente', $regimeniva, $recargo, $modStock = 0)
    {
        $articulos = $this->randomArticulos();

        /// 1 out of 15 times we use negative quantities
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
     * Generates $max random sale delivery notes.
     * Returns the number of generated delivery notes
     *
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
     * Generates $max random purchase delivery notes.
     * Returns the number of generated delivery notes
     *
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
     * Generates $max random sale orders.
     * Returns the number of generated orders.
     *
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
     * Generates $max random purchase orders.
     * Returns the number of generated orders.
     *
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
     * Generates $max random sale estimates.
     * Returns the number of generated estimates.
     *
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
}
