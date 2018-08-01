<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use BB\DurianBundle\Entity\AutoRemit;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\DomainAutoRemit;
use BB\DurianBundle\Entity\RemitAccount;

class AutoRemitController extends Controller
{
    /**
     * 取得自動認款平台
     *
     * @Route("/auto_remit/{autoRemitId}",
     *     name = "api_get_auto_remit",
     *     requirements = {"autoRemitId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $autoRemitId
     * @return JsonResponse
     */
    public function getAction($autoRemitId)
    {
        $autoRemit = $this->getAutoRemit($autoRemitId);

        $output = [
            'result' => 'ok',
            'ret' => $autoRemit->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得自動認款平台列表
     *
     * @Route("/auto_remit/list",
     *     name = "api_get_auto_remit_list",
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getListAction(Request $request)
    {
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');

        $query = $request->query;
        $removed = (bool) $query->get('removed');
        $sort = $query->get('sort');
        $order = $query->get('order');

        $orderBy = ['id' => 'ASC'];

        if ($sort) {
            $orderBy = $parameterHandler->orderBy($sort, $order);
        }

        $criteria = [];

        if ($query->has('removed')) {
            $criteria['removed'] = $removed;
        }

        $autoRemits = $em->getRepository('BBDurianBundle:AutoRemit')
            ->findBy($criteria, $orderBy);

        $ret = [];
        foreach ($autoRemits as $autoRemit) {
            $ret[] = $autoRemit->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret,
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定自動認款平台
     *
     * @Route("/auto_remit/{autoRemitId}",
     *     name = "api_edit_auto_remit",
     *     requirements = {"autoRemitId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $autoRemitId
     * @return JsonResponse
     */
    public function setAction(Request $request, $autoRemitId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');

        $post = $request->request;
        $label = trim($post->get('label'));
        $name = trim($post->get('name'));

        if ($post->has('label') && $label === '') {
            throw new \InvalidArgumentException('Invalid label', 150870002);
        }

        if ($post->has('name') && $name === '') {
            throw new \InvalidArgumentException('Invalid name', 150870003);
        }

        $checkParameter = [$label, $name];
        $validator->validateEncode($checkParameter);

        $autoRemit = $this->getAutoRemit($autoRemitId);

        $log = $operationLogger->create('auto_remit', ['id' => $autoRemitId]);

        if ($post->has('label') && $autoRemit->getLabel() !== $label) {
            $log->addMessage('label', $autoRemit->getLabel(), $label);
            $autoRemit->setLabel($label);
        }

        if ($post->has('name') && $autoRemit->getName() !== $name) {
            $log->addMessage('name', $autoRemit->getName(), $name);
            $autoRemit->setName($name);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output = [
            'result' => 'ok',
            'ret' => $autoRemit->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 刪除自動認款平台
     *
     * @Route("/auto_remit/{autoRemitId}",
     *     name = "api_remove_auto_remit",
     *     requirements = {"autoRemitId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $autoRemitId
     * @return JsonResponse
     */
    public function removeAction($autoRemitId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $darRepo = $em->getRepository('BBDurianBundle:DomainAutoRemit');

        $autoRemit = $this->getAutoRemit($autoRemitId);

        if ($this->getBankInfoByAutoRemit($autoRemit)) {
            throw new \RuntimeException('Can not remove AutoRemit when AutoRemit has BankInfo', 150870006);
        }

        $criteria = ['autoRemitId' => $autoRemit->getId()];
        $domainAutoRemit = $darRepo->findBy($criteria);

        if ($domainAutoRemit) {
            throw new \RuntimeException('Can not remove AutoRemit when DomainAutoRemit has AutoRemit', 150870008);
        }

        if (!$autoRemit->isRemoved()) {
            $autoRemit->remove();
            $em->flush();

            $log = $operationLogger->create('auto_remit', ['id' => $autoRemitId]);
            $log->addMessage('removed', 'false', 'true');
            $operationLogger->save($log);
            $emShare->flush();
        }

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 取得自動認款平台支援的銀行
     *
     * @Route("/auto_remit/{autoRemitId}/bank_info",
     *     name = "api_auto_remit_get_bank_info",
     *     requirements = {"autoRemitId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $autoRemitId
     * @return JsonResponse
     */
    public function getBankInfoAction($autoRemitId)
    {
        $autoRemit = $this->getAutoRemit($autoRemitId);

        $output = [
            'result' => 'ok',
            'ret' => $this->getBankInfoByAutoRemit($autoRemit),
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定自動認款平台支援的銀行
     *
     * @Route("/auto_remit/{autoRemitId}/bank_info",
     *     name = "api_auto_remit_set_bank_info",
     *     requirements = {"autoRemitId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $autoRemitId
     * @return JsonResponse
     */
    public function setBankInfoAction(Request $request, $autoRemitId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $post = $request->request;
        $bankInfoNew = $post->get('bank_info', []);
        $bankInfoNew = array_unique($bankInfoNew);

        $autoRemit = $this->getAutoRemit($autoRemitId);
        $this->checkAutoRemitRemoved($autoRemit);

        // 目前設定的支援銀行
        $bankInfoOld = [];
        foreach ($autoRemit->getBankInfo() as $bankInfo) {
            $bankInfoOld[] = $bankInfo->getId();
        }

        // 設定傳入有的但原本沒有，需添加
        $bankInfoAdd = array_diff($bankInfoNew, $bankInfoOld);
        foreach ($bankInfoAdd as $bankInfoId) {
            $bankInfo = $this->getBankInfo($bankInfoId);

            $autoRemit->addBankInfo($bankInfo);
        }

        // 原本有的但設定沒有傳入，需移除
        $bankInfoSub = array_diff($bankInfoOld, $bankInfoNew);
        foreach ($bankInfoSub as $bankInfoId) {
            $bankInfo = $this->getBankInfo($bankInfoId);
            $isUsed = $this->checkBankInfo($bankInfo, $autoRemit);

            // 被設定使用中不能移除
            if ($isUsed) {
                throw new \RuntimeException('BankInfo is in used', 150870005);
            }

            $autoRemit->removeBankInfo($bankInfo);
        }

        if ($bankInfoAdd || $bankInfoSub) {
            sort($bankInfoOld);
            sort($bankInfoNew);
            $oldIds = implode(', ', $bankInfoOld);
            $newIds = implode(', ', $bankInfoNew);

            $log = $operationLogger->create('auto_remit_has_bank_info', ['auto_remit_id' => $autoRemitId]);
            $log->addMessage('bank_info_id', $oldIds, $newIds);
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output = [
            'result' => 'ok',
            'ret' => $this->getBankInfoByAutoRemit($autoRemit),
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得自動認款平台廳的設定
     *
     * @Route("/domain/{domain}/auto_remit/{autoRemitId}",
     *     name = "api_get_domain_auto_remit",
     *     requirements = {"domain" = "\d+", "autoRemitId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain
     * @param integer $autoRemitId
     * @return JsonResponse
     */
    public function getDomainAutoRemitAction($domain, $autoRemitId)
    {
        $em = $this->getEntityManager();
        $autoRemitChecker = $this->get('durian.auto_remit_checker');

        $domainUser = $this->findUser($domain);
        if ($domainUser->getRole() != 7) {
            throw new \RuntimeException('Not a domain', 150870011);
        }

        $autoRemit = $this->getAutoRemit($autoRemitId);

        $domainAutoRemit = $autoRemitChecker->getPermission($domain, $autoRemit, $domainUser);

        $output = [
            'result' => 'ok',
            'ret' => $domainAutoRemit->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得廳的自動認款平台設定
     *
     * @Route("/domain/{domain}/auto_remit",
     *     name = "api_get_domain_all_auto_remit",
     *     requirements = {"domain" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function getDomainAllAutoRemitAction($domain)
    {
        $em = $this->getEntityManager();
        $autoRemitChecker = $this->get('durian.auto_remit_checker');

        $domainUser = $this->findUser($domain);
        if ($domainUser->getRole() != 7) {
            throw new \RuntimeException('Not a domain', 150870011);
        }

        // 先以同略雲的開關當作廳的大開關
        $autoRemit = $this->getAutoRemit(1);

        $domainAutoRemit = $autoRemitChecker->getPermission($domain, $autoRemit, $domainUser);

        $output = [
            'result' => 'ok',
            'ret' => [
                'domain' => $domainAutoRemit->getDomain(),
                'enable' => $domainAutoRemit->getEnable(),
            ],
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得自動認款平台廳的設定列表
     *
     * @Route("/domain/{domain}/auto_remit/list",
     *     name = "api_domain_auto_remit_list",
     *     requirements = {"domain" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain 廳
     * @return JsonResponse
     */
    public function listDomainAutoRemitAction($domain)
    {
        $em = $this->getEntityManager();
        $autoRemitChecker = $this->get('durian.auto_remit_checker');

        $domainUser = $this->findUser($domain);
        $autoRemitCount = count($em->getRepository('BBDurianBundle:AutoRemit')->findAll());

        $rets = [];
        for ($autoRemitId = 1; $autoRemitId <= $autoRemitCount; $autoRemitId++) {
            // domain 6, 32, 98 可以看到BB自動認款
            if (!in_array($domainUser->getDomain(), [6, 32, 98]) && in_array($autoRemitId, [2, 4])) {
                continue;
            }

            $autoRemit = $this->getAutoRemit($autoRemitId);

            $domainAutoRemit = $autoRemitChecker->getPermission($domain, $autoRemit, $domainUser);

            $ret = $domainAutoRemit->toArray();
            $rets[] = array_merge($ret, ['name' => $autoRemit->getName()]);
        }

        $output = [
            'result' => 'ok',
            'ret' => $rets,
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改自動認款平台廳的設定
     *
     * @Route("/domain/{domain}/auto_remit/{autoRemitId}",
     *     name = "api_set_domain_auto_remit",
     *     requirements = {"domain" = "\d+", "auto_remit_id" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain
     * @param integer $autoRemitId
     * @return JsonResponse
     */
    public function setDomainAutoRemitAction(Request $request, $domain, $autoRemitId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $autoRemitMaker = $this->get('durian.auto_remit_maker');

        $post = $request->request;
        $enable = $post->get('enable');
        $apiKey = trim($post->get('api_key'));

        // 非廳主不可以新增設定
        $domainUser = $this->findUser($domain);
        if ($domainUser->getRole() != 7) {
            throw new \RuntimeException('Not a domain', 150870011);
        }

        $log = $operationLogger->create('domain_auto_remit', ['domain' => $domain, 'auto_remit_id' => $autoRemitId]);

        $autoRemit = $this->getAutoRemit($autoRemitId);

        // 預設開啟自動認款平台列表，過渡期使用，待整體功能完整後移除
        $allAutoRemit = $autoRemitId == 1 ? true : false;

        $domainAutoRemit = $this->setPermission($domain, $autoRemit, $domainUser, $log, $enable, $allAutoRemit);

        if ($post->has('api_key')) {
            $log->addMessage('api_key', 'update');
            $autoRemit = $this->getAutoRemit($autoRemitId);
            $autoRemitMaker->checkApiKey($autoRemit->getLabel(), $apiKey);
            $domainAutoRemit->setApiKey($apiKey);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output = [
            'result' => 'ok',
            'ret' => $domainAutoRemit->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改廳的自動認款平台設定
     *
     * @Route("/domain/{domain}/auto_remit",
     *     name = "api_set_domain_all_auto_remit",
     *     requirements = {"domain" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain
     * @return JsonResponse
     */
    public function setDomainAllAutoRemitAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $post = $request->request;
        $enable = (bool) $post->get('enable');

        // 非廳主不可以新增設定
        $domainUser = $this->findUser($domain);
        if ($domainUser->getRole() != 7) {
            throw new \RuntimeException('Not a domain', 150870011);
        }

        // 預設開啟自動認款平台列表，過渡期使用，待整體功能完整後移除
        $autoRemitIds = [1, 3];

        foreach ($autoRemitIds as $autoRemitId) {
            $criteria = [
                'domain' => $domain,
                'autoRemitId' => $autoRemitId,
            ];
            $domainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy($criteria);
            $log = $operationLogger->create(
                'domain_auto_remit',
                ['domain' => $domain, 'auto_remit_id' => $autoRemitId]
            );

            $autoRemit = $this->getAutoRemit($autoRemitId);

            $domainAutoRemit = $this->setPermission($domain, $autoRemit, $domainUser, $log, $enable, true);

            if ($log->getMessage()) {
                $operationLogger->save($log);
                $em->flush();
                $emShare->flush();
            }
        }

        $output = [
            'result' => 'ok',
        ];

        return new JsonResponse($output);
    }

    /**
     * 刪除自動認款平台廳的設定
     *
     * @Route("/domain/{domain}/auto_remit/{autoRemitId}",
     *     name = "api_remove_domain_auto_remit",
     *     requirements = {"domain" = "\d+", "autoRemitId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $domain
     * @param integer $autoRemitId
     * @return JsonResponse
     */
    public function removeDomainAutoRemitAction($domain, $autoRemitId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $autoRemit = $this->getAutoRemit($autoRemitId);

        if ($this->checkRemitAccountByDomain($domain, $autoRemit)) {
            throw new \RuntimeException('Can not remove DomainAutoRemit when AutoRemit is in used', 150870010);
        }

        $domainAutoRemit = $this->getDomainAutoRemit($domain, $autoRemitId);

        $log = $operationLogger->create('domain_auto_remit', ['domain' => $domain, 'auto_remit_id' => $autoRemitId]);
        $operationLogger->save($log);

        $em->remove($domainAutoRemit);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

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
     * 取得自動認款平台
     *
     * @param integer $autoRemitId 自動認款平台ID
     * @return AutoRemit
     */
    private function getAutoRemit($autoRemitId)
    {
        $em = $this->getEntityManager();
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', $autoRemitId);

        if (!$autoRemit) {
            throw new \RuntimeException('No AutoRemit found', 150870001);
        }

        return $autoRemit;
    }

    /**
     * 取得銀行
     *
     * @param integer $bankInfoId 銀行ID
     * @return BankInfo
     */
    private function getBankInfo($bankInfoId)
    {
        $em = $this->getEntityManager();
        $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

        if (!$bankInfo) {
            throw new \RuntimeException('No BankInfo found', 150870004);
        }

        return $bankInfo;
    }

    /**
     * 回傳自動認款平台廳的設定
     *
     * @param integer $domain
     * @param integer $autoRemitId
     * @return DomainAutoRemit
     */
    private function getDomainAutoRemit($domain, $autoRemitId)
    {
        $em = $this->getEntityManager();

        $criteria = [
            'domain' => $domain,
            'autoRemitId' => $autoRemitId
        ];
        $domainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy($criteria);

        if (!$domainAutoRemit) {
            throw new \RuntimeException('No DomainAutoRemit found', 150870012);
        }

        return $domainAutoRemit;
    }

    /**
     * 回傳使用者
     *
     * @param integer $userId
     * @return User
     */
    private function findUser($userId)
    {
        $em = $this->getEntityManager();
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150870026);
        }

        return $user;
    }

    /**
     * 回傳自動認款平台支援的銀行
     *
     * @param AutoRemit $autoRemit
     * @return array
     */
    private function getBankInfoByAutoRemit(AutoRemit $autoRemit)
    {
        $bankInfos = [];

        foreach ($autoRemit->getBankInfo() as $bankInfo) {
            $bankInfos[] = $bankInfo->toArray();
        }

        return $bankInfos;
    }

    /**
     * 檢查傳入的銀行在自動認款平台下是否被使用
     *
     * @param BankInfo $bankInfo
     * @param AutoRemit $autoRemit
     * @return boolean
     */
    private function checkBankInfo(BankInfo $bankInfo, AutoRemit $autoRemit)
    {
        $em = $this->getEntityManager();
        $raRepo = $em->getRepository('BBDurianBundle:RemitAccount');

        // 自動認款帳號使用中
        $criteria = [
            'enable' => true,
            'autoConfirm' => true,
            'bankInfoId' => $bankInfo->getId(),
            'autoRemitId' => $autoRemit->getId(),
        ];
        $raInUse = $raRepo->countRemitAccounts($criteria);

        if ($raInUse) {
            return true;
        }

        return false;
    }

    /**
     * 檢查傳入的自動認款平台是否已刪除
     *
     * @param AutoRemit $autoRemit
     */
    private function checkAutoRemitRemoved(AutoRemit $autoRemit)
    {
        if ($autoRemit->isRemoved()) {
            throw new \RuntimeException('AutoRemit is removed', 150870007);
        }
    }

    /**
     * 檢查傳入的廳底下自動認款平台是否被使用
     *
     * @param integer $domain
     * @param AutoRemit $autoRemit
     * @return boolean
     */
    private function checkRemitAccountByDomain($domain, AutoRemit $autoRemit)
    {
        $em = $this->getEntityManager();
        $raRepo = $em->getRepository('BBDurianBundle:RemitAccount');

        $criteria = [
            'enable' => true,
            'autoConfirm' => true,
            'domain' => $domain,
            'autoRemitId' => $autoRemit->getId(),
        ];
        $raInUse = $raRepo->countRemitAccounts($criteria);

        if ($raInUse) {
            return true;
        }

        return false;
    }

    /**
     * 將該廳底下所有自動認款帳號變更為公司入款
     *
     * @param integer $domain
     * @param integer $autoRemitId
     * @param boolean $allAutoRemit
     */
    private function turnOffAutoConfirm($domain, $autoRemitId, $allAutoRemit)
    {
        $em = $this->getEntityManager();

        $criteria = [
            'domain' => $domain,
            'accountType' => 1,
            'autoConfirm' => true,
            'autoRemitId' => $autoRemitId,
        ];

        // 是否為大開關
        if ($allAutoRemit) {
            unset($criteria['autoRemitId']);
        }

        $remitAccounts = $em->getRepository('BBDurianBundle:RemitAccount')->findBy($criteria);

        foreach ($remitAccounts as $remitAccount) {
            $remitAccount->setAutoConfirm(false);
        }
    }

    /**
     * 停用廳主子帳號設定
     *
     * @param integer $domain
     * @param integer $autoRemitId
     * @param boolean $allAutoRemit
     */
    private function disableSubDomainAutoRemit($domain, $autoRemitId, $allAutoRemit)
    {
        $em = $this->getEntityManager();

        $criteria = [
            'parent' => $domain,
            'sub' => true,
        ];
        $subUsers = $em->getRepository('BBDurianBundle:User')->findBy($criteria);

        foreach ($subUsers as $subUser) {
            $criteria = [
                'domain' => $subUser,
                'autoRemitId' => $autoRemitId,
            ];

            // 是否為大開關
            if ($allAutoRemit) {
                unset($criteria['autoRemitId']);
            }

            $subDomainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy($criteria);

            if ($subDomainAutoRemit) {
                $subDomainAutoRemit->setEnable(false);
            }
        }
    }

    /**
     * 設定權限
     *
     * @param integer $domain
     * @param AutoRemit $autoRemit
     * @param User $domainUser
     * @param OperationLogger $log
     * @param boolean $enable
     * @param boolean $allAutoRemit
     * @return DomainAutoRemit
     */
    private function setPermission($domain, $autoRemit, $domainUser, $log, $enable, $allAutoRemit = false)
    {
        $em = $this->getEntityManager();

        $criteria = [
            'domain' => $domain,
            'autoRemitId' => $autoRemit->getId(),
        ];
        $domainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy($criteria);

        // 沒資料則新增
        if (!$domainAutoRemit) {
            $domainAutoRemit = new DomainAutoRemit($domain, $autoRemit);
            $log->addMessage('api_key', 'new');
            $em->persist($domainAutoRemit);
        }

        // 若只有改key，新增完資料後返回
        if ($enable === null) {
            return $domainAutoRemit;
        }
        $enable = (bool) $enable;

        if ($domainAutoRemit->getEnable() != $enable) {
            $log->addMessage('enable', var_export($domainAutoRemit->getEnable(), true), var_export($enable, true));
            $domainAutoRemit->setEnable($enable);
        }

        // 廳主停用
        if (!$domainUser->isSub() && !$enable) {
            $em->flush();

            $disableCriteria = [
                'domain' => $domain,
                'autoRemitId' => $autoRemit->getId(),
            ];

            // 是否為大開關
            if ($allAutoRemit) {
                unset($disableCriteria['autoRemitId']);
            }

            $disableDomainAutoRemits = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findBy($disableCriteria);
            foreach ($disableDomainAutoRemits as $disableDomainAutoRemit) {
                if ($disableDomainAutoRemit->getEnable() != $enable) {
                    $disableDomainAutoRemit->setEnable($enable);
                }
                // 取出該廳底下所有自動認款帳號變更為公司入款
                $this->turnOffAutoConfirm($domain, $autoRemit->getId(), $allAutoRemit);

                // 如果是停用廳主設定需一併停用廳主子帳號設定
                $this->disableSubDomainAutoRemit($domain, $autoRemit->getId(), $allAutoRemit);
            }
        }

        return $domainAutoRemit;
    }
}
