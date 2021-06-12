<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DownloadTools;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\ReciboCliente;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\TotalModel;

/**
 * Description of Dashboard
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Dashboard extends Controller
{

    /**
     *
     * @var array
     */
    public $createLinks = [];

    /**
     *
     * @var array
     */
    public $lowStock = [];

    /**
     *
     * @var array
     */
    public $news = [];

    /**
     *
     * @var array
     */
    public $openLinks = [];

    /**
     *
     * @var array
     */
    public $receipts = [];

    /**
     *
     * @var array
     */
    public $sections = [];

    /**
     *
     * @var array
     */
    public $stats = [];

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'dashboard';
        $data['icon'] = 'fas fa-chalkboard-teacher';
        $data['menu'] = 'reports';
        return $data;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->title = $this->toolBox()->i18n()->trans('dashboard-for', ['%company%' => $this->empresa->nombrecorto]);
        $this->loadExtensions();
        $this->loadNews();
    }

    /**
     * Set the quick links for data creation.
     * Example: createLinks['EditControllerName'] = 'label'
     */
    private function loadCreateLinks()
    {
        $this->createLinks['EditProducto'] = 'product';
        $this->createLinks['EditCliente'] = 'customer';
        $this->createLinks['EditContacto'] = 'contact';
        $this->createLinks['EditFacturaCliente'] = 'customer-invoice';
        $this->createLinks['EditAlbaranCliente'] = 'customer-delivery-note';
        $this->createLinks['EditPedidoCliente'] = 'customer-order';
        $this->createLinks['EditPresupuestoCliente'] = 'customer-estimation';

        $this->pipe('loadCreateLinks');
    }

    /**
     * Establish the sections to be displayed on the dashboard.
     */
    private function loadExtensions()
    {
        $this->loadCreateLinks();
        $this->loadOpenLinks();
        $this->loadStats();
        $this->loadLowStockSection();
        $this->loadReceiptSection();

        $this->pipe('loadExtensions');
    }

    /**
     * Load the data regarding the stock under minimum.
     */
    private function loadLowStockSection()
    {
        $found = false;
        $sql = 'SELECT * FROM stocks WHERE stockmin > 0 AND disponible < stockmin;';
        foreach ($this->dataBase->select($sql) as $row) {
            $this->lowStock[] = new Stock($row);
            $found = true;
        }

        if ($found) {
            $this->sections[] = 'low-stock';
        }
    }

    /**
     * Loads the links to the latest data created by the user.
     */
    private function loadOpenLinks()
    {
        $this->setOpenLinksForDocument(new FacturaCliente(), 'invoice');
        $this->setOpenLinksForDocument(new AlbaranCliente(), 'delivery-note');
        $this->setOpenLinksForDocument(new PedidoCliente(), 'order');
        $this->setOpenLinksForDocument(new PresupuestoCliente(), 'estimation');

        $customerModel = new Cliente();
        foreach ($customerModel->all([], ['fechaalta' => 'DESC'], 0, 3) as $customer) {
            $this->openLinks[] = [
                'type' => 'customer',
                'url' => $customer->url(),
                'name' => $customer->nombre,
                'date' => $customer->fechaalta
            ];
        }

        $contactModel = new Contacto();
        foreach ($contactModel->all([], ['fechaalta' => 'DESC'], 0, 3) as $contact) {
            $this->openLinks[] = [
                'type' => 'contact',
                'url' => $contact->url(),
                'name' => $contact->fullName(),
                'date' => $contact->fechaalta
            ];
        }

        $productModel = new Producto();
        foreach ($productModel->all([], ['actualizado' => 'DESC'], 0, 3) as $product) {
            $this->openLinks[] = [
                'type' => 'product',
                'url' => $product->url(),
                'name' => $product->referencia,
                'date' => $product->fechaalta
            ];
        }

        $this->pipe('loadOpenLinks');
    }

    /**
     * Load the receipts pending collection.
     */
    private function loadReceiptSection()
    {
        $receiptModel = new ReciboCliente();
        $where = [
            new DataBaseWhere('pagado', false),
            new DataBaseWhere('vencimiento', $this->toolBox()->today(), '<'),
            new DataBaseWhere('vencimiento', \date('Y-m-d', \strtotime('-1 year')), '>')
        ];
        $this->receipts = $receiptModel->all($where, ['vencimiento' => 'DESC']);

        if (\count($this->receipts) > 0) {
            $this->sections[] = 'receipts';
        }
    }

    /**
     * Load statistical data.
     */
    private function loadStats()
    {
        $totalModel = new TotalModel();
        $this->stats['purchases'] = [
            'this-month' => $totalModel->sum('facturasprov', 'total', [new DataBaseWhere('fecha', \date('1-m-Y'), '>=')]),
            'last-month' => $totalModel->sum('facturasprov', 'total', [
                new DataBaseWhere('fecha', \date('1-m-Y'), '<'),
                new DataBaseWhere('fecha', \date('1-m-Y', \strtotime('-1 month')), '>=')
            ])
        ];

        $this->stats['sales'] = [
            'this-month' => $totalModel->sum('facturascli', 'total', [new DataBaseWhere('fecha', \date('1-m-Y'), '>=')]),
            'last-month' => $totalModel->sum('facturascli', 'total', [
                new DataBaseWhere('fecha', \date('1-m-Y'), '<'),
                new DataBaseWhere('fecha', \date('1-m-Y', \strtotime('-1 month')), '>=')
            ])
        ];

        $customerModel = new Cliente();
        $this->stats['new-customers'] = [
            'this-month' => $customerModel->count([new DataBaseWhere('fechaalta', \date('1-m-Y'), '>=')]),
            'last-month' => $customerModel->count([
                new DataBaseWhere('fechaalta', \date('1-m-Y'), '<'),
                new DataBaseWhere('fechaalta', \date('1-m-Y', \strtotime('-1 month')), '>=')
            ])
        ];
    }

    /**
     * Load last news from Facturascripts Api.
     */
    private function loadNews()
    {
        $data = DownloadTools::getContents('https://facturascripts.com/comm3/index.php?page=community_changelog&json=TRUE');
        if ($data === 'ERROR') {
            return;
        }

        $this->news = json_decode($data, true);
    }

    /**
     *
     * @param BusinessDocument $model
     * @param string $label
     */
    private function setOpenLinksForDocument($model, $label)
    {
        $where = [new DataBaseWhere('nick', $this->user->nick)];
        foreach ($model->all($where, [$model->primaryColumn() => 'DESC'], 0, 3) as $doc) {
            $this->openLinks[] = [
                'type' => $label,
                'url' => $doc->url(),
                'name' => $doc->codigo,
                'date' => $doc->fecha
            ];
        }
    }
}
