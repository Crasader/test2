<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ChatRoomFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',

        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadChatRoomData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試回傳使用者聊天室資訊
     */
    public function testGetChatRoom()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/chat_room');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertTrue($output['ret']['readable']);
        $this->assertTrue($output['ret']['writable']);
        $this->assertEquals('9998-01-01T00:00:00+0800', $output['ret']['ban_at']);
    }

    /**
     * 測試回傳使用者聊天室已禁言列表
     */
    public function testGetBanList()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/chat_room/ban_list', ['domain' => 3]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['pagination']['total']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertTrue($output['ret'][0]['readable']);
        $this->assertTrue($output['ret'][0]['writable']);
        $this->assertEquals('9998-01-01T00:00:00+0800', $output['ret'][0]['ban_at']);

        $this->assertEquals(51, $output['ret'][1]['user_id']);
        $this->assertTrue($output['ret'][1]['readable']);
        $this->assertFalse($output['ret'][1]['writable']);
        $this->assertNull($output['ret'][1]['ban_at']);
    }

    /**
     * 測試回傳聊天室預設值
     */
    public function testGetChatRoomReturnDefaultValue()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $user->setTest(true);
        $em->flush();

        $client->request('GET', '/api/user/7/chat_room');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertTrue($output['ret']['readable']);
        $this->assertFalse($output['ret']['writable']);
        $this->assertNull($output['ret']['ban_at']);
    }

    /**
     * 測試回傳使用者聊天室資訊，但使用者不存在
     */
    public function testGetChatRoomButNoSuchUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/9898777/chat_room');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150750001, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試修改使用者聊天室資訊
     */
    public function testEditChatRoom()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/user/8/chat_room', ['readable' => 0]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertFalse($output['ret']['readable']);
        $this->assertTrue($output['ret']['writable']);
        $this->assertEquals('9998-01-01T00:00:00+0800', $output['ret']['ban_at']);

        $chatRoom = $emShare->find('BBDurianBundle:ChatRoom', 8);
        $this->assertFalse($chatRoom->isReadable());
        $this->assertTrue($chatRoom->isWritable());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('chat_room', $log->getTableName());
        $this->assertEquals('@user_id:8', $log->getMajorKey());
        $this->assertEquals('@readable:true=>false', $log->getMessage());
    }

    /**
     * 測試修改使用者聊天室資訊並新增聊天室資料
     */
    public function testEditChatRoomAndCreateChatRoom()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $user->setTest(true);
        $em->flush();

        $param = [
            'readable' => 0,
            'writable' => 1
        ];

        //修改值與預設值不同時會新增聊天室資料
        $client->request('PUT', '/api/user/7/chat_room', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertFalse($output['ret']['readable']);
        $this->assertTrue($output['ret']['writable']);
        $this->assertNull($output['ret']['ban_at']);

        $chatRoom = $emShare->find('BBDurianBundle:ChatRoom', 7);
        $this->assertFalse($chatRoom->isReadable());
        $this->assertTrue($chatRoom->isWritable());
        $this->assertNull($chatRoom->getBanAt());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('chat_room', $log->getTableName());
        $this->assertEquals('@user_id:7', $log->getMajorKey());
        $this->assertEquals('@readable:true, @writable:false, @ban_at:null', $log->getMessage());

        $log = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('chat_room', $log->getTableName());
        $this->assertEquals('@user_id:7', $log->getMajorKey());
        $this->assertEquals('@readable:true=>false, @writable:false=>true', $log->getMessage());
    }

    /**
     * 測試修改時同分秒產生聊天室資訊
     */
    public function testEditChatRoomWithDuplicateKey()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 7);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'clear'])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($user);

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockEmShare->expects($this->at(0))
            ->method('find')
            ->willReturn(null);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception("Duplicate entry '7' for key 'PRIMARY'", 23000, $pdoExcep);

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $param = [
            'readable' => 0,
            'writable' => 0
        ];

        $client->request('PUT', '/api/user/7/chat_room', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150750002, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試修改時新增使用者聊天室資訊，但flush時丟錯誤訊息
     */
    public function testEditChatRoomWithSomeErrorMessage()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 7);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'clear'])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($user);

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockEmShare->expects($this->at(0))
            ->method('find')
            ->willReturn(null);

        $pdoExcep = new \PDOException('failed', 9910);
        $exception = new \Exception('Some error message', 9999, $pdoExcep);

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $param = [
            'readable' => 0,
            'writable' => 0
        ];
        $client->request('PUT', '/api/user/7/chat_room', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(9999, $output['code']);
        $this->assertEquals('Some error message', $output['msg']);
    }

    /**
     * 測試設定禁言時間
     */
    public function testSetBanAt()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $banAt = new \DateTime('+5 days');
        $param = ['ban_at' => $banAt->format(\DateTime::ISO8601)];

        //修改值與預設值不同時會新增聊天室資料
        $client->request('PUT', '/api/user/7/chat_room/ban_at', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertTrue($output['ret']['readable']);
        $this->assertTrue($output['ret']['writable']);
        $this->assertEquals($banAt->format(\DateTime::ISO8601), $output['ret']['ban_at']);

        //禁言時間若大於現在時間，則回傳值為不可寫入，但資料庫資料不變動
        $chatRoom = $emShare->find('BBDurianBundle:ChatRoom', 7);
        $this->assertTrue($chatRoom->isReadable());
        $this->assertTrue($chatRoom->isWritable());
        $this->assertEquals($chatRoom->getBanAt(), $banAt);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('chat_room', $log->getTableName());
        $this->assertEquals('@user_id:7', $log->getMajorKey());
        $this->assertEquals('@readable:true, @writable:true, @ban_at:null', $log->getMessage());

        $log = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('chat_room', $log->getTableName());
        $this->assertEquals('@user_id:7', $log->getMajorKey());
        $this->assertContains('@ban_at:', $log->getMessage());
    }

    /**
     * 測試設定禁言時間同分秒產生使用者聊天室資訊
     */
    public function testSetBanAtWithDuplicateKey()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 7);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'clear'])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($user);

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockEmShare->expects($this->at(0))
            ->method('find')
            ->willReturn(null);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception("Duplicate entry '7' for key 'PRIMARY'", 23000, $pdoExcep);

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $banAt = new \DateTime('+5 days');
        $param = ['ban_at' => $banAt->format(\DateTime::ISO8601)];

        //修改值與預設值不同時會新增聊天室資料
        $client->request('PUT', '/api/user/7/chat_room/ban_at', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150750004, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試回傳時新增使用者聊天室資訊，但flush時丟錯誤訊息
     */
    public function testSetBanAtWithSomeErrorMessage()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 7);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'clear'])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($user);

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockEmShare->expects($this->at(0))
            ->method('find')
            ->willReturn(null);

        $pdoExcep = new \PDOException('failed', 9910);
        $exception = new \Exception('Some error message', 9999, $pdoExcep);

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $banAt = new \DateTime('+5 days');
        $param = ['ban_at' => $banAt->format(\DateTime::ISO8601)];

        //修改值與預設值不同時會新增聊天室資料
        $client->request('PUT', '/api/user/7/chat_room/ban_at', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(9999, $output['code']);
        $this->assertEquals('Some error message', $output['msg']);
    }
}
