<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Component\ComponentCheckbox;
use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\Lib\MultiRequestProtection;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UIComponents\UIListController;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Listado de asientos contables construido sobre UIListController.
 *
 * Replica el comportamiento de ListAsiento con cuatro pestañas:
 *  - ListAsiento: asientos contables (Asiento)
 *  - ListAsiento-not: asientos desbalanceados (Asiento con filtro SQL)
 *  - ListConceptoPartida: conceptos predefinidos (ConceptoPartida)
 *  - ListDiario: diarios (Diario)
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewListAsiento extends UIListController
{
    public function getModelClassName(): string
    {
        return 'Asiento';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'new-accounting-entries';
        $data['icon'] = 'fa-solid fa-balance-scale';
        return $data;
    }

    protected function createUI(): void
    {
        $this->createViewsAccountEntries();
        $this->createViewsNotBalanced();
        $this->createViewsConcepts();
        $this->createViewsJournals();
    }

    protected function createViewsAccountEntries(string $tabName = 'ListAsiento'): void
    {
        $tab = $this->addTab($tabName, 'Asiento', 'accounting-entries', 'fa-solid fa-balance-scale');

        $tab->addColumn(ComponentText::make('numero')->setLabel('number')->setCols(2));
        $tab->addColumn(ComponentText::make('fecha')->setLabel('date')->setCols(2));
        $tab->addColumn(ComponentText::make('concepto')->setLabel('concept'));
        $tab->addColumn(ComponentText::make('documento')->setLabel('document')->setCols(2));
        $tab->addColumn(ComponentNumber::make('importe')->setLabel('amount')->setCols(2));
        $tab->addColumn(ComponentCheckbox::make('editable')->setLabel('editable'));

        $tab->addSearchField('concepto', 'documento', 'CAST(numero AS char(255))');

        $tab->addOrderBy(['fecha', 'numero'], 'date', 2);
        $tab->addOrderBy(['numero', 'idasiento'], 'number');
        $tab->addOrderBy(['importe', 'idasiento'], 'amount');

        $tab->addColor('editable', false, 'table-warning', 'locked');

        $tab->setNewUrl('NewEditAsiento');
        $tab->setRowUrlCallback(fn($r) => 'NewEditAsiento?code=' . urlencode((string)$r->idasiento));

        if ($this->permissions->allowUpdate) {
            $tab->addButtonGroup('entry-actions', 'fa-solid fa-circle-check', 'actions')
                ->addGroupButton('entry-actions', [
                    'action'  => 'lock-entries',
                    'confirm' => true,
                    'icon'    => 'fa-solid fa-lock',
                    'label'   => 'lock-entry',
                ])
                ->addGroupButton('entry-actions', [
                    'action' => 'renumber',
                    'icon'   => 'fa-solid fa-sort-numeric-down',
                    'label'  => 'renumber',
                    'type'   => 'modal',
                    'target' => 'renumberModal',
                ]);
        }
    }

    protected function createViewsNotBalanced(string $tabName = 'ListAsiento-not'): void
    {
        $db = $this->dataBase;

        $tab = $this->addTab($tabName, 'Asiento', 'unbalance', 'fa-solid fa-exclamation-circle');

        $tab->addColumn(ComponentText::make('numero')->setLabel('number')->setCols(2));
        $tab->addColumn(ComponentText::make('fecha')->setLabel('date')->setCols(2));
        $tab->addColumn(ComponentText::make('concepto')->setLabel('concept'));
        $tab->addColumn(ComponentText::make('documento')->setLabel('document')->setCols(2));
        $tab->addColumn(ComponentNumber::make('importe')->setLabel('amount')->setCols(2));

        $tab->addSearchField('concepto', 'documento', 'CAST(numero AS char(255))');

        $tab->addOrderBy(['fecha', 'idasiento'], 'date', 2);
        $tab->addOrderBy(['numero', 'idasiento'], 'number');
        $tab->addOrderBy(['importe', 'idasiento'], 'amount');

        $tab->addColor('editable', false, 'table-warning', 'locked');

        $tab->setRowUrlCallback(fn($r) => 'NewEditAsiento?code=' . urlencode((string)$r->idasiento));

        // Filtro: solo asientos desbalanceados (calculado via SQL al cargar)
        $tab->setExtraWhere(function () use ($db): array {
            $sql = Tools::config('db_type') === 'postgresql'
                ? 'SELECT partidas.idasiento FROM partidas GROUP BY 1 HAVING ABS(SUM(partidas.debe) - SUM(partidas.haber)) >= 0.01'
                : 'SELECT partidas.idasiento FROM partidas GROUP BY 1 HAVING ROUND(ABS(SUM(partidas.debe) - SUM(partidas.haber)), 2) >= 0.01';

            $ids = [];
            foreach ($db->select($sql) as $row) {
                $ids[] = (int)$row['idasiento'];
            }

            // Si no hay desbalanceados, usar condición imposible para retornar vacío
            return empty($ids)
                ? [new DataBaseWhere('idasiento', 0)]
                : [new DataBaseWhere('idasiento', implode(',', $ids), 'IN')];
        });
    }

    protected function createViewsConcepts(string $tabName = 'ListConceptoPartida'): void
    {
        $tab = $this->addTab($tabName, 'ConceptoPartida', 'predefined-concepts', 'fa-solid fa-indent');

        $tab->addColumn(ComponentText::make('codconcepto')->setLabel('code')->setCols(3));
        $tab->addColumn(ComponentText::make('descripcion')->setLabel('description'));

        $tab->addSearchField('codconcepto', 'descripcion');

        $tab->addOrderBy(['codconcepto'], 'code');
        $tab->addOrderBy(['descripcion'], 'description', 1);

        $tab->setNewUrl('EditConceptoPartida?code=new');
        $tab->setRowUrlCallback(fn($r) => 'EditConceptoPartida?code=' . urlencode((string)$r->codconcepto));
    }

    protected function createViewsJournals(string $tabName = 'ListDiario'): void
    {
        $tab = $this->addTab($tabName, 'Diario', 'journals', 'fa-solid fa-book');

        $tab->addColumn(ComponentNumber::make('iddiario')->setLabel('code')->setDecimals(0)->setCols(2));
        $tab->addColumn(ComponentText::make('descripcion')->setLabel('description'));

        $tab->addSearchField('descripcion');

        $tab->addOrderBy(['iddiario'], 'code');
        $tab->addOrderBy(['descripcion'], 'description', 1);

        $tab->setNewUrl('EditDiario?code=new');
        $tab->setRowUrlCallback(fn($r) => 'EditDiario?code=' . urlencode((string)$r->iddiario));
    }

    protected function execPreviousAction(string $action): bool
    {
        switch ($action) {
            case 'lock-entries':
                $this->lockEntriesAction();
                return true;

            case 'renumber':
                $this->renumberAction();
                return true;
        }

        return parent::execPreviousAction($action);
    }

    protected function lockEntriesAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        }

        if (false === $this->validateFormToken()) {
            return;
        }

        $codes = $this->request->request->getArray('codes');
        if (false === is_array($codes) || empty($codes)) {
            Tools::log()->warning('no-selected-item');
            return;
        }

        $model = new \FacturaScripts\Dinamic\Model\Asiento();

        $this->dataBase->beginTransaction();
        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                Tools::log()->error('record-not-found');
                continue;
            }

            if (false === $model->editable) {
                continue;
            }

            $model->editable = false;
            if (false === $model->save()) {
                Tools::log()->error('record-save-error');
                $this->dataBase->rollback();
                $model->clear();
                return;
            }
        }

        Tools::log()->notice('record-updated-correctly');
        $this->dataBase->commit();
        $model->clear();
    }

    protected function renumberAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        }

        if (false === $this->validateFormToken()) {
            return;
        }

        $codejercicio = $this->request->input('exercise');
        $model = new \FacturaScripts\Dinamic\Model\Asiento();

        $this->dataBase->beginTransaction();
        if ($model->renumber($codejercicio)) {
            Tools::log()->notice('renumber-accounting-ok');
            $this->dataBase->commit();
            return;
        }

        $this->dataBase->rollback();
        Tools::log()->error('record-save-error');
    }

    public function tabModals(string $tabName): string
    {
        if ($tabName !== 'ListAsiento' || false === $this->permissions->allowUpdate) {
            return '';
        }

        $lang = Tools::lang();
        $ejercicios = Ejercicio::all([], ['codejercicio' => 'DESC'], 0, 0);

        $options = '';
        foreach ($ejercicios as $ej) {
            $options .= '<option value="' . htmlspecialchars($ej->codejercicio) . '">'
                . htmlspecialchars($ej->nombre) . '</option>';
        }

        $token = '<input type="hidden" name="multireqtoken" value="'
            . htmlspecialchars((new MultiRequestProtection())->newToken()) . '"/>';

        return '<div class="modal fade" id="renumberModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog"><div class="modal-content">'
            . '<form method="post" onsubmit="animateSpinner(\'add\')">'
            . $token
            . '<input type="hidden" name="action" value="renumber">'
            . '<input type="hidden" name="activetab" value="ListAsiento">'
            . '<div class="modal-header"><h5 class="modal-title">'
            . '<i class="fa-solid fa-sort-numeric-down fa-fw me-1"></i>'
            . $lang->trans('renumber') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>'
            . '</div>'
            . '<div class="modal-body"><div class="mb-3">'
            . '<label class="form-label">' . $lang->trans('exercise') . '</label>'
            . '<select name="exercise" class="form-select" required>'
            . '<option value="">------</option>'
            . $options
            . '</select></div></div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
            . $lang->trans('cancel') . '</button>'
            . '<button type="submit" class="btn btn-primary">'
            . '<i class="fa-solid fa-check fa-fw me-1"></i>' . $lang->trans('accept')
            . '</button></div>'
            . '</form></div></div></div>';
    }
}
