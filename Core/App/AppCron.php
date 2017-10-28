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

namespace FacturaScripts\Core\App;

/**
 * Description of App
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppCron extends App
{
    /**
     * Ejecuta el cron.
     *
     * @return boolean
     */
    public function run()
    {
        $this->response->headers->set('Content-Type', 'text/plain');
        if ($this->dataBase->connected()) {
            /// implementar aquí
            /// devolvemos true, para los test
            return true;
        }

        $this->response->setContent('DB-ERROR');

        return false;
    }
}
