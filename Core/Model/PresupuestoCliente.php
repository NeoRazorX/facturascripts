<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017    Carlos Garcia Gomez        neorazorx@gmail.com
 * Copyright (C) 2014         Francesc Pineda Segarra    shawe.ewahs@gmail.com
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Presupuesto de cliente
 */
class PresupuestoCliente
{

    use Base\DocumentoVenta;
    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * Clave primaria.
     * @var type 
     */
    public $idpresupuesto;

    /**
     * ID del pedido relacionado, si lo hay.
     * @var type 
     */
    public $idpedido;

    /**
     * Fecha en la que termina la validéz del presupuesto.
     * @var type 
     */
    public $finoferta;

    /**
     * Estado del presupuesto:
     * 0 -> pendiente. (editable)
     * 1 -> aprobado. (hay un idpedido y no es editable)
     * 2 -> rechazado. (no hay idpedido y no es editable)
     * @var integer
     */
    public $status;
    public $editable;

    /**
     * Si este presupuesto es la versión de otro, aquí se almacena el idpresupuesto del original.
     * @var type 
     */
    public $idoriginal;

    public function __construct($data = [])
    {
        $this->init('presupuestoscli', 'idpresupuesto');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    public function clear()
    {
        $this->clearTrait();
        $this->codpago = $this->default_items->codpago();
        $this->codserie = $this->default_items->codserie();
        $this->codalmacen = $this->default_items->codalmacen();
        $this->fecha = Date('d-m-Y');
        $this->finoferta = date("d-m-Y", strtotime(Date('d-m-Y') . " +1month"));
        $this->hora = Date('H:i:s');
        $this->tasaconv = 1;
        $this->status = 0;
        $this->editable = TRUE;
    }

    public function finoferta()
    {
        return (strtotime(Date('d-m-Y')) > strtotime($this->finoferta));
    }

    public function url()
    {
        if (is_null($this->idpresupuesto)) {
            return 'index.php?page=ventas_presupuestos';
        }

        return 'index.php?page=ventas_presupuesto&id=' . $this->idpresupuesto;
    }

    public function pedido_url()
    {
        if (is_null($this->idpedido)) {
            return 'index.php?page=ventas_pedido';
        }

        return 'index.php?page=ventas_pedido&id=' . $this->idpedido;
    }

    public function getLineas()
    {
        $lineaModel = new LineaPresupuestoCliente();
        return $lineaModel->all(new DataBaseWhere('idpresupuesto', $this->idpresupuesto));
    }

    public function get_versiones()
    {
        $versiones = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE idoriginal = " . $this->var2str($this->idpresupuesto);
        if ($this->idoriginal) {
            $sql .= " OR idoriginal = " . $this->var2str($this->idoriginal);
            $sql .= " OR idpresupuesto = " . $this->var2str($this->idoriginal);
        }
        $sql .= "ORDER BY fecha DESC, hora DESC;";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $versiones[] = new PresupuestoCliente($d);
            }
        }

        return $versiones;
    }

    public function newCodigo()
    {
        $this->numero = fs_documento_new_numero($this->db, $this->table_name, $this->codejercicio, $this->codserie, 'npresupuestocli');

        /**
         * Para evitar confusiones, si se elige "factura proforma" o algo similar
         * como traducción de FS_PRESUPUESTO, mejor ponemos "PRO" como inicio de código.
         */
        $tipodoc = strtoupper(substr(FS_PRESUPUESTO, 0, 3));
        if ($tipodoc == 'FAC') {
            $tipodoc = 'PRO';
        }
        $this->codigo = fs_documento_new_codigo($tipodoc, $this->codejercicio, $this->codserie, $this->numero);
    }

    /**
     * Comprueba los datos del presupuesto, devuelve TRUE si está todo correcto
     * @return boolean
     */
    public function test()
    {
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

        /// comprobamos que editable se corresponda con el status
        if ($this->idpedido) {
            $this->status = 1;
            $this->editable = FALSE;
        } else if ($this->status == 0) {
            $this->editable = TRUE;
        } else if ($this->status == 2) {
            $this->editable = FALSE;
        }

        if ($this->floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, TRUE)) {
            return TRUE;
        }

        $this->miniLog->critical("Error grave: El total está mal calculado. ¡Informa del error!");
        return FALSE;
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
            $this->miniLog->critical("Valor neto de " . FS_PRESUPUESTO . " incorrecto. Valor correcto: " . $neto);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor totaliva de " . FS_PRESUPUESTO . " incorrecto. Valor correcto: " . $iva);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor totalirpf de " . FS_PRESUPUESTO . " incorrecto. Valor correcto: " . $irpf);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor totalrecargo de " . FS_PRESUPUESTO . " incorrecto. Valor correcto: " . $recargo);
            $status = FALSE;
        } else if (!$this->floatcmp($this->total, $total, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor total de " . FS_PRESUPUESTO . " incorrecto. Valor correcto: " . $total);
            $status = FALSE;
        }

        return $status;
    }

    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            $this->newCodigo();
            return $this->saveInsert();
        }

        return FALSE;
    }

    /**
     * Devuelve un array con los presupuestos que coinciden con $query
     * @param type $query
     * @param integer $offset
     * @return \PresupuestoCliente
     */
    public function search($query, $offset = 0)
    {
        $preslist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numero2 LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%'
            OR total BETWEEN '" . ($query - .01) . "' AND '" . ($query + .01) . "'";
        } else if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query)) {
            /// es una fecha
            $consulta .= "fecha = " . $this->var2str($query) . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numero2) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= " ORDER BY fecha DESC, codigo DESC";

        $data = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $p) {
                $preslist[] = new PresupuestoCliente($p);
            }
        }

        return $preslist;
    }

    /**
     * Devuelve un array con los presupuestos del cliente $codcliente que coinciden con $query
     * @param type $codcliente
     * @param type $desde
     * @param type $hasta
     * @param type $serie
     * @param type $obs
     * @return PresupuestoCliente[]
     */
    public function search_from_cliente($codcliente, $desde, $hasta, $serie, $obs = '')
    {
        $pedilist = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE codcliente = " . $this->var2str($codcliente) .
            " AND idpedido AND fecha BETWEEN " . $this->var2str($desde) . " AND " . $this->var2str($hasta) .
            " AND codserie = " . $this->var2str($serie);

        if ($obs != '') {
            $sql .= " AND lower(observaciones) = " . $this->var2str(strtolower($obs));
        }

        $sql .= " ORDER BY fecha DESC, codigo DESC;";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $p) {
                $preslist[] = new PresupuestoCliente($p);
            }
        }

        return $preslist;
    }

    public function cron_job()
    {
        /// marcamos como aprobados los presupuestos con idpedido
        $this->db->exec("UPDATE " . $this->table_name . " SET status = '1', editable = FALSE"
            . " WHERE status != '1' AND idpedido IS NOT NULL;");

        /// devolvemos al estado pendiente a los presupuestos con estado 1 a los que se haya borrado el pedido
        $this->db->exec("UPDATE " . $this->table_name . " SET status = '0', idpedido = NULL, editable = TRUE"
            . " WHERE status = '1' AND idpedido NOT IN (SELECT idpedido FROM pedidoscli);");

        /// marcamos como rechazados todos los presupuestos con finoferta ya pasada
        $this->db->exec("UPDATE presupuestoscli SET status = '2' WHERE finoferta IS NOT NULL AND"
            . " finoferta < " . $this->var2str(Date('d-m-Y')) . " AND idpedido IS NULL;");

        /// marcamos como rechazados todos los presupuestos no editables y sin pedido asociado
        $this->db->exec("UPDATE presupuestoscli SET status = '2' WHERE idpedido IS NULL AND"
            . " editable = false;");
    }
}
