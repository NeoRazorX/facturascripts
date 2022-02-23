<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\App\AppRouter;
use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of Wizard
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Wizard extends Controller
{

    const ITEM_SELECT_LIMIT = 500;
    const NEW_DEFAULT_PAGE = 'Dashboard';

    /**
     * @var AppSettings
     */
    protected $appSettings;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'wizard';
        $data['icon'] = 'fas fa-magic';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * @return array
     */
    public function getRegimenIva(): array
    {
        return RegimenIVA::all();
    }

    /**
     * Returns an array with all data from selected model.
     *
     * @param string $modelName
     * @param bool $addEmpty
     *
     * @return array
     */
    public function getSelectValues(string $modelName, bool $addEmpty = false): array
    {
        $values = $addEmpty ? ['' => '------'] : [];
        $modelName = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $model = new $modelName();

        $order = [$model->primaryDescriptionColumn() => 'ASC'];
        foreach ($model->all([], $order, 0, self::ITEM_SELECT_LIMIT) as $newModel) {
            $values[$newModel->primaryColumnValue()] = $newModel->primaryDescription();
        }

        return $values;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param Model\User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->appSettings = self::toolBox()::appSettings();

        $action = $this->request->get('action', '');
        switch ($action) {
            case 'step1':
                $this->saveStep1();
                break;

            case 'step2':
                $this->saveStep2();
                break;

            case 'step3':
                $this->saveStep3();
                break;

            default:
                if (empty($this->empresa->email) && $this->user->email) {
                    $this->empresa->email = $this->user->email;
                    $this->empresa->save();
                }
        }
    }

    /**
     * Add/update the default role for agents, and adds to this role access to all default pages.
     *
     * @return void
     */
    private function addDefaultRoleAccess(): void
    {
        $role = new Model\Role();
        $role->codrole = 'employee';
        $role->descripcion = $this->toolBox()->i18n()->trans('employee');
        if ($role->exists()) {
            return;
        }

        $role->save();
        $this->addPagesToRole($role->codrole);
    }

    /**
     * Adds to received codrole, all pages that are not in admin menu and are not yet enabled.
     *
     * @param string $codrole
     *
     * @return void
     */
    private function addPagesToRole(string $codrole): void
    {
        $this->dataBase->beginTransaction();

        try {
            $page = new Model\Page();
            $roleAccess = new Model\RoleAccess();

            // all pages not in admin menu and not yet enabled
            $inSQL = "SELECT name FROM pages WHERE menu != 'admin' AND name NOT IN "
                . '(SELECT pagename FROM roles_access WHERE codrole = ' . $this->dataBase->var2str($codrole) . ')';
            $where = [new DataBaseWhere('name', $inSQL, 'IN')];
            $pages = $page->all($where, [], 0, 0);

            // add EditUser page
            if ($page->loadFromCode('EditUser')) {
                $pages[] = $page;
            }

            // add pages to the role
            if (false === $roleAccess->addPagesToRole($codrole, $pages)) {
                throw new Exception($this->toolBox()->i18n()->trans('cancel-process'));
            }

            $this->dataBase->commit();
        } catch (Exception $exc) {
            $this->dataBase->rollback();
            $this->toolBox()->log()->error($exc->getMessage());
            return;
        }
    }

    protected function finalRedirect()
    {
        // redirect to the home page
        $this->redirect($this->user->homepage, 2);
    }

    /**
     * Initialize required models.
     *
     * @param array $names
     */
    private function initModels(array $names)
    {
        foreach ($names as $name) {
            $className = '\\FacturaScripts\\Dinamic\\Model\\' . $name;
            new $className();
        }
    }

    /**
     * Loads the default accounting plan. If there is one.
     *
     * @param string $codpais
     *
     * @return void
     */
    private function loadDefaultAccountingPlan(string $codpais): void
    {
        // Is there a default accounting plan?
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/defaultPlan.csv';
        if (false === file_exists($filePath)) {
            return;
        }

        // Does an accounting plan already exist?
        $cuenta = new Model\Cuenta();
        if ($cuenta->count() > 0 || $this->dataBase->tableExists('co_cuentas')) {
            return;
        }

        $exerciseModel = new Model\Ejercicio();
        foreach ($exerciseModel->all() as $exercise) {
            $planImport = new AccountingPlanImport();
            $planImport->importCSV($filePath, $exercise->codejercicio);
            return;
        }
    }

    /**
     * Set default AppSettings based on codpais
     *
     * @param string $codpais
     */
    private function preSetAppSettings(string $codpais)
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/default.json';
        if (false === file_exists($filePath)) {
            return;
        }

        $fileContent = file_get_contents($filePath);
        $defaultValues = json_decode($fileContent, true) ?? [];
        foreach ($defaultValues as $group => $values) {
            foreach ($values as $key => $value) {
                $this->appSettings->set($group, $key, $value);
            }
        }

        $this->appSettings->set('default', 'codpais', $codpais);
        $this->appSettings->set('default', 'homepage', 'AdminPlugins');
        $this->appSettings->save();
    }

    /**
     * Save company default address.
     *
     * @param string $codpais
     */
    private function saveAddress(string $codpais)
    {
        $this->empresa->apartado = $this->request->request->get('apartado', '');
        $this->empresa->cifnif = $this->request->request->get('cifnif', '');
        $this->empresa->ciudad = $this->request->request->get('ciudad', '');
        $this->empresa->codpais = $codpais;
        $this->empresa->codpostal = $this->request->request->get('codpostal', '');
        $this->empresa->direccion = $this->request->request->get('direccion', '');
        $this->empresa->nombre = $this->request->request->get('empresa', '');
        $this->empresa->nombrecorto = mb_substr($this->empresa->nombre, 0, 32);
        $this->empresa->personafisica = (bool)$this->request->request->get('personafisica', '0');
        $this->empresa->provincia = $this->request->request->get('provincia', '');
        $this->empresa->telefono1 = $this->request->request->get('telefono1', '');
        $this->empresa->telefono2 = $this->request->request->get('telefono2', '');
        $this->empresa->tipoidfiscal = $this->request->request->get('tipoidfiscal', '');
        if (empty($this->empresa->tipoidfiscal)) {
            $this->empresa->tipoidfiscal = $this->appSettings->get('default', 'tipoidfiscal');
        }
        $this->empresa->save();

        // assigns warehouse?
        $almacenModel = new Model\Almacen();
        $where = [
            new DataBaseWhere('idempresa', $this->empresa->idempresa),
            new DataBaseWhere('idempresa', null, 'IS', 'OR')
        ];
        foreach ($almacenModel->all($where) as $almacen) {
            $this->setWarehouse($almacen, $codpais);
            return;
        }

        // no assigned warehouse? Create a new one
        $almacen = new Model\Almacen();
        $this->setWarehouse($almacen, $codpais);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    private function saveEmail(string $email): bool
    {
        if (empty($this->empresa->email)) {
            $this->empresa->email = $email;
        }

        if (empty($this->user->email)) {
            $this->user->email = $email;
        }

        return $this->empresa->save() && $this->user->save();
    }

    /**
     * Save the new password if data is admin admin
     *
     * @return bool Returns true if success, otherwise return false.
     */
    private function saveNewPassword(string $pass): bool
    {
        $this->user->newPassword = $pass;
        $this->user->newPassword2 = $this->request->request->get('repassword', '');
        return $this->user->save();
    }

    private function saveStep1()
    {
        $codpais = $this->request->request->get('codpais', $this->empresa->codpais);
        $this->preSetAppSettings($codpais);

        $this->initModels(['AttachedFile', 'Diario', 'EstadoDocumento', 'FormaPago',
            'Impuesto', 'Retencion', 'Serie', 'Provincia']);
        $this->saveAddress($codpais);

        // change password
        $pass = $this->request->request->get('password', '');
        if ('' !== $pass && false === $this->saveNewPassword($pass)) {
            return;
        }

        // change email
        $email = $this->request->request->get('email', '');
        if ('' !== $email && false === $this->saveEmail($email)) {
            return;
        }

        // change template
        $this->setTemplate('Wizard-2');
    }

    private function saveStep2()
    {
        $this->empresa->regimeniva = $this->request->request->get('regimeniva');
        $this->empresa->save();

        foreach (['codimpuesto', 'costpricepolicy'] as $key) {
            $value = $this->request->request->get($key);
            $finalValue = empty($value) ? null : $value;
            $this->appSettings->set('default', $key, $finalValue);
        }
        $this->appSettings->set('default', 'updatesupplierprices', (bool)$this->request->request->get('updatesupplierprices', '0'));
        $this->appSettings->set('default', 'ventasinstock', (bool)$this->request->request->get('ventasinstock', '0'));
        $this->appSettings->save();

        if ($this->request->request->get('defaultplan', '0')) {
            $this->loadDefaultAccountingPlan($this->empresa->codpais);
        }

        // change template and redirect
        $this->setTemplate('Wizard-3');
        $this->redirect($this->url() . '?action=step3', 2);
    }

    protected function saveStep3()
    {
        // load all models
        $modelNames = [];
        $modelsFolder = FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Model';
        foreach ($this->toolBox()->files()->scanFolder($modelsFolder) as $fileName) {
            if ('.php' === substr($fileName, -4)) {
                $modelNames[] = substr($fileName, 0, -4);
            }
        }
        if (false === $this->dataBase->tableExists('fs_users')) {
            // avoid this step in 2017 installations
            $this->initModels($modelNames);
        }

        // load controllers
        $pluginManager = new PluginManager();
        $pluginManager->deploy(true, true);

        // add the default role for employees
        $this->addDefaultRoleAccess();

        // clear routes
        $appRouter = new AppRouter();
        $appRouter->clear();

        // change user homepage
        $this->user->homepage = $this->dataBase->tableExists('fs_users') ? 'AdminPlugins' : static::NEW_DEFAULT_PAGE;
        $this->user->save();

        // change template and redirect
        $this->setTemplate('Wizard-3');
        $this->finalRedirect();
    }

    /**
     * @param Model\Almacen $almacen
     * @param string $codpais
     */
    private function setWarehouse(Model\Almacen $almacen, string $codpais): void
    {
        $almacen->ciudad = $this->empresa->ciudad;
        $almacen->codpais = $codpais;
        $almacen->codpostal = $this->empresa->codpostal;
        $almacen->direccion = $this->empresa->direccion;
        $almacen->idempresa = $this->empresa->idempresa;
        $almacen->nombre = $this->empresa->nombrecorto;
        $almacen->provincia = $this->empresa->provincia;
        $almacen->save();

        $this->appSettings->set('default', 'codalmacen', $almacen->codalmacen);
        $this->appSettings->set('default', 'idempresa', $this->empresa->idempresa);
        $this->appSettings->save();
    }
}
