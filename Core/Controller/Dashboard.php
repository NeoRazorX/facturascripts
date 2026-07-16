<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Http;
use FacturaScripts\Core\Internal\Forja;
use FacturaScripts\Core\Where;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Telemetry;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\ReciboCliente;
use FacturaScripts\Dinamic\Model\Stock;
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
    public $firstSteps = [];

    /** @var int */
    public $firstStepsCompleted = 0;

    /** @var bool */
    public $isOnboarding = false;

    /** @var array */
    public $lowStock = [];

    /** @var int */
    public $lowStockCount = 0;

    /** @var array */
    public $news = [];

    /** @var array */
    public $openLinks = [];

    /** @var array */
    public $receipts = [];

    /** @var int */
    public $receiptCount = 0;

    /** @var bool */
    public $registered = false;

    /** @var array */
    public $sections = [];

    /** @var array */
    public $stats = [];

    /** @var array */
    public $statChanges = [];

    /** @var bool */
    public $updated = false;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'dashboard';
        $data['icon'] = 'fa-solid fa-chalkboard-teacher';
        $data['showonmenu'] = false;
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

        $this->title = Tools::trans('dashboard-for', ['%company%' => $this->empresa->nombrecorto]);

        $this->loadExtensions();

        // comprobamos si la instalación está registrada (solo para administradores)
        $this->registered = $user->admin === false || Telemetry::init()->ready();

        // comprobamos si hay actualizaciones disponibles (solo para administradores)
        $this->updated = $user->admin === false || Forja::canUpdateCore() === false;
    }

    public function showBackupWarning(): bool
    {
        if (false === $this->user->admin) {
            return false;
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        // comprobamos si estamos el localhost
        if (
            $ipAddress == 'localhost' ||
            $ipAddress == '127.0.0.1' ||
            $ipAddress == '::1' ||
            substr($ipAddress, 0, 4) == '192.' ||
            substr($ipAddress, 0, 4) == '172.'
        ) {
            // si el plugin Backup está activo, devolvemos false
            return !Plugins::isEnabled('Backup');
        }

        return false;
    }

    /**
     * Set the quick links for data creation.
     * Example: createLinks['EditControllerName'] = 'label'
     */
    private function loadCreateLinks(): void
    {
        $links = [
            'EditFacturaCliente' => 'customer-invoice',
            'EditFacturaProveedor' => 'supplier-invoice',
            'EditCliente' => 'customer',
            'EditProducto' => 'product',
        ];
        foreach ($links as $pageName => $label) {
            if ($this->user->can($pageName, 'update')) {
                $this->createLinks[$pageName] = $label;
            }
        }

        $this->pipe('loadCreateLinks');
    }

    /**
     * Establish the sections to be displayed on the dashboard.
     */
    private function loadExtensions(): void
    {
        $this->loadCreateLinks();
        $this->loadFirstSteps();
        $this->loadOpenLinks();

        if (false === $this->isOnboarding) {
            $this->loadLowStockSection();
            $this->loadReceiptSection();
            $this->loadStats();
            $this->loadNews();
        }

        $this->pipe('loadExtensions');
    }

    private function loadFirstSteps(): void
    {
        $customerCount = Cliente::count();
        $productCount = Producto::countWhereEq('sevende', true);
        $invoiceCount = FacturaCliente::count();

        $steps = [
            [
                'complete' => $customerCount > 0,
                'icon' => 'fa-solid fa-plus',
                'label' => 'customer',
                'url' => 'EditCliente',
            ],
            [
                'complete' => $productCount > 0,
                'icon' => 'fa-solid fa-plus',
                'label' => 'product',
                'url' => 'EditProducto',
            ],
            [
                'complete' => $invoiceCount > 0,
                'icon' => 'fa-solid fa-plus',
                'label' => 'customer-invoice',
                'url' => 'EditFacturaCliente',
            ],
        ];

        foreach ($steps as $step) {
            if (false === $this->user->can($step['url'], 'update')) {
                continue;
            }

            $this->firstSteps[] = $step;
            if ($step['complete']) {
                ++$this->firstStepsCompleted;
            }
        }

        if ($this->user->admin && $this->showBackupWarning()) {
            $this->firstSteps[] = [
                'complete' => false,
                'icon' => 'fa-solid fa-floppy-disk',
                'label' => 'dashboard-backup',
                'url' => 'https://facturascripts.com/plugins/backup',
            ];
        }

        $this->isOnboarding = $invoiceCount === 0 && $this->user->can('EditFacturaCliente', 'update');
        $this->pipe('loadFirstSteps');
    }

    /**
     * Load the data regarding the stock under minimum.
     */
    private function loadLowStockSection(): void
    {
        if (false === $this->user->can('ListProducto') || false === $this->dataBase->tableExists('stocks')) {
            return;
        }

        $where = 'stockmin > 0 AND disponible < stockmin';
        $count = $this->dataBase->select('SELECT COUNT(*) AS total FROM stocks WHERE ' . $where . ';');
        $this->lowStockCount = (int)($count[0]['total'] ?? 0);
        if ($this->lowStockCount === 0) {
            return;
        }

        $sql = 'SELECT * FROM stocks WHERE ' . $where . ' ORDER BY (stockmin - disponible) DESC LIMIT 5;';
        foreach ($this->dataBase->select($sql) as $row) {
            $this->lowStock[] = new Stock($row);
        }

        $this->sections[] = 'low-stock';
    }

    /**
     * Load last news from facturascripts.com
     */
    private function loadNews(): void
    {
        $news = Cache::remember('dashboard-news', function () {
            return Http::get('https://facturascripts.com/comm3/index.php?page=community_changelog&json=TRUE')
                ->setTimeout(5)
                ->json() ?? [];
        });
        $this->news = is_array($news) ? array_slice($news, 0, 1) : [];
    }

    /**
     * Loads the links to the latest data created by the user.
     */
    private function loadOpenLinks(): void
    {
        $this->setOpenLinksForDocument(new FacturaCliente(), 'invoice', 'EditFacturaCliente');
        $this->setOpenLinksForDocument(new FacturaProveedor(), 'supplier-invoice', 'EditFacturaProveedor');
        $this->setOpenLinksForDocument(new AlbaranCliente(), 'delivery-note', 'EditAlbaranCliente');
        $this->setOpenLinksForDocument(new PedidoCliente(), 'order', 'EditPedidoCliente');
        $this->setOpenLinksForDocument(new PresupuestoCliente(), 'estimation', 'EditPresupuestoCliente');

        usort($this->openLinks, function (array $link1, array $link2) {
            return strtotime($link2['date']) <=> strtotime($link1['date']);
        });
        $this->openLinks = array_slice($this->openLinks, 0, 6);

        $this->pipe('loadOpenLinks');
    }

    /**
     * Load the receipts pending collection.
     */
    private function loadReceiptSection(): void
    {
        if (false === $this->user->can('ListReciboCliente')) {
            return;
        }

        $where = [
            Where::eq('pagado', false),
            Where::lt('vencimiento', Tools::date()),
            Where::gt('vencimiento', date('Y-m-d', strtotime('-1 year'))),
        ];

        // si el usuario solo ve sus datos, limitamos los recibos a los suyos
        if ($this->user->can('ListReciboCliente', 'only-owner-data')) {
            $where[] = Where::eq('nick', $this->user->nick);
        }

        $this->receiptCount = ReciboCliente::count($where);
        $this->receipts = ReciboCliente::all($where, ['vencimiento' => 'ASC'], 0, 5);

        if ($this->receiptCount > 0) {
            $this->sections[] = 'receipts';
        }
    }

    /**
     * Load statistical data.
     */
    private function loadStats(): void
    {
        $sales = $this->user->can('ListFacturaCliente')
            ? $this->getMonthlyDocumentTotals(FacturaCliente::tableName(), 'ListFacturaCliente')
            : null;
        $purchases = $this->user->can('ListFacturaProveedor')
            ? $this->getMonthlyDocumentTotals(FacturaProveedor::tableName(), 'ListFacturaProveedor')
            : null;

        if ($purchases) {
            $this->setStats('purchases', $purchases['current_net'], $purchases['previous_net']);
        }
        if ($sales) {
            $this->setStats('sales', $sales['current_net'], $sales['previous_net']);
        }
        if ($sales && $purchases) {
            $this->setStats(
                'taxes',
                $sales['current_tax'] - $purchases['current_tax'],
                $sales['previous_tax'] - $purchases['previous_tax']
            );
        }
        if (
            $this->user->can('ListCliente') &&
            false === $this->user->can('ListCliente', 'only-owner-data')
        ) {
            $customers = $this->getMonthlyCustomerTotals();
            $this->setStats('new-customers', $customers['current'], $customers['previous']);
        }
    }

    private function getMonthlyCustomerTotals(): array
    {
        [$previousFrom, $currentFrom, $until] = $this->getStatsDates();
        $sql = 'SELECT '
            . 'COALESCE(SUM(CASE WHEN fechaalta >= ' . $this->dataBase->var2str($currentFrom)
            . ' THEN 1 ELSE 0 END), 0) AS current_total, '
            . 'COALESCE(SUM(CASE WHEN fechaalta < ' . $this->dataBase->var2str($currentFrom)
            . ' THEN 1 ELSE 0 END), 0) AS previous_total '
            . 'FROM ' . Cliente::tableName()
            . ' WHERE fechaalta >= ' . $this->dataBase->var2str($previousFrom)
            . ' AND fechaalta < ' . $this->dataBase->var2str($until) . ';';
        $row = $this->dataBase->select($sql)[0] ?? [];

        return [
            'current' => (float)($row['current_total'] ?? 0),
            'previous' => (float)($row['previous_total'] ?? 0),
        ];
    }

    private function getMonthlyDocumentTotals(string $tableName, string $pageName): array
    {
        [$previousFrom, $currentFrom, $until] = $this->getStatsDates();
        $ownerFilter = $this->user->can($pageName, 'only-owner-data')
            ? ' AND nick = ' . $this->dataBase->var2str($this->user->nick)
            : '';
        $sql = 'SELECT '
            . 'COALESCE(SUM(CASE WHEN fecha >= ' . $this->dataBase->var2str($currentFrom)
            . ' THEN neto ELSE 0 END), 0) AS current_net, '
            . 'COALESCE(SUM(CASE WHEN fecha < ' . $this->dataBase->var2str($currentFrom)
            . ' THEN neto ELSE 0 END), 0) AS previous_net, '
            . 'COALESCE(SUM(CASE WHEN fecha >= ' . $this->dataBase->var2str($currentFrom)
            . ' THEN totaliva + totalrecargo ELSE 0 END), 0) AS current_tax, '
            . 'COALESCE(SUM(CASE WHEN fecha < ' . $this->dataBase->var2str($currentFrom)
            . ' THEN totaliva + totalrecargo ELSE 0 END), 0) AS previous_tax '
            . 'FROM ' . $tableName
            . ' WHERE fecha >= ' . $this->dataBase->var2str($previousFrom)
            . ' AND fecha < ' . $this->dataBase->var2str($until)
            . $ownerFilter . ';';
        $row = $this->dataBase->select($sql)[0] ?? [];

        return [
            'current_net' => (float)($row['current_net'] ?? 0),
            'current_tax' => (float)($row['current_tax'] ?? 0),
            'previous_net' => (float)($row['previous_net'] ?? 0),
            'previous_tax' => (float)($row['previous_tax'] ?? 0),
        ];
    }

    private function getStatsDates(): array
    {
        return [
            date('Y-m-01', strtotime('-1 month')),
            date('Y-m-01'),
            date('Y-m-01', strtotime('+1 month')),
        ];
    }

    private function setStats(string $group, float $current, float $previous): void
    {
        $this->stats[$group] = [
            'this-month' => $current,
            'last-month' => $previous,
        ];
        $this->statChanges[$group] = abs($previous) > 0.00001
            ? (($current - $previous) / abs($previous)) * 100
            : null;
    }

    /**
     * @param BusinessDocument $model
     * @param string $label
     * @param string $pageName
     */
    private function setOpenLinksForDocument($model, $label, string $pageName): void
    {
        if (false === $this->user->can($pageName)) {
            return;
        }

        $minDate = Tools::date('-7 days');
        $where = [
            Where::gte('fecha', $minDate),
            Where::eq('nick', $this->user->nick),
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
