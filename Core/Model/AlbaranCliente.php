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
 * Albarán de cliente o albarán de venta. Representa la entrega a un cliente
 * de un material que se le ha vendido. Implica la salida de ese material
 * del almacén de la empresa.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AlbaranCliente
{
    use Model;

    /**
     * Clave primaria. Integer.
     * @var int
     */
    public $idalbaran;

    /**
     * ID de la factura relacionada, si la hay.
     * @var int
     */
    public $idfactura;

    /**
     * Identificador único de cara a humanos.
     * @var string
     */
    public $codigo;

    /**
     * Serie relacionada.
     * @var string
     */
    public $codserie;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     * @var string
     */
    public $codejercicio;

    /**
     * Cliente del albarán.
     * @var string
     */
    public $codcliente;

    /**
     * Empleado que ha creado este albarán. Modelo agente.
     * @var string
     */
    public $codagente;

    /**
     * Forma de pago de este albarán.
     * @var string
     */
    public $codpago;

    /**
     * Divisa de este albarán.
     * @var string
     */
    public $coddivisa;

    /**
     * Almacén del que sale la mercancía.
     * @var string
     */
    public $codalmacen;

    /**
     * País del cliente.
     * @var string
     */
    public $codpais;

    /**
     * ID de la dirección del cliente. Modelo direccion_cliente.
     * @var int
     */
    public $coddir;

    /**
     * Código postal del cliente.
     * @var string
     */
    public $codpostal;

    /**
     * Número de albarán.
     * Es único dentro de la serie+ejercicio.
     * @var string
     */
    public $numero;

    /**
     * Número opcional a disposición del usuario.
     * @var string
     */
    public $numero2;

    /**
     * Nombre del cliente
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

    /**
     * Fecha del albarán
     * @var string
     */
    public $fecha;

    /**
     * Hora del albarán
     * @var |DateTime('H:i:s')
     */
    public $hora;
    /// datos de transporte

    /**
     * Código de transportista para el envío
     * @var string
     */
    public $envio_codtrans;

    /**
     * Código de seguimiento del envío
     * @var string
     */
    public $envio_codigo;

    /**
     * Nombre de la dirección de envío
     * @var string
     */
    public $envio_nombre;

    /**
     * Apellidos de la dirección de envío
     * @var string
     */
    public $envio_apellidos;

    /**
     * Apartado de correos de la dirección de envío
     * @var string
     */
    public $envio_apartado;

    /**
     * Dirección de la dirección de envío
     * @var string
     */
    public $envio_direccion;

    /**
     * Código postal de la dirección de envío
     * @var string
     */
    public $envio_codpostal;

    /**
     * Ciudad de la dirección de envío
     * @var string
     */
    public $envio_ciudad;

    /**
     * Provincia de la dirección de envío
     * @var string
     */
    public $envio_provincia;

    /**
     * Código de país de la dirección de envío
     * @var string
     */
    public $envio_codpais;

    /**
     * Suma del pvptotal de líneas. Total del albarán antes de impuestos.
     * @var float
     */
    public $neto;

    /**
     * Importe total del albarán, con impuestos.
     * @var float
     */
    public $total;

    /**
     * Suma total del IVA de las líneas.
     * @var float
     */
    public $totaliva;

    /**
     * Total expresado en euros, por si no fuese la divisa del albarán.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     * @var float
     */
    public $totaleuros;

    /**
     * % de retención IRPF del albarán. Se obtiene de la serie.
     * Cada línea puede tener un % distinto.
     * @var float
     */
    public $irpf;

    /**
     * Suma total de las retenciones IRPF de las líneas.
     * @var float
     */
    public $totalirpf;

    /**
     * % de comisión del empleado.
     * @var float
     */
    public $porcomision;

    /**
     * Tasa de conversión a Euros de la divisa seleccionada.
     * @var float
     */
    public $tasaconv;

    /**
     * Suma total del recargo de equivalencia de las líneas.
     * @var float
     */
    public $totalrecargo;

    /**
     * Observaciones del albarán
     * @var string
     */
    public $observaciones;

    /**
     * TRUE => está pendiente de factura.
     * @var bool
     */
    public $ptefactura;

    /**
     * Fecha en la que se envió el albarán por email.
     * @var string
     */
    public $femail;

    /**
     * Número de documentos adjuntos.
     * @var int
     */
    public $numdocs;

    /**
     * AlbaranCliente constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'albaranescli', 'idalbaran');
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
        $this->idalbaran = null;
        $this->idfactura = null;
        $this->codigo = null;
        $this->codagente = null;
        $this->codserie = $this->defaultItems->codSerie();
        $this->codejercicio = null;
        $this->codcliente = null;
        $this->codpago = $this->defaultItems->codPago();
        $this->coddivisa = null;
        $this->codalmacen = $this->defaultItems->codAlmacen();
        $this->codpais = null;
        $this->coddir = null;
        $this->codpostal = '';
        $this->numero = null;
        $this->numero2 = null;
        $this->nombrecliente = '';
        $this->cifnif = '';
        $this->direccion = null;
        $this->ciudad = null;
        $this->provincia = null;
        $this->apartado = null;
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->neto = 0;
        $this->total = 0;
        $this->totaliva = 0;
        $this->totaleuros = 0;
        $this->irpf = 0;
        $this->totalirpf = 0;
        $this->porcomision = 0;
        $this->tasaconv = 1;
        $this->totalrecargo = 0;
        $this->observaciones = null;
        $this->ptefactura = true;
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

        $this->numdocs = 0;
    }

    /**
     * Muestra la hora en formato legible,
     * si $seg = true el formato es 'H:i:s'
     * y sino 'H:i'
     *
     * @param bool $seg
     *
     * @return false|string
     */
    public function showHora($seg = true)
    {
        if ($seg) {
            return date('H:i:s', strtotime($this->hora));
        }

        return date('H:i', strtotime($this->hora));
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
     * Devuelve la url donde se pueden ver/modificar los datos de los albaranes
     * @return string
     */
    public function url()
    {
        if ($this->idalbaran === null) {
            return 'index.php?page=VentasAlbaranes';
        }
        return 'index.php?page=VentasAlbaran&id=' . $this->idalbaran;
    }

    /**
     * Devuelve la url donde se pueden ver/modificar los datos de la factura
     * @return string
     */
    public function facturaUrl()
    {
        if ($this->idfactura === null) {
            return '#';
        }
        return 'index.php?page=VentasFactura&id=' . $this->idfactura;
    }

    /**
     * Devuelve la url donde se pueden ver/modificar los datos de los agentes
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
     * Devuelve la url donde se pueden ver/modificar los datos de los clientes
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
     * Devuelve las líneas del albarán.
     * @return array
     */
    public function getLineas()
    {
        $linea = new LineaAlbaranCliente();
        return $linea->allFromAlbaran($this->idalbaran);
    }

    /**
     * Devuelve un albarán por su código si existe,
     * sino devuelve false
     *
     * @param string $cod
     *
     * @return AlbaranCliente|bool
     */
    public function getByCodigo($cod)
    {
        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE upper(codigo) = ' . strtoupper($this->var2str($cod)) . ';';
        $albaran = $this->database->select($sql);
        if (!empty($albaran)) {
            return new AlbaranCliente($albaran[0]);
        }
        return false;
    }

    /**
     * Genera un nuevo código y número para este albarán
     */
    public function newCodigo()
    {
        $this->numero = fsDocumentoNewNumero(
            $this->database,
            $this->tableName(),
            $this->codejercicio,
            $this->codserie,
            'nalbarancli'
        );
        $this->codigo = fsDocumentoNewCodigo(FS_ALBARAN, $this->codejercicio, $this->codserie, $this->numero);
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si son correctos
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

        if ($this->idfactura) {
            $this->ptefactura = false;
        }

        if ($this->floatcmp(
            $this->total,
            $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo,
            FS_NF0,
            true
        )) {
            return true;
        }

        $this->miniLog->alert('Error grave: El total está mal calculado. ¡Avisa al informático!');
        return false;
    }

    /**
     * Comprobaciones extra del albarán, devuelve TRUE si está correcto
     *
     * @param bool $duplicados
     *
     * @return bool
     */
    public function fullTest($duplicados = true)
    {
        $status = true;

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
            $this->miniLog->alert(
                'Valor neto del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: ' . $neto
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totaliva, $iva, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totaliva del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: ' . $iva
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totalirpf del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: ' . $irpf
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totalrecargo del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $recargo
            );
            $status = false;
        } elseif (!$this->floatcmp($this->total, $total, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor total del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: ' . $total
            );
            $status = false;
        }

        if ($this->total !== 0) {
            /// comprobamos las facturas asociadas
            $lineaFactura = new LineaFacturaCliente();
            $facturas = $lineaFactura->facturasFromAlbaran($this->idalbaran);
            if (!empty($facturas)) {
                if (count($facturas) > 1) {
                    $msg = 'Este ' . FS_ALBARAN . ' esta asociado a las siguientes facturas (y no debería):';
                    foreach ($facturas as $f) {
                        if($f instanceof FacturaCliente) {
                            $msg .= " <a href='" . $f->url() . "'>" . $f->codigo . '</a>';
                        }
                    }
                    $this->miniLog->alert($msg);
                    $status = false;
                } elseif ($facturas[0]->idfactura !== $this->idfactura) {
                    $this->miniLog->alert('Este ' . FS_ALBARAN . " esta asociado a una <a href='"
                        . $this->facturaUrl() . "'>factura</a> incorrecta. La correcta es <a href='"
                        . $facturas[0]->url() . "'>esta</a>.");
                    $status = false;
                }
            } elseif ($this->idfactura !== null) {
                $this->miniLog->alert('Este ' . FS_ALBARAN . " esta asociado a una <a href='" . $this->facturaUrl()
                    . "'>factura</a> que ya no existe.");
                $this->idfactura = null;
                $this->save();

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
                . ' AND idalbaran != ' . $this->var2str($this->idalbaran) . ';';
            $data = $this->database->select($sql);
            if (!empty($data)) {
                foreach ($data as $alb) {
                    /// comprobamos las líneas
                    $sql = 'SELECT referencia FROM lineasalbaranescli WHERE
                  idalbaran = ' . $this->var2str($this->idalbaran) . '
                  AND referencia NOT IN (SELECT referencia FROM lineasalbaranescli
                  WHERE idalbaran = ' . $this->var2str($alb['idalbaran']) . ');';
                    $aux = $this->database->select($sql);
                    if (!empty($aux)) {
                        $this->miniLog->alert('Este ' . FS_ALBARAN . " es un posible duplicado de
                     <a href='index.php?page=VentasAlbaran&id=" . $alb['idalbaran'] . "'>este otro</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                        $status = false;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Inserta los datos del modelo en la base de datos.
     * @return boolean
     */
    public function saveInsert()
    {
        $this->newCodigo();
        $sql = 'INSERT INTO ' . $this->tableName() . ' (idfactura,codigo,codagente,
               codserie,codejercicio,codcliente,codpago,coddivisa,codalmacen,codpais,coddir,
               codpostal,numero,numero2,nombrecliente,cifnif,direccion,ciudad,provincia,apartado,
               fecha,hora,neto,total,totaliva,totaleuros,irpf,totalirpf,porcomision,tasaconv,
               totalrecargo,observaciones,ptefactura,femail,codtrans,codigoenv,nombreenv,apellidosenv,
               apartadoenv,direccionenv,codpostalenv,ciudadenv,provinciaenv,codpaisenv,numdocs) VALUES '
            . '(' . $this->var2str($this->idfactura)
            . ', ' . $this->var2str($this->codigo)
            . ', ' . $this->var2str($this->codagente)
            . ', ' . $this->var2str($this->codserie)
            . ', ' . $this->var2str($this->codejercicio)
            . ', ' . $this->var2str($this->codcliente)
            . ', ' . $this->var2str($this->codpago)
            . ', ' . $this->var2str($this->coddivisa)
            . ', ' . $this->var2str($this->codalmacen)
            . ', ' . $this->var2str($this->codpais)
            . ', ' . $this->var2str($this->coddir)
            . ', ' . $this->var2str($this->codpostal)
            . ', ' . $this->var2str($this->numero)
            . ', ' . $this->var2str($this->numero2)
            . ', ' . $this->var2str($this->nombrecliente)
            . ', ' . $this->var2str($this->cifnif)
            . ', ' . $this->var2str($this->direccion)
            . ', ' . $this->var2str($this->ciudad)
            . ', ' . $this->var2str($this->provincia)
            . ', ' . $this->var2str($this->apartado)
            . ', ' . $this->var2str($this->fecha)
            . ', ' . $this->var2str($this->hora)
            . ', ' . $this->var2str($this->neto)
            . ', ' . $this->var2str($this->total)
            . ', ' . $this->var2str($this->totaliva)
            . ', ' . $this->var2str($this->totaleuros)
            . ', ' . $this->var2str($this->irpf)
            . ', ' . $this->var2str($this->totalirpf)
            . ', ' . $this->var2str($this->porcomision)
            . ', ' . $this->var2str($this->tasaconv)
            . ', ' . $this->var2str($this->totalrecargo)
            . ', ' . $this->var2str($this->observaciones)
            . ', ' . $this->var2str($this->ptefactura)
            . ', ' . $this->var2str($this->femail)
            . ', ' . $this->var2str($this->envio_codtrans)
            . ', ' . $this->var2str($this->envio_codigo)
            . ', ' . $this->var2str($this->envio_nombre)
            . ', ' . $this->var2str($this->envio_apellidos)
            . ', ' . $this->var2str($this->envio_apartado)
            . ', ' . $this->var2str($this->envio_direccion)
            . ', ' . $this->var2str($this->envio_codpostal)
            . ', ' . $this->var2str($this->envio_ciudad)
            . ', ' . $this->var2str($this->envio_provincia)
            . ', ' . $this->var2str($this->envio_codpais)
            . ', ' . $this->var2str($this->numdocs) . ');';
        if ($this->database->exec($sql)) {
            $this->idalbaran = $this->database->lastval();
            return true;
        }
        return false;
    }

    /**
     * Devuelve un array con los albaranes pendientes.
     *
     * @param int $offset
     * @param string $order
     * @param int $limit
     *
     * @return array
     */
    public function allPtefactura($offset = 0, $order = 'fecha ASC', $limit = FS_ITEM_LIMIT)
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE ptefactura = TRUE ORDER BY ' . $order;

        $data = $this->database->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new AlbaranCliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes del cliente.
     *
     * @param string $codcliente
     * @param int $offset
     *
     * @return array
     */
    public function allFromCliente($codcliente, $offset = 0)
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($codcliente)
            . ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new AlbaranCliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes del agente/empleado
     *
     * @param string $codagente
     * @param int $offset
     *
     * @return array
     */
    public function allFromAgente($codagente, $offset = 0)
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codagente = ' . $this->var2str($codagente)
            . ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new AlbaranCliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve todos los albaranes relacionados con la factura.
     *
     * @param int $idfac
     *
     * @return array
     */
    public function allFromFactura($idfac)
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idfactura = ' . $this->var2str($idfac)
            . ' ORDER BY fecha DESC, codigo DESC;';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new AlbaranCliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes comprendidos entre $desde y $hasta
     *
     * @param string $desde
     * @param string $hasta
     *
     * @return array
     */
    public function allDesde($desde, $hasta)
    {
        $alblist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fecha >= ' . $this->var2str($desde)
            . ' AND fecha <= ' . $this->var2str($hasta) . ' ORDER BY codigo ASC;';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alblist[] = new AlbaranCliente($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con todos los albaranes que coinciden con $query
     *
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $alblist = [];
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

        $data = $this->database->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alblist[] = new AlbaranCliente($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes del cliente $codcliente que coincidan
     * con los filtros.
     *
     * @param string $codcliente
     * @param string $desde
     * @param string $hasta
     * @param string $codserie
     * @param string $obs
     * @param string $coddivisa
     *
     * @return array
     */
    public function searchFromCliente($codcliente, $desde, $hasta, $codserie = '', $obs = '', $coddivisa = '')
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($codcliente)
            . ' AND ptefactura AND fecha BETWEEN ' . $this->var2str($desde) . ' AND ' . $this->var2str($hasta);

        if ($codserie !== '') {
            $sql .= ' AND codserie = ' . $this->var2str($codserie);
        }

        if ($obs !== '') {
            $sql .= ' AND lower(observaciones) = ' . $this->var2str(mb_strtolower($obs, 'UTF8'));
        }

        if ($coddivisa !== '') {
            $sql .= ' AND coddivisa = ' . $this->var2str($coddivisa);
        }

        $sql .= ' ORDER BY fecha ASC, codigo ASC;';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new AlbaranCliente($a);
            }
        }

        return $albalist;
    }

    /**
     * TODO
     */
    public function cronJob()
    {
        /**
         * Ponemos a NULL todos los idfactura que no están en facturascli.
         * ¿Por qué? Porque muchos usuarios se dedican a tocar la base de datos.
         */
        $this->database->exec('UPDATE ' . $this->tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturascli);');
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    private function install()
    {
        /// nos aseguramos de que se comprueba la tabla de facturas antes
        // new FacturaCliente();

        return '';
    }
}
