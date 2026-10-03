<?php
/**
 * Worker to unlink clientes and proveedores when a payment method is deactivated.
 */

namespace FacturaScripts\Core\Worker;

use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\FormaPago;

class FormaPagoWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        $codpago = $event->value;
        if (empty($codpago)) {
            return $this->done();
        }

        $forma = new FormaPago();
        if (false === $forma->load($codpago)) {
            return $this->done();
        }

        // only act when the payment method is inactive
        if ($forma->activa) {
            return $this->done();
        }

        // prevent workers from creating new model events while we update rows
        $this->preventNewEvents(['Model.Cliente.*', 'Model.Proveedor.*']);

        $where = [new DataBaseWhere('codpago', $codpago)];

        // unlink from clientes
        foreach (Cliente::all($where, [], 0, 0) as $cliente) {
            $cliente->codpago = null;
            if (method_exists($cliente, 'disableAdditionalTest')) {
                $cliente->disableAdditionalTest(true);
            }
            $cliente->save();
        }

        // unlink from proveedores
        foreach (Proveedor::all($where, [], 0, 0) as $proveedor) {
            $proveedor->codpago = null;
            if (method_exists($proveedor, 'disableAdditionalTest')) {
                $proveedor->disableAdditionalTest(true);
            }
            $proveedor->save();
        }

        return $this->done();
    }
}
