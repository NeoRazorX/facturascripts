<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\Role;
use FacturaScripts\Dinamic\Model\RoleAccess;
use FacturaScripts\Dinamic\Model\User;
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

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'wizard';
        $data['icon'] = 'fa-solid fa-wand-magic-sparkles';
        $data['showonmenu'] = false;
        return $data;
    }

    public function getRegimenIva(): array
    {
        $list = [];
        foreach (RegimenIVA::all() as $key => $value) {
            $list[$key] = Tools::lang()->trans($value);
        }
        return $list;
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
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

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
        $role = new Role();
        $role->codrole = 'employee';
        $role->descripcion = Tools::lang()->trans('employee');
        if ($role->exists()) {
            return;
        }

        $role->save();
        $this->addPagesToRole($role->codrole);

        // asignamos este rol como el predeterminado
        Tools::settingsSet('default', 'codrole', $role->codrole);
        Tools::settingsSave();
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
            $page = new Page();
            $roleAccess = new RoleAccess();

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
                throw new Exception(Tools::lang()->trans('cancel-process'));
            }

            $this->dataBase->commit();
        } catch (Exception $exc) {
            $this->dataBase->rollback();
            Tools::log()->error($exc->getMessage());
            return;
        }
    }

    protected function finalRedirect(): void
    {
        // redirect to the home page
        $this->redirect($this->user->homepage, 2);
    }

    /**
     * Initialize required models.
     *
     * @param array $names
     */
    private function initModels(array $names): void
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
        $cuenta = new Cuenta();
        if ($cuenta->count() > 0 || $this->dataBase->tableExists('co_cuentas')) {
            return;
        }

        $exerciseModel = new Ejercicio();
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
    private function preSetAppSettings(string $codpais): void
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/default.json';
        if (false === file_exists($filePath)) {
            return;
        }

        $fileContent = file_get_contents($filePath);
        $defaultValues = json_decode($fileContent, true) ?? [];
        foreach ($defaultValues as $group => $values) {
            foreach ($values as $key => $value) {
                Tools::settingsSet($group, $key, $value);
            }
        }

        Tools::settingsSet('default', 'codpais', $codpais);
        Tools::settingsSet('default', 'homepage', 'AdminPlugins');
        Tools::settingsSave();
    }

    /**
     * Save company default address.
     *
     * @param string $codpais
     */
    private function saveAddress(string $codpais): void
    {
        $this->empresa->apartado = $this->request->request->get('apartado', '');
        $this->empresa->cifnif = $this->request->request->get('cifnif', '');
        $this->empresa->ciudad = $this->request->request->get('ciudad', '');
        $this->empresa->codpais = $codpais;
        $this->empresa->codpostal = $this->request->request->get('codpostal', '');
        $this->empresa->direccion = $this->request->request->get('direccion', '');
        $this->empresa->nombre = $this->request->request->get('empresa', '');
        $this->empresa->nombrecorto = Tools::textBreak($this->empresa->nombre, 32);
        $this->empresa->personafisica = (bool)$this->request->request->get('personafisica', '0');
        $this->empresa->provincia = $this->request->request->get('provincia', '');
        $this->empresa->telefono1 = $this->request->request->get('telefono1', '');
        $this->empresa->telefono2 = $this->request->request->get('telefono2', '');
        $this->empresa->tipoidfiscal = $this->request->request->get('tipoidfiscal', '');
        if (empty($this->empresa->tipoidfiscal)) {
            $this->empresa->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
        }
        $this->empresa->save();

        // assigns warehouse?
        $almacenModel = new Almacen();
        $where = [
            new DataBaseWhere('idempresa', $this->empresa->idempresa),
            new DataBaseWhere('idempresa', null, 'IS', 'OR')
        ];
        foreach ($almacenModel->all($where) as $almacen) {
            $this->setWarehouse($almacen, $codpais);
            return;
        }

        // no assigned warehouse? Create a new one
        $almacen = new Almacen();
        $this->setWarehouse($almacen, $codpais);
    }

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

    private function saveStep1(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

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

    private function saveStep2(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        $this->empresa->regimeniva = $this->request->request->get('regimeniva');
        $this->empresa->save();

        foreach (['codimpuesto', 'costpricepolicy'] as $key) {
            $value = $this->request->request->get($key);
            $finalValue = empty($value) ? null : $value;
            Tools::settingsSet('default', $key, $finalValue);
        }
        Tools::settingsSet('default', 'updatesupplierprices', (bool)$this->request->request->get('updatesupplierprices', '0'));
        Tools::settingsSet('default', 'ventasinstock', (bool)$this->request->request->get('ventasinstock', '0'));
        Tools::settingsSave();

        if ($this->request->request->get('defaultplan', '0')) {
            $this->loadDefaultAccountingPlan($this->empresa->codpais);
        }

        // change template and redirect
        $this->setTemplate('Wizard-3');
        $this->redirect($this->url() . '?action=step3', 2);
    }

    protected function saveStep3(): void
    {
        // load all models
        $modelNames = [];
        $modelsFolder = Tools::folder('Dinamic', 'Model');
        foreach (Tools::folderScan($modelsFolder) as $fileName) {
            if ('.php' === substr($fileName, -4)) {
                $modelNames[] = substr($fileName, 0, -4);
            }
        }
        if (false === $this->dataBase->tableExists('fs_users')) {
            // avoid this step in 2017 installations
            $this->initModels($modelNames);
        }

        // load controllers
        Plugins::deploy(true, true);

        // add the default role for employees
        $this->addDefaultRoleAccess();

        // change user homepage
        $this->user->homepage = $this->dataBase->tableExists('fs_users') ? 'AdminPlugins' : static::NEW_DEFAULT_PAGE;
        $this->user->save();

        // change template and redirect
        $this->setTemplate('Wizard-3');
        $this->finalRedirect();
    }

    private function setWarehouse(Almacen $almacen, string $codpais): void
    {
        $almacen->ciudad = $this->empresa->ciudad;
        $almacen->codpais = $codpais;
        $almacen->codpostal = $this->empresa->codpostal;
        $almacen->direccion = $this->empresa->direccion;
        $almacen->idempresa = $this->empresa->idempresa;
        $almacen->nombre = $this->empresa->nombrecorto;
        $almacen->provincia = $this->empresa->provincia;
        $almacen->save();

        Tools::settingsSet('default', 'codalmacen', $almacen->codalmacen);
        Tools::settingsSet('default', 'idempresa', $this->empresa->idempresa);
        Tools::settingsSave();
    }
}
