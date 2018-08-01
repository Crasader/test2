<?php

namespace BB\DurianBundle\Maintain;

use BB\DurianBundle\Entity\Maintain;

class DomainMsg
{
    /**
     * 全部遊戲繁簡英名稱
     *
     * @var array
     */
    private $games = [
        1 => ['TW' => 'BB體育', 'CN' => 'BB体育', 'EN' => 'BB SPORTS'],
        2 => ['TW' => 'KENO', 'CN' => 'KENO', 'EN' => 'KENO'],
        3 => ['TW' => 'BB視訊', 'CN' => 'BB视讯', 'EN' => 'BB LIVE CASINO'],
        4 => ['TW' => '體育投注', 'CN' => '体育投注', 'EN' => 'SPORT BETTING'],
        5 => ['TW' => 'BB電子', 'CN' => 'BB电子', 'EN' => 'BB CASINO'],
        12 => ['TW' => 'BB彩票', 'CN' => 'BB彩票', 'EN' => 'BB LOTTERY'],
        19 => ['TW' => 'AG視訊', 'CN' => 'AG视讯', 'EN' => 'AG LIVE CASINO'],
        20 => ['TW' => 'PT電子', 'CN' => 'PT电子', 'EN' => 'PT CASINO'],
        21 => ['TW' => '樂透', 'CN' => '乐透', 'EN' => 'LOTTERY'],
        22 => ['TW' => '歐博視訊', 'CN' => '欧博视讯', 'EN' => 'ALLBET LIVE CASINO'],
        23 => ['TW' => 'MG電子', 'CN' => 'MG电子', 'EN' => 'MG CASINO'],
        24 => ['TW' => 'OG視訊', 'CN' => 'OG视讯', 'EN' => 'OG LIVE CASINO'],
        27 => ['TW' => 'GD視訊', 'CN' => 'GD视讯', 'EN' => 'GD LIVE CASINO'],
        28 => ['TW' => 'GNS電子', 'CN' => 'GNS电子', 'EN' => 'GNS CASINO'],
        29 => ['TW' => 'ISB電子', 'CN' => 'ISB電子', 'EN' => 'ISB CASINO'],
        30 => ['TW' => 'BB捕魚達人', 'CN' => 'BB捕鱼达人', 'EN' => 'BB FISH HUNTER'],
        31 => ['TW' => 'NEW BB體育', 'CN' => 'NEW BB体育', 'EN' => 'NEW BB SPORTS'],
        32 => ['TW' => 'HB電子', 'CN' => 'HB电子', 'EN' => 'HB CASINO'],
        33 => ['TW' => '888捕魚', 'CN' => '888捕鱼', 'EN' => '888 FISHING'],
        34 => ['TW' => 'BB一元奪寶', 'CN' => 'BB一元夺宝', 'EN' => 'BB TREASURE HUNTER'],
        35 => ['TW' => '賭神廳', 'CN' => '赌神厅', 'EN' => 'GOD LIVE'],
        36 => ['TW' => 'BG視訊', 'CN' => 'BG视讯', 'EN' => 'BG LIVE'],
        37 => ['TW' => 'PP電子', 'CN' => 'PP电子', 'EN' => 'PP CASINO'],
        38 => ['TW' => 'BB捕魚大師', 'CN' => 'BB捕鱼大师', 'EN' => 'BB FISHING MASTER'],
        39 => ['TW' => 'JDB電子', 'CN' => 'JDB电子', 'EN' => 'JDB CASINO'],
        40 => ['TW' => 'AG電子', 'CN' => 'AG电子', 'EN' => 'AG CASINO'],
        41 => ['TW' => 'MW電子', 'CN' => 'MW电子', 'EN' => 'MW CASINO'],
        42 => ['TW' => 'RT電子', 'CN' => 'RT电子', 'EN' => 'RT CASINO'],
        43 => ['TW' => 'IN體育', 'CN' => 'IN体育', 'EN' => 'IN Sports'],
        44 => ['TW' => 'SG電子', 'CN' => 'SG电子', 'EN' => 'SG CASINO'],
        45 => ['TW' => 'VR彩票', 'CN' => 'VR彩票', 'EN' => 'VR LOTTERY'],
        46 => ['TW' => 'SW電子', 'CN' => 'SW電子', 'EN' => 'SW CASINO'],
        47 => ['TW' => 'EVO視訊', 'CN' => 'EVO视讯', 'EN' => 'EVO LIVE'],
        48 => ['TW' => 'BNG電子', 'CN' => 'BNG电子', 'EN' => 'BNG CASINO'],
        49 => ['TW' => '開元 棋牌', 'CN' => '开元 棋牌', 'EN' => 'KY GAMING CARD GAME'],
        50 => ['TW' => 'WM電子', 'CN' => 'WM电子', 'EN' => 'WM CASINO']
    ];

    /**
     * 公司遊戲
     *
     * @var array
     */
    private $companyGame = [1, 3, 5, 12, 31, 34];

    /**
     * 外接遊戲
     *
     * @var array
     */
    private $apiGame = [4, 19, 20, 22, 23, 24, 27, 28, 29, 32, 33, 36, 37, 39, 40, 41, 42, 44, 45, 47, 48, 49, 50];

    /**
     * 特例遊戲(遊戲為公司遊戲但照外接遊戲邏輯)
     *
     * @var array
     */
    private $specialGame = [30, 35, 38, 43, 46];

    /**
     * 取得掛上維護繁體中文標題
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getStartMaintainTWTitle($maintain)
    {
        $code = $maintain->getCode();
        $beginAt = $maintain->getBeginAt();
        $endAt = $maintain->getEndAt();
        $beginTime = date_format($beginAt, 'm/d H:i');
        $endTime = date_format($endAt, 'm/d H:i');

        return "【{$this->games[$code]['TW']}遊戲 臨時維護通知】(北京時間 $beginTime ~ $endTime)";
    }

    /**
     * 取得掛上維護繁體中文內容
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getStartMaintainTWContent($maintain)
    {
        $code = $maintain->getCode();
        $beginAt = $maintain->getBeginAt();
        $endAt = $maintain->getEndAt();
        $beginTime = date_format($beginAt, 'm/d H:i');
        $endTime = date_format($endAt, 'm/d H:i');

        $content = "●時間: 北京時間 $beginTime ~ $endTime\n";

        if (in_array($code, $this->companyGame)) {
            $content .= "●無法修改詳細設定\n" .
                        "●無法新增代理(新增帳號&刪除功能無法使用)\n" .
                        "●維護中的遊戲會顯示維護訊息，暫時無法顯示實際有效投注\n";
        }

        if (in_array($code, $this->apiGame)) {
            $content .= "●維護中的遊戲會顯示維護訊息，無法轉移額度\n" .
                        "●暫時無法稽核出款有效投注\n";
        }

        if (in_array($code, $this->specialGame)) {
            $content .= "●維護中的遊戲會顯示維護訊息\n" .
                        "●暫時無法稽核出款有效投注\n";
        }

        return $content .= 'BBIN 通知您';
    }

    /**
     * 取得掛上維護簡體中文標題
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getStartMaintainCNTitle($maintain)
    {
        $code = $maintain->getCode();
        $beginAt = $maintain->getBeginAt();
        $endAt = $maintain->getEndAt();
        $beginTime = date_format($beginAt, 'm/d H:i');
        $endTime = date_format($endAt, 'm/d H:i');

        return "【{$this->games[$code]['CN']}游戏 临时维护通知】(北京时间 $beginTime ~ $endTime)";
    }

    /**
     * 取得掛上維護簡體中文內容
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getStartMaintainCNContent($maintain)
    {
        $code = $maintain->getCode();
        $beginAt = $maintain->getBeginAt();
        $endAt = $maintain->getEndAt();
        $beginTime = date_format($beginAt, 'm/d H:i');
        $endTime = date_format($endAt, 'm/d H:i');

        $content = "●时间: 北京时间 $beginTime ~ $endTime\n";

        if (in_array($code, $this->companyGame)) {
            $content .= "●无法修改详细设定\n" .
                        "●无法新增代理(新增帐号&删除功能无法使用)\n" .
                        "●维护中的游戏会显示维护讯息，暂时无法显示实际有效投注\n";
        }

        if (in_array($code, $this->apiGame)) {
            $content .= "●维护中的游戏会显示维护讯息，无法转移额度\n" .
                        "●暂时无法稽核出款有效投注\n";
        }

        if (in_array($code, $this->specialGame)) {
            $content .= "●维护中的游戏会显示维护讯息\n" .
                        "●暂时无法稽核出款有效投注\n";
        }

        return $content .= 'BBIN 通知您';
    }

    /**
     * 取得掛上維護英文標題
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getStartMaintainENTitle($maintain)
    {
        $code = $maintain->getCode();
        $beginAt = $maintain->getBeginAt();
        $endAt = $maintain->getEndAt();
        $beginTime = date_format($beginAt, 'm/d H:i');
        $endTime = date_format($endAt, 'm/d H:i');

        return "【{$this->games[$code]['EN']} Temporary maintenance notice】($beginTime ~ $endTime)GMT+8";
    }

    /**
     * 取得掛上維護英文內容
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getStartMaintainENContent($maintain)
    {
        $code = $maintain->getCode();
        $beginAt = $maintain->getBeginAt();
        $endAt = $maintain->getEndAt();
        $beginTime = date_format($beginAt, 'm/d H:i');
        $endTime = date_format($endAt, 'm/d H:i');

        $content = "●TIME: $beginTime ~ $endTime (GMT+8)\n";

        if (in_array($code, $this->companyGame)) {
            $content .= "●Can't modify detail settings.\n" .
                        "●Can't add agent.(Can't Add Username & Delete)\n" .
                        "●The maintenance games will show maintenance notice and can't show valid bet in temporarily.\n";
        }

        if (in_array($code, $this->apiGame)) {
            $content .= "●The maintenance games will show maintenance notice and can't transform credit.\n" .
                        "●Withdraw valid bet is temporarily can't audit.\n";
        }

        if (in_array($code, $this->specialGame)) {
            $content .= "●The maintenance games will show maintenance notice.\n" .
                        "●Withdraw valid bet is temporarily can't audit.\n";
        }

        return $content .= 'BBIN NOTICE';
    }

    /**
     * 取得撤下維護繁體中文標題
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getEndMaintainTWTitle($maintain)
    {
        $code = $maintain->getCode();

        return "【{$this->games[$code]['TW']}遊戲 臨時維護完成通知】";
    }

    /**
     * 取得撤下維護繁體中文內容
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getEndMaintainTWContent($maintain)
    {
        $code = $maintain->getCode();

        return "{$this->games[$code]['TW']}遊戲 臨時維護 完成通知\n" .
               "請玩家重整後，即可進入遊戲\n" .
               'BBIN 通知您';
    }

    /**
     * 取得撤下維護簡體中文標題
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getEndMaintainCNTitle($maintain)
    {
        $code = $maintain->getCode();

        return "【{$this->games[$code]['CN']}游戏 临时维护完成通知】";
    }

    /**
     * 取得撤下維護簡體中文內容
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getEndMaintainCNContent($maintain)
    {
        $code = $maintain->getCode();

        return "{$this->games[$code]['CN']}游戏 临时维护 完成通知\n" .
               "请玩家重整后，即可进入游戏\n" .
               'BBIN 通知您';
    }

    /**
     * 取得撤下維護英文標題
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getEndMaintainENTitle($maintain)
    {
        $code = $maintain->getCode();

        return "【{$this->games[$code]['EN']} Temporary maintenance finish notice】";
    }

    /**
     * 取得撤下維護英文內容
     *
     * @param Maintain $maintain
     * @return string
     */
    public function getEndMaintainENContent($maintain)
    {
        $code = $maintain->getCode();

        return "The {$this->games[$code]['EN']} Temporary maintenance finish notice.\n" .
               "Please refresh web that can enter games.\n" .
               'BBIN NOTICE';
    }
}
