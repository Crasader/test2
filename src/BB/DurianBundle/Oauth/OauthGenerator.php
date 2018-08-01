<?php

namespace BB\DurianBundle\Oauth;

use BB\DurianBundle\Entity\Oauth;

class OauthGenerator
{
    /**
     * 回傳對應的oauth服務
     *
     * @param Oauth $oauth oauth設定
     * @param string $ip 發request時, 對外機器的ip
     *
     * @return AbstractOauthProvider
     */
    public function get($oauth, $ip)
    {
        $vendorName = $oauth->getVendor()->getName();
        $className = \Doctrine\Common\Util\Inflector::classify($vendorName);
        $wholeClassName = 'BB\DurianBundle\Oauth\\' . $className;

        return new $wholeClassName(
            $oauth->getAppId(),
            $oauth->getAppKey(),
            $oauth->getRedirectUrl(),
            $oauth->getVendor()->getApiUrl(),
            $ip
        );
    }
}
