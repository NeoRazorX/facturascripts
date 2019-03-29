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
        foreach ($balance->all([], [], 0, 0) as $ba) {
            $aux = $xml->addChild("balance");
            $aux->addChild("codbalance", $ba->codbalance);
            $aux->addChild("naturaleza", $ba->naturaleza);
            $aux->addChild("nivel1", $ba->nivel1);
            $aux->addChild("descripcion1", base64_encode($ba->descripcion1));
            $aux->addChild("nivel2", $ba->nivel2);
            $aux->addChild("descripcion2", base64_encode($ba->descripcion2));
            $aux->addChild("nivel3", $ba->nivel3);
            $aux->addChild("descripcion3", base64_encode($ba->descripcion3));
            $aux->addChild("orden3", $ba->orden3);
            $aux->addChild("nivel4", $ba->nivel4);
            $aux->addChild("descripcion4", base64_encode($ba->descripcion4));
            $aux->addChild("descripcion4ba", base64_encode($ba->descripcion4ba));
        }

        $balanceCuenta = new BalanceCuenta();
        foreach ($balanceCuenta->all([], [], 0, 0) as $ba) {
            $aux = $xml->addChild("balance_cuenta");
            $aux->addChild("codbalance", $ba->codbalance);
            $aux->addChild("codcuenta", $ba->codcuenta);
            $aux->addChild("descripcion", base64_encode($ba->desccuenta));
        }

        $balanceCuentaA = new BalanceCuentaA();
        foreach ($balanceCuentaA->all([], [], 0, 0) as $ba) {
            $aux = $xml->addChild("balance_cuenta_a");
            $aux->addChild("codbalance", $ba->codbalance);
            $aux->addChild("codcuenta", $ba->codcuenta);
            $aux->addChild("descripcion", base64_encode($ba->desccuenta));
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
        foreach ($cuenta->all($where, $order, 0, 0) as $c) {
            $aux = $xml->addChild("cuenta");
            $aux->addChild("parent_codcuenta", $c->parent_codcuenta);
            $aux->addChild("codcuenta", $c->codcuenta);
            $aux->addChild("descripcion", base64_encode($c->descripcion));
            $aux->addChild("codcuentaesp", $c->codcuentaesp);
        }
    }

    /**
     * 
     * @param object $xml
     */
    protected function addCuentasEspeciales(&$xml)
    {
        $cuentaEsp = new CuentaEspecial();
        foreach ($cuentaEsp->all([], [], 0, 0) as $ce) {
            $aux = $xml->addChild("cuenta_especial");
            $aux->addChild("idcuentaesp", $ce->idcuentaesp);
            $aux->addChild("descripcion", base64_encode($ce->descripcion));
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
        foreach ($subcuenta->all($where, $order, 0, 0) as $sc) {
            $aux = $xml->addChild("subcuenta");
            $aux->addChild("codcuenta", $sc->codcuenta);
            $aux->addChild("codsubcuenta", $sc->codsubcuenta);
            $aux->addChild("descripcion", base64_encode($sc->descripcion));
            $aux->addChild("codcuentaesp", $c->codcuentaesp);
        }
    }
}
