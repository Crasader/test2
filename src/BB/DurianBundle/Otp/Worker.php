<?php

namespace BB\DurianBundle\Otp;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\User;
use Jenssegers\Agent\Agent;
use Dapphp\Radius\Radius;

class Worker extends ContainerAware
{
    /**
     * @var Dapphp\Radius\Radius
     */
    private $radius;

    /**
     * @param Dapphp\Radius\Radius $radius
     */
    public function setRadius($radius)
    {
        $this->radius = $radius;
    }

    /**
     * 取得 otp 驗證結果
     *
     * @param string $otpUser otp 使用者名稱
     * @param string $token token
     * @param integer $userId 使用者編號
     * @param integer $domain 廳別
     * @return array
     */
    public function getOtpResult($otpUser, $token, $userId, $domain)
    {
        $ip = $this->container->getParameter('otp_server_ip');
        $secret = $this->container->getParameter('otp_secret');
        $logger = $this->container->get('durian.logger_manager')->setUpLogger('domain_radius.log');

        $client = new Radius();

        if ($this->radius) {
            $client = $this->radius;
        }

        $client->setServer($ip)
            ->setSecret($secret)
            ->setNasIpAddress($_SERVER["SERVER_ADDR"]);

        $result['response'] = $client->accessRequest($otpUser, $token);
        $result['responseStatus'] = var_export($result['response'], true);
        $result['error'] = $client->getLastError();

        $message = "User: $userId, domain: $domain, " .
            "response: {$result['responseStatus']}, error: {$result['error']}";

        $logger->addInfo($message);
        $logger->popHandler()->close();

        return $result;
    }
}
