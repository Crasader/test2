<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Entity\BitcoinWallet;
use BB\DurianBundle\Exception\PaymentConnectionException;

class BlockChain extends PaymentBase
{
    /**
     * 取得匯率
     *
     * @param string $currency 幣別
     * @return array
     */
    public function getExchange($currency)
    {
        $paymentIp = $this->container->getParameter('payment_ip');
        $validator = $this->container->get('durian.validator');

        $this->validateCurrency($currency);

        $requestData = [
            'currency' => $currency,
            'value' => 1,
        ];
        $curlParam = [
            'method' => 'GET',
            'uri' => '/tobtc',
            'ip' => [$paymentIp],
            'host' => 'payment.https.blockchain.info',
            'param' => http_build_query($requestData),
            'header' => [],
        ];

        $result = $this->curlRequestWithoutValidStatusCode($curlParam);

        if (!$validator->isFloat($result)) {
            throw new \RuntimeException($result, 150180202);
        }

        return $result;
    }

    /**
     * 驗證比特幣資料
     *
     * @param String $walletCode 比特幣錢包帳號
     * @param String $password 錢包密碼
     * @throws \RuntimeException
     */
    public function validateBitcoinWallet($walletCode, $password)
    {
        $paymentIp = $this->container->getParameter('payment_ip');

        $requestData = [
            'password' => $password,
        ];
        $resource = '/merchant/' . $walletCode . '/balance';

        $curlParam = [
            'method' => 'POST',
            'uri' => $resource,
            'ip' => [$paymentIp],
            'host' => 'payment.http.127.0.0.1',
            'param' => http_build_query($requestData),
            'header' => ['Port' => 3000],
            'timeout' => 30,
        ];

        $result = $this->curlRequestWithoutValidStatusCode($curlParam);
        $retArray = json_decode($result, true);

        if (!is_array($retArray)) {
            throw new \RuntimeException('Parse data error', 150180204);
        }

        if (!array_key_exists('balance', $retArray)) {
            if (array_key_exists('error', $retArray)) {
                throw new \RuntimeException($retArray['error'], 150180202);
            }

            throw new \RuntimeException('Parse data error', 150180204);
        }
    }

    /**
     * 新建入款帳戶與位址
     *
     * @param BitcoinWallet $bitcoinWallet 比特幣錢包
     * @param string $username 會員帳號
     * @return array
     */
    public function createAccountAddress(BitcoinWallet $bitcoinWallet, $username)
    {
        $xpub = $this->createAccount($bitcoinWallet, $username);
        $address = $this->getReceiveAddress($bitcoinWallet, $xpub);

        return [
            "account" => $xpub,
            "address" => $address,
        ];
    }

    /**
     * 新建入款帳戶
     *
     * @param BitcoinWallet $bitcoinWallet 比特幣錢包
     * @param string $username 會員帳號
     * @return string
     */
    private function createAccount(BitcoinWallet $bitcoinWallet, $username)
    {
        $paymentIp = $this->container->getParameter('payment_ip');

        $requestData = [
            'label' => $username,
            'password' => $bitcoinWallet->getPassword(),
            'api_code' => $bitcoinWallet->getApiCode(),
        ];

        if ($bitcoinWallet->getSecondPassword()) {
            $requestData['second_password'] = $bitcoinWallet->getSecondPassword();
        }
        $resource = '/merchant/' . $bitcoinWallet->getWalletCode() . '/accounts/create';

        $curlParam = [
            'method' => 'POST',
            'uri' => $resource,
            'ip' => [$paymentIp],
            'host' => 'payment.http.127.0.0.1',
            'param' => http_build_query($requestData),
            'header' => ['Port' => 3000],
            'timeout' => 30,
        ];

        $result = $this->curlRequestWithoutValidStatusCode($curlParam);
        $retArray = json_decode($result, true);

        if (!is_array($retArray)) {
            throw new \RuntimeException('Parse data error', 150180204);
        }

        if (!array_key_exists('xpub', $retArray)) {
            if (array_key_exists('error', $retArray)) {
                throw new \RuntimeException($retArray['error'], 150180202);
            }

            throw new \RuntimeException('Parse data error', 150180204);
        }

        return $retArray['xpub'];
    }

    /**
     * 取得帳戶入款位址
     *
     * @param BitcoinWallet $bitcoinWallet 比特幣錢包
     * @param string $xpub 帳戶
     * @return string
     */
    private function getReceiveAddress(BitcoinWallet $bitcoinWallet, $xpub)
    {
        $paymentIp = $this->container->getParameter('payment_ip');

        $requestData = [
            'password' => $bitcoinWallet->getPassword(),
            'api_code' => $bitcoinWallet->getApiCode(),
        ];
        $resource = '/merchant/' . $bitcoinWallet->getWalletCode() . '/accounts/';
        $resource .= $xpub . '/receiveAddress';

        $curlParam = [
            'method' => 'POST',
            'uri' => $resource,
            'ip' => [$paymentIp],
            'host' => 'payment.http.127.0.0.1',
            'param' => http_build_query($requestData),
            'header' => ['Port' => 3000],
            'timeout' => 30,
        ];

        $result = $this->curlRequestWithoutValidStatusCode($curlParam);
        $retArray = json_decode($result, true);

        if (!is_array($retArray)) {
            throw new \RuntimeException('Parse data error', 150180204);
        }

        if (!array_key_exists('address', $retArray)) {
            if (array_key_exists('error', $retArray)) {
                throw new \RuntimeException($retArray['error'], 150180202);
            }

            throw new \RuntimeException('Parse data error', 150180204);
        }

        return $retArray['address'];
    }

    /**
     * 轉帳
     *
     * @param BitcoinWallet $bitcoinWallet 比特幣錢包
     * @param string $xpub 出款公鑰
     * @param string $to 位址
     * @param float $amount 比特幣
     * @return string
     */
    public function makePayment(BitcoinWallet $bitcoinWallet, $xpub, $to, $amount)
    {
        $paymentIp = $this->container->getParameter('payment_ip');
        $accountIdex = $this->getAccountIndex($bitcoinWallet, $xpub);
        $satoshi = $amount * 100000000;

        $requestData = [
            'to' => $to,
            'amount' => $satoshi,
            'password' => $bitcoinWallet->getPassword(),
            'from' => $accountIdex,
            'api_code' => $bitcoinWallet->getApiCode(),
        ];

        if ($bitcoinWallet->getSecondPassword()) {
            $requestData['second_password'] = $bitcoinWallet->getSecondPassword();
        }

        if ($bitcoinWallet->getFeePerByte() > 0) {
            $requestData['fee_per_byte'] = $bitcoinWallet->getFeePerByte();
        }
        $resource = '/merchant/' . $bitcoinWallet->getWalletCode() . '/payment';

        $curlParam = [
            'method' => 'POST',
            'uri' => $resource,
            'ip' => [$paymentIp],
            'host' => 'payment.http.127.0.0.1',
            'param' => http_build_query($requestData),
            'header' => ['Port' => 3000],
            'timeout' => 30,
        ];

        $result = $this->curlRequestWithoutValidStatusCode($curlParam);
        $retArray = json_decode($result, true);

        if (!is_array($retArray)) {
            throw new \RuntimeException('Parse data error', 150180204);
        }

        if (!$retArray || !array_key_exists('txid', $retArray)) {
            if (array_key_exists('error', $retArray)) {
                throw new \RuntimeException($retArray['error'], 150180202);
            }

            throw new \RuntimeException('Parse data error', 150180204);
        }

        return $retArray['txid'];
    }

    /**
     * 取得帳戶索引值
     *
     * @param BitcoinWallet $bitcoinWallet 比特幣錢包
     * @param string $xpub 出款公鑰
     * @return integer
     */
    private function getAccountIndex(BitcoinWallet $bitcoinWallet, $xpub)
    {
        $paymentIp = $this->container->getParameter('payment_ip');
        $requestData = [
            'password' => $bitcoinWallet->getPassword(),
            'api_code' => $bitcoinWallet->getApiCode(),
        ];
        $resource = '/merchant/' . $bitcoinWallet->getWalletCode() . '/accounts/' . $xpub;

        $curlParam = [
            'method' => 'POST',
            'uri' => $resource,
            'ip' => [$paymentIp],
            'host' => 'payment.http.127.0.0.1',
            'param' => http_build_query($requestData),
            'header' => ['Port' => 3000],
            'timeout' => 30,
        ];

        try {
            $result = $this->curlRequestWithoutValidStatusCode($curlParam);
        } catch (PaymentConnectionException $e) {
            if ($e->getCode() === 180089) {
                throw new \RuntimeException("Invalid bitcoin payment xPub", 150180203);
            }

            throw $e;
        }
        $retArray = json_decode($result, true);

        if (!is_array($retArray)) {
            throw new \RuntimeException('Parse data error', 150180204);
        }

        if (!$retArray || !array_key_exists('index', $retArray)) {
            if (array_key_exists('error', $retArray)) {
                throw new \RuntimeException($retArray['error'], 150180202);
            }

            throw new \RuntimeException('Parse data error', 150180204);
        }

        return $retArray['index'];
    }

    /**
     * 驗證幣別是否支援
     *
     * @param string currency
     */
    private function validateCurrency($currency)
    {
        $bitcoinCurrency = [
            'USD', // 美金
            'AUD', // 澳幣
            'BRL', // 巴西雷亞爾
            'CAD', // 加拿大幣
            'CHF', // 瑞士法郎
            'CLP', // 智利披索
            'CNY', // 人民幣
            'DKK', // 丹麥克朗
            'EUR', // 歐元
            'GBP', // 英鎊
            'HKD', // 港幣
            'INR', // 印度盧比
            'ISK', // 冰島克朗
            'JPY', // 日幣
            'KRW', // 韓圜
            'NZD', // 紐西蘭元
            'PLN', // 波蘭茲羅提
            'RUB', // 俄羅斯盧布
            'SEK', // 瑞典克朗
            'SGD', // 新加坡幣
            'THB', // 泰銖
            'TWD', // 台幣
        ];

        //不支援的幣別丟例外
        if (!in_array($currency, $bitcoinCurrency)) {
            throw new \RuntimeException('Illegal currency', 150180205);
        }
    }
}
