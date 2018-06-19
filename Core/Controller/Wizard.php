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
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of Wizard
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
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

        //Show message if user and password are admin
        if ($this->user->verifyPassword('admin')) {
            $this->miniLog->warning($this->i18n->trans('you-should-change-the-default-user-and-password'));
        }

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

            /// clear routes
            $appRouter = new AppRouter();
            $appRouter->clear();

            // If password is empty don't update and use default password, check and updates the new password
            if (empty($this->request->request->get('password', '')) || $this->checkPassword()) {
                /// redir to EditSettings
                $this->response->headers->set('Refresh', '0; EditSettings');
            }
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
     * Save the new password if data is admin admin
     *
     * @return bool Returns true if success, otherwise return false.
     */
    private function checkPassword() : bool
    {
        $pass = $this->request->request->get('password', '');
        $repeatPass = $this->request->request->get('repassword', '');

        if ($pass !== $repeatPass) {
            $this->miniLog->warning($this->i18n->trans('pass-not-match'));
            return false;
        }

        $this->user->setPassword($pass);
        if (!$this->user->save()) {
            $this->miniLog->warning($this->i18n->trans('pass-not-updated'));
            return false;
        }

        return true;
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
}
