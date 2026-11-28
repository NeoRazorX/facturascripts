<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Lib\AlbaranFacturador;

/**
 * Megafacturador: lista los clientes con albaranes pendientes de facturar y
 * permite generar, para cada uno, una única factura con todos esos albaranes.
 * La lógica de datos vive en Lib\AlbaranFacturador; este controlador solo
 * recoge la request, comprueba permisos/token y conecta con esa clase.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
class MegaFacturador extends Controller
{
    /** @var array Grupos de albaranes pendientes de facturar (uno o más por cliente). */
    public $groups = [];

    /** @var FacturaCliente|null Última factura generada, para enlazarla en la vista. */
    public $lastInvoice;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'mega-invoicer';
        $data['icon'] = 'fa-solid fa-file-invoice-dollar';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Indica si hay que mostrar la columna de empresa (solo si hay albaranes
     * pendientes de más de una empresa).
     *
     * @return bool
     */
    public function hasSeveralCompanies(): bool
    {
        return count(array_unique(array_column($this->groups, 'idempresa'))) > 1;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->input('action', '');
        if ($action === 'invoice') {
            $this->invoiceAction();
        }

        // cargamos los pendientes después de facturar, para que el cliente
        // facturado desaparezca del listado (o se actualice)
        $facturador = new AlbaranFacturador();
        $this->groups = $facturador->getPendingGroups($this->getOwner());
    }

    /**
     * Devuelve la restricción de propietario cuando el usuario solo puede ver
     * sus propios datos, o un array vacío si puede verlo todo.
     *
     * @return array
     */
    protected function getOwner(): array
    {
        if (false === $this->permissions->onlyOwnerData) {
            return [];
        }

        return ['nick' => $this->user->nick, 'codagente' => $this->user->codagente];
    }

    /**
     * Genera la factura del grupo de albaranes seleccionado con la fecha elegida.
     */
    protected function invoiceAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $group = [];
        foreach (AlbaranFacturador::GROUP_FIELDS as $field) {
            $group[$field] = $this->request->input($field, '');
        }

        $facturador = new AlbaranFacturador();
        $this->lastInvoice = $facturador->invoice($group, $this->request->input('fecha', ''), $this->getOwner());
        if ($this->lastInvoice) {
            Tools::log()->notice('mega-invoice-created', ['%code%' => $this->lastInvoice->codigo]);
        }
    }
}
