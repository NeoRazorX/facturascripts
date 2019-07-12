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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\ModelClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Export interface.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
interface ExportInterface
{

    /**
     * Adds a new page with the document data.
     *
     * @param BusinessDocument $model
     */
    public function generateBusinessDocPage($model);

    /**
     * Adds a new page with a table listing the models data.
     *
     * @param ModelClass      $model
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param array           $columns
     * @param string          $title
     */
    public function generateListModelPage($model, $where, $order, $offset, $columns, $title = '');

    /**
     * Adds a new page with the model data.
     *
     * @param ModelClass $model
     * @param array      $columns
     * @param string     $title
     */
    public function generateModelPage($model, $columns, $title = '');

    /**
     * Adds a new page with the table.
     *
     * @param array $headers
     * @param array $rows
     */
    public function generateTablePage($headers, $rows);

    /**
     * Return the full document.
     *
     * @return mixed
     */
    public function getDoc();

    /**
     * Blank document.
     */
    public function newDoc();

    /**
     * Set headers and output document content to response.
     *
     * @param Response $response
     */
    public function show(Response &$response);
}
