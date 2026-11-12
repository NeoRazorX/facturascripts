<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Lib\Import\CSVImport;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

class CodigoPostal extends ModelClass
{
    use ModelTrait;

    /** @var string Código del país al que pertenece el código postal. */
    public $codpais;

    /** @var string Fecha y hora de creación del código postal. */
    public $creation_date;

    /** @var int Identificador único del código postal. */
    public $id;

    /** @var int Identificador de la ciudad asociada. */
    public $idciudad;

    /** @var int Identificador de la provincia asociada. */
    public $idprovincia;

    /** @var string Nombre del último usuario que modificó el código postal. */
    public $last_nick;

    /** @var string Fecha y hora de la última modificación. */
    public $last_update;

    /** @var string Nombre del usuario que creó el código postal. */
    public $nick;

    /** @var string Número del código postal. Es texto para conservar los ceros a la izquierda y admitir letras. */
    public $number;

    public function clear(): void
    {
        parent::clear();
        $this->codpais = Tools::settings('default', 'codpais', 'ESP');
    }

    /**
     * Devuelve las ciudades, con su provincia, que tienen asignado el código postal indicado.
     *
     * @param string $number código postal a buscar
     * @param string $codpais país en el que buscar; vacío para buscar en todos
     *
     * @return array lista de ['ciudad' => string, 'provincia' => string], ordenada por ciudad
     */
    public static function findPlaces(string $number, string $codpais = ''): array
    {
        $number = trim($number);
        if ('' === $number) {
            return [];
        }

        $where = [Where::eq('number', $number)];
        if ('' !== $codpais) {
            $where[] = Where::eq('codpais', $codpais);
        }

        $places = [];
        foreach (static::all($where, [], 0, 50) as $zipCode) {
            $city = Ciudad::find($zipCode->idciudad);
            $province = Provincia::find($zipCode->idprovincia);
            if (null === $city || null === $province) {
                continue;
            }

            $places[] = [
                'ciudad' => (string)Tools::fixHtml($city->ciudad),
                'provincia' => (string)Tools::fixHtml($province->provincia)
            ];
        }

        usort($places, function (array $a, array $b): int {
            return strcmp($a['ciudad'], $b['ciudad']);
        });

        return $places;
    }

    public function install(): string
    {
        // needed dependencies
        new Ciudad();

        return parent::install();
    }

    /**
     * Añade los códigos postales del archivo de datos del país predeterminado que todavía
     * no existen. No modifica ni elimina los existentes. Se descartan los códigos cuya
     * ciudad no existe o no pertenece a la provincia indicada en el archivo, porque en esa
     * instalación los identificadores de las ciudades no coinciden con los del archivo.
     *
     * @return array|null ['added' => int, 'discarded' => int], o null si no hay archivo de datos
     */
    public static function restoreFromDataFile(): ?array
    {
        $filePath = CSVImport::getTableFilePath(static::tableName());
        if (empty($filePath)) {
            return null;
        }

        // provincia de cada ciudad instalada
        $provinces = [];
        $sqlCities = 'SELECT idciudad, idprovincia FROM ' . Ciudad::tableName();
        foreach (self::db()->select($sqlCities) as $row) {
            $provinces[(int)$row['idciudad']] = (int)$row['idprovincia'];
        }

        // países instalados
        $countries = [];
        foreach (Pais::all() as $country) {
            $countries[$country->codpais] = true;
        }

        $values = [];
        $discarded = 0;
        foreach (CSVImport::readFile($filePath)->data as $row) {
            $idciudad = (int)($row['idciudad'] ?? 0);
            $idprovincia = (int)($row['idprovincia'] ?? 0);
            $codpais = $row['codpais'] ?? '';
            $number = trim($row['number'] ?? '');
            if (
                '' === $number
                || false === isset($countries[$codpais])
                || false === isset($provinces[$idciudad])
                || $provinces[$idciudad] !== $idprovincia
            ) {
                $discarded++;
                continue;
            }

            $values[] = '(' . self::db()->var2str($codpais)
                . ',' . self::db()->var2str($idprovincia)
                . ',' . self::db()->var2str($idciudad)
                . ',' . self::db()->var2str($number)
                . ',' . self::db()->var2str(Tools::dateTime()) . ')';
        }

        // insertamos por bloques, ignorando los que ya existen
        $columns = implode(',', array_map(function (string $name): string {
            return self::db()->escapeColumn($name);
        }, ['codpais', 'idprovincia', 'idciudad', 'number', 'creation_date']));
        $isPostgres = 'postgresql' === Tools::config('db_type');
        $insert = ($isPostgres ? 'INSERT INTO ' : 'INSERT IGNORE INTO ') . static::tableName()
            . ' (' . $columns . ') VALUES ';
        $suffix = $isPostgres ? ' ON CONFLICT DO NOTHING;' : ';';

        $before = static::count();
        $newTransaction = false === self::db()->inTransaction() && self::db()->beginTransaction();
        foreach (array_chunk($values, 1000) as $chunk) {
            if (false === self::db()->exec($insert . implode(',', $chunk) . $suffix)) {
                if ($newTransaction) {
                    self::db()->rollback();
                }
                return ['added' => 0, 'discarded' => $discarded + count($values)];
            }
        }
        if ($newTransaction) {
            self::db()->commit();
        }

        return ['added' => static::count() - $before, 'discarded' => $discarded];
    }

    public static function tableName(): string
    {
        return "codigos_postales";
    }

    public function test(): bool
    {
        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;

        // el código postal es texto: quitamos espacios y html, y si queda vacío lo dejamos a null
        $number = Tools::noHtml(trim((string)$this->number));
        $this->number = '' === $number ? null : $number;

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveUpdate(): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();

        return parent::saveUpdate();
    }
}
