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

use FacturaScripts\Core\Contract\SalesModInterface;
use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Model\AgenciaTransporte;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Provincia;

/**
 * Description of SalesHeaderHTML
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class SalesHeaderHTML
{
    use CommonSalesPurchases;

    /** @var Cliente */
    private static $cliente;

    /** @var SalesModInterface[] */
    private static $mods = [];

    public static function addMod(SalesModInterface $mod): void
    {
        self::$mods[] = $mod;
    }

    public static function apply(SalesDocument &$model, array $formData): void
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->applyBefore($model, $formData);
        }

        $cliente = new Cliente();
        if (empty($model->primaryColumnValue())) {
            // new record. Sets user and customer
            $model->setAuthor(Session::user());
            if (isset($formData['codcliente']) && $formData['codcliente'] && $cliente->loadFromCode($formData['codcliente'])) {
                $model->setSubject($cliente);
                if (empty($formData['action']) || $formData['action'] === 'set-customer') {
                    return;
                }
            }

            $contacto = new Contacto();
            if (isset($formData['idcontactofact']) && $contacto->loadFromCode($formData['idcontactofact'])) {
                $model->setSubject($contacto);
                if (empty($formData['action'])) {
                    return;
                }
            }
        } elseif (isset($formData['action'], $formData['codcliente']) &&
            $formData['action'] === 'set-customer' &&
            $cliente->loadFromCode($formData['codcliente'])) {
            // existing record and change customer
            $model->setSubject($cliente);
            return;
        }

        $model->setWarehouse($formData['codalmacen'] ?? $model->codalmacen);
        $model->cifnif = $formData['cifnif'] ?? $model->cifnif;
        $model->codcliente = $formData['codcliente'] ?? $model->codcliente;
        $model->codigoenv = $formData['codigoenv'] ?? $model->codigoenv;
        $model->coddivisa = $formData['coddivisa'] ?? $model->coddivisa;
        $model->codpago = $formData['codpago'] ?? $model->codpago;
        $model->codserie = $formData['codserie'] ?? $model->codserie;
        $model->fecha = empty($formData['fecha']) ? $model->fecha : Tools::date($formData['fecha']);
        $model->femail = isset($formData['femail']) && !empty($formData['femail']) ? $formData['femail'] : $model->femail;
        $model->hora = $formData['hora'] ?? $model->hora;
        $model->nombrecliente = $formData['nombrecliente'] ?? $model->nombrecliente;
        $model->numero2 = $formData['numero2'] ?? $model->numero2;
        $model->operacion = $formData['operacion'] ?? $model->operacion;
        $model->tasaconv = (float)($formData['tasaconv'] ?? $model->tasaconv);

        foreach (['codagente', 'codtrans', 'fechadevengo', 'finoferta'] as $key) {
            if (isset($formData[$key])) {
                $model->{$key} = empty($formData[$key]) ? null : $formData[$key];
            }
        }

        if (false === isset($formData['idcontactofact'], $formData['idcontactoenv'])) {
            return;
        }

        // set billing address
        $dir = new Contacto();
        if (empty($formData['idcontactofact'])) {
            $model->idcontactofact = null;
            $model->direccion = $formData['direccion'] ?? $model->direccion;
            $model->apartado = $formData['apartado'] ?? $model->apartado;
            $model->codpostal = $formData['codpostal'] ?? $model->codpostal;
            $model->ciudad = $formData['ciudad'] ?? $model->ciudad;
            $model->provincia = $formData['provincia'] ?? $model->provincia;
            $model->codpais = $formData['codpais'] ?? $model->codpais;
        } elseif ($dir->loadFromCode($formData['idcontactofact'])) {
            // update billing address
            $model->idcontactofact = $dir->idcontacto;

            // Is Billing address empty?
            if (empty($dir->direccion)) {
                $model->direccion = $formData['direccion'] ?? $model->direccion;
                $model->apartado = $formData['apartado'] ?? $model->apartado;
                $model->codpostal = $formData['codpostal'] ?? $model->codpostal;
                $model->ciudad = $formData['ciudad'] ?? $model->ciudad;
                $model->provincia = $formData['provincia'] ?? $model->provincia;
                $model->codpais = $formData['codpais'] ?? $model->codpais;
            } else {
                $model->direccion = $dir->direccion;
                $model->apartado = $dir->apartado;
                $model->codpostal = $dir->codpostal;
                $model->ciudad = $dir->ciudad;
                $model->provincia = $dir->provincia;
                $model->codpais = $dir->codpais;
            }
        }

        // set shipping address
        $model->idcontactoenv = empty($formData['idcontactoenv']) ? null : $formData['idcontactoenv'];

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

    public static function render(SalesDocument $model): string
    {
        return '<div class="container-fluid">'
            . '<div class="row g-3 align-items-end">'
            . self::renderField($model, 'codcliente')
            . self::renderField($model, 'codalmacen')
            . self::renderField($model, 'codserie')
            . self::renderField($model, 'fecha')
            . self::renderNewFields($model)
            . self::renderField($model, 'numero2')
            . self::renderField($model, 'codpago')
            . self::renderField($model, 'finoferta')
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

    private static function addressField(SalesDocument $model, string $field, string $label, int $size, int $maxlength): string
    {
        $attributes = $model->editable && (empty($model->idcontactofact) || empty($model->direccion)) ?
            'name="' . $field . '" maxlength="' . $maxlength . '" autocomplete="off"' :
            'disabled=""';

        return '<div class="col-sm-' . $size . '">'
            . '<div class="mb-3">' . Tools::lang()->trans($label)
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->{$field}) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function ciudad(SalesDocument $model, int $size, int $maxlength): string
    {
        $list = '';
        $dataList = '';
        $attributes = $model->editable && (empty($model->idcontactofact) || empty($model->direccion)) ?
            'name="ciudad" maxlength="' . $maxlength . '" autocomplete="off"' :
            'disabled=""';

        if ($model->editable) {
            // pre-cargamos listado de ciudades
            $list = 'list="ciudades"';
            $dataList = '<datalist id="ciudades">';

            $ciudadModel = new Ciudad();
            foreach ($ciudadModel->all([], ['ciudad' => 'ASC'], 0, 0) as $ciudad) {
                $dataList .= '<option value="' . $ciudad->ciudad . '">' . $ciudad->ciudad . '</option>';
            }
            $dataList .= '</datalist>';
        }

        return '<div class="col-sm-' . $size . '">'
            . '<div class="mb-3">' . Tools::lang()->trans('city')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->ciudad) . '" ' . $list . ' class="form-control"/>'
            . $dataList
            . '</div>'
            . '</div>';
    }

    private static function codagente(SalesDocument $model): string
    {
        $agentes = Agentes::all();
        if (count($agentes) === 0) {
            return '';
        }

        $options = ['<option value="">------</option>'];
        foreach ($agentes as $row) {
            // si el agente no está activo o seleccionado, lo ignoramos
            if ($row->debaja && $row->codagente != $model->codagente) {
                continue;
            }

            $options[] = ($row->codagente === $model->codagente) ?
                '<option value="' . $row->codagente . '" selected>' . $row->nombre . '</option>' :
                '<option value="' . $row->codagente . '">' . $row->nombre . '</option>';
        }

        $attributes = $model->editable ? 'name="codagente"' : 'disabled';
        return empty($model->subjectColumnValue()) ? '' : '<div class="col-sm-6">'
            . '<div class="mb-3">'
            . '<a href="' . Agentes::get($model->codagente)->url() . '">' . Tools::lang()->trans('agent') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function codcliente(SalesDocument $model): string
    {
        self::$cliente = new Cliente();
        if (empty($model->codcliente) || false === self::$cliente->loadFromCode($model->codcliente)) {
            return '<div class="col-sm-3">'
                . '<div class="mb-3">' . Tools::lang()->trans('customer')
                . '<input type="hidden" name="codcliente"/>'
                . '<a href="#" id="btnFindCustomerModal" class="btn btn-block btn-primary" onclick="$(\'#findCustomerModal\').modal(\'show\');'
                . ' $(\'#findCustomerInput\').focus(); return false;"><i class="fa-solid fa-users fa-fw"></i> '
                . Tools::lang()->trans('select') . '</a>'
                . '</div>'
                . '</div>'
                . self::detailModal($model);
        }

        $btnCliente = $model->editable ?
            '<button class="btn btn-outline-secondary" type="button" onclick="$(\'#findCustomerModal\').modal(\'show\');'
            . ' $(\'#findCustomerInput\').focus(); return false;"><i class="fa-solid fa-pen"></i></button>' :
            '<button class="btn btn-outline-secondary" type="button"><i class="fa-solid fa-lock"></i></button>';

        $html = '<div class="col-sm-3 col-lg">'
            . '<div class="mb-3">'
            . '<a href="' . self::$cliente->url() . '">' . Tools::lang()->trans('customer') . '</a>'
            . '<input type="hidden" name="codcliente" value="' . $model->codcliente . '"/>'
            . '<div class="input-group">'
            . '<input type="text" value="' . Tools::noHtml(self::$cliente->nombre) . '" class="form-control" readonly/>'
            . '' . $btnCliente . ''
            . '</div>'
            . '</div>'
            . '</div>';

        if (empty($model->primaryColumnValue())) {
            $html .= self::detail($model, true);
        }

        return $html;
    }

    private static function codigoenv(SalesDocument $model): string
    {
        $attributes = $model->editable ? 'name="codigoenv" maxlength="200" autocomplete="off"' : 'disabled=""';
        return '<div class="col-sm-4">'
            . '<div class="mb-3">' . Tools::lang()->trans('tracking-code')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->codigoenv) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function codpais(SalesDocument $model): string
    {
        $options = [];
        foreach (Paises::all() as $pais) {
            $options[] = ($pais->codpais === $model->codpais) ?
                '<option value="' . $pais->codpais . '" selected>' . $pais->nombre . '</option>' :
                '<option value="' . $pais->codpais . '">' . $pais->nombre . '</option>';
        }

        $pais = new Pais();
        $attributes = $model->editable && (empty($model->idcontactofact) || empty($model->direccion)) ?
            'name="codpais"' :
            'disabled=""';
        return '<div class="col-sm-6">'
            . '<div class="mb-3">'
            . '<a href="' . $pais->url() . '">' . Tools::lang()->trans('country') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function codtrans(SalesDocument $model): string
    {
        $options = ['<option value="">------</option>'];
        $agenciaTransporte = new AgenciaTransporte();
        foreach ($agenciaTransporte->all() as $agencia) {
            $options[] = ($agencia->codtrans === $model->codtrans) ?
                '<option value="' . $agencia->codtrans . '" selected>' . $agencia->nombre . '</option>' :
                '<option value="' . $agencia->codtrans . '">' . $agencia->nombre . '</option>';
        }

        $attributes = $model->editable ? 'name="codtrans"' : 'disabled=""';
        return '<div class="col-sm-4">'
            . '<div class="mb-3">'
            . '<a href="' . $agenciaTransporte->url() . '">' . Tools::lang()->trans('carrier') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function detail(SalesDocument $model, bool $new = false): string
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

    private static function detailModal(SalesDocument $model): string
    {
        return '<div class="modal fade" id="headerModal" tabindex="-1" aria-labelledby="headerModalLabel" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered modal-lg">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fa-solid fa-edit fa-fw" aria-hidden="true"></i> ' . Tools::lang()->trans('detail') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-3">'
            . self::renderField($model, 'nombrecliente')
            . self::renderField($model, 'cifnif')
            . self::renderField($model, 'idcontactofact')
            . self::renderField($model, 'direccion')
            . self::renderField($model, 'apartado')
            . self::renderField($model, 'codpostal')
            . self::renderField($model, 'ciudad')
            . self::renderField($model, 'provincia')
            . self::renderField($model, 'codpais')
            . self::renderField($model, 'idcontactoenv')
            . self::renderField($model, 'codtrans')
            . self::renderField($model, 'codigoenv')
            . self::renderField($model, 'fechadevengo')
            . self::renderField($model, 'hora')
            . self::renderField($model, 'operacion')
            . self::renderField($model, 'femail')
            . self::renderField($model, 'coddivisa')
            . self::renderField($model, 'tasaconv')
            . self::renderField($model, 'user')
            . self::renderField($model, 'codagente')
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

    private static function finoferta(SalesDocument $model): string
    {
        if (false === property_exists($model, 'finoferta') || empty($model->primaryColumnValue())) {
            return '';
        }

        $label = empty($model->finoferta) || strtotime($model->finoferta) > time() ?
            Tools::lang()->trans('expiration') :
            '<span class="text-danger">' . Tools::lang()->trans('expiration') . '</span>';

        $attributes = $model->editable ? 'name="finoferta"' : 'disabled=""';
        $value = empty($model->finoferta) ? '' : 'value="' . date('Y-m-d', strtotime($model->finoferta)) . '"';
        return '<div class="col-sm">'
            . '<div class="mb-3">' . $label
            . '<input type="date" ' . $attributes . ' ' . $value . ' class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function getAddressOptions($selected, bool $empty): array
    {
        $options = $empty ? ['<option value="">------</option>'] : [];
        foreach (self::$cliente->getAddresses() as $contact) {
            $descripcion = empty($contact->descripcion) ? '(' . Tools::lang()->trans('empty') . ') ' : '(' . $contact->descripcion . ') ';
            $descripcion .= empty($contact->direccion) ? '' : $contact->direccion;
            $options[] = $contact->idcontacto == $selected ?
                '<option value="' . $contact->idcontacto . '" selected>' . $descripcion . '</option>' :
                '<option value="' . $contact->idcontacto . '">' . $descripcion . '</option>';
        }
        return $options;
    }

    private static function idcontactoenv(SalesDocument $model): string
    {
        if (empty($model->codcliente)) {
            return '';
        }

        $attributes = $model->editable ? 'name="idcontactoenv"' : 'disabled=""';
        $options = self::getAddressOptions($model->idcontactoenv, true);
        return '<div class="col-sm-4">'
            . '<div class="mb-3">'
            . '<a href="' . self::$cliente->url() . '&activetab=EditDireccionContacto" target="_blank">'
            . Tools::lang()->trans('shipping-address') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function idcontactofact(SalesDocument $model): string
    {
        if (empty($model->codcliente)) {
            return '';
        }

        $attributes = $model->editable ? 'name="idcontactofact" onchange="return salesFormActionWait(\'recalculate-line\', \'0\', event);"' : 'disabled=""';
        $options = self::getAddressOptions($model->idcontactofact, true);
        return '<div class="col-sm-6">'
            . '<div class="mb-3">'
            . '<a href="' . self::$cliente->url() . '&activetab=EditDireccionContacto" target="_blank">' . Tools::lang()->trans('billing-address') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function nombrecliente(SalesDocument $model): string
    {
        $attributes = $model->editable ? 'name="nombrecliente" required="" maxlength="100" autocomplete="off"' : 'disabled=""';
        return '<div class="col-sm-6">'
            . '<div class="mb-3">'
            . Tools::lang()->trans('business-name')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->nombrecliente) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function numero2(SalesDocument $model): string
    {
        $attributes = $model->editable ? 'name="numero2" maxlength="50" placeholder="' . Tools::lang()->trans('optional') . '"' : 'disabled=""';
        return empty($model->codcliente) ? '' : '<div class="col-sm">'
            . '<div class="mb-3">'
            . Tools::lang()->trans('number2')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->numero2) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function provincia(SalesDocument $model, int $size, int $maxlength): string
    {
        $list = '';
        $dataList = '';
        $attributes = $model->editable && (empty($model->idcontactofact) || empty($model->direccion)) ?
            'name="provincia" maxlength="' . $maxlength . '" autocomplete="off"' :
            'disabled=""';

        if ($model->editable) {
            // pre-cargamos listado de provincias
            $list = 'list="provincias"';
            $dataList = '<datalist id="provincias">';

            $provinciaModel = new Provincia();
            foreach ($provinciaModel->all([], ['provincia' => 'ASC'], 0, 0) as $provincia) {
                $dataList .= '<option value="' . $provincia->provincia . '">' . $provincia->provincia . '</option>';
            }
            $dataList .= '</datalist>';
        }

        return '<div class="col-sm-' . $size . '">'
            . '<div class="mb-3">' . Tools::lang()->trans('province')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->provincia) . '" ' . $list . ' class="form-control"/>'
            . $dataList
            . '</div>'
            . '</div>';
    }

    private static function renderField(SalesDocument $model, string $field): ?string
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
                return self::paid($model, 'salesFormSave');

            case '_parents':
                return self::parents($model);

            case 'apartado':
                return self::addressField($model, 'apartado', 'post-office-box', 4, 10);

            case 'cifnif':
                return self::cifnif($model);

            case 'ciudad':
                return self::ciudad($model, 4, 100);

            case 'codagente':
                return self::codagente($model);

            case 'codalmacen':
                return self::codalmacen($model, 'salesFormAction');

            case 'codcliente':
                return self::codcliente($model);

            case 'coddivisa':
                return self::coddivisa($model);

            case 'codigoenv':
                return self::codigoenv($model);

            case 'codpago':
                return self::codpago($model);

            case 'codpais':
                return self::codpais($model);

            case 'codpostal':
                return self::addressField($model, 'codpostal', 'zip-code', 4, 10);

            case 'codserie':
                return self::codserie($model, 'salesFormAction');

            case 'codtrans':
                return self::codtrans($model);

            case 'direccion':
                return self::addressField($model, 'direccion', 'address', 6, 100);

            case 'fecha':
                return self::fecha($model);

            case 'fechadevengo':
                return self::fechadevengo($model);

            case 'femail':
                return self::femail($model);

            case 'finoferta':
                return self::finoferta($model);

            case 'hora':
                return self::hora($model);

            case 'idcontactofact':
                return self::idcontactofact($model);

            case 'idcontactoenv':
                return self::idcontactoenv($model);

            case 'idestado':
                return self::idestado($model, 'salesFormSave');

            case 'nombrecliente':
                return self::nombrecliente($model);

            case 'numero2':
                return self::numero2($model);

            case 'operacion':
                return self::operacion($model);

            case 'provincia':
                return self::provincia($model, 6, 100);

            case 'tasaconv':
                return self::tasaconv($model);

            case 'total':
                return self::total($model, 'salesFormSave');

            case 'user':
                return self::user($model);
        }

        return null;
    }

    private static function renderNewBtnFields(SalesDocument $model): string
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

    private static function renderNewFields(SalesDocument $model): string
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

    private static function renderNewModalFields(SalesDocument $model): string
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
