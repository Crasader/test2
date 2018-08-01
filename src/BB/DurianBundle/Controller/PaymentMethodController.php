<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;

class PaymentMethodController extends Controller
{
    /**
     * 取得全部的付款方式
     *
     * @Route("/payment_method",
     *        name = "api_payment_method_get_all",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getAllAction()
    {
        $em = $this->getEntityManager();
        $ret = array();

        $pms = $em->getRepository('BBDurianBundle:PaymentMethod')->findAll();
        foreach ($pms as $paymentMethod) {
            $ret[] = $paymentMethod->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 取得付款方式
     *
     * @Route("/payment_method/{paymentMethodId}",
     *        name = "api_payment_method_get",
     *        requirements = {"paymentMethodId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentMethodId
     * @return JsonResponse
     */
    public function getAction($paymentMethodId)
    {
        $paymentMethod = $this->getPaymentMethod($paymentMethodId);

        $output['result'] = 'ok';
        $output['ret'] = $paymentMethod->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得付款廠商
     *
     * @Route("/payment_vendor/{paymentVendorId}",
     *        name = "api_payment_vendor_get",
     *        requirements = {"paymentVendorId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentVendorId
     * @return JsonResponse
     */
    public function getPaymentVendorAction($paymentVendorId)
    {
        $paymentVendor = $this->getPaymentVendor($paymentVendorId);

        $output['result'] = 'ok';
        $output['ret'] = $paymentVendor->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得付款方式支援的付款廠商
     *
     * @Route("/payment_method/{paymentMethodId}/payment_vendor",
     *        name = "api_payment_vendor_get_by_payment_method",
     *        requirements = {"paymentMethodId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentMethodId
     * @return JsonResponse
     */
    public function getPaymentVendorByPaymentMethodAction($paymentMethodId)
    {
        $paymentMethod = $this->getPaymentMethod($paymentMethodId);

        $ret = array();
        foreach ($paymentMethod->getVendors() as $vendor) {
            $ret[] = $vendor->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 取得付款方式
     *
     * @param integer $id 指定的ID
     * @return PaymentMethod
     * @throws \RuntimeException
     */
    private function getPaymentMethod($id)
    {
        $em = $this->getEntityManager();
        $paymentMethod = $em->find('BBDurianBundle:PaymentMethod', $id);

        if (!$paymentMethod) {
            throw new \RuntimeException('No PaymentMethod found', 540001);
        }

        return $paymentMethod;
    }

    /**
     * 取得付款廠商
     *
     * @param integer $id 指定的ID
     * @return PaymentVendor
     * @throws \RuntimeException
     */
    private function getPaymentVendor($id)
    {
        $em = $this->getEntityManager();
        $pv = $em->find('BBDurianBundle:PaymentVendor', $id);

        if (!$pv) {
            throw new \RuntimeException('No PaymentVendor found', 540002);
        }

        return $pv;
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
