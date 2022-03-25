<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

trait CommonSalesPurchases
{
    protected static function cifnif(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = $model->editable ? 'name="cifnif" maxlength="30" autocomplete="off"' : 'disabled=""';
        return '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('cifnif')
            . '<input type="text" ' . $attributes . ' value="' . $model->cifnif . '" class="form-control"/>'
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
                    . '<div class="form-group">'
                    . '<a href="' . $children[0]->url() . '" class="btn btn-block btn-info">'
                    . '<i class="fas fa-forward fa-fw" aria-hidden="true"></i> ' . $children[0]->primaryDescription()
                    . '</a>'
                    . '</div>'
                    . '</div>';
        }

        // more than one
        return '<div class="col-sm-auto">'
            . '<div class="form-group">'
            . '<button class="btn btn-block btn-info" type="button" data-toggle="modal" data-target="#documentsModal">'
            . '<i class="fas fa-forward fa-fw" aria-hidden="true"></i> ' . count($children) . ' </button>'
            . '</div>'
            . '</div>'
            . self::modalDocList($i18n, $children);
    }

    protected static function codalmacen(Translator $i18n, BusinessDocument $model): string
    {
        $options = [];
        foreach (Almacenes::all() as $row) {
            $options[] = ($row->codalmacen === $model->codalmacen) ?
                '<option value="' . $row->codalmacen . '" selected="">' . $row->nombre . '</option>' :
                '<option value="' . $row->codalmacen . '">' . $row->nombre . '</option>';
        }

        $attributes = $model->editable ? 'name="codalmacen" required=""' : 'disabled=""';
        return empty($model->subjectColumnValue()) || count($options) <= 1 ? '' : '<div class="col-sm-2 col-lg">'
            . '<div class="form-group">'
            . '<a href="' . Almacenes::get($model->codalmacen)->url() . '">' . $i18n->trans('warehouse') . '</a>'
            . '<select ' . $attributes . ' class="form-control">'
            . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function coddivisa(Translator $i18n, BusinessDocument $model): string
    {
        $options = [];
        foreach (Divisas::all() as $row) {
            $options[] = ($row->coddivisa === $model->coddivisa) ?
                '<option value="' . $row->coddivisa . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->coddivisa . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="coddivisa" required=""' : 'disabled=""';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . '<a href="' . Divisas::get($model->coddivisa)->url() . '">' . $i18n->trans('currency') . '</a>'
            . '<select ' . $attributes . ' class="form-control">'
            . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function codpago(Translator $i18n, BusinessDocument $model): string
    {
        $options = [];
        foreach (FormasPago::all() as $row) {
            $options[] = ($row->codpago === $model->codpago) ?
                '<option value="' . $row->codpago . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codpago . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="codpago" required=""' : 'disabled=""';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . '<a href="' . Paises::get($model->codpago)->url() . '">' . $i18n->trans('payment-method') . '</a>'
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function codserie(Translator $i18n, BusinessDocument $model, string $jsFunc): string
    {
        $options = [];
        foreach (Series::all() as $row) {
            $options[] = ($row->codserie === $model->codserie) ?
                '<option value="' . $row->codserie . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codserie . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ?
            'name="codserie" onchange="return ' . $jsFunc . '(\'recalculate\', \'0\');" required=""' :
            'disabled=""';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm-2 col-lg">'
            . '<div class="form-group">'
            . '<a href="' . Series::get($model->codserie)->url() . '">' . $i18n->trans('serie') . '</a>'
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function column(Translator $i18n, BusinessDocument $model, string $colName, string $label, bool $autoHide = false): string
    {
        return empty($model->{$colName}) && $autoHide ? '' : '<div class="col-sm"><div class="form-group">' . $i18n->trans($label)
            . '<input type="text" value="' . number_format($model->{$colName}, FS_NF0, FS_NF1, '')
            . '" class="form-control" disabled=""/></div></div>';
    }

    protected static function deleteBtn(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return $model->primaryColumnValue() && $model->editable ?
            '<button type="button" class="btn btn-danger mb-3" data-toggle="modal" data-target="#deleteDocModal">'
            . '<i class="fas fa-trash-alt fa-fw"></i> ' . $i18n->trans('delete')
            . '</button>'
            . '<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"></h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body text-center">'
            . '<i class="fas fa-trash-alt fa-3x"></i>'
            . '<h5 class="mt-3 mb-1">' . $i18n->trans('confirm-delete') . '</h5>'
            . '<p class="mb-0">' . $i18n->trans('are-you-sure') . '</p>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">' . $i18n->trans('cancel') . '</button>'
            . '<button type="button" class="btn btn-danger" onclick="return ' . $jsName . '(\'delete-doc\', \'0\');">' . $i18n->trans('delete') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>' : '';
    }

    protected static function dtopor1(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        $attributes = $model->editable ?
            'max="100" min="0" name="dtopor1" required="" step="any" onchange="return ' . $jsName . '(\'recalculate\', \'0\');"' :
            'disabled=""';
        return empty($model->netosindto) ? '' : '<div class="col-sm"><div class="form-group">' . $i18n->trans('global-dto')
            . '<div class="input-group">'
            . '<div class="input-group-prepend">'
            . '<span class="input-group-text"><i class="fas fa-percentage"></i></span>'
            . '</div>'
            . '<input type="number" ' . $attributes . ' value="' . $model->dtopor1 . '" class="form-control"/>'
            . '</div></div></div>';
    }

    protected static function dtopor2(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        $attributes = $model->editable ?
            'max="100" min="0" name="dtopor2" required="" step="any" onchange="return ' . $jsName . '(\'recalculate\', \'0\');"' :
            'disabled=""';
        return empty($model->dtopor1) ? '' : '<div class="col-sm-2 col-md"><div class="form-group">' . $i18n->trans('global-dto-2')
            . '<div class="input-group">'
            . '<div class="input-group-prepend">'
            . '<span class="input-group-text"><i class="fas fa-percentage"></i></span>'
            . '</div>'
            . '<input type="number" ' . $attributes . ' value="' . $model->dtopor2 . '" class="form-control"/>'
            . '</div></div></div>';
    }

    protected static function fastLineInput(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return $model->editable ? '<div class="col-sm">'
            . '<div class="input-group mb-3">'
            . '<div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-barcode"></i></span></div>'
            . '<input type="text" name="fastli" class="form-control" placeholder="' . $i18n->trans('barcode')
            . ' / ' . $i18n->trans('reference') . '" onkeyup="' . $jsName . '(event)"/>'
            . '</div></div>' : '<div class="col-sm"></div>';
    }

    protected static function fecha(Translator $i18n, BusinessDocument $model, bool $enabled = true): string
    {
        $attributes = $model->editable && $enabled ? 'name="fecha" required=""' : 'disabled=""';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('date')
            . '<input type="date" ' . $attributes . ' value="' . date('Y-m-d', strtotime($model->fecha)) . '" class="form-control" />'
            . '</div>'
            . '</div>';
    }

    protected static function femail(Translator $i18n, BusinessDocument $model): string
    {
        if (empty($model->primaryColumnValue())) {
            return '';
        }

        $attributes = empty($model->femail) && $model->editable ? 'name="femail" ' : 'disabled=""';
        $value = empty($model->femail) ? '' : date('Y-m-d', strtotime($model->femail));
        return '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('email-sent')
            . '<input type="date" ' . $attributes . ' value="' . $value . '" class="form-control" />'
            . '</div>'
            . '</div>';
    }

    protected static function hora(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = $model->editable ? 'name="hora" required=""' : 'disabled=""';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('hour')
            . '<input type="time" ' . $attributes . ' value="' . date('H:i:s', strtotime($model->hora)) . '" class="form-control" />'
            . '</div>'
            . '</div>';
    }

    protected static function idestado(Translator $i18n, TransformerDocument $model, string $jsName): string
    {
        if (empty($model->primaryColumnValue())) {
            return '';
        }

        $status = $model->getStatus();
        $btnClass = 'btn btn-block btn-secondary btn-spin-action';
        if (false === $status->editable && empty($status->generadoc) && empty($status->actualizastock)) {
            $btnClass = 'btn btn-block btn-danger btn-spin-action';
        }

        if ($status->generadoc) {
            return '<div class="col-sm-auto">'
                . '<div class="form-group">'
                . '<button type="button" class="' . $btnClass . '">'
                . '<i class="' . static::idestadoIcon($status) . ' fa-fw"></i> ' . $status->nombre
                . '</button>'
                . '</div>'
                . '</div>';
        }

        $options = [];
        foreach ($model->getAvailableStatus() as $sta) {
            if ($sta->idestado === $model->idestado) {
                continue;
            }

            $options[] = '<a class="dropdown-item' . static::idestadoTextColor($sta) . '"'
                . ' href="#" onclick="return ' . $jsName . '(\'save-status\', \'' . $sta->idestado . '\', this);">'
                . '<i class="' . static::idestadoIcon($sta, true) . ' fa-fw"></i> ' . $sta->nombre
                . '</a>';
        }

        if ($model->editable) {
            $options[] = '<div class="dropdown-divider"></div>'
                . '<a class="dropdown-item" href="DocumentStitcher?model=' . $model->modelClassName() . '&codes=' . $model->primaryColumnValue() . '">'
                . '<i class="fas fa-magic fa-fw" aria-hidden="true"></i> ' . $i18n->trans('group-or-split')
                . '</a>';
        }

        return '<div class="col-sm-auto">'
            . '<div class="form-group statusButton">'
            . '<div class="dropdown">'
            . '<button class="' . $btnClass . ' dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
            . '<i class="' . static::idestadoIcon($status) . ' fa-fw"></i> ' . $status->nombre
            . '</button>'
            . '<div class="dropdown-menu dropdown-menu-right">' . implode('', $options) . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function idestadoIcon(EstadoDocumento $status, bool $alternative = false): string
    {
        if ($status->icon) {
            return $status->icon;
        } elseif ($status->generadoc && $alternative) {
            return 'fas fa-forward';
        }

        return $status->editable ? 'fas fa-pen' : 'fas fa-lock';
    }

    protected static function idestadoTextColor(EstadoDocumento $status): string
    {
        if ($status->generadoc) {
            return ' text-success';
        }

        return false === $status->editable && empty($status->actualizastock) ? ' text-danger' : '';
    }

    public static function modalDocList(Translator $i18n, array $documents): string
    {
        $list = '';
        foreach ($documents as $doc) {
            $list .= '<tr>';
            $list .= '<td><a href="' . $doc->url() . '">' . $i18n->trans($doc->modelClassName()) . ' ' . $doc->codigo . '</a></td>';
            $list .= '<td>' . $doc->observaciones . '</td>';
            $list .= '<td class="text-right">' . ToolBox::coins()::format($doc->total) . '</td>';
            $list .= '<td class="text-right">' . $doc->fecha . ' ' . $doc->hora . '</td>';
            $list .= '</tr>';
        }

        return '<div class="modal fade" tabindex="-1" id="documentsModal">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">' . $i18n->trans('documents-generated') . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="' . $i18n->trans('close') . '">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body"></div>'
            . '<div class="table-responsive">'
            . '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . $i18n->trans('document') . '</th>'
            . '<th>' . $i18n->trans('observations') . '</th>'
            . '<th class="text-right">' . $i18n->trans('total') . '</th>'
            . '<th class="text-right">' . $i18n->trans('date') . '</th>'
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
        return empty($model->dtopor1) ? '' : '<div class="col-sm-2"><div class="form-group">'
            . $i18n->trans('subtotal')
            . '<input type="text" value="' . number_format($model->netosindto, FS_NF0, FS_NF1, '')
            . '" class="form-control" disabled=""/></div></div>';
    }

    protected static function newLineBtn(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return $model->editable ? '<div class="col-sm-6 col-md-auto">'
            . '<a href="#" class="btn btn-success btn-spin-action mb-3" onclick="return ' . $jsName . '(\'new-line\', \'0\');">'
            . '<i class="fas fa-plus fa-fw"></i> ' . $i18n->trans('line')
            . '</a></div>' : '';
    }

    protected static function observaciones(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = $model->editable ? 'name="observaciones"' : 'disabled=""';
        $rows = 1;
        foreach (explode("\n", $model->observaciones) as $desLine) {
            $rows += mb_strlen($desLine) < 140 ? 1 : ceil(mb_strlen($desLine) / 140);
        }

        return '<div class="col-sm-12"><div class="form-group">'
            . $i18n->trans('observations')
            . '<textarea ' . $attributes . ' class="form-control" placeholder="' . $i18n->trans('observations')
            . '" rows="' . $rows . '">' . $model->observaciones . '</textarea>'
            . '</div></div>';
    }

    protected static function paid(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        if (empty($model->primaryColumnValue()) || false === method_exists($model, 'getReceipts')) {
            return '';
        }

        if ($model->paid()) {
            return '<div class="col-sm-auto">'
                . '<div class="form-group">'
                . '<button class="btn btn-outline-success dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">'
                . '<i class="fas fa-check-square fa-fw"></i> ' . $i18n->trans('paid') . '</button>'
                . '<div class="dropdown-menu"><a class="dropdown-item text-danger" href="#" onclick="return ' . $jsName . '(\'save-paid\', \'0\');">'
                . '<i class="fas fa-times fa-fw"></i> ' . $i18n->trans('unpaid') . '</a></div>'
                . '</div>'
                . '</div>';
        }

        return '<div class="col-sm-auto">'
            . '<div class="form-group">'
            . '<button class="btn btn-outline-danger dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">'
            . '<i class="fas fa-times fa-fw"></i> ' . $i18n->trans('unpaid') . '</button>'
            . '<div class="dropdown-menu"><a class="dropdown-item text-success" href="#" onclick="return ' . $jsName . '(\'save-paid\', \'1\');">'
            . '<i class="fas fa-check-square fa-fw"></i> ' . $i18n->trans('paid') . '</a></div>'
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
                    . '<div class="form-group">'
                    . '<a href="' . $parents[0]->url() . '" class="btn btn-block btn-warning">'
                    . '<i class="fas fa-backward fa-fw" aria-hidden="true"></i> ' . $parents[0]->primaryDescription()
                    . '</a>'
                    . '</div>'
                    . '</div>';
        }

        // more than one
        return '<div class="col-sm-auto">'
            . '<div class="form-group">'
            . '<button class="btn btn-block btn-warning" type="button" data-toggle="modal" data-target="#documentsModal">'
            . '<i class="fas fa-backward fa-fw" aria-hidden="true"></i> ' . count($parents) . ' </button>'
            . '</div>'
            . '</div>'
            . self::modalDocList($i18n, $parents);
    }

    protected static function productBtn(Translator $i18n, BusinessDocument $model): string
    {
        return $model->editable ? '<div class="col-sm-6 col-md-auto">'
            . '<a href="#" class="btn btn-info mb-3" onclick="$(\'#findProductModal\').modal(); $(\'#findProductInput\').focus(); return false;">'
            . '<i class="fas fa-search fa-fw"></i> ' . $i18n->trans('products')
            . '</a>'
            . '</div>' : '';
    }

    protected static function saveBtn(Translator $i18n, BusinessDocument $model, string $jsName): string
    {
        return $model->subjectColumnValue() && $model->editable ? '<button type="button" class="btn btn-primary btn-spin-action"'
            . ' load-after="true" onclick="return ' . $jsName . '(\'save-doc\', \'0\');">'
            . '<i class="fas fa-save fa-fw"></i> ' . $i18n->trans('save')
            . '</button>' : '';
    }

    protected static function sortableBtn(Translator $i18n, BusinessDocument $model): string
    {
        return $model->editable ? '<div class="col-sm-6 col-md-auto">'
            . '<button type="button" class="btn btn-light mb-3" id="sortableBtn">'
            . '<i class="fas fa-arrows-alt-v fa-fw"></i> ' . $i18n->trans('move-lines')
            . '</button>'
            . '</div>' : '';
    }

    protected static function tasaconv(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = $model->editable ? 'name="tasaconv" step="any" autocomplete="off"' : 'disabled=""';
        return '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('conversion-rate')
            . '<input type="number" ' . $attributes . ' value="' . $model->tasaconv . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function user(Translator $i18n, BusinessDocument $model): string
    {
        $attributes = 'disabled=""';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('user')
            . '<input type="text" ' . $attributes . ' value="' . $model->nick . '" class="form-control" />'
            . '</div>'
            . '</div>';
    }
}
