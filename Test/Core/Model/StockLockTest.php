<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\LineaAlbaranCliente;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use mysqli;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Comprueba que el stock se lee con bloqueo de fila (SELECT ... FOR UPDATE) al guardar líneas
 * de documentos y al transferir entre almacenes, para que dos peticiones simultáneas no se pisen
 * las cantidades. La concurrencia se comprueba con una segunda conexión a la base de datos
 * que intenta bloquear la misma fila con NOWAIT.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
final class StockLockTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var DataBase Conexión principal de FacturaScripts. */
    private $db;

    /** @var AlbaranCliente[] Albaranes creados en el test, para eliminarlos al terminar. */
    private $docs = [];

    /** @var Producto[] Productos creados en el test, para eliminarlos al terminar. */
    private $products = [];

    /** @var Cliente[] Clientes creados en el test, para eliminarlos al terminar. */
    private $customers = [];

    /** @var Almacen[] Almacenes creados en el test, para eliminarlos al terminar. */
    private $warehouses = [];

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();

        // las tablas no se pueden crear dentro de una transacción: nos aseguramos de que existen
        new LineaAlbaranCliente();
    }

    protected function setUp(): void
    {
        $this->db = new DataBase();
    }

    public function testLoadForUpdate(): void
    {
        $product = $this->createProduct(true);
        $stock = $this->createStock($product, Tools::settings('default', 'codalmacen'), 7);

        // dentro de una transacción carga el stock existente
        $this->assertTrue($this->db->beginTransaction(), 'cant-begin-transaction');
        $locked = new Stock();
        $this->assertTrue($locked->loadForUpdate($stock->codalmacen, $stock->referencia), 'stock-not-loaded');
        $this->assertEquals($stock->idstock, $locked->idstock, 'bad-idstock');
        $this->assertEquals(7, $locked->cantidad, 'bad-cantidad');

        // la fila queda bloqueada para otras conexiones hasta terminar la transacción
        $this->assertFalse($this->otherConnectionCanLock($stock->codalmacen, $stock->referencia), 'stock-row-not-locked');
        $this->assertTrue($this->db->rollback(), 'cant-rollback');
        $this->assertTrue($this->otherConnectionCanLock($stock->codalmacen, $stock->referencia), 'stock-row-still-locked');

        // un stock inexistente devuelve false y deja el modelo limpio
        $missing = new Stock();
        $this->assertFalse($missing->loadForUpdate($stock->codalmacen, 'no-existe-' . mt_rand(1, 99999)), 'missing-stock-loaded');
        $this->assertNull($missing->idstock, 'missing-stock-not-cleared');
    }

    public function testLineSaveLocksStockInOpenTransaction(): void
    {
        $product = $this->createProduct(false);
        $codalmacen = Tools::settings('default', 'codalmacen');
        $stock = $this->createStock($product, $codalmacen, 10);
        $doc = $this->createDoc($codalmacen);

        // guardamos una línea dentro de una transacción abierta por quien llama (como los controladores)
        $this->assertTrue($this->db->beginTransaction(), 'cant-begin-transaction');
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 4;
        $this->assertTrue($line->save(), 'cant-save-line');
        $this->assertTrue($this->db->inTransaction(), 'outer-transaction-closed');

        // mientras no termine la transacción, otra conexión no puede bloquear la fila de stock
        $this->assertFalse($this->otherConnectionCanLock($codalmacen, $product->referencia), 'stock-row-not-locked');

        // al deshacer la transacción se libera la fila y el stock vuelve a su valor
        $this->assertTrue($this->db->rollback(), 'cant-rollback');
        $this->assertTrue($this->otherConnectionCanLock($codalmacen, $product->referencia), 'stock-row-still-locked');
        $this->assertTrue($stock->reload(), 'cant-reload-stock');
        $this->assertEquals(10, $stock->cantidad, 'stock-changed-after-rollback');
    }

    public function testStockReadLocksRowBeforeWriting(): void
    {
        // una venta sin stock suficiente no llega a escribir el stock: si la fila queda bloqueada
        // es porque la lectura se ha hecho con FOR UPDATE, que es lo que evita que dos peticiones
        // lean la misma cantidad y se salten la comprobación de venta sin stock
        $product = $this->createProduct(false);
        $codalmacen = Tools::settings('default', 'codalmacen');
        $this->createStock($product, $codalmacen, 10);
        $doc = $this->createDoc($codalmacen);

        $this->assertTrue($this->db->beginTransaction(), 'cant-begin-transaction');
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 11;
        $this->assertFalse($line->save(), 'can-sell-without-stock');
        $this->assertFalse($this->otherConnectionCanLock($codalmacen, $product->referencia), 'stock-read-without-lock');

        $this->assertTrue($this->db->rollback(), 'cant-rollback');
        $this->assertTrue($this->otherConnectionCanLock($codalmacen, $product->referencia), 'stock-row-still-locked');
    }

    public function testLineSaveWithoutTransaction(): void
    {
        $product = $this->createProduct(false);
        $codalmacen = Tools::settings('default', 'codalmacen');
        $stock = $this->createStock($product, $codalmacen, 10);
        $doc = $this->createDoc($codalmacen);
        $this->assertFalse($this->db->inTransaction(), 'unexpected-open-transaction');

        // sin transacción previa, la línea abre la suya y la confirma
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 4;
        $this->assertTrue($line->save(), 'cant-save-line');
        $this->assertFalse($this->db->inTransaction(), 'stock-transaction-not-closed');
        $this->assertTrue($this->otherConnectionCanLock($codalmacen, $product->referencia), 'stock-row-still-locked');
        $this->assertTrue($stock->reload(), 'cant-reload-stock');
        $this->assertEquals(6, $stock->cantidad, 'bad-stock-after-sale');

        // modificamos la cantidad: se descuenta solo la diferencia
        $line->cantidad = 7;
        $this->assertTrue($line->save(), 'cant-update-line');
        $this->assertTrue($stock->reload(), 'cant-reload-stock');
        $this->assertEquals(3, $stock->cantidad, 'bad-stock-after-update');

        // sin stock suficiente la línea no se guarda, la transacción se deshace y el stock no cambia
        $line->cantidad = 11;
        $this->assertFalse($line->save(), 'can-sell-without-stock');
        $this->assertFalse($this->db->inTransaction(), 'stock-transaction-not-closed-on-error');
        $this->assertTrue($stock->reload(), 'cant-reload-stock');
        $this->assertEquals(3, $stock->cantidad, 'stock-changed-on-error');

        // al eliminar la línea se devuelve el stock
        $line->reload();
        $this->assertTrue($line->delete(), 'cant-delete-line');
        $this->assertTrue($stock->reload(), 'cant-reload-stock');
        $this->assertEquals(10, $stock->cantidad, 'bad-stock-after-delete');
    }

    public function testLineSalesSeeEachOtherStock(): void
    {
        // dos líneas de documentos distintos con el mismo producto y 10 unidades:
        // la segunda debe partir de lo que dejó la primera y no poder vender de más
        $product = $this->createProduct(false);
        $codalmacen = Tools::settings('default', 'codalmacen');
        $stock = $this->createStock($product, $codalmacen, 10);

        $doc1 = $this->createDoc($codalmacen);
        $line1 = $doc1->getNewProductLine($product->referencia);
        $line1->cantidad = 10;
        $this->assertTrue($line1->save(), 'cant-save-line-1');

        $doc2 = $this->createDoc($codalmacen);
        $line2 = $doc2->getNewProductLine($product->referencia);
        $line2->cantidad = 10;
        $this->assertFalse($line2->save(), 'second-sale-without-stock-saved');

        $this->assertTrue($stock->reload(), 'cant-reload-stock');
        $this->assertEquals(0, $stock->cantidad, 'bad-stock-after-sales');
    }

    public function testDocumentWarehouseChangeTransfersStock(): void
    {
        $product = $this->createProduct(true);
        $warehouse1 = Tools::settings('default', 'codalmacen');
        $warehouse2 = $this->createWarehouse()->codalmacen;
        $stock1 = $this->createStock($product, $warehouse1, 10);
        $stock2 = $this->createStock($product, $warehouse2, 5);

        // vendemos 3 unidades desde el primer almacén
        $doc = $this->createDoc($warehouse1);
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 3;
        $this->assertTrue($line->save(), 'cant-save-line');
        $this->assertTrue($stock1->reload(), 'cant-reload-stock-1');
        $this->assertEquals(7, $stock1->cantidad, 'bad-stock-1-after-sale');

        // cambiamos el almacén del documento: el stock pasa de un almacén a otro
        $doc->codalmacen = $warehouse2;
        $this->assertTrue($doc->save(), 'cant-change-warehouse');
        $this->assertFalse($this->db->inTransaction(), 'transfer-transaction-not-closed');
        $this->assertTrue($stock1->reload(), 'cant-reload-stock-1');
        $this->assertTrue($stock2->reload(), 'cant-reload-stock-2');
        $this->assertEquals(10, $stock1->cantidad, 'bad-stock-1-after-transfer');
        $this->assertEquals(2, $stock2->cantidad, 'bad-stock-2-after-transfer');
        $this->assertTrue($this->otherConnectionCanLock($warehouse1, $product->referencia), 'stock-1-still-locked');
        $this->assertTrue($this->otherConnectionCanLock($warehouse2, $product->referencia), 'stock-2-still-locked');
    }

    public function testTransferToUsesCurrentQuantity(): void
    {
        $product = $this->createProduct(true);
        $warehouse1 = Tools::settings('default', 'codalmacen');
        $warehouse2 = $this->createWarehouse()->codalmacen;
        $stock = $this->createStock($product, $warehouse1, 10);

        // otra petición suma 5 unidades después de que hayamos cargado el stock
        $other = new Stock();
        $this->assertTrue($other->load($stock->idstock), 'cant-load-stock');
        $other->cantidad += 5;
        $this->assertTrue($other->save(), 'cant-save-other-stock');

        // la transferencia parte de la cantidad real (15), no de la que teníamos cargada (10)
        $this->assertTrue($stock->transferTo($warehouse2, 3), 'cant-transfer');
        $this->assertFalse($this->db->inTransaction(), 'transfer-transaction-not-closed');
        $this->assertTrue($stock->reload(), 'cant-reload-stock');
        $this->assertEquals(12, $stock->cantidad, 'bad-origin-stock');

        $destination = new Stock();
        $this->assertTrue($destination->loadForUpdate($warehouse2, $product->referencia), 'destination-not-created');
        $this->assertEquals(3, $destination->cantidad, 'bad-destination-stock');

        // a un almacén con stock se le suma la cantidad
        $this->assertTrue($stock->transferTo($warehouse2, 2), 'cant-transfer-2');
        $this->assertTrue($destination->reload(), 'cant-reload-destination');
        $this->assertEquals(5, $destination->cantidad, 'bad-destination-stock-2');
        $this->assertTrue($stock->reload(), 'cant-reload-stock');
        $this->assertEquals(10, $stock->cantidad, 'bad-origin-stock-2');
    }

    protected function tearDown(): void
    {
        // por si algún test ha fallado con la transacción abierta
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }

        // limpiamos cada entidad por separado para no dejar datos aunque falle alguna
        foreach ($this->docs as $doc) {
            try {
                $doc->delete();
            } catch (Throwable $e) {
            }
        }
        foreach ($this->customers as $customer) {
            try {
                $customer->getDefaultAddress()->delete();
                $customer->delete();
            } catch (Throwable $e) {
            }
        }
        foreach ($this->products as $product) {
            try {
                foreach ($product->getVariants() as $variant) {
                    foreach (Stock::all([Where::eq('referencia', $variant->referencia)]) as $stock) {
                        $stock->cantidad = $stock->reservada = $stock->pterecibir = 0;
                        $stock->save();
                    }
                }
                $product->delete();
            } catch (Throwable $e) {
            }
        }
        foreach ($this->warehouses as $warehouse) {
            try {
                $warehouse->delete();
            } catch (Throwable $e) {
            }
        }

        $this->docs = $this->customers = $this->products = $this->warehouses = [];
        $this->logErrors();
    }

    /**
     * Crea un albarán de cliente en el almacén indicado.
     *
     * @param string $codalmacen Código del almacén.
     *
     * @return AlbaranCliente
     */
    private function createDoc(string $codalmacen): AlbaranCliente
    {
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-save-customer');
        $this->customers[] = $customer;

        $doc = new AlbaranCliente();
        $doc->setSubject($customer);
        $doc->codalmacen = $codalmacen;
        $this->assertTrue($doc->save(), 'cant-save-albaran');
        $this->docs[] = $doc;

        return $doc;
    }

    /**
     * Crea un producto que permite o no vender sin stock.
     *
     * @param bool $ventasinstock Si se permite vender sin stock.
     *
     * @return Producto
     */
    private function createProduct(bool $ventasinstock): Producto
    {
        $product = $this->getRandomProduct();
        $product->ventasinstock = $ventasinstock;
        $this->assertTrue($product->save(), 'cant-save-product');
        $this->products[] = $product;

        return $product;
    }

    /**
     * Crea el stock del producto en el almacén indicado.
     *
     * @param Producto $product Producto.
     * @param string $codalmacen Código del almacén.
     * @param float $cantidad Cantidad inicial.
     *
     * @return Stock
     */
    private function createStock(Producto $product, string $codalmacen, float $cantidad): Stock
    {
        $stock = new Stock();
        $stock->codalmacen = $codalmacen;
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = $cantidad;
        $this->assertTrue($stock->save(), 'cant-save-stock');

        return $stock;
    }

    /**
     * Crea un almacén nuevo.
     *
     * @return Almacen
     */
    private function createWarehouse(): Almacen
    {
        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save(), 'cant-save-warehouse');
        $this->warehouses[] = $warehouse;

        return $warehouse;
    }

    /**
     * Abre una segunda conexión a la base de datos e intenta bloquear la fila de stock con NOWAIT.
     * Devuelve false si la fila está bloqueada por otra transacción.
     *
     * @param string $codalmacen Código del almacén.
     * @param string $referencia Referencia de la variante.
     *
     * @return bool
     */
    private function otherConnectionCanLock(string $codalmacen, string $referencia): bool
    {
        $sql = 'SELECT * FROM stocks WHERE codalmacen = ' . $this->db->var2str($codalmacen)
            . ' AND referencia = ' . $this->db->var2str($referencia) . ' FOR UPDATE NOWAIT';

        if (strtolower((string)Tools::config('db_type')) === 'postgresql') {
            $string = 'host=' . Tools::config('db_host') . ' dbname=' . Tools::config('db_name')
                . ' port=' . Tools::config('db_port') . ' user=' . Tools::config('db_user')
                . ' password=' . Tools::config('db_pass');
            $link = pg_connect($string, PGSQL_CONNECT_FORCE_NEW);
            $this->assertNotFalse($link, 'cant-open-second-connection');
            pg_query($link, 'BEGIN');
            $result = @pg_query($link, $sql);
            pg_query($link, 'ROLLBACK');
            pg_close($link);
            return $result !== false;
        }

        $link = new mysqli(
            Tools::config('db_host'),
            Tools::config('db_user'),
            Tools::config('db_pass'),
            Tools::config('db_name'),
            (int)Tools::config('db_port')
        );
        try {
            $link->begin_transaction();
            $result = $link->query($sql);
            $canLock = $result !== false;
        } catch (Throwable $e) {
            $canLock = false;
        }
        $link->rollback();
        $link->close();

        return $canLock;
    }
}
