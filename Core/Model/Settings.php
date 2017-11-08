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
namespace FacturaScripts\Core\Model;

/**
 * Description of Settings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Settings
{

    use Base\ModelTrait {
        clear as clearTrait;
        loadFromData as loadFromDataTrait;
    }

    /**
     * Identificador del grupo de valores
     *
     * @var string
     */
    public $name;

    /**
     * Descripción del contenido y valor del grupo
     *
     * @var string
     */
    public $description;

    /**
     * Icono a visualizar
     *
     * @var string
     */
    public $icon;

    /**
     * Conjunto de valores de configuración
     *
     * @var array
     */
    public $properties;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'fs_settings';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'name';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearTrait();
        $this->properties = [];
    }

    /**
     * Comprueba un array de datos para que tenga la estructura correcta del modelo
     *
     * @param array $data
     */
    public function checkArrayData(&$data)
    {
        $properties = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, ['name', 'action', 'active'])) {
                $properties[$key] = $value;
                unset($data[$key]);
            }
        }
        $data['properties'] = json_encode($properties);
        unset($properties);
    }

    /**
     * Carga los datos desde un array
     *
     * @param array $data
     */
    public function loadFromData($data)
    {
        $this->loadFromDataTrait($data, ['properties', 'action']);
        $this->properties = isset($data['properties']) ? json_decode($data['properties'], true) : [];
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function saveUpdate()
    {
        $properties = json_encode($this->properties);

        $sql = 'UPDATE ' . $this->tableName() . ' SET '
            . ' properties = ' . $this->var2str($properties)
            . ' WHERE ' . $this->primaryColumn() . ' = ' . $this->var2str($this->name) . ';';

        return $this->dataBase->exec($sql);
    }

    /**
     * Crea la consulta necesaria para crear configuración base en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        $description1 = $this->i18n->trans('description-general-settings');
        $values1 = [
            'decimals' => 2, 'product_decimals' => 2, 'decimal_separator' => ',', 'thousands_separator' => '.',
            'dateshort' => 'dd-mm-yy', 'datelong' => 'dd mmm yyyy', 'datemaxtoday' => 3
        ];
        $properties1 = json_encode($values1);

        $sqlBase = 'INSERT INTO ' . $this->tableName() . ' (name, icon, description, properties) VALUES ';
        $sql1 = $sqlBase . "('default', 'fa-globe', " . $this->var2str($description1) . ',' . $this->var2str($properties1) . ');';

        $description2 = $this->i18n->trans('description-pdf-template-settings');
        $values2 = [
            'ppdf_plantilla' => '2', 'ppdf_pcolor' => '#1296D7', 'ppdf_scolor' => '#FFFFFF', 'ppdf_tcolor' => '#F1F1F1',
            'ppdf_fsize' => 9, 'ppdf_font' => 'dejavusans', 'ppdf_margin_top' => 0, 'ppdf_mostrar_empresa' => 'h1',
            'ppdf_numero2' => '1', 'ppdf_multidivisa' => '0', 'ppdf_referencias' => '1', 'ppdf_descuentos' => '1',
            'ppdf_numlinea' => '0', 'ppdf_lf_alb' => '0', 'ppdf_lf_ped' => '0', 'ppdf_pie_f_y' => 270, 'ppdf_lalb_ped' => '0',
            'ppdf_pie_alb' => '', 'ppdf_pie_alb_y' => 270, 'ppdf_lped_pre' => '0', 'ppdf_pie_ped' => '', 'ppdf_pie_ped_y' => 270,
            'ppdf_lpre_wooc' => '0', 'ppdf_pie_pre' => '', 'ppdf_pie_pre_y' => 270
        ];
        $properties2 = json_encode($values2);

        $sql2 = $sqlBase . "('plantillaspdf', 'fa-file-pdf-o', " . $this->var2str($description2) . ',' . $this->var2str($properties2) . ');';
        return $sql1 . $sql2;
    }
}
