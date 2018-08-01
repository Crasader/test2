<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserPassword;
use BB\DurianBundle\Entity\EmailVerifyCode;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends Controller
{
    /**
     * 使用者修改密碼
     *
     * @Route("/user/{userId}/password",
     *        name = "api_auth_set_password",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     *
     * @author Linda 2014.11.25
     */
    public function setPasswordAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $userValidator = $this->get('durian.user_validator');

        $request = $request->request;
        $oldPassword = $request->get('old_password');
        $newPassword = $request->get('new_password');
        $confirmPassword = $request->get('confirm_password');
        $expireAt = $request->get('password_expire_at');
        $passwordReset = $request->get('password_reset', 0);
        $verify = $request->get('verify', 1);

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        if (!$oldPassword && $verify) {
            throw new\InvalidArgumentException('No old_password specified', 150390001);
        }

        if (!$newPassword) {
            throw new \InvalidArgumentException('No new_password specified', 150390002);
        }

        if (!$confirmPassword) {
            throw new \InvalidArgumentException('No confirm_password specified', 150390003);
        }

        if (!$expireAt) {
            throw new \InvalidArgumentException('No password_expire_at specified', 150390004);
        }

        if ($newPassword !== $confirmPassword) {
            throw new \RunTimeException('New password and confirm password are different', 150390007);
        }

        $userValidator->validatePassword($newPassword);

        $user = $this->findUser($userId);
        $result = $sensitiveLogger->validateAllowedOperator($user);

        if (!$result['result']) {
            throw new \RuntimeException($result['msg'], $result['code']);
        }

        $sensitiveLogger->getAnalyzedSensitiveData();

        $userPasswordLog = $operationLogger->create('user_password', ['user_id' => $userId]);

        $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);

        // 已停用密碼的使用者無法修改密碼
        if ($userPassword->getHash() == '') {
            throw new \RuntimeException('DisabledPassword user cannot change password', 150390015);
        }

        if ($verify) {
            if (!password_verify($oldPassword, $userPassword->getHash())) {
                throw new \RunTimeException('Old password is not correct', 150390005);
            }

            if (password_verify($newPassword, $userPassword->getHash())) {
                throw new \RunTimeException('New password cannot be the same as old password',150390006);
            }
        }

        $userPasswordLog->addMessage('password', 'updated');
        $operationLogger->save($userPasswordLog);

        $now = new \DateTime('now');
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        $userPassword->setHash($hash)
            ->setExpireAt(new \DateTime($expireAt))
            ->setModifiedAt($now)
            ->setReset($passwordReset);

        $user->setPassword($newPassword)
            ->setPasswordExpireAt(new \DateTime($expireAt))
            ->setPasswordReset($passwordReset);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 產生使用者email認證碼
     *
     * @Route("/user/{userId}/email_verify_code",
     *        name = "api_auth_generate_email_verify_code",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param integer $userId 使用者id
     * @return JsonResponse
     *
     * @author Ruby 2015.03.26
     */
    public function generateVerifyCodeAction($userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $this->findUser($userId);
        $userEmail = $em->find('BBDurianBundle:UserEmail', $userId);

        if (!$userEmail->getEmail()) {
            throw new \RuntimeException('Email can not be null', 150390016);
        }

        if ($userEmail->isConfirm()) {
            throw new \RuntimeException('This email has been confirmed', 150390010);
        }

        $emailAddress = $userEmail->getEmail();
        $at = new \DateTime('now');
        $code = $emailAddress . $at->format('YmdHis');
        $encodeKey = hash('sha256', $code);

        //設定認證時效為24小時
        $at->add(new \DateInterval('PT24H'));

        $emailVerifyCode = $em->find('BBDurianBundle:EmailVerifyCode', $userId);

        if ($emailVerifyCode) {
            $emailVerifyCode->setCode($encodeKey);
            $emailVerifyCode->setExpireAt($at);
        } else {
            $emailVerifyCode = new EmailVerifyCode($userId, $encodeKey, $at);
            $em->persist($emailVerifyCode);
        }

        $log = $operationLogger->create('email_verify_code', ['user_id' => $userId]);
        $log->addMessage('code', 'generated');
        $log->addMessage('expire_at', $at->format('YmdHis'));
        $operationLogger->save($log);

        try {
            $em->flush();
            $emShare->flush();
        } catch (\Exception $e) {
            if ($e->getPrevious()->getCode() == 23000) {
                throw new \RuntimeException('Database is busy', 150390011);
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['code'] = $encodeKey;

        return new JsonResponse($output);
    }

    /**
     * 使用者email認證
     *
     * @Route("/user/email/verify",
     *        name = "api_auth_email_verify",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Ruby 2015.03.26
     */
    public function verifyEmailAction(Request $request)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $request = $request->request;
        $verifyCode = $request->get('code');

        if(!$verifyCode) {
            throw new \InvalidArgumentException('No verify code specified', 150390012);
        }

        $emailVerifyCode = $em->getRepository('BBDurianBundle:EmailVerifyCode')->getByCode($verifyCode);

        if (!$emailVerifyCode) {
            throw new \RuntimeException('No emailVerifyCode found', 150390013);
        }

        $userId = $emailVerifyCode[0]->getUserId();
        $this->findUser($userId);
        $userEmail = $em->find('BBDurianBundle:UserEmail', $userId);

        if ($userEmail->isConfirm()) {
            throw new \RuntimeException('This email has been confirmed', 150390010);
        }

        $emailAddress = $userEmail->getEmail();
        $encodeAt = $emailVerifyCode[0]->getExpireAt()->sub(new \DateInterval('PT24H'));
        $codeCheck = $emailAddress . $encodeAt->format('YmdHis');
        $codeCheck = hash('sha256', $codeCheck);

        //檢查認證碼是否與使用者信箱配對相同
        if ($verifyCode != $codeCheck) {
            throw new \RuntimeException('Failed to verify email', 150390014);
        }

        $at = new \DateTime('now');
        $log = $operationLogger->create('user_email', ['user_id' => $userId]);
        $log->addMessage('confirm', 'false', 'true');
        $log->addMessage('confirm_at', 'null', $at->format('Y-m-d H:i:s'));
        $operationLogger->save($log);

        $userEmail->setConfirmAt($at)
            ->setConfirm(true);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 新增臨時密碼
     *
     * @Route("/user/{userId}/once_password",
     *        name = "api_create_once_password",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者編號
     * @return JsonResponse
     */
    public function createOncePasswordAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $italkingOperator = $this->get('durian.italking_operator');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $request = $request->request;
        $operator = $request->get('operator');

        if (!$operator) {
            throw new \InvalidArgumentException('No operator specified', 150390017);
        }

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($operator);

        $user = $this->findUser($userId);
        $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);

        $oncePassword = openssl_random_pseudo_bytes(4);
        $oncePassword = bin2hex($oncePassword);
        $hash = password_hash($oncePassword, PASSWORD_BCRYPT);
        $now = new \DateTime();
        $at = clone $now;
        $at->add(new \DateInterval('PT30M'));

        $log = $operationLogger->create('user_password', ['user_id' => $userId]);
        $log->addMessage('once_password', 'created');
        $log->addMessage('used', 'false');
        $log->addMessage('once_expire_at', $at->format('Y-m-d H:i:s'));
        $operationLogger->save($log);

        $userPassword->setOncePassword($hash);
        $userPassword->setUsed(false);
        $userPassword->setOnceExpireAt($at);
        $em->flush();
        $emShare->flush();

        $domain = $emShare->find('BBDurianBundle:DomainConfig', $user->getDomain());

        $msg = sprintf(
            "廳名: %s，使用者帳號: %s 已產生臨時密碼，操作者為: %s，建立時間: %s",
            $domain->getName(),
            $user->getUsername(),
            $operator,
            $now->format('Y-m-d H:i:s')
        );

        $italkingOperator->pushMessageToQueue('acc_system', $msg);

        $output['result'] = 'ok';
        $output['code'] = $oncePassword;

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
            throw new \RuntimeException('No such user', 150390008);
        }

        return $user;
    }
}
