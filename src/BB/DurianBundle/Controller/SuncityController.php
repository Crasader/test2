<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class SuncityController extends Controller
{
    /**
     * 取得太陽城交易資訊
     * 這邊只做route 設定，真正運作會透過 pineapple-platform-suncity 導向太陽城做操作
     *
     * @Route("/suncity/transaction/{id}",
     *        name = "api_suncity_get_trans",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getTransactionAction()
    {
    }

    /**
     * 取得太陽城明細資訊
     * 這邊只做route 設定，真正運作會透過 pineapple-platform-suncity 導向太陽城做操作
     *
     * @Route("/suncity/entry/{entryId}",
     *        name = "api_get_suncity_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getEntryAction()
    {
    }

    /**
     * 取得太陽城使用者明細資訊
     * 這邊只做route 設定，真正運作會透過 pineapple-platform-suncity 導向太陽城做操作
     *
     * @Route("/user/{userId}/suncity/entry",
     *        name = "api_get_suncity_entries",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getEntriesAction()
    {
    }

    /**
     * 依照ref_id取得太陽城明細
     * 這邊只做route 設定，真正運作會透過 pineapple-platform-suncity 導向太陽城做操作
     *
     * @Route("/suncity/entries_by_ref_id",
     *        name = "api_get_suncity_entries_by_ref_id",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getEntriesByRefIdAction()
    {
    }
}
