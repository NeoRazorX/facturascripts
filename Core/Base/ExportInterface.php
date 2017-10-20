<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base;

use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ExportInterface
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
interface ExportInterface
{
    /**
     * Asigna la cabecera
     *
     * @param Response $response
     *
     * @return mixed
     */
    public function setHeaders(&$response);

    /**
     * Nuevo documento
     *
     * @param $model
     *
     * @return mixed
     */
    public function newDoc($model);

    /**
     * Nueva lista de documentos
     *
     * @param $model
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param array $columns
     *
     * @return mixed
     */
    public function newListDoc($model, $where, $order, $offset, $columns);
}
