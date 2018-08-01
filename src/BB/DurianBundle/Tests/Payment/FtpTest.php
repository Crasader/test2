<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Ftp;

class FtpTest extends DurianTestCase
{
    /**
     * 訂單參數
     *
     * @var array
     */
    private $options;

    /**
     * 返回結果
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->options = [
            'number' => 'qbWtqWbIVuz65aPU1oBL9x811D7pJq33',
            'amount' => '2',
            'orderId' => '201805030000011605',
            'orderCreateDate' => '2018-05-03 11:40:00',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://www.seafood.help/',
            'ip' => '192.168.1.1',
            'postUrl' => 'https://www.funtopay.com',
        ];

        $this->returnResult = [
            'amount' => '200',
            'auth_key' => 'qbWtqWbIVuz65aPU1oBL9x811D7pJq33',
            'auth_signature' => '81d578fd60fe4671b33525b171e36e6727bd951f81d6733989971d45fbd6fffe',
            'auth_timestamp' => '1525346658',
            'auth_version' => '1.0',
            'code' => '1',
            'ftp_response' => [
                'code' => '1',
                'message' => 'Transaction successful',
            ],
            'order_id' => '201805030000011605',
            'status' => 'OK',
            'trans_id' => 'DcrGKQJymyaf(Hy4)(W4$m5*D5g,^MLi',
        ];
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ftp = new Ftp();
        $ftp->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->options['paymentVendorId'] = '9999';

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->setOptions($this->options);
        $ftp->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->setOptions($this->options);
        $requestData = $ftp->getVerifyData();

        $this->assertEquals('https://www.funtopay.com/qq/deposit/', $requestData['post_url']);
        $this->assertEquals('200', $requestData['params']['amount']);
        $this->assertEquals('qbWtqWbIVuz65aPU1oBL9x811D7pJq33', $requestData['params']['api_key']);
        $this->assertEquals('201805030000011605', $requestData['params']['order_id']);
        $this->assertEquals('CNY', $requestData['params']['currency']);
        $this->assertEquals('', $requestData['params']['bank']);
        $this->assertEquals('http://www.seafood.help/', $requestData['params']['callback_url']);
        $this->assertEquals('http://www.seafood.help/', $requestData['params']['return_url']);
        $this->assertEquals('1.0', $requestData['params']['auth_version']);
        $this->assertEquals('qbWtqWbIVuz65aPU1oBL9x811D7pJq33', $requestData['params']['auth_key']);
        $this->assertEquals(1525318800, $requestData['params']['auth_timestamp']);
        $this->assertEquals('d449ae596bb0567f42a477adfac8a3015fb431aaadfb2c937d574299924eed33', $requestData['params']['auth_signature']);
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ftp = new Ftp();
        $ftp->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['auth_signature']);

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->setOptions($this->returnResult);
        $ftp->verifyOrderPayment([]);
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

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->setOptions($this->returnResult);
        $ftp->verifyOrderPayment([]);
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

        $this->returnResult['ftp_response']['code'] = '2';
        $this->returnResult['ftp_response']['message'] = 'Transaction failed';
        $this->returnResult['auth_signature'] = '626d9c94edb6be2c6ff24ff5d3aa8f7f0dad292ae12af43b86570fc206f3d76b';

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->setOptions($this->returnResult);
        $ftp->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $this->returnResult['auth_signature'] = '10d5e9940819dd234a85c5e742cee2cd8501edcb0291852040a5dfb8b30aa7c9';

        $entry = [
            'id' => '201801190000003819',
        ];

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->setOptions($this->returnResult);
        $ftp->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $this->returnResult['auth_signature'] = '10d5e9940819dd234a85c5e742cee2cd8501edcb0291852040a5dfb8b30aa7c9';

        $entry = [
            'id' => '201805030000011605',
            'amount' => '2.8',
        ];

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->setOptions($this->returnResult);
        $ftp->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $this->returnResult['auth_signature'] = '10d5e9940819dd234a85c5e742cee2cd8501edcb0291852040a5dfb8b30aa7c9';

        $entry = [
            'id' => '201805030000011605',
            'amount' => '2',
        ];

        $ftp = new Ftp();
        $ftp->setPrivateKey('test');
        $ftp->setOptions($this->returnResult);
        $ftp->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $ftp->getMsg());
    }
}
