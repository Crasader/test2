<?php

namespace BB\DurianBundle\Remit;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\RemitOrder;

/**
 * 負責產生出公司入款訂單號
 */
class OrderNumberGenerator extends ContainerAware
{
    /**
     * 產生訂單號
     *
     * @param \DateTime $date 時間參數
     * @return integer 訂單號
     * @throws \RuntimeException
     */
    public function generate(\DateTime $date)
    {
        $count = 0;
        $orderNumber = 0;
        $doctrine = $this->container->get('doctrine');

        while ($orderNumber == 0) {
            // 防止無窮迴圈
            $count++;
            if ($count > 20) {
                break;
            }

            $em = $doctrine->getManager();
            $remitOrder = new RemitOrder($date);
            $em->persist($remitOrder);

            try {
                $em->flush();
                $orderNumber = $remitOrder->getOrderNumber();
            } catch (\Exception $e) {
                $em->detach($remitOrder);
                unset($remitOrder);

                /**
                 * 原本的manager在第二次while執行時會噴"The EntityManager is closed."
                 * 的錯誤，故這邊需要reset。
                 */
                $doctrine->resetManager();
                continue;
            }
        }

        if ($orderNumber == 0) {
            throw new \RuntimeException('System is busy please try again', 300023);
        }

        return $orderNumber;
    }
}
