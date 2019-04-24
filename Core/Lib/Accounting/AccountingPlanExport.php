<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Dinamic\Model\Balance;
use FacturaScripts\Dinamic\Model\BalanceCuenta;
use FacturaScripts\Dinamic\Model\BalanceCuentaA;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\CuentaEspecial;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Class to export accounting plans.
 *
 * @author Carlos García Gómez      <carlos@facturapascripts.com>
 * @author Oscar G. Villa González  <ogvilla@gmail.com>
 */
class AccountingPlanExport
{

    /**
     * 
     * @param string $code
     *
     * @return string
     */
    public function exportXML($code)
    {
        $xmlString = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : ejercicio_" . $code . ".xml
    Description:
        Estructura cuentas y subcuentas del ejercicio " . $code . ".
-->
        <ejercicio></ejercicio>\n";
        $xml = simplexml_load_string($xmlString);
        if (false === $xml) {
            return '';
        }

        $this->addBalances($xml);
        $this->addCuentasEspeciales($xml);
        $this->addCuentas($xml, $code);
        $this->addSubcuentas($xml, $code);

        return $xml->asXML();
    }

    /**
     * 
     * @param object $xml
     */
    protected function addBalances(&$xml)
    {
        $balance = new Balance();
        foreach ($balance->all([], [], 0, 0) as $item) {
            $aux = $xml->addChild("balance");
            $aux->addChild("codbalance", $item->codbalance);
            $aux->addChild("naturaleza", $item->naturaleza);
            $aux->addChild("nivel1", $item->nivel1);
            $aux->addChild("descripcion1", base64_encode($item->descripcion1));
            $aux->addChild("nivel2", $item->nivel2);
            $aux->addChild("descripcion2", base64_encode($item->descripcion2));
            $aux->addChild("nivel3", $item->nivel3);
            $aux->addChild("descripcion3", base64_encode($item->descripcion3));
            $aux->addChild("orden3", $item->orden3);
            $aux->addChild("nivel4", $item->nivel4);
            $aux->addChild("descripcion4", base64_encode($item->descripcion4));
            $aux->addChild("descripcion4ba", base64_encode($item->descripcion4ba));
        }

        $balanceCuenta = new BalanceCuenta();
        foreach ($balanceCuenta->all([], [], 0, 0) as $item) {
            $aux = $xml->addChild("balance_cuenta");
            $aux->addChild("codbalance", $item->codbalance);
            $aux->addChild("codcuenta", $item->codcuenta);
            $aux->addChild("descripcion", base64_encode($item->desccuenta));
        }

        $balanceCuentaA = new BalanceCuentaA();
        foreach ($balanceCuentaA->all([], [], 0, 0) as $item) {
            $aux = $xml->addChild("balance_cuenta_a");
            $aux->addChild("codbalance", $item->codbalance);
            $aux->addChild("codcuenta", $item->codcuenta);
            $aux->addChild("descripcion", base64_encode($item->desccuenta));
        }
    }

    /**
     * 
     * @param object $xml
     * @param string $code
     */
    protected function addCuentas(&$xml, $code)
    {
        $cuenta = new Cuenta();
        $where = [new DataBaseWhere('codejercicio', $code)];
        $order = ['codcuenta' => 'ASC'];
        foreach ($cuenta->all($where, $order, 0, 0) as $item) {
            $aux = $xml->addChild("cuenta");
            $aux->addChild("parent_codcuenta", $item->parent_codcuenta);
            $aux->addChild("codcuenta", $item->codcuenta);
            $aux->addChild("descripcion", base64_encode($item->descripcion));
            $aux->addChild("codcuentaesp", $item->codcuentaesp);
        }
    }

    /**
     * 
     * @param object $xml
     */
    protected function addCuentasEspeciales(&$xml)
    {
        $cuentaEsp = new CuentaEspecial();
        foreach ($cuentaEsp->all([], [], 0, 0) as $item) {
            $aux = $xml->addChild("cuenta_especial");
            $aux->addChild("idcuentaesp", $item->idcuentaesp);
            $aux->addChild("descripcion", base64_encode($item->descripcion));
        }
    }

    /**
     * 
     * @param object $xml
     * @param string $code
     */
    protected function addSubcuentas(&$xml, $code)
    {
        $subcuenta = new Subcuenta();
        $where = [new DataBaseWhere('codejercicio', $code)];
        $order = ['codcuenta' => 'ASC'];
        foreach ($subcuenta->all($where, $order, 0, 0) as $item) {
            $aux = $xml->addChild("subcuenta");
            $aux->addChild("codcuenta", $item->codcuenta);
            $aux->addChild("codsubcuenta", $item->codsubcuenta);
            $aux->addChild("descripcion", base64_encode($item->descripcion));
            $aux->addChild("codcuentaesp", $item->codcuentaesp);
        }
    }
}
