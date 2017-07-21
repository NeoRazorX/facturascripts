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

/**
 * La línea de IVA de una factura de proveedor.
 * Indica el neto, iva y total para un determinado IVA y una factura.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaIvaFacturaProveedor
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idlinea;

    /**
     * ID de la factura relacionada.
     * @var type 
     */
    public $idfactura;

    /**
     * neto + totaliva + totalrecargo.
     * @var type 
     */
    public $totallinea;

    /**
     * Total de recargo de equivalencia para ese impuesto.
     * @var type 
     */
    public $totalrecargo;

    /**
     * % de recargo de equivalencia del impuesto.
     * @var type 
     */
    public $recargo;

    /**
     * Total de IVA para ese impuesto.
     * @var type 
     */
    public $totaliva;

    /**
     * % de IVA del impuesto.
     * @var type 
     */
    public $iva;

    /**
     * Código del impuesto relacionado.
     * @var type 
     */
    public $codimpuesto;

    /**
     * Neto o base imponible para ese impuesto.
     * @var type 
     */
    public $neto;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'lineasivafactprov', 'idlinea');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear()
    {
        $this->idlinea = NULL;
        $this->idfactura = NULL;
        $this->neto = 0;
        $this->codimpuesto = NULL;
        $this->iva = 0;
        $this->totaliva = 0;
        $this->recargo = 0;
        $this->totalrecargo = 0;
        $this->totallinea = 0;
    }

    protected function install() {
        return '';
    }

    public function exists() {
        if (is_null($this->idlinea)) {
            return FALSE;
        } else
            return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idlinea = " . $this->var2str($this->idlinea) . ";");
    }

    public function test() {
        if ($this->floatcmp($this->totallinea, $this->neto + $this->totaliva + $this->totalrecargo, FS_NF0, TRUE)) {
            return TRUE;
        } else {
            $this->new_error_msg("Error en el valor de totallinea de la línea de IVA del impuesto " .
                    $this->codimpuesto . " de la factura. Valor correcto: " .
                    round($this->neto + $this->totaliva + $this->totalrecargo, FS_NF0));
            return FALSE;
        }
    }

    public function factura_test($idfactura, $neto, $totaliva, $totalrecargo) {
        $status = TRUE;

        $li_neto = 0;
        $li_iva = 0;
        $li_recargo = 0;
        foreach ($this->all_from_factura($idfactura) as $li) {
            if (!$li->test()) {
                $status = FALSE;
            }

            $li_neto += $li->neto;
            $li_iva += $li->totaliva;
            $li_recargo += $li->totalrecargo;
        }

        $li_neto = round($li_neto, FS_NF0);
        $li_iva = round($li_iva, FS_NF0);
        $li_recargo = round($li_recargo, FS_NF0);

        if (!$this->floatcmp($neto, $li_neto, FS_NF0, TRUE)) {
            $this->new_error_msg("La suma de los netos de las líneas de IVA debería ser: " . $neto);
            $status = FALSE;
        } else if (!$this->floatcmp($totaliva, $li_iva, FS_NF0, TRUE)) {
            $this->new_error_msg("La suma de los totales de iva de las líneas de IVA debería ser: " . $totaliva);
            $status = FALSE;
        } else if (!$this->floatcmp($totalrecargo, $li_recargo, FS_NF0, TRUE)) {
            $this->new_error_msg("La suma de los totalrecargo de las líneas de IVA debería ser: " . $totalrecargo);
            $status = FALSE;
        }

        return $status;
    }

    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET idfactura = " . $this->var2str($this->idfactura)
                        . ", neto = " . $this->var2str($this->neto)
                        . ", codimpuesto = " . $this->var2str($this->codimpuesto)
                        . ", iva = " . $this->var2str($this->iva)
                        . ", totaliva = " . $this->var2str($this->totaliva)
                        . ", recargo = " . $this->var2str($this->recargo)
                        . ", totalrecargo = " . $this->var2str($this->totalrecargo)
                        . ", totallinea = " . $this->var2str($this->totallinea)
                        . "  WHERE idlinea = " . $this->var2str($this->idlinea) . ";";

                return self::$dataBase->exec($sql);
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (idfactura,neto,codimpuesto,iva,
               totaliva,recargo,totalrecargo,totallinea) VALUES 
                      (" . $this->var2str($this->idfactura)
                        . "," . $this->var2str($this->neto)
                        . "," . $this->var2str($this->codimpuesto)
                        . "," . $this->var2str($this->iva)
                        . "," . $this->var2str($this->totaliva)
                        . "," . $this->var2str($this->recargo)
                        . "," . $this->var2str($this->totalrecargo)
                        . "," . $this->var2str($this->totallinea) . ");";

                if (self::$dataBase->exec($sql)) {
                    $this->idlinea = self::$dataBase->lastval();
                    return TRUE;
                } else
                    return FALSE;
            }
        } else
            return FALSE;
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idlinea = " . $this->var2str($this->idlinea) . ";");
    }

    public function all_from_factura($id) {
        $linealist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idfactura = " . $this->var2str($id) . " ORDER BY iva DESC;");
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_iva_factura_proveedor($l);
            }
        }

        return $linealist;
    }

}
