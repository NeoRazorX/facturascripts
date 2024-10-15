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

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Model\AgenciaTransporte;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Provincia;

/**
 * Description of SalesHeaderHTML
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 *
 * @deprecated replaced by Core/Lib/AjaxForms/SalesHeaderHTML
 */
class SalesHeaderHTML
{
    use CommonSalesPurchases;

    /** @var Cliente */
    private static $cliente;

    /** @var SalesModInterface[] */
    private static $mods = [];

    public static function addMod(SalesModInterface $mod)
    {
        self::$mods[] = $mod;
    }

    public static function apply(SalesDocument &$model, array $formData, User $user)
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->applyBefore($model, $formData, $user);
        }

        $cliente = new Cliente();
        if (empty($model->primaryColumnValue())) {
            // new record. Sets user and customer
            $model->setAuthor($user);
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

    public static function render(SalesDocument $model): string
    {
        $i18n = new Translator();
        return '<div class="container-fluid">'
            . '<div class="form-row align-items-end">'
            . self::renderField($i18n, $model, 'codcliente')
            . self::renderField($i18n, $model, 'codalmacen')
            . self::renderField($i18n, $model, 'codserie')
            . self::renderField($i18n, $model, 'fecha')
            . self::renderNewFields($i18n, $model)
            . self::renderField($i18n, $model, 'numero2')
            . self::renderField($i18n, $model, 'codpago')
            . self::renderField($i18n, $model, 'finoferta')
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

    private static function addressField(Translator $i18n, SalesDocument $model, string $field, string $label, int $size, int $maxlength): string
    {
        $attributes = $model->editable && (empty($model->idcontactofact) || empty($model->direccion)) ?
            'name="' . $field . '" maxlength="' . $maxlength . '" autocomplete="off"' :
            'disabled=""';

        return '<div class="col-sm-' . $size . '">'
            . '<div class="form-group">' . $i18n->trans($label)
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->{$field}) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function ciudad(Translator $i18n, SalesDocument $model, int $size, int $maxlength): string
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
            . '<div class="form-group">' . $i18n->trans('city')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->ciudad) . '" ' . $list . ' class="form-control"/>'
            . $dataList
            . '</div>'
            . '</div>';
    }

    private static function codagente(Translator $i18n, SalesDocument $model): string
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
            . '<div class="form-group">'
            . '<a href="' . Agentes::get($model->codagente)->url() . '">' . $i18n->trans('agent') . '</a>'
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function codcliente(Translator $i18n, SalesDocument $model): string
    {
        self::$cliente = new Cliente();
        if (empty($model->codcliente) || false === self::$cliente->loadFromCode($model->codcliente)) {
            return '<div class="col-sm-3">'
                . '<div class="form-group">' . $i18n->trans('customer')
                . '<input type="hidden" name="codcliente"/>'
                . '<a href="#" id="btnFindCustomerModal" class="btn btn-block btn-primary" onclick="$(\'#findCustomerModal\').modal();'
                . ' $(\'#findCustomerInput\').focus(); return false;"><i class="fas fa-users fa-fw"></i> '
                . $i18n->trans('select') . '</a>'
                . '</div>'
                . '</div>'
                . self::detailModal($i18n, $model);
        }

        $btnCliente = $model->editable ?
            '<button class="btn btn-outline-secondary" type="button" onclick="$(\'#findCustomerModal\').modal();'
            . ' $(\'#findCustomerInput\').focus(); return false;"><i class="fas fa-pen"></i></button>' :
            '<button class="btn btn-outline-secondary" type="button"><i class="fas fa-lock"></i></button>';

        $html = '<div class="col-sm-3 col-lg">'
            . '<div class="form-group">'
            . '<a href="' . self::$cliente->url() . '">' . $i18n->trans('customer') . '</a>'
            . '<input type="hidden" name="codcliente" value="' . $model->codcliente . '"/>'
            . '<div class="input-group">'
            . '<input type="text" value="' . Tools::noHtml(self::$cliente->nombre) . '" class="form-control" readonly/>'
            . '<div class="input-group-append">' . $btnCliente . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';

        if (empty($model->primaryColumnValue())) {
            $html .= self::detail($i18n, $model, true);
        }

        return $html;
    }

    private static function codigoenv(Translator $i18n, SalesDocument $model): string
    {
        $attributes = $model->editable ? 'name="codigoenv" maxlength="200" autocomplete="off"' : 'disabled=""';
        return '<div class="col-sm-4">'
            . '<div class="form-group">' . $i18n->trans('tracking-code')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->codigoenv) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function codpais(Translator $i18n, SalesDocument $model): string
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
            . '<div class="form-group">'
            . '<a href="' . $pais->url() . '">' . $i18n->trans('country') . '</a>'
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function codtrans(Translator $i18n, SalesDocument $model): string
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
            . '<div class="form-group">'
            . '<a href="' . $agenciaTransporte->url() . '">' . $i18n->trans('carrier') . '</a>'
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function detail(Translator $i18n, SalesDocument $model, bool $new = false): string
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

    private static function detailModal(Translator $i18n, SalesDocument $model): string
    {
        return '<div class="modal fade" id="headerModal" tabindex="-1" aria-labelledby="headerModalLabel" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered modal-lg">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fas fa-edit fa-fw" aria-hidden="true"></i> ' . $i18n->trans('detail') . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, 'nombrecliente')
            . self::renderField($i18n, $model, 'cifnif')
            . self::renderField($i18n, $model, 'idcontactofact')
            . self::renderField($i18n, $model, 'direccion')
            . self::renderField($i18n, $model, 'apartado')
            . self::renderField($i18n, $model, 'codpostal')
            . self::renderField($i18n, $model, 'ciudad')
            . self::renderField($i18n, $model, 'provincia')
            . self::renderField($i18n, $model, 'codpais')
            . self::renderField($i18n, $model, 'idcontactoenv')
            . self::renderField($i18n, $model, 'codtrans')
            . self::renderField($i18n, $model, 'codigoenv')
            . self::renderField($i18n, $model, 'fechadevengo')
            . self::renderField($i18n, $model, 'hora')
            . self::renderField($i18n, $model, 'operacion')
            . self::renderField($i18n, $model, 'femail')
            . self::renderField($i18n, $model, 'coddivisa')
            . self::renderField($i18n, $model, 'tasaconv')
            . self::renderField($i18n, $model, 'user')
            . self::renderField($i18n, $model, 'codagente')
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

    private static function finoferta(Translator $i18n, SalesDocument $model): string
    {
        if (false === property_exists($model, 'finoferta') || empty($model->primaryColumnValue())) {
            return '';
        }

        $label = empty($model->finoferta) || strtotime($model->finoferta) > time() ?
            $i18n->trans('expiration') :
            '<span class="text-danger">' . $i18n->trans('expiration') . '</span>';

        $attributes = $model->editable ? 'name="finoferta"' : 'disabled=""';
        $value = empty($model->finoferta) ? '' : 'value="' . date('Y-m-d', strtotime($model->finoferta)) . '"';
        return '<div class="col-sm">'
            . '<div class="form-group">' . $label
            . '<input type="date" ' . $attributes . ' ' . $value . ' class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param mixed $selected
     * @param bool $empty
     *
     * @return array
     */
    private static function getAddressOptions(Translator $i18n, $selected, bool $empty): array
    {
        $options = $empty ? ['<option value="">------</option>'] : [];
        foreach (self::$cliente->getAddresses() as $contact) {
            $descripcion = empty($contact->descripcion) ? '(' . $i18n->trans('empty') . ') ' : '(' . $contact->descripcion . ') ';
            $descripcion .= empty($contact->direccion) ? '' : $contact->direccion;
            $options[] = $contact->idcontacto == $selected ?
                '<option value="' . $contact->idcontacto . '" selected>' . $descripcion . '</option>' :
                '<option value="' . $contact->idcontacto . '">' . $descripcion . '</option>';
        }
        return $options;
    }

    private static function idcontactoenv(Translator $i18n, SalesDocument $model): string
    {
        if (empty($model->codcliente)) {
            return '';
        }

        $attributes = $model->editable ? 'name="idcontactoenv"' : 'disabled=""';
        $options = self::getAddressOptions($i18n, $model->idcontactoenv, true);
        return '<div class="col-sm-4">'
            . '<div class="form-group">'
            . '<a href="' . self::$cliente->url() . '&activetab=EditDireccionContacto" target="_blank">'
            . $i18n->trans('shipping-address') . '</a>'
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function idcontactofact(Translator $i18n, SalesDocument $model): string
    {
        if (empty($model->codcliente)) {
            return '';
        }

        $attributes = $model->editable ? 'name="idcontactofact" onchange="return salesFormActionWait(\'recalculate-line\', \'0\', event);"' : 'disabled=""';
        $options = self::getAddressOptions($i18n, $model->idcontactofact, true);
        return '<div class="col-sm-6">'
            . '<div class="form-group">'
            . '<a href="' . self::$cliente->url() . '&activetab=EditDireccionContacto" target="_blank">' . $i18n->trans('billing-address') . '</a>'
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function nombrecliente(Translator $i18n, SalesDocument $model): string
    {
        $attributes = $model->editable ? 'name="nombrecliente" required="" maxlength="100" autocomplete="off"' : 'disabled=""';
        return '<div class="col-sm-6">'
            . '<div class="form-group">'
            . $i18n->trans('business-name')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->nombrecliente) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function numero2(Translator $i18n, SalesDocument $model): string
    {
        $attributes = $model->editable ? 'name="numero2" maxlength="50" placeholder="' . $i18n->trans('optional') . '"' : 'disabled=""';
        return empty($model->codcliente) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('number2')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->numero2) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function provincia(Translator $i18n, SalesDocument $model, int $size, int $maxlength): string
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
            . '<div class="form-group">' . $i18n->trans('province')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($model->provincia) . '" ' . $list . ' class="form-control"/>'
            . $dataList
            . '</div>'
            . '</div>';
    }

    private static function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
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
                return self::paid($i18n, $model, 'salesFormSave');

            case '_parents':
                return self::parents($i18n, $model);

            case 'apartado':
                return self::addressField($i18n, $model, 'apartado', 'post-office-box', 4, 10);

            case 'cifnif':
                return self::cifnif($i18n, $model);

            case 'ciudad':
                return self::ciudad($i18n, $model, 4, 100);

            case 'codagente':
                return self::codagente($i18n, $model);

            case 'codalmacen':
                return self::codalmacen($i18n, $model, 'salesFormAction');

            case 'codcliente':
                return self::codcliente($i18n, $model);

            case 'coddivisa':
                return self::coddivisa($i18n, $model);

            case 'codigoenv':
                return self::codigoenv($i18n, $model);

            case 'codpago':
                return self::codpago($i18n, $model);

            case 'codpais':
                return self::codpais($i18n, $model);

            case 'codpostal':
                return self::addressField($i18n, $model, 'codpostal', 'zip-code', 4, 10);

            case 'codserie':
                return self::codserie($i18n, $model, 'salesFormAction');

            case 'codtrans':
                return self::codtrans($i18n, $model);

            case 'direccion':
                return self::addressField($i18n, $model, 'direccion', 'address', 6, 100);

            case 'fecha':
                return self::fecha($i18n, $model);

            case 'fechadevengo':
                return self::fechadevengo($i18n, $model);

            case 'femail':
                return self::femail($i18n, $model);

            case 'finoferta':
                return self::finoferta($i18n, $model);

            case 'hora':
                return self::hora($i18n, $model);

            case 'idcontactofact':
                return self::idcontactofact($i18n, $model);

            case 'idcontactoenv':
                return self::idcontactoenv($i18n, $model);

            case 'idestado':
                return self::idestado($i18n, $model, 'salesFormSave');

            case 'nombrecliente':
                return self::nombrecliente($i18n, $model);

            case 'numero2':
                return self::numero2($i18n, $model);

            case 'operacion':
                return self::operacion($i18n, $model);

            case 'provincia':
                return self::provincia($i18n, $model, 6, 100);

            case 'tasaconv':
                return self::tasaconv($i18n, $model);

            case 'total':
                return self::total($i18n, $model, 'salesFormSave');

            case 'user':
                return self::user($i18n, $model);
        }

        return null;
    }

    private static function renderNewBtnFields(Translator $i18n, SalesDocument $model): string
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

    private static function renderNewFields(Translator $i18n, SalesDocument $model): string
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

    private static function renderNewModalFields(Translator $i18n, SalesDocument $model): string
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
