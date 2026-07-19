<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\ModelClass;

/**
 * Comprueba si el usuario activo es propietario de un registro, según la
 * restricción onlyOwnerData y las columnas de propiedad (codagente / nick)
 * del modelo. El host debe disponer de $this->user y $this->permissions.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait OwnerDataTrait
{
    /**
     * Returns true if the active user has permission to view the information
     * of the active record in the informed model.
     *
     * @param ModelClass $model
     *
     * @return bool
     */
    protected function checkOwnerData($model): bool
    {
        if (false === $this->permissions->onlyOwnerData || empty($model->id())) {
            return true;
        }

        // si el modelo no tiene ninguna columna de propiedad, permitimos
        if (false === $model->hasColumn('codagente') && false === $model->hasColumn('nick')) {
            return true;
        }

        // criterios de propiedad que el usuario puede cumplir en este modelo
        $checkAgente = $model->hasColumn('codagente') && false === empty($this->user->codagente);
        $checkNick = $model->hasColumn('nick');

        // permitimos si coincide el agente o el nick
        if ($checkAgente && $model->codagente === $this->user->codagente) {
            return true;
        }
        if ($checkNick && $model->nick === $this->user->nick) {
            return true;
        }

        return false;
    }
}
