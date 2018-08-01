<?php
namespace BB\DurianBundle\Tests\Logger;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Logger\Operation;

class OperationTest extends WebTestCase
{
    /**
     * 測試新增操作紀錄
     */
    public function testCreate()
    {
        $mockRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->setMethods(['getRequestUri', 'getMethod', 'getClientIp'])
            ->getMock();

        $mockRequest->expects($this->any())
            ->method('getRequestUri')
            ->will($this->returnValue('uri'));
        $mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue('post'));
        $mockParameterBag= $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->server = $mockParameterBag;
        $mockParameterBag= $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockParameterBag->expects($this->any())
            ->method('get')
            ->with('session-id', null, false)
            ->will($this->returnValue('test'));
        $mockRequest->headers = $mockParameterBag;
        $mockRequest->expects($this->any())
            ->method('getClientIp')
            ->will($this->returnValue('192.168.1.1'));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $mockContainer->expects($this->any())
            ->method('get')
            ->with('request')
            ->will($this->returnValue($mockRequest));

        $operation = new Operation();
        $operation->setContainer($mockContainer);
        $log = $operation->create('log_operation', ['major_key' => 'major']);
        $log->addMessage('message', 'msg');

        $this->assertEquals('log_operation', $log->getTableName());
        $this->assertEquals('@major_key:major', $log->getMajorKey());
        $this->assertEquals('@message:msg', $log->getMessage());
        $this->assertEquals('test', $log->getSessionId());
        $this->assertEquals(gethostname(), $log->getServerName());
        $this->assertEquals('192.168.1.1', $log->getClientIp());
    }

    /**
     * 測試新增操作紀錄，且由command呼叫
     */
    public function testCreateWithCallByCommand()
    {
        $mockCommand = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand')
            ->disableOriginalConstructor()
            ->setMethods(['getName'])
            ->getMock();

        $mockCommand->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('command_name'));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $mockContainer->expects($this->at(0))
            ->method('get')
            ->with('request')
            ->will($this->returnValue(null));

        $mockContainer->expects($this->at(1))
            ->method('get')
            ->with('durian.command')
            ->will($this->returnValue($mockCommand));

        $operation = new Operation();
        $operation->setContainer($mockContainer);
        $log = $operation->create('log_operation', ['major_key' => 'major']);
        $log->addMessage('message', 'msg');

        $this->assertEquals('log_operation', $log->getTableName());
        $this->assertEquals('@major_key:major', $log->getMajorKey());
        $this->assertEquals('@message:msg', $log->getMessage());
        $this->assertEquals('CMD', $log->getMethod());
        $this->assertEmpty($log->getSessionId());
        $this->assertEquals(gethostname(), $log->getServerName());
        $this->assertEquals('127.0.0.1', $log->getClientIp());
    }
}
