<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Export;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Dinamic\Lib\Export\PDFExport as ParentClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of MAILExport
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MAILExport extends ParentClass
{

    /**
     *
     * @var array
     */
    protected $sendParams = [];

    /**
     * 
     * @param BusinessDocument $model
     *
     * @return bool
     */
    public function addBusinessDocPage($model): bool
    {
        $this->sendParams['modelClassName'] = $model->modelClassName();
        $this->sendParams['modelCode'] = $model->primaryColumnValue();
        return parent::addBusinessDocPage($model);
    }

    /**
     * 
     * @param ModelClass $model
     * @param array      $columns
     * @param string     $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $this->sendParams['modelClassName'] = $model->modelClassName();
        $this->sendParams['modelCode'] = $model->primaryColumnValue();
        return parent::addModelPage($model, $columns, $title);
    }

    /**
     * 
     * @param Response $response
     */
    public function show(Response &$response)
    {
        $fileName = $this->getFileName() . '_mail_' . time() . '.pdf';
        $filePath = \FS_FOLDER . '/MyFiles/' . $fileName;
        if (false === \file_put_contents($filePath, $this->getDoc())) {
            $this->toolBox()->i18nLog()->error('folder-not-writable');
            return;
        }

        $this->sendParams['fileName'] = $fileName;
        $response->headers->set('Refresh', '0; SendMail?' . \http_build_query($this->sendParams));
    }
}
