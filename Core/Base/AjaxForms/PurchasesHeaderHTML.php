<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Plugins\BetaForms\Contract\PurchasesModInterface;

/**
 * Description of PurchasesHeaderHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
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
                if (isset($formData['action']) && $formData['action'] === 'set-supplier') {
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

        $model->cifnif = $formData['cifnif'] ?? $model->cifnif;
        $model->codalmacen = $formData['codalmacen'] ?? $model->codalmacen;
        $model->coddivisa = $formData['coddivisa'] ?? $model->coddivisa;
        $model->codpago = $formData['codpago'] ?? $model->codpago;
        $model->codserie = $formData['codserie'] ?? $model->codserie;
        $model->fecha = $formData['fecha'] ?? $model->fecha;
        $model->femail = isset($formData['femail']) && !empty($formData['femail']) ? $formData['femail'] : $model->femail;
        $model->hora = $formData['hora'] ?? $model->hora;
        $model->nombre = $formData['nombre'] ?? $model->nombre;
        $model->numproveedor = $formData['numproveedor'] ?? $model->numproveedor;
        $model->tasaconv = (float)($formData['tasaconv'] ?? $model->tasaconv);

        // mods
        foreach (self::$mods as $mod) {
            $mod->apply($model, $formData, $user);
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
            . self::renderField($i18n, $model, 'numproveedor')
            . self::renderField($i18n, $model, 'codpago')
            . self::renderField($i18n, $model, 'total')
            . '</div>'
            . '<div class="form-row align-items-end">'
            . self::renderField($i18n, $model, '_detail')
            . self::renderField($i18n, $model, '_parents')
            . self::renderField($i18n, $model, '_children')
            . self::renderField($i18n, $model, 'paid')
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
                . '<a href="#" class="btn btn-block btn-primary" onclick="$(\'#findSupplierModal\').modal();'
                . ' $(\'#findSupplierInput\').focus(); return false;"><i class="fas fa-users fa-fw"></i> '
                . $i18n->trans('select') . '</a>'
                . '</div>'
                . '</div>';
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
            . '<input type="text" value="' . $proveedor->nombre . '" class="form-control" readonly />'
            . '<div class="input-group-append">' . $btnProveedor . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';

        if (empty($model->primaryColumnValue())) {
            $html .= self::detail($i18n, $model, true);
        }

        return $html;
    }

    private static function detail(Translator $i18n, PurchaseDocument $model, bool $force = false): string
    {
        if (empty($model->primaryColumnValue()) && $force === false) {
            return '';
        }

        $css = $force ? 'col-sm-auto' : 'col-sm';
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
            . '<h5 class="modal-title">'
            . $i18n->trans($model->modelClassName() . '-min') . ' ' . $model->codigo
            . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, '_fecha')
            . self::renderField($i18n, $model, 'hora')
            . '</div>'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, 'nombreproveedor')
            . '</div>'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, 'cifnif')
            . '</div>'
            . '<div class="form-row">'
            . self::renderNewFields($i18n, $model)
            . '</div>'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, 'coddivisa')
            . self::renderField($i18n, $model, 'tasaconv')
            . '</div>'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, 'femail')
            . self::renderField($i18n, $model, 'user')
            . '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">'
            . $i18n->trans('close')
            . '</button>'
            . '<button type="button" class="btn btn-primary" data-dismiss="modal">'
            . $i18n->trans('accept')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function nombreproveedor(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = $model->editable ? 'name="nombre" required=""' : 'disabled=""';
        return '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('business-name')
            . '<input type="text" ' . $attributes . ' value="' . $model->nombre . '" class="form-control" maxlength="100" autocomplete="off" />'
            . '</div>'
            . '</div>';
    }

    private static function numproveedor(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = $model->editable ? 'name="numproveedor"' : 'disabled=""';
        return empty($model->codproveedor) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('numsupplier')
            . '<input type="text" ' . $attributes . ' value="' . $model->numproveedor . '" class="form-control" maxlength="50"'
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

            case '_fecha':
                return self::fecha($i18n, $model, false);

            case '_parents':
                return self::parents($i18n, $model);

            case 'cifnif':
                return self::cifnif($i18n, $model);

            case 'codalmacen':
                return self::codalmacen($i18n, $model);

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

            case 'femail':
                return self::femail($i18n, $model);

            case 'hora':
                return self::hora($i18n, $model);

            case 'idestado':
                return self::idestado($i18n, $model, 'purchasesFormSave');

            case 'nombreproveedor':
                return self::nombreproveedor($i18n, $model);

            case 'numproveedor':
                return self::numproveedor($i18n, $model);

            case 'paid':
                return self::paid($i18n, $model, 'purchasesFormSave');

            case 'saveBtn':
                return self::saveBtn($i18n, $model, 'purchasesFormSave');

            case 'tasaconv':
                return self::tasaconv($i18n, $model);

            case 'total':
                return self::column($i18n, $model, 'total', 'total', true);

            case 'user':
                return self::user($i18n, $model);
        }

        return null;
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
}