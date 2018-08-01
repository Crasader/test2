<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DomainBank;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Entity\DomainCurrency;
use BB\DurianBundle\Exception\ShareLimitNotExists;
use BB\DurianBundle\Entity\UserCreatedPerIp;
use BB\DurianBundle\Entity\OauthUserBinding;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Entity\IpBlacklist;
use BB\DurianBundle\Entity\UserPassword;
use BB\DurianBundle\Entity\UserEmail;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\UserLevel;
use BB\DurianBundle\Entity\DomainTotalTest;
use BB\DurianBundle\Entity\Blacklist;
use BB\DurianBundle\Entity\BlacklistOperationLog;
use BB\DurianBundle\Entity\OutsidePayway;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class UserController extends Controller
{
    /**
     * 產生使用者id (即將移除,因case 209442,原採用GET,將改為POST)
     *
     * @Route("/user/id",
     *        name = "api_user_id_generate",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateIdAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $blacklistValidator = $this->get('durian.blacklist_validator');
        $query = $request->query;

        $parentId = $query->get('parent_id');
        $domainId = $query->get('domain');
        $ip = $query->get('client_ip');
        $role = $query->get('role');
        $sub = $query->get('sub', false);
        $verifyIp = $query->get('verify_ip', true);
        $verifyBlacklist = (bool) $query->get('verify_blacklist', 1);
        $ingress = $query->get('ingress', 0);

        //parent role 對應的 son role
        $hierarchyMap = [
            7 => 5,
            5 => 4,
            4 => 3,
            3 => 2,
            2 => 1
        ];

        if (!$role) {
            throw new \InvalidArgumentException('No role specified', 150010057);
        }

        $isDomain = ($role == 7 && !$sub);

        // 若不是要產生domain id,則一定要帶domain值
        if (!$domainId && !$isDomain) {
            throw new \InvalidArgumentException('No domain specified', 150010100);
        }

        if (!$ip) {
            throw new \InvalidArgumentException('No client_ip specified', 150010092);
        }

        if (!$isDomain && !$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150010036);
        }

        if ($isDomain && $parentId) {
            throw new \RuntimeException('Domain shall not have parent', 150010058);
        }

        $criteriaBlacklist = ['ip' => $ip];

        // 手機登入不檢查系統封鎖 ip 黑名單
        if ($ingress == 2 || $ingress == 4) {
            $criteriaBlacklist['system_lock'] = false;
        }

        // 會員預設檢查ip黑名單
        if ($verifyBlacklist && $role == 1) {
            $repo = $emShare->getRepository('BBDurianBundle:Blacklist');
            $blacklist = $repo->getBlacklistSingleBy($criteriaBlacklist, $domainId);

            if ($blacklist) {
                throw new \RuntimeException('Cannot create user when ip is in blacklist', 150010142);
            }
        }

        $isBlock = false;

        // 若verifyIp為false或是非會員,則不檢查是否阻擋
        if ($verifyIp && $role == 1) {
            // 檢查domain是否要阻擋該ip新增使用者;預設為不阻擋ip
            $config = $emShare->find('BBDurianBundle:DomainConfig', $domainId);

            // domain設定需阻擋新增使用者,則檢查ip是否在IP封鎖列表中
            if ($config && $config->isBlockCreateUser()) {
                $repo = $emShare->getRepository('BBDurianBundle:IpBlacklist');
                $isBlock = $repo->isBlockCreateUser($domainId, $ip);
            }
        }

        if ($isBlock) {
            throw new \RuntimeException('Cannot create user when ip is in ip blacklist', 150010091);
        }

        if ($parentId) {
            $parent = $em->find('BBDurianBundle:User', $parentId);
            if (!$parent) {
                throw new \RuntimeException('No parent found', 150010023);
            }

            $parentRole = $parent->getRole();
            if ($parentRole == 1) {
                throw new \RuntimeException('Parent can not be member', 150010063);
            }
        }

        if ($sub && $role != $parentRole) {
            throw new \InvalidArgumentException('Invalid role', 150010064);
        }

        if (!$sub) {
            if ($role != 7 && $role != $hierarchyMap[$parentRole]) {
                throw new \InvalidArgumentException('Invalid role', 150010064);
            }
        }

        $userId = null;
        if ($isDomain) {
            $userId = $this->get('durian.domain_id_generator')->generate();
        }

        if (!$userId) {
            $userId = $this->get('durian.user_id_generator')->generate();
        }

        $output['ret']['user_id'] = $userId;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 產生使用者id
     *
     * @Route("/user/id",
     *        name = "api_user_generate_id",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateUserIdAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $blacklistValidator = $this->get('durian.blacklist_validator');

        $request = $request->request;

        $parentId = $request->get('parent_id');
        $domainId = $request->get('domain');
        $ip = $request->get('client_ip');
        $role = $request->get('role');
        $sub = $request->get('sub', false);
        $verifyIp = $request->get('verify_ip', true);
        $verifyBlacklist = (bool) $request->get('verify_blacklist', 1);
        $ingress = $request->get('ingress', 0);

        //parent role 對應的 son role
        $hierarchyMap = [
            7 => 5,
            5 => 4,
            4 => 3,
            3 => 2,
            2 => 1
        ];

        if (!$role) {
            throw new \InvalidArgumentException('No role specified', 150010163);
        }

        $isDomain = ($role == 7 && !$sub);

        // 若不是要產生domain id,則一定要帶domain值
        if (!$domainId && !$isDomain) {
            throw new \InvalidArgumentException('No domain specified', 150010164);
        }

        if (!$ip) {
            throw new \InvalidArgumentException('No client_ip specified', 150010165);
        }

        if (!$isDomain && !$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150010166);
        }

        if ($isDomain && $parentId) {
            throw new \RuntimeException('Domain shall not have parent', 150010167);
        }

        $criteriaBlacklist = ['ip' => $ip];

        // 手機登入不檢查系統封鎖 ip 黑名單
        if ($ingress == 2 || $ingress == 4) {
            $criteriaBlacklist['system_lock'] = false;
        }

        // 會員預設檢查ip黑名單
        if ($verifyBlacklist && $role == 1) {
            $repo = $emShare->getRepository('BBDurianBundle:Blacklist');
            $blacklist = $repo->getBlacklistSingleBy($criteriaBlacklist, $domainId);

            if ($blacklist) {
                throw new \RuntimeException('Cannot create user when ip is in blacklist', 150010142);
            }
        }

        $isBlock = false;

        // 若verifyIp為false或是非會員,則不檢查是否阻擋
        if ($verifyIp && $role == 1) {
            // 檢查domain是否要阻擋該ip新增使用者;預設為不阻擋ip
            $config = $emShare->find('BBDurianBundle:DomainConfig', $domainId);

            // domain設定需阻擋新增使用者,則檢查ip是否在IP封鎖列表中
            if ($config && $config->isBlockCreateUser()) {
                $repo = $emShare->getRepository('BBDurianBundle:IpBlacklist');
                $isBlock = $repo->isBlockCreateUser($domainId, $ip);
            }
        }

        if ($isBlock) {
            throw new \RuntimeException('Cannot create user when ip is in ip blacklist', 150010168);
        }

        if ($parentId) {
            $parent = $em->find('BBDurianBundle:User', $parentId);
            if (!$parent) {
                throw new \RuntimeException('No parent found', 150010169);
            }

            $parentRole = $parent->getRole();
            if ($parentRole == 1) {
                throw new \RuntimeException('Parent can not be member', 150010170);
            }
        }

        if ($sub && $role != $parentRole) {
            throw new \InvalidArgumentException('Invalid role', 150010171);
        }

        if (!$sub) {
            if ($role != 7 && $role != $hierarchyMap[$parentRole]) {
                throw new \InvalidArgumentException('Invalid role', 150010171);
            }
        }

        $userId = null;
        if ($isDomain) {
            $userId = $this->get('durian.domain_id_generator')->generate();
        }

        if (!$userId) {
            $userId = $this->get('durian.user_id_generator')->generate();
        }

        $output['ret']['user_id'] = $userId;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 新增使用者
     *
     * @Route("/user",
     *        name = "api_user_create",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $validator = $this->get('durian.validator');
        $userValidator = $this->get('durian.user_validator');
        $currencyOperator = $this->get('durian.currency');
        $domainValidator = $this->get('durian.domain_validator');
        $redis = $this->get('snc_redis.map');
        $parameterHandler = $this->get('durian.parameter_handler');
        $blacklistValidator = $this->get('durian.blacklist_validator');

        $oriRequest = $request;
        $request = $request->request;
        $parentId = $request->get('parent_id');
        $userId = $request->get('user_id');
        $role = $request->get('role');
        $username = trim($request->get('username'));
        $name = trim($request->get('name'));
        $loginCode = $request->get('login_code');
        $disabledPassword = $request->get('disabled_password', false);
        $password = $request->get('password');
        $oauthVendorId = $request->get('oauth_vendor_id');
        $oauthOpenid = $request->get('oauth_openid');
        $alias = trim($request->get('alias', ''));
        $currency = $request->get('currency', 'CNY');
        $sub = $request->get('sub', false);
        $enable = $request->get('enable', true);
        $block = $request->get('block', false);
        $test = $request->get('test', false);
        $hiddenTest = $request->get('hidden_test', false);
        $rent = $request->get('rent', false);
        $passwordReset = $request->get('password_reset', false);
        $clientIp = trim($request->get('client_ip'));
        $verifyBlacklist = $request->getBoolean('verify_blacklist', 1);
        $ingress = $request->get('ingress', 0);
        $verifyIp = $request->get('verify_ip', true);
        $parameter = $request->get('user_detail', []);
        $entrance = $request->get('entrance');

        $validator->validateEncode($alias);
        $userValidator->validateUsername($username);
        $userValidator->validateAlias($alias);

        // 如果沒有設密碼停用, 或是沒綁定oauth帳號，則需要驗證密碼
        if (!$disabledPassword && !$oauthVendorId) {
            $userValidator->validatePassword($password);
        }

        //若有設密碼停用, 或綁定oauth帳號，則password設為空字串
        if ($disabledPassword || $oauthVendorId) {
            $password = '';
        }

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150010101);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);

        if (empty($role)) {
            throw new \InvalidArgumentException('No role specified', 150010057);
        }

        //是否為廳主
        $isDomain = ($role == 7 && !$sub);

        if (!$isDomain && !$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150010036);
        }

        //新增廳主必定要新增登入代碼
        if ($isDomain) {
            $domainValidator->validateLoginCode($loginCode);
        }

        if ($userId && !$validator->isInt($userId)) {
            throw new \InvalidArgumentException('Invalid user_id', 150010055);
        }

        if ($parentId) {
            $parent = $em->find('BBDurianBundle:User', $parentId);
            if (!$parent) {
                throw new \RuntimeException('No parent found', 150010023);
            }

            $parentRole = $parent->getRole();
            if ($parentRole == 1) {
                throw new \RuntimeException('Parent can not be member', 150010063);
            }
        }

        if ($sub == 0) {
            //parent role 對應的 son role
            $hierarchyMap = [
                7 => 5,
                5 => 4,
                4 => 3,
                3 => 2,
                2 => 1
            ];

            if ($role != 7 && $role != $hierarchyMap[$parentRole]) {
                throw new \InvalidArgumentException('Invalid role', 150010064);
            }

            //ATTENTION：此處為廳主為最上層的邏輯，若有日後新增大廳主則需修改 by Thor
            if ($role == 7 && isset($parent)) {
                throw new \RuntimeException('Domain shall not have parent', 150010058);
            }

            //新增廳主必定要新增廳名與登入代碼，並檢查是否重覆
            if ($role == 7) {
                if (!$name) {
                    throw new \InvalidArgumentException('No domain name specified', 150010140);
                }

                //檢查login_code是否重覆
                $dcRepo = $emShare->getRepository('BBDurianBundle:DomainConfig');
                $dcResult = $dcRepo->findOneBy(['loginCode' => $loginCode]);
                if ($dcResult) {
                    throw new \InvalidArgumentException('Invalid login code', 150010081);
                }

                $domainValidator->validateName($name);
            }
        }

        if ($sub && $role != $parentRole) {
            throw new \InvalidArgumentException('Invalid role', 150010064);
        }

        if ($oauthVendorId) {
            $oauthVendor = $em->find('BBDurianBundle:OauthVendor', $oauthVendorId);
            if (empty($oauthVendor)) {
                throw new \InvalidArgumentException('Invalid oauth vendor', 150010104);
            }

            if (empty($oauthOpenid)) {
                throw new \InvalidArgumentException('Invalid oauth openid', 150010103);
            }

            // 驗證參數編碼是否為utf8
            $validator->validateEncode($oauthOpenid);
        }

        $activateSLNext = $this->get('durian.activate_sl_next');
        $curDate = new \DateTime('now');

        if ($activateSLNext->isUpdating($curDate)) {
            throw new \RuntimeException('Cannot perform this during updating sharelimit', 150010107);
        }

        if (!$activateSLNext->hasBeenUpdated($curDate)) {
            throw new \RuntimeException(
                'Cannot perform this due to updating sharelimit is not performed for too long time',
                150010105
            );
        }

        $uid = null;
        //新增使用者但未指定userId或為空字串
        if (!$userId) {
            if ($isDomain) {
                $uid = $this->get('durian.domain_id_generator')->generate();
            }

            if (!$uid) {
                $uid = $this->get('durian.user_id_generator')->generate();
            }
        }

        //新增使用者且指定userId
        if ($userId) {
            $userExist = $em->find('BBDurianBundle:User', $userId);
            if ($userExist) {
                throw new \InvalidArgumentException('Invalid user_id', 150010055);
            }

            if (!$isDomain) {
                if ($userId < 20000000) {
                    throw new \InvalidArgumentException('Invalid user_id', 150010055);
                }

                $userSequence = $this->get('durian.user_id_generator')->getCurrentId();
                if ($userId > $userSequence) {
                    throw new \RuntimeException('Not a generated user_id', 150010059);
                }
            }

            if ($isDomain && $userId > 20000000) {
                throw new \InvalidArgumentException('Invalid user_id', 150010055);
            }

            $uid = $userId;
        }

        //上層為空則該userId為domain Id , 若上層不為空則抓其上層的domain
        if ($parentId) {
            $domain = $parent->getDomain();
            $userValidator->validateUniqueUsername($username, $domain);
        } else {
            $domain = $uid;
        }

        //驗證黑名單
        if ($verifyBlacklist && $role == 1) {
            $criteria = $parameter;

            if ($clientIp) {
                $criteria['ip'] = $clientIp;
            }

            // 手機登入不檢查系統封鎖 ip 黑名單
            if ($ingress == 2 || $ingress == 4) {
                $criteria['system_lock'] = false;
            }

            $blacklistValidator->validate($criteria, $domain);
        }

        $isBlock = false;

        // 若verifyIp為false或是非會員,則不檢查是否阻擋
        if ($verifyIp && $role == 1) {
            // 檢查domain是否要阻擋該ip新增使用者;預設為不阻擋ip
            $config = $emShare->find('BBDurianBundle:DomainConfig', $domain);

            // domain設定需阻擋新增使用者,則檢查ip是否在IP封鎖列表中
            if ($config && $config->isBlockCreateUser()) {
                $repo = $emShare->getRepository('BBDurianBundle:IpBlacklist');
                $isBlock = $repo->isBlockCreateUser($domain, $clientIp);
            }
        }

        if ($isBlock) {
            throw new \RuntimeException('Cannot create user when ip is in ip blacklist', 150010172);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $userParameters = [
                'uid' => $uid,
                'username' => $username,
                'domain' => $domain,
                'alias' => $alias,
                'sub' => $sub,
                'enable' => $enable,
                'block' => $block,
                'disabled_password' => $disabledPassword,
                'password' => $password,
                'test' => $test,
                'currency' => $currency,
                'rent' => $rent,
                'password_reset' => $passwordReset,
                'role' => $role,
                'currency_num' => $currencyNum,
                'hidden_test' => $hiddenTest
            ];

            if (isset($entrance)) {
                $userParameters['entrance'] = $entrance;
            }

            if (isset($parent)) {
                $userParameters['parent'] = $parent;
            }

            $userParameters = $this->newUser($userParameters);
            $user = $userParameters['user'];
            $test = $userParameters['test'];
            $hiddenTest = $userParameters['hidden_test'];

            if ($isDomain) {
                $this->generateDomainCurrency($user);
            }

            if ($oauthVendorId) {
                $this->generateOauthUserBinding($uid, $oauthVendor, $oauthOpenid, $domain);
            }

            if (!$sub) {
                // 用來記錄此使用者支援的交易方式
                $availableWays = [
                    'cash' => false,
                    'cash_fake' => false,
                    'credit' => false,
                    'outside' => false
                ];

                // 現金
                if ($request->has('cash')) {
                    $cData = $request->get('cash');
                    $this->generateCash($user, $cData, $isDomain);
                    $availableWays['cash'] = true;
                }

                // 假現金
                if ($request->has('cash_fake')) {
                    // 太陽城要原廳(cashFake)轉移成outside，配合封測也會在原廳封測
                    // 但因為該廳要維持營運，封測不能直接將cash_fake改為outside，因此在建立會員時需做特例處理
                    // 這邊建立會員時如果帶入的代理為封測用體系(outside)則不建立cash_fake，改為outside
                    // 太陽城封測完後，該廳已經調整為outside後需移除這段特例
                    $suncityAgent = $this->container->getParameter('suncity_agent');

                    if (in_array($parentId, $suncityAgent)) {
                        $paywayOp = $this->get('durian.user_payway');
                        $paywayOp->isParentEnabled($user, ['outside' => true]);
                        $availableWays['outside'] = true;
                    } else {
                        $transactionIdArray = $this->generateCashFake($user, $request->get('cash_fake'));
                        $availableWays['cash_fake'] = true;
                    }
                }

                // 信用額度
                if ($request->has('credit')) {
                    $credits = $this->generateCredit($user, $request->get('credit'));
                    $availableWays['credit'] = true;
                }

                // 外接額度
                if ($request->getBoolean('outside')) {
                    if ($user->hasParent()) {
                        $paywayOp = $this->get('durian.user_payway');
                        $paywayOp->isParentEnabled($user, ['outside' => true]);
                    }
                    $availableWays['outside'] = true;
                }

                if (isset($loginCode) && isset($name) && $role == 7) {
                    $validator->validateEncode($loginCode);
                    $loginCode = $parameterHandler->filterSpecialChar($loginCode);
                    $validator->validateEncode($name);
                    $name = $parameterHandler->filterSpecialChar($name);

                    $domainConfig = new DomainConfig($user, $name, $loginCode);
                    $emShare->persist($domainConfig);

                    $totalTest = new DomainTotalTest($user->getId());
                    $em->persist($totalTest);

                    // 若是新增廳主且是外接額度則須新增外接額度資料
                    if ($availableWays['outside']) {
                        $outsidePayway = new OutsidePayway($user->getId());
                        $em->persist($outsidePayway);
                    }
                }

                // 會員無佔成
                if ($role != 1) {
                    $this->generateShareLimit($request, $user);

                    //為了讓預改佔成抓取現行佔成的ID 所以要先flush
                    $em->flush();
                    $emShare->flush();
                    $this->generateShareLimitNext($request, $user);

                    // updateMinMax前要先flush
                    $em->flush();
                    $emShare->flush();
                    $this->get('durian.share_scheduled_for_update')->execute();
                }

                // 記錄使用者支援的交易方式，若與上層相同則不建立
                if ($availableWays) {
                    $this->generateUserPayway($user, $availableWays);
                }
            }

            if ($test && !$hiddenTest && $role == 1) {
                $this->processDomainTotalTest($domain, 1);
            }

            // 新增使用者異常，則加入封鎖列表，與判斷是否加入黑名單
            $createBlacklist = $this->generateBlackListIfAbnormal($clientIp, $user);

            // 處理新增使用者統計
            $this->processUserCreatedPerIp($user, $clientIp);

            // 使用者密碼以加密方式儲存
            $this->generateUserPassword($user);

            // 新增UserDetail
            $detail = $this->generateUserDetail($user, $parameter);
            $detailData = $detail->toArray();

            // 新增UserEmail
            $userEmail = $this->generateUserEmail($user, $parameter);

            $queueIndex = [];

            // 新增現金會員的會員層級設定
            if ($role == 1 && isset($cData['currency'])) {
                $queueIndex = $this->generateUserLevel($user, $cData['currency']);
            }

            $em->flush();
            $emShare->flush();

            if ($createBlacklist) {
                $blacklistLog = new BlacklistOperationLog($createBlacklist->getId());
                $blacklistLog->setCreatedOperator('system');
                $blacklistLog->setNote('註冊使用者超過限制');

                $emShare->persist($blacklistLog);
                $emShare->flush();
            }

            // 加入redis對應表
            $um = $this->get('durian.user_manager');
            $domainKey = $um->getKey($uid, 'domain');
            $usernameKey = $um->getKey($uid, 'username');

            $map = [
                $domainKey => $user->getDomain(),
                $usernameKey => $username
            ];

            $redis->mset($map);

            // 整理需更新使用者下層計數
            if ($sub == 0 && $parentId) {
                $queueIndex['user'] = $parentId;
            }

            $this->pushUpdateQueue('create', $queueIndex);

            $em->commit();
            $emShare->commit();

            // 先讓 $em->commit() 確保進資料庫沒有問題，接著再進行 cash_fake commit
            if (isset($transactionIdArray['user'])) {
                $this->get('durian.cashfake_op')->transactionCommit($transactionIdArray['user']);
            }

            if (isset($transactionIdArray['parent'])) {
                $this->get('durian.cashfake_op')->transactionCommit($transactionIdArray['parent']);
            }

            $output['result'] = 'ok';
            $output['ret'] = $this->getUserInfo($user);
            $output['ret']['user_detail'] = $detailData;
            //因移除了detail身上的email欄位，但為了不影響原本的輸出，所以detail的email從user_email取得
            $output['ret']['user_detail']['email'] = $userEmail->getEmail();
        } catch (\Exception $e) {
            if (isset($credits)) {
                $this->rollbackCreditTotalLine($credits, $request->get('credit'));
            }

            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();

                if (isset($domainKey)) {
                    $redis->del($domainKey);
                    $redis->del($usernameKey);
                }
            }

            if ($emShare->getConnection()->isTransactionActive()) {
                $emShare->rollback();
            }

            if (isset($transactionIdArray['user'])) {
                $this->get('durian.cashfake_op')->transactionRollback($transactionIdArray['user']);
            }

            if (isset($transactionIdArray['parent'])) {
                $this->get('durian.cashfake_op')->transactionRollback($transactionIdArray['parent']);
            }

            $locale = $oriRequest->getPreferredLanguage();
            $this->get('translator')->setLocale($locale);

            $output['code'] = $e->getCode();
            $msg = $e->getMessage();

            //DBALException內部BUG,並判斷是否為Duplicate entry 跟 deadlock
            if (!is_null($e->getPrevious())) {
                $output['code'] = $e->getPrevious()->getCode();
                $msg = $e->getPrevious()->errorInfo[2];

                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 1062) {
                    $pdoMsg = $e->getMessage();

                    if (strpos($pdoMsg, 'uni_username_domain')) {
                        $output['code'] = 150010014;
                        $msg = 'Username already exist';
                    }
                    /*
                     * 隱藏阻擋同分秒同廳同IP新增的狀況，
                     * 改以不同error code區別 Database is busy錯誤訊息狀況
                     */
                    if (strpos($pdoMsg, 'uni_ip_at_domain') &&  strpos($pdoMsg, 'user_created_per_ip')) {
                        $output['code'] = 150010071;
                        $msg = 'Database is busy';
                    }

                    /**
                     * 隱藏阻擋同分秒加入黑名單的狀況，
                     * 改以不同error code區別 Database is busy錯誤訊息狀況
                     */
                    if (strpos($pdoMsg, 'uni_blacklist_domain_ip')) {
                        $output['code'] = 150010143;
                        $msg = 'Database is busy';
                    }

                    /**
                     * 隱藏阻擋同分秒加入封鎖列表的狀況，
                     * 改以不同error code區別 Database is busy錯誤訊息狀況
                     */
                    if (strpos($pdoMsg, 'uni_ip_blacklist_domain_ip_created_date')) {
                        $output['code'] = 150010162;
                        $msg = 'Database is busy';
                    }
                }

                if ($e->getPrevious()->getCode() == 40001 && $e->getPrevious()->errorInfo[1] == 1213) {
                    $output['code'] = 150010066;
                    $msg = 'Database is busy';
                }

                //Redis的user_sequence出錯的情況
                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 19) {
                    $msg = 'PRIMARY KEY must be unique';
                }
            }

            $output['result'] = 'error';
            $output['msg'] = $this->get('translator')->trans($msg);
        }

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 刪除使用者
     *
     * @Route("/user/{userId}",
     *        name = "api_user_remove",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @param integer $userId 使用者ID
     * @return JsonResponse
     */
    public function removeAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $operator = trim($request->get('operator'));

        // 驗證參數編碼是否為utf8
        $validator->validateEncode($operator);

        // 確認有使用者後，其餘檢查連線調整至slave
        $user = $this->findUser($userId, true);
        $em->getConnection()->connect('slave');

        $activateSLNext = $this->get('durian.activate_sl_next');
        $curDate = new \DateTime('now');

        $log = $operationLogger->create('user', ['id' => $userId]);
        $log->addMessage('username', $user->getUserName());

        $removedUserExist = $emShare->find('BBDurianBundle:RemovedUser', $userId);
        if ($removedUserExist) {
            throw new \RuntimeException('User id already exists in the removed user list', 150010090);
        }

        if ($activateSLNext->isUpdating($curDate)) {
            throw new \RuntimeException('Cannot perform this during updating sharelimit', 150010107);
        }

        if (!$activateSLNext->hasBeenUpdated($curDate)) {
            throw new \RuntimeException(
                'Cannot perform this due to updating sharelimit is not performed for too long time',
                150010105
            );
        }

        $sensitiveLogger->validateAllowedOperator($user);

        // 刪帳號時連同子帳號一起刪掉
        $criteria = [
            'parent' => $user->getId(),
            'sub' => 1
        ];

        $subUsers = $em->getRepository('BBDurianBundle:User')
            ->findBy($criteria);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach ($subUsers as $subUser) {
                $this->get('durian.user_manager')->remove($subUser);
            }
            $em->flush();
            $emShare->flush();

            $queueIndex = $this->get('durian.user_manager')->remove($user, $operator);

            $this->pushUpdateQueue('remove', $queueIndex);

            $em->flush();
            $emShare->flush();

            $this->get('durian.share_scheduled_for_update')->execute();

            $operationLogger->save($log);
            $emShare->flush();

            $em->commit();
            $emShare->commit();

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }

            if ($emShare->getConnection()->isTransactionActive()) {
                $emShare->rollback();
            }

            throw $e;
        }

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 回復使用者
     *
     * @Route("/user/{userId}/recover",
     *        name = "api_user_recover",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者ID
     * @return JsonResponse
     */
    public function recoverAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->get('snc_redis.map');
        $userManager = $this->get('durian.user_manager');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $operationLogger = $this->get('durian.operation_logger');
        $currencyOperator = $this->get('durian.currency');
        $request = $request->request;
        $domain = $request->get('domain', null);

        // 必填參數 domain
        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150010151);
        }

        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', $userId);

        if (!$removedUser) {
            throw new \RuntimeException('No such removed user', 150010152);
        }

        if ($domain != $removedUser->getDomain()) {
            throw new \RuntimeException('No such removed user', 150010152);
        }

        // username已再次被使用 不能回復
        $username = $removedUser->getUsername();
        $existingUser = $em->getRepository('BBDurianBundle:User')
            ->findOneBy([
                'username' => $username,
                'domain'   => $domain
            ]);

        if ($existingUser) {
            throw new \RuntimeException('Can not recover user when its username already used by another', 150010153);
        }

        // 沒有上層 不能回復
        $parentId = $removedUser->getParentId();
        $parent = $em->find('BBDurianBundle:User', $parentId);

        if (!$parent) {
            throw new \RuntimeException('Can not recover user when its parent not exists', 150010154);
        }

        $activateSLNext = $this->get('durian.activate_sl_next');
        $curDate = new \DateTime('now');

        if ($activateSLNext->isUpdating($curDate)) {
            throw new \RuntimeException('Cannot perform this during updating sharelimit', 150010155);
        }

        if (!$activateSLNext->hasBeenUpdated($curDate)) {
            throw new \RuntimeException(
                'Cannot perform this due to updating sharelimit is not performed for too long time',
                150010156
            );
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $sensitiveLogger->validateAllowedOperator($removedUser);

            // 回復 User
            $user = new User();
            $user->setFromRemoved($removedUser);
            $userManager->setParent($user, $parent);
            $em->persist($user);

            $this->get('durian.ancestor_manager')->generateAncestor($user);

            $currency = $currencyOperator->getMappedCode($user->getCurrency());
            $role = $user->getRole();

            // 回復使用者操作記錄
            $userLog = $operationLogger->create('user', ['id' => $userId]);
            $userLog->addMessage('username', $username);
            $userLog->addMessage('domain', $domain);
            $userLog->addMessage('alisas', $user->getAlias());
            $userLog->addMessage('sub', var_export($user->isSub(), true));
            $userLog->addMessage('enable', var_export($user->isEnabled(), true));
            $userLog->addMessage('block', var_export($user->isBlock(), true));
            $userLog->addMessage('password', 'recover');
            $userLog->addMessage('test', var_export($user->isTest(), true));
            $userLog->addMessage('currency', $currency);
            $userLog->addMessage('rent', var_export($user->isRent(), true));
            $userLog->addMessage('password_reset', var_export($user->isPasswordReset(), true));
            $userLog->addMessage('role', $role);
            $operationLogger->save($userLog);

            // 回復 UserDetail
            $removedUD = $emShare->find('BBDurianBundle:RemovedUserDetail', $removedUser);
            $detail = new UserDetail($user);
            $detail->setFromRemoved($removedUD);
            $em->persist($detail);
            $emShare->remove($removedUD);

            // 回復使用者詳細資料操作記錄
            $detailData = $detail->toArray();
            $detailLog = $operationLogger->create('user_detail', ['user_id' => $userId]);
            $detailLog->addMessage('nickname', $detailData['nickname']);
            $detailLog->addMessage('name_real', $detailData['name_real']);
            $detailLog->addMessage('name_chinese', $detailData['name_chinese']);
            $detailLog->addMessage('name_english', $detailData['name_english']);
            $detailLog->addMessage('country', $detailData['country']);
            $detailLog->addMessage('passport', $detailData['passport']);
            $detailLog->addMessage('identity_card', $detailData['identity_card']);
            $detailLog->addMessage('driver_license', $detailData['driver_license']);
            $detailLog->addMessage('insurance_card', $detailData['insurance_card']);
            $detailLog->addMessage('health_card', $detailData['health_card']);
            $detailLog->addMessage('birthday', $detailData['birthday']);
            $detailLog->addMessage('telephone', $detailData['telephone']);
            $detailLog->addMessage('password', $detailData['password']);
            $detailLog->addMessage('qq_num', $detailData['qq_num']);
            $detailLog->addMessage('note', $detailData['note']);
            $operationLogger->save($detailLog);

            // 回復 UserEmail
            $removedEmail = $emShare->find('BBDurianBundle:RemovedUserEmail', $removedUser);
            $userEmail = new UserEmail($user);
            $userEmail->setFromRemoved($removedEmail);
            $em->persist($userEmail);
            $emShare->remove($removedEmail);

            // 回復使用者信箱操作記錄
            $email = $userEmail->getEmail();
            $emailLog = $operationLogger->create('user_email', ['user_id' => $userId]);
            $emailLog->addMessage('email', $email);
            $operationLogger->save($emailLog);

            // 回復 UserPassword
            $removedPassword = $emShare->find('BBDurianBundle:RemovedUserPassword', $removedUser);
            $password = new UserPassword($user);
            $password->setFromRemoved($removedPassword);
            $em->persist($password);
            $emShare->remove($removedPassword);

            // 新增使用者密碼操作紀錄
            $passwordLog = $operationLogger->create('user_password', ['user_id' => $userId]);
            $passwordLog->addMessage('hash', 'recover');
            $operationLogger->save($passwordLog);

            $parentPayway = $em->getRepository('BBDurianBundle:UserPayway')
                ->getUserPayway($parent);
            $payway = [
                'cash' => false,
                'cash_fake' => false,
                'credit' => false
            ];

            $em->flush();

            // 回復 Cash
            $removedCash = $removedUser->getRemovedCash();
            if ($removedCash) {
                if ($parentPayway->isCashEnabled()) {
                    $em->getRepository('BBDurianBundle:Cash')->recoverRemovedCash($removedCash);
                    $payway['cash'] = true;
                    $currencyCode = $currencyOperator->getMappedCode($removedCash->getCurrency());

                    // 新增現金操作紀錄
                    $cashLog = $operationLogger->create('cash', ['user_id' => $userId]);
                    $cashLog->addMessage('currency', $currencyCode);
                    $operationLogger->save($cashLog);
                }

                $emShare->remove($removedCash);
            }

            // 回復 CashFake
            $removedCashFake = $removedUser->getRemovedCashFake();
            if ($removedCashFake) {
                if ($parentPayway->isCashFakeEnabled()) {
                    $em->getRepository('BBDurianBundle:CashFake')->recoverRemovedCashFake($removedCashFake);
                    $payway['cash_fake'] = true;
                    $currencyCode = $currencyOperator->getMappedCode($removedCashFake->getCurrency());

                    // 新增假現金操作紀錄
                    $cashFakeLog = $operationLogger->create('cashFake', ['user_id' => $userId]);
                    $cashFakeLog->addMessage('currency', $currencyCode);
                    $operationLogger->save($cashFakeLog);
                }

                $emShare->remove($removedCashFake);
            }

            // 回復 Credits
            $removedCredits = $removedUser->getRemovedCredits();
            if ($removedCredits && $parentPayway->isCreditEnabled()) {
                $payway['credit'] = true;
            }

            foreach ($removedCredits as $removedCredit) {
                $group = $removedCredit->getGroupNum();

                if ($user->getParent()->getCredit($group)) {
                    $credit = new Credit($user, $group);
                    $credit->setId($removedCredit);
                    $metadata = $em->getClassMetaData('BBDurianBundle:Credit');
                    $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                    $em->persist($credit);

                    // 新增信用額度操作紀錄
                    $creditLog = $operationLogger->create('credit', ['user_id' => $userId]);
                    $creditLog->addMessage('group_num', $group);
                    $creditLog->addMessage('line', 0);
                    $operationLogger->save($creditLog);
                }

                $emShare->remove($removedCredit);
            }

            // 回復 Card
            $removedCard = $removedUser->getRemovedCard();
            if ($removedCard) {
                $cardOp = $this->get('durian.card_operator');

                if ($user->isRent() || $cardOp->checkParentIsRent($user)) {
                    $card = new Card($user);
                    $card->setId($removedCard);
                    $metadata = $em->getClassMetaData('BBDurianBundle:Card');
                    $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                    $em->persist($card);
                    $cardOp->enable($card);

                    // 新增租卡操作紀錄
                    $cardLog = $operationLogger->create('card', ['user_id' => $userId]);
                    $cardLog->addMessage('enable', 'false', 'true');
                    $operationLogger->save($cardLog);
                }

                $emShare->remove($removedCard);
            }

            // 記錄使用者支援的交易方式，若與上層相同則不建立
            $this->generateUserPayway($user, $payway);

            $queueIndex = [];

            // 新增現金會員的會員層級設定
            if ($role == 1 && isset($currencyCode)) {
                $queueIndex = $this->generateUserLevel($user, $currencyCode);
            }

            $emShare->remove($removedUser);

            $em->flush();
            $emShare->flush();

            // 加入redis對應表
            $domainKey = $userManager->getKey($userId, 'domain');
            $usernameKey = $userManager->getKey($userId, 'username');

            $map = [
                $domainKey => $domain,
                $usernameKey => $username
            ];
            $redis->mset($map);

            // 整理需更新使用者下層計數
            if (!$user->isSub()) {
                $queueIndex['user'] = $parentId;
            }

            $this->pushUpdateQueue('recover', $queueIndex);

            $output['result'] = 'ok';
            $output['ret'] = $this->getUserInfo($user);
            $output['ret']['user_detail'] = $detailData;
            $output['ret']['user_detail']['email'] = $email;

            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();

                if (isset($domainKey)) {
                    $redis->del($domainKey);
                    $redis->del($usernameKey);
                }
            }

            if ($emShare->getConnection()->isTransactionActive()) {
                $emShare->rollback();
            }

            throw $e;
        }

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 啟用使用者
     *
     * @Route("/user/{userId}/enable",
     *        name = "api_user_enable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function enableAction($userId)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);

        //若$user->isEnabled()為false才紀錄
        if (!$user->isEnabled()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('enable', var_export($user->isEnabled(), true), 'true');
            $operationLogger->save($log);
            $user->setModifiedAt($now);
        }

        $user->enable();
        $em->getRepository('BB\DurianBundle\Entity\User')
           ->enableAllSub($user);

        $em->flush();
        $emShare->flush();

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $userId);

        if ($domainConfig) {
            $domainConfig->enable();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 停用使用者
     *
     * @Route("/user/{userId}/disable",
     *        name = "api_user_disable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function disableAction($userId)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $user = $this->findUser($userId);

        if ($user->isSub()) {
            throw new \RuntimeException('Sub user can not be disabled', 150010052);
        }

        $sensitiveLogger->validateAllowedOperator($user);

        //若$user->isEnabled()為true才紀錄
        if ($user->isEnabled()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('enable', var_export($user->isEnabled(), true), 'false');
            $operationLogger->save($log);
            $user->setModifiedAt($now);
        }

        $disableNumber = $em->getRepository('BBDurianBundle:User')
            ->countEnabledChildOfUser($user);

        //停用的下層使用者數量不可以超過一萬
        if ($disableNumber > 10000) {
            throw new \RuntimeException(
                'Can not disable more than 10000 users',
                150010089
            );
        }

        $em->getRepository('BB\DurianBundle\Entity\User')
           ->disableAllChild($user);

        $user->disable();

        $em->flush();
        $emShare->flush();

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $userId);

        if ($domainConfig) {
            $domainConfig->disable();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 凍結使用者
     *
     * @Route("/user/{userId}/block",
     *        name = "api_user_block",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function blockAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);

        //若$user->isBlock()為false才紀錄
        if (!$user->isBlock()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('block', var_export($user->isBlock(), true), 'true');
            $operationLogger->save($log);
            $user->setModifiedAt($now);
        }

        $user->block();

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 解凍使用者
     *
     * @Route("/user/{userId}/unblock",
     *        name = "api_user_unblock",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function unblockAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);
        $sensitiveLogger->validateAllowedOperator($user);

        //若$user->isBlock()為true才紀錄
        if ($user->isBlock()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('block', var_export($user->isBlock(), true), 'false');
            $operationLogger->save($log);
            $user->setModifiedAt($now);

            if ($userPassword) {
                $userPassword->zeroErrNum();
                $userPassword->setModifiedAt($now);
            }
        }

        $user->unblock();
        $user->zeroErrNum(); //歸零錯誤登入計數

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 使用者停權
     *
     * @Route("/user/{userId}/bankrupt/1",
     *        name = "api_user_bankrupt_on",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param  integer $userId
     * @return JsonResponse
     */
    public function setBankruptOnAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);

        //若$user->isBankrupt()為false才紀錄
        if (!$user->isBankrupt()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('bankrupt', var_export($user->isBankrupt(), true), 'true');
            $operationLogger->save($log);
            $user->setModifiedAt($now);
        }

        $user->setBankrupt(true);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 使用者停權關閉
     *
     * @Route("/user/{userId}/bankrupt/0",
     *        name = "api_user_bankrupt_off",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function setBankruptOffAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);

        //若$user->isBankrupt()為true才紀錄
        if ($user->isBankrupt()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('bankrupt', var_export($user->isBankrupt(), true), 'false');
            $operationLogger->save($log);
            $user->setModifiedAt($now);
        }

        $user->setBankrupt(false);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 使用者測試帳號開啟
     *
     * @Route("/user/{userId}/test/1",
     *        name = "api_user_test_on",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param  integer $userId
     * @return JsonResponse
     */
    public function setTestOnAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redisTotalBalance = $this->container->get('snc_redis.total_balance');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);
        $affectedNum = 0;

        if ($user->isTest()) {
            throw new \RuntimeException('Can not set test on when user is test user', 150010179);
        }

        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('user', ['id' => $userId]);
        $log->addMessage('test', var_export($user->isTest(), true), 'true');
        $operationLogger->save($log);
        $user->setModifiedAt($now);

        if ($user->getRole() == 1) {
            $affectedNum++;
        }

        //若下層會員非測試帳號且非隱藏測試帳號,都應加入廳下層測試帳號統計內
        if (!$user->isHiddenTest()) {
            $affectedNum += $em->getRepository('BBDurianBundle:User')
                ->countAllChildUserByTest($userId, false);

            $this->processDomainTotalTest($user->getDomain(), $affectedNum);
        }

        $user->setTest(true);
        $em->getRepository('BBDurianBundle:User')
           ->setTestUserOnAllChild($user);

        // 設定測試帳號真實姓名為 Test User
        $userDetail = $em->find('BBDurianBundle:UserDetail', $userId);
        $userDetail->setNameReal('Test User');

        $em->flush();
        $emShare->flush();

        // 設定會員總餘額
        if ($user->getRole() == 1) {
            $domain = $user->getDomain();
            $currency = $user->getCurrency();

            if ($user->getCash()) {
                $cash = $user->getCash();
                $amount = (int) round($cash->getBalance() * 10000);
                $key = sprintf('%s_%s_%s', 'cash_total_balance', $domain, $currency);

                $redisTotalBalance->hincrby($key, 'normal', $amount * -1);
                $redisTotalBalance->hincrby($key, 'test', $amount);
            }

            if ($user->getCashFake()) {
                $cashFake = $user->getCashFake();
                $amount = (int) round($cashFake->getBalance() * 10000);
                $key = sprintf('%s_%s_%s', 'cash_fake_total_balance', $domain, $currency);

                $redisTotalBalance->hincrby($key, 'normal', $amount * -1);
                $redisTotalBalance->hincrby($key, 'test', $amount);
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 使用者測試帳號關閉
     *
     * @Route("/user/{userId}/test/0",
     *        name = "api_user_test_off",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function setTestOffAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redisTotalBalance = $this->container->get('snc_redis.total_balance');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();
        $affectedNum = 0;

        $user = $this->findUser($userId);

        if ($user->hasParent() && $user->getParent()->isTest()) {
            throw new \RuntimeException('Can not set test off when parent is test user', 150010056);
        }

        $sensitiveLogger->validateAllowedOperator($user);

        if (!$user->isTest()) {
            throw new \RuntimeException('Can not set test off when user is not test user', 150010180);
        }

        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('user', ['id' => $userId]);
        $log->addMessage('test', var_export($user->isTest(), true), 'false');
        $operationLogger->save($log);
        $user->setModifiedAt($now);

        if ($user->getRole() == 1) {
            $affectedNum++;
        }

        //若下層會員是測試帳號且非隱藏測試帳號,都應從廳下層測試帳號統計內扣除
        if (!$user->isHiddenTest()) {
            $affectedNum += $em->getRepository('BBDurianBundle:User')
                ->countAllChildUserByTest($userId, true);

            $this->processDomainTotalTest($user->getDomain(), -$affectedNum);
        }

        $user->setTest(false);
        $em->getRepository('BB\DurianBundle\Entity\User')
           ->setTestUserOffAllChild($user);

        $em->flush();
        $emShare->flush();

        // 設定會員總餘額
        if ($user->getRole() == 1) {
            $domain = $user->getDomain();
            $currency = $user->getCurrency();

            if ($user->getCash()) {
                $cash = $user->getCash();
                $amount = (int) round($cash->getBalance() * 10000);
                $key = sprintf('%s_%s_%s', 'cash_total_balance', $domain, $currency);

                $redisTotalBalance->hincrby($key, 'normal', $amount);
                $redisTotalBalance->hincrby($key, 'test', $amount * -1);
            }

            if ($user->getCashFake()) {
                $cashFake = $user->getCashFake();
                $amount = (int) round($cashFake->getBalance() * 10000);
                $key = sprintf('%s_%s_%s', 'cash_fake_total_balance', $domain, $currency);

                $redisTotalBalance->hincrby($key, 'normal', $amount);
                $redisTotalBalance->hincrby($key, 'test', $amount * -1);
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 使用者租卡體系開啟
     *
     * @Route("/user/{userId}/rent/1",
     *        name = "api_user_rent_on",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param  integer $userId
     * @return JsonResponse
     */
    public function setRentOnAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);

        //若$user->isRent()為false才紀錄
        if (!$user->isRent()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('rent', var_export($user->isRent(), true), 'true');
            $operationLogger->save($log);
            $user->setModifiedAt($now);
        }

        $user->setRent(true);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();
        $output['ret']['rent'] = $user->isRent();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 使用者租卡體系關閉
     *
     * @Route("/user/{userId}/rent/0",
     *        name = "api_user_rent_off",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function setRentOffAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);

        //若$user->isRent()為true才紀錄
        if ($user->isRent()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('rent', var_export($user->isRent(), true), 'false');
            $operationLogger->save($log);
            $user->setModifiedAt($now);
        }

        $user->setRent(false);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();
        $output['ret']['rent'] = $user->isRent();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 開啟重設密碼
     *
     * @Route("/user/{userId}/password_reset/1",
     *        name = "api_user_password_reset_on",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param  integer $userId
     * @return JsonResponse
     */
    public function setPasswordResetOnAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);
        $sensitiveLogger->validateAllowedOperator($user);

        // 若$user->isPasswordReset()為false才紀錄
        if (!$user->isPasswordReset()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('password_reset', 'false', 'true');
            $operationLogger->save($log);
            $user->setModifiedAt($now);

            if ($userPassword) {
                $logPassword = $operationLogger->create('user_password', ['id' => $userId]);
                $logPassword->addMessage('password_reset', 'false', 'true');
                $operationLogger->save($logPassword);
                $userPassword->setReset(true);
                $userPassword->setModifiedAt($now);
            }
        }

        $user->setPasswordReset(true);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 關閉重設密碼
     *
     * @Route("/user/{userId}/password_reset/0",
     *        name = "api_user_password_reset_off",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function setPasswordResetOffAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);
        $sensitiveLogger->validateAllowedOperator($user);

        // 若$user->isPasswordReset()為true才紀錄
        if ($user->isPasswordReset()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('password_reset', 'true', 'false');
            $operationLogger->save($log);
            $user->setModifiedAt($now);

            if ($userPassword) {
                $logPassword = $operationLogger->create('user_password', ['id' => $userId]);
                $logPassword->addMessage('password_reset', 'true', 'false');
                $operationLogger->save($logPassword);
                $userPassword->setReset(false);
                $userPassword->setModifiedAt($now);
            }
        }

        $user->setPasswordReset(false);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 檢查登入密碼是否正確
     *
     * @Route("/user/{userId}/check_password",
     *        name = "api_user_check_password",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $userId　使用者Id
     * @return JsonResponse
     */
    public function checkPasswordAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $request = $request->request;

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);
        $output['ret']['isValid'] = 'false';

        $password = $request->get('password');

        // 密碼皆轉成小寫後再比對
        $password = strtolower($password);
        $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);

        // 在時效內需檢查臨時密碼
        $now = new \DateTime('now');
        $onceExpireAt = $userPassword->getOnceExpireAt();
        $isOncePassword = false;

        if (!$userPassword->isUsed() && $onceExpireAt && $now < $onceExpireAt) {
            $isOncePassword = password_verify($password, $userPassword->getOncePassword());
        }

        if ($isOncePassword || password_verify($password, $userPassword->getHash())) {
            $output['ret']['isValid'] = 'true';
        }

        $output['result'] = 'ok';

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 回傳登入密碼
     *
     * @Route("/user/{userId}/password",
     *        name = "api_user_get_password",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getPasswordAction($userId)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');

        $user = $this->findUser($userId);

        //敏感資料操作資訊驗證及寫相關LOG
        $sensitiveLogger->writeSensitiveLog();
        //不符合敏感資料操作資訊規則則跳錯
        $result = $sensitiveLogger->validateAllowedOperator($user);

        if (!$result['result']) {
            throw new \RuntimeException($result['msg'], $result['code']);
        }

        $output['ret'] = $user->getPassword();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改使用者資料
     *
     * @Route("/user/{userId}",
     *        name = "api_user_set_info",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function setUserAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $userValidator = $this->get('durian.user_validator');
        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');
        $redis = $this->get('snc_redis.map');
        $oriRequest = $request;
        $request = $request->request;

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $em->beginTransaction();
        $emShare->beginTransaction();
        $fields = array();
        $userFields = [];
        $isModified = false;
        $setModifiedAtByUser = false;
        $passwordModified = false;
        $username = '';
        $isModifiedUsername = false;

        if ($request->has('alias')) {
            $alias = trim($request->get('alias'));
            $validator->validateEncode($alias);
            $userValidator->validateAlias($alias);
        }

        if ($request->has('currency')) {
            $currency = $request->get('currency');

            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Illegal currency', 150010101);
            }
        }

        try {
            $user = $this->findUser($userId);
            $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);

            $sensitiveLogger->validateAllowedOperator($user);

            $userLog = $operationLogger->create('user', ['id' => $userId]);
            $passwordLog = $operationLogger->create('user_password', ['user_id' => $userId]);

            if ($request->has('username')) {
                $userFields[] = 'username';

                $username = trim($request->get('username'));
                $userValidator->validateUsername($username);
                $userValidator->validateUniqueUsername(
                    $username,
                    $user->getDomain()
                );

                if ($user->getUsername() != $username) {
                    $userLog->addMessage('username', $user->getUsername(), $username);
                }

                $user->setUsername($username);
                $isModified = true;
                $isModifiedUsername = true;
            }

            if ($request->has('alias')) {
                $userFields[] = 'alias';

                if ($user->getAlias() != $alias) {
                    $userLog->addMessage('alias', $user->getAlias(), $alias);
                }

                $user->setAlias($alias);
                $isModified = true;
            }

            if ($request->has('modified_at')) {
                $userFields[] = 'modified_at';
                $date = new \DateTime($request->get('modified_at'));

                if ($user->getModifiedAt()->format('Y-m-d H:i:s') != $request->get('modified_at')) {
                    $modifiedAt = $user->getModifiedAt()->format('Y-m-d H:i:s');
                    $modifiedAtRequest = $request->get('modified_at');
                    $userLog->addMessage('modified_at', $modifiedAt, $modifiedAtRequest);
                }

                $user->setModifiedAt($date);
                $setModifiedAtByUser = true;
            }

            if ($request->has('password_expire_at')) {
                $userFields[] = 'password_expire_at';
                $date = new \DateTime($request->get('password_expire_at'));

                if ($user->getPasswordExpireAt()->format('Y-m-d H:i:s') != $request->get('password_expire_at')) {
                    $passwordExpireAt = $user->getPasswordExpireAt()->format('Y-m-d H:i:s');
                    $pwdExpireAtRequest = $request->get('password_expire_at');
                    $userLog->addMessage('password_expire_at', $passwordExpireAt, $pwdExpireAtRequest);
                    $passwordLog->addMessage('password_expire_at', $passwordExpireAt, $pwdExpireAtRequest);
                }

                $user->setPasswordExpireAt($date);
                $userPassword->setExpireAt($date);
                $isModified = true;
                $passwordModified = true;
            }

            if ($request->has('password')) {
                $userFields[] = 'password';

                //已停用密碼的使用者無法修改密碼
                if ($user->getPassword() == '') {
                    throw new \RuntimeException('DisabledPassword user cannot change password', 150010072);
                }

                $password = $request->get('password');
                $userValidator->validatePassword($password);

                if ($user->getPassword() != $password) {
                    $userLog->addMessage('password', 'updated');
                    $passwordLog->addMessage('hash', 'updated');
                }

                $user->setPassword($password);
                $userPassword->setHash(password_hash($password, PASSWORD_BCRYPT));
                $passwordModified = true;
            }

            if ($request->has('currency')) {
                $userFields[] = 'currency';
                $currency = $request->get('currency');

                $currencyNum = $currencyOperator->getMappedNum($currency);

                if ($user->getCurrency() != $currencyNum) {
                    $oldCurrency = $currencyOperator->getMappedCode($user->getCurrency());
                    $userLog->addMessage('currency', $oldCurrency, $currency);
                }

                $user->setCurrency($currencyNum);
                $isModified = true;
            }

            if ($request->has('last_bank')) {
                $userFields[] = 'last_bank';
                $lastBankId = $request->get('last_bank');

                $criteria = array('id' => $lastBankId);

                $banks = $em->getRepository('BB\DurianBundle\Entity\Bank')
                            ->getBankArrayBy($user, ['id'], $criteria);

                if (count($banks) == 0) {
                    throw new \RuntimeException('No Bank found', 150010110);
                }

                if ($user->getLastBank() != $lastBankId) {
                    $userLog->addMessage('last_bank', $user->getLastBank(), $lastBankId);
                }

                $user->setLastBank($lastBankId);
                $isModified = true;
            }

            if ($request->has('cash_fake')) {
                $fields[] = 'cash_fake';
                $cashFake = $request->get('cash_fake');
                $operator = array_key_exists('operator', $cashFake) ? $cashFake['operator'] : '';

                if ($user->getCashFake()->getBalance() != $cashFake['balance']) {
                    $cashFakeBalance = $user->getCashFake()->getBalance();
                    $cashFakeLog = $operationLogger->create('cash_fake', ['user_id' => $userId]);
                    $cashFakeLog->addMessage('balance', $cashFakeBalance, $cashFake['balance']);
                    $operationLogger->save($cashFakeLog);
                }

                // 驗證參數編碼是否為utf8
                $validator->validateEncode($operator);

                $result = $this->get('durian.cashfake_op')
                    ->editCashFake($user, $cashFake['balance'], $operator);

                if (isset($result['entry'])) {
                    foreach ($result['entry'] as $row) {
                        $transactionId[] = $row['id'] ;
                    }
                }
                $isModified = true;
            }

            if ($request->has('credit')) {
                $fields[] = 'credit';
                $credits = $request->get('credit');

                $creditOp = $this->get('durian.credit_op');

                // 設定額度
                foreach ($credits as $group => $value) {
                    $credit = $user->getCredit($group);
                    $creditLog = null;

                    if ($value['line'] > Credit::LINE_MAX) {
                        throw new \RangeException('Oversize line given which exceeds the MAX', 150010139);
                    }

                    if (!$validator->isInt($value['line'])) {
                        throw new \InvalidArgumentException('Invalid line given', 150010102);
                    }

                    if ($user->getCurrency() != 156) {
                        $value['line'] = $this->exchangeReconv($value['line'], $user->getCurrency());
                        $value['line'] = $creditOp->roundDown($value['line'], CreditPeriod::NUMBER_OF_DECIMAL_PLACES);
                    }

                    if ($credit && $value['line'] != null) {
                        if ($credit->getLine() != $value['line']) {
                            $creditLine = $credit->getLine();
                            $lineValue = $value['line'];

                            $majorKey = [
                                'user_id' => $userId,
                                'group_num' => $group
                            ];

                            $creditLog = $operationLogger->create('credit', $majorKey);
                            $creditLog->addMessage('line', $creditLine, $lineValue);
                        }

                        $creditInfo[] = $creditOp->setLine($value['line'], $credit);
                    }

                    $isModified = true;

                    if ($creditLog && $creditLog->getMessage()) {
                        $operationLogger->save($creditLog);
                    }
                }
            }

            // 有傳shareLimit or shareLimitNext則須檢查佔成更新狀況
            if ($request->has('sharelimit') || $request->has('sharelimit_next')) {

                $activateSLNext = $this->get('durian.activate_sl_next');
                $curDate = new \DateTime('now');

                if ($activateSLNext->isUpdating($curDate)) {
                    throw new \RuntimeException('Cannot perform this during updating sharelimit', 150010107);
                }

                if (!$activateSLNext->hasBeenUpdated($curDate)) {
                    throw new \RuntimeException(
                        'Cannot perform this due to updating sharelimit is not performed for too long time',
                        150010105
                    );
                }
            }

            // shareLimit
            if (1 != $user->getRole() && $request->has('sharelimit')) {
                $fields[] = 'sharelimit';
                $ret = $this->editShareLimit($user, $request->get('sharelimit'));

                if ($ret) {
                    $isModified = true;
                }
            }

            // shareLimitNext
            if (1 != $user->getRole() && $request->has('sharelimit_next')) {
                $fields[] = 'sharelimit_next';
                $ret = $this->editShareLimitNext($user, $request->get('sharelimit_next'));

                if ($ret) {
                    $isModified = true;
                }
            }

            $date = new \DateTime('now');

            // 若有更動到user欄位, 則更新modified時間
            if ($isModified && (!$setModifiedAtByUser)) {
                $user->setModifiedAt($date);
            }

            //若有修改密碼或時效，UserPassword表單將一併更正
            if ($passwordModified) {
                $userPassword->setModifiedAt($date);
                $operationLogger->save($passwordLog);
            }

            if ($userLog->getMessage()) {
                $operationLogger->save($userLog);
            }
            $em->flush();
            $emShare->flush();

            $this->get('durian.share_scheduled_for_update')->execute();
            $em->flush();

            if (isset($transactionId)) {
                $this->get('durian.cashfake_op')->cashfakeMultiCommit($transactionId);
            }

            if ($isModifiedUsername) {
                // 修改redis對應表
                $um = $this->get('durian.user_manager');
                $usernameKey = $um->getKey($userId, 'username');

                $redis->set($usernameKey, $username);
            }

            $em->commit();
            $emShare->commit();

            $output['result'] = 'ok';
            $output['ret'] = $user->toArray();

            // 若有修改但不包含user欄位，則清空user回傳值
            if ($fields && !$userFields) {
                $output['ret'] = [];
            }

            if ($fields) {
                foreach ($this->getUserInfo($user, $fields) as $key => $value) {
                    $output['ret'][$key] = $value;
                }
            }

        } catch (\Exception $e) {
            if (isset($creditInfo)) {
                $this->rollbackCreditTotalLine($creditInfo);
            }

            $em->rollback();
            $emShare->rollback();

            if (isset($transactionId)) {
                $this->get('durian.cashfake_op')->cashfakeMultiRollback($transactionId);
            }

            $locale = $oriRequest->getPreferredLanguage();
            $this->get('translator')->setLocale($locale);

            $output['result'] = 'error';
            $output['code'] = $e->getCode();
            $msg = $e->getMessage();

            if (!is_null($e->getPrevious())) {
                // 隱藏同分秒使用者密碼重覆新增的錯誤訊息，以不同error code區別
                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 1062) {
                        $output['code'] = 150010099;
                        $msg = 'Database is busy';
                }
            }

            if ($e instanceof ShareLimitNotExists) {
                $data = array('%groupNum%' => $e->getGroupNum(), '%userId%' => $e->getUser()->getId());
                $output['msg'] = $this->get('translator')->trans($msg, $data);
            } else {
                $output['msg'] = $this->get('translator')->trans($msg);
            }
        }

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取得多使用者資訊
     *
     * @Route("/v2/users",
     *        name = "api_v2_users",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     *
     * @Route("/users",
     *        name = "api_users",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     *
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsersAction(Request $request)
    {
        $currencyOperator = $this->get('durian.currency');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $em = $this->getEntityManager();
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $query = $request->query;

        $userIds = $query->get('users', []);
        $subRet = $query->get('sub_ret', false);
        $fields = $query->get('fields', []);

        if (!is_array($userIds)) {
            throw new \InvalidArgumentException('Invalid users', 150010069);
        }

        try {
            $output['ret'] = array();

            foreach ($userIds as $index => $userId) {
                $userId = (int) $userId;
                if (empty($userId)) {
                    unset($userIds[$index]);
                }
            }
            if (!empty($userIds)) {
                $shareLimitException = null;
                if ($this->areMappedFields($fields)) {
                    $users = $userRepo->getMultiUserByIds($userIds);

                    //預先取得所有上層以減少query數量
                    if (empty($fields) || in_array('all_parents', $fields)) {
                        $userRepo->getAllParentsAtOnce($users);
                    }

                    $userRet = [];
                    foreach ($users as $key => $u) {
                        $output['ret'][] = $this->getUserInfo($u, $fields);

                        /*
                         * 因users數量眾多驗證全部會有效能問題，
                         * 且各組大多只會送單一userId。因此僅驗證第一個
                         */
                        if ($key == 0) {
                            $sensitiveLogger->validateAllowedOperator($u);
                        }

                        //取得使用者附屬資訊
                        if ($subRet) {
                            $userRet = $this->getUserRet($u, $fields, $userRet);
                        }

                        if (isset($output['ret'][$key]['shareLimitException'])) {
                            $shareLimitException = $output['ret'][$key]['shareLimitException'];
                        }

                        unset($output['ret'][$key]['shareLimitException']);
                    }

                    if ($subRet && count($userRet) > 0) {
                        $output['sub_ret']['user'] = $userRet;
                    }

                    if ($shareLimitException instanceof \Exception) {
                        throw $shareLimitException;
                    }
                } else {
                    $users = $userRepo->getUserArrayByIds($userIds);

                    foreach ($users as $key => $u) {
                        if (isset($u['currency'])) {
                            $u['currency'] = $currencyOperator->getMappedCode($u['currency']);
                        }

                        /*
                         * 因users數量眾多驗證全部會有效能問題，
                         * 且各組大多只會送單一userId。因此僅驗證第一個
                         */
                        if ($key == 0 && isset($u['domain'])) {
                            $sensitiveLogger->validateAllowedOperator($u['domain']);
                        }

                        $output['ret'][] = $this->fieldFilter($u, $fields);
                    }
                }
            }

            $output['result'] = 'ok';

        } catch (\Exception $e) {
            $locale = $request->getPreferredLanguage();
            $this->get('translator')->setLocale($locale);

            $output['result'] = 'error';
            $output['code'] = $e->getCode();

            if ($e instanceof ShareLimitNotExists) {
                $data = array('%groupNum%' => $e->getGroupNum(), '%userId%' => $e->getUser()->getId());
                $output['msg'] = $this->get('translator')->trans($e->getMessage(), $data);
            } else {
                $output['msg'] = $this->get('translator')->trans($e->getMessage());
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 取得單一使用者資訊
     *
     * @Route("/user/{userId}",
     *        name = "api_user",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getUserAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $query = $request->query;

        $subRet = $query->get('sub_ret', false);
        $fields = $query->get('fields', array());

        try {
            $user = $this->findUser($userId);

            //預先取得所有上層以減少query數量
            if (empty($fields) || in_array('all_parents', $fields)) {
                $userRepo->getAllParentsAtOnce(array($user));
            }

            $sensitiveLogger->validateAllowedOperator($user);

            $output['ret'][] = $this->getUserInfo($user, $fields);

            $userRet = [];
            $shareLimitException = null;
            //取得附屬資訊
            if ($subRet) {
                $userRet = $this->getUserRet($user, $fields, $userRet);
            }

            if ($subRet && count($userRet) > 0) {
                $output['sub_ret']['user'] = $userRet;
            }

            if (isset($output['ret'][0]['shareLimitException'])) {
                $shareLimitException = $output['ret'][0]['shareLimitException'];
            }
            unset($output['ret'][0]['shareLimitException']);

            if ($shareLimitException instanceof \Exception) {
                throw $shareLimitException;
            }

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $locale = $request->getPreferredLanguage();
            $this->get('translator')->setLocale($locale);
            $output['result'] = 'error';
            $output['code'] = $e->getCode();

            if ($e instanceof ShareLimitNotExists) {
                $data = array('%groupNum%' => $e->getGroupNum(), '%userId%' => $e->getUser()->getId());
                $output['msg'] = $this->get('translator')->trans($e->getMessage(), $data);
            } else {
                $output['msg'] = $this->get('translator')->trans($e->getMessage());
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 取得體系架構
     *
     * @Route("/user/hierarchy",
     *        name = "api_user_hierarchy",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $username
     * @return JsonResponse
     */
    public function getHierarchyAction(Request $request)
    {
        $em = $this->getEntityManager();
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $currencyOperator = $this->get('durian.currency');
        $query = $request->query;

        $domain      = $query->get('domain');
        $hiddenTest  = $query->get('hidden_test');
        $username    = trim($query->get('username'));
        $firstResult = $query->get('first_result');
        $maxResults  = $query->get('max_results');

        $hierarchies = array();
        $users = $em->getRepository('BBDurianBundle:User')
            ->findByFuzzyName($username, $domain, $hiddenTest, $firstResult, $maxResults);

        foreach ($users as $user) {
            $hierarchy = array();
            $sensitiveLogger->validateAllowedOperator($user);

            // 自己是體系的最下層
            $data = $user->toArray();
            $data['currency'] = $currencyOperator->getMappedCode($user->getCurrency());
            $hierarchy[] = $data;

            // 依序將上層填入
            foreach ($user->getAllParents() as $parent) {
                $data = $parent->toArray();
                $data['currency'] = $currencyOperator->getMappedCode($parent->getCurrency());
                $hierarchy[] = $data;
            }

            $hierarchies[] = $hierarchy;
        }

        $total = $em->getRepository('BBDurianBundle:User')
            ->countByFuzzyName($username, $domain, $hiddenTest);

        $output['result'] = 'ok';
        $output['ret'] = $hierarchies;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得體系架構
     *
     * @Route("/v2/user/hierarchy",
     *        name = "api_v2_user_hierarchy",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.11.04
     */
    public function getHierarchyV2Action(Request $request)
    {
        $em = $this->getEntityManager();
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $currencyOperator = $this->get('durian.currency');
        $query = $request->query;

        $hiddenTest  = $query->get('hidden_test');
        $username    = trim($query->get('username'));
        $firstResult = $query->get('first_result');
        $maxResults  = $query->get('max_results');

        $hierarchies = [];
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $users = $userRepo->findByFuzzyName($username, null, $hiddenTest, $firstResult, $maxResults);

        foreach ($users as $user) {
            $hierarchy = [];
            $sensitiveLogger->validateAllowedOperator($user);

            // 自己是體系的最下層
            $data = $user->toArray();
            $data['currency'] = $currencyOperator->getMappedCode($user->getCurrency());
            $hierarchy[] = $data;

            // 依序將上層填入
            foreach ($user->getAllParents() as $parent) {
                $data = $parent->toArray();
                $data['currency'] = $currencyOperator->getMappedCode($parent->getCurrency());
                $hierarchy[] = $data;
            }

            $hierarchies[] = $hierarchy;
        }

        $total = $userRepo->countByFuzzyName($username, null, $hiddenTest);

        $output['result'] = 'ok';
        $output['ret'] = $hierarchies;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得指定廳體系架構
     *
     * @Route("/user/hierarchy_by_domain",
     *        name = "api_user_hierarchy_by_domain",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.11.04
     */
    public function getHierarchyByDomainAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $currencyOperator = $this->get('durian.currency');
        $query = $request->query;

        $domain      = $query->get('domain');
        $hiddenTest  = $query->get('hidden_test');
        $username    = trim($query->get('username'));
        $firstResult = $query->get('first_result');
        $maxResults  = $query->get('max_results');

        if (empty($domain)) {
            throw new \InvalidArgumentException('No domain specified', 150010147);
        }

        $validDomain = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if(!$validDomain) {
            throw new \RuntimeException('Not a domain', 150010148);
        }

        $hierarchies = [];
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $users = $userRepo->findByFuzzyName($username, $domain, $hiddenTest, $firstResult, $maxResults);

        foreach ($users as $user) {
            $hierarchy = [];
            $sensitiveLogger->validateAllowedOperator($user);

            // 自己是體系的最下層
            $data = $user->toArray();
            $data['currency'] = $currencyOperator->getMappedCode($user->getCurrency());
            $hierarchy[] = $data;

            // 依序將上層填入
            foreach ($user->getAllParents() as $parent) {
                $data = $parent->toArray();
                $data['currency'] = $currencyOperator->getMappedCode($parent->getCurrency());
                $hierarchy[] = $data;
            }

            $hierarchies[] = $hierarchy;
        }

        $total = $userRepo->countByFuzzyName($username, $domain, $hiddenTest);

        $output['result'] = 'ok';
        $output['ret'] = $hierarchies;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得指定的下層帳號資料
     *
     * @Route("/user/list",
     *        name = "api_user_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $currencyOperator = $this->get('durian.currency');
        $query = $request->query;

        $criteria = array();

        // 參數設定
        $pid      = $query->get('parent_id');
        $depth    = $query->get('depth');
        $startAt  = $query->get('start_at');
        $endAt    = $query->get('end_at');
        $fields   = $query->get('fields', array());
        $subRet   = $query->get('sub_ret', false);
        $sort     = $query->get('sort');
        $order    = $query->get('order');
        $username = $query->get('username', false);
        $searchFields = $query->get('search_field', array());
        $searchValues = $query->get('search_value', array());
        $firstResult  = $query->get('first_result');
        $maxResults   = $query->get('max_results');
        $modifiedStartAt = $query->get('modified_start_at');
        $modifiedEndAt   = $query->get('modified_end_at');

        if (!$pid) {
            throw new \InvalidArgumentException('No parent_id specified', 150010036);
        }

        //search_field,search_value 如果帶字串，先轉成array
        if (!is_array($searchFields)) {
            $searchFields = [$searchFields];
        }

        if (!is_array($searchValues)) {
            $searchValues = [$searchValues];
        }

        if ($query->has('enable')) {
            $criteria['enable'] = $query->get('enable');
        }

        if ($query->has('sub')) {
            $criteria['sub'] = $query->get('sub');
        }

        if ($query->has('block')) {
            $criteria['block'] = $query->get('block');
        }

        if ($query->has('test')) {
            $criteria['test'] = $query->get('test');
        }

        if ($query->has('hidden_test')) {
            $criteria['hiddenTest'] = $query->get('hidden_test');
        }

        if ($query->has('rent')) {
            $criteria['rent'] = $query->get('rent');
        }

        if ($query->has('bankrupt')) {
            $criteria['bankrupt'] = $query->get('bankrupt');
        }

        if ($query->has('password_reset')) {
            $criteria['passwordReset'] = $query->get('password_reset');
        }

        if ($query->has('role')) {
            $criteria['role'] = $query->get('role');
        }

        if ($query->has('size')) {
            $criteria['size'] = $query->get('size');
        }

        $orderBy = $parameterHandler->orderBy($sort, $order);

        if ($startAt) {
            $startAt = new \DateTime($startAt);
            $startAt->format('Y-m-d H:i:s');
        }

        if ($endAt) {
            $endAt = new \DateTime($endAt);
            $endAt->format('Y-m-d H:i:s');
        }

        if ($modifiedStartAt) {
            $modifiedStartAt = $parameterHandler->datetimeToYmdHis($modifiedStartAt);
        }

        if ($modifiedEndAt) {
            $modifiedEndAt = $parameterHandler->datetimeToYmdHis($modifiedEndAt);
        }

        if ($username !== false) {
            $searchFields[] = 'username';
            $searchValues[] = trim($username);
        }

        //檢查search_field和search_value 兩邊的的變數量是否一致
        if (count($searchFields) != count($searchValues)) {
            throw new \InvalidArgumentException('Search field and value did not match', 150010083);
        }

        $searchSet = $this->makeSearch($searchFields, $searchValues);
        $searchPeriod = array(
            'startAt' => $startAt,
            'endAt'   => $endAt,
            'modifiedStartAt' => $modifiedStartAt,
            'modifiedEndAt'   => $modifiedEndAt
        );

        $params['criteria'] = $criteria;
        $params['depth']    = $depth;
        $params['order_by'] = $orderBy;
        $params['first_result'] = $firstResult;
        $params['max_results'] = $maxResults;

        $userRepo = $em->getRepository('BB\DurianBundle\Entity\User');

        try {
            $parent = $this->findUser($pid);
            $sensitiveLogger->validateAllowedOperator($parent);

            $total = $userRepo->countChildOf(
                $parent,
                $params,
                $searchSet,
                $searchPeriod
            );

            $output['ret'] = array();
            $shareLimitException = null;

            if ($this->areMappedFields($fields)) {
                $users = $userRepo->findChildBy(
                    $parent,
                    $params,
                    $searchSet,
                    $searchPeriod
                );

                //預先取得所有上層以減少query數量
                if (empty($fields) || in_array('all_parents', $fields)) {
                    $userRepo->getAllParentsAtOnce($users);
                }

                $userRet = [];
                foreach ($users as $key => $u) {
                    $output['ret'][] = $this->getUserInfo($u, $fields, true);

                    //取得使用者附屬資訊
                    if ($subRet) {
                        $userRet = $this->getUserRet($u, $fields, $userRet);
                    }

                    if (isset($output['ret'][$key]['shareLimitException'])) {
                        $shareLimitException = $output['ret'][$key]['shareLimitException'];
                    }

                    unset($output['ret'][$key]['shareLimitException']);
                }

                if ($subRet && count($userRet) > 0) {
                    $output['sub_ret']['user'] = $userRet;
                }
            } else {
                foreach ($fields as $field) {
                    $selectFields[] = \Doctrine\Common\Util\Inflector::camelize($field);
                }

                $users = $userRepo->findChildArrayBy(
                    $parent,
                    $params,
                    $searchSet,
                    $searchPeriod,
                    $selectFields
                );

                foreach ($users as $u) {
                    if (isset($u['currency'])) {
                        $u['currency'] = $currencyOperator->getMappedCode($u['currency']);
                    }

                    $output['ret'][] = $this->fieldFilter($u, $fields);
                }
            }

            $output['result'] = 'ok';
            $output['pagination']['first_result'] = $firstResult;
            $output['pagination']['max_results']  = $maxResults;
            $output['pagination']['total'] = $total;

            if ($shareLimitException instanceof \Exception) {
                throw $shareLimitException;
            }
        } catch (\Exception $e) {
            $locale = $request->getPreferredLanguage();
            $this->get('translator')->setLocale($locale);
            $output['result'] = 'error';
            $output['code'] = $e->getCode();

            if ($e instanceof ShareLimitNotExists) {
                $data = array('%groupNum%' => $e->getGroupNum(), '%userId%' => $e->getUser()->getId());
                $output['msg'] = $this->get('translator')->trans($e->getMessage(), $data);
            } else {
                $output['msg'] = $this->get('translator')->trans($e->getMessage());
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 體系轉移
     *
     * @Route("/user/{userId}/change_parent/{parentId}",
     *        name = "api_user_change_parent",
     *        requirements = {"userId" = "\d+", "parentId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId   使用者
     * @param integer $parentId 上層
     * @return JsonResponse
     */
    public function changeParentAction(Request $request, $userId, $parentId)
    {
        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');
        $emShare = $this->getEntityManager('share');
        $redis = $this->container->get('snc_redis.default');
        $oriRequest = $request;
        $request = $request->request;

        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $operator  = trim($request->get('operator', ''));

        // 驗證參數編碼是否為utf8
        $validator->validateEncode($operator);

        $em->beginTransaction();
        $emHis->beginTransaction();
        $emShare->beginTransaction();
        try {

            $activateSLNext = $this->get('durian.activate_sl_next');
            $curDate = new \DateTime('now');

            if ($activateSLNext->isUpdating($curDate)) {
                throw new \RuntimeException('Cannot perform this during updating sharelimit', 150010107);
            }

            if (!$activateSLNext->hasBeenUpdated($curDate)) {
                throw new \RuntimeException(
                    'Cannot perform this due to updating sharelimit is not performed for too long time',
                    150010105
                );
            }

            $user = $this->findUser($userId);
            $targetParent = $this->findUser($parentId);
            $sensitiveLogger->validateAllowedOperator($user);

            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('parent_id', $user->getParent()->getId(), $parentId);
            $operationLogger->save($log);

            $sizeQueue = $this->get('durian.ancestor_manager')
                ->changeParent($user, $targetParent, $operator);
            $user->setModifiedAt($curDate);

            if (isset($sizeQueue['old_parent'])) {
                $data = [
                    'index' => $sizeQueue['old_parent'],
                    'value' => -1
                ];
                $redis->rpush('user_size_queue', json_encode($data));
            }

            if (isset($sizeQueue['new_parent'])) {
                $data = [
                    'index' => $sizeQueue['new_parent'],
                    'value' => 1
                ];
                $redis->rpush('user_size_queue', json_encode($data));
            }

            $em->flush();
            $emHis->flush();
            $emShare->flush();

            $this->get('durian.share_scheduled_for_update')->execute();

            $em->flush();
            $em->commit();
            $emHis->commit();
            $emShare->commit();

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $em->rollback();
            $emHis->rollback();
            $emShare->rollback();

            $locale = $oriRequest->getPreferredLanguage();
            $this->get('translator')->setLocale($locale);
            $output['result'] = 'error';
            $output['code'] = $e->getCode();

            if ($e instanceof \BB\DurianBundle\Exception\ShareLimitNotExists) {
                $data = array('%groupNum%' => $e->getGroupNum(), '%userId' => $e->getUser()->getId());
                $output['msg'] = $this->get('translator')->trans($e->getMessage(), $data);
            } else {
                $output['msg'] = $this->get('translator')->trans($e->getMessage());
            }
        }

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 檢查指定的站別內使用者資料是否重複
     *
     * @Route("/user/check_unique",
     *        name = "api_user_check_unique",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function userCheckUniqueAction(Request $request)
    {
        $em = $this->getEntityManager();
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $query = $request->query;

        // 取得傳入的參數
        $domain = $query->get('domain');
        $fields = array_map('trim', $query->get('fields', []));

        // initialize
        $unique = true;
        $supportField = array('username');

        foreach ($fields as $field => $value) {
            // 過濾掉不支援的欄位
            if (in_array($field, $supportField)) {
                $criteria[$field] = $value;
            }
        }

        if (empty($criteria)) {
            throw new \InvalidArgumentException('No fields specified', 150010038);
        }

        if (null == $domain) {
            throw new \InvalidArgumentException('No domain specified', 150010100);
        }

        $criteria['domain'] = $domain;
        $sensitiveLogger->validateAllowedOperator($domain);

        $result = $em->getRepository('BB\DurianBundle\Entity\User')
                     ->findOneBy($criteria);

        if (!empty($result)) {
            $unique = false;
        }

        $output['result'] = 'ok';
        $output['ret']['unique'] = $unique;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 設定廳主的銀行幣別資料
     *
     * @Route("/domain/{domain}/bank",
     *        name = "api_domain_set_bank",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain
     * @return JsonResponse
     */
    public function setDomainBankAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $request = $request->request;

        $em->beginTransaction();
        try {
            $user = $this->findDomain($domain);
            $bcSet = $request->get('banks', array());
            $bcHas = $this->getBankCurrency($domain);
            $sensitiveLogger->validateAllowedOperator($user);

            // 新增:設定有的但原本沒有的
            $bcAdds = array_diff($bcSet, $bcHas);
            foreach ($bcAdds as $bcId) {
                $bcAdd = $em->find('BB\DurianBundle\Entity\BankCurrency', $bcId);

                if ($bcAdd) {
                    $domainBankAdd = new DomainBank($user, $bcAdd);
                    $em->persist($domainBankAdd);
                }
            }

            // 移除:原本有的但設定沒有的
            $bcDiffs = array_diff($bcHas, $bcSet);
            foreach ($bcDiffs as $bcId) {
                $criteria = array(
                    'domain'         => $domain,
                    'bankCurrencyId' => $bcId,
                );
                $domainBankBye = $em->getRepository('BB\DurianBundle\Entity\DomainBank')
                                    ->findOneBy($criteria);

                if ($domainBankBye) {
                    $em->remove($domainBankBye);
                }
            }
            $em->flush();

            $output['ret'] = $this->getBankCurrency($domain);
            $output['result'] = 'ok';
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();

            throw $e;
        }

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者對應銀行幣別資料
     *
     * @Route("/domain/{domain}/bank",
     *        name = "api_domain_get_bank",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function getDomainBankAction($domain)
    {
        $this->findDomain($domain);
        $bcs = $this->getBankCurrency($domain);

        $output['ret'] = $bcs;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改使用者Email
     *
     * @Route("/user/{userId}/email",
     *        name = "api_user_edit_email",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者ID
     * @return JsonResponse
     *
     * @author Linda 2015.03.27
     */
    public function editEmailAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $userValidator = $this->get('durian.user_validator');
        $blacklistValidator = $this->get('durian.blacklist_validator');
        $now = new \DateTime('now');

        $request = $request->request;
        $email = trim($request->get('email', ''));
        $verify = $request->get('verify', 1);
        $verifyBlacklist = (bool) $request->get('verify_blacklist', 1);

        if ($email) {
            $userValidator->validateEmail($email);
        }

        $user = $this->findUser($userId);

        //驗證黑名單
        if ($verifyBlacklist && $user->getRole() == 1) {
            $criteria['email'] = $email;
            $blacklistValidator->validate($criteria, $user->getDomain());
        }

        $userEmail = $em->find('BBDurianBundle:UserEmail', $user);

        //新增使用者email操作紀錄
        $operationLogger = $this->get('durian.operation_logger');
        $emailLog = $operationLogger->create('user_email', ['user_id' => $userId]);
        $now = new \DateTime;

        $changed = false;

        if ($userEmail->getEmail() != $email) {
            $emailLog->addMessage('email', $userEmail->getEmail(), $email);
            $userEmail->setEmail($email);
            $changed = true;
        }

        if ($verify) {
            $emailLog->addMessage('confirm', var_export($userEmail->isConfirm(), true), 'false');

            $confirmAt = $userEmail->getConfirmAt();
            $confirmAtStr = 'NULL';

            if ($confirmAt) {
                $confirmAtStr = $confirmAt->format('Y-m-d H:i:s');
            }

            $emailLog->addMessage('confirm_at', $confirmAtStr, '');

            $userEmail->setConfirm(false)
                ->removeConfirmAt();
        }

        if ($changed) {
            $userLog = $operationLogger->create('user', ['id' => $userId]);
            $userLog->addMessage('modified_at',
                $user->getModifiedAt()->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s')
            );
            $user->setModifiedAt($now);

            $operationLogger->save($emailLog);
            $operationLogger->save($userLog);
            $em->flush();
            $emShare->flush();
        }

        $ret['result'] = 'ok';
        $ret['ret'] = $userEmail->toArray();

        return new JsonResponse($ret);
    }

    /**
     * 取得廳主設定的銀行幣別資料
     *
     * @param integer $domain
     * @return array
     */
    private function getBankCurrency($domain)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BB\DurianBundle\Entity\DomainBank');

        $criteria = array('domain' => $domain);

        $domainBanks = $repo->findBy($criteria);

        $data = array();
        foreach ($domainBanks as $domainBank) {
            $data[] = $domainBank->getBankCurrencyId();
        }

        return $data;
    }

    /**
     * 取得使用者密碼錯誤次數
     *
     * @Route("/user/{userId}/login_log/error_number",
     *        name = "api_user_get_error_number",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getErrorNumberAction($userId)
    {
        $user = $this->findUser($userId);

        $output['result'] = 'ok';
        $output['ret']['err_num'] = $user->getErrNum();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者上次登入成功記錄
     *
     * @Route("/user/{userId}/login_log/previous",
     *        name = "api_user_get_previous_login",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getPreviousLoginAction($userId)
    {
        $em = $this->getEntityManager();

        $user = $this->findUser($userId);

        $loginRepo = $em->getRepository('BB\DurianBundle\Entity\LoginLog');
        $preLog = $loginRepo->getPreviousSuccess($user);

        $loginArray = array();

        if ($preLog) {
            $loginArray = $preLog->toArray();
        }

        $output['ret']['previous_login'] = $loginArray;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得使用者登入記錄
     *
     * @Route("/user/{userId}/login_log",
     *        name = "api_user_get_login_log",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getLoginLogAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        // initial
        $query = $request->query;
        $intCriteria = array();
        $strCriteria = array();
        $loginlogs   = array();
        $time = null;

        // 基本查詢設定
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $mobileInfo = $query->get('mobile_info', 0);

        $validator->validatePagination($firstResult, $maxResults);

        if ($query->has('result')) {
            $intCriteria['result'] = $query->get('result');
        }

        if ($query->has('ip')) {
            $strCriteria['ip'] = ip2long($query->get('ip'));
        }

        // 取得排序資訊
        $orderBy = $parameterHandler->orderBy($query->get('sort'), $query->get('order'));

        // 取得時間參數
        if ($query->has('start')) {
            $time['start'] = $parameterHandler->datetimeToYmdHis($query->get('start'));

            /*
             * login_log每週刪一次，但login_log_mobile每天刪一次
             * 會有60~65天前的手機login_log沒有對應的login_log_mobile
             * 這邊限制最多只撈近60天避免回傳不完整的資料
             */
            $minTime = (new \DateTime())->modify('-60 days')->format('Y-m-d 00:00:00');

            if (strtotime($time['start']) < strtotime($minTime)) {
                $time['start'] = $minTime;
            }
        }

        if ($query->has('end')) {
            $time['end'] = $parameterHandler->datetimeToYmdHis($query->get('end'));
        }

        $repo = $em->getRepository('BB\DurianBundle\Entity\LoginLog');

        $user = $this->findUser($userId);

        $total = $repo->countByUser(
            $user,
            $intCriteria,
            $strCriteria,
            $time
        );

        $logs = $repo->getByUser(
            $user,
            $intCriteria,
            $strCriteria,
            $time,
            $mobileInfo,
            $orderBy,
            $firstResult,
            $maxResults
        );

        foreach ($logs as $log) {
            $log['ip'] = long2ip($log['ip']);
            $log['at'] = $log['at']->format(\DateTime::ISO8601);

            if (isset($log['mobile'])) {
                $log['mobile']['login_log_id'] = $log['mobile']['loginLogId'];
                unset($log['mobile']['loginLogId']);
            }

            $loginlogs[] = $log;
        }

        $output['result'] = 'ok';
        $output['ret'] = $loginlogs;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取指定廳時間點後修改資料的會員
     *
     * @Route("/domain/{domain}/modified_user",
     *        name = "api_get_modified_user_by_domain",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $domain
     * @return JsonResponse
     */
    public function getModifiedUserByDomainAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $query = $request->query;

        $beginAt = $query->get('begin_at');
        $firstResult = $query->get('first_result', 0);
        $maxResults = $query->get('max_results', 100);
        $userRepo = $em->getRepository('BBDurianBundle:User');

        if (!$beginAt) {
            throw new \InvalidArgumentException('No begin_at specified', 150010065);
        }

        $sensitiveLogger->validateAllowedOperator($domain);

        $at = $parameterHandler->datetimeToYmdHis($beginAt);
        $limit = array(
            'first' => $firstResult,
            'max'   => $maxResults
        );

        //若沒資料則回傳空陣列及帶入的時間條件
        $mergedRet = array();
        $output['last_modified'] = $at;

        $total = $userRepo->countModifiedUserByDomain($domain, $at);

        if ($total > 0) {
            $ret = $userRepo->getModifiedUserByDomain($domain, $at, $limit);

            //把$ret[0](user), $ret[1](userDetail)合併為一個陣列再output
            for ($i = 0; $i < count($ret); $i = $i+3) {
                $userRet = $ret[$i]->toArray();

                unset($userRet['password']);
                unset($userRet['password_expire_at']);
                unset($userRet['password_reset']);

                $udIndex = $i+1;
                $userDetailRet = [];
                if (!empty($ret[$udIndex])) {
                    $userDetailRet = $ret[$udIndex]->toArray();

                    unset($userDetailRet['id']);
                    unset($userDetailRet['password']);
                }

                $ueIndex = $i+2;
                $userDetailRet['email'] = $ret[$ueIndex]->getEmail();

                $mergedRet[] = array_merge($userRet, $userDetailRet);
            }
        }

        $bottom = count($mergedRet) - 1;
        if ($bottom >= 0) {
            $output['last_modified'] = $mergedRet[$bottom]['modified_at'];
        }

        $output['result'] = 'ok';
        $output['ret'] = $mergedRet;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取指定廳時間點後刪除的會員
     *
     * @Route("/domain/{domain}/removed_user",
     *        name = "api_get_removed_user_by_domain",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $domain
     * @return JsonResponse
     */
    public function getRemovedUserByDomainAction(Request $request, $domain)
    {
        $em = $this->getEntityManager('share');
        $parameterHandler = $this->get('durian.parameter_handler');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $query = $request->query;

        $beginAt = $query->get('begin_at');
        $firstResult = $query->get('first_result', 0);
        $maxResults = $query->get('max_results', 100);
        $userRepo = $em->getRepository('BBDurianBundle:RemovedUser');
        $currencyOperator = $this->get('durian.currency');

        if (!$beginAt) {
            throw new \InvalidArgumentException('No begin_at specified', 150010065);
        }

        $sensitiveLogger->validateAllowedOperator($domain);

        $at = $parameterHandler->datetimeToYmdHis($beginAt);
        $limit = array(
            'first' => $firstResult,
            'max'   => $maxResults
        );

        //若沒資料則回傳空陣列及帶入的時間條件
        $output['last_modified'] = $at;

        $total = $userRepo->countRemovedUserByDomain($domain, $at);
        $ret = $userRepo->getRemovedUserByDomain($domain, $at, $limit);

        $fields = [
            'created_at',
            'modified_at',
            'last_login'
        ];

        foreach ($ret as $i => $info) {
            $ret[$i]['currency'] = $currencyOperator->getMappedCode($ret[$i]['currency']);

            if ($ret[$i]['birthday']) {
                $ret[$i]['birthday'] = $ret[$i]['birthday']->format('Y-m-d');
            }

            $fieldFilter = $this->fieldFilter($ret[$i], $fields);

            $ret[$i]['created_at'] = $fieldFilter['created_at'];
            $ret[$i]['modified_at'] = $fieldFilter['modified_at'];
            $ret[$i]['last_login'] = $fieldFilter['last_login'];

            $output['last_modified'] = $ret[$i]['modified_at'];
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取指定廳時間區間內新增會員的詳細相關資訊
     *
     * @Route("/domain/{domain}/member_detail",
     *        name = "api_get_member_detail_by_domain",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $domain
     * @return JsonResponse
     */
    public function getMemberDetailAction(Request $request, $domain)
    {
        $em               = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $sensitiveLogger  = $this->get('durian.sensitive_logger');
        $query            = $request->query;

        $startAt          = $query->get('start_at');
        $endAt            = $query->get('end_at');
        $firstResult      = $query->get('first_result');
        $maxResults       = $query->get('max_results');
        $userRepo         = $em->getRepository('BBDurianBundle:User');
        $bankRepo         = $em->getRepository('BBDurianBundle:Bank');

        $users = array();
        $stats = array();

        if (!$startAt) {
            throw new \InvalidArgumentException('No start_at specified', 150010067);
        }

        if (!$endAt) {
            throw new \InvalidArgumentException('No end_at specified', 150010068);
        }

        $startAtString = $parameterHandler->datetimeToYmdHis($startAt);
        $endAtString = $parameterHandler->datetimeToYmdHis($endAt);

        $sensitiveLogger->validateAllowedOperator($domain);

        $result = $userRepo->getMemberDetail($domain, $startAtString, $endAtString, $firstResult, $maxResults);

        //整理出回傳的userId並修改成tableize欄位KEY
        foreach ($result as $key => $ret) {
            $users[] = $ret['user_id'];

            $result[$key] = $this->fieldFilter($ret);

            if (isset($ret['createdAt']) && $ret['createdAt'] instanceof \DateTime) {
                $result[$key]['created_at'] = $ret['createdAt']->format('Y-m-d H:i:s');
            }

            if (isset($ret['birthday']) && $ret['birthday'] instanceof \DateTime) {
                $result[$key]['birthday'] = $ret['birthday']->format('Y-m-d H:i:s');
            }
        }

        $banks = $bankRepo->getBankArrayByUserIds($users);
        $allParent = $userRepo->getMemberAllParentUsername($users);
        $withdrawStats = $em->getRepository('BBDurianBundle:UserStat')->findBy(['userId' => $users]);

        //把出款統計資料的userId 寫至Key
        foreach ($withdrawStats as $stat) {
            $stats[$stat->getUserId()] = $stat->toArray();
            unset($stat);
        }

        foreach ($result as $key => $ret) {

            $result[$key]['banks'] = array();
            $result[$key]['withdraw_total'] = null;
            $result[$key]['withdraw_count'] = null;

            if (key_exists($ret['user_id'], $banks)) {
                $result[$key]['banks'] = $banks[$ret['user_id']];
            }
            if (key_exists($ret['user_id'], $allParent)) {
                $result[$key] = array_merge($result[$key], $allParent[$ret['user_id']]);
            }

            if (key_exists($ret['user_id'], $stats)) {
                $result[$key]['withdraw_total'] = -$stats[$ret['user_id']]['withdraw_total'];
                $result[$key]['withdraw_count'] = $stats[$ret['user_id']]['withdraw_count'];
            }
            unset($ret);
        }

        $total = $userRepo->countMemberDetail($domain, $startAtString, $endAtString);

        $output['result'] = 'ok';
        $output['ret'] = $result;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者是否已線上入款過
     *
     * @Route("/user/{userId}/deposited",
     *        name = "api_get_user_deposited",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getDepositedAction($userId)
    {
        $em = $this->getEntityManager();

        $ret = false;
        $user = $this->findUser($userId);
        $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());

        if ($userStat) {
            $count = $userStat->getDepositCount();
            $count += $userStat->getRemitCount();
            $count += $userStat->getManualCount();
            $count += $userStat->getSudaCount();

            if ($count > 0) {
                $ret = true;
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 根據使用者id，取得使用者出入款統計資料
     *
     * @Route("/user/stat",
     *        name = "api_get_user_stat",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatAction(Request $request)
    {
        $em = $this->getEntityManager();

        $userIds = $request->get('user_id');

        if (!is_array($userIds)) {
            throw new \InvalidArgumentException('Invalid user_id', 150010055);
        }

        if (count($userIds) == 0) {
            throw new \InvalidArgumentException('No user_id specified', 150010137);
        }

        $userStats = $em->getRepository('BBDurianBundle:UserStat')->findby(['userId' => $userIds]);

        $ret = [];
        foreach ($userStats as $userStat) {
            $ret[] = $userStat->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 隱藏測試帳號開啟
     *
     * @Route("/user/{userId}/hidden_test/1",
     *        name = "api_user_hidden_test_on",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param  integer $userId
     * @return JsonResponse
     */
    public function setHiddenTestOnAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);
        $affectedNum = 0;

        //若$user->isHiddenTest()為false才紀錄
        if (!$user->isHiddenTest()) {
            $operationLogger = $this->get('durian.operation_logger');

            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('hidden_test', 'false', 'true');

            $operationLogger->save($log);

            $user->setModifiedAt($now);

            if ($user->getRole() == 1 && $user->isTest()) {
                $affectedNum++;
            }
        }

        //若下層會員是測試帳號且非隱藏測試帳號,都應從廳下層測試帳號統計內扣除
        $affectedNum += $em->getRepository('BBDurianBundle:User')
            ->countAllChildUserByHiddenTest($userId, false);

        $this->processDomainTotalTest($user->getDomain(), -$affectedNum);

        $user->setHiddenTest(true);
        $em->getRepository('BBDurianBundle:User')
            ->setHiddenTestUserOnAllChild($user);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 隱藏測試帳號關閉
     *
     * @Route("/user/{userId}/hidden_test/0",
     *        name = "api_user_hidden_test_off",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function setHiddenTestOffAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $now = new \DateTime();

        $user = $this->findUser($userId);

        if ($user->hasParent() && $user->getParent()->isHiddenTest()) {
            throw new \RuntimeException(
                'Can not set hidden test off when parent is hidden test user',
                150010098
            );
        }

        $sensitiveLogger->validateAllowedOperator($user);
        $affectedNum = 0;

        //若$user->isHiddenTest()為true才紀錄
        if ($user->isHiddenTest()) {
            $operationLogger = $this->get('durian.operation_logger');

            $log = $operationLogger->create('user', ['id' => $userId]);
            $log->addMessage('hidden_test', 'true', 'false');

            $operationLogger->save($log);

            $user->setModifiedAt($now);

            if ($user->getRole() == 1 && $user->isTest()) {
                $affectedNum++;
            }
        }

        //若下層會員是測試帳號且隱藏測試帳號,都應加入廳下層測試帳號統計內
        $affectedNum += $em->getRepository('BBDurianBundle:User')
            ->countAllChildUserByHiddenTest($userId, true);

        $this->processDomainTotalTest($user->getDomain(), $affectedNum);

        $user->setHiddenTest(false);
        $em->getRepository('BBDurianBundle:User')
            ->setHiddenTestUserOffAllChild($user);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者的上層id
     *
     * @Route("/user/{userId}/ancestor_id",
     *        name = "api_get_user_ancestor_id",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getAncestorIdAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $depth = $query->get('depth');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $ids = [];
        $total = 0;

        $validator->validatePagination($firstResult, $maxResults);

        $this->findUser($userId);

        // 取出指定上層的Id
        $ancestorIdArray = $em->getRepository('BBDurianBundle:UserAncestor')
            ->getAncestorIdBy($userId, $depth, $firstResult, $maxResults);

        $total = count($ancestorIdArray);
        if (isset($firstResult) && isset($maxResults)) {
            // 計算上層的userId數量
            $total = $em->getRepository('BBDurianBundle:UserAncestor')
                ->countAncestorIdBy($userId, $depth);
        }

        $output['result'] = 'ok';
        $output['ret'] = $ancestorIdArray;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得使用者的下層id
     *
     * @Route("/user/{userId}/children_id",
     *        name = "api_get_user_children_id",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getChildrenIdAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $depth = $query->get('depth');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $ids = [];
        $total = 0;

        $validator->validatePagination($firstResult, $maxResults);

        $this->findUser($userId);

        // 取出指定下層的Id
        $childrenIdArray = $em->getRepository('BBDurianBundle:UserAncestor')
            ->getChildrenIdBy($userId, $depth, $firstResult, $maxResults);

        $total = count($childrenIdArray);
        if (isset($firstResult) && isset($maxResults)) {
            // 計算指定下層的userId數量
            $total = $em->getRepository('BBDurianBundle:UserAncestor')
                ->countChildrenIdBy($userId, $depth);
        }

        $output['result'] = 'ok';
        $output['ret'] = $childrenIdArray;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得一筆被移除的使用者資訊
     *
     * @Route("/removed_user/{userId}",
     *        name = "api_get_removed_user",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者
     * @return JsonResponse
     */
    public function getRemovedUserByIdAction($userId)
    {
        $em = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $removedUser = $em->find('BBDurianBundle:RemovedUser', $userId);

        if (!$removedUser) {
            throw new \RuntimeException('No such removed user', 150010126);
        }

        $output['result'] = 'ok';
        $output['ret'] = $removedUser->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取指定時間點後刪除的會員
     *
     * @Route("/v2/removed_user_by_time",
     *        name = "api_v2_get_removed_user_by_time",
     *        defaults = {"_format" = "json"})
     *
     * @Route("/removed_user_by_time",
     *        name = "api_get_removed_user_by_time",
     *        defaults = {"_format" = "json"})
     *
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRemovedUserByTimeAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');

        $removedAt = $query->get('removed_at');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        if (!$removedAt) {
            throw new \InvalidArgumentException('No removed_at specified', 150010129);
        }

        if (!$validator->validateDate($removedAt)) {
            throw new \InvalidArgumentException('Invalid removed_at given', 150010130);
        }

        $validator->validatePagination($firstResult, $maxResults);

        $removeUserArray = $em->getRepository('BBDurianBundle:RemovedUser')
            ->getRemovedUserByTime($removedAt, $firstResult, $maxResults);

        $total = count($removeUserArray);
        if (isset($firstResult) && isset($maxResults)) {
            $total = $em->getRepository('BBDurianBundle:RemovedUser')
                ->countRemovedUserByTime($removedAt);
        }

        $output['result'] = 'ok';
        $output['ret'] = $removeUserArray;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得使用者信箱
     *
     * @Route("/user/{userId}/email",
     *        name = "api_user_get_email",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者ID
     * @return JsonResponse
     */
    public function getEmailAction($userId)
    {
        $em = $this->getEntityManager();

        $this->findUser($userId);

        $userEmail = $em->find('BBDurianBundle:UserEmail', $userId);

        $output['result'] = 'ok';
        $output['ret'] = $userEmail->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得多個被刪除使用者資訊
     *
     * @Route("/removed_users",
     *        name = "api_removed_users",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRemovedUsersAction(Request $request)
    {
        $currencyOperator = $this->get('durian.currency');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $em = $this->getEntityManager('share');
        $removedUserRepo = $em->getRepository('BBDurianBundle:RemovedUser');
        $query = $request->query;
        $userIds = $request->query->get('users', []);
        $detail = $query->get('detail', false);

        if (!is_array($userIds) || empty($userIds)) {
            throw new \InvalidArgumentException('Invalid users', 150010144);
        }

        $domain = null;

        foreach ($userIds as $index => $userId) {
            $userId = (int) $userId;
            $removedUser = $em->find('BBDurianBundle:RemovedUser', $userId);

            if (empty($userId) || !$removedUser) {
                unset($userIds[$index]);

                continue;
            }

            $domainCompare = $removedUser->getDomain();

            if (!$domain) {
                $domain = $removedUser->getDomain();
            }

            if ($domainCompare != $domain) {
                throw new \RuntimeException('User ids must be in the same domain', 150010145);
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = [];

        if (!empty($userIds)) {
            $ret = $removedUserRepo->getRemovedUserByUserIds($userIds, $detail);

            $fields = [
                'created_at',
                'modified_at',
                'last_login'
            ];

            foreach ($ret as $index => $info) {
                $ret[$index]['currency'] = $currencyOperator->getMappedCode($ret[$index]['currency']);

                if (isset($ret[$index]['birthday'])) {
                    $ret[$index]['birthday'] = $ret[$index]['birthday']->format('Y-m-d');
                }

                $fieldFilter = $this->fieldFilter($ret[$index], $fields);

                $ret[$index]['created_at'] = $fieldFilter['created_at'];
                $ret[$index]['modified_at'] = $fieldFilter['modified_at'];
                $ret[$index]['last_login'] = $fieldFilter['last_login'];
            }

            $output['ret'] = $ret;
            $sensitiveLogger->writeSensitiveLog();
        }

        return new JsonResponse($output);
    }

    /**
     * 取得使用者名稱
     *
     * @Route("/users/username",
     *        name = "api_user_get_username",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsernameAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $userIds = $query->get('users', []);

        if (!$userIds || !is_array($userIds)) {
            throw new \InvalidArgumentException('Invalid users', 150010149);
        }

        $users = $userRepo->getMultiUserByIds($userIds, 'username');

        $output['result'] = 'ok';
        $output['ret'] = $users;

        return new JsonResponse($output);
    }

    /**
     * 產生搜索資訊
     *
     * @param array $searchField 搜索欄位
     * @param array $searchValue 搜索資料
     */
    private function makeSearch(array $searchFields, array $searchValues)
    {
        $currencyOperator = $this->get('durian.currency');

        $searchSet = array();
        $userFields = array(
            'username',
            'alias', 'currency',
            'err_num',
            'last_bank'
        );
        $bankFields = array(
            'account'
        );
        $detailFields = array(
            'name_real',
            'name_english',
            'passport',
            'identity_card',
            'driver_license',
            'insurance_card',
            'health_card',
            'telephone',
            'qq_num'
        );
        $emailFields = ['email'];
        $depositWithdrawFields = [
            'deposit',
            'withdraw'
        ];

        foreach ($searchFields as $index => $searchField) {
            if (in_array($searchField, $userFields)) {
                $table = 'User';
            } elseif (in_array($searchField, $bankFields)) {
                $table = 'Bank';
            } elseif (in_array($searchField, $detailFields)) {
                $table = 'UserDetail';
            } elseif (in_array($searchField, $emailFields)) {
                $table = 'UserEmail';
            } elseif (in_array($searchField, $depositWithdrawFields)) {
                $table = 'UserHasDepositWithdraw';
            } else {
                continue;
            }

            // 去掉username空白格
            if ($searchField == 'username') {
                $searchValues[$index] = trim($searchValues[$index]);
            }

            if ($searchField == 'currency') {
                $searchValues[$index] = $currencyOperator->getMappedNum($searchValues[$index]);
            }

            $searchSet[$table][] = array(
                'field' => \Doctrine\Common\Util\Inflector::camelize($searchField),
                'value' => $searchValues[$index],
            );
        }

        return $searchSet;
    }

    /**
     * 取得使用者資訊
     *
     * @param User    $user        使用者
     * @param array   $fields      指定查詢的資料
     * @param boolean $isDbBalance 指定是否為資料庫的餘額(不含cash,cash皆直接取redis值)
     * @return array
     */
    private function getUserInfo(User $user, $fields = [], $isDbBalance = false)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $exchange = $this->get('durian.exchange');
        $currencyOperator = $this->get('durian.currency');

        $output = array();
        $all = false;
        $shareLimitException = null;

        // 預設傳回全部欄位
        if (empty($fields)) {
            $all = true;
        }

        $output['id'] = $user->getId();

        // all會回傳parent欄位，不需再傳parent_id
        if (in_array('parent_id', $fields)) {
            $output['parent_id'] = null;
            if ($user->hasParent()) {
                $parentId = $em->getUnitOfWork()
                               ->getEntityIdentifier($user->getParent());
                $output['parent_id'] = $parentId['id'];
            }
        }

        if ($all || in_array('parent', $fields)) {
            $output['parent'] = null;
            if ($user->hasParent()) {
                $parent = $user->getParent();
                $output['parent'] = $parent->getId();
            }
        }

        if ($all || in_array('all_parents', $fields)) {
            $output['all_parents'] = array();
            if ($user->hasParent()) {
                $output['all_parents'] = $user->getAllParentsId();
            }
        }

        if ($all || in_array('alias', $fields)) {
            $output['alias'] = $user->getAlias();
        }

        if ($all || in_array('created_at', $fields)) {
            $output['created_at'] = $user->getCreatedAt()->format(\DateTime::ISO8601);
        }

        if ($all || in_array('domain', $fields)) {
            $output['domain'] = $user->getDomain();
        }

        if ($all || in_array('modified_at', $fields)) {
            $output['modified_at'] = $user->getModifiedAt()->format(\DateTime::ISO8601);
        }

        if ($all || in_array('last_login', $fields)) {
            $lastLogin = null;
            if (null !== $user->getLastLogin()) {
                $lastLogin = $user->getLastLogin()->format(\DateTime::ISO8601);
            }

            $output['last_login'] = $lastLogin;
        }

        if ($all || in_array('last_login_ip', $fields)) {
            $lastLoginIp = null;
            $last = $em->find('BBDurianBundle:LastLogin', $user->getId());

            if ($last) {
                $lastLoginIp = $last->getIp();
            }

            $output['last_login_ip'] = $lastLoginIp;
        }

        if ($all || in_array('currency', $fields)) {
            $output['currency'] = $currencyOperator->getMappedCode($user->getCurrency());
        }

        if ($all || in_array('role', $fields)) {
            $output['role'] = $user->getRole();
        }

        if ($all || in_array('password_expire_at', $fields)) {
            $output['password_expire_at'] = $user->getPasswordExpireAt()->format(\DateTime::ISO8601);
        }

        if ($all || in_array('password_reset', $fields)) {
            $output['password_reset'] = $user->isPasswordReset();
        }

        if ($all || in_array('last_bank', $fields)) {
            $output['last_bank'] = $user->getLastBank();
        }

        if ($all || in_array('username', $fields)) {
            $output['username'] = $user->getUsername();
        }

        if ($all || in_array('enable', $fields)) {
            $output['enable'] = $user->isEnabled();
        }

        if ($all || in_array('block', $fields)) {
            $output['block'] = $user->isBlock();
        }

        if ($all || in_array('bankrupt', $fields)) {
            $output['bankrupt'] = $user->isBankrupt();
        }

        if ($all || in_array('sub', $fields)) {
            $output['sub'] = $user->isSub();
        }

        if ($all || in_array('test', $fields)) {
            $output['test'] = $user->isTest();
        }

        if ($all || in_array('hidden_test', $fields)) {
            $output['hidden_test'] = $user->isHiddenTest();
        }

        if ($all || in_array('rent', $fields)) {
            if ($user->isRent()) {
                $output['rent'] = true;
            } else {
                $output['rent'] = $this->get('durian.card_operator')
                                       ->checkParentIsRent($user);
            }
        }

        if ($all || in_array('size', $fields)) {
            $output['size'] = $user->getSize();
        }

        if ($all || in_array('err_num', $fields)) {
            $output['err_num'] = $user->getErrNum();
        }

        if ($all || in_array('login_code', $fields)) {
            $dcRepo = $emShare->getRepository('BBDurianBundle:DomainConfig');
            $criteria = ['domain' => $user->getDomain()];
            $config = $dcRepo->findOneBy($criteria);

            $output['login_code'] = '';
            if ($config) {
                $output['login_code'] = $config->getLoginCode();
            }
        }

        if ($all || in_array('cash', $fields)) {
            $output['cash'] = null;

            $cash = $user->getCash();

            if ($cash) {
                $output['cash'] = $cash->toArray();

                $redisCashInfo = $this->get('durian.op')->getRedisCashBalance($cash);

                $output['cash']['balance'] = $redisCashInfo['balance'];
                $output['cash']['pre_sub'] = $redisCashInfo['pre_sub'];
                $output['cash']['pre_add'] = $redisCashInfo['pre_add'];
            }
        }

        if ($all || in_array('cash_fake', $fields)) {
            $output['cash_fake'] = null;

            $cashFake = $user->getCashFake();

            if ($cashFake) {
                $enable = $this->get('durian.cashfake_op')
                    ->isEnabled($user, $cashFake->getCurrency());
                $output['cash_fake'] = $cashFake->toArray();
                $output['cash_fake']['enable'] = $enable;
            }

            if ($cashFake && !$isDbBalance) {
                $redisCashfakeInfo = $this->get('durian.cashfake_op')
                    ->getBalanceByRedis($user, $cashFake->getCurrency());

                $output['cash_fake']['balance'] = $redisCashfakeInfo['balance'];
                $output['cash_fake']['pre_sub'] = $redisCashfakeInfo['pre_sub'];
                $output['cash_fake']['pre_add'] = $redisCashfakeInfo['pre_add'];
            }
        }

        if ($all || in_array('credit', $fields)) {
            $tmp = array();
            $credits = $user->getCredits();
            $creditOp = $this->get('durian.credit_op');

            foreach ($credits as $credit) {
                $groupNum = $credit->getGroupNum();
                $tmp[$groupNum] = $credit->toArray();

                // 抓取上層credit的enable
                $enable = $creditOp->isEnabled($user->getId(), $credit->getGroupNum());
                $tmp[$groupNum]['enable'] = $enable;

                if (!$isDbBalance) {
                    $creditBalance = $creditOp->getBalanceByRedis($user->getId(), $groupNum);

                    $tmp[$groupNum]['line'] = $creditBalance['line'];
                    $tmp[$groupNum]['balance'] = $creditBalance['balance'];
                }

                if ($user->getCurrency() != 156) {
                    $tmp[$groupNum] = $exchange->exchangeCreditByCurrency($tmp[$groupNum], $user->getCurrency());
                }
            }

            $output['credit'] = $tmp;
        }

        if ($all || in_array('card', $fields)) {
            $output['card'] = null;

            $card = $user->getCard();

            if ($card) {
                $output['card'] = $card->toArray();
            }
        }

        if ($all || in_array('enabled_card', $fields)) {
            $output['enabled_card'] = null;

            $enabledCard = $this->get('durian.card_operator')->check($user);

            if ($enabledCard) {
                $output['enabled_card'] = $enabledCard->toArray();
            }
        }

        if ($all || in_array('outside', $fields)) {
            $output['outside'] = false;


            $repo = $em->getRepository('BBDurianBundle:UserPayway');
            $payway = $repo->getUserPayway($user);

            if ($payway && $payway->isOutsideEnabled()) {
                $output['outside'] = true;
            }
        }

        if ($all || in_array('sharelimit', $fields)) {
            $tmp = array();

            $shareLimits = $user->getShareLimits();

            foreach ($shareLimits as $share) {
                $tmp[$share->getGroupNum()] = $share->toArray();
            }

            $output['sharelimit'] = $tmp;
        }

        if ($all || in_array('sharelimit_next', $fields)) {
            $tmp = array();

            $shareLimitNexts = $user->getShareLimitNexts();

            foreach ($shareLimitNexts as $share) {
                $tmp[$share->getGroupNum()] = $share->toArray();
            }

            $output['sharelimit_next'] = $tmp;
        }

        if ($all || in_array('sharelimit_sys', $fields)) {
            $tmp = array();

            $dealer = $this->get('durian.share_dealer');
            $mocker = $this->get('durian.share_mocker');

            $dealer->setBaseUser($user);
            $allGroups = $this->getAllGroupNumber($user);

            foreach ($allGroups as $group) {

                // 沒有佔成就mock給它
                if (!$user->getShareLimit($group)) {
                    $mocker->mockShareLimit($user, $group);
                }

                $dealer->setGroupNum($group);

                try {
                    $tmp[$group] = $dealer->toArray();
                } catch (\Exception $e) {
                    $tmp[$group] = 'error';
                    $shareLimitException = $e;
                }

                // 算完就刪除mock的資料
                if ($mocker->hasMock()) {
                    $mocker->removeMockShareLimit($user, $group);
                }
            }

            $output['shareLimitException'] = $shareLimitException;
            $output['sharelimit_division'] = $tmp;
        }

        if ($all || in_array('sharelimit_next_sys', $fields)) {
            $tmp = array();

            $dealer = $this->get('durian.share_dealer');
            $mocker = $this->get('durian.share_mocker');

            $dealer->setBaseUser($user);
            $allGroups = $this->getAllGroupNumber($user);

            foreach ($allGroups as $group) {

                // 沒有佔成就mock給它
                if (!$user->getShareLimitNext($group)) {
                    $mocker->mockShareLimit($user, $group);
                    $mocker->mockShareLimitNext($user, $group);
                }

                $dealer->setGroupNum($group);
                $dealer->setIsNext(true);

                try {
                    $tmp[$group] = $dealer->toArray();
                } catch (\Exception $e) {
                    $tmp[$group] = 'error';
                    $shareLimitException = $e;
                }

                // 算完就刪除mock的資料
                if ($mocker->hasMock()) {
                    $mocker->removeMockShareLimit($user, $group, true);
                }
            }

            $output['shareLimitException'] = $shareLimitException;
            $output['sharelimit_next_division'] = $tmp;
        }

        if ($all || in_array('oauth', $fields)) {
            $tmp = array();
            $userId = $user->getId();
            $criteria = array('userId' => $userId);
            $bindings = $em->getRepository('BBDurianBundle:OauthUserBinding')->findBy($criteria);

            foreach ($bindings as $binding) {
                $tmp[] = array(
                    'vendor_id' => $binding->getVendor()->getId(),
                    'openid'    => $binding->getOpenid()
                );
            }

            $output['oauth'] = $tmp;
        }

        if ($all || in_array('deposit_withdraw', $fields)) {
            $userId = $user->getId();
            $output['deposit_withdraw'] = null;

            $depositWithdraw = $em->find('BBDurianBundle:UserHasDepositWithdraw', $userId);

            if ($depositWithdraw) {
                $output['deposit_withdraw'] = $depositWithdraw->toArray();
            }
        }

        return $output;
    }

    /**
     * 回傳user所有的佔成編號
     *
     * @param User $user 使用者
     * @return array
     */
    private function getAllGroupNumber(User $user)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:ShareLimit');

        $userId = $user->getId();
        // 會員無佔成，改抓上層的group
        if (1 == $user->getRole()) {
            $userId = $user->getParent()->getId();
        }

        return $repo->getAllGroupNum($userId);
    }

    /**
     * Generate user's cash
     *
     * @param User $user 使用者
     * @param Array $data 現金資料
     * @param boolean $isDomain 是否廳主
     *
     * @return Cash
     */
    private function generateCash($user, $data, $isDomain)
    {
        $em = $this->getEntityManager();
        $paywayOp = $this->get('durian.user_payway');
        $operationLogger = $this->get('durian.operation_logger');
        $currencyOperator = $this->get('durian.currency');

        // 非廳主需判斷上層是否支援cash
        if (!$isDomain) {
            $paywayOp->isParentEnabled($user, ['cash' => true]);
        }

        if (!isset($data['currency'])) {
            throw new \InvalidArgumentException('No currency specified', 150010141);
        }

        if (!$currencyOperator->isAvailable($data['currency'])) {
            throw new \InvalidArgumentException('Illegal currency', 150010101);
        }

        $currency = $currencyOperator->getMappedNum($data['currency']);
        $currencyCode = $currencyOperator->getMappedCode($currency);

        $cash = new Cash($user, $currency);
        $em->persist($cash);

        $log = $operationLogger->create('cash', ['user_id' => $user->getId()]);
        $log->addMessage('currency', $currencyCode);
        $operationLogger->save($log);

        return $cash;
    }

    /**
     * Generate user's cashFake
     *
     * @param User $user 使用者
     * @param Array $data 快開額度資料
     *
     * @return Array
     */
    private function generateCashFake($user, $data)
    {
        $em = $this->getEntityManager();
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->container->get('durian.operation_logger');

        if ($user->getParent() && !$user->getParent()->getCashFake()) {
            throw new \RuntimeException('No parent cashFake found', 150010111);
        }

        if (!isset($data['currency'])) {
            throw new \InvalidArgumentException('No currency specified', 150010141);
        }

        if (!$currencyOperator->isAvailable($data['currency'])) {
            throw new \InvalidArgumentException('Illegal currency', 150010101);
        }

        $operator = '';
        $currency = $currencyOperator->getMappedNum($data['currency']);
        $currencyCode = $currencyOperator->getMappedCode($currency);
        $balance = 0;

        if (isset($data['operator'])) {
            // 驗證參數編碼是否為utf8
            $validator->validateEncode($data['operator']);

            $operator = $data['operator'];
        }

        if (isset($data['balance'])) {
            $balance = $data['balance'];
        }

        $validator->validateDecimal($balance, CashFake::NUMBER_OF_DECIMAL_PLACES);

        $cashFake = new CashFake($user, $currency);
        $em->persist($cashFake);
        $em->flush();

        $log = $operationLogger->create('cash_fake', ['user_id' => $user->getId()]);
        $log->addMessage('currency', $currencyCode);
        $log->addMessage('balance', $balance);
        $log->addMessage('operator', $operator);
        $operationLogger->save($log);

        $result = $this->get('durian.cashfake_op')->newCashFake($cashFake, $balance, $operator);
        $transactionIdArray = [];

        //若有交易機制記錄，則抓出transactionId備用
        if ($result['entry']) {
            $transactionIdArray['user'] = $result['entry'][0]['id'];
        }

        //若有上層交易機制記錄，則抓出transactionId備用
        if ($result['parent_entry']) {
            $transactionIdArray['parent'] = $result['parent_entry'][0]['id'];
        }

        return $transactionIdArray;
    }

    /**
     * Generate user's credits
     *
     * @param User  $user 使用者
     * @param array $data 信用額度
     * @return array
     */
    private function generateCredit($user, $data)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');
        $credits = array();
        $creditOp = $this->get('durian.credit_op');
        $repo = $em->getRepository('BBDurianBundle:Credit');

        foreach ($data as $group => $value) {
            if (!$group) {
                continue;
            }

            //如上層沒有相對應的credit則噴錯
            if ($user->hasParent() && !$user->getParent()->getCredit($group)) {
                throw new \RuntimeException('No parent credit found', 150010112);
            }
        }

        $log = $operationLogger->create('credit', ['user_id' => $user->getId()]);

        $readyParentCredits = [];

        foreach ($data as $group => $value) {
            if (!$group) {
                continue;
            }

            if ($value['line'] == null) {
                $value['line'] = 0;
            }

            //幣別轉換
            if ($user->getCurrency() != 156) {
                $value['line'] = $this->exchangeReconv($value['line'], $user->getCurrency());
            }

            // 一律捨棄小數點
            $value['line'] = (int) floor($value['line']);

            try {
                //設定上層信用額度上限
                if ($user->hasParent()) {
                    $creditOp->addTotalLine(
                        $user->getParent()->getId(),
                        $group,
                        $value['line']
                    );

                    $readyParentCredits[] = [
                        $user->getParent()->getId(),
                        $group,
                        $value['line']
                    ];
                }

                $log->addMessage('group_num', $group);
                $log->addMessage('line', $value['line']);

                $line = (int) $value['line'];
                $credit = new Credit($user, $group);
                $em->persist($credit);

                if ($line != 0) {
                    if ($line > Credit::LINE_MAX) {
                        throw new \RangeException('Line exceeds the max value', 150010174);
                    }

                    if ($line < $credit->getTotalLine()) {
                        throw new \RuntimeException('Line is less than sum of children credit', 150010175);
                    }

                    if (-$line > $credit->getBalance()) {
                        throw new \RuntimeException('Line still in use can not be withdraw', 150010176);
                    }

                    $em->flush();
                    $repo->addLine($credit->getId(), $line);
                    $em->refresh($credit);

                    $pCredit = $credit->getParent();

                    // 上層調整額度總和
                    if ($pCredit) {
                        $newTotalLine = $pCredit->getTotalLine() + $line;

                        if ($newTotalLine > $pCredit->getLine()) {
                            throw new \RuntimeException('Not enough line to be dispensed', 150010177);
                        }

                        if ($newTotalLine < 0) {
                            throw new \RuntimeException('TotalLine can not be negative', 150010178);
                        }

                        $repo->addTotalLine($pCredit->getId(), $line);
                    }
                }
            } catch (\Exception $e) {
                foreach ($readyParentCredits as $parentCredit) {
                    $creditOp->addTotalLine(
                        $parentCredit[0],
                        $parentCredit[1],
                        $parentCredit[2] * -1
                    );
                }

                throw $e;
            }

            $credits[] = $credit;
        }
        $operationLogger->save($log);

        return $credits;
    }

    /**
     * Generate user payway
     * 1. 上層沒有 payway 就不新增
     * 2. 與上層 payway 相同也不新增
     *
     * @param User  $user 使用者
     * @param Array $ways 支援的交易方式
     */
    private function generateUserPayway(User $user, Array $ways = [])
    {
        $em = $this->getEntityManager();

        $parent = $user->getParent();
        if ($parent) {
            $parentPayway = $em->find('BBDurianBundle:UserPayway', $parent->getId());
            if (!$parentPayway) {
                return;
            }

            // 相同就不建立
            $sameWay = true;
            if ($ways['cash'] != $parentPayway->isCashEnabled()) {
                $sameWay = false;
            }

            if ($ways['cash_fake'] != $parentPayway->isCashFakeEnabled()) {
                $sameWay = false;
            }

            if ($ways['credit'] != $parentPayway->isCreditEnabled()) {
                $sameWay = false;
            }

            if ($ways['outside'] != $parentPayway->isOutsideEnabled()) {
                $sameWay = false;
            }

            if ($sameWay) {
                return;
            }
        }

        $paywayOp = $this->get('durian.user_payway');
        $paywayOp->create($user, $ways);
    }

    /**
     * Generate user's shareLimit
     *
     * @param ParameterBag $request
     * @param User $user 使用者
     * @return array
     */
    private function generateShareLimit($request, $user)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');
        $data = $request->get('sharelimit', array());
        $shares = array();
        $parentShares = array();
        $shareGroups = array();
        $parentShareGroups = array();

        if ($user->hasParent()) {
            $parentShares = $user->getParent()->getShareLimits();
            $parentShareGroups = $parentShares->getKeys();
        }

        $log = $operationLogger->create('share_limit', ['user_id' => $user->getId()]);
        foreach ($data as $group => $value) {

            if (is_null($group)) {
                continue;
            }

            //如上層沒有相對應的佔成則噴錯
            if ($user->hasParent() && !$user->getParent()->getShareLimit($group)) {
                throw new \RuntimeException('No parent sharelimit found', 150010113);
            }

            $log->addMessage('group_num', $group);
            $log->addMessage('upper', $value['upper']);
            $log->addMessage('lower', $value['lower']);
            $log->addMessage('parent_upper', $value['parent_upper']);
            $log->addMessage('parent_lower', $value['parent_lower']);

            $share = new ShareLimit($user, $group);

            $share->setUpper($value['upper'])
                  ->setLower($value['lower'])
                  ->setParentUpper($value['parent_upper'])
                  ->setParentLower($value['parent_lower']);

            $this->get('durian.share_validator')->prePersist($share);
            $em->persist($share);
            $shares[] = $share;
            $shareGroups[] = $group;
        }

        if (count(array_diff($parentShareGroups, $shareGroups)) > 0) {
            throw new \RuntimeException('Need to set sharelimit groups when parent have', 150010108);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        return $shares;
    }

    /**
     * Generate user's shareLimitNext
     *
     * @param ParameterBag $request
     * @param User $user 使用者
     * @return array
     */
    private function generateShareLimitNext($request, $user)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');
        //如有帶入預改參數，則使用預改。否則使用現行參數
        if ($request->has('sharelimit_next')) {
            $data = $request->get('sharelimit_next', array());
        } else {
            $data = $request->get('sharelimit', array());
        }

        $shares       = array();
        $parentShares = array();
        $shareGroups  = array();
        $parentShareGroups = array();

        if ($user->hasParent()) {
            $parentShares = $user->getParent()->getShareLimitNexts();
            $parentShareGroups = $parentShares->getKeys();
        }

        $log = $operationLogger->create('share_limit_next', ['user_id' => $user->getId()]);
        foreach ($data as $group => $value) {

            if (is_null($group)) {
                continue;
            }

            //如上層沒有相對應的佔成則噴錯
            if ($user->hasParent() && !$user->getParent()->getShareLimitNext($group)) {
                throw new \RuntimeException('No parent sharelimit_next found', 150010114);
            }

            $share = new ShareLimitNext($user, $group);

            $log->addMessage('group_num', $group);
            $log->addMessage('upper', $value['upper']);
            $log->addMessage('lower', $value['lower']);
            $log->addMessage('parent_upper', $value['parent_upper']);
            $log->addMessage('parent_lower', $value['parent_lower']);

            $share->setUpper($value['upper'])
                  ->setLower($value['lower'])
                  ->setParentUpper($value['parent_upper'])
                  ->setParentLower($value['parent_lower']);

            $this->get('durian.share_validator')->prePersist($share);
            $em->persist($share);
            $shares[] = $share;
            $shareGroups[] = $group;
        }

        if (count(array_diff($parentShareGroups, $shareGroups)) > 0) {
            throw new \RuntimeException('Need to set sharelimit_next groups when parent have', 150010109);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        return $shares;
    }

    /**
     * Generate DomainCurrency
     *
     * @param User $domain
     */
    private function generateDomainCurrency($domain)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');

        //廳主預設幣別
        $domainCurrencies = [
            156, // 人民幣
        ];

        //廳主預設顯示幣別
        $domainCurrencyPreset = 156;

        $log = $operationLogger->create('domain_currency', ['domain' => $domain->getId()]);

        foreach ($domainCurrencies as $currency) {

            $dcDefault = new DomainCurrency($domain, $currency);
            $em->persist($dcDefault);

            // 開啟預設顯示
            if ($currency == $domainCurrencyPreset) {
                $dcDefault->presetOn();
            }

            $log->addMessage('currency', $currency);
        }

        $operationLogger->save($log);
    }

    /**
     * Generate OauthUserBinding
     *
     * @param integer $uid 使用者編號
     * @param OauthVendor $oauthVendor oauth廠商
     * @param string $oauthOpenid 唯一識別id
     * @param integer $domain 廳主
     */
    private function generateOauthUserBinding($uid, $oauthVendor, $oauthOpenid, $domain)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');

        $binding = new OauthUserBinding($uid, $oauthVendor,$oauthOpenid);
        $em->persist($binding);
        $em->flush();

        $log = $operationLogger->create('oauth_user_binding', ['id' => $binding->getId()]);
        $log->addMessage('uid', $uid);
        $log->addMessage('oauth_vendor_id', $oauthVendor->getId());
        $log->addMessage('domain', $domain);
        $log->addMessage('openid', $oauthOpenid);
        $operationLogger->save($log);
    }

    /**
     * Edit user's shareLimit
     *
     * @param User $user 使用者
     * @param array $data 佔成資料
     *
     * @return bool 是否修改到佔成
     */
    private function editShareLimit($user, $data)
    {
        $ret = false;
        $operationLogger = $this->get('durian.operation_logger');

        foreach ($data as $group => $value) {

            if (is_null($group)) {
                continue;
            }

            $share = $user->getShareLimit($group);

            $changed = false;

            $majorKey = [
                'user_id' => $user->getId(),
                'group_num' => $group
            ];

            $log = $operationLogger->create('share_limit', $majorKey);

            if (!$share) {
                throw new ShareLimitNotExists($user, $group, false);
            }

            if ($share->getUpper() != $value['upper']) {
                $log->addMessage('upper', $share->getUpper(), $value['upper']);
                $changed = true;
            }

            if ($share->getLower() != $value['lower']) {
                $log->addMessage('lower', $share->getLower(), $value['lower']);
                $changed = true;
            }

            if ($share->getParentUpper() != $value['parent_upper']) {
                $log->addMessage('parent_upper', $share->getParentUpper(), $value['parent_upper']);
                $changed = true;
            }

            if ($share->getParentLower() != $value['parent_lower']) {
                $log->addMessage('parent_lower', $share->getParentLower(), $value['parent_lower']);
                $changed = true;
            }

            $share->setUpper($value['upper'])
                  ->setLower($value['lower'])
                  ->setParentUpper($value['parent_upper'])
                  ->setParentLower($value['parent_lower']);

            $this->get('durian.share_validator')->prePersist($share);
            $ret = true;

            if ($log->getMessage()) {
                $operationLogger->save($log);
            }
        }

        return $ret;
    }

    /**
     * Edit user's shareLimitNext
     *
     * @param User $user 使用者
     * @param array $data 佔成資料
     *
     * @return bool 是否修改到佔成
     */
    private function editShareLimitNext($user, $data)
    {
        $ret = false;
        $operationLogger = $this->get('durian.operation_logger');

        foreach ($data as $group => $value) {

            if (is_null($group)) {
                continue;
            }

            $share = $user->getShareLimitNext($group);

            $changed = false;

            $majorKey = [
                'user_id' => $user->getId(),
                'group_num' => $group
            ];

            $log = $operationLogger->create('share_limit_next', $majorKey);

            if (!$share) {
                throw new ShareLimitNotExists($user, $group, true);
            }

            if ($share->getUpper() != $value['upper']) {
                $log->addMessage('upper', $share->getUpper(), $value['upper']);
                $changed = true;
            }

            if ($share->getLower() != $value['lower']) {
                $log->addMessage('lower', $share->getLower(), $value['lower']);
                $changed = true;
            }

            if ($share->getParentUpper() != $value['parent_upper']) {
                $log->addMessage('parent_upper', $share->getParentUpper(), $value['parent_upper']);
                $changed = true;
            }

            if ($share->getParentLower() != $value['parent_lower']) {
                $log->addMessage('parent_lower', $share->getParentLower(), $value['parent_lower']);
                $changed = true;
            }

            $share->setUpper($value['upper'])
                  ->setLower($value['lower'])
                  ->setParentUpper($value['parent_upper'])
                  ->setParentLower($value['parent_lower']);

            $this->get('durian.share_validator')->prePersist($share);
            $ret = true;

            if ($log->getMessage()) {
                $operationLogger->save($log);
            }
        }

        return $ret;
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
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @param boolean $master 是否連線至master
     * @return User
     */
    private function findUser($userId, $master = false)
    {
        $em = $this->getEntityManager();

        if ($master) {
            $em->getConnection()->connect('master');
        }

        $user = $em->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150010029);
        }

        return $user;
    }

    /**
     * 取得廳主
     *
     * @param integer $domain 廳主ID
     * @return User
     */
    private function findDomain($domain)
    {
        $user = $this->findUser($domain);

        if (!is_null($user->getParent())) {
            throw new \RuntimeException('Not a domain', 150010051);
        }

        return $user;
    }

    /**
     * 根據$fields參數過濾掉不需要的資料
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    private function fieldFilter($data, $fields = array())
    {
        $result = array();
        $all = false;

        $intFields = array(
            'id',
            'domain',
            'size',
            'err_num',
            'last_bank',
            'role'
        );

        $dateFields = array(
            'created_at',
            'modified_at',
            'last_login',
            'password_expire_at'
        );

        $boolFields = array(
            'enable',
            'sub',
            'block',
            'bankrupt',
            'test',
            'hidden_test',
            'rent',
            'password_reset'
        );

        if (empty($fields)) {
            $all = true;
        }

        foreach ($data as $key => $value) {
            $key = \Doctrine\Common\Util\Inflector::tableize($key);

            if ($value instanceof \DateTime) {
                $value = $value->format(\DateTime::ISO8601);
            }

            if (in_array($key, $intFields) && is_string($value)) {
                $value = (int)$value;
            }

            if (in_array($key, $dateFields) && is_string($value)) {
                $value = new \DateTime($value);
                $value = $value->format(\DateTime::ISO8601);
            }

            if (in_array($key, $boolFields) &&  is_string($value)) {
                $value = (bool)$value;
            }

            if ($all || in_array($key, $fields)) {
                $result[$key] = $value;
            }
        }

        /**
         * 若parent_id為null，DQL回傳的$data便不會回傳parent_id的資料，
         * 固須手動填加parent_id欄位以維持回傳格式的一致性。
         */
        if (in_array('parent_id', $fields) && !isset($result['parent_id'])) {
            $result['parent_id'] = null;
        }

        return $result;
    }

    /**
     * 檢查fields是否為User Entity關聯欄位(因rent必須參考上層，故不列在unMappedFields上)
     * 關聯欄位，即在User Table中沒有資料的欄位
     *
     * @param array $fields
     * @return boolean
     */
    private function areMappedFields(array $fields)
    {
        if (empty($fields)) {
            return true;
        }

        $unMappedFields = [
            'id',
            'parent_id',
            'username',
            'domain',
            'alias',
            'sub',
            'enable',
            'block',
            'bankrupt',
            'test',
            'hidden_test',
            'size',
            'err_num',
            'currency',
            'created_at',
            'modified_at',
            'password_expire_at',
            'password_reset',
            'last_login',
            'last_bank',
            'role'
        ];

        $result = array_diff($fields, $unMappedFields);

        return count($result) > 0 ?  true : false;
    }

    /**
     * 從傳入的幣別轉為基本幣
     *
     * @param Integer $value
     * @param Integer $currency
     * @return Integer
     */
    private function exchangeReconv($value, $currency)
    {
        if (!$value) {
            return 0;
        }

        $exchange = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange')
            ->findByCurrencyAt($currency, new \dateTime('now'));

        if (!$exchange) {
            throw new \RuntimeException('No such exchange', 150010106);
        }

        return $exchange->reconvertByBasic($value);
    }

    /**
     * 當例外發生時可以用來回溯Redis裡的信用額度資料
     *
     * @param Array $credits
     */
    private function rollbackCreditTotalLine($credits, $data = null)
    {
        $em = $this->getEntityManager();
        $creditOp = $this->get('durian.credit_op');

        foreach ($credits as $credit) {
            if ($credit instanceof Credit) {
                $pCredit = $credit->getParent();
                if ($pCredit) {
                    $parentId = $pCredit->getUser()->getId();
                    $groupNum = $pCredit->getGroupNum();
                    $creditOp->addTotalLine($parentId, $groupNum, -$data[$groupNum]['line']);
                }
            } else {
                $creditOb = $em->find('BB\DurianBundle\Entity\Credit', $credit['id']);
                $creditOp->setLine(
                    $credit['line'] - $credit['line_diff'],
                    $creditOb
                );
            }
        }
    }

    /**
     * 處理新增使用者IP統計
     *
     * @param User $user
     * @param string $clientIp
     */
    private function processUserCreatedPerIp(User $user, $clientIp)
    {
        if (empty($clientIp)) {
            return;
        }

        if ($user->getRole() != 1) {
            return;
        }

        $em          = $this->getEntityManager('share');
        $createdHour = $user->getCreatedAt()->format('YmdH\0000');
        $ipNumber    = ip2long($clientIp);

        $criteria = array(
            'ip'     => $ipNumber,
            'at'     => $createdHour,
            'domain' => $user->getDomain()
        );

        $repo = $em->getRepository('BBDurianBundle:UserCreatedPerIp');
        $stat = $repo->findOneBy($criteria);

        if (!$stat) {
            $stat = new UserCreatedPerIp($clientIp, $user->getCreatedAt(), $user->getDomain());
            $em->persist($stat);
            $em->flush();
        }

        $repo->increaseCount($stat->getId());
    }

    /**
     * 判斷ip是否正常新增使用者
     *
     * 判斷條件：
     *    1.domain有設定且開啟阻擋新增使用者才判斷條件2,否則回傳正常
     *    2.若同一domain IP一天內超出設定的限制,則判斷條件3,否則回傳正常
     *    3.若時效內已存在該ip的封鎖列表紀錄,則不用再新增封鎖列表紀錄,回傳正常,否則判斷條件4
     *    4.若30天內曾經被封鎖過IP,則回傳異常,否則判斷條件5
     *    5.若超出最多可新增的設定限制,則回傳異常,否則回傳正常
     *
     * @param integer $domainId 廳主id
     * @param string  $clientIp 操作者ip
     * @return boolean
     *
     * @author petty 2014.10.06
     */
    private function checkIpIsNormal($domainId, $clientIp)
    {
        if (empty($clientIp)) {
            return true;
        }

        $em = $this->getEntityManager('share');
        $config = $em->find('BBDurianBundle:DomainConfig', $domainId);

        // 若domain沒有設定阻擋新增使用者,則回傳正常狀態
        if (!$config) {
            return true;
        }

        // 若domain設定為不阻擋新增使用者,則回傳正常狀態
        if (!$config->isBlockCreateUser()) {
            return true;
        }

        // 統計一天內的新增使用者數量是否超過異常
        $now = new \DateTime('now');
        $cloneNow = clone $now;
        $yesterday = $cloneNow->sub(new \DateInterval('P1D')); // 減 1 day
        $start = $yesterday->format('YmdHis');
        $end = $now->format('YmdH0000');

        $criteria = [
            'domain'    => $domainId,
            'ip'        => ip2long($clientIp),
            'startTime' => $start,
            'endTime'   => $end
        ];

        $repo = $em->getRepository('BBDurianBundle:UserCreatedPerIp');

        $sum = 1; // 計算已新增使用者人數,先加上目前這一次新增
        $sum += $repo->sumUserCreatedPerIp($criteria);

        // 未超過限制,判定為新增使用者正常
        if ($sum < DomainConfig::LIMITED_CREATED_USER_TIMES) {
            return true;
        }

        $repo = $em->getRepository('BBDurianBundle:IpBlacklist');

        // 若時效內已存在封鎖列表紀錄,回傳true正常狀態
        if ($repo->hasBlockCreateUser($domainId, $clientIp)) {
            return true;
        }

        // 因已超出近期被封鎖的IP可新增的次數,則檢查30天內是否被封鎖過
        $startTime = clone $now;

        $listCriteria = [
            'domain'     => $domainId,
            'ip'         => $clientIp,
            'createUser' => 1,
            'start'      => $startTime->sub(new \DateInterval('P30D')),
            'end'        => $now
        ];

        $countList = $repo->countListBy($listCriteria);

        // 30天內曾經被封鎖,則回傳異常狀態
        if ($countList > 0) {
            return false;
        }

        // 檢查是否超出最多可新增的設定限制
        if ($sum < DomainConfig::MAX_CREATE_USER_TIMES) {
            return true;
        }

        return false;
    }

    /**
     * 取得使用者附屬資訊
     *
     * @param User  $user   使用者
     * @param array $fields
     * @param array $userRet
     * @return array
     */
    private function getUserRet($user, $fields, $userRet)
    {
        $all = false;

        if (empty($fields)) {
            $all = true;
        }

        if ($all || in_array('parent', $fields)) {
            $parent = $user->getParent();
            if ($parent && !in_array($parent->toArray(), $userRet)) {
                $userRet[] = $parent->toArray();
            }
        }

        if ($all || in_array('all_parents', $fields)) {
            foreach ($user->getAllParents() as $parent) {
                if (!in_array($parent->toArray(), $userRet)) {
                    $userRet[] = $parent->toArray();
                }
            }
        }

        return $userRet;
    }

    /**
     * 產生封鎖列表與黑名單
     *
     * @param string $clientIp 操作者ip
     * @param User $user 使用者
     * @return false | Blacklist
     */
    private function generateBlackListIfAbnormal($clientIp, $user)
    {
        // 檢查是否正常新增使用者
        $isCreateNormal = $this->checkIpIsNormal($user->getDomain(), $clientIp);

        if ($isCreateNormal) {
            return;
        }

        // 24小時內 ip 封鎖列表至少有一筆不同廳紀錄，且黑名單沒有紀錄需加進黑名單
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:IpBlacklist');
        $operationLogger = $this->get('durian.operation_logger');

        $now = new \DateTime('now');
        $cloneNow = clone $now;
        $yesterday = $cloneNow->sub(new \DateInterval('P1D'));

        $criteria = [
            'ip' => $clientIp,
            'removed' => false,
            'createUser' => true,
            'start' => $yesterday->format('Y-m-d H:i:s'),
            'end' => $now->format('Y-m-d H:i:s')
        ];

        $ipBlacklist = $repo->getListBy($criteria, [], null, null, 'domain');
        $count = count($ipBlacklist);

        // 阻擋同廳同分秒跨天新增
        $sameDomain = false;

        if ($count == 1 && $ipBlacklist[0]->getDomain() == $user->getDomain()) {
            $sameDomain = true;
        }

        // 檢查黑名單有沒有此筆ip紀錄，有的話則不再加入黑名單
        $repo = $emShare->getRepository('BBDurianBundle:Blacklist');
        $blacklistCriteria = [
            'wholeDomain' => true,
            'ip' => ip2long($criteria['ip'])
        ];

        $oldBlacklist = $repo->findOneBy($blacklistCriteria);

        $createBlacklist = false;

        if ($count != 0 && !$oldBlacklist && !$sameDomain) {
            $blacklist = new Blacklist();
            $blacklist->setIp($clientIp);
            $blacklist->setSystemLock(true);
            $blacklist->setControlTerminal(true);

            $emShare->persist($blacklist);
            $createBlacklist = $blacklist;

            $blacklistLog = $operationLogger->create('blacklist', []);
            $blacklistLog->addMessage('whole_domain', var_export($blacklist->isWholeDomain(), true));
            $blacklistLog->addMessage('ip', $blacklist->getIp());
            $blacklistLog->addMessage('created_at', $blacklist->getCreatedAt()->format('Y-m-d H:i:s'));
            $blacklistLog->addMessage('modified_at', $blacklist->getModifiedAt()->format('Y-m-d H:i:s'));
            $operationLogger->save($blacklistLog);
        }

        // 加到封鎖列表，需阻擋同分秒新增同廳封鎖列表
        if (!$sameDomain) {
            $ipBlock = new IpBlacklist($user->getDomain(), $clientIp);
            $ipBlock->setCreateUser(true);
            $emShare->persist($ipBlock);
            $emShare->flush();

            $ipBlacklistLog = $operationLogger->create('ip_blacklist', []);
            $ipBlacklistLog->addMessage('domain', $ipBlock->getDomain());
            $ipBlacklistLog->addMessage('ip', $ipBlock->getIp());
            $ipBlacklistLog->addMessage('create_user', var_export($ipBlock->isCreateUser(), true));
            $ipBlacklistLog->addMessage('removed', var_export($ipBlock->isRemoved(), true));
            $ipBlacklistLog->addMessage('created_at', $ipBlock->getCreatedAt()->format(\DateTime::ISO8601));
            $ipBlacklistLog->addMessage('modified_at', $ipBlock->getModifiedAt()->format(\DateTime::ISO8601));
            $ipBlacklistLog->addMessage('operator', $ipBlock->getOperator());
            $operationLogger->save($ipBlacklistLog);
        }

        return $createBlacklist;
    }

    /**
     * 產生使用者密碼資料表
     *
     * @param User $user 使用者
     */
    private function generateUserPassword($user)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');

        $userPassword = new UserPassword($user);
        $userPassword->setHash('');

        //若使用者未停用密碼或未綁定oauth帳號，則將密碼加密
        if ($user->getPassword()) {
            $userPassword->setHash(password_hash($user->getPassword(), PASSWORD_BCRYPT));
        }

        $userPassword->setModifiedAt($user->getModifiedAt())
            ->setExpireAt($user->getPasswordExpireAt())
            ->setReset($user->isPasswordReset());
        $em->persist($userPassword);

        //新增使用者密碼操作紀錄
        $passwordLog = $operationLogger->create('user_password', ['user_id' => $user->getId()]);
        $passwordLog->addMessage('hash', 'new');
        $operationLogger->save($passwordLog);
    }

    /**
     * 產生使用者詳細資料表
     *
     * @param User $user 使用者
     * @param Array $parameter 詳細資料參數
     *
     * @return Array
     */
    private function generateUserDetail($user, $parameter)
    {
        $em = $this->getEntityManager();
        $generator = $this->get('durian.userdetail_generator');
        $operationLogger = $this->get('durian.operation_logger');

        foreach ($parameter as $key => $value) {
            // 密碼不須前後去空白
            if ($key == 'password') {
                continue;
            }

            $parameter[$key] = trim($value);
        }

        $detail = $generator->create($user, $parameter);
        $em->persist($detail);

        $detailData = $detail->toArray();
        $log = $operationLogger->create('user_detail', ['user_id' => $user->getId()]);

        foreach($detailData as $key => $value) {
            if ($key == 'user_id') {
                continue;
            }

            if ($key == 'password') {
                $log->addMessage('password', 'new');
                continue;
            }

            $log->addMessage($key, $value);
        }

        $operationLogger->save($log);

        return $detail;
    }

    /**
     * 產生使用者電子郵件資料表
     *
     * @param User $user 使用者
     * @param Array $parameter 詳細資料參數
     *
     * @return UserEmail
     */
    private function generateUserEmail($user, $parameter)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');
        $userValidator = $this->get('durian.user_validator');

        $userEmail = new UserEmail($user);
        $emailLog = $operationLogger->create('user_email', ['user_id' => $user->getId()]);

        $email = 'NULL';
        $userEmail->setEmail('');

        if (isset($parameter['email'])) {
            $userValidator->validateEmail($parameter['email']);
            $userEmail->setEmail($parameter['email']);
            $email = $parameter['email'];
        }

        $emailLog->addMessage('email', $email);
        $operationLogger->save($emailLog);

        $em->persist($userEmail);
        $em->flush();

        return $userEmail;
    }

    /**
     * 產生使用者
     *
     * @param Array $parameterArray 參數陣列
     *
     * @return Array
     */
    private function newUser($parameterArray)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');

        $userLog = $operationLogger->create('user', ['id' => $parameterArray['uid']]);
        $userLog->addMessage('username', $parameterArray['username']);
        $userLog->addMessage('domain', $parameterArray['domain']);
        $userLog->addMessage('alias', $parameterArray['alias']);
        $userLog->addMessage('sub', var_export($parameterArray['sub'], true));
        $userLog->addMessage('enable', var_export($parameterArray['enable'], true));
        $userLog->addMessage('block', var_export($parameterArray['block'], true));
        $userLog->addMessage('disabled_password', var_export($parameterArray['disabled_password'], true));
        $userLog->addMessage('password', 'new');
        $userLog->addMessage('test', var_export($parameterArray['test'], true));
        $userLog->addMessage('currency', $parameterArray['currency']);
        $userLog->addMessage('rent', var_export($parameterArray['rent'], true));
        $userLog->addMessage('password_reset', var_export($parameterArray['password_reset'], true));
        $userLog->addMessage('role', $parameterArray['role']);

        if (isset($parameterArray['entrance'])) {
            $userLog->addMessage('entrance', $parameterArray['entrance']);
        }

        $operationLogger->save($userLog);

        $user = new User();
        $user->setId($parameterArray['uid'])
             ->setUsername($parameterArray['username'])
             ->setAlias($parameterArray['alias'])
             ->setPassword($parameterArray['password'])
             ->setSub($parameterArray['sub'])
             ->setCurrency($parameterArray['currency_num'])
             ->setDomain($parameterArray['domain'])
             ->setRole($parameterArray['role']);

        if (isset($parameterArray['parent'])) {
            $this->get('durian.user_manager')->setParent($user, $parameterArray['parent']);
        }

        $sensitiveLogger->validateAllowedOperator($user);
        $em->persist($user);

        $this->get('durian.ancestor_manager')->generateAncestor($user);

        if (!$parameterArray['enable']) {
            $user->disable();
        }

        if ($parameterArray['block']) {
            $user->block();
        }

        //上層是測試帳號下層一定是測試帳號。上層不是測試帳號，可以指定為測試帳號
        if ($user->hasParent() && $user->getParent()->isTest()) {
             $parameterArray['test'] = true;
        }

        if ($parameterArray['test']) {
            $user->setTest(true);
        }

        if ($user->hasParent() && $user->getParent()->isHiddenTest()) {
            $parameterArray['hidden_test'] = true;
        }

        if ($parameterArray['hidden_test']) {
            $user->setHiddenTest(true);
        }

        if ($parameterArray['rent']) {
            $user->setRent(true);
        }

        if ($parameterArray['password_reset']) {
            $user->setPasswordReset(true);
        }

        $parameterArray['user'] = $user;

        return $parameterArray;
    }

    /**
     * 產生使用者層級資料表
     *
     * @param User $user 使用者
     * @param integer $currency 幣別
     *
     * @return array $queueIndex 需要寫回 redis 更新計數的層級和層級幣別
     */
    private function generateUserLevel($user, $currency)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $currencyOperator = $this->get('durian.currency');

        // 取出最靠近的上層的預設層級
        $presetLevel = $em->getRepository('BBDurianBundle:PresetLevel')
            ->getAncestorPresetLevel($user->getId());

        if (!$presetLevel) {
            throw new \RuntimeException('No PresetLevel found', 150010135);
        }

        $level = $presetLevel[0]->getLevel();
        $levelId = $level->getId();

        $userLevel = new UserLevel($user, $levelId);
        $em->persist($userLevel);

        $ulLog = $operationLogger->create('user_level', ['user_id' => $user->getId()]);
        $ulLog->addMessage('level_id', $levelId);
        $operationLogger->save($ulLog);

        $em->flush();
        $emShare->flush();

        $queueIndex = [];

        // 需更新計數的層級幣別
        $currency = $currencyOperator->getMappedNum($currency);
        $queueIndex['level_currency'] = $levelId . '_' . $currency;

        // 需更新計數的層級
        $queueIndex['level'] = $levelId;

        return $queueIndex;
    }

    /**
     * 將需要調整的計數資料推入佇列
     *
     * @param string $action
     * @param array $queueIndex
     *     integer ['user'] 須調整的計數的 user 主鍵
     *     integer ['level'] 須調整的計數的 level 主鍵
     *     integer ['level_currency'] 須調整的計數的 level_currency 主鍵
     */
    private function pushUpdateQueue($action, $queueIndex)
    {
        $redis = $this->get('snc_redis.default');

        if (in_array($action, ['create', 'recover'])) {
            $value = 1;
        }

        if (in_array($action, ['remove'])) {
            $value = -1;
        }

        if (isset($queueIndex['user'])) {
            $redis->rpush('user_size_queue', json_encode([
                'index' => $queueIndex['user'],
                'value' => $value
            ]));
        }

        if (isset($queueIndex['level'])) {
            $redis->rpush('level_user_count_queue', json_encode([
                'index' => $queueIndex['level'],
                'value' => $value
            ]));
        }

        if (isset($queueIndex['level_currency'])) {
            $redis->rpush('level_currency_user_count_queue', json_encode([
                'index' => $queueIndex['level_currency'],
                'value' => $value
            ]));
        }
    }

    /**
     * 處理廳下層會員的測試帳號數量
     *
     * @param integer $domain 廳id
     * @param integer $number 欲加減的測試帳號數量
     */
    private function processDomainTotalTest($domain, $number)
    {
        if ($number == 0) {
            return ;
        }

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', $domain);

        $total = $totalTest->getTotalTest();
        $totalTest->addTotalTest($number);

        // 若要減少測試帳號數量,則不需要偵測是否有超過限制的問題
        if ($number > 0 && $totalTest->getTotalTest() > DomainConfig::MAX_TOTAL_TEST) {
            $config = $emShare->find('BBDurianBundle:DomainConfig', $domain);

            if ($config->isBlockTestUser()) {
                throw new \RuntimeException('The number of test exceeds limitation in the same domain', 150010136);
            }
        }

        $log = $operationLogger->create('domain_total_test', ['domain' => $domain]);
        $log->addMessage('total_test', $total, $totalTest->getTotalTest());
        $operationLogger->save($log);

        $now = new \DateTime();
        $totalTest->setAt($now);
    }
}
