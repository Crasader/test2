<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;
use BB\DurianBundle\Entity\BankInfo;

class PaymentGatewayTest extends DurianTestCase
{
    /**
     * 測試新增支付平台
     */
    public function testNewPaymentGateway()
    {
        $code = 'BBPAY';
        $name = 'BBPAY';
        $postUrl = '';
        $id = 99;
        $version = 0;
        $orderId = 1;

        $paymentGateway = new PaymentGateway($code, $name, $postUrl, $orderId);

        $paymentGateway->setId($id);
        $this->assertEquals($version, $paymentGateway->getVersion());

        $array = $paymentGateway->toArray();

        $this->assertEquals($id, $array['id']);
        $this->assertEquals($code, $array['code']);
        $this->assertEquals($name, $array['name']);
        $this->assertEquals($postUrl, $array['post_url']);
        $this->assertFalse($array['auto_reop']);
        $this->assertEquals('', $array['reop_url']);
        $this->assertEquals('', $array['label']);
        $this->assertEquals('', $array['verify_url']);
        $this->assertEquals('', $array['verify_ip']);
        $this->assertFalse($paymentGateway->isRemoved());
        $this->assertFalse($paymentGateway->isWithdraw());
        $this->assertTrue($array['hot']);
        $this->assertEquals($orderId, $array['order_id']);
        $this->assertFalse($array['upload_key']);
        $this->assertTrue($array['deposit']);
        $this->assertFalse($array['mobile']);
        $this->assertEquals('', $array['withdraw_url']);
        $this->assertEquals('', $array['withdraw_host']);
        $this->assertFalse($array['withdraw_tracking']);
        $this->assertFalse($array['random_float']);
        $this->assertEquals('', $array['document_url']);
    }

    /**
     * 測試新增支付平台
     */
    public function testEditPaymentGateway()
    {
        $code = 'BBPAY';
        $name = 'BBPAY';
        $postUrl = '';
        $reopUrl = 'http://re.op';
        $orderId = 1;
        $withdrawUrl = 'http://pay.com/withdraw';
        $withdrawHost = 'pay.com';
        $documentUrl = 'http://pay.com/document';

        $paymentGateway = new PaymentGateway($code, $name, $postUrl, $orderId);

        $this->assertEquals($code, $paymentGateway->getCode());
        $this->assertEquals($name, $paymentGateway->getName());
        $this->assertEquals($postUrl, $paymentGateway->getPostUrl());
        $this->assertEquals($orderId, $paymentGateway->getOrderId());

        $paymentGateway->setCode('TestPay');
        $this->assertEquals('TestPay', $paymentGateway->getCode());

        $paymentGateway->setName('TestPay');
        $this->assertEquals('TestPay', $paymentGateway->getName());

        $paymentGateway->setPostUrl('http://pay.com/pay');
        $this->assertEquals('http://pay.com/pay', $paymentGateway->getPostUrl());

        $paymentGateway->setAutoReop(true);
        $this->assertTrue($paymentGateway->isAutoReop());

        $paymentGateway->setReopUrl($reopUrl);
        $this->assertEquals($reopUrl, $paymentGateway->getReopUrl());

        $paymentGateway->setWithdraw(true);
        $this->assertTrue($paymentGateway->isWithdraw());

        $paymentGateway->setHot(false);
        $this->assertFalse($paymentGateway->isHot());

        $paymentGateway->setUploadKey(true);
        $this->assertTrue($paymentGateway->isUploadKey());

        $paymentGateway->setDeposit(false);
        $this->assertFalse($paymentGateway->isDeposit());

        $paymentGateway->setMobile(true);
        $this->assertTrue($paymentGateway->isMobile());

        $paymentGateway->setWithdrawUrl($withdrawUrl);
        $this->assertEquals($withdrawUrl, $paymentGateway->getWithdrawUrl());

        $paymentGateway->setWithdrawHost($withdrawHost);
        $this->assertEquals($withdrawHost, $paymentGateway->getWithdrawHost());

        $paymentGateway->setWithdrawTracking(true);
        $this->assertTrue($paymentGateway->isWithdrawTracking());

        $paymentGateway->setRandomFloat(true);
        $this->assertTrue($paymentGateway->isRandomFloat());

        $paymentGateway->setDocumentUrl($documentUrl);
        $this->assertEquals($documentUrl, $paymentGateway->getDocumentUrl());
    }

    /**
     * 測試設定付款方式&付款廠商
     */
    public function testPaymentMethodPaymentVendor()
    {
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $paymentMethod = new PaymentMethod('mm');
        $paymentVendor = new PaymentVendor($paymentMethod, 'vv');

        $paymentGateway->addPaymentMethod($paymentMethod);
        $pgpm = $paymentGateway->getPaymentMethod();
        $this->assertEquals($paymentMethod, $pgpm[0]);

        $paymentGateway->addPaymentVendor($paymentVendor);
        $pgpv = $paymentGateway->getPaymentVendor();
        $this->assertEquals($paymentVendor, $pgpv[0]);

        $paymentGateway->removePaymentMethod($paymentMethod);
        $paymentGateway->removePaymentVendor($paymentVendor);
        $this->assertEquals(0, count($paymentGateway->getPaymentMethod()));
        $this->assertEquals(0, count($paymentGateway->getPaymentVendor()));
    }

    /**
     * 測試刪除支付平台
     */
    public function testRemovePaymentGateway()
    {
        $paymentGateway = new PaymentGateway('Mofoo', '魔付', 'http://mofoo.test', 1);

        //一開始是沒刪除
        $this->assertFalse($paymentGateway->isRemoved());

        //測試刪除
        $paymentGateway->remove();
        $this->assertTrue($paymentGateway->isRemoved());
    }

    /**
     * 測試設定label
     */
    public function testSetPaymentGatewayLabel()
    {
        $paymentGateway = new PaymentGateway('Baofoo', '寶付', 'http://baofoo.test', 1);

        $this->assertNull($paymentGateway->getLabel());

        $paymentGateway->setLabel('Baofoo');

        $this->assertEquals('Baofoo', $paymentGateway->getLabel());
    }

    /**
     * 測試設定verifyUrl
     */
    public function testSetPaymentGatewayVerifyUrl()
    {
        $paymentGateway = new PaymentGateway('Baofoo', '寶付', 'http://baofoo.test', 1);

        $this->assertEquals('', $paymentGateway->getVerifyUrl());

        $paymentGateway->setVerifyUrl('www.boofoo.com');

        $this->assertEquals('www.boofoo.com', $paymentGateway->getVerifyUrl());
    }

    /**
     * 測試設定verifyIp
     */
    public function testSetPaymentGatewayVerifyIp()
    {
        $paymentGateway = new PaymentGateway('Baofoo', '寶付', 'http://baofoo.test', 1);

        $this->assertEquals('', $paymentGateway->getVerifyIp());

        $paymentGateway->setVerifyIp('http://127.0.0.1');

        $this->assertEquals('http://127.0.0.1', $paymentGateway->getVerifyIp());
    }

    /**
     * 測試支付平台是否驗證綁定IP
     */
    public function testPaymentGatewayBindIpAndUnbindIp()
    {
        $paymentGateway = new PaymentGateway('Baofoo', '寶付', 'http://baofoo.test', 1);

        //一開始是不驗證
        $this->assertFalse($paymentGateway->isBindIp());

        //測試啟用驗證綁定IP
        $paymentGateway->bindIp();
        $this->assertTrue($paymentGateway->isBindIp());

        //測試停用驗證綁定IP
        $paymentGateway->unbindIp();
        $this->assertFalse($paymentGateway->isBindIp());
    }

    /**
     * 測試設定支付平台支援的出款銀行
     */
    public function testBankInfo()
    {
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $bankInfo = new BankInfo('工商銀行');
        $bankInfo->setWithdraw(true);

        $paymentGateway->addBankInfo($bankInfo);
        $pgbi = $paymentGateway->getBankInfo();
        $this->assertEquals($bankInfo, $pgbi[0]);

        $paymentGateway->removeBankInfo($bankInfo);
        $this->assertEquals(0, count($paymentGateway->getBankInfo()));
    }
}
