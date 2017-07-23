<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Model;

/**
 * Factura de un proveedor.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FacturaProveedor
{
    use Model {
        saveInsert as private saveInsertTrait;
    }

    /**
     * Clave primaria.
     * @var int
     */
    public $idfactura;

    /**
     * ID de la factura a la que rectifica.
     * @var int
     */
    public $idfacturarect;

    /**
     * ID del asiento relacionado, si lo hay.
     * @var int
     */
    public $idasiento;

    /**
     * ID del asiento de pago relacionado, si lo hay.
     * @var int
     */
    public $idasientop;

    /**
     * CIF/NIF del proveedor
     * @var string
     */
    public $cifnif;

    /**
     * Empleado que ha creado la factura.
     * Modelo agente.
     * @var string
     */
    public $codagente;

    /**
     * Almacén en el que entra la mercancía.
     * @var string
     */
    public $codalmacen;

    /**
     * Divisa de la factura.
     * @var string
     */
    public $coddivisa;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     * @var string
     */
    public $codejercicio;

    /**
     * Código único de la factura. Para humanos.
     * @var string
     */
    public $codigo;

    /**
     * Código de la factura a la que rectifica.
     * @var string
     */
    public $codigorect;

    /**
     * Forma de pago.
     * @var string
     */
    public $codpago;

    /**
     * Proveedor de la factura.
     * @var string
     */
    public $codproveedor;

    /**
     * Serie de la factura.
     * @var string
     */
    public $codserie;

    /**
     * Fecha de la factura
     * @var string
     */
    public $fecha;

    /**
     * Horade la factura
     * @var string
     */
    public $hora;

    /**
     * % de retención IRPF de la factura.
     * Cada línea puede tener uno distinto.
     * @var float
     */
    public $irpf;

    /**
     * Suma total antes de impuestos.
     * @var float
     */
    public $neto;

    /**
     * Nombre del proveedor.
     * @var string
     */
    public $nombre;

    /**
     * Número de la factura.
     * Único dentro de serie+ejercicio.
     * @var string
     */
    public $numero;

    /**
     * Número de factura del proveedor, si lo hay.
     * @var string
     */
    public $numproveedor;
    /**
     * Observaciones de la factura
     * @var string
     */
    public $observaciones;
    /**
     * TRUE => pagada
     * @var bool
     */
    public $pagada;

    /**
     * Tasa de conversión a Euros de la divisa de la factura.
     * @var float
     */
    public $tasaconv;

    /**
     * Importe total de la factura, con impuestos.
     * @var float
     */
    public $total;

    /**
     * Total expresado en euros, por si no fuese la divisa de la factura.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     * @var float
     */
    public $totaleuros;

    /**
     * Suma total de retenciones IRPF de las líneas.
     * @var float
     */
    public $totalirpf;

    /**
     * Suma total del IVA de las líneas.
     * @var float
     */
    public $totaliva;

    /**
     * Suma del recargo de equivalencia de las líneas.
     * @var float
     */
    public $totalrecargo;

    /**
     * TRUE => anulada
     * @var bool
     */
    public $anulada;

    /**
     * Número de documentos adjuntos.
     * @var int
     */
    public $numdocs;

    /**
     * FacturaProveedor constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'facturasprov', ''); // No sé cual es la clave principal
        $this->clear();
        if (!empty($data)) {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->anulada = false;
        $this->cifnif = '';
        $this->codagente = null;
        $this->codalmacen = $this->defaultItems->codAlmacen();
        $this->coddivisa = null;
        $this->codejercicio = null;
        $this->codigo = null;
        $this->codigorect = null;
        $this->codpago = $this->defaultItems->codPago();
        $this->codproveedor = null;
        $this->codserie = $this->defaultItems->codSerie();
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->idasiento = null;
        $this->idasientop = null;
        $this->idfactura = null;
        $this->idfacturarect = null;
        $this->irpf = 0;
        $this->neto = 0;
        $this->nombre = '';
        $this->numero = null;
        $this->numproveedor = null;
        $this->observaciones = null;
        $this->pagada = false;
        $this->tasaconv = 1;
        $this->total = 0;
        $this->totaleuros = 0;
        $this->totalirpf = 0;
        $this->totaliva = 0;
        $this->totalrecargo = 0;

        $this->numdocs = 0;
    }

    /**
     * Acorta el texto de observaciones
     * @return string
     */
    public function observacionesResume()
    {
        if ($this->observaciones === '') {
            return '-';
        }
        if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }
        return substr($this->observaciones, 0, 50) . '...';
    }

    /**
     * Establece la fecha y la hora, pero respetando el ejercicio y las
     * regularizaciones de IVA.
     * Devuelve TRUE si se asigna una fecha u hora distinta a los solicitados.
     *
     * @param string $fecha
     * @param string $hora
     *
     * @return bool
     */
    public function setFechaHora($fecha, $hora)
    {
        $cambio = false;

        if ($this->numero === null) { /// nueva factura
            $this->fecha = $fecha;
            $this->hora = $hora;
        } elseif ($fecha !== $this->fecha) { /// factura existente y cambiamos fecha
            $cambio = true;

            $eje0 = new Ejercicio();
            $ejercicio = $eje0->get($this->codejercicio);
            if ($ejercicio) {
                /// ¿El ejercicio actual está abierto?
                if ($ejercicio->abierto()) {
                    $eje2 = $eje0->getByFecha($fecha);
                    if ($eje2) {
                        if ($eje2->abierto()) {
                            /// ¿La factura está dentro de alguna regularización?
                            $regiva0 = new RegularizacionIva();
                            if ($regiva0->getFechaInside($this->fecha)) {
                                $this->miniLog->alert('La factura se encuentra dentro de una regularización de '
                                    . FS_IVA . '. No se puede modificar la fecha.');
                            } elseif ($regiva0->getFechaInside($fecha)) {
                                $this->miniLog->alert('No se puede asignar la fecha ' . $fecha . ' porque ya hay'
                                    . ' una regularización de ' . FS_IVA . ' para ese periodo.');
                            } else {
                                $cambio = false;
                                $this->fecha = $fecha;
                                $this->hora = $hora;

                                /// ¿El ejercicio es distinto?
                                if ($this->codejercicio !== $eje2->codejercicio) {
                                    $this->codejercicio = $eje2->codejercicio;
                                    $this->newCodigo();
                                }
                            }
                        } else {
                            $this->miniLog->alert(
                                'El ejercicio ' . $eje2->nombre . ' está cerrado. No se puede modificar la fecha.'
                            );
                        }
                    }
                } else {
                    $this->miniLog->alert(
                        'El ejercicio ' . $ejercicio->nombre . ' está cerrado. No se puede modificar la fecha.'
                    );
                }
            } else {
                $this->miniLog->alert('Ejercicio no encontrado.');
            }
        } elseif ($hora !== $this->hora) { /// factura existente y cambiamos hora
            $this->hora = $hora;
        }

        return $cambio;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->idfactura === null) {
            return 'index.php?page=ComprasFacturas';
        }
        return 'index.php?page=ComprasFactura&id=' . $this->idfactura;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos del asiento
     * @return string
     */
    public function asientoUrl()
    {
        if ($this->idasiento === null) {
            return 'index.php?page=ContabilidadAsientos';
        }
        return 'index.php?page=ContabilidadAsiento&id=' . $this->idasiento;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos del asiento de pago
     * @return string
     */
    public function asientoPagoUrl()
    {
        if ($this->idasientop === null) {
            return 'index.php?page=ContabilidadAsientos';
        }
        return 'index.php?page=ContabilidadAsiento&id=' . $this->idasientop;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos del agente
     * @return string
     */
    public function agenteUrl()
    {
        if ($this->codagente === null) {
            return 'index.php?page=AdminAgentes';
        }
        return 'index.php?page=AdminAgente&cod=' . $this->codagente;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos del proveedor
     * @return string
     */
    public function proveedorUrl()
    {
        if ($this->codproveedor === null) {
            return 'index.php?page=ComprasProveedores';
        }
        return 'index.php?page=ComprasProveedor&cod=' . $this->codproveedor;
    }

    /**
     * Devuelve las líneas de la factura.
     * @return array
     */
    public function getLineas()
    {
        $linea = new LineaFacturaProveedor();
        return $linea->allFromFactura($this->idfactura);
    }

    /**
     * Devuelve las líneas de IVA de la factura.
     * Si no hay, las crea.
     * @return array
     */
    public function getLineasIva()
    {
        $lineaIva = new LineaIvaFacturaProveedor();
        $lineasi = $lineaIva->allFromFactura($this->idfactura);
        /// si no hay lineas de IVA las generamos
        if (!empty($lineasi)) {
            $lineas = $this->getLineas();
            if (!empty($lineas)) {
                foreach ($lineas as $l) {
                    $i = 0;
                    $encontrada = false;
                    while ($i < count($lineasi)) {
                        if ($l->iva === $lineasi[$i]->iva && $l->recargo === $lineasi[$i]->recargo) {
                            $encontrada = true;
                            $lineasi[$i]->neto += $l->pvptotal;
                            $lineasi[$i]->totaliva += ($l->pvptotal * $l->iva) / 100;
                            $lineasi[$i]->totalrecargo += ($l->pvptotal * $l->recargo) / 100;
                        }
                        $i++;
                    }
                    if (!$encontrada) {
                        $lineasi[$i] = new LineaIvaFacturaProveedor();
                        $lineasi[$i]->idfactura = $this->idfactura;
                        $lineasi[$i]->codimpuesto = $l->codimpuesto;
                        $lineasi[$i]->iva = $l->iva;
                        $lineasi[$i]->recargo = $l->recargo;
                        $lineasi[$i]->neto = $l->pvptotal;
                        $lineasi[$i]->totaliva = ($l->pvptotal * $l->iva) / 100;
                        $lineasi[$i]->totalrecargo = ($l->pvptotal * $l->recargo) / 100;
                    }
                }

                /// redondeamos y guardamos
                if (count($lineasi) === 1) {
                    $lineasi[0]->neto = round($lineasi[0]->neto, FS_NF0);
                    $lineasi[0]->totaliva = round($lineasi[0]->totaliva, FS_NF0);
                    $lineasi[0]->totalrecargo = round($lineasi[0]->totalrecargo, FS_NF0);
                    $lineasi[0]->totallinea = $lineasi[0]->neto + $lineasi[0]->totaliva + $lineasi[0]->totalrecargo;
                    $lineasi[0]->save();
                } else {
                    /*
                     * Como el neto y el iva se redondean en la factura, al dividirlo
                     * en líneas de iva podemos encontrarnos con un descuadre que
                     * hay que calcular y solucionar.
                     */
                    $tNeto = 0;
                    $tIva = 0;
                    foreach ($lineasi as $li) {
                        $li->neto = bround($li->neto, FS_NF0);
                        $li->totaliva = bround($li->totaliva, FS_NF0);
                        $li->totallinea = $li->neto + $li->totaliva + $li->totalrecargo;

                        $tNeto += $li->neto;
                        $tIva += $li->totaliva;
                    }

                    if (!$this->floatcmp($this->neto, $tNeto)) {
                        /*
                         * Sumamos o restamos un céntimo a los netos más altos
                         * hasta que desaparezca el descuadre
                         */
                        $diferencia = round(($this->neto - $tNeto) * 100);
                        usort(
                            $lineasi,
                            function ($a, $b) {
                                if ($a->totallinea === $b->totallinea) {
                                    return 0;
                                }
                                if ($a->totallinea < 0) {
                                    return ($a->totallinea < $b->totallinea) ? -1 : 1;
                                }
                                return ($a->totallinea < $b->totallinea) ? 1 : -1;
                            }
                        );

                        foreach ($lineasi as $i => $value) {
                            if ($diferencia > 0) {
                                $lineasi[$i]->neto += .01;
                                $diferencia--;
                            } elseif ($diferencia < 0) {
                                $lineasi[$i]->neto -= .01;
                                $diferencia++;
                            } else {
                                break;
                            }
                        }
                    }

                    if (!$this->floatcmp($this->totaliva, $tIva)) {
                        /*
                         * Sumamos o restamos un céntimo a los importes más altos
                         * hasta que desaparezca el descuadre
                         */
                        $diferencia = round(($this->totaliva - $tIva) * 100);
                        usort(
                            $lineasi,
                            function ($a, $b) {
                                if ($a->totaliva === $b->totaliva) {
                                    return 0;
                                }
                                if ($a->totaliva < 0) {
                                    return ($a->totaliva < $b->totaliva) ? -1 : 1;
                                }
                                return ($a->totaliva < $b->totaliva) ? 1 : -1;
                            }
                        );

                        foreach ($lineasi as $i => $value) {
                            if ($diferencia > 0) {
                                $lineasi[$i]->totaliva += .01;
                                $diferencia--;
                            } elseif ($diferencia < 0) {
                                $lineasi[$i]->totaliva -= .01;
                                $diferencia++;
                            } else {
                                break;
                            }
                        }
                    }

                    foreach ($lineasi as $i => $value) {
                        $lineasi[$i]->totallinea = $value->neto + $value->totaliva + $value->totalrecargo;
                        $lineasi[$i]->save();
                    }
                }
            }
        }
        return $lineasi;
    }

    /**
     * Devuelve el asiento asociado a la factura
     * @return bool|mixed
     */
    public function getAsiento()
    {
        $asiento = new Asiento();
        return $asiento->get($this->idasiento);
    }

    /**
     * Devuelve el asiento de pago asociado a la factura
     * @return bool|mixed
     */
    public function getAsientoPago()
    {
        $asiento = new Asiento();
        return $asiento->get($this->idasientop);
    }

    /**
     * Devuelve un array con todas las facturas rectificativas de esta factura.
     * @return array
     */
    public function getRectificativas()
    {
        $devoluciones = [];

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE idfacturarect = ' . $this->var2str($this->idfactura) . ';';
        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $devoluciones[] = new FacturaProveedor($d);
            }
        }

        return $devoluciones;
    }

    /**
     * Devuelve una factura por su código
     *
     * @param string $cod
     *
     * @return bool|FacturaProveedor
     */
    public function getByCodigo($cod)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codigo = ' . $this->var2str($cod) . ';';
        $fact = $this->database->select($sql);
        if (!empty($fact)) {
            return new FacturaProveedor($fact[0]);
        }
        return false;
    }

    /**
     * Genera el número y código de la factura.
     */
    public function newCodigo()
    {
        /// buscamos un hueco o el siguiente número disponible
        $encontrado = false;
        $num = 1;
        $sql = 'SELECT ' . $this->database->sql2Int('numero') . ' as numero,fecha,hora FROM ' . $this->tableName();
        if (FS_NEW_CODIGO !== 'NUM' && FS_NEW_CODIGO !== '0-NUM') {
            $sql .= ' WHERE codejercicio = ' . $this->var2str($this->codejercicio)
                . ' AND codserie = ' . $this->var2str($this->codserie);
        }
        $sql .= ' ORDER BY numero ASC;';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                if ((int)$d['numero'] < $num) {
                    /**
                     * El número de la factura es menor que el inicial.
                     * El usuario ha cambiado el número inicial después de hacer
                     * facturas.
                     */
                } elseif ((int)$d['numero'] === $num) {
                    /// el número es correcto, avanzamos
                    $num++;
                } else {
                    /// Hemos encontrado un hueco
                    $encontrado = true;
                    break;
                }
            }
        }

        $this->numero = $num;

        if (!$encontrado) {
            /// nos guardamos la secuencia para abanq/eneboo
            $sec0 = new Secuencia();
            $sec = $sec0->getByParams2($this->codejercicio, $this->codserie, 'nfacturaprov');
            if ($sec && $sec->valorout <= $this->numero) {
                $sec->valorout = 1 + $this->numero;
                $sec->save();
            }
        }

        $this->codigo = fsDocumentoNewCodigo(FS_FACTURA, $this->codejercicio, $this->codserie, $this->numero, 'C');
    }

    /**
     * Comprueba los datos de la factura, devuelve TRUE si está correcto
     * @return bool
     */
    public function test()
    {
        $this->nombre = static::noHtml($this->nombre);
        if ($this->nombre === '') {
            $this->nombre = '-';
        }

        $this->numproveedor = static::noHtml($this->numproveedor);
        $this->observaciones = static::noHtml($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->floatcmp(
            $this->total,
            $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo,
            FS_NF0,
            true
        )) {
            return true;
        }
        $this->miniLog->alert('Error grave: El total está mal calculado. ¡Informa del error!');
        return false;
    }

    /**
     * TODO
     *
     * @param bool $duplicados
     *
     * @return bool
     */
    public function fullTest($duplicados = true)
    {
        $status = true;

        /// comprobamos la fecha de la factura
        $ejercicio = new Ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            if (strtotime($this->fecha) < strtotime($eje0->fechainicio) ||
                strtotime($this->fecha) > strtotime($eje0->fechafin)) {
                $status = false;
                $this->miniLog->alert(
                    'La fecha de esta factura está fuera del rango del'
                    . " <a target='_blank' href='" . $eje0->url() . "'>ejercicio</a>."
                );
            }
        }

        /// comprobamos las líneas
        $neto = 0;
        $iva = 0;
        $irpf = 0;
        $recargo = 0;
        foreach ($this->getLineas() as $l) {
            if (!$l->test()) {
                $status = false;
            }

            $neto += $l->pvptotal;
            $iva += $l->pvptotal * $l->iva / 100;
            $irpf += $l->pvptotal * $l->irpf / 100;
            $recargo += $l->pvptotal * $l->recargo / 100;
        }

        $neto = round($neto, FS_NF0);
        $iva = round($iva, FS_NF0);
        $irpf = round($irpf, FS_NF0);
        $recargo = round($recargo, FS_NF0);
        $total = $neto + $iva - $irpf + $recargo;

        if (!$this->floatcmp($this->neto, $neto, FS_NF0, true)) {
            $this->miniLog->alert('Valor neto de la factura ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $neto);
            $status = false;
        } elseif (!$this->floatcmp($this->totaliva, $iva, FS_NF0, true)) {
            $this->miniLog->alert('Valor totaliva de la factura ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $iva);
            $status = false;
        } elseif (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totalirpf de la factura ' . $this->codigo . ' incorrecto. Valor correcto: ' . $irpf
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, true)) {
            $this->miniLog->alert('Valor totalrecargo de la factura ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $recargo);
            $status = false;
        } elseif (!$this->floatcmp($this->total, $total, FS_NF0, true)) {
            $this->miniLog->alert('Valor total de la factura ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $total);
            $status = false;
        }

        /// comprobamos las líneas de IVA
        $this->getLineasIva();
        $lineaIva = new LineaIvaFacturaProveedor();
        if (!$lineaIva->facturaTest($this->idfactura, $neto, $iva, $recargo)) {
            $status = false;
        }

        /// comprobamos el asiento
        if ($this->idasiento !== null) {
            $asiento = $this->getAsiento();
            if ($asiento) {
                if ($asiento->tipodocumento !== 'Factura de proveedor' || $asiento->documento !== $this->codigo) {
                    $this->miniLog->alert(
                        "Esta factura apunta a un <a href='" . $this->asientoUrl() . "'>asiento incorrecto</a>."
                    );
                    $status = false;
                } elseif ($this->coddivisa === $this->defaultItems->codDivisa() &&
                    (abs($asiento->importe) - abs($this->total + $this->totalirpf) >= .02)) {
                    $this->miniLog->alert('El importe del asiento es distinto al de la factura.');
                    $status = false;
                } else {
                    $asientop = $this->getAsientoPago();
                    if ($asientop) {
                        if ($this->totalirpf !== 0) {
                            /// excluimos la comprobación si la factura tiene IRPF
                        } elseif (!$this->floatcmp($asiento->importe, $asientop->importe)) {
                            $this->miniLog->alert('No coinciden los importes de los asientos.');
                            $status = false;
                        }
                    }
                }
            } else {
                $this->miniLog->alert('Asiento no encontrado.');
                $status = false;
            }
        }

        if ($status && $duplicados) {
            /// comprobamos si es un duplicado
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fecha = ' . $this->var2str($this->fecha)
                . ' AND codproveedor = ' . $this->var2str($this->codproveedor)
                . ' AND total = ' . $this->var2str($this->total)
                . ' AND codagente = ' . $this->var2str($this->codagente)
                . ' AND numproveedor = ' . $this->var2str($this->numproveedor)
                . ' AND observaciones = ' . $this->var2str($this->observaciones)
                . ' AND idfactura != ' . $this->var2str($this->idfactura) . ';';
            $facturas = $this->database->select($sql);
            if (!empty($facturas)) {
                foreach ($facturas as $fac) {
                    /// comprobamos las líneas
                    $sql = 'SELECT referencia FROM lineasfacturasprov WHERE
                  idfactura = ' . $this->var2str($this->idfactura) . '
                  AND referencia NOT IN (SELECT referencia FROM lineasfacturasprov
                  WHERE idfactura = ' . $this->var2str($fac['idfactura']) . ');';
                    $aux = $this->database->select($sql);
                    if (!empty($aux)) {
                        $this->miniLog->alert("Esta factura es un posible duplicado de
                     <a href='index.php?page=ComprasFactura&id=" . $fac['idfactura'] . "'>esta otra</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                        $status = false;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Elimina la factura de la base de datos.
     * @return bool
     */
    public function delete()
    {
        $bloquear = false;

        $eje0 = new Ejercicio();
        $ejercicio = $eje0->get($this->codejercicio);
        if ($ejercicio) {
            if ($ejercicio->abierto()) {
                $reg0 = new RegularizacionIva();
                if ($reg0->getFechaInside($this->fecha)) {
                    $this->miniLog->alert('La factura se encuentra dentro de una regularización de '
                        . FS_IVA . '. No se puede eliminar.');
                    $bloquear = true;
                } else {
                    foreach ($this->getRectificativas() as $rect) {
                        $this->miniLog->alert('La factura ya tiene una rectificativa. No se puede eliminar.');
                        $bloquear = true;
                        break;
                    }
                }
            } else {
                $this->miniLog->alert('El ejercicio ' . $ejercicio->nombre . ' está cerrado.');
                $bloquear = true;
            }
        }

        /// desvincular albaranes asociados y eliminar factura
        $sql = 'UPDATE albaranesprov SET idfactura = NULL, ptefactura = TRUE'
            . ' WHERE idfactura = ' . $this->var2str($this->idfactura) . ';'
            . 'DELETE FROM ' . $this->tableName() . ' WHERE idfactura = ' . $this->var2str($this->idfactura) . ';';

        if ($bloquear) {
            return false;
        }
        if ($this->database->exec($sql)) {
            if ($this->idasiento) {
                /**
                 * Delegamos la eliminación del asiento en la clase correspondiente.
                 */
                $asiento = new Asiento();
                $asi0 = $asiento->get($this->idasiento);
                if ($asi0) {
                    $asi0->delete();
                }

                $asi1 = $asiento->get($this->idasientop);
                if ($asi1) {
                    $asi1->delete();
                }
            }

            $this->miniLog->info(ucfirst(FS_FACTURA) . ' de compra ' . $this->codigo . ' eliminada correctamente.');
            return true;
        }
        return false;
    }

    /**
     * Devuelve un array con las últimas facturas
     *
     * @param int $offset
     * @param int $limit
     * @param string $order
     *
     * @return array
     */
    public function all($offset = 0, $limit = FS_ITEM_LIMIT, $order = 'fecha DESC, codigo DESC')
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' ORDER BY ' . $order;

        $data = $this->database->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaProveedor($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas sin pagar.
     *
     * @param int $offset
     * @param int $limit
     * @param string $order
     *
     * @return array
     */
    public function allSinPagar($offset = 0, $limit = FS_ITEM_LIMIT, $order = 'fecha ASC, codigo ASC')
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE pagada = FALSE ORDER BY ' . $order;

        $data = $this->database->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaProveedor($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas del agente/empleado
     *
     * @param string $codagente
     * @param int $offset
     *
     * @return array
     */
    public function allFromAgente($codagente, $offset = 0)
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() .
            ' WHERE codagente = ' . $this->var2str($codagente) .
            ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaProveedor($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas del proveedor
     *
     * @param string $codproveedor
     * @param int $offset
     *
     * @return array
     */
    public function allFromProveedor($codproveedor, $offset = 0)
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() .
            ' WHERE codproveedor = ' . $this->var2str($codproveedor) .
            ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaProveedor($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas comprendidas entre $desde y $hasta
     *
     * @param string $desde
     * @param string $hasta
     * @param string $codserie código de la serie
     * @param string $codagente código del empleado
     * @param string $codproveedor código del proveedor
     * @param string $estado
     * @param string $codpago código de la forma de pago
     * @param string $codalmacen código del almacén
     *
     * @return array
     */
    public function allDesde($desde, $hasta, $codserie = '', $codagente = '', $codproveedor = '', $estado = '', $codpago = '', $codalmacen = '')
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE fecha >= ' . $this->var2str($desde) . ' AND fecha <= ' . $this->var2str($hasta);
        if ($codserie !== '') {
            $sql .= ' AND codserie = ' . $this->var2str($codserie);
        }
        if ($codagente !== '') {
            $sql .= ' AND codagente = ' . $this->var2str($codagente);
        }
        if ($codproveedor !== '') {
            $sql .= ' AND codproveedor = ' . $this->var2str($codproveedor);
        }
        if ($estado !== '') {
            if ($estado === 'pagada') {
                $sql .= ' AND pagada = true';
            } else {
                $sql .= ' AND pagada = false';
            }
        }
        if ($codpago !== '') {
            $sql .= ' AND codpago = ' . $this->var2str($codpago);
        }
        if ($codalmacen !== '') {
            $sql .= ' AND codalmacen = ' . $this->var2str($codalmacen);
        }
        $sql .= ' ORDER BY fecha ASC, codigo ASC;';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaProveedor($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas coincidentes con $query
     *
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $faclist = [];
        $query = mb_strtolower(static::noHtml($query), 'UTF8');

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query
                . "%' OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaProveedor($f);
            }
        }

        return $faclist;
    }

    /**
     * TODO
     */
    public function cronJob()
    {
    }

    /**
     * Inserta los datos del modelo en la base de datos.
     * @return bool
     */
    private function saveInsert()
    {
        $this->newCodigo();
        return $this->saveInsertTrait();
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    private function install()
    {
        // new Serie();
        // new Asiento();

        return '';
    }
}
