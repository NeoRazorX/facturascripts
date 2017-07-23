<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

use FacturaScripts\Core\Base\Model;

/**
 * Una familia de artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Familia
{
    use Model;

    /**
     * Clave primaria.
     * @var string
     */
    public $codfamilia;
    /**
     * Descripción de la fanília
     * @var string
     */
    public $descripcion;

    /**
     * Código de la familia madre.
     * @var string
     */
    public $madre;
    /**
     * Nivel
     * @var string
     */
    public $nivel;

    /**
     * Familia constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'familias', 'codfamilia');
        $this->clear();
        if (is_array($data) && !empty($data)) {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codfamilia = null;
        $this->descripcion = '';
        $this->madre = null;
        $this->nivel = '';
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->codfamilia === null) {
            return 'index.php?page=VentasFamilias';
        }
        return 'index.php?page=VentasFamilia&cod=' . urlencode($this->codfamilia);
    }

    /**
     * Devuelve la descripción, acortada a len
     *
     * @param int $len
     *
     * @return string
     */
    public function getDescripcion($len = 12)
    {
        if (mb_strlen($this->descripcion) > $len) {
            return substr($this->descripcion, 0, $len) . '...';
        }
        return $this->descripcion;
    }

    /**
     * Devuelve si es la família por defecto
     * @deprecated since version 50
     * @return bool
     */
    public function isDefault()
    {
        return false;
    }

    /**
     * Devuelve todos los artículos de la família
     *
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function getArticulos($offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $articulo = new Articulo();
        return $articulo->allFromFamilia($this->codfamilia, $offset, $limit);
    }

    /**
     * Comprueba los datos de la familia, devuelve TRUE si son correctos
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codfamilia = static::noHtml($this->codfamilia);
        $this->descripcion = static::noHtml($this->descripcion);

        if (empty($this->codfamilia) || strlen($this->codfamilia) > 8) {
            $this->miniLog->alert('Código de familia no válido. Deben ser entre 1 y 8 caracteres.');
        } elseif (empty($this->descripcion) || strlen($this->descripcion) > 100) {
            $this->miniLog->alert('Descripción de familia no válida.');
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Elimina la familia de la base de datos
     * @return bool
     */
    public function delete()
    {
        $this->cleanCache();
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE codfamilia = ' . $this->var2str($this->codfamilia) . ';'
            . 'UPDATE ' . $this->tableName() . ' SET madre = ' . $this->var2str($this->madre)
            . ' WHERE madre = ' . $this->var2str($this->codfamilia) . ';';

        return $this->database->exec($sql);
    }

    /**
     * Devuelve un array con todas las familias
     * @return array
     */
    public function all()
    {
        /// lee la lista de la caché
        $famlist = $this->cache->get('m_familia_all');
        if (!$famlist) {
            /// si la lista no está en caché, leemos de la base de datos
            $sql = 'SELECT * FROM ' . $this->tableName() . ' ORDER BY lower(descripcion) ASC;';
            $data = $this->database->select($sql);
            if (!empty($data)) {
                foreach ($data as $d) {
                    if ($d['madre'] === null) {
                        $famlist[] = new Familia($d);
                        foreach ($this->auxAll($data, $d['codfamilia'], '· ') as $value) {
                            $famlist[] = new Familia($value);
                        }
                    }
                }
            }

            /// guardamos la lista en caché
            $this->cache->set('m_familia_all', $famlist);
        }

        return $famlist;
    }

    /**
     * TODO
     * @return array
     */
    public function madres()
    {
        $famlist = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE madre IS NULL ORDER BY lower(descripcion) ASC;';
        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $famlist[] = new Familia($d);
            }
        }

        if (empty($famlist)) {
            /// si la lista está vacía, ponemos madre a NULL en todas por si el usuario ha estado jugando
            $sql = 'UPDATE ' . $this->tableName() . ' SET madre = NULL;';
            $this->database->exec($sql);
        }

        return $famlist;
    }

    /**
     * TODO
     *
     * @param string $codmadre
     *
     * @return array
     */
    public function hijas($codmadre = false)
    {
        $famlist = [];

        if (!empty($codmadre)) {
            $codmadre = $this->codfamilia;
        }

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE madre = ' . $this->var2str($codmadre) . ' ORDER BY descripcion ASC;';
        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $famlist[] = new Familia($d);
            }
        }

        return $famlist;
    }

    /**
     * TODO
     *
     * @param string $query
     *
     * @return array
     */
    public function search($query)
    {
        $famlist = [];
        $query = static::noHtml(mb_strtolower($query, 'UTF8'));

        $sql = 'SELECT * FROM ' . $this->tableName()
            . " WHERE lower(descripcion) LIKE '%" . $query . "%' ORDER BY descripcion ASC;";
        $familias = $this->database->select($sql);
        if (!empty($familias)) {
            foreach ($familias as $f) {
                $famlist[] = new Familia($f);
            }
        }

        return $famlist;
    }

    /**
     * Aplicamos correcciones a la tabla.
     */
    public function fixDb()
    {
        /// comprobamos que las familias con madre, su madre exista.
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE madre IS NOT NULL;';
        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $fam = $this->get($d['madre']);
                if (!$fam) {
                    /// si no existe, desvinculamos
                    $sql = 'UPDATE ' . $this->tableName() . ' SET madre = null WHERE codfamilia = '
                        . $this->var2str($d['codfamilia']) . ':';
                    $this->database->exec($sql);
                }
            }
        }
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    private function install()
    {
        $this->cleanCache();
        return 'INSERT INTO ' . $this->tableName() . " (codfamilia,descripcion) VALUES ('VARI','VARIOS');";
    }

    /**
     * Limpia la caché
     */
    private function cleanCache()
    {
        $this->cache->delete('m_familia_all');
    }

    /**
     * Completa los datos de la lista de familias con el nivel
     *
     * @param array $familias
     * @param string $madre
     * @param string $nivel
     *
     * @return array
     */
    private function auxAll(&$familias, $madre, $nivel)
    {
        $subfamilias = [];

        foreach ($familias as $fam) {
            if ($fam['madre'] === $madre) {
                $fam['nivel'] = $nivel;
                $subfamilias[] = $fam;
                foreach ($this->auxAll($familias, $fam['codfamilia'], '&nbsp;&nbsp;' . $nivel) as $value) {
                    $subfamilias[] = $value;
                }
            }
        }

        return $subfamilias;
    }
}
