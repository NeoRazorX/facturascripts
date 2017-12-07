<?php
/**
 * This file is part of FacturaScripts
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

/**
 * Store the data of an item.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Articulo
{

    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * Primary key. Varchar (18).
     *
     * @var string
     */
    public $referencia;

    /**
     * Define the type of item, so you can set distinctions
     * according to one type or another. Varchar (10)
     *
     * @var string
     */
    public $tipo;

    /**
     * Code of the family to which it belongs. In the family class.
     *
     * @var string
     */
    public $codfamilia;

    /**
     * Description of the article. Type text, without character limit.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Code of the manufacturer to which it belongs. In the manufacturer class.
     *
     * @var string
     */
    public $codfabricante;

    /**
     * Price of the item, without taxes.
     *
     * @var float|int
     */
    public $pvp;

    /**
     * Store the value of the pvp before making the change.
     * This value is not stored in the database, ie,
     * do not remember.
     *
     * @var float|int
     */
    public $pvp_ant;

    /**
     * Update date of the pvp.
     *
     * @var string
     */
    public $factualizado;

    /**
     * Average cost when buying the item. Calculated.
     *
     * @var float|int
     */
    public $costemedio;

    /**
     * Cost price manually edited.
     * It is not necessarily the purchase price, it can include
     * also other costs.
     *
     * @var float|int
     */
    public $preciocoste;

    /**
     * Tax assigned. Taxed class.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * True => the items are locked / obsolete.
     *
     * @var bool
     */
    public $bloqueado;

    /**
     * True => the item is purchased
     *
     * @var bool
     */
    public $secompra;

    /**
     * True => the item is sold
     *
     * @var bool
     */
    public $sevende;

    /**
     * True -> will be synchronized with the online store.
     *
     * @var bool
     */
    public $publico;

    /**
     * Equivalence code. Varchar (18).
     * Partnumber of the product. Maximum 38 characters.
     *
     * @var string
     */
    public $equivalencia;

    /**
     * Partnumber of the product. Maximum 38 characters.
     *
     * @var string
     */
    public $partnumber;

    /**
     * Physical stock. The sum of the amounts of this reference that in the table stocks.
     *
     * @var float|int
     */
    public $stockfis;

    /**
     * The minimum stock that must have
     *
     * @var float|int
     */
    public $stockmin;

    /**
     * The maximum stock that must have
     *
     * @var float|int
     */
    public $stockmax;

    /**
     * True -> allow sales without stock.
     * Yes, I know it does not make sense to put controlstock to True
     * implies the absence of stock control. But it's a shit
     * FacturaLux -> Abanq -> Eneboo, and for compatibility reasons
     * it keeps.
     *
     * @var bool
     */
    public $controlstock;

    /**
     * True -> do not control the stock.
     * Activating it implies putting True $ controlstock;
     *
     * @var bool
     */
    public $nostock;

    /**
     * Barcode.
     *
     * @var string
     */
    public $codbarras;

    /**
     * Observations of the article
     *
     * @var string
     */
    public $observaciones;

    /**
     * Sub-account code for purchases.
     *
     * @var string
     */
    public $codsubcuentacom;

    /**
     * Code for the purchasing sub-account, but with IRPF.
     *
     * @var string
     */
    public $codsubcuentairpfcom;

    /**
     * Traceability control.
     *
     * @var bool
     */
    public $trazabilidad;

    /**
     * % VAT tax assigned.
     *
     * @var float|int
     */
    private $iva;

    /**
     * Route to the image
     *
     * @var string
     */
    private $imagen;

    /**
     * Tax Array
     *
     * @var Impuesto[]
     */
    private static $impuestos;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulos';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'referencia';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /**
         * The article table has several foreign keys, so we must force the checking of these tables.
         */
        new Fabricante();
        new Familia();
        new Impuesto();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->clearTrait();
        $this->factualizado = date('d-m-Y');
    }

    /**
     * Returns the PVP with VAT
     *
     * @return float
     */
    public function pvpIva()
    {
        return $this->pvp * (100 + $this->getIva()) / 100;
    }

    /**
     * Returns the cost price, whether it is configured as calculated or editable.
     *
     * @return float
     */
    public function preciocoste()
    {
        return ($this->secompra && FS_COST_IS_AVERAGE) ? $this->costemedio : $this->preciocoste;
    }

    /**
     * Returns the cost price with VAT
     *
     * @return float
     */
    public function preciocosteIva()
    {
        return $this->preciocoste() * (100 + $this->getIva()) / 100;
    }

    /**
     * Returns the coded reference to be used in images.
     * We thus avoid errors with special characters such as / and \.
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
     * Returns a new reference, the next to the last one in the database.
     */
    public function getNewReferencia()
    {
        $sql = 'SELECT referencia FROM ' . $this->tableName() . ' WHERE referencia ';
        $sql .= (strtolower(FS_DB_TYPE) === 'postgresql') ? "~ '^\d+$' ORDER BY referencia::BIGINT DESC" : "REGEXP '^\d+$' ORDER BY ABS(referencia) DESC";

        $data = $this->dataBase->selectLimit($sql, 1);
        if (!empty($data)) {
            return sprintf(1 + (int) $data[0]['referencia']);
        }

        return '1';
    }

    /**
     * Returns the article family.
     *
     * @return bool|Familia
     */
    public function getFamilia()
    {
        $fam = new Familia();
        return $this->codfamilia === null ? false : $fam->get($this->codfamilia);
    }

    /**
     * Returns the article's manufacturer.
     *
     * @return bool|Fabricante
     */
    public function getFabricante()
    {
        $fab = new Fabricante();
        return $this->codfabricante === null ? false : $fab->get($this->codfabricante);
    }

    /**
     * Returns the stock of the item
     *
     * @return Stock[]
     */
    public function getStock()
    {
        $stock = new Stock();
        return $this->nostock ? [] : $stock->all([new DataBaseWhere('referencia', $this->referencia)]);
    }

    /**
     * Returns the item tax
     *
     * @return bool|Impuesto
     */
    public function getImpuesto()
    {
        $imp = new Impuesto();
        return $imp->get($this->codimpuesto);
    }

    /**
     * Returns the VAT% of the item.
     * If $ reload is True, check back instead of using the loaded data.
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

        $this->checkTaxes();

        if ($this->iva === null) {
            $this->iva = 0;

            if (!$this->codimpuesto === null && isset(self::$impuestos[$this->codimpuesto])) {
                $this->iva = self::$impuestos[$this->codimpuesto]->iva;
            }
        }

        return $this->iva;
    }

    /**
     * Returns the relative url of the article image.
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
     * Assign an image to an article.
     * If $img is empty, the previous image is deleted.
     *
     * @param string $img
     * @param bool $png
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
     * Assign the PVP
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
     * Assign PVP with VAT
     *
     * @param float $pvp
     */
    public function setPvpIva($pvp)
    {
        $this->setPvp((100 * $pvp) / (100 + $this->getIva()));
    }

    /**
     * Change the article reference.
     * He does it at the moment, there is no need to do save().
     *
     * @param string $ref
     */
    public function setReferencia($ref)
    {
        $ref = trim($ref);
        if ($ref === null || empty($ref) || strlen($ref) > 18) {
            $this->miniLog->alert($this->i18n->trans('product-reference-not-valid', [$this->referencia]));
        } elseif ($ref !== $this->referencia && !$this->referencia === null) {
            $sql = 'UPDATE ' . $this->tableName() . ' SET referencia = ' . $this->dataBase->var2str($ref)
                . ' WHERE referencia = ' . $this->dataBase->var2str($this->referencia) . ';';
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
     * Change the tax associated with the article.
     *
     * @param string $codimpuesto
     */
    public function setImpuesto($codimpuesto)
    {
        if ($codimpuesto !== $this->codimpuesto) {
            $this->codimpuesto = $codimpuesto;
            $this->iva = null;

            $this->checkTaxes();
        }
    }

    /**
     * Modify the stock of the item in a specific warehouse.
     * Already responsible for executing save () if necessary.
     *
     * @param string $codalmacen
     * @param int $cantidad
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
            /// $ result is True
            /// this code is highly optimized to save only the changes

            $nuevoStock = $stock->totalFromArticulo($this->referencia);
            if ($this->stockfis !== $nuevoStock) {
                $this->stockfis = $nuevoStock;

                if ($this->exists()) {
                    $sql = 'UPDATE ' . $this->tableName()
                        . ' SET stockfis = ' . $this->dataBase->var2str($this->stockfis)
                        . ' WHERE referencia = ' . $this->dataBase->var2str($this->referencia) . ';';
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
     * Add the specified amount to the stock of the item in the specified store.
     * Already responsible for executing save () if necessary.
     *
     * @param string $codalmacen
     * @param int $cantidad
     * @param bool $recalculaCoste
     * @param string $codcombinacion
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
                        . '  SET costemedio = ' . $this->dataBase->var2str($this->costemedio)
                        . '  WHERE referencia = ' . $this->dataBase->var2str($this->referencia) . ';';
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
                /// this code is highly optimized to save only the changes

                $nuevoStock = $stock->totalFromArticulo($this->referencia);
                if ($this->stockfis !== $nuevoStock) {
                    $this->stockfis = $nuevoStock;

                    if ($this->exists()) {
                        $sql = 'UPDATE ' . $this->tableName()
                            . '  SET stockfis = ' . $this->dataBase->var2str($this->stockfis)
                            . ', costemedio = ' . $this->dataBase->var2str($this->costemedio)
                            . '  WHERE referencia = ' . $this->dataBase->var2str($this->referencia) . ';';
                        $result = $this->dataBase->exec($sql);
                    } elseif (!$this->save()) {
                        $this->miniLog->alert($this->i18n->trans('error-updating-product-stock'));
                        $result = false;
                    }

                    /// Any combination?
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
     * Returns True if the item data is correct.
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
     * Remove the article from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM articulosprov WHERE referencia = ' . $this->dataBase->var2str($this->referencia) . ';';
        $sql .= 'DELETE FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->dataBase->var2str($this->referencia) . ';';
        if ($this->dataBase->exec($sql)) {
            $this->setImagen(false);

            return true;
        }

        return false;
    }

    /**
     * Execute a task with cron
     */
    public function cronJob()
    {
        $this->fixDb();
    }

    /**
     * We made some corrections to the database.
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

    /**
     * Check list of taxes, and if is not set load all values.
     */
    public function checkTaxes()
    {
        if (!isset(self::$impuestos)) {
            self::$impuestos = [];
            $impuestoModel = new Impuesto();
            foreach ($impuestoModel->all() as $imp) {
                self::$impuestos[$imp->codimpuesto] = $imp;
            }
        }
    }
}
