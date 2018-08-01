<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\LastLogin;
use BB\DurianBundle\Entity\LoginLog;
use BB\DurianBundle\Entity\LoginLogMobile;
use BB\DurianBundle\Entity\SlideDevice;
use BB\DurianBundle\Entity\SlideBinding;

class SlideController extends Controller
{
    /**
     * 手勢密碼綁定標記redis key名稱
     */
    const BINDING_KEY_NAME = 'binding_token_';

    /**
     * 手勢登入存取標記redis key名稱
     */
    const ACCESS_KEY_NAME = 'access_token_';

    /**
     * 產生手勢密碼綁定標記
     *
     * @Route("/user/{userId}/slide/binding_token",
     *        name = "api_generate_binding_token",
     *        requirements = {
     *            "_format" = "json",
     *            "userId": "\d+"
     *        },
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function generateBindingTokenAction($userId)
    {
        $em = $this->getEntityManager();
        $redis = $this->get('snc_redis.slide');

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150790001);
        }

        $ttl = $this->container->getParameter('ttl_binding_token');
        $token = sha1("{$userId}_" . microtime());
        $redis->hmset(self::BINDING_KEY_NAME . $token, ['user_id' => $userId]);
        $redis->expire(self::BINDING_KEY_NAME . $token, $ttl);

        $output['result'] = 'ok';
        $output['ret']['binding_token'] = $token;
        $output['ret']['user_id'] = $userId;

        return new JsonResponse($output);
    }

    /**
     * 帳號綁定手勢登入裝置
     *
     * @Route("/slide/binding",
     *        name = "api_create_binding",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createBindingAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->get('snc_redis.slide');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $appId = $request->get('app_id');
        $slidePassword = $request->get('slide_password');
        $bindingToken = $request->get('binding_token');
        $deviceName = trim($request->get('device_name'));
        $os = trim($request->get('os'));
        $brand = trim($request->get('brand'));
        $model = trim($request->get('model'));

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790003);
        }

        if (!$slidePassword && $slidePassword !== '0') {
            throw new \InvalidArgumentException('No slide_password specified', 150790004);
        }

        if (!$bindingToken) {
            throw new \InvalidArgumentException('No binding_token specified', 150790005);
        }

        // 驗證參數編碼是否為utf8
        $checkParameter = [$appId, $bindingToken, $deviceName, $os, $brand, $model];
        $validator->validateEncode($checkParameter);

        $userId = $redis->hget(self::BINDING_KEY_NAME . $bindingToken, 'user_id');

        if (!$userId) {
            throw new \RuntimeException('The binding token not found', 150790002);
        }

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150790006);
        }

        $ttl = $redis->ttl(self::BINDING_KEY_NAME . $bindingToken);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')->findOneByAppId($appId);
        $slideBinding = null;

        if ($device) {
            $count = $device->countBindings();
            $slideBinding = $emShare->getRepository('BBDurianBundle:SlideBinding')
                ->findOneBy([
                    'userId' => $userId,
                    'device' => $device
                ]);
        }

        $logDevice = $operationLogger->create('slide_device', ['app_id' => $appId]);

        $emShare->beginTransaction();
        try {
            if (!$device) {
                $device = new SlideDevice($appId, password_hash($slidePassword, PASSWORD_BCRYPT));
                $device->setOs($os);
                $device->setBrand($brand);
                $device->setModel($model);
                $emShare->persist($device);
                $emShare->flush();

                $logDevice->addMessage('hash', 'new');
                $logDevice->addMessage('enabled', var_export($device->isEnabled(), true));
                $logDevice->addMessage('err_num', $device->getErrNum());
                $logDevice->addMessage('os', $device->getOs());
                $logDevice->addMessage('brand', $device->getBrand());
                $logDevice->addMessage('model', $device->getModel());
                $operationLogger->save($logDevice);
            } elseif ($count == 0) {
                $device->setOs($os);
                $device->setBrand($brand);
                $device->setModel($model);

                $logDevice->addMessage('os', $device->getOs());
                $logDevice->addMessage('brand', $device->getBrand());
                $logDevice->addMessage('model', $device->getModel());
            }

            $enabled = $device->isEnabled();
            $deviceId = $device->getId();

            if (!$slideBinding) {
                // 密碼重設後若再次綁定的帳號不在裝置綁定帳號清單內，需移除裝置所有綁定帳號
                if (!$enabled) {
                    foreach ($device->getBindings() as $binding) {
                        $emShare->remove($binding);

                        $logRemoved = $operationLogger->create('slide_binding', [
                            'user_id' => $binding->getUserId(),
                            'device_id' => $deviceId
                        ]);
                        $logRemoved->addMessage('id', $binding->getId());
                        $logRemoved->addMessage('name', $binding->getName());
                        $operationLogger->save($logRemoved);
                    }

                    $device->setHash(password_hash($slidePassword, PASSWORD_BCRYPT));
                    $logDevice->addMessage('hash', 'updated');
                }

                if ($enabled && !password_verify($slidePassword, $device->getHash())) {
                    throw new \RuntimeException('A device can only bind to a password', 150790008);
                }

                $slideBinding = new SlideBinding($userId, $device);
                $slideBinding->setBindingToken($bindingToken);
                $slideBinding->setName($deviceName);
                $emShare->persist($slideBinding);
            } else {
                if ($enabled) {
                    throw new \RuntimeException('The device has already been bound', 150790009);
                }

                $slideBinding->setBindingToken($bindingToken);
                $slideBinding->setName($deviceName);
                $device->setHash(password_hash($slidePassword, PASSWORD_BCRYPT));
                $logDevice->addMessage('hash', 'updated');
            }

            $logBinding = $operationLogger->create('slide_binding', [
                'user_id' => $userId,
                'device_id' => $deviceId
            ]);
            $logBinding->addMessage('binding_token', $bindingToken);
            $logBinding->addMessage('name', $deviceName);
            $operationLogger->save($logBinding);

            $device->zeroErrNum();
            $device->enable();

            if (!$enabled) {
                $logDevice->addMessage('enabled', var_export($device->isEnabled(), true));
                $operationLogger->save($logDevice);
            }

            $redis->del(self::BINDING_KEY_NAME . $bindingToken);

            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $emShare->rollback();

            if (!$redis->exists(self::BINDING_KEY_NAME . $bindingToken)) {
                $redis->hmset(self::BINDING_KEY_NAME . $bindingToken, ['user_id' => $userId]);
                $redis->expire(self::BINDING_KEY_NAME . $bindingToken, $ttl);
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret']['user_id'] = $userId;
        $output['ret']['app_id'] = $appId;
        $output['ret']['device_name'] = $deviceName;
        $output['ret']['os'] = $device->getOs();
        $output['ret']['brand'] = $device->getBrand();
        $output['ret']['model'] = $device->getModel();

        return new JsonResponse($output);
    }

    /**
     * 移除一筆手勢登入綁定
     *
     * @Route("/slide/binding",
     *        name = "api_remove_binding",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeBindingAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $request = $request->request;

        $userId = $request->get('user_id');
        $appId = $request->get('app_id');

        if (!$userId) {
            throw new \InvalidArgumentException('No user_id specified', 150790010);
        }

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790011);
        }

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150790044);
        }

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId($userId, $appId);

        if (!$binding) {
            throw new \RuntimeException('The device has not been bound', 150790012);
        }

        $logRemoved = $operationLogger->create('slide_binding', [
            'user_id' => $userId,
            'device_id' => $binding->getDevice()->getId()
        ]);
        $logRemoved->addMessage('id', $binding->getId());
        $logRemoved->addMessage('name', $binding->getName());
        $operationLogger->save($logRemoved);

        $device = $binding->getDevice();

        // 如果要移除的手勢登入綁定已經是該裝置最後一筆綁定的帳號了，則停用裝置
        if ($device->countBindings() == 1) {
            $device->disable();

            $logDevice = $operationLogger->create('slide_device', ['app_id' => $appId]);
            $logDevice->addMessage('enabled', var_export($device->isEnabled(), true));
            $operationLogger->save($logDevice);
        }

        $emShare->remove($binding);
        $emShare->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 移除一個裝置上所有綁定的手勢登入
     *
     * @Route("/slide/device/bindings",
     *        name = "api_remove_all_bindings",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeAllBindingsAction(Request $request)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $redis = $this->get('snc_redis.slide');
        $appId = $request->request->get('app_id');

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790013);
        }

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId($appId);

        if (!$device) {
            throw new \RuntimeException('The device has not been bound', 150790014);
        }

        foreach ($device->getBindings() as $binding) {
            $emShare->remove($binding);

            $logRemoved = $operationLogger->create('slide_binding', [
                'user_id' => $binding->getUserId(),
                'device_id' => $device->getId()
            ]);
            $logRemoved->addMessage('id', $binding->getId());
            $logRemoved->addMessage('name', $binding->getName());
            $operationLogger->save($logRemoved);
        }

        $device->disable();

        $logDevice = $operationLogger->create('slide_device', ['app_id' => $appId]);
        $logDevice->addMessage('enabled', var_export($device->isEnabled(), true));
        $operationLogger->save($logDevice);

        $token = $redis->get(self::ACCESS_KEY_NAME . $appId);
        $ttl = $redis->ttl(self::ACCESS_KEY_NAME . $appId);

        try {
            $redis->del(self::ACCESS_KEY_NAME . $appId);
            $emShare->flush();
        } catch (\Exception $e) {
            if (!$redis->exists(self::ACCESS_KEY_NAME . $appId)) {
                $redis->setex(self::ACCESS_KEY_NAME . $appId, $ttl, $token);
            }

            throw $e;
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 驗證裝置產生手勢登入標記
     *
     * @Route("/slide/device/access_token",
     *        name = "api_generate_access_token",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateAccessTokenAction(Request $request)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $redis = $this->get('snc_redis.slide');
        $request = $request->request;

        $appId = $request->get('app_id');
        $slidePassword = $request->get('slide_password');

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790015);
        }

        if (!$slidePassword && $slidePassword !== '0') {
            throw new \InvalidArgumentException('No slide_password specified', 150790016);
        }

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId($appId);

        if (!$device) {
            throw new \RuntimeException('The device has not been bound', 150790017);
        }

        if (!$device->isEnabled()) {
            throw new \RuntimeException('The device is disabled', 150790045);
        }

        $errNumLast = $device->getErrNum();

        if (!password_verify($slidePassword, $device->getHash())) {
            $token = null;
            $device->addErrNum();
        } else {
            $token = sha1("{$appId}_" . microtime());

            if ($device->getErrNum() != 0) {
                $device->zeroErrNum();
            }
        }

        $errNum = $device->getErrNum();

        if ($errNum != $errNumLast) {
            $logDevice = $operationLogger->create('slide_device', ['app_id' => $appId]);

            if ($errNum == 3) {
                $device->disable();
                $logDevice->addMessage('enabled', var_export($device->isEnabled(), true));
            }

            $logDevice->addMessage('err_num', $errNum);
            $operationLogger->save($logDevice);
        }

        try {
            $ttl = $this->container->getParameter('ttl_access_token');

            if ($token) {
                $redis->setex(self::ACCESS_KEY_NAME . $appId, $ttl, $token);
            }

            $emShare->flush();
        } catch (\Exception $e) {
            $redis->del(self::ACCESS_KEY_NAME . $appId);

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret']['access_token'] = $token;
        $output['ret']['err_num'] = $errNum;

        return new JsonResponse($output);
    }

    /**
     * 修改裝置綁定名稱
     *
     * @Route("/slide/binding/name",
     *        name = "api_edit_binding_name",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function editBindingNameAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $userId = $request->get('user_id');
        $appId = $request->get('app_id');
        $name = trim($request->get('device_name'));

        if (!$userId) {
            throw new \InvalidArgumentException('No user_id specified', 150790019);
        }

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790020);
        }

        if (!$name) {
            throw new \InvalidArgumentException('No device_name specified', 150790021);
        }

        // 驗證參數編碼是否為utf8
        $validator->validateEncode($name);

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150790046);
        }

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId($userId, $appId);

        if (!$binding) {
            throw new \RuntimeException('The device has not been bound', 150790022);
        }

        $binding->setName($name);

        $logBinding = $operationLogger->create('slide_binding', [
            'user_id' => $userId,
            'device_id' => $binding->getDevice()->getId()
        ]);
        $logBinding->addMessage('name', $binding->getName());
        $operationLogger->save($logBinding);

        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret']['user_id'] = $userId;
        $output['ret']['app_id'] = $appId;
        $output['ret']['device_name'] = $binding->getName();

        return new JsonResponse($output);
    }

    /**
     * 列出裝置所有綁定使用者
     *
     * @Route("/slide/device/users",
     *        name = "api_get_binding_users_by_device",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listBindingUsersByDeviceAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redisSlide = $this->get('snc_redis.slide');
        $query = $request->query;

        $appId = $query->get('app_id');
        $accessToken = $query->get('access_token');

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790023);
        }

        if (!$accessToken) {
            throw new \InvalidArgumentException('No access_token specified', 150790047);
        }

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId($appId);

        if (!$device) {
            throw new \RuntimeException('The device has not been bound', 150790035);
        }

        if ($accessToken != $redisSlide->get(self::ACCESS_KEY_NAME . $appId)) {
            throw new \RuntimeException('The device has not been verified', 150790048);
        }

        $userIds = [];

        foreach ($device->getBindings() as $binding) {
            $userIds[] = $binding->getUserId();
        }

        $users = $em->getRepository('BBDurianBundle:User')->findById($userIds);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($users as $user) {
            $output['ret'][] = [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'domain' => $user->getDomain()
            ];
        }

        return new JsonResponse($output);
    }

    /**
     * 列出使用者所有綁定裝置
     *
     * @Route("/user/{userId}/slide/device",
     *        name = "api_get_binding_devices_by_user",
     *        requirements = {
     *            "_format" = "json",
     *            "userId": "\d+"
     *        },
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function listBindingDevicesByUserAction($userId)
    {
        $emShare = $this->getEntityManager('share');

        $devicesArray = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->getBindingDeviceByUser($userId);

        foreach ($devicesArray as $key => $deviceArray) {
            $devicesArray[$key]['created_at'] = $deviceArray['created_at']
                ->format(\DateTime::ISO8601);
        }

        $output['result'] = 'ok';
        $output['ret'] = $devicesArray;

        return new JsonResponse($output);
    }

    /**
     * 裝置停用手勢登入
     *
     * @Route("/slide/device/disable",
     *        name = "api_disable_device",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function disableDeviceAction(Request $request)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $redis = $this->get('snc_redis.slide');
        $appId = $request->request->get('app_id');

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790024);
        }

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId($appId);

        if (!$device) {
            throw new \RuntimeException('The device has not been bound', 150790025);
        }

        if ($device->isEnabled()) {
            $device->disable();

            $logDevice = $operationLogger->create('slide_device', ['app_id' => $appId]);
            $logDevice->addMessage('enabled', var_export($device->isEnabled(), true));
            $operationLogger->save($logDevice);
        }

        $token = $redis->get(self::ACCESS_KEY_NAME . $appId);
        $ttl = $redis->ttl(self::ACCESS_KEY_NAME . $appId);

        try {
            $redis->del(self::ACCESS_KEY_NAME . $appId);
            $emShare->flush();
        } catch (\Exception $e) {
            if (!$redis->exists(self::ACCESS_KEY_NAME . $appId)) {
                $redis->setex(self::ACCESS_KEY_NAME . $appId, $ttl, $token);
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret']['app_id'] = $appId;
        $output['ret']['enabled'] = false;

        return new JsonResponse($output);
    }

    /**
     * 解凍手勢登入綁定
     *
     * @Route("/slide/binding/unblock",
     *        name = "api_unblock_binding",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unblockBindingAction(Request $request)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $request = $request->request;

        $userId = $request->get('user_id');
        $appId = $request->get('app_id');

        if (!$userId) {
            throw new \InvalidArgumentException('No user_id specified', 150790026);
        }

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790027);
        }

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId($userId, $appId);

        if (!$binding) {
            throw new \RuntimeException('The device has not been bound', 150790028);
        }

        if ($binding->getErrNum() != 0) {
            $binding->zeroErrNum();

            $logBinding = $operationLogger->create('slide_binding', [
                'user_id' => $userId,
                'device_id' => $binding->getDevice()->getId()
            ]);
            $logBinding->addMessage('err_num', $binding->getErrNum());
            $operationLogger->save($logBinding);
        }

        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret']['user_id'] = $userId;
        $output['ret']['app_id'] = $appId;
        $output['ret']['device_name'] = $binding->getName();
        $output['ret']['block'] = false;

        return new JsonResponse($output);
    }

    /**
     * 綁定裝置手勢密碼登入
     *
     * @Route("/slide/login",
     *        name = "api_slide_login",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function slideLoginAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->container->get('durian.operation_logger');
        $redis = $this->get('snc_redis.default');
        $loginValidator = $this->get('durian.login_validator');
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $ip = trim($request->get('ip'));
        $username = trim($request->get('username'));
        $domain = $request->get('domain');
        $appId = $request->get('app_id');
        $slidePassword = $request->get('slide_password');
        $accessToken = $request->get('access_token');
        $entrance = $request->get('entrance');
        $loginCode = '';
        $language = $request->get('language', 0);
        $host = trim($request->get('host'));
        $ipv6 = trim($request->get('ipv6'));
        $clientOs = $request->get('client_os', 0);
        $clientBrowser = $request->get('client_browser', 0);
        $ingress = $request->get('ingress', 0);
        $deviceName = $request->get('device_name');
        $brand = $request->get('brand');
        $model = $request->get('model');
        $userAgent = trim($request->get('user_agent', ''));
        $duplicateLogin = (bool) $request->get('duplicate_login', 0);
        $xForwardedFor = trim($request->get('x_forwarded_for'));
        $verifyBlacklist = (bool) $request->get('verify_blacklist', 1);
        $lastLoginInterval = $request->get('last_login_interval', 0);
        $verifyParentId = $request->get('verify_parent_id', []);
        $verifyLevel = (bool) $request->get('verify_level', 0);

        if (!$username) {
            throw new \InvalidArgumentException('No username specified', 150790029);
        }

        if (strpos($username, '@')) {
            list($username, $loginCode) = explode('@', $username);
        }

        if (!$ip) {
            throw new \InvalidArgumentException('No ip specified', 150790030);
        }

        if (!$domain && !$loginCode) {
            throw new \InvalidArgumentException('No domain specified', 150790031);
        }

        if ($domain && $loginCode) {
            $checkResult = $loginValidator->checkDomainIdentical($domain, $loginCode);

            if (!$checkResult) {
                throw new \RuntimeException('Domain and LoginCode are not matching', 150790032);
            }
        }

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150790033);
        }

        if (!$slidePassword && $slidePassword !== '0') {
            throw new \InvalidArgumentException('No slide_password specified', 150790034);
        }

        if (!$accessToken) {
            throw new \InvalidArgumentException('No access_token specified', 150790036);
        }

        if (!$loginValidator->checkValidEntrance($entrance)) {
            throw new \InvalidArgumentException('Invalid entrance given', 150790037);
        }

        // 驗證參數編碼是否為utf8
        $checkParameter = [$username, $ipv6, $host, $deviceName, $brand, $model];
        $validator->validateEncode($checkParameter);

        $criteriaBlacklist = ['ip' => $ip];

        // 手機登入不檢查系統封鎖 ip 黑名單
        if ($ingress == 2 || $ingress == 4) {
            $criteriaBlacklist['system_lock'] = false;
        }

        // 若非陣列則強制轉為陣列
        if (!is_array($verifyParentId)) {
            $verifyParentId = [$verifyParentId];
        }

        // 解析 X-FORWARDED-FOR 資訊
        $proxy = $loginValidator->parseXForwardedFor($xForwardedFor);

        // 解析客戶端資訊
        $clientInfo = $loginValidator->parseClientInfo(
            $clientOs,
            $clientBrowser,
            $ingress,
            $language,
            $userAgent
        );

        if ($loginCode && !$domain) {
            $dcRepo = $emShare->getRepository('BBDurianBundle:DomainConfig');
            $config = $dcRepo->findOneBy(['loginCode' => $loginCode]);

            if (!$config) {
                throw new \RuntimeException("No login code found", 150790038);
            }

            $domain = $config->getDomain();
        }

        // 取得IP來源國家與城市
        $ipBlockRepo = $emShare->getRepository('BBDurianBundle:GeoipBlock');
        $verId = $ipBlockRepo->getCurrentVersion();
        $ipBlock = $ipBlockRepo->getBlockByIpAddress($ip, $verId);

        $country = null;
        $city = null;
        if ($ipBlock) {
            $ipCountry = $emShare->find('BBDurianBundle:GeoipCountry', $ipBlock['country_id']);

            $country = $ipCountry->getzhTwName();
            if (empty($country)) {
                $country = $ipCountry->getCountryCode();
            }

            if ($ipBlock['city_id']) {
                $ipCity = $emShare->find('BBDurianBundle:GeoipCity', $ipBlock['city_id']);

                $city = $ipCity->getZhTwName();
                if (empty($city)) {
                    $city = $ipCity->getCityCode();
                }
            }
        }

        $criteria = [
            'domain'   => $domain,
            'username' => $username
        ];

        $userId = 0;
        $userRole = 0;
        $userIsSub = null;
        $test = false;
        $repo = $em->getRepository('BBDurianBundle:User');
        $user = $repo->findOneBy($criteria);

        if ($user) {
            $userId = $user->getId();
            $userRole = $user->getRole();
            $userIsSub = $user->isSub();
            $test = $user->isTest();
        }

        // 預設檢查ip黑名單
        if ($verifyBlacklist) {
            $repo = $emShare->getRepository('BBDurianBundle:Blacklist');
            $blacklist = $repo->getBlacklistSingleBy($criteriaBlacklist, $domain);

            if ($blacklist) {
                $log = new LoginLog($ip, $domain, LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST);
                $log->setUserId($userId);
                $log->setUsername($username);
                $log->setRole($userRole);
                $log->setSub($userIsSub);
                $log->setAt(new \DateTime('now'));
                $log->setHost($host);
                $log->setLanguage($clientInfo['language']);
                $log->setIpv6($ipv6);
                $log->setClientOs($clientInfo['os']);
                $log->setClientBrowser($clientInfo['browser']);
                $log->setIngress($clientInfo['ingress']);
                $log->setProxy1($proxy[0]);
                $log->setProxy2($proxy[1]);
                $log->setProxy3($proxy[2]);
                $log->setProxy4($proxy[3]);
                $log->setCountry($country);
                $log->setCity($city);
                $log->setEntrance($entrance);
                $log->setTest($test);
                $log->setSlide(true);

                $em->persist($log);
                $em->flush();

                $redis->lpush('login_log_queue', json_encode($log->getInfo()));

                $output['ret']['login_user'] = ['id' => $userId];
                $output['ret']['code'] = null;
                $output['ret']['login_result'] = LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST;
                $output['result'] = 'ok';

                return new JsonResponse($output);
            }
        }

        // 檢查domain是否要阻擋登入的封鎖列表;預設為不阻擋
        $config = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        $isBlock = false;
        // domain設定需阻擋登入的封鎖列表,則檢查是否阻擋此ip登入
        if ($config && $config->isBlockLogin()) {
            $blackListRepo = $emShare->getRepository('BBDurianBundle:IpBlacklist');
            $isBlock = $blackListRepo->isBlockLogin($domain, $ip);
        }

        // 若有封鎖列表紀錄且沒有被手動移除,則阻擋下來
        if ($isBlock) {
            $log = new LoginLog($ip, $domain, LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST);
            $log->setUserId($userId);
            $log->setUsername($username);
            $log->setRole($userRole);
            $log->setSub($userIsSub);
            $log->setAt(new \DateTime('now'));
            $log->setHost($host);
            $log->setLanguage($clientInfo['language']);
            $log->setIpv6($ipv6);
            $log->setClientOs($clientInfo['os']);
            $log->setClientBrowser($clientInfo['browser']);
            $log->setIngress($clientInfo['ingress']);
            $log->setProxy1($proxy[0]);
            $log->setProxy2($proxy[1]);
            $log->setProxy3($proxy[2]);
            $log->setProxy4($proxy[3]);
            $log->setCountry($country);
            $log->setCity($city);
            $log->setEntrance($entrance);
            $log->setTest($test);
            $log->setSlide(true);

            $em->persist($log);

            $em->beginTransaction();
            $redis->multi();
            try {
                $em->flush();

                if ($ingress == 2 || $ingress == 4) {
                    $logMobile = new LoginLogMobile($log);
                    $logMobile->setName($deviceName);
                    $logMobile->setBrand($brand);
                    $logMobile->setModel($model);

                    $em->persist($logMobile);
                    $em->flush();
                    $redis->lpush('login_log_mobile_queue', json_encode($logMobile->getInfo()));
                }

                $em->commit();
                $redis->lpush('login_log_queue', json_encode($log->getInfo()));
                $redis->exec();
            } catch (\Exception $e) {
                $redis->discard();
                $em->rollback();

                throw $e;
            }

            $output['ret']['login_user'] = ['id' => $userId];
            $output['ret']['code'] = null;
            $output['ret']['login_result'] = LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST;
            $output['result'] = 'ok';

            return new JsonResponse($output);
        }

        $data = [
            'app_id' => $appId,
            'slide_password' => $slidePassword,
            'access_token' => $accessToken,
            'host' => $host,
            'entrance' => $entrance,
            'last_login_interval' => $lastLoginInterval,
            'verify_parent_id' => $verifyParentId,
            'verify_level' => $verifyLevel,
            'ignore_verify_otp' => false
        ];

        $result = $loginValidator->getLoginResult($user, $data);

        // 判斷是否需要回傳導向網址
        $isRedirect = false;

        // 登入結果為登入成功需導向網址，需調整結果為登入成功
        // RESULR_SUCCESS_AND_REDIRECT 僅用來判斷需不需要回傳導向網址
        if ($result == LoginLog::RESULT_SUCCESS_AND_REDIRECT) {
            $result = LoginLog::RESULT_SUCCESS;
            $isRedirect = true;
        }

        $log = null;
        $logMobile = null;
        $output['ret']['login_user'] = [];
        $output['ret']['code'] = null;

        // 根據login result做對應動作
        if (null != $user) {
            $log = new LoginLog($ip, $domain, $result);
            $log->setUserId($userId);
            $log->setUsername($user->getUsername());
            $log->setRole($user->getRole());
            $log->setSub($user->isSub());
            $log->setAt(new \DateTime('now'));
            $log->setHost($host);
            $log->setLanguage($clientInfo['language']);
            $log->setIpv6($ipv6);
            $log->setClientOs($clientInfo['os']);
            $log->setClientBrowser($clientInfo['browser']);
            $log->setIngress($clientInfo['ingress']);
            $log->setProxy1($proxy[0]);
            $log->setProxy2($proxy[1]);
            $log->setProxy3($proxy[2]);
            $log->setProxy4($proxy[3]);
            $log->setCountry($country);
            $log->setCity($city);
            $log->setEntrance($entrance);
            $log->setTest($user->isTest());
            $log->setSlide(true);

            $em->persist($log);

            // 若登入成功, 則更新使用者登入時間，並產生一段加密的編碼
            if ($result == LoginLog::RESULT_SUCCESS) {
                $output = $loginValidator->loginSuccess($user, $log, $duplicateLogin, $isRedirect, $appId);
                $lastLogin = $em->find('BBDurianBundle:LastLogin', $userId);
                $log->setSessionId($output['ret']['login_user']['session_id']);
            }

            $output['ret']['login_user']['id'] = $userId;

            // 若手勢密碼錯誤或被凍結，須回傳手勢密碼錯誤次數
            if ($result == LoginLog::RESULT_SLIDEPASSWORD_WRONG
                || $result == LoginLog::RESULT_SLIDEPASSWORD_WRONG_AND_BLOCK
                || $result == LoginLog::RESULT_SLIDEPASSWORD_BLOCKED
            ) {
                $output['ret']['login_user']['err_num'] = $emShare->getRepository('BBDurianBundle:SlideBinding')
                    ->findOneByUserAndAppId($userId, $appId)->getErrNum();
            }
        }

        $emShare->beginTransaction();
        $em->beginTransaction();
        try {
            $em->flush();

            if ($log && in_array($ingress, [2, 4])) {
                $logMobile = new LoginLogMobile($log);
                $logMobile->setName($deviceName);
                $logMobile->setBrand($brand);
                $logMobile->setModel($model);

                $em->persist($logMobile);
            }

            // 為避免 deadlock，最後登入 ip & id 需同時更新
            if ($result == LoginLog::RESULT_SUCCESS) {
                if (!$lastLogin) {
                    $lastLogin = new LastLogin($userId, $ip);
                    $em->persist($lastLogin);
                }

                $lastLogin->setIp($ip);
                $lastLogin->setLoginLogId($log->getId());
            }

            $emShare->flush();
            $em->flush();
            $emShare->commit();
            $em->commit();

            if ($log) {
                $redis->lpush('login_log_queue', json_encode($log->getInfo()));
            }

            if ($logMobile) {
                $redis->lpush('login_log_mobile_queue', json_encode($logMobile->getInfo()));
            }
        } catch (\Exception $e) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }

            if ($emShare->getConnection()->isTransactionActive()) {
                $emShare->rollback();
            }

            // 如果出錯，必須將 Session 資料刪除
            if (isset($output['ret']['login_user']['session_id'])) {
                $sessionBroker = $this->get('durian.session_broker');
                $sessionBroker->remove($output['ret']['login_user']['session_id']);
            }

            //DBALException內部BUG,並判斷是否為Duplicate entry 跟 deadlock
            if (!is_null($e->getPrevious())) {
                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 1062) {
                    $pdoMsg = $e->getMessage();

                    // 隱藏阻擋同分秒同廳同IP登入錯誤的狀況
                    if (strpos($pdoMsg, 'uni_login_error_ip_at_domain') && strpos($pdoMsg, 'login_error_per_ip')) {
                        $output['code'] = 150790041;
                        $output['result'] = 'error';
                        $output['msg'] = $this->get('translator')->trans('Database is busy');

                        return new JsonResponse($output);
                    }

                    /**
                     * 隱藏阻擋同分秒加入黑名單的狀況，
                     * 改以不同error code區別 Database is busy錯誤訊息狀況
                     */
                    if (strpos($pdoMsg, 'uni_blacklist_domain_ip')) {
                        throw new \RuntimeException('Database is busy', 150790042);
                    }

                    /**
                     * 隱藏阻擋同分秒加入封鎖列表的狀況，
                     * 改以不同error code區別 Database is busy錯誤訊息狀況
                     */
                    if (strpos($pdoMsg, 'uni_ip_blacklist_domain_ip_created_date')) {
                        throw new \RuntimeException('Database is busy', 150790007);
                    }

                    if (strpos($pdoMsg, 'last_login')) {
                        throw new \RuntimeException('Database is busy', 150790043);
                    }
                }
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret']['login_result'] = $result;

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
