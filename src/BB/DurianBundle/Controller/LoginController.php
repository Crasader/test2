<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\LastLogin;
use BB\DurianBundle\Entity\LoginLog;
use BB\DurianBundle\Entity\LoginLogMobile;
use BB\DurianBundle\Entity\IpBlacklist;
use BB\DurianBundle\Entity\Blacklist;
use BB\DurianBundle\Entity\BlacklistOperationLog;
use Symfony\Component\HttpFoundation\Request;

class LoginController extends Controller
{
    /**
     * 取得與使用者相同IP登入的最後登入紀錄
     *
     * @Route("/login_log/same_ip",
     *        name = "api_login_log_same_ip",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSameIpAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');
        $validator = $this->get('durian.validator');
        $paramHandler = $this->get('durian.parameter_handler');

        $query = $request->query;
        $userId = $query->get('user_id');
        $role = $query->get('role');
        $start = $query->get('start');
        $end = $query->get('end');
        $filterUser = $query->get('filter_user', 1);
        $filter = $query->get('filter', 0);
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $domain = $query->get('domain');

        if (!$userId) {
            throw new \InvalidArgumentException('No user_id specified', 150250025);
        }

        if (!$validator->validateDateRange($start, $end)) {
            throw new \InvalidArgumentException('No start or end specified', 150250016);
        }

        $validator->validatePagination($firstResult, $maxResults);

        $start = $paramHandler->datetimeToYmdHis($start);
        $end = $paramHandler->datetimeToYmdHis($end);

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150250024);
        }

        if ($role && !is_array($role)) {
            $role = [$role];
        }

        $repo = $emHis->getRepository('BBDurianBundle:LoginLog');
        $ipList = $repo->getIpList($userId, $start, $end);

        $loginLogs = [];
        $total = 0;

        if ($ipList) {
            $criteria = [
                'user_id' => $userId,
                'ip' => $ipList,
                'domain' => $domain,
                'role' => $role,
                'filter_user' => $filterUser,
                'filter' => $filter,
                'start' => $start,
                'end' => $end
            ];

            $loginLogs = $repo->getSameIpList($criteria, $firstResult, $maxResults);
            $total = $repo->countSameIpOf($criteria);
        }

        foreach ($loginLogs as $key => $log) {
            $loginLogs[$key]['ip'] = long2ip($log['ip']);
            $loginLogs[$key]['at'] = $log['at']->format(\DateTime::ISO8601);
            $loginLogs[$key]['is_slide'] = (bool) $log['is_slide'];
        }

        $pagination = [
            'first_result' => $firstResult,
            'max_results' => $maxResults,
            'total' => $total
        ];

        $ret = ['result' => 'ok'];
        $ret['ret'] = $loginLogs;
        $ret['pagination'] = $pagination;

        return new JsonResponse($ret);
    }

    /**
     * 使用者登入
     *
     * @Route("/login",
     *        name = "api_login",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginAction(Request $request)
    {
        $loginValidator = $this->get('durian.login_validator');
        $validator = $this->get('durian.validator');
        $request  = $request->request;
        $ip       = trim($request->get('ip'));
        $username = trim($request->get('username'));
        $domain   = $request->get('domain');
        $password = $request->get('password');
        $entrance = $request->get('entrance');
        $loginCode = '';
        $language = $request->get('language', 0);
        $host = trim($request->get('host'));
        $ipv6 = trim($request->get('ipv6'));
        $clientOs = $request->get('client_os', 0);
        $clientBrowser = $request->get('client_browser', 0);
        $ingress = $request->get('ingress', 0);
        $deviceName = $request->get('device_name');
        $brand = $request->get('brand');
        $model = $request->get('model');
        $userAgent = trim($request->get('user_agent', ''));
        $duplicateLogin = (bool) $request->get('duplicate_login', 0);
        $xForwardedFor = trim($request->get('x_forwarded_for'));
        $verifyBlacklist = (bool) $request->get('verify_blacklist', 1);
        $lastLoginInterval = $request->get('last_login_interval', 0);
        $verifyParentId = $request->get('verify_parent_id', []);
        $verifyLevel = (bool) $request->get('verify_level', 0);
        $otpToken = $request->get('otp_token', '');
        $ignoreVerifyOtp = (bool) $request->get('ignore_verify_otp', 0);

        // 驗證參數編碼是否為utf8
        $checkParameter = [$ipv6, $host, $deviceName, $brand, $model];
        $validator->validateEncode($checkParameter);

        if (!$username) {
            throw new \InvalidArgumentException('No username specified', 150250004);
        }

        if (strpos($username, '@')) {
            list($username, $loginCode) = explode('@', $username);
        }

        if (!$ip) {
            throw new \InvalidArgumentException('No ip specified', 150250005);
        }

        if (!$domain && !$loginCode) {
            throw new \InvalidArgumentException('No domain specified', 150250006);
        }

        if ($domain && $loginCode) {
            $checkResult = $loginValidator->checkDomainIdentical($domain, $loginCode);

            if (!$checkResult) {
                throw new \RuntimeException('Domain and LoginCode are not matching', 150250002);
            }
        }

        if (!$password) {
            throw new \InvalidArgumentException('No password specified', 150250007);
        }

        if ((!$loginValidator->checkValidEntrance($entrance))) {
            throw new \InvalidArgumentException('Invalid entrance given', 150250001);
        }

        $criteriaBlacklist = ['ip' => $ip];

        // 手機登入不檢查系統封鎖 ip 黑名單
        if ($ingress == 2 || $ingress == 4) {
            $criteriaBlacklist['system_lock'] = false;
        }

        // 若非陣列則強制轉為陣列
        if (!is_array($verifyParentId)) {
            $verifyParentId = [$verifyParentId];
        }

        // 解析 X-FORWARDED-FOR 資訊
        $proxy = $loginValidator->parseXForwardedFor($xForwardedFor);

        // 解析客戶端資訊
        $clientInfo = $loginValidator->parseClientInfo(
            $clientOs,
            $clientBrowser,
            $ingress,
            $language,
            $userAgent
        );

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->getRedis();

        if ($loginCode && !$domain) {
            $dcRepo = $emShare->getRepository('BBDurianBundle:DomainConfig');
            $config = $dcRepo->findOneBy(['loginCode' => $loginCode]);

            if (!$config) {
                throw new \RuntimeException("No login code found", 150250009);
            }

            $domain = $config->getDomain();
        }

        // 取得IP來源國家與城市
        $ipBlockRepo = $emShare->getRepository('BBDurianBundle:GeoipBlock');
        $verId = $ipBlockRepo->getCurrentVersion();
        $ipBlock = $ipBlockRepo->getBlockByIpAddress($ip, $verId);

        $country = null;
        $city = null;
        if ($ipBlock) {
            $ipCountry = $emShare->find('BBDurianBundle:GeoipCountry', $ipBlock['country_id']);

            $country = $ipCountry->getzhTwName();
            if (empty($country)) {
                $country = $ipCountry->getCountryCode();
            }

            if ($ipBlock['city_id']) {
                $ipCity = $emShare->find('BBDurianBundle:GeoipCity', $ipBlock['city_id']);

                $city = $ipCity->getZhTwName();
                if (empty($city)) {
                    $city = $ipCity->getCityCode();
                }
            }
        }

        $criteria = [
            'domain'   => $domain,
            'username' => $username
        ];

        $userId = 0;
        $userRole = 0;
        $userIsSub = null;
        $test = false;
        $userErrNum = 0;
        $repo = $em->getRepository('BBDurianBundle:User');
        $user = $repo->findOneBy($criteria);

        if ($user) {
            $userId = $user->getId();
            $userRole = $user->getRole();
            $userIsSub = $user->isSub();
            $test = $user->isTest();
            $userErrNum = $user->getErrNum();
        }

        // 預設檢查ip黑名單
        if ($verifyBlacklist) {
            $repo = $emShare->getRepository('BBDurianBundle:Blacklist');
            $blacklist = $repo->getBlacklistSingleBy($criteriaBlacklist, $domain);

            if ($blacklist) {
                $log = new LoginLog($ip, $domain, LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST);
                $log->setUserId($userId);
                $log->setUsername($username);
                $log->setRole($userRole);
                $log->setSub($userIsSub);
                $log->setAt(new \DateTime('now'));
                $log->setHost($host);
                $log->setLanguage($clientInfo['language']);
                $log->setIpv6($ipv6);
                $log->setClientOs($clientInfo['os']);
                $log->setClientBrowser($clientInfo['browser']);
                $log->setIngress($clientInfo['ingress']);
                $log->setProxy1($proxy[0]);
                $log->setProxy2($proxy[1]);
                $log->setProxy3($proxy[2]);
                $log->setProxy4($proxy[3]);
                $log->setCountry($country);
                $log->setCity($city);
                $log->setEntrance($entrance);
                $log->setTest($test);

                $em->persist($log);
                $em->flush();

                $redis->lpush('login_log_queue', json_encode($log->getInfo()));

                $loginUser = [
                    'id' => $userId,
                    'err_num' => $userErrNum
                ];
                $output['ret']['login_user'] = $loginUser;
                $output['ret']['code'] = null;
                $output['ret']['login_result'] = LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST;
                $output['result'] = 'ok';

                return new JsonResponse($output);
            }
        }

        // 檢查domain是否要阻擋登入的封鎖列表;預設為不阻擋
        $config = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        $isBlock = false;
        // domain設定需阻擋登入的封鎖列表,則檢查是否阻擋此ip登入
        if ($config && $config->isBlockLogin()) {
            $blackListRepo = $emShare->getRepository('BBDurianBundle:IpBlacklist');
            $isBlock = $blackListRepo->isBlockLogin($domain, $ip);
        }

        // 若有封鎖列表紀錄且沒有被手動移除,則阻擋下來
        if ($isBlock) {
            $log = new LoginLog($ip, $domain, LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST);
            $log->setUserId($userId);
            $log->setUsername($username);
            $log->setRole($userRole);
            $log->setSub($userIsSub);
            $log->setAt(new \DateTime('now'));
            $log->setHost($host);
            $log->setLanguage($clientInfo['language']);
            $log->setIpv6($ipv6);
            $log->setClientOs($clientInfo['os']);
            $log->setClientBrowser($clientInfo['browser']);
            $log->setIngress($clientInfo['ingress']);
            $log->setProxy1($proxy[0]);
            $log->setProxy2($proxy[1]);
            $log->setProxy3($proxy[2]);
            $log->setProxy4($proxy[3]);
            $log->setCountry($country);
            $log->setCity($city);
            $log->setEntrance($entrance);
            $log->setTest($test);

            $em->persist($log);

            $em->beginTransaction();
            $redis->multi();
            try {
                $em->flush();

                if ($ingress == 2 || $ingress == 4) {
                    $logMobile = new LoginLogMobile($log);
                    $logMobile->setName($deviceName);
                    $logMobile->setBrand($brand);
                    $logMobile->setModel($model);

                    $em->persist($logMobile);
                    $em->flush();
                    $redis->lpush('login_log_mobile_queue', json_encode($logMobile->getInfo()));
                }

                $em->commit();
                $redis->lpush('login_log_queue', json_encode($log->getInfo()));
                $redis->exec();
            } catch (\Exception $e) {
                $redis->discard();
                $em->rollback();

                throw $e;
            }

            $loginUser = [
                'id' => $userId,
                'err_num' => $userErrNum
            ];
            $output['ret']['login_user'] = $loginUser;
            $output['ret']['code'] = null;
            $output['ret']['login_result'] = LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST;
            $output['result'] = 'ok';

            return new JsonResponse($output);
        }

        $data = [
            'password' => $password,
            'host' => $host,
            'entrance' => $entrance,
            'last_login_interval' => $lastLoginInterval,
            'verify_parent_id' => $verifyParentId,
            'verify_level' => $verifyLevel,
            'otp_token' => $otpToken,
            'ignore_verify_otp' => $ignoreVerifyOtp
        ];

        $result = $loginValidator->getLoginResult($user, $data);

        // 判斷是否需要回傳導向網址
        $isRedirect = false;

        // 登入結果為登入成功需導向網址，需調整結果為登入成功
        // RESULR_SUCCESS_AND_REDIRECT 僅用來判斷需不需要回傳導向網址
        if ($result == LoginLog::RESULT_SUCCESS_AND_REDIRECT) {
            $result = LoginLog::RESULT_SUCCESS;
            $isRedirect = true;
        }

        $log = null;
        $logMobile = null;
        $createBlacklist = false;
        $output['ret']['login_user'] = array();
        $output['ret']['code'] = null;

        // 根據login result做對應動作
        if (null != $user) {
            $log = new LoginLog($ip, $domain, $result);
            $log->setUserId($userId);
            $log->setUsername($user->getUsername());
            $log->setRole($user->getRole());
            $log->setSub($user->isSub());
            $log->setAt(new \DateTime('now'));
            $log->setHost($host);
            $log->setLanguage($clientInfo['language']);
            $log->setIpv6($ipv6);
            $log->setClientOs($clientInfo['os']);
            $log->setClientBrowser($clientInfo['browser']);
            $log->setIngress($clientInfo['ingress']);
            $log->setProxy1($proxy[0]);
            $log->setProxy2($proxy[1]);
            $log->setProxy3($proxy[2]);
            $log->setProxy4($proxy[3]);
            $log->setCountry($country);
            $log->setCity($city);
            $log->setEntrance($entrance);
            $log->setTest($test);

            if ($user->getRole() == 7 && $config->isVerifyOtp() && !$ignoreVerifyOtp) {
                $log->setOtp(true);
            }

            $em->persist($log);

            // 若密碼錯誤3次, 便凍結使用者
            if ($result == LoginLog::RESULT_PASSWORD_WRONG_AND_BLOCK) {
                $user->block();
            }

            // 若登入成功, 則更新使用者登入時間，並產生一段加密的編碼
            if ($result == LoginLog::RESULT_SUCCESS) {
                $output = $loginValidator->loginSuccess($user, $log, $duplicateLogin, $isRedirect);
                $lastLogin = $em->find('BBDurianBundle:LastLogin', $userId);
                $log->setSessionId($output['ret']['login_user']['session_id']);
            }

            $isTryPwd = false;
            // 若密碼錯誤,處理登入錯誤統計,並確認是否要加入封鎖列表
            if ($result == LoginLog::RESULT_PASSWORD_WRONG) {
                $loginValidator->processLoginErrorPerIp($log, $ip); // 處理登入錯誤統計
                $isTryPwd = $loginValidator->checkIpTryPwd($domain, $ip); // 檢查ip是否在試密碼
            }

            // 若密碼錯誤造成凍結,處理登入錯誤統計,並確認是否要加入封鎖列表
            if ($result == LoginLog::RESULT_PASSWORD_WRONG_AND_BLOCK) {
                $loginValidator->processLoginErrorPerIp($log, $ip); // 處理登入錯誤統計
                $isTryPwd = $loginValidator->checkIpTryPwd($domain, $ip); // 檢查ip是否在試密碼
            }

            // ip試密碼, 則加入封鎖列表，與判斷是否加入黑名單
            if ($isTryPwd) {
                $operationLogger = $this->container->get('durian.operation_logger');

                // 24小時內 ip 封鎖列表至少有一筆不同廳紀錄，且黑名單沒有紀錄需加進黑名單
                $repo = $emShare->getRepository('BBDurianBundle:IpBlacklist');

                $now = new \DateTime('now');
                $cloneNow = clone $now;
                $yesterday = $cloneNow->sub(new \DateInterval('P1D'));

                $criteria = [
                    'ip' => $ip,
                    'removed' => false,
                    'loginError' => true,
                    'start' => $yesterday->format('Y-m-d H:i:s'),
                    'end' => $now->format('Y-m-d H:i:s')
                ];

                $ipBlacklist = $repo->getListBy($criteria, [], null, null, 'domain');
                $count = count($ipBlacklist);

                // 阻擋同廳同分秒跨天新增
                $sameDomain = false;

                if ($count == 1 && $ipBlacklist[0]->getDomain() == $domain) {
                    $sameDomain = true;
                }

                // 檢查黑名單有沒有此筆ip紀錄，有的話則不再加入黑名單
                $repo = $emShare->getRepository('BBDurianBundle:Blacklist');
                $oldBlacklist = $repo->findOneBy([
                    'wholeDomain' => true,
                    'ip' => ip2long($criteria['ip'])
                ]);

                if ($count != 0 && !$oldBlacklist && !$sameDomain) {
                    $createBlacklist = true;
                    $blacklist = new Blacklist();
                    $blacklist->setIp($ip);
                    $blacklist->setSystemLock(true);
                    $blacklist->setControlTerminal(true);
                    $emShare->persist($blacklist);

                    $blacklistLog = $operationLogger->create('blacklist', []);
                    $blacklistLog->addMessage('whole_domain', var_export($blacklist->isWholeDomain(), true));
                    $blacklistLog->addMessage('ip', $blacklist->getIp());
                    $blacklistLog->addMessage('created_at', $blacklist->getCreatedAt()->format('Y-m-d H:i:s'));
                    $blacklistLog->addMessage('modified_at', $blacklist->getModifiedAt()->format('Y-m-d H:i:s'));
                    $operationLogger->save($blacklistLog);
                }

                // 加到封鎖列表，需阻擋同分秒新增同廳封鎖列表
                if (!$sameDomain) {
                    $ipBlock = new IpBlacklist($domain, $ip);
                    $ipBlock->setLoginError(true);
                    $emShare->persist($ipBlock);

                    $ipBlacklistLog = $operationLogger->create('ip_blacklist', []);
                    $ipBlacklistLog->addMessage('domain', $ipBlock->getDomain());
                    $ipBlacklistLog->addMessage('ip', $ipBlock->getIp());
                    $ipBlacklistLog->addMessage('login_error', var_export($ipBlock->isLoginError(), true));
                    $ipBlacklistLog->addMessage('removed', var_export($ipBlock->isRemoved(), true));
                    $ipBlacklistLog->addMessage('created_at', $ipBlock->getCreatedAt()->format(\DateTime::ISO8601));
                    $ipBlacklistLog->addMessage('modified_at', $ipBlock->getModifiedAt()->format(\DateTime::ISO8601));
                    $ipBlacklistLog->addMessage('operator', $ipBlock->getOperator());
                    $operationLogger->save($ipBlacklistLog);
                }
            }

            $output['ret']['login_user']['id'] = $user->getId();
            $output['ret']['login_user']['err_num'] = $user->getErrNum();
        }

        $emShare->beginTransaction();
        $em->beginTransaction();
        try {
            $em->flush();

            if ($log && in_array($ingress, [2, 4])) {
                $logMobile = new LoginLogMobile($log);
                $logMobile->setName($deviceName);
                $logMobile->setBrand($brand);
                $logMobile->setModel($model);

                $em->persist($logMobile);
            }

            // 為避免 deadlock，最後登入 ip & id 需同時更新
            if ($result == LoginLog::RESULT_SUCCESS) {
                if (!$lastLogin) {
                    $lastLogin = new LastLogin($userId, $ip);
                    $em->persist($lastLogin);
                }

                $lastLogin->setIp($ip);
                $lastLogin->setLoginLogId($log->getId());
            }

            $emShare->flush();
            $em->flush();

            if ($createBlacklist) {
                $blacklistLog = new BlacklistOperationLog($blacklist->getId());
                $blacklistLog->setCreatedOperator('system');
                $blacklistLog->setNote('登入密碼錯誤超過限制');

                $emShare->persist($blacklistLog);
                $emShare->flush();
            }

            $emShare->commit();
            $em->commit();

            if ($log) {
                $redis->lpush('login_log_queue', json_encode($log->getInfo()));
            }

            if ($logMobile) {
                $redis->lpush('login_log_mobile_queue', json_encode($logMobile->getInfo()));
            }
        } catch (\Exception $e) {
            $pdoMsg = null;

            //DBALException內部BUG,並判斷是否為Duplicate entry 跟 deadlock
            if (!is_null($e->getPrevious())) {
                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 1062) {
                    $pdoMsg = $e->getMessage();
                }
            }

            /**
             * 同分秒加入黑名單的狀況或同分秒加入封鎖列表的狀況
             */
            if (strpos($pdoMsg, 'uni_blacklist_domain_ip') || strpos($pdoMsg, 'uni_ip_blacklist_domain_ip_created_date')) {
                $output['result'] = 'ok';
                $output['ret']['login_result'] = $result;

                return new JsonResponse($output);
            }

            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }

            if ($emShare->getConnection()->isTransactionActive()) {
                $emShare->rollback();
            }

            // 如果出錯，必須將 Session 資料刪除
            if (isset($output['ret']['login_user']['session_id'])) {
                $sessionBroker = $this->get('durian.session_broker');
                $sessionBroker->remove($output['ret']['login_user']['session_id']);
            }

            // 隱藏阻擋同分秒同廳同IP登入錯誤的狀況
            if (strpos($pdoMsg, 'uni_login_error_ip_at_domain') && strpos($pdoMsg, 'login_error_per_ip')) {
                $output['code'] = 150250014;
                $output['result'] = 'error';
                $output['msg'] = $this->get('translator')->trans('Database is busy');

                return new JsonResponse($output);
            }

            if (strpos($pdoMsg, 'last_login')) {
                throw new \RuntimeException('Database is busy', 150250029);
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret']['login_result'] = $result;

        return new JsonResponse($output);
    }

    /**
     * Oauth使用者登入
     *
     * @Route("/oauth/login",
     *        name = "api_oauth_login",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function oauthLoginAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->getRedis();
        $loginValidator = $this->get('durian.login_validator');
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $oauthId = $request->get('oauth_id');
        $openid = $request->get('openid');
        $ip = trim($request->get('ip'));
        $entrance = $request->get('entrance');
        $language = $request->get('language', 0);
        $host = trim($request->get('host'));
        $ipv6 = trim($request->get('ipv6'));
        $clientOs = $request->get('client_os', 0);
        $clientBrowser = $request->get('client_browser', 0);
        $ingress = $request->get('ingress', 0);
        $deviceName = $request->get('device_name');
        $brand = $request->get('brand');
        $model = $request->get('model');
        $userAgent = trim($request->get('user_agent', ''));
        $duplicateLogin = (bool) $request->get('duplicate_login', 0);
        $xForwardedFor = trim($request->get('x_forwarded_for'));
        $lastLoginInterval = $request->get('last_login_interval', 0);
        $verifyParentId = $request->get('verify_parent_id', []);
        $verifyLevel = (bool) $request->get('verify_level', 0);

        // 驗證參數編碼是否為utf8
        $checkParameter = [$ipv6, $deviceName, $brand, $model];
        $validator->validateEncode($checkParameter);

        if (empty($openid)) {
            throw new \InvalidArgumentException('Invalid oauth openid', 150250010);
        }

        if (!$ip) {
            throw new \InvalidArgumentException('No ip specified', 150250005);
        }

        if (!$loginValidator->checkValidEntrance($entrance)) {
            throw new \InvalidArgumentException('Invalid entrance given', 150250001);
        }

        // 若非陣列則強制轉為陣列
        if (!is_array($verifyParentId)) {
            $verifyParentId = [$verifyParentId];
        }

        $oauth = $em->find('BBDurianBundle:Oauth', $oauthId);
        if (empty($oauth)) {
            throw new \InvalidArgumentException('Invalid oauth id', 150250011);
        }

        $domain = $oauth->getDomain();
        $vendorId = $oauth->getVendor()->getId();
        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->getBindingBy($domain, $vendorId, $openid);

        if (empty($binding)) {
            throw new \RuntimeException('User has no oauth binding', 150250012);
        }

        $result = $loginValidator->getOauthLoginResult(
            $binding,
            $entrance,
            $host,
            $lastLoginInterval,
            $verifyParentId,
            $verifyLevel
        );

        // 判斷是否需要回傳導向網址
        $isRedirect = false;

        // 登入結果為登入成功需導向網址，需調整結果為登入成功
        // RESULR_SUCCESS_AND_REDIRECT 僅用來判斷需不需要回傳導向網址
        if ($result == LoginLog::RESULT_SUCCESS_AND_REDIRECT) {
            $result = LoginLog::RESULT_SUCCESS;
            $isRedirect = true;
        }

        $userId = $binding->getUserId();
        $user = $em->find('BBDurianBundle:User', $userId);
        $domain = $user->getDomain();

        // 解析 X-FORWARDED-FOR 資訊
        $proxy = $loginValidator->parseXForwardedFor($xForwardedFor);

        // 解析客戶端資訊
        $clientInfo = $loginValidator->parseClientInfo(
            $clientOs,
            $clientBrowser,
            $ingress,
            $language,
            $userAgent
        );

        // 取得IP來源國家與城市
        $ipBlockRepo = $emShare->getRepository('BBDurianBundle:GeoipBlock');
        $verId = $ipBlockRepo->getCurrentVersion();
        $ipBlock = $ipBlockRepo->getBlockByIpAddress($ip, $verId);

        $country = null;
        $city = null;
        if ($ipBlock) {
            $ipCountry = $emShare->find('BBDurianBundle:GeoipCountry', $ipBlock['country_id']);

            $country = $ipCountry->getzhTwName();
            if (empty($country)) {
                $country = $ipCountry->getCountryCode();
            }

            if ($ipBlock['city_id']) {
                $ipCity = $emShare->find('BBDurianBundle:GeoipCity', $ipBlock['city_id']);

                $city = $ipCity->getZhTwName();
                if (empty($city)) {
                    $city = $ipCity->getCityCode();
                }
            }
        }

        $log = new LoginLog($ip, $domain, $result);
        $log->setAt(new \DateTime('now'));
        $log->setHost($host);
        $log->setLanguage($clientInfo['language']);
        $log->setIpv6($ipv6);
        $log->setClientOs($clientInfo['os']);
        $log->setClientBrowser($clientInfo['browser']);
        $log->setIngress($clientInfo['ingress']);
        $log->setProxy1($proxy[0]);
        $log->setProxy2($proxy[1]);
        $log->setProxy3($proxy[2]);
        $log->setProxy4($proxy[3]);
        $log->setCountry($country);
        $log->setCity($city);
        $log->setEntrance($entrance);
        $log->setUserId($user->getId());
        $log->setUsername($user->getUsername());
        $log->setRole($user->getRole());
        $log->setSub($user->isSub());
        $log->setTest($user->isTest());

        $em->persist($log);

        $output['ret']['login_user'] = array();
        $output['ret']['code'] = null;

        // 若登入成功, 則更新使用者登入時間，並產生一段加密的編碼
        if ($result == LoginLog::RESULT_SUCCESS) {
            $output = $loginValidator->loginSuccess($user, $log, $duplicateLogin, $isRedirect);
            $lastLogin = $em->find('BBDurianBundle:LastLogin', $userId);
        }

        $output['ret']['login_user']['id'] = $user->getId();

        $em->beginTransaction();
        try {
            $em->flush();
            $logMobile = null;

            if ($ingress == 2 || $ingress == 4) {
                $logMobile = new LoginLogMobile($log);
                $logMobile->setName($deviceName);
                $logMobile->setBrand($brand);
                $logMobile->setModel($model);

                $em->persist($logMobile);
            }

            // 為避免 deadlock，最後登入 ip & id 需同時更新
            if ($result == LoginLog::RESULT_SUCCESS) {
                if (!$lastLogin) {
                    $lastLogin = new LastLogin($userId, $ip);
                    $em->persist($lastLogin);
                }

                $lastLogin->setIp($ip);
                $lastLogin->setLoginLogId($log->getId());
            }

            $em->flush();
            $em->commit();

            $redis->lpush('login_log_queue', json_encode($log->getInfo()));

            if ($logMobile) {
                $redis->lpush('login_log_mobile_queue', json_encode($logMobile->getInfo()));
            }
        } catch (\Exception $e) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }

            // 如果出錯，必須將 Session 資料刪除
            if (isset($output['ret']['login_user']['session_id'])) {
                $sessionBroker = $this->get('durian.session_broker');
                $sessionBroker->remove($output['ret']['login_user']['session_id']);
            }

            if (!is_null($e->getPrevious())) {
                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 1062) {
                    $pdoMsg = $e->getMessage();

                    if (strpos($pdoMsg, 'last_login')) {
                        throw new \RuntimeException('Database is busy', 150250030);
                    }
                }
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret']['login_result'] = $result;

        return new JsonResponse($output);
    }

    /**
     * 登出
     *
     * @Route("/logout",
     *        name = "api_logout",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAction(Request $request)
    {
        $request = $request->request;
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            throw new \InvalidArgumentException('No session_id specified', 150250008);
        }

        $sessionBroker = $this->get('durian.session_broker');

        //搜尋是否存在session_id
        if (!$sessionBroker->existsBySessionId($sessionId)) {
            throw new \RuntimeException('Session not found', 150250013);
        }

        //刪除session資料
        $sessionBroker->remove($sessionId);

        $out = ['result' => 'ok'];

        return new JsonResponse($out);
    }

    /**
     * 取得登入記錄列表
     *
     * @Route("/login_log/list",
     *        name = "api_login_log_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLogListAction(Request $request)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');
        $paramHandler = $this->get('durian.parameter_handler');

        $query = $request->query;
        $start = $query->get('start');
        $end = $query->get('end');
        $filterUser = $query->get('filter_user', 1);
        $filter = $query->get('filter', 0);
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        // 檢查時間區間是否有帶入
        if (!$validator->validateDateRange($start, $end)) {
            throw new \InvalidArgumentException('No start or end specified', 150250016);
        }

        $criteria['start'] = $paramHandler->datetimeToYmdHis($start);
        $criteria['end'] = $paramHandler->datetimeToYmdHis($end);
        $criteria['filter_user'] = $filterUser;
        $criteria['filter'] = $filter;

        // 取得排序資訊
        $orderBy = $paramHandler->orderBy($query->get('sort'), $query->get('order'));

        if ($query->has('user_id')) {
            $criteria['userId'] = $query->get('user_id');
        }

        if ($query->has('username')) {
            $criteria['username'] = $query->get('username');
        }

        if ($query->has('ip')) {
            $criteria['ip'] = ip2long($query->get('ip'));
        }

        if ($query->has('domain')) {
            $criteria['domain'] = $query->get('domain');
        }

        $validator->validatePagination($firstResult, $maxResults);

        $repo = $em->getRepository('BBDurianBundle:LoginLog');

        $total = $repo->countBy($criteria);

        $logs = $repo->getListBy(
            $criteria,
            $orderBy,
            $firstResult,
            $maxResults
        );

        $loginLogs = [];
        foreach ($logs as $log) {
            $loginLogs[] = $log->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $loginLogs;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以使用者帳號查詢最後成功登入紀錄
     *
     * @Route("/user/last_login",
     *        name = "api_get_last_login",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLastLoginByUsernameAction(Request $request)
    {
        $query = $request->query;
        $username = trim($query->get('username'));
        $domain = $query->get('domain');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        if (!$username) {
            throw new \InvalidArgumentException('No username specified', 150250004);
        }

        $this->get('durian.validator')->validatePagination($firstResult, $maxResults);

        $limit = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $repo = $this->getEntityManager()->getRepository('BBDurianBundle:LoginLog');
        $repoHis = $this->getEntityManager('his')->getRepository('BBDurianBundle:LoginLog');
        $lastLoginIds = $repo->getLoginLogIdByUsername($username, $limit, $domain);

        $total = 0;
        $lastLogins = [];

        if ($lastLoginIds) {
            $total = $repo->countLastLoginByUsername($username, $domain);
            $lastLogins = $repoHis->getLastLoginByIds($lastLoginIds);
        }

        foreach ($lastLogins as $key => $value) {
            $lastLogins[$key]['last_login'] = $value['at']->format(\DateTime::ISO8601);
            $lastLogins[$key]['sub'] = (bool) $value['sub'];
            $lastLogins[$key]['ip'] = long2ip($value['ip']);
            $lastLogins[$key]['is_slide'] = (bool) $value['is_slide'];
        }

        $output['result'] = 'ok';
        $output['ret'] = $lastLogins;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 用ip和上層使用者取得登入記錄列表
     *
     * @Route("/login_log/list_by_ip_parent",
     *        name = "api_login_log_list_by_ip_parent",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLogListByIpParentAction(Request $request)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');
        $paramHandler = $this->get('durian.parameter_handler');

        $query = $request->query;
        $parentId = $query->get('parent_id');
        $ip = $query->get('ip');
        $start = $query->get('start');
        $end = $query->get('end');
        $filterUser = $query->get('filter_user', 1);
        $firstResult = $query->get('first_result', 0);
        $maxResults = $query->get('max_results', 20);
        $sort = $query->get('sort', ['at']);
        $order = $query->get('order', ['desc']);

        // 檢查時間區間是否有帶入
        if (!$validator->validateDateRange($start, $end)) {
            throw new \InvalidArgumentException('No start or end specified', 150250016);
        }

        if (!$validator->validateIp($ip)) {
            throw new \InvalidArgumentException('Invalid IP', 150250026);
        }

        $validator->validatePagination($firstResult, $maxResults);

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150250027);
        }

        $parent = $em->find('BBDurianBundle:User', $parentId);
        if (!$parent) {
            throw new \RuntimeException('No parent found', 150250028);
        }

        $criteria['start'] = $paramHandler->datetimeToYmdHis($start);
        $criteria['end'] = $paramHandler->datetimeToYmdHis($end);
        $criteria['ip'] = ip2long($query->get('ip'));
        $criteria['parentId'] = $parentId;
        $criteria['filter_user'] = $filterUser;

        // 額外帶入 domain 條件是為了提升語法效能
        $criteria['domain'] = $parent->getDomain();

        // 取得排序資訊
        $orderBy = $paramHandler->orderBy($sort, $order);

        $repo = $em->getRepository('BBDurianBundle:LoginLog');

        $total = $repo->countByIpParent($criteria);
        $logs = $repo->getListByIpParent(
            $criteria,
            $orderBy,
            $firstResult,
            $maxResults
        );

        $loginLogs = [];
        foreach ($logs as $key => $value) {
            $loginLogs[$key] = $value;
            $loginLogs[$key]['at'] = $value['at']->format(\DateTime::ISO8601);
            $loginLogs[$key]['ip'] = long2ip($value['ip']);
        }

        $output['result'] = 'ok';
        $output['ret'] = $loginLogs;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @return \Predis\Client
     */
    private function getRedis()
    {
        return $this->container->get('snc_redis.default');
    }
}
