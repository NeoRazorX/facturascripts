<?php
/**
 * Plugin CDTBankTools para FacturaScripts
 *
 * Banking utilities: force reconciliation and related tools.
 *
 * @author CDTCOM
 * @license MIT
 */

namespace FacturaScripts\Plugins\CDTBankTools;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\MovimientoBanco;

final class Init extends InitClass
{
    public function init(): void
    {
        // EditMovimientoBanco: force-reconcile button on detail view.
        $this->loadExtension(new Extension\Controller\EditMovimientoBanco());

        // Handle force-reconcile form POST from ConciliateBankMovements.
        // Init runs on every request — check action early before controller.
        if (isset($_POST['action']) && $_POST['action'] === 'force-reconcile' && !empty($_POST['id'])) {
            $model = new MovimientoBanco();
            if ($model->loadFromCode($_POST['id']) && false === $model->reconciled) {
                $model->reconciled = true;
                if ($model->save()) {
                    Tools::log()->info('Movimiento ' . $_POST['id'] . ' marcado como conciliado.');
                } else {
                    Tools::log()->error('Error al guardar movimiento ' . $_POST['id']);
                }
            }
        }
    }

    public function update(): void
    {
    }

    public function uninstall(): void
    {
    }
}
