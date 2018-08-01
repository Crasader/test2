<?php
namespace BB\DurianBundle\Exchange;

use Symfony\Component\DependencyInjection\ContainerAware;

class Exchange extends ContainerAware
{
    /**
     * @return Registry
     */
    public function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

    /**
     * 回傳EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 依傳入的幣別進行匯率轉換
     * @param array $creditData
     * @param integer $currency
     * @param \DateTime $creditAt
     * @return array
     */
    public function exchangeCreditByCurrency(array $creditData, $currency, $creditAt = null)
    {
        if (is_null($creditAt)) {
            $creditAt = new \DateTime();
        }

        $exchange = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange')
            ->findByCurrencyAt($currency, $creditAt);

        if (!$exchange) {
            throw new \RuntimeException('No such exchange', 470010);
        }

        $creditData['line'] = (int) floor($exchange->convertByBasic($creditData['line'], $currency));
        $creditData['balance'] = $exchange->convertByBasic($creditData['balance'], $currency);

        return $creditData;
    }
}
