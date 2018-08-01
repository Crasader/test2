<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CardToPay;

class CardToPayTest extends DurianTestCase
{
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

        $cardToPay = new CardToPay();
        $cardToPay->getVerifyData();
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

        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('3Wd1T4WESw76RT');

        $sourceData = ['number' => ''];
        $cardToPay->setOptions($sourceData);
        $cardToPay->getVerifyData();
    }

    /**
     * 測試加密，這邊參數設定是用文件的範例來測試的
     */
    public function testSetEncodeSuccess()
    {
        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('a1b2c3d4e5');

        $sourceData = [
            'number'          => '88000002',
            'orderId'         => '20110331201103312011033120110331',
            'orderCreateDate' => '20110331',
            'amount'          => '1234.56'
        ];

        $cardToPay->setOptions($sourceData);
        $encodeData = $cardToPay->getVerifyData();

        $this->assertEquals($sourceData['number'], base64_decode($encodeData['mrch_no']));
        $this->assertEquals($sourceData['orderId'], base64_decode($encodeData['ord_no']));
        $this->assertSame("1234.56", base64_decode($encodeData['ord_amt']));
        $this->assertEquals('20110331', base64_decode($encodeData['ord_date']));
        $this->assertEquals('3DB764C192D8629EDEAD700CB6500123', $encodeData['mac']);
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

        $cardToPay = new CardToPay();

        $cardToPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數
     */
    public function testVerifyNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('3Wd1T4WESw76RT');

        $sourceData = [
            'ver'        => base64_encode('01'),
            'mrch_no'    => base64_encode('88000002'),
            'ord_no'     => base64_encode('201403190000000123'),
            'ord_date'   => base64_encode('20140319'),
            'ord_amt'    => base64_encode('1234.56'),
            'sno'        => base64_encode('111122334567'),
            'ord_result' => base64_encode('success'),
            'add_msg'    => base64_encode(''),
            'ord_seq'    => base64_encode('10000000000000001'),
            'mac'        => '7E762345973E34ACC046907BDE161401'
        ];

        $cardToPay->setOptions($sourceData);
        $cardToPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試mac:加密簽名)
     */
    public function testVerifyWithoutMac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('3Wd1T4WESw76RT');

        $sourceData = [
            'ver'        => base64_encode('01'),
            'mrch_no'    => base64_encode('88000002'),
            'ord_no'     => base64_encode('201403190000000123'),
            'ord_date'   => base64_encode('20140319'),
            'ord_amt'    => base64_encode('1234.56'),
            'sno'        => base64_encode('111122334567'),
            'ord_status' => base64_encode('1'),
            'ord_result' => base64_encode('success'),
            'add_msg'    => base64_encode(''),
            'ord_seq'    => base64_encode('10000000000000001')
        ];

        $cardToPay->setOptions($sourceData);
        $cardToPay->verifyOrderPayment([]);
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

        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('3Wd1T4WESw76RT');

        $sourceData = [
            'ver'        => base64_encode('01'),
            'mrch_no'    => base64_encode('88000002'),
            'ord_no'     => base64_encode('201403190000000123'),
            'ord_date'   => base64_encode('20140319'),
            'ord_amt'    => base64_encode('1234.56'),
            'sno'        => base64_encode('111122334567'),
            'ord_status' => base64_encode('1'),
            'ord_result' => base64_encode('success'),
            'add_msg'    => base64_encode(''),
            'ord_seq'    => base64_encode('10000000000000001'),
            'mac'        => '1e1088985104bedbce48c9ea909315e9'
        ];

        $cardToPay->setOptions($sourceData);
        $cardToPay->verifyOrderPayment([]);
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

        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('3Wd1T4WESw76RT');

        $sourceData = [
            'ver'        => base64_encode('01'),
            'mrch_no'    => base64_encode('88000002'),
            'ord_no'     => base64_encode('201403190000000123'),
            'ord_date'   => base64_encode('20140319'),
            'ord_amt'    => base64_encode('1234.56'),
            'sno'        => base64_encode('111122334567'),
            'ord_status' => base64_encode('1'),
            'ord_result' => base64_encode('fail'),
            'add_msg'    => base64_encode(''),
            'ord_seq'    => base64_encode('10000000000000001'),
            'mac'        => '894E16C70104E333E69F758B6C6A6A97'
        ];

        $cardToPay->setOptions($sourceData);
        $cardToPay->verifyOrderPayment([]);

        $sourceData = [
            'ver'        => base64_encode('01'),
            'mrch_no'    => base64_encode('88000002'),
            'ord_no'     => base64_encode('201403190000000123'),
            'ord_date'   => base64_encode('20140319'),
            'ord_amt'    => base64_encode('1234.56'),
            'sno'        => base64_encode('111122334567'),
            'ord_status' => base64_encode('0'),
            'ord_result' => base64_encode('success'),
            'add_msg'    => base64_encode(''),
            'ord_seq'    => base64_encode('10000000000000001'),
            'mac'        => '894E16C70104E333E69F758B6C6A6A97'
        ];

        $cardToPay->setOptions($sourceData);
        $cardToPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('3Wd1T4WESw76RT');

        $sourceData = [
            'ver'        => base64_encode('01'),
            'mrch_no'    => base64_encode('88000002'),
            'ord_no'     => base64_encode('201403190000000123'),
            'ord_date'   => base64_encode('20140319'),
            'ord_amt'    => base64_encode('1234.56'),
            'sno'        => base64_encode('111122334567'),
            'ord_status' => base64_encode('1'),
            'ord_result' => base64_encode('success'),
            'add_msg'    => base64_encode(''),
            'ord_seq'    => base64_encode('10000000000000001'),
            'mac'        => '7E762345973E34ACC046907BDE161401'
        ];

        $entry = ['id' => '201403190000000001'];

        $cardToPay->setOptions($sourceData);
        $cardToPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('3Wd1T4WESw76RT');

        $sourceData = [
            'ver'        => base64_encode('01'),
            'mrch_no'    => base64_encode('88000002'),
            'ord_no'     => base64_encode('201403190000000123'),
            'ord_date'   => base64_encode('20140319'),
            'ord_amt'    => base64_encode('1234.56'),
            'sno'        => base64_encode('111122334567'),
            'ord_status' => base64_encode('1'),
            'ord_result' => base64_encode('success'),
            'add_msg'    => base64_encode(''),
            'ord_seq'    => base64_encode('10000000000000001'),
            'mac'        => '7E762345973E34ACC046907BDE161401'
        ];

        $entry = [
            'id' => '201403190000000123',
            'amount' => '1234.0000'
        ];

        $cardToPay->setOptions($sourceData);
        $cardToPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $cardToPay = new CardToPay();
        $cardToPay->setPrivateKey('3Wd1T4WESw76RT');

        $sourceData = [
            'ver'        => base64_encode('01'),
            'mrch_no'    => base64_encode('88000002'),
            'ord_no'     => base64_encode('201403190000000123'),
            'ord_date'   => base64_encode('20140319'),
            'ord_amt'    => base64_encode('1234.56'),
            'sno'        => base64_encode('111122334567'),
            'ord_status' => base64_encode('1'),
            'ord_result' => base64_encode('success'),
            'add_msg'    => base64_encode(''),
            'ord_seq'    => base64_encode('10000000000000001'),
            'mac'        => '7E762345973E34ACC046907BDE161401'
        ];

        $entry = [
            'id' => '201403190000000123',
            'amount' => '1234.5600'
        ];

        $cardToPay->setOptions($sourceData);
        $cardToPay->verifyOrderPayment($entry);

        $this->assertEquals('[success]', $cardToPay->getMsg());
    }
}
