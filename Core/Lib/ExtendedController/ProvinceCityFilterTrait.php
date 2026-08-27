<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Provincia;

/**
 * Trait que implementa el autocompletado en cascada de los filtros
 * "provincias" y "ciudades": las provincias se filtran por el país
 * seleccionado (filtercountry) y las ciudades por la provincia
 * seleccionada (filterprovincia).
 *
 * La clase que use este trait debe ser un controlador con el método
 * requestGet(), las propiedades codeModel y request, y un
 * autocompleteAction() del padre al que recurrir si el origen no
 * coincide con "provincias" ni "ciudades".
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait ProvinceCityFilterTrait
{
    protected function autocompleteAction(): array
    {
        $data = $this->requestGet(['source', 'fieldcode', 'fieldtitle', 'strict', 'term']);
        switch ($data['source']) {
            case 'provincias':
                return $this->autocompleteProvince($data);

            case 'ciudades':
                return $this->autocompleteCity($data);
        }

        return parent::autocompleteAction();
    }

    private function autocompleteCity(array $data): array
    {
        $where = [];
        $codprovincia = $this->request->input('filterprovincia');
        if (false === empty($codprovincia)) {
            $provincias = Provincia::allWhereEq('provincia', $codprovincia);
            if (empty($provincias)) {
                return [];
            }

            $where[] = Where::eq('idprovincia', $provincias[0]->idprovincia);
        }

        $result = [];
        foreach ($this->codeModel->search('ciudades', $data['fieldcode'], $data['fieldtitle'], $data['term'], $where) as $value) {
            $result[] = ['key' => $value->code, 'value' => $value->description];
        }

        return $result;
    }

    private function autocompleteProvince(array $data): array
    {
        $where = [];
        $codpais = $this->request->input('filtercountry');
        if (false === empty($codpais)) {
            $where[] = Where::eq('codpais', $codpais);
        }

        $result = [];
        foreach ($this->codeModel->search('provincias', $data['fieldcode'], $data['fieldtitle'], $data['term'], $where) as $value) {
            $result[] = ['key' => $value->code, 'value' => $value->description];
        }

        return $result;
    }
}
