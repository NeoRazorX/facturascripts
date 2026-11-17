<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\ProductRelationTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Almacen as DinAlmacen;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;
use Throwable;

/**
 * The quantity in inventory of an item in a particular warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Stock extends ModelClass
{
    use ModelTrait;
    use ProductRelationTrait;

    const MAX_DECIMALS = 3;

    /** @var float|int Cantidad física almacenada. */
    public $cantidad;

    /** @var string Código del almacén al que corresponde el stock. */
    public $codalmacen;

    /** @var float|int Cantidad disponible tras descontar la reservada. */
    public $disponible;

    /** @var int Identificador único del registro de stock. */
    public $idstock;

    /** @var float|int Cantidad pendiente de recibir de proveedores. */
    public $pterecibir;

    /** @var string Referencia de la variante del producto. */
    public $referencia;

    /** @var float|int Cantidad reservada en pedidos de clientes. */
    public $reservada;

    /** @var float|int Cantidad máxima de stock recomendada. */
    public $stockmax;

    /** @var float|int Cantidad mínima de stock recomendada. */
    public $stockmin;

    /** @var string Ubicación física del producto dentro del almacén. */
    public $ubicacion;

    public function clear(): void
    {
        parent::clear();
        $this->cantidad = 0.0;
        $this->codalmacen = Tools::settings('default', 'codalmacen');
        $this->disponible = 0.0;
        $this->pterecibir = 0.0;
        $this->reservada = 0.0;
        $this->stockmax = 0.0;
        $this->stockmin = 0.0;
    }

    public function install(): string
    {
        // needed dependencies
        new DinAlmacen();
        new DinProducto();
        new DinVariante();

        return parent::install();
    }

    /**
     * Carga el stock de la referencia en el almacén indicado bloqueando la fila (SELECT ... FOR UPDATE)
     * hasta que termine la transacción en curso, para que otras peticiones no puedan leerla ni modificarla
     * a la vez y se pierdan actualizaciones.
     *
     * El bloqueo solo tiene efecto dentro de una transacción: quien llama debe abrirla antes.
     * No se usa selectLimit() porque añade el LIMIT al final y MySQL/MariaDB exigen el LIMIT antes
     * del FOR UPDATE; tampoco hace falta, ya que (codalmacen, referencia) es único.
     *
     * @param string $codalmacen Código del almacén.
     * @param string $referencia Referencia de la variante.
     *
     * @return bool True si existe el stock y se ha cargado; false si no existe (el modelo queda limpio).
     */
    public function loadForUpdate(string $codalmacen, string $referencia): bool
    {
        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE codalmacen = ' . self::db()->var2str($codalmacen)
            . ' AND referencia = ' . self::db()->var2str($referencia)
            . ' FOR UPDATE;';
        $data = self::db()->select($sql);
        if (empty($data)) {
            $this->clear();
            return false;
        }

        $this->loadFromData($data[0]);
        return true;
    }

    /**
     * Bloquea las filas de stock de una referencia en varios almacenes, siempre en el mismo orden
     * (por código de almacén), para que dos transacciones que bloquean los mismos almacenes
     * no se esperen mutuamente (deadlock).
     *
     * Debe llamarse dentro de una transacción. Los almacenes sin fila de stock se ignoran.
     *
     * @param string $referencia Referencia de la variante.
     * @param array $warehouses Códigos de almacén.
     */
    public static function lockWarehouses(string $referencia, array $warehouses): void
    {
        $codes = array_unique(array_map('strval', $warehouses));
        sort($codes, SORT_STRING);
        foreach ($codes as $code) {
            $stock = new static();
            $stock->loadForUpdate($code, $referencia);
        }
    }

    public static function primaryColumn(): string
    {
        return 'idstock';
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        $this->updateProductStock();

        return true;
    }

    public static function tableName(): string
    {
        return 'stocks';
    }

    /**
     * Transfiere $qty unidades de stock a $toWarehouse.
     *
     * Bloquea las filas de origen y destino (en orden fijo de almacén) dentro de una transacción,
     * abriendo una propia si no hay ninguna en curso, y recarga el origen desde la base de datos
     * antes de restar, por lo que se descartan los cambios sin guardar de este modelo.
     *
     * @param string $toWarehouse almacén de destino
     * @param float $qty cantidad a mover
     *
     * @return bool
     */
    public function transferTo(string $toWarehouse, float $qty): bool
    {
        if ($toWarehouse === $this->codalmacen) {
            // mismo almacén: no hay nada que mover
            return true;
        }

        $newTransaction = false === self::db()->inTransaction() && self::db()->beginTransaction();

        try {
            $result = $this->transferToLocked($toWarehouse, $qty);
        } catch (Throwable $exception) {
            if ($newTransaction) {
                self::db()->rollback();
            }
            throw $exception;
        }

        if (false === $newTransaction) {
            return $result;
        }

        if ($result && self::db()->commit()) {
            return true;
        }

        self::db()->rollback();
        return false;
    }

    public function test(): bool
    {
        $this->ubicacion = Tools::noHtml($this->ubicacion);

        $this->cantidad = round($this->cantidad, self::MAX_DECIMALS);
        $this->reservada = round($this->reservada, self::MAX_DECIMALS);
        $this->pterecibir = round($this->pterecibir, self::MAX_DECIMALS);
        $this->disponible = max([0, $this->cantidad - $this->reservada]);

        $this->referencia = Tools::noHtml($this->referencia);
        if (empty($this->idproducto)) {
            $variante = new DinVariante();
            if ($variante->loadWhereEq('referencia', $this->referencia)) {
                $this->idproducto = $variante->idproducto;
            }
        }

        // no puede haber dos registros de stock para la misma referencia y almacén
        $where = [
            Where::eq('codalmacen', $this->codalmacen),
            Where::eq('referencia', $this->referencia)
        ];
        if (!empty($this->idstock)) {
            $where[] = Where::notEq('idstock', $this->idstock);
        }
        if (static::count($where) > 0) {
            Tools::log()->warning('stock-already-exists', [
                '%reference%' => $this->referencia,
                '%warehouse%' => $this->codalmacen
            ]);
            return false;
        }

        return parent::test();
    }

    /**
     * Returns the total stock of the product.
     *
     * @param int $idproducto
     * @param string $referencia
     *
     * @return float
     */
    public function totalFromProduct(int $idproducto, string $referencia = ''): float
    {
        $sql = 'SELECT SUM(cantidad) AS total FROM ' . static::tableName()
            . ' WHERE idproducto = ' . self::db()->var2str($idproducto);

        if (!empty($referencia)) {
            $sql .= ' AND referencia = ' . self::db()->var2str($referencia);
        }

        $data = self::db()->select($sql);
        return empty($data) ? 0.0 : round((float)$data[0]['total'], self::MAX_DECIMALS);
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $this->getProducto()->url($type);
    }

    protected function onDelete(): void
    {
        // añadimos el log de auditoría
        Tools::log(LogMessage::AUDIT_CHANNEL)->warning('deleted-model', [
            '%model%' => $this->modelClassName(),
            '%key%' => $this->id(),
            '%desc%' => $this->referencia . ' / ' . $this->codalmacen,
            'model-class' => $this->modelClassName(),
            'model-code' => $this->id(),
            'model-data' => $this->toArray()
        ]);

        // actualizamos el stock del producto y de la variante
        $this->cantidad = 0.0;
        $this->updateProductStock();

        parent::onDelete();
    }

    protected function onDeleteBefore(): bool
    {
        // el stock es un acumulado: si lo eliminamos con cantidades, se descuadra respecto a los documentos
        if ($this->cantidad != 0 || $this->reservada != 0 || $this->pterecibir != 0) {
            Tools::log()->warning('cant-delete-stock-with-quantity', [
                '%reference%' => $this->referencia,
                '%warehouse%' => $this->codalmacen
            ]);
            return false;
        }

        return parent::onDeleteBefore();
    }

    /**
     * Realiza la transferencia de transferTo() con las filas de stock bloqueadas.
     * Debe ejecutarse dentro de una transacción.
     *
     * @param string $toWarehouse almacén de destino
     * @param float $qty cantidad a mover
     *
     * @return bool
     */
    private function transferToLocked(string $toWarehouse, float $qty): bool
    {
        static::lockWarehouses($this->referencia, [$this->codalmacen, $toWarehouse]);

        // recargamos el origen ya bloqueado para partir de la cantidad real
        if (!empty($this->idstock) && false === $this->loadForUpdate($this->codalmacen, $this->referencia)) {
            return false;
        }

        $destination = new static();
        if ($destination->loadForUpdate($toWarehouse, $this->referencia)) {
            $destination->cantidad += $qty;
        } else {
            $destination->codalmacen = $toWarehouse;
            $destination->idproducto = $this->idproducto;
            $destination->referencia = $this->referencia;
            $destination->cantidad = $qty;
        }

        $this->cantidad -= $qty;

        return $destination->save() && $this->save();
    }

    protected function updateProductStock(): bool
    {
        $total = $this->totalFromProduct($this->idproducto);
        $sql = "UPDATE " . DinProducto::tableName() . " SET stockfis = " . self::db()->var2str($total)
            . ", actualizado = " . self::db()->var2str(Tools::dateTime())
            . " WHERE idproducto = " . self::db()->var2str($this->idproducto) . ';';

        $totalVariant = $this->totalFromProduct($this->idproducto, $this->referencia);
        $sql .= "UPDATE " . DinVariante::tableName() . " SET stockfis = " . self::db()->var2str($totalVariant)
            . " WHERE referencia = " . self::db()->var2str($this->referencia) . ';';

        return self::db()->exec($sql);
    }
}
