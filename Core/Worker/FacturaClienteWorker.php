<?php declare(strict_types=1);

namespace FacturaScripts\Core\Worker;

use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;

class FacturaClienteWorker extends WorkerClass
{
    public function run(WorkEvent $event):bool
    {
        $facturaCliente = new FacturaCliente();

        if (false === $facturaCliente->loadFromCode($event->param($facturaCliente::primaryColumn()))) {
            return $this->done();
        }

        $totalInvoiced = DbQuery::table($facturaCliente::tableName())
            ->whereEq('codcliente', $facturaCliente->codcliente)
            ->sum('total', 2);

        $totalToBePaid = DbQuery::table($facturaCliente::tableName())
            ->whereEq('codcliente', $facturaCliente->codcliente)
            ->whereEq('pagada', 0)
            ->sum('total', 2);

        $cliente = $facturaCliente->getSubject();
        $cliente->totalfacturado = $totalInvoiced;
        $cliente->pdtepago = $totalToBePaid;
        $cliente->save();

        return $this->done();
    }
}
