<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\LogOperation;

/**
 * 測試 LogOperation
 */
class LogOperationTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $uri = 'url';
        $method = 'post';
        $serverIp = '127.0.0.1';
        $clientIp = '192.168.1.1';
        $message = '';
        $tableName = 'LogOperation';
        $majorKey = 'name';
        $sessionId = 'test';

        $entry = new LogOperation(
            $uri,
            $method,
            $serverIp,
            $clientIp,
            $message,
            $tableName,
            $majorKey,
            $sessionId
        );
        $this->assertEquals($tableName, $entry->getTableName());
        $this->assertEquals($majorKey, $entry->getMajorKey());
        $this->assertEquals($message, $entry->getMessage());

        $entry->addMessage('id', 30);
        $entry->addMessage('method', 'post', 'put');
        $this->assertEquals('@id:30, @method:post=>put', $entry->getMessage());

        $entry->setMajorKey(['domain' => 6]);
        $this->assertEquals('@domain:6', $entry->getMajorKey());
        $this->assertEquals('post', $entry->getMethod());

        $entry->setAt(new \Datetime('2014-10-23 00:00:00'));
        $this->assertEquals(new \Datetime('2014-10-23 00:00:00'), $entry->getAt());

        $this->assertEquals('test', $entry->getSessionId());
    }
}
