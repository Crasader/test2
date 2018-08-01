<?php
namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use BB\DurianBundle\Entity\DomainCurrency;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DomainTotalTest;
use BB\DurianBundle\Entity\OutsidePayway;

class DomainController extends Controller
{
    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @param \Buzz\Client\Curl
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * 廳主列表API
     * @author Thor
     * @Route("/domain",
     *        name = "api_domain",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDomainListAction(Request $request)
    {
        $query = $request->query;
        $bigBallDomain = array(20000007, 20000008, 20000009, 20000010);

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        //filter為0不過濾，1為只抓整合，2為只抓大球
        $filter = $query->get('filter', 0);

        //$enableFilter為-1不過濾，1為抓啟用, 0為取停用
        $enableFilter = $query->get('enable', -1);

        $criteria = [];
        if ($enableFilter != -1) {
            $criteria = array('enable' => (boolean)$enableFilter);
        }

        $params['criteria'] = $criteria;
        $columns = array('id', 'username', 'alias', 'enable');

        $userRep = $em->getRepository('BB\DurianBundle\Entity\User');
        $dominators = $userRep->findChildArrayBy(
            null,
            $params,
            null,
            null,
            $columns
        );

        $domainIds = [];
        foreach ($dominators as $index => $dominator) {
            //filter為1只抓整合，故大球的廳主濾掉
            if ($filter == 1 && in_array($dominator['id'], $bigBallDomain)) {
                unset($dominators[$index]);
                continue;
            }

            //filter為2只抓大球，故濾掉非大球的廳主
            if ($filter == 2 && !in_array($dominator['id'], $bigBallDomain)) {
                unset($dominators[$index]);
                continue;
            }

            $domainIds[$index] = $dominator['id'];
        }

        //取得所有廳的尾碼並填入結果
        $dcRepo = $emShare->getRepository('BBDurianBundle:DomainConfig');
        $configs = $dcRepo->findBy(['domain' => $domainIds]);
        $configMap = [];
        $configMap[0]['name'] = '';
        $configMap[0]['login_code'] = '';

        foreach ($configs as $config) {
            $domain = $config->getDomain();

            $configMap[$domain]['name'] = $config->getName();
            $configMap[$domain]['login_code'] = $config->getLoginCode();
        }

        foreach ($dominators as $idx => $dominator) {
            $id = $dominator['id'];

            if (!isset($configMap[$id])) {
                $id = 0;
            }

            $dominators[$idx] += $configMap[$id];
        }

        $output['result'] = 'ok';
        $output['ret'] = $dominators;

        return new JsonResponse($output);
    }

    /**
     * 設定Domain支援的幣別
     *
     * @Route("/domain/{domain}/currency",
     *        name = "api_domain_set_currency",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain 廳主id
     * @return JsonResponse
     */
    public function setDomainCurrencyAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $curService = $this->get('durian.currency');
        $repo = $em->getRepository('BBDurianBundle:DomainCurrency');

        $request = $request->request;
        $curIn = $request->get('currencies', array());

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $curNew = array();
            foreach ($curIn as $currency) {
                if (!$curService->isAvailable($currency)) {
                    throw new \InvalidArgumentException('Currency not support', 150360009);
                }

                $curNew[] = $curService->getMappedNum($currency);
            }

            $domain = $this->getDomain($domain);
            $curOld = $this->getCurrencyNumBy($domain);

            // 設定傳入有的但原本沒有的要添加
            $curAdd = array_diff($curNew, $curOld);
            foreach ($curAdd as $curNum) {
                $domainCurrency = new DomainCurrency($domain, $curNum);
                $em->persist($domainCurrency);
            }

            // 原本有的但設定傳入沒有的要移除
            $curDiff = array_diff($curOld, $curNew);
            foreach ($curDiff as $curNum) {
                $criteria = array(
                    'domain'   => $domain,
                    'currency' => $curNum,
                );
                $curBye = $repo->findOneBy($criteria);

                if ($curBye) {
                    $em->remove($curBye);
                }
            }

            $oldCurs = $newCurs = '';

            if (!empty($curOld)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($curOld);
                $oldCurs = implode(', ', $curOld);
            }

            if (!empty($curNew)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($curNew);
                $newCurs = implode(', ', $curNew);
            }

            if ($oldCurs != $newCurs) {
                $log = $operationLogger->create('domain_currency', ['domain' => $domain->getId()]);
                $log->addMessage('currency', $oldCurs, $newCurs);
                $operationLogger->save($log);
                $em->flush();
                $emShare->flush();
            }

            $output['result'] = 'ok';
            $output['ret'] = $this->getCurrencyBy($domain);
            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得Domain支援幣別
     *
     * @Route("/domain/{domain}/currency",
     *        name = "api_domain_get_currency",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain 廳主id
     * @return JsonResponse
     */
    public function getDomainCurrencyAction($domain)
    {
        $domain = $this->getDomain($domain);

        $output['result'] = 'ok';
        $output['ret'] = $this->getCurrencyBy($domain);

        return new JsonResponse($output);
    }

    /**
     * 設定Domain預設幣別
     *
     * @Route("/domain/{domain}/currency/{currency}/preset",
     *        name = "api_domain_currency_preset",
     *        requirements = {"domain" = "\d+", "currency" = "\w+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $domain 廳主id
     * @param string $currency 幣別
     * @return JsonResponse
     */
    public function setDomainCcurrencyPresetAction($domain, $currency)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $curService = $this->get('durian.currency');
        $repo = $em->getRepository('BBDurianBundle:DomainCurrency');

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if (!$curService->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 150360009);
            }

            $domain = $this->getDomain($domain);
            $curNum = $curService->getMappedNum($currency);

            // 檢查傳入的幣別是否被domain支援
            $criteria = array(
                'domain' => $domain,
                'currency' => $curNum,
            );
            $domainCurrency = $repo->findOneBy($criteria);

            if (!$domainCurrency) {
                throw new \RuntimeException('Domain not support this currency', 150360010);
            }

            // 將原本預設的幣別關閉
            $criteriaPreset = array(
                'domain' => $domain,
                'preset' => 1,
            );
            $domainCurrencies = $repo->findBy($criteriaPreset);

            foreach ($domainCurrencies as $dcOff) {
                $dcOff->presetOff();

                $majorKey = [
                    'domain' => $dcOff->getDomain(),
                    'currency' => $dcOff->getCurrency()
                ];

                $log = $operationLogger->create('domain_currency', $majorKey);
                $log->addMessage('preset', 'true', 'false');
                $operationLogger->save($log);
            }

            // 設定為預設
            $domainCurrency->presetOn();

            $majorKey = [
                'domain' => $domainCurrency->getDomain(),
                'currency' => $domainCurrency->getCurrency()
            ];

            $log = $operationLogger->create('domain_currency', $majorKey);
            $log->addMessage('preset', 'false', 'true');
            $operationLogger->save($log);

            $em->flush();
            $emShare->flush();

            $output['result'] = 'ok';
            $output['ret'] = $domainCurrency->toArray();
            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得單一廳主API
     * @Route("/domain/{id}",
     *        name = "api_get_domain",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $id 廳主id
     * @return JsonResponse
     */
    public function getDomainAction($id)
    {
        $domain = $this->getDomain($id);
        $config = $this->getConfig($id);

        $output['result'] = 'ok';
        $output['ret'] = [
            'id'       => $domain->getId(),
            'username' => $domain->getUsername(),
            'alias'    => $domain->getAlias(),
            'enable'   => $domain->isEnabled(),
            'name'     => $config->getName()
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得登入代碼API
     * @Route("/domain/login_code",
     *        name = "api_get_login_code",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLoginCodeAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager('share');
        $lcRepo = $em->getRepository('BBDurianBundle:DomainConfig');

        $domain = $query->get('domain');
        $loginCode = '';

        if ($query->get('code')) {
            $loginCode = $query->get('code');
        }

        $criteria = [];
        if ($domain) {
            $criteria['domain'] = [$domain];
        }

        if ($loginCode) {
            $criteria['loginCode'] = [$loginCode];
        }

        $result = $lcRepo->findBy($criteria, ['domain' => 'ASC']);

        $output['result'] = 'ok';
        $output['ret'] = [];
        foreach ($result as $row) {
            $output['ret'][] = [
                'domain'  => $row->getDomain(),
                'code'    => $row->getLoginCode(),
                'removed' => $row->isRemoved()
            ];
        }

        return new JsonResponse($output);
    }

    /**
     * 修改登入代碼API
     * @Route("/domain/{domain}/login_code",
     *        name = "api_set_login_code",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain 廳主id
     * @return JsonResponse
     */
    public function setLoginCodeAction(Request $request, $domain)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $loginCode = '';

        if ($request->get('code')) {
            $loginCode = $request->get('code');
        }

        $this->checkLoginCode($loginCode);

        $config = $this->getConfig($domain);
        $oldCode = $config->getLoginCode();

        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('domain_config', ['domain' => $domain]);
        $log->addMessage('login_code', $oldCode, $loginCode);
        $operationLogger->save($log);

        $config->setLoginCode($loginCode);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = [
            'domain' => $config->getDomain(),
            'code'   => $config->getLoginCode()
        ];

        return new JsonResponse($output);
    }

    /**
     * 回傳廳的設定
     *
     * @Route("/domain/config",
     *        name = "api_domain_get_config",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author petty 2014.10.06
     */
    public function getConfigAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:DomainConfig');
        $redis = $this->get('snc_redis.default_client');

        $domain = $query->get('domain');
        $disableOtp = $redis->get('disable_otp');

        $criteria = [];
        if ($domain) {
            $criteria['domain'] = $domain;
        }

        if ($query->has('block_create_user')) {
            $criteria['blockCreateUser'] = $query->get('block_create_user');
        }

        if ($query->has('block_login')) {
            $criteria['blockLogin'] = $query->get('block_login');
        }

        if ($query->has('block_test_user')) {
            $criteria['blockTestUser'] = $query->get('block_test_user');
        }

        if ($query->has('login_code')) {
            $criteria['loginCode'] = $query->get('login_code');
        }

        if ($query->has('name')) {
            $criteria['name'] = $query->get('name');
        }

        if ($query->has('verify_otp')) {
            $criteria['verifyOtp'] = $query->get('verify_otp');
        }

        if ($query->has('free_transfer_wallet')) {
            $criteria['freeTransferWallet'] = $query->get('free_transfer_wallet');
        }

        if ($query->has('wallet_status')) {
            $criteria['walletStatus'] = $query->get('wallet_status');
        }

        //filter為0不過濾，1為只抓整合，2為只抓大球
        $filter = $query->get('filter', 0);

        //$enable為-1不過濾，1為抓啟用, 0為取停用
        $enable = $query->get('enable', -1);

        if ($enable != -1) {
            $criteria['enable'] = (boolean) $enable;
        }

        $dominators = $repo->findBy($criteria, ['domain' => 'ASC']);

        $output['result'] = 'ok';
        $output['ret'] = [];

        if ($dominators) {
            $bigBallDomain = [20000007, 20000008, 20000009, 20000010];

            foreach ($dominators as $dominator) {
                $domainId = $dominator->getDomain();

                //filter為1只抓整合，故大球的廳主濾掉
                if ($filter == 1 && in_array($domainId, $bigBallDomain)) {
                    continue;
                }

                //filter為2只抓大球，故濾掉非大球的廳主
                if ($filter == 2 && !in_array($domainId, $bigBallDomain)) {
                    continue;
                }

                $result = $dominator->toArray();

                //因為研六會用verfiy_otp欄位結果當作前端是否顯示輸入token欄位的依據，
                //當總開關為關閉時，前端不須顯示輸入欄位，故覆蓋掉回傳結果為false
                if ($disableOtp) {
                    $result['verify_otp'] = false;
                }

                $output['ret'][] = $result;
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 回傳廳的設定
     *
     * @Route("/v2/domain/config",
     *        name = "api_v2_domain_get_config",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.11.04
     */
    public function getConfigV2Action(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:DomainConfig');
        $redis = $this->get('snc_redis.default_client');

        $criteria = [];
        $disableOtp = $redis->get('disable_otp');

        if ($query->has('block_create_user')) {
            $criteria['blockCreateUser'] = $query->get('block_create_user');
        }

        if ($query->has('block_login')) {
            $criteria['blockLogin'] = $query->get('block_login');
        }

        if ($query->has('block_test_user')) {
            $criteria['blockTestUser'] = $query->get('block_test_user');
        }

        if ($query->has('login_code')) {
            $criteria['loginCode'] = $query->get('login_code');
        }

        if ($query->has('name')) {
            $criteria['name'] = $query->get('name');
        }

        if ($query->has('verify_otp')) {
            $criteria['verifyOtp'] = $query->get('verify_otp');
        }

        if ($query->has('free_transfer_wallet')) {
            $criteria['freeTransferWallet'] = $query->get('free_transfer_wallet');
        }

        if ($query->has('wallet_status')) {
            $criteria['walletStatus'] = $query->get('wallet_status');
        }

        //filter為0不過濾，1為只抓整合，2為只抓大球
        $filter = $query->get('filter', 0);

        //$enable為-1不過濾，1為抓啟用, 0為取停用
        $enable = $query->get('enable', -1);

        if ($enable != -1) {
            $criteria['enable'] = (boolean) $enable;
        }

        $dominators = $repo->findBy($criteria, ['domain' => 'ASC']);

        $output['result'] = 'ok';
        $output['ret'] = [];

        if ($dominators) {
            $bigBallDomain = [20000007, 20000008, 20000009, 20000010];

            foreach ($dominators as $dominator) {
                $domainId = $dominator->getDomain();

                //filter為1只抓整合，故大球的廳主濾掉
                if ($filter == 1 && in_array($domainId, $bigBallDomain)) {
                    continue;
                }

                //filter為2只抓大球，故濾掉非大球的廳主
                if ($filter == 2 && !in_array($domainId, $bigBallDomain)) {
                    continue;
                }

                $result = $dominator->toArray();

                //OTP總開關為不驗證時，覆蓋掉回傳結果
                if ($disableOtp) {
                    $result['verify_otp'] = false;
                }

                $output['ret'][] = $result;
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 回傳指定廳相關設定
     *
     * @Route("/domain/config_by_domain",
     *        name = "api_domain_get_config_by_domain",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.11.04
     */
    public function getConfigByDomainAction(Request $request)
    {
        $query = $request->query;
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:DomainConfig');
        $redis = $this->get('snc_redis.default_client');

        $domain = $query->get('domain');
        $disableOtp = $redis->get('disable_otp');

        if (empty($domain)) {
            throw new \InvalidArgumentException('No domain specified', 150360023);
        }

        $criteria = [];

        $criteria['domain'] = $domain;

        if ($query->has('block_create_user')) {
            $criteria['blockCreateUser'] = $query->get('block_create_user');
        }

        if ($query->has('block_login')) {
            $criteria['blockLogin'] = $query->get('block_login');
        }

        if ($query->has('block_test_user')) {
            $criteria['blockTestUser'] = $query->get('block_test_user');
        }

        if ($query->has('login_code')) {
            $criteria['loginCode'] = $query->get('login_code');
        }

        if ($query->has('name')) {
            $criteria['name'] = $query->get('name');
        }

        if ($query->has('verify_otp')) {
            $criteria['verifyOtp'] = $query->get('verify_otp');
        }

        if ($query->has('free_transfer_wallet')) {
            $criteria['freeTransferWallet'] = $query->get('free_transfer_wallet');
        }

        if ($query->has('wallet_status')) {
            $criteria['walletStatus'] = $query->get('wallet_status');
        }

        $validDomain = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if(!$validDomain) {
            throw new \RuntimeException('Not a domain', 150360024);
        }

        $dominators = $repo->findBy($criteria, ['domain' => 'ASC']);

        $output['result'] = 'ok';
        $output['ret'] = [];

        if ($dominators) {
            foreach ($dominators as $dominator) {
                $result = $dominator->toArray();

                //OTP總開關為不驗證時，覆蓋掉回傳結果
                if ($disableOtp) {
                    $result['verify_otp'] = false;
                }

                $output['ret'][] = $result;
            }
        }

        return new JsonResponse($output);
    }

    /**
     * 修改廳相關設定
     *
     * @Route("/domain/{domain}/config",
     *        name = "api_domain_set_config",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain 廳主id
     * @return JsonResponse
     *
     * @author petty 2014.10.06
     */
    public function setConfigAction(Request $request, $domain)
    {
        $request = $request->request;
        $em = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');

        $blockCreateUser = (bool) $request->get('block_create_user');
        $blockLogin = (bool) $request->get('block_login');
        $blockTestUser = (bool) $request->get('block_test_user');
        $loginCode = $request->get('login_code');
        $name = trim($request->get('name'));
        $verifyOtp = (bool) $request->get('verify_otp');

        $validator->validateEncode($name);
        $name = $parameterHandler->filterSpecialChar($name);

        $config = $em->find('BBDurianBundle:DomainConfig', $domain);

        if (!$config) {
            throw new \RuntimeException('No domain config found', 150360004);
        }

        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('domain_config', ['domain' => $domain]);

        // 設定阻擋新增使用者
        $old = $config->isBlockCreateUser();
        if ($request->has('block_create_user') && $old != $blockCreateUser) {
            $log->addMessage('block_create_user', var_export($old, true), var_export($blockCreateUser, true));
            $config->setBlockCreateUser($blockCreateUser);
        }

        // 設定阻擋登入
        $oldLoginSet = $config->isBlockLogin();
        if ($request->has('block_login') && $oldLoginSet != $blockLogin) {
            $log->addMessage('block_login', var_export($oldLoginSet, true), var_export($blockLogin, true));
            $config->setBlockLogin($blockLogin);
        }

        // 設定阻擋測試帳號
        $oldTestSet = $config->isBlockTestUser();
        if ($request->has('block_test_user') && $oldTestSet != $blockTestUser) {
            $log->addMessage('block_test_user', var_export($oldTestSet, true), var_export($blockTestUser, true));
            $config->setBlockTestUser($blockTestUser);
        }

        $oldLoginCode = $config->getLoginCode();
        if ($request->has('login_code') && $oldLoginCode != $loginCode) {
            $this->checkLoginCode($loginCode);
            $log->addMessage('login_code', $oldLoginCode, $loginCode);
            $config->setLoginCode($loginCode);
        }

        $oldName = $config->getName();
        if ($name && $oldName != $name) {
            $validator = $this->get('durian.domain_validator');
            $validator->validateName($name);

            $log->addMessage('name', $oldName, $name);
            $config->setName($name);
        }

        // 設定otp驗證
        $oldVerifyOtp = $config->isVerifyOtp();
        if ($request->has('verify_otp') && $oldVerifyOtp != $verifyOtp) {
            $log->addMessage('verify_otp', var_export($oldVerifyOtp, true), var_export($verifyOtp, true));
            $config->setVerifyOtp($verifyOtp);
        }

        $operationLogger->save($log);
        $em->flush();

        $output['result'] = 'ok';
        $output['ret'] = $config->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳單一IP封鎖列表
     *
     * @Route("/domain/ip_blacklist/{ipBlacklistId}",
     *        name = "api_get_ip_blacklist_by_id",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $ipBlacklistId IP封鎖列表id
     * @return JsonResponse
     */
    public function getIpBlacklistByIdAction($ipBlacklistId)
    {
        $emShare = $this->getEntityManager('share');
        $ipBlacklist = $emShare->find('BBDurianBundle:IpBlacklist', $ipBlacklistId);

        if (!$ipBlacklist) {
            throw new \RuntimeException('No ipBlacklist found', 150360027);
        }

        $output['result'] = 'ok';
        $output['ret'] = $ipBlacklist->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳IP封鎖列表
     *
     * @Route("/domain/ip_blacklist",
     *        name = "api_get_ip_blacklist",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author petty 2014.10.06
     */
    public function getIpBlacklistAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager('share');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $domain = $query->get('domain', []);

        $criteria = [
            'ip'      => $query->get('ip'),
            'removed' => $query->get('removed'),
            'start'   => $query->get('start'),
            'end'     => $query->get('end')
        ];

        if ($domain) {
            $criteria['domain'] = $domain;
        }

        if ($query->has('create_user')) {
            $criteria['createUser'] = $query->get('create_user');
        }

        if ($query->has('login_error')) {
            $criteria['loginError'] = $query->get('login_error');
        }

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $repo = $em->getRepository('BBDurianBundle:IpBlacklist');

        $total = $repo->countListBy($criteria);

        $list = $repo->getListBy($criteria, $orderBy, $firstResult, $maxResults);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($list as $data) {
            $ret = $data->toArray();
            $output['ret'][] = $ret;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 從IP封鎖列表中移除ip
     *
     * @Route("/domain/ip_blacklist",
     *        name = "api_remove_ip_blacklist",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author petty 2014.10.06
     */
    public function removeIpBlacklistAction(Request $request)
    {
        $request = $request->request;
        $validator = $this->get('durian.validator');

        $blacklistId = $request->get('blacklist_id');
        $operator = trim($request->get('operator', ''));

        // 驗證參數編碼是否為utf8
        $validator->validateEncode($operator);

        if (!$blacklistId) {
            throw new \InvalidArgumentException('No blacklist_id specified', 150360003);
        }

        $emShare = $this->getEntityManager('share');

        $repo = $emShare->getRepository('BBDurianBundle:IpBlacklist');
        $blacklist = $repo->findOneBy(['id' => $blacklistId]);

        if (!$blacklist) {
            throw new \RuntimeException('No ipBlacklist found', 150360001);
        }

        if ($blacklist->isRemoved()) {
            throw new \RuntimeException('Blacklist_id already removed', 150360002);
        }

        // 寫入操作紀錄
        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('ip_blacklist', ['id' => $blacklist->getId()]);
        $log->addMessage('removed', 'false', 'true');
        $operationLogger->save($log);

        $blacklist->remove($operator);
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $blacklist->toArray();

        return new JsonResponse($output);
    }


    /**
     * 停用廳主及子帳號
     *
     * @Route("/domain/{domain}/disable",
     *        name = "api_domain_disable",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $domain 廳主id
     * @return JsonResponse
     *
     * @author ruby 2014.10.24
     */
    public function disableDomainAction($domain)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if (!$domainConfig) {
            throw new \RuntimeException('Not a domain', 150360019);
        }

        $user = $em->find('BBDurianBundle:User', $domain);

        $sensitiveLogger->writeSensitiveLog();
        $result = $sensitiveLogger->validateHasOperationData();

        if (!$result['result']) {
            throw new \RuntimeException($result['msg'], $result['code']);
        }

        $sensitiveLogger->validateAllowedOperator($user);

        $domains = $em->getRepository('BBDurianBundle:User')->findBy([
            'domain' => $domain,
            'role' => 7
        ]);

        $domainConfig->disable();

        // 停用廳主及子帳號
        foreach ($domains as $domain) {
            if (!$domain->isEnabled()) {
                continue;
            }

            $log = $operationLogger->create('user', ['id' => $domain->getId()]);
            $log->addMessage('enable', var_export($domain->isEnabled(), true), 'false');
            $operationLogger->save($log);
            $domain->setModifiedAt(new \DateTime());
            $domain->disable();
        }

        $em->flush();
        $emShare->flush();

        $name = $this->getConfig($user->getId())->getName();

        $output['result'] = 'ok';
        $output['ret'] = $user->toArray();
        $output['ret']['name'] = $name;

        return new JsonResponse($output);
    }

    /**
     * 取得單一廳主下所有的會員層級
     * @Route("/domain/{domain}/levels",
     *        name = "api_get_domain_levels",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain 廳主id
     * @return JsonResponse
     */
    public function getDomainLevelsAction($domain)
    {
        $levelRepo = $this->getEntityManager()->getRepository('BBDurianBundle:Level');
        $out = $levelRepo->getDomainLevels($domain);

        $output['result'] = 'ok';
        $output['ret'] = $out;

        return new JsonResponse($output);
    }

    /**
     * 更新廳下層會員的測試帳號數量記錄
     *
     * @Route("/domain/{domain}/total_test",
     *        name = "api_domain_update_total_test",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $domain 廳主id
     * @return JsonResponse
     *
     * @author petty 2015.12.01
     */
    public function updateTotalTestAction($domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $this->getDomain($domain);

        $number = $em->getRepository('BBDurianBundle:User')
            ->countAllChildUserByTest($domain, true);

        $log = $operationLogger->create('domain_total_test', ['domain' => $domain]);

        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', $domain);

        if (!$totalTest) {
            $totalTest = new DomainTotalTest($domain);
            $em->persist($totalTest);
        }

        if ($totalTest->getTotalTest() != $number) {
            $log->addMessage('total_test', $totalTest->getTotalTest(), $number);
        }

        $totalTest->setTotalTest($number);

        $now = new \DateTime('now');
        $totalTest->setAt($now);

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();
        $output['result'] = 'ok';
        $output['ret'] = $totalTest->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳廳下層會員的測試帳號數量記錄
     *
     * @Route("/domain/total_test",
     *        name = "api_domain_get_total_test",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author petty 2015.12.18
     */
    public function getDomainTotalTestAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:DomainTotalTest');

        $domain = $query->get('domain');

        $criteria = [];
        if ($domain) {
            $criteria['domain'] = $domain;
        }

        $result = $repo->findBy($criteria);

        $output['result'] = 'ok';
        $output['ret'] = [];
        foreach ($result as $row) {
            $output['ret'][] = $row->toArray();
        }

        return new JsonResponse($output);
    }

    /**
     * 回傳廳時間區間內會員的建立數量(輸出日期為美東時間)
     *
     * @Route("/domain/{domain}/count_member_created",
     *        name = "api_domain_count_member_created",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $domain 廳主id
     * @return JsonResponse
     *
     * @author Billy 2016.08.26
     */
    public function domainCountMemberCreatedAction(Request $request, $domain)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:User');
        $validator = $this->get('durian.validator');

        $start = $query->get('start');
        $end = $query->get('end');

        if (!$validator->validateDateRange($start, $end)) {
            throw new \InvalidArgumentException('No start or end specified', 150360018);
        }
        $this->getDomain($domain);

        // 須將輸入的美東時間改為台灣時間以做搜尋條件
        $timeZoneTaipei = new \DateTimeZone('Asia/Taipei');
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);

        $criteria['start_at'] = $startDate->setTimeZone($timeZoneTaipei);
        $criteria['end_at'] = $endDate->setTimeZone($timeZoneTaipei);
        $criteria['domain'] = $domain;

        $result = $repo->countMemberCreatedByDomain($criteria);

        // 需轉換為美東時間, 格式保留Ymd
        $timeZoneUSE = new \DateTimeZone('Etc/GMT+4');
        $startDate->setTimezone($timeZoneUSE);
        $endDate->setTimezone($timeZoneUSE);

        $ret = [];

        // 該日期沒有會員建立的話需補0
        while ($startDate <= $endDate) {
            $count = '0';

            // 檢查查詢結果有無此天資料
            $key = array_search($startDate->format('Y/m/d'), array_column($result, 'date'));

            if ($key !== false) {
                $count = $result[$key]['total'];
            }

            $ret[] = [
                'date' => $startDate->format('Y-m-d'),
                'count' => $count
            ];

            $startDate->add(new \DateInterval('P1D'));
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }
    /**
     * 回傳網址列表
     *
     * @Route("/domain/url/list",
     *        name = "api_domain_get_url_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUrlListAction(Request $request)
    {
        $query = $request->query;
        $group = $query->get('group', '');

        $client = new Curl();
        $response = new Response();

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $url = $this->container->getParameter('kiwi_ip') . '/api/url/list';
        $key = $this->container->getParameter('kiwi_api_key');
        $host = $this->container->getParameter('kiwi_host');
        $port = $this->container->getParameter('kiwi_port');

        $curlRequest = new FormRequest('GET', $url);
        $curlRequest->addHeader('api-key: ' . $key);
        $curlRequest->addHeader('host: ' . $host);

        if ($group) {
            $curlRequest->addFields(['group' => $group]);
        }

        $client->setOption(CURLOPT_TIMEOUT, 30);
        $client->setOption(CURLOPT_PORT, $port);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $client->send($curlRequest, $response);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Curl getUrlList api failed', 150360012);
        }

        $ret = json_decode($response->getContent(), true);
        unset($ret['profile']);

        return new JsonResponse($ret);
    }

    /**
     * 回傳網址狀態
     *
     * @Route("/domain/url/status",
     *        name = "api_domain_get_url_status",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getUrlStatusAction()
    {
        $client = new Curl();
        $response = new Response();

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $url = $this->container->getParameter('kiwi_ip') . '/api/url/status';
        $key = $this->container->getParameter('kiwi_api_key');
        $host = $this->container->getParameter('kiwi_host');
        $port = $this->container->getParameter('kiwi_port');

        $curlRequest = new FormRequest('GET', $url);
        $curlRequest->addHeader('api-key: ' . $key);
        $curlRequest->addHeader('host: ' . $host);

        $client->setOption(CURLOPT_TIMEOUT, 30);
        $client->setOption(CURLOPT_PORT, $port);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $client->send($curlRequest, $response);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Curl getUrlStatus api failed', 150360013);
        }

        $ret = json_decode($response->getContent(), true);
        unset($ret['profile']);

        return new JsonResponse($ret);
    }

    /**
     * 回傳網址站別列表
     *
     * @Route("/domain/url/site",
     *        name = "api_domain_get_url_site",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getUrlSiteAction()
    {
        $client = new Curl();
        $response = new Response();

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $url = $this->container->getParameter('kiwi_ip') . '/api/url/site?big_group=IPL';
        $key = $this->container->getParameter('kiwi_api_key');
        $host = $this->container->getParameter('kiwi_host');
        $port = $this->container->getParameter('kiwi_port');

        $curlRequest = new FormRequest('GET', $url);
        $curlRequest->addHeader('api-key: ' . $key);
        $curlRequest->addHeader('host: ' . $host);

        $client->setOption(CURLOPT_TIMEOUT, 30);
        $client->setOption(CURLOPT_PORT, $port);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $client->send($curlRequest, $response);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Curl getUrlSite api failed', 150360014);
        }

        $ret = json_decode($response->getContent(), true);
        unset($ret['profile']);

        return new JsonResponse($ret);
    }

    /**
     * 停用廳主商家
     *
     * @Route("/domain/{domain}/merchants/disable",
     *        name = "api_domain_merchants_disable",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function disableDomainMerchantsAction($domain)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $domain = $this->getDomain($domain);

        if ($domain->isEnabled()) {
            throw new \RuntimeException('Cannot disable merchants when domain enabled', 150360022);
        }

        $sensitiveLogger->writeSensitiveLog();
        $result = $sensitiveLogger->validateHasOperationData();

        if (!$result['result']) {
            throw new \RuntimeException($result['msg'], $result['code']);
        }

        $sensitiveLogger->validateAllowedOperator($domain);

        /**
         * 停用該廳所有商家，為了回復方便
         * 1.不改狀態
         * 2.只清空privateKey與shopUrl
         * 3.privateKey不遮蔽
         */
        $merchants = $em->getRepository('BBDurianBundle:Merchant')->findBy(['domain' => $domain]);

        foreach ($merchants as $merchant) {
            $log = $operationLogger->create('merchant', ['id' => $merchant->getId()]);
            $log->addMessage('private_key', $merchant->getPrivateKey(), '');
            $log->addMessage('shop_url', $merchant->getShopUrl(), '');
            $operationLogger->save($log);

            $merchant->setPrivateKey('');
            $merchant->setShopUrl('');
        }

        // 停用租卡商家
        $merchantCards = $em->getRepository('BBDurianBundle:MerchantCard')->findBy(['domain' => $domain]);

        foreach ($merchantCards as $merchantCard) {
            $log = $operationLogger->create('merchant_card', ['id' => $merchantCard->getId()]);
            $log->addMessage('private_key', $merchantCard->getPrivateKey(), '');
            $log->addMessage('shop_url', $merchantCard->getShopUrl(), '');
            $operationLogger->save($log);

            $merchantCard->setPrivateKey('');
            $merchantCard->setShopUrl('');
        }

        // 停用出款商家
        $merchantWithdraws = $em->getRepository('BBDurianBundle:MerchantWithdraw')->findBy(['domain' => $domain]);

        foreach ($merchantWithdraws as $merchantWithdraw) {
            $log = $operationLogger->create('merchant_withdraw', ['id' => $merchantWithdraw->getId()]);
            $log->addMessage('private_key', $merchantWithdraw->getPrivateKey(), '');
            $log->addMessage('shop_url', $merchantWithdraw->getShopUrl(), '');
            $operationLogger->save($log);

            $merchantWithdraw->setPrivateKey('');
            $merchantWithdraw->setShopUrl('');
        }

        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 設定Domain外接額度交易機制
     *
     * @Route("/domain/{domain}/outside/payway",
     *        name = "api_domain_set_outside_payway",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain 廳主id
     * @return JsonResponse
     */
    public function setDomainOutsidePaywayAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $request = $request->request;
        $bodog = (bool) $request->get('bodog');

        if (!$request->has('bodog')) {
            throw new \InvalidArgumentException('No outside payway specified', 150360025);
        }

        $outsidePayway = $em->find('BBDurianBundle:OutsidePayway', $domain);

        if (!$outsidePayway) {
            throw new \RuntimeException('No outside supported', 150360026);
        }

        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('outside_payway', ['domain' => $domain]);

        if ($outsidePayway->isBodog() != $bodog) {
            $log->addMessage('bodog', var_export($outsidePayway->isBodog(), true), var_export($bodog, true));
            $outsidePayway->setBodog($bodog);
        }

        $em->flush();

        if ($log->getMessage()) {
            $operationLogger->save($log);
            $emShare->flush();
        }

        $output = [
            'result' => 'ok',
            'ret' => $outsidePayway->toArray()
        ];

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
     * 取得廳主
     *
     * @param integer $userId 廳主ID
     * @return User
     */
    private function getDomain($userId)
    {
        $em = $this->getEntityManager();
        $domain = $em->find('BBDurianBundle:User', $userId);

        if (!$domain) {
            throw new \RuntimeException('No such user', 150360006);
        }

        if (!is_null($domain->getParent())) {
            throw new \RuntimeException('Not a domain', 150360007);
        }

        return $domain;
    }

    /**
     * 取得廳主可用的幣別資料
     *
     * @param User $domain 廳主
     * @return array
     */
    private function getCurrencyBy($domain)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:DomainCurrency');

        $criteria = array('domain' => $domain);
        $domainCurrencies = $repo->findBy($criteria);

        $currencies = array();
        foreach ($domainCurrencies as $domainCurrency) {
            $currencies[] = $domainCurrency->toArray();
        }

        return $currencies;
    }

    /**
     * 取得廳主設定的幣別num
     *
     * @param User $domain 廳主
     * @return array
     */
    private function getCurrencyNumBy($domain)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:DomainCurrency');

        $criteria = array('domain' => $domain);
        $domainCurrencies = $repo->findBy($criteria);

        $currencies = array();
        foreach ($domainCurrencies as $domainCurrency) {
            $currencies[] = $domainCurrency->getCurrency();
        }

        return $currencies;
    }

    /**
     * 檢查login code是否符合要求
     *
     * @param string $loginCode 登入代碼
     * @throws \InvalidArgumentException
     */
    private function checkLoginCode($loginCode)
    {
        $em = $this->getEntityManager('share');
        $dcRepo = $em->getRepository('BBDurianBundle:DomainConfig');
        $domainValidator = $this->get('durian.domain_validator');

        $domainValidator->validateLoginCode($loginCode);

        //檢查loginCode是否重覆
        $dcResult = $dcRepo->findOneBy(['loginCode' => $loginCode]);
        if ($dcResult) {
            throw new \RuntimeException('Login code already exists', 150360011);
        }
    }

    /**
     * 取得廳的設定
     *
     * @param integer $domain 廳
     * @return DomainConfig
     */
    private function getConfig($domain)
    {
        $em = $this->getEntityManager('share');
        $config = $em->find('BBDurianBundle:DomainConfig', $domain);

        if (!$config) {
            throw new \RuntimeException('No domain config found', 150360004);
        }

        return $config;
    }
}
