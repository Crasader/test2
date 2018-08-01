<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Captcha\Genie;

class CaptchaController extends Controller
{
    /**
     * 建立Captcha
     *
     * @Route("/user/{userId}/captcha",
     *      name = "api_captcha_create",
     *      requirements = {"userId" = "\d+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId 使用者ID
     * @return JsonResponse
     */
    public function createAction(Request $request, $userId)
    {
        $em = $this->getDoctrine()->getManager();
        $captchaGenie = $this->get('durian.captcha_genie');
        $validator = $this->get('durian.validator');

        $post = $request->request;
        $identifier = $post->get('identifier');
        $length = $post->get('length');
        // 固定參數，有需要再開放。
        $format = Genie::FORMAT_ALPHANUMERIC;
        $ttl = 300;

        // 檢查identifier為必填
        if (!$post->has('identifier')) {
            throw new \InvalidArgumentException('No identifier specified', 150400002);
        }

        // 檢查identifier是否為正整數
        if (!$validator->isInt($identifier, true)) {
            throw new \InvalidArgumentException('Invalid identifier', 150400004);
        }

        // 檢查length是否為大於0的正整數
        if (!$validator->isInt($length, true) || $length == 0) {
            throw new \InvalidArgumentException('Invalid length', 150400003);
        }

        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 150400007);
        }

        $setting = [
            'format' => $format,
            'length' => $length,
            'ttl' => $ttl
        ];
        $captcha = $captchaGenie->create($user, $identifier, $setting);

        $response = [
            'result' => 'ok',
            'ret' => $captcha
        ];

        return new JsonResponse($response);
    }

    /**
     * 驗證Captcha
     *
     * @Route("/user/{userId}/captcha/verify",
     *      name = "api_captcha_verify",
     *      requirements = {"userId" = "\d+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function verifyAction(Request $request, $userId)
    {
        $query = $request->query;
        $identifier = $query->get('identifier');
        $captchaCheck = $query->get('captcha');
        $captchaGenie = $this->get('durian.captcha_genie');

        // 檢查identifier為必填
        if (!$query->has('identifier')) {
            throw new \InvalidArgumentException('No identifier specified', 150400002);
        }

        // 檢查identifier是否為正整數
        if (!$this->get('durian.validator')->isInt($identifier, true)) {
            throw new \InvalidArgumentException('Invalid identifier', 150400004);
        }

        $captcha = $captchaGenie->get($userId, $identifier);
        if (!$captcha) {
            throw new \RuntimeException('Captcha not exists', 150400005);
        }

        // 驗證不一樣噴錯
        if ($captcha != $captchaCheck) {
            throw new \RuntimeException('Verify failed', 150400006);
        }

        // 驗證一樣後刪除
        $captchaGenie->remove($userId, $identifier);

        $response = [
            'result' => 'ok',
            'ret' => true
        ];

        return new JsonResponse($response);
    }
}
