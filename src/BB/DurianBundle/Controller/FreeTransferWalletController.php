<?php
namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\UserLastGame;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;

class FreeTransferWalletController extends Controller
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
     * 啟用廳免轉錢包功能
     *
     * @Route("/domain/{domain}/free_transfer_wallet/enable",
     *        name = "api_domain_free_transfer_wallet_enable",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $domain 廳主編號
     * @return JsonResponse
     */
    public function enableFreeTransferWalletAction($domain)
    {
        $em = $this->getEntityManager('share');
        $domainConfig = $em->find('BBDurianBundle:DomainConfig', $domain);

        if (!$domainConfig) {
            throw new \RuntimeException('Not a domain', 150960001);
        }

        //原本為停用才紀錄
        if (!$domainConfig->isFreeTransferWallet()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('domain_config', ['domain' => $domain]);
            $log->addMessage('free_transfer_wallet', var_export($domainConfig->isFreeTransferWallet(), true), 'true');
            $log->addMessage('wallet_status', $domainConfig->getWalletStatus(), 2);
            $operationLogger->save($log);

            //控端開啟廳主免轉錢包時，預設為會員自選
            $domainConfig->enableFreeTransferWallet();
            $domainConfig->setWalletStatus(2);
            $em->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $domainConfig->toArray();

        return new JsonResponse($output);
    }

    /**
     * 停用廳免轉錢包功能
     *
     * @Route("/domain/{domain}/free_transfer_wallet/disable",
     *        name = "api_domain_free_transfer_wallet_disable",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $domain 廳主編號
     * @return JsonResponse
     */
    public function disableFreeTransferWalletAction($domain)
    {
        $em = $this->getEntityManager('share');
        $domainConfig = $em->find('BBDurianBundle:DomainConfig', $domain);

        if (!$domainConfig) {
            throw new \RuntimeException('Not a domain', 150960002);
        }

        //原本為啟用才紀錄
        if ($domainConfig->isFreeTransferWallet()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('domain_config', ['domain' => $domain]);
            $log->addMessage('free_transfer_wallet', var_export($domainConfig->isFreeTransferWallet(), true), 'false');

            //原先錢包狀態不為0時，寫操作紀錄
            if ($domainConfig->getWalletStatus() != 0) {
                $log->addMessage('wallet_status', $domainConfig->getWalletStatus(), 0);
            }

            $operationLogger->save($log);

            $domainConfig->disableFreeTransferWallet();
            $domainConfig->setWalletStatus(0);
            $em->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $domainConfig->toArray();

        return new JsonResponse($output);
    }

    /**
     * 設定廳錢包狀態(0:多錢包、1:免轉錢包、2:會員自選)
     *
     * @Route("/domain/{domain}/free_transfer_wallet/status",
     *        name = "api_set_domain_wallet_status",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain 廳主編號
     * @return JsonResponse
     */
    public function setDomainWalletAction(Request $request, $domain)
    {
        $walletStatus = $request->request->get('wallet_status');
        $regex = '/^[0-2]$/';

        if (!preg_match($regex, $walletStatus)) {
            throw new \InvalidArgumentException('Invalid domain wallet status', 150960003);
        }

        $em = $this->getEntityManager('share');

        $domainConfig = $em->find('BBDurianBundle:DomainConfig', $domain);

        if (!$domainConfig) {
            throw new \RuntimeException('Not a domain', 150960004);
        }

        //若設定免轉錢包或會員自選，免轉錢包開關應一起開啟
        if (!$domainConfig->isFreeTransferWallet()) {
            throw new \RuntimeException('Domain free transfer wallet is not enabled', 150960005);
        }

        if ($domainConfig->getWalletStatus() != $walletStatus) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('domain_config', ['domain' => $domain]);
            $log->addMessage('wallet_status', $domainConfig->getWalletStatus(), $walletStatus);
            $operationLogger->save($log);

            $domainConfig->setWalletStatus($walletStatus);
            $em->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $domainConfig->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者是否啟用免轉錢包
     *
     * @Route("/user/{userId}/free_transfer_wallet",
     *        name = "api_get_user_free_transfer_wallet",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者編號
     * @return JsonResponse
     */
    public function getUserLastGameAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150960006);
        }

        // 若使用者尚未調整過錢包狀態，預設為停用免轉錢包
        $ret = [
            'user_id' => $userId,
            'enable' => false,
            'last_game_code' => 1,
            'modified_at' => null
        ];

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', $userId);

        if ($userLastGame) {
            $ret = $userLastGame->toArray();
        }

        // 回傳以使用者所在廳為主，若廳開放會員自選則顯示會員本身的設定
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $user->getDomain());

        $walletStatus = $domainConfig->getWalletStatus();

        if ($walletStatus == 0) {
            $ret['enable'] = false;
        }

        if ($walletStatus == 1) {
            $ret['enable'] = true;
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 啟用使用者免轉錢包功能
     *
     * @Route("/user/{userId}/free_transfer_wallet/enable",
     *        name = "api_user_free_transfer_wallet_enable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId 使用者編號
     * @return JsonResponse
     */
    public function enableUserLastGameAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150960007);
        }

        $domain = $user->getDomain();
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if ($domainConfig->getWalletStatus() !== 2) {
            throw new \RuntimeException('Domain is not allow user to set free transfer wallet', 150960012);
        }

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', $userId);

        if (!$userLastGame) {
            $userLastGame = new UserLastGame($user);
            $userLastGame->setModifiedAt(new \DateTime('now'));
            $em->persist($userLastGame);

            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user_last_game', ['user' => $userId]);
            $log->addMessage('enable', 'true');
            $log->addMessage('last_game_code', 1);
            $operationLogger->save($log);
        }

        if (!$userLastGame->isEnabled()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user_last_game', ['user' => $userId]);
            $log->addMessage('enable', var_export($userLastGame->isEnabled(), true), 'true');

            $userLastGame->enable();
            $userLastGame->setModifiedAt(new \DateTime('now'));

            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $userLastGame->toArray();

        return new JsonResponse($output);
    }

    /**
     * 停用使用者免轉錢包功能
     *
     * @Route("/user/{userId}/free_transfer_wallet/disable",
     *        name = "api_user_free_transfer_wallet_disable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId 使用者編號
     * @return JsonResponse
     */
    public function disableUserLastGameAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150960008);
        }

        $domain = $user->getDomain();
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if ($domainConfig->getWalletStatus() !== 2) {
            throw new \RuntimeException('Domain is not allow user to set free transfer wallet', 150960014);
        }

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', $userId);

        if (!$userLastGame) {
            $userLastGame = new UserLastGame($user);
            $em->persist($userLastGame);

            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user_last_game', ['user' => $userId]);
            $log->addMessage('enable', 'true');
            $log->addMessage('last_game_code', 1);
            $operationLogger->save($log);
        }

        if ($userLastGame->isEnabled()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user_last_game', ['user' => $userId]);
            $log->addMessage('enable', var_export($userLastGame->isEnabled(), true), 'false');
            $operationLogger->save($log);

            $userLastGame->disable();
            $userLastGame->setModifiedAt(new \DateTime('now'));
        }

        if ($userLastGame->getLastGameCode() != 1) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user_last_game', ['user' => $userId]);
            $log->addMessage('last_game_code', $userLastGame->getLastGameCode(), 1);
            $operationLogger->save($log);
            $userLastGame->setLastGameCode(1);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $userLastGame->toArray();

        return new JsonResponse($output);
    }

    /**
     * 設定使用者最近登入遊戲
     *
     * @Route("/user/{userId}/last_game_code",
     *        name = "api_set_user_last_game_code",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者編號
     * @return JsonResponse
     */
    public function setUserLastGameCode(Request $request, $userId)
    {
        $gameCode = $request->request->get('game_code');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        if (!$gameCode) {
            throw new \InvalidArgumentException('No game code specified', 150960009);
        }

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150960010);
        }

        $key = 'external_game_list';
        $redis = $this->get('snc_redis.default');
        $list = $redis->get($key);

        $gameList = json_decode($list, true);
        $gameList['1'] = 'Durian';

        if (!array_key_exists($gameCode, $gameList)) {
            throw new \RuntimeException('No such game code', 150960011);
        }

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', $userId);
        $domain = $emShare->find('BBDurianBundle:DomainConfig', $user->getDomain());

        if (!$userLastGame) {
            $userLastGame = new UserLastGame($user);
            $em->persist($userLastGame);
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user_last_game', ['user' => $userId]);

            // 因為 userLastGame 預設 enable = true，但若廳主設定強制多錢包、會員自選時，則須將 enable 設為 false
            if ($domain->getWalletStatus() == 0 || $domain->getWalletStatus() == 2) {
                $userLastGame->disable();
            }

            $log->addMessage('enable', var_export($userLastGame->isEnabled(), true));
            $log->addMessage('last_game_code', 1);
            $operationLogger->save($log);
        }

        if ($userLastGame->getLastGameCode() != $gameCode) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('user_last_game', ['user' => $userId]);
            $log->addMessage('last_game_code', $userLastGame->getLastGameCode(), $gameCode);
            $operationLogger->save($log);

            $userLastGame->setLastGameCode($gameCode);
            $userLastGame->setModifiedAt(new \DateTime('now'));
        }

        $em->flush();
        $emShare->flush();

        $ret = $userLastGame->toArray();

        if ($domain->getWalletStatus() != 2) {
            if ($domain->getWalletStatus() == 0) {
                $ret['enable'] = false;
            }

            if ($domain->getWalletStatus() == 1) {
                $ret['enable'] = true;
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 免轉錢包
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/free_transfer_wallet",
     *        name = "api_free_transfer_wallet",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function freeTransferWalletAction()
    {
    }

    /**
     * 免轉錢包自動回收額度
     * 這邊只做route 設定，會導向對應的外接遊戲平台做操作
     *
     * @Route("/user/{userId}/free_transfer_wallet/recycle",
     *        name = "api_free_transfer_wallet_recycle",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function freeTransferWalletRecycleAction()
    {
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
}
