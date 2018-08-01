<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class IpBlockerController extends Controller
{
    /**
     * 檢查IP是否被阻擋
     * 這邊只做route 設定，真正運作會透過 pineapple-ip-blocker 做操作
     *
     * @Route("/ip_blocker/check/ip",
     *        name = "api_check_ip",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function checkIpAction()
    {
    }

    /**
     * 刪除廳白名單API
     * 這邊只做route 設定，真正運作會透過 pineapple-ip-blocker 做操作
     *
     * @Route("/ip_blocker/white_list/domain/{id}/ip",
     *        name = "api_delete_domain_whitelist_ip",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     */
    public function deleteDomainWhitelistIpAction()
    {
    }

    /**
     * 根據ips取得國家資訊API
     * 這邊只做route 設定，真正運作會透過 pineapple-ip-blocker 做操作
     *
     * @Route("/ip_blocker/geoip/countries_by_ip",
     *        name = "api_get_countries_by_ip",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getCountriesByIpAction()
    {
    }
}
