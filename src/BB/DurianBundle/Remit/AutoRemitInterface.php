<?php

namespace BB\DurianBundle\Remit;

use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;

/**
 * 自動認款平台介面
 */
interface AutoRemitInterface
{
    /**
     * 設定自動認款帳號
     *
     * @param RemitAccount $remitAccount 自動認款帳號
     */
    public function setRemitAccount(RemitAccount $remitAccount);

    /**
     * 檢查自動認款 api 密鑰
     *
     * @param string $apiKey api 密鑰
     */
    public function checkApiKey($apiKey);

    /**
     * 啟用自動認款帳號時需檢查設定
     */
    public function checkAutoRemitAccount();

    /**
     * 啟用自動認款帳號時需檢查設定
     */
    public function checkNewAutoRemitAccount();

    /**
     * 提交自動認款訂單
     *
     * @param integer $orderNumber 訂單號
     * @param array $payData 訂單相關資料
     */
    public function submitAutoRemitEntry($orderNumber, $payData);

    /**
     * 取消自動認款訂單
     *
     * @param RemitEntry $remitEntry 自動認款訂單
     */
    public function cancelAutoRemitEntry(RemitEntry $remitEntry);

    /**
     * 檢查是否設定自動認款帳號
     */
    public function verifyRemitAccount();
}
