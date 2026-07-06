<?php
/**
 * Extension for EditMovimientoBanco — adds "Force Reconciliation" button.
 *
 * @author CDTCOM
 * @license MIT
 */

namespace FacturaScripts\Plugins\CDTBankTools\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

class EditMovimientoBanco
{
    public function createViews(): Closure
    {
        return function () {
            $model = $this->getModel();
            if (false === $model->load($this->request->get('code'))) {
                return;
            }

            if (false === $model->reconciled) {
                $mvn = $this->getMainViewName();
                $this->addButton($mvn, [
                    'action' => 'force-reconcile',
                    'color' => 'warning',
                    'icon' => 'fa-solid fa-check-double',
                    'label' => 'Forzar Conciliación',
                    'type' => 'action',
                ]);
            }
        };
    }

    public function execPreviousAction(): Closure
    {
        return function ($action) {
            if ($action === 'force-reconcile') {
                $id = $this->request->get('code');
                $model = $this->getModel();
                if ($model->loadFromCode($id) && false === $model->reconciled) {
                    $model->reconciled = true;
                    if ($model->save()) {
                        Tools::log()->info('Movimiento marcado como conciliado.');
                    } else {
                        Tools::log()->error('Error al guardar.');
                    }
                }
                return true;
            }
        };
    }
}
