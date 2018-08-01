<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Exchange;
use BB\DurianBundle\Entity\ExchangeRecord;
use BB\DurianBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class ExchangeController extends Controller
{
    /**
     * 新增匯率
     *
     * @Route("/exchange",
     *        name = "api_exchange_create",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $currencyOperator = $this->get('durian.currency');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');

        $request  = $request->request;
        $currency = $request->get('currency');
        $buy      = $request->get('buy');
        $sell     = $request->get('sell');
        $basic    = $request->get('basic');
        $at       = $request->get('active_at');

        $emShare = $this->getEntityManager('share');

        $emShare->beginTransaction();
        try {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 470001);
            }

            if (!$validator->isFloat($buy) || $buy <= 0) {
                throw new \InvalidArgumentException('Illegal buy specified', 470002);
            }

            if (!$validator->isFloat($sell) || $sell <= 0) {
                throw new \InvalidArgumentException('Illegal sell specified', 470006);
            }

            if (!$validator->isFloat($basic) || $basic <= 0) {
                throw new \InvalidArgumentException('Illegal basic specified', 470003);
            }

            if (!$at) {
                throw new \InvalidArgumentException('No active_at specified', 470004);
            }

            // 新增的生效時間不可小於現在時間
            $now      = new \DateTime('now');
            $activeAt = new \DateTime($at, new \DateTimeZone('Asia/Taipei'));

            if ($activeAt < $now) {
                throw new \InvalidArgumentException('Illegal active_at specified', 470007);
            }

            $currencyNum = $currencyOperator->getMappedNum($currency);

            $this->checkExchangeUnique($currencyNum, $activeAt);

            $buy = $this->exchangeform($buy);
            $sell = $this->exchangeform($sell);
            $basic = $this->exchangeform($basic);
            $exchange = new Exchange($currencyNum, $buy, $sell, $basic, $activeAt);

            $emShare->persist($exchange);
            $emShare->flush();

            $log = $operationLogger->create('exchange', ['id' => $exchange->getId()]);
            $log->addMessage('currency', $currency);
            $log->addMessage('basic', $basic);
            $log->addMessage('active_at', $at);
            $log->addMessage('buy', $buy);
            $log->addMessage('sell', $sell);
            $operationLogger->save($log);

            $exchangeRecord = new ExchangeRecord($exchange, 'New');
            $emShare->persist($exchangeRecord);
            $emShare->flush();

            $output['ret'] = $exchange->toArray();
            $output['result'] = 'ok';
            $emShare->commit();
        } catch (\Exception $e) {
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 刪除匯率
     *
     * @Route("/exchange/{exchangeId}",
     *        name = "api_exchange_remove",
     *        requirements = {"exchangeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $exchangeId 匯率ID
     * @return JsonResponse
     */
    public function removeAction($exchangeId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $emShare = $this->getEntityManager('share');

        $emShare->beginTransaction();
        try {
            $exchange = $this->getExchange($exchangeId);
            // 不能刪除現在以前的匯率
            $currency = $exchange->getCurrency();
            $activeAt = new \DateTime('now');

            $nowExchange = $emShare->getRepository('BBDurianBundle:Exchange')
                ->findByCurrencyAt($currency, $activeAt);

            if ($nowExchange) {
                if ($exchange->getActiveAt() <= $nowExchange->getActiveAt()) {
                    throw new \RuntimeException('Can not modified history exchange', 470005);
                }
            }

            $log = $operationLogger->create('exchange', ['id' => $exchangeId]);
            $log->addMessage('id', $exchangeId);
            $operationLogger->save($log);

            $emShare->remove($exchange);

            $exchangeRecord = new ExchangeRecord($exchange, 'Remove');
            $emShare->persist($exchangeRecord);

            $emShare->flush();

            $output['result'] = 'ok';
            $emShare->commit();
        } catch (\Exception $e) {
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 修改匯率
     *
     * @Route("/exchange/{exchangeId}",
     *        name = "api_exchange_edit",
     *        requirements = {"exchangeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $exchangeId 匯率ID
     * @return JsonResponse
     */
    public function editAction(Request $request, $exchangeId)
    {
        $request = $request->request;
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $emShare = $this->getEntityManager('share');

        $emShare->beginTransaction();
        try {
            $exchange = $this->getExchange($exchangeId);
            // 不能修改歷史匯率(現在可以)
            $currency = $exchange->getCurrency();
            $activeAt = new \DateTime('now');

            $nowExchange = $emShare->getRepository('BBDurianBundle:Exchange')
                ->findByCurrencyAt($currency, $activeAt);

            if ($nowExchange) {
                if ($exchange->getActiveAt() < $nowExchange->getActiveAt()) {
                    throw new \RuntimeException('Can not modified history exchange', 470005);
                }
            }

            $buy   = $request->get('buy');
            $sell  = $request->get('sell');
            $basic = $request->get('basic');
            $at    = $request->get('active_at');

            $log = $operationLogger->create('exchange', ['id' => $exchangeId]);

            if ($buy) {
                if ($exchange->getBuy() != $buy) {
                    $originalBuy = $exchange->getBuy();
                    $newBuy = $this->exchangeform($buy);
                    $log->addMessage('buy', $originalBuy, $newBuy);
                }

                if (!$validator->isFloat($buy) || $buy <= 0) {
                    throw new \InvalidArgumentException('Illegal buy specified', 470002);
                }

                $exchange->setBuy($this->exchangeform($buy));
            }

            if ($sell) {
                if ($exchange->getSell() != $sell) {
                    $originalSell = $exchange->getSell();
                    $newSell = $this->exchangeform($sell);
                    $log->addMessage('sell', $originalSell, $newSell);
                }

                if (!$validator->isFloat($sell) || $sell <= 0) {
                    throw new \InvalidArgumentException('Illegal sell specified', 470006);
                }

                $exchange->setSell($this->exchangeform($sell));
            }

            if ($basic) {
                if ($exchange->getBasic() != $basic) {
                    $originalBasic = $exchange->getBasic();
                    $newBasic = $this->exchangeform($basic);
                    $log->addMessage('basic', $originalBasic, $newBasic);
                }

                if (!$validator->isFloat($basic) || $basic <= 0) {
                    throw new \InvalidArgumentException('Illegal basic specified', 470003);
                }

                $exchange->setBasic($this->exchangeform($basic));
            }

            if (trim($at)) {
                $activeAt = new \DateTime($at, new \DateTimeZone('Asia/Taipei'));

                if ($exchange->getActiveAt() != $activeAt) {
                    $originalActiveAt = $exchange->getActiveAt()->format('Y-m-d H:i:s');
                    $newActiveAt = $activeAt->format('Y-m-d H:i:s');
                    $log->addMessage('active_at', $originalActiveAt, $newActiveAt);
                }

                // 修改的生效時間不可小於現行匯率的生效時間
                if ($activeAt < $nowExchange->getActiveAt()) {
                    throw new \InvalidArgumentException('Illegal active_at specified', 470007);
                }

                $this->checkExchangeUnique($currency, $activeAt);

                $exchange->setActiveAt($activeAt);
            }

            if ($log->getMessage()) {
                $operationLogger->save($log);
            }

            $exchangeRecord = new ExchangeRecord($exchange, 'Edit');
            $emShare->persist($exchangeRecord);

            $emShare->flush();

            $output['ret'] = $exchange->toArray();
            $output['result'] = 'ok';
            $emShare->commit();
        } catch (\Exception $e) {
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 回傳匯率
     *
     * @Route("/exchange/{exchangeId}",
     *        name = "api_exchange_get",
     *        requirements = {"exchangeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $exchangeId
     * @return JsonResponse
     */
    public function getAction($exchangeId)
    {
        $exchange = $this->getExchange($exchangeId);

        $output['ret'] = $exchange->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 依照幣別時間回傳匯率
     *
     * @Route("/currency/{currency}/exchange",
     *        name = "api_exchange_get_by_currency",
     *        requirements = {"currency" = "[A-Z]+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $currency
     * @return JsonResponse
     */
    public function getByCurrencyAction(Request $request, $currency)
    {
        $query = $request->query;
        $currencyOperator = $this->get('durian.currency');

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 470001);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);

        $time = $query->get('active_at', 'now');
        $activeAt = new \DateTime($time, new \DateTimeZone('Asia/Taipei'));

        $exchange = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange')
            ->findByCurrencyAt($currencyNum, $activeAt);

        $data = array();
        if ($exchange) {
            $data = $exchange->toArray();
        }

        $output['ret'] = $data;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳匯率列表
     *
     * @Route("/currency/{currency}/exchange/list",
     *        name = "api_currency_exchange_list",
     *        requirements = {"currency" = "[A-Z]+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $currency
     * @return JsonResponse
     */
    public function listAction(Request $request, $currency)
    {
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 470001);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $start = $parameterHandler->datetimeToYmdHis($query->get('start'));
        $end = $parameterHandler->datetimeToYmdHis($query->get('end'));

        $sort = $query->get('sort');
        $order = $query->get('order');

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $repo = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange');
        $total = $repo->countNumOf($currencyNum, $start, $end);
        $exchanges = $repo->getExchangeBy(
            $currencyNum,
            $orderBy,
            $firstResult,
            $maxResults,
            $start,
            $end
        );

        $output = array();
        foreach ($exchanges as $exchange) {
            $output['ret'][] = $exchange->toArray();
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳系統支援幣別
     *
     * @Route("/currency",
     *        name = "api_currency",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrencyAction(Request $request)
    {
        $query = $request->query;
        $isVirtual = $query->get('is_virtual');

        $allCur = $this->get('durian.currency')->getAvailable();
        $ret = array();

        foreach ($allCur as $currency) {
            $tmp['currency'] = $currency['code'];
            $tmp['is_virtual'] = $currency['is_virtual'];

            // 有指定參數
            if ($query->has('is_virtual')) {
                // 依照指定的參數放入對應的幣別
                if ($isVirtual == $currency['is_virtual']) {
                    $ret[] = $tmp;
                }
            } else {
                // 沒指定全部回傳
                $ret[] = $tmp;
            }
        }

        $output['ret'] = $ret;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳所有幣別的匯率資訊
     *
     * @Route("/currency/exchange",
     *        name = "api_currency_exchange",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getCurrencyExchangeAction()
    {
        $activeAt = new \DateTime('now');
        $repo = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange');

        $allCurrency = $this->get('durian.currency')->getAvailable();
        $ret = array();

        foreach (array_keys($allCurrency) as $num) {
            $exchange = $repo->findByCurrencyAt($num, $activeAt);

            $tmp = [];
            if ($exchange) {
                $tmp = $exchange->toArray();
            }

            $ret[] = $tmp;
        }

        $output['ret'] = $ret;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 匯率兌換
     *
     * @Route("/exchange/convert",
     *        name = "api_exchange_convert",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function convertAction(Request $request)
    {
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        $request = $request->request;
        $amount = $request->get('amount');

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric', 470008);
        }

        $curFrom = $request->get('from');
        $curTo   = $request->get('to');
        $at      = $request->get('active_at', 'now');
        $preview = $request->get('preview');
        // 目前只提供試算功能
        $preview = 1;
        $activeAt = new \DateTime($at, new \DateTimeZone('Asia/Taipei'));

        // 同幣別不可轉匯
        if ($curFrom == $curTo) {
            throw new \RuntimeException('The same currency can not convert', 470009);
        }

        $repo = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange');

        $curFromNum = $currencyOperator->getMappedNum($curFrom);
        $curToNum = $currencyOperator->getMappedNum($curTo);

        $exchangeFrom = $repo->findByCurrencyAt($curFromNum, $activeAt);
        if (!$exchangeFrom) {
            throw new \RuntimeException('No such exchange', 470010);
        }
        $amount = $exchangeFrom->reconvertByBuy($amount);

        $exchangeTo = $repo->findByCurrencyAt($curToNum, $activeAt);
        if (!$exchangeTo) {
            throw new \RuntimeException('No such exchange', 470010);
        }
        $amount = $exchangeTo->convertBySell($amount);

        if (!$preview) {
            // TODO set amount
        }

        $rate = number_format($exchangeFrom->getBuy() / $exchangeTo->getSell(), 8, '.', '');
        $output['ret']['amount'] = $amount;
        $output['ret']['rate'] = $rate;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改匯率(以指定的幣別+生效時間來做修改)
     *
     * @Route("/exchange",
     *        name = "api_exchange_edit_by_currency_active_at",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function editByCurrencyAction(Request $request)
    {
        $request = $request->request;
        $operationLogger = $this->get('durian.operation_logger');
        $emShare = $this->getEntityManager('share');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        $emShare->beginTransaction();
        try {
            $activeAt = new \DateTime($request->get('active_at'));
            $activeAt->setTimezone(new \DateTimeZone('Asia/Taipei'));
            $criteria = array(
                'currency' => $currencyOperator->getMappedNum($request->get('currency')),
                'activeAt' => $activeAt
            );
            $exchange = $emShare->getRepository('BBDurianBundle:Exchange')
                ->findOneBy($criteria);
            if (!$exchange) {
                throw new \RuntimeException('No such exchange', 470010);
            }

            // 只可修改預改匯率(不能修改歷史&現行匯率)
            $currency = $exchange->getCurrency();
            $now = new \DateTime('now');

            $nowExchange = $emShare->getRepository('BBDurianBundle:Exchange')
                ->findByCurrencyAt($currency, $now);

            if ($nowExchange) {
                if ($exchange->getActiveAt() <= $nowExchange->getActiveAt()) {
                    throw new \RuntimeException('Can not modified history exchange', 470005);
                }
            }

            $buy   = $request->get('buy');
            $sell  = $request->get('sell');
            $basic = $request->get('basic');
            $newAt = $request->get('new_active_at');

            $log = $operationLogger->create('exchange', ['id' => $nowExchange->getId()]);

            if ($buy) {
                if ($exchange->getBuy() != $buy) {
                    $originalBuy = $exchange->getBuy();
                    $newBuy = $this->exchangeform($buy);
                    $log->addMessage('buy', $originalBuy, $newBuy);
                }

                if (!$validator->isFloat($buy) || $buy <= 0) {
                    throw new \InvalidArgumentException('Illegal buy specified', 470002);
                }

                $exchange->setBuy($this->exchangeform($buy));
            }

            if ($sell) {
                if ($exchange->getSell() != $sell) {
                    $originalSell = $exchange->getSell();
                    $newSell = $this->exchangeform($sell);
                    $log->addMessage('sell', $originalSell, $newSell);
                }

                if (!$validator->isFloat($sell) || $sell <= 0) {
                    throw new \InvalidArgumentException('Illegal sell specified', 470006);
                }

                $exchange->setSell($this->exchangeform($sell));
            }

            if ($basic) {
                if ($exchange->getBasic() != $basic) {
                    $originalBasic = $exchange->getBasic();
                    $newBasic = $this->exchangeform($basic);
                    $log->addMessage('basic', $originalBasic, $newBasic);
                }

                if (!$validator->isFloat($basic) || $basic <= 0) {
                    throw new \InvalidArgumentException('Illegal basic specified', 470003);
                }

                $exchange->setBasic($this->exchangeform($basic));
            }

            if (trim($newAt)) {
                $newActiveAt = new \DateTime($newAt);
                $newActiveAt->setTimezone(new \DateTimeZone('Asia/Taipei'));

                if ($exchange->getActiveAt() != $newActiveAt) {
                    $originalActiveAt = $exchange->getActiveAt()->format('Y-m-d H:i:s');
                    $formatNewActiveAt = $newActiveAt->format('Y-m-d H:i:s');
                    $log->addMessage('active_at', $originalActiveAt, $formatNewActiveAt);
                }

                // 修改的生效時間不可小於現在
                if ($newActiveAt < $now) {
                    throw new \InvalidArgumentException('Illegal active_at specified', 470007);
                }

                $this->checkExchangeUnique($currency, $newActiveAt);
                $exchange->setActiveAt($newActiveAt);
            }

            if ($log->getMessage()) {
                $operationLogger->save($log);
            }

            $exchangeRecord = new ExchangeRecord($exchange, 'Edit');
            $emShare->persist($exchangeRecord);

            $emShare->flush();

            $output['ret'] = $exchange->toArray();
            $output['result'] = 'ok';
            $emShare->commit();
        } catch (\Exception $e) {
            $emShare->rollback();

            throw $e;
        }

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
     * 取得匯率
     *
     * @param integer $exchangeId 匯率ID
     * @return User
     */
    private function getExchange($exchangeId)
    {
        $exchange = $this->getEntityManager('share')
            ->find('BBDurianBundle:Exchange', $exchangeId);

        if (!$exchange) {
            throw new \RuntimeException('No such exchange', 470010);
        }

        return $exchange;
    }

    /**
     * 檢查匯率是否唯一
     *
     * @param integer  $currency
     * @param \DateTime $activeAt
     */
    private function checkExchangeUnique($currency, $activeAt)
    {
        $criteria = array(
            'currency' => $currency,
            'activeAt' => $activeAt,
        );

        $exchange = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange')
            ->findOneBy($criteria);

        if ($exchange) {
            throw new \RuntimeException('Exchange at this active_at already exists', 470011);
        }
    }

    /**
     * 匯率只取到小數點下六位，之後的無條件捨去
     *
     * @param float $rate
     * @return float
     */
    private function exchangeform($rate)
    {
        $rate = $rate * 1000000;

        // 乘以1000000後若無小數點，取floor會少一，取round是因為float的1不是1!!!
        if (round($rate, 2) != (int) round($rate, 2)) {
            $rate = floor($rate);
        }

        return $rate / 1000000;
    }
}
