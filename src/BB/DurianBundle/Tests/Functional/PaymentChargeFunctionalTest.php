<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\Deposit;
use BB\DurianBundle\Entity\CashDepositEntry;

class PaymentChargeFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositOnlineData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositCompanyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayFeeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentWithdrawFeeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentWithdrawVerifyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositMobileData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositBitcoinData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試新增線上支付設定
     */
    public function testCreatePaymentCharge()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $pwfRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawFee');
        $pwvRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawVerify');

        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'name' => '英磅',
            'preset' => false,
            'code' => 'GBP'
        ];

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $pcid = $output['ret']['id'];
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('英磅', $output['ret']['name']);
        $this->assertEquals('GBP', $output['ret']['code']);
        $this->assertEquals(0, $output['ret']['rank']);
        $this->assertFalse($output['ret']['preset']);
        $this->assertEquals(1, $output['ret']['version']);

        $pc = $em->find('BBDurianBundle:PaymentCharge', $pcid);

        // 檢查DepositOnline為預設值
        $online = $pc->getDepositOnline();
        $this->assertFalse($online->isDiscountGiveUp());
        $this->assertTrue($online->isAuditLive());
        $this->assertTrue($online->isAuditBall());
        $this->assertTrue($online->isAuditComplex());
        $this->assertTrue($online->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $online->getDiscount());
        $this->assertEquals(100, $online->getDiscountAmount());
        $this->assertEquals(0, $online->getDiscountPercent());
        $this->assertEquals(1, $online->getDiscountFactor());
        $this->assertEquals(0, $online->getDiscountLimit());
        $this->assertEquals(1000, $online->getDepositMax());
        $this->assertEquals(10, $online->getDepositMin());
        $this->assertEquals(10, $online->getAuditLiveAmount());
        $this->assertEquals(10, $online->getAuditBallAmount());
        $this->assertEquals(10, $online->getAuditComplexAmount());
        $this->assertEquals(100, $online->getAuditNormalAmount());
        $this->assertEquals(0, $online->getAuditDiscountAmount());
        $this->assertEquals(10, $online->getAuditLoosen());
        $this->assertEquals(0, $online->getAuditAdministrative());

        // 檢查DepositCompany為預設值
        $company = $pc->getDepositCompany();
        $this->assertFalse($company->isDiscountGiveUp());
        $this->assertFalse($company->isAuditLive());
        $this->assertFalse($company->isAuditBall());
        $this->assertTrue($company->isAuditComplex());
        $this->assertFalse($company->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $company->getDiscount());
        $this->assertEquals(1000, $company->getDiscountAmount());
        $this->assertEquals(15, $company->getDiscountPercent());
        $this->assertEquals(1, $company->getDiscountFactor());
        $this->assertEquals(10000, $company->getDiscountLimit());
        $this->assertEquals(30000, $company->getDepositMax());
        $this->assertEquals(100, $company->getDepositMin());
        $this->assertEquals(0, $company->getAuditLiveAmount());
        $this->assertEquals(0, $company->getAuditBallAmount());
        $this->assertEquals(10, $company->getAuditComplexAmount());
        $this->assertEquals(100, $company->getAuditNormalAmount());
        $this->assertEquals(0, $company->getAuditDiscountAmount());
        $this->assertEquals(0, $company->getAuditLoosen());
        $this->assertEquals(0, $company->getAuditAdministrative());
        $this->assertEquals(100, $company->getOtherDiscountAmount());
        $this->assertEquals(0, $company->getOtherDiscountPercent());
        $this->assertEquals(0, $company->getOtherDiscountLimit());
        $this->assertEquals(0, $company->getDailyDiscountLimit());
        $this->assertEquals(0, $company->getDepositScMax());
        $this->assertEquals(0, $company->getDepositScMin());
        $this->assertEquals(0, $company->getDepositCoMax());
        $this->assertEquals(0, $company->getDepositCoMin());
        $this->assertEquals(0, $company->getDepositSaMax());
        $this->assertEquals(0, $company->getDepositSaMin());
        $this->assertEquals(0, $company->getDepositAgMax());
        $this->assertEquals(0, $company->getDepositAgMin());

        // 檢查DepositMobile為預設值
        $mobile = $pc->getDepositMobile();
        $this->assertFalse($mobile->isDiscountGiveUp());
        $this->assertTrue($mobile->isAuditLive());
        $this->assertTrue($mobile->isAuditBall());
        $this->assertTrue($mobile->isAuditComplex());
        $this->assertTrue($mobile->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $mobile->getDiscount());
        $this->assertEquals(100, $mobile->getDiscountAmount());
        $this->assertEquals(0, $mobile->getDiscountPercent());
        $this->assertEquals(1, $mobile->getDiscountFactor());
        $this->assertEquals(0, $mobile->getDiscountLimit());
        $this->assertEquals(1000, $mobile->getDepositMax());
        $this->assertEquals(10, $mobile->getDepositMin());
        $this->assertEquals(10, $mobile->getAuditLiveAmount());
        $this->assertEquals(10, $mobile->getAuditBallAmount());
        $this->assertEquals(10, $mobile->getAuditComplexAmount());
        $this->assertEquals(100, $mobile->getAuditNormalAmount());
        $this->assertEquals(0, $mobile->getAuditDiscountAmount());
        $this->assertEquals(10, $mobile->getAuditLoosen());
        $this->assertEquals(0, $mobile->getAuditAdministrative());

        // 檢查DepositMobile為預設值
        $bitcoin = $pc->getDepositBitcoin();
        $this->assertFalse($bitcoin->isDiscountGiveUp());
        $this->assertFalse($bitcoin->isAuditLive());
        $this->assertFalse($bitcoin->isAuditBall());
        $this->assertFalse($bitcoin->isAuditComplex());
        $this->assertFalse($bitcoin->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $bitcoin->getDiscount());
        $this->assertEquals(0, $bitcoin->getDiscountAmount());
        $this->assertEquals(0, $bitcoin->getDiscountPercent());
        $this->assertEquals(1, $bitcoin->getDiscountFactor());
        $this->assertEquals(0, $bitcoin->getDiscountLimit());
        $this->assertEquals(30000, $bitcoin->getDepositMax());
        $this->assertEquals(100, $bitcoin->getDepositMin());
        $this->assertEquals(0, $bitcoin->getAuditLiveAmount());
        $this->assertEquals(0, $bitcoin->getAuditBallAmount());
        $this->assertEquals(0, $bitcoin->getAuditComplexAmount());
        $this->assertEquals(100, $bitcoin->getAuditNormalAmount());
        $this->assertEquals(0, $bitcoin->getAuditDiscountAmount());
        $this->assertEquals(0, $bitcoin->getAuditLoosen());
        $this->assertEquals(0, $bitcoin->getAuditAdministrative());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_charge', $logOperation->getTableName());
        $this->assertEquals('@payment_charge_id:' . $pcid, $logOperation->getMajorKey());
        $this->assertEquals('@payway:1, @domain:2, @name:英磅, @preset:false, @code:GBP', $logOperation->getMessage());

        //驗證新增的支付平台手續費
        $pgfs = $pgfRepo->findBy(['paymentCharge' => $pcid]);

        // CCPay(id=77)為已刪除的支付平台，不會新增
        $this->assertEquals(5, count($pgfs));

        $removedGateway = $em->find('BBDurianBundle:PaymentGateway', 77);
        $this->assertTrue($removedGateway->isRemoved());

        $pgf = $pgfs[0]->toArray();
        $this->assertEquals(1, $pgf['payment_gateway_id']);
        $this->assertEquals($pcid, $pgf['payment_charge_id']);
        $this->assertEquals(0, $pgf['rate']);

        $pgf = $pgfs[1]->toArray();
        $this->assertEquals(2, $pgf['payment_gateway_id']);
        $this->assertEquals($pcid, $pgf['payment_charge_id']);
        $this->assertEquals(0, $pgf['rate']);

        $pgf = $pgfs[2]->toArray();
        $this->assertEquals(67, $pgf['payment_gateway_id']);
        $this->assertEquals($pcid, $pgf['payment_charge_id']);
        $this->assertEquals(0, $pgf['rate']);

        $pgf = $pgfs[3]->toArray();
        $this->assertEquals(68, $pgf['payment_gateway_id']);
        $this->assertEquals($pcid, $pgf['payment_charge_id']);
        $this->assertEquals(0, $pgf['rate']);

        $pwfs = $pwfRepo->findBy(['paymentCharge' => $pcid]);
        $pwf = $pwfs[0]->toArray();
        $this->assertEquals($pcid, $pwf['payment_charge_id']);
        $this->assertEquals(24, $pwf['free_period']);
        $this->assertEquals(1, $pwf['free_count']);
        $this->assertEquals(50, $pwf['amount_max']);
        $this->assertEquals(1, $pwf['amount_percent']);

        $pwvs = $pwvRepo->findBy(['paymentCharge' => $pcid]);
        $pwv = $pwvs[0]->toArray();
        $this->assertEquals($pcid, $pwv['payment_charge_id']);
        $this->assertTrue($pwv['need_verify']);
        $this->assertEquals(24, $pwv['verify_time']);
        $this->assertEquals(5000, $pwv['verify_amount']);
    }

    /**
     * 測試使用來源來新增線上支付設定
     */
    public function testCreatePaymentChargeWithSource()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $pc = $em->find('BBDurianBundle:PaymentCharge', 6);

        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'name' => '英磅',
            'preset' => false,
            'source' => 6,
            'code' => 'GBP',
        ];

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $pcid = $output['ret']['id'];
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('英磅', $output['ret']['name']);
        $this->assertEquals('GBP', $output['ret']['code']);
        $this->assertEquals(0, $output['ret']['rank']);
        $this->assertFalse($output['ret']['preset']);
        $this->assertEquals(1, $output['ret']['version']);

        // 檢查DepositOnline為預設值
        $online = $pc->getDepositOnline();
        $this->assertTrue($online->isDiscountGiveUp());
        $this->assertTrue($online->isAuditLive());
        $this->assertTrue($online->isAuditBall());
        $this->assertTrue($online->isAuditComplex());
        $this->assertTrue($online->isAuditNormal());
        $this->assertTrue($online->isAudit3D());
        $this->assertTrue($online->isAuditBattle());
        $this->assertTrue($online->isAuditVirtual());
        $this->assertEquals(Deposit::EACH, $online->getDiscount());
        $this->assertEquals(100, $online->getDiscountAmount());
        $this->assertEquals(2.12, $online->getDiscountPercent());
        $this->assertEquals(1, $online->getDiscountFactor());
        $this->assertEquals(500, $online->getDiscountLimit());
        $this->assertEquals(7000, $online->getDepositMax());
        $this->assertEquals(100, $online->getDepositMin());
        $this->assertEquals(10, $online->getAuditLiveAmount());
        $this->assertEquals(20, $online->getAuditBallAmount());
        $this->assertEquals(30, $online->getAuditComplexAmount());
        $this->assertEquals(100, $online->getAuditNormalAmount());
        $this->assertEquals(40, $online->getAudit3DAmount());
        $this->assertEquals(50, $online->getAuditBattleAmount());
        $this->assertEquals(60, $online->getAuditVirtualAmount());
        $this->assertEquals(5, $online->getAuditDiscountAmount());
        $this->assertEquals(40, $online->getAuditLoosen());
        $this->assertEquals(10, $online->getAuditAdministrative());

        // 檢查DepositCompany為預設值
        $company = $pc->getDepositCompany();
        $this->assertTrue($company->isDiscountGiveUp());
        $this->assertTrue($company->isAuditLive());
        $this->assertTrue($company->isAuditBall());
        $this->assertTrue($company->isAuditComplex());
        $this->assertTrue($company->isAuditNormal());
        $this->assertEquals(Deposit::EACH, $company->getDiscount());
        $this->assertEquals(51, $company->getDiscountAmount());
        $this->assertEquals(10, $company->getDiscountPercent());
        $this->assertEquals(4, $company->getDiscountFactor());
        $this->assertEquals(1000, $company->getDiscountLimit());
        $this->assertEquals(5000, $company->getDepositMax());
        $this->assertEquals(10, $company->getDepositMin());
        $this->assertEquals(5, $company->getAuditLiveAmount());
        $this->assertEquals(10, $company->getAuditBallAmount());
        $this->assertEquals(15, $company->getAuditComplexAmount());
        $this->assertEquals(100, $company->getAuditNormalAmount());
        $this->assertEquals(10, $company->getAuditDiscountAmount());
        $this->assertEquals(10, $company->getAuditLoosen());
        $this->assertEquals(5, $company->getAuditAdministrative());
        $this->assertEquals(50, $company->getOtherDiscountAmount());
        $this->assertEquals(5, $company->getOtherDiscountPercent());
        $this->assertEquals(100, $company->getOtherDiscountLimit());
        $this->assertEquals(500, $company->getDailyDiscountLimit());
        $this->assertEquals(1000, $company->getDepositScMax());
        $this->assertEquals(5, $company->getDepositScMin());
        $this->assertEquals(900, $company->getDepositCoMax());
        $this->assertEquals(6, $company->getDepositCoMin());
        $this->assertEquals(800, $company->getDepositSaMax());
        $this->assertEquals(7, $company->getDepositSaMin());
        $this->assertEquals(700, $company->getDepositAgMax());
        $this->assertEquals(8, $company->getDepositAgMin());

        // 檢查DepositMobile為預設值
        $mobile = $pc->getDepositMobile();
        $this->assertTrue($mobile->isDiscountGiveUp());
        $this->assertTrue($mobile->isAuditLive());
        $this->assertTrue($mobile->isAuditBall());
        $this->assertTrue($mobile->isAuditComplex());
        $this->assertTrue($mobile->isAuditNormal());
        $this->assertTrue($mobile->isAudit3D());
        $this->assertTrue($mobile->isAuditBattle());
        $this->assertTrue($mobile->isAuditVirtual());
        $this->assertEquals(Deposit::EACH, $mobile->getDiscount());
        $this->assertEquals(100, $mobile->getDiscountAmount());
        $this->assertEquals(2.12, $mobile->getDiscountPercent());
        $this->assertEquals(1, $mobile->getDiscountFactor());
        $this->assertEquals(500, $mobile->getDiscountLimit());
        $this->assertEquals(7000, $mobile->getDepositMax());
        $this->assertEquals(100, $mobile->getDepositMin());
        $this->assertEquals(10, $mobile->getAuditLiveAmount());
        $this->assertEquals(20, $mobile->getAuditBallAmount());
        $this->assertEquals(30, $mobile->getAuditComplexAmount());
        $this->assertEquals(100, $mobile->getAuditNormalAmount());
        $this->assertEquals(40, $mobile->getAudit3DAmount());
        $this->assertEquals(50, $mobile->getAuditBattleAmount());
        $this->assertEquals(60, $mobile->getAuditVirtualAmount());
        $this->assertEquals(5, $mobile->getAuditDiscountAmount());
        $this->assertEquals(40, $mobile->getAuditLoosen());
        $this->assertEquals(10, $mobile->getAuditAdministrative());

        // 檢查DepositMobile為預設值
        $bitcoin = $pc->getDepositBitcoin();
        $this->assertTrue($bitcoin->isDiscountGiveUp());
        $this->assertTrue($bitcoin->isAuditLive());
        $this->assertTrue($bitcoin->isAuditBall());
        $this->assertTrue($bitcoin->isAuditComplex());
        $this->assertTrue($bitcoin->isAuditNormal());
        $this->assertTrue($bitcoin->isAudit3D());
        $this->assertTrue($bitcoin->isAuditBattle());
        $this->assertTrue($bitcoin->isAuditVirtual());
        $this->assertEquals(Deposit::EACH, $bitcoin->getDiscount());
        $this->assertEquals(100, $bitcoin->getDiscountAmount());
        $this->assertEquals(2.12, $bitcoin->getDiscountPercent());
        $this->assertEquals(1, $bitcoin->getDiscountFactor());
        $this->assertEquals(500, $bitcoin->getDiscountLimit());
        $this->assertEquals(7000, $bitcoin->getDepositMax());
        $this->assertEquals(100, $bitcoin->getDepositMin());
        $this->assertEquals(10, $bitcoin->getAuditLiveAmount());
        $this->assertEquals(20, $bitcoin->getAuditBallAmount());
        $this->assertEquals(30, $bitcoin->getAuditComplexAmount());
        $this->assertEquals(100, $bitcoin->getAuditNormalAmount());
        $this->assertEquals(40, $bitcoin->getAudit3DAmount());
        $this->assertEquals(50, $bitcoin->getAuditBattleAmount());
        $this->assertEquals(60, $bitcoin->getAuditVirtualAmount());
        $this->assertEquals(5, $bitcoin->getAuditDiscountAmount());
        $this->assertEquals(40, $bitcoin->getAuditLoosen());
        $this->assertEquals(10, $bitcoin->getAuditAdministrative());
        $this->assertEquals(1000, $bitcoin->getBitcoinFeeMax());
        $this->assertEquals(10, $bitcoin->getBitcoinFeePercent());
    }

    /**
     * 測試以有問題的來源來新增線上支付設定
     */
    public function testCreatePaymentChargeWithBrokenSource()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $pc = $em->find('BBDurianBundle:PaymentCharge', 6);

        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'name'   => '英磅自定義',
            'preset' => false,
            'source' => 6,
            'code'   => 'GBP_CUS'
        ];

        $em->remove($pc->getDepositBitcoin());
        $em->flush();

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200046, $output['code']);
        $this->assertEquals('No DepositBitcoin found', $output['msg']);

        $em->refresh($pc);
        $em->remove($pc->getDepositMobile());
        $em->flush();

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200038, $output['code']);
        $this->assertEquals('No DepositMobile found', $output['msg']);

        $em->refresh($pc);
        $em->remove($pc->getDepositCompany());
        $em->flush();

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200028, $output['code']);
        $this->assertEquals('No DepositCompany found', $output['msg']);

        $em->refresh($pc);
        $em->remove($pc->getDepositOnline());
        $em->flush();

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200027, $output['code']);
        $this->assertEquals('No DepositOnline found', $output['msg']);
    }

    /**
     * 測試以不存在的來源來新增線上支付設定
     */
    public function testCreatePaymentChargeWithNotExistSource()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $pc = $em->find('BBDurianBundle:PaymentCharge', 2);
        $em->remove($pc);
        $em->flush();

        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'name'   => '英磅自定義',
            'preset' => false,
            'source' => 2,
            'code'   => 'GBP_CUS'
        ];

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200010, $output['code']);
        $this->assertEquals('Cannot find source PaymentCharge', $output['msg']);
    }

    /**
     * 測試新增線上支付設定未帶支付種類
     */
    public function testCreatePaymentChargeWithoutPayway()
    {
        $client = $this->createClient();
        $params = [
            'name' => '英磅',
            'preset' => false,
            'code' => 'GBP'
        ];

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret']['id']);
        $this->assertEquals('英磅', $output['ret']['name']);
        // 預設是現金
        $this->assertEquals(CashDepositEntry::PAYWAY_CASH, $output['ret']['payway']);
    }

    /**
     * 測試新增線上支付設定帶入不合法的支付種類
     */
    public function testCreatePaymentChargeWithInvalidPayway()
    {
        $client = $this->createClient();
        $params = [
            'payway' => '',
            'name'   => '英磅',
            'preset' => false,
            'code'   => 'GBP'
        ];

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('200034', $output['code']);
        $this->assertEquals('Invalid payway', $output['msg']);
    }

    /**
     * 測試新增preset paymentCharge不帶任何參數，自動批次新增
     */
    public function testCreatePresetPaymentChargeBunchAction()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'role' => 7,
            'login_code' => 'dm',
            'name' => 'domainator',
            'currency' => 'CNY',
            'cash' => array('currency' => 'CNY'),
            'sharelimit' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            )
        );

        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $domain = $output['ret']['domain'];

        $client->request('POST', "/api/domain/$domain/payment_charge/preset", array());
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(52, $output['ret'][0]['domain']);
        $this->assertEquals('', $output['ret'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['preset']);
        $this->assertEquals('CNY', $output['ret'][0]['code']);
        $this->assertEquals(0, $output['ret'][0]['rank']);

        $this->assertEquals(19, $output['ret'][12]['id']);
        $this->assertEquals(52, $output['ret'][12]['domain']);
        $this->assertEquals('', $output['ret'][12]['name']);
        $this->assertEquals(1, $output['ret'][12]['preset']);
        $this->assertEquals('VND', $output['ret'][12]['code']);
        $this->assertEquals(0, $output['ret'][12]['rank']);

        $currency = new \BB\DurianBundle\Currency();
        $availableCount = count($currency->getAvailable());

        //檢驗新增的筆數正確
        $pcRepo = $em->getRepository('BBDurianBundle:PaymentCharge');
        $paymentCharges = $pcRepo->findBy(array('domain' => $domain));

        $this->assertEquals(count($output['ret']), $availableCount);
        $this->assertEquals(count($paymentCharges), $availableCount);

        // 檢查paymentGatewayFee為預設值
        $pcid = $output['ret'][0]['id'];
        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $pgfs = $pgfRepo->findBy(['paymentCharge' => $pcid]);
        $this->assertEquals(5, count($pgfs));

        $pgf = $pgfs[0]->toArray();
        $this->assertEquals(1, $pgf['payment_gateway_id']);
        $this->assertEquals($pcid, $pgf['payment_charge_id']);
        $this->assertEquals(0, $pgf['rate']);

        $pgf = $pgfs[1]->toArray();
        $this->assertEquals(2, $pgf['payment_gateway_id']);
        $this->assertEquals($pcid, $pgf['payment_charge_id']);
        $this->assertEquals(0, $pgf['rate']);

        // 檢查paymentWithdrawFee為預設值
        $pwfRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawFee');
        $pwfs = $pwfRepo->findBy(['paymentCharge' => $pcid]);
        $pwf = $pwfs[0]->toArray();
        $this->assertEquals($pcid, $pwf['payment_charge_id']);
        $this->assertEquals(24, $pwf['free_period']);
        $this->assertEquals(1, $pwf['free_count']);
        $this->assertEquals(50, $pwf['amount_max']);
        $this->assertEquals(1, $pwf['amount_percent']);

        // 檢查paymentWithdrawVerify為預設值
        $pwvRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawVerify');
        $pwvs = $pwvRepo->findBy(['paymentCharge' => $pcid]);
        $pwv = $pwvs[0]->toArray();
        $this->assertEquals($pcid, $pwv['payment_charge_id']);
        $this->assertTrue($pwv['need_verify']);
        $this->assertEquals(24, $pwv['verify_time']);
        $this->assertEquals(5000, $pwv['verify_amount']);

        // 檢查DepositOnline為預設值
        $doRepo = $em->getRepository('BBDurianBundle:DepositOnline');
        $online = $doRepo->findOneBy(['paymentCharge' => $pcid]);
        $this->assertFalse($online->isDiscountGiveUp());
        $this->assertTrue($online->isAuditLive());
        $this->assertTrue($online->isAuditBall());
        $this->assertTrue($online->isAuditComplex());
        $this->assertTrue($online->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $online->getDiscount());
        $this->assertEquals(100, $online->getDiscountAmount());
        $this->assertEquals(0, $online->getDiscountPercent());
        $this->assertEquals(1, $online->getDiscountFactor());
        $this->assertEquals(0, $online->getDiscountLimit());
        $this->assertEquals(1000, $online->getDepositMax());
        $this->assertEquals(10, $online->getDepositMin());
        $this->assertEquals(10, $online->getAuditLiveAmount());
        $this->assertEquals(10, $online->getAuditBallAmount());
        $this->assertEquals(10, $online->getAuditComplexAmount());
        $this->assertEquals(100, $online->getAuditNormalAmount());
        $this->assertEquals(0, $online->getAuditDiscountAmount());
        $this->assertEquals(10, $online->getAuditLoosen());
        $this->assertEquals(0, $online->getAuditAdministrative());

        // 檢查DepositCompany為預設值
        $dcRepo = $em->getRepository('BBDurianBundle:DepositCompany');
        $company = $dcRepo->findOneBy(['paymentCharge' => $pcid]);
        $this->assertFalse($company->isDiscountGiveUp());
        $this->assertFalse($company->isAuditLive());
        $this->assertFalse($company->isAuditBall());
        $this->assertTrue($company->isAuditComplex());
        $this->assertFalse($company->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $company->getDiscount());
        $this->assertEquals(1000, $company->getDiscountAmount());
        $this->assertEquals(15, $company->getDiscountPercent());
        $this->assertEquals(1, $company->getDiscountFactor());
        $this->assertEquals(10000, $company->getDiscountLimit());
        $this->assertEquals(30000, $company->getDepositMax());
        $this->assertEquals(100, $company->getDepositMin());
        $this->assertEquals(0, $company->getAuditLiveAmount());
        $this->assertEquals(0, $company->getAuditBallAmount());
        $this->assertEquals(10, $company->getAuditComplexAmount());
        $this->assertEquals(100, $company->getAuditNormalAmount());
        $this->assertEquals(0, $company->getAuditDiscountAmount());
        $this->assertEquals(0, $company->getAuditLoosen());
        $this->assertEquals(0, $company->getAuditAdministrative());
        $this->assertEquals(100, $company->getOtherDiscountAmount());
        $this->assertEquals(0, $company->getOtherDiscountPercent());
        $this->assertEquals(0, $company->getOtherDiscountLimit());
        $this->assertEquals(0, $company->getDailyDiscountLimit());

        // 檢查DepositMobile為預設值
        $dwRepo = $em->getRepository('BBDurianBundle:DepositMobile');
        $mobile = $dwRepo->findOneBy(['paymentCharge' => $pcid]);
        $this->assertFalse($mobile->isDiscountGiveUp());
        $this->assertTrue($mobile->isAuditLive());
        $this->assertTrue($mobile->isAuditBall());
        $this->assertTrue($mobile->isAuditComplex());
        $this->assertTrue($mobile->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $mobile->getDiscount());
        $this->assertEquals(100, $mobile->getDiscountAmount());
        $this->assertEquals(0, $mobile->getDiscountPercent());
        $this->assertEquals(1, $mobile->getDiscountFactor());
        $this->assertEquals(0, $mobile->getDiscountLimit());
        $this->assertEquals(1000, $mobile->getDepositMax());
        $this->assertEquals(10, $mobile->getDepositMin());
        $this->assertEquals(10, $mobile->getAuditLiveAmount());
        $this->assertEquals(10, $mobile->getAuditBallAmount());
        $this->assertEquals(10, $mobile->getAuditComplexAmount());
        $this->assertEquals(100, $mobile->getAuditNormalAmount());
        $this->assertEquals(0, $mobile->getAuditDiscountAmount());
        $this->assertEquals(10, $mobile->getAuditLoosen());
        $this->assertEquals(0, $mobile->getAuditAdministrative());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 11);
        $this->assertEquals('payment_charge', $logOperation->getTableName());
        $this->assertEquals('@payment_charge_id:8', $logOperation->getMajorKey());
        $this->assertEquals('@payway:1, @domain:52, @preset:true, @code:EUR', $logOperation->getMessage());
    }

    /**
     * 測試有帶參數新增preset paymentCharge，新增有帶入的代碼(幣別)
     */
    public function testCreatePresetPaymentCharge()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'username' => 'domainator',
            'password' => 'domainator',
            'alias' => 'domainator',
            'role' => 7,
            'login_code' => 'dm',
            'name' => 'domainator',
            'currency' => 'CNY',
            'cash' => array('currency' => 'CNY'),
            'sharelimit' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            ),
            'sharelimit_next' => array(
                1 => array(
                    'upper' => 100,
                    'lower' => 0,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                )
            )
        );

        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $domain = $output['ret']['domain'];
        $paymentChargeCodes = array('CNY', 'TWD', 'USD');
        $parameters = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'codes' => $paymentChargeCodes
        ];

        $client->request('POST', "/api/domain/$domain/payment_charge/preset", $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(52, $output['ret'][0]['domain']);
        $this->assertEquals('', $output['ret'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['preset']);
        $this->assertEquals('CNY', $output['ret'][0]['code']);
        $this->assertEquals(0, $output['ret'][0]['rank']);

        $this->assertEquals(8, $output['ret'][1]['id']);
        $this->assertEquals(52, $output['ret'][1]['domain']);
        $this->assertEquals('', $output['ret'][1]['name']);
        $this->assertEquals(1, $output['ret'][1]['preset']);
        $this->assertEquals('TWD', $output['ret'][1]['code']);
        $this->assertEquals(0, $output['ret'][1]['rank']);

        $this->assertEquals(9, $output['ret'][2]['id']);
        $this->assertEquals(52, $output['ret'][2]['domain']);
        $this->assertEquals('', $output['ret'][2]['name']);
        $this->assertEquals(1, $output['ret'][2]['preset']);
        $this->assertEquals('USD', $output['ret'][2]['code']);
        $this->assertEquals(0, $output['ret'][2]['rank']);

        $availableCount = count($paymentChargeCodes);

        //檢驗新增的筆數正確
        $pcRepo = $em->getRepository('BBDurianBundle:PaymentCharge');
        $paymentCharges = $pcRepo->findBy(array('domain' => $domain));

        $this->assertEquals(count($output['ret']), $availableCount);
        $this->assertEquals(count($paymentCharges), $availableCount);

        // 檢查paymentGatewayFee為預設值
        $pcid = $output['ret'][0]['id'];
        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $pgfs = $pgfRepo->findBy(['paymentCharge' => $pcid]);
        $this->assertEquals(5, count($pgfs));

        $pgf = $pgfs[0]->toArray();
        $this->assertEquals(1, $pgf['payment_gateway_id']);
        $this->assertEquals($pcid, $pgf['payment_charge_id']);
        $this->assertEquals(0, $pgf['rate']);

        $pgf = $pgfs[1]->toArray();
        $this->assertEquals(2, $pgf['payment_gateway_id']);
        $this->assertEquals($pcid, $pgf['payment_charge_id']);
        $this->assertEquals(0, $pgf['rate']);

        // 檢查paymentWithdrawFee為預設值
        $pwfRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawFee');
        $pwfs = $pwfRepo->findBy(['paymentCharge' => $pcid]);
        $pwf = $pwfs[0]->toArray();
        $this->assertEquals($pcid, $pwf['payment_charge_id']);
        $this->assertEquals(24, $pwf['free_period']);
        $this->assertEquals(1, $pwf['free_count']);
        $this->assertEquals(50, $pwf['amount_max']);
        $this->assertEquals(1, $pwf['amount_percent']);

        // 檢查paymentWithdrawVerify為預設值
        $pwvRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawVerify');
        $pwvs = $pwvRepo->findBy(['paymentCharge' => $pcid]);
        $pwv = $pwvs[0]->toArray();
        $this->assertEquals($pcid, $pwv['payment_charge_id']);
        $this->assertTrue($pwv['need_verify']);
        $this->assertEquals(24, $pwv['verify_time']);
        $this->assertEquals(5000, $pwv['verify_amount']);

        // 檢查DepositOnline為預設值
        $doRepo = $em->getRepository('BBDurianBundle:DepositOnline');
        $online = $doRepo->findOneBy(['paymentCharge' => $pcid]);
        $this->assertFalse($online->isDiscountGiveUp());
        $this->assertTrue($online->isAuditLive());
        $this->assertTrue($online->isAuditBall());
        $this->assertTrue($online->isAuditComplex());
        $this->assertTrue($online->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $online->getDiscount());
        $this->assertEquals(100, $online->getDiscountAmount());
        $this->assertEquals(0, $online->getDiscountPercent());
        $this->assertEquals(1, $online->getDiscountFactor());
        $this->assertEquals(0, $online->getDiscountLimit());
        $this->assertEquals(1000, $online->getDepositMax());
        $this->assertEquals(10, $online->getDepositMin());
        $this->assertEquals(10, $online->getAuditLiveAmount());
        $this->assertEquals(10, $online->getAuditBallAmount());
        $this->assertEquals(10, $online->getAuditComplexAmount());
        $this->assertEquals(100, $online->getAuditNormalAmount());
        $this->assertEquals(0, $online->getAuditDiscountAmount());
        $this->assertEquals(10, $online->getAuditLoosen());
        $this->assertEquals(0, $online->getAuditAdministrative());

        // 檢查DepositCompany為預設值
        $dcRepo = $em->getRepository('BBDurianBundle:DepositCompany');
        $company = $dcRepo->findOneBy(['paymentCharge' => $pcid]);
        $this->assertFalse($company->isDiscountGiveUp());
        $this->assertFalse($company->isAuditLive());
        $this->assertFalse($company->isAuditBall());
        $this->assertTrue($company->isAuditComplex());
        $this->assertFalse($company->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $company->getDiscount());
        $this->assertEquals(1000, $company->getDiscountAmount());
        $this->assertEquals(15, $company->getDiscountPercent());
        $this->assertEquals(1, $company->getDiscountFactor());
        $this->assertEquals(10000, $company->getDiscountLimit());
        $this->assertEquals(30000, $company->getDepositMax());
        $this->assertEquals(100, $company->getDepositMin());
        $this->assertEquals(0, $company->getAuditLiveAmount());
        $this->assertEquals(0, $company->getAuditBallAmount());
        $this->assertEquals(10, $company->getAuditComplexAmount());
        $this->assertEquals(100, $company->getAuditNormalAmount());
        $this->assertEquals(0, $company->getAuditDiscountAmount());
        $this->assertEquals(0, $company->getAuditLoosen());
        $this->assertEquals(0, $company->getAuditAdministrative());
        $this->assertEquals(100, $company->getOtherDiscountAmount());
        $this->assertEquals(0, $company->getOtherDiscountPercent());
        $this->assertEquals(0, $company->getOtherDiscountLimit());
        $this->assertEquals(0, $company->getDailyDiscountLimit());

        // 檢查DepositMobile為預設值
        $dwRepo = $em->getRepository('BBDurianBundle:DepositMobile');
        $mobile = $dwRepo->findOneBy(['paymentCharge' => $pcid]);
        $this->assertFalse($mobile->isDiscountGiveUp());
        $this->assertTrue($mobile->isAuditLive());
        $this->assertTrue($mobile->isAuditBall());
        $this->assertTrue($mobile->isAuditComplex());
        $this->assertTrue($mobile->isAuditNormal());
        $this->assertEquals(Deposit::FIRST, $mobile->getDiscount());
        $this->assertEquals(100, $mobile->getDiscountAmount());
        $this->assertEquals(0, $mobile->getDiscountPercent());
        $this->assertEquals(1, $mobile->getDiscountFactor());
        $this->assertEquals(0, $mobile->getDiscountLimit());
        $this->assertEquals(1000, $mobile->getDepositMax());
        $this->assertEquals(10, $mobile->getDepositMin());
        $this->assertEquals(10, $mobile->getAuditLiveAmount());
        $this->assertEquals(10, $mobile->getAuditBallAmount());
        $this->assertEquals(10, $mobile->getAuditComplexAmount());
        $this->assertEquals(100, $mobile->getAuditNormalAmount());
        $this->assertEquals(0, $mobile->getAuditDiscountAmount());
        $this->assertEquals(10, $mobile->getAuditLoosen());
        $this->assertEquals(0, $mobile->getAuditAdministrative());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 11);
        $this->assertEquals('payment_charge', $logOperation->getTableName());
        $this->assertEquals('@payment_charge_id:8', $logOperation->getMajorKey());
        $this->assertEquals('@payway:1, @domain:52, @preset:true, @code:TWD', $logOperation->getMessage());
    }

    /**
     * 測試新增預設paymentCharge時帶入重複的代碼參數
     */
    public function testCreatePresetPaymentChargeWithDuplicateCode()
    {
        $client = $this->createClient();
        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'codes' => ['TWD', 'TWD']
        ];
        $client->request('POST', '/api/domain/2/payment_charge/preset', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200032, $output['code']);
        $this->assertEquals('Duplicate code parameter', $output['msg']);
    }

    /**
     * 測試新增預設paymentCharge時Domain不存在
     */
    public function testCreatePresetPaymentChargeWithEmptyDomain()
    {
        $client = $this->createClient();
        $params = ['payway' => CashDepositEntry::PAYWAY_CASH];
        $client->request('POST', '/api/domain/999/payment_charge/preset', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200033, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試新增預設paymentCharge時帶入非法的幣別
     */
    public function testCreatePresetPaymentChargeWithInvalidCurrency()
    {
        $client = $this->createClient();
        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'codes' => ['CCC']
        ];

        $client->request('POST', '/api/domain/2/payment_charge/preset', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200030, $output['code']);
        $this->assertEquals('Invalid PaymentCharge code given', $output['msg']);
    }

    /**
     * 測試新增預設paymentCharge時帶入重覆的幣別
     */
    public function testCreatePresetPaymentChargeWithDuplicateCurrency()
    {
        $client = $this->createClient();
        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'codes' => ['CNY']
        ];

        $client->request('POST', '/api/domain/2/payment_charge/preset', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200005, $output['code']);
        $this->assertEquals('Duplicate PaymentCharge', $output['msg']);
    }

    /**
     * 測試新增預設線上支付設定未帶支付種類
     */
    public function testCreatePresetPaymentChargeWithoutPayway()
    {
        $client = $this->createClient();
        $params = ['codes' => ['HKD']];

        $client->request('POST', '/api/domain/2/payment_charge/preset', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals('', $output['ret'][0]['name']);
        // 預設是現金
        $this->assertEquals(CashDepositEntry::PAYWAY_CASH, $output['ret'][0]['payway']);
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試新增預設線上支付設定帶入不合法的支付種類
     */
    public function testCreatePresetPaymentChargeWithInvalidPayway()
    {
        $client = $this->createClient();
        $params = [
            'payway' => '',
            'codes' => ['HKD']
        ];

        $client->request('POST', '/api/domain/2/payment_charge/preset', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('200034', $output['code']);
        $this->assertEquals('Invalid payway', $output['msg']);
    }

    /**
     * 檢查兩個入款設定是否相等
     *
     * @param Deposit $from
     * @param Deposit $to
     */
    private function checkDeposit($from, $to)
    {
        $this->assertEquals($to->getDiscount(), $from->getDiscount());
        $this->assertEquals($to->isDiscountGiveUp(), $from->isDiscountGiveUp());
        $this->assertEquals($to->getDiscountAmount(), $from->getDiscountAmount());
        $this->assertEquals($to->getDiscountPercent(), $from->getDiscountPercent());
        $this->assertEquals($to->getDiscountFactor(), $from->getDiscountFactor());
        $this->assertEquals($to->getDiscountLimit(), $from->getDiscountLimit());
        $this->assertEquals($to->getDepositMax(), $from->getDepositMax());
        $this->assertEquals($to->getDepositMin(), $from->getDepositMin());
        $this->assertEquals($to->isAuditLive(), $from->isAuditLive());
        $this->assertEquals($to->getAuditLiveAmount(), $from->getAuditLiveAmount());
        $this->assertEquals($to->isAuditBall(), $from->isAuditBall());
        $this->assertEquals($to->getAuditBallAmount(), $from->getAuditBallAmount());
        $this->assertEquals($to->isAuditComplex(), $from->isAuditComplex());
        $this->assertEquals($to->getAuditComplexAmount(), $from->getAuditComplexAmount());
        $this->assertEquals($to->isAuditNormal(), $from->isAuditNormal());
        $this->assertEquals($to->getAuditNormalAmount(), $from->getAuditNormalAmount());
        $this->assertEquals($to->getAuditDiscountAmount(), $from->getAuditDiscountAmount());
        $this->assertEquals($to->getAuditLoosen(), $from->getAuditLoosen());
        $this->assertEquals($to->getAuditAdministrative(), $from->getAuditAdministrative());
    }

    /**
     * 測試新增時Domain不存在
     */
    public function testCreatePaymentChargeWithEmptyDomain()
    {
        $client = $this->createClient();
        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'preset' => true,
            'code' => 'GBP'
        ];

        $client->request('POST', '/api/domain/999/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200033, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試新增時未帶入name
     */
    public function testCreatePaymentChargeWithEmptyName()
    {
        $client = $this->createClient();
        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'preset' => true,
            'code' => 'GBP'
        ];

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200004, $output['code']);
        $this->assertEquals('No name specified', $output['msg']);
    }

    /**
     * 測試新增時未帶入code
     */
    public function testCreatePaymentChargeWithEmptyCode()
    {
        $client = $this->createClient();
        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'name' => '拉拉幣',
            'preset' => true
        ];

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200002, $output['code']);
        $this->assertEquals('No PaymentCharge code specified', $output['msg']);
    }

    /**
     * 測試新增時帶入重複code
     */
    public function testCreatePaymentChargeWithDuplicateCode()
    {
        $client = $this->createClient();
        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'name' => '迪西幣',
            'code' => 'CNY',
            'preset' => true
        ];

        $client->request('POST', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200005, $output['code']);
        $this->assertEquals('Duplicate PaymentCharge', $output['msg']);
    }

    /**
     * 測試修改線上支付順序
     */
    public function testSetPaymentChargeRank()
    {
        $client = $this->createClient();

        $paramTWD = array(
            'id' => 1,
            'rank' => 99,
            'version' => 1
        );
        $paramCNY = array(
            'id' => 2,
            'rank' => 98,
            'version' => 1
        );

        $params = array('data' => array($paramTWD, $paramCNY));

        $client->request('PUT', '/api/payment_charge/rank', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(99, $output['ret'][0]['rank']);
        $this->assertEquals(2, $output['ret'][0]['version']);
        $this->assertEquals(98, $output['ret'][1]['rank']);
        $this->assertEquals(2, $output['ret'][1]['version']);
    }

    /**
     * 測試修改順序的例外
     */
    public function testSetPaymentChargeRankButExceptionOccur()
    {
        $client = $this->createClient();

        //測試傳入空陣列
        $client->request('PUT', '/api/payment_charge/rank', array());
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200003, $output['code']);
        $this->assertEquals('No data specified', $output['msg']);

        $paramTWD = array(
            'id' => 1,
            'version' => 56
        );

        $params = array('data' => array($paramTWD));

        $client->request('PUT', '/api/payment_charge/rank', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200006, $output['code']);
        $this->assertEquals('No rank specified', $output['msg']);

        $paramTWD = array(
            'id' => 1,
            'rank' => 69
        );

        $params = array('data' => array($paramTWD));

        $client->request('PUT', '/api/payment_charge/rank', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200007, $output['code']);
        $this->assertEquals('No version specified', $output['msg']);

        $paramTWD = array(
            'id' => 1,
            'rank' => 99,
            'version' => 56
        );

        $params = array('data' => array($paramTWD));

        $client->request('PUT', '/api/payment_charge/rank', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200001, $output['code']);
        $this->assertEquals('PaymentCharge has been changed', $output['msg']);
    }

    /**
     * 測試修改支付平台手續費
     */
    public function testSetPaymentGatewayFee()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $paramBB = [
            'payment_gateway_id' => 1,
            'rate' => 1,
            'withdraw_rate' => 0.2,
        ];
        $paramZZ = [
            'payment_gateway_id' => 2,
            'rate' => 0.4,
            'withdraw_rate' => 0.6,
        ];

        $params = array('data' => array($paramBB, $paramZZ));

        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_charge_id']);
        $this->assertEquals(1, $output['ret'][0]['rate']);
        $this->assertEquals(0.2, $output['ret'][0]['withdraw_rate']);

        $this->assertEquals(2, $output['ret'][1]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][1]['payment_charge_id']);
        $this->assertEquals(0.4, $output['ret'][1]['rate']);
        $this->assertEquals(0.6, $output['ret'][1]['withdraw_rate']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_fee', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:1, @payment_charge_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@rate:0.5=>1, @withdraw_rate:0=>0.2', $logOperation->getMessage());

        $logOperation2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('payment_gateway_fee', $logOperation2->getTableName());
        $this->assertEquals('@payment_gateway_id:2, @payment_charge_id:1', $logOperation2->getMajorKey());
        $this->assertEquals('@rate:0.2=>0.4, @withdraw_rate:0=>0.6', $logOperation2->getMessage());
    }

    /**
     * 測試修改PaymentGatewayFee時會補值
     */
    public function testSetPaymentGatewayFeeWillCreateOneIfNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $feeData = [
            'payment_gateway_id' => 92,
            'rate' => 1.5,
            'withdraw_rate' => 0.6,
        ];

        $params = array('data' => array($feeData));

        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $pgf = $pgfRepo->findOneBy(['paymentGateway' => 92]);

        $this->assertEquals($output['ret'][2]['payment_gateway_id'], 92);
        $this->assertEquals($output['ret'][2]['payment_charge_id'], 1);
        $this->assertEquals($output['ret'][2]['rate'], 1.5);

        $this->assertEquals($feeData['rate'], $pgf->getRate());
        $this->assertEquals($feeData['payment_gateway_id'], $pgf->getPaymentGateway()->getId());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_fee', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:92, @payment_charge_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@rate:1.5, @withdraw_rate:0.6', $logOperation->getMessage());
    }

    /**
     * 測試修改支付平台手續費時例外
     */
    public function testSetPaymentGatewayFeeButExceptionOccur()
    {
        $client = $this->createClient();

        //測試傳入空陣列
        $client->request('PUT', '/api/payment_charge/888/payment_gateway/fee', array());
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200008, $output['code']);
        $this->assertEquals('Cannot find specified PaymentCharge', $output['msg']);

        //測試傳入空陣列
        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', array());
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200003, $output['code']);
        $this->assertEquals('No data specified', $output['msg']);

        $paramPg = array('payment_gateway_id' => 1);

        $params = array('data' => array($paramPg));

        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200011, $output['code']);
        $this->assertEquals('Invalid PaymentGatewayFee rate specified', $output['msg']);

        $paramPg = array(
            'id' => 1,
            'rate' => '123..553',
        );

        $params = array('data' => array($paramPg));

        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200011, $output['code']);
        $this->assertEquals('Invalid PaymentGatewayFee rate specified', $output['msg']);

        $paramPg = array(
            'id' => 1,
            'rate' => 'abc123',
        );

        $params = array('data' => array($paramPg));

        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200011, $output['code']);
        $this->assertEquals('Invalid PaymentGatewayFee rate specified', $output['msg']);
    }

    /**
     * 測試修改支付平台手續費時未帶入出款手續費率
     */
    public function testSetPaymentGatewayFeeWithoutWithdrawRate()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $paramPg = [
            'payment_gateway_id' => 1,
            'rate' => '123',
        ];

        $params = ['data' => [$paramPg]];

        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_charge_id']);
        $this->assertEquals(123, $output['ret'][0]['rate']);
        $this->assertEquals(0, $output['ret'][0]['withdraw_rate']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_fee', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:1, @payment_charge_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@rate:0.5=>123', $logOperation->getMessage());
    }

    /**
     * 測試修改支付平台手續費時帶入的出款手續費率為負數
     */
    public function testSetPaymentGatewayFeeWithWithdrawRateIsNegative()
    {
        $client = $this->createClient();

        $paramPg = [
            'payment_gateway_id' => 1,
            'rate' => '123',
            'withdraw_rate' => -123,
        ];

        $params = ['data' => [$paramPg]];

        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200045, $output['code']);
        $this->assertEquals('Invalid PaymentGatewayFee withdraw_rate specified', $output['msg']);
    }

    /**
     * 測試修改支付平台手續費時帶入的出款手續費率非數字
     */
    public function testSetPaymentGatewayFeeWithWithdrawRateIsNotNumber()
    {
        $client = $this->createClient();

        $paramPg = [
            'payment_gateway_id' => 1,
            'rate' => '123',
            'withdraw_rate' => 'abc123',
        ];

        $params = ['data' => [$paramPg]];

        $client->request('PUT', '/api/payment_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200045, $output['code']);
        $this->assertEquals('Invalid PaymentGatewayFee withdraw_rate specified', $output['msg']);
    }

    /**
     * 測試修改線上支付名稱
     */
    public function testSetPaymentChargeName()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $params = array(
            'name' => '台灣hrhr',
        );

        $client->request('PUT', '/api/payment_charge/1/name', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('台灣hrhr', $output['ret']['name']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_charge', $logOperation->getTableName());
        $this->assertEquals('@payment_charge_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@name:台幣=>台灣hrhr', $logOperation->getMessage());
    }

    /**
     * 測試修改線上支付名稱時發生例外
     */
    public function testSetPaymentChargeNameButExceptionOccur()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/payment_charge/1/name', array());
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200004, $output['code']);
        $this->assertEquals('No name specified', $output['msg']);

        $params = array('name' => '台灣hrhr');

        $client->request('PUT', '/api/payment_charge/58/name', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200008, $output['code']);
        $this->assertEquals('Cannot find specified PaymentCharge', $output['msg']);
    }

    /**
     * 測試修改線上支付設定的出款手續費
     */
    public function testSetPaymentWithdrawFee()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $params = array(
            'free_period' => 2,
            'free_count' => 1,
            'amount_max' => 6000,
            'amount_percent' => 3.75,
            'withdraw_max' => 65000.366,
            'withdraw_min' => 250.25,
            'mobile_free_period' => 50,
            'mobile_free_count' => 30,
            'mobile_amount_max' => 100,
            'mobile_amount_percent' => 0.8,
            'mobile_withdraw_max' => 10000.366,
            'mobile_withdraw_min' => 50.2,
            'bitcoin_free_period' => 10,
            'bitcoin_free_count' => 40,
            'bitcoin_amount_max' => 60,
            'bitcoin_amount_percent' => 10.5,
            'bitcoin_withdraw_max' => 20000.5321,
            'bitcoin_withdraw_min' => 100.5321,
            'account_replacement_tips' => true,
            'account_tips_interval' => 20,
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['free_period']);
        $this->assertEquals(1, $output['ret']['free_count']);
        $this->assertEquals(6000, $output['ret']['amount_max']);
        $this->assertEquals(3.75, $output['ret']['amount_percent']);
        $this->assertEquals(65000.366, $output['ret']['withdraw_max']);
        $this->assertEquals(250.25, $output['ret']['withdraw_min']);
        $this->assertEquals(50, $output['ret']['mobile_free_period']);
        $this->assertEquals(30, $output['ret']['mobile_free_count']);
        $this->assertEquals(100, $output['ret']['mobile_amount_max']);
        $this->assertEquals(0.8, $output['ret']['mobile_amount_percent']);
        $this->assertEquals(10000.366, $output['ret']['mobile_withdraw_max']);
        $this->assertEquals(50.2, $output['ret']['mobile_withdraw_min']);
        $this->assertEquals(10, $output['ret']['bitcoin_free_period']);
        $this->assertEquals(40, $output['ret']['bitcoin_free_count']);
        $this->assertEquals(60, $output['ret']['bitcoin_amount_max']);
        $this->assertEquals(10.5, $output['ret']['bitcoin_amount_percent']);
        $this->assertEquals(20000.5321, $output['ret']['bitcoin_withdraw_max']);
        $this->assertEquals(100.5321, $output['ret']['bitcoin_withdraw_min']);
        $this->assertEquals(true, $output['ret']['account_replacement_tips']);
        $this->assertEquals(20, $output['ret']['account_tips_interval']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_withdraw_fee', $logOperation->getTableName());
        $this->assertEquals('@payment_charge_id:1', $logOperation->getMajorKey());
        $msg = '@free_period:6=>2, @free_count:2=>1, @amount_max:12000=>6000, '.
            '@amount_percent:5=>3.75, @withdraw_max:18000=>65000.366, @withdraw_min:600=>250.25' .
            ', @mobile_free_period:24=>50, @mobile_free_count:1=>30, @mobile_amount_max:50=>100, ' .
            '@mobile_amount_percent:1=>0.8, @mobile_withdraw_max:50000=>10000.366, @mobile_withdraw_min:100=>50.2, ' .
            '@bitcoin_free_period:24=>10, @bitcoin_free_count:0=>40, @bitcoin_amount_max:50=>60, ' .
            '@bitcoin_amount_percent:1=>10.5, @bitcoin_withdraw_max:50000=>20000.5321, ' .
            '@bitcoin_withdraw_min:100=>100.5321, @account_replacement_tips:=>1, @account_tips_interval:1=>20';
        $this->assertEquals($msg, $logOperation->getMessage());
    }

    /**
     * 測試修改線上支付設定的出款手續費時發生例外
     */
    public function testSetPaymentWithdrawFeeButExceptionOccur()
    {
        $client = $this->createClient();

        $params = array(
            'free_period' => 2,
            'free_count' => 1,
            'amount_max' => 6000,
        );

        $client->request('PUT', '/api/payment_charge/58/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200013, $output['code']);
        $this->assertEquals('Cannot find specified PaymentWithdrawFee', $output['msg']);

        $params = array(
            'free_period' => '台灣hrhr',
            'free_count' => 1,
            'amount_max' => 6000
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200015, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee free_period', $output['msg']);

        $params = array(
            'free_period' => 1,
            'free_count' => '台灣hrhr',
            'amount_max' => 6000
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200016, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee free_count', $output['msg']);

        $params = array(
            'free_period' => 1,
            'free_count' => 2,
            'amount_max' => '台灣hrhr'
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200017, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee amount_max', $output['msg']);

        $params = array(
            'free_period' => 1,
            'free_count' => 2,
            'amount_max' => 7200,
            'amount_percent' => '台灣hrhr'
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200031, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee amount_percent', $output['msg']);

        $params = [
            'free_period' => 1,
            'free_count' => 2,
            'amount_max' => 7200,
            'amount_percent' => 5,
            'withdraw_max' => 'oh yeag~',
        ];

        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200023, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee withdraw_max', $output['msg']);

        $params = [
            'free_period' => 1,
            'free_count' => 2,
            'amount_max' => 7200,
            'amount_percent' => 5,
            'withdraw_max' => 20,
            'withdraw_min' => 'string',
        ];

        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200024, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee withdraw_min', $output['msg']);

        $params = ['mobile_free_period' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200043, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee mobile_free_period', $output['msg']);

        $params = ['mobile_free_count' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200044, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee mobile_free_count', $output['msg']);

        $params = ['mobile_amount_max' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200039, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee mobile_amount_max', $output['msg']);

        $params = ['mobile_amount_percent' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200040, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee mobile_amount_percent', $output['msg']);

        $params = ['mobile_withdraw_max' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200041, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee mobile_withdraw_max', $output['msg']);

        $params = ['mobile_withdraw_min' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200042, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee mobile_withdraw_min', $output['msg']);

        $params = ['bitcoin_free_period' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200048, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee bitcoin_free_period', $output['msg']);

        $params = ['bitcoin_free_count' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200049, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee bitcoin_free_count', $output['msg']);

        $params = ['bitcoin_amount_max' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200050, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee bitcoin_amount_max', $output['msg']);

        $params = ['bitcoin_amount_percent' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200051, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee bitcoin_amount_percent', $output['msg']);

        $params = ['bitcoin_withdraw_max' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200052, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee bitcoin_withdraw_max', $output['msg']);

        $params = ['bitcoin_withdraw_min' => 'test'];
        $client->request('PUT', '/api/payment_charge/1/withdraw_fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200053, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawFee bitcoin_withdraw_min', $output['msg']);
    }

    /**
     * 測試修改線上支付設定的取款金額審核時間
     */
    public function testSetPaymentWithdrawVerify()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $params = array(
            'need_verify' => true,
            'verify_time' => 1,
            'verify_amount' => 6000
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_verify', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertTrue($output['ret']['need_verify']);
        $this->assertEquals(1, $output['ret']['verify_time']);
        $this->assertEquals(6000, $output['ret']['verify_amount']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_withdraw_verify', $logOperation->getTableName());
        $this->assertEquals('@payment_charge_id:1', $logOperation->getMajorKey());
        $this->assertEquals(
            '@need_verify:false=>true, @verify_time:0=>1, @verify_amount:0=>6000',
            $logOperation->getMessage()
        );
    }

    /**
     * 測試修改線上支付設定的取款金額審核時間時發生例外
     */
    public function testSetPaymentWithdrawVerifyButExceptionOccur()
    {
        $client = $this->createClient();

        $params = array(
            'need_verify' => true,
            'verify_time' => 1,
            'verify_amount' => 6000
        );

        $client->request('PUT', '/api/payment_charge/58/withdraw_verify', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200018, $output['code']);
        $this->assertEquals('Cannot find specified PaymentWithdrawVerify', $output['msg']);

        $params = array(
            'need_verify' => 'true',
            'verify_time' => 1,
            'verify_amount' => 6000
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_verify', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200020, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawVerify need_verify', $output['msg']);

        $params = array(
            'need_verify' => true,
            'verify_time' => '3600分鐘',
            'verify_amount' => 6000
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_verify', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200021, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawVerify verify_time', $output['msg']);

        $params = array(
            'need_verify' => true,
            'verify_time' => 60,
            'verify_amount' => '五萬'
        );

        $client->request('PUT', '/api/payment_charge/1/withdraw_verify', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200022, $output['code']);
        $this->assertEquals('Invalid PaymentWithdrawVerify verify_amount', $output['msg']);
    }

    /**
     * 測試取得線上支付設定
     */
    public function testGetPaymentCharge()
    {
        $client = $this->createClient();

        $sort = array('rank', 'id');
        $order = array('ASC', 'DESC');

        $params = array(
            'sort'  => $sort,
            'order' => $order
        );

        $client->request('GET', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, count($output['ret']));

        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('台幣-自訂', $output['ret'][0]['name']);
        $this->assertFalse($output['ret'][0]['preset']);
        $this->assertEquals(1, $output['ret'][0]['rank']);
        $this->assertEquals('TWD-C', $output['ret'][0]['code']);

        $this->assertEquals(1, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals('台幣', $output['ret'][1]['name']);
        $this->assertTrue($output['ret'][1]['preset']);
        $this->assertEquals(1, $output['ret'][1]['rank']);
        $this->assertEquals('TWD', $output['ret'][1]['code']);

        $this->assertEquals(2, $output['ret'][3]['id']);
        $this->assertEquals(2, $output['ret'][3]['domain']);
        $this->assertEquals('人民幣', $output['ret'][3]['name']);
        $this->assertTrue($output['ret'][3]['preset']);
        $this->assertEquals(2, $output['ret'][3]['rank']);
        $this->assertEquals('CNY', $output['ret'][3]['code']);

        $this->assertEquals(6, count($output['ret']));
    }

    /**
     * 測試取得線上支付設定代入缺漏的排序條件
     */
    public function testGetPaymentChargeWithNotMatchedOrderAndSort()
    {
        $client = $this->createClient();

        $sort = array('rank', 'id');
        $order = array('ASC',);

        $params = array(
            'sort'  => $sort,
            'order' => $order
        );

        $client->request('GET', '/api/domain/2/payment_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('台幣', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['preset']);
        $this->assertEquals(1, $output['ret'][0]['rank']);
        $this->assertEquals('TWD', $output['ret'][0]['code']);

        $this->assertEquals(4, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals('台幣-自訂', $output['ret'][1]['name']);
        $this->assertFalse($output['ret'][1]['preset']);
        $this->assertEquals(1, $output['ret'][1]['rank']);
        $this->assertEquals('TWD-C', $output['ret'][1]['code']);

        $this->assertEquals(5, $output['ret'][3]['id']);
        $this->assertEquals(2, $output['ret'][3]['domain']);
        $this->assertEquals('人民幣-自訂', $output['ret'][3]['name']);
        $this->assertFalse($output['ret'][3]['preset']);
        $this->assertEquals(2, $output['ret'][3]['rank']);
        $this->assertEquals('CNY-C', $output['ret'][3]['code']);
    }

    /**
     * 測試取得線上支付設定時代入無效的domain
     */
    public function testGetPaymentChargeWithInvalidDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/97245/payment_charge');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200033, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試取得線上支付設定時代入不合法的付款種類
     */
    public function testGetPaymentChargeWithInvalidPayway()
    {
        $client = $this->createClient();
        $params = ['payway' => 999];
        $client->request('GET', '/api/domain/2/payment_charge', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('200034', $output['code']);
        $this->assertEquals('Invalid payway', $output['msg']);
    }

    /**
     * 測試取得線上支付設定的出款手續費
     */
    public function testGetPaymentWithdrawFee()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/1/withdraw_fee');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['payment_charge_id']);
        $this->assertEquals(6, $output['ret']['free_period']);
        $this->assertEquals(2, $output['ret']['free_count']);
        $this->assertEquals(18000, $output['ret']['withdraw_max']);
        $this->assertEquals(600, $output['ret']['withdraw_min']);
        $this->assertEquals(12000, $output['ret']['amount_max']);
        $this->assertEquals(5, $output['ret']['amount_percent']);
        $this->assertEquals(24, $output['ret']['mobile_free_period']);
        $this->assertEquals(1, $output['ret']['mobile_free_count']);
        $this->assertEquals(50, $output['ret']['mobile_amount_max']);
        $this->assertEquals(1, $output['ret']['mobile_amount_percent']);
        $this->assertEquals(50000, $output['ret']['mobile_withdraw_max']);
        $this->assertEquals(100, $output['ret']['mobile_withdraw_min']);
    }

    /**
     * 測試取得不存在的線上支付設定的出款手續費
     */
    public function testGetPaymentWithdrawFeeWithNoneExistPaymentCharge()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/939921/withdraw_fee');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200008, $output['code']);
        $this->assertEquals('Cannot find specified PaymentCharge', $output['msg']);
    }

    /**
     * 測試取得線上支付設定的取款金額審核時間
     */
    public function testGetPaymentGatewayVerify()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/1/withdraw_verify');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['payment_charge_id']);
        $this->assertFalse($output['ret']['need_verify']);
        $this->assertEquals(0, $output['ret']['verify_time']);
        $this->assertEquals(0, $output['ret']['verify_amount']);
    }

    /**
     * 測試取得不存在的線上支付設定的取款金額審核時間
     */
    public function testGetPaymentWithdrawVerifyWithNoneExistPaymentCharge()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/55688/withdraw_verify');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200008, $output['code']);
        $this->assertEquals('Cannot find specified PaymentCharge', $output['msg']);
    }

    /**
     * 測試取得線上支付設定的支付平台手續費
     */
    public function testGetPaymentGatewayFee()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/1/payment_gateway/fee');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // CCPay(id=77)為已刪除的支付平台，不會取得
        $this->assertEquals(5, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_charge_id']);
        $this->assertEquals(0.5, $output['ret'][0]['rate']);

        $this->assertEquals(2, $output['ret'][1]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][1]['payment_charge_id']);
        $this->assertEquals(0.2, $output['ret'][1]['rate']);
    }

    /**
     * 測試取得不存在的線上支付設定的支付平台手續費
     */
    public function testGetPaymentGatewayFeeWithNoneExistPaymentCharge()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/739568/payment_gateway/fee');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200008, $output['code']);
        $this->assertEquals('Cannot find specified PaymentCharge', $output['msg']);
    }

    /**
     * 測試未設定支付平台手續費時，會取得預設值
     */
    public function testGetPaymentGatewayFeeWillReturnDefaultValueIfNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/1/payment_gateway/fee');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(92, $output['ret'][4]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][4]['payment_charge_id']);
        $this->assertEquals(0, $output['ret'][4]['rate']);
    }

    /**
     * 測試刪除線上支付設定
     */
    public function testRemovePaymentCharge()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $pwfRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawFee');
        $pwvRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawVerify');

        $paymentChargeId = 4;

        $client->request('DELETE', '/api/payment_charge/'.$paymentChargeId);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);


        $pc = $em->find('BBDurianBundle:PaymentCharge', $paymentChargeId);
        $this->assertNull($pc);

        $online = $em->find('BBDurianBundle:DepositOnline', $paymentChargeId);
        $this->assertNull($online);

        $company = $em->find('BBDurianBundle:DepositCompany', $paymentChargeId);
        $this->assertNull($company);

        $mobile = $em->find('BBDurianBundle:DepositMobile', $paymentChargeId);
        $this->assertNull($mobile);

        $pc = $em->find('BBDurianBundle:PaymentCharge', $paymentChargeId);
        $this->assertNull($pc);

        $gatewayFees = $pgfRepo->findBy(array('paymentCharge' => $paymentChargeId));
        $this->assertEmpty($gatewayFees);

        $withdrawFee = $pwfRepo->findOneBy(array('paymentCharge' => $paymentChargeId));
        $this->assertNull($withdrawFee);

        $withdrawVerify = $pwvRepo->findOneBy(array('paymentCharge' => $paymentChargeId));
        $this->assertNull($withdrawVerify);

    }

    /**
     * 測試刪除線上支付設定的例外
     */
    public function testRemovePaymentChargeButExceptionOccur()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/payment_charge/128');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200008, $output['code']);
        $this->assertEquals('Cannot find specified PaymentCharge', $output['msg']);
    }

    /**
     * 測試刪除線上支付設定已被設定層級幣別相關資料
     */
    public function testRemovePaymentChargeButLevelCurrencyUse()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/payment_charge/5');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200036, $output['code']);
        $this->assertEquals('Can not remove PaymentCharge when LevelCurrency in use', $output['msg']);
    }

    /**
     * 測試取得線上存款設定
     */
    public function testGetDepositOnline()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/6/deposit_online');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertTrue($output['ret']['discount_give_up']);
        $this->assertTrue($output['ret']['audit_live']);
        $this->assertTrue($output['ret']['audit_ball']);
        $this->assertTrue($output['ret']['audit_complex']);
        $this->assertTrue($output['ret']['audit_normal']);
        $this->assertTrue($output['ret']['audit_3d']);
        $this->assertTrue($output['ret']['audit_battle']);
        $this->assertTrue($output['ret']['audit_virtual']);
        $this->assertEquals(Deposit::EACH, $output['ret']['discount']);
        $this->assertEquals(100, $output['ret']['discount_amount']);
        $this->assertEquals(2.12, $output['ret']['discount_percent']);
        $this->assertEquals(1, $output['ret']['discount_factor']);
        $this->assertEquals(500, $output['ret']['discount_limit']);
        $this->assertEquals(7000, $output['ret']['deposit_max']);
        $this->assertEquals(100, $output['ret']['deposit_min']);
        $this->assertEquals(10, $output['ret']['audit_live_amount']);
        $this->assertEquals(20, $output['ret']['audit_ball_amount']);
        $this->assertEquals(30, $output['ret']['audit_complex_amount']);
        $this->assertEquals(100, $output['ret']['audit_normal_amount']);
        $this->assertEquals(40, $output['ret']['audit_3d_amount']);
        $this->assertEquals(50, $output['ret']['audit_battle_amount']);
        $this->assertEquals(60, $output['ret']['audit_virtual_amount']);
        $this->assertEquals(5, $output['ret']['audit_discount_amount']);
        $this->assertEquals(40, $output['ret']['audit_loosen']);
        $this->assertEquals(10, $output['ret']['audit_administrative']);
    }

    /**
     * 測試要取得的線上存款設定不存在
     */
    public function testGetDepositOnlineWhenNotExist()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymentCharge = new PaymentCharge(CashDepositEntry::PAYWAY_CASH, 999, '台幣', true);
        $em->persist($paymentCharge);
        $em->flush();

        $client->request('GET', '/api/payment_charge/7/deposit_online');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200027, $output['code']);
        $this->assertEquals('No DepositOnline found', $output['msg']);
    }

    /**
     * 測試取得公司入款設定
     */
    public function testGetDepositCompany()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/6/deposit_company');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertTrue($output['ret']['discount_give_up']);
        $this->assertTrue($output['ret']['audit_live']);
        $this->assertTrue($output['ret']['audit_ball']);
        $this->assertTrue($output['ret']['audit_complex']);
        $this->assertTrue($output['ret']['audit_normal']);
        $this->assertTrue($output['ret']['audit_3d']);
        $this->assertTrue($output['ret']['audit_battle']);
        $this->assertTrue($output['ret']['audit_virtual']);
        $this->assertEquals(Deposit::EACH, $output['ret']['discount']);
        $this->assertEquals(51, $output['ret']['discount_amount']);
        $this->assertEquals(10, $output['ret']['discount_percent']);
        $this->assertEquals(4, $output['ret']['discount_factor']);
        $this->assertEquals(1000, $output['ret']['discount_limit']);
        $this->assertEquals(50, $output['ret']['other_discount_amount']);
        $this->assertEquals(5, $output['ret']['other_discount_percent']);
        $this->assertEquals(100, $output['ret']['other_discount_limit']);
        $this->assertEquals(500, $output['ret']['daily_discount_limit']);
        $this->assertEquals(5000, $output['ret']['deposit_max']);
        $this->assertEquals(10, $output['ret']['deposit_min']);
        $this->assertEquals(5, $output['ret']['audit_live_amount']);
        $this->assertEquals(10, $output['ret']['audit_ball_amount']);
        $this->assertEquals(15, $output['ret']['audit_complex_amount']);
        $this->assertEquals(100, $output['ret']['audit_normal_amount']);
        $this->assertEquals(20, $output['ret']['audit_3d_amount']);
        $this->assertEquals(25, $output['ret']['audit_battle_amount']);
        $this->assertEquals(30, $output['ret']['audit_virtual_amount']);
        $this->assertEquals(10, $output['ret']['audit_discount_amount']);
        $this->assertEquals(10, $output['ret']['audit_loosen']);
        $this->assertEquals(5, $output['ret']['audit_administrative']);
        $this->assertEquals(1000, $output['ret']['deposit_sc_max']);
        $this->assertEquals(5, $output['ret']['deposit_sc_min']);
        $this->assertEquals(900, $output['ret']['deposit_co_max']);
        $this->assertEquals(6, $output['ret']['deposit_co_min']);
        $this->assertEquals(800, $output['ret']['deposit_sa_max']);
        $this->assertEquals(7, $output['ret']['deposit_sa_min']);
        $this->assertEquals(700, $output['ret']['deposit_ag_max']);
        $this->assertEquals(8, $output['ret']['deposit_ag_min']);
    }

    /**
     * 測試要取得的公司入款設定不存在
     */
    public function testGetDepositCompanyWhenNotExist()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymentCharge = new PaymentCharge(CashDepositEntry::PAYWAY_CASH, 999, '台幣', true);
        $em->persist($paymentCharge);
        $em->flush();

        $client->request('GET', '/api/payment_charge/7/deposit_company');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200028, $output['code']);
        $this->assertEquals('No DepositCompany found', $output['msg']);
    }

    /**
     * 測試取得電子錢包設定
     */
    public function testGetDepositMobile()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/6/deposit_mobile');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertTrue($output['ret']['discount_give_up']);
        $this->assertTrue($output['ret']['audit_live']);
        $this->assertTrue($output['ret']['audit_ball']);
        $this->assertTrue($output['ret']['audit_complex']);
        $this->assertTrue($output['ret']['audit_normal']);
        $this->assertTrue($output['ret']['audit_3d']);
        $this->assertTrue($output['ret']['audit_battle']);
        $this->assertTrue($output['ret']['audit_virtual']);
        $this->assertEquals(Deposit::EACH, $output['ret']['discount']);
        $this->assertEquals(100, $output['ret']['discount_amount']);
        $this->assertEquals(2.12, $output['ret']['discount_percent']);
        $this->assertEquals(1, $output['ret']['discount_factor']);
        $this->assertEquals(500, $output['ret']['discount_limit']);
        $this->assertEquals(7000, $output['ret']['deposit_max']);
        $this->assertEquals(100, $output['ret']['deposit_min']);
        $this->assertEquals(10, $output['ret']['audit_live_amount']);
        $this->assertEquals(20, $output['ret']['audit_ball_amount']);
        $this->assertEquals(30, $output['ret']['audit_complex_amount']);
        $this->assertEquals(100, $output['ret']['audit_normal_amount']);
        $this->assertEquals(40, $output['ret']['audit_3d_amount']);
        $this->assertEquals(50, $output['ret']['audit_battle_amount']);
        $this->assertEquals(60, $output['ret']['audit_virtual_amount']);
        $this->assertEquals(5, $output['ret']['audit_discount_amount']);
        $this->assertEquals(40, $output['ret']['audit_loosen']);
        $this->assertEquals(10, $output['ret']['audit_administrative']);
    }

    /**
     * 測試要取得的電子錢包設定不存在
     */
    public function testGetDepositMobileWhenNotExist()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymentCharge = new PaymentCharge(CashDepositEntry::PAYWAY_CASH, 999, '台幣', true);
        $em->persist($paymentCharge);
        $em->flush();

        $client->request('GET', '/api/payment_charge/7/deposit_mobile');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200038, $output['code']);
        $this->assertEquals('No DepositMobile found', $output['msg']);
    }

    /**
     * 測試取得比特幣設定
     */
    public function testGetDepositBitcoin()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_charge/6/deposit_bitcoin');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertTrue($output['ret']['discount_give_up']);
        $this->assertTrue($output['ret']['audit_live']);
        $this->assertTrue($output['ret']['audit_ball']);
        $this->assertTrue($output['ret']['audit_complex']);
        $this->assertTrue($output['ret']['audit_normal']);
        $this->assertTrue($output['ret']['audit_3d']);
        $this->assertTrue($output['ret']['audit_battle']);
        $this->assertTrue($output['ret']['audit_virtual']);
        $this->assertEquals(Deposit::EACH, $output['ret']['discount']);
        $this->assertEquals(100, $output['ret']['discount_amount']);
        $this->assertEquals(2.12, $output['ret']['discount_percent']);
        $this->assertEquals(1, $output['ret']['discount_factor']);
        $this->assertEquals(500, $output['ret']['discount_limit']);
        $this->assertEquals(7000, $output['ret']['deposit_max']);
        $this->assertEquals(100, $output['ret']['deposit_min']);
        $this->assertEquals(10, $output['ret']['audit_live_amount']);
        $this->assertEquals(20, $output['ret']['audit_ball_amount']);
        $this->assertEquals(30, $output['ret']['audit_complex_amount']);
        $this->assertEquals(100, $output['ret']['audit_normal_amount']);
        $this->assertEquals(40, $output['ret']['audit_3d_amount']);
        $this->assertEquals(50, $output['ret']['audit_battle_amount']);
        $this->assertEquals(60, $output['ret']['audit_virtual_amount']);
        $this->assertEquals(5, $output['ret']['audit_discount_amount']);
        $this->assertEquals(40, $output['ret']['audit_loosen']);
        $this->assertEquals(10, $output['ret']['audit_administrative']);
        $this->assertEquals(1000, $output['ret']['bitcoin_fee_max']);
        $this->assertEquals(10, $output['ret']['bitcoin_fee_percent']);
    }

    /**
     * 測試要取得的比特幣設定不存在
     */
    public function testGetDepositBitcoinWhenNotExist()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymentCharge = new PaymentCharge(CashDepositEntry::PAYWAY_CASH, 999, '台幣', true);
        $em->persist($paymentCharge);
        $em->flush();

        $client->request('GET', '/api/payment_charge/7/deposit_bitcoin');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200046, $output['code']);
        $this->assertEquals('No DepositBitcoin found', $output['msg']);
    }

    /**
     * 測試修改線上存款設定
     */
    public function testSetDepositOnline()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = array(
            'discount' => Deposit::FIRST,
            'discount_give_up' => 0,
            'discount_amount' => 1,
            'discount_percent' => 33,
            'discount_factor' => 7,
            'discount_limit' => 1,
            'deposit_max' => 1,
            'deposit_min' => 1,
            'audit_live' => 0,
            'audit_live_amount' => 1,
            'audit_ball' => 0,
            'audit_ball_amount' => 1,
            'audit_complex' => 0,
            'audit_complex_amount' => 1,
            'audit_normal' => 0,
            'audit_3d' => 0,
            'audit_3d_amount' => 1,
            'audit_battle' => 0,
            'audit_battle_amount' => 1,
            'audit_virtual' => 0,
            'audit_virtual_amount' => 1,
            'audit_discount_amount' => 1,
            'audit_loosen' => 1,
            'audit_administrative' => 1
        );
        $client->request('PUT', '/api/payment_charge/6/deposit_online', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertFalse($output['ret']['discount_give_up']);
        $this->assertFalse($output['ret']['audit_live']);
        $this->assertFalse($output['ret']['audit_ball']);
        $this->assertFalse($output['ret']['audit_complex']);
        $this->assertFalse($output['ret']['audit_normal']);
        $this->assertFalse($output['ret']['audit_3d']);
        $this->assertFalse($output['ret']['audit_battle']);
        $this->assertFalse($output['ret']['audit_virtual']);
        $this->assertEquals(Deposit::FIRST, $output['ret']['discount']);
        $this->assertEquals(1, $output['ret']['discount_amount']);
        $this->assertEquals(33, $output['ret']['discount_percent']);
        $this->assertEquals(7, $output['ret']['discount_factor']);
        $this->assertEquals(1, $output['ret']['discount_limit']);
        $this->assertEquals(1, $output['ret']['deposit_max']);
        $this->assertEquals(1, $output['ret']['deposit_min']);
        $this->assertEquals(1, $output['ret']['audit_live_amount']);
        $this->assertEquals(1, $output['ret']['audit_ball_amount']);
        $this->assertEquals(1, $output['ret']['audit_complex_amount']);
        $this->assertEquals(100, $output['ret']['audit_normal_amount']);
        $this->assertEquals(1, $output['ret']['audit_3d_amount']);
        $this->assertEquals(1, $output['ret']['audit_battle_amount']);
        $this->assertEquals(1, $output['ret']['audit_virtual_amount']);
        $this->assertEquals(1, $output['ret']['audit_discount_amount']);
        $this->assertEquals(1, $output['ret']['audit_loosen']);
        $this->assertEquals(1, $output['ret']['audit_administrative']);

        // 檢查Log是否正確記錄
        $logOp= $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('deposit_online', $logOp->getTableName());
        $this->assertEquals('@payment_charge_id:6', $logOp->getMajorKey());
        $msg = '@discount:2=>1, '.
               '@discount_give_up:true=>false, '.
               '@discount_amount:100=>1, '.
               '@discount_percent:2.12=>33, '.
               '@discount_factor:1=>7, '.
               '@discount_limit:500=>1, '.
               '@deposit_max:7000=>1, '.
               '@deposit_min:100=>1, '.
               '@audit_live:true=>false, '.
               '@audit_live_amount:10=>1, '.
               '@audit_ball:true=>false, '.
               '@audit_ball_amount:20=>1, '.
               '@audit_complex:true=>false, '.
               '@audit_complex_amount:30=>1, '.
               '@audit_normal:true=>false, '.
               '@audit_3d:true=>false, '.
               '@audit_3d_amount:40=>1, '.
               '@audit_battle:true=>false, '.
               '@audit_battle_amount:50=>1, '.
               '@audit_virtual:true=>false, '.
               '@audit_virtual_amount:60=>1, '.
               '@audit_discount_amount:5=>1, '.
               '@audit_loosen:40=>1, '.
               '@audit_administrative:10=>1';
        $this->assertEquals($msg, $logOp->getMessage());
    }

    /**
     * 測試修改線上存款傳入不支援的設定
     */
    public function testSetDepositOnlineDiscountNotSupport()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = array('discount' => 777);
        $client->request('PUT', '/api/payment_charge/6/deposit_online', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('200029', $output['code']);
        $this->assertEquals('Not support this discount', $output['msg']);

        // 資料未被修改
        $deposit= $em->find('BBDurianBundle:DepositOnline', 6);
        $this->assertTrue($deposit->isDiscountGiveUp());
        $this->assertTrue($deposit->isAuditBall());
        $this->assertTrue($deposit->isAuditNormal());
        $this->assertEquals(Deposit::EACH, $deposit->getDiscount());
        $this->assertEquals(100, $deposit->getDiscountAmount());
        $this->assertEquals(2.12, $deposit->getDiscountPercent());
        $this->assertEquals(7000, $deposit->getDepositMax());
        $this->assertEquals(40, $deposit->getAuditLoosen());

        // 噴錯不會寫操作紀錄
        $logOp= $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試修改不存在的線上存款設定
     */
    public function testSetNotExistDepositOnline()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 6);
        $depositOnline = $paymentCharge->getDepositOnline();

        $em->remove($depositOnline);
        $em->flush();

        $client->request('PUT', '/api/payment_charge/6/deposit_online', []);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200027, $output['code']);
        $this->assertEquals('No DepositOnline found', $output['msg']);
    }

    /**
     * 測試修改公司入款設定
     */
    public function testSetDepositCompany()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = array(
            'discount' => Deposit::FIRST,
            'discount_give_up' => 0,
            'discount_amount' => 1,
            'discount_percent' => 33,
            'discount_factor' => 7,
            'discount_limit' => 1,
            'deposit_max' => 1,
            'deposit_min' => 1,
            'audit_live' => 0,
            'audit_live_amount' => 1,
            'audit_ball' => 0,
            'audit_ball_amount' => 1,
            'audit_complex' => 0,
            'audit_complex_amount' => 1,
            'audit_normal' => 0,
            'audit_3d' => 0,
            'audit_3d_amount' => 1,
            'audit_battle' => 0,
            'audit_battle_amount' => 1,
            'audit_virtual' => 0,
            'audit_virtual_amount' => 1,
            'audit_discount_amount' => 1,
            'audit_loosen' => 1,
            'audit_administrative' => 1,
            'other_discount_amount' => 1,
            'other_discount_percent' => 1,
            'other_discount_limit' => 1,
            'daily_discount_limit' => 1,
            'deposit_sc_max' => 20,
            'deposit_sc_min' => 9,
            'deposit_co_max' => 30,
            'deposit_co_min' => 8,
            'deposit_sa_max' => 40,
            'deposit_sa_min' => 6,
            'deposit_ag_max' => 50,
            'deposit_ag_min' => 5,
        );
        $client->request('PUT', '/api/payment_charge/6/deposit_company', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertFalse($output['ret']['discount_give_up']);
        $this->assertFalse($output['ret']['audit_live']);
        $this->assertFalse($output['ret']['audit_ball']);
        $this->assertFalse($output['ret']['audit_complex']);
        $this->assertFalse($output['ret']['audit_normal']);
        $this->assertFalse($output['ret']['audit_3d']);
        $this->assertFalse($output['ret']['audit_battle']);
        $this->assertFalse($output['ret']['audit_virtual']);
        $this->assertEquals(Deposit::FIRST, $output['ret']['discount']);
        $this->assertEquals(1, $output['ret']['discount_amount']);
        $this->assertEquals(33, $output['ret']['discount_percent']);
        $this->assertEquals(7, $output['ret']['discount_factor']);
        $this->assertEquals(1, $output['ret']['discount_limit']);
        $this->assertEquals(1, $output['ret']['deposit_max']);
        $this->assertEquals(1, $output['ret']['deposit_min']);
        $this->assertEquals(1, $output['ret']['audit_live_amount']);
        $this->assertEquals(1, $output['ret']['audit_ball_amount']);
        $this->assertEquals(1, $output['ret']['audit_complex_amount']);
        $this->assertEquals(100, $output['ret']['audit_normal_amount']);
        $this->assertEquals(1, $output['ret']['audit_3d_amount']);
        $this->assertEquals(1, $output['ret']['audit_battle_amount']);
        $this->assertEquals(1, $output['ret']['audit_virtual_amount']);
        $this->assertEquals(1, $output['ret']['audit_discount_amount']);
        $this->assertEquals(1, $output['ret']['audit_loosen']);
        $this->assertEquals(1, $output['ret']['audit_administrative']);
        $this->assertEquals(1, $output['ret']['other_discount_amount']);
        $this->assertEquals(1, $output['ret']['other_discount_percent']);
        $this->assertEquals(1, $output['ret']['other_discount_limit']);
        $this->assertEquals(1, $output['ret']['daily_discount_limit']);
        $this->assertEquals(20, $output['ret']['deposit_sc_max']);
        $this->assertEquals(9, $output['ret']['deposit_sc_min']);
        $this->assertEquals(30, $output['ret']['deposit_co_max']);
        $this->assertEquals(8, $output['ret']['deposit_co_min']);
        $this->assertEquals(40, $output['ret']['deposit_sa_max']);
        $this->assertEquals(6, $output['ret']['deposit_sa_min']);
        $this->assertEquals(50, $output['ret']['deposit_ag_max']);
        $this->assertEquals(5, $output['ret']['deposit_ag_min']);

        // 檢查Log是否正確記錄
        $logOp= $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('deposit_company', $logOp->getTableName());
        $this->assertEquals('@payment_charge_id:6', $logOp->getMajorKey());
        $msg = '@other_discount_amount:50=>1, '.
               '@other_discount_percent:5=>1, '.
               '@other_discount_limit:100=>1, '.
               '@daily_discount_limit:500=>1, '.
               '@deposit_sc_max:1000=>20, '.
               '@deposit_sc_min:5=>9, '.
               '@deposit_co_max:900=>30, '.
               '@deposit_co_min:6=>8, '.
               '@deposit_sa_max:800=>40, '.
               '@deposit_sa_min:7=>6, '.
               '@deposit_ag_max:700=>50, '.
               '@deposit_ag_min:8=>5, '.
               '@discount:2=>1, '.
               '@discount_give_up:true=>false, '.
               '@discount_amount:51=>1, '.
               '@discount_percent:10=>33, '.
               '@discount_factor:4=>7, '.
               '@discount_limit:1000=>1, '.
               '@deposit_max:5000=>1, '.
               '@deposit_min:10=>1, '.
               '@audit_live:true=>false, '.
               '@audit_live_amount:5=>1, '.
               '@audit_ball:true=>false, '.
               '@audit_ball_amount:10=>1, '.
               '@audit_complex:true=>false, '.
               '@audit_complex_amount:15=>1, '.
               '@audit_normal:true=>false, '.
               '@audit_3d:true=>false, '.
               '@audit_3d_amount:20=>1, '.
               '@audit_battle:true=>false, '.
               '@audit_battle_amount:25=>1, '.
               '@audit_virtual:true=>false, '.
               '@audit_virtual_amount:30=>1, '.
               '@audit_discount_amount:10=>1, '.
               '@audit_loosen:10=>1, '.
               '@audit_administrative:5=>1';
        $this->assertEquals($msg, $logOp->getMessage());
    }

    /**
     * 測試修改公司入款傳入不支援的設定
     */
    public function testSetDepositCompanyDiscountNotSupport()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = array('discount' => 999);
        $client->request('PUT', '/api/payment_charge/6/deposit_company', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('200029', $output['code']);
        $this->assertEquals('Not support this discount', $output['msg']);

        // 資料未被修改
        $deposit= $em->find('BBDurianBundle:DepositCompany', 6);
        $this->assertTrue($deposit->isAuditLive());
        $this->assertTrue($deposit->isAuditComplex());
        $this->assertEquals(Deposit::EACH, $deposit->getDiscount());
        $this->assertEquals(4, $deposit->getDiscountFactor());
        $this->assertEquals(100, $deposit->getOtherDiscountLimit());
        $this->assertEquals(500, $deposit->getDailyDiscountLimit());
        $this->assertEquals(10, $deposit->getDepositMin());
        $this->assertEquals(5, $deposit->getAuditAdministrative());

        // 噴錯不會寫操作紀錄
        $logOp= $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試修改不存在的公司入款設定
     */
    public function testSetNotExistDepositCompany()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 6);
        $depositCompany = $paymentCharge->getDepositCompany();

        $em->remove($depositCompany);
        $em->flush();

        $client->request('PUT', '/api/payment_charge/6/deposit_company', []);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200028, $output['code']);
        $this->assertEquals('No DepositCompany found', $output['msg']);
    }

    /**
     * 測試修改電子錢包設定
     */
    public function testSetDepositMobile()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'discount' => Deposit::FIRST,
            'discount_give_up' => 0,
            'discount_amount' => 1,
            'discount_percent' => 33,
            'discount_factor' => 7,
            'discount_limit' => 1,
            'deposit_max' => 1,
            'deposit_min' => 1,
            'audit_live' => 0,
            'audit_live_amount' => 1,
            'audit_ball' => 0,
            'audit_ball_amount' => 1,
            'audit_complex' => 0,
            'audit_complex_amount' => 1,
            'audit_normal' => 0,
            'audit_3d' => 0,
            'audit_3d_amount' => 1,
            'audit_battle' => 0,
            'audit_battle_amount' => 1,
            'audit_virtual' => 0,
            'audit_virtual_amount' => 1,
            'audit_discount_amount' => 1,
            'audit_loosen' => 1,
            'audit_administrative' => 1
        ];
        $client->request('PUT', '/api/payment_charge/6/deposit_mobile', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertFalse($output['ret']['discount_give_up']);
        $this->assertFalse($output['ret']['audit_live']);
        $this->assertFalse($output['ret']['audit_ball']);
        $this->assertFalse($output['ret']['audit_complex']);
        $this->assertFalse($output['ret']['audit_normal']);
        $this->assertFalse($output['ret']['audit_3d']);
        $this->assertFalse($output['ret']['audit_battle']);
        $this->assertFalse($output['ret']['audit_virtual']);
        $this->assertEquals(Deposit::FIRST, $output['ret']['discount']);
        $this->assertEquals(1, $output['ret']['discount_amount']);
        $this->assertEquals(33, $output['ret']['discount_percent']);
        $this->assertEquals(7, $output['ret']['discount_factor']);
        $this->assertEquals(1, $output['ret']['discount_limit']);
        $this->assertEquals(1, $output['ret']['deposit_max']);
        $this->assertEquals(1, $output['ret']['deposit_min']);
        $this->assertEquals(1, $output['ret']['audit_live_amount']);
        $this->assertEquals(1, $output['ret']['audit_ball_amount']);
        $this->assertEquals(1, $output['ret']['audit_complex_amount']);
        $this->assertEquals(100, $output['ret']['audit_normal_amount']);
        $this->assertEquals(1, $output['ret']['audit_3d_amount']);
        $this->assertEquals(1, $output['ret']['audit_battle_amount']);
        $this->assertEquals(1, $output['ret']['audit_virtual_amount']);
        $this->assertEquals(1, $output['ret']['audit_discount_amount']);
        $this->assertEquals(1, $output['ret']['audit_loosen']);
        $this->assertEquals(1, $output['ret']['audit_administrative']);

        // 檢查Log是否正確記錄
        $logOp= $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('deposit_mobile', $logOp->getTableName());
        $this->assertEquals('@payment_charge_id:6', $logOp->getMajorKey());
        $msg = '@discount:2=>1, ' .
            '@discount_give_up:true=>false, ' .
            '@discount_amount:100=>1, ' .
            '@discount_percent:2.12=>33, ' .
            '@discount_factor:1=>7, ' .
            '@discount_limit:500=>1, ' .
            '@deposit_max:7000=>1, ' .
            '@deposit_min:100=>1, ' .
            '@audit_live:true=>false, ' .
            '@audit_live_amount:10=>1, ' .
            '@audit_ball:true=>false, ' .
            '@audit_ball_amount:20=>1, ' .
            '@audit_complex:true=>false, ' .
            '@audit_complex_amount:30=>1, ' .
            '@audit_normal:true=>false, ' .
            '@audit_3d:true=>false, ' .
            '@audit_3d_amount:40=>1, ' .
            '@audit_battle:true=>false, ' .
            '@audit_battle_amount:50=>1, ' .
            '@audit_virtual:true=>false, ' .
            '@audit_virtual_amount:60=>1, ' .
            '@audit_discount_amount:5=>1, ' .
            '@audit_loosen:40=>1, ' .
            '@audit_administrative:10=>1';
        $this->assertEquals($msg, $logOp->getMessage());
    }

    /**
     * 測試修改電子錢包傳入不支援的設定
     */
    public function testSetDepositMobileDiscountNotSupport()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = ['discount' => 777];
        $client->request('PUT', '/api/payment_charge/6/deposit_mobile', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('200029', $output['code']);
        $this->assertEquals('Not support this discount', $output['msg']);

        // 資料未被修改
        $deposit= $em->find('BBDurianBundle:DepositMobile', 6);
        $this->assertTrue($deposit->isDiscountGiveUp());
        $this->assertTrue($deposit->isAuditBall());
        $this->assertTrue($deposit->isAuditNormal());
        $this->assertEquals(Deposit::EACH, $deposit->getDiscount());
        $this->assertEquals(100, $deposit->getDiscountAmount());
        $this->assertEquals(2.12, $deposit->getDiscountPercent());
        $this->assertEquals(7000, $deposit->getDepositMax());
        $this->assertEquals(40, $deposit->getAuditLoosen());

        // 噴錯不會寫操作紀錄
        $logOp= $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試修改不存在的電子錢包設定
     */
    public function testSetNotExistDepositMobile()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 6);
        $depositMobile = $paymentCharge->getDepositMobile();

        $em->remove($depositMobile);
        $em->flush();

        $client->request('PUT', '/api/payment_charge/6/deposit_mobile', []);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(200038, $output['code']);
        $this->assertEquals('No DepositMobile found', $output['msg']);
    }

    /**
     * 測試修改比特幣設定
     */
    public function testSetDepositBitcoin()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'discount' => Deposit::FIRST,
            'discount_give_up' => 0,
            'discount_amount' => 1,
            'discount_percent' => 33,
            'discount_factor' => 7,
            'discount_limit' => 1,
            'deposit_max' => 1,
            'deposit_min' => 1,
            'audit_live' => 0,
            'audit_live_amount' => 1,
            'audit_ball' => 0,
            'audit_ball_amount' => 1,
            'audit_complex' => 0,
            'audit_complex_amount' => 1,
            'audit_normal' => 0,
            'audit_3d' => 0,
            'audit_3d_amount' => 1,
            'audit_battle' => 0,
            'audit_battle_amount' => 1,
            'audit_virtual' => 0,
            'audit_virtual_amount' => 1,
            'audit_discount_amount' => 1,
            'audit_loosen' => 1,
            'audit_administrative' => 1,
            'bitcoin_fee_max' => 200,
            'bitcoin_fee_percent' => 2,
        ];
        $client->request('PUT', '/api/payment_charge/6/deposit_bitcoin', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertFalse($output['ret']['discount_give_up']);
        $this->assertFalse($output['ret']['audit_live']);
        $this->assertFalse($output['ret']['audit_ball']);
        $this->assertFalse($output['ret']['audit_complex']);
        $this->assertFalse($output['ret']['audit_normal']);
        $this->assertFalse($output['ret']['audit_3d']);
        $this->assertFalse($output['ret']['audit_battle']);
        $this->assertFalse($output['ret']['audit_virtual']);
        $this->assertEquals(Deposit::FIRST, $output['ret']['discount']);
        $this->assertEquals(1, $output['ret']['discount_amount']);
        $this->assertEquals(33, $output['ret']['discount_percent']);
        $this->assertEquals(7, $output['ret']['discount_factor']);
        $this->assertEquals(1, $output['ret']['discount_limit']);
        $this->assertEquals(1, $output['ret']['deposit_max']);
        $this->assertEquals(1, $output['ret']['deposit_min']);
        $this->assertEquals(1, $output['ret']['audit_live_amount']);
        $this->assertEquals(1, $output['ret']['audit_ball_amount']);
        $this->assertEquals(1, $output['ret']['audit_complex_amount']);
        $this->assertEquals(100, $output['ret']['audit_normal_amount']);
        $this->assertEquals(1, $output['ret']['audit_3d_amount']);
        $this->assertEquals(1, $output['ret']['audit_battle_amount']);
        $this->assertEquals(1, $output['ret']['audit_virtual_amount']);
        $this->assertEquals(1, $output['ret']['audit_discount_amount']);
        $this->assertEquals(1, $output['ret']['audit_loosen']);
        $this->assertEquals(1, $output['ret']['audit_administrative']);
        $this->assertEquals(200, $output['ret']['bitcoin_fee_max']);
        $this->assertEquals(2, $output['ret']['bitcoin_fee_percent']);

        // 檢查Log是否正確記錄
        $logOp= $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('deposit_bitcoin', $logOp->getTableName());
        $this->assertEquals('@payment_charge_id:6', $logOp->getMajorKey());
        $msg = '@bitcoin_fee_max:1000=>200, ' .
            '@bitcoin_fee_percent:10=>2, ' .
            '@discount:2=>1, ' .
            '@discount_give_up:true=>false, ' .
            '@discount_amount:100=>1, ' .
            '@discount_percent:2.12=>33, ' .
            '@discount_factor:1=>7, ' .
            '@discount_limit:500=>1, ' .
            '@deposit_max:7000=>1, ' .
            '@deposit_min:100=>1, ' .
            '@audit_live:true=>false, ' .
            '@audit_live_amount:10=>1, ' .
            '@audit_ball:true=>false, ' .
            '@audit_ball_amount:20=>1, ' .
            '@audit_complex:true=>false, ' .
            '@audit_complex_amount:30=>1, ' .
            '@audit_normal:true=>false, ' .
            '@audit_3d:true=>false, ' .
            '@audit_3d_amount:40=>1, ' .
            '@audit_battle:true=>false, ' .
            '@audit_battle_amount:50=>1, ' .
            '@audit_virtual:true=>false, ' .
            '@audit_virtual_amount:60=>1, ' .
            '@audit_discount_amount:5=>1, ' .
            '@audit_loosen:40=>1, ' .
            '@audit_administrative:10=>1';
        $this->assertEquals($msg, $logOp->getMessage());
    }

    /**
     * 測試修改比特幣傳入不支援的設定
     */
    public function testSetDepositBitcoinDiscountNotSupport()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = ['discount' => 777];
        $client->request('PUT', '/api/payment_charge/6/deposit_bitcoin', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('200029', $output['code']);
        $this->assertEquals('Not support this discount', $output['msg']);

        // 資料未被修改
        $deposit= $em->find('BBDurianBundle:DepositBitcoin', 6);
        $this->assertTrue($deposit->isDiscountGiveUp());
        $this->assertTrue($deposit->isAuditBall());
        $this->assertTrue($deposit->isAuditNormal());
        $this->assertEquals(Deposit::EACH, $deposit->getDiscount());
        $this->assertEquals(100, $deposit->getDiscountAmount());
        $this->assertEquals(2.12, $deposit->getDiscountPercent());
        $this->assertEquals(7000, $deposit->getDepositMax());
        $this->assertEquals(40, $deposit->getAuditLoosen());

        // 噴錯不會寫操作紀錄
        $logOp= $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試修改不存在的比特幣設定
     */
    public function testSetNotExistDepositBitcoin()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 6);
        $depositBitcoin = $paymentCharge->getDepositBitcoin();

        $em->remove($depositBitcoin);
        $em->flush();

        $client->request('PUT', '/api/payment_charge/6/deposit_bitcoin', []);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150200046, $output['code']);
        $this->assertEquals('No DepositBitcoin found', $output['msg']);
    }
}
