<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 使用者登錄紀錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\LoginLogRepository")
 * @ORM\Table(
 *      name = "login_log",
 *      indexes = {
 *          @ORM\Index(name = "idx_login_user_id", columns = {"user_id"}),
 *          @ORM\Index(name = "idx_login_at_ip", columns = {"at", "ip"}),
 *          @ORM\Index(name = "idx_login_ip", columns = {"ip"}),
 *          @ORM\Index(name = "idx_login_username", columns = {"username"})
 *      }
 * )
 */
class LoginLog
{
    /**
     * 登入成功
     */
    const RESULT_SUCCESS = 1;

    /**
     * 個人資料錯誤
     */
    const RESULT_USER_ERROR = 2;

    /**
     * 密碼錯誤
     */
    const RESULT_PASSWORD_WRONG = 3;

    /**
     * 帳號已停用
     */
    const RESULT_USER_IS_DISABLE = 4;

    /**
     * 帳號已凍結
     */
    const RESULT_USER_IS_BLOCK = 5;

    /**
     * 帳號已刪除
     */
    const RESULT_USER_IS_REMOVE = 6;

    /**
     * 帳號錯誤
     */
    const RESULT_USERNAME_WRONG = 7;

    /**
     * 驗證碼錯誤
     */
    const RESULT_CAPTCHA_WRONG = 8;

    /**
     * 密碼錯誤並凍結使用者
     */
    const RESULT_PASSWORD_WRONG_AND_BLOCK = 9;

    /**
     * oauth帳號已與使用者帳號綁定
     */
    const RESULT_USER_HAS_OAUTH_BINDING = 10;

    /**
     * 登入密碼停用，無法以密碼登入
     */
    const RESULT_USER_DISABLED_PASSWORD = 12;

    /**
     * 登入IP已被黑名單阻擋,無法登入
     */
    const RESULT_IP_IS_BLOCKED_BY_BLACKLIST = 13;

    /**
     * 時間內重複登入
     */
    const RESULT_DUPLICATED_WITHIN_TIME = 14;

    /**
     * 登入為不同體系
     */
    const RESULT_NOT_IN_HIERARCHY = 15;

    /**
     * 層級錯誤
     */
    const RESULT_LEVEL_WRONG = 16;

    /**
     * 登入成功需要導向網址 (此代碼僅供內部使用)
     */
    const RESULT_SUCCESS_AND_REDIRECT = 17;

    /**
     * 裝置未綁定此帳號，不支援手勢登入
     */
    const RESULT_SLIDEPASSWORD_NOT_FOUND = 18;

    /**
     * 裝置已停用手勢登入功能
     */
    const RESULT_DEVICE_DISABLED = 19;

    /**
     * 手勢密碼錯誤
     */
    const RESULT_SLIDEPASSWORD_WRONG = 20;

    /**
     * 手勢密碼錯誤三次，凍結此手勢登入綁定
     */
    const RESULT_SLIDEPASSWORD_WRONG_AND_BLOCK = 21;

    /**
     * 手勢密碼已凍結
     */
    const RESULT_SLIDEPASSWORD_BLOCKED = 22;

    /**
     * 廳主OTP錯誤
     */
    const RESULT_OTP_WRONG = 23;

    /**
     * 裝置未驗證，無法以手勢密碼登入
     */
    const RESULT_DEVICE_NOT_VERIFIED = 24;

    /**
     * 驗證碼錯誤並凍結此帳號 (此代碼僅供GM管理系統使用)
     */
    const RESULT_CAPTCHA_WRONG_AND_BLOCK = 25;

    /**
     * 登入IP已被IP封鎖列表阻擋,無法登入
     */
    const RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST = 26;

    /**
     * 從控端登入
     */
    const LOGIN_FROM_CONTROL = 1;

    /**
     * 從管端登入
     */
    const LOGIN_FROM_ADMIN = 2;

    /**
     * 從客端登入
     */
    const LOGIN_FROM_CLIENT = 3;

    /**
     * 作業系統對應表
     *
     * @var array
     */
    public static $clientOsMap = [
        1 => 'Windows',
        2 => 'OS X',
        3 => 'Linux',
        4 => 'AndroidOS',
        5 => 'AndroidOS Tablet',
        6 => 'iOS',
        7 => 'iOS Tablet',
        8 => 'Windows Phone',
        9 => 'Windows Tablet',
        10 => 'BlackBerryOS',
        11 => 'BlackBerryOS Tablet',
        12 => 'other'
    ];

    /**
     * 瀏覽器對應表
     *
     * @var array
     */
    public static $clientBrowserMap = [
        1 => 'IE',
        2 => 'Chrome',
        3 => 'Firefox',
        4 => 'Safari',
        5 => 'Opera',
        6 => 'BB瀏覽器',
        7 => '寰宇瀏覽器',
        8 => 'other',
        9 => 'QQ',
        10 => 'UC',
        11 => 'Oppo'
    ];

    /**
     * 登入來源對應表
     *
     * @var array
     */
    public static $ingressMap = [
        1 => '網頁版',
        2 => '手機網頁版',
        3 => 'api 站',
        4 => 'mobile app',
        5 => '下載版',
        6 => 'BB瀏覽器',
        7 => '寰宇瀏覽器'
    ];

    /**
     * 語系對應表
     *
     * @var array
     */
    public static $languageMap = [
        1 => 'en', // 英文
        2 => 'zh-tw', // 繁中
        3 => 'zh-cn', // 簡中
        4 => 'th', // 泰文
        5 => 'ja', // 日文
        6 => 'ko', // 韓文
        7 => 'vi', // 越南文
        8 => 'id', // 印尼
        9 => 'ug', // 維吾爾
        10 => 'es', // 西班牙
        11 => 'lo', // 寮國
        12 => 'km', // 柬埔寨
        13 => 'other'
    ];

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 記錄使用者的id
     *
     * @var integer
     * @ORM\Column(name = "user_id", type = "integer", nullable = true)
     */
    private $userId;

    /**
     * 使用者帳號
     *
     * @var string
     *
     * @ORM\Column(name = "username", type = "string", length = 30)
     */
    private $username;

    /**
     * 使用者層級
     *
     * @var integer
     *
     * @ORM\Column(name = "role", type = "smallint", nullable = true)
     */
    private $role;

    /**
     * 是否子帳號
     *
     * @var boolean
     *
     * @ORM\Column(name = "sub", type = "boolean", nullable = true)
     */
    private $sub;

    /**
     * 登入來源:控(1) / 管(2) / 客(3) 端
     *
     * @var integer
     *
     * @ORM\Column(name = "entrance", type = "smallint", options = {"unsigned" = true}, nullable = true)
     */
    private $entrance;

    /**
     * 輸入的domain
     *
     * @var integer
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 登入ip
     *
     * @var integer
     * @ORM\Column(name = "ip", type = "integer", options = {"unsigned" = true})
     */
    private $ip;

    /**
     * 登入ip，以ipv6記錄
     *
     * @var string
     * @ORM\Column(name = "ipv6", type = "string", length = 40)
     */
    private $ipv6;

    /**
     * 會員入口網址
     *
     * @var string
     *
     * @ORM\Column(name = "host", type = "string", length = 255)
     */
    private $host;

    /**
     * 登入時間
     *
     * @var \DateTime
     * @ORM\Column(name = "at", type = "datetime")
     */
    private $at;

    /**
     * 登入結果
     *
     * @var int
     * @ORM\Column(name = "result", type = "smallint", options = {"unsigned" = true})
     */
    private $result;

    /**
     * session編號
     *
     * @var string
     *
     * @ORM\Column(name = "session_id", type = "string", length = 50, nullable = true)
     */
    private $sessionId;

    /**
     * 登入語系
     *
     * @var string
     * @ORM\Column(name = "language", type = "string", length = 20)
     */
    private $language;

    /**
     * 客戶端作業系統
     *
     * @var string
     * @ORM\Column(name = "client_os", type = "string", length = 30)
     */
    private $clientOs;

    /**
     * 客戶端瀏覽器
     *
     * @var string
     * @ORM\Column(name = "client_browser", type = "string", length = 30)
     */
    private $clientBrowser;

    /**
     * 登入來源(公司入口)
     *
     * @var int
     * @ORM\Column(name = "ingress", type = "smallint", options = {"unsigned" = true}, nullable = true)
     */
    private $ingress;

    /**
     * proxy ip 第1組
     *
     * @var integer
     * @ORM\Column(name = "proxy1", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $proxy1;

    /**
     * proxy ip 第2組
     *
     * @var integer
     * @ORM\Column(name = "proxy2", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $proxy2;

    /**
     * proxy ip 第3組
     *
     * @var integer
     * @ORM\Column(name = "proxy3", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $proxy3;

    /**
     * proxy ip 第4組
     *
     * @var integer
     * @ORM\Column(name = "proxy4", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $proxy4;

    /**
     * IP來源(國家)
     *
     * @var string
     * @ORM\Column(name = "country", type = "string", length = 40, nullable = true)
     */
    private $country;

    /**
     * IP來源(城市)
     *
     * @var string
     * @ORM\Column(name = "city", type = "string", length = 40, nullable = true)
     */
    private $city;

    /**
     * 是否otp登入
     *
     * @var boolean
     *
     * @ORM\Column(name = "is_otp", type = "boolean")
     */
    private $isOtp;

    /**
     * 是否手勢密碼
     *
     * @var boolean
     *
     * @ORM\Column(name = "is_slide", type = "boolean")
     */
    private $isSlide;

    /**
     * 是否測試帳號
     *
     * @var boolean
     *
     * @ORM\Column(name = "test", type = "boolean")
     */
    private $test;

    /**
     * @param string  $ip     登入ip
     * @param integer $domain 廳
     * @param integer $result 登入結果
     */
    public function __construct($ip, $domain, $result)
    {
        $this->ip = ip2long($ip);
        $this->domain = $domain;
        $this->result = $result;
        $this->host = '';
        $this->language = '';
        $this->username = '';
        $this->role = 0;
        $this->ipv6 = '';
        $this->clientOs = '';
        $this->clientBrowser = '';
        $this->isOtp = false;
        $this->isSlide = false;
        $this->test = false;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定使用者id
     *
     * @param integer $id 使用者id
     * @return LoginLog
     */
    public function setUserId($id)
    {
        $this->userId = $id;

        return $this;
    }

    /**
     * 回傳使用者id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳登入ip
     *
     * @return string
     */
    public function getIP()
    {
        return long2ip($this->ip);
    }

    /**
     * 回傳登入站別
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳登入結果
     *
     * @return integer
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * 回傳登入時間
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 設定登入時間
     *
     * @param \DateTime $at 登入時間
     * @return LoginLog
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 設定sessionId
     *
     * @param string $sessionId session
     * @return LoginLog
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * 回傳sessionId
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * 設定使用者層級
     *
     * @param integer $role 使用者層級
     * @return LoginLog
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * 回傳使用者層級
     *
     * @return integer
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * 設定登入來源:控(1) / 管(2) / 客(3) 端
     *
     * @param integer $entrance 登入來源
     * @return LoginLog
     */
    public function setEntrance($entrance)
    {
        $this->entrance = $entrance;

        return $this;
    }

    /**
     * 回傳登入來源:控(1) / 管(2) / 客(3) 端
     *
     * @return integer
     */
    public function getEntrance()
    {
        return $this->entrance;
    }

    /**
     * 設定會員入口網址
     *
     * @param string $host 會員入口網址
     * @return LoginLog
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * 回傳會員入口網址
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * 設定會員帳號
     *
     * @param string $username 會員帳號
     * @return LoginLog
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * 回傳會員帳號
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * 設定會員語系
     *
     * @param string $language 會員語系
     * @return LoginLog
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * 回傳會員語系
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * 設定Ipv6
     *
     * @param string $ipv6 Ipv6
     * @return LoginLog
     */
    public function setIpv6($ipv6)
    {
        $this->ipv6 = $ipv6;

        return $this;
    }

    /**
     * 回傳Ipv6
     *
     * @return string
     */
    public function getIpv6()
    {
        return $this->ipv6;
    }

    /**
     * 設定客戶端作業系統
     *
     * @param string $clientOs 客戶端作業系統
     * @return LoginLog
     */
    public function setClientOs($clientOs)
    {
        $this->clientOs = $clientOs;

        return $this;
    }

    /**
     * 回傳客戶端作業系統
     *
     * @return string
     */
    public function getClientOs()
    {
        return $this->clientOs;
    }

    /**
     * 設定客戶端瀏覽器
     *
     * @param string $clientBrowser 客戶端瀏覽器
     * @return LoginLog
     */
    public function setClientBrowser($clientBrowser)
    {
        $this->clientBrowser = $clientBrowser;

        return $this;
    }

    /**
     * 回傳客戶端瀏覽器
     *
     * @return string
     */
    public function getClientBrowser()
    {
        return $this->clientBrowser;
    }

    /**
     * 設定登入來源
     *
     * @param integer $ingress 登入來源
     * @return LoginLog
     */
    public function setIngress($ingress)
    {
        $this->ingress = $ingress;

        return $this;
    }

    /**
     * 回傳登入來源
     *
     * @return integer
     */
    public function getIngress()
    {
        return $this->ingress;
    }

    /**
     * 設定 proxy ip 第1組資料
     *
     * @param string $proxy1 proxy ip 第1組
     * @return LoginLog
     */
    public function setProxy1($proxy1)
    {
        if ($proxy1) {
            $this->proxy1 = ip2long($proxy1);
        }

        return $this;
    }

    /**
     * 回傳 proxy ip 第1組資料(ip格式)
     *
     * @return string
     */
    public function getProxy1()
    {
        if ($this->proxy1) {
            return long2ip($this->proxy1);
        }

        return $this->proxy1;
    }

    /**
     * 設定 proxy ip 第2組資料
     *
     * @param string $proxy2 proxy ip 第2組
     * @return LoginLog
     */
    public function setProxy2($proxy2)
    {
        if ($proxy2) {
            $this->proxy2 = ip2long($proxy2);
        }

        return $this;
    }

    /**
     * 回傳 proxy ip 第2組資料(ip格式)
     *
     * @return string
     */
    public function getProxy2()
    {
        if ($this->proxy2) {
            return long2ip($this->proxy2);
        }

        return $this->proxy2;
    }

    /**
     * 設定 proxy ip 第3組資料
     *
     * @param string $proxy3 proxy ip 第3組
     * @return LoginLog
     */
    public function setProxy3($proxy3)
    {
        if ($proxy3) {
            $this->proxy3 = ip2long($proxy3);
        }

        return $this;
    }

    /**
     * 回傳 proxy ip 第3組資料(ip格式)
     *
     * @return string
     */
    public function getProxy3()
    {
        if ($this->proxy3) {
            return long2ip($this->proxy3);
        }

        return $this->proxy3;
    }

    /**
     * 設定 proxy ip 第4組資料
     *
     * @param string $proxy4 proxy ip 第4組
     * @return LoginLog
     */
    public function setProxy4($proxy4)
    {
        if ($proxy4) {
            $this->proxy4 = ip2long($proxy4);
        }

        return $this;
    }

    /**
     * 回傳 proxy ip 第4組資料(ip格式)
     *
     * @return string
     */
    public function getProxy4()
    {
        if ($this->proxy4) {
            return long2ip($this->proxy4);
        }

        return $this->proxy4;
    }

    /**
     * 設定IP來源國家
     *
     * @param string $country IP來源國家
     * @return LoginLog
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * 回傳IP來源國家
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * 設定IP來源城市
     *
     * @param string $city IP來源城市
     * @return LoginLog
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * 回傳IP來源城市
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * 是否子帳號
     *
     * @return boolean
     */
    public function isSub()
    {
        return (bool) $this->sub;
    }

    /**
     * 設定是否為子帳號
     *
     * @param boolean $bool 是否為子帳號
     * @return LoginLog
     */
    public function setSub($bool)
    {
        $this->sub = $bool;

        return $this;
    }

    /**
     * 是否Otp登入
     *
     * @return boolean
     */
    public function isOtp()
    {
        return (bool) $this->isOtp;
    }

    /**
     * 設定是否Otp登入
     *
     * @param boolean $bool 是否為Otp登入
     * @return LoginLog
     */
    public function setOtp($bool)
    {
        $this->isOtp = $bool;

        return $this;
    }

    /**
     * 是否手勢密碼
     *
     * @return boolean
     */
    public function isSlide()
    {
        return (bool) $this->isSlide;
    }

    /**
     * 設定是否手勢密碼
     *
     * @param boolean $bool 是否為手勢密碼
     * @return LoginLog
     */
    public function setSlide($bool)
    {
        $this->isSlide = $bool;

        return $this;
    }

    /**
     * 是否測試帳號
     *
     * @return boolean
     */
    public function isTest()
    {
        return (bool) $this->test;
    }

    /**
     * 設定是否測試帳號
     *
     * @param boolean $bool 是否為測試帳號
     * @return LoginLog
     */
    public function setTest($bool)
    {
        $this->test = $bool;

        return $this;
    }

    /**
     * 回傳資料(sync_login_log專用)
     * 如有新增欄位請同步新增
     *
     * @return array
     */
    public function getInfo()
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'username' => $this->getUsername(),
            'role' => $this->getRole(),
            'sub' => $this->sub,
            'domain' => $this->getDomain(),
            'ip' => $this->ip,
            'ipv6' => $this->ipv6,
            'host' => $this->getHost(),
            'at' => $this->getAt()->format('Y-m-d H:i:s'),
            'result' => $this->getResult(),
            'session_id' => $this->getSessionId(),
            'language' => $this->getLanguage(),
            'client_os' => $this->getClientOs(),
            'client_browser' => $this->getClientBrowser(),
            'ingress' => $this->getIngress(),
            'proxy1' => $this->proxy1,
            'proxy2' => $this->proxy2,
            'proxy3' => $this->proxy3,
            'proxy4' => $this->proxy4,
            'country' => $this->getCountry(),
            'city' => $this->getCity(),
            'entrance' => $this->getEntrance(),
            'is_otp' => $this->isOtp,
            'is_slide' => $this->isSlide,
            'test' => $this->test
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'username' => $this->getUsername(),
            'role' => $this->getRole(),
            'sub' => $this->isSub(),
            'domain' => $this->getDomain(),
            'ip' => $this->getIP(),
            'ipv6' => $this->getIpv6(),
            'host' => $this->getHost(),
            'at' => $this->getAt()->format(\DateTime::ISO8601),
            'result' => $this->getResult(),
            'session_id' => $this->getSessionId(),
            'language' => $this->getLanguage(),
            'client_os' => $this->getClientOs(),
            'client_browser' => $this->getClientBrowser(),
            'ingress' => $this->getIngress(),
            'proxy1' => $this->getProxy1(),
            'proxy2' => $this->getProxy2(),
            'proxy3' => $this->getProxy3(),
            'proxy4' => $this->getProxy4(),
            'country' => $this->getCountry(),
            'city' => $this->getCity(),
            'entrance' => $this->getEntrance(),
            'is_otp' => $this->isOtp(),
            'is_slide' => $this->isSlide(),
            'test' => $this->isTest()
        ];
    }
}
