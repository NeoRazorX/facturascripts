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

/**
 * Factura de un cliente.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FacturaCliente
{

    use Base\ModelTrait {
        saveInsert as private saveInsertTrait;
    }

    /**
     * Clave primaria.
     * @var int
     */
    public $idfactura;

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
     * ID de la factura que rectifica.
     * @var int
     */
    public $idfacturarect;

    /**
     * Código único de la factura. Para humanos.
     * @var string
     */
    public $codigo;

    /**
     * Número de la factura.
     * Único dentro de la serie+ejercicio.
     * @var string
     */
    public $numero;

    /**
     * Número opcional a disposición del usuario.
     * @var string
     */
    public $numero2;

    /**
     * Código de la factura que rectifica.
     * @var string
     */
    public $codigorect;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     * @var string
     */
    public $codejercicio;

    /**
     * Serie relacionada.
     * @var string
     */
    public $codserie;

    /**
     * Almacén del que sale la mercancía.
     * @var string
     */
    public $codalmacen;

    /**
     * Forma de pago.
     * @var string
     */
    public $codpago;

    /**
     * Divisa de la factura.
     * @var string
     */
    public $coddivisa;

    /**
     * Fecha de la factura
     * @var string
     */
    public $fecha;

    /**
     * Hora de la factura
     * @var string
     */
    public $hora;

    /**
     * Código identificador del cliente de la factura.
     * @var string
     */
    public $codcliente;

    /**
     * Nombre del cliente de la factura
     * @var string
     */
    public $nombrecliente;

    /**
     * CIF/NIF del cliente
     * @var string
     */
    public $cifnif;

    /**
     * Dirección del cliente
     * @var string
     */
    public $direccion;

    /**
     * Ciudad del cliente
     * @var string
     */
    public $ciudad;

    /**
     * Provincia del cliente
     * @var string
     */
    public $provincia;

    /**
     * Apartado de correos del cliente
     * @var string
     */
    public $apartado;
    /// datos de transporte
    /**
     * Código del transportista
     * @var string
     */
    public $envio_codtrans;

    /**
     * Código de seguimiento
     * @var string
     */
    public $envio_codigo;

    /**
     * Nombre para la dirección del envío
     * @var string
     */
    public $envio_nombre;

    /**
     * Apellidos para la dirección del envío
     * @var string
     */
    public $envio_apellidos;

    /**
     * Apartado de correos para la dirección del envío
     * @var string
     */
    public $envio_apartado;

    /**
     * Dirección para el envío
     * @var string
     */
    public $envio_direccion;

    /**
     * Código postal para la dirección del envío
     * @var string
     */
    public $envio_codpostal;

    /**
     * Ciudad para la dirección del envío
     * @var string
     */
    public $envio_ciudad;

    /**
     * Provincia para la dirección del envío
     * @var string
     */
    public $envio_provincia;

    /**
     * Código del país para la dirección del envío
     * @var string
     */
    public $envio_codpais;

    /**
     * ID de la dirección en dirclientes.
     * Modelo direccion_cliente.
     * @var string
     */
    public $coddir;

    /**
     * Código postal del cliente
     * @var string
     */
    public $codpostal;

    /**
     * Código del país del cliente
     * @var string
     */
    public $codpais;

    /**
     * Empleado que ha creado la factura.
     * Modelo agente.
     * @var string
     */
    public $codagente;

    /**
     * Suma de los pvptotal de las líneas.
     * Es el total antes de impuestos.
     * @var float
     */
    public $neto;

    /**
     * Suma total del IVA de las líneas.
     * @var float
     */
    public $totaliva;

    /**
     * Suma total de la factura, con impuestos.
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
     * % de retención IRPF de la factura.
     * Puede variar en cada línea.
     * @var float
     */
    public $irpf;

    /**
     * Suma total de retenciones IRPF de las líneas.
     * @var float
     */
    public $totalirpf;

    /**
     * % comisión del empleado (agente).
     * @var float
     */
    public $porcomision;

    /**
     * Tasa de conversión a Euros de la divisa de la factura.
     * @var float
     */
    public $tasaconv;

    /**
     * Suma del recargo de equivalencia de las líneas.
     * @var float
     */
    public $totalrecargo;

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
     * TRUE => anulada
     * @var bool
     */
    public $anulada;

    /**
     * Fecha de vencimiento de la factura.
     * @var string
     */
    public $vencimiento;

    /**
     * Fecha en la que se envió la factura por email.
     * @var string
     */
    public $femail;

    /**
     * Identificador opcional para la impresión. Todavía sin uso.
     * Se puede usar para identificar una forma de impresión y usar siempre
     * esa en esta factura.
     * @var int
     */
    public $idimprenta;

    /**
     * Número de documentos adjuntos.
     * @var int
     */
    public $numdocs;

    /**
     * FacturaCliente constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'facturascli', 'idfactura');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->idfactura = null;
        $this->idasiento = null;
        $this->idasientop = null;
        $this->idfacturarect = null;
        $this->codigo = null;
        $this->numero = null;
        $this->numero2 = null;
        $this->codigorect = null;
        $this->codejercicio = null;
        $this->codserie = $this->defaultItems->codSerie();
        $this->codalmacen = $this->defaultItems->codAlmacen();
        $this->codpago = $this->defaultItems->codPago();
        $this->coddivisa = null;
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->codcliente = null;
        $this->nombrecliente = '';
        $this->cifnif = '';
        $this->direccion = null;
        $this->provincia = null;
        $this->ciudad = null;
        $this->apartado = null;
        $this->coddir = null;
        $this->codpostal = null;
        $this->codpais = null;
        $this->codagente = null;
        $this->neto = 0;
        $this->totaliva = 0;
        $this->total = 0;
        $this->totaleuros = 0;
        $this->irpf = 0;
        $this->totalirpf = 0;
        $this->porcomision = 0;
        $this->tasaconv = 1;
        $this->totalrecargo = 0;
        $this->observaciones = null;
        $this->pagada = false;
        $this->anulada = false;
        $this->vencimiento = date('d-m-Y', strtotime('+1 day'));
        $this->femail = null;

        $this->envio_codtrans = null;
        $this->envio_codigo = null;
        $this->envio_nombre = null;
        $this->envio_apellidos = null;
        $this->envio_apartado = null;
        $this->envio_direccion = null;
        $this->envio_codpostal = null;
        $this->envio_ciudad = null;
        $this->envio_provincia = null;
        $this->envio_codpais = null;

        $this->idimprenta = null;
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
     * Devuelve true su está vencida, sino false
     * @return bool
     */
    public function vencida()
    {
        if ($this->pagada) {
            return false;
        }
        return (strtotime($this->vencimiento) < strtotime(date('d-m-Y')));
    }

    /**
     * Establece la fecha y la hora, pero respetando la numeración, el ejercicio
     * y las regularizaciones de IVA.
     * Devuelve TRUE si se asigna una fecha distinta a los solicitados.
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
            /// buscamos la última fecha usada en una factura en esta serie y ejercicio
            $sql = 'SELECT MAX(fecha) AS fecha FROM ' . $this->tableName()
                . ' WHERE codserie = ' . $this->var2str($this->codserie)
                . ' AND codejercicio = ' . $this->var2str($this->codejercicio) . ';';

            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                if (strtotime($data[0]['fecha']) > strtotime($fecha)) {
                    $fechaOld = $fecha;
                    $fecha = date('d-m-Y', strtotime($data[0]['fecha']));

                    $this->miniLog->alert('Ya hay facturas posteriores a la fecha seleccionada (' . $fechaOld . ').'
                        . ' Nueva fecha asignada: ' . $fecha);
                    $cambio = true;
                }
            }

            /// ahora buscamos la última hora usada para esa fecha, serie y ejercicio
            $sql = 'SELECT MAX(hora) AS hora FROM ' . $this->tableName()
                . ' WHERE codserie = ' . $this->var2str($this->codserie)
                . ' AND codejercicio = ' . $this->var2str($this->codejercicio)
                . ' AND fecha = ' . $this->var2str($fecha) . ';';

            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                if (strtotime($data[0]['hora']) > strtotime($hora) || $cambio) {
                    $hora = date('H:i:s', strtotime($data[0]['hora']));
                    $cambio = true;
                }
            }

            $this->fecha = $fecha;
            $this->hora = $hora;
        } elseif ($fecha !== $this->fecha) { /// factura existente y cambiamos fecha
            $cambio = true;

            $eje0 = new Ejercicio();
            $ejercicio = $eje0->get($this->codejercicio);
            if ($ejercicio) {
                if (!$ejercicio->abierto()) {
                    $this->miniLog->alert(
                        'El ejercicio ' . $ejercicio->nombre . ' está cerrado. No se puede modificar la fecha.'
                    );
                } elseif ($fecha === $ejercicio->get_best_fecha($fecha)) {
                    $regiva0 = new RegularizacionIva();
                    if ($regiva0->getFechaInside($fecha)) {
                        $this->miniLog->alert('No se puede asignar la fecha ' . $fecha . ' porque ya hay'
                            . ' una regularización de ' . FS_IVA . ' para ese periodo.');
                    } elseif ($regiva0->getFechaInside($this->fecha)) {
                        $this->miniLog->alert('La factura se encuentra dentro de una regularización de '
                            . FS_IVA . '. No se puede modificar la fecha.');
                    } else {
                        $this->fecha = $fecha;
                        $this->hora = $hora;
                        $cambio = false;
                    }
                } else {
                    $this->miniLog->alert('La fecha está fuera del rango del ejercicio ' . $ejercicio->nombre);
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
            return 'index.php?page=VentasFacturas';
        }
        return 'index.php?page=VentasFactura&id=' . $this->idfactura;
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
     * Devuelve la url donde ver/modificar estos datos del cliente
     * @return string
     */
    public function clienteUrl()
    {
        if ($this->codcliente === null) {
            return 'index.php?page=VentasClientes';
        }
        return 'index.php?page=VentasCliente&cod=' . $this->codcliente;
    }

    /**
     * Devuelve el asiento asociado
     * @return bool|Asiento
     */
    public function getAsiento()
    {
        $asiento = new Asiento();
        return $asiento->get($this->idasiento);
    }

    /**
     * Devuelve el asiento de pago asociado
     * @return bool|mixed
     */
    public function getAsientoPago()
    {
        $asiento = new Asiento();
        return $asiento->get($this->idasientop);
    }

    /**
     * Devulve las líneas de la factura.
     * @return array
     */
    public function getLineas()
    {
        $linea = new LineaFacturaCliente();
        return $linea->allFromFactura($this->idfactura);
    }

    /**
     * Devuelve las líneas de IVA de la factura.
     * Si no hay, las crea.
     * @return array
     */
    public function getLineasIva()
    {
        $lineaIva = new LineaIvaFacturaCliente();
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
                        $lineasi[$i] = new LineaIvaFacturaCliente();
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
                    $lineasi[0]->totaliva = round($lineasi[0]->totaliva, FS_NF0);
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
                        $li->totallinea = $li->neto + $li->totaliva;

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
                            $lineasi, function($a, $b) {
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
                            $lineasi, function($a, $b) {
                            if ($a->totaliva === $b->totaliva) {
                                return 0;
                            }
                            if ($a->totallinea < 0) {
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
     * Devuelve un array con todas las facturas rectificativas de esta factura.
     * @return array
     */
    public function getRectificativas()
    {
        $devoluciones = [];

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE idfacturarect = ' . $this->var2str($this->idfactura) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $devoluciones[] = new FacturaCliente($d);
            }
        }

        return $devoluciones;
    }

    /**
     * Devuelve la factura por su código
     *
     * @param string $cod
     *
     * @return bool|FacturaCliente
     */
    public function getByCodigo($cod)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codigo = ' . $this->var2str($cod) . ';';
        $fact = $this->dataBase->select($sql);
        if (!empty($fact)) {
            return new FacturaCliente($fact[0]);
        }
        return false;
    }

    /**
     * Devuelve la factura por número, serie y ejercicio
     *
     * @param string $num
     * @param string $serie
     * @param string $eje
     *
     * @return bool|FacturaCliente
     */
    public function getByNumSerie($num, $serie, $eje)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE numero = ' . $this->var2str($num)
            . ' AND codserie = ' . $this->var2str($serie)
            . ' AND codejercicio = ' . $this->var2str($eje) . ';';

        $fact = $this->dataBase->select($sql);
        if (!empty($fact)) {
            return new FacturaCliente($fact[0]);
        }
        return false;
    }

    /**
     * Genera el número y código de la factura.
     */
    public function newCodigo()
    {
        /// buscamos el número inicial para la serie
        $num = 1;
        $serie0 = new Serie();
        $serie = $serie0->get($this->codserie);
        /// ¿Se ha definido un nº de factura inicial para esta serie y ejercicio?
        if ($serie && $this->codejercicio === $serie->codejercicio) {
            $num = $serie->numfactura;
        }

        /// buscamos un hueco o el siguiente número disponible
        $encontrado = false;
        $fecha = $this->fecha;
        $hora = $this->hora;
        $sql = 'SELECT ' . $this->dataBase->sql2Int('numero') . ' as numero,fecha,hora FROM ' . $this->tableName();
        if (FS_NEW_CODIGO !== 'NUM' && FS_NEW_CODIGO !== '0-NUM') {
            $sql .= ' WHERE codejercicio = ' . $this->var2str($this->codejercicio)
                . ' AND codserie = ' . $this->var2str($this->codserie);
        }
        $sql .= ' ORDER BY numero ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                if ((int) $d['numero'] < $num) {
                    /**
                     * El número de la factura es menor que el inicial.
                     * El usuario ha cambiado el número inicial después de hacer
                     * facturas.
                     */
                } elseif ((int) $d['numero'] === $num) {
                    /// el número es correcto, avanzamos
                    $num++;
                } else {
                    /// Hemos encontrado un hueco y debemos usar el número y la fecha.
                    $encontrado = true;
                    $fecha = date('d-m-Y', strtotime($d['fecha']));
                    $hora = date('H:i:s', strtotime($d['hora']));
                    break;
                }
            }
        }

        $this->numero = $num;

        if ($encontrado) {
            $this->fecha = $fecha;
            $this->hora = $hora;
        } else {
            /// nos guardamos la secuencia para abanq/eneboo
            $sec0 = new Secuencia();
            $sec = $sec0->getByParams2($this->codejercicio, $this->codserie, 'nfacturacli');
            if ($sec && $sec->valorout <= $this->numero) {
                $sec->valorout = 1 + $this->numero;
                $sec->save();
            }
        }

        $this->codigo = fsDocumentoNewCodigo(FS_FACTURA, $this->codejercicio, $this->codserie, $this->numero);
    }

    /**
     * Comprueba los datos de la factura, devuelve TRUE si está correcto
     * @return bool
     */
    public function test()
    {
        $this->nombrecliente = static::noHtml($this->nombrecliente);
        if ($this->nombrecliente === '') {
            $this->nombrecliente = '-';
        }

        $this->direccion = static::noHtml($this->direccion);
        $this->ciudad = static::noHtml($this->ciudad);
        $this->provincia = static::noHtml($this->provincia);
        $this->envio_nombre = static::noHtml($this->envio_nombre);
        $this->envio_apellidos = static::noHtml($this->envio_apellidos);
        $this->envio_direccion = static::noHtml($this->envio_direccion);
        $this->envio_ciudad = static::noHtml($this->envio_ciudad);
        $this->envio_provincia = static::noHtml($this->envio_provincia);
        $this->numero2 = static::noHtml($this->numero2);
        $this->observaciones = static::noHtml($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->floatcmp(
                $this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true
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
                $this->miniLog->alert('La fecha de esta factura está fuera del rango del'
                    . " <a target='_blank' href='" . $eje0->url() . "'>ejercicio</a>.");
            }
        }
        $numero0 = (int) $this->numero - 1;
        if ($numero0 > 0) {
            $fac0 = $this->getByNumSerie($numero0, $this->codserie, $this->codejercicio);
            if ($fac0) {
                if (strtotime($fac0->fecha) > strtotime($this->fecha)) {
                    $status = false;
                    $this->miniLog->alert("La fecha de esta factura es anterior a la fecha de <a href='" .
                        $fac0->url() . "'>la factura anterior</a>.");
                }
            }
        }
        $numero2 = (int) $this->numero + 1;
        $fac2 = $this->getByNumSerie($numero2, $this->codserie, $this->codejercicio);
        if ($fac2) {
            if (strtotime($fac2->fecha) < strtotime($this->fecha)) {
                $status = false;
                $this->miniLog->alert("La fecha de esta factura es posterior a la fecha de <a href='" .
                    $fac2->url() . "'>la factura siguiente</a>.");
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
            $this->miniLog->alert('Valor neto de la factura ' . $this->codigo . ' incorrecto. Valor correcto: ' . $neto);
            $status = false;
        } elseif (!$this->floatcmp($this->totaliva, $iva, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totaliva de la factura ' . $this->codigo . ' incorrecto. Valor correcto: ' . $iva
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totalirpf de la factura ' . $this->codigo . ' incorrecto. Valor correcto: ' . $irpf
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totalrecargo de la factura ' . $this->codigo . ' incorrecto. Valor correcto: ' . $recargo
            );
            $status = false;
        } elseif (!$this->floatcmp($this->total, $total, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor total de la factura ' . $this->codigo . ' incorrecto. Valor correcto: ' . $total
            );
            $status = false;
        }

        /// comprobamos las líneas de IVA
        $this->getLineasIva();
        $lineaIva = new LineaIvaFacturaCliente();
        if (!$lineaIva->facturaTest($this->idfactura, $neto, $iva, $recargo)) {
            $status = false;
        }

        /// comprobamos el asiento
        if ($this->idasiento !== null) {
            $asiento = $this->getAsiento();
            if ($asiento) {
                if ($asiento->tipodocumento !== 'Factura de cliente' || $asiento->documento !== $this->codigo) {
                    $this->miniLog->alert(
                        "Esta factura apunta a un <a href='" . $this->asientoUrl() . "'>asiento incorrecto</a>."
                    );
                    $status = false;
                } elseif ($this->coddivisa === $this->defaultItems->codDivisa() && (abs($asiento->importe) - abs($this->total + $this->totalirpf) >= .02)) {
                    $this->miniLog->alert(
                        'El importe del asiento es distinto al de la factura.'
                    );
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
                . ' AND codcliente = ' . $this->var2str($this->codcliente)
                . ' AND total = ' . $this->var2str($this->total)
                . ' AND codagente = ' . $this->var2str($this->codagente)
                . ' AND numero2 = ' . $this->var2str($this->numero2)
                . ' AND observaciones = ' . $this->var2str($this->observaciones)
                . ' AND idfactura != ' . $this->var2str($this->idfactura) . ';';
            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                foreach ($data as $fac) {
                    /// comprobamos las líneas
                    $sql = 'SELECT referencia FROM lineasfacturascli WHERE
                  idfactura = ' . $this->var2str($this->idfactura) . '
                  AND referencia NOT IN (SELECT referencia FROM lineasfacturascli
                  WHERE idfactura = ' . $this->var2str($fac['idfactura']) . ');';
                    $aux = $this->dataBase->select($sql);
                    if (empty($aux)) {
                        $this->miniLog->alert("Esta factura es un posible duplicado de
                     <a href='index.php?page=VentasFactura&id=" . $fac['idfactura'] . "'>esta otra</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                        $status = false;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Elimina una factura y actualiza los registros relacionados con ella.
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
        $sql = 'UPDATE albaranescli'
            . ' SET idfactura = NULL, ptefactura = TRUE WHERE idfactura = ' . $this->var2str($this->idfactura) . ';'
            . 'DELETE FROM ' . $this->tableName() . ' WHERE idfactura = ' . $this->var2str($this->idfactura) . ';';

        if ($bloquear) {
            return false;
        }
        if ($this->dataBase->exec($sql)) {
            $this->cleanCache();

            if ($this->idasiento) {
                /**
                 * Delegamos la eliminación de los asientos en la clase correspondiente.
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

            $this->miniLog->info(ucfirst(FS_FACTURA) . ' de venta ' . $this->codigo . ' eliminada correctamente.');
            return true;
        }
        return false;
    }

    /**
     * Devuelve un array con las facturas sin pagar
     *
     * @param int $offset
     * @param int $limit
     * @param string $order
     *
     * @return array
     */
    public function allSinPagar($offset = 0, $limit = FS_ITEM_LIMIT, $order = 'vencimiento ASC, codigo ASC')
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE pagada = FALSE ORDER BY ' . $order;

        $data = $this->dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaCliente($f);
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

        $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaCliente($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas del cliente $codcliente
     *
     * @param string $codcliente
     * @param int $offset
     *
     * @return array
     */
    public function allFromCliente($codcliente, $offset = 0)
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() .
            ' WHERE codcliente = ' . $this->var2str($codcliente) .
            ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaCliente($f);
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
     * @param string|boolean $codcliente código del cliente
     * @param string|boolean $estado
     * @param string|boolean $codpago código de la forma de pago
     * @param string|boolean $codalmacen código del almacén
     *
     * @return array
     */
    public function allDesde($desde, $hasta, $codserie = '', $codagente = '', $codcliente = '', $estado = '', $codpago = '', $codalmacen = '')
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
        if ($codcliente !== '') {
            $sql .= ' AND codcliente = ' . $this->var2str($codcliente);
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

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaCliente($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas que coinciden con $query
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
            $consulta .= "codigo LIKE '%" . $query . "%' OR numero2 LIKE '%" . $query . "%'"
                . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numero2) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaCliente($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas del cliente $codcliente que coinciden con $query
     *
     * @param string $codcliente
     * @param string $desde
     * @param string $hasta
     * @param string $serie
     * @param string $obs
     *
     * @return array
     */
    public function searchFromCliente($codcliente, $desde, $hasta, $serie, $obs = '')
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($codcliente) .
            ' AND fecha BETWEEN ' . $this->var2str($desde) . ' AND ' . $this->var2str($hasta) .
            ' AND codserie = ' . $this->var2str($serie);

        if ($obs !== '') {
            $sql .= ' AND lower(observaciones) = ' . $this->var2str(mb_strtolower($obs, 'UTF8'));
        }

        $sql .= ' ORDER BY fecha DESC, codigo DESC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaCliente($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con los huecos en la numeración.
     * @return mixed
     */
    public function huecos()
    {
        $error = true;
        $huecolist = $this->cache->get('factura_cliente_huecos');
        if ($error) {
            $huecolist = fsHuecosFacturasCliente($this->dataBase, $this->tableName());
            $this->cache->set('factura_cliente_huecos', $huecolist);
        }

        return $huecolist;
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
    public function install()
    {
        // new Serie();
        // new Asiento();

        return '';
    }

    /**
     * TODO
     */
    private function cleanCache()
    {
        $this->cache->delete('factura_cliente_huecos');
    }
}
