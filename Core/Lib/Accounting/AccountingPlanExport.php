<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model;
use FacturaScripts\Core\Base\DataBase;

/**
 * Edit Description of AccountingPlanImport
 *
 * @author Oscar G. Villa González  <ogvilla@gmail.com>
 * 
 */
class AccountingPlanExport
{

    function exportXML($codejercicio)
    {
        /// creamos el xml
        $cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : ejercicio_" . $codejercicio . ".xml
    Description:
        Estructura cuentas y subcuentas del ejercicio " . $codejercicio . ".
-->
        <ejercicio></ejercicio>\n";
        $archivo_xml = simplexml_load_string($cadena_xml);
        /// añadimos los balances
        $balance = new Model\Balance();
        foreach ($balance->all() as $ba) {
            $aux = $archivo_xml->addChild("balance");
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
        /// añadimos las cuentas de balances
        $balance_cuenta = new Model\BalanceCuenta();
        foreach ($balance_cuenta->all() as $ba) {
            $aux = $archivo_xml->addChild("balance_cuenta");
            $aux->addChild("codbalance", $ba->codbalance);
            $aux->addChild("codcuenta", $ba->codcuenta);
            $aux->addChild("descripcion", base64_encode($ba->desccuenta));
        }
        /// añadimos las cuentas de balance abreviadas
        $balance_cuenta_a = new Model\BalanceCuentaA();
        foreach ($balance_cuenta_a->all() as $ba) {
            $aux = $archivo_xml->addChild("balance_cuenta_a");
            $aux->addChild("codbalance", $ba->codbalance);
            $aux->addChild("codcuenta", $ba->codcuenta);
            $aux->addChild("descripcion", base64_encode($ba->desccuenta));
        }
        /// añadimos las cuentas especiales
        $cuenta_esp = new Model\CuentaEspecial();
        foreach ($cuenta_esp->all() as $ce) {
            $aux = $archivo_xml->addChild("cuenta_especial");
            $aux->addChild("idcuentaesp", $ce->idcuentaesp);
            $aux->addChild("descripcion", base64_encode($ce->descripcion));
        }
        /// añadimos las cuentas
        $cuenta = new Model\Cuenta();
        $where = [new DataBase\DataBaseWhere('codejercicio', $codejercicio)];
        $order = ['codcuenta' => 'ASC'];
        foreach ($cuenta->all($where, $order, 0, 0) as $c) {
            $aux = $archivo_xml->addChild("cuenta");
            $aux->addChild("parent_codcuenta", $c->parent_codcuenta);
            $aux->addChild("codcuenta", $c->codcuenta);
            $aux->addChild("descripcion", base64_encode($c->descripcion));
            $aux->addChild("codcuentaesp", $c->codcuentaesp);
        }
        /// añadimos las subcuentas
        $subcuenta = new Model\Subcuenta();
        $where = [new DataBase\DataBaseWhere('codejercicio', $codejercicio)];
        $order = ['codcuenta' => 'ASC'];
        foreach ($subcuenta->all($where, $order, 0, 0) as $sc) {
            $aux = $archivo_xml->addChild("subcuenta");
            $aux->addChild("codcuenta", $sc->codcuenta);
            $aux->addChild("codsubcuenta", $sc->codsubcuenta);
            $aux->addChild("descripcion", base64_encode($sc->descripcion));
            $aux->addChild("codcuentaesp", $c->codcuentaesp);           
        }
        /// volcamos el XML
        header("content-type: application/xml; charset=UTF-8");
        header('Content-Disposition: attachment; filename="ejercicio_' . $codejercicio . '.xml"');
        echo $archivo_xml->saveXML();
    }
}
