<?php

namespace BB\DurianBundle\Login;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\LoginLog;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Entity\LoginErrorPerIp;
use Jenssegers\Agent\Agent;
use Dapphp\Radius\Radius;

class Validator extends ContainerAware
{
    /**
     * 控,管,客端擁有的層級對照表
     *
     * 管端:代理(2),總代理(3),股東(4),大股東(5),廳主(7)
     *
     * 客端:會員(1)
     *
     * @var Array
     */
    private $entranceRole = [
        LoginLog::LOGIN_FROM_CONTROL => [],
        LoginLog::LOGIN_FROM_ADMIN => [2, 3, 4, 5, 7, 8],
        LoginLog::LOGIN_FROM_CLIENT => [1]
    ];

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 檢查代入的domain及其代碼是否一致
     *
     * @param integer $domain
     * @param string  $loginCode
     * @return boolean
     */
    public function checkDomainIdentical($domain, $loginCode)
    {
        $em = $this->getEntityManager('share');
        $dcRepo = $em->getRepository('BBDurianBundle:DomainConfig');

        $criteria = [
            'domain' => $domain,
            'loginCode' => $loginCode
        ];
        $config = $dcRepo->findBy($criteria);

        if (!$config) {
            return false;
        }

        return true;
    }

    /**
     * 檢查登入端是否合法
     *
     * @param integer $entrance
     * @return boolean
     */
    public function checkValidEntrance($entrance)
    {
        return array_key_exists($entrance, $this->entranceRole);
    }

    /**
     * 解析及驗證 X-FORWARDED-FOR 資訊
     * 回傳前四組 ip, 不足補 null
     *
     * @param string $xForwardedFor
     * @return Array
     *
     * @author billy 2015.8.7
     */
    public function parseXForwardedFor($xForwardedFor)
    {
        $validator = $this->container->get('durian.validator');

        $proxy = explode(', ', $xForwardedFor);

        for ($i = 0; $i < 4; $i++) {
            if (!isset($proxy[$i])) {
                $proxy[$i] = null;
            }

            if ($proxy[$i] && !$validator->validateIp($proxy[$i])) {
                throw new \InvalidArgumentException('Invalid x_forwarded_for format', 150250015);
            }
        }

        return array_slice($proxy, 0, 4);
    }

    /**
     * 解析客戶端資訊
     *
     * @param integer $clientOs 客戶端作業系統
     * @param integer $clientBrowser 客戶端瀏覽器
     * @param integer $ingress 登入來源
     * @param integer $language 登入語系
     * @param string $userAgent 用戶代理
     * @return Array
     *
     * @author billy 2015.11.16
     */
    public function parseClientInfo($clientOs, $clientBrowser, $ingress, $language, $userAgent)
    {
        // 不存在於分類的值視為未帶入
        if (!array_key_exists($clientOs, LoginLog::$clientOsMap)) {
            $clientOs = '';
        }

        if (!array_key_exists($clientBrowser, LoginLog::$clientBrowserMap)) {
            $clientBrowser = '';
        }

        if (!array_key_exists($ingress, LoginLog::$ingressMap)) {
            $ingress = null;
        }

        if (!array_key_exists($language, LoginLog::$languageMap)) {
            $language = '';
        }

        // 有帶入有效分類則先做轉換
        if ($clientOs && array_key_exists($clientOs, LoginLog::$clientOsMap)) {
            $clientOs = LoginLog::$clientOsMap[$clientOs];
        }

        if ($clientBrowser && array_key_exists($clientBrowser, LoginLog::$clientBrowserMap)) {
            $clientBrowser = LoginLog::$clientBrowserMap[$clientBrowser];
        }

        if ($language && array_key_exists($language, LoginLog::$languageMap)) {
            $language = LoginLog::$languageMap[$language];
        }

        $info['os'] = '';
        $info['browser'] = '';
        $info['language'] = $language;
        $info['ingress'] = $ingress;

        if ($userAgent) {
            $agent = new Agent();
            $agent->setUserAgent($userAgent);

            $info['os'] = $agent->platform();
            $info['browser'] = $agent->browser();
        }

        // 若不存在於分類的作業系統列表，則歸類其他
        $osList = [
            'BlackBerryOS',
            'WindowsPhoneOS',
            'WindowsMobileOS',
            'iOS',
            'OS X',
            'Linux',
            'Windows',
            'AndroidOS'
        ];

        if ($userAgent && !in_array($info['os'], $osList)) {
            $info['os'] = 'other';
        }

        // 這兩組系統我們統一分類在 Windows Phone
        if (in_array($info['os'], ['WindowsPhoneOS', 'WindowsMobileOS'])) {
            $info['os'] = 'Windows Phone';
        }

        // 這些項目需要確認是否平板
        if (in_array($info['os'], ['BlackBerryOS', 'iOS', 'Windows', 'AndroidOS']) && $agent->isTablet()) {
            $info['os'] .= ' Tablet';
        }

        // 若不存在於分類的瀏覽器列表，則歸類其他
        $browserList = [
            'IE',
            'Opera',
            'Chrome',
            'Safari',
            'Firefox'
        ];

        if ($userAgent && !in_array($info['browser'], $browserList)) {
            $info['browser'] = 'other';
        }

        if ($userAgent) {
            $customBrowser = [
                'UBiOS' => '寰宇瀏覽器',
                'UB' => '寰宇瀏覽器',
                'BBBrowser' => 'BB瀏覽器',
                'QQBrowser' => 'QQ',
                'UCBrowser' => 'UC',
                'OppoBrowser' => 'Oppo'
            ];

            foreach ($customBrowser as $browserCode => $name) {
                $match = ' ' . $browserCode . '/';
                if (strpos($userAgent, $match) === false) {
                    continue;
                }

                $info['browser'] = $name;
            }
        }

        // 若有帶入有效的 $clientOs 跟 $clientBrowser, 則記錄帶入的值
        if ($clientOs) {
            $info['os'] = $clientOs;
        }

        if ($clientBrowser) {
            $info['browser'] = $clientBrowser;
        }

        // 來源為 mobile app or 下載版, 則強制紀錄瀏覽器為空
        if ($ingress == 4 || $ingress == 5) {
            $info['browser'] = '';
        }

        // 來源為BB瀏覽器, 則強制紀錄瀏覽器為BB瀏覽器
        if ($ingress == 6) {
            $info['browser'] = 'BB瀏覽器';
        }

        // 來源為寰宇瀏覽器, 則強制紀錄瀏覽器為寰宇瀏覽器
        if ($ingress == 7) {
            $info['browser'] = '寰宇瀏覽器';
        }

        return $info;
    }

    /**
     * 取得登入結果
     *
     * @param User $user 使用者
     * @param array $data 登入驗證資料，參數說明如下：
     *     string ['password'] 使用者密碼
     *     string ['slide_password'] 手勢密碼
     *     string ['app_id'] 手勢裝置識別碼
     *     string ['access_token'] 手勢存取標記
     *     string ['host'] 登入網址
     *     integer ['entrance'] 登入來源
     *     integer ['last_login_interval'] 阻擋重複登入秒數
     *     array ['verify_parent_id'] 上層使用者編號，體系檢查用
     *     boolean ['verify_level'] 是否驗證層級
     *     string ['otp_token'] otp token
     *     boolean ['ignore_verify_otp'] 廳主是否略過驗證OTP
     * @return integer
     */
    public function getLoginResult($user, $data)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redisSlide = $this->container->get('snc_redis.slide');
        $redisDefault = $this->container->get('snc_redis.default_client');
        $otpWorker = $this->container->get('durian.otp_worker');

        if (empty($user)) {
            return LoginLog::RESULT_USERNAME_WRONG;
        }

        $userId = $user->getId();
        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->findOneBy(['userId' => $userId]);

        if ($binding) {
            return LoginLog::RESULT_USER_HAS_OAUTH_BINDING;
        }

        if ($data['entrance'] && !in_array($user->getRole(), $this->entranceRole[$data['entrance']])) {
            return LoginLog::RESULT_USERNAME_WRONG;
        }

        if ($data['last_login_interval'] > 0) {
            $now = new \DateTime();

            // 有last_login 再驗證上次登入與這次登入的秒數
            if ($user->getLastLogin()) {
                $loginInterval = $user->getLastLogin()->modify("+{$data['last_login_interval']} sec");

                // 判斷時間內是否再次登入
                if ($loginInterval >= $now) {
                    return LoginLog::RESULT_DUPLICATED_WITHIN_TIME;
                }
            }
        }

        if ($data['verify_parent_id']) {
            $ancestorIdArray = $em->getRepository('BBDurianBundle:UserAncestor')
                ->getAncestorIdBy($userId);

            $inHierarchy = false;

            // 大股東面板會有多個大股東使用，因此只要使用者符合其中一條體系就不阻擋
            foreach ($data['verify_parent_id'] as $parentId) {
                if (in_array($parentId, $ancestorIdArray)) {
                    $inHierarchy = true;

                    break;
                }
            }

            if (!$inHierarchy) {
                return LoginLog::RESULT_NOT_IN_HIERARCHY;
            }
        }

        if (!$user->isEnabled()) {
            return LoginLog::RESULT_USER_IS_DISABLE;
        }

        if ($user->isBlock()) {
            return LoginLog::RESULT_USER_IS_BLOCK;
        }

        if (isset($data['slide_password'])) {
            $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
                ->findOneByUserAndAppId($userId, $data['app_id']);

            if (!$binding) {
                return LoginLog::RESULT_SLIDEPASSWORD_NOT_FOUND;
            }

            $device = $binding->getDevice();

            if (!$device->isEnabled()) {
                return LoginLog::RESULT_DEVICE_DISABLED;
            }

            if ($binding->getErrNum() >= 3) {
                return LoginLog::RESULT_SLIDEPASSWORD_BLOCKED;
            }

            if (!password_verify($data['slide_password'], $device->getHash())) {
                $binding->addErrNum();

                if ($binding->getErrNum() >= 3) {
                    return LoginLog::RESULT_SLIDEPASSWORD_WRONG_AND_BLOCK;
                }

                return LoginLog::RESULT_SLIDEPASSWORD_WRONG;
            }

            if ($data['access_token'] != $redisSlide->get("access_token_{$data['app_id']}")) {
                return LoginLog::RESULT_DEVICE_NOT_VERIFIED;
            }
        }

        $isOncePassword = false;

        if (isset($data['password'])) {
            // 密碼皆轉成小寫後再比對
            $password = strtolower($data['password']);

            // 取得使用者密碼表
            $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);
            $hash = $userPassword->getHash();

            if ($hash == '') {
                return LoginLog::RESULT_USER_DISABLED_PASSWORD;
            }

            // 在時效內且未使用過需驗證臨時密碼
            $now = new \DateTime('now');
            $onceExpireAt = $userPassword->getOnceExpireAt();

            if (!$userPassword->isUsed() && $onceExpireAt && $now < $onceExpireAt) {
                $isOncePassword = password_verify($password, $userPassword->getOncePassword());
            }

            if (!$isOncePassword && !password_verify($password, $hash)) {
                $user->addErrNum();
                $userPassword->addErrNum();

                if ($userPassword->getErrNum() == 3) {
                    return LoginLog::RESULT_PASSWORD_WRONG_AND_BLOCK;
                }

                return LoginLog::RESULT_PASSWORD_WRONG;
            }
        }

        // 廳主與廳主子帳號，判斷是否需OTP驗證
        if ($user->getRole() == 7 && !$data['ignore_verify_otp']) {
            $domain = $user->getDomain();
            $config = $emShare->find('BBDurianBundle:DomainConfig', $domain);

            $otpUser = $domain;

            if ($user->isSub()) {
                $otpUser .= '_sub';
            }

            if ($config->isVerifyOtp() && !$redisDefault->get('disable_otp')) {
                try {
                    $result = $otpWorker->getOtpResult($otpUser, $data['otp_token'], $userId, $domain);
                    if (!$result['response']) {
                        if ($result['error'] == 'Access rejected (3)') {
                            return LoginLog::RESULT_OTP_WRONG;
                        }

                        throw new \RuntimeException('Otp server connection failure', 150250032);
                    }
                } catch (\Exception $e) {
                    $italkingOperator = $this->container->get('durian.italking_operator');
                    $hostName = gethostname();
                    $now = date('Y-m-d H:i:s');
                    $exceptionType = get_class($e);
                    $msg = sprintf(
                        '[%s] [%s] Error Code: 150250032 OTP伺服器連線異常，請GM先檢查 OTP機器測試並通知 DCOP，如有異常再請通知 RD5-帳號研發部 工程師檢查。',
                        $hostName,
                        $now
                    );

                    $italkingOperator->pushExceptionToQueue(
                        'acc_system',
                        $exceptionType,
                        $msg
                    );

                    throw $e;
                }
            }
        }

        // 驗證使用者層級，這部分若使用者登入層級網址與登入網址不同，會表示登入成功並回傳層級網址
        // 若有新增登入錯誤的驗證需在此動作之前，否則可能會誤判成登入成功
        if ($data['verify_level']) {
            $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

            if ($userLevel) {
                $levelUrlRepo = $em->getRepository('BBDurianBundle:LevelUrl');
                $userLevelUrl = $levelUrlRepo->findOneBy([
                    'level' => $userLevel->getLevelId(),
                    'enable' => true
                ]);

                $hostLevelUrl = $levelUrlRepo->getByUrl($data['host']);

                // 使用者沒有綁定層級網址，但登入網址有層級
                if (!$userLevelUrl && $hostLevelUrl) {
                    return LoginLog::RESULT_LEVEL_WRONG;
                }

                // 使用者層級網址跟登入網址不同
                if ($userLevelUrl && $userLevelUrl->getUrl() != $data['host']) {
                    return LoginLog::RESULT_SUCCESS_AND_REDIRECT;
                }
            }
        }

        //　臨時密碼登入成功一次則標記為使用過
        if ($isOncePassword) {
            $userPassword->setUsed(true);
            $operationLogger = $this->container->get('durian.operation_logger');
            $log = $operationLogger->create('user_password', ['user_id' => $userId]);
            $log->addMessage('used', 'false', 'true');
            $operationLogger->save($log);
        }

        return LoginLog::RESULT_SUCCESS;
    }

    /**
     * 取得Oauth登入結果
     *
     * @param string  $binding           oauth綁定資料
     * @param string  $entrance          登入來源
     * @param string  $host              登入網址
     * @param integer $lastLoginInterval 阻擋重複登入秒數
     * @param array   $verifyParentId    上層使用者編號，體系檢查用
     * @param boolean $verifyLevel       是否驗證層級
     *
     * @return integer
     */
    public function getOauthLoginResult(
        $binding,
        $entrance,
        $host,
        $lastLoginInterval,
        $verifyParentId,
        $verifyLevel
    )
    {
        $em = $this->getEntityManager();

        $user = $em->find('BBDurianBundle:User', $binding->getUserId());

        if ($entrance && !in_array($user->getRole(), $this->entranceRole[$entrance])) {
            return LoginLog::RESULT_USERNAME_WRONG;
        }

        if ($lastLoginInterval > 0) {
            $now = new \DateTime();

            // 有last_login 再驗證上次登入與這次登入的秒數
            if ($user->getLastLogin()) {
                $loginInterval = $user->getLastLogin()->modify("+{$lastLoginInterval} sec");

                // 判斷時間內是否再次登入
                if ($loginInterval >= $now) {
                    return LoginLog::RESULT_DUPLICATED_WITHIN_TIME;
                }
            }
        }

        if ($verifyParentId) {
            $ancestorIdArray = $em->getRepository('BBDurianBundle:UserAncestor')
                ->getAncestorIdBy($user->getId());

            $inHierarchy = false;

            // 大股東面板會有多個大股東使用，因此只要使用者符合其中一條體系就不阻擋
            foreach ($verifyParentId as $parentId) {
                if (in_array($parentId, $ancestorIdArray)) {
                    $inHierarchy = true;

                    break;
                }
            }

            if (!$inHierarchy) {
                return LoginLog::RESULT_NOT_IN_HIERARCHY;
            }
        }

        if (!$user->isEnabled()) {
            return LoginLog::RESULT_USER_IS_DISABLE;
        }

        if ($user->isBlock()) {
            return LoginLog::RESULT_USER_IS_BLOCK;
        }

        // 驗證使用者層級，這部分若使用者登入層級網址與登入網址不同，會表示登入成功並回傳層級網址
        // 若有新增登入錯誤的驗證需在此動作之前，否則可能會誤判成登入成功
        if ($verifyLevel) {
            $userLevel = $em->find('BBDurianBundle:UserLevel', $user->getId());

            if ($userLevel) {
                $levelUrlRepo = $em->getRepository('BBDurianBundle:LevelUrl');
                $userLevelUrl = $levelUrlRepo->findOneBy([
                    'level' => $userLevel->getLevelId(),
                    'enable' => true
                ]);

                $hostLevelUrl = $levelUrlRepo->findOneBy(['url' => $host]);

                // 使用者沒有綁定層級網址，但登入網址有層級
                if (!$userLevelUrl && $hostLevelUrl) {
                    return LoginLog::RESULT_LEVEL_WRONG;
                }

                // 使用者層級網址跟登入網址不同
                if ($userLevelUrl && $userLevelUrl->getUrl() != $host) {
                    return LoginLog::RESULT_SUCCESS_AND_REDIRECT;
                }
            }
        }

        return LoginLog::RESULT_SUCCESS;
    }

    /**
     * 登入成功後做的動作
     *
     * @param User $user 登入使用者
     * @param LoginLog $log 登入記錄
     * @param boolean $duplicateLogin 是否支援重複登入
     * @param boolean $isRedirect 是否回傳網址
     * @param string $appId 手勢登入裝置識別碼
     *
     * @return Array
     */
    public function loginSuccess($user, $log, $duplicateLogin, $isRedirect, $appId = null)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $output['ret']['login_user'] = [];
        $userId = $user->getId();

        $user->setLastLogin($log->getAt());
        $user->zeroErrNum();
        $data = $user->toArray();

        if (!$appId) {
            $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);
            $userPassword->zeroErrNum();

            // 以UserPassword資料為主
            $data['err_num'] = $userPassword->getErrNum();
            $data['password_expire_at'] = $userPassword->getExpireAt()->format(\DateTime::ISO8601);
            $data['password_reset'] = $userPassword->isReset();
        } else {
            $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
                ->findOneByUserAndAppId($userId, $appId);
            $binding->zeroErrNum();
            $binding->getDevice()->zeroErrNum();

            // 手勢登入回傳手勢密碼錯誤次數，不回傳使用者密碼相關
            unset($data['password_expire_at']);
            unset($data['password_reset']);
            $data['err_num'] = $binding->getErrNum();
        }

        $output['ret']['login_user'] = $data;
        $output['ret']['login_user']['parent'] = null;
        $output['ret']['login_user']['all_parents'] = array();

        if ($user->hasParent()) {
            $output['ret']['login_user']['parent'] = $user->getParent()->getId();
            $output['ret']['login_user']['all_parents'] = $user->getAllParentsId();
        }

        $sessionBroker = $this->container->get('durian.session_broker');

        // 若不支援重複登入,則應先刪除之前的session
        if (!$duplicateLogin) {
            $sessionBroker->removeByUserId($userId);
        }


        $loginInfo['client_os']     = $log->getClientOs();
        $loginInfo['ingress']       = $log->getIngress();
        $loginInfo['last_login_ip'] = $log->getIp();

        $output['ret']['login_user']['session_id'] = $sessionBroker->create($user, true, $loginInfo);

        // 回傳導向網址
        if ($isRedirect) {
            $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);
            $levelUrlRepo = $em->getRepository('BBDurianBundle:LevelUrl');

            $userLevelUrl = $levelUrlRepo->findOneBy([
                'level' => $userLevel->getLevelId(),
                'enable' => true
            ]);

            $output['ret']['redirect_url'] = $userLevelUrl->getUrl();
        }

        return $output;
    }

    /**
     * 處理登入錯誤IP統計
     *
     * @param LoginLog $log
     * @param string $clientIp
     *
     * @author petty 2014.11.10
     */
    public function processLoginErrorPerIp(LoginLog $log, $clientIp)
    {
        $em          = $this->getEntityManager('share');
        $createdHour = $log->getAt()->format('YmdH0000');
        $ipNumber    = ip2long($clientIp);

        $criteria = [
            'ip'     => $ipNumber,
            'at'     => $createdHour,
            'domain' => $log->getDomain()
        ];

        $stat = $em->getRepository('BBDurianBundle:LoginErrorPerIp')->findOneBy($criteria);

        if (!$stat) {
            $stat = new LoginErrorPerIp($clientIp, $log->getAt(), $log->getDomain());
            $em->persist($stat);
        }

        $stat->addCount();
    }

    /**
     * 檢查ip是否在試密碼
     *
     * 判斷條件：
     *    1.domain有設定且開啟阻擋登入才判斷條件2,否則回傳非試密碼
     *    2.若同一domain IP一天內超出輸入密碼錯誤的次數限制,則判斷條件3,否則回傳非試密碼
     *    3.若時效內已存在該ip的IP封鎖列表紀錄,則不用再新增IP封鎖列表紀錄,回傳非試密碼,否則回傳試密碼
     *
     * @param integer $domain 廳主id
     * @param string  $ip     操作者ip
     * @return boolean
     *
     * @author petty 2014.10.14
     */
    public function checkIpTryPwd($domain, $ip)
    {
        $em = $this->getEntityManager('share');
        $config = $em->find('BBDurianBundle:DomainConfig', $domain);

        // 若domain沒有設定阻擋登入,則回傳非試密碼狀態
        if (!$config) {
            return false;
        }

        // 若domain設定為不阻擋登入,則回傳非試密碼狀態
        if (!$config->isBlockLogin()) {
            return false;
        }

        $now = new \DateTime('now');
        $cloneNow = clone $now;
        $yesterday = $cloneNow->sub(new \DateInterval('P1D')); // 減1天
        $start = $yesterday->format('YmdHis');
        $end = $now->format('YmdH0000');

        $criteria = [
            'domain'    => $domain,
            'ip'        => ip2long($ip),
            'startTime' => $start,
            'endTime'   => $end
        ];

        $repo = $em->getRepository('BBDurianBundle:LoginErrorPerIp');

        $errorNum = 1; // 已試密碼錯誤一筆資料
        $errorNum += $repo->sumLoginErrorPerIp($criteria);

        // 未超出限制,判定此ip非試密碼
        if ($errorNum < DomainConfig::MAX_ERROR_PWD_TIMES) {
            return false;
        }

        $repo = $em->getRepository('BBDurianBundle:IpBlacklist');

        // 若已存在IP封鎖列表紀錄,回傳false非試密碼狀態
        if ($repo->hasBlockLogin($domain, $ip)) {
            return false;
        }

        return true;
    }
}
