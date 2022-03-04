<?php

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

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
        foreach ($modelDest->all($where) as $md) {
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