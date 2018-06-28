<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['showonmenu'] = false;
        $pageData['icon'] = 'fa-magic';

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

        $coddivisa = $this->request->request->get('coddivisa', '');
        $codpais = $this->request->request->get('codpais', '');
        if ($codpais !== '') {
            $appSettings = new AppSettings();
            $appSettings->set('default', 'coddivisa', $coddivisa);
            $appSettings->set('default', 'codpais', $codpais);
            $appSettings->set('default', 'homepage', 'AdminPlugins');
            $appSettings->save();
            $this->initModels();
            $this->saveAddress($appSettings, $codpais);

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

    /**
     * Initialize required models.
     */
    private function initModels()
    {
        new Model\FormaPago();
        new Model\Impuesto();
        new Model\Serie();

        $pluginManager = new PluginManager();
        $pluginManager->deploy(true, true);
    }

    /**
     * Save company default address.
     *
     * @param AppSettings $appSettings
     * @param string      $codpais
     */
    private function saveAddress(&$appSettings, $codpais)
    {
        $this->empresa->codpais = $codpais;
        $this->empresa->provincia = $this->request->request->get('provincia');
        $this->empresa->ciudad = $this->request->request->get('ciudad');
        $this->empresa->save();

        $almacenModel = new Model\Almacen();
        foreach ($almacenModel->all() as $almacen) {
            $almacen->codpais = $codpais;
            $almacen->provincia = $this->empresa->provincia;
            $almacen->ciudad = $this->empresa->ciudad;
            $almacen->save();
                 
            $appSettings->set('default', 'codalmacen', $almacen->codalmacen);
            $appSettings->save();
            break;
        }
    }

    /**
     * Enable all logs by default.
     */
    private function enableLogs()
    {
        $types = ['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        $appSettings = new AppSettings();
        foreach ($types as $type) {
            $appSettings->set('log', $type, 'true');
        }
        $appSettings->save();
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
            return $this->insertDefaultPages($role->codrole);
        }

        $role->codrole = \mb_strtolower($this->i18n->trans('agents'));
        $role->descripcion = $this->i18n->trans('agents');
        if ($role->save()) {
            return $this->insertDefaultPages($role->codrole);
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
    private function insertDefaultPages($codrole): bool
    {
        $this->dataBase->beginTransaction();
        try {
            $page = new Model\Page();
            /// All pages not in admin menu and not yet enabled
            $inSQL = "SELECT name FROM pages WHERE menu <> 'admin' AND name NOT IN ("
                . 'SELECT pagename FROM roles_access WHERE codrole = ' . $this->dataBase->var2str($codrole)
                . ')';
            $where = [new DataBaseWhere('name', $inSQL, 'IN')];
            $pages = $page->all($where, [], 0, 0);
            // add Pages to Rol
            if (!Model\RoleAccess::addPagesToRole($codrole, $pages)) {
                throw new \Exception($this->i18n->trans('cancel-process'));
            }
            $this->dataBase->commit();
            return true;
        } catch (\Exception $e) {
            $this->dataBase->rollback();
            $this->miniLog->notice($e->getMessage());
            return false;
        }
    }
}
