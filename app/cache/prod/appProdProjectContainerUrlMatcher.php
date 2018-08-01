<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * appProdProjectContainerUrlMatcher.
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class appProdProjectContainerUrlMatcher extends Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($pathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($pathinfo);
        $context = $this->context;
        $request = $this->request;

        // fos_js_routing_js
        if (0 === strpos($pathinfo, '/js/routing') && preg_match('#^/js/routing(?:\\.(?P<_format>js|json))?$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'fos_js_routing_js')), array (  '_controller' => 'fos_js_routing.controller:indexAction',  '_format' => 'js',));
        }

        if (0 === strpos($pathinfo, '/api')) {
            if (0 === strpos($pathinfo, '/api/account_log')) {
                // api_account_log_list
                if ($pathinfo === '/api/account_log/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_account_log_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AccountLogController::accountLogListAction',  '_route' => 'api_account_log_list',);
                }
                not_api_account_log_list:

                // api_account_log_zero
                if (preg_match('#^/api/account_log/(?P<id>\\d+)/zero$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_account_log_zero;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_account_log_zero')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AccountLogController::zeroAccCountAction',));
                }
                not_api_account_log_zero:

                // api_account_log_status
                if (preg_match('#^/api/account_log/(?P<id>\\d+)/status$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_account_log_status;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_account_log_status')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AccountLogController::setStatusAction',));
                }
                not_api_account_log_status:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_auth_set_password
                if (preg_match('#^/api/user/(?P<userId>\\d+)/password$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_auth_set_password;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_auth_set_password')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AuthController::setPasswordAction',));
                }
                not_api_auth_set_password:

                // api_auth_generate_email_verify_code
                if (preg_match('#^/api/user/(?P<userId>\\d+)/email_verify_code$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_auth_generate_email_verify_code;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_auth_generate_email_verify_code')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AuthController::generateVerifyCodeAction',));
                }
                not_api_auth_generate_email_verify_code:

                // api_auth_email_verify
                if ($pathinfo === '/api/user/email/verify') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_auth_email_verify;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AuthController::verifyEmailAction',  '_route' => 'api_auth_email_verify',);
                }
                not_api_auth_email_verify:

                // api_create_once_password
                if (preg_match('#^/api/user/(?P<userId>\\d+)/once_password$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_create_once_password;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_once_password')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AuthController::createOncePasswordAction',));
                }
                not_api_create_once_password:

            }

            // api_auto_confirm_check_status
            if ($pathinfo === '/api/auto_confirm/check_status') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_auto_confirm_check_status;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoConfirmController::checkStatusAction',  '_route' => 'api_auto_confirm_check_status',);
            }
            not_api_auto_confirm_check_status:

            if (0 === strpos($pathinfo, '/api/remit_account')) {
                // api_remit_account_lock_password_error
                if (preg_match('#^/api/remit_account/(?P<account>\\d+)/lock/password_error$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_remit_account_lock_password_error;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_lock_password_error')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoConfirmController::lockPasswordErrorAction',));
                }
                not_api_remit_account_lock_password_error:

                // api_create_remit_account_auto_confirm_entry
                if (preg_match('#^/api/remit_account/(?P<account>\\d+)/auto_confirm_entry$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_remit_account_auto_confirm_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_remit_account_auto_confirm_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoConfirmController::createAction',));
                }
                not_api_create_remit_account_auto_confirm_entry:

                // api_create_remit_account_single_auto_confirm_entry
                if (preg_match('#^/api/remit_account/(?P<account>\\d+)/single_auto_confirm_entry$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_remit_account_single_auto_confirm_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_remit_account_single_auto_confirm_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoConfirmController::createSingleAction',));
                }
                not_api_create_remit_account_single_auto_confirm_entry:

            }

            if (0 === strpos($pathinfo, '/api/auto_')) {
                if (0 === strpos($pathinfo, '/api/auto_confirm')) {
                    if (0 === strpos($pathinfo, '/api/auto_confirm/entry')) {
                        // api_auto_confirm_entry_list
                        if ($pathinfo === '/api/auto_confirm/entry/list') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_auto_confirm_entry_list;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoConfirmController::listEntryAction',  '_route' => 'api_auto_confirm_entry_list',);
                        }
                        not_api_auto_confirm_entry_list:

                        // api_get_auto_confirm_entry
                        if (preg_match('#^/api/auto_confirm/entry/(?P<autoConfirmEntryId>\\d+)$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_get_auto_confirm_entry;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_auto_confirm_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoConfirmController::getEntryAction',));
                        }
                        not_api_get_auto_confirm_entry:

                        // api_set_auto_confirm_entry
                        if (preg_match('#^/api/auto_confirm/entry/(?P<autoConfirmEntryId>\\d+)$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_set_auto_confirm_entry;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_auto_confirm_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoConfirmController::setEntryAction',));
                        }
                        not_api_set_auto_confirm_entry:

                    }

                    // api_manual_match_auto_confirm_entry
                    if (preg_match('#^/api/auto_confirm/(?P<autoConfirmEntryId>\\d+)/manual$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_manual_match_auto_confirm_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_manual_match_auto_confirm_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoConfirmController::manualMatchAction',));
                    }
                    not_api_manual_match_auto_confirm_entry:

                }

                if (0 === strpos($pathinfo, '/api/auto_remit')) {
                    // api_get_auto_remit
                    if (preg_match('#^/api/auto_remit/(?P<autoRemitId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_auto_remit;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_auto_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::getAction',));
                    }
                    not_api_get_auto_remit:

                    // api_get_auto_remit_list
                    if ($pathinfo === '/api/auto_remit/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_auto_remit_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::getListAction',  '_route' => 'api_get_auto_remit_list',);
                    }
                    not_api_get_auto_remit_list:

                    // api_edit_auto_remit
                    if (preg_match('#^/api/auto_remit/(?P<autoRemitId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_edit_auto_remit;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_edit_auto_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::setAction',));
                    }
                    not_api_edit_auto_remit:

                    // api_remove_auto_remit
                    if (preg_match('#^/api/auto_remit/(?P<autoRemitId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_remove_auto_remit;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_auto_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::removeAction',));
                    }
                    not_api_remove_auto_remit:

                    // api_auto_remit_get_bank_info
                    if (preg_match('#^/api/auto_remit/(?P<autoRemitId>\\d+)/bank_info$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_auto_remit_get_bank_info;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_auto_remit_get_bank_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::getBankInfoAction',));
                    }
                    not_api_auto_remit_get_bank_info:

                    // api_auto_remit_set_bank_info
                    if (preg_match('#^/api/auto_remit/(?P<autoRemitId>\\d+)/bank_info$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_auto_remit_set_bank_info;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_auto_remit_set_bank_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::setBankInfoAction',));
                    }
                    not_api_auto_remit_set_bank_info:

                }

            }

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_get_domain_auto_remit
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/auto_remit/(?P<autoRemitId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_domain_auto_remit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_domain_auto_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::getDomainAutoRemitAction',));
                }
                not_api_get_domain_auto_remit:

                // api_get_domain_all_auto_remit
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/auto_remit$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_domain_all_auto_remit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_domain_all_auto_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::getDomainAllAutoRemitAction',));
                }
                not_api_get_domain_all_auto_remit:

                // api_domain_auto_remit_list
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/auto_remit/list$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_domain_auto_remit_list;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_auto_remit_list')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::listDomainAutoRemitAction',));
                }
                not_api_domain_auto_remit_list:

                // api_set_domain_auto_remit
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/auto_remit/(?P<autoRemitId>[^/]++)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_domain_auto_remit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_domain_auto_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::setDomainAutoRemitAction',));
                }
                not_api_set_domain_auto_remit:

                // api_set_domain_all_auto_remit
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/auto_remit$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_domain_all_auto_remit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_domain_all_auto_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::setDomainAllAutoRemitAction',));
                }
                not_api_set_domain_all_auto_remit:

                // api_remove_domain_auto_remit
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/auto_remit/(?P<autoRemitId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_domain_auto_remit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_domain_auto_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\AutoRemitController::removeDomainAutoRemitAction',));
                }
                not_api_remove_domain_auto_remit:

            }

            // api_bank_get
            if (0 === strpos($pathinfo, '/api/bank') && preg_match('#^/api/bank/(?P<bankId>\\d+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_bank_get;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::getBankAction',));
            }
            not_api_bank_get:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_bank_create
                if (preg_match('#^/api/user/(?P<userId>\\d+)/bank$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_bank_create;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_create')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::createAction',));
                }
                not_api_bank_create:

                // api_bank_edit
                if (preg_match('#^/api/user/(?P<userId>\\d+)/bank$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_bank_edit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_edit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::editBankAction',));
                }
                not_api_bank_edit:

                // api_usr_get_bank
                if (preg_match('#^/api/user/(?P<userId>\\d+)/bank$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_usr_get_bank;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_usr_get_bank')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::getBankByUserAction',));
                }
                not_api_usr_get_bank:

            }

            if (0 === strpos($pathinfo, '/api/bank')) {
                // api_bank_check_unique
                if ($pathinfo === '/api/bank/check_unique') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_bank_check_unique;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::checkUniqueAction',  '_route' => 'api_bank_check_unique',);
                }
                not_api_bank_check_unique:

                // api_bank_remove
                if (preg_match('#^/api/bank/(?P<bankId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_bank_remove;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_remove')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::removeAction',));
                }
                not_api_bank_remove:

                if (0 === strpos($pathinfo, '/api/bank/holder_config_by_users')) {
                    // api_get_bank_holder_config_by_users
                    if ($pathinfo === '/api/bank/holder_config_by_users') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_bank_holder_config_by_users;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::getHolderConfigByUsersAction',  '_route' => 'api_get_bank_holder_config_by_users',);
                    }
                    not_api_get_bank_holder_config_by_users:

                    // api_enable_bank_holder_config_by_users
                    if ($pathinfo === '/api/bank/holder_config_by_users/enable') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_enable_bank_holder_config_by_users;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::enableHolderConfigByUsersAction',  '_route' => 'api_enable_bank_holder_config_by_users',);
                    }
                    not_api_enable_bank_holder_config_by_users:

                    // api_disable_bank_holder_config_by_users
                    if ($pathinfo === '/api/bank/holder_config_by_users/disable') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_disable_bank_holder_config_by_users;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::disableHolderConfigByUsersAction',  '_route' => 'api_disable_bank_holder_config_by_users',);
                    }
                    not_api_disable_bank_holder_config_by_users:

                }

                if (0 === strpos($pathinfo, '/api/bank/edit_holder_by_users')) {
                    // api_enable_edit_holder_by_users
                    if ($pathinfo === '/api/bank/edit_holder_by_users/enable') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_enable_edit_holder_by_users;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::enableEditHolderByUsersAction',  '_route' => 'api_enable_edit_holder_by_users',);
                    }
                    not_api_enable_edit_holder_by_users:

                    // api_disable_edit_holder_config_by_users
                    if ($pathinfo === '/api/bank/edit_holder_by_users/disable') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_disable_edit_holder_config_by_users;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::disableEditHolderByUsersAction',  '_route' => 'api_disable_edit_holder_config_by_users',);
                    }
                    not_api_disable_edit_holder_config_by_users:

                }

            }

            // api_domain_bank_holder_config_disable
            if (0 === strpos($pathinfo, '/api/domain') && preg_match('#^/api/domain/(?P<domain>\\d+)/bank_holder_config/disable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_domain_bank_holder_config_disable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_bank_holder_config_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankController::disableBankHolderConfigAction',));
            }
            not_api_domain_bank_holder_config_disable:

            if (0 === strpos($pathinfo, '/api/bank_info')) {
                // api_bank_info_get
                if (preg_match('#^/api/bank_info/(?P<bankInfoId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_bank_info_get;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_info_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::getAction',));
                }
                not_api_bank_info_get:

                // api_bank_info_add_currency
                if (preg_match('#^/api/bank_info/(?P<bankInfoId>\\d+)/currency/(?P<currency>\\w+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_bank_info_add_currency;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_info_add_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::addCurrencyAction',));
                }
                not_api_bank_info_add_currency:

                // api_bank_info_remove_currency
                if (preg_match('#^/api/bank_info/(?P<bankInfoId>\\d+)/currency/(?P<currency>\\w+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_bank_info_remove_currency;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_info_remove_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::removeCurrencyAction',));
                }
                not_api_bank_info_remove_currency:

                // api_bank_info_get_currency
                if (preg_match('#^/api/bank_info/(?P<bankInfoId>\\d+)/currency$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_bank_info_get_currency;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_info_get_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::getCurrencyByBankAction',));
                }
                not_api_bank_info_get_currency:

            }

            // api_currency_get_bank_info
            if (0 === strpos($pathinfo, '/api/currency') && preg_match('#^/api/currency/(?P<currency>\\w+)/bank_info$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_currency_get_bank_info;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_currency_get_bank_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::getBankByCurrencyAction',));
            }
            not_api_currency_get_bank_info:

            if (0 === strpos($pathinfo, '/api/bank_info')) {
                // api_all_bank_info_currency
                if ($pathinfo === '/api/bank_info/currency') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_all_bank_info_currency;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::getAllBankCurrencyAction',  '_route' => 'api_all_bank_info_currency',);
                }
                not_api_all_bank_info_currency:

                // api_bank_info_enable
                if (preg_match('#^/api/bank_info/(?P<bankInfoId>\\d+)/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_bank_info_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_info_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::enableAction',));
                }
                not_api_bank_info_enable:

                // api_bank_info_disable
                if (preg_match('#^/api/bank_info/(?P<bankInfoId>\\d+)/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_bank_info_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_info_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::disableAction',));
                }
                not_api_bank_info_disable:

                // api_bank_info_edit
                if (preg_match('#^/api/bank_info/(?P<bankInfoId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_bank_info_edit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bank_info_edit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::editAction',));
                }
                not_api_bank_info_edit:

            }

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_domain_get_withdraw_bank_currency
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/withdraw/bank_currency$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_domain_get_withdraw_bank_currency;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_get_withdraw_bank_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::getDomainWithdrawBankCurrencyAction',));
                }
                not_api_domain_get_withdraw_bank_currency:

                // api_domain_set_withdraw_bank_currency
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/withdraw/bank_currency$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_set_withdraw_bank_currency;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_set_withdraw_bank_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::setDomainWithdrawBankCurrencyAction',));
                }
                not_api_domain_set_withdraw_bank_currency:

            }

            if (0 === strpos($pathinfo, '/api/level')) {
                // api_level_get_withdraw_bank_currency
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/withdraw/bank_currency$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_level_get_withdraw_bank_currency;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_level_get_withdraw_bank_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::getLevelWithdrawBankCurrencyAction',));
                }
                not_api_level_get_withdraw_bank_currency:

                // api_level_set_withdraw_bank_currency
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/withdraw/bank_currency$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_level_set_withdraw_bank_currency;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_level_set_withdraw_bank_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::setLevelWithdrawBankCurrencyAction',));
                }
                not_api_level_set_withdraw_bank_currency:

            }

            // api_domain_withdraw_bank_currency_disable
            if (0 === strpos($pathinfo, '/api/domain') && preg_match('#^/api/domain/(?P<domain>\\d+)/withdraw/bank_currency/disable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_domain_withdraw_bank_currency_disable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_withdraw_bank_currency_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BankInfoController::disableWithdrawBankCurrencyAction',));
            }
            not_api_domain_withdraw_bank_currency_disable:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_create_bitcoin_address
                if (preg_match('#^/api/user/(?P<userId>[^/]++)/bitcoin_address$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_bitcoin_address;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_bitcoin_address')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinAdreessController::createAction',));
                }
                not_api_create_bitcoin_address:

                // api_get_user_bitcoin_address
                if (preg_match('#^/api/user/(?P<userId>\\d+)/bitcoin_address$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_bitcoin_address;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_bitcoin_address')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinAdreessController::getBitcoinAddressByUserAction',));
                }
                not_api_get_user_bitcoin_address:

                // api_get_user_bitcoin_rate
                if (preg_match('#^/api/user/(?P<userId>\\d+)/bitcoin_rate$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_bitcoin_rate;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_bitcoin_rate')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinController::getBitcoinRateAction',));
                }
                not_api_get_user_bitcoin_rate:

                // api_user_bitcoin_deposit
                if (preg_match('#^/api/user/(?P<userId>\\d+)/bitcoin_deposit$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_user_bitcoin_deposit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_bitcoin_deposit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinDepositController::createAction',));
                }
                not_api_user_bitcoin_deposit:

            }

            if (0 === strpos($pathinfo, '/api/bitcoin_deposit')) {
                if (0 === strpos($pathinfo, '/api/bitcoin_deposit/entry')) {
                    // api_bitcoin_deposit_confirm
                    if (preg_match('#^/api/bitcoin_deposit/entry/(?P<entryId>\\d+)/confirm$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_bitcoin_deposit_confirm;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bitcoin_deposit_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinDepositController::confirmAction',));
                    }
                    not_api_bitcoin_deposit_confirm:

                    // api_bitcoin_deposit_cancel
                    if (preg_match('#^/api/bitcoin_deposit/entry/(?P<entryId>\\d+)/cancel$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_bitcoin_deposit_cancel;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bitcoin_deposit_cancel')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinDepositController::cancelAction',));
                    }
                    not_api_bitcoin_deposit_cancel:

                    // api_get_bitcoin_deposit_entry
                    if (preg_match('#^/api/bitcoin_deposit/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_bitcoin_deposit_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_bitcoin_deposit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinDepositController::getEntryAction',));
                    }
                    not_api_get_bitcoin_deposit_entry:

                }

                // api_set_bitcoin_deposit_entry_memo
                if (preg_match('#^/api/bitcoin_deposit/(?P<entryId>\\d+)/memo$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_bitcoin_deposit_entry_memo;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_bitcoin_deposit_entry_memo')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinDepositController::setBitcoinDepositEntryMemoAction',));
                }
                not_api_set_bitcoin_deposit_entry_memo:

                // api_bitcoin_deposit_entry_list
                if ($pathinfo === '/api/bitcoin_deposit/entry/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_bitcoin_deposit_entry_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinDepositController::listEntryAction',  '_route' => 'api_bitcoin_deposit_entry_list',);
                }
                not_api_bitcoin_deposit_entry_list:

            }

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_create_domain_bitcoin_wallet
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/bitcoin_wallet$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_domain_bitcoin_wallet;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_domain_bitcoin_wallet')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWalletController::createAction',));
                }
                not_api_create_domain_bitcoin_wallet:

                // api_get_domain_bitcoin_wallet
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/bitcoin_wallet$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_domain_bitcoin_wallet;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_domain_bitcoin_wallet')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWalletController::getWalletByDomainAction',));
                }
                not_api_get_domain_bitcoin_wallet:

            }

            if (0 === strpos($pathinfo, '/api/bitcoin_wallet')) {
                // api_get_bitcoin_wallet
                if (preg_match('#^/api/bitcoin_wallet/(?P<bitcoinWalletId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_bitcoin_wallet;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_bitcoin_wallet')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWalletController::getWalletAction',));
                }
                not_api_get_bitcoin_wallet:

                // api_edit_bitcoin_wallet
                if (preg_match('#^/api/bitcoin_wallet/(?P<bitcoinWalletId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_edit_bitcoin_wallet;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_edit_bitcoin_wallet')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWalletController::editWalletAction',));
                }
                not_api_edit_bitcoin_wallet:

            }

            // api_user_bitcoin_withdraw
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/bitcoin_withdraw$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_user_bitcoin_withdraw;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_bitcoin_withdraw')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWithdrawController::createAction',));
            }
            not_api_user_bitcoin_withdraw:

            if (0 === strpos($pathinfo, '/api/b')) {
                if (0 === strpos($pathinfo, '/api/bitcoin_withdraw')) {
                    if (0 === strpos($pathinfo, '/api/bitcoin_withdraw/entry')) {
                        // api_bitcoin_withdraw_confirm
                        if (preg_match('#^/api/bitcoin_withdraw/entry/(?P<entryId>\\d+)/confirm$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_bitcoin_withdraw_confirm;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bitcoin_withdraw_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWithdrawController::confirmAction',));
                        }
                        not_api_bitcoin_withdraw_confirm:

                        // api_bitcoin_withdraw_cancel
                        if (preg_match('#^/api/bitcoin_withdraw/entry/(?P<entryId>\\d+)/cancel$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_bitcoin_withdraw_cancel;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bitcoin_withdraw_cancel')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWithdrawController::cancelAction',));
                        }
                        not_api_bitcoin_withdraw_cancel:

                        // api_bitcoin_withdraw_locked
                        if (preg_match('#^/api/bitcoin_withdraw/entry/(?P<entryId>\\d+)/locked$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_bitcoin_withdraw_locked;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bitcoin_withdraw_locked')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWithdrawController::lockedAction',));
                        }
                        not_api_bitcoin_withdraw_locked:

                        // api_bitcoin_withdraw_unlocked
                        if (preg_match('#^/api/bitcoin_withdraw/entry/(?P<entryId>\\d+)/unlocked$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_bitcoin_withdraw_unlocked;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bitcoin_withdraw_unlocked')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWithdrawController::unlockedAction',));
                        }
                        not_api_bitcoin_withdraw_unlocked:

                        // api_get_bitcoin_withdraw_entry
                        if (preg_match('#^/api/bitcoin_withdraw/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_get_bitcoin_withdraw_entry;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_bitcoin_withdraw_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWithdrawController::getEntryAction',));
                        }
                        not_api_get_bitcoin_withdraw_entry:

                    }

                    // api_set_bitcoin_withdraw_entry_memo
                    if (preg_match('#^/api/bitcoin_withdraw/(?P<entryId>\\d+)/memo$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_bitcoin_withdraw_entry_memo;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_bitcoin_withdraw_entry_memo')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWithdrawController::setBitcoinWithdrawEntryMemoAction',));
                    }
                    not_api_set_bitcoin_withdraw_entry_memo:

                    // api_bitcoin_withdraw_entry_list
                    if ($pathinfo === '/api/bitcoin_withdraw/entry/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_bitcoin_withdraw_entry_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BitcoinWithdrawController::listEntryAction',  '_route' => 'api_bitcoin_withdraw_entry_list',);
                    }
                    not_api_bitcoin_withdraw_entry_list:

                }

                if (0 === strpos($pathinfo, '/api/blacklist')) {
                    // api_blacklist_create
                    if ($pathinfo === '/api/blacklist') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_blacklist_create;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BlacklistController::createAction',  '_route' => 'api_blacklist_create',);
                    }
                    not_api_blacklist_create:

                    // api_blacklist_edit
                    if (preg_match('#^/api/blacklist/(?P<blacklistId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_blacklist_edit;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_blacklist_edit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BlacklistController::editAction',));
                    }
                    not_api_blacklist_edit:

                    // api_get_blacklist_by_id
                    if (preg_match('#^/api/blacklist/(?P<blacklistId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_blacklist_by_id;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_blacklist_by_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BlacklistController::getBlacklistByIdAction',));
                    }
                    not_api_get_blacklist_by_id:

                    // api_get_blacklist
                    if ($pathinfo === '/api/blacklist') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_blacklist;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BlacklistController::getBlacklistAction',  '_route' => 'api_get_blacklist',);
                    }
                    not_api_get_blacklist:

                    // api_blacklist_remove
                    if (preg_match('#^/api/blacklist/(?P<blacklistId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_blacklist_remove;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_blacklist_remove')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BlacklistController::removeAction',));
                    }
                    not_api_blacklist_remove:

                    // api_get_blacklist_operation_log
                    if ($pathinfo === '/api/blacklist/operation_log') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_blacklist_operation_log;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BlacklistController::getBlacklistOperationLogAction',  '_route' => 'api_get_blacklist_operation_log',);
                    }
                    not_api_get_blacklist_operation_log:

                }

                if (0 === strpos($pathinfo, '/api/bodog')) {
                    // api_bodog_get_trans
                    if (0 === strpos($pathinfo, '/api/bodog/transaction') && preg_match('#^/api/bodog/transaction/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_bodog_get_trans;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_bodog_get_trans')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BodogController::getTransactionAction',));
                    }
                    not_api_bodog_get_trans:

                    // api_get_bodog_entry
                    if (0 === strpos($pathinfo, '/api/bodog/entry') && preg_match('#^/api/bodog/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_bodog_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_bodog_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BodogController::getEntryAction',));
                    }
                    not_api_get_bodog_entry:

                }

            }

            // api_get_bodog_entries
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/bodog/entry$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_bodog_entries;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_bodog_entries')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BodogController::getEntriesAction',));
            }
            not_api_get_bodog_entries:

            if (0 === strpos($pathinfo, '/api/b')) {
                // api_get_bodog_entries_by_ref_id
                if ($pathinfo === '/api/bodog/entries_by_ref_id') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_bodog_entries_by_ref_id;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BodogController::getEntriesByRefIdAction',  '_route' => 'api_get_bodog_entries_by_ref_id',);
                }
                not_api_get_bodog_entries_by_ref_id:

                // api_fetch_user_ids_by_username
                if ($pathinfo === '/api/bulk/fetch_user_ids_by_username') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_fetch_user_ids_by_username;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\BulkController::fetchUserIdsByUsernameAction',  '_route' => 'api_fetch_user_ids_by_username',);
                }
                not_api_fetch_user_ids_by_username:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_captcha_create
                if (preg_match('#^/api/user/(?P<userId>\\d+)/captcha$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_captcha_create;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_captcha_create')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CaptchaController::createAction',));
                }
                not_api_captcha_create:

                // api_captcha_verify
                if (preg_match('#^/api/user/(?P<userId>\\d+)/captcha/verify$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_captcha_verify;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_captcha_verify')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CaptchaController::verifyAction',));
                }
                not_api_captcha_verify:

            }

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_create_domain_card_charge
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/card_charge$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_domain_card_charge;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_domain_card_charge')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardChargeController::createAction',));
                }
                not_api_create_domain_card_charge:

                // api_get_domain_card_charge
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/card_charge$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_domain_card_charge;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_domain_card_charge')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardChargeController::getAction',));
                }
                not_api_get_domain_card_charge:

            }

            if (0 === strpos($pathinfo, '/api/card')) {
                if (0 === strpos($pathinfo, '/api/card_charge')) {
                    // api_set_card_charge
                    if (preg_match('#^/api/card_charge/(?P<cardChargeId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_card_charge;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_card_charge')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardChargeController::setAction',));
                    }
                    not_api_set_card_charge:

                    // api_get_card_payment_gateway_fee
                    if (preg_match('#^/api/card_charge/(?P<cardChargeId>\\d+)/payment_gateway/fee$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_card_payment_gateway_fee;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_card_payment_gateway_fee')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardChargeController::getCardPaymentGatewayFeeAction',));
                    }
                    not_api_get_card_payment_gateway_fee:

                    // api_set_card_payment_gateway_fee
                    if (preg_match('#^/api/card_charge/(?P<cardChargeId>\\d+)/payment_gateway/fee$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_card_payment_gateway_fee;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_card_payment_gateway_fee')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardChargeController::setCardPaymentGatewayFeeAction',));
                    }
                    not_api_set_card_payment_gateway_fee:

                }

                // api_card_get
                if (preg_match('#^/api/card/(?P<cardId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_card_get;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::getAction',));
                }
                not_api_card_get:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_card_get_by_user_id
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_card_get_by_user_id;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_get_by_user_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::getCardByUserIdAction',));
                }
                not_api_card_get_by_user_id:

                // api_card_get_which_enable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/which_enable$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_card_get_which_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_get_which_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::getWhichEnableAction',));
                }
                not_api_card_get_which_enable:

            }

            // api_card_get_entry
            if (0 === strpos($pathinfo, '/api/card') && preg_match('#^/api/card/(?P<cardId>\\d+)/entry$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_card_get_entry;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_get_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::getEntriesAction',));
            }
            not_api_card_get_entry:

            // api_user_card_get_entry
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/card/entry$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_user_card_get_entry;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_card_get_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::getEntriesByUserAction',));
            }
            not_api_user_card_get_entry:

            if (0 === strpos($pathinfo, '/api/card')) {
                // api_get_card_entries_by_parent
                if ($pathinfo === '/api/card/entries') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_card_entries_by_parent;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::getEntriesByParentAction',  '_route' => 'api_get_card_entries_by_parent',);
                }
                not_api_get_card_entries_by_parent:

                // api_card_op
                if (preg_match('#^/api/card/(?P<cardId>\\d+)/op$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_card_op;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_op')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::cardOpAction',));
                }
                not_api_card_op:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_user_card_direct_op
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/direct_op$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_card_direct_op;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_card_direct_op')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::directCardOpAction',));
                }
                not_api_user_card_direct_op:

                // api_user_card_op
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/op$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_card_op;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_card_op')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::userOpAction',));
                }
                not_api_user_card_op:

                // api_card_enable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_card_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::enableAction',));
                }
                not_api_card_enable:

                // api_card_disable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_card_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::disableAction',));
                }
                not_api_card_disable:

            }

            if (0 === strpos($pathinfo, '/api/card/entr')) {
                // api_get_card_entry
                if (0 === strpos($pathinfo, '/api/card/entry') && preg_match('#^/api/card/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_card_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_card_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::getEntryAction',));
                }
                not_api_get_card_entry:

                // api_get_card_entries_by_ref_id
                if ($pathinfo === '/api/card/entries_by_ref_id') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_card_entries_by_ref_id;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardController::getEntriesByRefIdAction',  '_route' => 'api_get_card_entries_by_ref_id',);
                }
                not_api_get_card_entries_by_ref_id:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_get_deposit_merchant_card
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/deposit/merchant_card$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_deposit_merchant_card;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_deposit_merchant_card')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::getDepositMerchantCardAction',));
                }
                not_api_get_deposit_merchant_card:

                // api_card_deposit
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/deposit$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_card_deposit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_deposit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::cardDepositAction',));
                }
                not_api_card_deposit:

            }

            if (0 === strpos($pathinfo, '/api/card/deposit')) {
                // api_card_deposit_params
                if (preg_match('#^/api/card/deposit/(?P<entryId>\\d+)/params$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_card_deposit_params;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_deposit_params')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::getParamsAction',));
                }
                not_api_card_deposit_params:

                // api_get_card_deposit_entry
                if (preg_match('#^/api/card/deposit/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_card_deposit_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_card_deposit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::getEntryAction',));
                }
                not_api_get_card_deposit_entry:

                // api_set_card_deposit_entry
                if (preg_match('#^/api/card/deposit/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_card_deposit_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_card_deposit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::setEntryAction',));
                }
                not_api_set_card_deposit_entry:

                // api_list_card_deposit_entry
                if ($pathinfo === '/api/card/deposit/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_list_card_deposit_entry;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::listEntryAction',  '_route' => 'api_list_card_deposit_entry',);
                }
                not_api_list_card_deposit_entry:

                // api_get_card_deposit_total_amount
                if ($pathinfo === '/api/card/deposit/total_amount') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_card_deposit_total_amount;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::getTotalAmountAction',  '_route' => 'api_get_card_deposit_total_amount',);
                }
                not_api_get_card_deposit_total_amount:

                // api_card_deposit_confirm
                if (preg_match('#^/api/card/deposit/(?P<entryId>\\d+)/confirm$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_card_deposit_confirm;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_deposit_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::confirmAction',));
                }
                not_api_card_deposit_confirm:

                // api_card_deposit_verify
                if (preg_match('#^/api/card/deposit/(?P<entryId>\\d+)/verify$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_card_deposit_verify;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_deposit_verify')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::verifyDecodeAction',));
                }
                not_api_card_deposit_verify:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_user_get_card_deposit_payment_method
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/deposit/payment_method$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_card_deposit_payment_method;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_card_deposit_payment_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::getPaymentMethodAction',));
                }
                not_api_user_get_card_deposit_payment_method:

                // api_user_get_card_deposit_payment_vendor
                if (preg_match('#^/api/user/(?P<userId>\\d+)/card/deposit/payment_vendor$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_card_deposit_payment_vendor;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_card_deposit_payment_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::getPaymentVendorAction',));
                }
                not_api_user_get_card_deposit_payment_vendor:

            }

            if (0 === strpos($pathinfo, '/api/card/deposit')) {
                // api_card_deposit_tracking
                if (preg_match('#^/api/card/deposit/(?P<entryId>\\d+)/tracking$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_card_deposit_tracking;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_deposit_tracking')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::trackingAction',));
                }
                not_api_card_deposit_tracking:

                // api_card_deposit_real_name_auth_params
                if (preg_match('#^/api/card/deposit/(?P<entryId>\\d+)/real_name_auth/params$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_card_deposit_real_name_auth_params;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_card_deposit_real_name_auth_params')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::getRealNameAuthParamsAction',));
                }
                not_api_card_deposit_real_name_auth_params:

                // api_get_card_deposit_real_name_auth
                if (preg_match('#^/api/card/deposit/(?P<entryId>\\d+)/real_name_auth$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_card_deposit_real_name_auth;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_card_deposit_real_name_auth')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CardDepositController::getRealNameAuthAction',));
                }
                not_api_get_card_deposit_real_name_auth:

            }

            // api_cash_create
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cash$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_cash_create;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_create')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::createAction',));
            }
            not_api_cash_create:

            // api_cash_get
            if (0 === strpos($pathinfo, '/api/cash') && preg_match('#^/api/cash/(?P<cashId>\\d+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_cash_get;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getAction',));
            }
            not_api_cash_get:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_cash_get_by_user_id
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_get_by_user_id;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_get_by_user_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getCashByUserIdAction',));
                }
                not_api_cash_get_by_user_id:

                // api_cash_total_amount
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash/total_amount$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_total_amount;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_total_amount')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getTotalAmountAction',));
                }
                not_api_cash_total_amount:

                // api_cash_transfer_total_amount
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash/transfer_total_amount$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_transfer_total_amount;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_transfer_total_amount')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getTotalTransferAction',));
                }
                not_api_cash_transfer_total_amount:

            }

            if (0 === strpos($pathinfo, '/api/cash')) {
                // api_cash_transfer_total_below
                if ($pathinfo === '/api/cash/transfer_total_below') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_transfer_total_below;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getTransferTotalBelowAction',  '_route' => 'api_cash_transfer_total_below',);
                }
                not_api_cash_transfer_total_below:

                if (0 === strpos($pathinfo, '/api/cash/negative_')) {
                    // api_cash_negative_balance_get
                    if ($pathinfo === '/api/cash/negative_balance') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_negative_balance_get;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getNegativeBalanceAction',  '_route' => 'api_cash_negative_balance_get',);
                    }
                    not_api_cash_negative_balance_get:

                    // api_cash_negative_entry_get
                    if ($pathinfo === '/api/cash/negative_entry') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_negative_entry_get;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getNegativeEntryAction',  '_route' => 'api_cash_negative_entry_get',);
                    }
                    not_api_cash_negative_entry_get:

                }

            }

            // api_get_user_cash_negative_entry
            if ($pathinfo === '/api/user/cash/negative_entry') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_user_cash_negative_entry;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getNegativeEntryByUserAction',  '_route' => 'api_get_user_cash_negative_entry',);
            }
            not_api_get_user_cash_negative_entry:

            if (0 === strpos($pathinfo, '/api/cash')) {
                // api_cash_get_negative
                if ($pathinfo === '/api/cash/negative') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_get_negative;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getNegativeAction',  '_route' => 'api_cash_get_negative',);
                }
                not_api_cash_get_negative:

                // api_cash_error_get
                if ($pathinfo === '/api/cash/error') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_error_get;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getCashErrorAction',  '_route' => 'api_cash_error_get',);
                }
                not_api_cash_error_get:

                if (0 === strpos($pathinfo, '/api/cash/t')) {
                    if (0 === strpos($pathinfo, '/api/cash/total_balance')) {
                        // api_cash_update_total_balance
                        if ($pathinfo === '/api/cash/total_balance') {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_cash_update_total_balance;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::updateTotalBalanceAction',  '_route' => 'api_cash_update_total_balance',);
                        }
                        not_api_cash_update_total_balance:

                        // api_cash_get_total_balance
                        if ($pathinfo === '/api/cash/total_balance') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_cash_get_total_balance;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getTotalBalanceAction',  '_route' => 'api_cash_get_total_balance',);
                        }
                        not_api_cash_get_total_balance:

                        // api_cash_get_total_balance_live
                        if ($pathinfo === '/api/cash/total_balance_live') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_cash_get_total_balance_live;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getTotalBalanceLiveAction',  '_route' => 'api_cash_get_total_balance_live',);
                        }
                        not_api_cash_get_total_balance_live:

                    }

                    // api_cash_transaction_uncommit
                    if ($pathinfo === '/api/cash/transaction/uncommit') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_transaction_uncommit;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::transactionUncommitAction',  '_route' => 'api_cash_transaction_uncommit',);
                    }
                    not_api_cash_transaction_uncommit:

                }

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_cash_operation
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash/op$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_cash_operation;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_operation')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::operationAction',));
                }
                not_api_cash_operation:

                // api_cash_transfer_out
                if (preg_match('#^/api/user/(?P<userId>\\d+)/transfer_out$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_cash_transfer_out;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_transfer_out')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::transferAction',));
                }
                not_api_cash_transfer_out:

            }

            if (0 === strpos($pathinfo, '/api/cash/transaction')) {
                // api_cash_transaction_commit
                if (preg_match('#^/api/cash/transaction/(?P<id>\\d+)/commit$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_cash_transaction_commit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_transaction_commit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::transactionCommitAction',));
                }
                not_api_cash_transaction_commit:

                // api_cash_transaction_rollback
                if (preg_match('#^/api/cash/transaction/(?P<id>\\d+)/rollback$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_cash_transaction_rollback;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_transaction_rollback')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::transactionRollbackAction',));
                }
                not_api_cash_transaction_rollback:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_cash_get_entry
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash/entry$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_get_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_get_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getEntriesAction',));
                }
                not_api_cash_get_entry:

                // api_cash_get_transfer_entry
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash/transfer_entry$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_get_transfer_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_get_transfer_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getTransferEntriesAction',));
                }
                not_api_cash_get_transfer_entry:

            }

            if (0 === strpos($pathinfo, '/api/cash')) {
                if (0 === strpos($pathinfo, '/api/cash/trans')) {
                    // api_cash_get_transfer_entry_list
                    if ($pathinfo === '/api/cash/transfer_entry/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_get_transfer_entry_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getTransferEntriesListAction',  '_route' => 'api_cash_get_transfer_entry_list',);
                    }
                    not_api_cash_get_transfer_entry_list:

                    // api_cash_get_trans
                    if (0 === strpos($pathinfo, '/api/cash/transaction') && preg_match('#^/api/cash/transaction/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_get_trans;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_get_trans')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getTransactionAction',));
                    }
                    not_api_cash_get_trans:

                }

                if (0 === strpos($pathinfo, '/api/cash/entr')) {
                    // api_set_cash_entry
                    if (0 === strpos($pathinfo, '/api/cash/entry') && preg_match('#^/api/cash/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_cash_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_cash_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::setEntryAction',));
                    }
                    not_api_set_cash_entry:

                    // api_get_cash_entries_by_ref_id
                    if ($pathinfo === '/api/cash/entries_by_ref_id') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_cash_entries_by_ref_id;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getEntriesByRefIdAction',  '_route' => 'api_get_cash_entries_by_ref_id',);
                    }
                    not_api_get_cash_entries_by_ref_id:

                }

                // api_get_cash_list
                if ($pathinfo === '/api/cash/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_cash_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getCashListAction',  '_route' => 'api_get_cash_list',);
                }
                not_api_get_cash_list:

                // api_get_cash_entry
                if (0 === strpos($pathinfo, '/api/cash/entry') && preg_match('#^/api/cash/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_cash_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_cash_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashController::getEntryAction',));
                }
                not_api_get_cash_entry:

            }

            // api_cash_fake_create
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cashFake$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_cash_fake_create;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_fake_create')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::createAction',));
            }
            not_api_cash_fake_create:

            // api_cashFake_get
            if (0 === strpos($pathinfo, '/api/cash_fake') && preg_match('#^/api/cash_fake/(?P<cashFakeId>\\d+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_cashFake_get;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cashFake_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getAction',));
            }
            not_api_cashFake_get:

            // api_cash_fake_get_by_user_id
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_cash_fake_get_by_user_id;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_fake_get_by_user_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getCashFakeByUserIdAction',));
            }
            not_api_cash_fake_get_by_user_id:

            // api_cashfake_transaction_uncommit
            if ($pathinfo === '/api/cash_fake/transaction/uncommit') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_cashfake_transaction_uncommit;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::transactionUncommitAction',  '_route' => 'api_cashfake_transaction_uncommit',);
            }
            not_api_cashfake_transaction_uncommit:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_cash_fake_total_amount
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/total_amount$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_fake_total_amount;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_fake_total_amount')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getTotalAmountAction',));
                }
                not_api_cash_fake_total_amount:

                // api_cash_fake_transfer_total_amount
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/transfer_total_amount$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_fake_transfer_total_amount;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_fake_transfer_total_amount')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getTotalTransferAction',));
                }
                not_api_cash_fake_transfer_total_amount:

            }

            // api_cashFake_transfer_to
            if ($pathinfo === '/api/cash_fake/transfer') {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_cashFake_transfer_to;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::transferToAction',  '_route' => 'api_cashFake_transfer_to',);
            }
            not_api_cashFake_transfer_to:

            // api_cashFake_operation
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/op$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_cashFake_operation;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cashFake_operation')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::operationAction',));
            }
            not_api_cashFake_operation:

            if (0 === strpos($pathinfo, '/api/cash_fake')) {
                if (0 === strpos($pathinfo, '/api/cash_fake/transaction')) {
                    // api_cashFake_transaction_commit
                    if (preg_match('#^/api/cash_fake/transaction/(?P<id>\\d+)/commit$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_cashFake_transaction_commit;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cashFake_transaction_commit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::transactionCommitAction',));
                    }
                    not_api_cashFake_transaction_commit:

                    // api_cashFake_transaction_rollback
                    if (preg_match('#^/api/cash_fake/transaction/(?P<id>\\d+)/rollback$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_cashFake_transaction_rollback;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cashFake_transaction_rollback')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::transactionRollbackAction',));
                    }
                    not_api_cashFake_transaction_rollback:

                }

                // api_cash_fake_negative_entry_get
                if ($pathinfo === '/api/cash_fake/negative_entry') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_fake_negative_entry_get;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getNegativeEntryAction',  '_route' => 'api_cash_fake_negative_entry_get',);
                }
                not_api_cash_fake_negative_entry_get:

            }

            // api_get_user_cash_fake_negative_entry
            if ($pathinfo === '/api/user/cash_fake/negative_entry') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_user_cash_fake_negative_entry;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getNegativeEntryByUserAction',  '_route' => 'api_get_user_cash_fake_negative_entry',);
            }
            not_api_get_user_cash_fake_negative_entry:

            if (0 === strpos($pathinfo, '/api/cash_fake')) {
                if (0 === strpos($pathinfo, '/api/cash_fake/negative')) {
                    // api_cash_fake_negative_balance_get
                    if ($pathinfo === '/api/cash_fake/negative_balance') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_fake_negative_balance_get;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getNegativeBalanceAction',  '_route' => 'api_cash_fake_negative_balance_get',);
                    }
                    not_api_cash_fake_negative_balance_get:

                    // api_cash_fake_get_negative
                    if ($pathinfo === '/api/cash_fake/negative') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_fake_get_negative;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getNegativeAction',  '_route' => 'api_cash_fake_get_negative',);
                    }
                    not_api_cash_fake_get_negative:

                }

                // api_cash_fake_error_get
                if ($pathinfo === '/api/cash_fake/error') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_fake_error_get;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getCashFakeErrorAction',  '_route' => 'api_cash_fake_error_get',);
                }
                not_api_cash_fake_error_get:

                if (0 === strpos($pathinfo, '/api/cash_fake/total_balance')) {
                    // api_cash_fake_update_total_balance
                    if ($pathinfo === '/api/cash_fake/total_balance') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_cash_fake_update_total_balance;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::updateTotalBalanceAction',  '_route' => 'api_cash_fake_update_total_balance',);
                    }
                    not_api_cash_fake_update_total_balance:

                    // api_cash_fake_get_total_balance
                    if ($pathinfo === '/api/cash_fake/total_balance') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_fake_get_total_balance;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getTotalBalanceAction',  '_route' => 'api_cash_fake_get_total_balance',);
                    }
                    not_api_cash_fake_get_total_balance:

                    // api_cash_fake_get_total_balance_live
                    if ($pathinfo === '/api/cash_fake/total_balance_live') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_fake_get_total_balance_live;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getTotalBalanceLiveAction',  '_route' => 'api_cash_fake_get_total_balance_live',);
                    }
                    not_api_cash_fake_get_total_balance_live:

                }

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_cashFake_get_entry
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/entry$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cashFake_get_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cashFake_get_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getEntriesAction',));
                }
                not_api_cashFake_get_entry:

                // api_cashFake_get_transfer_entry
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/transfer_entry$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cashFake_get_transfer_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cashFake_get_transfer_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getTransferEntriesAction',));
                }
                not_api_cashFake_get_transfer_entry:

            }

            if (0 === strpos($pathinfo, '/api/cash_fake/trans')) {
                // api_cashFake_get_transfer_entry_list
                if ($pathinfo === '/api/cash_fake/transfer_entry/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cashFake_get_transfer_entry_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getTransferEntriesListAction',  '_route' => 'api_cashFake_get_transfer_entry_list',);
                }
                not_api_cashFake_get_transfer_entry_list:

                // api_cahFake_get_trans
                if (0 === strpos($pathinfo, '/api/cash_fake/transaction') && preg_match('#^/api/cash_fake/transaction/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cahFake_get_trans;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cahFake_get_trans')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getTransactionAction',));
                }
                not_api_cahFake_get_trans:

            }

            // api_cashFake_get_total_below
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/total_below$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_cashFake_get_total_below;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cashFake_get_total_below')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getTotalBelowAction',));
            }
            not_api_cashFake_get_total_below:

            // api_cash_fake_disable
            if (0 === strpos($pathinfo, '/api/cash_fake') && preg_match('#^/api/cash_fake/(?P<cashFakeId>\\d+)/disable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_cash_fake_disable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_fake_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::disableAction',));
            }
            not_api_cash_fake_disable:

            // api_user_cash_fake_disable
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/disable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_user_cash_fake_disable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_cash_fake_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::disableByUserAction',));
            }
            not_api_user_cash_fake_disable:

            // api_cash_fake_enable
            if (0 === strpos($pathinfo, '/api/cash_fake') && preg_match('#^/api/cash_fake/(?P<cashFakeId>\\d+)/enable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_cash_fake_enable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_fake_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::enableAction',));
            }
            not_api_cash_fake_enable:

            // api_user_cash_fake_enable
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/enable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_user_cash_fake_enable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_cash_fake_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::enableByUserAction',));
            }
            not_api_user_cash_fake_enable:

            if (0 === strpos($pathinfo, '/api/cash_fake')) {
                if (0 === strpos($pathinfo, '/api/cash_fake/entr')) {
                    if (0 === strpos($pathinfo, '/api/cash_fake/entry')) {
                        // api_set_cash_fake_entry
                        if (preg_match('#^/api/cash_fake/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_set_cash_fake_entry;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_cash_fake_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::setEntryAction',));
                        }
                        not_api_set_cash_fake_entry:

                        // api_get_cash_fake_entry
                        if (preg_match('#^/api/cash_fake/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_get_cash_fake_entry;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_cash_fake_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getEntryAction',));
                        }
                        not_api_get_cash_fake_entry:

                    }

                    // api_get_cash_fake_entries_by_ref_id
                    if ($pathinfo === '/api/cash_fake/entries_by_ref_id') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_cash_fake_entries_by_ref_id;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getEntriesByRefIdAction',  '_route' => 'api_get_cash_fake_entries_by_ref_id',);
                    }
                    not_api_get_cash_fake_entries_by_ref_id:

                }

                if (0 === strpos($pathinfo, '/api/cash_fake/l')) {
                    // api_get_cash_fake_list
                    if ($pathinfo === '/api/cash_fake/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_cash_fake_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getCashFakeListAction',  '_route' => 'api_get_cash_fake_list',);
                    }
                    not_api_get_cash_fake_list:

                    // api_cash_fake_get_last_balance
                    if ($pathinfo === '/api/cash_fake/last_balance') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_cash_fake_get_last_balance;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::getLastBalanceAction',  '_route' => 'api_cash_fake_get_last_balance',);
                    }
                    not_api_cash_fake_get_last_balance:

                }

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_cash_fake_transfer_out
                if (preg_match('#^/api/user/(?P<userId>\\d+)/cash_fake/transfer_out$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_cash_fake_transfer_out;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_fake_transfer_out')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CashFakeController::transferAction',));
                }
                not_api_cash_fake_transfer_out:

                // api_chat_room_get_user
                if (preg_match('#^/api/user/(?P<userId>\\d+)/chat_room$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_chat_room_get_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_chat_room_get_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ChatRoomController::getAction',));
                }
                not_api_chat_room_get_user:

            }

            // api_chat_room_get_ban_list
            if ($pathinfo === '/api/chat_room/ban_list') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_chat_room_get_ban_list;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ChatRoomController::getBanListAction',  '_route' => 'api_chat_room_get_ban_list',);
            }
            not_api_chat_room_get_ban_list:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_chat_room_edit_user
                if (preg_match('#^/api/user/(?P<userId>\\d+)/chat_room$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_chat_room_edit_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_chat_room_edit_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ChatRoomController::editAction',));
                }
                not_api_chat_room_edit_user:

                // api_chat_room_set_ban_at
                if (preg_match('#^/api/user/(?P<userId>\\d+)/chat_room/ban_at$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_chat_room_set_ban_at;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_chat_room_set_ban_at')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ChatRoomController::setBanAtAction',));
                }
                not_api_chat_room_set_ban_at:

            }

            if (0 === strpos($pathinfo, '/api/check')) {
                if (0 === strpos($pathinfo, '/api/check/cash')) {
                    // api_check_cash_total_amount
                    if ($pathinfo === '/api/check/cash/total_amount') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_total_amount;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashTotalAmountAction',  '_route' => 'api_check_cash_total_amount',);
                    }
                    not_api_check_cash_total_amount:

                    // api_check_cash_fake_total_amount
                    if ($pathinfo === '/api/check/cash_fake/total_amount') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_fake_total_amount;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashFakeTotalAmountAction',  '_route' => 'api_check_cash_fake_total_amount',);
                    }
                    not_api_check_cash_fake_total_amount:

                }

                // api_check_outside_total_amount
                if ($pathinfo === '/api/check/outside/total_amount') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_outside_total_amount;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::outsideTotalAmountAction',  '_route' => 'api_check_outside_total_amount',);
                }
                not_api_check_outside_total_amount:

                if (0 === strpos($pathinfo, '/api/check/c')) {
                    // api_check_credit_period_amount
                    if ($pathinfo === '/api/check/credit/period_amount') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_credit_period_amount;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::creditPeriodAmountAction',  '_route' => 'api_check_credit_period_amount',);
                    }
                    not_api_check_credit_period_amount:

                    if (0 === strpos($pathinfo, '/api/check/cash')) {
                        // api_check_cash_count_entries
                        if ($pathinfo === '/api/check/cash/count_entries') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_check_cash_count_entries;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashCountEntriesAction',  '_route' => 'api_check_cash_count_entries',);
                        }
                        not_api_check_cash_count_entries:

                        // api_check_cash_fake_count_entries
                        if ($pathinfo === '/api/check/cash_fake/count_entries') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_check_cash_fake_count_entries;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashFakeCountEntriesAction',  '_route' => 'api_check_cash_fake_count_entries',);
                        }
                        not_api_check_cash_fake_count_entries:

                    }

                }

                // api_check_outside_count_entries
                if ($pathinfo === '/api/check/outside/count_entries') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_outside_count_entries;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::outsideCountEntriesAction',  '_route' => 'api_check_outside_count_entries',);
                }
                not_api_check_outside_count_entries:

                if (0 === strpos($pathinfo, '/api/check/cash')) {
                    // api_check_cash_total_amount_by_ref_id
                    if ($pathinfo === '/api/check/cash/total_amount_by_ref_id') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_total_amount_by_ref_id;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashTotalAmountByRefIdAction',  '_route' => 'api_check_cash_total_amount_by_ref_id',);
                    }
                    not_api_check_cash_total_amount_by_ref_id:

                    // api_check_cash_fake_total_amount_by_ref_id
                    if ($pathinfo === '/api/check/cash_fake/total_amount_by_ref_id') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_fake_total_amount_by_ref_id;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashFakeTotalAmountByRefIdAction',  '_route' => 'api_check_cash_fake_total_amount_by_ref_id',);
                    }
                    not_api_check_cash_fake_total_amount_by_ref_id:

                }

                // api_check_outside_total_amount_by_ref_id
                if ($pathinfo === '/api/check/outside/total_amount_by_ref_id') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_outside_total_amount_by_ref_id;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::outsideTotalAmountByRefIdAction',  '_route' => 'api_check_outside_total_amount_by_ref_id',);
                }
                not_api_check_outside_total_amount_by_ref_id:

                if (0 === strpos($pathinfo, '/api/check/cash')) {
                    // api_check_cash_entry
                    if ($pathinfo === '/api/check/cash/entry') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_entry;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashEntryAction',  '_route' => 'api_check_cash_entry',);
                    }
                    not_api_check_cash_entry:

                    // api_check_cash_fake_entry
                    if ($pathinfo === '/api/check/cash_fake/entry') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_fake_entry;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashFakeEntryAction',  '_route' => 'api_check_cash_fake_entry',);
                    }
                    not_api_check_cash_fake_entry:

                }

                // api_check_outside_entry
                if ($pathinfo === '/api/check/outside/entry') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_outside_entry;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::outsideEntryAction',  '_route' => 'api_check_outside_entry',);
                }
                not_api_check_outside_entry:

                if (0 === strpos($pathinfo, '/api/check/cash')) {
                    // api_check_cash_entry_ref_id
                    if ($pathinfo === '/api/check/cash/entry/ref_id') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_entry_ref_id;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashEntryRefIdAction',  '_route' => 'api_check_cash_entry_ref_id',);
                    }
                    not_api_check_cash_entry_ref_id:

                    // api_check_cash_fake_entry_ref_id
                    if ($pathinfo === '/api/check/cash_fake/entry/ref_id') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_fake_entry_ref_id;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::cashFakeEntryRefIdAction',  '_route' => 'api_check_cash_fake_entry_ref_id',);
                    }
                    not_api_check_cash_fake_entry_ref_id:

                }

                // api_check_outside_entry_ref_id
                if ($pathinfo === '/api/check/outside/entry/ref_id') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_outside_entry_ref_id;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::outsideEntryRefIdAction',  '_route' => 'api_check_outside_entry_ref_id',);
                }
                not_api_check_outside_entry_ref_id:

                if (0 === strpos($pathinfo, '/api/check/cash')) {
                    // api_check_cash_entry_by_time
                    if ($pathinfo === '/api/check/cash/entry_by_time') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_entry_by_time;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::getCashEntryByTimeAction',  '_route' => 'api_check_cash_entry_by_time',);
                    }
                    not_api_check_cash_entry_by_time:

                    // api_check_cash_fake_entry_by_time
                    if ($pathinfo === '/api/check/cash_fake/entry_by_time') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_check_cash_fake_entry_by_time;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::getCashFakeEntryByTimeAction',  '_route' => 'api_check_cash_fake_entry_by_time',);
                    }
                    not_api_check_cash_fake_entry_by_time:

                }

                // api_check_outside_entry_by_time
                if ($pathinfo === '/api/check/outside/entry_by_time') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_outside_entry_by_time;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::getOutsideEntryByTimeAction',  '_route' => 'api_check_outside_entry_by_time',);
                }
                not_api_check_outside_entry_by_time:

                // api_check_cash_fake_entry_by_domain
                if ($pathinfo === '/api/check/cash_fake/entry_by_domain') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_cash_fake_entry_by_domain;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CheckController::getCashFakeEntryByDomainAction',  '_route' => 'api_check_cash_fake_entry_by_domain',);
                }
                not_api_check_cash_fake_entry_by_domain:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_credit_create
                if (preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_credit_create;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_credit_create')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::createAction',));
                }
                not_api_credit_create:

                // api_get_user_one_credit
                if (preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_one_credit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_one_credit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::getUserCreditAction',));
                }
                not_api_get_user_one_credit:

                // api_get_total_enable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)/get_total_enable$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_total_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_total_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::getUserTotalEnableAction',));
                }
                not_api_get_total_enable:

                // api_get_total_disable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)/get_total_disable$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_total_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_total_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::getUserTotalDisableAction',));
                }
                not_api_get_total_disable:

                // api_get_user_all_credit
                if (preg_match('#^/api/user/(?P<userId>\\d+)/credit$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_all_credit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_all_credit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::getUserAllCreditAction',));
                }
                not_api_get_user_all_credit:

                // api_credit_get_entry
                if (preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)/entry$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_credit_get_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_credit_get_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::getEntriesAction',));
                }
                not_api_credit_get_entry:

            }

            // api_credit_get
            if (0 === strpos($pathinfo, '/api/credit') && preg_match('#^/api/credit/(?P<creditId>\\d+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_credit_get;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_credit_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::getAction',));
            }
            not_api_credit_get:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_credit_op
                if (preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)/op$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_credit_op;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_credit_op')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::opAction',));
                }
                not_api_credit_op:

                // api_credit_set
                if (preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_credit_set;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_credit_set')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::setAction',));
                }
                not_api_credit_set:

            }

            // api_credit_disable
            if (0 === strpos($pathinfo, '/api/credit') && preg_match('#^/api/credit/(?P<creditId>\\d+)/disable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_credit_disable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_credit_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::disableAction',));
            }
            not_api_credit_disable:

            // api_user_credit_disable
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)/disable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_user_credit_disable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_credit_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::disableByUserAction',));
            }
            not_api_user_credit_disable:

            // api_credit_enable
            if (0 === strpos($pathinfo, '/api/credit') && preg_match('#^/api/credit/(?P<creditId>\\d+)/enable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_credit_enable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_credit_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::enableAction',));
            }
            not_api_credit_enable:

            // api_user_credit_enable
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>[^/]++)/enable$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_user_credit_enable;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_credit_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::enableByUserAction',));
            }
            not_api_user_credit_enable:

            // api_credit_recover
            if (0 === strpos($pathinfo, '/api/credit') && preg_match('#^/api/credit/(?P<creditId>\\d+)/recover$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_credit_recover;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_credit_recover')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::recoverAction',));
            }
            not_api_credit_recover:

            // api_user_credit_recover
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/credit/(?P<groupNum>\\d+)/recover$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_user_credit_recover;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_credit_recover')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::recoverByUserAction',));
            }
            not_api_user_credit_recover:

            if (0 === strpos($pathinfo, '/api/c')) {
                if (0 === strpos($pathinfo, '/api/credit/entr')) {
                    if (0 === strpos($pathinfo, '/api/credit/entry')) {
                        // api_set_credit_entry
                        if (preg_match('#^/api/credit/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_set_credit_entry;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_credit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::setEntryAction',));
                        }
                        not_api_set_credit_entry:

                        // api_get_credit_entry
                        if (preg_match('#^/api/credit/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_get_credit_entry;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_credit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::getEntryAction',));
                        }
                        not_api_get_credit_entry:

                    }

                    // api_get_credit_entries_by_ref_id
                    if ($pathinfo === '/api/credit/entries_by_ref_id') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_credit_entries_by_ref_id;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CreditController::getEntriesByRefIdAction',  '_route' => 'api_get_credit_entries_by_ref_id',);
                    }
                    not_api_get_credit_entries_by_ref_id:

                }

                if (0 === strpos($pathinfo, '/api/customize')) {
                    // api_supreme_shareholder_list
                    if ($pathinfo === '/api/customize/supreme_shareholder/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_supreme_shareholder_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CustomizeController::getSupremeShareholderListAction',  '_route' => 'api_supreme_shareholder_list',);
                    }
                    not_api_supreme_shareholder_list:

                    if (0 === strpos($pathinfo, '/api/customize/user')) {
                        // api_domain_user_detail
                        if ($pathinfo === '/api/customize/user_detail') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_domain_user_detail;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CustomizeController::getUserDetailByDomainAction',  '_route' => 'api_domain_user_detail',);
                        }
                        not_api_domain_user_detail:

                        // api_customize_user_copy
                        if ($pathinfo === '/api/customize/user/copy') {
                            if ($this->context->getMethod() != 'POST') {
                                $allow[] = 'POST';
                                goto not_api_customize_user_copy;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CustomizeController::copyUserAction',  '_route' => 'api_customize_user_copy',);
                        }
                        not_api_customize_user_copy:

                    }

                    // api_get_domain_inactivated_user
                    if ($pathinfo === '/api/customize/domain/inactivated_user') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_domain_inactivated_user;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\CustomizeController::getInactivatedUserByDomainAction',  '_route' => 'api_get_domain_inactivated_user',);
                    }
                    not_api_get_domain_inactivated_user:

                }

            }

            // api_user_deposit
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/deposit$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_user_deposit;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_deposit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::paymentDepositAction',));
            }
            not_api_user_deposit:

            if (0 === strpos($pathinfo, '/api/deposit')) {
                // api_deposit_params
                if (preg_match('#^/api/deposit/(?P<entryId>\\d+)/params$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_deposit_params;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_deposit_params')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getDepositParamsAction',));
                }
                not_api_deposit_params:

                // api_get_deposit_entry_list
                if ($pathinfo === '/api/deposit/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_deposit_entry_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getDepositEntryListAction',  '_route' => 'api_get_deposit_entry_list',);
                }
                not_api_get_deposit_entry_list:

                // api_get_deposit_entry_total_amount
                if ($pathinfo === '/api/deposit/total_amount') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_deposit_entry_total_amount;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getDepositTotalAmountAction',  '_route' => 'api_get_deposit_entry_total_amount',);
                }
                not_api_get_deposit_entry_total_amount:

                // api_deposit_confirm
                if (preg_match('#^/api/deposit/(?P<entryId>\\d+)/confirm$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_deposit_confirm;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_deposit_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::confirmAction',));
                }
                not_api_deposit_confirm:

                // api_get_deposit_tracking
                if (preg_match('#^/api/deposit/(?P<entryId>\\d+)/tracking$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_deposit_tracking;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_deposit_tracking')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getDepositTrackingAction',));
                }
                not_api_get_deposit_tracking:

                // api_deposit_manual_confirm
                if (preg_match('#^/api/deposit/(?P<entryId>\\d+)/manual_confirm$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_deposit_manual_confirm;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_deposit_manual_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::manualConfirmDepositAction',));
                }
                not_api_deposit_manual_confirm:

                // api_deposit_verify
                if (preg_match('#^/api/deposit/(?P<entryId>\\d+)/verify$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_deposit_verify;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_deposit_verify')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::cashDepositVerifyDecode',));
                }
                not_api_deposit_verify:

                // api_set_deposit_entry
                if (preg_match('#^/api/deposit/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_deposit_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_deposit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::setDepositEntryAction',));
                }
                not_api_set_deposit_entry:

                // api_get_deposit_entry
                if (preg_match('#^/api/deposit/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_deposit_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_deposit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getDepositEntryAction',));
                }
                not_api_get_deposit_entry:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_create_deposit_confirm_quota
                if (preg_match('#^/api/user/(?P<userId>\\d+)/deposit/confirm_quota$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_deposit_confirm_quota;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_deposit_confirm_quota')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::createDepositConfirmQuotaAction',));
                }
                not_api_create_deposit_confirm_quota:

                // api_get_deposit_confirm_quota
                if (preg_match('#^/api/user/(?P<userId>\\d+)/deposit/confirm_quota$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_deposit_confirm_quota;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_deposit_confirm_quota')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getDepositConfirmQuotaAction',));
                }
                not_api_get_deposit_confirm_quota:

                // api_set_deposit_confirm_quota
                if (preg_match('#^/api/user/(?P<userId>\\d+)/deposit/confirm_quota$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_deposit_confirm_quota;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_deposit_confirm_quota')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::setDepositConfirmQuotaAction',));
                }
                not_api_set_deposit_confirm_quota:

                // api_get_user_deposit_offer_params
                if (preg_match('#^/api/user/(?P<userId>\\d+)/deposit/offer_params$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_deposit_offer_params;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_deposit_offer_params')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getUserDepositOfferParamsAction',));
                }
                not_api_get_user_deposit_offer_params:

            }

            if (0 === strpos($pathinfo, '/api/d')) {
                if (0 === strpos($pathinfo, '/api/deposit')) {
                    if (0 === strpos($pathinfo, '/api/deposit/abnormal_notify_email')) {
                        // api_create_deposit_abnormal_notify_email
                        if ($pathinfo === '/api/deposit/abnormal_notify_email') {
                            if ($this->context->getMethod() != 'POST') {
                                $allow[] = 'POST';
                                goto not_api_create_deposit_abnormal_notify_email;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::createAbnormalDepositNotifyEmailAction',  '_route' => 'api_create_deposit_abnormal_notify_email',);
                        }
                        not_api_create_deposit_abnormal_notify_email:

                        // api_remove_deposit_abnormal_notify_email
                        if (preg_match('#^/api/deposit/abnormal_notify_email/(?P<emailId>\\d+)$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'DELETE') {
                                $allow[] = 'DELETE';
                                goto not_api_remove_deposit_abnormal_notify_email;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_deposit_abnormal_notify_email')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::removeAbnormalDepositNotifyEmailAction',));
                        }
                        not_api_remove_deposit_abnormal_notify_email:

                    }

                    // api_deposit_real_name_auth_params
                    if (preg_match('#^/api/deposit/(?P<entryId>\\d+)/real_name_auth/params$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_deposit_real_name_auth_params;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_deposit_real_name_auth_params')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getRealNameAuthParamsAction',));
                    }
                    not_api_deposit_real_name_auth_params:

                    // api_get_deposit_real_name_auth
                    if (preg_match('#^/api/deposit/(?P<entryId>\\d+)/real_name_auth$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_deposit_real_name_auth;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_deposit_real_name_auth')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getRealNameAuthAction',));
                    }
                    not_api_get_deposit_real_name_auth:

                    if (0 === strpos($pathinfo, '/api/deposit/pay_status_error_')) {
                        // api_deposit_pay_status_error_list
                        if ($pathinfo === '/api/deposit/pay_status_error_list') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_deposit_pay_status_error_list;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::getDepositPayStatusErrorListAction',  '_route' => 'api_deposit_pay_status_error_list',);
                        }
                        not_api_deposit_pay_status_error_list:

                        // api_deposit_pay_status_error_checked
                        if ($pathinfo === '/api/deposit/pay_status_error_checked') {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_deposit_pay_status_error_checked;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DepositController::depositPayStatusErrorCheckedAction',  '_route' => 'api_deposit_pay_status_error_checked',);
                        }
                        not_api_deposit_pay_status_error_checked:

                    }

                }

                if (0 === strpos($pathinfo, '/api/domain')) {
                    // api_domain
                    if ($pathinfo === '/api/domain') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_domain;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getDomainListAction',  '_route' => 'api_domain',);
                    }
                    not_api_domain:

                    // api_domain_set_currency
                    if (preg_match('#^/api/domain/(?P<domain>\\d+)/currency$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_domain_set_currency;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_set_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::setDomainCurrencyAction',));
                    }
                    not_api_domain_set_currency:

                    // api_domain_get_currency
                    if (preg_match('#^/api/domain/(?P<domain>\\d+)/currency$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_domain_get_currency;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_get_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getDomainCurrencyAction',));
                    }
                    not_api_domain_get_currency:

                    // api_domain_currency_preset
                    if (preg_match('#^/api/domain/(?P<domain>\\d+)/currency/(?P<currency>\\w+)/preset$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_domain_currency_preset;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_currency_preset')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::setDomainCcurrencyPresetAction',));
                    }
                    not_api_domain_currency_preset:

                    // api_get_domain
                    if (preg_match('#^/api/domain/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_domain;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_domain')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getDomainAction',));
                    }
                    not_api_get_domain:

                    // api_get_login_code
                    if ($pathinfo === '/api/domain/login_code') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_login_code;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getLoginCodeAction',  '_route' => 'api_get_login_code',);
                    }
                    not_api_get_login_code:

                    // api_set_login_code
                    if (preg_match('#^/api/domain/(?P<domain>\\d+)/login_code$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_login_code;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_login_code')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::setLoginCodeAction',));
                    }
                    not_api_set_login_code:

                    // api_domain_get_config
                    if ($pathinfo === '/api/domain/config') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_domain_get_config;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getConfigAction',  '_route' => 'api_domain_get_config',);
                    }
                    not_api_domain_get_config:

                }

            }

            // api_v2_domain_get_config
            if ($pathinfo === '/api/v2/domain/config') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_v2_domain_get_config;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getConfigV2Action',  '_route' => 'api_v2_domain_get_config',);
            }
            not_api_v2_domain_get_config:

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_domain_get_config_by_domain
                if ($pathinfo === '/api/domain/config_by_domain') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_domain_get_config_by_domain;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getConfigByDomainAction',  '_route' => 'api_domain_get_config_by_domain',);
                }
                not_api_domain_get_config_by_domain:

                // api_domain_set_config
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/config$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_set_config;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_set_config')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::setConfigAction',));
                }
                not_api_domain_set_config:

                if (0 === strpos($pathinfo, '/api/domain/ip_blacklist')) {
                    // api_get_ip_blacklist_by_id
                    if (preg_match('#^/api/domain/ip_blacklist/(?P<ipBlacklistId>[^/]++)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_ip_blacklist_by_id;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_ip_blacklist_by_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getIpBlacklistByIdAction',));
                    }
                    not_api_get_ip_blacklist_by_id:

                    // api_get_ip_blacklist
                    if ($pathinfo === '/api/domain/ip_blacklist') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_ip_blacklist;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getIpBlacklistAction',  '_route' => 'api_get_ip_blacklist',);
                    }
                    not_api_get_ip_blacklist:

                    // api_remove_ip_blacklist
                    if ($pathinfo === '/api/domain/ip_blacklist') {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_remove_ip_blacklist;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::removeIpBlacklistAction',  '_route' => 'api_remove_ip_blacklist',);
                    }
                    not_api_remove_ip_blacklist:

                }

                // api_domain_disable
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::disableDomainAction',));
                }
                not_api_domain_disable:

                // api_get_domain_levels
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/levels$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_domain_levels;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_domain_levels')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getDomainLevelsAction',));
                }
                not_api_get_domain_levels:

                // api_domain_update_total_test
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/total_test$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_update_total_test;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_update_total_test')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::updateTotalTestAction',));
                }
                not_api_domain_update_total_test:

                // api_domain_get_total_test
                if ($pathinfo === '/api/domain/total_test') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_domain_get_total_test;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getDomainTotalTestAction',  '_route' => 'api_domain_get_total_test',);
                }
                not_api_domain_get_total_test:

                // api_domain_count_member_created
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/count_member_created$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_domain_count_member_created;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_count_member_created')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::domainCountMemberCreatedAction',));
                }
                not_api_domain_count_member_created:

                if (0 === strpos($pathinfo, '/api/domain/url')) {
                    // api_domain_get_url_list
                    if ($pathinfo === '/api/domain/url/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_domain_get_url_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getUrlListAction',  '_route' => 'api_domain_get_url_list',);
                    }
                    not_api_domain_get_url_list:

                    if (0 === strpos($pathinfo, '/api/domain/url/s')) {
                        // api_domain_get_url_status
                        if ($pathinfo === '/api/domain/url/status') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_domain_get_url_status;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getUrlStatusAction',  '_route' => 'api_domain_get_url_status',);
                        }
                        not_api_domain_get_url_status:

                        // api_domain_get_url_site
                        if ($pathinfo === '/api/domain/url/site') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_domain_get_url_site;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::getUrlSiteAction',  '_route' => 'api_domain_get_url_site',);
                        }
                        not_api_domain_get_url_site:

                    }

                }

                // api_domain_merchants_disable
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/merchants/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_merchants_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_merchants_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::disableDomainMerchantsAction',));
                }
                not_api_domain_merchants_disable:

                // api_domain_set_outside_payway
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/outside/payway$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_set_outside_payway;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_set_outside_payway')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\DomainController::setDomainOutsidePaywayAction',));
                }
                not_api_domain_set_outside_payway:

            }

            if (0 === strpos($pathinfo, '/api/exchange')) {
                // api_exchange_create
                if ($pathinfo === '/api/exchange') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_exchange_create;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::createAction',  '_route' => 'api_exchange_create',);
                }
                not_api_exchange_create:

                // api_exchange_remove
                if (preg_match('#^/api/exchange/(?P<exchangeId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_exchange_remove;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_exchange_remove')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::removeAction',));
                }
                not_api_exchange_remove:

                // api_exchange_edit
                if (preg_match('#^/api/exchange/(?P<exchangeId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_exchange_edit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_exchange_edit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::editAction',));
                }
                not_api_exchange_edit:

                // api_exchange_get
                if (preg_match('#^/api/exchange/(?P<exchangeId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_exchange_get;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_exchange_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::getAction',));
                }
                not_api_exchange_get:

            }

            if (0 === strpos($pathinfo, '/api/currency')) {
                // api_exchange_get_by_currency
                if (preg_match('#^/api/currency/(?P<currency>[A-Z]+)/exchange$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_exchange_get_by_currency;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_exchange_get_by_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::getByCurrencyAction',));
                }
                not_api_exchange_get_by_currency:

                // api_currency_exchange_list
                if (preg_match('#^/api/currency/(?P<currency>[A-Z]+)/exchange/list$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_currency_exchange_list;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_currency_exchange_list')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::listAction',));
                }
                not_api_currency_exchange_list:

                // api_currency
                if ($pathinfo === '/api/currency') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_currency;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::getCurrencyAction',  '_route' => 'api_currency',);
                }
                not_api_currency:

                // api_currency_exchange
                if ($pathinfo === '/api/currency/exchange') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_currency_exchange;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::getCurrencyExchangeAction',  '_route' => 'api_currency_exchange',);
                }
                not_api_currency_exchange:

            }

            if (0 === strpos($pathinfo, '/api/exchange')) {
                // api_exchange_convert
                if ($pathinfo === '/api/exchange/convert') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_exchange_convert;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::convertAction',  '_route' => 'api_exchange_convert',);
                }
                not_api_exchange_convert:

                // api_exchange_edit_by_currency_active_at
                if ($pathinfo === '/api/exchange') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_exchange_edit_by_currency_active_at;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExchangeController::editByCurrencyAction',  '_route' => 'api_exchange_edit_by_currency_active_at',);
                }
                not_api_exchange_edit_by_currency_active_at:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_create_external_user
                if (preg_match('#^/api/user/(?P<userId>\\d+)/external$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_external_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_external_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::createUserAction',));
                }
                not_api_create_external_user:

                // api_get_external_balance
                if (preg_match('#^/api/user/(?P<userId>\\d+)/external/balance$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_external_balance;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_external_balance')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getBalanceAction',));
                }
                not_api_get_external_balance:

                // api_external_transfer
                if (preg_match('#^/api/user/(?P<userId>\\d+)/external/transfer$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_external_transfer;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_external_transfer')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::transferAction',));
                }
                not_api_external_transfer:

                // api_get_external_entry_balance
                if (preg_match('#^/api/user/(?P<userId>\\d+)/external/entry/balance$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_external_entry_balance;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_external_entry_balance')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getEntryBalanceAction',));
                }
                not_api_get_external_entry_balance:

                // api_external_manual_transfer
                if (preg_match('#^/api/user/(?P<userId>\\d+)/external/manual_transfer$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_external_manual_transfer;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_external_manual_transfer')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::manualTransferAction',));
                }
                not_api_external_manual_transfer:

                // api_external_recycle_balance
                if (preg_match('#^/api/user/(?P<userId>\\d+)/external/recycle_balance$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_external_recycle_balance;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_external_recycle_balance')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::recycleBalanceAction',));
                }
                not_api_external_recycle_balance:

            }

            // api_get_external_lost_balance
            if ($pathinfo === '/api/external/lost_balance') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_external_lost_balance;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getLostBalanceAction',  '_route' => 'api_get_external_lost_balance',);
            }
            not_api_get_external_lost_balance:

            // api_update_external_lost_balance
            if (0 === strpos($pathinfo, '/api/transaction') && preg_match('#^/api/transaction/(?P<transId>\\d+)/external/lost_balance$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_update_external_lost_balance;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_update_external_lost_balance')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::updateLostBalanceAction',));
            }
            not_api_update_external_lost_balance:

            // api_get_external_password
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/external/password$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_external_password;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_external_password')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getPasswordAction',));
            }
            not_api_get_external_password:

            // api_get_external_owner_transfer_status
            if ($pathinfo === '/api/owner/external/transfer/status') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_external_owner_transfer_status;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getApiOwnerTransferStatusAction',  '_route' => 'api_get_external_owner_transfer_status',);
            }
            not_api_get_external_owner_transfer_status:

            // api_get_external_transfer_status
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/external/transfer/status$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_external_transfer_status;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_external_transfer_status')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getTransferStatusAction',));
            }
            not_api_get_external_transfer_status:

            // api_get_relative_name
            if ($pathinfo === '/api/external/relative_name') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_relative_name;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getRelativeNameAction',  '_route' => 'api_get_relative_name',);
            }
            not_api_get_relative_name:

            // api_external_transfer_record
            if (0 === strpos($pathinfo, '/api/transaction') && preg_match('#^/api/transaction/(?P<transId>\\d+)/external/transfer_record$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_external_transfer_record;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_external_transfer_record')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::checkTransferRecordAction',));
            }
            not_api_external_transfer_record:

            // api_external_reset_password
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/external/reset_password$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_external_reset_password;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_external_reset_password')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::resetPasswordAction',));
            }
            not_api_external_reset_password:

            if (0 === strpos($pathinfo, '/api/external')) {
                // api_get_balance_stat
                if ($pathinfo === '/api/external/balance_stat') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_balance_stat;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getBalanceStatAction',  '_route' => 'api_get_balance_stat',);
                }
                not_api_get_balance_stat:

                // api_get_game_list
                if ($pathinfo === '/api/external/game_list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_game_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getGameListAction',  '_route' => 'api_get_game_list',);
                }
                not_api_get_game_list:

                // api_connection_test
                if ($pathinfo === '/api/external/connection_test') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_connection_test;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::connectionTestAction',  '_route' => 'api_connection_test',);
                }
                not_api_connection_test:

                // api_get_upper_balance
                if ($pathinfo === '/api/external/upper_balance') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_upper_balance;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::getUpperBalanceAction',  '_route' => 'api_get_upper_balance',);
                }
                not_api_get_upper_balance:

            }

            // api_external_recycle_balance_async
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/external/recycle_balance_async$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_external_recycle_balance_async;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_external_recycle_balance_async')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ExternalController::recycleBalanceAsyncAction',));
            }
            not_api_external_recycle_balance_async:

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_domain_free_transfer_wallet_enable
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/free_transfer_wallet/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_free_transfer_wallet_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_free_transfer_wallet_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::enableFreeTransferWalletAction',));
                }
                not_api_domain_free_transfer_wallet_enable:

                // api_domain_free_transfer_wallet_disable
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/free_transfer_wallet/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_free_transfer_wallet_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_free_transfer_wallet_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::disableFreeTransferWalletAction',));
                }
                not_api_domain_free_transfer_wallet_disable:

                // api_set_domain_wallet_status
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/free_transfer_wallet/status$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_domain_wallet_status;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_domain_wallet_status')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::setDomainWalletAction',));
                }
                not_api_set_domain_wallet_status:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_get_user_free_transfer_wallet
                if (preg_match('#^/api/user/(?P<userId>\\d+)/free_transfer_wallet$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_free_transfer_wallet;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_free_transfer_wallet')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::getUserLastGameAction',));
                }
                not_api_get_user_free_transfer_wallet:

                // api_user_free_transfer_wallet_enable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/free_transfer_wallet/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_free_transfer_wallet_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_free_transfer_wallet_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::enableUserLastGameAction',));
                }
                not_api_user_free_transfer_wallet_enable:

                // api_user_free_transfer_wallet_disable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/free_transfer_wallet/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_free_transfer_wallet_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_free_transfer_wallet_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::disableUserLastGameAction',));
                }
                not_api_user_free_transfer_wallet_disable:

                // api_set_user_last_game_code
                if (preg_match('#^/api/user/(?P<userId>\\d+)/last_game_code$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_user_last_game_code;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_user_last_game_code')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::setUserLastGameCode',));
                }
                not_api_set_user_last_game_code:

                // api_free_transfer_wallet
                if (preg_match('#^/api/user/(?P<userId>\\d+)/free_transfer_wallet$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_free_transfer_wallet;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_free_transfer_wallet')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::freeTransferWalletAction',));
                }
                not_api_free_transfer_wallet:

                // api_free_transfer_wallet_recycle
                if (preg_match('#^/api/user/(?P<userId>\\d+)/free_transfer_wallet/recycle$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_free_transfer_wallet_recycle;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_free_transfer_wallet_recycle')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\FreeTransferWalletController::freeTransferWalletRecycleAction',));
                }
                not_api_free_transfer_wallet_recycle:

            }

            if (0 === strpos($pathinfo, '/api/geoip')) {
                if (0 === strpos($pathinfo, '/api/geoip/country')) {
                    // api_geoip_country
                    if (preg_match('#^/api/geoip/country/(?P<countryId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_geoip_country;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_geoip_country')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::getGeoipCountryAction',));
                    }
                    not_api_geoip_country:

                    // bb_durian_geoip_getgeoipcountry
                    if ($pathinfo === '/api/geoip/country') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_bb_durian_geoip_getgeoipcountry;
                        }

                        return array (  '_format' => 'json',  'countryId' => NULL,  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::getGeoipCountryAction',  '_route' => 'bb_durian_geoip_getgeoipcountry',);
                    }
                    not_bb_durian_geoip_getgeoipcountry:

                }

                // api_geoip_region
                if (0 === strpos($pathinfo, '/api/geoip/region') && preg_match('#^/api/geoip/region/(?P<regionId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_geoip_region;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_geoip_region')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::getGeoipRegionAction',));
                }
                not_api_geoip_region:

                if (0 === strpos($pathinfo, '/api/geoip/c')) {
                    // api_geoip_region_list
                    if (0 === strpos($pathinfo, '/api/geoip/country') && preg_match('#^/api/geoip/country/(?P<countryId>\\d+)/region$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_geoip_region_list;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_geoip_region_list')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::getGeoipRegionsByCountryAction',));
                    }
                    not_api_geoip_region_list:

                    // api_geoip_city
                    if (0 === strpos($pathinfo, '/api/geoip/city') && preg_match('#^/api/geoip/city/(?P<cityId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_geoip_city;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_geoip_city')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::getGeoipCityAction',));
                    }
                    not_api_geoip_city:

                }

                // api_geoip_city_list
                if (0 === strpos($pathinfo, '/api/geoip/region') && preg_match('#^/api/geoip/region/(?P<regionId>\\d+)/city$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_geoip_city_list;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_geoip_city_list')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::getGeoipCitiesByRegionAction',));
                }
                not_api_geoip_city_list:

                // api_geoip_country_set
                if (0 === strpos($pathinfo, '/api/geoip/country') && preg_match('#^/api/geoip/country/(?P<countryId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_geoip_country_set;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_geoip_country_set')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::setCountryNameAction',));
                }
                not_api_geoip_country_set:

                // api_geoip_region_set
                if (0 === strpos($pathinfo, '/api/geoip/region') && preg_match('#^/api/geoip/region/(?P<regionId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_geoip_region_set;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_geoip_region_set')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::setRegionNameAction',));
                }
                not_api_geoip_region_set:

                // api_geoip_city_set
                if (0 === strpos($pathinfo, '/api/geoip/city') && preg_match('#^/api/geoip/city/(?P<cityId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_geoip_city_set;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_geoip_city_set')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\GeoipController::setCityNameAction',));
                }
                not_api_geoip_city_set:

            }

            if (0 === strpos($pathinfo, '/api/ip_blocker')) {
                // api_check_ip
                if ($pathinfo === '/api/ip_blocker/check/ip') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_ip;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\IpBlockerController::checkIpAction',  '_route' => 'api_check_ip',);
                }
                not_api_check_ip:

                // api_delete_domain_whitelist_ip
                if (0 === strpos($pathinfo, '/api/ip_blocker/white_list/domain') && preg_match('#^/api/ip_blocker/white_list/domain/(?P<id>\\d+)/ip$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_delete_domain_whitelist_ip;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_delete_domain_whitelist_ip')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\IpBlockerController::deleteDomainWhitelistIpAction',));
                }
                not_api_delete_domain_whitelist_ip:

                // api_get_countries_by_ip
                if ($pathinfo === '/api/ip_blocker/geoip/countries_by_ip') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_countries_by_ip;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\IpBlockerController::getCountriesByIpAction',  '_route' => 'api_get_countries_by_ip',);
                }
                not_api_get_countries_by_ip:

            }

            if (0 === strpos($pathinfo, '/api/level')) {
                // api_create_level
                if ($pathinfo === '/api/level') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_level;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::createAction',  '_route' => 'api_create_level',);
                }
                not_api_create_level:

                // api_set_level
                if (preg_match('#^/api/level/(?P<levelId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::setAction',));
                }
                not_api_set_level:

                // api_get_level
                if (preg_match('#^/api/level/(?P<levelId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::getAction',));
                }
                not_api_get_level:

                // api_level_list
                if ($pathinfo === '/api/level/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_level_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::listAction',  '_route' => 'api_level_list',);
                }
                not_api_level_list:

                // api_remove_level
                if (preg_match('#^/api/level/(?P<levelId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::removeAction',));
                }
                not_api_remove_level:

                if (0 === strpos($pathinfo, '/api/level/transfer')) {
                    // api_level_transfer_list
                    if ($pathinfo === '/api/level/transfer/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_level_transfer_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::transferListAction',  '_route' => 'api_level_transfer_list',);
                    }
                    not_api_level_transfer_list:

                    // api_level_transfer
                    if ($pathinfo === '/api/level/transfer') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_level_transfer;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::transferAction',  '_route' => 'api_level_transfer',);
                    }
                    not_api_level_transfer:

                }

                if (0 === strpos($pathinfo, '/api/level_url')) {
                    // api_create_level_url
                    if ($pathinfo === '/api/level_url') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_create_level_url;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::createUrlAction',  '_route' => 'api_create_level_url',);
                    }
                    not_api_create_level_url:

                    // api_level_url_list
                    if ($pathinfo === '/api/level_url/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_level_url_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::urlListAction',  '_route' => 'api_level_url_list',);
                    }
                    not_api_level_url_list:

                    // api_set_level_url
                    if (preg_match('#^/api/level_url/(?P<levelUrlId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_level_url;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_level_url')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::setUrlAction',));
                    }
                    not_api_set_level_url:

                    // api_remove_level_url
                    if (preg_match('#^/api/level_url/(?P<levelUrlId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_remove_level_url;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_level_url')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::removeUrlAction',));
                    }
                    not_api_remove_level_url:

                }

                // api_set_level_order
                if ($pathinfo === '/api/level/order') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_level_order;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::setOrderAction',  '_route' => 'api_set_level_order',);
                }
                not_api_set_level_order:

                // api_get_level_users
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/users$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_level_users;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_level_users')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::getUsersAction',));
                }
                not_api_get_level_users:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_create_preset_level
                if (preg_match('#^/api/user/(?P<userId>\\d+)/preset_level$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_preset_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_preset_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::createPresetAction',));
                }
                not_api_create_preset_level:

                // api_remove_preset_level
                if (preg_match('#^/api/user/(?P<userId>\\d+)/preset_level$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_preset_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_preset_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::removePresetAction',));
                }
                not_api_remove_preset_level:

            }

            if (0 === strpos($pathinfo, '/api/l')) {
                if (0 === strpos($pathinfo, '/api/level')) {
                    // api_set_level_currency
                    if (preg_match('#^/api/level/(?P<levelId>\\d+)/currency$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_level_currency;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_level_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::setCurrencyAction',));
                    }
                    not_api_set_level_currency:

                    // api_get_level_currency
                    if (preg_match('#^/api/level/(?P<levelId>\\d+)/currency$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_level_currency;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_level_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LevelController::getCurrencyAction',));
                    }
                    not_api_get_level_currency:

                }

                if (0 === strpos($pathinfo, '/api/login')) {
                    // api_login_log_same_ip
                    if ($pathinfo === '/api/login_log/same_ip') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_login_log_same_ip;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LoginController::getSameIpAction',  '_route' => 'api_login_log_same_ip',);
                    }
                    not_api_login_log_same_ip:

                    // api_login
                    if ($pathinfo === '/api/login') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_login;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LoginController::loginAction',  '_route' => 'api_login',);
                    }
                    not_api_login:

                }

            }

            // api_oauth_login
            if ($pathinfo === '/api/oauth/login') {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_oauth_login;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LoginController::oauthLoginAction',  '_route' => 'api_oauth_login',);
            }
            not_api_oauth_login:

            if (0 === strpos($pathinfo, '/api/log')) {
                // api_logout
                if ($pathinfo === '/api/logout') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_logout;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LoginController::logoutAction',  '_route' => 'api_logout',);
                }
                not_api_logout:

                // api_login_log_list
                if ($pathinfo === '/api/login_log/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_login_log_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LoginController::getLogListAction',  '_route' => 'api_login_log_list',);
                }
                not_api_login_log_list:

            }

            // api_get_last_login
            if ($pathinfo === '/api/user/last_login') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_last_login;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LoginController::getLastLoginByUsernameAction',  '_route' => 'api_get_last_login',);
            }
            not_api_get_last_login:

            // api_login_log_list_by_ip_parent
            if ($pathinfo === '/api/login_log/list_by_ip_parent') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_login_log_list_by_ip_parent;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\LoginController::getLogListByIpParentAction',  '_route' => 'api_login_log_list_by_ip_parent',);
            }
            not_api_login_log_list_by_ip_parent:

            if (0 === strpos($pathinfo, '/api/maintain')) {
                if (0 === strpos($pathinfo, '/api/maintain/g')) {
                    // api_get_illegal_tester
                    if ($pathinfo === '/api/maintain/get_illegal_tester') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_illegal_tester;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MaintainController::getIllegalTestUserAction',  '_route' => 'api_get_illegal_tester',);
                    }
                    not_api_get_illegal_tester:

                    if (0 === strpos($pathinfo, '/api/maintain/game')) {
                        // api_set_maintain_by_game
                        if (preg_match('#^/api/maintain/game/(?P<code>\\d+)$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_set_maintain_by_game;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_maintain_by_game')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MaintainController::setMaintainByGameAction',));
                        }
                        not_api_set_maintain_by_game:

                        // api_get_maintain_by_game
                        if (preg_match('#^/api/maintain/game/(?P<code>\\d+)$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_get_maintain_by_game;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_maintain_by_game')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MaintainController::getMaintainByGameAction',));
                        }
                        not_api_get_maintain_by_game:

                        // api_get_maintain_game_list
                        if ($pathinfo === '/api/maintain/game_list') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_get_maintain_game_list;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MaintainController::getMaintainGameList',  '_route' => 'api_get_maintain_game_list',);
                        }
                        not_api_get_maintain_game_list:

                    }

                }

                if (0 === strpos($pathinfo, '/api/maintain/whitelist')) {
                    // api_create_maintain_whitelist
                    if ($pathinfo === '/api/maintain/whitelist') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_create_maintain_whitelist;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MaintainController::createWhitelistAction',  '_route' => 'api_create_maintain_whitelist',);
                    }
                    not_api_create_maintain_whitelist:

                    // api_delete_maintain_whitelist
                    if ($pathinfo === '/api/maintain/whitelist') {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_delete_maintain_whitelist;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MaintainController::deleteWhitelistAction',  '_route' => 'api_delete_maintain_whitelist',);
                    }
                    not_api_delete_maintain_whitelist:

                    // api_get_maintain_whitelist
                    if ($pathinfo === '/api/maintain/whitelist') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_maintain_whitelist;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MaintainController::getWhitelistAction',  '_route' => 'api_get_maintain_whitelist',);
                    }
                    not_api_get_maintain_whitelist:

                }

            }

            // api_manual
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/manual$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_manual;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_manual')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ManualController::manualAction',));
            }
            not_api_manual:

            if (0 === strpos($pathinfo, '/api/merchant_card')) {
                // api_create_merchant_card
                if ($pathinfo === '/api/merchant_card') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_merchant_card;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::createAction',  '_route' => 'api_create_merchant_card',);
                }
                not_api_create_merchant_card:

                // api_get_merchant_card
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_card;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_card')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::getAction',));
                }
                not_api_get_merchant_card:

                // api_set_merchant_card
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_card;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_card')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::setAction',));
                }
                not_api_set_merchant_card:

                // api_remove_merchant_card
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_card;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_card')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::removeAction',));
                }
                not_api_remove_merchant_card:

                // api_merchant_card_list
                if ($pathinfo === '/api/merchant_card/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_card_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::listAction',  '_route' => 'api_merchant_card_list',);
                }
                not_api_merchant_card_list:

                // api_merchant_card_disable
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_card_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::disableAction',));
                }
                not_api_merchant_card_disable:

                // api_merchant_card_enable
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_card_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::enableAction',));
                }
                not_api_merchant_card_enable:

                // api_merchant_card_suspend
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/suspend$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_card_suspend;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_suspend')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::suspendAction',));
                }
                not_api_merchant_card_suspend:

                // api_merchant_card_resume
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/resume$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_card_resume;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_resume')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::resumeAction',));
                }
                not_api_merchant_card_resume:

                // api_merchant_card_approve
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/approve$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_card_approve;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_approve')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::approveAction',));
                }
                not_api_merchant_card_approve:

                // api_merchant_card_get_payment_method
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/payment_method$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_card_get_payment_method;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_get_payment_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::getPaymentMethodAction',));
                }
                not_api_merchant_card_get_payment_method:

                // api_merchant_card_set_payment_method
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/payment_method$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_card_set_payment_method;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_set_payment_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::setPaymentMethodAction',));
                }
                not_api_merchant_card_set_payment_method:

                // api_merchant_card_get_payment_vendor
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/payment_vendor$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_card_get_payment_vendor;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_get_payment_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::getPaymentVendorAction',));
                }
                not_api_merchant_card_get_payment_vendor:

                // api_merchant_card_set_payment_vendor
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/payment_vendor$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_card_set_payment_vendor;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_set_payment_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::setPaymentVendorAction',));
                }
                not_api_merchant_card_set_payment_vendor:

                // api_get_merchant_card_order
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/order$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_card_order;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_card_order')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::getOrderAction',));
                }
                not_api_get_merchant_card_order:

                // api_set_merchant_card_order
                if ($pathinfo === '/api/merchant_card/order') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_card_order;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::setOrderAction',  '_route' => 'api_set_merchant_card_order',);
                }
                not_api_set_merchant_card_order:

                // api_set_merchant_card_key
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/key$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_card_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_card_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::setKeyAction',));
                }
                not_api_set_merchant_card_key:

                // api_remove_merchant_card_key
                if (0 === strpos($pathinfo, '/api/merchant_card/key') && preg_match('#^/api/merchant_card/key/(?P<keyId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_card_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_card_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::removeKeyAction',));
                }
                not_api_remove_merchant_card_key:

                // api_merchant_card_set_private_key
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/private_key$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_card_set_private_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_card_set_private_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::setPrivateKeyAction',));
                }
                not_api_merchant_card_set_private_key:

                // api_get_merchant_card_extra
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/extra$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_card_extra;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_card_extra')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::getExtraAction',));
                }
                not_api_get_merchant_card_extra:

                // api_set_merchant_card_extra
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/extra$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_card_extra;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_card_extra')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::setExtraAction',));
                }
                not_api_set_merchant_card_extra:

                // api_set_merchant_card_bank_limit
                if (preg_match('#^/api/merchant_card/(?P<merchantCardId>\\d+)/bank_limit$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_set_merchant_card_bank_limit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_card_bank_limit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::setBankLimitAction',));
                }
                not_api_set_merchant_card_bank_limit:

                // api_merchant_card_bank_limit_list
                if ($pathinfo === '/api/merchant_card/bank_limit/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_card_bank_limit_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::listBankLimitAction',  '_route' => 'api_merchant_card_bank_limit_list',);
                }
                not_api_merchant_card_bank_limit_list:

            }

            // api_get_merchant_card_record
            if (0 === strpos($pathinfo, '/api/domain') && preg_match('#^/api/domain/(?P<domain>\\d+)/merchant_card_record$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_merchant_card_record;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_card_record')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantCardController::getRecordAction',));
            }
            not_api_get_merchant_card_record:

            if (0 === strpos($pathinfo, '/api/merchant')) {
                // api_create_merchant
                if ($pathinfo === '/api/merchant') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_merchant;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::createAction',  '_route' => 'api_create_merchant',);
                }
                not_api_create_merchant:

                // api_get_merchant
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::getAction',));
                }
                not_api_get_merchant:

                // api_edit_merchant
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_edit_merchant;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_edit_merchant')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::setAction',));
                }
                not_api_edit_merchant:

                // api_remove_merchant
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::removeAction',));
                }
                not_api_remove_merchant:

                if (0 === strpos($pathinfo, '/api/merchant/list')) {
                    // api_merchant_list
                    if ($pathinfo === '/api/merchant/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_merchant_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::listAction',  '_route' => 'api_merchant_list',);
                    }
                    not_api_merchant_list:

                    // api_merchant_list_by_web_url
                    if ($pathinfo === '/api/merchant/list_by_web_url') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_merchant_list_by_web_url;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::listByWebUrlAction',  '_route' => 'api_merchant_list_by_web_url',);
                    }
                    not_api_merchant_list_by_web_url:

                }

                // api_set_merchant_bank_limit
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/bank_limit$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_set_merchant_bank_limit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_bank_limit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::setMerchantBankLimitAction',));
                }
                not_api_set_merchant_bank_limit:

                // api_merchant_disable
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::disableAction',));
                }
                not_api_merchant_disable:

                // api_merchant_enable
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::enableAction',));
                }
                not_api_merchant_enable:

                // api_merchant_suspend
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/suspend$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_suspend;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_suspend')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::suspendAction',));
                }
                not_api_merchant_suspend:

                // api_merchant_resume
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/resume$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_resume;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_resume')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::resumeAction',));
                }
                not_api_merchant_resume:

                // api_get_merchant_extra
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/extra$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_extra;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_extra')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::getMerchantExtraAction',));
                }
                not_api_get_merchant_extra:

                // api_set_merchant_extra
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/merchant_extra$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_extra;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_extra')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::setMerchantExtraAction',));
                }
                not_api_set_merchant_extra:

                // api_merchant_bank_limit_list
                if ($pathinfo === '/api/merchant/bank_limit_list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_bank_limit_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::getMerchantBankLimitListAction',  '_route' => 'api_merchant_bank_limit_list',);
                }
                not_api_merchant_bank_limit_list:

                // api_get_merchant_ip_strategy
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/ip_strategy$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_ip_strategy;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_ip_strategy')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::getIpStrategyAction',));
                }
                not_api_get_merchant_ip_strategy:

                // api_create_merchant_ip_strategy
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/ip_strategy$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_merchant_ip_strategy;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_merchant_ip_strategy')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::addIpStrategyAction',));
                }
                not_api_create_merchant_ip_strategy:

                // api_remove_merchant_ip_strategy
                if (0 === strpos($pathinfo, '/api/merchant/ip_strategy') && preg_match('#^/api/merchant/ip_strategy/(?P<strategyId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_ip_strategy;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_ip_strategy')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::removeIpStrategyAction',));
                }
                not_api_remove_merchant_ip_strategy:

            }

            // api_get_merchant_record
            if (0 === strpos($pathinfo, '/api/domain') && preg_match('#^/api/domain/(?P<domain>\\d+)/merchant_record$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_merchant_record;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_record')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::getMerchantRecordAction',));
            }
            not_api_get_merchant_record:

            if (0 === strpos($pathinfo, '/api/merchant')) {
                // api_check_merchant_ip_limit
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/check_ip_limit$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_merchant_ip_limit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_check_merchant_ip_limit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::checkMerchantIpLimitAction',));
                }
                not_api_check_merchant_ip_limit:

                // api_merchant_approve
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/approve$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_approve;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_approve')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::approveAction',));
                }
                not_api_merchant_approve:

                // api_set_merchant_key
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/key$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::setKeyAction',));
                }
                not_api_set_merchant_key:

                // api_remove_merchant_key
                if (0 === strpos($pathinfo, '/api/merchant/key') && preg_match('#^/api/merchant/key/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::removeMerchantKeyAction',));
                }
                not_api_remove_merchant_key:

                // api_merchant_set_private_key
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/private_key$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_set_private_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_set_private_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::setPrivateKeyAction',));
                }
                not_api_merchant_set_private_key:

                // api_merchant_shop_url_check_connection
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/shop_url/check_connection$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_shop_url_check_connection;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_shop_url_check_connection')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::shopUrlCheckConnectionAction',));
                }
                not_api_merchant_shop_url_check_connection:

                // api_merchant_shop_url_check_ip
                if ($pathinfo === '/api/merchant/shop_url/check_ip') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_shop_url_check_ip;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::shopUrlCheckIpAction',  '_route' => 'api_merchant_shop_url_check_ip',);
                }
                not_api_merchant_shop_url_check_ip:

                if (0 === strpos($pathinfo, '/api/merchant/whitelist_')) {
                    // api_merchant_whitelist_update
                    if ($pathinfo === '/api/merchant/whitelist_update') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_merchant_whitelist_update;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::whitelistUpdateAction',  '_route' => 'api_merchant_whitelist_update',);
                    }
                    not_api_merchant_whitelist_update:

                    // api_merchant_whitelist_reset
                    if ($pathinfo === '/api/merchant/whitelist_reset') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_merchant_whitelist_reset;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantController::whitelistResetAction',  '_route' => 'api_merchant_whitelist_reset',);
                    }
                    not_api_merchant_whitelist_reset:

                }

                // api_get_merchant_level
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/level/(?P<levelId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::getAction',));
                }
                not_api_get_merchant_level:

                // api_get_merchant_level_list
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/level/list$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_level_list;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_level_list')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::listAction',));
                }
                not_api_get_merchant_level_list:

            }

            // api_get_merchant_level_by_level
            if (0 === strpos($pathinfo, '/api/level') && preg_match('#^/api/level/(?P<levelId>\\d+)/merchant_level$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_merchant_level_by_level;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_level_by_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::getByLevelAction',));
            }
            not_api_get_merchant_level_by_level:

            // api_set_merchant_level
            if (0 === strpos($pathinfo, '/api/merchant') && preg_match('#^/api/merchant/(?P<merchantId>\\d+)/level$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_set_merchant_level;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::setAction',));
            }
            not_api_set_merchant_level:

            if (0 === strpos($pathinfo, '/api/level')) {
                // api_set_merchant_level_by_level
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/merchant$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_level_by_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_level_by_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::setByLevelAction',));
                }
                not_api_set_merchant_level_by_level:

                // api_set_merchant_level_order
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/merchant/order$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_level_order;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_level_order')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::setOrderAction',));
                }
                not_api_set_merchant_level_order:

            }

            if (0 === strpos($pathinfo, '/api/merchant')) {
                // api_set_merchant_level_method
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/level/payment_method$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_level_method;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_level_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::setMerchantLevelMethodAction',));
                }
                not_api_set_merchant_level_method:

                // api_remove_merchant_level_method
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/level/payment_method$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_level_method;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_level_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::removeMerchantLevelMethodAction',));
                }
                not_api_remove_merchant_level_method:

                // api_set_merchant_level_vendor
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/level/payment_vendor$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_level_vendor;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_level_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::setMerchantLevelVendorAction',));
                }
                not_api_set_merchant_level_vendor:

                // api_remove_merchant_level_vendor
                if (preg_match('#^/api/merchant/(?P<merchantId>\\d+)/level/payment_vendor$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_level_vendor;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_level_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::removeMerchantLevelVendorAction',));
                }
                not_api_remove_merchant_level_vendor:

            }

            if (0 === strpos($pathinfo, '/api/level/payment_')) {
                // api_get_merchant_level_method
                if ($pathinfo === '/api/level/payment_method') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_level_method;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::getMerchantLevelMethodAction',  '_route' => 'api_get_merchant_level_method',);
                }
                not_api_get_merchant_level_method:

                // api_get_merchant_level_vendor
                if ($pathinfo === '/api/level/payment_vendor') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_level_vendor;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantLevelController::getMerchantLevelVendorAction',  '_route' => 'api_get_merchant_level_vendor',);
                }
                not_api_get_merchant_level_vendor:

            }

            if (0 === strpos($pathinfo, '/api/merchant/withdraw')) {
                // api_get_merchant_withdraw
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_withdraw;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_withdraw')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::getAction',));
                }
                not_api_get_merchant_withdraw:

                // api_remove_merchant_withdraw
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_withdraw;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_withdraw')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::removeAction',));
                }
                not_api_remove_merchant_withdraw:

                // api_merchant_withdraw_disable
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_withdraw_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_withdraw_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::disableAction',));
                }
                not_api_merchant_withdraw_disable:

                // api_edit_merchant_withdraw
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_edit_merchant_withdraw;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_edit_merchant_withdraw')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::setAction',));
                }
                not_api_edit_merchant_withdraw:

                // api_set_merchant_withdraw_key
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/key$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_withdraw_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_withdraw_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::setKeyAction',));
                }
                not_api_set_merchant_withdraw_key:

                // api_remove_merchant_withdraw_key
                if (0 === strpos($pathinfo, '/api/merchant/withdraw/key') && preg_match('#^/api/merchant/withdraw/key/(?P<keyId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_withdraw_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_withdraw_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::removeKeyAction',));
                }
                not_api_remove_merchant_withdraw_key:

                // api_merchant_withdraw_set_private_key
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/private_key$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_withdraw_set_private_key;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_withdraw_set_private_key')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::setPrivateKeyAction',));
                }
                not_api_merchant_withdraw_set_private_key:

                // api_merchant_withdraw_resume
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/resume$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_withdraw_resume;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_withdraw_resume')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::resumeAction',));
                }
                not_api_merchant_withdraw_resume:

                // api_merchant_withdraw_enable
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_withdraw_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_withdraw_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::enableAction',));
                }
                not_api_merchant_withdraw_enable:

                // api_merchant_withdraw_list
                if ($pathinfo === '/api/merchant/withdraw/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_withdraw_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::listAction',  '_route' => 'api_merchant_withdraw_list',);
                }
                not_api_merchant_withdraw_list:

                // api_get_merchant_withdraw_extra
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/extra$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_withdraw_extra;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_withdraw_extra')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::getExtraAction',));
                }
                not_api_get_merchant_withdraw_extra:

                // api_set_merchant_withdraw_extra
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/extra$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_withdraw_extra;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_withdraw_extra')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::setExtraAction',));
                }
                not_api_set_merchant_withdraw_extra:

                // api_merchant_withdraw_bank_limit_list
                if ($pathinfo === '/api/merchant/withdraw/bank_limit_list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_merchant_withdraw_bank_limit_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::getBankLimitListAction',  '_route' => 'api_merchant_withdraw_bank_limit_list',);
                }
                not_api_merchant_withdraw_bank_limit_list:

                // api_get_merchant_withdraw_record
                if ($pathinfo === '/api/merchant/withdraw/record') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_withdraw_record;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::getRecordAction',  '_route' => 'api_get_merchant_withdraw_record',);
                }
                not_api_get_merchant_withdraw_record:

                // api_merchant_withdraw_approve
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/approve$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_withdraw_approve;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_withdraw_approve')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::approveAction',));
                }
                not_api_merchant_withdraw_approve:

                // api_create_merchant_withdraw
                if ($pathinfo === '/api/merchant/withdraw') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_merchant_withdraw;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::createAction',  '_route' => 'api_create_merchant_withdraw',);
                }
                not_api_create_merchant_withdraw:

                // api_set_merchant_withdraw_bank_limit
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/bank_limit$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_set_merchant_withdraw_bank_limit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_withdraw_bank_limit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::setMerchantWithdrawBankLimitAction',));
                }
                not_api_set_merchant_withdraw_bank_limit:

                // api_merchant_withdraw_suspend
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/suspend$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_merchant_withdraw_suspend;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_merchant_withdraw_suspend')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::suspendAction',));
                }
                not_api_merchant_withdraw_suspend:

                // api_create_merchant_withdraw_ip_strategy
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/ip_strategy$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_merchant_withdraw_ip_strategy;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_merchant_withdraw_ip_strategy')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::addIpStrategyAction',));
                }
                not_api_create_merchant_withdraw_ip_strategy:

                // api_get_merchant_withdraw_ip_strategy
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/ip_strategy$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_withdraw_ip_strategy;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_withdraw_ip_strategy')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::getIpStrategyAction',));
                }
                not_api_get_merchant_withdraw_ip_strategy:

                // api_remove_merchant_withdraw_ip_strategy
                if (0 === strpos($pathinfo, '/api/merchant/withdraw/ip_strategy') && preg_match('#^/api/merchant/withdraw/ip_strategy/(?P<strategyId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_withdraw_ip_strategy;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_withdraw_ip_strategy')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::removeIpStrategyAction',));
                }
                not_api_remove_merchant_withdraw_ip_strategy:

                // api_check_merchant_withdraw_ip_limit
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/check_ip_limit$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_check_merchant_withdraw_ip_limit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_check_merchant_withdraw_ip_limit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawController::checkIpLimitAction',));
                }
                not_api_check_merchant_withdraw_ip_limit:

                // api_get_merchant_withdraw_level
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/level/(?P<levelId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_withdraw_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_withdraw_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::getAction',));
                }
                not_api_get_merchant_withdraw_level:

                // api_get_merchant_withdraw_level_list
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/level/list$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_withdraw_level_list;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_withdraw_level_list')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::listAction',));
                }
                not_api_get_merchant_withdraw_level_list:

            }

            // api_get_merchant_withdraw_level_by_level
            if (0 === strpos($pathinfo, '/api/level') && preg_match('#^/api/level/(?P<levelId>\\d+)/merchant/withdraw/level$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_merchant_withdraw_level_by_level;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_merchant_withdraw_level_by_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::getByLevelAction',));
            }
            not_api_get_merchant_withdraw_level_by_level:

            // api_set_merchant_withdraw_level
            if (0 === strpos($pathinfo, '/api/merchant/withdraw') && preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/level$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_set_merchant_withdraw_level;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_withdraw_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::setAction',));
            }
            not_api_set_merchant_withdraw_level:

            if (0 === strpos($pathinfo, '/api/level')) {
                // api_set_merchant_withdraw_level_by_level
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/merchant/withdraw/level$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_withdraw_level_by_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_withdraw_level_by_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::setByLevelAction',));
                }
                not_api_set_merchant_withdraw_level_by_level:

                // api_set_merchant_withdraw_level_order
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/merchant/withdraw/order$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_withdraw_level_order;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_withdraw_level_order')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::setOrderAction',));
                }
                not_api_set_merchant_withdraw_level_order:

            }

            if (0 === strpos($pathinfo, '/api/merchant/withdraw')) {
                // api_get_merchant_withdraw_level_bank_info
                if ($pathinfo === '/api/merchant/withdraw/level/bank_info') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_merchant_withdraw_level_bank_info;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::getBankInfoAction',  '_route' => 'api_get_merchant_withdraw_level_bank_info',);
                }
                not_api_get_merchant_withdraw_level_bank_info:

                // api_set_merchant_withdraw_level_bank_info
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/level/bank_info$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_merchant_withdraw_level_bank_info;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_merchant_withdraw_level_bank_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::setBankInfoAction',));
                }
                not_api_set_merchant_withdraw_level_bank_info:

                // api_remove_merchant_withdraw_level_bank_info
                if (preg_match('#^/api/merchant/withdraw/(?P<merchantWithdrawId>\\d+)/level/bank_info$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_merchant_withdraw_level_bank_info;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_merchant_withdraw_level_bank_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\MerchantWithdrawLevelController::removeBankInfoAction',));
                }
                not_api_remove_merchant_withdraw_level_bank_info:

            }

            if (0 === strpos($pathinfo, '/api/oauth')) {
                if (0 === strpos($pathinfo, '/api/oauth2')) {
                    if (0 === strpos($pathinfo, '/api/oauth2/client')) {
                        // api_oauth2_create
                        if ($pathinfo === '/api/oauth2/client') {
                            if ($this->context->getMethod() != 'POST') {
                                $allow[] = 'POST';
                                goto not_api_oauth2_create;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\Oauth2Controller::createAction',  '_route' => 'api_oauth2_create',);
                        }
                        not_api_oauth2_create:

                        // api_oauth2_edit
                        if (preg_match('#^/api/oauth2/client/(?P<clientId>\\w+)$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_oauth2_edit;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_oauth2_edit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\Oauth2Controller::editAction',));
                        }
                        not_api_oauth2_edit:

                        // api_oauth2_remove
                        if (preg_match('#^/api/oauth2/client/(?P<clientId>\\w+)$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'DELETE') {
                                $allow[] = 'DELETE';
                                goto not_api_oauth2_remove;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_oauth2_remove')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\Oauth2Controller::removeAction',));
                        }
                        not_api_oauth2_remove:

                        // api_oauth2_get_client
                        if (preg_match('#^/api/oauth2/client/(?P<clientId>\\w+)$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_oauth2_get_client;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_oauth2_get_client')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\Oauth2Controller::getClientAction',));
                        }
                        not_api_oauth2_get_client:

                        // api_oauth2_get_clients
                        if ($pathinfo === '/api/oauth2/clients') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_oauth2_get_clients;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\Oauth2Controller::getClientsAction',  '_route' => 'api_oauth2_get_clients',);
                        }
                        not_api_oauth2_get_clients:

                    }

                    // api_oauth2_authenticate
                    if ($pathinfo === '/api/oauth2/authenticate') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_oauth2_authenticate;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\Oauth2Controller::authenticateAction',  '_route' => 'api_oauth2_authenticate',);
                    }
                    not_api_oauth2_authenticate:

                    // api_oauth2_token
                    if ($pathinfo === '/api/oauth2/token') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_oauth2_token;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\Oauth2Controller::generateTokenAction',  '_route' => 'api_oauth2_token',);
                    }
                    not_api_oauth2_token:

                    // api_oauth2_get_user_by_token
                    if ($pathinfo === '/api/oauth2/user_by_token') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_oauth2_get_user_by_token;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\Oauth2Controller::getSessionDataActionByAccessToken',  '_route' => 'api_oauth2_get_user_by_token',);
                    }
                    not_api_oauth2_get_user_by_token:

                }

                // api_oauth_get_user_profile
                if ($pathinfo === '/api/oauth/user_profile') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_oauth_get_user_profile;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::getUserProfileAction',  '_route' => 'api_oauth_get_user_profile',);
                }
                not_api_oauth_get_user_profile:

            }

            // api_oauth_get_by_domain
            if (0 === strpos($pathinfo, '/api/domain') && preg_match('#^/api/domain/(?P<domain>\\d+)/oauth$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_oauth_get_by_domain;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_oauth_get_by_domain')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::getOauthByDomainAction',));
            }
            not_api_oauth_get_by_domain:

            if (0 === strpos($pathinfo, '/api/oauth')) {
                // api_oauth_get_by_id
                if (preg_match('#^/api/oauth/(?P<oauthId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_oauth_get_by_id;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_oauth_get_by_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::getOauthByIdAction',));
                }
                not_api_oauth_get_by_id:

                // api_oauth_create
                if ($pathinfo === '/api/oauth') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_oauth_create;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::createAction',  '_route' => 'api_oauth_create',);
                }
                not_api_oauth_create:

                // api_oauth_edit
                if (preg_match('#^/api/oauth/(?P<oauthId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_oauth_edit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_oauth_edit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::editAction',));
                }
                not_api_oauth_edit:

                // api_oauth_remove
                if (preg_match('#^/api/oauth/(?P<oauthId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_oauth_remove;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_oauth_remove')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::removeAction',));
                }
                not_api_oauth_remove:

                // api_oauth_create_binding
                if ($pathinfo === '/api/oauth/binding') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_oauth_create_binding;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::createBindingAction',  '_route' => 'api_oauth_create_binding',);
                }
                not_api_oauth_create_binding:

            }

            // api_oauth_remove_binding
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/oauth_binding$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'DELETE') {
                    $allow[] = 'DELETE';
                    goto not_api_oauth_remove_binding;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_oauth_remove_binding')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::removeBindingAction',));
            }
            not_api_oauth_remove_binding:

            if (0 === strpos($pathinfo, '/api/oauth')) {
                // api_oauth_is_binding
                if ($pathinfo === '/api/oauth/is_binding') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_oauth_is_binding;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::isBindingAction',  '_route' => 'api_oauth_is_binding',);
                }
                not_api_oauth_is_binding:

                // api_oauth_get_all_vendor
                if ($pathinfo === '/api/oauth/vendor') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_oauth_get_all_vendor;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OauthController::getAllOauthVendorAction',  '_route' => 'api_oauth_get_all_vendor',);
                }
                not_api_oauth_get_all_vendor:

            }

            // api_order_do
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/order$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_order_do;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_order_do')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OrderController::orderAction',));
            }
            not_api_order_do:

            // api_multi_order
            if ($pathinfo === '/api/orders') {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_multi_order;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OrderController::multiOrderAction',  '_route' => 'api_multi_order',);
            }
            not_api_multi_order:

            // api_multi_order_bunch
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/multi_order_bunch$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_multi_order_bunch;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_multi_order_bunch')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OrderController::multiOrderBunchAction',));
            }
            not_api_multi_order_bunch:

            // api_otp_verify
            if ($pathinfo === '/api/otp/verify') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_otp_verify;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OtpController::verifyAction',  '_route' => 'api_otp_verify',);
            }
            not_api_otp_verify:

            if (0 === strpos($pathinfo, '/api/global_ip')) {
                // api_global_ip_verify
                if ($pathinfo === '/api/global_ip/verify') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_global_ip_verify;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OtpController::verifyGlobalIpAction',  '_route' => 'api_global_ip_verify',);
                }
                not_api_global_ip_verify:

                // api_global_ip_create
                if ($pathinfo === '/api/global_ip') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_global_ip_create;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OtpController::createGlobalIpAction',  '_route' => 'api_global_ip_create',);
                }
                not_api_global_ip_create:

                // api_global_ip_remove
                if ($pathinfo === '/api/global_ip') {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_global_ip_remove;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OtpController::removeGlobalIpAction',  '_route' => 'api_global_ip_remove',);
                }
                not_api_global_ip_remove:

                // api_global_ip_check
                if ($pathinfo === '/api/global_ip/check') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_global_ip_check;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OtpController::checkGlobalIpAction',  '_route' => 'api_global_ip_check',);
                }
                not_api_global_ip_check:

            }

            // api_otp_set
            if ($pathinfo === '/api/otp/set') {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_otp_set;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OtpController::setAction',  '_route' => 'api_otp_set',);
            }
            not_api_otp_set:

            // api_global_ip_edit
            if ($pathinfo === '/api/global_ip') {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_global_ip_edit;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OtpController::editGlobalIpAction',  '_route' => 'api_global_ip_edit',);
            }
            not_api_global_ip_edit:

            // api_get_user_outside_payway
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/outside/payway$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_user_outside_payway;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_outside_payway')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::getOutsidePaywayAction',));
            }
            not_api_get_user_outside_payway:

            // api_get_outside_entry
            if (0 === strpos($pathinfo, '/api/outside/entry') && preg_match('#^/api/outside/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_outside_entry;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_outside_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::getEntryAction',));
            }
            not_api_get_outside_entry:

            // api_get_outside_entries
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/outside/entry$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_outside_entries;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_outside_entries')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::getEntriesAction',));
            }
            not_api_get_outside_entries:

            if (0 === strpos($pathinfo, '/api/outside')) {
                // api_get_outside_entries_by_ref_id
                if ($pathinfo === '/api/outside/entries_by_ref_id') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_outside_entries_by_ref_id;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::getEntriesByRefIdAction',  '_route' => 'api_get_outside_entries_by_ref_id',);
                }
                not_api_get_outside_entries_by_ref_id:

                // api_outside_get_trans
                if (0 === strpos($pathinfo, '/api/outside/transaction') && preg_match('#^/api/outside/transaction/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_outside_get_trans;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_outside_get_trans')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::getTransactionAction',));
                }
                not_api_outside_get_trans:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_outside_total_amount
                if (preg_match('#^/api/user/(?P<userId>\\d+)/outside/total_amount$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_outside_total_amount;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_outside_total_amount')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::getTotalAmountAction',));
                }
                not_api_outside_total_amount:

                // api_outside_operation
                if (preg_match('#^/api/user/(?P<userId>\\d+)/outside/op$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_outside_operation;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_outside_operation')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::operationAction',));
                }
                not_api_outside_operation:

            }

            if (0 === strpos($pathinfo, '/api/outside/transaction')) {
                // api_outside_transaction_commit
                if (preg_match('#^/api/outside/transaction/(?P<id>\\d+)/commit$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_outside_transaction_commit;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_outside_transaction_commit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::transactionCommitAction',));
                }
                not_api_outside_transaction_commit:

                // api_outside_transaction_rollback
                if (preg_match('#^/api/outside/transaction/(?P<id>\\d+)/rollback$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_outside_transaction_rollback;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_outside_transaction_rollback')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::transactionRollbackAction',));
                }
                not_api_outside_transaction_rollback:

            }

            // api_outside_get_by_user_id
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/outside$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_outside_get_by_user_id;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_outside_get_by_user_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\OutsideController::getOutsideByUserIdAction',));
            }
            not_api_outside_get_by_user_id:

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_create_domain_payment_charge
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/payment_charge$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_domain_payment_charge;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_domain_payment_charge')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::createPaymentChargeAction',));
                }
                not_api_create_domain_payment_charge:

                // api_create_payment_charge_preset
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/payment_charge/preset$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_payment_charge_preset;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_payment_charge_preset')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::createPresetPaymentChargeAction',));
                }
                not_api_create_payment_charge_preset:

            }

            if (0 === strpos($pathinfo, '/api/payment_charge')) {
                // api_set_payment_charge_rank
                if ($pathinfo === '/api/payment_charge/rank') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_payment_charge_rank;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setPaymentChargeRankAction',  '_route' => 'api_set_payment_charge_rank',);
                }
                not_api_set_payment_charge_rank:

                // api_set_payment_charge_name
                if (preg_match('#^/api/payment_charge/(?P<id>\\d+)/name$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_payment_charge_name;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_payment_charge_name')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setPaymentChargeNameAction',));
                }
                not_api_set_payment_charge_name:

                // api_get_payment_gateway_fee
                if (preg_match('#^/api/payment_charge/(?P<id>\\d+)/payment_gateway/fee$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_payment_gateway_fee;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_gateway_fee')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::getPaymentGatewayFeeAction',));
                }
                not_api_get_payment_gateway_fee:

                // api_set_payment_gateway_fee
                if (preg_match('#^/api/payment_charge/(?P<id>\\d+)/payment_gateway/fee$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_payment_gateway_fee;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_payment_gateway_fee')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setPaymentGatewayFeeAction',));
                }
                not_api_set_payment_gateway_fee:

                // api_get_payment_withdraw_fee
                if (preg_match('#^/api/payment_charge/(?P<id>\\d+)/withdraw_fee$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_payment_withdraw_fee;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_withdraw_fee')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::getPaymentWithdrawFeeAction',));
                }
                not_api_get_payment_withdraw_fee:

                // api_set_payment_charge_withdraw_fee
                if (preg_match('#^/api/payment_charge/(?P<id>\\d+)/withdraw_fee$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_payment_charge_withdraw_fee;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_payment_charge_withdraw_fee')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setPaymentWithdrawFeeAction',));
                }
                not_api_set_payment_charge_withdraw_fee:

                // api_get_payment_withdraw_verify
                if (preg_match('#^/api/payment_charge/(?P<id>\\d+)/withdraw_verify$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_payment_withdraw_verify;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_withdraw_verify')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::getPaymentWithdrawVerifyAction',));
                }
                not_api_get_payment_withdraw_verify:

                // api_set_payment_charge_withdraw_verify
                if (preg_match('#^/api/payment_charge/(?P<id>\\d+)/withdraw_verify$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_payment_charge_withdraw_verify;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_payment_charge_withdraw_verify')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setPaymentWithdrawVerifyAction',));
                }
                not_api_set_payment_charge_withdraw_verify:

            }

            // api_get_domain_payment_charge
            if (0 === strpos($pathinfo, '/api/domain') && preg_match('#^/api/domain/(?P<domain>\\d+)/payment_charge$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_domain_payment_charge;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_domain_payment_charge')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::getPaymentChargeAction',));
            }
            not_api_get_domain_payment_charge:

            if (0 === strpos($pathinfo, '/api/payment_')) {
                if (0 === strpos($pathinfo, '/api/payment_charge')) {
                    // api_remove_payment_charge
                    if (preg_match('#^/api/payment_charge/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_remove_payment_charge;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_payment_charge')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::removePaymentChargeAction',));
                    }
                    not_api_remove_payment_charge:

                    // api_payment_charge_deposit_online_get
                    if (preg_match('#^/api/payment_charge/(?P<paymentChargeId>\\d+)/deposit_online$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_payment_charge_deposit_online_get;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_charge_deposit_online_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::getDepositOnlineAction',));
                    }
                    not_api_payment_charge_deposit_online_get:

                    // api_payment_charge_deposit_company_get
                    if (preg_match('#^/api/payment_charge/(?P<paymentChargeId>\\d+)/deposit_company$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_payment_charge_deposit_company_get;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_charge_deposit_company_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::getDepositCompanyAction',));
                    }
                    not_api_payment_charge_deposit_company_get:

                    // api_payment_charge_deposit_mobile_get
                    if (preg_match('#^/api/payment_charge/(?P<paymentChargeId>\\d+)/deposit_mobile$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_payment_charge_deposit_mobile_get;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_charge_deposit_mobile_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::getDepositMobileAction',));
                    }
                    not_api_payment_charge_deposit_mobile_get:

                    // api_payment_charge_deposit_bitcoin_get
                    if (preg_match('#^/api/payment_charge/(?P<paymentChargeId>\\d+)/deposit_bitcoin$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_payment_charge_deposit_bitcoin_get;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_charge_deposit_bitcoin_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::getDepositBitcoinAction',));
                    }
                    not_api_payment_charge_deposit_bitcoin_get:

                    // api_payment_charge_deposit_online_set
                    if (preg_match('#^/api/payment_charge/(?P<paymentChargeId>\\d+)/deposit_online$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_payment_charge_deposit_online_set;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_charge_deposit_online_set')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setDepositOnlineAction',));
                    }
                    not_api_payment_charge_deposit_online_set:

                    // api_payment_charge_deposit_company_set
                    if (preg_match('#^/api/payment_charge/(?P<paymentChargeId>\\d+)/deposit_company$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_payment_charge_deposit_company_set;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_charge_deposit_company_set')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setDepositCompanyAction',));
                    }
                    not_api_payment_charge_deposit_company_set:

                    // api_payment_charge_deposit_mobile_set
                    if (preg_match('#^/api/payment_charge/(?P<paymentChargeId>\\d+)/deposit_mobile$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_payment_charge_deposit_mobile_set;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_charge_deposit_mobile_set')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setDepositMobileAction',));
                    }
                    not_api_payment_charge_deposit_mobile_set:

                    // api_payment_charge_deposit_bitcoin_set
                    if (preg_match('#^/api/payment_charge/(?P<paymentChargeId>\\d+)/deposit_bitcoin$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_payment_charge_deposit_bitcoin_set;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_charge_deposit_bitcoin_set')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentChargeController::setDepositBitcoinAction',));
                    }
                    not_api_payment_charge_deposit_bitcoin_set:

                }

                if (0 === strpos($pathinfo, '/api/payment_gateway')) {
                    // api_get_payment_gateway
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_payment_gateway;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_gateway')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getAction',));
                    }
                    not_api_get_payment_gateway:

                    // api_get_payment_gateway_list
                    if ($pathinfo === '/api/payment_gateway') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_payment_gateway_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getListAction',  '_route' => 'api_get_payment_gateway_list',);
                    }
                    not_api_get_payment_gateway_list:

                    // api_edit_payment_gateway
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_edit_payment_gateway;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_edit_payment_gateway')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::setAction',));
                    }
                    not_api_edit_payment_gateway:

                    // api_remove_payment_gateway
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_remove_payment_gateway;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_payment_gateway')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::removeAction',));
                    }
                    not_api_remove_payment_gateway:

                    // api_payment_gateway_get_payment_method
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/payment_method$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_payment_gateway_get_payment_method;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_gateway_get_payment_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getPaymentMethodAction',));
                    }
                    not_api_payment_gateway_get_payment_method:

                    // api_payment_gateway_set_payment_method
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/payment_method$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_payment_gateway_set_payment_method;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_gateway_set_payment_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::setPaymentMethodAction',));
                    }
                    not_api_payment_gateway_set_payment_method:

                    // api_payment_gateway_get_payment_vendor
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/payment_vendor$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_payment_gateway_get_payment_vendor;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_gateway_get_payment_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getPaymentVendorAction',));
                    }
                    not_api_payment_gateway_get_payment_vendor:

                    // api_payment_gateway_set_payment_vendor
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/payment_vendor$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_payment_gateway_set_payment_vendor;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_gateway_set_payment_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::setPaymentVendorAction',));
                    }
                    not_api_payment_gateway_set_payment_vendor:

                    // api_get_payment_gateway_currency
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/currency$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_payment_gateway_currency;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_gateway_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getPaymentGatewayCurrencyAction',));
                    }
                    not_api_get_payment_gateway_currency:

                    // api_set_payment_gateway_currency
                    if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/currency$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_payment_gateway_currency;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_payment_gateway_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::setPaymentGatewayCurrencyAction',));
                    }
                    not_api_set_payment_gateway_currency:

                }

            }

            // api_get_payment_gateway_by_currency
            if (0 === strpos($pathinfo, '/api/currency') && preg_match('#^/api/currency/(?P<currency>\\w+)/payment_gateway$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_payment_gateway_by_currency;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_gateway_by_currency')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getPaymentGatewayByCurrency',));
            }
            not_api_get_payment_gateway_by_currency:

            if (0 === strpos($pathinfo, '/api/payment_gateway')) {
                // api_payment_gateway_bind_ip_enable
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/bind_ip_enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_payment_gateway_bind_ip_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_gateway_bind_ip_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::paymentGatewayBindIpEnableAction',));
                }
                not_api_payment_gateway_bind_ip_enable:

                // api_payment_gateway_bind_ip_disable
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/bind_ip_disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_payment_gateway_bind_ip_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_gateway_bind_ip_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::paymentGatewayBindIpDisableAction',));
                }
                not_api_payment_gateway_bind_ip_disable:

                // api_add_payment_gateway_bind_ip
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/bind_ip$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_add_payment_gateway_bind_ip;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_add_payment_gateway_bind_ip')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::addPaymentGatewayBindIpAction',));
                }
                not_api_add_payment_gateway_bind_ip:

                // api_remove_payment_gateway_bind_ip
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/bind_ip$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_payment_gateway_bind_ip;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_payment_gateway_bind_ip')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::removePaymentGatewayBindIpAction',));
                }
                not_api_remove_payment_gateway_bind_ip:

                // api_get_payment_gateway_bind_ip
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/bind_ip$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_payment_gateway_bind_ip;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_gateway_bind_ip')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getPaymentGatewayBindIpAction',));
                }
                not_api_get_payment_gateway_bind_ip:

                // api_get_payment_gateway_bank_info
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/bank_info$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_payment_gateway_bank_info;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_gateway_bank_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getBankInfoAction',));
                }
                not_api_get_payment_gateway_bank_info:

                // api_set_payment_gateway_bank_info
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/bank_info$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_payment_gateway_bank_info;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_payment_gateway_bank_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::setBankInfoAction',));
                }
                not_api_set_payment_gateway_bank_info:

                // api_get_payment_gateway_description
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/description$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_payment_gateway_description;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_payment_gateway_description')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getDescriptionAction',));
                }
                not_api_get_payment_gateway_description:

                // api_set_payment_gateway_description
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/description$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_payment_gateway_description;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_payment_gateway_description')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::setDescriptionAction',));
                }
                not_api_set_payment_gateway_description:

                // api_payment_gateway_get_random_float_vendor
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/random_float_vendor$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_payment_gateway_get_random_float_vendor;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_gateway_get_random_float_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::getRandomFloatVendorAction',));
                }
                not_api_payment_gateway_get_random_float_vendor:

                // api_payment_gateway_set_random_float_vendor
                if (preg_match('#^/api/payment_gateway/(?P<paymentGatewayId>\\d+)/random_float_vendor$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_payment_gateway_set_random_float_vendor;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_gateway_set_random_float_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentGatewayController::setRandomFloatVendorAction',));
                }
                not_api_payment_gateway_set_random_float_vendor:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_user_get_payment_method
                if (preg_match('#^/api/user/(?P<userId>\\d+)/payment_method$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_payment_method;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_payment_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentLevelController::getPaymentMethodByUserAction',));
                }
                not_api_user_get_payment_method:

                // api_user_get_payment_vendor
                if (preg_match('#^/api/user/(?P<userId>\\d+)/payment_vendor$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_payment_vendor;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_payment_vendor')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentLevelController::getPaymentVendorByUserAction',));
                }
                not_api_user_get_payment_vendor:

                // api_get_deposit_merchant
                if (preg_match('#^/api/user/(?P<userId>\\d+)/deposit_merchant$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_deposit_merchant;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_deposit_merchant')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentLevelController::getDepositMerchantAction',));
                }
                not_api_get_deposit_merchant:

            }

            if (0 === strpos($pathinfo, '/api/p')) {
                if (0 === strpos($pathinfo, '/api/payment_')) {
                    if (0 === strpos($pathinfo, '/api/payment_method')) {
                        // api_payment_method_get_all
                        if ($pathinfo === '/api/payment_method') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_payment_method_get_all;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentMethodController::getAllAction',  '_route' => 'api_payment_method_get_all',);
                        }
                        not_api_payment_method_get_all:

                        // api_payment_method_get
                        if (preg_match('#^/api/payment_method/(?P<paymentMethodId>\\d+)$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_payment_method_get;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_method_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentMethodController::getAction',));
                        }
                        not_api_payment_method_get:

                    }

                    // api_payment_vendor_get
                    if (0 === strpos($pathinfo, '/api/payment_vendor') && preg_match('#^/api/payment_vendor/(?P<paymentVendorId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_payment_vendor_get;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_vendor_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentMethodController::getPaymentVendorAction',));
                    }
                    not_api_payment_vendor_get:

                    // api_payment_vendor_get_by_payment_method
                    if (0 === strpos($pathinfo, '/api/payment_method') && preg_match('#^/api/payment_method/(?P<paymentMethodId>\\d+)/payment_vendor$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_payment_vendor_get_by_payment_method;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_payment_vendor_get_by_payment_method')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PaymentMethodController::getPaymentVendorByPaymentMethodAction',));
                    }
                    not_api_payment_vendor_get_by_payment_method:

                }

                if (0 === strpos($pathinfo, '/api/petition')) {
                    // api_petition_create
                    if ($pathinfo === '/api/petition') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_petition_create;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PetitionController::createAction',  '_route' => 'api_petition_create',);
                    }
                    not_api_petition_create:

                    // api_petition_cancel
                    if (preg_match('#^/api/petition/(?P<petitionId>\\d+)/cancel$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_petition_cancel;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_petition_cancel')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PetitionController::cancelAction',));
                    }
                    not_api_petition_cancel:

                    // api_petition_confirm
                    if (preg_match('#^/api/petition/(?P<petitionId>\\d+)/confirm$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_petition_confirm;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_petition_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PetitionController::confirmAction',));
                    }
                    not_api_petition_confirm:

                    // api_petition_list
                    if ($pathinfo === '/api/petition/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_petition_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\PetitionController::getListAction',  '_route' => 'api_petition_list',);
                    }
                    not_api_petition_list:

                }

            }

            // api_get_user_register_bonus
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/register_bonus$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_user_register_bonus;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_register_bonus')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RegisterBonusController::getUserRegisterBonusAction',));
            }
            not_api_get_user_register_bonus:

            // api_get_all_currency_register_bonus
            if ($pathinfo === '/api/currency/register_bonus') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_all_currency_register_bonus;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RegisterBonusController::getAllCurrencyRegisterBonusAction',  '_route' => 'api_get_all_currency_register_bonus',);
            }
            not_api_get_all_currency_register_bonus:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_set_user_register_bonus
                if (preg_match('#^/api/user/(?P<userId>\\d+)/register_bonus$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_user_register_bonus;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_user_register_bonus')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RegisterBonusController::setUserRegisterBonusAction',));
                }
                not_api_set_user_register_bonus:

                // api_remove_user_register_bonus
                if (preg_match('#^/api/user/(?P<userId>\\d+)/register_bonus$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remove_user_register_bonus;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_user_register_bonus')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RegisterBonusController::removeUserRegisterBonusAction',));
                }
                not_api_remove_user_register_bonus:

            }

            // api_get_register_bonus_by_domain
            if (0 === strpos($pathinfo, '/api/domain') && preg_match('#^/api/domain/(?P<domain>\\d+)/register_bonus$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_register_bonus_by_domain;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_register_bonus_by_domain')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RegisterBonusController::getRegisterBonusByDomainAction',));
            }
            not_api_get_register_bonus_by_domain:

            if (0 === strpos($pathinfo, '/api/remit_account')) {
                // api_create_remit_account
                if ($pathinfo === '/api/remit_account') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_remit_account;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::createAction',  '_route' => 'api_create_remit_account',);
                }
                not_api_create_remit_account:

                // api_remit_account_get
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_remit_account_get;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::getAction',));
                }
                not_api_remit_account_get:

                // api_remit_account_get_stat
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/stat$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_remit_account_get_stat;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_get_stat')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::getStatAction',));
                }
                not_api_remit_account_get_stat:

                // api_remit_account_list
                if ($pathinfo === '/api/remit_account/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_remit_account_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::listAction',  '_route' => 'api_remit_account_list',);
                }
                not_api_remit_account_list:

                // api_remit_account_enable
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_remit_account_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::enableAction',));
                }
                not_api_remit_account_enable:

                // api_remit_account_disable
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_remit_account_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::disableAction',));
                }
                not_api_remit_account_disable:

                // api_remit_account_remove
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_remit_account_remove;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_remove')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::removeAction',));
                }
                not_api_remit_account_remove:

                // api_remit_account_recover
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/recover$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_remit_account_recover;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_recover')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::recoverAction',));
                }
                not_api_remit_account_recover:

                // api_remit_account_resume
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/resume$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_remit_account_resume;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_resume')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::resumeAction',));
                }
                not_api_remit_account_resume:

                // api_remit_account_suspend
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/suspend$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_remit_account_suspend;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_suspend')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::suspendAction',));
                }
                not_api_remit_account_suspend:

                // api_set_remit_account_crawler
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/crawler$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_remit_account_crawler;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_remit_account_crawler')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::setCrawlerAction',));
                }
                not_api_set_remit_account_crawler:

                // api_set_remit_account_crawler_run
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/crawler_run$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_remit_account_crawler_run;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_remit_account_crawler_run')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::setCrawlerRunAction',));
                }
                not_api_set_remit_account_crawler_run:

                // api_remit_account_unlock_password_error
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/unlock/password_error$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_remit_account_unlock_password_error;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_account_unlock_password_error')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::unlockPasswordErrorAction',));
                }
                not_api_remit_account_unlock_password_error:

                // api_edit_remit_account
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_edit_remit_account;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_edit_remit_account')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::setAction',));
                }
                not_api_edit_remit_account:

                // api_get_remit_account_level
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/level$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_remit_account_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_remit_account_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::getRemitAccountLevelAction',));
                }
                not_api_get_remit_account_level:

                // api_set_remit_account_level
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/level$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_remit_account_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_remit_account_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::setRemitAccountLevelAction',));
                }
                not_api_set_remit_account_level:

                // api_set_remit_account_qrcode
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/qrcode$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_remit_account_qrcode;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_remit_account_qrcode')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::setRemitAccountQrcodeAction',));
                }
                not_api_set_remit_account_qrcode:

                // api_get_remit_account_qrcode
                if (preg_match('#^/api/remit_account/(?P<remitAccountId>\\d+)/qrcode$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_remit_account_qrcode;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_remit_account_qrcode')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountController::getRemitAccountQrcodeAction',));
                }
                not_api_get_remit_account_qrcode:

            }

            if (0 === strpos($pathinfo, '/api/level')) {
                // api_get_remit_account_level_by_level
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/remit_account_level$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_remit_account_level_by_level;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_remit_account_level_by_level')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountLevelController::getByLevelAction',));
                }
                not_api_get_remit_account_level_by_level:

                // api_set_remit_account_level_order
                if (preg_match('#^/api/level/(?P<levelId>\\d+)/remit_account/order$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_remit_account_level_order;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_remit_account_level_order')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitAccountLevelController::setOrderAction',));
                }
                not_api_set_remit_account_level_order:

            }

            // api_remit_entry_get_order_number
            if ($pathinfo === '/api/remit/entry/order_number') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_remit_entry_get_order_number;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::getOrderNumberAction',  '_route' => 'api_remit_entry_get_order_number',);
            }
            not_api_remit_entry_get_order_number:

            // api_user_remit
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/remit$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_user_remit;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_remit')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::remitAction',));
            }
            not_api_user_remit:

            if (0 === strpos($pathinfo, '/api/remit/entry')) {
                // api_get_remit_entry
                if (preg_match('#^/api/remit/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_remit_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_remit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::getEntryAction',));
                }
                not_api_get_remit_entry:

                // api_set_remit_entry
                if (preg_match('#^/api/remit/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_remit_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_remit_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::setEntryAction',));
                }
                not_api_set_remit_entry:

                // api_remit_entry_confirm
                if (preg_match('#^/api/remit/entry/(?P<entryId>\\d+)/confirm$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_remit_entry_confirm;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_entry_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::confirmAction',));
                }
                not_api_remit_entry_confirm:

                // api_remit_entry_list
                if ($pathinfo === '/api/remit/entry/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_remit_entry_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::listEntryAction',  '_route' => 'api_remit_entry_list',);
                }
                not_api_remit_entry_list:

            }

            // api_get_user_remit_discount
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/remit/discount$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_user_remit_discount;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_remit_discount')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::getUserRemitDiscountAction',));
            }
            not_api_get_user_remit_discount:

            if (0 === strpos($pathinfo, '/api/re')) {
                if (0 === strpos($pathinfo, '/api/rem')) {
                    if (0 === strpos($pathinfo, '/api/remit/domain')) {
                        // api_remit_get_remit_level_order
                        if (preg_match('#^/api/remit/domain/(?P<domain>\\d+)/remit_level_order$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_remit_get_remit_level_order;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_get_remit_level_order')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::getRemitLevelOrderAction',));
                        }
                        not_api_remit_get_remit_level_order:

                        // api_remit_set_remit_level_order
                        if (preg_match('#^/api/remit/domain/(?P<domain>\\d+)/remit_level_order$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_remit_set_remit_level_order;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remit_set_remit_level_order')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemitController::setRemitLevelOrderAction',));
                        }
                        not_api_remit_set_remit_level_order:

                    }

                    if (0 === strpos($pathinfo, '/api/remove_plan')) {
                        // api_create_remove_plan
                        if ($pathinfo === '/api/remove_plan') {
                            if ($this->context->getMethod() != 'POST') {
                                $allow[] = 'POST';
                                goto not_api_create_remove_plan;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanController::createPlanAction',  '_route' => 'api_create_remove_plan',);
                        }
                        not_api_create_remove_plan:

                        // api_cancel_remove_plan_user
                        if (preg_match('#^/api/remove_plan/(?P<planId>\\d+)/user/cancel$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_cancel_remove_plan_user;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cancel_remove_plan_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanController::cancelPlanUserAction',));
                        }
                        not_api_cancel_remove_plan_user:

                        // api_cancel_remove_plan
                        if (preg_match('#^/api/remove_plan/(?P<planId>\\d+)/cancel$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_cancel_remove_plan;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cancel_remove_plan')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanController::cancelPlanAction',));
                        }
                        not_api_cancel_remove_plan:

                        // api_confirm_remove_plan
                        if (preg_match('#^/api/remove_plan/(?P<planId>\\d+)/confirm$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_confirm_remove_plan;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_confirm_remove_plan')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanController::confirmPlanAction',));
                        }
                        not_api_confirm_remove_plan:

                        // api_get_remove_plan_user
                        if (preg_match('#^/api/remove_plan/(?P<planId>\\d+)/user$#s', $pathinfo, $matches)) {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_get_remove_plan_user;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_remove_plan_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanController::getPlanUserAction',));
                        }
                        not_api_get_remove_plan_user:

                        // api_get_remove_plan
                        if ($pathinfo === '/api/remove_plan') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_get_remove_plan;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanController::getPlanAction',  '_route' => 'api_get_remove_plan',);
                        }
                        not_api_get_remove_plan:

                        // api_check_plan_finish_by_plan_user
                        if ($pathinfo === '/api/remove_plan/check_finish') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_check_plan_finish_by_plan_user;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanController::checkPlanFinishByPlanUserIdAction',  '_route' => 'api_check_plan_finish_by_plan_user',);
                        }
                        not_api_check_plan_finish_by_plan_user:

                        // api_finish_remove_plan
                        if (preg_match('#^/api/remove_plan/(?P<planId>\\d+)/finish$#s', $pathinfo, $matches)) {
                            if ($this->context->getMethod() != 'PUT') {
                                $allow[] = 'PUT';
                                goto not_api_finish_remove_plan;
                            }

                            return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_finish_remove_plan')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanController::finishPlanAction',));
                        }
                        not_api_finish_remove_plan:

                        if (0 === strpos($pathinfo, '/api/remove_plan_user')) {
                            // api_update_remove_plan_user
                            if (preg_match('#^/api/remove_plan_user/(?P<planUserId>\\d+)$#s', $pathinfo, $matches)) {
                                if ($this->context->getMethod() != 'PUT') {
                                    $allow[] = 'PUT';
                                    goto not_api_update_remove_plan_user;
                                }

                                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_update_remove_plan_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanUserController::updatePlanUserStatusAction',));
                            }
                            not_api_update_remove_plan_user:

                            // api_check_remove_plan_user
                            if (preg_match('#^/api/remove_plan_user/(?P<planUserId>\\d+)/check$#s', $pathinfo, $matches)) {
                                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                    $allow = array_merge($allow, array('GET', 'HEAD'));
                                    goto not_api_check_remove_plan_user;
                                }

                                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_check_remove_plan_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RemovePlanUserController::checkPlanUserAction',));
                            }
                            not_api_check_remove_plan_user:

                        }

                    }

                }

                if (0 === strpos($pathinfo, '/api/reward')) {
                    // api_reward_create
                    if ($pathinfo === '/api/reward') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_reward_create;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::createRewardAction',  '_route' => 'api_reward_create',);
                    }
                    not_api_reward_create:

                    // api_cancel_reward
                    if (preg_match('#^/api/reward/(?P<rewardId>\\d+)/cancel$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_cancel_reward;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cancel_reward')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::cancelRewardAction',));
                    }
                    not_api_cancel_reward:

                    // api_get_available_reward
                    if ($pathinfo === '/api/reward/available') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_available_reward;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::getAvailableRewardAction',  '_route' => 'api_get_available_reward',);
                    }
                    not_api_get_available_reward:

                    // api_get_active_reward
                    if (preg_match('#^/api/reward/(?P<rewardId>\\d+)/active$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_active_reward;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_active_reward')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::getActiveRewardAction',));
                    }
                    not_api_get_active_reward:

                    // api_reward_obtain
                    if ($pathinfo === '/api/reward/obtain') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_reward_obtain;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::obtainRewardAction',  '_route' => 'api_reward_obtain',);
                    }
                    not_api_reward_obtain:

                    // api_get_reward
                    if (preg_match('#^/api/reward/(?P<rewardId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_reward;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_reward')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::getRewardAction',));
                    }
                    not_api_get_reward:

                    // api_get_reward_list
                    if ($pathinfo === '/api/reward/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_reward_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::getRewardListAction',  '_route' => 'api_get_reward_list',);
                    }
                    not_api_get_reward_list:

                    // api_get_reward_entry
                    if (0 === strpos($pathinfo, '/api/reward/entry') && preg_match('#^/api/reward/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_reward_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_reward_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::getRewardEntryAction',));
                    }
                    not_api_get_reward_entry:

                    // api_get_reward_entries
                    if (preg_match('#^/api/reward/(?P<rewardId>\\d+)/entry$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_reward_entries;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_reward_entries')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::getRewardEntriesAction',));
                    }
                    not_api_get_reward_entries:

                }

            }

            // api_reward_entry_get_by_user_id
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/reward/entry$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_reward_entry_get_by_user_id;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_reward_entry_get_by_user_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::getRewardEntriesByUserIdAction',));
            }
            not_api_reward_entry_get_by_user_id:

            // api_end_reward
            if (0 === strpos($pathinfo, '/api/reward') && preg_match('#^/api/reward/(?P<rewardId>\\d+)/end$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_end_reward;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_end_reward')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\RewardController::endRewardAction',));
            }
            not_api_end_reward:

            // api_session_create
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/session$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_session_create;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_create')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::createAction',));
            }
            not_api_session_create:

            if (0 === strpos($pathinfo, '/api/session')) {
                // api_session_create_by_session_id
                if (preg_match('#^/api/session/(?P<sessionId>\\w+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_session_create_by_session_id;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_create_by_session_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::createBySessionIdAction',));
                }
                not_api_session_create_by_session_id:

                // api_session_get
                if (preg_match('#^/api/session/(?P<sessionId>\\w+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_session_get;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::getAction',));
                }
                not_api_session_get:

            }

            // api_session_get_by_user_id
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/session$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_session_get_by_user_id;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_get_by_user_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::getByUserAction',));
            }
            not_api_session_get_by_user_id:

            // api_session_delete
            if (0 === strpos($pathinfo, '/api/session') && preg_match('#^/api/session/(?P<sessionId>\\w+)$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'DELETE') {
                    $allow[] = 'DELETE';
                    goto not_api_session_delete;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_delete')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::deleteAction',));
            }
            not_api_session_delete:

            // api_session_delete_by_user_id
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/session$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'DELETE') {
                    $allow[] = 'DELETE';
                    goto not_api_session_delete_by_user_id;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_delete_by_user_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::deleteByUserAction',));
            }
            not_api_session_delete_by_user_id:

            // api_session_delete_by_parent
            if ($pathinfo === '/api/session') {
                if ($this->context->getMethod() != 'DELETE') {
                    $allow[] = 'DELETE';
                    goto not_api_session_delete_by_parent;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::deleteByParentAction',  '_route' => 'api_session_delete_by_parent',);
            }
            not_api_session_delete_by_parent:

            if (0 === strpos($pathinfo, '/api/online')) {
                if (0 === strpos($pathinfo, '/api/online/list')) {
                    // api_get_online_list
                    if ($pathinfo === '/api/online/list') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_online_list;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::getOnlineListAction',  '_route' => 'api_get_online_list',);
                    }
                    not_api_get_online_list:

                    // api_get_online_list_by_username
                    if ($pathinfo === '/api/online/list_by_username') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_online_list_by_username;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::getOnlineListByUsernameAction',  '_route' => 'api_get_online_list_by_username',);
                    }
                    not_api_get_online_list_by_username:

                }

                // api_get_total_online
                if ($pathinfo === '/api/online/total') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_total_online;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::getTotalOnlineAction',  '_route' => 'api_get_total_online',);
                }
                not_api_get_total_online:

            }

            // api_session_create_ots
            if (0 === strpos($pathinfo, '/api/session') && preg_match('#^/api/session/(?P<sessionId>\\w+)/ots$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_session_create_ots;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_create_ots')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::createOneTimeSessionAction',));
            }
            not_api_session_create_ots:

            // api_session_get_ots
            if (0 === strpos($pathinfo, '/api/ots') && preg_match('#^/api/ots/(?P<otsId>\\w+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_session_get_ots;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_get_ots')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::getOneTimeSessionAction',));
            }
            not_api_session_get_ots:

            // api_session_set_rd_info
            if (0 === strpos($pathinfo, '/api/session') && preg_match('#^/api/session/(?P<sessionId>\\w+)/rd_info$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_session_set_rd_info;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_session_set_rd_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SessionController::setSessionRdInfoAction',));
            }
            not_api_session_set_rd_info:

            // api_shareLimit_create
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/share_limit/(?P<groupNum>\\d+)$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_shareLimit_create;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_shareLimit_create')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ShareLimitController::createAction',));
            }
            not_api_shareLimit_create:

            // api_shareLimit_get
            if (0 === strpos($pathinfo, '/api/share_limit') && preg_match('#^/api/share_limit/(?P<shareId>\\d+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_shareLimit_get;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_shareLimit_get')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ShareLimitController::getAction',));
            }
            not_api_shareLimit_get:

            // api_get_by_user_id
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/share_limit/(?P<groupNum>\\d+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_by_user_id;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_by_user_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ShareLimitController::getByUserIdAction',));
            }
            not_api_get_by_user_id:

            if (0 === strpos($pathinfo, '/api/share_limit')) {
                // api_shareLimit_validate
                if ($pathinfo === '/api/share_limit/validate') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_shareLimit_validate;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ShareLimitController::validateAction',  '_route' => 'api_shareLimit_validate',);
                }
                not_api_shareLimit_validate:

                // api_get_shareLimit_option
                if ($pathinfo === '/api/share_limit/option') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_shareLimit_option;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ShareLimitController::getOptionAction',  '_route' => 'api_get_shareLimit_option',);
                }
                not_api_get_shareLimit_option:

                // api_get_shareLimit_activated_time
                if (preg_match('#^/api/share_limit/(?P<groupNum>\\d+)/activated_time$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_shareLimit_activated_time;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_shareLimit_activated_time')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ShareLimitController::getActivatedTimeAction',));
                }
                not_api_get_shareLimit_activated_time:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_get_shareLimit_division
                if (preg_match('#^/api/user/(?P<userId>\\d+)/share_limit/(?P<groupNum>\\d+)/division$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_shareLimit_division;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_shareLimit_division')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ShareLimitController::getDivisionAction',));
                }
                not_api_get_shareLimit_division:

                // api_get_divisions
                if (preg_match('#^/api/user/(?P<userId>\\d+)/divisions$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_divisions;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_divisions')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ShareLimitController::getMultiDivisionAction',));
                }
                not_api_get_divisions:

                // api_generate_binding_token
                if (preg_match('#^/api/user/(?P<userId>\\d+)/slide/binding_token$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_generate_binding_token;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_generate_binding_token')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::generateBindingTokenAction',));
                }
                not_api_generate_binding_token:

            }

            if (0 === strpos($pathinfo, '/api/slide')) {
                if (0 === strpos($pathinfo, '/api/slide/binding')) {
                    // api_create_binding
                    if ($pathinfo === '/api/slide/binding') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_create_binding;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::createBindingAction',  '_route' => 'api_create_binding',);
                    }
                    not_api_create_binding:

                    // api_remove_binding
                    if ($pathinfo === '/api/slide/binding') {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_remove_binding;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::removeBindingAction',  '_route' => 'api_remove_binding',);
                    }
                    not_api_remove_binding:

                }

                if (0 === strpos($pathinfo, '/api/slide/device')) {
                    // api_remove_all_bindings
                    if ($pathinfo === '/api/slide/device/bindings') {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_remove_all_bindings;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::removeAllBindingsAction',  '_route' => 'api_remove_all_bindings',);
                    }
                    not_api_remove_all_bindings:

                    // api_generate_access_token
                    if ($pathinfo === '/api/slide/device/access_token') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_generate_access_token;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::generateAccessTokenAction',  '_route' => 'api_generate_access_token',);
                    }
                    not_api_generate_access_token:

                }

                // api_edit_binding_name
                if ($pathinfo === '/api/slide/binding/name') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_edit_binding_name;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::editBindingNameAction',  '_route' => 'api_edit_binding_name',);
                }
                not_api_edit_binding_name:

                // api_get_binding_users_by_device
                if ($pathinfo === '/api/slide/device/users') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_binding_users_by_device;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::listBindingUsersByDeviceAction',  '_route' => 'api_get_binding_users_by_device',);
                }
                not_api_get_binding_users_by_device:

            }

            // api_get_binding_devices_by_user
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/slide/device$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_binding_devices_by_user;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_binding_devices_by_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::listBindingDevicesByUserAction',));
            }
            not_api_get_binding_devices_by_user:

            if (0 === strpos($pathinfo, '/api/s')) {
                if (0 === strpos($pathinfo, '/api/slide')) {
                    // api_disable_device
                    if ($pathinfo === '/api/slide/device/disable') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_disable_device;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::disableDeviceAction',  '_route' => 'api_disable_device',);
                    }
                    not_api_disable_device:

                    // api_unblock_binding
                    if ($pathinfo === '/api/slide/binding/unblock') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_unblock_binding;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::unblockBindingAction',  '_route' => 'api_unblock_binding',);
                    }
                    not_api_unblock_binding:

                    // api_slide_login
                    if ($pathinfo === '/api/slide/login') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_slide_login;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SlideController::slideLoginAction',  '_route' => 'api_slide_login',);
                    }
                    not_api_slide_login:

                }

                if (0 === strpos($pathinfo, '/api/stat')) {
                    if (0 === strpos($pathinfo, '/api/stat/deposit')) {
                        // api_stat_deposit_withdraw
                        if ($pathinfo === '/api/stat/deposit_withdraw') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_deposit_withdraw;
                            }

                            return array (  '_format' => 'json',  'item' => 'deposit_withdraw',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statUserListAction',  '_route' => 'api_stat_deposit_withdraw',);
                        }
                        not_api_stat_deposit_withdraw:

                        // api_stat_deposit
                        if ($pathinfo === '/api/stat/deposit') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_deposit;
                            }

                            return array (  '_format' => 'json',  'item' => 'deposit',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statUserListAction',  '_route' => 'api_stat_deposit',);
                        }
                        not_api_stat_deposit:

                    }

                    // api_stat_withdraw
                    if ($pathinfo === '/api/stat/withdraw') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_stat_withdraw;
                        }

                        return array (  '_format' => 'json',  'item' => 'withdraw',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statUserListAction',  '_route' => 'api_stat_withdraw',);
                    }
                    not_api_stat_withdraw:

                    // api_stat_all_offer
                    if ($pathinfo === '/api/stat/all_offer') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_stat_all_offer;
                        }

                        return array (  '_format' => 'json',  'item' => 'all_offer',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statUserListAction',  '_route' => 'api_stat_all_offer',);
                    }
                    not_api_stat_all_offer:

                    // api_stat_offer
                    if ($pathinfo === '/api/stat/offer') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_stat_offer;
                        }

                        return array (  '_format' => 'json',  'item' => 'offer',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statUserListAction',  '_route' => 'api_stat_offer',);
                    }
                    not_api_stat_offer:

                    // api_stat_rebate
                    if ($pathinfo === '/api/stat/rebate') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_stat_rebate;
                        }

                        return array (  '_format' => 'json',  'item' => 'rebate',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statUserListAction',  '_route' => 'api_stat_rebate',);
                    }
                    not_api_stat_rebate:

                    // api_stat_offer_remit
                    if ($pathinfo === '/api/stat/offer_remit') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_stat_offer_remit;
                        }

                        return array (  '_format' => 'json',  'item' => 'remit',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statUserListAction',  '_route' => 'api_stat_offer_remit',);
                    }
                    not_api_stat_offer_remit:

                    if (0 === strpos($pathinfo, '/api/stat/ag')) {
                        if (0 === strpos($pathinfo, '/api/stat/ag/deposit')) {
                            // api_stat_ag_deposit_withdraw
                            if ($pathinfo === '/api/stat/ag/deposit_withdraw') {
                                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                    $allow = array_merge($allow, array('GET', 'HEAD'));
                                    goto not_api_stat_ag_deposit_withdraw;
                                }

                                return array (  '_format' => 'json',  'item' => 'deposit_withdraw',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statAgentListAction',  '_route' => 'api_stat_ag_deposit_withdraw',);
                            }
                            not_api_stat_ag_deposit_withdraw:

                            // api_stat_ag_deposit
                            if ($pathinfo === '/api/stat/ag/deposit') {
                                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                    $allow = array_merge($allow, array('GET', 'HEAD'));
                                    goto not_api_stat_ag_deposit;
                                }

                                return array (  '_format' => 'json',  'item' => 'deposit',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statAgentListAction',  '_route' => 'api_stat_ag_deposit',);
                            }
                            not_api_stat_ag_deposit:

                        }

                        // api_stat_ag_withdraw
                        if ($pathinfo === '/api/stat/ag/withdraw') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_ag_withdraw;
                            }

                            return array (  '_format' => 'json',  'item' => 'withdraw',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statAgentListAction',  '_route' => 'api_stat_ag_withdraw',);
                        }
                        not_api_stat_ag_withdraw:

                        // api_stat_ag_all_offer
                        if ($pathinfo === '/api/stat/ag/all_offer') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_ag_all_offer;
                            }

                            return array (  '_format' => 'json',  'item' => 'all_offer',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statAgentListAction',  '_route' => 'api_stat_ag_all_offer',);
                        }
                        not_api_stat_ag_all_offer:

                        // api_stat_ag_offer
                        if ($pathinfo === '/api/stat/ag/offer') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_ag_offer;
                            }

                            return array (  '_format' => 'json',  'item' => 'offer',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statAgentListAction',  '_route' => 'api_stat_ag_offer',);
                        }
                        not_api_stat_ag_offer:

                        // api_stat_ag_rebate
                        if ($pathinfo === '/api/stat/ag/rebate') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_ag_rebate;
                            }

                            return array (  '_format' => 'json',  'item' => 'rebate',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statAgentListAction',  '_route' => 'api_stat_ag_rebate',);
                        }
                        not_api_stat_ag_rebate:

                        // api_stat_ag_offer_remit
                        if ($pathinfo === '/api/stat/ag/offer_remit') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_ag_offer_remit;
                            }

                            return array (  '_format' => 'json',  'item' => 'remit',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::statAgentListAction',  '_route' => 'api_stat_ag_offer_remit',);
                        }
                        not_api_stat_ag_offer_remit:

                    }

                    if (0 === strpos($pathinfo, '/api/stat/domain')) {
                        if (0 === strpos($pathinfo, '/api/stat/domain/deposit_')) {
                            // api_stat_domain_deposit_manual
                            if ($pathinfo === '/api/stat/domain/deposit_manual') {
                                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                    $allow = array_merge($allow, array('GET', 'HEAD'));
                                    goto not_api_stat_domain_deposit_manual;
                                }

                                return array (  '_format' => 'json',  'category' => 'deposit_manual',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::getStatDomainAction',  '_route' => 'api_stat_domain_deposit_manual',);
                            }
                            not_api_stat_domain_deposit_manual:

                            // api_stat_domain_deposit_company
                            if ($pathinfo === '/api/stat/domain/deposit_company') {
                                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                    $allow = array_merge($allow, array('GET', 'HEAD'));
                                    goto not_api_stat_domain_deposit_company;
                                }

                                return array (  '_format' => 'json',  'category' => 'deposit_company',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::getStatDomainAction',  '_route' => 'api_stat_domain_deposit_company',);
                            }
                            not_api_stat_domain_deposit_company:

                            // api_stat_domain_deposit_online
                            if ($pathinfo === '/api/stat/domain/deposit_online') {
                                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                    $allow = array_merge($allow, array('GET', 'HEAD'));
                                    goto not_api_stat_domain_deposit_online;
                                }

                                return array (  '_format' => 'json',  'category' => 'deposit_online',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::getStatDomainAction',  '_route' => 'api_stat_domain_deposit_online',);
                            }
                            not_api_stat_domain_deposit_online:

                        }

                        // api_stat_domain_withdraw_manual
                        if ($pathinfo === '/api/stat/domain/withdraw_manual') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_domain_withdraw_manual;
                            }

                            return array (  '_format' => 'json',  'category' => 'withdraw_manual',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::getStatDomainAction',  '_route' => 'api_stat_domain_withdraw_manual',);
                        }
                        not_api_stat_domain_withdraw_manual:

                        // api_stat_domain_rebate
                        if ($pathinfo === '/api/stat/domain/rebate') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_domain_rebate;
                            }

                            return array (  '_format' => 'json',  'category' => 'rebate',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::getStatDomainAction',  '_route' => 'api_stat_domain_rebate',);
                        }
                        not_api_stat_domain_rebate:

                        // api_stat_domain_offer
                        if ($pathinfo === '/api/stat/domain/offer') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_api_stat_domain_offer;
                            }

                            return array (  '_format' => 'json',  'category' => 'offer',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::getStatDomainAction',  '_route' => 'api_stat_domain_offer',);
                        }
                        not_api_stat_domain_offer:

                    }

                    // api_stat_history_ledger
                    if ($pathinfo === '/api/stat/history_ledger') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_stat_history_ledger;
                        }

                        return array (  '_format' => 'json',  'category' => 'deposit_manual',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::getStatDomainAction',  '_route' => 'api_stat_history_ledger',);
                    }
                    not_api_stat_history_ledger:

                    // api_get_stat_domain_count_first_deposit_users
                    if (0 === strpos($pathinfo, '/api/stat/domain') && preg_match('#^/api/stat/domain/(?P<domain>\\d+)/count_first_deposit_users$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_stat_domain_count_first_deposit_users;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_stat_domain_count_first_deposit_users')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\StatController::getStatDomainCountFirstDepositUsersAction',));
                    }
                    not_api_get_stat_domain_count_first_deposit_users:

                }

                if (0 === strpos($pathinfo, '/api/suncity')) {
                    // api_suncity_get_trans
                    if (0 === strpos($pathinfo, '/api/suncity/transaction') && preg_match('#^/api/suncity/transaction/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_suncity_get_trans;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_suncity_get_trans')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SuncityController::getTransactionAction',));
                    }
                    not_api_suncity_get_trans:

                    // api_get_suncity_entry
                    if (0 === strpos($pathinfo, '/api/suncity/entry') && preg_match('#^/api/suncity/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_suncity_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_suncity_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SuncityController::getEntryAction',));
                    }
                    not_api_get_suncity_entry:

                }

            }

            // api_get_suncity_entries
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/suncity/entry$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_suncity_entries;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_suncity_entries')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SuncityController::getEntriesAction',));
            }
            not_api_get_suncity_entries:

            // api_get_suncity_entries_by_ref_id
            if ($pathinfo === '/api/suncity/entries_by_ref_id') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_suncity_entries_by_ref_id;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\SuncityController::getEntriesByRefIdAction',  '_route' => 'api_get_suncity_entries_by_ref_id',);
            }
            not_api_get_suncity_entries_by_ref_id:

            if (0 === strpos($pathinfo, '/api/transcribe')) {
                // api_get_transcribe_entries
                if ($pathinfo === '/api/transcribe/entries') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_transcribe_entries;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::getTranscribeEntriesAction',  '_route' => 'api_get_transcribe_entries',);
                }
                not_api_get_transcribe_entries:

                // api_get_transcribe_unconfirm_list
                if ($pathinfo === '/api/transcribe/unconfirm_list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_transcribe_unconfirm_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::getUnconfirmListAction',  '_route' => 'api_get_transcribe_unconfirm_list',);
                }
                not_api_get_transcribe_unconfirm_list:

                // api_get_transcribe_max_rank
                if ($pathinfo === '/api/transcribe/max_rank') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_transcribe_max_rank;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::getTranscribeMaxRankAction',  '_route' => 'api_get_transcribe_max_rank',);
                }
                not_api_get_transcribe_max_rank:

                if (0 === strpos($pathinfo, '/api/transcribe/entry')) {
                    // api_create_transcribe_entry
                    if ($pathinfo === '/api/transcribe/entry') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_create_transcribe_entry;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::createTranscribeEntryAction',  '_route' => 'api_create_transcribe_entry',);
                    }
                    not_api_create_transcribe_entry:

                    // api_edit_transcribe_entry
                    if (preg_match('#^/api/transcribe/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_edit_transcribe_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_edit_transcribe_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::editTranscribeEntryAction',));
                    }
                    not_api_edit_transcribe_entry:

                    // api_remove_transcribe_entry
                    if (preg_match('#^/api/transcribe/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_api_remove_transcribe_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_remove_transcribe_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::removeTranscribeEntryAction',));
                    }
                    not_api_remove_transcribe_entry:

                    // api_get_transcribe_entry
                    if (preg_match('#^/api/transcribe/entry/(?P<entryId>\\d+)$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_transcribe_entry;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_transcribe_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::getTranscribeEntryAction',));
                    }
                    not_api_get_transcribe_entry:

                }

                // api_get_transcribe_total
                if ($pathinfo === '/api/transcribe/total') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_transcribe_total;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::getTranscribeTotalAction',  '_route' => 'api_get_transcribe_total',);
                }
                not_api_get_transcribe_total:

                // api_set_transcribe_rank
                if (0 === strpos($pathinfo, '/api/transcribe/entry') && preg_match('#^/api/transcribe/entry/(?P<entryId>\\d+)/rank$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_set_transcribe_rank;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_set_transcribe_rank')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::setTranscribeEntryRankAction',));
                }
                not_api_set_transcribe_rank:

                // api_force_confirm_transcribe_entry
                if (preg_match('#^/api/transcribe/(?P<entryId>\\d+)/force_confirm$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_force_confirm_transcribe_entry;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_force_confirm_transcribe_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::forceConfirmAction',));
                }
                not_api_force_confirm_transcribe_entry:

                // api_get_transcribe_inquiry
                if ($pathinfo === '/api/transcribe/inquiry') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_transcribe_inquiry;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::getTranscribeInquiry',  '_route' => 'api_get_transcribe_inquiry',);
                }
                not_api_get_transcribe_inquiry:

                // api_get_transcribe_blank_total
                if (0 === strpos($pathinfo, '/api/transcribe/account') && preg_match('#^/api/transcribe/account/(?P<accountId>\\d+)/blank_total$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_transcribe_blank_total;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_transcribe_blank_total')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TranscribeController::getTranscribeBlankTotal',));
                }
                not_api_get_transcribe_blank_total:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                if (0 === strpos($pathinfo, '/api/user/id')) {
                    // api_user_id_generate
                    if ($pathinfo === '/api/user/id') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_user_id_generate;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::generateIdAction',  '_route' => 'api_user_id_generate',);
                    }
                    not_api_user_id_generate:

                    // api_user_generate_id
                    if ($pathinfo === '/api/user/id') {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_api_user_generate_id;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::generateUserIdAction',  '_route' => 'api_user_generate_id',);
                    }
                    not_api_user_generate_id:

                }

                // api_user_create
                if ($pathinfo === '/api/user') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_user_create;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::createAction',  '_route' => 'api_user_create',);
                }
                not_api_user_create:

                // api_user_remove
                if (preg_match('#^/api/user/(?P<userId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_user_remove;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_remove')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::removeAction',));
                }
                not_api_user_remove:

                // api_user_recover
                if (preg_match('#^/api/user/(?P<userId>\\d+)/recover$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_recover;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_recover')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::recoverAction',));
                }
                not_api_user_recover:

                // api_user_enable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/enable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_enable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_enable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::enableAction',));
                }
                not_api_user_enable:

                // api_user_disable
                if (preg_match('#^/api/user/(?P<userId>\\d+)/disable$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_disable;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_disable')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::disableAction',));
                }
                not_api_user_disable:

                // api_user_block
                if (preg_match('#^/api/user/(?P<userId>\\d+)/block$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_block;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_block')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::blockAction',));
                }
                not_api_user_block:

                // api_user_unblock
                if (preg_match('#^/api/user/(?P<userId>\\d+)/unblock$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_unblock;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_unblock')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::unblockAction',));
                }
                not_api_user_unblock:

                // api_user_bankrupt_on
                if (preg_match('#^/api/user/(?P<userId>\\d+)/bankrupt/1$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_bankrupt_on;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_bankrupt_on')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setBankruptOnAction',));
                }
                not_api_user_bankrupt_on:

                // api_user_bankrupt_off
                if (preg_match('#^/api/user/(?P<userId>\\d+)/bankrupt/0$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_bankrupt_off;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_bankrupt_off')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setBankruptOffAction',));
                }
                not_api_user_bankrupt_off:

                // api_user_test_on
                if (preg_match('#^/api/user/(?P<userId>\\d+)/test/1$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_test_on;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_test_on')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setTestOnAction',));
                }
                not_api_user_test_on:

                // api_user_test_off
                if (preg_match('#^/api/user/(?P<userId>\\d+)/test/0$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_test_off;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_test_off')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setTestOffAction',));
                }
                not_api_user_test_off:

                // api_user_rent_on
                if (preg_match('#^/api/user/(?P<userId>\\d+)/rent/1$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_rent_on;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_rent_on')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setRentOnAction',));
                }
                not_api_user_rent_on:

                // api_user_rent_off
                if (preg_match('#^/api/user/(?P<userId>\\d+)/rent/0$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_rent_off;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_rent_off')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setRentOffAction',));
                }
                not_api_user_rent_off:

                // api_user_password_reset_on
                if (preg_match('#^/api/user/(?P<userId>\\d+)/password_reset/1$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_password_reset_on;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_password_reset_on')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setPasswordResetOnAction',));
                }
                not_api_user_password_reset_on:

                // api_user_password_reset_off
                if (preg_match('#^/api/user/(?P<userId>\\d+)/password_reset/0$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_password_reset_off;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_password_reset_off')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setPasswordResetOffAction',));
                }
                not_api_user_password_reset_off:

                // api_user_check_password
                if (preg_match('#^/api/user/(?P<userId>\\d+)/check_password$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_check_password;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_check_password')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::checkPasswordAction',));
                }
                not_api_user_check_password:

                // api_user_get_password
                if (preg_match('#^/api/user/(?P<userId>\\d+)/password$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_password;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_password')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getPasswordAction',));
                }
                not_api_user_get_password:

                // api_user_set_info
                if (preg_match('#^/api/user/(?P<userId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_set_info;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_set_info')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setUserAction',));
                }
                not_api_user_set_info:

            }

            // api_v2_users
            if ($pathinfo === '/api/v2/users') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_v2_users;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getUsersAction',  '_route' => 'api_v2_users',);
            }
            not_api_v2_users:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_users
                if ($pathinfo === '/api/users') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_users;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getUsersAction',  '_route' => 'api_users',);
                }
                not_api_users:

                // api_user
                if (preg_match('#^/api/user/(?P<userId>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getUserAction',));
                }
                not_api_user:

                // api_user_hierarchy
                if ($pathinfo === '/api/user/hierarchy') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_hierarchy;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getHierarchyAction',  '_route' => 'api_user_hierarchy',);
                }
                not_api_user_hierarchy:

            }

            // api_v2_user_hierarchy
            if ($pathinfo === '/api/v2/user/hierarchy') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_v2_user_hierarchy;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getHierarchyV2Action',  '_route' => 'api_v2_user_hierarchy',);
            }
            not_api_v2_user_hierarchy:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_user_hierarchy_by_domain
                if ($pathinfo === '/api/user/hierarchy_by_domain') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_hierarchy_by_domain;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getHierarchyByDomainAction',  '_route' => 'api_user_hierarchy_by_domain',);
                }
                not_api_user_hierarchy_by_domain:

                // api_user_list
                if ($pathinfo === '/api/user/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::listAction',  '_route' => 'api_user_list',);
                }
                not_api_user_list:

                // api_user_change_parent
                if (preg_match('#^/api/user/(?P<userId>\\d+)/change_parent/(?P<parentId>\\d+)$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_change_parent;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_change_parent')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::changeParentAction',));
                }
                not_api_user_change_parent:

                // api_user_check_unique
                if ($pathinfo === '/api/user/check_unique') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_check_unique;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::userCheckUniqueAction',  '_route' => 'api_user_check_unique',);
                }
                not_api_user_check_unique:

            }

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_domain_set_bank
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/bank$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_domain_set_bank;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_set_bank')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setDomainBankAction',));
                }
                not_api_domain_set_bank:

                // api_domain_get_bank
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/bank$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_domain_get_bank;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_domain_get_bank')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getDomainBankAction',));
                }
                not_api_domain_get_bank:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_user_edit_email
                if (preg_match('#^/api/user/(?P<userId>\\d+)/email$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_edit_email;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_edit_email')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::editEmailAction',));
                }
                not_api_user_edit_email:

                // api_user_get_error_number
                if (preg_match('#^/api/user/(?P<userId>\\d+)/login_log/error_number$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_error_number;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_error_number')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getErrorNumberAction',));
                }
                not_api_user_get_error_number:

                // api_user_get_previous_login
                if (preg_match('#^/api/user/(?P<userId>\\d+)/login_log/previous$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_previous_login;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_previous_login')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getPreviousLoginAction',));
                }
                not_api_user_get_previous_login:

                // api_user_get_login_log
                if (preg_match('#^/api/user/(?P<userId>\\d+)/login_log$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_login_log;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_login_log')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getLoginLogAction',));
                }
                not_api_user_get_login_log:

            }

            if (0 === strpos($pathinfo, '/api/domain')) {
                // api_get_modified_user_by_domain
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/modified_user$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_modified_user_by_domain;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_modified_user_by_domain')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getModifiedUserByDomainAction',));
                }
                not_api_get_modified_user_by_domain:

                // api_get_removed_user_by_domain
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/removed_user$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_removed_user_by_domain;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_removed_user_by_domain')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getRemovedUserByDomainAction',));
                }
                not_api_get_removed_user_by_domain:

                // api_get_member_detail_by_domain
                if (preg_match('#^/api/domain/(?P<domain>\\d+)/member_detail$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_member_detail_by_domain;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_member_detail_by_domain')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getMemberDetailAction',));
                }
                not_api_get_member_detail_by_domain:

            }

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_get_user_deposited
                if (preg_match('#^/api/user/(?P<userId>\\d+)/deposited$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_deposited;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_deposited')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getDepositedAction',));
                }
                not_api_get_user_deposited:

                // api_get_user_stat
                if ($pathinfo === '/api/user/stat') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_stat;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getStatAction',  '_route' => 'api_get_user_stat',);
                }
                not_api_get_user_stat:

                // api_user_hidden_test_on
                if (preg_match('#^/api/user/(?P<userId>\\d+)/hidden_test/1$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_hidden_test_on;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_hidden_test_on')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setHiddenTestOnAction',));
                }
                not_api_user_hidden_test_on:

                // api_user_hidden_test_off
                if (preg_match('#^/api/user/(?P<userId>\\d+)/hidden_test/0$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_hidden_test_off;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_hidden_test_off')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::setHiddenTestOffAction',));
                }
                not_api_user_hidden_test_off:

                // api_get_user_ancestor_id
                if (preg_match('#^/api/user/(?P<userId>\\d+)/ancestor_id$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_ancestor_id;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_ancestor_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getAncestorIdAction',));
                }
                not_api_get_user_ancestor_id:

                // api_get_user_children_id
                if (preg_match('#^/api/user/(?P<userId>\\d+)/children_id$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_children_id;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_children_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getChildrenIdAction',));
                }
                not_api_get_user_children_id:

            }

            // api_get_removed_user
            if (0 === strpos($pathinfo, '/api/removed_user') && preg_match('#^/api/removed_user/(?P<userId>\\d+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_removed_user;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_removed_user')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getRemovedUserByIdAction',));
            }
            not_api_get_removed_user:

            // api_v2_get_removed_user_by_time
            if ($pathinfo === '/api/v2/removed_user_by_time') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_v2_get_removed_user_by_time;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getRemovedUserByTimeAction',  '_route' => 'api_v2_get_removed_user_by_time',);
            }
            not_api_v2_get_removed_user_by_time:

            // api_get_removed_user_by_time
            if ($pathinfo === '/api/removed_user_by_time') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_removed_user_by_time;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getRemovedUserByTimeAction',  '_route' => 'api_get_removed_user_by_time',);
            }
            not_api_get_removed_user_by_time:

            // api_user_get_email
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/email$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_user_get_email;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_email')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getEmailAction',));
            }
            not_api_user_get_email:

            // api_removed_users
            if ($pathinfo === '/api/removed_users') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_removed_users;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getRemovedUsersAction',  '_route' => 'api_removed_users',);
            }
            not_api_removed_users:

            if (0 === strpos($pathinfo, '/api/user')) {
                // api_user_get_username
                if ($pathinfo === '/api/users/username') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_username;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserController::getUsernameAction',  '_route' => 'api_user_get_username',);
                }
                not_api_user_get_username:

                // api_get_user_created_per_ip
                if ($pathinfo === '/api/user/created_per_ip') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_user_created_per_ip;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserCreatedPerIpController::getAction',  '_route' => 'api_get_user_created_per_ip',);
                }
                not_api_get_user_created_per_ip:

                // api_user_edit_detail
                if (preg_match('#^/api/user/(?P<userId>\\d+)/detail$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_user_edit_detail;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_edit_detail')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::editUserDetailAction',));
                }
                not_api_user_edit_detail:

                // api_user_get_detail
                if (preg_match('#^/api/user/(?P<userId>\\d+)/detail$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_get_detail;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_get_detail')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::getUserDetailByUserAction',));
                }
                not_api_user_get_detail:

                // api_user_detail_list
                if ($pathinfo === '/api/user_detail/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_user_detail_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::listAction',  '_route' => 'api_user_detail_list',);
                }
                not_api_user_detail_list:

            }

            // api_v2_user_detail_list
            if ($pathinfo === '/api/v2/user_detail/list') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_v2_user_detail_list;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::listV2Action',  '_route' => 'api_v2_user_detail_list',);
            }
            not_api_v2_user_detail_list:

            if (0 === strpos($pathinfo, '/api/user')) {
                if (0 === strpos($pathinfo, '/api/user_detail')) {
                    // api_user_detail_list_by_domain
                    if ($pathinfo === '/api/user_detail/list_by_domain') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_user_detail_list_by_domain;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::listByDomainAction',  '_route' => 'api_user_detail_list_by_domain',);
                    }
                    not_api_user_detail_list_by_domain:

                    // api_user_detail_check_unique
                    if ($pathinfo === '/api/user_detail/check_unique') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_user_detail_check_unique;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::userDetailCheckUniqueAction',  '_route' => 'api_user_detail_check_unique',);
                    }
                    not_api_user_detail_check_unique:

                }

                // api_create_promotion
                if (preg_match('#^/api/user/(?P<userId>\\d+)/promotion$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_create_promotion;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_create_promotion')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::createPromotionAction',));
                }
                not_api_create_promotion:

                // api_edit_promotion
                if (preg_match('#^/api/user/(?P<userId>\\d+)/promotion$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_edit_promotion;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_edit_promotion')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::editPromotionAction',));
                }
                not_api_edit_promotion:

                // api_get_promotion
                if (preg_match('#^/api/user/(?P<userId>\\d+)/promotion$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_promotion;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_promotion')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::getPromotionAction',));
                }
                not_api_get_promotion:

                // api_delete_promotion
                if (preg_match('#^/api/user/(?P<userId>\\d+)/promotion$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_api_delete_promotion;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_delete_promotion')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserDetailController::deletePromotionAction',));
                }
                not_api_delete_promotion:

                if (0 === strpos($pathinfo, '/api/user_level')) {
                    // api_get_user_level
                    if ($pathinfo === '/api/user_level') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_api_get_user_level;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserLevelController::getAction',  '_route' => 'api_get_user_level',);
                    }
                    not_api_get_user_level:

                    // api_set_user_level
                    if ($pathinfo === '/api/user_level') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_set_user_level;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserLevelController::setAction',  '_route' => 'api_set_user_level',);
                    }
                    not_api_set_user_level:

                    // api_lock_user_level
                    if ($pathinfo === '/api/user_level/lock') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_lock_user_level;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserLevelController::lockAction',  '_route' => 'api_lock_user_level',);
                    }
                    not_api_lock_user_level:

                    // api_unlock_user_level
                    if ($pathinfo === '/api/user_level/unlock') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_api_unlock_user_level;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\UserLevelController::unlockAction',  '_route' => 'api_unlock_user_level',);
                    }
                    not_api_unlock_user_level:

                }

            }

            if (0 === strpos($pathinfo, '/api/wallet')) {
                // api_wallet_get_payway
                if ($pathinfo === '/api/wallet/payway') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_wallet_get_payway;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WalletController::getAction',  '_route' => 'api_wallet_get_payway',);
                }
                not_api_wallet_get_payway:

                // api_get_deposit_withdraw
                if ($pathinfo === '/api/wallet/deposit_withdraw') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_get_deposit_withdraw;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WalletController::getDepositWithdrawAction',  '_route' => 'api_get_deposit_withdraw',);
                }
                not_api_get_deposit_withdraw:

            }

            // api_user_cash_withdraw
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cash/withdraw$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_api_user_cash_withdraw;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_user_cash_withdraw')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::withdrawAction',));
            }
            not_api_user_cash_withdraw:

            // api_cash_withdraw_confirm
            if (0 === strpos($pathinfo, '/api/cash/withdraw') && preg_match('#^/api/cash/withdraw/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_cash_withdraw_confirm;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_withdraw_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::confirmWithdrawAction',));
            }
            not_api_cash_withdraw_confirm:

            // api_withdraw_account_confirm
            if (0 === strpos($pathinfo, '/api/withdraw') && preg_match('#^/api/withdraw/(?P<withdrawEntryId>\\d+)/account_confirm$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_withdraw_account_confirm;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_withdraw_account_confirm')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::withdrawAccountConfirmAction',));
            }
            not_api_withdraw_account_confirm:

            if (0 === strpos($pathinfo, '/api/cash/withdraw')) {
                // api_cash_withdraw_memo
                if (preg_match('#^/api/cash/withdraw/(?P<id>\\d+)/memo$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_cash_withdraw_memo;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_withdraw_memo')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::setWithdrawMemoAction',));
                }
                not_api_cash_withdraw_memo:

                // api_cash_withdraw_lock
                if (preg_match('#^/api/cash/withdraw/(?P<entryId>\\d+)/lock$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_cash_withdraw_lock;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_withdraw_lock')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::lockAction',));
                }
                not_api_cash_withdraw_lock:

                // api_cash_withdraw_unlock
                if (preg_match('#^/api/cash/withdraw/(?P<entryId>\\d+)/unlock$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_api_cash_withdraw_unlock;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_withdraw_unlock')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::unlockAction',));
                }
                not_api_cash_withdraw_unlock:

                // api_cash_get_withdraw_entry_by_id
                if (preg_match('#^/api/cash/withdraw/(?P<id>\\d+)$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_get_withdraw_entry_by_id;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_get_withdraw_entry_by_id')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::getWithdrawEntryAction',));
                }
                not_api_cash_get_withdraw_entry_by_id:

            }

            // api_cash_get_withdraw_entry
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/cash/withdraw$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_cash_get_withdraw_entry;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_cash_get_withdraw_entry')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::getWithdrawEntriesAction',));
            }
            not_api_cash_get_withdraw_entry:

            if (0 === strpos($pathinfo, '/api/cash/withdraw')) {
                // api_cash_get_withdraw_entry_list
                if ($pathinfo === '/api/cash/withdraw/list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_get_withdraw_entry_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::getWithdrawEntriesListAction',  '_route' => 'api_cash_get_withdraw_entry_list',);
                }
                not_api_cash_get_withdraw_entry_list:

                // api_cash_get_withdraw_confirmed_list
                if ($pathinfo === '/api/cash/withdraw/confirmed_list') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_get_withdraw_confirmed_list;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::getWithdrawConfirmedListAction',  '_route' => 'api_cash_get_withdraw_confirmed_list',);
                }
                not_api_cash_get_withdraw_confirmed_list:

                // api_cash_get_withdraw_report
                if ($pathinfo === '/api/cash/withdraw/report') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_cash_get_withdraw_report;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::getWithdrawReportAction',  '_route' => 'api_cash_get_withdraw_report',);
                }
                not_api_cash_get_withdraw_report:

            }

            // api_get_user_withdraw_stat
            if (0 === strpos($pathinfo, '/api/user') && preg_match('#^/api/user/(?P<userId>\\d+)/withdraw_stat$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_user_withdraw_stat;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_user_withdraw_stat')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::getWithdrawStatAction',));
            }
            not_api_get_user_withdraw_stat:

            // api_get_withdraw_tracking
            if (0 === strpos($pathinfo, '/api/withdraw') && preg_match('#^/api/withdraw/(?P<withdrawEntryId>\\d+)/tracking$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_get_withdraw_tracking;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'api_get_withdraw_tracking')), array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\WithdrawController::getWithdrawTrackingAction',));
            }
            not_api_get_withdraw_tracking:

        }

        // monitor
        if ($pathinfo === '/monitor') {
            return array (  '_controller' => 'BB\\DurianBundle\\Controller\\MonitorController::indexAction',  '_route' => 'monitor',);
        }

        if (0 === strpos($pathinfo, '/api/monitor')) {
            // api_monitor_background
            if ($pathinfo === '/api/monitor/background') {
                return array (  '_controller' => 'BB\\DurianBundle\\Controller\\MonitorController::backgroundAction',  '_route' => 'api_monitor_background',);
            }

            // api_monitor_database
            if ($pathinfo === '/api/monitor/database') {
                return array (  '_controller' => 'BB\\DurianBundle\\Controller\\MonitorController::databaseAction',  '_route' => 'api_monitor_database',);
            }

            // api_monitor_queue
            if ($pathinfo === '/api/monitor/queue') {
                return array (  '_controller' => 'BB\\DurianBundle\\Controller\\MonitorController::queueAction',  '_route' => 'api_monitor_queue',);
            }

        }

        // home
        if (rtrim($pathinfo, '/') === '') {
            if (substr($pathinfo, -1) !== '/') {
                return $this->redirect($pathinfo.'/', 'home');
            }

            return array (  '_controller' => 'BB\\DurianBundle\\Controller\\DefaultController::indexAction',  '_route' => 'home',);
        }

        // bb_durian_default_version
        if ($pathinfo === '/version') {
            return array (  '_controller' => 'BB\\DurianBundle\\Controller\\DefaultController::versionAction',  '_route' => 'bb_durian_default_version',);
        }

        if (0 === strpos($pathinfo, '/d')) {
            if (0 === strpos($pathinfo, '/demo')) {
                // demo_default
                if (rtrim($pathinfo, '/') === '/demo') {
                    if (substr($pathinfo, -1) !== '/') {
                        return $this->redirect($pathinfo.'/', 'demo_default');
                    }

                    return array (  'group' => '',  'item' => 'portal',  '_controller' => 'BB\\DurianBundle\\Controller\\DemoController::demoAction',  '_route' => 'demo_default',);
                }

                // demo
                if (preg_match('#^/demo(?:/(?P<group>[^/]++)(?:/(?P<item>[^/]++))?)?$#s', $pathinfo, $matches)) {
                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'demo')), array (  'group' => '',  'item' => 'portal',  '_controller' => 'BB\\DurianBundle\\Controller\\DemoController::demoAction',));
                }

            }

            // doc
            if (0 === strpos($pathinfo, '/doc') && preg_match('#^/doc(?:/(?P<item>.+))?$#s', $pathinfo, $matches)) {
                return $this->mergeDefaults(array_replace($matches, array('_route' => 'doc')), array (  'item' => '',  '_controller' => 'BB\\DurianBundle\\Controller\\DocController::docAction',));
            }

        }

        // api_validate_share_limit
        if ($pathinfo === '/api/validate_share_limit') {
            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                $allow = array_merge($allow, array('GET', 'HEAD'));
                goto not_api_validate_share_limit;
            }

            return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::validateShareLimitAction',  '_route' => 'api_validate_share_limit',);
        }
        not_api_validate_share_limit:

        if (0 === strpos($pathinfo, '/tools')) {
            // tools_check
            if ($pathinfo === '/tools/check') {
                return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::checkAction',  '_route' => 'tools_check',);
            }

            // tools_domain_map
            if ($pathinfo === '/tools/domain_map') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_tools_domain_map;
                }

                return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::domainMapAction',  '_route' => 'tools_domain_map',);
            }
            not_tools_domain_map:

            // api_update_share_limit
            if ($pathinfo === '/tools/update_share_limit') {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_api_update_share_limit;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::updateSharelimitAction',  '_route' => 'api_update_share_limit',);
            }
            not_api_update_share_limit:

        }

        if (0 === strpos($pathinfo, '/api/tools')) {
            // api_otp_server_connection
            if ($pathinfo === '/api/tools/otp_server_connection') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_otp_server_connection;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::otpServerConnectionAction',  '_route' => 'api_otp_server_connection',);
            }
            not_api_otp_server_connection:

            // api_tools_get_binding_users_by_device
            if ($pathinfo === '/api/tools/device/users') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_tools_get_binding_users_by_device;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::listBindingUsersByDeviceAction',  '_route' => 'api_tools_get_binding_users_by_device',);
            }
            not_api_tools_get_binding_users_by_device:

        }

        if (0 === strpos($pathinfo, '/t')) {
            if (0 === strpos($pathinfo, '/tools')) {
                // tools_check_speed
                if ($pathinfo === '/tools/check_speed') {
                    return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::checkSpeedAction',  '_route' => 'tools_check_speed',);
                }

                if (0 === strpos($pathinfo, '/tools/d')) {
                    if (0 === strpos($pathinfo, '/tools/deposit')) {
                        // tools_deposit_check
                        if ($pathinfo === '/tools/deposit/check') {
                            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                                $allow = array_merge($allow, array('GET', 'HEAD'));
                                goto not_tools_deposit_check;
                            }

                            return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::depositCheckAction',  '_route' => 'tools_deposit_check',);
                        }
                        not_tools_deposit_check:

                        // tools_deposit_test
                        if ($pathinfo === '/tools/deposit/test') {
                            if ($this->context->getMethod() != 'POST') {
                                $allow[] = 'POST';
                                goto not_tools_deposit_test;
                            }

                            return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::depositTestAction',  '_route' => 'tools_deposit_test',);
                        }
                        not_tools_deposit_test:

                    }

                    // tools_display_background_process_name
                    if ($pathinfo === '/tools/display_background_process_name') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_tools_display_background_process_name;
                        }

                        return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::displayBgProcessNameAction',  '_route' => 'tools_display_background_process_name',);
                    }
                    not_tools_display_background_process_name:

                }

                // tools_set_background_process
                if ($pathinfo === '/tools/set_background_process') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_tools_set_background_process;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::setBgProcessAction',  '_route' => 'tools_set_background_process',);
                }
                not_tools_set_background_process:

                // tools_revise_entry
                if ($pathinfo === '/tools/revise_entry') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_tools_revise_entry;
                    }

                    return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::reviseEntryAction',  '_route' => 'tools_revise_entry',);
                }
                not_tools_revise_entry:

                if (0 === strpos($pathinfo, '/tools/cash')) {
                    // tools_cash_entry_revise
                    if ($pathinfo === '/tools/cash_entry/revise') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_tools_cash_entry_revise;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::reviseCashEntryAction',  '_route' => 'tools_cash_entry_revise',);
                    }
                    not_tools_cash_entry_revise:

                    // tools_cashfake_entry_revise
                    if ($pathinfo === '/tools/cashfake_entry/revise') {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_tools_cashfake_entry_revise;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::reviseCashfakeEntryAction',  '_route' => 'tools_cashfake_entry_revise',);
                    }
                    not_tools_cashfake_entry_revise:

                }

                // tools_error_remove
                if ($pathinfo === '/tools/error/remove') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_tools_error_remove;
                    }

                    return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::removeErrorAction',  '_route' => 'tools_error_remove',);
                }
                not_tools_error_remove:

                // tools_set_random_float_vendor
                if ($pathinfo === '/tools/set_random_float_vendor') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_tools_set_random_float_vendor;
                    }

                    return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::setRandomFloatVendor',  '_route' => 'tools_set_random_float_vendor',);
                }
                not_tools_set_random_float_vendor:

                // tools_repair_entry_page
                if ($pathinfo === '/tools/repair_entry_page') {
                    return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::repairEntryPageAction',  '_route' => 'tools_repair_entry_page',);
                }

                // tools_show_entry
                if ($pathinfo === '/tools/show_entry') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_tools_show_entry;
                    }

                    return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::showEntryAction',  '_route' => 'tools_show_entry',);
                }
                not_tools_show_entry:

                // tools_execute_repair_entry
                if ($pathinfo === '/tools/execute_repair_entry') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_tools_execute_repair_entry;
                    }

                    return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::executeRepairEntryAction',  '_route' => 'tools_execute_repair_entry',);
                }
                not_tools_execute_repair_entry:

                // tools_display_ip_blacklist
                if ($pathinfo === '/tools/display_ip_blacklist') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_tools_display_ip_blacklist;
                    }

                    return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::displayIpBlacklistAction',  '_route' => 'tools_display_ip_blacklist',);
                }
                not_tools_display_ip_blacklist:

                // tools_get_ip_activity_record
                if ($pathinfo === '/tools/get_ip_activity_record') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_tools_get_ip_activity_record;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::getIpActivityRecordAction',  '_route' => 'tools_get_ip_activity_record',);
                }
                not_tools_get_ip_activity_record:

                if (0 === strpos($pathinfo, '/tools/d')) {
                    // tools_display_kue_job
                    if ($pathinfo === '/tools/display_kue_job') {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_tools_display_kue_job;
                        }

                        return array (  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::displayKueJobAction',  '_route' => 'tools_display_kue_job',);
                    }
                    not_tools_display_kue_job:

                    // tools_delete_kue_job
                    if ($pathinfo === '/tools/delete_kue_job') {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_tools_delete_kue_job;
                        }

                        return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::deleteKueJobAction',  '_route' => 'tools_delete_kue_job',);
                    }
                    not_tools_delete_kue_job:

                }

                // tools_redo_kue_job
                if ($pathinfo === '/tools/redo_kue_job') {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_tools_redo_kue_job;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\ToolsController::redoKueJobAction',  '_route' => 'tools_redo_kue_job',);
                }
                not_tools_redo_kue_job:

            }

            // test
            if ($pathinfo === '/test') {
                return array (  '_controller' => 'BB\\DurianBundle\\Controller\\TestController::testAction',  '_route' => 'test',);
            }

        }

        if (0 === strpos($pathinfo, '/api/test')) {
            // api_test_timeout
            if ($pathinfo === '/api/test/timeout') {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_api_test_timeout;
                }

                return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TestController::timeoutAction',  '_route' => 'api_test_timeout',);
            }
            not_api_test_timeout:

            if (0 === strpos($pathinfo, '/api/test/c')) {
                // api_test_connection
                if ($pathinfo === '/api/test/connection') {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_api_test_connection;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TestController::connectionAction',  '_route' => 'api_test_connection',);
                }
                not_api_test_connection:

                // api_test_checkdb
                if ($pathinfo === '/api/test/checkdb') {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_api_test_checkdb;
                    }

                    return array (  '_format' => 'json',  '_controller' => 'BB\\DurianBundle\\Controller\\TestController::checkDbAction',  '_route' => 'api_test_checkdb',);
                }
                not_api_test_checkdb:

            }

        }

        // log_operation
        if ($pathinfo === '/log_operation') {
            if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                $allow = array_merge($allow, array('GET', 'HEAD'));
                goto not_log_operation;
            }

            return array (  '_controller' => 'BB\\DurianBundle\\Controller\\LogOperationController::getLogOperationAction',  '_route' => 'log_operation',);
        }
        not_log_operation:

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
