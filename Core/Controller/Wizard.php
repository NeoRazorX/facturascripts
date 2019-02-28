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

use FacturaScripts\Core\App\AppRouter;
use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Model;
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
     * @var AppSettings
     */
    private $appSettings;

    /**
     *
     * @var bool
     */
    public $showChangePasswd = false;

    /**
     * Check if the user must introduce the email.
     *
     * @var bool
     */
    public $showIntroduceEmail = false;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'wizard';
        $pageData['menu'] = 'admin';
        $pageData['showonmenu'] = false;
        $pageData['icon'] = 'fas fa-magic';

        return $pageData;
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
        $modelName = '\FacturaScripts\Dinamic\Model\\' . $modelName;
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
        $this->appSettings = new AppSettings();

        // Show message if user and password are admin
        if ($this->user->nick === 'admin' && $this->user->verifyPassword('admin')) {
            $this->showChangePasswd = true;
        }

        if (empty($this->user->email)) {
            $this->showIntroduceEmail = true;
        }

        $pass = $this->request->request->get('password', '');
        if ('' !== $pass && !$this->saveNewPassword($pass)) {
            return;
        }

        $email = $this->request->request->get('email', '');
        if ('' !== $email && !$this->saveEmail($email)) {
            return;
        }

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
        if ($role->loadFromCode($role->codrole)) {
            return $this->addPagesToRole($role->codrole);
        }

        $role->codrole = \mb_strtolower($this->i18n->trans('agents'), 'UTF8');
        $role->descripcion = $this->i18n->trans('agents');
        if ($role->save()) {
            return $this->addPagesToRole($role->codrole);
        }

        return false;
    }

    /**
     * Adds to received codrole, all pages that are not in admin menu and are not yet enabled.
     *
     * @param $codrole
     *
     * @return bool Returns true on success, false otherwise and rollback the changes
     */
    private function addPagesToRole($codrole): bool
    {
        $roleAccess = new Model\RoleAccess();
        $this->dataBase->beginTransaction();
        try {
            $page = new Model\Page();
            /// All pages not in admin menu and not yet enabled
            $inSQL = "SELECT name FROM pages WHERE menu != 'admin' AND name NOT IN ("
                . 'SELECT pagename FROM roles_access WHERE codrole = ' . $this->dataBase->var2str($codrole)
                . ')';
            $where = [new DataBaseWhere('name', $inSQL, 'IN')];
            $pages = $page->all($where, [], 0, 0);
            // add Pages to Rol
            if (!$roleAccess->addPagesToRole($codrole, $pages)) {
                throw new \Exception($this->i18n->trans('cancel-process'));
            }
            $this->dataBase->commit();
            return true;
        } catch (\Exception $exc) {
            $this->dataBase->rollback();
            $this->miniLog->error($exc->getMessage());
            return false;
        }
    }

    /**
     * Enable all logs by default.
     */
    private function enableLogs()
    {
        $types = ['error', 'critical', 'alert', 'emergency'];
        $appSettings = new AppSettings();
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
        new Model\FormaPago();
        new Model\Impuesto();
        new Model\Serie();
        new Model\Provincia();

        $pluginManager = new PluginManager();
        $pluginManager->deploy(true, true);
    }

    /**
     * Set default AppSettings based on codpais
     *
     * @param string $codpais
     */
    private function preSetAppSettings(string $codpais)
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/default.json';
        if (!file_exists($filePath)) {
            return;
        }

        $fileContent = file_get_contents($filePath);
        $defaultValues = json_decode($fileContent, true) ?? [];
        foreach ($defaultValues as $group => $values) {
            foreach ($values as $key => $value) {
                $this->appSettings->set($group, $key, $value);
            }
        }

        $this->appSettings->save();
    }

    /**
     * Save company default address.
     *
     * @param string $codpais
     */
    private function saveAddress(string $codpais)
    {
        $this->empresa->ciudad = $this->request->request->get('ciudad', '');
        $this->empresa->codpais = $codpais;
        $this->empresa->codpostal = $this->request->request->get('codpostal', '');
        $this->empresa->direccion = $this->request->request->get('direccion', '');
        $this->empresa->nombre = $this->empresa->nombrecorto = $this->request->request->get('empresa', '');
        $this->empresa->provincia = $this->request->request->get('provincia', '');
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
            $almacen->nombre = $this->empresa->nombre;
            $almacen->provincia = $this->empresa->provincia;
            $almacen->save();

            $this->appSettings->set('default', 'codalmacen', $almacen->codalmacen);
            $this->appSettings->set('default', 'idempresa', $this->empresa->idempresa);
            $this->appSettings->save();
            return;
        }

        /// no assigned warehouse? Create a new one
        $almacen = new Model\Almacen();
        $almacen->ciudad = $this->empresa->ciudad;
        $almacen->codpais = $codpais;
        $almacen->codpostal = $this->empresa->codpostal;
        $almacen->direccion = $this->empresa->direccion;
        $almacen->idempresa = $this->empresa->idempresa;
        $almacen->nombre = $this->empresa->nombre;
        $almacen->provincia = $this->empresa->provincia;
        $almacen->save();

        $this->appSettings->set('default', 'codalmacen', $almacen->codalmacen);
        $this->appSettings->set('default', 'idempresa', $this->empresa->idempresa);
        $this->appSettings->save();
    }

    /**
     * 
     * @param string $email
     *
     * @return bool
     */
    private function saveEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->miniLog->warning($this->i18n->trans('not-valid-email', ['%email%' => $email]));
            return false;
        }

        $this->user->email = $email;
        return $this->user->save();
    }

    /**
     * Save the new password if data is admin admin
     *
     * @return bool Returns true if success, otherwise return false.
     */
    private function saveNewPassword(string $pass): bool
    {
        if ($pass === '') {
            return true;
        }

        $repeatPass = $this->request->request->get('repassword', '');
        if ($pass !== $repeatPass) {
            $this->miniLog->warning($this->i18n->trans('different-passwords', ['%userNick%' => $this->user->nick]));
            return false;
        }

        $this->user->setPassword($pass);
        return $this->user->save();
    }

    /**
     * 
     * @param string $codpais
     */
    private function saveStep1(string $codpais)
    {
        $this->preSetAppSettings($codpais);
        $this->appSettings->set('default', 'codpais', $codpais);
        $this->appSettings->set('default', 'homepage', 'AdminPlugins');
        $this->appSettings->save();
        $this->initModels();
        $this->saveAddress($codpais);

        /// change user homepage
        $this->user->homepage = 'AdminPlugins';
        $this->user->save();

        /// change default log values to enabled
        $this->enableLogs();

        /// add the default role for agents
        $this->addDefaultRoleAccess();

        /// clear routes
        $appRouter = new AppRouter();
        $appRouter->clear();

        /// redir to EditSettings
        $this->response->headers->set('Refresh', '0; EditSettings');
    }
}
