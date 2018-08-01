<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\EasyPay;

class EasyPayTest extends DurianTestCase
{
    /**
     * 此部分用於需要取得MerchantExtra資料的時候
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * 此部分用於需要取得MerchantExtra資料的時候
     */
    public function setUp()
    {
        parent::setUp();

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockMerchantExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantExtra')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValue($mockEm));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue($mockMerchantExtra));

        $mockMerchantExtra->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue('2461851318@qq.com'));

        $this->container = $container;
    }

    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $easyPay = new EasyPay();
        $easyPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd');

        $sourceData = ['number' => ''];

        $easyPay->setOptions($sourceData);
        $easyPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd');

        $sourceData = [
            'number' => '100000000006463',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '201404070013026085',
            'amount' => '590',
            'paymentVendorId' => '999',
            'merchantId' => '47266',
            'domain' => '6',
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->getVerifyData();
    }

    /**
     * 測試取得驗證資料,找不到商家的SellerEmail附加設定值
     */
    public function testGetVerifyDataButNoSellerEmailSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd');

        $sourceData = [
            'number' => '100000000006463',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '201404070013026085',
            'amount' => '590',
            'paymentVendorId' => '12',
            'merchantId' => '47266',
            'merchant_extra' => [],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '100000000006463',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '201404070013026085',
            'amount' => '590',
            'paymentVendorId' => '12', //'12' => 'CEB'
            'merchant_extra' => ['seller_email' => '2461851318@qq.com'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd');
        $easyPay->setOptions($sourceData);
        $encodeData = $easyPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals($notifyUrl, $encodeData['notify_url']);
        $this->assertEquals($notifyUrl, $encodeData['return_url']);
        $this->assertEquals($sourceData['orderId'], $encodeData['out_trade_no']);
        $this->assertSame('590.00', $encodeData['total_fee']);
        $this->assertEquals('CEB', $encodeData['defaultbank']);
        $this->assertEquals('2461851318@qq.com', $encodeData['seller_email']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $easyPay = new EasyPay();

        $easyPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試trade_status:交易狀態)
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'discount'             => '0.00',
            'sign'                 => 'e96df31c224fc0555147b5e9d8e186c6',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '2461851318@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試sign:加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '2461851318@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'sign'                 => 'e96df31c224fw945d147b5e9d8e186c6',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '2461851318@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FILED',
            'discount'             => '0.00',
            'sign'                 => 'bf34f682374d4983283421ecd35ad87e',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '2461851318@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'sign'                 => 'e96df31c224fc0555147b5e9d8e186c6',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '2461851318@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $entry = ['id' => '20140113143143'];

        $easyPay->setOptions($sourceData);
        $easyPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'sign'                 => 'e96df31c224fc0555147b5e9d8e186c6',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '2461851318@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $entry = [
            'id' => '201404070013044019',
            'amount' => '115.00'
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證沒有buyer_id也可以通過驗證
     */
    public function testVerifyWithoutSubject()
    {
        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'sign'                 => 'e96df31c224fc0555147b5e9d8e186c6',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '2461851318@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $entry = [
            'id' => '201404070013044019',
            'amount' => '110.00'
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $easyPay->getMsg());
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $easyPay = new EasyPay();
        $easyPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'sign'                 => 'e96df31c224fc0555147b5e9d8e186c6',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '2461851318@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $entry = [
            'id' => '201404070013044019',
            'amount' => '110.00'
        ];

        $easyPay->setOptions($sourceData);
        $easyPay->verifyOrderPayment($entry);
    }
}
