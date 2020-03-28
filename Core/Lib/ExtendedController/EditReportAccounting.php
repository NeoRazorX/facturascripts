<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\ReportAccounting;

/**
 * Class base for accounting reports
 *
 * @author Jose Antonio Cuello <jcuello@artextrading.com>
 */
abstract class EditReportAccounting extends EditController
{

    /**
     * Generate data for report
     *
     * @param ReportAccounting $model
     */
    abstract protected function generateReport($model);

    protected function exportAction()
    {
        $model = $this->getModel();
        $pages = $this->generateReport($model);
        if (empty($pages)) {
            $this->toolBox()->i18nLog()->warning('no-data');
            return;
        }

        $format = $this->request->get('option', 'PDF');
        $title = $this->getTitle() . ' - ' . $model->name;

        $this->setTemplate(false);
        $this->exportData($pages, $title, $format);
    }

    /**
     * Exports data to indicated format.
     *
     * @param array  $pages
     * @param string $title
     * @param string $format
     */
    protected function exportData(&$pages, $title, $format)
    {
        $mainViewName = $this->getMainViewName();
        $view = $this->views[$mainViewName];

        $this->exportManager->newDoc($format, $title);
        $this->exportManager->addModelPage($view->model, $view->getColumns(), $view->title);

        foreach ($pages as $data) {
            $headers = empty($data) ? [] : array_keys($data[0]);
            $this->exportManager->addTablePage($headers, $data);
        }

        $this->exportManager->show($this->response);
    }

    /**
     * Get Title for report
     *
     * @return string
     */
    protected function getTitle(): string
    {
        return $this->toolBox()->i18n()->trans($this->getPageData()['title']);
    }
}
