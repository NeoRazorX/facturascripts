<?php

/*
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
use FacturaScripts\Core\Base\DefaultItems;

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
     * @var integer 
     */
    public $idalbaran;

    /**
     * ID de la factura relacionada, si la hay.
     * @var integer 
     */
    public $idfactura;

    /**
     * Identificador único de cara a humanos.
     * @var type 
     */
    public $codigo;

    /**
     * Serie relacionada.
     * @var type 
     */
    public $codserie;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     * @var type 
     */
    public $codejercicio;

    /**
     * Cliente del albarán.
     * @var type 
     */
    public $codcliente;

    /**
     * Empleado que ha creado este albarán. Modelo agente.
     * @var type 
     */
    public $codagente;

    /**
     * Forma de pago de este albarán.
     * @var type 
     */
    public $codpago;

    /**
     * Divisa de este albarán.
     * @var type 
     */
    public $coddivisa;

    /**
     * Almacén del que sale la mercancía.
     * @var type 
     */
    public $codalmacen;

    /**
     * País del cliente.
     * @var type 
     */
    public $codpais;

    /**
     * ID de la dirección del cliente. Modelo direccion_cliente.
     * @var type 
     */
    public $coddir;

    /**
     * Código postal del cliente.
     * @var type 
     */
    public $codpostal;

    /**
     * Número de albarán.
     * Es único dentro de la serie+ejercicio.
     * @var type 
     */
    public $numero;

    /**
     * Número opcional a disposición del usuario.
     * @var type 
     */
    public $numero2;
    public $nombrecliente;
    public $cifnif;
    public $direccion;
    public $ciudad;
    public $provincia;
    public $apartado;
    public $fecha;
    public $hora;
    /// datos de transporte
    public $envio_codtrans;
    public $envio_codigo;
    public $envio_nombre;
    public $envio_apellidos;
    public $envio_apartado;
    public $envio_direccion;
    public $envio_codpostal;
    public $envio_ciudad;
    public $envio_provincia;
    public $envio_codpais;

    /**
     * Suma del pvptotal de líneas. Total del albarán antes de impuestos.
     * @var type 
     */
    public $neto;

    /**
     * Importe total del albarán, con impuestos.
     * @var type 
     */
    public $total;

    /**
     * Suma total del IVA de las líneas.
     * @var type 
     */
    public $totaliva;

    /**
     * Total expresado en euros, por si no fuese la divisa del albarán.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     * @var type 
     */
    public $totaleuros;

    /**
     * % de retención IRPF del albarán. Se obtiene de la serie.
     * Cada línea puede tener un % distinto.
     * @var type 
     */
    public $irpf;

    /**
     * Suma total de las retenciones IRPF de las líneas.
     * @var type 
     */
    public $totalirpf;

    /**
     * % de comisión del empleado.
     * @var type 
     */
    public $porcomision;

    /**
     * Tasa de conversión a Euros de la divisa seleccionada.
     * @var type 
     */
    public $tasaconv;

    /**
     * Suma total del recargo de equivalencia de las líneas.
     * @var type 
     */
    public $totalrecargo;
    public $observaciones;

    /**
     * TRUE => está pendiente de factura.
     * @var type 
     */
    public $ptefactura;

    /**
     * Fecha en la que se envió el albarán por email.
     * @var type 
     */
    public $femail;

    /**
     * Número de documentos adjuntos.
     * @var integer 
     */
    public $numdocs;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'albaranescli', 'idalbaran');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear()
    {
            $this->idalbaran = NULL;
            $this->idfactura = NULL;
            $this->codigo = NULL;
            $this->codagente = NULL;
            $this->codserie = $this->defaultItems->codSerie();
            $this->codejercicio = NULL;
            $this->codcliente = NULL;
            $this->codpago = $this->defaultItems->codPago();
            $this->coddivisa = NULL;
            $this->codalmacen = $this->defaultItems->codAlmacen();
            $this->codpais = NULL;
            $this->coddir = NULL;
            $this->codpostal = '';
            $this->numero = NULL;
            $this->numero2 = NULL;
            $this->nombrecliente = '';
            $this->cifnif = '';
            $this->direccion = NULL;
            $this->ciudad = NULL;
            $this->provincia = NULL;
            $this->apartado = NULL;
            $this->fecha = Date('d-m-Y');
            $this->hora = Date('H:i:s');
            $this->neto = 0;
            $this->total = 0;
            $this->totaliva = 0;
            $this->totaleuros = 0;
            $this->irpf = 0;
            $this->totalirpf = 0;
            $this->porcomision = 0;
            $this->tasaconv = 1;
            $this->totalrecargo = 0;
            $this->observaciones = NULL;
            $this->ptefactura = TRUE;
            $this->femail = NULL;

            $this->envio_codtrans = NULL;
            $this->envio_codigo = NULL;
            $this->envio_nombre = NULL;
            $this->envio_apellidos = NULL;
            $this->envio_apartado = NULL;
            $this->envio_direccion = NULL;
            $this->envio_codpostal = NULL;
            $this->envio_ciudad = NULL;
            $this->envio_provincia = NULL;
            $this->envio_codpais = NULL;

            $this->numdocs = 0;
    }
        
    protected function install() {
        /// nos aseguramos de que se comprueba la tabla de facturas antes
        // new \FacturaCliente();

        return '';
    }

    public function show_hora($s = TRUE) {
        if ($s) {
            return Date('H:i:s', strtotime($this->hora));
        } else
            return Date('H:i', strtotime($this->hora));
    }

    public function observaciones_resume() {
        if ($this->observaciones == '') {
            return '-';
        } else if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        } else
            return substr($this->observaciones, 0, 50) . '...';
    }

    public function url() {
        if (is_null($this->idalbaran)) {
            return 'index.php?page=ventas_albaranes';
        } else
            return 'index.php?page=ventas_albaran&id=' . $this->idalbaran;
    }

    public function factura_url() {
        if (is_null($this->idfactura)) {
            return '#';
        } else
            return 'index.php?page=ventas_factura&id=' . $this->idfactura;
    }

    public function agente_url() {
        if (is_null($this->codagente)) {
            return "index.php?page=admin_agentes";
        } else
            return "index.php?page=admin_agente&cod=" . $this->codagente;
    }

    public function cliente_url() {
        if (is_null($this->codcliente)) {
            return "index.php?page=ventas_clientes";
        } else
            return "index.php?page=ventas_cliente&cod=" . $this->codcliente;
    }

    /**
     * Devuelve las líneas del albarán.
     * @return \linea_albaran_cliente
     */
    public function get_lineas() {
        $linea = new \linea_albaran_cliente();
        return $linea->all_from_albaran($this->idalbaran);
    }

    /**
     * Devuelve el albarán solicitado o false si no se encuentra.
     * @param type $id
     * @return \albaran_cliente|boolean
     */
    public function get($id) {
        $albaran = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idalbaran = " . $this->var2str($id) . ";");
        if ($albaran) {
            return new \albaran_cliente($albaran[0]);
        } else
            return FALSE;
    }

    public function get_by_codigo($cod) {
        $albaran = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE upper(codigo) = " . strtoupper($this->var2str($cod)) . ";");
        if ($albaran) {
            return new \albaran_cliente($albaran[0]);
        } else
            return FALSE;
    }

    public function exists() {
        if (is_null($this->idalbaran)) {
            return FALSE;
        } else
            return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idalbaran = " . $this->var2str($this->idalbaran) . ";");
    }

    /**
     * Genera un nuevo código y número para este albarán
     */
    public function new_codigo() {
        $this->numero = fs_documento_new_numero($this->db, $this->table_name, $this->codejercicio, $this->codserie, 'nalbarancli');
        $this->codigo = fs_documento_new_codigo(FS_ALBARAN, $this->codejercicio, $this->codserie, $this->numero);
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $this->nombrecliente = $this->no_html($this->nombrecliente);
        if ($this->nombrecliente == '') {
            $this->nombrecliente = '-';
        }

        $this->direccion = $this->no_html($this->direccion);
        $this->ciudad = $this->no_html($this->ciudad);
        $this->provincia = $this->no_html($this->provincia);
        $this->envio_nombre = $this->no_html($this->envio_nombre);
        $this->envio_apellidos = $this->no_html($this->envio_apellidos);
        $this->envio_direccion = $this->no_html($this->envio_direccion);
        $this->envio_ciudad = $this->no_html($this->envio_ciudad);
        $this->envio_provincia = $this->no_html($this->envio_provincia);
        $this->numero2 = $this->no_html($this->numero2);
        $this->observaciones = $this->no_html($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->idfactura) {
            $this->ptefactura = FALSE;
        }

        if ($this->floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, TRUE)) {
            return TRUE;
        } else {
            $this->new_error_msg("Error grave: El total está mal calculado. ¡Avisa al informático!");
            return FALSE;
        }
    }

    /**
     * Comprobaciones extra del albarán, devuelve TRUE si está todo correcto
     * @param type $duplicados
     * @return boolean
     */
    public function full_test($duplicados = TRUE) {
        $status = TRUE;

        /// comprobamos las líneas
        $neto = 0;
        $iva = 0;
        $irpf = 0;
        $recargo = 0;
        foreach ($this->get_lineas() as $l) {
            if (!$l->test()) {
                $status = FALSE;
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

        if (!$this->floatcmp($this->neto, $neto, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor neto del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $neto);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totaliva del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $iva);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totalirpf del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $irpf);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totalrecargo del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $recargo);
            $status = FALSE;
        } else if (!$this->floatcmp($this->total, $total, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor total del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $total);
            $status = FALSE;
        }

        if ($this->total != 0) {
            /// comprobamos las facturas asociadas
            $linea_factura = new \linea_factura_cliente();
            $facturas = $linea_factura->facturas_from_albaran($this->idalbaran);
            if ($facturas) {
                if (count($facturas) > 1) {
                    $msg = "Este " . FS_ALBARAN . " esta asociado a las siguientes facturas (y no debería):";
                    foreach ($facturas as $f) {
                        $msg .= " <a href='" . $f->url() . "'>" . $f->codigo . "</a>";
                    }
                    $this->new_error_msg($msg);
                    $status = FALSE;
                } else if ($facturas[0]->idfactura != $this->idfactura) {
                    $this->new_error_msg("Este " . FS_ALBARAN . " esta asociado a una <a href='" . $this->factura_url() .
                            "'>factura</a> incorrecta. La correcta es <a href='" . $facturas[0]->url() . "'>esta</a>.");
                    $status = FALSE;
                }
            } else if (isset($this->idfactura)) {
                $this->new_error_msg("Este " . FS_ALBARAN . " esta asociado a una <a href='" . $this->factura_url()
                        . "'>factura</a> que ya no existe.");
                $this->idfactura = NULL;
                $this->save();

                $status = FALSE;
            }
        }

        if ($status AND $duplicados) {
            /// comprobamos si es un duplicado
            $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE fecha = " . $this->var2str($this->fecha)
                    . " AND codcliente = " . $this->var2str($this->codcliente)
                    . " AND total = " . $this->var2str($this->total)
                    . " AND codagente = " . $this->var2str($this->codagente)
                    . " AND numero2 = " . $this->var2str($this->numero2)
                    . " AND observaciones = " . $this->var2str($this->observaciones)
                    . " AND idalbaran != " . $this->var2str($this->idalbaran) . ";");
            if ($data) {
                foreach ($data as $alb) {
                    /// comprobamos las líneas
                    $aux = self::$dataBase->select("SELECT referencia FROM lineasalbaranescli WHERE
                  idalbaran = " . $this->var2str($this->idalbaran) . "
                  AND referencia NOT IN (SELECT referencia FROM lineasalbaranescli
                  WHERE idalbaran = " . $this->var2str($alb['idalbaran']) . ");");
                    if (!$aux) {
                        $this->new_error_msg("Este " . FS_ALBARAN . " es un posible duplicado de
                     <a href='index.php?page=ventas_albaran&id=" . $alb['idalbaran'] . "'>este otro</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                        $status = FALSE;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET idfactura = " . $this->var2str($this->idfactura)
                        . ", codigo = " . $this->var2str($this->codigo)
                        . ", codagente = " . $this->var2str($this->codagente)
                        . ", codserie = " . $this->var2str($this->codserie)
                        . ", codejercicio = " . $this->var2str($this->codejercicio)
                        . ", codcliente = " . $this->var2str($this->codcliente)
                        . ", codpago = " . $this->var2str($this->codpago)
                        . ", coddivisa = " . $this->var2str($this->coddivisa)
                        . ", codalmacen = " . $this->var2str($this->codalmacen)
                        . ", codpais = " . $this->var2str($this->codpais)
                        . ", coddir = " . $this->var2str($this->coddir)
                        . ", codpostal = " . $this->var2str($this->codpostal)
                        . ", numero = " . $this->var2str($this->numero)
                        . ", numero2 = " . $this->var2str($this->numero2)
                        . ", nombrecliente = " . $this->var2str($this->nombrecliente)
                        . ", cifnif = " . $this->var2str($this->cifnif)
                        . ", direccion = " . $this->var2str($this->direccion)
                        . ", ciudad = " . $this->var2str($this->ciudad)
                        . ", provincia = " . $this->var2str($this->provincia)
                        . ", apartado = " . $this->var2str($this->apartado)
                        . ", fecha = " . $this->var2str($this->fecha)
                        . ", hora = " . $this->var2str($this->hora)
                        . ", neto = " . $this->var2str($this->neto)
                        . ", total = " . $this->var2str($this->total)
                        . ", totaliva = " . $this->var2str($this->totaliva)
                        . ", totaleuros = " . $this->var2str($this->totaleuros)
                        . ", irpf = " . $this->var2str($this->irpf)
                        . ", totalirpf = " . $this->var2str($this->totalirpf)
                        . ", porcomision = " . $this->var2str($this->porcomision)
                        . ", tasaconv = " . $this->var2str($this->tasaconv)
                        . ", totalrecargo = " . $this->var2str($this->totalrecargo)
                        . ", observaciones = " . $this->var2str($this->observaciones)
                        . ", ptefactura = " . $this->var2str($this->ptefactura)
                        . ", femail = " . $this->var2str($this->femail)
                        . ", codtrans = " . $this->var2str($this->envio_codtrans)
                        . ", codigoenv = " . $this->var2str($this->envio_codigo)
                        . ", nombreenv = " . $this->var2str($this->envio_nombre)
                        . ", apellidosenv = " . $this->var2str($this->envio_apellidos)
                        . ", apartadoenv = " . $this->var2str($this->envio_apartado)
                        . ", direccionenv = " . $this->var2str($this->envio_direccion)
                        . ", codpostalenv = " . $this->var2str($this->envio_codpostal)
                        . ", ciudadenv = " . $this->var2str($this->envio_ciudad)
                        . ", provinciaenv = " . $this->var2str($this->envio_provincia)
                        . ", codpaisenv = " . $this->var2str($this->envio_codpais)
                        . ", numdocs = " . $this->var2str($this->numdocs)
                        . "  WHERE idalbaran = " . $this->var2str($this->idalbaran) . ";";

                return self::$dataBase->exec($sql);
            } else {
                $this->new_codigo();
                $sql = "INSERT INTO " . $this->table_name . " (idfactura,codigo,codagente,
               codserie,codejercicio,codcliente,codpago,coddivisa,codalmacen,codpais,coddir,
               codpostal,numero,numero2,nombrecliente,cifnif,direccion,ciudad,provincia,apartado,
               fecha,hora,neto,total,totaliva,totaleuros,irpf,totalirpf,porcomision,tasaconv,
               totalrecargo,observaciones,ptefactura,femail,codtrans,codigoenv,nombreenv,apellidosenv,
               apartadoenv,direccionenv,codpostalenv,ciudadenv,provinciaenv,codpaisenv,numdocs) VALUES "
                        . "(" . $this->var2str($this->idfactura)
                        . "," . $this->var2str($this->codigo)
                        . "," . $this->var2str($this->codagente)
                        . "," . $this->var2str($this->codserie)
                        . "," . $this->var2str($this->codejercicio)
                        . "," . $this->var2str($this->codcliente)
                        . "," . $this->var2str($this->codpago)
                        . "," . $this->var2str($this->coddivisa)
                        . "," . $this->var2str($this->codalmacen)
                        . "," . $this->var2str($this->codpais)
                        . "," . $this->var2str($this->coddir)
                        . "," . $this->var2str($this->codpostal)
                        . "," . $this->var2str($this->numero)
                        . "," . $this->var2str($this->numero2)
                        . "," . $this->var2str($this->nombrecliente)
                        . "," . $this->var2str($this->cifnif)
                        . "," . $this->var2str($this->direccion)
                        . "," . $this->var2str($this->ciudad)
                        . "," . $this->var2str($this->provincia)
                        . "," . $this->var2str($this->apartado)
                        . "," . $this->var2str($this->fecha)
                        . "," . $this->var2str($this->hora)
                        . "," . $this->var2str($this->neto)
                        . "," . $this->var2str($this->total)
                        . "," . $this->var2str($this->totaliva)
                        . "," . $this->var2str($this->totaleuros)
                        . "," . $this->var2str($this->irpf)
                        . "," . $this->var2str($this->totalirpf)
                        . "," . $this->var2str($this->porcomision)
                        . "," . $this->var2str($this->tasaconv)
                        . "," . $this->var2str($this->totalrecargo)
                        . "," . $this->var2str($this->observaciones)
                        . "," . $this->var2str($this->ptefactura)
                        . "," . $this->var2str($this->femail)
                        . "," . $this->var2str($this->envio_codtrans)
                        . "," . $this->var2str($this->envio_codigo)
                        . "," . $this->var2str($this->envio_nombre)
                        . "," . $this->var2str($this->envio_apellidos)
                        . "," . $this->var2str($this->envio_apartado)
                        . "," . $this->var2str($this->envio_direccion)
                        . "," . $this->var2str($this->envio_codpostal)
                        . "," . $this->var2str($this->envio_ciudad)
                        . "," . $this->var2str($this->envio_provincia)
                        . "," . $this->var2str($this->envio_codpais)
                        . "," . $this->var2str($this->numdocs) . ");";

                if (self::$dataBase->exec($sql)) {
                    $this->idalbaran = self::$dataBase->lastval();
                    return TRUE;
                } else
                    return FALSE;
            }
        } else
            return FALSE;
    }

    public function delete() {
        if (self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idalbaran = " . $this->var2str($this->idalbaran) . ";")) {
            if ($this->idfactura) {
                /**
                 * Delegamos la eliminación de la factura en la clase correspondiente,
                 * que tendrá que hacer más cosas.
                 */
                $factura = new \factura_cliente();
                $factura0 = $factura->get($this->idfactura);
                if ($factura0) {
                    $factura0->delete();
                }
            }

            $this->new_message(ucfirst(FS_ALBARAN) . " de venta " . $this->codigo . " eliminado correctamente.");
            return TRUE;
        } else
            return FALSE;
    }

    /**
     * Devuelve un array con los últimos albaranes
     * @param type $offset
     * @param type $order
     * @return \albaran_cliente
     */
    public function all($offset = 0, $order = 'fecha DESC', $limit = FS_ITEM_LIMIT) {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " ORDER BY " . $order;

        $data = self::$dataBase->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_cliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes pendientes.
     * @param type $offset
     * @param type $order
     * @return \albaran_cliente
     */
    public function all_ptefactura($offset = 0, $order = 'fecha ASC', $limit = FS_ITEM_LIMIT) {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE ptefactura = true ORDER BY " . $order;

        $data = self::$dataBase->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_cliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes del cliente.
     * @param type $codcliente
     * @param type $offset
     * @return \albaran_cliente
     */
    public function all_from_cliente($codcliente, $offset = 0) {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codcliente = " . $this->var2str($codcliente)
                . " ORDER BY fecha DESC, codigo DESC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_cliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes del agente/empleado
     * @param type $codagente
     * @param type $offset
     * @return \albaran_cliente
     */
    public function all_from_agente($codagente, $offset = 0) {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codagente = " . $this->var2str($codagente)
                . " ORDER BY fecha DESC, codigo DESC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_cliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve todos los albaranes relacionados con la factura.
     * @param type $id
     * @return \albaran_cliente
     */
    public function all_from_factura($id) {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idfactura = " . $this->var2str($id)
                . " ORDER BY fecha DESC, codigo DESC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_cliente($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes comprendidos entre $desde y $hasta
     * @param type $desde
     * @param type $hasta
     * @return \albaran_cliente
     */
    public function all_desde($desde, $hasta) {
        $alblist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE fecha >= " . $this->var2str($desde)
                . " AND fecha <= " . $this->var2str($hasta) . " ORDER BY codigo ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $alblist[] = new \albaran_cliente($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con todos los albaranes que coinciden con $query
     * @param type $query
     * @param type $offset
     * @return \albaran_cliente
     */
    public function search($query, $offset = 0) {
        $alblist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numero2 LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numero2) LIKE '%" . $query . "%' "
                    . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= " ORDER BY fecha DESC, codigo DESC";

        $data = self::$dataBase->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $a) {
                $alblist[] = new \albaran_cliente($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes del cliente $codcliente que coincidan
     * con los filtros.
     * @param type $codcliente
     * @param type $desde
     * @param type $hasta
     * @param type $codserie
     * @param type $obs
     * @param type $coddivisa
     * @return \albaran_cliente
     */
    public function search_from_cliente($codcliente, $desde, $hasta, $codserie = '', $obs = '', $coddivisa = '') {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codcliente = " . $this->var2str($codcliente)
                . " AND ptefactura AND fecha BETWEEN " . $this->var2str($desde) . " AND " . $this->var2str($hasta);

        if ($codserie) {
            $sql .= " AND codserie = " . $this->var2str($codserie);
        }

        if ($obs) {
            $sql .= " AND lower(observaciones) = " . $this->var2str(mb_strtolower($obs, 'UTF8'));
        }

        if ($coddivisa) {
            $sql .= " AND coddivisa = " . $this->var2str($coddivisa);
        }

        $sql .= " ORDER BY fecha ASC, codigo ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_cliente($a);
            }
        }

        return $albalist;
    }

    public function cron_job() {
        /**
         * Ponemos a NULL todos los idfactura que no están en facturascli.
         * ¿Por qué? Porque muchos usuarios se dedican a tocar la base de datos.
         */
        self::$dataBase->exec("UPDATE " . $this->table_name . " SET idfactura = NULL WHERE idfactura IS NOT NULL"
                . " AND idfactura NOT IN (SELECT idfactura FROM facturascli);");
    }

}
