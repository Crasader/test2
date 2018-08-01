<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\BitcoinAddress;

class BitcoinAdreessController extends Controller
{
    /**
     * 新增比特幣入款位址
     *
     * @Route("/user/{userId}/bitcoin_address",
     *        name = "api_create_bitcoin_address",
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
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $blockChain = $this->get('durian.block_chain');

        $post = $request->request;
        $walletId = $post->get('wallet_id');

        if (!$walletId) {
            throw new \InvalidArgumentException('No wallet_id specified', 150900001);
        }

        $user = $this->findUser($userId);
        $bitcoinAddress = $this->getBitcoinAddressByUser($userId);

        if (!$bitcoinAddress) {
            $bitcoinWallet = $em->getRepository('BBDurianBundle:BitcoinWallet')
                ->findOneBy(['id' => $walletId, 'domain' => $user->getDomain()]);

            if (!$bitcoinWallet) {
                throw new \RuntimeException('No such bitcoin wallet', 150900003);
            }

            $em->beginTransaction();
            $emShare->beginTransaction();
            try {
                // 先產entity, 沒問題才call api產address
                $bitcoinAddress = new BitcoinAddress(
                    $userId,
                    $walletId,
                    '',
                    ''
                );

                $em->persist($bitcoinAddress);
                $em->flush();

                $username = $user->getUsername();
                $bitcoinAcc = $blockChain->createAccountAddress($bitcoinWallet, $username);
                $bitcoinAddress->setAccount($bitcoinAcc['account']);
                $bitcoinAddress->setAddress($bitcoinAcc['address']);

                $em->flush();

                $addressId = $bitcoinAddress->getId();
                $log = $operationLogger->create('bitcoin_address', ['id' => $addressId]);
                $log->addMessage('user_id', $userId);
                $log->addMessage('wallet_id', $walletId);
                $log->addMessage('account', 'new');
                $log->addMessage('address', $bitcoinAcc['address']);
                $operationLogger->save($log);

                $em->commit();
                $emShare->flush();
                $emShare->commit();
            } catch (\Exception $e) {
                $em->rollback();
                $emShare->rollback();

                // 重複的紀錄
                if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                    throw new \RuntimeException('Database is busy', 150900004);
                }

                throw $e;
            }
        }

        $output = [
            'result' => 'ok',
            'ret' => $bitcoinAddress->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得使用者比特幣入款位址
     *
     * @Route("/user/{userId}/bitcoin_address",
     *        name = "api_get_user_bitcoin_address",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getBitcoinAddressByUserAction($userId)
    {
        $this->findUser($userId);
        $bitcoinAddress = $this->getBitcoinAddressByUser($userId);

        $output = [
            'result' => 'ok',
            'ret' => [],
        ];

        if ($bitcoinAddress) {
            $output['ret'] = $bitcoinAddress->toArray();
        }

        return new JsonResponse($output);
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

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150900002);
        }

        return $user;
    }

    /**
     * 取得單比特幣入款位址
     *
     * @param integer $userId
     * @return BitcoinAddress|null
     */
    private function getBitcoinAddressByUser($userId)
    {
        $em = $this->getEntityManager();

        $bitcoinAddress = $em->getRepository('BBDurianBundle:BitcoinAddress')->findOneBy(['userId' => $userId]);

        return $bitcoinAddress;
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
