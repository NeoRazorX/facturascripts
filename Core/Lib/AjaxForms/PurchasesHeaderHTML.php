<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\AjaxForms;

use FacturaScripts\Core\Contract\PurchasesModInterface;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of PurchasesHeaderHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Daniel Fernández Giménez      <hola@danielfg.es>
 */
class PurchasesHeaderHTML
{
    use CommonSalesPurchases;

    /** @var PurchasesModInterface[] */
    private static $mods = [];

    public static function addMod(PurchasesModInterface $mod): void
    {
        self::$mods[] = $mod;
    }

    public static function apply(PurchaseDocument &$model, array $formData): void
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->applyBefore($model, $formData);
        }

        $proveedor = new Proveedor();
        if (empty($model->primaryColumnValue())) {
            // new record. Sets user and supplier
            $model->setAuthor(Session::user());
            if (isset($formData['codproveedor']) && $formData['codproveedor'] && $proveedor->loadFromCode($formData['codproveedor'])) {
                $model->setSubject($proveedor);
                if (empty($formData['action']) || $formData['action'] === 'set-supplier') {
                    return;
                }
            }
        } elseif (isset($formData['action'], $formData['codproveedor']) &&
            $formData['action'] === 'set-supplier' &&
            $proveedor->loadFromCode($formData['codproveedor'])) {
            // existing record and change supplier
            $model->setSubject($proveedor);
            return;
        }

        $model->setWarehouse($formData['codalmacen'] ?? $model->codalmacen);
        $model->cifnif = $formData['cifnif'] ?? $model->cifnif;
        $model->coddivisa = $formData['coddivisa'] ?? $model->coddivisa;
        $model->codpago = $formData['codpago'] ?? $model->codpago;
        $model->codproveedor = $formData['codproveedor'] ?? $model->codproveedor;
        $model->codserie = $formData['codserie'] ?? $model->codserie;
        $model->fecha = empty($formData['fecha']) ? $model->fecha : Tools::date($formData['fecha']);
        $model->femail = isset($formData['femail']) && !empty($formData['femail']) ? $formData['femail'] : $model->femail;
        $model->hora = $formData['hora'] ?? $model->hora;
        $model->nombre = $formData['nombre'] ?? $model->nombre;
        $model->numproveedor = $formData['numproveedor'] ?? $model->numproveedor;
        $model->operacion = $formData['operacion'] ?? $model->operacion;
        $model->tasaconv = (float)($formData['tasaconv'] ?? $model->tasaconv);

        foreach (['fechadevengo'] as $key) {
            if (isset($formData[$key])) {
                $model->{$key} = empty($formData[$key]) ? null : $formData[$key];
            }
        }

        // mods
        foreach (self::$mods as $mod) {
            $mod->apply($model, $formData);
        }
    }

    public static function assets(): void
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->assets();
        }
    }

    public static function render(PurchaseDocument $model): string
    {
        return '<div class="container-fluid">'
            . '<div class="row g-3 align-items-end">'
            . self::renderField($model, 'codproveedor')
            . self::renderField($model, 'codalmacen')
            . self::renderField($model, 'codserie')
            . self::renderField($model, 'fecha')
            . self::renderNewFields($model)
            . self::renderField($model, 'numproveedor')
            . self::renderField($model, 'codpago')
            . self::renderField($model, 'total')
            . '</div>'
            . '<div class="row g-3 align-items-end">'
            . self::renderField($model, '_detail')
            . self::renderField($model, '_parents')
            . self::renderField($model, '_children')
            . self::renderField($model, '_email')
            . self::renderNewBtnFields($model)
            . self::renderField($model, '_paid')
            . self::renderField($model, 'idestado')
            . '</div>'
            . '</div>';
    }

    private static function codproveedor(PurchaseDocument $model): string
    {
        $proveedor = new Proveedor();
        if (empty($model->codproveedor) || false === $proveedor->loadFromCode($model->codproveedor)) {
            return '<div class="col-sm-3">'
                . '<div class="mb-3">' . Tools::lang()->trans('supplier')
                . '<input type="hidden" name="codproveedor" />'
                . '<a href="#" id="btnFindSupplierModal" class="btn btn-block btn-primary" onclick="$(\'#findSupplierModal\').modal(\'show\');'
                . ' $(\'#findSupplierInput\').focus(); return false;"><i class="fa-solid fa-users fa-fw"></i> '
                . Tools::lang()->trans('select') . '</a>'
                . '</div>'
                . '</div>'
                . self::detailModal($model);
        }

        $btnProveedor = $model->editable ?
            '<button class="btn btn-outline-secondary" type="button" onclick="$(\'#findSupplierModal\').modal(\'show\');'
            . ' $(\'#findSupplierInput\').focus(); return false;"><i class="fa-solid fa-pen"></i></button>' :
            '<button class="btn btn-outline-secondary" type="button"><i class="fa-solid fa-lock"></i></button>';

        $html = '<div class="col-sm-3 col-lg">'
            . '<div class="mb-3">'
            . '<a href="' . $proveedor->url() . '">' . Tools::lang()->trans('supplier') . '</a>'
            . '<input type="hidden" name="codproveedor" value="' . $model->codproveedor . '" />'
            . '<div class="input-group">'
            . '<input type="text" value="' . Tools::noHtml($proveedor->nombre) . '" class="form-control" readonly />'
            . '' . $btnProveedor . ''
            . '</div>'
            . '</div>'
            . '</div>';

        if (empty($model->primaryColumnValue())) {
            $html .= self::detail($model, true);
        }

        return $html;
    }

    private static function detail(PurchaseDocument $model, bool $new = false): string
    {
        if (empty($model->primaryColumnValue()) && $new === false) {
            // si el modelo es nuevo, ya hemos pintado el modal de detalle
            return '';
        }

        $css = $new ? 'col-sm-auto' : 'col-sm';
        return '<div class="' . $css . '">'
            . '<div class="mb-3">'
            . '<button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#headerModal">'
            . '<i class="fa-solid fa-edit fa-fw" aria-hidden="true"></i> ' . Tools::lang()->trans('detail') . ' </button>'
            . '</div>'
            . '</div>'
            . self::detailModal($model);
    }

    private static function detailModal(PurchaseDocument $model): string
    {
        return '<div class="modal fade" id="headerModal" tabindex="-1" aria-labelledby="headerModalLabel" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">' . Tools::lang()->trans('detail') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-3">'
            . self::renderField($model, 'nombre')
            . self::renderField($model, 'cifnif')
            . self::renderField($model, 'fechadevengo')
            . self::renderField($model, 'hora')
            . self::renderField($model, 'operacion')
            . self::renderField($model, 'femail')
            . self::renderField($model, 'coddivisa')
            . self::renderField($model, 'tasaconv')
            . self::renderField($model, 'user')
            . self::renderNewModalFields($model)
            . '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Tools::lang()->trans('close') . '</button>'
            . '<button type="button" class="btn btn-primary" data-bs-dismiss="modal">' . Tools::lang()->trans('accept') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function nombre(PurchaseDocument $model): string
    {
        $attributes = $model->editable ? 'name="nombre" required=""' : 'disabled=""';
        return '<div class="col-sm-6">'
            . '<div class="mb-3">' . Tools::lang()->trans('business-name')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->nombre) . '" class="form-control" maxlength="100" autocomplete="off" />'
            . '</div>'
            . '</div>';
    }

    private static function numproveedor(PurchaseDocument $model): string
    {
        $attributes = $model->editable ? 'name="numproveedor"' : 'disabled=""';
        return empty($model->codproveedor) ? '' : '<div class="col-sm-3 col-md-2 col-lg">'
            . '<div class="mb-3">' . Tools::lang()->trans('numsupplier')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->numproveedor) . '" class="form-control" maxlength="50"'
            . ' placeholder="' . Tools::lang()->trans('optional') . '" />'
            . '</div>'
            . '</div>';
    }

    private static function renderField(PurchaseDocument $model, string $field): ?string
    {
        foreach (self::$mods as $mod) {
            $html = $mod->renderField($model, $field);
            if ($html !== null) {
                return $html;
            }
        }

        switch ($field) {
            case '_children':
                return self::children($model);

            case '_detail':
                return self::detail($model);

            case '_email':
                return self::email($model);

            case '_fecha':
                return self::fecha($model, false);

            case '_paid':
                return self::paid($model, 'purchasesFormSave');

            case '_parents':
                return self::parents($model);

            case 'cifnif':
                return self::cifnif($model);

            case 'codalmacen':
                return self::codalmacen($model, 'purchasesFormAction');

            case 'coddivisa':
                return self::coddivisa($model);

            case 'codpago':
                return self::codpago($model);

            case 'codproveedor':
                return self::codproveedor($model);

            case 'codserie':
                return self::codserie($model, 'purchasesFormAction');

            case 'fecha':
                return self::fecha($model);

            case 'fechadevengo':
                return self::fechadevengo($model);

            case 'femail':
                return self::femail($model);

            case 'hora':
                return self::hora($model);

            case 'idestado':
                return self::idestado($model, 'purchasesFormSave');

            case 'nombre':
                return self::nombre($model);

            case 'numproveedor':
                return self::numproveedor($model);

            case 'operacion':
                return self::operacion($model);

            case 'tasaconv':
                return self::tasaconv($model);

            case 'total':
                return self::total($model, 'purchasesFormSave');

            case 'user':
                return self::user($model);
        }

        return null;
    }

    private static function renderNewBtnFields(PurchaseDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newBtnFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewFields(PurchaseDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewModalFields(PurchaseDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newModalFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }
}
