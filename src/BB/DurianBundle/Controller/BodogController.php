<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class BodogController extends Controller
{
    /**
     * 取得博狗交易資訊
     * 這邊只做route 設定，真正運作會透過 pineapple-platform-bodog 導向博狗做操作
     *
     * @Route("/bodog/transaction/{id}",
     *        name = "api_bodog_get_trans",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getTransactionAction()
    {
    }

    /**
     * 取得博狗明細資訊
     * 這邊只做route 設定，真正運作會透過 pineapple-platform-bodog 導向博狗做操作
     *
     * @Route("/bodog/entry/{entryId}",
     *        name = "api_get_bodog_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getEntryAction()
    {
    }

    /**
     * 取得博狗使用者明細資訊
     * 這邊只做route 設定，真正運作會透過 pineapple-platform-bodog 導向博狗做操作
     *
     * @Route("/user/{userId}/bodog/entry",
     *        name = "api_get_bodog_entries",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getEntriesAction()
    {
    }

    /**
     * 依照ref_id取得博狗明細
     * 這邊只做route 設定，真正運作會透過 pineapple-platform-bodog 導向博狗做操作
     *
     * @Route("/bodog/entries_by_ref_id",
     *        name = "api_get_bodog_entries_by_ref_id",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getEntriesByRefIdAction()
    {
    }
}
