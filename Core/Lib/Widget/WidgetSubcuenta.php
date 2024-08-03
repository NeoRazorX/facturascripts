<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Widget;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use Symfony\Component\HttpFoundation\Request;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Core\Translator;

use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Empresa;

/**
 * Description of WidgetSubcuenta
 *
 * @author Raúl
 */
class WidgetSubcuenta extends WidgetText {
    /** @var string */
    public $match;

    /**
     * @var string
     */
    protected static $orden;

    /**
     * @var string
     */
    protected static $query;

    protected static $i18n;

    /** @param array $data */
    public function __construct($data) {
        parent::__construct($data);

        $this->match = $data['match'] ?? 'codsubcuenta';
            self::$i18n = new Translator();
        if (empty($this->id)) {
            $this->id = $this->getUniqueId();
        }
        $this->type = 'Subcuenta';
    }

    public function edit($model, $title = '', $description = '', $titleurl = '') {
        $this->setValue($model);
        $descriptionHtml = empty($description) ?
                '' :
                '<small class="form-text text-muted">' . Tools::lang()->trans($description) . '</small>';
        $label = Tools::lang()->trans($title);
        $labelHtml = $this->onclickHtml($label, $titleurl);
        $icon = empty($this->icon) ? 'fas fa-book' : $this->icon;

        // hay que cargar la subcuenta
        $subcuenta = new Subcuenta();
        $subcuenta->loadFromCode('', [
            new DataBaseWhere($this->match, $this->value)
        ]);

        if ($this->readonly()) {
            $icon = 'fas fa-eye';
            $html = '<div class="col pb-2 small">' . $subcuenta->descripcion
                    . '<div class="input-group"><input type="text" value="' . $subcuenta->codsubcuenta
                    . '" class="form-control" tabindex="-1" readonly="">'
                    . '<div class="input-group-append">';

            if ($subcuenta->idsubcuenta) {
                $html .= '<a href="EditSubcuenta?code=' . $subcuenta->id . '" target="_blank" class="btn btn-outline-primary">'
                        . '<svg class="svg-inline--fa fa-eye" aria-hidden="true" focusable="false" data-prefix="far" data-icon="eye" '
                    . ' role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" data-fa-i2svg=""><path fill="currentColor" '
                    . 'd="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-.7 0-1.3 0-2 0c1.3 5.1 2 10.5 2 16c0 35.3-28.7 64-64 64c-5.5 0-10.9-.7-16-2c0 .7 0 1.3 0 2c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"></path></svg><!-- <i class="far fa-eye"></i> Font Awesome fontawesome.com -->'
                    . '</a>';
            }
            $html .= ' </div></div></div>';
            if ($subcuenta->idsubcuenta) {
                $html .= '<small>' . $subcuenta->descripcion . '</small>';
            }
        
        } else {
            $html = '<div class="col-sm-12 col-md-12 mb-12">'
                    . '<div class="input-group">'
                    . '<input type="hidden" id="' . $this->id . '"  name="' . $this->fieldname . '" value="' . $subcuenta->idsubcuenta . '"/>'
                    . '<input type="text" class="form-control" maxlength="15" autocomplete="off" placeholder="Subcuenta" '
                    . 'id="widgetsubcta_' . $this->id . '" name="' . $this->fieldname . '" onchange="" readonly="" value="' . $subcuenta->codsubcuenta . '">'
                    . '<div class="input-group-append">'
                    . '<button class="btn btn-info" type="button" title="Subcuentas" '
                    . 'onclick="$(\'#modal_' . $this->id . '\').modal(); $(\'#findSubaccountInput\').focus();">'
                    . '<svg class="svg-inline--fa fa-book" aria-hidden="true" focusable="false" data-prefix="fas" '
                    . 'data-icon="book" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg="">'
                    . '<path fill="currentColor" '
                    . 'd="M96 0C43 0 0 43 0 96V416c0 53 43 96 96 96H384h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V384c17.7 0 32-14.3 32-32V32c0-17.7-14.3-32-32-32H384 96zm0 384H352v64H96c-17.7 0-32-14.3-32-32s14.3-32 32-32zm32-240c0-8.8 7.2-16 16-16H336c8.8 0 16 7.2 16 16s-7.2 16-16 16H144c-8.8 0-16-7.2-16-16zm16 48H336c8.8 0 16 7.2 16 16s-7.2 16-16 16H144c-8.8 0-16-7.2-16-16s7.2-16 16-16z"></path></svg><!-- <i class="fas fa-book"></i> Font Awesome fontawesome.com --></button></div></div></div>';
            $html .= $this->renderModal($icon, $html);
        }


        return $html;
    }

    protected function assets() {
        AssetManager::addJs(FS_ROUTE . '/Dinamic/Assets/JS/WidgetSubcuenta.js');
    }

    protected function renderModal(string $icon, string $label): string {
        $ejercicio = new Ejercicio();
         $ejercicio->loadFromDate(date('Y-m-d'), true);
        $codejercicio = $ejercicio->codejercicio;
        $codempresa = Tools::Settings('default', 'codempresa');
        $html = '<div class="modal" id="modal_' . $this->id . '" tabindex="-1"  aria-labelledby="modal_'
                . $this->id . '_label" aria-hidden="true">'
                . '<div class="modal-dialog modal-xl">'
                . '<div class="modal-content">'
                . '<div class="modal-header">'
                . '<h5 class="modal-title" " id="modal_' . $this->id . '_label"><i class="fas ' . $icon . ' fa-fw"></i> ' . self::$i18n->trans('subaccounts') . '</h5>'
                . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                . '<span aria-hidden="true">&times;</span>'
                . '</button>'
                . '</div>'
                . '<div class="modal-body">'
                . '<div class="form-row">';
        $html .= '<input type="hidden" id="target_' . $this->id . '" value="widgetsubcta_' . $this->id . '"/>';
        $html .= $this->renderExerciseFilter();
        $html .= '<div class="col-sm">'
                . '<div class="input-group">'
                . '<input type="text" id="modal_' . $this->id . '_q" class="form-control" placeholder="' . self::$i18n->trans('search')
                . '" onkeyup="return findSubaccountSearch(\'' . $this->id . '\');"/>'
                . '<div class="input-group-apend">'
                . '<button class="btn btn-primary" type="button" onclick="findSubaccountSearch(\'' . $this->id . '\');"'
                . ' data-loading-text="<span class=\'spinner-border spinner-border-sm\' role=\'status\' aria-hidden=\'true\'></span>"><i class="fas fa-search"></i></button>'
                . '</div>'
                . '</div>'
                . '</div>'
                . static::orden(self::$i18n)
                . '</div>'
                . '</div>'
                . '<div class="table-responsive" id="findSubaccountList">' . static::renderSubaccountList($codejercicio, $codempresa) . '</div>'
                . '</div>'
                . '</div>'
                . '</div>';
        return $html;
    }

    protected function orden(): string {
        return '<div class="col-sm">'
                . '<div class="input-group">'
                . '<div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-sort-amount-down-alt"></i></span></div>'
                . '<select name="search_ordensubcta" id="search_ordensubcta" class="form-control" onchange="findSubaccountSearch(\'' . $this->id . '\');">'
                . '<option value="code_asc">' . self::$i18n->trans('code') . '</option>'
                . '<option value="desc_asc">' . self::$i18n->trans('description') . '</option>'
                . '<option value="saldo_desc">' . self::$i18n->trans('balance') . '</option>'
                . '</select>'
                . '</div>'
                . '</div>';
    }

    public static function renderSubaccountList($codejercicio, $codempresa): string {
        $tbody = '';
       
        foreach (static::getSubaccounts($codejercicio, $codempresa) as $subaccount) {
            $cssClass = $subaccount->saldo > 0 ? 'table-success clickableRow' : 'clickableRow';
            $onclick = ' return widgetSubuentaSelect(\'' . '0' . '\' ,\'' . $subaccount->idsubcuenta . '\',\'' . $subaccount->codsubcuenta . '\');';

            $tbody .= '<tr class="' . $cssClass . '" onclick="' . $onclick . '">'
                    . '<td><b>' . $subaccount->codsubcuenta . '</b> ' . $subaccount->descripcion . '</td>'
                    . '<td class="text-right">' . Tools::money($subaccount->saldo) . '</td>'
                    . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="3">' . self::$i18n->trans('no-data') . '</td></tr>';
        }

        return '<table class="table table-hover mb-0">'
                . '<thead>'
                . '<tr>'
                . '<th>' . self::$i18n->trans('subaccount') . '</th>'
        . '<th class="text-right">' . self::$i18n->trans('balance') . '</th>'
                . '</tr>'
                . '</thead>'
                . '<tbody>' . $tbody . '</tbody>'
                . '</table>';
    }

    /**
     * @param Asiento $model
     *
     * @return Subcuenta[]
     */
    protected static function getSubaccounts(): array {
        $subaccount = new Subcuenta();
        $defaultExercise = static::defaultExercise();
        $codejercicio = $defaultExercise->codejercicio;
        
        $where = [new DataBaseWhere('codejercicio', $codejercicio)];
        if (self::$query) {
            $where[] = new DataBaseWhere('descripcion|codsubcuenta', self::$query, 'XLIKE');
        }

        switch (self::$orden) {
            case 'desc_asc':
                $order = ['descripcion' => 'ASC'];
                break;

            case 'saldo_desc':
                $order = ['saldo' => 'DESC'];
                break;

            default:
                $order = ['codsubcuenta' => 'ASC'];
                break;
        }

        return $subaccount->all($where, $order);
    }

    private function renderExerciseFilter(): string {
        $empresas = new Empresa();
        $ejercicios = new Ejercicio();
       
        $empresas = $empresas->all();
        $html = '<div class="col-sm col-sm-4 col-4 col-md-4">'
                . '<div class="form-group">'
                . '<select id="searchcodejercicio" name="searchcodejercicio" class="form-control" required="true" onchange="findSubaccountSearch(\'' . $this->id . '\')">';
        $defaultExercise = $this->defaultExercise();
        foreach ($empresas as $empresa) {
            $html .= '<optgroup label="' . $empresa->nombrecorto . '">';
            $ejercicios = $ejercicios->all([new DataBaseWhere('idempresa', $empresa->idempresa)]);
            foreach ($ejercicios as $ejercicio) {
                $html .= '<option value="' . $ejercicio->codejercicio . '"';
                if ($ejercicio->codejercicio === $defaultExercise->codejercicio){
                     $html .= ' selected=""';
                }    
                $html .= '>' . $ejercicio->nombre . '</option>';
            }
            $html .= '</optgroup>';
        }
        $html .= ' </select></div></div>';
        return $html;
    }

    private static function defaultExercise(): null|Ejercicio {
        $exerciseModel = new Ejercicio();
        foreach ($exerciseModel->all([], ['fechainicio' => 'DESC'], 0, 0) as $exe) {
            if ($exe->isOpened()) {
                return $exe;
            }
        }
        $exe = $exerciseModel->all([], [], 1, 0);
        return $exe[0];
    }

    /**
     * @param string $query
     * @param string $codfabricante
     * @param string $codfamilia
     * @param string $sort
     * @return Variante[]
     */
    public function subcuentas(string $query = '', string $codejercicio = '', string $sort = 'subcuenta-asc'): string {
        $list = [];

        // cargamos y añadimos la subcuenta seleccionada
        $model = new Subcuenta();
        if ($this->value && $model->loadFromCode($this->value)) {
            $list[] = $model;
            $where[] = new DataBaseWhere('subcuentas.codsubcuenta', $model->codsubcuenta, '<>');
        }

        $joinModel = new Subcuenta();
        if ($query) {
            $where[] = new DataBaseWhere('subcuentas.codsubcuenta|subcuentas.descripcion', $query, 'LIKE');
        }

        if ($codejercicio) {
            $where[] = new DataBaseWhere('codejercicio', $codejercicio);
        }



        switch ($sort) {
            case 'code_asc':
                $orderBy = ['codsubcuenta' => 'DESC'];
                break;
            case 'desc_asc':
                $orderBy = ['descripcion' => 'ASC'];
                break;
            case 'saldo_desc':
                $orderBy = ['descripcion' => 'DESC'];
                break;
            default:
                $orderBy = ['codsubcuenta' => 'ASC'];
                break;
        }
        
        $tbody = '';
        foreach ($joinModel->all($where, $orderBy, 0, 50) as $subaccount) {
             

            $cssClass = $subaccount->saldo > 0 ? 'table-success clickableRow' : 'clickableRow';
            $onclick = ' return widgetSubuentaSelect(\'' . $this->id . '\' ,\'' . $subaccount->idsubcuenta . '\',\'' . $subaccount->codsubcuenta . '\');';

            $tbody .= '<tr class="' . $cssClass . '" onclick="' . $onclick . '">'
                    . '<td><b>' . $subaccount->codsubcuenta . '</b> ' . $subaccount->descripcion . '</td>'
                    . '<td class="text-right">' . Tools::money($subaccount->saldo) . '</td>'
                    . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="3">' . self::$i18n->trans('no-data') . '</td></tr>';
        }

        return '<table class="table table-hover mb-0">'
                . '<thead>'
                . '<tr>'
                . '<th>' . self::$i18n->trans('subaccount') . '</th>'
        . '<th class="text-right">' . self::$i18n->trans('balance') . '</th>'
                . '</tr>'
                . '</thead>'
                . '<tbody>' . $tbody . '</tbody>'
                . '</table>';

    }
}
