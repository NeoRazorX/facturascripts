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

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

/**
 * Description of CommonSalesPurchases
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 * @deprecated since version 2024.92 replaced by Facturascripts/Core/AjaxForms/CommonSalesPurchases
 */
trait CommonSalesPurchases
{
    /** @var string */
    protected static $columnView;

    public static function checkLevel(int $level): bool
    {
        $user = Session::user();

        // si el usuario no existe, devolvemos false
        if (false === $user->exists()) {
            return false;
        }

        // si el usuario es administrador, devolvemos true
        if ($user->admin) {
            return true;
        }

        // si el nivel es menor que el del usuario, devolvemos false
        return $level <= $user->level;
    }

    protected static function cifnif(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = $model->editable ? 'name="cifnif" maxlength="30" autocomplete="off"' : 'disabled';
        return '<div class="col-sm-6">'
            . '<div class="mb-3">' . $i18n->trans('cifnif')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->cifnif) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function children(Translator $i18n, TransformerDocument $model): string
    {
        if (empty($model->primaryColumnValue())) {
            return '';
        }

        $children = $model->childrenDocuments();
        switch (count($children)) {
            case 0:
                return '';

            case 1:
                return '<div class="col-sm-auto">'
                    . '<div class="mb-3 d-grid">'
                    . '<a href="' . $children[0]->url() . '" class="btn btn-info">'
                    . '<i class="fa-solid fa-forward fa-fw" aria-hidden="true"></i> ' . $children[0]->primaryDescription()
                    . '</a>'
                    . '</div>'
                    . '</div>';
        }

        // more than one
        return '<div class="col-sm-auto">'
            . '<div class="mb-3 d-grid">'
            . '<button class="btn btn-info" type="button" title="' . $i18n->trans('documents-generated')
            . '" data-bs-toggle="modal" data-bs-target="#childrenModal"><i class="fa-solid fa-forward fa-fw" aria-hidden="true"></i> '
            . count($children) . ' </button>'
            . '</div>'
            . '</div>'
            . self::modalDocList($i18n, $children, 'documents-generated', 'childrenModal');
    }

    protected static function codalmacen(Translator $i18n, BusinessDocument $model, string $jsFunc): string
    {
        $warehouses = 0;
        $options = [];
        foreach (Empresas::all() as $company) {
            if ($company->idempresa != $model->idempresa && $model->exists()) {
                continue;
            }

            $option = '';
            foreach ($company->getWarehouses() as $row) {
                // si el almacén no está activo o seleccionado, no lo mostramos
                if ($row->codalmacen != $model->codalmacen && !$row->activo) {
                    continue;
                }

                $option .= ($row->codalmacen === $model->codalmacen) ?
                    '<option value="' . $row->codalmacen . '" selected>' . $row->nombre . '</option>' :
                    '<option value="' . $row->codalmacen . '">' . $row->nombre . '</option>';
                $warehouses++;
            }
            $options[] = '<optgroup label="' . $company->nombrecorto . '">' . $option . '</optgroup>';
        }

        $attributes = $model->editable ?
            'name="codalmacen" onchange="return ' . $jsFunc . '(\'recalculate\', \'0\');" required' :
            'disabled';

        return empty($model->subjectColumnValue()) || $warehouses <= 1 ? '' : '<div class="col-sm-2 col-lg">'
            . '<div class="mb-3">'
            . '<a href="' . Almacenes::get($model->codalmacen)->url() . '">' . $i18n->trans('company-warehouse') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function coddivisa(Translator $i18n, BusinessDocument $model): string
    {
        $options = [];
        foreach (Divisas::all() as $row) {
            $options[] = ($row->coddivisa === $model->coddivisa) ?
                '<option value="' . $row->coddivisa . '" selected>' . $row->descripcion . '</option>' :
                '<option value="' . $row->coddivisa . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="coddivisa" required' : 'disabled';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm-6">'
            . '<div class="mb-3">'
            . '<a href="' . Divisas::get($model->coddivisa)->url() . '">' . $i18n->trans('currency') . '</a>'
            . '<select ' . $attributes . ' class="form-select">'
            . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function codpago(Translator $i18n, BusinessDocument $model): string
    {
        $options = [];
        foreach (FormasPago::all() as $row) {
            // saltamos las formas de pago de otras empresas
            if ($row->idempresa != $model->idempresa) {
                continue;
            }

            // si la forma de pago no está activa o seleccionada, la saltamos
            if ($row->codpago != $model->codpago && !$row->activa) {
                continue;
            }

            $options[] = ($row->codpago === $model->codpago) ?
                '<option value="' . $row->codpago . '" selected>' . $row->descripcion . '</option>' :
                '<option value="' . $row->codpago . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="codpago" required' : 'disabled';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm-3 col-md-2 col-lg">'
            . '<div id="payment-methods" class="mb-3">'
            . '<a href="' . FormasPago::get($model->codpago)->url() . '">' . $i18n->trans('payment-method') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function codserie(Translator $i18n, BusinessDocument $model, string $jsFunc): string
    {
        // es una factura rectificativa?
        $rectificativa = property_exists($model, 'idfacturarect') && $model->idfacturarect;

        $options = [];
        foreach (Series::all() as $row) {
            // es la serie seleccionada
            if ($row->codserie === $model->codserie) {
                $options[] = '<option value="' . $row->codserie . '" selected>' . $row->descripcion . '</option>';
                continue;
            }

            // si la serie es rectificativa y la factura también, la añadimos
            if ($rectificativa && $row->tipo === 'R') {
                $options[] = '<option value="' . $row->codserie . '">' . $row->descripcion . '</option>';
                continue;
            }

            // si la serie no es rectificativa y la factura tampoco, la añadimos
            if (false === $rectificativa && $row->tipo !== 'R') {
                $options[] = '<option value="' . $row->codserie . '">' . $row->descripcion . '</option>';
            }
        }

        $attributes = $model->editable ?
            'name="codserie" onchange="return ' . $jsFunc . '(\'recalculate\', \'0\');" required' :
            'disabled';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm-3 col-md-2 col-lg">'
            . '<div class="mb-3">'
            . '<a href="' . Series::get($model->codserie)->url() . '">' . $i18n->trans('serie') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function column(Translator $i18n, BusinessDocument $model, string $colName, string $label, bool $autoHide = false, int $level = 0): string
    {
        if (false === self::checkLevel($level)) {
            return '';
        }

        return empty($model->{$colName}) && $autoHide ? '' : '<div class="col-sm"><div class="mb-3">' . $i18n->trans($label)
            . '<input type="text" value="' . number_format($model->{$colName}, FS_NF0, FS_NF1, '')
            . '" class="form-control" disabled/></div></div>';
    }

    protected static function deleteBtn(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return $model->primaryColumnValue() && $model->editable ?
            '<button type="button" class="btn btn-spin-action btn-danger mb-3" data-bs-toggle="modal" data-bs-target="#deleteDocModal">'
            . '<i class="fa-solid fa-trash-alt fa-fw"></i> ' . $i18n->trans('delete')
            . '</button>'
            . '<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"></h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body text-center">'
            . '<i class="fa-solid fa-trash-alt fa-3x"></i>'
            . '<h5 class="mt-3 mb-1">' . $i18n->trans('confirm-delete') . '</h5>'
            . '<p class="mb-0">' . $i18n->trans('are-you-sure') . '</p>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-spin-action btn-secondary" data-bs-dismiss="modal">' . $i18n->trans('cancel') . '</button>'
            . '<button type="button" class="btn btn-spin-action btn-danger" onclick="return ' . $jsName . '(\'delete-doc\', \'0\');">'
            . $i18n->trans('delete') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>' : '';
    }

    protected static function dtopor1(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        if (empty($model->netosindto) && empty($model->dtopor1)) {
            return '<input type="hidden" name="dtopor1" value="0"/>';
        }

        $attributes = $model->editable ?
            'max="100" min="0" name="dtopor1" required step="any" onkeyup="return ' . $jsName . '(\'recalculate\', \'0\', event);"' :
            'disabled';
        return '<div class="col-sm"><div class="mb-3">' . $i18n->trans('global-dto')
            . '<div class="input-group">'
            . '<span class="input-group-text"><i class="fa-solid fa-percentage"></i></span>'
            . '<input type="number" ' . $attributes . ' value="' . floatval($model->dtopor1) . '" class="form-control"/>'
            . '</div></div></div>';
    }

    protected static function dtopor2(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        if (empty($model->dtopor1) && empty($model->dtopor2)) {
            return '<input type="hidden" name="dtopor2" value="0"/>';
        }

        $attributes = $model->editable ?
            'max="100" min="0" name="dtopor2" required step="any" onkeyup="return ' . $jsName . '(\'recalculate\', \'0\', event);"' :
            'disabled';
        return '<div class="col-sm-2 col-md"><div class="mb-3">' . $i18n->trans('global-dto-2')
            . '<div class="input-group">'
            . ''
            . '<span class="input-group-text"><i class="fa-solid fa-percentage"></i></span>'
            . ''
            . '<input type="number" ' . $attributes . ' value="' . floatval($model->dtopor2) . '" class="form-control"/>'
            . '</div></div></div>';
    }

    private static function email(Translator $i18n, BusinessDocument $model): string
    {
        return empty($model->femail) ? '' : '<div class="col-sm-auto">'
            . '<div class="mb-3">'
            . '<button class="btn btn-outline-info" type="button" title="' . $i18n->trans('email-sent')
            . '" data-bs-toggle="modal" data-bs-target="#headerModal"><i class="fa-solid fa-envelope fa-fw" aria-hidden="true"></i> '
            . $model->femail . ' </button></div></div>';
    }

    protected static function fastLineInput(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return $model->editable ? '<div class="col-8 col-md">'
            . '<div class="input-group mb-3">'
            . '<span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>'
            . '<input type="text" name="fastli" class="form-control" placeholder="' . $i18n->trans('barcode')
            . '" onkeyup="' . $jsName . '(event)"/>'
            . '</div></div>' : '<div class="col"></div>';
    }

    protected static function fecha(Translator $i18n, BusinessDocument $model, bool $enabled = true): string
    {
        $attributes = $model->editable && $enabled ? 'name="fecha" required' : 'disabled';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm">'
            . '<div id="document-date" class="mb-3">' . $i18n->trans('date')
            . '<input type="date" ' . $attributes . ' value="' . date('Y-m-d', strtotime($model->fecha)) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function fechadevengo(Translator $i18n, BusinessDocument $model): string
    {
        if (false === property_exists($model, 'fechadevengo')) {
            return '';
        }

        $attributes = $model->editable ? 'name="fechadevengo" required' : 'disabled';
        $value = empty($model->fechadevengo) ? '' : date('Y-m-d', strtotime($model->fechadevengo));
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm">'
            . '<div class="mb-3">' . $i18n->trans('accrual-date')
            . '<input type="date" ' . $attributes . ' value="' . $value . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function femail(Translator $i18n, BusinessDocument $model): string
    {
        if (empty($model->primaryColumnValue())) {
            return '';
        }

        $attributes = empty($model->femail) && $model->editable ? 'name="femail" ' : 'disabled';
        $value = empty($model->femail) ? '' : date('Y-m-d', strtotime($model->femail));
        return '<div class="col-sm-6">'
            . '<div class="mb-3">' . $i18n->trans('email-sent')
            . '<input type="date" ' . $attributes . ' value="' . $value . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function hora(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = $model->editable ? 'name="hora" required' : 'disabled';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm-6">'
            . '<div class="mb-3">' . $i18n->trans('hour')
            . '<input type="time" ' . $attributes . ' value="' . date('H:i:s', strtotime($model->hora)) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function idestado(Translator $i18n, TransformerDocument $model, string $jsName): string
    {
        // Si no se ha guardado no se puede cambiar el estado. Mantenemos el predeterminado
        if (empty($model->primaryColumnValue())) {
            return '';
        }

        $status = $model->getStatus();
        $btnClass = 'btn btn-secondary btn-spin-action';
        if (false === $status->editable && empty($status->generadoc) && empty($status->actualizastock)) {
            $btnClass = 'btn btn-danger btn-spin-action';
        }

        // si el estado genera documento, no se puede cambiar, sin eliminar el nuevo documento
        if ($status->generadoc) {
            return '<div class="col-sm-auto">'
                . '<div class="mb-3 d-grid">'
                . '<button type="button" class="' . $btnClass . '">'
                . '<i class="' . static::idestadoIcon($status) . ' fa-fw"></i> ' . $status->nombre
                . '</button>'
                . '</div>'
                . '</div>';
        }

        // añadimos los estados posibles
        $options = [];
        foreach ($model->getAvailableStatus() as $sta) {
            // si está seleccionado o no activo, lo saltamos
            if ($sta->idestado === $model->idestado || false === $sta->activo) {
                continue;
            }

            $options[] = '<a class="dropdown-item' . static::idestadoTextColor($sta) . '"'
                . ' href="#" onclick="return ' . $jsName . '(\'save-status\', \'' . $sta->idestado . '\', this);">'
                . '<i class="' . static::idestadoIcon($sta, true) . ' fa-fw"></i> ' . $sta->nombre . '</a>';
        }

        // añadimos la opción de agrupar o partir (excepto facturas y documentos no editables)
        if ($model->editable && false === in_array($model->modelClassName(), ['FacturaCliente', 'FacturaProveedor'])) {
            $options[] = '<div class="dropdown-divider"></div>'
                . '<a class="dropdown-item" href="DocumentStitcher?model=' . $model->modelClassName() . '&codes=' . $model->primaryColumnValue() . '">'
                . '<i class="fa-solid fa-magic fa-fw" aria-hidden="true"></i> ' . $i18n->trans('group-or-split')
                . '</a>';
        }

        return '<div class="col-sm-auto">'
            . '<div class="mb-3 statusButton">'
            . '<div class="dropdown">'
            . '<button class="' . $btnClass . ' dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
            . '<i class="' . static::idestadoIcon($status) . ' fa-fw"></i> ' . $status->nombre
            . '</button>'
            . '<div class="dropdown-menu dropdown-menu-end">' . implode('', $options) . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function idestadoIcon(EstadoDocumento $status, bool $alternative = false): string
    {
        if ($status->icon) {
            return $status->icon;
        } elseif ($status->generadoc && $alternative) {
            return 'fa-solid fa-forward';
        }

        return $status->editable ? 'fa-solid fa-pen' : 'fa-solid fa-lock';
    }

    protected static function idestadoTextColor(EstadoDocumento $status): string
    {
        if ($status->generadoc) {
            return ' text-success';
        }

        return false === $status->editable && empty($status->actualizastock) ? ' text-danger' : '';
    }

    public static function modalDocList(Translator $i18n, array $documents, string $title, string $id): string
    {
        $list = '';
        $sum = 0;
        foreach ($documents as $doc) {
            $list .= '<tr>'
                . '<td><a href="' . $doc->url() . '">' . $i18n->trans($doc->modelClassName()) . ' ' . $doc->codigo . '</a></td>'
                . '<td>' . $doc->observaciones . '</td>'
                . '<td class="text-end text-nowrap">' . Tools::money($doc->total) . '</td>'
                . '<td class="text-end text-nowrap">' . $doc->fecha . ' ' . $doc->hora . '</td>'
                . '</tr>';
            $sum += $doc->total;
        }

        // añadimos el total
        $list .= '<tr class="table-warning">'
            . '<td class="text-end text-nowrap" colspan="3">'
            . $i18n->trans('total') . ' <b>' . Tools::money($sum) . '</b></td>'
            . '<td></td>'
            . '</tr>';

        return '<div class="modal fade" tabindex="-1" id="' . $id . '">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fa-solid fa-copy fa-fw" aria-hidden="true"></i> ' . $i18n->trans($title) . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . $i18n->trans('close') . '">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="table-responsive">'
            . '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . $i18n->trans('document') . '</th>'
            . '<th>' . $i18n->trans('observations') . '</th>'
            . '<th class="text-end">' . $i18n->trans('total') . '</th>'
            . '<th class="text-end">' . $i18n->trans('date') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $list . '</tbody>'
            . '</table>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function netosindto(Translator $i18n, BusinessDocument $model): string
    {
        return empty($model->dtopor1) && empty($model->dtopor2) ? '' : '<div class="col-sm-2"><div class="mb-3">' . $i18n->trans('subtotal')
            . '<input type="text" value="' . number_format($model->netosindto, FS_NF0, FS_NF1, '')
            . '" class="form-control" disabled/></div></div>';
    }

    protected static function newLineBtn(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return $model->editable ? '<div class="col-3 col-md-auto d-grid">'
            . '<a href="#" class="btn btn-success btn-spin-action mb-3" onclick="return ' . $jsName . '(\'new-line\', \'0\');">'
            . '<i class="fa-solid fa-plus fa-fw"></i> ' . $i18n->trans('line') . '</a></div>' : '';
    }

    protected static function observaciones(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = $model->editable ? 'name="observaciones"' : 'disabled';
        $rows = 1;
        foreach (explode("\n", $model->observaciones ?? '') as $desLine) {
            $rows += mb_strlen($desLine) < 140 ? 1 : ceil(mb_strlen($desLine) / 140);
        }

        return '<div class="col-sm-12"><div class="mb-3">' . $i18n->trans('observations')
            . '<textarea ' . $attributes . ' class="form-control" placeholder="' . $i18n->trans('observations')
            . '" rows="' . $rows . '">' . Tools::noHtml($model->observaciones) . '</textarea>'
            . '</div></div>';
    }

    protected static function operacion(Translator $i18n, BusinessDocument $model): string
    {
        $options = ['<option value="">------</option>'];
        foreach (InvoiceOperation::all() as $key => $value) {
            $options[] = ($key === $model->operacion) ?
                '<option value="' . $key . '" selected>' . $i18n->trans($value) . '</option>' :
                '<option value="' . $key . '">' . $i18n->trans($value) . '</option>';
        }

        $attributes = $model->editable ? ' name="operacion"' : ' disabled';
        return '<div class="col-sm-6">'
            . '<div class="mb-3">' . $i18n->trans('operation')
            . '<select' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function paid(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        if (empty($model->primaryColumnValue()) || false === method_exists($model, 'getReceipts')) {
            return '';
        }

        if ($model->paid()) {
            return '<div class="col-sm-auto">'
                . '<div class="mb-3">'
                . '<button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">'
                . '<i class="fa-solid fa-check-square fa-fw"></i> ' . $i18n->trans('paid') . '</button>'
                . '<div class="dropdown-menu"><a class="dropdown-item text-danger" href="#" onclick="return ' . $jsName . '(\'save-paid\', \'0\');">'
                . '<i class="fa-solid fa-times fa-fw"></i> ' . $i18n->trans('unpaid') . '</a></div>'
                . '</div>'
                . '</div>';
        }

        return '<div class="col-sm-auto">'
            . '<div class="mb-3">'
            . '<button class="btn btn-spin-action btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">'
            . '<i class="fa-solid fa-times fa-fw"></i> ' . $i18n->trans('unpaid') . '</button>'
            . '<div class="dropdown-menu"><a class="dropdown-item text-success" href="#" onclick="showModalPaymentConditions(' . $jsName . ')">'
            . '<i class="fa-solid fa-check-square fa-fw"></i> ' . $i18n->trans('paid') . '</a></div>'
            . '</div>'
            . '</div>';
    }

    protected static function parents(Translator $i18n, TransformerDocument $model): string
    {
        if (empty($model->primaryColumnValue())) {
            return '';
        }

        $parents = $model->parentDocuments();
        switch (count($parents)) {
            case 0:
                return '';

            case 1:
                return '<div class="col-sm-auto">'
                    . '<div class="mb-3 d-grid">'
                    . '<a href="' . $parents[0]->url() . '" class="btn btn-warning">'
                    . '<i class="fa-solid fa-backward fa-fw" aria-hidden="true"></i> ' . $parents[0]->primaryDescription()
                    . '</a>'
                    . '</div>'
                    . '</div>';
        }

        // more than one
        return '<div class="col-sm-auto">'
            . '<div class="mb-3 d-grid">'
            . '<button class="btn btn-warning" type="button" title="' . $i18n->trans('previous-documents')
            . '" data-bs-toggle="modal" data-bs-target="#parentsModal"><i class="fa-solid fa-backward fa-fw" aria-hidden="true"></i> '
            . count($parents) . ' </button>'
            . '</div>'
            . '</div>'
            . self::modalDocList($i18n, $parents, 'previous-documents', 'parentsModal');
    }

    protected static function productBtn(Translator $i18n, BusinessDocument $model): string
    {
        return $model->editable ? '<div class="col-9 col-md col-lg-2">'
            . '<div class="input-group mb-3">'
            . '<input type="text" id="findProductInput" class="form-control" placeholder="' . $i18n->trans('reference') . '"/>'
            . '<button class="btn btn-info" type="button" onclick="$(\'#findProductModal\').modal(\'show\');'
            . ' $(\'#productModalInput\').select();"><i class="fa-solid fa-book fa-fw"></i></button>'
            . '</div>'
            . '</div>' : '';
    }

    protected static function saveBtn(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return $model->subjectColumnValue() && $model->editable ? '<button type="button" class="btn btn-primary btn-spin-action"'
            . ' load-after="true" onclick="return ' . $jsName . '(\'save-doc\', \'0\');">'
            . '<i class="fa-solid fa-save fa-fw"></i> ' . $i18n->trans('save')
            . '</button>' : '';
    }

    protected static function sortableBtn(Translator $i18n, BusinessDocument $model): string
    {
        return $model->editable ? '<div class="col-4 col-md-auto d-grid">'
            . '<button type="button" class="btn btn-light mb-3" id="sortableBtn">'
            . '<i class="fa-solid fa-arrows-alt-v fa-fw"></i> ' . $i18n->trans('move-lines')
            . '</button>'
            . '</div>' : '';
    }

    protected static function subtotalNetoBtn(Translator $i18n): string
    {
        $html = '<div class="col-12 col-md-auto mb-3 d-grid">'
            . '<div id="columnView" class="btn-group " role="group">';

        if ('subtotal' === self::$columnView) {
            $html .= '<button type="button" class="btn btn-light" data-column="neto" onclick="changeColumn(this)">'
                . $i18n->trans('net') . '</button>'
                . '<button type="button" class="btn btn-light active" data-column="subtotal" onclick="changeColumn(this)">'
                . $i18n->trans('subtotal') . '</button>';
        } else {
            $html .= '<button type="button" class="btn btn-light active" data-column="neto" onclick="changeColumn(this)">'
                . $i18n->trans('net') . '</button>'
                . '<button type="button" class="btn btn-light" data-column="subtotal" onclick="changeColumn(this)">'
                . $i18n->trans('subtotal') . '</button>';
        }

        $html .= '</div></div>';
        return $html;
    }

    protected static function tasaconv(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = $model->editable ? 'name="tasaconv" step="any" autocomplete="off"' : 'disabled';
        return '<div class="col-sm-6">'
            . '<div class="mb-3">' . $i18n->trans('conversion-rate')
            . '<input type="number" ' . $attributes . ' value="' . floatval($model->tasaconv) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function total(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return empty($model->total) ? '' : '<div class="col-sm"><div class="mb-3">' . $i18n->trans('total')
            . '<div class="input-group">'
            . '<input type="text" value="' . number_format($model->total, FS_NF0, FS_NF1, '')
            . '" class="form-control" disabled/>'
            . '<button class="btn btn-primary btn-spin-action" onclick="return ' . $jsName
            . '(\'save-doc\', \'0\');" title="' . $i18n->trans('save') . '" type="button">'
            . '<i class="fa-solid fa-save fa-fw"></i></button>'
            . '</div></div></div>';
    }

    protected static function undoBtn(Translator $i18n, BusinessDocument $model): string
    {
        return $model->subjectColumnValue() && $model->editable ? '<a href="' . $model->url() . '" class="btn btn-secondary me-2">'
            . '<i class="fa-solid fa-undo fa-fw"></i> ' . $i18n->trans('undo')
            . '</a>' : '';
    }

    protected static function user(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = 'disabled';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm-6">'
            . '<div class="mb-3">' . $i18n->trans('user')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->nick) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }
}
