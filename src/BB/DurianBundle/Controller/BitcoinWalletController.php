<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\BitcoinWallet;

class BitcoinWalletController extends Controller
{
    /**
     * 新增比特幣錢包
     *
     * @Route("/domain/{domain}/bitcoin_wallet",
     *        name = "api_create_domain_bitcoin_wallet",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request, $domain)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $blockChain = $this->get('durian.block_chain');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $post = $request->request;
        $walletCode = trim($post->get('wallet_code'));
        $password = $post->get('password');
        $secondPassword = $post->get('second_password');
        $apiCode = trim($post->get('api_code'));
        $xpub = trim($post->get('xpub'));
        $feePerByte = trim($post->get('fee_per_byte', 0));

        if (!$walletCode) {
            throw new \InvalidArgumentException('No wallet_code specified', 150890001);
        }

        if (!$password) {
            throw new \InvalidArgumentException('No password specified', 150890002);
        }

        if (!$apiCode) {
            throw new \InvalidArgumentException('No api_code specified', 150890003);
        }

        $param = [$walletCode, $password, $secondPassword, $apiCode, $xpub];
        $validator->validateEncode($param);

        if (!$validator->isInt($feePerByte, true)) {
            throw new \InvalidArgumentException('FeePerByte must be an integer', 150890005);
        }

        $blockChain->validateBitcoinWallet($walletCode, $password);

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $bitcoinWallet = new BitcoinWallet($domain, $walletCode, $password, $apiCode);
            $bitcoinWallet->setFeePerByte($feePerByte);

            if ($secondPassword) {
                $bitcoinWallet->setSecondPassword($secondPassword);
            }

            if ($xpub) {
                $bitcoinWallet->setXpub($xpub);
            }
            $em->persist($bitcoinWallet);
            $em->flush();

            $walletId = $bitcoinWallet->getId();
            $log = $operationLogger->create('bitcoin_wallet', ['id' => $walletId]);
            $log->addMessage('domain', $domain);
            $log->addMessage('wallet_code', $walletCode);
            $log->addMessage('password', 'new');
            $log->addMessage('second_password', 'new');
            $log->addMessage('api_code', $apiCode);
            $log->addMessage('xpub', 'new');
            $log->addMessage('fee_per_byte', $feePerByte);
            $operationLogger->save($log);

            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $bitcoinWallet->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得廳主的比特幣錢包
     *
     * @Route("/domain/{domain}/bitcoin_wallet",
     *        name = "api_get_domain_bitcoin_wallet",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function getWalletByDomainAction($domain)
    {
        $em = $this->getEntityManager();

        $bitcoinWallets = $em->getRepository('BBDurianBundle:BitcoinWallet')->findBy(['domain' => $domain]);

        $output = [
            'result' => 'ok',
            'ret' => [],
        ];

        foreach ($bitcoinWallets as $bitcoinWallet) {
            $output['ret'][] = $bitcoinWallet->toArray();
        }

        return new JsonResponse($output);
    }

    /**
     * 取得比特幣錢包
     *
     * @Route("/bitcoin_wallet/{bitcoinWalletId}",
     *        name = "api_get_bitcoin_wallet",
     *        requirements = {"bitcoinWalletId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $bitcoinWalletId
     * @return JsonResponse
     */
    public function getWalletAction($bitcoinWalletId)
    {
        $bitcoinWallet = $this->getBitcoinWallet($bitcoinWalletId);

        $output = [
            'result' => 'ok',
            'ret' => $bitcoinWallet->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改比特幣錢包
     *
     * @Route("/bitcoin_wallet/{bitcoinWalletId}",
     *        name = "api_edit_bitcoin_wallet",
     *        requirements = {"bitcoinWalletId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $bitcoinWalletId
     * @return JsonResponse
     */
    public function editWalletAction(Request $request, $bitcoinWalletId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $blockChain = $this->get('durian.block_chain');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $walletCode = $request->get('wallet_code');
        $password = $request->get('password');
        $secondPassword = $request->get('second_password');
        $apiCode = $request->get('api_code');
        $xpub = $request->get('xpub');
        $feePerByte = $request->get('fee_per_byte');

        $bitcoinWallet = $this->getBitcoinWallet($bitcoinWalletId);

        $param = [$walletCode, $password, $secondPassword, $apiCode, $xpub];
        $validator->validateEncode($param);

        $log = $operationLogger->create('bitcoin_wallet', ['id' => $bitcoinWalletId]);

        $valid = false;
        if (!is_null($walletCode) && $bitcoinWallet->getWalletCode() != trim($walletCode)) {
            $walletCode = trim($walletCode);
            $originalWalletCode = $bitcoinWallet->getWalletCode();
            $log->addMessage('wallet_code', $originalWalletCode, $walletCode);
            $bitcoinWallet->setWalletCode($walletCode);
            $valid = true;
        }
        if (!is_null($password) && $bitcoinWallet->getPassword() != $password) {
            $bitcoinWallet->setPassword($password);
            $log->addMessage('password', 'update');
            $valid = true;
        }
        if ($valid) {
            $blockChain->validateBitcoinWallet($bitcoinWallet->getWalletCode(), $bitcoinWallet->getPassword());
        }

        if (!is_null($secondPassword)) {

            if (strlen($secondPassword) == 0) {
                $secondPassword = null;
            }
            $originalSecondPassword = $bitcoinWallet->getSecondPassword();

            if ($secondPassword != $originalSecondPassword) {
                $log->addMessage('second_password', 'update');
            }

            $bitcoinWallet->setSecondPassword($secondPassword);
        }

        if (!is_null($apiCode)) {
            $originalApiCode = $bitcoinWallet->getApiCode();

            if ($apiCode != $originalApiCode) {
                $log->addMessage('api_code', $originalApiCode, $apiCode);
            }

            $bitcoinWallet->setApiCode($apiCode);
        }

        if (!is_null($xpub)) {
            $xpub = trim($xpub);

            if (strlen($xpub) == 0) {
                $xpub = null;
            }
            $originalXpub = $bitcoinWallet->getXpub();

            if ($xpub != $originalXpub) {
                $log->addMessage('xpub', 'update');
            }

            $bitcoinWallet->setXpub($xpub);
        }

        if (!is_null($feePerByte)) {
            if (!$validator->isInt($feePerByte, true)) {
                throw new \InvalidArgumentException('FeePerByte must be an integer', 150890005);
            }

            $originalFeePerByte = $bitcoinWallet->getFeePerByte();

            if ($feePerByte != $originalFeePerByte) {
                $log->addMessage('fee_per_byte', $originalFeePerByte, $feePerByte);
            }

            $bitcoinWallet->setFeePerByte($feePerByte);
        }

        $operationLogger->save($log);

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $em->flush();
            $emShare->flush();

            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $bitcoinWallet->toArray(),
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
     * 取得單一比特幣錢包
     *
     * @param integer $walletId
     * @return BitcoinWallet
     */
    private function getBitcoinWallet($walletId)
    {
        $em = $this->getEntityManager();

        $bitcoinWallet = $em->find('BBDurianBundle:BitcoinWallet', $walletId);

        if (!$bitcoinWallet) {
            throw new \RuntimeException('No such bitcoin wallet', 150890004);
        }

        return $bitcoinWallet;
    }
}
