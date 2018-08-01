<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Bank;
use BB\DurianBundle\Entity\BankHolderConfig;

class BankController extends Controller
{
    /**
     * 取得銀行資料
     *
     * @Route("/bank/{bankId}",
     *        name = "api_bank_get",
     *        requirements = {"bankId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $bankId
     * @return JsonResponse
     */
    public function getBankAction($bankId)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $em = $this->getEntityManager();

        $bank = $em->find('BB\DurianBundle\Entity\Bank', $bankId);

        if (!$bank) {
            throw new \RuntimeException('No Bank found', 120004);
        }

        $sensitiveLogger->validateAllowedOperator($bank->getUser());

        $output['result'] = 'ok';
        $output['ret'] = $bank->toArray();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 新增銀行資料
     *
     * @Route("/user/{userId}/bank",
     *        name = "api_bank_create",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function createAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $validator = $this->get('durian.validator');
        $blacklistValidator = $this->get('durian.blacklist_validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        // 去頭尾空白
        $post = $request->request;
        $account = trim($post->get('account'));
        $code = $post->get('code');
        $province = trim($post->get('province', ''));
        $city = trim($post->get('city', ''));
        $verifyBlacklist = (bool) $post->get('verify_blacklist', 1);
        $mobile = (bool) $post->get('mobile', 0);
        $accountHolder = trim($post->get('account_holder'));
        $branch = trim($post->get('branch'));
        $force = (bool) $post->get('force', 0);

        $checkParameter = [$province, $city, $branch, $accountHolder];

        $validator->validateEncode($checkParameter);

        if ($account === '') {
            throw new \InvalidArgumentException('No account specified', 120003);
        }

        if (!$code) {
            throw new \InvalidArgumentException('No code specified', 120002);
        }

        // 只接受英數組合
        if (!preg_match("/^([A-Za-z0-9-\s])*$/i", $account)) {
            throw new \RuntimeException('Illegal Bank account', 120006);
        }

        $user = $this->findUser($userId);

        // 驗證黑名單
        if ($verifyBlacklist && $user->getRole() == 1) {
            $criteria = ['account' => $account];
            $blacklistValidator->validate($criteria, $user->getDomain());
        }

        $criteria = [
            'domain' => $user->getDomain(),
            'bankCurrencyId' => $code,
        ];

        // 如果是現金會員，需要取層級的設定
        if ($user->getRole() == 1 && $user->getCash()) {
            // 取得會員的層級
            $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

            if (!$userLevel) {
                throw new \RuntimeException('No UserLevel found', 150120013);
            }

            $criteria['levelId'] = $userLevel->getLevelId();
        }

        $this->validBankCurrency($criteria);

        if ($accountHolder) {
            $bankHolderConfig = $em->find('BBDurianBundle:BankHolderConfig', $userId);

            if (!$bankHolderConfig) {
                throw new \RuntimeException('User does not support non-personal account', 150120012);
            }

            if (!$bankHolderConfig->isEditHolder() && !$force) {
                throw new \RuntimeException('User Can not edit account holder', 150120015);
            }
        }

        $sensitiveLogger->validateAllowedOperator($user);

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $log = $operationLogger->create('bank', ['user_id' => $userId]);
            $log->addMessage('code', $code);
            $log->addMessage('account', $account);
            $log->addMessage('province', $province);
            $log->addMessage('city', $city);
            $log->addMessage('mobile', var_export($mobile, true));
            $log->addMessage('branch', $branch);
            $log->addMessage('account_holder', $accountHolder);
            $operationLogger->save($log);

            $bank = new Bank($user);
            $bank->setAccount($account);
            $bank->setCode($code);
            $bank->setProvince($province);
            $bank->setCity($city);
            $bank->setMobile($mobile);
            $bank->setBranch($branch);
            $bank->setAccountHolder($accountHolder);

            $repo = $em->getRepository('BB\DurianBundle\Entity\Bank');
            $depth = count($user->getAllParents());

            $checkUnique = $repo->getByDomain(
                $user->getDomain(),
                $bank->getAccount(),
                $depth
            );

            if (!empty($checkUnique)) {
                throw new \RuntimeException('This account already been used', 120001);
            }

            $em->persist($bank);
            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 120009);
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret'] = $bank->toArray();

        $sensitiveLogger->writeSensitiveLog();
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 修改銀行資料
     *
     * @Route("/user/{userId}/bank",
     *        name = "api_bank_edit",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者id
     * @return JsonResponse
     */
    public function editBankAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $validator = $this->get('durian.validator');
        $blacklistValidator = $this->get('durian.blacklist_validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $post = $request->request;
        $bankId = $post->get('bank_id', 0);
        $oldAccount = $post->get('old_account');
        $newAccount = trim($post->get('new_account'));
        $verifyBlacklist = (bool) $post->get('verify_blacklist', 1);
        $mobile = (bool) $post->get('mobile', 0);
        $accountHolder = trim($post->get('account_holder'));
        $branch = trim($post->get('branch'));
        $force = (bool) $post->get('force', 0);

        if (trim($oldAccount) === '') {
            $oldBank = $em->getRepository('BBDurianBundle:Bank')->find($bankId);

            if (!$oldBank) {
                throw new \InvalidArgumentException('No account specified', 120003);
            }

            $oldAccount = $oldBank->getAccount();
        }

        $user = $this->findUser($userId);

        $param = [];
        $param['account'] = $oldAccount;

        $banks = $this->getBanks($user, $param);

        if (empty($banks)) {
            throw new \RuntimeException('No Bank found', 120004);
        }

        $bank = $banks[0];

        $log = $operationLogger->create('bank', ['user_id' => $userId]);

        if ($post->has('new_account')) {
            if ($newAccount === '') {
                throw new \InvalidArgumentException('No account specified', 120003);
            }

            // 只接受英數組合
            if (!preg_match("/^([A-Za-z0-9-\s])*$/i", $newAccount)) {
                throw new \RuntimeException('Illegal Bank account', 120006);
            }

            // 驗證黑名單
            if ($verifyBlacklist && $user->getRole() == 1) {
                $criteria = ['account' => $newAccount];
                $blacklistValidator->validate($criteria, $user->getDomain());
            }

            if (strval($oldAccount) !== strval($newAccount)) {
                $this->processAccount($user, $bank, $newAccount);
                $log->addMessage('account', $oldAccount, $newAccount);
            }
        }

        if ($post->has('code')) {
            $originalCode = $bank->getCode();
            $newCode = $post->get('code');
            if ($originalCode != $newCode) {
                $log->addMessage('code', $originalCode, $newCode);
            }

            $bank->setCode($post->get('code'));

            $criteria = [
                'domain' => $user->getDomain(),
                'bankCurrencyId' => $post->get('code'),
            ];

            // 如果是現金會員，需要取層級的設定
            if ($user->getRole() == 1 && $user->getCash()) {
                // 取得會員的層級
                $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

                if (!$userLevel) {
                    throw new \RuntimeException('No UserLevel found', 150120013);
                }

                $criteria['levelId'] = $userLevel->getLevelId();
            }

            $this->validBankCurrency($criteria);
        }

        if ($post->has('status')) {
            $originalStatus = $bank->getStatus();
            $newStatus = $post->get('status');
            if ($newStatus != Bank::IN_USE && $newStatus != Bank::USED) {
                throw new \InvalidArgumentException('Invalid bank status', 120008);
            }

            if ($originalStatus != $newStatus) {
                $log->addMessage('status', $originalStatus, $newStatus);
            }

            $bank->setStatus($post->get('status'));
        }

        if ($post->has('province')) {
            $newProvince = trim($post->get('province'));
            $validator->validateEncode($newProvince);
            $originalProvince = $bank->getProvince();
            if ($originalProvince != $newProvince) {
                $log->addMessage('province', $originalProvince, $newProvince);
            }

            $bank->setProvince($newProvince);
        }

        if ($post->has('city')) {
            $newCity = trim($post->get('city'));
            $validator->validateEncode($newCity);
            $originalCity = $bank->getCity();
            if ($originalCity != $newCity) {
                $log->addMessage('city', $originalCity, $newCity);
            }

            $bank->setCity($newCity);
        }

        if (!is_null($post->get('mobile'))) {
            $originalMobile = $bank->isMobile();
            if ($originalMobile != $mobile) {
                $log->addMessage('mobile', var_export($originalMobile, true), var_export($mobile, true));
            }

            $bank->setMobile($mobile);
        }

        if ($post->has('branch')) {
            $validator->validateEncode($branch);

            $originalBranch = $bank->getBranch();
            if ($originalBranch != $branch) {
                $log->addMessage('branch', $originalBranch, $branch);
            }

            $bank->setBranch($branch);
        }

        // accountHolder 不可為空字串，i.e.空字串時不處理
        if ($accountHolder) {
            $bankHolderConfig = $em->find('BBDurianBundle:BankHolderConfig', $userId);

            if (!$bankHolderConfig) {
                throw new \RuntimeException('User does not support non-personal account', 150120012);
            }

            if (!$bankHolderConfig->isEditHolder() && !$force) {
                throw new \RuntimeException('User Can not edit account holder', 150120015);
            }

            $originalAccountHolder = $bank->getAccountHolder();
            if ($originalAccountHolder != $accountHolder) {
                $log->addMessage('account_holder', $originalAccountHolder, $accountHolder);
            }

            $bank->setAccountHolder($accountHolder);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        $sensitiveLogger->validateAllowedOperator($user);

        try {
            $em->flush();
            $emShare->flush();
        } catch (\Exception $e) {

            // 重複的紀錄
            if ($e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 120009);
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret'] = $bank->toArray();

        $sensitiveLogger->writeSensitiveLog();
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得使用者銀行資料
     *
     * @Route("/user/{userId}/bank",
     *        name = "api_usr_get_bank",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getBankByUserAction(Request $request, $userId)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $em = $this->getEntityManager();

        $query = $request->query;
        $mobile = $query->get('mobile');
        $accountHolder = trim($query->get('account_holder'));

        $userbank = array();

        $user = $this->findUser($userId);
        $sensitiveLogger->validateAllowedOperator($user);

        // 取得最後出款銀行資訊
        if ($query->get('last') == 1) {
            $bank = null;

            if ($user->getLastBank()) {
                $bank = $em->find('BB\DurianBundle\Entity\Bank', $user->getLastBank());
            }

            if ($bank) {
                $userbank[] = $bank->toArray();
            }
        } else {
            $param = [];

            // 可指定account取得銀行資訊
            $param['account'] = $query->get('account');

            // 是否為電子錢包帳戶
            if (!is_null($mobile) && trim($mobile) != '') {
                $param['mobile'] = $mobile;
            }

            // 可指定account_holder取得銀行資訊
            $param['account_holder'] = $query->get('account_holder');

            $banks = $this->getBanks($user, $param);

            foreach ($banks as $bank) {
                $userbank[] = $bank->toArray();
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $userbank;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 檢查指定的站別層數內銀行帳號是否重複
     *
     * @Route("/bank/check_unique",
     *        name = "api_bank_check_unique",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkUniqueAction(Request $request)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $em = $this->getEntityManager();

        // 取得傳入的參數
        $query = $request->query;
        $domain   = $query->get('domain');//向下相容
        $parentId = $query->get('parent_id');
        $depth    = $query->get('depth');
        $account  = $query->get('account');
        $userId   = $query->get('user_id');

        // initialize
        $unique = true;

        if (null == $domain && null == $parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 120010);
        }

        if ($domain) {
            $parentId = $domain;
        }

        if (trim($account) === '') {
            throw new \InvalidArgumentException('No account specified', 120003);
        }

        $sensitiveLogger->validateAllowedOperator($parentId);

        $banks = $em->getRepository('BB\DurianBundle\Entity\Bank')
                    ->getByDomain($parentId, $account, $depth, $userId);

        if (count($banks) > 0) {
            $unique = false;
        }

        $output['result'] = 'ok';
        $output['ret']['unique'] = $unique;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 刪除銀行帳號
     *
     * @Route("/bank/{bankId}",
     *        name = "api_bank_remove",
     *        requirements = {"bankId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $bankId 使用者ID
     * @return JsonResponse
     */
    public function removeAction($bankId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $bank = $em->find('BB\DurianBundle\Entity\Bank', $bankId);

        if (!$bank) {
            throw new \RuntimeException('No Bank found', 120004);
        }

        $user = $bank->getUser();
        $sensitiveLogger->validateAllowedOperator($user);

        $log = $operationLogger->create('bank', ['user_id' => $user->getId()]);
        $operationLogger->save($log);

        $lastBank = $user->getLastBank();

        //如使用者綁定帳號為要刪除的銀行，則設為空
        if ($lastBank == $bankId) {
            $log = $operationLogger->create('user', ['id' => $user->getId()]);
            $log->addMessage('last_bank', $lastBank, 'null');
            $operationLogger->save($log);

            $user->setLastBank(null);
        }

        $em->remove($bank);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        $sensitiveLogger->writeSensitiveLog();
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得開放非本人出款銀行設定的會員
     *
     * @Route("/bank/holder_config_by_users",
     *        name = "api_get_bank_holder_config_by_users",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getHolderConfigByUsersAction()
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:BankHolderConfig');
        $validator = $this->get('durian.validator');

        // 取得傳入的參數
        $query = $this->getRequest()->query;
        $userIds = $query->get('users', []);
        $domain = $query->get('domain');
        $editHolder = (bool) $query->get('edit_holder', 1);
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [];

        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        if ($userIds) {
            $criteria['userIds'] = $userIds;
        }

        if ($domain) {
            $criteria['domain'] = $domain;
        }

        if ($query->has('edit_holder')) {
            $criteria['edit_holder'] = $editHolder;
        }

        $bankHolderConfig = $repo->getUserIdBy($criteria, $firstResult, $maxResults);

        $total = $repo->countUserIdBy($criteria);

        $output= [
            'result' => 'ok',
            'ret' => $bankHolderConfig,
            'pagination' => [
                'first_result' => $firstResult,
                'max_results' => $maxResults,
                'total' => $total,
            ],
        ];

        return new JsonResponse($output);
    }

    /**
     * 開放非本人出款銀行設定的會員
     *
     * @Route("/bank/holder_config_by_users/enable",
     *        name = "api_enable_bank_holder_config_by_users",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @return JsonResponse
     */
    public function enableHolderConfigByUsersAction()
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:BankHolderConfig');

        // 取得傳入的參數
        $request = $this->getRequest()->request;
        $userIds = $request->get('users', []);

        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        $enableUser = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach (array_unique($userIds) as $userId) {
                $user = $this->findUser($userId);

                $bankHolderConfig = $repo->findOneBy(['userId' => $userId]);

                if (!$bankHolderConfig) {
                    $enableUser[] = $userId;

                    $bankHolderConfig = new BankHolderConfig($userId, $user->getDomain());
                    $em->persist($bankHolderConfig);
                }
            }

            if ($enableUser) {
                $log = $operationLogger->create('bank_holder_config', ['user_id' => $enableUser[0]]);
                $log->addMessage('user_id', implode(', ', $enableUser));
                $operationLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 120009);
            }

            throw $e;
        }

        $output= [
            'result' => 'ok',
            'ret' => $enableUser,
        ];

        return new JsonResponse($output);
    }

    /**
     * 取消開放非本人出款銀行設定的會員
     *
     * @Route("/bank/holder_config_by_users/disable",
     *        name = "api_disable_bank_holder_config_by_users",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @return JsonResponse
     */
    public function disableHolderConfigByUsersAction()
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $bankRepo = $em->getRepository('BBDurianBundle:Bank');
        $bhcRepo = $em->getRepository('BBDurianBundle:BankHolderConfig');

        // 取得傳入的參數
        $request = $this->getRequest()->request;
        $userIds = $request->get('users', []);

        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        $disableUser = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $userId = 0;

            foreach (array_unique($userIds) as $userId) {
                $bankHolderConfig = $bhcRepo->findOneBy(['userId' => $userId]);

                if ($bankHolderConfig) {
                    $disableUser[] = $userId;

                    $em->remove($bankHolderConfig);
                }

                $banks = $bankRepo->getNonHolderBankByUserId($userId);

                foreach ($banks as $bank) {
                    $user = $bank->getUser();
                    $lastBank = $user->getLastBank();

                    // 如使用者綁定帳號為要刪除的銀行，則設為空
                    if ($lastBank == $bank->getId()) {
                        $user->setLastBank(null);

                        // 如果還有其他銀行資料，取第一個非本人的銀行資料當LastBank
                        $criteria = [
                            'user' => $userId,
                            'accountHolder' => '',
                        ];
                        $newLastBank = $bankRepo->findOneBy($criteria);

                        if ($newLastBank) {
                            $user->setLastBank($newLastBank->getId());
                        }
                    }

                    $em->remove($bank);
                }
            }

            if ($disableUser) {
                $log = $operationLogger->create('bank_holder_config', ['user_id' => $disableUser[0]]);
                $log->addMessage('user_id', implode(', ', $disableUser));
                $operationLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 120009);
            }

            throw $e;
        }

        $output= [
            'result' => 'ok',
            'ret' => $disableUser,
        ];

        return new JsonResponse($output);
    }

    /**
     * 開放修改非本人出款銀行戶名的權限
     *
     * @Route("/bank/edit_holder_by_users/enable",
     *        name = "api_enable_edit_holder_by_users",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @return JsonResponse
     */
    public function enableEditHolderByUsersAction()
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:BankHolderConfig');

        // 取得傳入的參數
        $request = $this->getRequest()->request;
        $userIds = $request->get('users', []);

        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        $enableUser = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach (array_unique($userIds) as $userId) {
                $bankHolderConfig = $repo->findOneBy(['userId' => $userId]);

                if ($bankHolderConfig) {
                    $enableUser[] = $userId;

                    $bankHolderConfig->editHolder();
                }
            }

            if ($enableUser) {
                $log = $operationLogger->create('bank_holder_config', ['user_id' => $enableUser[0]]);
                $log->addMessage('user_id', implode(', ', $enableUser));
                $operationLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 120009);
            }

            throw $e;
        }

        $output= [
            'result' => 'ok',
            'ret' => $enableUser,
        ];

        return new JsonResponse($output);
    }

    /**
     * 關閉修改非本人出款銀行戶名的權限
     *
     * @Route("/bank/edit_holder_by_users/disable",
     *        name = "api_disable_edit_holder_config_by_users",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @return JsonResponse
     */
    public function disableEditHolderByUsersAction()
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $bhcRepo = $em->getRepository('BBDurianBundle:BankHolderConfig');

        // 取得傳入的參數
        $request = $this->getRequest()->request;
        $userIds = $request->get('users', []);

        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        $disableUser = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $userId = 0;

            foreach (array_unique($userIds) as $userId) {
                $bankHolderConfig = $bhcRepo->findOneBy(['userId' => $userId]);

                if ($bankHolderConfig) {
                    $disableUser[] = $userId;

                    $bankHolderConfig->unEditHolder();
                }
            }

            if ($disableUser) {
                $log = $operationLogger->create('bank_holder_config', ['user_id' => $disableUser[0]]);
                $log->addMessage('user_id', implode(', ', $disableUser));
                $operationLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 120009);
            }

            throw $e;
        }

        $output= [
            'result' => 'ok',
            'ret' => $disableUser,
        ];

        return new JsonResponse($output);
    }

    /**
     * 停用該廳綁定非本人銀行卡功能
     *
     * @Route("domain/{domain}/bank_holder_config/disable",
     *        name = "api_domain_bank_holder_config_disable",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function disableBankHolderConfigAction($domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $bankRepo = $em->getRepository('BBDurianBundle:Bank');
        $bhcRepo = $em->getRepository('BBDurianBundle:BankHolderConfig');

        $configs = $bhcRepo->findBy(['domain' => $domain]);

        $userIds = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach ($configs as $config) {
                $userIds[] = $config->getUserId();
                $em->remove($config);
            }

            if ($userIds) {
                $removeData = $bankRepo->removeByUser($userIds);

                $log = $operationLogger->create('bank_holder_config', ['domain' => $domain]);
                $operationLogger->save($log);

                $emShare->flush();
                $emShare->commit();
            }

            foreach ($removeData as $data) {
                $user = $em->find('BBDurianBundle:User', $data['user_id']);

                $lastBank = $user->getLastBank();

                // 如使用者綁定帳號為要刪除的銀行，則設為空
                if ($lastBank == $data['bank_id']) {
                    $user->setLastBank(null);

                    // 如果還有其他銀行資料，取第一個非本人的銀行資料當LastBank
                    $criteria = [
                        'user' => $data['user_id'],
                        'accountHolder' => '',
                    ];
                    $newLastBank = $bankRepo->findOneBy($criteria);

                    if ($newLastBank) {
                        $user->setLastBank($newLastBank->getId());
                    }
                }
            }

            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse(['result' => 'ok']);
    }

    /**
     * 修改銀行帳號的相關檢查
     *
     * @param User $user
     * @param Bank $bank
     * @param string $accountReq
     * @return Bank
     */
    private function processAccount(User $user, Bank $bank, $accountReq)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BB\DurianBundle\Entity\Bank');

        $domain = $user->getDomain();
        $depth = count($user->getAllParents());

        $checkBanks = $repo->getByDomain($domain, $accountReq, $depth);

        if (0 != count($checkBanks)) {
            throw new \RuntimeException('This account already been used', 120001);
        }

        $bank->setAccount($accountReq);

        return $bank;
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
     * @return User
     */
    private function findUser($userId)
    {
        $em = $this->getEntityManager();

        $user = $em->find('BB\DurianBundle\Entity\User', $userId);

        if (null === $user) {
            throw new \RuntimeException('No such user', 120011);
        }

        return $user;
    }

    /**
     * 取得單一使用者的銀行資訊
     *
     * @param User   $user    使用者
     * @param array $param 參數
     * @return array
     */
    private function getBanks(User $user, $param = [])
    {
        $criteria['user'] = $user->getId();

        if ($param['account'] && $param['account'] != '') {
            $criteria['account'] = $param['account'];
        }

        if (isset($param['mobile']) && $param['mobile'] != '') {
            $criteria['mobile'] = $param['mobile'];
        }

        if (isset($param['account_holder']) && $param['account_holder'] != '') {
            $criteria['accountHolder'] = $param['account_holder'];
        }

        $em = $this->getEntityManager();
        $banks = $em->getRepository('BBDurianBundle:Bank')
                    ->findBy($criteria);

        return $banks;
    }

    /**
     * 檢查銀行幣別
     *
     * @param array $criteria
     */
    private function validBankCurrency($criteria)
    {
        $em = $this->getEntityManager();
        $lwbcRepo = $em->getRepository('BBDurianBundle:LevelWithdrawBankCurrency');
        $dwbcRepo = $em->getRepository('BBDurianBundle:DomainWithdrawBankCurrency');
        $redis = $this->get('snc_redis.default_client');

        $levelWithdrawBankCurrencies = [];

        // 如果有層級(現金會員)
        if (isset($criteria['levelId'])) {
            $levelWithdrawBankCurrencies = $lwbcRepo->getByLevel($criteria);
        }

        // 如果沒有層級(非現金會員)或該層級從來沒設定過出款銀行則取廳的設定值
        if (!isset($criteria['levelId']) || !$redis->sismember('level_withdraw_bank_currency', $criteria['levelId'])) {
            $levelWithdrawBankCurrencies = $dwbcRepo->getByDomain($criteria);
        }

        if (!$levelWithdrawBankCurrencies) {
            throw new \RuntimeException('BankCurrency not support by level', 150120014);
        }
    }
}
