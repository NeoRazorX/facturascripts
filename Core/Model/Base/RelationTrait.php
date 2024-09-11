<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    public static function hasMany(array $fields, ModelClass $model1, string $model2, string $modelDest, string $id1 = '', string $id2 = ''): bool
    {
        $modelClass2 = '\\FacturaScripts\\Dinamic\\Model\\' . $model2;
        if (false === class_exists($modelClass2)) {
            return false;
        }

        $modelClassDest = '\\FacturaScripts\\Dinamic\\Model\\' . $modelDest;
        if (false === class_exists($modelClassDest)) {
            return false;
        }

        $dataBase = new DataBase();
        $newTransaction = $dataBase->beginTransaction();
        if (false === $newTransaction) {
            $newTransaction = true;
            $dataBase->beginTransaction();
        }

        $model2 = new $modelClass2();
        $modelDest = new $modelClassDest();

        $id1 = false === empty($id1) && property_exists($model1, $id1) ? $id1 : $model1->primaryColumn();
        $id2 = false === empty($id2) && property_exists($model2, $id2) ? $id2 : $model2->primaryColumn();

        $found = true;
        foreach ($fields as $field) {
            if (false === property_exists($model1, $field)) {
                $found = false;
            }
        }

        if (false === $found) {
            return false;
        }

        $where = [new DataBaseWhere($id1, $model1->{$id1})];
        foreach ($modelDest->all($where, [], 0, 0) as $md) {
            if (false === $md->delete()) {
                if ($newTransaction) {
                    $dataBase->rollback();
                }
                return false;
            }
        }

        foreach ($fields as $field) {
            foreach ($model1->{$field} as $v) {
                $modelDest->clear();
                $modelDest->{$id1} = $model1->{$id1};
                $modelDest->{$id2} = $v;
                if ($modelDest->save() === false) {
                    if ($newTransaction) {
                        $dataBase->rollback();
                    }
                    return false;
                }
            }
        }

        if ($newTransaction) {
            $dataBase->commit();
        }

        return true;
    }
}