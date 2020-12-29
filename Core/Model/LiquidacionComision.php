<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;

/**
 * List of Commissions Settlement.
 *
 * @author Artex Trading s.a.   <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class LiquidacionComision extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * id of agent.
     *
     * @var string
     */
    public $codagente;

    /**
     *
     * @var string
     */
    public $codserie;

    /**
     * Date of creation of the settlement.
     *
     * @var string
     */
    public $fecha;

    /**
     *
     * @var int
     */
    public $idempresa;

    /**
     * id of generate invoice.
     *
     * @var int
     */
    public $idfactura;

    /**
     *
     * @var int
     */
    public $idliquidacion;

    /**
     *
     * @var string
     */
    public $observaciones;

    /**
     * Total amount of the commission settlement.
     *
     * @var double
     */
    public $total;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->fecha = \date(self::DATE_STYLE);
        $this->total = 0.0;
    }

    /**
     * Calculate the total commission amount of a settlement
     *
     * @param int $code
     */
    public function calculateTotalCommission($code)
    {
        $sql = 'UPDATE ' . self::tableName()
            . ' SET total = COALESCE('
            . '(SELECT SUM(totalcomision)'
            . ' FROM ' . FacturaCliente::tableName()
            . ' WHERE idliquidacion = ' . self::$dataBase->var2str($code) . ')'
            . ',0)'
            . ' WHERE idliquidacion = ' . self::$dataBase->var2str($code);

        return self::$dataBase->exec($sql);
    }

    /**
     * Generates an supplier invoice with this settlement.
     * 
     * @return bool
     */
    public function generateInvoice()
    {
        if (null !== $this->idfactura) {
            return true;
        }

        $agent = $this->getAgent();
        $contact = $agent->getContact();
        if (empty($contact->codproveedor)) {
            $this->toolBox()->i18nLog()->warning('agent-dont-have-associated-supplier');
            return false;
        }

        $invoice = new FacturaProveedor();
        $invoice->setSubject($contact->getSupplier());
        $invoice->codserie = $this->codserie;
        $invoice->fecha = $this->fecha;

        $warehouse = new Almacen();
        foreach ($warehouse->all() as $alm) {
            if ($alm->idempresa == $this->idempresa) {
                $invoice->codalmacen = $alm->codalmacen;
                $invoice->idempresa = $alm->idempresa;
            }
        }

        if ($invoice->save()) {
            $product = $agent->getProducto();
            $newLine = $product->exists() ? $invoice->getNewProductLine($product->referencia) : $invoice->getNewLine();
            $newLine->cantidad = 1;
            $newLine->descripcion = $this->toolBox()->i18n()->trans('commission-settlement', ['%code%' => $this->idliquidacion]);
            $newLine->pvpunitario = $this->total;
            $newLine->save();

            $docTools = new BusinessDocumentTools();
            $docTools->recalculate($invoice);
            if ($invoice->save()) {
                $this->idfactura = $invoice->idfactura;
                return $this->save();
            }

            $invoice->delete();
        }

        return false;
    }

    /**
     * 
     * @return Agente
     */
    public function getAgent()
    {
        $agent = new Agente();
        $agent->loadFromCode($this->codagente);
        return $agent;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Agente();
        new FacturaProveedor();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idliquidacion';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'liquidacionescomisiones';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        if (empty($this->idempresa)) {
            $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        }

        $this->observaciones = $this->toolBox()->utils()->noHtml($this->observaciones);
        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListAgente?activetab=List')
    {
        return parent::url($type, $list);
    }
}
