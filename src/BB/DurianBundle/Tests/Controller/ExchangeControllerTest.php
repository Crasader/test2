<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\ExchangeController;

class ExchangeControllerTest extends ControllerTest
{
    /**
     * 測試新增匯率資料時幣別未帶值
     */
    public function testNewExchangeWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            470001
        );

        $params = ['currency' => ''];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時幣別帶入空格
     */
    public function testNewExchangeWithSpaceCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            470001
        );

        $params = ['currency' => ' '];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時幣別帶非數字
     */
    public function testNewExchangeWithNotNumericCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            470001
        );

        $params = ['currency' => 'OTZ'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時匯率未帶值
     */
    public function testNewExchangeWithEmptyRate()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal buy specified',
            470002
        );

        $params = [
            'currency' => 'CNY',
            'buy'      => ''
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時匯率帶空格
     */
    public function testNewExchangeWithSpaceRate()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal sell specified',
            470006
        );

        $params = [
            'currency' => 'CNY',
            'buy'      => '1',
            'sell'     => ' '
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時匯率帶非數字
     */
    public function testNewExchangeWithNotNumericRate()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal basic specified',
            470003
        );

        $params = [
            'currency' => 'CNY',
            'buy'      => '1',
            'sell'     => '0.311',
            'basic'    => 'CNY'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時匯率帶負數
     */
    public function testNewExchangeWithNegativeRate()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal basic specified',
            470003
        );

        $params = [
            'currency' => 'CNY',
            'buy'      => '1',
            'sell'     => '0.311',
            'basic'    => '-0.214'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時沒帶生效時間
     */
    public function testNewExchangeWithoutActiveAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No active_at specified',
            470004
        );

        $params = [
            'currency'  => 'CNY',
            'buy'       => '0.690000',
            'sell'      => '0.710000',
            'basic'     => '0.700001'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時生效時間未帶值
     */
    public function testNewExchangeWithEmptyActiveAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No active_at specified',
            470004
        );

        $params = [
            'currency'  => 'CNY',
            'buy'       => '0.690000',
            'sell'      => '0.710000',
            'basic'     => '0.700001',
            'active_at' => ''
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增匯率資料時生效時間帶小於現在時間
     */
    public function testNewExchangeWithPassedActiveAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal active_at specified',
            470007
        );

        $params = [
            'currency'  => 'CNY',
            'buy'       => '0.690000',
            'sell'      => '0.710000',
            'basic'     => '0.700001',
            'active_at' => '2012-02-12T16:00:00+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試回傳匯率列表，不帶currency
     */
    public function testListExchangeWhenCurrencyNotSupport()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            470001
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->listAction($request, '');
    }

    /**
     * 依照幣別時間回傳匯率，沒帶currency
     */
    public function testGetNonExistExchange()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            470001
        );

        $params = ['active_at' => '2012-02-12T16:00:00+0800'];

        $container = static::$kernel->getContainer();
        $request = new Request($params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->getByCurrencyAction($request, '');
    }

    /**
     * 測試同幣別匯率轉換
     */
    public function testExchangeConvertSameCurrency()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The same currency can not convert',
            470009
        );

        $params = [
            'amount'    => '10000',
            'from'      => 'TWD',
            'to'        => 'TWD',
            'active_at' => '2012-02-12T16:00:00+0800',
            'preview'   => 1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->convertAction($request);
    }

    /**
     * 測試匯率轉換時帶入不合法Amount
     */
    public function testExchangeConvertByIllegalAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount must be numeric',
            470008
        );

        $params = ['amount' => 'test'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new ExchangeController();
        $controller->setContainer($container);

        $controller->convertAction($request);
    }
}
