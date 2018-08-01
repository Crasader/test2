<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class BitcoinController extends Controller
{
    /**
     * 取得比特幣匯率
     *
     * @Route("/user/{userId}/bitcoin_rate",
     *        name = "api_get_user_bitcoin_rate",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getBitcoinRateAction($userId)
    {
        $currencyOperator = $this->get('durian.currency');
        $blockChain = $this->get('durian.block_chain');
        $depositOperator = $this->container->get('durian.deposit_operator');
        $em = $this->getEntityManager();

        $user = $this->findUser($userId);
        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 150910002);
        }
        $levelId = $userLevel->getLevelId();
        $paymentCharge = $depositOperator->getPaymentCharge($user, 1, $levelId);
        $paymentChargeId = $paymentCharge->getId();
        $paymentWithdrawFee = $em->getRepository('BBDurianBundle:PaymentWithdrawFee')
            ->findOneBy(['paymentCharge' => $paymentChargeId]);

        if (!$paymentWithdrawFee) {
            throw new \RuntimeException('No PaymentWithdrawFee found', 150910003);
        }

        $currency = $user->getCurrency();

        if ($user->getCash()) {
            $currency = $user->getCash()->getCurrency();
        }
        $currencyCode = $currencyOperator->getMappedCode($currency);
        $btc = number_format($blockChain->getExchange($currencyCode), 8);

        $depositBitcoin = $paymentCharge->getDepositBitcoin();
        $depositFeePercent = $depositBitcoin->getBitcoinFeePercent();
        $depositFee = $depositFeePercent / 100;
        // 小數位數後8碼容易出現浮點數運算的問題，改用bcmach
        $depositRateDifference = bcmul($btc, $depositFee, 8);
        $depositTotalRate = bcadd($btc, $depositRateDifference, 8);

        $withdrawFeePercent = $paymentWithdrawFee->getBitcoinAmountPercent();
        $withdrawFee = $withdrawFeePercent / 100;
        // 小數位數後8碼容易出現浮點數運算的問題，改用bcmach
        $withdrawRateDifference = bcmul($btc, $withdrawFee, 8);
        $withdrawTotalRate = bcsub($btc, $withdrawRateDifference, 8);

        $output = [
            'result' => 'ok',
            'ret' => [
                'deposit_bitcoin_rate' => $btc,
                'deposit_rate_difference' => $depositRateDifference,
                'deposit_total_rate' => $depositTotalRate,
                'withdraw_bitcoin_rate' => $btc,
                'withdraw_rate_difference' => $withdrawRateDifference,
                'withdraw_total_rate' => $withdrawTotalRate,
            ],
        ];

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
            throw new \RuntimeException('No such user', 150910001);
        }

        return $user;
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
