<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\PluginManager;
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

    /**
     * 
     * @return array
     */
    public function getAvaliablePlugins()
    {
        $pluginManager = new PluginManager();
        $installedPlugins = $pluginManager->installedPlugins();
        if (!defined('FS_HIDDEN_PLUGINS')) {
            return $installedPlugins;
        }

        /// exclude hidden plugins
        $hiddenPlugins = \explode(',', \FS_HIDDEN_PLUGINS);
        foreach ($installedPlugins as $key => $plugin) {
            if (\in_array($plugin['name'], $hiddenPlugins, false)) {
                unset($installedPlugins[$key]);
            }
        }

        return $installedPlugins;
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['showonmenu'] = false;
        $data['title'] = 'wizard';
        $data['icon'] = 'fas fa-magic';
        return $data;
    }

    /**
     * Returns an array with all data from selected model.
     *
     * @param string $modelName
     *
     * @return mixed
     */
    public function getSelectValues($modelName)
    {
        $values = [];
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
     * @param Response              $response
     * @param Model\User            $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $codpais = $this->request->request->get('codpais', '');
        if ('' !== $codpais) {
            $this->saveStep1($codpais);
        }
    }

    /**
     * Add/update the default role for agents, and adds to this role access to all default pages.
     *
     * @return bool Returns true on success, false otherwise
     */
    private function addDefaultRoleAccess(): bool
    {
        $role = new Model\Role();
        $role->codrole = \mb_strtolower($this->toolBox()->i18n()->trans('employee'), 'UTF8');
        $role->descripcion = $this->toolBox()->i18n()->trans('employee');

        if ($role->exists()) {
            return true;
        } elseif ($role->save()) {
            return $this->addPagesToRole($role->codrole);
        }

        return false;
    }

    /**
     * Adds to received codrole, all pages that are not in admin menu and are not yet enabled.
     *
     * @param string $codrole
     *
     * @return bool Returns true on success, false otherwise and rollback the changes
     */
    private function addPagesToRole($codrole): bool
    {
        $this->dataBase->beginTransaction();

        try {
            $page = new Model\Page();
            $roleAccess = new Model\RoleAccess();

            /// all pages not in admin menu and not yet enabled
            $inSQL = "SELECT name FROM pages WHERE menu != 'admin' AND name NOT IN ("
                . 'SELECT pagename FROM roles_access WHERE codrole = ' . $this->dataBase->var2str($codrole)
                . ')';
            $where = [new DataBaseWhere('name', $inSQL, 'IN')];
            $pages = $page->all($where, [], 0, 0);

            /// add EditUser page
            if ($page->loadFromCode('EditUser')) {
                $pages[] = $page;
            }

            /// add pages to the role
            if (!$roleAccess->addPagesToRole($codrole, $pages)) {
                throw new Exception($this->toolBox()->i18n()->trans('cancel-process'));
            }

            $this->dataBase->commit();
        } catch (Exception $exc) {
            $this->dataBase->rollback();
            $this->toolBox()->log()->error($exc->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Enable all logs by default.
     */
    private function enableLogs()
    {
        $appSettings = $this->toolBox()->appSettings();

        $types = ['critical', 'error', 'warning'];
        foreach ($types as $type) {
            $appSettings->set('log', $type, 'true');
        }

        $appSettings->save();
    }

    /**
     * Initialize required models.
     */
    private function initModels()
    {
        new Model\AttachedFile();
        new Model\Diario();
        new Model\IdentificadorFiscal();
        new Model\FormaPago();
        new Model\Impuesto();
        new Model\Retencion();
        new Model\Serie();
        new Model\Provincia();
    }

    /**
     * Initialize selected plugins
     */
    private function initPlugins()
    {
        $pluginManager = new PluginManager();
        $pluginManager->deploy(true, true);

        $hiddenPlugins = \explode(',', \FS_HIDDEN_PLUGINS);
        if (is_array($hiddenPlugins)) {
            foreach ($hiddenPlugins as $pluginName) {
                $pluginManager->enable($pluginName);
            }
        }

        $plugins = $this->request->request->get('plugins', []);
        if (is_array($plugins)) {
            foreach ($plugins as $pluginName) {
                $pluginManager->enable($pluginName);
            }
        }
    }

    /**
     * Set default AppSettings based on codpais
     *
     * @param string $codpais
     */
    private function preSetAppSettings(string $codpais)
    {
        $filePath = \FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/default.json';
        if (!file_exists($filePath)) {
            return;
        }

        $appSettings = $this->toolBox()->appSettings();

        $fileContent = file_get_contents($filePath);
        $defaultValues = json_decode($fileContent, true) ?? [];
        foreach ($defaultValues as $group => $values) {
            foreach ($values as $key => $value) {
                $appSettings->set($group, $key, $value);
            }
        }

        $appSettings->save();
    }

    /**
     * Save company default address.
     *
     * @param string $codpais
     */
    private function saveAddress(string $codpais)
    {
        $appSettings = $this->toolBox()->appSettings();

        $this->empresa->apartado = $this->request->request->get('apartado', '');
        $this->empresa->cifnif = $this->request->request->get('cifnif', '');
        $this->empresa->ciudad = $this->request->request->get('ciudad', '');
        $this->empresa->codpais = $codpais;
        $this->empresa->codpostal = $this->request->request->get('codpostal', '');
        $this->empresa->direccion = $this->request->request->get('direccion', '');
        $this->empresa->nombre = $this->request->request->get('empresa', '');
        $this->empresa->nombrecorto = $this->request->request->get('nombrecorto', '');
        $this->empresa->provincia = $this->request->request->get('provincia', '');
        $this->empresa->telefono1 = $this->request->request->get('telefono1', '');
        $this->empresa->telefono2 = $this->request->request->get('telefono2', '');
        $this->empresa->save();

        /// assignes warehouse?
        $almacenModel = new Model\Almacen();
        $where = [
            new DataBaseWhere('idempresa', $this->empresa->idempresa),
            new DataBaseWhere('idempresa', null, 'IS', 'OR'),
        ];
        foreach ($almacenModel->all($where) as $almacen) {
            $almacen->ciudad = $this->empresa->ciudad;
            $almacen->codpais = $codpais;
            $almacen->codpostal = $this->empresa->codpostal;
            $almacen->direccion = $this->empresa->direccion;
            $almacen->idempresa = $this->empresa->idempresa;
            $almacen->nombre = $this->empresa->nombrecorto;
            $almacen->provincia = $this->empresa->provincia;
            $almacen->save();

            $appSettings->set('default', 'codalmacen', $almacen->codalmacen);
            $appSettings->set('default', 'idempresa', $this->empresa->idempresa);
            $appSettings->save();
            return;
        }

        /// no assigned warehouse? Create a new one
        $almacen = new Model\Almacen();
        $almacen->ciudad = $this->empresa->ciudad;
        $almacen->codpais = $codpais;
        $almacen->codpostal = $this->empresa->codpostal;
        $almacen->direccion = $this->empresa->direccion;
        $almacen->idempresa = $this->empresa->idempresa;
        $almacen->nombre = $this->empresa->nombrecorto;
        $almacen->provincia = $this->empresa->provincia;
        $almacen->save();

        $appSettings->set('default', 'codalmacen', $almacen->codalmacen);
        $appSettings->set('default', 'idempresa', $this->empresa->idempresa);
        $appSettings->save();
    }

    /**
     * 
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

    /**
     * 
     * @param string $codpais
     */
    private function saveStep1(string $codpais)
    {
        $this->preSetAppSettings($codpais);

        $appSettings = $this->toolBox()->appSettings();
        $appSettings->set('default', 'codpais', $codpais);
        $appSettings->set('default', 'homepage', 'AdminPlugins');
        $appSettings->save();

        $this->initModels();
        $this->initPlugins();
        $this->saveAddress($codpais);

        /// change password
        $pass = $this->request->request->get('password', '');
        if ('' !== $pass && !$this->saveNewPassword($pass)) {
            return;
        }

        /// change email
        $email = $this->request->request->get('email', '');
        if ('' !== $email && !$this->saveEmail($email)) {
            return;
        }

        /// change user homepage
        $this->user->homepage = $this->dataBase->tableExists('fs_users') ? 'AdminPlugins' : 'ListFacturaCliente';
        $this->user->save();

        /// change default log values to enabled
        $this->enableLogs();

        /// add the default role for employees
        $this->addDefaultRoleAccess();

        /// clear routes
        $appRouter = new AppRouter();
        $appRouter->clear();

        /// redirect to the home page
        $this->redirect($this->user->homepage);
    }
}
