<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Doctrine\ORM\OptimisticLockException;

class PetitionFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPetitionData'
        ];

        $this->loadFixtures($classnames, 'share');

        $this->clearSensitiveLog();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=UserDetailFunctionsTest.php&operator_id=2&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
    }

    /**
     * 測試新增提交單
     */
    public function testCreate()
    {
        $client = $this->createClient();

        // 測試新增提交單
        $parameters = ['user_id' => 10, 'value' => '李四', 'operator' => 'ttadmin'];

        $client->request('POST', '/api/petition', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證頁面
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('10', $output['ret']['user_id']);
        $this->assertEquals('李四', $output['ret']['value']);
        $this->assertEquals('ttadmin', $output['ret']['operator']);
        $this->assertEquals('叮叮說你好', $output['ret']['old_value']);
        $this->assertTrue($output['ret']['untreated']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['cancel']);

        // 驗證petition table是否有新增資料
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $petition = $emShare->find('BBDurianBundle:Petition', 4);
        $this->assertEquals('叮叮說你好', $petition->getOldValue());
        $this->assertEquals('10', $petition->getUserId());
        $this->assertEquals(9, $petition->getDomain());
        $this->assertEquals(7, $petition->getRole());
        $this->assertEquals('李四', $petition->getValue());
        $this->assertEquals('ttadmin', $petition->getOperator());

        $this->assertTrue($petition->isUntreated());
        $this->assertFalse($petition->isConfirm());
        $this->assertFalse($petition->isCancel());

        // 操作紀錄檢查
        $value = $petition->getValue();
        $operator = $petition->getOperator();

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('petition', $logOperation->getTableName());
        $this->assertEquals('@user_id:10', $logOperation->getMajorKey());
        $this->assertEquals("@value:$value, @operator:$operator", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試新增提交單，value 會過濾特殊字元
     */
    public function testCreateValueContainsSpecialCharacter()
    {
        $client = $this->createClient();

        // 測試新增提交單
        $parameters = ['user_id' => 10, 'value' => '李四', 'operator' => 'ttadmin'];

        $client->request('POST', '/api/petition', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證頁面
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('10', $output['ret']['user_id']);
        $this->assertEquals('李四', $output['ret']['value']);
        $this->assertEquals('ttadmin', $output['ret']['operator']);
        $this->assertEquals('叮叮說你好', $output['ret']['old_value']);
        $this->assertTrue($output['ret']['untreated']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['cancel']);

        // 驗證petition table是否有新增資料
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $petition = $emShare->find('BBDurianBundle:Petition', 4);
        $this->assertEquals('叮叮說你好', $petition->getOldValue());
        $this->assertEquals('10', $petition->getUserId());
        $this->assertEquals(9, $petition->getDomain());
        $this->assertEquals(7, $petition->getRole());
        $this->assertEquals('李四', $petition->getValue());
        $this->assertEquals('ttadmin', $petition->getOperator());

        $this->assertTrue($petition->isUntreated());
        $this->assertFalse($petition->isConfirm());
        $this->assertFalse($petition->isCancel());

        // 操作紀錄檢查
        $value = $petition->getValue();
        $operator = $petition->getOperator();

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('petition', $logOperation->getTableName());
        $this->assertEquals('@user_id:10', $logOperation->getMajorKey());
        $this->assertEquals("@value:$value, @operator:$operator", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試新增提交單，輸入新資料值與原值相同
     */
    public function testCreateWithSameValue()
    {
        $client = $this->createClient();

        $parameters = [
            'user_id' => 10,
            'value' => '叮叮說你好',
            'operator' => 'ttadmin'
        ];

        $client->request('POST', '/api/petition', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150310006, $output['code']);
        $this->assertEquals('The value can not be the same as the original value', $output['msg']);
    }

    /**
     * 測試新增提交單，使用測試帳號
     */
    public function testCreateWithTestUser()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 10);
        $user->setTest(true);
        $em->flush();

        $parameters = [
            'user_id' => 10,
            'value' => '叮叮說你好',
            'operator' => 'ttadmin'
        ];

        $client->request('POST', '/api/petition', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150310012, $output['code']);
        $this->assertEquals('Test user can not create petition', $output['msg']);
    }

    /**
     * 測試撤銷提交單
     */
    public function testCancel()
    {
        $client = $this->createClient();

        // 測試撤銷提交單
        $client->request('PUT', '/api/petition/1/cancel');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證頁面
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('8', $output['ret']['user_id']);
        $this->assertEquals('張三', $output['ret']['value']);
        $this->assertEquals('cinned', $output['ret']['operator']);
        $this->assertFalse($output['ret']['untreated']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertTrue($output['ret']['cancel']);

        // 驗證petition table是否有撤銷資料
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $petition = $emShare->find('BBDurianBundle:Petition', 1);
        $this->assertFalse($petition->isUntreated());
        $this->assertTrue($petition->isCancel());

        // 操作紀錄檢查
        $activeAt =  $petition->getActiveAt()->format('Y-m-d H:i:s');
        $message = "@untreated:true=>false, @cancel:false=>true, @activeAt:$activeAt";

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('petition', $logOperation->getTableName());
        $this->assertEquals('@petition_id:1', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());
    }

    /**
     * 測試撤銷提交單時發生OptimisticLockException
     */
    public function testCancelButOptimisticLockException()
    {
        $client = $this->createClient();

        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $petition = $emShare->find('BBDurianBundle:Petition', 1);

        $exception = new OptimisticLockException('Test for OptimisticLockException', null);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($petition));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);

        $client->request('PUT', '/api/petition/1/cancel');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150310008, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試取得提交單列表
     */
    public function testGetList()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 測試僅送untreated
        $parameters = ['untreated' => 1];

        $client->request('GET', '/api/petition/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $petition = $emShare->find('BBDurianBundle:Petition', 1);

        $createdAt = $petition->getCreatedAt()->format(\DateTime::ISO8601);

        $activeAt = null;
        if ($petition->getActiveAt()) {
            $activeAt = $petition->getActiveAt()->format(\DateTime::ISO8601);
        }

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($petition->getId(), $output['ret'][0]['id']);
        $this->assertEquals($petition->getValue(), $output['ret'][0]['value']);
        $this->assertEquals($petition->getOperator(), $output['ret'][0]['operator']);
        $this->assertEquals($createdAt, $output['ret'][0]['created_at']);
        $this->assertEquals($activeAt, $output['ret'][0]['active_at']);
        $this->assertEquals($petition->isUntreated(), $output['ret'][0]['untreated']);
        $this->assertEquals($petition->getOldValue(), $output['ret'][0]['old_value']);
        $this->assertEquals($petition->isConfirm(), $output['ret'][0]['confirm']);
        $this->assertEquals($petition->isCancel(), $output['ret'][0]['cancel']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 測試僅送id
        $parameters = ['id' => 1];

        $client->request('GET', '/api/petition/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $petition = $emShare->find('BBDurianBundle:Petition', 1);

        $createdAt = $petition->getCreatedAt()->format(\DateTime::ISO8601);

        $activeAt = null;
        if ($petition->getActiveAt()) {
            $activeAt = $petition->getActiveAt()->format(\DateTime::ISO8601);
        }

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($petition->getId(), $output['ret'][0]['id']);
        $this->assertEquals($petition->getValue(), $output['ret'][0]['value']);
        $this->assertEquals($petition->getOperator(), $output['ret'][0]['operator']);
        $this->assertEquals($createdAt, $output['ret'][0]['created_at']);
        $this->assertEquals($activeAt, $output['ret'][0]['active_at']);
        $this->assertEquals($petition->isUntreated(), $output['ret'][0]['untreated']);
        $this->assertEquals($petition->isConfirm(), $output['ret'][0]['confirm']);
        $this->assertEquals($petition->isCancel(), $output['ret'][0]['cancel']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 測試僅送domain
        $parameters = ['domain' => 9];

        $client->request('GET', '/api/petition/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $petition = $emShare->find('BBDurianBundle:Petition', 2);

        $createdAt = $petition->getCreatedAt()->format(\DateTime::ISO8601);

        $activeAt = null;
        if ($petition->getActiveAt()) {
            $activeAt = $petition->getActiveAt()->format(\DateTime::ISO8601);
        }

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($petition->getId(), $output['ret'][0]['id']);
        $this->assertEquals($petition->getValue(), $output['ret'][0]['value']);
        $this->assertEquals($petition->getOperator(), $output['ret'][0]['operator']);
        $this->assertEquals($createdAt, $output['ret'][0]['created_at']);
        $this->assertEquals($activeAt, $output['ret'][0]['active_at']);
        $this->assertEquals($petition->isUntreated(), $output['ret'][0]['untreated']);
        $this->assertEquals($petition->isConfirm(), $output['ret'][0]['confirm']);
        $this->assertEquals($petition->isCancel(), $output['ret'][0]['cancel']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 測試僅送userId
        $parameters = ['user_id' => 6];

        $client->request('GET', '/api/petition/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $petition = $emShare->find('BBDurianBundle:Petition', 3);

        $createdAt = $petition->getCreatedAt()->format(\DateTime::ISO8601);

        $activeAt = null;
        if ($petition->getActiveAt()) {
            $activeAt = $petition->getActiveAt()->format(\DateTime::ISO8601);
        }

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($petition->getId(), $output['ret'][0]['id']);
        $this->assertEquals($petition->getValue(), $output['ret'][0]['value']);
        $this->assertEquals($petition->getOperator(), $output['ret'][0]['operator']);
        $this->assertEquals($createdAt, $output['ret'][0]['created_at']);
        $this->assertEquals($activeAt, $output['ret'][0]['active_at']);
        $this->assertEquals($petition->isUntreated(), $output['ret'][0]['untreated']);
        $this->assertEquals($petition->isConfirm(), $output['ret'][0]['confirm']);
        $this->assertEquals($petition->isCancel(), $output['ret'][0]['cancel']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 測試僅送role
        $parameters = ['role' => 3];

        $client->request('GET', '/api/petition/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $petition = $emShare->find('BBDurianBundle:Petition', 3);

        $createdAt = $petition->getCreatedAt()->format(\DateTime::ISO8601);

        $activeAt = null;
        if ($petition->getActiveAt()) {
            $activeAt = $petition->getActiveAt()->format(\DateTime::ISO8601);
        }

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($petition->getId(), $output['ret'][0]['id']);
        $this->assertEquals($petition->getValue(), $output['ret'][0]['value']);
        $this->assertEquals($petition->getOperator(), $output['ret'][0]['operator']);
        $this->assertEquals($createdAt, $output['ret'][0]['created_at']);
        $this->assertEquals($activeAt, $output['ret'][0]['active_at']);
        $this->assertEquals($petition->isUntreated(), $output['ret'][0]['untreated']);
        $this->assertEquals($petition->isConfirm(), $output['ret'][0]['confirm']);
        $this->assertEquals($petition->isCancel(), $output['ret'][0]['cancel']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試搜尋已取消的提交單列表
     */
    public function testListCancelPetition()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'user_id' => 6,
            'sort' => 'domain',
            'order' => 'asc',
            'cancel' => '1',
            'first_result' => 0,
            'max_results' => 20
        ];

        $client->request('GET', '/api/petition/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $petition = $emShare->find('BBDurianBundle:Petition', 3);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($petition->getId(), $output['ret'][0]['id']);
        $this->assertEquals($petition->isCancel(), $output['ret'][0]['cancel']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(20, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試搜尋已確認的提交單列表
     */
    public function testListConfirmPetition()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'domain' => 9,
            'sort' => ['id', 'role'],
            'order' => 'asc',
            'confirm' => '1',
            'first_result' => 0,
            'max_results' => 20
        ];

        $client->request('GET', '/api/petition/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $petition = $emShare->find('BBDurianBundle:Petition', 2);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($petition->getId(), $output['ret'][0]['id']);
        $this->assertEquals($petition->isConfirm(), $output['ret'][0]['confirm']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試確認提交單
     */
    public function testConfirm()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $petition = $emShare->find('BBDurianBundle:Petition', 1);
        $user = $em->find('BBDurianBundle:User', '8');
        $userDetail = $em
            ->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($user->getId());

        $oriUserStamp = $user->getModifiedAt()->getTimestamp();
        $oriNameReal = $userDetail->getNameReal();

        $em->clear();
        $emShare->clear();

        // 測試確認提交單
        $client->request('PUT', '/api/petition/1/confirm', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證頁面資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('8', $output['ret']['user_id']);
        $this->assertEquals('張三', $output['ret']['value']);
        $this->assertEquals('cinned', $output['ret']['operator']);
        $this->assertFalse($output['ret']['untreated']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertFalse($output['ret']['cancel']);

        // 驗證user table是否有更新修改時間
        $now = new \DateTime();
        $nowStamp = $now->getTimestamp();
        $modifiedAtStamp = $user->getModifiedAt()->getTimestamp();

        $this->assertGreaterThanOrEqual(0, $modifiedAtStamp - $oriUserStamp);

        // 驗證petition table是否有確認資料
        $petition = $emShare->find('BBDurianBundle:Petition', 1);
        $modifiedAtStamp = $petition->getActiveAt()->getTimestamp();

        $this->assertEquals($oriNameReal, $petition->getOldValue());
        $this->assertFalse($petition->isUntreated());
        $this->assertTrue($petition->isConfirm());
        $this->assertLessThanOrEqual(3, $nowStamp - $modifiedAtStamp);

        // 驗證user_detail table是否有修改真實姓名
        $user = $em->find('BBDurianBundle:User', 8);
        $userDetail = $em
            ->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($user->getId());
        $this->assertEquals('張三', $userDetail->getNameReal());

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_detail', $logOperation->getTableName());
        $this->assertEquals('@user_id:8', $logOperation->getMajorKey());
        $this->assertEquals('@name_real:達文西=>張三', stripslashes($logOperation->getMessage()));

        // 使用者操作紀錄檢查
        $userLogOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user', $userLogOperation->getTableName());
        $this->assertEquals('@id:8', $userLogOperation->getMajorKey());
        $this->assertEquals('@modifiedAt', substr($userLogOperation->getMessage(), 0, 11));

        // 操作紀錄檢查
        $activeAt =  $petition->getActiveAt()->format('Y-m-d H:i:s');
        $message = "@untreated:true=>false, @confirm:false=>true, @activeAt:$activeAt";

        $petitionOperation = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('petition', $petitionOperation->getTableName());
        $this->assertEquals('@petition_id:1', $petitionOperation->getMajorKey());
        $this->assertEquals($message, $petitionOperation->getMessage());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試確認通過提交單時發生OptimisticLockException
     */
    public function testConfirmButOptimisticLockException()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $petition = $emShare->find('BBDurianBundle:Petition', 1);
        $user = $em->find('BBDurianBundle:User', 8);
        $detail = $em->find('BBDurianBundle:UserDetail', 6);

        $exception = new OptimisticLockException('Test for OptimisticLockException', null);

        $repo= $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByUser'])
            ->getMock();

        $repo->expects($this->any())
            ->method('findOneByUser')
            ->will($this->returnValue($detail));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'flush', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockEmShare->expects($this->any())
            ->method('find')
            ->willReturn($petition);

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $client->request('PUT', '/api/petition/1/confirm', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150310011, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試新增提交單但真實姓名在黑名單中
     */
    public function testCreateButNameRealInBlacklist()
    {
        $client = $this->createClient();

        // 測試新增提交單
        $parameters = [
            'user_id' => 8,
            'value' => '控端指定廳人工新增黑名單',
            'operator' => 'ttadmin',
            'verify_blacklist' => 1
        ];

        $client->request('POST', '/api/petition', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650017, $output['code']);
        $this->assertEquals('This name_real has been blocked', $output['msg']);

        // 測試帶後綴詞一樣會阻擋
        $parameters = [
            'user_id' => 8,
            'value' => '控端指定廳人工新增黑名單-3',
            'operator' => 'ttadmin',
            'verify_blacklist' => 1
        ];

        $client->request('POST', '/api/petition', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650017, $output['code']);
        $this->assertEquals('This name_real has been blocked', $output['msg']);
    }

    /**
     * 測試新增提交單跳過檢查黑名單
     */
    public function testCreateWithoutCheckBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'user_id' => 8,
            'value' => '王小明',
            'operator' => 'ttadmin',
            'verify_blacklist' => 0
        ];

        $client->request('POST', '/api/petition', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('王小明', $output['ret']['value']);
        $this->assertEquals('ttadmin', $output['ret']['operator']);
    }

    /**
     * 測試新增提交單，但輸入過長真實姓名
     */
    public function testCreateButNameRealInvalidLength()
    {
        $client = $this->createClient();

        // 測試新增提交單
        $parameters = [
            'user_id' => 8,
            'value' => '我是用來測試真實姓名的字串我是用來測試真實姓名的字串我是用來測試真實姓名的字串'
            . '我是用來測試真實姓名的字串我是用來測試真實姓名的字串我是用來測試真實姓名的字串'
            . '我是用來測試真實姓名的字串我是用來測試真實姓名的字串我是用來測試真實姓名的字串',
            'operator' => 'ttadmin'
        ];

        $client->request('POST', '/api/petition', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150310013, $output['code']);
        $this->assertEquals('Invalid value length given', $output['msg']);
    }
}
