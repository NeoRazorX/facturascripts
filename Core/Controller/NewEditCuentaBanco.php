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

use FacturaScripts\Core\Component\ActionResult;
use FacturaScripts\Core\Component\ComponentCheckbox;
use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentSelect;
use FacturaScripts\Core\Component\ComponentModelPicker;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UIComponents\UIEditController;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Formulario de edición de cuentas bancarias propias de la empresa.
 *
 * Replica EditCuentaBanco usando el sistema de componentes UI.
 * No visible en el menú; se accede desde NewListFormaPago (pestaña ListCuentaBanco).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewEditCuentaBanco extends UIEditController
{
    public function getModelClassName(): string
    {
        return 'CuentaBanco';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu']  = 'accounting';
        $data['title'] = 'bank-account';
        $data['icon']  = 'fa-solid fa-piggy-bank';
        return $data;
    }

    public function listUrl(): string
    {
        return 'NewListFormaPago?activetab=ListCuentaBanco';
    }

    protected function getViewName(): string
    {
        return 'EditCuentaBanco';
    }

    protected function buildForm(): void
    {
        $this->loadModel();

        // Grupo principal: datos de la cuenta
        $this->startGroup('data');

        $this->addComponent(
            ComponentNumber::make('codcuenta')
                ->setLabel('code')
                ->setReadOnly()
                ->setDisplay('none')
        );

        $this->addComponent(
            ComponentText::make('descripcion')
                ->setLabel('description')
                ->setRequired()
                ->setMaxLength(100)
        );

        $this->addComponent(
            ComponentText::make('swift')
                ->setLabel('swift')
                ->setMaxLength(11)
                ->setCols(2)
        );

        $this->addComponent(
            ComponentText::make('iban')
                ->setLabel('iban')
                ->setMaxLength(34)
        );

        // Grupo contabilidad: empresa, sufijo SEPA, subcuentas
        $this->startGroup('accounting');

        $empresas = (new Empresa())->all();
        if (count($empresas) > 1) {
            $this->addComponent(
                ComponentSelect::make('idempresa')
                    ->setLabel('company')
                    ->setLabelUrl('ListEmpresa')
                    ->setRequired()
                    ->setReadOnlyDynamic()
                    ->setCols(2)
                    ->setSource('empresas', 'idempresa', 'nombrecorto')
                    ->setOptionsResolver(fn() => array_map(
                        fn($e) => ['value' => $e->idempresa, 'title' => $e->nombrecorto, 'group' => ''],
                        $empresas
                    ))
            );
        } else {
            $this->addComponent(
                ComponentSelect::make('idempresa')
                    ->setDisplay('none')
                    ->setValue($empresas[0]->idempresa ?? null)
            );
        }

        $this->addComponent(
            ComponentText::make('sufijosepa')
                ->setLabel('sepa-suffix')
                ->setMaxLength(3)
                ->setCols(2)
        );

        $idempresa = isset($this->editModel->idempresa) ? (int) $this->editModel->idempresa : null;
        $ejWhere   = $idempresa ? [Where::eq('idempresa', $idempresa)] : [];

        $subcuentaExtraFilters = function (string $id, string $prefix) use ($ejWhere): string {
            $ejercicios = Ejercicio::all($ejWhere, ['codejercicio' => 'DESC']);
            $options = '';
            $first = true;
            foreach ($ejercicios as $ej) {
                $sel = $first ? ' selected' : '';
                $options .= '<option value="' . htmlspecialchars($ej->codejercicio) . '"' . $sel . '>'
                    . htmlspecialchars($ej->nombre) . '</option>';
                $first = false;
            }
            return '<div class="col"><select class="form-select mb-2" id="modal_' . $id . '_ej"'
                . ' onchange="' . $prefix . 'Search(\'' . $id . '\');" required>'
                . $options . '</select></div>';
        };

        $subcuentaExtraWhere = function ($request) use ($ejWhere): array {
            $codej = $request->request->get('codejercicio', '');
            if (empty($codej)) {
                $ejercicios = Ejercicio::all($ejWhere, ['codejercicio' => 'DESC'], 0, 1);
                $codej = $ejercicios[0]->codejercicio ?? '';
            }
            return $codej ? [Where::eq('codejercicio', $codej)] : [];
        };

        $this->addComponent(
            ComponentModelPicker::make('codsubcuenta')
                ->setModel(Subcuenta::class)
                ->setMatch('codsubcuenta')
                ->setIcon('fa-solid fa-book')
                ->setColumns(['codsubcuenta' => 'subaccount', 'descripcion' => 'description'])
                ->setSearchFields('codsubcuenta|descripcion')
                ->setSortOptions([
                    'cod-asc'   => ['sort-by-code-asc',          ['codsubcuenta' => 'ASC']],
                    'cod-desc'  => ['sort-by-code-desc',         ['codsubcuenta' => 'DESC']],
                    'desc-asc'  => ['sort-by-description-asc',  ['descripcion'  => 'ASC']],
                    'desc-desc' => ['sort-by-description-desc', ['descripcion'  => 'DESC']],
                ])
                ->setExtraFilters($subcuentaExtraFilters)
                ->setExtraWhere($subcuentaExtraWhere)
                ->setNewUrl((new Subcuenta())->url('new'))
                ->setLabel('subaccount')
                ->setLabelUrl('ListCuenta')
                ->setDescription('related-subaccount-purchases-sales')
                ->setCols(4)
        );

        $this->addComponent(
            ComponentModelPicker::make('codsubcuentagasto')
                ->setModel(Subcuenta::class)
                ->setMatch('codsubcuenta')
                ->setIcon('fa-solid fa-book')
                ->setColumns(['codsubcuenta' => 'subaccount', 'descripcion' => 'description'])
                ->setSearchFields('codsubcuenta|descripcion')
                ->setSortOptions([
                    'cod-asc'   => ['sort-by-code-asc',          ['codsubcuenta' => 'ASC']],
                    'cod-desc'  => ['sort-by-code-desc',         ['codsubcuenta' => 'DESC']],
                    'desc-asc'  => ['sort-by-description-asc',  ['descripcion'  => 'ASC']],
                    'desc-desc' => ['sort-by-description-desc', ['descripcion'  => 'DESC']],
                ])
                ->setExtraFilters($subcuentaExtraFilters)
                ->setExtraWhere($subcuentaExtraWhere)
                ->setNewUrl((new Subcuenta())->url('new'))
                ->setLabel('expense-subaccount')
                ->setLabelUrl('ListCuenta')
                ->setDescription('related-subaccount-bank-charges')
                ->setCols(4)
        );

        // Grupo flags: alineados al fondo
        $this->startGroup('extra', alignBottom: true);

        $this->addComponent(ComponentCheckbox::make('activa')->setLabel('active'));

        // Vista de subcuentas debajo del formulario (igual que EditCuentaBanco original)
        $this->addListView('ListSubcuenta', 'Subcuenta', 'subaccounts', 'fa-solid fa-book')
            ->addSearchFields(['codsubcuenta', 'descripcion', 'codejercicio'])
            ->addOrderBy(['codejercicio'], 'exercise', 2)
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);

        // Evento para generar la subcuenta contable
        $this->onEvent('generate-subaccount', fn() => $this->generateSubaccountAction());
    }

    protected function modifyUI(): void
    {
        parent::modifyUI();

        $model = $this->editModel;
        if ($model === null || !$model->exists()) {
            return;
        }

        $list = $this->listView('ListSubcuenta');
        if ($list === null) {
            return;
        }

        // Procesa parámetros de búsqueda/orden/paginación del request
        $list->processFormData($this->request, 'load');

        $codsubcuenta = $model->codsubcuenta ?? '';
        $codejercicios = $this->getExerciseCodesOfCompany($model->idempresa ?? null);

        if (empty($codejercicios) || empty($codsubcuenta)) {
            return;
        }

        $where = [
            Where::in('codejercicio', $codejercicios),
            Where::eq('codsubcuenta', $codsubcuenta),
        ];
        $codsubcuentagasto = $model->codsubcuentagasto ?? '';
        if ($codsubcuentagasto && $codsubcuentagasto !== $codsubcuenta) {
            $where[] = Where::orEq('codsubcuenta', $codsubcuentagasto);
        }

        $list->loadData('', $where, ['codejercicio' => 'DESC']);
        unset($list->totalAmounts['saldo']);
    }

    protected function generateSubaccountAction(): ActionResult
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-update');
            return ActionResult::make();
        }

        if (false === $this->validateFormToken()) {
            return ActionResult::make();
        }

        $model = $this->loadModel();
        if ($model === null || !$model->exists()) {
            Tools::log()->warning('record-not-found');
            return ActionResult::make();
        }

        if (!empty($model->codsubcuenta)) {
            return ActionResult::make();
        }

        $ejercicio = new Ejercicio();
        $where = [
            Where::eq('idempresa', $model->idempresa),
            Where::eq('estado', Ejercicio::EXERCISE_STATUS_OPEN),
        ];
        if (false === $ejercicio->loadWhere($where, ['fechainicio' => 'DESC'])) {
            Tools::log()->warning('exercise-not-found');
            return ActionResult::make();
        }

        $subcuenta = $model->createSubcuenta($ejercicio->codejercicio);
        if (empty($subcuenta->codsubcuenta)) {
            Tools::log()->error('record-save-error');
            return ActionResult::make();
        }

        Tools::log()->notice('record-updated-correctly');
        return ActionResult::make()->withRedirect(
            $this->url() . '?code=' . urlencode($model->primaryColumnValue()) . '&action=save-ok'
        );
    }

    private function getExerciseCodesOfCompany(?int $idempresa): array
    {
        if ($idempresa === null) {
            return [];
        }

        $result = [];
        foreach (Ejercicio::all([Where::eq('idempresa', $idempresa)], [], 0, 0) as $ej) {
            $result[] = $ej->codejercicio;
        }
        return $result;
    }
}
