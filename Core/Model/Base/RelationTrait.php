<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of RelationTrait
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
trait RelationTrait
{
    /**
     * @param array $fields
     * @param object $model1
     * @param string $model2
     * @param string $modelDest
     * @param string $id1
     * @param string $id2
     * @return bool
     */
    public static function hasMany(array $fields, object $model1, string $model2, string $modelDest, string $id1 = '', string $id2 = ''): bool
    {
        $modelClass2 = '\\FacturaScripts\\Dinamic\\Model\\' . $model2;
        if (class_exists($modelClass2) === false) {
            return false;
        }

        $modelClassDest = '\\FacturaScripts\\Dinamic\\Model\\' . $modelDest;
        if (class_exists($modelClassDest) === false) {
            return false;
        }

        $dataBase = new DataBase();
        $dataBase->beginTransaction();

        $model2 = new $modelClass2();
        $modelDest = new $modelClassDest();

        $id1 = empty($id1) ? $model1->primaryColumn() : $id1;
        $id2 = empty($id2) ? $model2->primaryColumn() : $id2;

        $found = true;
        foreach ($fields as $field) {
            if (isset($model1->{$field}) === false) {
                $found = false;
            }
        }

        if ($found === false) {
            return false;
        }

        $where = [new DataBaseWhere($id1, $model1->{$id1})];
        foreach ($modelDest->all($where, [], 0, 0) as $md) {
            if ($md->delete() === false) {
                $dataBase->rollback();
                return false;
            }
        }

        foreach ($fields as $field) {
            foreach ($model1->{$field} as $v) {
                $modelDest->clear();
                $modelDest->{$id1} = $model1->{$id1};
                $modelDest->{$id2} = $v;
                if ($modelDest->save() === false) {
                    $dataBase->rollback();
                    return false;
                }
            }
        }

        $dataBase->commit();
        return true;
    }
}