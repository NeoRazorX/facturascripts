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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Model\Base\BusinessDocumentLine;

/**
 * Lógica común para los controladores de la API que crean o editan documentos
 * de negocio (ApiCreateDocument, ApiEditDocument).
 */
trait ApiBusinessDocumentTrait
{
    /**
     * Asigna a la línea los campos recibidos en el array de datos.
     * Si un campo no viene en los datos se conserva el valor actual de la línea,
     * salvo la cantidad, que en una línea nueva ($isNew) toma 1 por defecto.
     *
     * @param BusinessDocumentLine $line
     * @param array $data
     * @param bool $isNew
     */
    protected function applyLineFields(BusinessDocumentLine &$line, array $data, bool $isNew): void
    {
        $line->cantidad = (float)($data['cantidad'] ?? ($isNew ? 1 : $line->cantidad));
        $line->descripcion = $data['descripcion'] ?? $line->descripcion ?? '?';
        $line->pvpunitario = (float)($data['pvpunitario'] ?? $line->pvpunitario);
        $line->dtopor = (float)($data['dtopor'] ?? $line->dtopor);
        $line->dtopor2 = (float)($data['dtopor2'] ?? $line->dtopor2);

        if (isset($data['excepcioniva'])) {
            $line->excepcioniva = $data['excepcioniva'] === 'null' ? null : $data['excepcioniva'];
        }

        if (isset($data['codimpuesto'])) {
            $newCodimpuesto = $data['codimpuesto'] === 'null' ? null : $data['codimpuesto'];
            if ($newCodimpuesto !== $line->codimpuesto) {
                $line->setTax($newCodimpuesto);
            }
        }

        if (array_key_exists('suplido', $data)) {
            $line->suplido = $this->toBool($data['suplido']);
        }

        if (array_key_exists('mostrar_cantidad', $data)) {
            $line->mostrar_cantidad = $this->toBool($data['mostrar_cantidad']);
        }

        if (array_key_exists('mostrar_precio', $data)) {
            $line->mostrar_precio = $this->toBool($data['mostrar_precio']);
        }

        if (array_key_exists('salto_pagina', $data)) {
            $line->salto_pagina = $this->toBool($data['salto_pagina']);
        }
    }

    /**
     * Convierte un valor a booleano.
     * Acepta: true, false, 1, 0, "1", "0", "true", "false"
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool)$value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            return in_array($lower, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool)$value;
    }
}
