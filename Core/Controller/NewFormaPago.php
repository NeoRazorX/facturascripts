<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
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
use FacturaScripts\Core\Component\UIController;
use FacturaScripts\Core\Model\CuentaBanco;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Tools;

/**
 * Gestor de formas de pago construido íntegramente sobre el sistema de componentes.
 *
 * Demuestra cómo una única subclase de UIController puede servir tanto una vista de
 * lista como un formulario de edición/creación sobreescribiendo resolveTemplate() según
 * el estado interno. El modo se deriva del parámetro de consulta 'codpago': ausente →
 * lista, 'new' → formulario de creación, cualquier otro valor → formulario de edición.
 *
 * El modelo se carga de forma anticipada en createUI() para que buildEditForm() pueda
 * inspeccionar su estado actual al decidir qué componentes incluir (p. ej. el campo de
 * cuenta bancaria se oculta en registros nuevos que aún no han sido guardados).
 *
 * @see FormaPago
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewFormaPago extends UIController
{
    private string $mode = 'list';

    /** @var FormaPago|null */
    private $editModel = null;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu']  = 'accounting';
        $data['title'] = 'new-payment-method';
        $data['icon']  = 'fa-solid fa-credit-card';
        return $data;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function records(): array
    {
        return (new FormaPago())->all([], ['codpago' => 'ASC']);
    }

    public function subtitle(): string
    {
        if ($this->editModel === null || empty($this->editModel->codpago)) {
            return Tools::lang()->trans('new');
        }

        return $this->editModel->codpago . ' — ' . $this->editModel->descripcion;
    }

    protected function createUI(): void
    {
        $code = $this->request->get('codpago', '');

        if (empty($code)) {
            $this->mode = 'list';
            return;
        }

        $this->mode = ($code === 'new') ? 'new' : 'edit';

        $this->loadModel();

        $this->buildEditForm();
        $this->onEvent('save',   fn() => $this->saveRecord());
        $this->onEvent('delete', fn() => $this->deleteRecord());
    }

    protected function loadModel(): ?object
    {
        if ($this->editModel !== null) {
            return $this->editModel;
        }

        $this->editModel = new FormaPago();

        if ($this->mode === 'edit') {
            $code = $this->request->get('codpago', '');
            if (!$this->editModel->loadFromCode($code)) {
                $this->mode = 'list';
                $this->editModel = null;
                return null;
            }
        }

        return $this->editModel;
    }

    protected function resolveTemplate(): string
    {
        return $this->mode === 'list'
            ? 'NewFormaPago/list'
            : 'NewFormaPago/edit';
    }

    protected function saveRecord(): ActionResult
    {
        if ($this->editModel === null) {
            return ActionResult::make();
        }

        if ($this->editModel->save()) {
            Tools::log()->notice('record-updated-correctly');
        } else {
            Tools::log()->error('record-save-error');
        }

        return ActionResult::make()->withRedirect(Tools::config('route') . '/NewFormaPago');
    }

    protected function deleteRecord(): ActionResult
    {
        $model = $this->loadModel();

        if ($model !== null && $model->exists()) {
            if ($model->delete()) {
                Tools::log()->notice('record-deleted-correctly');
            } else {
                Tools::log()->error('record-delete-error');
            }
        }

        return ActionResult::make()->withRedirect(Tools::config('route') . '/NewFormaPago');
    }

    private function buildEditForm(): void
    {
        $this->addComponent(
            ComponentText::make('codpago')
                ->setLabel('code')
                ->setIcon('fa-solid fa-hashtag')
                ->setMaxLength(10)
                ->setRequired()
                ->setReadOnlyDynamic()
                ->addRule(fn($v, $lang) =>
                    !preg_match('/^[A-Z0-9_+.\- ]{1,10}$/i', (string)$v)
                        ? $lang->trans('invalid-alphanumeric-code')
                        : null
                )
                ->setCols(3)
        );

        $this->addComponent(
            ComponentText::make('descripcion')
                ->setLabel('description')
                ->setRequired()
                ->setMaxLength(100)
                ->setCols(5)
        );

        $this->addComponent(
            ComponentNumber::make('plazovencimiento')
                ->setLabel('expiration')
                ->setMin(0)
                ->setDecimals(0)
                ->setCols(2)
        );

        $this->addComponent(
            ComponentSelect::make('tipovencimiento')
                ->setLabel('expiration-type')
                ->setRequired()
                ->setCols(2)
                ->setValuesFromArrayKeys([
                    'days'   => Tools::lang()->trans('days'),
                    'weeks'  => Tools::lang()->trans('weeks'),
                    'months' => Tools::lang()->trans('months'),
                    'years'  => Tools::lang()->trans('years'),
                ])
        );

        $empresas = (new Empresa())->all();
        if (count($empresas) > 1) {
            $this->addComponent(
                ComponentSelect::make('idempresa')
                    ->setLabel('company')
                    ->setRequired()
                    ->setReadOnlyDynamic()
                    ->setCols(4)
                    ->setSource('empresas', 'idempresa', 'nombrecorto')
                    ->setOptionsResolver(fn() => array_map(
                        fn($e) => ['value' => $e->idempresa, 'title' => $e->nombrecorto, 'group' => ''],
                        $empresas
                    ))
            );
        }

        if ($this->mode !== 'new') {
            $cuentas = (new CuentaBanco())->all([], ['codcuenta' => 'ASC']);
            $this->addComponent(
                ComponentSelect::make('codcuentabanco')
                    ->setLabel('bank-account')
                    ->setCols(4)
                    ->setSource('cuentasbanco', 'codcuenta', 'descripcion')
                    ->setOptionsResolver(function () use ($cuentas) {
                        $opts = [['value' => '', 'title' => '------', 'group' => '']];
                        foreach ($cuentas as $c) {
                            $opts[] = ['value' => $c->codcuenta, 'title' => $c->descripcion, 'group' => ''];
                        }
                        return $opts;
                    })
            );
        }

        $this->addComponent(
            ComponentCheckbox::make('activa')
                ->setLabel('active')
                ->setCols(3)
        );

        $this->addComponent(
            ComponentCheckbox::make('domiciliado')
                ->setLabel('domiciled')
                ->setCols(3)
        );

        $this->addComponent(
            ComponentCheckbox::make('pagado')
                ->setLabel('paid')
                ->setCols(3)
        );

        $this->addComponent(
            ComponentCheckbox::make('imprimir')
                ->setLabel('print-bank-data')
                ->setCols(3)
        );
    }
}
