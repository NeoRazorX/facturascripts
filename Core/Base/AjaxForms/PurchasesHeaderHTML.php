<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\Contract\PurchasesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of PurchasesHeaderHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Daniel Fernández Giménez      <hola@danielfg.es>
 *
 * @deprecated replaced by Core/Lib/AjaxForms/PurchasesHeaderHTML
 */
class PurchasesHeaderHTML
{
    use CommonSalesPurchases;

    /** @var PurchasesModInterface[] */
    private static $mods = [];

    public static function addMod(PurchasesModInterface $mod)
    {
        self::$mods[] = $mod;
    }

    public static function apply(PurchaseDocument &$model, array $formData, User $user)
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->applyBefore($model, $formData, $user);
        }

        $proveedor = new Proveedor();
        if (empty($model->primaryColumnValue())) {
            // new record. Sets user and supplier
            $model->setAuthor($user);
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
            $mod->apply($model, $formData, $user);
        }
    }

    public static function assets()
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->assets();
        }
    }

    public static function render(PurchaseDocument $model): string
    {
        $i18n = new Translator();
        return '<div class="container-fluid">'
            . '<div class="form-row align-items-end">'
            . self::renderField($i18n, $model, 'codproveedor')
            . self::renderField($i18n, $model, 'codalmacen')
            . self::renderField($i18n, $model, 'codserie')
            . self::renderField($i18n, $model, 'fecha')
            . self::renderNewFields($i18n, $model)
            . self::renderField($i18n, $model, 'numproveedor')
            . self::renderField($i18n, $model, 'codpago')
            . self::renderField($i18n, $model, 'total')
            . '</div>'
            . '<div class="form-row align-items-end">'
            . self::renderField($i18n, $model, '_detail')
            . self::renderField($i18n, $model, '_parents')
            . self::renderField($i18n, $model, '_children')
            . self::renderField($i18n, $model, '_email')
            . self::renderNewBtnFields($i18n, $model)
            . self::renderField($i18n, $model, '_paid')
            . self::renderField($i18n, $model, 'idestado')
            . '</div>'
            . '</div>';
    }

    private static function codproveedor(Translator $i18n, PurchaseDocument $model): string
    {
        $proveedor = new Proveedor();
        if (empty($model->codproveedor) || false === $proveedor->loadFromCode($model->codproveedor)) {
            return '<div class="col-sm-3">'
                . '<div class="form-group">' . $i18n->trans('supplier')
                . '<input type="hidden" name="codproveedor" />'
                . '<a href="#" id="btnFindSupplierModal" class="btn btn-block btn-primary" onclick="$(\'#findSupplierModal\').modal();'
                . ' $(\'#findSupplierInput\').focus(); return false;"><i class="fas fa-users fa-fw"></i> '
                . $i18n->trans('select') . '</a>'
                . '</div>'
                . '</div>'
                . self::detailModal($i18n, $model);
        }

        $btnProveedor = $model->editable ?
            '<button class="btn btn-outline-secondary" type="button" onclick="$(\'#findSupplierModal\').modal();'
            . ' $(\'#findSupplierInput\').focus(); return false;"><i class="fas fa-pen"></i></button>' :
            '<button class="btn btn-outline-secondary" type="button"><i class="fas fa-lock"></i></button>';

        $html = '<div class="col-sm-3 col-lg">'
            . '<div class="form-group">'
            . '<a href="' . $proveedor->url() . '">' . $i18n->trans('supplier') . '</a>'
            . '<input type="hidden" name="codproveedor" value="' . $model->codproveedor . '" />'
            . '<div class="input-group">'
            . '<input type="text" value="' . Tools::noHtml($proveedor->nombre) . '" class="form-control" readonly />'
            . '<div class="input-group-append">' . $btnProveedor . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';

        if (empty($model->primaryColumnValue())) {
            $html .= self::detail($i18n, $model, true);
        }

        return $html;
    }

    private static function detail(Translator $i18n, PurchaseDocument $model, bool $new = false): string
    {
        if (empty($model->primaryColumnValue()) && $new === false) {
            // si el modelo es nuevo, ya hemos pintado el modal de detalle
            return '';
        }

        $css = $new ? 'col-sm-auto' : 'col-sm';
        return '<div class="' . $css . '">'
            . '<div class="form-group">'
            . '<button class="btn btn-outline-secondary" type="button" data-toggle="modal" data-target="#headerModal">'
            . '<i class="fas fa-edit fa-fw" aria-hidden="true"></i> ' . $i18n->trans('detail') . ' </button>'
            . '</div>'
            . '</div>'
            . self::detailModal($i18n, $model);
    }

    private static function detailModal(Translator $i18n, PurchaseDocument $model): string
    {
        return '<div class="modal fade" id="headerModal" tabindex="-1" aria-labelledby="headerModalLabel" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">' . $i18n->trans('detail') . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, 'nombre')
            . self::renderField($i18n, $model, 'cifnif')
            . self::renderField($i18n, $model, 'fechadevengo')
            . self::renderField($i18n, $model, 'hora')
            . self::renderField($i18n, $model, 'operacion')
            . self::renderField($i18n, $model, 'femail')
            . self::renderField($i18n, $model, 'coddivisa')
            . self::renderField($i18n, $model, 'tasaconv')
            . self::renderField($i18n, $model, 'user')
            . self::renderNewModalFields($i18n, $model)
            . '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">' . $i18n->trans('close') . '</button>'
            . '<button type="button" class="btn btn-primary" data-dismiss="modal">' . $i18n->trans('accept') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function nombre(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = $model->editable ? 'name="nombre" required=""' : 'disabled=""';
        return '<div class="col-sm-6">'
            . '<div class="form-group">' . $i18n->trans('business-name')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->nombre) . '" class="form-control" maxlength="100" autocomplete="off" />'
            . '</div>'
            . '</div>';
    }

    private static function numproveedor(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = $model->editable ? 'name="numproveedor"' : 'disabled=""';
        return empty($model->codproveedor) ? '' : '<div class="col-sm-3 col-md-2 col-lg">'
            . '<div class="form-group">' . $i18n->trans('numsupplier')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->numproveedor) . '" class="form-control" maxlength="50"'
            . ' placeholder="' . $i18n->trans('optional') . '" />'
            . '</div>'
            . '</div>';
    }

    private static function renderField(Translator $i18n, PurchaseDocument $model, string $field): ?string
    {
        foreach (self::$mods as $mod) {
            $html = $mod->renderField($i18n, $model, $field);
            if ($html !== null) {
                return $html;
            }
        }

        switch ($field) {
            case '_children':
                return self::children($i18n, $model);

            case '_detail':
                return self::detail($i18n, $model);

            case '_email':
                return self::email($i18n, $model);

            case '_fecha':
                return self::fecha($i18n, $model, false);

            case '_paid':
                return self::paid($i18n, $model, 'purchasesFormSave');

            case '_parents':
                return self::parents($i18n, $model);

            case 'cifnif':
                return self::cifnif($i18n, $model);

            case 'codalmacen':
                return self::codalmacen($i18n, $model, 'purchasesFormAction');

            case 'coddivisa':
                return self::coddivisa($i18n, $model);

            case 'codpago':
                return self::codpago($i18n, $model);

            case 'codproveedor':
                return self::codproveedor($i18n, $model);

            case 'codserie':
                return self::codserie($i18n, $model, 'purchasesFormAction');

            case 'fecha':
                return self::fecha($i18n, $model);

            case 'fechadevengo':
                return self::fechadevengo($i18n, $model);

            case 'femail':
                return self::femail($i18n, $model);

            case 'hora':
                return self::hora($i18n, $model);

            case 'idestado':
                return self::idestado($i18n, $model, 'purchasesFormSave');

            case 'nombre':
                return self::nombre($i18n, $model);

            case 'numproveedor':
                return self::numproveedor($i18n, $model);

            case 'operacion':
                return self::operacion($i18n, $model);

            case 'tasaconv':
                return self::tasaconv($i18n, $model);

            case 'total':
                return self::total($i18n, $model, 'purchasesFormSave');

            case 'user':
                return self::user($i18n, $model);
        }

        return null;
    }

    private static function renderNewBtnFields(Translator $i18n, PurchaseDocument $model): string
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
                $fieldHtml = $mod->renderField($i18n, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewFields(Translator $i18n, PurchaseDocument $model): string
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
                $fieldHtml = $mod->renderField($i18n, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewModalFields(Translator $i18n, PurchaseDocument $model): string
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
                $fieldHtml = $mod->renderField($i18n, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }
}
