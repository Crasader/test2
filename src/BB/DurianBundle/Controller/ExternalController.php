<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ExternalController extends Controller
{
    /**
     * 建立外接遊戲使用者
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external",
     *        name = "api_create_external_user",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     */
    public function createUserAction()
    {
    }

    /**
     * 取得外接遊戲使用者額度
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/balance",
     *        name = "api_get_external_balance",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getBalanceAction()
    {
    }

    /**
     * 外接遊戲轉移額度
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/transfer",
     *        name = "api_external_transfer",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function transferAction()
    {
    }

    /**
     * 取得外接遊戲單筆明細餘額
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/entry/balance",
     *        name = "api_get_external_entry_balance",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getEntryBalanceAction()
    {
    }

    /**
     * 外接遊戲人工存提
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/manual_transfer",
     *        name = "api_external_manual_transfer",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function manualTransferAction()
    {
    }

    /**
     * 回收所有外接遊戲額度
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/recycle_balance",
     *        name = "api_external_recycle_balance",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function recycleBalanceAction()
    {
    }

    /**
     * 取得外接遊戲額度遺失資料
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/external/lost_balance",
     *        name = "api_get_external_lost_balance",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getLostBalanceAction()
    {
    }

    /**
     * 更新外接遊戲額度遺失資料
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/transaction/{transId}/external/lost_balance",
     *        name = "api_update_external_lost_balance",
     *        requirements = {"transId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function updateLostBalanceAction()
    {
    }

    /**
     * 取得外接遊戲使用者密碼
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/password",
     *        name = "api_get_external_password",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getPasswordAction()
    {
    }

    /**
     * 取得外接遊戲API業主外接交易結果
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/owner/external/transfer/status",
     *        name = "api_get_external_owner_transfer_status",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getApiOwnerTransferStatusAction()
    {
    }

    /**
     * 取得外接遊戲轉帳狀態
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/transfer/status",
     *        name = "api_get_external_transfer_status",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getTransferStatusAction()
    {
    }

    /**
     * 取得/反查外接帳號
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/external/relative_name",
     *        name = "api_get_relative_name",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getRelativeNameAction()
    {
    }

    /**
     * 檢查成功交易資料
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/transaction/{transId}/external/transfer_record",
     *        name = "api_external_transfer_record",
     *        requirements = {"transId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function checkTransferRecordAction()
    {
    }

    /**
     * 重設外接遊戲使用者密碼
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/reset_password",
     *        name = "api_external_reset_password",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function resetPasswordAction()
    {
    }

    /**
     * 取得外接遊戲餘額統計
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/external/balance_stat",
     *        name = "api_get_balance_stat",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getBalanceStatAction()
    {
    }

    /**
     * 取得外接遊戲列表
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/external/game_list",
     *        name = "api_get_game_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getGameListAction()
    {
    }

    /**
     * 測試連線
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/external/connection_test",
     *        name = "api_connection_test",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function connectionTestAction()
    {
    }

    /**
     * 取得上層餘額
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/external/upper_balance",
     *        name = "api_get_upper_balance",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getUpperBalanceAction()
    {
    }

    /**
     * 回收所有外接遊戲額度(非同步執行)
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/external/recycle_balance_async",
     *        name = "api_external_recycle_balance_async",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function recycleBalanceAsyncAction()
    {
    }
}
