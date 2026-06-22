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
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UIComponents\UIEditController;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Diario;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Formulario de edición de asientos contables construido sobre UIEditController.
 *
 * Replica la funcionalidad de EditAsiento con el nuevo sistema de componentes UI.
 * No visible en el menú; se accede desde NewListAsiento.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewEditAsiento extends UIEditController
{
    public function getModelClassName(): string
    {
        return 'Asiento';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-entry';
        $data['icon'] = 'fa-solid fa-balance-scale';
        return $data;
    }

    public function listUrl(): string
    {
        return 'NewListAsiento';
    }

    protected function getViewName(): string
    {
        return 'EditAsiento';
    }

    protected function buildForm(): void
    {
        $this->loadModel();

        // Grupo principal: cabecera del asiento
        $this->startGroup('header');

        $this->addComponent(
            ComponentNumber::make('idasiento')
                ->setLabel('id')
                ->setDisplay('none')
        );

        $this->addComponent(
            ComponentText::make('fecha')
                ->setLabel('date')
                ->setRequired()
                ->setCols(2)
        );

        $this->addComponent(
            ComponentNumber::make('numero')
                ->setLabel('number')
                ->setReadOnly()
                ->setDecimals(0)
                ->setCols(2)
        );

        // Ejercicio (read-only dynamic: se asigna desde fecha al guardar)
        $ejercicios = Ejercicio::all([], ['codejercicio' => 'DESC']);
        $this->addComponent(
            ComponentSelect::make('codejercicio')
                ->setLabel('exercise')
                ->setLabelUrl('ListEjercicio')
                ->setReadOnlyDynamic()
                ->setCols(3)
                ->setSource('ejercicios', 'codejercicio', 'nombre')
                ->setOptionsResolver(function () use ($ejercicios) {
                    $opts = [['value' => '', 'title' => '------', 'group' => '']];
                    foreach ($ejercicios as $ej) {
                        $opts[] = ['value' => $ej->codejercicio, 'title' => $ej->nombre, 'group' => ''];
                    }
                    return $opts;
                })
        );

        $this->addComponent(
            ComponentText::make('concepto')
                ->setLabel('concept')
                ->setRequired()
                ->setMaxLength(255)
        );

        $this->addComponent(
            ComponentText::make('documento')
                ->setLabel('document')
                ->setMaxLength(255)
                ->setCols(3)
        );

        $this->addComponent(
            ComponentNumber::make('canal')
                ->setLabel('channel')
                ->setDecimals(0)
                ->setCols(2)
        );

        // Diario
        $diarios = (new Diario())->all([], ['descripcion' => 'ASC']);
        $this->addComponent(
            ComponentSelect::make('iddiario')
                ->setLabel('journal')
                ->setLabelUrl('EditDiario')
                ->setCols(3)
                ->setSource('diarios', 'iddiario', 'descripcion')
                ->setOptionsResolver(function () use ($diarios) {
                    $opts = [['value' => '', 'title' => '------', 'group' => '']];
                    foreach ($diarios as $d) {
                        $opts[] = ['value' => $d->iddiario, 'title' => $d->descripcion, 'group' => ''];
                    }
                    return $opts;
                })
        );

        // Tipo de operación
        $this->addComponent(
            ComponentSelect::make('operacion')
                ->setLabel('operation')
                ->setCols(3)
                ->setValuesFromArrayKeys([
                    ''                            => '------',
                    Asiento::OPERATION_OPENING       => Tools::lang()->trans('opening-operation'),
                    Asiento::OPERATION_CLOSING       => Tools::lang()->trans('closing-operation'),
                    Asiento::OPERATION_REGULARIZATION => Tools::lang()->trans('regularization-operation'),
                ])
        );

        // Empresa (oculto si solo hay una)
        $empresas = (new Empresa())->all();
        if (count($empresas) > 1) {
            $this->addComponent(
                ComponentSelect::make('idempresa')
                    ->setLabel('company')
                    ->setRequired()
                    ->setReadOnlyDynamic()
                    ->setCols(3)
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

        // Grupo de importes (calculados, solo lectura)
        $this->startGroup('amounts', alignBottom: true);

        $this->addComponent(
            ComponentNumber::make('debe')
                ->setLabel('debit')
                ->setReadOnly()
                ->setCols(3)
        );

        $this->addComponent(
            ComponentNumber::make('haber')
                ->setLabel('credit')
                ->setReadOnly()
                ->setCols(3)
        );

        $this->addComponent(
            ComponentNumber::make('importe')
                ->setLabel('amount')
                ->setReadOnly()
                ->setCols(3)
        );

        $this->addComponent(
            ComponentCheckbox::make('editable')
                ->setLabel('editable')
                ->setReadOnly()
        );

        // Lista de partidas contables debajo del formulario
        $this->addListView('ListPartidaAsiento', 'Partida', 'accounting-lines', 'fa-solid fa-list')
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);

        // Eventos personalizados
        $this->onEvent('lock-doc', fn() => $this->lockDocAction(false));
        $this->onEvent('unlock-doc', fn() => $this->lockDocAction(true));
    }

    protected function modifyUI(): void
    {
        parent::modifyUI();

        $model = $this->editModel;
        if ($model === null || !$model->exists()) {
            return;
        }

        $list = $this->listView('ListPartidaAsiento');
        if ($list !== null) {
            $list->processFormData($this->request, 'load');
            $list->loadData('', [Where::eq('idasiento', $model->idasiento)]);
        }
    }

    public function extraHeaderButtons(): string
    {
        $model = $this->editModel;
        if (!$this->hasData || $model === null) {
            return '';
        }

        $code = htmlspecialchars((string)$model->primaryColumnValue());
        $lang = Tools::lang();
        $html = '';

        // Bloquear / desbloquear
        if ($model->editable) {
            $html .= '<button type="button" class="btn btn-sm btn-warning ms-1"'
                . ' title="' . $lang->trans('lock') . '"'
                . ' onclick="uiEditSendEvent(\'lock-doc\')">'
                . '<i class="fa-solid fa-lock fa-fw" aria-hidden="true"></i>'
                . '<span class="d-none d-lg-inline-block"> ' . $lang->trans('lock') . '</span>'
                . '</button>';
        } else {
            $html .= '<button type="button" class="btn btn-sm btn-success ms-1"'
                . ' title="' . $lang->trans('unlock') . '"'
                . ' onclick="uiEditSendEvent(\'unlock-doc\')">'
                . '<i class="fa-solid fa-lock-open fa-fw" aria-hidden="true"></i>'
                . '<span class="d-none d-lg-inline-block"> ' . $lang->trans('unlock') . '</span>'
                . '</button>';
        }

        // Copiar asiento
        $html .= '<a href="CopyModel?model=Asiento&amp;code=' . urlencode((string)$model->primaryColumnValue()) . '"'
            . ' class="btn btn-sm btn-secondary ms-1"'
            . ' title="' . $lang->trans('copy') . '">'
            . '<i class="fa-solid fa-copy fa-fw" aria-hidden="true"></i>'
            . '<span class="d-none d-lg-inline-block"> ' . $lang->trans('copy') . '</span>'
            . '</a>';

        return $html;
    }

    protected function lockDocAction(bool $unlock): ActionResult
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
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

        $model->editable = $unlock;
        if (false === $model->save()) {
            Tools::log()->error('record-save-error');
            return ActionResult::make();
        }

        Tools::log()->notice('record-updated-correctly');
        return ActionResult::make()->withRedirect(
            $this->url() . '?code=' . urlencode((string)$model->primaryColumnValue()) . '&action=save-ok'
        );
    }
}
