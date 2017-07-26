<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
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
 * Pedido de proveedor
 */
class PedidoProveedor
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idpedido;

    /**
     * ID del albarán relacionado.
     * @var type 
     */
    public $idalbaran;

    /**
     * Código único. Para humanos.
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
     * Código del proveedor del pedido.
     * @var type 
     */
    public $codproveedor;

    /**
     * Empleado que ha creado el pedido.
     * @var type 
     */
    public $codagente;

    /**
     * Forma de pago del pedido.
     * @var type 
     */
    public $codpago;

    /**
     * Divisa del pedido.
     * @var type 
     */
    public $coddivisa;

    /**
     * Almacén en el que entrará la mercancía.
     * @var type 
     */
    public $codalmacen;

    /**
     * Número de pedido.
     * Único para la serie+ejercicio.
     * @var type 
     */
    public $numero;

    /**
     * Número del pedido del proveedor. Si lo tiene.
     * @var type 
     */
    public $numproveedor;

    /**
     * Nombre del proveedor.
     * @var type 
     */
    public $nombre;
    public $cifnif;
    public $fecha;
    public $hora;

    /**
     * Imprte total antes de impuestos.
     * es la suma del pvptotal de las líneas.
     * @var type 
     */
    public $neto;

    /**
     * Importe total del pedido, con impuestos.
     * @var type 
     */
    public $total;

    /**
     * Suma total del IVA de las líneas.
     * @var type 
     */
    public $totaliva;

    /**
     * Total expresado en euros, por si no fuese la divisa del pedido.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     * @var type 
     */
    public $totaleuros;

    /**
     * % de retención IRPF del pedido. Se obtiene de la serie.
     * Cada línea puede tener un % distinto.
     * @var type 
     */
    public $irpf;

    /**
     * Suma de las retenciones IRPF de las líneas del pedido.
     * @var type 
     */
    public $totalirpf;

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
     * Indica si se puede editar o no.
     * @var type 
     */
    public $editable;

    /**
     * Número de documentos adjuntos.
     * @var integer 
     */
    public $numdocs;

    /**
     * Si este presupuesto es la versión de otro, aquí se almacena el idpresupuesto del original.
     * @var type 
     */
    public $idoriginal;

    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'pedidosprov', 'idpedido');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    public function clear()
    {
        $this->idpedido = NULL;
        $this->idalbaran = NULL;
        $this->codigo = NULL;
        $this->codagente = NULL;
        $this->codpago = $this->default_items->codpago();
        $this->codserie = $this->default_items->codserie();
        $this->codejercicio = NULL;
        $this->codproveedor = NULL;
        $this->coddivisa = NULL;
        $this->codalmacen = $this->default_items->codalmacen();
        $this->numero = NULL;
        $this->numproveedor = NULL;
        $this->nombre = '';
        $this->cifnif = '';
        $this->fecha = Date('d-m-Y');
        $this->hora = Date('H:i:s');
        $this->neto = 0;
        $this->total = 0;
        $this->totaliva = 0;
        $this->totaleuros = 0;
        $this->irpf = 0;
        $this->totalirpf = 0;
        $this->tasaconv = 1;
        $this->totalrecargo = 0;
        $this->observaciones = NULL;
        $this->editable = TRUE;
        $this->numdocs = 0;
        $this->idoriginal = NULL;
    }

    public function show_hora($s = TRUE)
    {
        if ($s) {
            return Date('H:i:s', strtotime($this->hora));
        } else {
                    return Date('H:i', strtotime($this->hora));
        }
    }

    public function observaciones_resume()
    {
        if ($this->observaciones == '') {
            return '-';
        } else if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        } else {
                    return substr($this->observaciones, 0, 50) . '...';
        }
    }

    public function url()
    {
        if (is_null($this->idpedido)) {
            return 'index.php?page=compras_pedidos';
        } else {
                    return 'index.php?page=compras_pedido&id=' . $this->idpedido;
        }
    }

    public function albaran_url()
    {
        if (is_null($this->idalbaran)) {
            return 'index.php?page=compras_albaranes';
        } else {
                    return 'index.php?page=compras_albaran&id=' . $this->idalbaran;
        }
    }

    public function agente_url()
    {
        if (is_null($this->codagente)) {
            return "index.php?page=admin_agentes";
        } else {
                    return "index.php?page=admin_agente&cod=" . $this->codagente;
        }
    }

    public function proveedor_url()
    {
        if (is_null($this->codproveedor)) {
            return "index.php?page=compras_proveedores";
        } else {
                    return "index.php?page=compras_proveedor&cod=" . $this->codproveedor;
        }
    }

    public function get_lineas()
    {
        $linea = new LineaPedidoProveedor();
        return $linea->all_from_pedido($this->idpedido);
    }

    public function get_versiones()
    {
        $versiones = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE idoriginal = " . $this->var2str($this->idpedido);
        if ($this->idoriginal) {
            $sql .= " OR idoriginal = " . $this->var2str($this->idoriginal);
            $sql .= " OR idpedido = " . $this->var2str($this->idoriginal);
        }
        $sql .= "ORDER BY fecha DESC, hora DESC;";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $versiones[] = new PedidoProveedor($d);
            }
        }

        return $versiones;
    }

    public function new_codigo()
    {
        $this->numero = fs_documento_new_numero($this->db, $this->table_name, $this->codejercicio, $this->codserie, 'npedidoprov');
        $this->codigo = fs_documento_new_codigo(FS_PEDIDO, $this->codejercicio, $this->codserie, $this->numero, 'C');
    }

    /**
     * Comprueba los daros del pedido, devuelve TRUE si está todo correcto
     * @return boolean
     */
    public function test()
    {
        $this->nombre = $this->no_html($this->nombre);
        if ($this->nombre == '') {
            $this->nombre = '-';
        }

        $this->numproveedor = $this->no_html($this->numproveedor);
        $this->observaciones = $this->no_html($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, TRUE)) {
            return TRUE;
        } else {
            $this->new_error_msg("Error grave: El total está mal calculado. ¡Informa del error!");
            return FALSE;
        }
    }

    public function full_test($duplicados = TRUE)
    {
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
            $this->new_error_msg("Valor neto de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $neto);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totaliva de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $iva);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totalirpf de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $irpf);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totalrecargo de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $recargo);
            $status = FALSE;
        } else if (!$this->floatcmp($this->total, $total, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor total de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $total);
            $status = FALSE;
        }

        if ($this->idalbaran) {
            $alb0 = new AlbaranProveedor();
            $albaran = $alb0->get($this->idalbaran);
            if (!$albaran) {
                $this->idalbaran = NULL;
                $this->save();
            }
        }

        return $status;
    }

    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            } else {
                $this->new_codigo();
                return $this->saveInsert();
            }
        }

        return FALSE;
    }

    /**
     * Devuelve un array con los pedidos que coinciden con $query
     * @param type $query
     * @param integer $offset
     * @return \PedidoProveedor
     */
    public function search($query, $offset = 0)
    {
        $pedilist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%'
            OR total BETWEEN '" . ($query - .01) . "' AND '" . ($query + .01) . "'";
        } else if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query)) {
            /// es una fecha
            $consulta .= "fecha = " . $this->var2str($query) . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= " ORDER BY fecha DESC, codigo DESC";

        $data = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $p) {
                $pedilist[] = new PedidoProveedor($p);
            }
        }

        return $pedilist;
    }

    public function cron_job()
    {
        $sql = "UPDATE " . $this->table_name . " SET idalbaran = NULL, editable = TRUE"
            . " WHERE idalbaran IS NOT NULL AND NOT EXISTS(SELECT 1 FROM albaranesprov t1 WHERE t1.idalbaran = " . $this->table_name . ".idalbaran);";
        $this->db->exec($sql);
    }
}
