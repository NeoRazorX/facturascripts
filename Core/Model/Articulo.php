<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2012-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Almacena los datos de un artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Articulo
{

    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * TODO
     * @var array
     */
    private static $impuestos;

    /**
     * TODO
     * @var array
     */
    private static $search_tags;

    /**
     * TODO
     * @var
     */
    private static $cleaned_cache;

    /**
     * TODO
     * @var array
     */
    private static $column_list;

    /**
     * Clave primaria. Varchar (18).
     * @var string
     */
    public $referencia;

    /**
     * Define el tipo de artículo, así se pueden establecer distinciones
     * según un tipo u otro. Varchar (10)
     * @var string
     */
    public $tipo;

    /**
     * Código de la familia a la que pertenece. En la clase familia.
     * @var string
     */
    public $codfamilia;

    /**
     * Descripción del artículo. Tipo text, sin límite de caracteres.
     * @var string
     */
    public $descripcion;

    /**
     * Código del fabricante al que pertenece. En la clase fabricante.
     * @var string
     */
    public $codfabricante;

    /**
     * Precio del artículo, sin impuestos.
     * @var float
     */
    public $pvp;

    /**
     * Almacena el valor del pvp antes de hacer el cambio.
     * Este valor no se almacena en la base de datos, es decir,
     * no se recuerda.
     * @var float
     */
    public $pvp_ant;

    /**
     * Fecha de actualización del pvp.
    * @var string
     */
    public $factualizado;

    /**
     * Coste medio al comprar el artículo. Calculado.
     * @var float
     */
    public $costemedio;

    /**
     * Precio de coste editado manualmente.
     * No necesariamente es el precio de compra, puede incluir
     * también otros costes.
     * @var float
     */
    public $preciocoste;

    /**
     * Impuesto asignado. Clase impuesto.
     * @var string
     */
    public $codimpuesto;

    /**
     * TRUE => el artículos está bloqueado / obsoleto.
     * @var bool
     */
    public $bloqueado;

    /**
     * TRUE => el artículo se compra
     * @var bool
     */
    public $secompra;

    /**
     * TRUE => el artículo se vende
     * @var bool
     */
    public $sevende;

    /**
     * TRUE -> se mostrará sincronizará con la tienda online.
     * @var bool
     */
    public $publico;

    /**
     * Código de equivalencia. Varchar (18).
     * Dos artículos o más son equivalentes si tienen el mismo código de equivalencia.
     * @var string
     */
    public $equivalencia;

    /**
     * Partnumber del producto. Máximo 38 caracteres.
     * @var string
     */
    public $partnumber;

    /**
     * Stock físico. La suma de las cantidades de esta referencia que en la tabla stocks.
     * @var float
     */
    public $stockfis;

    /**
     * El stock mínimo que debe haber
     * @var float
     */
    public $stockmin;

    /**
     * El stock máximo que debe haber
     * @var float
     */
    public $stockmax;

    /**
     * TRUE -> permitir ventas sin stock.
     * Si, sé que no tiene sentido que poner controlstock a TRUE
     * implique la ausencia de control de stock. Pero es una cagada de
     * FacturaLux -> Abanq -> Eneboo, y por motivos de compatibilidad
     * se mantiene.
     * @var bool
     */
    public $controlstock;

    /**
     * TRUE -> no controlar el stock.
     * Activarlo implica poner a TRUE $controlstock;
     * @var bool
     */
    public $nostock;

    /**
     * Código de barras.
     * @var string
     */
    public $codbarras;

    /**
     * Observaciones del artículo
     * @var string
     */
    public $observaciones;

    /**
     * Código de la subcuenta para compras.
     * @var string
     */
    public $codsubcuentacom;

    /**
     * Código para la subcuenta de compras, pero con IRPF.
     * @var string
     */
    public $codsubcuentairpfcom;

    /**
     * Control de trazabilidad.
     * @var bool
     */
    public $trazabilidad;

    /**
     * % IVA del impuesto asignado.
     * @var float
     */
    private $iva;

    /**
     * Ruta a la imagen
     * @var string
     */
    private $imagen;

    /**
     * TODO
     * @var bool
     */
    private $exists;

    public function tableName()
    {
        return 'articulos';
    }

    public function primaryColumn()
    {
        return 'referencia';
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    public function install()
    {
        /**
         * Limpiamos la caché por si el usuario ha borrado la tabla, pero ya tenía búsquedas.
         */
        $this->cleanCache();

        /**
         * La tabla articulos tiene varias claves ajenas, por eso debemos forzar la comprobación de esas tablas.
         */
        new Fabricante();
        new Familia();
        new Impuesto();

        return '';
    }
    
    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearTrait();
        $this->factualizado = date('d-m-Y');
        $this->imagen = null;
        $this->exists = false;
    }

    /**
     * Devuelve la descripción,
     * si se indica $len se cortará a a esa longitud
     *
     * @param int $len
     *
     * @return string
     */
    public function getDescripcion($len = 120)
    {
        if (mb_strlen($this->descripcion, 'UTF8') > $len) {
            return mb_substr($this->descripcion, 0, $len) . '...';
        }

        return $this->descripcion;
    }

    /**
     * Devuelve el PVP con IVA
     * @return float
     */
    public function pvpIva()
    {
        return $this->pvp * (100 + $this->getIva()) / 100;
    }

    /**
     * Devuelve el precio de coste, ya esté configurado como calculado o editable.
     * @return float
     */
    public function preciocoste()
    {
        if ($this->secompra && FS_COST_IS_AVERAGE) {
            return $this->costemedio;
        }

        return $this->preciocoste;
    }

    /**
     * Devuelve el precio de coste con IVA
     * @return float
     */
    public function preciocosteIva()
    {
        return $this->preciocoste() * (100 + $this->getIva()) / 100;
    }

    /**
     * Devuelve la referencia codificada para poder ser usada en imágenes.
     * Evitamos así errores con caracteres especiales como / y \.
     *
     * @param bool $ref
     *
     * @return string
     */
    public function imageRef($ref = false)
    {
        if (!$ref) {
            $ref = $this->referencia;
        }

        $ref = str_replace(['/', '\\'], '_', $ref);

        return $ref;
    }

    /**
     * Devuelve una nueva referencia, la siguiente a la última de la base de datos.
     */
    public function getNewReferencia()
    {
        $sql = 'SELECT referencia FROM ' . $this->tableName() . " WHERE referencia REGEXP '^\d+$'"
            . ' ORDER BY ABS(referencia) DESC';
        if (strtolower(FS_DB_TYPE) === 'postgresql') {
            $sql = 'SELECT referencia FROM ' . $this->tableName() . " WHERE referencia ~ '^\d+$'"
                . ' ORDER BY referencia::BIGINT DESC';
        }

        $ref = 1;
        $data = $this->dataBase->selectLimit($sql, 1);
        if (!empty($data)) {
            $ref = sprintf(1 + (int) $data[0]['referencia']);
        }

        $this->exists = false;

        return $ref;
    }

    /**
     * Devuelve la familia del artículo.
     * @return bool|Familia
     */
    public function getFamilia()
    {
        if ($this->codfamilia === null) {
            return false;
        }
        $fam = new Familia();

        return $fam->get($this->codfamilia);
    }

    /**
     * Devuelve el fabricante del artículo.
     * @return bool|Fabricante
     */
    public function getFabricante()
    {
        if ($this->codfabricante === null) {
            return false;
        }
        $fab = new Fabricante();

        return $fab->get($this->codfabricante);
    }

    /**
     * Devuelve el stock del artículo
     * @return array
     */
    public function getStock()
    {
        if ($this->nostock) {
            return [];
        }
        $stock = new Stock();

        return $stock->all([new DataBaseWhere('referencia', $this->referencia)]);
    }

    /**
     * Devuelve el impuesto del artículo
     * @return bool|Impuesto
     */
    public function getImpuesto()
    {
        $imp = new Impuesto();

        return $imp->get($this->codimpuesto);
    }

    /**
     * Devuelve el % de IVA del artículo.
     * Si $reload es TRUE, vuelve a consultarlo en lugar de usar los datos cargados.
     *
     * @param bool $reload
     *
     * @return float|null
     */
    public function getIva($reload = false)
    {
        if ($reload) {
            $this->iva = null;
        }

        if ($this->iva === null) {
            $this->iva = 0;

            if (!$this->codimpuesto === null) {
                $encontrado = false;
                foreach (self::$impuestos as $i) {
                    if ($i->codimpuesto === $this->codimpuesto) {
                        $this->iva = $i->iva;
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {
                    $imp = new Impuesto();
                    $imp0 = $imp->get($this->codimpuesto);
                    if ($imp0) {
                        $this->iva = $imp0->iva;
                        self::$impuestos[] = $imp0;
                    }
                }
            }
        }

        return $this->iva;
    }

    /**
     * Devuelve la url relativa de la imagen del artículo.
     * @return string|false
     */
    public function imagenUrl()
    {
        if (file_exists(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.png')) {
            return 'images/articulos/' . $this->imageRef() . '-1.png';
        }
        if (file_exists(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.jpg')) {
            return 'images/articulos/' . $this->imageRef() . '-1.jpg';
        }

        return false;
    }

    /**
     * Asigna una imagen a un artículo.
     *
     * @param string $img
     * @param bool $png
     */
    public function setImagen($img, $png = true)
    {
        $this->imagen = null;

        if (file_exists(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.png')) {
            unlink(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.png');
        } elseif (file_exists('images/articulos/' . $this->imageRef() . '-1.jpg')) {
            unlink(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.jpg');
        }

        if ($img) {
            if (!file_exists(FS_MYDOCS . 'images/articulos')) {
                @mkdir(FS_MYDOCS . 'images/articulos', 0777, true);
            }

            if ($png) {
                $file = @fopen(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.png', 'ab');
            } else {
                $file = @fopen(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.jpg', 'ab');
            }

            if ($file) {
                fwrite($file, $img);
                fclose($file);
            }
        }
    }

    /**
     * Asigna el PVP
     *
     * @param float $pvp
     */
    public function setPvp($pvp)
    {
        $pvp = bround($pvp, FS_NF0_ART);

        if (!$this->floatcmp($this->pvp, $pvp, FS_NF0_ART + 2)) {
            $this->pvp_ant = $this->pvp;
            $this->factualizado = date('d-m-Y');
            $this->pvp = $pvp;
        }
    }

    /**
     * Asigna el PVP con IVA
     *
     * @param float $pvp
     */
    public function setPvpIva($pvp)
    {
        $this->setPvp((100 * $pvp) / (100 + $this->getIva()));
    }

    /**
     * Cambia la referencia del artículo.
     * Lo hace en el momento, no hace falta hacer save().
     *
     * @param string $ref
     */
    public function setReferencia($ref)
    {
        $ref = trim($ref);
        if ($ref === null || empty($ref) || strlen($ref) > 18) {
            $this->miniLog->alert('¡Referencia de artículo no válida! Debe tener entre 1 y 18 caracteres.');
        } elseif ($ref !== $this->referencia && !$this->referencia === null) {
            $sql = 'UPDATE ' . $this->tableName() . ' SET referencia = ' . $this->var2str($ref)
                . ' WHERE referencia = ' . $this->var2str($this->referencia) . ';';
            if ($this->dataBase->exec($sql)) {
                /// renombramos la imagen, si la hay
                if (file_exists(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.png')) {
                    rename(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.png', FS_MYDOCS . 'images/articulos/' . $this->imageRef($ref) . '-1.png');
                }

                $this->referencia = $ref;
            } else {
                $this->miniLog->alert('Imposible modificar la referencia.');
            }
        }

        $this->exists = false;
    }

    /**
     * Cambia el impuesto asociado al artículo.
     *
     * @param string $codimpuesto
     */
    public function setImpuesto($codimpuesto)
    {
        if ($codimpuesto !== $this->codimpuesto) {
            $this->codimpuesto = $codimpuesto;

            $encontrado = false;
            foreach (self::$impuestos as $i) {
                if ($i->codimpuesto === $this->codimpuesto) {
                    $this->iva = (float) $i->iva;
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                $imp = new Impuesto();
                $imp0 = $imp->get($this->codimpuesto);
                $this->iva = 0;
                if ($imp0) {
                    $this->iva = (float) $imp0->iva;
                    self::$impuestos[] = $imp0;
                }
            }
        }
    }

    /**
     * Modifica el stock del artículo en un almacén concreto.
     * Ya se encarga de ejecutar save() si es necesario.
     *
     * @param string $codalmacen
     * @param integer $cantidad
     *
     * @return bool
     */
    public function setStock($codalmacen, $cantidad = 1)
    {
        $result = false;

        if ($this->nostock) {
            $result = true;
        } else {
            $stock = new Stock();
            $encontrado = false;
            $stocks = $stock->allFromArticulo($this->referencia);
            foreach ($stocks as $sto) {
                if ($sto instanceof Stock && $sto->codalmacen === $codalmacen) {
                    $sto->setCantidad($cantidad);
                    $result = $sto->save();
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                $stock->referencia = $this->referencia;
                $stock->codalmacen = $codalmacen;
                $stock->setCantidad($cantidad);
                $result = $stock->save();
            }

            if ($result) {
                /// $result es TRUE
                /// este código está muy optimizado para guardar solamente los cambios

                $nuevoStock = $stock->totalFromArticulo($this->referencia);
                if ($this->stockfis !== $nuevoStock) {
                    $this->stockfis = $nuevoStock;

                    if ($this->exists) {
                        $this->cleanCache();
                        $sql = 'UPDATE ' . $this->tableName()
                            . ' SET stockfis = ' . $this->var2str($this->stockfis)
                            . ' WHERE referencia = ' . $this->var2str($this->referencia) . ';';
                        $result = $this->dataBase->exec($sql);
                    } elseif (!$this->save()) {
                        $this->miniLog->alert('¡Error al actualizar el stock del artículo!');
                    }
                }
            } else {
                $this->miniLog->alert('Error al guardar el stock');
            }
        }

        return $result;
    }

    /**
     * Suma la cantidad especificada al stock del artículo en el almacén especificado.
     * Ya se encarga de ejecutar save() si es necesario.
     *
     * @param string $codalmacen
     * @param integer $cantidad
     * @param bool $recalculaCoste
     * @param string $codcombinacion
     *
     * @return bool
     */
    public function sumStock($codalmacen, $cantidad = 1, $recalculaCoste = false, $codcombinacion = null)
    {
        $result = false;

        if ($recalculaCoste) {
            $this->costemedio = $this->getCostemedio();
        }

        if ($this->nostock) {
            $result = true;

            if ($recalculaCoste) {
                /// este código está muy optimizado para guardar solamente los cambios
                if ($this->exists) {
                    $this->cleanCache();
                    $sql = 'UPDATE ' . $this->tableName()
                        . '  SET costemedio = ' . $this->var2str($this->costemedio)
                        . '  WHERE referencia = ' . $this->var2str($this->referencia) . ';';
                    $result = $this->dataBase->exec($sql);
                } elseif (!$this->save()) {
                    $this->miniLog->alert('¡Error al actualizar el stock del artículo!');
                    $result = false;
                }
            }
        } else {
            $stock = new Stock();
            $encontrado = false;
            $stocks = $stock->allFromArticulo($this->referencia);
            foreach ($stocks as $sto) {
                if ($sto instanceof Stock && $sto->codalmacen === $codalmacen) {
                    $sto->sumCantidad($cantidad);
                    $result = $sto->save();
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                $stock->referencia = $this->referencia;
                $stock->codalmacen = $codalmacen;
                $stock->setCantidad($cantidad);
                $result = $stock->save();
            }

            if ($result) {
                /// este código está muy optimizado para guardar solamente los cambios

                $nuevoStock = $stock->totalFromArticulo($this->referencia);
                if ($this->stockfis !== $nuevoStock) {
                    $this->stockfis = $nuevoStock;

                    if ($this->exists) {
                        $this->cleanCache();
                        $sql = 'UPDATE ' . $this->tableName()
                            . '  SET stockfis = ' . $this->var2str($this->stockfis)
                            . ', costemedio = ' . $this->var2str($this->costemedio)
                            . '  WHERE referencia = ' . $this->var2str($this->referencia) . ';';
                        $result = $this->dataBase->exec($sql);
                    } elseif (!$this->save()) {
                        $this->miniLog->alert('¡Error al actualizar el stock del artículo!');
                        $result = false;
                    }

                    /// ¿Alguna combinación?
                    if ($codcombinacion !== null && $result) {
                        $com0 = new ArticuloCombinacion();
                        foreach ($com0->allFromCodigo($codcombinacion) as $combi) {
                            if ($combi instanceof ArticuloCombinacion) {
                                $combi->stockfis += $cantidad;
                                $combi->save();
                            }
                        }
                    }
                }
            } else {
                $this->miniLog->alert('¡Error al guardar el stock!');
            }
        }

        return $result;
    }

    /**
     * Devuelve TRUE  si los datos del artículo son correctos.
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->descripcion = self::noHtml($this->descripcion);
        $this->codbarras = self::noHtml($this->codbarras);
        $this->observaciones = self::noHtml($this->observaciones);

        if ($this->equivalencia === '') {
            $this->equivalencia = null;
        }

        if ($this->nostock) {
            $this->controlstock = true;
            $this->stockfis = 0;
            $this->stockmax = 0;
            $this->stockmin = 0;
        }

        if ($this->bloqueado) {
            $this->publico = false;
        }

        if ($this->referencia === null || empty($this->referencia) || strlen($this->referencia) > 18) {
            $this->miniLog->alert(
                'Referencia de artículo no válida: ' . $this->referencia . '. Debe tener entre 1 y 18 caracteres.'
            );
        } elseif ($this->equivalencia !== null && strlen($this->equivalencia) > 25) {
            $this->miniLog->alert(
                'Código de equivalencia del artículos no válido: ' . $this->equivalencia
                . '. Debe tener entre 1 y 25 caracteres.'
            );
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Elimina el artículo de la base de datos.
     * @return bool
     */
    public function delete()
    {
        $this->cleanCache();

        $sql = 'DELETE FROM articulosprov WHERE referencia = ' . $this->var2str($this->referencia) . ';';
        $sql .= 'DELETE FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($this->referencia) . ';';
        if ($this->dataBase->exec($sql)) {
            $this->setImagen(false);

            $this->exists = false;

            return true;
        }

        return false;
    }

    /**
     * TODO
     * @return mixed
     */
    public function getSearchTags()
    {
        if (self::$search_tags === null) {
            self::$search_tags = $this->cache->get('articulos_searches');
        }

        return self::$search_tags;
    }

    /**
     * Devuelve un array con los artículos encontrados en base a la búsqueda.
     *
     * @param string $query
     * @param integer $offset
     * @param string $codfamilia
     * @param bool $conStock
     * @param string $codfabricante
     * @param bool $bloqueados
     *
     * @return Articulo
     */
    public function search($query = '', $offset = 0, $codfamilia = '', $conStock = false, $codfabricante = '', $bloqueados = false)
    {
        $artilist = [];
        $query = self::noHtml(mb_strtolower($query, 'UTF8'));

        if ($query !== '' && $offset === 0 && $codfamilia === '' &&
            $codfabricante === '' && !$conStock && !$bloqueados) {
            /// intentamos obtener los datos de memcache
            if ($this->newSearchTag($query)) {
                $artilist = $this->cache->get('articulos_search_' . $query);
            }
        }

        if (count($artilist) <= 1) {
            $sql = 'SELECT ' . self::$column_list . ' FROM ' . $this->tableName();
            $separador = ' WHERE';

            if ($codfamilia !== '') {
                $sql .= $separador . ' codfamilia = ' . $this->var2str($codfamilia);
                $separador = ' AND';
            }

            if ($codfabricante !== '') {
                $sql .= $separador . ' codfabricante = ' . $this->var2str($codfabricante);
                $separador = ' AND';
            }

            if ($conStock) {
                $sql .= $separador . ' stockfis > 0';
                $separador = ' AND';
            }

            if ($bloqueados) {
                $sql .= $separador . ' bloqueado = TRUE';
                $separador = ' AND';
            } else {
                $sql .= $separador . ' bloqueado = FALSE';
                $separador = ' AND';
            }

            if ($query === '') {
                /// nada
            } elseif (is_numeric($query)) {
                $sql .= $separador . ' (referencia = ' . $this->var2str($query)
                    . " OR referencia LIKE '%" . $query . "%'"
                    . " OR partnumber LIKE '%" . $query . "%'"
                    . " OR equivalencia LIKE '%" . $query . "%'"
                    . " OR descripcion LIKE '%" . $query . "%'"
                    . ' OR codbarras = ' . $this->var2str($query) . ')';
                $separador = ' AND';
            } else {
                /// ¿La búsqueda son varias palabras?
                $palabras = explode(' ', $query);
                if (count($palabras) > 1) {
                    $sql .= $separador . ' (lower(referencia) = ' . $this->var2str($query)
                        . " OR lower(referencia) LIKE '%" . $query . "%'"
                        . " OR lower(partnumber) LIKE '%" . $query . "%'"
                        . " OR lower(equivalencia) LIKE '%" . $query . "%'"
                        . ' OR (';

                    foreach ($palabras as $i => $pal) {
                        if ($i === 0) {
                            $sql .= "lower(descripcion) LIKE '%" . $pal . "%'";
                        } else {
                            $sql .= " AND lower(descripcion) LIKE '%" . $pal . "%'";
                        }
                    }

                    $sql .= '))';
                } else {
                    $sql .= $separador . ' (lower(referencia) = ' . $this->var2str($query)
                        . " OR lower(referencia) LIKE '%" . $query . "%'"
                        . " OR lower(partnumber) LIKE '%" . $query . "%'"
                        . " OR lower(equivalencia) LIKE '%" . $query . "%'"
                        . ' OR lower(codbarras) = ' . $this->var2str($query)
                        . " OR lower(descripcion) LIKE '%" . $query . "%')";
                }
            }

            if (strtolower(FS_DB_TYPE) === 'mysql') {
                $sql .= ' ORDER BY lower(referencia) ASC';
            } else {
                $sql .= ' ORDER BY referencia ASC';
            }

            $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
            if (!empty($data)) {
                foreach ($data as $a) {
                    $artilist[] = new Articulo($a);
                }
            }
        }

        return $artilist;
    }

    /**
     * Devuelve un array con los artículos que tengan $cod como código de barras.
     *
     * @param string $cod
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function searchByCodbar($cod, $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $artilist = [];
        $sql = 'SELECT ' . self::$column_list . ' FROM ' . $this->tableName()
            . ' WHERE codbarras = ' . $this->var2str($cod)
            . ' ORDER BY lower(referencia) ASC';

        $data = $this->dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $d) {
                $artilist[] = new Articulo($d);
            }
        }

        return $artilist;
    }

    /**
     * TODO
     */
    public function cronJob()
    {
        /// aceleramos las búsquedas
        if ($this->getSearchTags()) {
            foreach (self::$search_tags as $i => $value) {
                if ($value['expires'] < time()) {
                    /// eliminamos las búsquedas antiguas
                    unset(self::$search_tags[$i]);
                } elseif ($value['count'] > 1) {
                    /// guardamos los resultados de la búsqueda en memcache
                    $this->cache->set('articulos_search_' . $value['tag'], $this->search($value['tag']));
                    echo '.';
                }
            }

            /// guardamos en memcache la lista de búsquedas
            $this->cache->set('articulos_searches', self::$search_tags);
        }

        $this->fixDb();
    }

    /**
     * Realizamos algunas correcciones a la base de datos.
     */
    public function fixDb()
    {
        $fixes = [
            'UPDATE ' . $this->tableName() . ' SET bloqueado = true WHERE bloqueado IS NULL;',
            'UPDATE ' . $this->tableName() . ' SET nostock = false WHERE nostock IS NULL;',
            /// desvinculamos de fabricantes que no existan
            'UPDATE ' . $this->tableName() . ' SET codfabricante = null WHERE codfabricante IS NOT NULL'
            . ' AND codfabricante NOT IN (SELECT codfabricante FROM fabricantes);',
            /// desvinculamos de familias que no existan
            'UPDATE ' . $this->tableName() . ' SET codfamilia = null WHERE codfamilia IS NOT NULL'
            . ' AND codfamilia NOT IN (SELECT codfamilia FROM familias);'
        ];
        foreach ($fixes as $sql) {
            $this->dataBase->exec($sql);
        }
    }

    /**
     * Comprueba y añade una cadena a la lista de búsquedas precargadas
     * en memcache. Devuelve TRUE si la cadena ya está en la lista de
     * precargadas.
     *
     * @param string $tag
     *
     * @return bool
     */
    private function newSearchTag($tag)
    {
        $encontrado = false;
        $actualizar = false;

        if (strlen($tag) > 1) {
            /// obtenemos los datos de memcache
            $this->getSearchTags();

            foreach (self::$search_tags as $i => $value) {
                if ($value['tag'] === $tag) {
                    $encontrado = true;
                    if (time() + 5400 > $value['expires'] + 300) {
                        self::$search_tags[$i]['count'] ++;
                        self::$search_tags[$i]['expires'] = time() + (self::$search_tags[$i]['count'] * 5400);
                        $actualizar = true;
                    }
                    break;
                }
            }
            if (!$encontrado) {
                self::$search_tags[] = ['tag' => $tag, 'expires' => time() + 5400, 'count' => 1];
                $actualizar = true;
            }

            if ($actualizar) {
                $this->cache->set('articulos_searches', self::$search_tags);
            }
        }

        return $encontrado;
    }

    /**
     * TODO
     */
    private function cleanCache()
    {
        /*
         * Durante las actualizaciones masivas de artículos se ejecuta esta
         * función cada vez que se guarda un artículo, por eso es mejor limitarla.
         */
        if (self::$cleaned_cache !== null && !empty(self::$cleaned_cache)) {
            /// obtenemos los datos de memcache
            $this->getSearchTags();

            if (!empty(self::$search_tags)) {
                foreach (self::$search_tags as $value) {
                    $this->cache->delete('articulos_search_' . $value['tag']);
                }
            }

            self::$cleaned_cache = true;
        }
    }
}
