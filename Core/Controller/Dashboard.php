<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Http;
use FacturaScripts\Core\Internal\Forja;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Telemetry;
use FacturaScripts\Core\Tools;
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
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of Dashboard
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Dashboard extends Controller
{
    /** @var array */
    public $createLinks = [];

    /** @var array */
    public $lowStock = [];

    /** @var array */
    public $news = [];

    /** @var array */
    public $openLinks = [];

    /** @var array */
    public $receipts = [];

    /** @var bool */
    public $registered = false;

    /** @var array */
    public $sections = [];

    /** @var array */
    public $stats = [];

    /** @var bool */
    public $updated = false;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'dashboard';
        $data['icon'] = 'fa-solid fa-chalkboard-teacher';
        return $data;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        $this->title = Tools::lang()->trans('dashboard-for', ['%company%' => $this->empresa->nombrecorto]);

        $this->loadExtensions();

        // comprobamos si la instalación está registrada
        $telemetry = new Telemetry();
        $this->registered = $telemetry->ready();

        // comprobamos si hay actualizaciones disponibles
        $this->updated = Forja::canUpdateCore() === false;
    }

    public function showBackupWarning(): bool
    {
        // comprobamos si estamos el localhost
        if ($_SERVER['REMOTE_ADDR'] == 'localhost' ||
            $_SERVER['REMOTE_ADDR'] == '::1' ||
            substr($_SERVER['REMOTE_ADDR'], 0, 4) == '192.' ||
            substr($_SERVER['REMOTE_ADDR'], 0, 4) == '172.') {
            // si el plugin Backup está activo, devolvemos false
            return !Plugins::isEnabled('Backup');
        }

        return false;
    }

    /**
     * Gets the name of the month for the statistics.
     *
     * @param int $previous
     *
     * @return string
     */
    private function getStatsMonth(int $previous): string
    {
        $firstDate = date('01-m-Y');
        $date = $previous > 0 ? date('01-m-Y', strtotime($firstDate . ' -' . $previous . ' month')) : $firstDate;
        return strtolower(date('F', strtotime($date)));
    }

    /**
     * Gets the where filter for calc of the statistics.
     *
     * @param string $field
     * @param int $previous
     *
     * @return DataBaseWhere[]
     */
    private function getStatsWhere(string $field, int $previous): array
    {
        $firstDate = date('01-m-Y');
        $fromDate = $previous > 0 ? date('01-m-Y', strtotime($firstDate . ' -' . $previous . ' month')) : $firstDate;
        $untilDate = date('01-m-Y', strtotime($fromDate . ' +1 month'));

        return [
            new DataBaseWhere($field, $fromDate, '>='),
            new DataBaseWhere($field, $untilDate, '<'),
        ];
    }

    /**
     * Set the quick links for data creation.
     * Example: createLinks['EditControllerName'] = 'label'
     */
    private function loadCreateLinks(): void
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
    private function loadExtensions(): void
    {
        $this->loadCreateLinks();
        $this->loadOpenLinks();
        $this->loadStats();
        $this->loadLowStockSection();
        $this->loadReceiptSection();
        $this->loadNews();

        $this->pipe('loadExtensions');
    }

    /**
     * Load the data regarding the stock under minimum.
     */
    private function loadLowStockSection(): void
    {
        if (false === $this->dataBase->tableExists('stocks')) {
            return;
        }

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
     * Load last news from facturascripts.com
     */
    private function loadNews(): void
    {
        $this->news = Cache::remember('dashboard-news', function () {
            return Http::get('https://facturascripts.com/comm3/index.php?page=community_changelog&json=TRUE')
                ->setTimeout(5)
                ->json();
        });
    }

    /**
     * Loads the links to the latest data created by the user.
     */
    private function loadOpenLinks(): void
    {
        $this->setOpenLinksForDocument(new FacturaCliente(), 'invoice');
        $this->setOpenLinksForDocument(new AlbaranCliente(), 'delivery-note');
        $this->setOpenLinksForDocument(new PedidoCliente(), 'order');
        $this->setOpenLinksForDocument(new PresupuestoCliente(), 'estimation');

        $minDate = Tools::date('-2 days');
        $minDateTime = Tools::dateTime('-2 days');

        $customerModel = new Cliente();
        $whereCustomer = [new DataBaseWhere('fechaalta', $minDate, '>=')];
        foreach ($customerModel->all($whereCustomer, ['fechaalta' => 'DESC'], 0, 3) as $customer) {
            $this->openLinks[] = [
                'type' => 'customer',
                'url' => $customer->url(),
                'name' => $customer->nombre,
                'date' => $customer->fechaalta,
            ];
        }

        $contactModel = new Contacto();
        $whereContact = [new DataBaseWhere('fechaalta', $minDate, '>=')];
        foreach ($contactModel->all($whereContact, ['fechaalta' => 'DESC'], 0, 3) as $contact) {
            $this->openLinks[] = [
                'type' => 'contact',
                'url' => $contact->url(),
                'name' => $contact->fullName(),
                'date' => $contact->fechaalta,
            ];
        }

        $productModel = new Producto();
        $whereProd = [new DataBaseWhere('actualizado', $minDateTime, '>=')];
        foreach ($productModel->all($whereProd, ['actualizado' => 'DESC'], 0, 3) as $product) {
            $this->openLinks[] = [
                'type' => 'product',
                'url' => $product->url(),
                'name' => $product->referencia,
                'date' => $product->actualizado,
            ];
        }

        $this->pipe('loadOpenLinks');
    }

    /**
     * Load the receipts pending collection.
     */
    private function loadReceiptSection(): void
    {
        $receiptModel = new ReciboCliente();
        $where = [
            new DataBaseWhere('pagado', false),
            new DataBaseWhere('vencimiento', Tools::date(), '<'),
            new DataBaseWhere('vencimiento', date('Y-m-d', strtotime('-1 year')), '>'),
        ];
        $this->receipts = $receiptModel->all($where, ['vencimiento' => 'DESC']);

        if (count($this->receipts) > 0) {
            $this->sections[] = 'receipts';
        }
    }

    /**
     * Load statistical data.
     */
    private function loadStats(): void
    {
        $totalModel = new TotalModel();

        // compras
        $this->stats['purchases'] = [
            $this->getStatsMonth(0) => $totalModel->sum('facturasprov', 'neto', $this->getStatsWhere('fecha', 0)),
            $this->getStatsMonth(1) => $totalModel->sum('facturasprov', 'neto', $this->getStatsWhere('fecha', 1)),
            $this->getStatsMonth(2) => $totalModel->sum('facturasprov', 'neto', $this->getStatsWhere('fecha', 2)),
        ];

        // ventas
        $this->stats['sales'] = [
            $this->getStatsMonth(0) => $totalModel->sum('facturascli', 'neto', $this->getStatsWhere('fecha', 0)),
            $this->getStatsMonth(1) => $totalModel->sum('facturascli', 'neto', $this->getStatsWhere('fecha', 1)),
            $this->getStatsMonth(2) => $totalModel->sum('facturascli', 'neto', $this->getStatsWhere('fecha', 2)),
        ];

        // impuestos
        foreach ([0, 1, 2] as $num) {
            $where = $this->getStatsWhere('fecha', $num);
            $this->stats['taxes'][$this->getStatsMonth($num)] = $totalModel->sum('facturascli', 'totaliva', $where)
                + $totalModel->sum('facturascli', 'totalrecargo', $where)
                - $totalModel->sum('facturasprov', 'totaliva', $where)
                - $totalModel->sum('facturasprov', 'totalrecargo', $where);
        }

        // clientes
        $customerModel = new Cliente();
        $this->stats['new-customers'] = [
            $this->getStatsMonth(0) => $customerModel->count($this->getStatsWhere('fechaalta', 0)),
            $this->getStatsMonth(1) => $customerModel->count($this->getStatsWhere('fechaalta', 1)),
            $this->getStatsMonth(2) => $customerModel->count($this->getStatsWhere('fechaalta', 2)),
        ];
    }

    /**
     * @param BusinessDocument $model
     * @param string $label
     */
    private function setOpenLinksForDocument($model, $label): void
    {
        $minDate = Tools::date('-2 days');
        $where = [
            new DataBaseWhere('fecha', $minDate, '>='),
            new DataBaseWhere('nick', $this->user->nick),
        ];
        foreach ($model->all($where, [$model->primaryColumn() => 'DESC'], 0, 3) as $doc) {
            $this->openLinks[] = [
                'type' => $label,
                'url' => $doc->url(),
                'name' => $doc->codigo,
                'date' => $doc->fecha,
            ];
        }
    }
}
