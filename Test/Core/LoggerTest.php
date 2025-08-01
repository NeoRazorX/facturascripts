<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Logger;
use FacturaScripts\Dinamic\Model\LogMessage;
use PHPUnit\Framework\TestCase;

/**
 * Description of LoggerTest
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @covers \FacturaScripts\Core\Logger
 */
final class LoggerTest extends TestCase
{
    const TEST_CHANNEL = 'test-logger';

    protected function setUp(): void
    {
        Logger::clear();
        Logger::disable(false);
        Logger::saveMethod('db');
    }

    public function testCritical(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'test-critical-message';
        $context = ['key1' => 'value1', 'key2' => 123];

        $result = $logger->critical($message, $context);
        $this->assertInstanceOf(Logger::class, $result);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(1, $data);
        $this->assertEquals($message, $data[0]['message']);
        $this->assertEquals(Logger::LEVEL_CRITICAL, $data[0]['level']);
        $this->assertEquals(self::TEST_CHANNEL, $data[0]['channel']);
        $this->assertEquals(1, $data[0]['count']);
    }

    public function testError(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'test-error-message';
        $context = ['error_code' => 500];

        $logger->error($message, $context);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(1, $data);
        $this->assertEquals($message, $data[0]['message']);
        $this->assertEquals(Logger::LEVEL_ERROR, $data[0]['level']);
    }

    public function testWarning(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'test-warning-message';

        $logger->warning($message);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(1, $data);
        $this->assertEquals(Logger::LEVEL_WARNING, $data[0]['level']);
    }

    public function testNotice(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'test-notice-message';

        $logger->notice($message);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(1, $data);
        $this->assertEquals(Logger::LEVEL_NOTICE, $data[0]['level']);
    }

    public function testInfo(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'test-info-message';

        $logger->info($message);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(1, $data);
        $this->assertEquals(Logger::LEVEL_INFO, $data[0]['level']);
    }

    public function testDebug(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'test-debug-message';

        $logger->debug($message);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertEmpty($data);
    }

    public function testMultipleChannels(): void
    {
        $channels = ['channel1', 'channel2'];
        $logger = Logger::stack($channels);
        $message = 'multi-channel-message';

        $logger->info($message);

        foreach ($channels as $channel) {
            $data = Logger::readChannel($channel);
            $this->assertCount(1, $data);
            $this->assertEquals($message, $data[0]['message']);
        }

        $fullData = Logger::read();
        $this->assertCount(2, $fullData);
    }

    public function testChannelIsolation(): void
    {
        $logger1 = Logger::channel('channel1');
        $logger2 = Logger::channel('channel2');
        
        $message1 = 'message-for-channel1';
        $message2 = 'message-for-channel2';
        
        $logger1->info($message1);
        $logger2->error($message2);
        
        // Verificar que cada canal solo contiene su mensaje
        $data1 = Logger::readChannel('channel1');
        $this->assertCount(1, $data1);
        $this->assertEquals($message1, $data1[0]['message']);
        $this->assertEquals(Logger::LEVEL_INFO, $data1[0]['level']);
        
        $data2 = Logger::readChannel('channel2');
        $this->assertCount(1, $data2);
        $this->assertEquals($message2, $data2[0]['message']);
        $this->assertEquals(Logger::LEVEL_ERROR, $data2[0]['level']);
        
        // Verificar que un canal no contiene mensajes del otro
        $this->assertNotEquals($message2, $data1[0]['message']);
        $this->assertNotEquals($message1, $data2[0]['message']);
        
        // Verificar que leer un canal inexistente devuelve vacío
        $this->assertEmpty(Logger::readChannel('nonexistent-channel'));
    }

    public function testMessageDeduplication(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'duplicate-message';
        $context = ['same' => 'context'];

        $logger->info($message, $context);
        $logger->info($message, $context);
        $logger->info($message, $context);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(1, $data);
        $this->assertEquals(3, $data[0]['count']);
    }

    public function testMessageWithDifferentContext(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'same-message';

        $logger->info($message, ['key' => 'value1']);
        $logger->info($message, ['key' => 'value2']);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(2, $data);
        $this->assertEquals(1, $data[0]['count']);
        $this->assertEquals(1, $data[1]['count']);
    }

    public function testClearChannel(): void
    {
        $logger1 = Logger::channel('channel1');
        $logger2 = Logger::channel('channel2');

        $logger1->info('message1');
        $logger2->info('message2');

        Logger::clearChannel('channel1');

        $this->assertEmpty(Logger::readChannel('channel1'));
        $this->assertCount(1, Logger::readChannel('channel2'));
    }

    public function testClearAll(): void
    {
        $logger1 = Logger::channel('channel1');
        $logger2 = Logger::channel('channel2');

        $logger1->info('message1');
        $logger2->info('message2');

        Logger::clear();

        $this->assertEmpty(Logger::readChannel('channel1'));
        $this->assertEmpty(Logger::readChannel('channel2'));
        $this->assertEmpty(Logger::read());
    }

    public function testWithContext(): void
    {
        Logger::withContext(['global_key' => 'global_value']);

        $logger = Logger::channel(self::TEST_CHANNEL);
        $logger->info('test-message', ['local_key' => 'local_value']);

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(1, $data);
        $this->assertEquals('global_value', $data[0]['context']['global_key']);
        $this->assertEquals('local_value', $data[0]['context']['local_key']);

        Logger::clearContext();
    }

    public function testClearContextRemovesGlobalContext(): void
    {
        // Añadir contexto global
        Logger::withContext(['global_key' => 'global_value', 'another_key' => 'another_value']);

        $logger = Logger::channel(self::TEST_CHANNEL);
        $logger->info('message-with-context', ['local_key' => 'local_value']);

        // Verificar que el contexto global está presente
        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('global_key', $data[0]['context']);
        $this->assertArrayHasKey('another_key', $data[0]['context']);
        $this->assertEquals('global_value', $data[0]['context']['global_key']);

        // Limpiar contexto
        Logger::clearContext();

        // Añadir nuevo mensaje después de limpiar contexto
        $logger->warning('message-after-clear', ['new_local_key' => 'new_value']);

        $allData = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(2, $allData);

        // El segundo mensaje no debe tener el contexto global anterior
        $secondMessage = $allData[1];
        $this->assertArrayNotHasKey('global_key', $secondMessage['context']);
        $this->assertArrayNotHasKey('another_key', $secondMessage['context']);
        $this->assertArrayHasKey('new_local_key', $secondMessage['context']);
        $this->assertEquals('new_value', $secondMessage['context']['new_local_key']);
    }

    public function testDisableEnable(): void
    {
        Logger::disable(true);
        $this->assertTrue(Logger::disabled());

        $logger = Logger::channel(self::TEST_CHANNEL);
        $logger->info('disabled-message');

        $this->assertEmpty(Logger::readChannel(self::TEST_CHANNEL));

        Logger::disable(false);
        $this->assertFalse(Logger::disabled());

        $logger->info('enabled-message');
        $this->assertCount(1, Logger::readChannel(self::TEST_CHANNEL));
    }

    public function testReadWithLimit(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);

        for ($i = 1; $i <= 5; $i++) {
            $logger->info("message-$i");
        }

        $all = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertCount(5, $all);

        $first3 = Logger::readChannel(self::TEST_CHANNEL, [], 3);
        $this->assertCount(3, $first3);

        $last2 = Logger::readChannel(self::TEST_CHANNEL, [], -2);
        $this->assertCount(2, $last2);
    }

    public function testReadByLevel(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);

        $logger->error('error-message');
        $logger->warning('warning-message');
        $logger->info('info-message');

        $errors = Logger::readLevel(Logger::LEVEL_ERROR);
        $this->assertCount(1, $errors);
        $this->assertEquals('error-message', $errors[0]['message']);

        $warnings = Logger::readLevel(Logger::LEVEL_WARNING);
        $this->assertCount(1, $warnings);
        $this->assertEquals('warning-message', $warnings[0]['message']);
    }

    public function testSaveMethod(): void
    {
        $this->assertTrue(Logger::saveMethod(Logger::SAVE_METHOD_DB));
        $this->assertTrue(Logger::saveMethod(Logger::SAVE_METHOD_FILE));
        $this->assertFalse(Logger::saveMethod('invalid'));
    }

    public function testSaveChannelToDB(): void
    {
        $where = [new DataBaseWhere('channel', self::TEST_CHANNEL)];
        foreach (LogMessage::all($where, [], 0, 0) as $item) {
            $item->delete();
        }

        $logger = Logger::channel(self::TEST_CHANNEL);
        $message = 'test-save-message';
        $logger->error($message, ['test_key' => 'test_value']);

        $this->assertTrue(Logger::saveChannelToDB(self::TEST_CHANNEL));
        $this->assertEmpty(Logger::readChannel(self::TEST_CHANNEL));

        $items = LogMessage::all($where, [], 0, 0);
        $this->assertCount(1, $items);
        $this->assertEquals($message, $items[0]->message);
        $this->assertEquals(Logger::LEVEL_ERROR, $items[0]->level);
        $this->assertEquals(self::TEST_CHANNEL, $items[0]->channel);

        foreach ($items as $item) {
            $item->delete();
        }
    }

    public function testMaxItemsLimit(): void
    {
        $logger = Logger::channel(self::TEST_CHANNEL);

        for ($i = 1; $i <= Logger::MAX_ITEMS + 10; $i++) {
            $logger->info("message-$i");
        }

        $data = Logger::readChannel(self::TEST_CHANNEL);
        $this->assertLessThanOrEqual(Logger::MAX_ITEMS, count($data));
    }

    protected function tearDown(): void
    {
        Logger::clear();
        Logger::clearContext();
    }
}
