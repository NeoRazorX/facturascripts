<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2012-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

define('FS_MYDOCS', '');

/**
 * Almacena los datos de un artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Articulo
{

    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * Clave primaria. Varchar (18).
     *
     * @var string
     */
    public $referencia;

    /**
     * Define el tipo de artículo, así se pueden establecer distinciones
     * según un tipo u otro. Varchar (10)
     *
     * @var string
     */
    public $tipo;

    /**
     * Código de la familia a la que pertenece. En la clase familia.
     *
     * @var string
     */
    public $codfamilia;

    /**
     * Descripción del artículo. Tipo text, sin límite de caracteres.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Código del fabricante al que pertenece. En la clase fabricante.
     *
     * @var string
     */
    public $codfabricante;

    /**
     * Precio del artículo, sin impuestos.
     *
     * @var float|int
     */
    public $pvp;

    /**
     * Almacena el valor del pvp antes de hacer el cambio.
     * Este valor no se almacena en la base de datos, es decir,
     * no se recuerda.
     *
     * @var float|int
     */
    public $pvp_ant;

    /**
     * Fecha de actualización del pvp.
     *
     * @var string
     */
    public $factualizado;

    /**
     * Coste medio al comprar el artículo. Calculado.
     *
     * @var float|int
     */
    public $costemedio;

    /**
     * Precio de coste editado manualmente.
     * No necesariamente es el precio de compra, puede incluir
     * también otros costes.
     *
     * @var float|int
     */
    public $preciocoste;

    /**
     * Impuesto asignado. Clase impuesto.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * True => el artículos está bloqueado / obsoleto.
     *
     * @var bool
     */
    public $bloqueado;

    /**
     * True => el artículo se compra
     *
     * @var bool
     */
    public $secompra;

    /**
     * True => el artículo se vende
     *
     * @var bool
     */
    public $sevende;

    /**
     * True -> se mostrará sincronizará con la tienda online.
     *
     * @var bool
     */
    public $publico;

    /**
     * Código de equivalencia. Varchar (18).
     * Dos artículos o más son equivalentes si tienen el mismo código de equivalencia.
     *
     * @var string
     */
    public $equivalencia;

    /**
     * Partnumber del producto. Máximo 38 caracteres.
     *
     * @var string
     */
    public $partnumber;

    /**
     * Stock físico. La suma de las cantidades de esta referencia que en la tabla stocks.
     *
     * @var float|int
     */
    public $stockfis;

    /**
     * El stock mínimo que debe haber
     *
     * @var float|int
     */
    public $stockmin;

    /**
     * El stock máximo que debe haber
     *
     * @var float|int
     */
    public $stockmax;

    /**
     * True -> permitir ventas sin stock.
     * Si, sé que no tiene sentido que poner controlstock a True
     * implique la ausencia de control de stock. Pero es una cagada de
     * FacturaLux -> Abanq -> Eneboo, y por motivos de compatibilidad
     * se mantiene.
     *
     * @var bool
     */
    public $controlstock;

    /**
     * True -> no controlar el stock.
     * Activarlo implica poner a True $controlstock;
     *
     * @var bool
     */
    public $nostock;

    /**
     * Código de barras.
     *
     * @var string
     */
    public $codbarras;

    /**
     * Observaciones del artículo
     *
     * @var string
     */
    public $observaciones;

    /**
     * Código de la subcuenta para compras.
     *
     * @var string
     */
    public $codsubcuentacom;

    /**
     * Código para la subcuenta de compras, pero con IRPF.
     *
     * @var string
     */
    public $codsubcuentairpfcom;

    /**
     * Control de trazabilidad.
     *
     * @var bool
     */
    public $trazabilidad;

    /**
     * % IVA del impuesto asignado.
     *
     * @var float|int
     */
    private $iva;

    /**
     * Ruta a la imagen
     *
     * @var string
     */
    private $imagen;

    /**
     * Array de impuestos
     *
     * @var Impuesto[]
     */
    private static $impuestos;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulos';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'referencia';
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
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
    }

    /**
     * Devuelve el PVP con IVA
     *
     * @return float
     */
    public function pvpIva()
    {
        return $this->pvp * (100 + $this->getIva()) / 100;
    }

    /**
     * Devuelve el precio de coste, ya esté configurado como calculado o editable.
     *
     * @return float
     */
    public function preciocoste()
    {
        return ($this->secompra && FS_COST_IS_AVERAGE) ? $this->costemedio : $this->preciocoste;
    }

    /**
     * Devuelve el precio de coste con IVA
     *
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
     * @param string|false $ref
     *
     * @return string
     */
    public function imageRef($ref = false)
    {
        $ref2 = ($ref === false) ? $this->referencia : $ref;
        return str_replace(['/', '\\'], '_', $ref2);
    }

    /**
     * Devuelve una nueva referencia, la siguiente a la última de la base de datos.
     */
    public function getNewReferencia()
    {
        $sql = 'SELECT referencia FROM ' . $this->tableName() . ' WHERE referencia ';
        $sql .= (strtolower(FS_DB_TYPE) === 'postgresql') ? "~ '^\d+$' ORDER BY referencia::BIGINT DESC" : "REGEXP '^\d+$' ORDER BY ABS(referencia) DESC";

        $ref = 1;
        $data = $this->dataBase->selectLimit($sql, 1);
        if (!empty($data)) {
            $ref = sprintf(1 + (int) $data[0]['referencia']);
        }

        return $ref;
    }

    /**
     * Devuelve la familia del artículo.
     *
     * @return bool|Familia
     */
    public function getFamilia()
    {
        $fam = new Familia();
        return $this->codfamilia === null ? false : $fam->get($this->codfamilia);
    }

    /**
     * Devuelve el fabricante del artículo.
     *
     * @return bool|Fabricante
     */
    public function getFabricante()
    {
        $fab = new Fabricante();
        return $this->codfabricante === null ? false : $fab->get($this->codfabricante);
    }

    /**
     * Devuelve el stock del artículo
     *
     * @return Stock[]
     */
    public function getStock()
    {
        $stock = new Stock();
        return $this->nostock ? [] : $stock->all([new DataBaseWhere('referencia', $this->referencia)]);
    }

    /**
     * Devuelve el impuesto del artículo
     *
     * @return bool|Impuesto
     */
    public function getImpuesto()
    {
        $imp = new Impuesto();
        return $imp->get($this->codimpuesto);
    }

    /**
     * Devuelve el % de IVA del artículo.
     * Si $reload es True, vuelve a consultarlo en lugar de usar los datos cargados.
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

        if (!isset(self::$impuestos)) {
            self::$impuestos = [];
            $impuestoModel = new Impuesto();
            foreach ($impuestoModel->all() as $imp) {
                self::$impuestos[$imp->codimpuesto] = $imp;
            }
        }

        if ($this->iva === null) {
            $this->iva = 0;

            if (!$this->codimpuesto === null && isset(self::$impuestos[$this->codimpuesto])) {
                $this->iva = self::$impuestos[$this->codimpuesto]->iva;
            }
        }

        return $this->iva;
    }

    /**
     * Devuelve la url relativa de la imagen del artículo.
     *
     * @return string|false
     */
    public function imagenUrl()
    {
        $images = [
            FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.png',
            FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.jpg'
        ];

        foreach ($images as $image) {
            if (file_exists($image)) {
                return $image;
            }
        }

        return false;
    }

    /**
     * Asigna una imagen a un artículo.
     * Si $img está vacío, se elimina la imagen anterior.
     *
     * @param string $img
     * @param bool   $png
     */
    public function setImagen($img, $png = true)
    {
        $this->imagen = null;

        if ($oldImage = $this->imagenUrl()) {
            unlink($oldImage);
        }

        if ($img) {
            if (!file_exists(FS_MYDOCS . 'images/articulos')) {
                @mkdir(FS_MYDOCS . 'images/articulos', 0777, true);
            }

            $file = @fopen(FS_MYDOCS . 'images/articulos/' . $this->imageRef() . '-1.' . ($png ? 'png' : 'jpg'), 'ab');
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
        $pvp = round($pvp, FS_NF0_ART);

        if (!static::floatcmp($this->pvp, $pvp, FS_NF0_ART + 2)) {
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
            $this->miniLog->alert($this->i18n->trans('product-reference-not-valid', [$this->referencia]));
        } elseif ($ref !== $this->referencia && !$this->referencia === null) {
            $sql = 'UPDATE ' . $this->tableName() . ' SET referencia = ' . $this->var2str($ref)
                . ' WHERE referencia = ' . $this->var2str($this->referencia) . ';';
            if ($this->dataBase->exec($sql)) {
                /// renombramos la imagen, si la hay
                if ($oldImage = $this->imagenUrl()) {
                    rename($oldImage, FS_MYDOCS . 'images/articulos/' . $this->imageRef($ref) . '-1.png');
                }

                $this->referencia = $ref;
            } else {
                $this->miniLog->alert($this->i18n->trans('cant-modify-reference'));
            }
        }
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
            $this->iva = null;

            if (!isset(self::$impuestos)) {
                self::$impuestos = [];
                $impuestoModel = new Impuesto();
                foreach ($impuestoModel->all() as $imp) {
                    self::$impuestos[$imp->codimpuesto] = $imp;
                }
            }
        }
    }

    /**
     * Modifica el stock del artículo en un almacén concreto.
     * Ya se encarga de ejecutar save() si es necesario.
     *
     * @param string $codalmacen
     * @param int    $cantidad
     *
     * @return bool
     */
    public function setStock($codalmacen, $cantidad = 1)
    {
        if ($this->nostock) {
            return true;
        }

        $result = false;
        $stock = new Stock();
        $encontrado = false;
        $stocks = $stock->allFromArticulo($this->referencia);
        foreach ($stocks as $sto) {
            if ($sto->codalmacen === $codalmacen) {
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
            /// $result es True
            /// este código está muy optimizado para guardar solamente los cambios

            $nuevoStock = $stock->totalFromArticulo($this->referencia);
            if ($this->stockfis !== $nuevoStock) {
                $this->stockfis = $nuevoStock;

                if ($this->exists()) {
                    $sql = 'UPDATE ' . $this->tableName()
                        . ' SET stockfis = ' . $this->var2str($this->stockfis)
                        . ' WHERE referencia = ' . $this->var2str($this->referencia) . ';';
                    $result = $this->dataBase->exec($sql);
                } elseif (!$this->save()) {
                    $this->miniLog->alert($this->i18n->trans('error-updating-product-stock'));
                }
            }
        } else {
            $this->miniLog->alert($this->i18n->trans('error-saving-stock'));
        }

        return $result;
    }

    /**
     * Suma la cantidad especificada al stock del artículo en el almacén especificado.
     * Ya se encarga de ejecutar save() si es necesario.
     *
     * @param string  $codalmacen
     * @param int     $cantidad
     * @param bool    $recalculaCoste
     * @param string  $codcombinacion
     *
     * @return bool
     */
    public function sumStock($codalmacen, $cantidad = 1, $recalculaCoste = false, $codcombinacion = null)
    {
        $result = false;

        if ($recalculaCoste) {
            // TODO: Uncomplete
            $this->costemedio = 1;
        }

        if ($this->nostock) {
            $result = true;

            if ($recalculaCoste) {
                /// este código está muy optimizado para guardar solamente los cambios
                if ($this->exists()) {
                    $sql = 'UPDATE ' . $this->tableName()
                        . '  SET costemedio = ' . $this->var2str($this->costemedio)
                        . '  WHERE referencia = ' . $this->var2str($this->referencia) . ';';
                    $result = $this->dataBase->exec($sql);
                } elseif (!$this->save()) {
                    $this->miniLog->alert($this->i18n->trans('error-updating-product-stock'));
                    $result = false;
                }
            }
        } else {
            $stock = new Stock();
            $encontrado = false;
            $stocks = $stock->all([new DataBaseWhere('referencia', $this->referencia)]);
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

                    if ($this->exists()) {
                        $sql = 'UPDATE ' . $this->tableName()
                            . '  SET stockfis = ' . $this->var2str($this->stockfis)
                            . ', costemedio = ' . $this->var2str($this->costemedio)
                            . '  WHERE referencia = ' . $this->var2str($this->referencia) . ';';
                        $result = $this->dataBase->exec($sql);
                    } elseif (!$this->save()) {
                        $this->miniLog->alert($this->i18n->trans('error-updating-product-stock'));
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
                $this->miniLog->alert($this->i18n->trans('error-saving-stock'));
            }
        }

        return $result;
    }

    /**
     * Devuelve True  si los datos del artículo son correctos.
     *
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
            $this->miniLog->alert($this->i18n->trans('product-reference-not-valid', [$this->referencia]));
        } elseif ($this->equivalencia !== null && strlen($this->equivalencia) > 25) {
            $this->miniLog->alert($this->i18n->trans('product-equivalence-not-valid', [$this->equivalencia]));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Elimina el artículo de la base de datos.
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM articulosprov WHERE referencia = ' . $this->var2str($this->referencia) . ';';
        $sql .= 'DELETE FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($this->referencia) . ';';
        if ($this->dataBase->exec($sql)) {
            $this->setImagen(false);

            return true;
        }

        return false;
    }

    /**
     * Ejecuta una tarea con cron
     */
    public function cronJob()
    {
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
            . ' AND codfamilia NOT IN (SELECT codfamilia FROM familias);',
        ];
        foreach ($fixes as $sql) {
            $this->dataBase->exec($sql);
        }
    }
}
