<?php
namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class GeoipController extends Controller
{
    /**
     * 取國家資料API
     *
     * @author Thor
     * @Route("/geoip/country/{countryId}",
     *        name = "api_geoip_country",
     *        requirements = {"_format" = "json", "countryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     *
     * @Route("/geoip/country",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "countryId" = null})
     *
     * @Method({"GET"})
     *
     * @param integer $countryId
     * @return JsonResponse
     */
    public function getGeoipCountryAction($countryId)
    {
        $em = $this->getEntityManager('share');
        $ipRep = $em->getRepository('BBDurianBundle:GeoipCountry');

        if (!empty($countryId)) {
            $country = $ipRep->find($countryId);

            if (!$country) {
                throw new \RuntimeException('Cannot find specified country', 150190004);
            }

            $ret = $country->toArray();
        } else {
            $ret = array();
            $countries = $ipRep->findAll();
            foreach ($countries as $country) {
                $ret[] = $country->toArray();
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 取區域資料API
     *
     * @author Thor
     *
     * @Route("/geoip/region/{regionId}",
     *        name = "api_geoip_region",
     *        requirements = {"_format" = "json", "regionId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $regionId
     * @return JsonResponse
     */
    public function getGeoipRegionAction($regionId)
    {
        $em = $this->getEntityManager('share');

        $ipRegion = $em->find('BBDurianBundle:GeoipRegion', $regionId);

        if (!$ipRegion) {
            throw new \RuntimeException('Cannot find specified region', 150190005);
        }

        $output['ret'] = $ipRegion->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 依國家查詢區域資料API
     *
     * @author Thor
     *
     * @Route("/geoip/country/{countryId}/region",
     *        name = "api_geoip_region_list",
     *        requirements = {"_format" = "json", "countryId" = "\d+"},
     *        defaults = {"_format" = "json"})

     * @Method({"GET"})
     *
     * @param integer $countryId
     * @return JsonResponse
     */
    public function getGeoipRegionsByCountryAction($countryId)
    {
        $em = $this->getEntityManager('share');

        $criteria = array();

        $criteria['countryId'] = $countryId;

        $ipRep = $em->getRepository('BBDurianBundle:GeoipRegion');
        $ipRegions = $ipRep->findBy($criteria);

        $output['ret'] = array();
        foreach ($ipRegions as $ipRegion) {
             $output['ret'][] = $ipRegion->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取城市資料API
     *
     * @author Thor
     * @Route("/geoip/city/{cityId}",
     *        name = "api_geoip_city",
     *        requirements = {"_format" = "json", "cityId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $cityId
     * @return JsonResponse
     */
    public function getGeoipCityAction($cityId)
    {
        $em = $this->getEntityManager('share');

        $ipCity = $em->find('BBDurianBundle:GeoipCity', $cityId);

        if (!$ipCity) {
            throw new \RuntimeException('Cannot find specified city', 150190006);
        }

        $output['ret'] = $ipCity->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 依區域查詢所有城市資料
     *
     * @author Thor
     *
     * @Route("/geoip/region/{regionId}/city",
     *        name = "api_geoip_city_list",
     *        requirements = {"_format" = "json", "regionId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $regionId
     * @return JsonResponse
     */
    public function getGeoipCitiesByRegionAction($regionId)
    {
        $em = $this->getEntityManager('share');

        $criteria['regionId'] = $regionId;

        $ipRep = $em->getRepository('BBDurianBundle:GeoipCity');
        $ipCities = $ipRep->findBy($criteria);

        $output['ret'] = array();
        foreach ($ipCities as $ipCity) {
            $output['ret'][] = $ipCity->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 設定國家名稱
     *
     * @author Thor
     * @Route("/geoip/country/{countryId}",
     *        name = "api_geoip_country_set",
     *        requirements = {"_format" = "json", "countryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $countryId
     * @return JsonResponse
     */
    public function setCountryNameAction(Request $request, $countryId)
    {
        $request = $request->request;

        $em = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');

        $enName = trim($request->get('en_name'));
        $zhTwName = trim($request->get('zh_tw_name'));
        $zhCnName = trim($request->get('zh_cn_name'));
        $checkParameter = array($enName, $zhTwName, $zhCnName);

        $validator->validateEncode($checkParameter);

        $ipCountry = $em->find('BBDurianBundle:GeoipCountry', $countryId);

        if (!$ipCountry) {
            throw new \RuntimeException('Cannot find specified country', 150190004);
        }

        if (!empty($enName)) {
            $ipCountry->setEnName($enName);
        }

        if (!empty($zhTwName)) {
            $ipCountry->setZhTwName($zhTwName);
        }

        if (!empty($zhCnName)) {
            $ipCountry->setZhCnName($zhCnName);
        }
        $em->flush();

        $output['ret'] = $ipCountry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }


    /**
     * 設定區域名稱
     *
     * @author Thor
     * @Route("/geoip/region/{regionId}",
     *        name = "api_geoip_region_set",
     *        requirements = {"_format" = "json", "regionId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $regionId
     * @return JsonResponse
     */
    public function setRegionNameAction(Request $request, $regionId)
    {
        $request = $request->request;

        $em = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');

        $enName = trim($request->get('en_name'));
        $zhTwName = trim($request->get('zh_tw_name'));
        $zhCnName = trim($request->get('zh_cn_name'));
        $checkParameter = array($enName, $zhTwName, $zhCnName);

        $validator->validateEncode($checkParameter);

        $ipRegion = $em->find('BBDurianBundle:GeoipRegion', $regionId);

        if (!$ipRegion) {
            throw new \RuntimeException('Cannot find specified region', 150190005);
        }

        if (!empty($enName)) {
            $ipRegion->setEnName($enName);
        }

        if (!empty($zhTwName)) {
            $ipRegion->setZhTwName($zhTwName);
        }

        if (!empty($zhCnName)) {
            $ipRegion->setZhCnName($zhCnName);
        }
        $em->flush();

        $output['ret'] = $ipRegion->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 設定城市名稱
     *
     * @author Thor
     * @Route("/geoip/city/{cityId}",
     *        name = "api_geoip_city_set",
     *        requirements = {"_format" = "json", "cityId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $cityId
     * @return JsonResponse
     */
    public function setCityNameAction(Request $request, $cityId)
    {
        $request = $request->request;

        $em = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');

        $enName = trim($request->get('en_name'));
        $zhTwName = trim($request->get('zh_tw_name'));
        $zhCnName = trim($request->get('zh_cn_name'));
        $checkParameter = array($enName, $zhTwName, $zhCnName);

        $validator->validateEncode($checkParameter);

        $ipCity = $em->find('BBDurianBundle:GeoipCity', $cityId);

        if (!$ipCity) {
            throw new \RuntimeException('Cannot find specified city', 150190006);
        }

        if (!empty($enName)) {
            $ipCity->setEnName($enName);
        }

        if (!empty($zhTwName)) {
            $ipCity->setZhTwName($zhTwName);
        }

        if (!empty($zhCnName)) {
            $ipCity->setZhCnName($zhCnName);
        }
        $em->flush();

        $output['ret'] = $ipCity->toArray();
        $output['result'] = 'ok';

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
}
