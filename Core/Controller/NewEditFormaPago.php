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

use FacturaScripts\Core\Component\ComponentCheckbox;
use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentSelect;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\Model\CuentaBanco;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UIComponents\UIEditController;

/**
 * Formulario de edición y creación de formas de pago construido sobre UIEditController.
 *
 * No visible en el menú (showonmenu = false, heredado de UIEditController). Se accede
 * desde NewListFormaPago mediante el parámetro ?code=<codpago>. Sin código crea un
 * registro nuevo. Los handlers de guardado y borrado se registran automáticamente.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewEditFormaPago extends UIEditController
{
    public function getModelClassName(): string
    {
        return 'FormaPago';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu']  = 'accounting';
        $data['title'] = 'payment-method';
        $data['icon']  = 'fa-solid fa-credit-card';
        return $data;
    }

    public function listUrl(): string
    {
        return 'NewListFormaPago';
    }

    protected function getViewName(): string
    {
        return 'EditFormaPago';
    }

    protected function buildForm(): void
    {
        $this->loadModel();

        // Grupo principal: datos del pago
        $this->startGroup('data');

        $this->addComponent(
            ComponentText::make('codpago')
                ->setLabel('code')
                ->setIcon('fa-solid fa-hashtag')
                ->setMaxLength(10)
                ->setRequired()
                ->setReadOnlyDynamic()
                ->setDisplay('none')   // oculto por defecto, igual que display="none" en EditFormaPago.xml
                ->addRule(fn($v, $lang) =>
                    !preg_match('/^[A-Z0-9_+.\- ]{1,10}$/i', (string) $v)
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
        } else {
            // Single company: hidden input preserving the value
            $this->addComponent(
                ComponentSelect::make('idempresa')
                    ->setDisplay('none')
                    ->setValue($empresas[0]->idempresa ?? null)
            );
        }

        $cuentas = (new CuentaBanco())->all([], ['codcuenta' => 'ASC']);
        $this->addComponent(
            ComponentSelect::make('codcuentabanco')
                ->setLabel('bank-account')
                ->setLabelUrl('NewListFormaPago?activetab=ListCuentaBanco')
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

        // Grupo secundario: flags booleanos, alineados al fondo
        $this->startGroup('advanced', alignBottom: true);

        $this->addComponent(ComponentCheckbox::make('activa')->setLabel('active'));
        $this->addComponent(ComponentCheckbox::make('domiciliado')->setLabel('domiciled'));
        $this->addComponent(ComponentCheckbox::make('pagado')->setLabel('paid'));
        $this->addComponent(ComponentCheckbox::make('imprimir')->setLabel('print-bank-data'));
    }
}
