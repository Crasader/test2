<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestControllerTest extends WebTestCase
{
    /**
     * 測試連線出現無法新增資料
     */
    public function testConnectioniWithInsertFail()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $em->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue(null));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $client->request('POST', '/api/test/connection');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Database can not insert data', $output['mysql_error'][0]);
    }

    /**
     * 測試連線出現DB無法修改資料
     */
    public function testConnectioniWithUpdateFail()
    {
        $mockTest = $this->getMockBuilder('BB\DurianBundle\Entity\Test')
            ->disableOriginalConstructor()
            ->setMethods(['getMemo', 'setMemo', 'getId'])
            ->getMock();

        $mockTest->expects($this->any())
            ->method('getMemo')
            ->will($this->returnValue('TEST!!'));

        $mockTest->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('0'));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $em->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(5))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $client->request('POST', '/api/test/connection');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Database can not update data', $output['mysql_error'][0]);
    }

    /**
     * 測試連線出現DB無法刪除資料
     */
    public function testConnectioniWithDeleteFail()
    {
        $mockTest = $this->getMockBuilder('BB\DurianBundle\Entity\Test')
            ->disableOriginalConstructor()
            ->setMethods(['getMemo', 'setMemo', 'getId'])
            ->getMock();

        $mockTest->expects($this->any())
            ->method('getMemo')
            ->will($this->returnValue('RESET'));

        $mockTest->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('0'));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'remove', 'clear'])
            ->getMock();

        $em->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(5))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(8))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $client->request('POST', '/api/test/connection');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Database can not delete data', $output['mysql_error'][0]);
    }

    /**
     * 測試連線出現redis無法新增key
     */
    public function testConnectioniWithRedisAddKeyFail()
    {
        $mockTest = $this->getMockBuilder('BB\DurianBundle\Entity\Test')
            ->disableOriginalConstructor()
            ->setMethods(['getMemo', 'setMemo', 'getId'])
            ->getMock();

        $mockTest->expects($this->any())
            ->method('getMemo')
            ->will($this->returnValue('RESET'));

        $mockTest->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('0'));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'remove', 'clear'])
            ->getMock();

        $em->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(5))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(8))
            ->method('find')
            ->will($this->returnValue(null));

        $redis= $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del', 'set', 'get'])
            ->getMock();

        $redis->expects($this->at(2))
            ->method('get')
            ->will($this->returnValue('bbb'));

        $client = $this->createClient();
        $client->getContainer()->set('snc_redis.default', $redis);
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $client->request('POST', '/api/test/connection');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Redis can not set key', $output['redis_error'][0]);
    }

    /**
     * 測試連線出現redis無法刪除key
     */
    public function testConnectioniWithRedisDeleteKeyFail()
    {
        $mockTest = $this->getMockBuilder('BB\DurianBundle\Entity\Test')
            ->disableOriginalConstructor()
            ->setMethods(['getMemo', 'setMemo', 'getId'])
            ->getMock();

        $mockTest->expects($this->any())
            ->method('getMemo')
            ->will($this->returnValue('RESET'));

        $mockTest->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('0'));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'remove', 'clear'])
            ->getMock();

        $em->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(5))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(8))
            ->method('find')
            ->will($this->returnValue(null));

        $redis= $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del', 'set', 'get'])
            ->getMock();

        $redis->expects($this->at(2))
            ->method('get')
            ->will($this->returnValue('bar'));

        $redis->expects($this->at(4))
            ->method('get')
            ->will($this->returnValue('123'));

        $client = $this->createClient();
        $client->getContainer()->set('snc_redis.default', $redis);
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $client->request('POST', '/api/test/connection');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Redis can not delete key', $output['redis_error'][0]);
    }

    /**
     * 測試連線出現DB錯誤且redis無法新增key
     */
    public function testConnectioniWithDbFailAndRedisAddKeyFail()
    {
        $mockTest = $this->getMockBuilder('BB\DurianBundle\Entity\Test')
            ->disableOriginalConstructor()
            ->setMethods(['getMemo', 'setMemo', 'getId'])
            ->getMock();

        $mockTest->expects($this->any())
            ->method('getMemo')
            ->will($this->returnValue('RESET'));

        $mockTest->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('0'));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'remove', 'clear'])
            ->getMock();

        $em->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(5))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $em->expects($this->at(8))
            ->method('find')
            ->will($this->returnValue($mockTest));

        $redis= $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del', 'set', 'get'])
            ->getMock();

        $redis->expects($this->at(2))
            ->method('get')
            ->will($this->returnValue('bbb'));

        $client = $this->createClient();
        $client->getContainer()->set('snc_redis.default', $redis);
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $client->request('POST', '/api/test/connection');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Database can not delete data', $output['mysql_error'][0]);
        $this->assertEquals('Redis can not set key', $output['redis_error'][0]);
    }

    /**
     * 測試連線出現DB錯誤且redis錯誤
     */
    public function testConnectioniWithDbFailAndRedisFail()
    {
        $mockTest = $this->getMockBuilder('BB\DurianBundle\Entity\Test')
            ->disableOriginalConstructor()
            ->setMethods(['getMemo', 'setMemo', 'getId'])
            ->getMock();

        $mockTest->expects($this->any())
            ->method('getMemo')
            ->will($this->returnValue('RESET'));

        $mockTest->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('0'));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'remove', 'clear'])
            ->getMock();

        $em->expects($this->at(1))
            ->method('flush')
            ->will($this->throwException(new \Exception('SQLSTATE[28000] [1045]')));

        $redis= $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del', 'set', 'get'])
            ->getMock();

        $redis->expects($this->at(0))
            ->method('del')
            ->will($this->throwException(new \Exception('Connection refused')));

        $client = $this->createClient();
        $client->getContainer()->set('snc_redis.default', $redis);
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $client->request('POST', '/api/test/connection');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('SQLSTATE[28000] [1045]', $output['mysql_error'][0]);
        $this->assertEquals('Connection refused', $output['redis_error'][0]);
    }

    /**
     * 測試資料庫連線
     */
    public function testCheckDb()
    {
        //測試資料庫連線帶正確值
        $parameters = [
            'redis' => ['default'],
            'mysql' => ['default']
        ];

        $client = $this->createClient();

        $client->request('GET', '/api/test/checkdb', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        //測試資料庫連線DB空白且redis空白
        $client = $this->createClient();

        $client->request('GET', '/api/test/checkdb');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試資料庫名稱錯誤
     */
    public function testCheckDbWithInvalidName()
    {
        //測試資料庫連線DB名稱錯誤且redis名稱錯誤
        $parameters = [
            'redis' => ['Fail'],
            'mysql' => ['Fail']
        ];

        $client = $this->createClient();

        $client->request('GET', '/api/test/checkdb', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);

        //測試資料庫連線DB名稱錯誤
        $parameters = [
            'redis' => ['default'],
            'mysql' => ['Fail']
        ];

        $client = $this->createClient();

        $client->request('GET', '/api/test/checkdb', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);

        //測試資料庫連線redis名稱錯誤
        $parameters = [
            'redis' => ['Fail'],
            'mysql' => ['default']
        ];

        $client = $this->createClient();

        $client->request('GET', '/api/test/checkdb', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
    }

    /**
     * 測試資料庫連線錯誤
     */
    public function testCheckDbWithConnFail()
    {
        // 測試兩欄位皆輸入時連線錯誤訊息
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getconnection')
            ->will($this->throwException(new \Exception('SQLSTATE[HY000] [2002]')));

        $redis= $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['del', 'set', 'get'])
            ->getMock();

        $redis->expects($this->at(0))
            ->method('get')
            ->will($this->throwException(new \Exception('Connection refused')));

        $client = $this->createClient();
        $client->getContainer()->set('snc_redis.default', $redis);
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $parameters = [
            'redis' => ['default'],
            'mysql' => ['default']
        ];

        $client->request('GET', '/api/test/checkdb', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('SQLSTATE[HY000] [2002]', $output['mysql']['default']['mysql_error'][0]);
        $this->assertEquals('Connection refused', $output['redis']['default']['redis_error'][0]);

        // 測試兩欄位皆不輸入時連線錯誤訊息
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getconnection')
            ->will($this->throwException(new \Exception('SQLSTATE[HY000] [2002]')));

        $redis->expects($this->at(0))
            ->method('get')
            ->will($this->throwException(new \Exception('Connection refused')));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);
        $client->getContainer()->set('snc_redis.default', $redis);

        $client->request('GET', '/api/test/checkdb');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('SQLSTATE[HY000] [2002]', $output['mysql']['default']['mysql_error'][0]);
        $this->assertEquals('Connection refused', $output['redis']['default']['redis_error'][0]);

        // 測試mysql連線錯誤訊息
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
        ->disableOriginalConstructor()
        ->getMock();

        $em->expects($this->any())
            ->method('getconnection')
            ->will($this->throwException(new \Exception('SQLSTATE[HY000] [2002]')));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $em);

        $client->request('GET', '/api/test/checkdb');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('SQLSTATE[HY000] [2002]', $output['mysql']['default']['mysql_error'][0]);

        // 測試redis連線錯誤訊息
        $redis->expects($this->at(0))
            ->method('get')
            ->will($this->throwException(new \Exception('Connection refused')));

        $client = $this->createClient();
        $client->getContainer()->set('snc_redis.default', $redis);

        $client->request('GET', '/api/test/checkdb');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Connection refused', $output['redis']['default']['redis_error'][0]);
    }
}
