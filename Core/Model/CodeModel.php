<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase;

/**
 * Modelo Auxiliar para cargar una lista de códigos y sus descripciones
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class CodeModel
{

    /**
     * Proporciona acceso directo a la base de datos.
     *
     * @var DataBase
     */
    private static $dataBase;

    /**
     * Valor del campo código del modelo leido
     *
     * @var string
     */
    public $code;

    /**
     * Valor del campo descripción del modelo leido
     *
     * @var string
     */
    public $description;

    /**
     * Constructor e inicializador de la clase
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->code = $data['code'];
        $this->description = $data['description'];
    }

    /**
     * Carga una lista de CodeModel (código y descripción) para la tabla indicada
     *
     * @param string  $tableName
     * @param string  $fieldCode
     * @param string  $fieldDescription
     * @param bool $addEmpty
     *
     * @return self[]
     */
    public static function all($tableName, $fieldCode, $fieldDescription, $addEmpty = false)
    {
        $result = [];

        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }

        if (self::$dataBase->tableExists($tableName)) {
            if ($addEmpty) {
                $result[] = new self(['code' => null, 'description' => '']);
            }

            $sql = 'SELECT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description FROM ' . $tableName . ' ORDER BY 2 ASC';
            $data = self::$dataBase->select($sql);
            if (!empty($data)) {
                foreach ($data as $d) {
                    $result[] = new self($d);
                }
            }
        }

        return $result;
    }
}
