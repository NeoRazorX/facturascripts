<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of MAILExport
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MAILExport extends PDFExport
{

    /**
     *
     * @var array
     */
    protected $sendParams = [];

    /**
     * 
     * @param BusinessDocument $model
     */
    public function generateBusinessDocPage($model)
    {
        parent::generateBusinessDocPage($model);
        $this->sendParams['modelClassName'] = $model->modelClassName();
        $this->sendParams['modelCode'] = $model->primaryColumnValue();
    }

    /**
     * 
     * @return mixed
     */
    public function getDoc()
    {
        if ($this->pdf === null) {
            $this->newPage();
            $this->pdf->ezText('');
        }

        return $this->pdf->ezOutput();
    }

    /**
     * 
     * @param Response $response
     */
    public function show(Response &$response)
    {
        $fileName = 'Mail_' . time() . '.pdf';
        $filePath = \FS_FOLDER . '/MyFiles/' . $fileName;
        file_put_contents($filePath, $this->getDoc());

        $this->sendParams['fileName'] = $fileName;
        $response->headers->set('Refresh', '0; SendMail?' . http_build_query($this->sendParams));
    }
}
