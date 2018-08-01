<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SDPay;

class SDPayTest extends DurianTestCase
{
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

        $sDPay = new SDPay();
        $sDPay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->getVerifyData();
    }

    /**
     * 測試支付時用戶資料參數未指定支付參數
     */
    public function testPayUserinfoDataWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => 'SZ9394',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'orderId' => '201805290000013428',
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($sourceData);
        $sDPay->getVerifyData();
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

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'postUrl' => 'sdpay.officenewline.org',
            'paymentVendorId' => '9999',
            'number' => 'SZ9394',
            'orderId' => '201805290000013428',
            'amount' => '1',
            'orderCreateDate' => '2018-05-30 11:45:55',
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w=',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $sDPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'postUrl' => 'sdpay.officenewline.org',
            'paymentVendorId' => '1088',
            'number' => 'SZ9394',
            'orderId' => '201805290000013428',
            'amount' => '1',
            'orderCreateDate' => '2018-05-30 11:45:55',
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w=',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $data = $sDPay->getVerifyData();

        $des = 'U+TiYHUtXXhlDQ/9TaLEUbfU2JPioykLiewe9BqwuHmedmLh3uborTJXQOIGWDTi4KyDEhZtRZhkgVldIVpJ714iu5RIqgDV' .
            '5y/G9QkUry+7v8Q9Iv42m9Ed1jRw0fu7sOONqrTsWzQE8UQIxUhZKDNzman5UnWdKDqCnxaicQaJ4i957WzJl52r+iiBBtgiZsZf+ol' .
            'gQJIzgH+2Hs73R1m4lK5QMfGUoY77Y8F+ZPBgeYvYpSINbqnc3TRaajD1ear6a4TCfiwRFjYDfpmmgSUZFIKVPiDotNP5BYBtfDKw54' .
            'TprZ3rhrS/+kk6MqhXFgZxo9zWnRnptByzoSBUmjpc5jXBbvMvX115+581qzAk9B3JXT1EQNeUrckxmRNWL3+igFP5SqDyXT4zb7ehh' .
            'Ne49TtnslAui/zTv3gHRGPA/vrzWAW+hVHjrP0jGv9Oq87fS9nj1gzDC60W5iVj/avt3I8MQDGVBQpumWaLyN+QlXRUdFsPdg/pkv8J' .
            'yCTY4AxqsvXnWQrxMTx9t/9jzZSML0VwsND8T5CyAvWAYNu9JW/B1gm7Di5yaHS0Z5hSSi3Kl5b3c82qhnqgcyCYgWy32/qBqNe3otC' .
            'wngiZeoViNUt/We6W+tMjiex71TVivtTbz9sJUEpF2yIkJZtuCrAjVT7s4PV9lop5dusnqIg=';

        $this->assertEquals('SZ9394', $data['params']['pid']);
        $this->assertEquals($des, $data['params']['des']);
        $this->assertEquals('http://api.m.sdpay.officenewline.org:11403/PMToService2.aspx', $data['post_url']);
    }

    /**
     * 測試銀聯在線
     */
    public function testQuickPay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'postUrl' => 'sdpay.officenewline.org',
            'paymentVendorId' => '278',
            'number' => 'SZ9394',
            'orderId' => '201805290000013428',
            'amount' => '1',
            'orderCreateDate' => '2018-05-30 11:45:55',
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w=',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $data = $sDPay->getVerifyData();

        $des = 'U+TiYHUtXXhlDQ/9TaLEUbfU2JPioykLiewe9BqwuHmedmLh3uborTJXQOIGWDTi7t5zutdEhy8I324LQlyVLR' .
            'BcSz2LLXtgOsihky2s6s9Gg+WXnLn0pxmzhAyoObrChv/qntWhJe99M2ZjAqYH3Iux7vth9RTCGD8JkenAoyS5KOAc7ZV' .
            '7snBYsZy14LGtEWvsULwXU82/d6ezUJn2BplHh7+WVFso0CSer5FjEWp0oM4EAK28BD8/XExyCIaKviINfogceH5bRyQm' .
            'PGa5pZBDE9nvmXz5QQVthf3Ow6Ea83J3kxrRQxUxw9/wyoqjj1X3YFbYCX3nwxVnGgSea/zU/Odg1sC+yw30vw2nhis4b' .
            'bW1FhsxajMHQQW39Aw0er1TarEI9pPILWXRdtDkmMyKn3HyXDFgdiQxKGEW+OtCJ/AUfz49IFgHidPwd29dI7dzfDe229' .
            'E6p1KD12YbeeGpBB0gpAs4XfsS9gtT5heLR5wRXNLrrKCqOMYBlvxcbd3VFIvTQO62NmXJ2HxuwSsJP5dP4iSIFGw3RiA' .
            'UwztXNEphOn9orL6kzXJOX9v9mUWy41vyIpJ0TuJiUtBB3gcb8+W1kD8p+Q9JK/0UzMud6S1RedtxWba7kLE75Z5uup5g' .
            'rQ2UMCy9Gbwd8K1zRGhbOsHrnZrffb3GEP5hI4U=';

        $this->assertEquals('SZ9394', $data['params']['pid']);
        $this->assertEquals($des, $data['params']['des']);
        $this->assertEquals('http://api.pc.sdpay.officenewline.org:11103/ToService.aspx', $data['post_url']);
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

        $sDPay = new SDPay();
        $sDPay->verifyOrderPayment([]);
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

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $encodeData = '<?xml version="1.0" encoding="utf-8" ?><message><cmd>6007</cmd><merchantid>SZ9394' .
            '</merchantid><order>201805290000013428</order><username>201805290000013428</username><money>1.00' .
            '</money><unit>1</unit><time>2018-05-30 12:04:01</time><call>server</call><result>1</result><rema' .
            'rk>201805290000013428</remark></message>';

        $data = $this->getData($encodeData);

        $options = [
            'pid' => 'SZ9394',
            'res' => $data,
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $sDPay->verifyOrderPayment([]);
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

        $encodeData = '<?xml version="1.0" encoding="utf-8" ?><message><cmd>6007</cmd><merchantid>SZ9394' .
            '</merchantid><order>201805290000013428</order><username>201805290000013428</username><money>1.00' .
            '</money><unit>1</unit><time>2018-05-30 12:04:01</time><call>server</call><result>1</result><rema' .
            'rk>201805290000013428</remark></message>806247402a032c8eb25f69ac3526ebc0';

        $data = $this->getData($encodeData);

        $options = [
            'pid' => 'SZ9394',
            'res' => $data,
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $sDPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeData = '<?xml version="1.0" encoding="utf-8" ?><message><cmd>6007</cmd><merchantid>SZ9394' .
            '</merchantid><order>201805290000013428</order><username>201805290000013428</username><money>1.00' .
            '</money><unit>1</unit><time>2018-05-30 12:04:01</time><call>server</call><result>0</result><rema' .
            'rk>201805290000013428</remark></message>85b0d5bfa69767ca35802fbf5d7cffdd';

        $data = $this->getData($encodeData);

        $options = [
            'pid' => 'SZ9394',
            'res' => $data,
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $sDPay->verifyOrderPayment([]);
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

        $encodeData = '<?xml version="1.0" encoding="utf-8" ?><message><cmd>6007</cmd><merchantid>SZ9394' .
            '</merchantid><order>201805290000013428</order><username>201805290000013428</username><money>1.00' .
            '</money><unit>1</unit><time>2018-05-30 12:04:01</time><call>server</call><result>1</result><rema' .
            'rk>201805290000013428</remark></message>8ba4aa093c697145feec4d9422946716';

        $data = $this->getData($encodeData);

        $options = [
            'pid' => 'SZ9394',
            'res' => $data,
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $entry = ['id' => '201503220000000555'];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $sDPay->verifyOrderPayment($entry);
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

        $encodeData = '<?xml version="1.0" encoding="utf-8" ?><message><cmd>6007</cmd><merchantid>SZ9394' .
            '</merchantid><order>201805290000013428</order><username>201805290000013428</username><money>1.00' .
            '</money><unit>1</unit><time>2018-05-30 12:04:01</time><call>server</call><result>1</result><rema' .
            'rk>201805290000013428</remark></message>8ba4aa093c697145feec4d9422946716';

        $data = $this->getData($encodeData);

        $options = [
            'pid' => 'SZ9394',
            'res' => $data,
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $entry = [
            'id' => '201805290000013428',
            'amount' => '15.00',
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $sDPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnResultSuccess()
    {
        $encodeData = '<?xml version="1.0" encoding="utf-8" ?><message><cmd>6007</cmd><merchantid>SZ9394' .
            '</merchantid><order>201805290000013428</order><username>201805290000013428</username><money>1.00' .
            '</money><unit>1</unit><time>2018-05-30 12:04:01</time><call>server</call><result>1</result><rema' .
            'rk>201805290000013428</remark></message>8ba4aa093c697145feec4d9422946716';

        $data = $this->getData($encodeData);

        $options = [
            'pid' => 'SZ9394',
            'res' => $data,
            'merchant_extra' => [
                'Key1' => 'uM6WMPWHh4w',
                'Key2' => '8jazYG/EtBA=',
            ],
        ];

        $entry = [
            'id' => '201805290000013428',
            'amount' => '1',
        ];

        $sDPay = new SDPay();
        $sDPay->setPrivateKey('test');
        $sDPay->setOptions($options);
        $sDPay->verifyOrderPayment($entry);

        $msg = '<?xml version="1.0" encoding="utf-8"?><message><cmd>60071</cmd><merchantid>SZ9394</merchantid>' .
            '<order>201805290000013428</order><username>201805290000013428</username><result>100</result></message>';

        $this->assertEquals($msg, $sDPay->getMsg());
    }

    /**
     * 組成支付平台回傳的data
     *
     * @param array $encodeParams
     * @return string
     */
    private function getData($encodeParams)
    {
        $extra['Key1'] = 'uM6WMPWHh4w=';
        $extra['Key2'] = '8jazYG/EtBA=';
        $key = base64_decode($extra['Key1']);
        $iv = base64_decode($extra['Key2']);

        $md5Str = md5($encodeParams . 'test');
        $tempStr = $encodeParams . $md5Str;

        $date = '2018-05-30 11:45:55';
        $md5hash = md5($date . '2018-05-30 11:45:55');
        $value = $tempStr . $md5hash;
        $value =  substr($value, 0, strlen($value) - 32);

        $encodeStr = openssl_encrypt($value, "des-ede3-cbc", $key, 0, $iv);

        return $encodeStr;
    }
}
