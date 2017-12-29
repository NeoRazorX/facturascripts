<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Lib\Dashboard as DashboardLib;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to edit a single item from the DashboardCard model
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class EditDashboardData extends ExtendedController\EditController
{
    
    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->validateProperties();
        $this->validateColumns();
    }

    /**
     * Return the propierties fields.
     *
     * @return mixed
     */
    private function getPropertiesFields()
    {
        $model = $this->view->getModel();
        $component = 'FacturaScripts\\Core\\Lib\\Dashboard\\'
            . $model->component
            . DashboardLib\BaseComponent::SUFIX_COMPONENTS;

        return $component::getPropertiesFields();
    }

    /**
     * Validate propierties columns.
     */
    private function validateColumns()
    {
        $fields = array_keys($this->view->getModel()->properties);
        $group = $this->view->getColumns()['options']->columns;
        foreach ($group as $column) {
            if (in_array($column->widget->fieldName, $fields, false)) {
                continue;
            }

            $column->display = 'none';
        }
    }

    /**
     * Validate propierties fields.
     */
    private function validateProperties()
    {
        $model = $this->view->getModel();
        $properties = $this->getPropertiesFields();
        foreach ($properties as $key => $value) {
            if (!isset($model->properties[$key])) {
                $model->properties[$key] = $value;
            }
        }
    }

    /**
     * Run the data edits
     *
     * @return bool
     */
    protected function editAction()
    {
        $model = $this->view->getModel();
        $properties = array_keys($this->getPropertiesFields());
        $fields = array_keys($model->properties);
        foreach ($fields as $key) {
            if (!in_array($key, $properties, false)) {
                unset($model->properties[$key]);
            }
        }
        return parent::editAction();
    }

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return '\FacturaScripts\Dinamic\Model\DashboardData';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'dashboard-card';
        $pagedata['menu'] = 'reports';
        $pagedata['icon'] = 'fa-dashboard';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Returns the configuration property value for a specified $field
     *
     * @param mixed $model
     * @param string $field
     * @return mixed
     */
    public function getFieldValue($model, $field)
    {
        $value = parent::getFieldValue($model, $field);
        if (isset($value)) {
            return $value;
        }

        if (is_array($model->properties) && array_key_exists($field, $model->properties)) {
            return $model->properties[$field];
        }

        return null;
    }
}
