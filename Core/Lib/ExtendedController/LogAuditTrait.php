<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

trait LogAuditTrait
{
    public function createViewLogAudit(string $viewName = 'ListLogMessage')
    {
        $this->addListView($viewName, 'LogMessage', 'history', 'fas fa-history');
        $this->views[$viewName]->addOrderBy(['time'], 'date', 2);
        $this->views[$viewName]->addSearchFields(['context', 'message']);

        // disable columns
        $this->views[$viewName]->disableColumn('channel');
        $this->views[$viewName]->disableColumn('url');

        // disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    public function loadDataLogAudit($view, $model, $modelid)
    {
        $where = [
            new DataBaseWhere('model', $model),
            new DataBaseWhere('modelcode', $modelid)
        ];
        $view->loadData('', $where);
    }
}
