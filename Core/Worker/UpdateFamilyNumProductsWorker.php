<?php declare(strict_types=1);

namespace FacturaScripts\Core\Worker;

use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\Model\Familia;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;

class UpdateFamilyNumProductsWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        $currentCodFamilia = $event->param(Familia::primaryColumn());
        $previousCodFamilia = is_array($event->param('previous')) ? $event->param('previous')[Familia::primaryColumn()] : null;

        $idFamilia = $currentCodFamilia ?? $previousCodFamilia;

        if (empty($idFamilia) || $currentCodFamilia === $previousCodFamilia) {
            return $this->done();
        }

        $family = new Familia();
        if (false === $family->loadFromCode($idFamilia)) {
            return $this->done();
        }

        $totalProductsFamily = DbQuery::table(Producto::tableName())
            ->whereEq(Familia::primaryColumn(), $family->primaryColumnValue())
            ->count();

        $family->numproductos = $totalProductsFamily;
        $family->save();

        return $this->done();
    }
}
