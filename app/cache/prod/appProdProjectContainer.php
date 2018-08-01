<?php

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/*
 * appProdProjectContainer.
 *
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 */
class appProdProjectContainer extends Container
{
    private $parameters;
    private $targetDirs = array();

    /*
     * Constructor.
     */
    public function __construct()
    {
        $dir = __DIR__;
        for ($i = 1; $i <= 4; ++$i) {
            $this->targetDirs[$i] = $dir = dirname($dir);
        }
        $this->parameters = $this->getDefaultParameters();

        $this->services =
        $this->scopedServices =
        $this->scopeStacks = array();
        $this->scopes = array('request' => 'container');
        $this->scopeChildren = array('request' => array());
        $this->methodMap = array(
            'annotation_reader' => 'getAnnotationReaderService',
            'assets.context' => 'getAssets_ContextService',
            'assets.packages' => 'getAssets_PackagesService',
            'cache_clearer' => 'getCacheClearerService',
            'cache_warmer' => 'getCacheWarmerService',
            'config_cache_factory' => 'getConfigCacheFactoryService',
            'controller_name_converter' => 'getControllerNameConverterService',
            'debug.debug_handlers_listener' => 'getDebug_DebugHandlersListenerService',
            'debug.stopwatch' => 'getDebug_StopwatchService',
            'doctrine' => 'getDoctrineService',
            'doctrine.dbal.connection_factory' => 'getDoctrine_Dbal_ConnectionFactoryService',
            'doctrine.dbal.default_connection' => 'getDoctrine_Dbal_DefaultConnectionService',
            'doctrine.dbal.entry_connection' => 'getDoctrine_Dbal_EntryConnectionService',
            'doctrine.dbal.his_connection' => 'getDoctrine_Dbal_HisConnectionService',
            'doctrine.dbal.ip_blocker_connection' => 'getDoctrine_Dbal_IpBlockerConnectionService',
            'doctrine.dbal.outside_connection' => 'getDoctrine_Dbal_OutsideConnectionService',
            'doctrine.dbal.share_connection' => 'getDoctrine_Dbal_ShareConnectionService',
            'doctrine.orm.default_entity_listener_resolver' => 'getDoctrine_Orm_DefaultEntityListenerResolverService',
            'doctrine.orm.default_entity_manager' => 'getDoctrine_Orm_DefaultEntityManagerService',
            'doctrine.orm.default_entity_manager.property_info_extractor' => 'getDoctrine_Orm_DefaultEntityManager_PropertyInfoExtractorService',
            'doctrine.orm.default_manager_configurator' => 'getDoctrine_Orm_DefaultManagerConfiguratorService',
            'doctrine.orm.default_result_cache' => 'getDoctrine_Orm_DefaultResultCacheService',
            'doctrine.orm.entry_entity_listener_resolver' => 'getDoctrine_Orm_EntryEntityListenerResolverService',
            'doctrine.orm.entry_entity_manager' => 'getDoctrine_Orm_EntryEntityManagerService',
            'doctrine.orm.entry_entity_manager.property_info_extractor' => 'getDoctrine_Orm_EntryEntityManager_PropertyInfoExtractorService',
            'doctrine.orm.entry_manager_configurator' => 'getDoctrine_Orm_EntryManagerConfiguratorService',
            'doctrine.orm.his_entity_listener_resolver' => 'getDoctrine_Orm_HisEntityListenerResolverService',
            'doctrine.orm.his_entity_manager' => 'getDoctrine_Orm_HisEntityManagerService',
            'doctrine.orm.his_entity_manager.property_info_extractor' => 'getDoctrine_Orm_HisEntityManager_PropertyInfoExtractorService',
            'doctrine.orm.his_manager_configurator' => 'getDoctrine_Orm_HisManagerConfiguratorService',
            'doctrine.orm.ip_blocker_entity_listener_resolver' => 'getDoctrine_Orm_IpBlockerEntityListenerResolverService',
            'doctrine.orm.ip_blocker_entity_manager' => 'getDoctrine_Orm_IpBlockerEntityManagerService',
            'doctrine.orm.ip_blocker_entity_manager.property_info_extractor' => 'getDoctrine_Orm_IpBlockerEntityManager_PropertyInfoExtractorService',
            'doctrine.orm.ip_blocker_manager_configurator' => 'getDoctrine_Orm_IpBlockerManagerConfiguratorService',
            'doctrine.orm.naming_strategy.default' => 'getDoctrine_Orm_NamingStrategy_DefaultService',
            'doctrine.orm.outside_entity_listener_resolver' => 'getDoctrine_Orm_OutsideEntityListenerResolverService',
            'doctrine.orm.outside_entity_manager' => 'getDoctrine_Orm_OutsideEntityManagerService',
            'doctrine.orm.outside_entity_manager.property_info_extractor' => 'getDoctrine_Orm_OutsideEntityManager_PropertyInfoExtractorService',
            'doctrine.orm.outside_manager_configurator' => 'getDoctrine_Orm_OutsideManagerConfiguratorService',
            'doctrine.orm.quote_strategy.default' => 'getDoctrine_Orm_QuoteStrategy_DefaultService',
            'doctrine.orm.share_entity_listener_resolver' => 'getDoctrine_Orm_ShareEntityListenerResolverService',
            'doctrine.orm.share_entity_manager' => 'getDoctrine_Orm_ShareEntityManagerService',
            'doctrine.orm.share_entity_manager.property_info_extractor' => 'getDoctrine_Orm_ShareEntityManager_PropertyInfoExtractorService',
            'doctrine.orm.share_manager_configurator' => 'getDoctrine_Orm_ShareManagerConfiguratorService',
            'doctrine.orm.validator.unique' => 'getDoctrine_Orm_Validator_UniqueService',
            'doctrine.orm.validator_initializer' => 'getDoctrine_Orm_ValidatorInitializerService',
            'doctrine_cache.providers.configurable_filesystem_provider_type' => 'getDoctrineCache_Providers_ConfigurableFilesystemProviderTypeService',
            'doctrine_cache.providers.doctrine.orm.default_result_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_DefaultResultCacheService',
            'doctrine_cache.providers.doctrine.orm.entry_metadata_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_EntryMetadataCacheService',
            'doctrine_cache.providers.doctrine.orm.entry_query_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_EntryQueryCacheService',
            'doctrine_cache.providers.doctrine.orm.entry_result_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_EntryResultCacheService',
            'doctrine_cache.providers.doctrine.orm.his_result_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_HisResultCacheService',
            'doctrine_cache.providers.doctrine.orm.ip_blocker_metadata_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_IpBlockerMetadataCacheService',
            'doctrine_cache.providers.doctrine.orm.ip_blocker_query_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_IpBlockerQueryCacheService',
            'doctrine_cache.providers.doctrine.orm.ip_blocker_result_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_IpBlockerResultCacheService',
            'doctrine_cache.providers.doctrine.orm.outside_metadata_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_OutsideMetadataCacheService',
            'doctrine_cache.providers.doctrine.orm.outside_query_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_OutsideQueryCacheService',
            'doctrine_cache.providers.doctrine.orm.outside_result_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_OutsideResultCacheService',
            'doctrine_cache.providers.doctrine.orm.share_metadata_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_ShareMetadataCacheService',
            'doctrine_cache.providers.doctrine.orm.share_query_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_ShareQueryCacheService',
            'doctrine_cache.providers.doctrine.orm.share_result_cache' => 'getDoctrineCache_Providers_Doctrine_Orm_ShareResultCacheService',
            'durian.activate_sl_next' => 'getDurian_ActivateSlNextService',
            'durian.ancestor_manager' => 'getDurian_AncestorManagerService',
            'durian.auto_confirm_match_maker' => 'getDurian_AutoConfirmMatchMakerService',
            'durian.auto_remit_checker' => 'getDurian_AutoRemitCheckerService',
            'durian.auto_remit_maker' => 'getDurian_AutoRemitMakerService',
            'durian.batch_op' => 'getDurian_BatchOpService',
            'durian.bitcoin_deposit_entry_id_generator' => 'getDurian_BitcoinDepositEntryIdGeneratorService',
            'durian.bitcoin_withdraw_entry_id_generator' => 'getDurian_BitcoinWithdrawEntryIdGeneratorService',
            'durian.blacklist_validator' => 'getDurian_BlacklistValidatorService',
            'durian.block_chain' => 'getDurian_BlockChainService',
            'durian.captcha_genie' => 'getDurian_CaptchaGenieService',
            'durian.card_entry_id_generator' => 'getDurian_CardEntryIdGeneratorService',
            'durian.card_operator' => 'getDurian_CardOperatorService',
            'durian.cash_entry_id_generator' => 'getDurian_CashEntryIdGeneratorService',
            'durian.cash_fake_entry_id_generator' => 'getDurian_CashFakeEntryIdGeneratorService',
            'durian.cash_fake_helper' => 'getDurian_CashFakeHelperService',
            'durian.cash_helper' => 'getDurian_CashHelperService',
            'durian.cashfake_op' => 'getDurian_CashfakeOpService',
            'durian.credit_op' => 'getDurian_CreditOpService',
            'durian.currency' => 'getDurian_CurrencyService',
            'durian.deposit_entry_id_generator' => 'getDurian_DepositEntryIdGeneratorService',
            'durian.deposit_operator' => 'getDurian_DepositOperatorService',
            'durian.domain_id_generator' => 'getDurian_DomainIdGeneratorService',
            'durian.domain_msg' => 'getDurian_DomainMsgService',
            'durian.domain_validator' => 'getDurian_DomainValidatorService',
            'durian.exception_listener' => 'getDurian_ExceptionListenerService',
            'durian.exchange' => 'getDurian_ExchangeService',
            'durian.http_curl_worker' => 'getDurian_HttpCurlWorkerService',
            'durian.italking_operator' => 'getDurian_ItalkingOperatorService',
            'durian.italking_worker' => 'getDurian_ItalkingWorkerService',
            'durian.kue_manager' => 'getDurian_KueManagerService',
            'durian.logger_manager' => 'getDurian_LoggerManagerService',
            'durian.logger_sql' => 'getDurian_LoggerSqlService',
            'durian.login_validator' => 'getDurian_LoginValidatorService',
            'durian.maintain_operator' => 'getDurian_MaintainOperatorService',
            'durian.mobile_whitelist_worker' => 'getDurian_MobileWhitelistWorkerService',
            'durian.monitor.background' => 'getDurian_Monitor_BackgroundService',
            'durian.oauth2_server' => 'getDurian_Oauth2ServerService',
            'durian.oauth_generator' => 'getDurian_OauthGeneratorService',
            'durian.op' => 'getDurian_OpService',
            'durian.operation_logger' => 'getDurian_OperationLoggerService',
            'durian.otp_worker' => 'getDurian_OtpWorkerService',
            'durian.parameter_handler' => 'getDurian_ParameterHandlerService',
            'durian.payment_logger' => 'getDurian_PaymentLoggerService',
            'durian.payment_operator' => 'getDurian_PaymentOperatorService',
            'durian.rd1_maintain_worker' => 'getDurian_Rd1MaintainWorkerService',
            'durian.rd1_operator' => 'getDurian_Rd1OperatorService',
            'durian.rd1_whitelist_worker' => 'getDurian_Rd1WhitelistWorkerService',
            'durian.rd2_operator' => 'getDurian_Rd2OperatorService',
            'durian.rd3_maintain_worker' => 'getDurian_Rd3MaintainWorkerService',
            'durian.rd3_operator' => 'getDurian_Rd3OperatorService',
            'durian.remit_auto_confirm_logger' => 'getDurian_RemitAutoConfirmLoggerService',
            'durian.remit_helper' => 'getDurian_RemitHelperService',
            'durian.remit_order_generator' => 'getDurian_RemitOrderGeneratorService',
            'durian.sensitive_logger' => 'getDurian_SensitiveLoggerService',
            'durian.session_broker' => 'getDurian_SessionBrokerService',
            'durian.share_dealer' => 'getDurian_ShareDealerService',
            'durian.share_mocker' => 'getDurian_ShareMockerService',
            'durian.share_option_generator' => 'getDurian_ShareOptionGeneratorService',
            'durian.share_scheduled_for_update' => 'getDurian_ShareScheduledForUpdateService',
            'durian.share_validator' => 'getDurian_ShareValidatorService',
            'durian.user_detail_validator' => 'getDurian_UserDetailValidatorService',
            'durian.user_id_generator' => 'getDurian_UserIdGeneratorService',
            'durian.user_manager' => 'getDurian_UserManagerService',
            'durian.user_payway' => 'getDurian_UserPaywayService',
            'durian.user_validator' => 'getDurian_UserValidatorService',
            'durian.userdetail_generator' => 'getDurian_UserdetailGeneratorService',
            'durian.validator' => 'getDurian_ValidatorService',
            'durian.withdraw_entry_id_generator' => 'getDurian_WithdrawEntryIdGeneratorService',
            'durian.withdraw_helper' => 'getDurian_WithdrawHelperService',
            'event_dispatcher' => 'getEventDispatcherService',
            'file_locator' => 'getFileLocatorService',
            'filesystem' => 'getFilesystemService',
            'form.csrf_provider' => 'getForm_CsrfProviderService',
            'form.factory' => 'getForm_FactoryService',
            'form.registry' => 'getForm_RegistryService',
            'form.resolved_type_factory' => 'getForm_ResolvedTypeFactoryService',
            'form.server_params' => 'getForm_ServerParamsService',
            'form.type.birthday' => 'getForm_Type_BirthdayService',
            'form.type.button' => 'getForm_Type_ButtonService',
            'form.type.checkbox' => 'getForm_Type_CheckboxService',
            'form.type.choice' => 'getForm_Type_ChoiceService',
            'form.type.collection' => 'getForm_Type_CollectionService',
            'form.type.country' => 'getForm_Type_CountryService',
            'form.type.currency' => 'getForm_Type_CurrencyService',
            'form.type.date' => 'getForm_Type_DateService',
            'form.type.datetime' => 'getForm_Type_DatetimeService',
            'form.type.email' => 'getForm_Type_EmailService',
            'form.type.entity' => 'getForm_Type_EntityService',
            'form.type.file' => 'getForm_Type_FileService',
            'form.type.form' => 'getForm_Type_FormService',
            'form.type.hidden' => 'getForm_Type_HiddenService',
            'form.type.integer' => 'getForm_Type_IntegerService',
            'form.type.language' => 'getForm_Type_LanguageService',
            'form.type.locale' => 'getForm_Type_LocaleService',
            'form.type.money' => 'getForm_Type_MoneyService',
            'form.type.number' => 'getForm_Type_NumberService',
            'form.type.password' => 'getForm_Type_PasswordService',
            'form.type.percent' => 'getForm_Type_PercentService',
            'form.type.radio' => 'getForm_Type_RadioService',
            'form.type.range' => 'getForm_Type_RangeService',
            'form.type.repeated' => 'getForm_Type_RepeatedService',
            'form.type.reset' => 'getForm_Type_ResetService',
            'form.type.search' => 'getForm_Type_SearchService',
            'form.type.submit' => 'getForm_Type_SubmitService',
            'form.type.text' => 'getForm_Type_TextService',
            'form.type.textarea' => 'getForm_Type_TextareaService',
            'form.type.time' => 'getForm_Type_TimeService',
            'form.type.timezone' => 'getForm_Type_TimezoneService',
            'form.type.url' => 'getForm_Type_UrlService',
            'form.type_extension.csrf' => 'getForm_TypeExtension_CsrfService',
            'form.type_extension.form.http_foundation' => 'getForm_TypeExtension_Form_HttpFoundationService',
            'form.type_extension.form.validator' => 'getForm_TypeExtension_Form_ValidatorService',
            'form.type_extension.repeated.validator' => 'getForm_TypeExtension_Repeated_ValidatorService',
            'form.type_extension.submit.validator' => 'getForm_TypeExtension_Submit_ValidatorService',
            'form.type_extension.upload.validator' => 'getForm_TypeExtension_Upload_ValidatorService',
            'form.type_guesser.doctrine' => 'getForm_TypeGuesser_DoctrineService',
            'form.type_guesser.validator' => 'getForm_TypeGuesser_ValidatorService',
            'fos_js_routing.controller' => 'getFosJsRouting_ControllerService',
            'fos_js_routing.extractor' => 'getFosJsRouting_ExtractorService',
            'fos_js_routing.serializer' => 'getFosJsRouting_SerializerService',
            'fragment.handler' => 'getFragment_HandlerService',
            'fragment.listener' => 'getFragment_ListenerService',
            'fragment.renderer.esi' => 'getFragment_Renderer_EsiService',
            'fragment.renderer.hinclude' => 'getFragment_Renderer_HincludeService',
            'fragment.renderer.inline' => 'getFragment_Renderer_InlineService',
            'fragment.renderer.ssi' => 'getFragment_Renderer_SsiService',
            'http_kernel' => 'getHttpKernelService',
            'kernel' => 'getKernelService',
            'kernel.class_cache.cache_warmer' => 'getKernel_ClassCache_CacheWarmerService',
            'locale_listener' => 'getLocaleListenerService',
            'logger' => 'getLoggerService',
            'markdown.parser' => 'getMarkdown_ParserService',
            'markdown.parser.parser_manager' => 'getMarkdown_Parser_ParserManagerService',
            'monolog.handler.check_balance' => 'getMonolog_Handler_CheckBalanceService',
            'monolog.handler.check_card_deposit_tracking' => 'getMonolog_Handler_CheckCardDepositTrackingService',
            'monolog.handler.check_deposit_tracking' => 'getMonolog_Handler_CheckDepositTrackingService',
            'monolog.handler.check_redis_balance' => 'getMonolog_Handler_CheckRedisBalanceService',
            'monolog.handler.copy_user_crossdomain' => 'getMonolog_Handler_CopyUserCrossdomainService',
            'monolog.handler.create_reward_entry' => 'getMonolog_Handler_CreateRewardEntryService',
            'monolog.handler.execute_rm_plan' => 'getMonolog_Handler_ExecuteRmPlanService',
            'monolog.handler.generate_rm_plan' => 'getMonolog_Handler_GenerateRmPlanService',
            'monolog.handler.generate_rm_plan_user' => 'getMonolog_Handler_GenerateRmPlanUserService',
            'monolog.handler.main' => 'getMonolog_Handler_MainService',
            'monolog.handler.message_to_italking' => 'getMonolog_Handler_MessageToItalkingService',
            'monolog.handler.monitor_queue_length' => 'getMonolog_Handler_MonitorQueueLengthService',
            'monolog.handler.monitor_stat' => 'getMonolog_Handler_MonitorStatService',
            'monolog.handler.nested' => 'getMonolog_Handler_NestedService',
            'monolog.handler.op_obtain_reward' => 'getMonolog_Handler_OpObtainRewardService',
            'monolog.handler.payment' => 'getMonolog_Handler_PaymentService',
            'monolog.handler.pop_failed_message' => 'getMonolog_Handler_PopFailedMessageService',
            'monolog.handler.regular_login' => 'getMonolog_Handler_RegularLoginService',
            'monolog.handler.remit_auto_confirm' => 'getMonolog_Handler_RemitAutoConfirmService',
            'monolog.handler.remove_ipl_overdue_user' => 'getMonolog_Handler_RemoveIplOverdueUserService',
            'monolog.handler.remove_overdue_user' => 'getMonolog_Handler_RemoveOverdueUserService',
            'monolog.handler.send_message' => 'getMonolog_Handler_SendMessageService',
            'monolog.handler.send_message_http_detail' => 'getMonolog_Handler_SendMessageHttpDetailService',
            'monolog.handler.sync_cash_fake_balance' => 'getMonolog_Handler_SyncCashFakeBalanceService',
            'monolog.handler.sync_cash_fake_entry' => 'getMonolog_Handler_SyncCashFakeEntryService',
            'monolog.handler.sync_cash_fake_entry_queue' => 'getMonolog_Handler_SyncCashFakeEntryQueueService',
            'monolog.handler.sync_cash_fake_history' => 'getMonolog_Handler_SyncCashFakeHistoryService',
            'monolog.handler.sync_cash_fake_queue' => 'getMonolog_Handler_SyncCashFakeQueueService',
            'monolog.handler.sync_credit' => 'getMonolog_Handler_SyncCreditService',
            'monolog.handler.sync_credit_entry' => 'getMonolog_Handler_SyncCreditEntryService',
            'monolog.handler.sync_login_log' => 'getMonolog_Handler_SyncLoginLogService',
            'monolog.handler.sync_login_log_mobile' => 'getMonolog_Handler_SyncLoginLogMobileService',
            'monolog.handler.sync_obtain_reward' => 'getMonolog_Handler_SyncObtainRewardService',
            'monolog.handler.sync_rm_plan_user' => 'getMonolog_Handler_SyncRmPlanUserService',
            'monolog.handler.sync_user_deposit_withdraw' => 'getMonolog_Handler_SyncUserDepositWithdrawService',
            'monolog.logger.doctrine' => 'getMonolog_Logger_DoctrineService',
            'monolog.logger.msg' => 'getMonolog_Logger_MsgService',
            'monolog.logger.php' => 'getMonolog_Logger_PhpService',
            'monolog.logger.request' => 'getMonolog_Logger_RequestService',
            'monolog.logger.router' => 'getMonolog_Logger_RouterService',
            'monolog.logger.snc_redis' => 'getMonolog_Logger_SncRedisService',
            'monolog.logger.translation' => 'getMonolog_Logger_TranslationService',
            'property_accessor' => 'getPropertyAccessorService',
            'request' => 'getRequestService',
            'request_stack' => 'getRequestStackService',
            'response_listener' => 'getResponseListenerService',
            'router' => 'getRouterService',
            'router.request_context' => 'getRouter_RequestContextService',
            'router_listener' => 'getRouterListenerService',
            'routing.loader' => 'getRouting_LoaderService',
            'security.csrf.token_manager' => 'getSecurity_Csrf_TokenManagerService',
            'security.secure_random' => 'getSecurity_SecureRandomService',
            'sensio_framework_extra.cache.listener' => 'getSensioFrameworkExtra_Cache_ListenerService',
            'sensio_framework_extra.controller.listener' => 'getSensioFrameworkExtra_Controller_ListenerService',
            'sensio_framework_extra.converter.datetime' => 'getSensioFrameworkExtra_Converter_DatetimeService',
            'sensio_framework_extra.converter.doctrine.orm' => 'getSensioFrameworkExtra_Converter_Doctrine_OrmService',
            'sensio_framework_extra.converter.listener' => 'getSensioFrameworkExtra_Converter_ListenerService',
            'sensio_framework_extra.converter.manager' => 'getSensioFrameworkExtra_Converter_ManagerService',
            'sensio_framework_extra.security.listener' => 'getSensioFrameworkExtra_Security_ListenerService',
            'sensio_framework_extra.view.guesser' => 'getSensioFrameworkExtra_View_GuesserService',
            'sensio_framework_extra.view.listener' => 'getSensioFrameworkExtra_View_ListenerService',
            'service_container' => 'getServiceContainerService',
            'session' => 'getSessionService',
            'session.handler' => 'getSession_HandlerService',
            'session.save_listener' => 'getSession_SaveListenerService',
            'session.storage.filesystem' => 'getSession_Storage_FilesystemService',
            'session.storage.metadata_bag' => 'getSession_Storage_MetadataBagService',
            'session.storage.native' => 'getSession_Storage_NativeService',
            'session.storage.php_bridge' => 'getSession_Storage_PhpBridgeService',
            'session_listener' => 'getSessionListenerService',
            'snc_redis.bodog' => 'getSncRedis_BodogService',
            'snc_redis.client.bodog_processor' => 'getSncRedis_Client_BodogProcessorService',
            'snc_redis.client.cluster_processor' => 'getSncRedis_Client_ClusterProcessorService',
            'snc_redis.client.default_processor' => 'getSncRedis_Client_DefaultProcessorService',
            'snc_redis.client.external_processor' => 'getSncRedis_Client_ExternalProcessorService',
            'snc_redis.client.ip_blocker_processor' => 'getSncRedis_Client_IpBlockerProcessorService',
            'snc_redis.client.kue_processor' => 'getSncRedis_Client_KueProcessorService',
            'snc_redis.client.map_processor' => 'getSncRedis_Client_MapProcessorService',
            'snc_redis.client.oauth2_processor' => 'getSncRedis_Client_Oauth2ProcessorService',
            'snc_redis.client.reward_processor' => 'getSncRedis_Client_RewardProcessorService',
            'snc_redis.client.sequence_processor' => 'getSncRedis_Client_SequenceProcessorService',
            'snc_redis.client.slide_processor' => 'getSncRedis_Client_SlideProcessorService',
            'snc_redis.client.suncity_processor' => 'getSncRedis_Client_SuncityProcessorService',
            'snc_redis.client.total_balance_processor' => 'getSncRedis_Client_TotalBalanceProcessorService',
            'snc_redis.client.wallet1_processor' => 'getSncRedis_Client_Wallet1ProcessorService',
            'snc_redis.client.wallet2_processor' => 'getSncRedis_Client_Wallet2ProcessorService',
            'snc_redis.client.wallet3_processor' => 'getSncRedis_Client_Wallet3ProcessorService',
            'snc_redis.client.wallet4_processor' => 'getSncRedis_Client_Wallet4ProcessorService',
            'snc_redis.cluster' => 'getSncRedis_ClusterService',
            'snc_redis.default' => 'getSncRedis_DefaultService',
            'snc_redis.external' => 'getSncRedis_ExternalService',
            'snc_redis.ip_blocker' => 'getSncRedis_IpBlockerService',
            'snc_redis.kue' => 'getSncRedis_KueService',
            'snc_redis.logger' => 'getSncRedis_LoggerService',
            'snc_redis.map' => 'getSncRedis_MapService',
            'snc_redis.oauth2' => 'getSncRedis_Oauth2Service',
            'snc_redis.reward' => 'getSncRedis_RewardService',
            'snc_redis.sequence' => 'getSncRedis_SequenceService',
            'snc_redis.slide' => 'getSncRedis_SlideService',
            'snc_redis.suncity' => 'getSncRedis_SuncityService',
            'snc_redis.total_balance' => 'getSncRedis_TotalBalanceService',
            'snc_redis.wallet1' => 'getSncRedis_Wallet1Service',
            'snc_redis.wallet2' => 'getSncRedis_Wallet2Service',
            'snc_redis.wallet3' => 'getSncRedis_Wallet3Service',
            'snc_redis.wallet4' => 'getSncRedis_Wallet4Service',
            'streamed_response_listener' => 'getStreamedResponseListenerService',
            'templating' => 'getTemplatingService',
            'templating.filename_parser' => 'getTemplating_FilenameParserService',
            'templating.helper.assets' => 'getTemplating_Helper_AssetsService',
            'templating.helper.markdown' => 'getTemplating_Helper_MarkdownService',
            'templating.helper.router' => 'getTemplating_Helper_RouterService',
            'templating.loader' => 'getTemplating_LoaderService',
            'templating.locator' => 'getTemplating_LocatorService',
            'templating.name_parser' => 'getTemplating_NameParserService',
            'translation.dumper.csv' => 'getTranslation_Dumper_CsvService',
            'translation.dumper.ini' => 'getTranslation_Dumper_IniService',
            'translation.dumper.json' => 'getTranslation_Dumper_JsonService',
            'translation.dumper.mo' => 'getTranslation_Dumper_MoService',
            'translation.dumper.php' => 'getTranslation_Dumper_PhpService',
            'translation.dumper.po' => 'getTranslation_Dumper_PoService',
            'translation.dumper.qt' => 'getTranslation_Dumper_QtService',
            'translation.dumper.res' => 'getTranslation_Dumper_ResService',
            'translation.dumper.xliff' => 'getTranslation_Dumper_XliffService',
            'translation.dumper.yml' => 'getTranslation_Dumper_YmlService',
            'translation.extractor' => 'getTranslation_ExtractorService',
            'translation.extractor.php' => 'getTranslation_Extractor_PhpService',
            'translation.loader' => 'getTranslation_LoaderService',
            'translation.loader.csv' => 'getTranslation_Loader_CsvService',
            'translation.loader.dat' => 'getTranslation_Loader_DatService',
            'translation.loader.ini' => 'getTranslation_Loader_IniService',
            'translation.loader.json' => 'getTranslation_Loader_JsonService',
            'translation.loader.mo' => 'getTranslation_Loader_MoService',
            'translation.loader.php' => 'getTranslation_Loader_PhpService',
            'translation.loader.po' => 'getTranslation_Loader_PoService',
            'translation.loader.qt' => 'getTranslation_Loader_QtService',
            'translation.loader.res' => 'getTranslation_Loader_ResService',
            'translation.loader.xliff' => 'getTranslation_Loader_XliffService',
            'translation.loader.yml' => 'getTranslation_Loader_YmlService',
            'translation.writer' => 'getTranslation_WriterService',
            'translator.default' => 'getTranslator_DefaultService',
            'translator_listener' => 'getTranslatorListenerService',
            'twig' => 'getTwigService',
            'twig.controller.exception' => 'getTwig_Controller_ExceptionService',
            'twig.controller.preview_error' => 'getTwig_Controller_PreviewErrorService',
            'twig.exception_listener' => 'getTwig_ExceptionListenerService',
            'twig.loader' => 'getTwig_LoaderService',
            'twig.profile' => 'getTwig_ProfileService',
            'twig.translation.extractor' => 'getTwig_Translation_ExtractorService',
            'uri_signer' => 'getUriSignerService',
            'validate_request_listener' => 'getValidateRequestListenerService',
            'validator' => 'getValidatorService',
            'validator.builder' => 'getValidator_BuilderService',
            'validator.email' => 'getValidator_EmailService',
            'validator.expression' => 'getValidator_ExpressionService',
        );
        $this->aliases = array(
            'database_connection' => 'doctrine.dbal.default_connection',
            'doctrine.orm.entity_manager' => 'doctrine.orm.default_entity_manager',
            'doctrine.orm.entry_metadata_cache' => 'doctrine_cache.providers.doctrine.orm.entry_metadata_cache',
            'doctrine.orm.entry_query_cache' => 'doctrine_cache.providers.doctrine.orm.entry_query_cache',
            'doctrine.orm.entry_result_cache' => 'doctrine_cache.providers.doctrine.orm.entry_result_cache',
            'doctrine.orm.his_result_cache' => 'doctrine_cache.providers.doctrine.orm.his_result_cache',
            'doctrine.orm.ip_blocker_metadata_cache' => 'doctrine_cache.providers.doctrine.orm.ip_blocker_metadata_cache',
            'doctrine.orm.ip_blocker_query_cache' => 'doctrine_cache.providers.doctrine.orm.ip_blocker_query_cache',
            'doctrine.orm.ip_blocker_result_cache' => 'doctrine_cache.providers.doctrine.orm.ip_blocker_result_cache',
            'doctrine.orm.outside_metadata_cache' => 'doctrine_cache.providers.doctrine.orm.outside_metadata_cache',
            'doctrine.orm.outside_query_cache' => 'doctrine_cache.providers.doctrine.orm.outside_query_cache',
            'doctrine.orm.outside_result_cache' => 'doctrine_cache.providers.doctrine.orm.outside_result_cache',
            'doctrine.orm.share_metadata_cache' => 'doctrine_cache.providers.doctrine.orm.share_metadata_cache',
            'doctrine.orm.share_query_cache' => 'doctrine_cache.providers.doctrine.orm.share_query_cache',
            'doctrine.orm.share_result_cache' => 'doctrine_cache.providers.doctrine.orm.share_result_cache',
            'file_system_cache' => 'doctrine_cache.providers.configurable_filesystem_provider_type',
            'session.storage' => 'session.storage.native',
            'snc_redis.bodog_client' => 'snc_redis.bodog',
            'snc_redis.cluster_client' => 'snc_redis.cluster',
            'snc_redis.default_client' => 'snc_redis.default',
            'snc_redis.external_client' => 'snc_redis.external',
            'snc_redis.ip_blocker_client' => 'snc_redis.ip_blocker',
            'snc_redis.kue_client' => 'snc_redis.kue',
            'snc_redis.map_client' => 'snc_redis.map',
            'snc_redis.oauth2_client' => 'snc_redis.oauth2',
            'snc_redis.reward_client' => 'snc_redis.reward',
            'snc_redis.sequence_client' => 'snc_redis.sequence',
            'snc_redis.slide_client' => 'snc_redis.slide',
            'snc_redis.suncity_client' => 'snc_redis.suncity',
            'snc_redis.total_balance_client' => 'snc_redis.total_balance',
            'snc_redis.wallet1_client' => 'snc_redis.wallet1',
            'snc_redis.wallet2_client' => 'snc_redis.wallet2',
            'snc_redis.wallet3_client' => 'snc_redis.wallet3',
            'snc_redis.wallet4_client' => 'snc_redis.wallet4',
            'translator' => 'translator.default',
        );
    }

    /*
     * {@inheritdoc}
     */
    public function compile()
    {
        throw new LogicException('You cannot compile a dumped frozen container.');
    }

    /*
     * {@inheritdoc}
     */
    public function isFrozen()
    {
        return true;
    }

    /*
     * Gets the 'annotation_reader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Annotations\CachedReader A Doctrine\Common\Annotations\CachedReader instance
     */
    protected function getAnnotationReaderService()
    {
        return $this->services['annotation_reader'] = new \Doctrine\Common\Annotations\CachedReader(new \Doctrine\Common\Annotations\AnnotationReader(), new \Doctrine\Common\Cache\FilesystemCache((__DIR__.'/annotations')), false);
    }

    /*
     * Gets the 'assets.context' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Asset\Context\RequestStackContext A Symfony\Component\Asset\Context\RequestStackContext instance
     */
    protected function getAssets_ContextService()
    {
        return $this->services['assets.context'] = new \Symfony\Component\Asset\Context\RequestStackContext($this->get('request_stack'));
    }

    /*
     * Gets the 'assets.packages' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Asset\Packages A Symfony\Component\Asset\Packages instance
     */
    protected function getAssets_PackagesService()
    {
        return $this->services['assets.packages'] = new \Symfony\Component\Asset\Packages(new \Symfony\Component\Asset\PathPackage('', new \Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy(), $this->get('assets.context')), array());
    }

    /*
     * Gets the 'cache_clearer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\CacheClearer\ChainCacheClearer A Symfony\Component\HttpKernel\CacheClearer\ChainCacheClearer instance
     */
    protected function getCacheClearerService()
    {
        return $this->services['cache_clearer'] = new \Symfony\Component\HttpKernel\CacheClearer\ChainCacheClearer(array());
    }

    /*
     * Gets the 'cache_warmer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate A Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate instance
     */
    protected function getCacheWarmerService()
    {
        $a = $this->get('kernel');
        $b = $this->get('templating.filename_parser');

        $c = new \Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinder($a, $b, ($this->targetDirs[2].'/Resources'));

        return $this->services['cache_warmer'] = new \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate(array(0 => new \Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplatePathsCacheWarmer($c, $this->get('templating.locator')), 1 => $this->get('kernel.class_cache.cache_warmer'), 2 => new \Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer($this->get('translator.default')), 3 => new \Symfony\Bundle\FrameworkBundle\CacheWarmer\RouterCacheWarmer($this->get('router')), 4 => new \Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheCacheWarmer($this, $c, array()), 5 => new \Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheWarmer($this->get('twig'), new \Symfony\Bundle\TwigBundle\TemplateIterator($a, $this->targetDirs[2], array())), 6 => new \Symfony\Bridge\Doctrine\CacheWarmer\ProxyCacheWarmer($this->get('doctrine'))));
    }

    /*
     * Gets the 'config_cache_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Config\ResourceCheckerConfigCacheFactory A Symfony\Component\Config\ResourceCheckerConfigCacheFactory instance
     */
    protected function getConfigCacheFactoryService()
    {
        return $this->services['config_cache_factory'] = new \Symfony\Component\Config\ResourceCheckerConfigCacheFactory(array());
    }

    /*
     * Gets the 'debug.debug_handlers_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\DebugHandlersListener A Symfony\Component\HttpKernel\EventListener\DebugHandlersListener instance
     */
    protected function getDebug_DebugHandlersListenerService()
    {
        return $this->services['debug.debug_handlers_listener'] = new \Symfony\Component\HttpKernel\EventListener\DebugHandlersListener(NULL, NULL, NULL, NULL, true, NULL);
    }

    /*
     * Gets the 'debug.stopwatch' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Stopwatch\Stopwatch A Symfony\Component\Stopwatch\Stopwatch instance
     */
    protected function getDebug_StopwatchService()
    {
        return $this->services['debug.stopwatch'] = new \Symfony\Component\Stopwatch\Stopwatch();
    }

    /*
     * Gets the 'doctrine' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Registry A Doctrine\Bundle\DoctrineBundle\Registry instance
     */
    protected function getDoctrineService()
    {
        return $this->services['doctrine'] = new \Doctrine\Bundle\DoctrineBundle\Registry($this, array('default' => 'doctrine.dbal.default_connection', 'his' => 'doctrine.dbal.his_connection', 'entry' => 'doctrine.dbal.entry_connection', 'share' => 'doctrine.dbal.share_connection', 'outside' => 'doctrine.dbal.outside_connection', 'ip_blocker' => 'doctrine.dbal.ip_blocker_connection'), array('default' => 'doctrine.orm.default_entity_manager', 'his' => 'doctrine.orm.his_entity_manager', 'entry' => 'doctrine.orm.entry_entity_manager', 'share' => 'doctrine.orm.share_entity_manager', 'outside' => 'doctrine.orm.outside_entity_manager', 'ip_blocker' => 'doctrine.orm.ip_blocker_entity_manager'), 'default', 'default');
    }

    /*
     * Gets the 'doctrine.dbal.connection_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\ConnectionFactory A Doctrine\Bundle\DoctrineBundle\ConnectionFactory instance
     */
    protected function getDoctrine_Dbal_ConnectionFactoryService()
    {
        return $this->services['doctrine.dbal.connection_factory'] = new \Doctrine\Bundle\DoctrineBundle\ConnectionFactory(array());
    }

    /*
     * Gets the 'doctrine.dbal.default_connection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\DBAL\Connection A Doctrine\DBAL\Connection instance
     */
    protected function getDoctrine_Dbal_DefaultConnectionService()
    {
        return $this->services['doctrine.dbal.default_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('driver' => 'pdo_mysql', 'slaves' => array('default_slave' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8')), 'driverOptions' => array(2 => NULL), 'keepSlave' => true, 'serverVersion' => 5.5999999999999996, 'master' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8', 'defaultTableOptions' => array()), 'wrapperClass' => 'Doctrine\\DBAL\\Connections\\MasterSlaveConnection'), new \Doctrine\DBAL\Configuration(), new \Symfony\Bridge\Doctrine\ContainerAwareEventManager($this), array());
    }

    /*
     * Gets the 'doctrine.dbal.entry_connection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\DBAL\Connection A Doctrine\DBAL\Connection instance
     */
    protected function getDoctrine_Dbal_EntryConnectionService()
    {
        return $this->services['doctrine.dbal.entry_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('driver' => 'pdo_mysql', 'slaves' => array('entry_slave' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8')), 'driverOptions' => array(2 => NULL), 'keepSlave' => true, 'serverVersion' => 5.5999999999999996, 'master' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8', 'defaultTableOptions' => array()), 'wrapperClass' => 'Doctrine\\DBAL\\Connections\\MasterSlaveConnection'), new \Doctrine\DBAL\Configuration(), new \Symfony\Bridge\Doctrine\ContainerAwareEventManager($this), array());
    }

    /*
     * Gets the 'doctrine.dbal.his_connection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\DBAL\Connection A Doctrine\DBAL\Connection instance
     */
    protected function getDoctrine_Dbal_HisConnectionService()
    {
        return $this->services['doctrine.dbal.his_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('driver' => 'pdo_mysql', 'slaves' => array('his_slave' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8')), 'driverOptions' => array(2 => NULL), 'keepSlave' => true, 'serverVersion' => 5.5999999999999996, 'master' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8', 'defaultTableOptions' => array()), 'wrapperClass' => 'Doctrine\\DBAL\\Connections\\MasterSlaveConnection'), new \Doctrine\DBAL\Configuration(), new \Symfony\Bridge\Doctrine\ContainerAwareEventManager($this), array());
    }

    /*
     * Gets the 'doctrine.dbal.ip_blocker_connection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\DBAL\Connection A Doctrine\DBAL\Connection instance
     */
    protected function getDoctrine_Dbal_IpBlockerConnectionService()
    {
        return $this->services['doctrine.dbal.ip_blocker_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('driver' => 'pdo_mysql', 'slaves' => array('ip_blocker_slave' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8')), 'driverOptions' => array(2 => NULL), 'keepSlave' => true, 'serverVersion' => 5.5999999999999996, 'master' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8', 'defaultTableOptions' => array()), 'wrapperClass' => 'Doctrine\\DBAL\\Connections\\MasterSlaveConnection'), new \Doctrine\DBAL\Configuration(), new \Symfony\Bridge\Doctrine\ContainerAwareEventManager($this), array());
    }

    /*
     * Gets the 'doctrine.dbal.outside_connection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\DBAL\Connection A Doctrine\DBAL\Connection instance
     */
    protected function getDoctrine_Dbal_OutsideConnectionService()
    {
        return $this->services['doctrine.dbal.outside_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('driver' => 'pdo_mysql', 'slaves' => array('outside_slave' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8')), 'driverOptions' => array(2 => NULL), 'keepSlave' => true, 'serverVersion' => 5.5999999999999996, 'master' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8', 'defaultTableOptions' => array()), 'wrapperClass' => 'Doctrine\\DBAL\\Connections\\MasterSlaveConnection'), new \Doctrine\DBAL\Configuration(), new \Symfony\Bridge\Doctrine\ContainerAwareEventManager($this), array());
    }

    /*
     * Gets the 'doctrine.dbal.share_connection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\DBAL\Connection A Doctrine\DBAL\Connection instance
     */
    protected function getDoctrine_Dbal_ShareConnectionService()
    {
        return $this->services['doctrine.dbal.share_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('driver' => 'pdo_mysql', 'slaves' => array('share_slave' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8')), 'driverOptions' => array(2 => NULL), 'keepSlave' => true, 'serverVersion' => 5.5999999999999996, 'master' => array('host' => 'mysql', 'port' => 3306, 'dbname' => 'durian_bb', 'user' => 'root', 'password' => 'very-secret', 'charset' => 'UTF8', 'defaultTableOptions' => array()), 'wrapperClass' => 'Doctrine\\DBAL\\Connections\\MasterSlaveConnection'), new \Doctrine\DBAL\Configuration(), new \Symfony\Bridge\Doctrine\ContainerAwareEventManager($this), array());
    }

    /*
     * Gets the 'doctrine.orm.default_entity_listener_resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver A Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver instance
     */
    protected function getDoctrine_Orm_DefaultEntityListenerResolverService()
    {
        return $this->services['doctrine.orm.default_entity_listener_resolver'] = new \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver($this);
    }

    /*
     * Gets the 'doctrine.orm.default_entity_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @param bool    $lazyLoad whether to try lazy-loading the service with a proxy
     *
     * @return \Doctrine\ORM\EntityManager A Doctrine\ORM\EntityManager instance
     */
    public function getDoctrine_Orm_DefaultEntityManagerService($lazyLoad = true)
    {
        if ($lazyLoad) {
            $container = $this;

            return $this->services['doctrine.orm.default_entity_manager'] = DoctrineORMEntityManager_00000000780113e3000000003d9a623965be85e65b22cd674e76ec56af8fd0f6::staticProxyConstructor(
                function (&$wrappedInstance, \ProxyManager\Proxy\LazyLoadingInterface $proxy) use ($container) {
                    $wrappedInstance = $container->getDoctrine_Orm_DefaultEntityManagerService(false);

                    $proxy->setProxyInitializer(null);

                    return true;
                }
            );
        }

        $a = $this->get('doctrine_cache.providers.configurable_filesystem_provider_type');

        $b = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        $b->addDriver(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array(0 => ($this->targetDirs[3].'/src/BB/DurianBundle/Entity'))), 'BB\\DurianBundle\\Entity');

        $c = new \Doctrine\ORM\Configuration();
        $c->setEntityNamespaces(array('BBDurianBundle' => 'BB\\DurianBundle\\Entity'));
        $c->setMetadataCacheImpl($a);
        $c->setQueryCacheImpl($a);
        $c->setResultCacheImpl($this->get('doctrine.orm.default_result_cache'));
        $c->setMetadataDriverImpl($b);
        $c->setProxyDir((__DIR__.'/doctrine/orm/Proxies'));
        $c->setProxyNamespace('Proxies');
        $c->setAutoGenerateProxyClasses(false);
        $c->setClassMetadataFactoryName('Doctrine\\ORM\\Mapping\\ClassMetadataFactory');
        $c->setDefaultRepositoryClassName('Doctrine\\ORM\\EntityRepository');
        $c->setNamingStrategy($this->get('doctrine.orm.naming_strategy.default'));
        $c->setQuoteStrategy($this->get('doctrine.orm.quote_strategy.default'));
        $c->setEntityListenerResolver($this->get('doctrine.orm.default_entity_listener_resolver'));

        $instance = \Doctrine\ORM\EntityManager::create($this->get('doctrine.dbal.default_connection'), $c);

        $this->get('doctrine.orm.default_manager_configurator')->configure($instance);

        return $instance;
    }

    /*
     * Gets the 'doctrine.orm.default_entity_manager.property_info_extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor A Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor instance
     */
    protected function getDoctrine_Orm_DefaultEntityManager_PropertyInfoExtractorService()
    {
        return $this->services['doctrine.orm.default_entity_manager.property_info_extractor'] = new \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor($this->get('doctrine.orm.default_entity_manager')->getMetadataFactory());
    }

    /*
     * Gets the 'doctrine.orm.default_manager_configurator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator A Doctrine\Bundle\DoctrineBundle\ManagerConfigurator instance
     */
    protected function getDoctrine_Orm_DefaultManagerConfiguratorService()
    {
        return $this->services['doctrine.orm.default_manager_configurator'] = new \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator(array(), array());
    }

    /*
     * Gets the 'doctrine.orm.default_result_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Snc\RedisBundle\Doctrine\Cache\RedisCache A Snc\RedisBundle\Doctrine\Cache\RedisCache instance
     */
    protected function getDoctrine_Orm_DefaultResultCacheService()
    {
        $this->services['doctrine.orm.default_result_cache'] = $instance = new \Snc\RedisBundle\Doctrine\Cache\RedisCache();

        $instance->setRedis($this->get('snc_redis.default'));

        return $instance;
    }

    /*
     * Gets the 'doctrine.orm.entry_entity_listener_resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver A Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver instance
     */
    protected function getDoctrine_Orm_EntryEntityListenerResolverService()
    {
        return $this->services['doctrine.orm.entry_entity_listener_resolver'] = new \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver($this);
    }

    /*
     * Gets the 'doctrine.orm.entry_entity_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @param bool    $lazyLoad whether to try lazy-loading the service with a proxy
     *
     * @return \Doctrine\ORM\EntityManager A Doctrine\ORM\EntityManager instance
     */
    public function getDoctrine_Orm_EntryEntityManagerService($lazyLoad = true)
    {
        if ($lazyLoad) {
            $container = $this;

            return $this->services['doctrine.orm.entry_entity_manager'] = DoctrineORMEntityManager_00000000780113e9000000003d9a623965be85e65b22cd674e76ec56af8fd0f6::staticProxyConstructor(
                function (&$wrappedInstance, \ProxyManager\Proxy\LazyLoadingInterface $proxy) use ($container) {
                    $wrappedInstance = $container->getDoctrine_Orm_EntryEntityManagerService(false);

                    $proxy->setProxyInitializer(null);

                    return true;
                }
            );
        }

        $a = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        $a->addDriver(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array(0 => ($this->targetDirs[3].'/src/BB/DurianBundle/Entity'))), 'BB\\DurianBundle\\Entity');

        $b = new \Doctrine\ORM\Configuration();
        $b->setEntityNamespaces(array('BBDurianBundle' => 'BB\\DurianBundle\\Entity'));
        $b->setMetadataCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.entry_metadata_cache'));
        $b->setQueryCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.entry_query_cache'));
        $b->setResultCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.entry_result_cache'));
        $b->setMetadataDriverImpl($a);
        $b->setProxyDir((__DIR__.'/doctrine/orm/Proxies'));
        $b->setProxyNamespace('Proxies');
        $b->setAutoGenerateProxyClasses(false);
        $b->setClassMetadataFactoryName('Doctrine\\ORM\\Mapping\\ClassMetadataFactory');
        $b->setDefaultRepositoryClassName('Doctrine\\ORM\\EntityRepository');
        $b->setNamingStrategy($this->get('doctrine.orm.naming_strategy.default'));
        $b->setQuoteStrategy($this->get('doctrine.orm.quote_strategy.default'));
        $b->setEntityListenerResolver($this->get('doctrine.orm.entry_entity_listener_resolver'));

        $instance = \Doctrine\ORM\EntityManager::create($this->get('doctrine.dbal.entry_connection'), $b);

        $this->get('doctrine.orm.entry_manager_configurator')->configure($instance);

        return $instance;
    }

    /*
     * Gets the 'doctrine.orm.entry_entity_manager.property_info_extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor A Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor instance
     */
    protected function getDoctrine_Orm_EntryEntityManager_PropertyInfoExtractorService()
    {
        return $this->services['doctrine.orm.entry_entity_manager.property_info_extractor'] = new \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor($this->get('doctrine.orm.entry_entity_manager')->getMetadataFactory());
    }

    /*
     * Gets the 'doctrine.orm.entry_manager_configurator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator A Doctrine\Bundle\DoctrineBundle\ManagerConfigurator instance
     */
    protected function getDoctrine_Orm_EntryManagerConfiguratorService()
    {
        return $this->services['doctrine.orm.entry_manager_configurator'] = new \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator(array(), array());
    }

    /*
     * Gets the 'doctrine.orm.his_entity_listener_resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver A Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver instance
     */
    protected function getDoctrine_Orm_HisEntityListenerResolverService()
    {
        return $this->services['doctrine.orm.his_entity_listener_resolver'] = new \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver($this);
    }

    /*
     * Gets the 'doctrine.orm.his_entity_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @param bool    $lazyLoad whether to try lazy-loading the service with a proxy
     *
     * @return \Doctrine\ORM\EntityManager A Doctrine\ORM\EntityManager instance
     */
    public function getDoctrine_Orm_HisEntityManagerService($lazyLoad = true)
    {
        if ($lazyLoad) {
            $container = $this;

            return $this->services['doctrine.orm.his_entity_manager'] = DoctrineORMEntityManager_00000000780113ef000000003d9a623965be85e65b22cd674e76ec56af8fd0f6::staticProxyConstructor(
                function (&$wrappedInstance, \ProxyManager\Proxy\LazyLoadingInterface $proxy) use ($container) {
                    $wrappedInstance = $container->getDoctrine_Orm_HisEntityManagerService(false);

                    $proxy->setProxyInitializer(null);

                    return true;
                }
            );
        }

        $a = $this->get('doctrine_cache.providers.configurable_filesystem_provider_type');

        $b = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        $b->addDriver(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array(0 => ($this->targetDirs[3].'/src/BB/DurianBundle/Entity'))), 'BB\\DurianBundle\\Entity');

        $c = new \Doctrine\ORM\Configuration();
        $c->setEntityNamespaces(array('BBDurianBundle' => 'BB\\DurianBundle\\Entity'));
        $c->setMetadataCacheImpl($a);
        $c->setQueryCacheImpl($a);
        $c->setResultCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.his_result_cache'));
        $c->setMetadataDriverImpl($b);
        $c->setProxyDir((__DIR__.'/doctrine/orm/Proxies'));
        $c->setProxyNamespace('Proxies');
        $c->setAutoGenerateProxyClasses(false);
        $c->setClassMetadataFactoryName('Doctrine\\ORM\\Mapping\\ClassMetadataFactory');
        $c->setDefaultRepositoryClassName('Doctrine\\ORM\\EntityRepository');
        $c->setNamingStrategy($this->get('doctrine.orm.naming_strategy.default'));
        $c->setQuoteStrategy($this->get('doctrine.orm.quote_strategy.default'));
        $c->setEntityListenerResolver($this->get('doctrine.orm.his_entity_listener_resolver'));

        $instance = \Doctrine\ORM\EntityManager::create($this->get('doctrine.dbal.his_connection'), $c);

        $this->get('doctrine.orm.his_manager_configurator')->configure($instance);

        return $instance;
    }

    /*
     * Gets the 'doctrine.orm.his_entity_manager.property_info_extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor A Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor instance
     */
    protected function getDoctrine_Orm_HisEntityManager_PropertyInfoExtractorService()
    {
        return $this->services['doctrine.orm.his_entity_manager.property_info_extractor'] = new \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor($this->get('doctrine.orm.his_entity_manager')->getMetadataFactory());
    }

    /*
     * Gets the 'doctrine.orm.his_manager_configurator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator A Doctrine\Bundle\DoctrineBundle\ManagerConfigurator instance
     */
    protected function getDoctrine_Orm_HisManagerConfiguratorService()
    {
        return $this->services['doctrine.orm.his_manager_configurator'] = new \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator(array(), array());
    }

    /*
     * Gets the 'doctrine.orm.ip_blocker_entity_listener_resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver A Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver instance
     */
    protected function getDoctrine_Orm_IpBlockerEntityListenerResolverService()
    {
        return $this->services['doctrine.orm.ip_blocker_entity_listener_resolver'] = new \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver($this);
    }

    /*
     * Gets the 'doctrine.orm.ip_blocker_entity_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @param bool    $lazyLoad whether to try lazy-loading the service with a proxy
     *
     * @return \Doctrine\ORM\EntityManager A Doctrine\ORM\EntityManager instance
     */
    public function getDoctrine_Orm_IpBlockerEntityManagerService($lazyLoad = true)
    {
        if ($lazyLoad) {
            $container = $this;

            return $this->services['doctrine.orm.ip_blocker_entity_manager'] = DoctrineORMEntityManager_0000000078011007000000003d9a623965be85e65b22cd674e76ec56af8fd0f6::staticProxyConstructor(
                function (&$wrappedInstance, \ProxyManager\Proxy\LazyLoadingInterface $proxy) use ($container) {
                    $wrappedInstance = $container->getDoctrine_Orm_IpBlockerEntityManagerService(false);

                    $proxy->setProxyInitializer(null);

                    return true;
                }
            );
        }

        $a = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        $a->addDriver(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array(0 => ($this->targetDirs[3].'/src/BB/DurianBundle/Entity'))), 'BB\\DurianBundle\\Entity');

        $b = new \Doctrine\ORM\Configuration();
        $b->setEntityNamespaces(array('BBDurianBundle' => 'BB\\DurianBundle\\Entity'));
        $b->setMetadataCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.ip_blocker_metadata_cache'));
        $b->setQueryCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.ip_blocker_query_cache'));
        $b->setResultCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.ip_blocker_result_cache'));
        $b->setMetadataDriverImpl($a);
        $b->setProxyDir((__DIR__.'/doctrine/orm/Proxies'));
        $b->setProxyNamespace('Proxies');
        $b->setAutoGenerateProxyClasses(false);
        $b->setClassMetadataFactoryName('Doctrine\\ORM\\Mapping\\ClassMetadataFactory');
        $b->setDefaultRepositoryClassName('Doctrine\\ORM\\EntityRepository');
        $b->setNamingStrategy($this->get('doctrine.orm.naming_strategy.default'));
        $b->setQuoteStrategy($this->get('doctrine.orm.quote_strategy.default'));
        $b->setEntityListenerResolver($this->get('doctrine.orm.ip_blocker_entity_listener_resolver'));

        $instance = \Doctrine\ORM\EntityManager::create($this->get('doctrine.dbal.ip_blocker_connection'), $b);

        $this->get('doctrine.orm.ip_blocker_manager_configurator')->configure($instance);

        return $instance;
    }

    /*
     * Gets the 'doctrine.orm.ip_blocker_entity_manager.property_info_extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor A Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor instance
     */
    protected function getDoctrine_Orm_IpBlockerEntityManager_PropertyInfoExtractorService()
    {
        return $this->services['doctrine.orm.ip_blocker_entity_manager.property_info_extractor'] = new \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor($this->get('doctrine.orm.ip_blocker_entity_manager')->getMetadataFactory());
    }

    /*
     * Gets the 'doctrine.orm.ip_blocker_manager_configurator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator A Doctrine\Bundle\DoctrineBundle\ManagerConfigurator instance
     */
    protected function getDoctrine_Orm_IpBlockerManagerConfiguratorService()
    {
        return $this->services['doctrine.orm.ip_blocker_manager_configurator'] = new \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator(array(), array());
    }

    /*
     * Gets the 'doctrine.orm.outside_entity_listener_resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver A Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver instance
     */
    protected function getDoctrine_Orm_OutsideEntityListenerResolverService()
    {
        return $this->services['doctrine.orm.outside_entity_listener_resolver'] = new \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver($this);
    }

    /*
     * Gets the 'doctrine.orm.outside_entity_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @param bool    $lazyLoad whether to try lazy-loading the service with a proxy
     *
     * @return \Doctrine\ORM\EntityManager A Doctrine\ORM\EntityManager instance
     */
    public function getDoctrine_Orm_OutsideEntityManagerService($lazyLoad = true)
    {
        if ($lazyLoad) {
            $container = $this;

            return $this->services['doctrine.orm.outside_entity_manager'] = DoctrineORMEntityManager_000000007801101d000000003d9a623965be85e65b22cd674e76ec56af8fd0f6::staticProxyConstructor(
                function (&$wrappedInstance, \ProxyManager\Proxy\LazyLoadingInterface $proxy) use ($container) {
                    $wrappedInstance = $container->getDoctrine_Orm_OutsideEntityManagerService(false);

                    $proxy->setProxyInitializer(null);

                    return true;
                }
            );
        }

        $a = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        $a->addDriver(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array(0 => ($this->targetDirs[3].'/src/BB/DurianBundle/Entity'))), 'BB\\DurianBundle\\Entity');

        $b = new \Doctrine\ORM\Configuration();
        $b->setEntityNamespaces(array('BBDurianBundle' => 'BB\\DurianBundle\\Entity'));
        $b->setMetadataCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.outside_metadata_cache'));
        $b->setQueryCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.outside_query_cache'));
        $b->setResultCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.outside_result_cache'));
        $b->setMetadataDriverImpl($a);
        $b->setProxyDir((__DIR__.'/doctrine/orm/Proxies'));
        $b->setProxyNamespace('Proxies');
        $b->setAutoGenerateProxyClasses(false);
        $b->setClassMetadataFactoryName('Doctrine\\ORM\\Mapping\\ClassMetadataFactory');
        $b->setDefaultRepositoryClassName('Doctrine\\ORM\\EntityRepository');
        $b->setNamingStrategy($this->get('doctrine.orm.naming_strategy.default'));
        $b->setQuoteStrategy($this->get('doctrine.orm.quote_strategy.default'));
        $b->setEntityListenerResolver($this->get('doctrine.orm.outside_entity_listener_resolver'));

        $instance = \Doctrine\ORM\EntityManager::create($this->get('doctrine.dbal.outside_connection'), $b);

        $this->get('doctrine.orm.outside_manager_configurator')->configure($instance);

        return $instance;
    }

    /*
     * Gets the 'doctrine.orm.outside_entity_manager.property_info_extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor A Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor instance
     */
    protected function getDoctrine_Orm_OutsideEntityManager_PropertyInfoExtractorService()
    {
        return $this->services['doctrine.orm.outside_entity_manager.property_info_extractor'] = new \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor($this->get('doctrine.orm.outside_entity_manager')->getMetadataFactory());
    }

    /*
     * Gets the 'doctrine.orm.outside_manager_configurator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator A Doctrine\Bundle\DoctrineBundle\ManagerConfigurator instance
     */
    protected function getDoctrine_Orm_OutsideManagerConfiguratorService()
    {
        return $this->services['doctrine.orm.outside_manager_configurator'] = new \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator(array(), array());
    }

    /*
     * Gets the 'doctrine.orm.share_entity_listener_resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver A Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver instance
     */
    protected function getDoctrine_Orm_ShareEntityListenerResolverService()
    {
        return $this->services['doctrine.orm.share_entity_listener_resolver'] = new \Doctrine\Bundle\DoctrineBundle\Mapping\ContainerAwareEntityListenerResolver($this);
    }

    /*
     * Gets the 'doctrine.orm.share_entity_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @param bool    $lazyLoad whether to try lazy-loading the service with a proxy
     *
     * @return \Doctrine\ORM\EntityManager A Doctrine\ORM\EntityManager instance
     */
    public function getDoctrine_Orm_ShareEntityManagerService($lazyLoad = true)
    {
        if ($lazyLoad) {
            $container = $this;

            return $this->services['doctrine.orm.share_entity_manager'] = DoctrineORMEntityManager_0000000078011013000000003d9a623965be85e65b22cd674e76ec56af8fd0f6::staticProxyConstructor(
                function (&$wrappedInstance, \ProxyManager\Proxy\LazyLoadingInterface $proxy) use ($container) {
                    $wrappedInstance = $container->getDoctrine_Orm_ShareEntityManagerService(false);

                    $proxy->setProxyInitializer(null);

                    return true;
                }
            );
        }

        $a = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        $a->addDriver(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array(0 => ($this->targetDirs[3].'/src/BB/DurianBundle/Entity'))), 'BB\\DurianBundle\\Entity');

        $b = new \Doctrine\ORM\Configuration();
        $b->setEntityNamespaces(array('BBDurianBundle' => 'BB\\DurianBundle\\Entity'));
        $b->setMetadataCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.share_metadata_cache'));
        $b->setQueryCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.share_query_cache'));
        $b->setResultCacheImpl($this->get('doctrine_cache.providers.doctrine.orm.share_result_cache'));
        $b->setMetadataDriverImpl($a);
        $b->setProxyDir((__DIR__.'/doctrine/orm/Proxies'));
        $b->setProxyNamespace('Proxies');
        $b->setAutoGenerateProxyClasses(false);
        $b->setClassMetadataFactoryName('Doctrine\\ORM\\Mapping\\ClassMetadataFactory');
        $b->setDefaultRepositoryClassName('Doctrine\\ORM\\EntityRepository');
        $b->setNamingStrategy($this->get('doctrine.orm.naming_strategy.default'));
        $b->setQuoteStrategy($this->get('doctrine.orm.quote_strategy.default'));
        $b->setEntityListenerResolver($this->get('doctrine.orm.share_entity_listener_resolver'));

        $instance = \Doctrine\ORM\EntityManager::create($this->get('doctrine.dbal.share_connection'), $b);

        $this->get('doctrine.orm.share_manager_configurator')->configure($instance);

        return $instance;
    }

    /*
     * Gets the 'doctrine.orm.share_entity_manager.property_info_extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor A Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor instance
     */
    protected function getDoctrine_Orm_ShareEntityManager_PropertyInfoExtractorService()
    {
        return $this->services['doctrine.orm.share_entity_manager.property_info_extractor'] = new \Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor($this->get('doctrine.orm.share_entity_manager')->getMetadataFactory());
    }

    /*
     * Gets the 'doctrine.orm.share_manager_configurator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator A Doctrine\Bundle\DoctrineBundle\ManagerConfigurator instance
     */
    protected function getDoctrine_Orm_ShareManagerConfiguratorService()
    {
        return $this->services['doctrine.orm.share_manager_configurator'] = new \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator(array(), array());
    }

    /*
     * Gets the 'doctrine.orm.validator.unique' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator A Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator instance
     */
    protected function getDoctrine_Orm_Validator_UniqueService()
    {
        return $this->services['doctrine.orm.validator.unique'] = new \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator($this->get('doctrine'));
    }

    /*
     * Gets the 'doctrine.orm.validator_initializer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\Validator\DoctrineInitializer A Symfony\Bridge\Doctrine\Validator\DoctrineInitializer instance
     */
    protected function getDoctrine_Orm_ValidatorInitializerService()
    {
        return $this->services['doctrine.orm.validator_initializer'] = new \Symfony\Bridge\Doctrine\Validator\DoctrineInitializer($this->get('doctrine'));
    }

    /*
     * Gets the 'doctrine_cache.providers.configurable_filesystem_provider_type' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\FilesystemCache A Doctrine\Common\Cache\FilesystemCache instance
     */
    protected function getDoctrineCache_Providers_ConfigurableFilesystemProviderTypeService()
    {
        return $this->services['doctrine_cache.providers.configurable_filesystem_provider_type'] = new \Doctrine\Common\Cache\FilesystemCache((__DIR__.'/doctrine/cache/file_system'), NULL, 2);
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.default_result_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_DefaultResultCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.default_result_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_default_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.entry_metadata_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_EntryMetadataCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.entry_metadata_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_entry_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.entry_query_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_EntryQueryCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.entry_query_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_entry_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.entry_result_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_EntryResultCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.entry_result_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_entry_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.his_result_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_HisResultCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.his_result_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_his_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.ip_blocker_metadata_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_IpBlockerMetadataCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.ip_blocker_metadata_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_ip_blocker_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.ip_blocker_query_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_IpBlockerQueryCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.ip_blocker_query_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_ip_blocker_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.ip_blocker_result_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_IpBlockerResultCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.ip_blocker_result_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_ip_blocker_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.outside_metadata_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_OutsideMetadataCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.outside_metadata_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_outside_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.outside_query_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_OutsideQueryCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.outside_query_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_outside_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.outside_result_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_OutsideResultCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.outside_result_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_outside_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.share_metadata_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_ShareMetadataCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.share_metadata_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_share_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.share_query_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_ShareQueryCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.share_query_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_share_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'doctrine_cache.providers.doctrine.orm.share_result_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance
     */
    protected function getDoctrineCache_Providers_Doctrine_Orm_ShareResultCacheService()
    {
        $this->services['doctrine_cache.providers.doctrine.orm.share_result_cache'] = $instance = new \Doctrine\Common\Cache\ArrayCache();

        $instance->setNamespace('sf2orm_share_8bf1da162ef326421c99a13a9a5830680c7abfd955a06a3c69aa2f56d644e2eb');

        return $instance;
    }

    /*
     * Gets the 'durian.activate_sl_next' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Share\ActivateSLNext A BB\DurianBundle\Share\ActivateSLNext instance
     */
    protected function getDurian_ActivateSlNextService()
    {
        $this->services['durian.activate_sl_next'] = $instance = new \BB\DurianBundle\Share\ActivateSLNext();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.ancestor_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\User\AncestorManager A BB\DurianBundle\User\AncestorManager instance
     */
    protected function getDurian_AncestorManagerService()
    {
        $this->services['durian.ancestor_manager'] = $instance = new \BB\DurianBundle\User\AncestorManager();

        $instance->setDoctrine($this->get('doctrine'));
        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.auto_confirm_match_maker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\AutoConfirm\MatchMaker A BB\DurianBundle\AutoConfirm\MatchMaker instance
     */
    protected function getDurian_AutoConfirmMatchMakerService()
    {
        $this->services['durian.auto_confirm_match_maker'] = $instance = new \BB\DurianBundle\AutoConfirm\MatchMaker();

        $instance->setDoctrine($this->get('doctrine'));
        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.auto_remit_checker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Remit\AutoRemitChecker A BB\DurianBundle\Remit\AutoRemitChecker instance
     */
    protected function getDurian_AutoRemitCheckerService()
    {
        $this->services['durian.auto_remit_checker'] = $instance = new \BB\DurianBundle\Remit\AutoRemitChecker();

        $instance->setDoctrine($this->get('doctrine'));
        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.auto_remit_maker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Remit\AutoRemitMaker A BB\DurianBundle\Remit\AutoRemitMaker instance
     */
    protected function getDurian_AutoRemitMakerService()
    {
        $this->services['durian.auto_remit_maker'] = $instance = new \BB\DurianBundle\Remit\AutoRemitMaker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.batch_op' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\BatchOperation A BB\DurianBundle\BatchOperation instance
     */
    protected function getDurian_BatchOpService()
    {
        $this->services['durian.batch_op'] = $instance = new \BB\DurianBundle\BatchOperation();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.bitcoin_deposit_entry_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Bitcoin\Deposit\Entry\IdGenerator A BB\DurianBundle\Bitcoin\Deposit\Entry\IdGenerator instance
     */
    protected function getDurian_BitcoinDepositEntryIdGeneratorService()
    {
        $this->services['durian.bitcoin_deposit_entry_id_generator'] = $instance = new \BB\DurianBundle\Bitcoin\Deposit\Entry\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.bitcoin_withdraw_entry_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Bitcoin\Withdraw\Entry\IdGenerator A BB\DurianBundle\Bitcoin\Withdraw\Entry\IdGenerator instance
     */
    protected function getDurian_BitcoinWithdrawEntryIdGeneratorService()
    {
        $this->services['durian.bitcoin_withdraw_entry_id_generator'] = $instance = new \BB\DurianBundle\Bitcoin\Withdraw\Entry\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.blacklist_validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Blacklist\Validator A BB\DurianBundle\Blacklist\Validator instance
     */
    protected function getDurian_BlacklistValidatorService()
    {
        $this->services['durian.blacklist_validator'] = $instance = new \BB\DurianBundle\Blacklist\Validator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.block_chain' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Payment\BlockChain A BB\DurianBundle\Payment\BlockChain instance
     */
    protected function getDurian_BlockChainService()
    {
        $this->services['durian.block_chain'] = $instance = new \BB\DurianBundle\Payment\BlockChain();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.captcha_genie' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Captcha\Genie A BB\DurianBundle\Captcha\Genie instance
     */
    protected function getDurian_CaptchaGenieService()
    {
        $this->services['durian.captcha_genie'] = $instance = new \BB\DurianBundle\Captcha\Genie();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.card_entry_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Card\Entry\IdGenerator A BB\DurianBundle\Card\Entry\IdGenerator instance
     */
    protected function getDurian_CardEntryIdGeneratorService()
    {
        $this->services['durian.card_entry_id_generator'] = $instance = new \BB\DurianBundle\Card\Entry\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.card_operator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Card\Operator A BB\DurianBundle\Card\Operator instance
     */
    protected function getDurian_CardOperatorService()
    {
        $this->services['durian.card_operator'] = $instance = new \BB\DurianBundle\Card\Operator();

        $instance->setDoctrine($this->get('doctrine'));
        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.cash_entry_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Cash\Entry\IdGenerator A BB\DurianBundle\Cash\Entry\IdGenerator instance
     */
    protected function getDurian_CashEntryIdGeneratorService()
    {
        $this->services['durian.cash_entry_id_generator'] = $instance = new \BB\DurianBundle\Cash\Entry\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.cash_fake_entry_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\CashFake\Entry\IdGenerator A BB\DurianBundle\CashFake\Entry\IdGenerator instance
     */
    protected function getDurian_CashFakeEntryIdGeneratorService()
    {
        $this->services['durian.cash_fake_entry_id_generator'] = $instance = new \BB\DurianBundle\CashFake\Entry\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.cash_fake_helper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Cash\Helper A BB\DurianBundle\Cash\Helper instance
     */
    protected function getDurian_CashFakeHelperService()
    {
        $this->services['durian.cash_fake_helper'] = $instance = new \BB\DurianBundle\Cash\Helper();

        $instance->setDoctrine($this->get('doctrine'));
        $instance->setCashEntryIdGenerator($this->get('durian.cash_entry_id_generator'));
        $instance->setCashFakeEntryIdGenerator($this->get('durian.cash_fake_entry_id_generator'));
        $instance->setOpService($this->get('durian.op'));

        return $instance;
    }

    /*
     * Gets the 'durian.cash_helper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Cash\Helper A BB\DurianBundle\Cash\Helper instance
     */
    protected function getDurian_CashHelperService()
    {
        $this->services['durian.cash_helper'] = $instance = new \BB\DurianBundle\Cash\Helper();

        $instance->setDoctrine($this->get('doctrine'));
        $instance->setCashEntryIdGenerator($this->get('durian.cash_entry_id_generator'));
        $instance->setCashFakeEntryIdGenerator($this->get('durian.cash_fake_entry_id_generator'));
        $instance->setOpService($this->get('durian.op'));

        return $instance;
    }

    /*
     * Gets the 'durian.cashfake_op' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\CashFake\CashFakeOperator A BB\DurianBundle\CashFake\CashFakeOperator instance
     */
    protected function getDurian_CashfakeOpService()
    {
        $this->services['durian.cashfake_op'] = $instance = new \BB\DurianBundle\CashFake\CashFakeOperator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.credit_op' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Credit\CreditOperator A BB\DurianBundle\Credit\CreditOperator instance
     */
    protected function getDurian_CreditOpService()
    {
        $this->services['durian.credit_op'] = $instance = new \BB\DurianBundle\Credit\CreditOperator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.currency' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Currency A BB\DurianBundle\Currency instance
     */
    protected function getDurian_CurrencyService()
    {
        return $this->services['durian.currency'] = new \BB\DurianBundle\Currency();
    }

    /*
     * Gets the 'durian.deposit_entry_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Deposit\Entry\IdGenerator A BB\DurianBundle\Deposit\Entry\IdGenerator instance
     */
    protected function getDurian_DepositEntryIdGeneratorService()
    {
        $this->services['durian.deposit_entry_id_generator'] = $instance = new \BB\DurianBundle\Deposit\Entry\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.deposit_operator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Deposit\Operator A BB\DurianBundle\Deposit\Operator instance
     */
    protected function getDurian_DepositOperatorService()
    {
        $this->services['durian.deposit_operator'] = $instance = new \BB\DurianBundle\Deposit\Operator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.domain_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Domain\IdGenerator A BB\DurianBundle\Domain\IdGenerator instance
     */
    protected function getDurian_DomainIdGeneratorService()
    {
        $this->services['durian.domain_id_generator'] = $instance = new \BB\DurianBundle\Domain\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.domain_msg' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Maintain\DomainMsg A BB\DurianBundle\Maintain\DomainMsg instance
     */
    protected function getDurian_DomainMsgService()
    {
        return $this->services['durian.domain_msg'] = new \BB\DurianBundle\Maintain\DomainMsg($this);
    }

    /*
     * Gets the 'durian.domain_validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Domain\Validator A BB\DurianBundle\Domain\Validator instance
     */
    protected function getDurian_DomainValidatorService()
    {
        $this->services['durian.domain_validator'] = $instance = new \BB\DurianBundle\Domain\Validator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.exception_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\EventListener\ExceptionListener A BB\DurianBundle\EventListener\ExceptionListener instance
     */
    protected function getDurian_ExceptionListenerService()
    {
        $this->services['durian.exception_listener'] = $instance = new \BB\DurianBundle\EventListener\ExceptionListener();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.exchange' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Exchange\Exchange A BB\DurianBundle\Exchange\Exchange instance
     */
    protected function getDurian_ExchangeService()
    {
        $this->services['durian.exchange'] = $instance = new \BB\DurianBundle\Exchange\Exchange();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.http_curl_worker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\HttpCurlWorker A BB\DurianBundle\Message\HttpCurlWorker instance
     */
    protected function getDurian_HttpCurlWorkerService()
    {
        $this->services['durian.http_curl_worker'] = $instance = new \BB\DurianBundle\Message\HttpCurlWorker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.italking_operator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\ITalkingOperator A BB\DurianBundle\Message\ITalkingOperator instance
     */
    protected function getDurian_ItalkingOperatorService()
    {
        return $this->services['durian.italking_operator'] = new \BB\DurianBundle\Message\ITalkingOperator($this);
    }

    /*
     * Gets the 'durian.italking_worker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\ITalkingWorker A BB\DurianBundle\Message\ITalkingWorker instance
     */
    protected function getDurian_ItalkingWorkerService()
    {
        $this->services['durian.italking_worker'] = $instance = new \BB\DurianBundle\Message\ITalkingWorker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.kue_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Kue\KueManager A BB\DurianBundle\Kue\KueManager instance
     */
    protected function getDurian_KueManagerService()
    {
        $this->services['durian.kue_manager'] = $instance = new \BB\DurianBundle\Kue\KueManager();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.logger_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Logger\LoggerManager A BB\DurianBundle\Logger\LoggerManager instance
     */
    protected function getDurian_LoggerManagerService()
    {
        $this->services['durian.logger_manager'] = $instance = new \BB\DurianBundle\Logger\LoggerManager();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.logger_sql' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Logger\SQL A BB\DurianBundle\Logger\SQL instance
     */
    protected function getDurian_LoggerSqlService()
    {
        return $this->services['durian.logger_sql'] = new \BB\DurianBundle\Logger\SQL($this->get('logger'));
    }

    /*
     * Gets the 'durian.login_validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Login\Validator A BB\DurianBundle\Login\Validator instance
     */
    protected function getDurian_LoginValidatorService()
    {
        $this->services['durian.login_validator'] = $instance = new \BB\DurianBundle\Login\Validator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.maintain_operator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Maintain\MaintainOperator A BB\DurianBundle\Maintain\MaintainOperator instance
     */
    protected function getDurian_MaintainOperatorService()
    {
        return $this->services['durian.maintain_operator'] = new \BB\DurianBundle\Maintain\MaintainOperator($this);
    }

    /*
     * Gets the 'durian.mobile_whitelist_worker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\MobileWhitelistWorker A BB\DurianBundle\Message\MobileWhitelistWorker instance
     */
    protected function getDurian_MobileWhitelistWorkerService()
    {
        $this->services['durian.mobile_whitelist_worker'] = $instance = new \BB\DurianBundle\Message\MobileWhitelistWorker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.monitor.background' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Monitor\Background A BB\DurianBundle\Monitor\Background instance
     */
    protected function getDurian_Monitor_BackgroundService()
    {
        $this->services['durian.monitor.background'] = $instance = new \BB\DurianBundle\Monitor\Background();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.oauth2_server' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Oauth2\Server A BB\DurianBundle\Oauth2\Server instance
     */
    protected function getDurian_Oauth2ServerService()
    {
        $this->services['durian.oauth2_server'] = $instance = new \BB\DurianBundle\Oauth2\Server();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.oauth_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Oauth\OauthGenerator A BB\DurianBundle\Oauth\OauthGenerator instance
     */
    protected function getDurian_OauthGeneratorService()
    {
        return $this->services['durian.oauth_generator'] = new \BB\DurianBundle\Oauth\OauthGenerator();
    }

    /*
     * Gets the 'durian.op' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Service\OpService A BB\DurianBundle\Service\OpService instance
     */
    protected function getDurian_OpService()
    {
        $this->services['durian.op'] = $instance = new \BB\DurianBundle\Service\OpService();

        $instance->setCashHelper($this->get('durian.cash_helper'));
        $instance->setDoctrine($this->get('doctrine'));
        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.operation_logger' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Logger\Operation A BB\DurianBundle\Logger\Operation instance
     */
    protected function getDurian_OperationLoggerService()
    {
        $this->services['durian.operation_logger'] = $instance = new \BB\DurianBundle\Logger\Operation();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.otp_worker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Otp\Worker A BB\DurianBundle\Otp\Worker instance
     */
    protected function getDurian_OtpWorkerService()
    {
        $this->services['durian.otp_worker'] = $instance = new \BB\DurianBundle\Otp\Worker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.parameter_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\ParameterHandler A BB\DurianBundle\ParameterHandler instance
     */
    protected function getDurian_ParameterHandlerService()
    {
        return $this->services['durian.parameter_handler'] = new \BB\DurianBundle\ParameterHandler();
    }

    /*
     * Gets the 'durian.payment_logger' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Logger\Payment A BB\DurianBundle\Logger\Payment instance
     */
    protected function getDurian_PaymentLoggerService()
    {
        $this->services['durian.payment_logger'] = $instance = new \BB\DurianBundle\Logger\Payment();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.payment_operator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Payment\Operator A BB\DurianBundle\Payment\Operator instance
     */
    protected function getDurian_PaymentOperatorService()
    {
        $this->services['durian.payment_operator'] = $instance = new \BB\DurianBundle\Payment\Operator();

        $instance->setDoctrine($this->get('doctrine'));
        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.rd1_maintain_worker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\RD1MaintainWorker A BB\DurianBundle\Message\RD1MaintainWorker instance
     */
    protected function getDurian_Rd1MaintainWorkerService()
    {
        $this->services['durian.rd1_maintain_worker'] = $instance = new \BB\DurianBundle\Message\RD1MaintainWorker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.rd1_operator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\RD1Worker A BB\DurianBundle\Message\RD1Worker instance
     */
    protected function getDurian_Rd1OperatorService()
    {
        $this->services['durian.rd1_operator'] = $instance = new \BB\DurianBundle\Message\RD1Worker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.rd1_whitelist_worker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\RD1WhitelistWorker A BB\DurianBundle\Message\RD1WhitelistWorker instance
     */
    protected function getDurian_Rd1WhitelistWorkerService()
    {
        $this->services['durian.rd1_whitelist_worker'] = $instance = new \BB\DurianBundle\Message\RD1WhitelistWorker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.rd2_operator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\RD2Worker A BB\DurianBundle\Message\RD2Worker instance
     */
    protected function getDurian_Rd2OperatorService()
    {
        $this->services['durian.rd2_operator'] = $instance = new \BB\DurianBundle\Message\RD2Worker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.rd3_maintain_worker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\RD3MaintainWorker A BB\DurianBundle\Message\RD3MaintainWorker instance
     */
    protected function getDurian_Rd3MaintainWorkerService()
    {
        $this->services['durian.rd3_maintain_worker'] = $instance = new \BB\DurianBundle\Message\RD3MaintainWorker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.rd3_operator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Message\RD3Worker A BB\DurianBundle\Message\RD3Worker instance
     */
    protected function getDurian_Rd3OperatorService()
    {
        $this->services['durian.rd3_operator'] = $instance = new \BB\DurianBundle\Message\RD3Worker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.remit_auto_confirm_logger' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Logger\RemitAutoConfirm A BB\DurianBundle\Logger\RemitAutoConfirm instance
     */
    protected function getDurian_RemitAutoConfirmLoggerService()
    {
        $this->services['durian.remit_auto_confirm_logger'] = $instance = new \BB\DurianBundle\Logger\RemitAutoConfirm();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.remit_helper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Remit\Helper A BB\DurianBundle\Remit\Helper instance
     */
    protected function getDurian_RemitHelperService()
    {
        $this->services['durian.remit_helper'] = $instance = new \BB\DurianBundle\Remit\Helper();

        $instance->setDoctrine($this->get('doctrine'));
        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.remit_order_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Remit\OrderNumberGenerator A BB\DurianBundle\Remit\OrderNumberGenerator instance
     */
    protected function getDurian_RemitOrderGeneratorService()
    {
        $this->services['durian.remit_order_generator'] = $instance = new \BB\DurianBundle\Remit\OrderNumberGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.sensitive_logger' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Logger\Sensitive A BB\DurianBundle\Logger\Sensitive instance
     */
    protected function getDurian_SensitiveLoggerService()
    {
        $this->services['durian.sensitive_logger'] = $instance = new \BB\DurianBundle\Logger\Sensitive();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.session_broker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Session\Broker A BB\DurianBundle\Session\Broker instance
     */
    protected function getDurian_SessionBrokerService()
    {
        $this->services['durian.session_broker'] = $instance = new \BB\DurianBundle\Session\Broker();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.share_dealer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Share\Dealer A BB\DurianBundle\Share\Dealer instance
     */
    protected function getDurian_ShareDealerService()
    {
        $this->services['durian.share_dealer'] = $instance = new \BB\DurianBundle\Share\Dealer();

        $instance->setValidator($this->get('durian.share_validator'));

        return $instance;
    }

    /*
     * Gets the 'durian.share_mocker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Share\Mocker A BB\DurianBundle\Share\Mocker instance
     */
    protected function getDurian_ShareMockerService()
    {
        return $this->services['durian.share_mocker'] = new \BB\DurianBundle\Share\Mocker();
    }

    /*
     * Gets the 'durian.share_option_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Share\OptionGenerator A BB\DurianBundle\Share\OptionGenerator instance
     */
    protected function getDurian_ShareOptionGeneratorService()
    {
        return $this->services['durian.share_option_generator'] = new \BB\DurianBundle\Share\OptionGenerator();
    }

    /*
     * Gets the 'durian.share_scheduled_for_update' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Share\ScheduledForUpdate A BB\DurianBundle\Share\ScheduledForUpdate instance
     */
    protected function getDurian_ShareScheduledForUpdateService()
    {
        $this->services['durian.share_scheduled_for_update'] = $instance = new \BB\DurianBundle\Share\ScheduledForUpdate();

        $instance->setDoctrine($this->get('doctrine'));

        return $instance;
    }

    /*
     * Gets the 'durian.share_validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Share\Validator A BB\DurianBundle\Share\Validator instance
     */
    protected function getDurian_ShareValidatorService()
    {
        $this->services['durian.share_validator'] = $instance = new \BB\DurianBundle\Share\Validator();

        $instance->setScheduler($this->get('durian.share_scheduled_for_update'));

        return $instance;
    }

    /*
     * Gets the 'durian.user_detail_validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\UserDetail\Validator A BB\DurianBundle\UserDetail\Validator instance
     */
    protected function getDurian_UserDetailValidatorService()
    {
        $this->services['durian.user_detail_validator'] = $instance = new \BB\DurianBundle\UserDetail\Validator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.user_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\User\IdGenerator A BB\DurianBundle\User\IdGenerator instance
     */
    protected function getDurian_UserIdGeneratorService()
    {
        $this->services['durian.user_id_generator'] = $instance = new \BB\DurianBundle\User\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.user_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\User\UserManager A BB\DurianBundle\User\UserManager instance
     */
    protected function getDurian_UserManagerService()
    {
        $this->services['durian.user_manager'] = $instance = new \BB\DurianBundle\User\UserManager();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.user_payway' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\User\Payway A BB\DurianBundle\User\Payway instance
     */
    protected function getDurian_UserPaywayService()
    {
        $this->services['durian.user_payway'] = $instance = new \BB\DurianBundle\User\Payway();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.user_validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\User\Validator A BB\DurianBundle\User\Validator instance
     */
    protected function getDurian_UserValidatorService()
    {
        $this->services['durian.user_validator'] = $instance = new \BB\DurianBundle\User\Validator();

        $instance->setDoctrine($this->get('doctrine'));

        return $instance;
    }

    /*
     * Gets the 'durian.userdetail_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\UserDetail\Generator A BB\DurianBundle\UserDetail\Generator instance
     */
    protected function getDurian_UserdetailGeneratorService()
    {
        $this->services['durian.userdetail_generator'] = $instance = new \BB\DurianBundle\UserDetail\Generator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Validator A BB\DurianBundle\Validator instance
     */
    protected function getDurian_ValidatorService()
    {
        return $this->services['durian.validator'] = new \BB\DurianBundle\Validator();
    }

    /*
     * Gets the 'durian.withdraw_entry_id_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Withdraw\Entry\IdGenerator A BB\DurianBundle\Withdraw\Entry\IdGenerator instance
     */
    protected function getDurian_WithdrawEntryIdGeneratorService()
    {
        $this->services['durian.withdraw_entry_id_generator'] = $instance = new \BB\DurianBundle\Withdraw\Entry\IdGenerator();

        $instance->setContainer($this);

        return $instance;
    }

    /*
     * Gets the 'durian.withdraw_helper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \BB\DurianBundle\Withdraw\Helper A BB\DurianBundle\Withdraw\Helper instance
     */
    protected function getDurian_WithdrawHelperService()
    {
        return $this->services['durian.withdraw_helper'] = new \BB\DurianBundle\Withdraw\Helper($this);
    }

    /*
     * Gets the 'event_dispatcher' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher A Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher instance
     */
    protected function getEventDispatcherService()
    {
        $this->services['event_dispatcher'] = $instance = new \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher($this);

        $instance->addListenerService('kernel.exception', array(0 => 'durian.exception_listener', 1 => 'onKernelException'), 0);
        $instance->addListenerService('kernel.response', array(0 => 'durian.exception_listener', 1 => 'onKernelResponse'), 0);
        $instance->addListenerService('kernel.request', array(0 => 'durian.exception_listener', 1 => 'onKernelRequest'), 0);
        $instance->addListenerService('console.exception', array(0 => 'durian.exception_listener', 1 => 'onConsoleException'), 0);
        $instance->addListenerService('kernel.controller', array(0 => 'durian.exception_listener', 1 => 'onKernelController'), 0);
        $instance->addSubscriberService('response_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\ResponseListener');
        $instance->addSubscriberService('streamed_response_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\StreamedResponseListener');
        $instance->addSubscriberService('locale_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\LocaleListener');
        $instance->addSubscriberService('translator_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\TranslatorListener');
        $instance->addSubscriberService('validate_request_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\ValidateRequestListener');
        $instance->addSubscriberService('session_listener', 'Symfony\\Bundle\\FrameworkBundle\\EventListener\\SessionListener');
        $instance->addSubscriberService('session.save_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\SaveSessionListener');
        $instance->addSubscriberService('fragment.listener', 'Symfony\\Component\\HttpKernel\\EventListener\\FragmentListener');
        $instance->addSubscriberService('router_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\RouterListener');
        $instance->addSubscriberService('debug.debug_handlers_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\DebugHandlersListener');
        $instance->addSubscriberService('twig.exception_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\ExceptionListener');
        $instance->addSubscriberService('sensio_framework_extra.controller.listener', 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\ControllerListener');
        $instance->addSubscriberService('sensio_framework_extra.converter.listener', 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\ParamConverterListener');
        $instance->addSubscriberService('sensio_framework_extra.view.listener', 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\TemplateListener');
        $instance->addSubscriberService('sensio_framework_extra.cache.listener', 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\HttpCacheListener');
        $instance->addSubscriberService('sensio_framework_extra.security.listener', 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\SecurityListener');

        return $instance;
    }

    /*
     * Gets the 'file_locator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\Config\FileLocator A Symfony\Component\HttpKernel\Config\FileLocator instance
     */
    protected function getFileLocatorService()
    {
        return $this->services['file_locator'] = new \Symfony\Component\HttpKernel\Config\FileLocator($this->get('kernel'), ($this->targetDirs[2].'/Resources'));
    }

    /*
     * Gets the 'filesystem' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Filesystem\Filesystem A Symfony\Component\Filesystem\Filesystem instance
     */
    protected function getFilesystemService()
    {
        return $this->services['filesystem'] = new \Symfony\Component\Filesystem\Filesystem();
    }

    /*
     * Gets the 'form.csrf_provider' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfTokenManagerAdapter A Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfTokenManagerAdapter instance
     *
     * @deprecated The "form.csrf_provider" service is deprecated since Symfony 2.4 and will be removed in 3.0. Use the "security.csrf.token_manager" service instead.
     */
    protected function getForm_CsrfProviderService()
    {
        @trigger_error('The "form.csrf_provider" service is deprecated since Symfony 2.4 and will be removed in 3.0. Use the "security.csrf.token_manager" service instead.', E_USER_DEPRECATED);

        return $this->services['form.csrf_provider'] = new \Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfTokenManagerAdapter($this->get('security.csrf.token_manager'));
    }

    /*
     * Gets the 'form.factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\FormFactory A Symfony\Component\Form\FormFactory instance
     */
    protected function getForm_FactoryService()
    {
        return $this->services['form.factory'] = new \Symfony\Component\Form\FormFactory($this->get('form.registry'), $this->get('form.resolved_type_factory'));
    }

    /*
     * Gets the 'form.registry' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\FormRegistry A Symfony\Component\Form\FormRegistry instance
     */
    protected function getForm_RegistryService()
    {
        return $this->services['form.registry'] = new \Symfony\Component\Form\FormRegistry(array(0 => new \Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension($this, array('form' => 'form.type.form', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType' => 'form.type.form', 'birthday' => 'form.type.birthday', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\BirthdayType' => 'form.type.birthday', 'checkbox' => 'form.type.checkbox', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType' => 'form.type.checkbox', 'choice' => 'form.type.choice', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType' => 'form.type.choice', 'collection' => 'form.type.collection', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType' => 'form.type.collection', 'country' => 'form.type.country', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CountryType' => 'form.type.country', 'date' => 'form.type.date', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateType' => 'form.type.date', 'datetime' => 'form.type.datetime', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType' => 'form.type.datetime', 'email' => 'form.type.email', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType' => 'form.type.email', 'file' => 'form.type.file', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FileType' => 'form.type.file', 'hidden' => 'form.type.hidden', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\HiddenType' => 'form.type.hidden', 'integer' => 'form.type.integer', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\IntegerType' => 'form.type.integer', 'language' => 'form.type.language', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\LanguageType' => 'form.type.language', 'locale' => 'form.type.locale', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\LocaleType' => 'form.type.locale', 'money' => 'form.type.money', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\MoneyType' => 'form.type.money', 'number' => 'form.type.number', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\NumberType' => 'form.type.number', 'password' => 'form.type.password', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\PasswordType' => 'form.type.password', 'percent' => 'form.type.percent', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\PercentType' => 'form.type.percent', 'radio' => 'form.type.radio', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RadioType' => 'form.type.radio', 'range' => 'form.type.range', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RangeType' => 'form.type.range', 'repeated' => 'form.type.repeated', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RepeatedType' => 'form.type.repeated', 'search' => 'form.type.search', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\SearchType' => 'form.type.search', 'textarea' => 'form.type.textarea', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType' => 'form.type.textarea', 'text' => 'form.type.text', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType' => 'form.type.text', 'time' => 'form.type.time', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TimeType' => 'form.type.time', 'timezone' => 'form.type.timezone', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TimezoneType' => 'form.type.timezone', 'url' => 'form.type.url', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\UrlType' => 'form.type.url', 'button' => 'form.type.button', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ButtonType' => 'form.type.button', 'submit' => 'form.type.submit', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\SubmitType' => 'form.type.submit', 'reset' => 'form.type.reset', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ResetType' => 'form.type.reset', 'currency' => 'form.type.currency', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CurrencyType' => 'form.type.currency', 'entity' => 'form.type.entity', 'Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType' => 'form.type.entity'), array('Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType' => array(0 => 'form.type_extension.form.http_foundation', 1 => 'form.type_extension.form.validator', 2 => 'form.type_extension.upload.validator', 3 => 'form.type_extension.csrf'), 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RepeatedType' => array(0 => 'form.type_extension.repeated.validator'), 'Symfony\\Component\\Form\\Extension\\Core\\Type\\SubmitType' => array(0 => 'form.type_extension.submit.validator')), array(0 => 'form.type_guesser.validator', 1 => 'form.type_guesser.doctrine'))), $this->get('form.resolved_type_factory'));
    }

    /*
     * Gets the 'form.resolved_type_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\ResolvedFormTypeFactory A Symfony\Component\Form\ResolvedFormTypeFactory instance
     */
    protected function getForm_ResolvedTypeFactoryService()
    {
        return $this->services['form.resolved_type_factory'] = new \Symfony\Component\Form\ResolvedFormTypeFactory();
    }

    /*
     * Gets the 'form.type.birthday' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\BirthdayType A Symfony\Component\Form\Extension\Core\Type\BirthdayType instance
     */
    protected function getForm_Type_BirthdayService()
    {
        return $this->services['form.type.birthday'] = new \Symfony\Component\Form\Extension\Core\Type\BirthdayType();
    }

    /*
     * Gets the 'form.type.button' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\ButtonType A Symfony\Component\Form\Extension\Core\Type\ButtonType instance
     */
    protected function getForm_Type_ButtonService()
    {
        return $this->services['form.type.button'] = new \Symfony\Component\Form\Extension\Core\Type\ButtonType();
    }

    /*
     * Gets the 'form.type.checkbox' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\CheckboxType A Symfony\Component\Form\Extension\Core\Type\CheckboxType instance
     */
    protected function getForm_Type_CheckboxService()
    {
        return $this->services['form.type.checkbox'] = new \Symfony\Component\Form\Extension\Core\Type\CheckboxType();
    }

    /*
     * Gets the 'form.type.choice' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\ChoiceType A Symfony\Component\Form\Extension\Core\Type\ChoiceType instance
     */
    protected function getForm_Type_ChoiceService()
    {
        return $this->services['form.type.choice'] = new \Symfony\Component\Form\Extension\Core\Type\ChoiceType(new \Symfony\Component\Form\ChoiceList\Factory\CachingFactoryDecorator(new \Symfony\Component\Form\ChoiceList\Factory\PropertyAccessDecorator(new \Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory(), $this->get('property_accessor'))));
    }

    /*
     * Gets the 'form.type.collection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\CollectionType A Symfony\Component\Form\Extension\Core\Type\CollectionType instance
     */
    protected function getForm_Type_CollectionService()
    {
        return $this->services['form.type.collection'] = new \Symfony\Component\Form\Extension\Core\Type\CollectionType();
    }

    /*
     * Gets the 'form.type.country' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\CountryType A Symfony\Component\Form\Extension\Core\Type\CountryType instance
     */
    protected function getForm_Type_CountryService()
    {
        return $this->services['form.type.country'] = new \Symfony\Component\Form\Extension\Core\Type\CountryType();
    }

    /*
     * Gets the 'form.type.currency' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\CurrencyType A Symfony\Component\Form\Extension\Core\Type\CurrencyType instance
     */
    protected function getForm_Type_CurrencyService()
    {
        return $this->services['form.type.currency'] = new \Symfony\Component\Form\Extension\Core\Type\CurrencyType();
    }

    /*
     * Gets the 'form.type.date' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\DateType A Symfony\Component\Form\Extension\Core\Type\DateType instance
     */
    protected function getForm_Type_DateService()
    {
        return $this->services['form.type.date'] = new \Symfony\Component\Form\Extension\Core\Type\DateType();
    }

    /*
     * Gets the 'form.type.datetime' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\DateTimeType A Symfony\Component\Form\Extension\Core\Type\DateTimeType instance
     */
    protected function getForm_Type_DatetimeService()
    {
        return $this->services['form.type.datetime'] = new \Symfony\Component\Form\Extension\Core\Type\DateTimeType();
    }

    /*
     * Gets the 'form.type.email' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\EmailType A Symfony\Component\Form\Extension\Core\Type\EmailType instance
     */
    protected function getForm_Type_EmailService()
    {
        return $this->services['form.type.email'] = new \Symfony\Component\Form\Extension\Core\Type\EmailType();
    }

    /*
     * Gets the 'form.type.entity' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\Form\Type\EntityType A Symfony\Bridge\Doctrine\Form\Type\EntityType instance
     */
    protected function getForm_Type_EntityService()
    {
        return $this->services['form.type.entity'] = new \Symfony\Bridge\Doctrine\Form\Type\EntityType($this->get('doctrine'));
    }

    /*
     * Gets the 'form.type.file' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\FileType A Symfony\Component\Form\Extension\Core\Type\FileType instance
     */
    protected function getForm_Type_FileService()
    {
        return $this->services['form.type.file'] = new \Symfony\Component\Form\Extension\Core\Type\FileType();
    }

    /*
     * Gets the 'form.type.form' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\FormType A Symfony\Component\Form\Extension\Core\Type\FormType instance
     */
    protected function getForm_Type_FormService()
    {
        return $this->services['form.type.form'] = new \Symfony\Component\Form\Extension\Core\Type\FormType($this->get('property_accessor'));
    }

    /*
     * Gets the 'form.type.hidden' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\HiddenType A Symfony\Component\Form\Extension\Core\Type\HiddenType instance
     */
    protected function getForm_Type_HiddenService()
    {
        return $this->services['form.type.hidden'] = new \Symfony\Component\Form\Extension\Core\Type\HiddenType();
    }

    /*
     * Gets the 'form.type.integer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\IntegerType A Symfony\Component\Form\Extension\Core\Type\IntegerType instance
     */
    protected function getForm_Type_IntegerService()
    {
        return $this->services['form.type.integer'] = new \Symfony\Component\Form\Extension\Core\Type\IntegerType();
    }

    /*
     * Gets the 'form.type.language' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\LanguageType A Symfony\Component\Form\Extension\Core\Type\LanguageType instance
     */
    protected function getForm_Type_LanguageService()
    {
        return $this->services['form.type.language'] = new \Symfony\Component\Form\Extension\Core\Type\LanguageType();
    }

    /*
     * Gets the 'form.type.locale' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\LocaleType A Symfony\Component\Form\Extension\Core\Type\LocaleType instance
     */
    protected function getForm_Type_LocaleService()
    {
        return $this->services['form.type.locale'] = new \Symfony\Component\Form\Extension\Core\Type\LocaleType();
    }

    /*
     * Gets the 'form.type.money' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\MoneyType A Symfony\Component\Form\Extension\Core\Type\MoneyType instance
     */
    protected function getForm_Type_MoneyService()
    {
        return $this->services['form.type.money'] = new \Symfony\Component\Form\Extension\Core\Type\MoneyType();
    }

    /*
     * Gets the 'form.type.number' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\NumberType A Symfony\Component\Form\Extension\Core\Type\NumberType instance
     */
    protected function getForm_Type_NumberService()
    {
        return $this->services['form.type.number'] = new \Symfony\Component\Form\Extension\Core\Type\NumberType();
    }

    /*
     * Gets the 'form.type.password' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\PasswordType A Symfony\Component\Form\Extension\Core\Type\PasswordType instance
     */
    protected function getForm_Type_PasswordService()
    {
        return $this->services['form.type.password'] = new \Symfony\Component\Form\Extension\Core\Type\PasswordType();
    }

    /*
     * Gets the 'form.type.percent' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\PercentType A Symfony\Component\Form\Extension\Core\Type\PercentType instance
     */
    protected function getForm_Type_PercentService()
    {
        return $this->services['form.type.percent'] = new \Symfony\Component\Form\Extension\Core\Type\PercentType();
    }

    /*
     * Gets the 'form.type.radio' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\RadioType A Symfony\Component\Form\Extension\Core\Type\RadioType instance
     */
    protected function getForm_Type_RadioService()
    {
        return $this->services['form.type.radio'] = new \Symfony\Component\Form\Extension\Core\Type\RadioType();
    }

    /*
     * Gets the 'form.type.range' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\RangeType A Symfony\Component\Form\Extension\Core\Type\RangeType instance
     */
    protected function getForm_Type_RangeService()
    {
        return $this->services['form.type.range'] = new \Symfony\Component\Form\Extension\Core\Type\RangeType();
    }

    /*
     * Gets the 'form.type.repeated' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\RepeatedType A Symfony\Component\Form\Extension\Core\Type\RepeatedType instance
     */
    protected function getForm_Type_RepeatedService()
    {
        return $this->services['form.type.repeated'] = new \Symfony\Component\Form\Extension\Core\Type\RepeatedType();
    }

    /*
     * Gets the 'form.type.reset' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\ResetType A Symfony\Component\Form\Extension\Core\Type\ResetType instance
     */
    protected function getForm_Type_ResetService()
    {
        return $this->services['form.type.reset'] = new \Symfony\Component\Form\Extension\Core\Type\ResetType();
    }

    /*
     * Gets the 'form.type.search' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\SearchType A Symfony\Component\Form\Extension\Core\Type\SearchType instance
     */
    protected function getForm_Type_SearchService()
    {
        return $this->services['form.type.search'] = new \Symfony\Component\Form\Extension\Core\Type\SearchType();
    }

    /*
     * Gets the 'form.type.submit' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\SubmitType A Symfony\Component\Form\Extension\Core\Type\SubmitType instance
     */
    protected function getForm_Type_SubmitService()
    {
        return $this->services['form.type.submit'] = new \Symfony\Component\Form\Extension\Core\Type\SubmitType();
    }

    /*
     * Gets the 'form.type.text' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\TextType A Symfony\Component\Form\Extension\Core\Type\TextType instance
     */
    protected function getForm_Type_TextService()
    {
        return $this->services['form.type.text'] = new \Symfony\Component\Form\Extension\Core\Type\TextType();
    }

    /*
     * Gets the 'form.type.textarea' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\TextareaType A Symfony\Component\Form\Extension\Core\Type\TextareaType instance
     */
    protected function getForm_Type_TextareaService()
    {
        return $this->services['form.type.textarea'] = new \Symfony\Component\Form\Extension\Core\Type\TextareaType();
    }

    /*
     * Gets the 'form.type.time' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\TimeType A Symfony\Component\Form\Extension\Core\Type\TimeType instance
     */
    protected function getForm_Type_TimeService()
    {
        return $this->services['form.type.time'] = new \Symfony\Component\Form\Extension\Core\Type\TimeType();
    }

    /*
     * Gets the 'form.type.timezone' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\TimezoneType A Symfony\Component\Form\Extension\Core\Type\TimezoneType instance
     */
    protected function getForm_Type_TimezoneService()
    {
        return $this->services['form.type.timezone'] = new \Symfony\Component\Form\Extension\Core\Type\TimezoneType();
    }

    /*
     * Gets the 'form.type.url' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Core\Type\UrlType A Symfony\Component\Form\Extension\Core\Type\UrlType instance
     */
    protected function getForm_Type_UrlService()
    {
        return $this->services['form.type.url'] = new \Symfony\Component\Form\Extension\Core\Type\UrlType();
    }

    /*
     * Gets the 'form.type_extension.csrf' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Csrf\Type\FormTypeCsrfExtension A Symfony\Component\Form\Extension\Csrf\Type\FormTypeCsrfExtension instance
     */
    protected function getForm_TypeExtension_CsrfService()
    {
        return $this->services['form.type_extension.csrf'] = new \Symfony\Component\Form\Extension\Csrf\Type\FormTypeCsrfExtension($this->get('security.csrf.token_manager'), true, '_token', $this->get('translator.default'), 'validators', $this->get('form.server_params'));
    }

    /*
     * Gets the 'form.type_extension.form.http_foundation' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension A Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension instance
     */
    protected function getForm_TypeExtension_Form_HttpFoundationService()
    {
        return $this->services['form.type_extension.form.http_foundation'] = new \Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension(new \Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler($this->get('form.server_params')));
    }

    /*
     * Gets the 'form.type_extension.form.validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension A Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension instance
     */
    protected function getForm_TypeExtension_Form_ValidatorService()
    {
        return $this->services['form.type_extension.form.validator'] = new \Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension($this->get('validator'));
    }

    /*
     * Gets the 'form.type_extension.repeated.validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Validator\Type\RepeatedTypeValidatorExtension A Symfony\Component\Form\Extension\Validator\Type\RepeatedTypeValidatorExtension instance
     */
    protected function getForm_TypeExtension_Repeated_ValidatorService()
    {
        return $this->services['form.type_extension.repeated.validator'] = new \Symfony\Component\Form\Extension\Validator\Type\RepeatedTypeValidatorExtension();
    }

    /*
     * Gets the 'form.type_extension.submit.validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Validator\Type\SubmitTypeValidatorExtension A Symfony\Component\Form\Extension\Validator\Type\SubmitTypeValidatorExtension instance
     */
    protected function getForm_TypeExtension_Submit_ValidatorService()
    {
        return $this->services['form.type_extension.submit.validator'] = new \Symfony\Component\Form\Extension\Validator\Type\SubmitTypeValidatorExtension();
    }

    /*
     * Gets the 'form.type_extension.upload.validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Validator\Type\UploadValidatorExtension A Symfony\Component\Form\Extension\Validator\Type\UploadValidatorExtension instance
     */
    protected function getForm_TypeExtension_Upload_ValidatorService()
    {
        return $this->services['form.type_extension.upload.validator'] = new \Symfony\Component\Form\Extension\Validator\Type\UploadValidatorExtension($this->get('translator.default'), 'validators');
    }

    /*
     * Gets the 'form.type_guesser.doctrine' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser A Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser instance
     */
    protected function getForm_TypeGuesser_DoctrineService()
    {
        return $this->services['form.type_guesser.doctrine'] = new \Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser($this->get('doctrine'));
    }

    /*
     * Gets the 'form.type_guesser.validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser A Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser instance
     */
    protected function getForm_TypeGuesser_ValidatorService()
    {
        return $this->services['form.type_guesser.validator'] = new \Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser($this->get('validator'));
    }

    /*
     * Gets the 'fos_js_routing.controller' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \FOS\JsRoutingBundle\Controller\Controller A FOS\JsRoutingBundle\Controller\Controller instance
     */
    protected function getFosJsRouting_ControllerService()
    {
        return $this->services['fos_js_routing.controller'] = new \FOS\JsRoutingBundle\Controller\Controller($this->get('fos_js_routing.serializer'), $this->get('fos_js_routing.extractor'), array('enabled' => false), false);
    }

    /*
     * Gets the 'fos_js_routing.extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \FOS\JsRoutingBundle\Extractor\ExposedRoutesExtractor A FOS\JsRoutingBundle\Extractor\ExposedRoutesExtractor instance
     */
    protected function getFosJsRouting_ExtractorService()
    {
        return $this->services['fos_js_routing.extractor'] = new \FOS\JsRoutingBundle\Extractor\ExposedRoutesExtractor($this->get('router'), array(0 => 'home', 1 => '^demo_.*$', 2 => '^api_.*$', 3 => '^monitor_.*$', 4 => '^tools.*$', 5 => '^log_operation$'), __DIR__, array('FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle', 'TwigBundle' => 'Symfony\\Bundle\\TwigBundle\\TwigBundle', 'MonologBundle' => 'Symfony\\Bundle\\MonologBundle\\MonologBundle', 'DoctrineBundle' => 'Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle', 'DoctrineCacheBundle' => 'Doctrine\\Bundle\\DoctrineCacheBundle\\DoctrineCacheBundle', 'DoctrineMigrationsBundle' => 'Doctrine\\Bundle\\MigrationsBundle\\DoctrineMigrationsBundle', 'SensioFrameworkExtraBundle' => 'Sensio\\Bundle\\FrameworkExtraBundle\\SensioFrameworkExtraBundle', 'FOSJsRoutingBundle' => 'FOS\\JsRoutingBundle\\FOSJsRoutingBundle', 'KnpMarkdownBundle' => 'Knp\\Bundle\\MarkdownBundle\\KnpMarkdownBundle', 'SncRedisBundle' => 'Snc\\RedisBundle\\SncRedisBundle', 'BBDurianBundle' => 'BB\\DurianBundle\\BBDurianBundle'));
    }

    /*
     * Gets the 'fos_js_routing.serializer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Serializer\Serializer A Symfony\Component\Serializer\Serializer instance
     */
    protected function getFosJsRouting_SerializerService()
    {
        return $this->services['fos_js_routing.serializer'] = new \Symfony\Component\Serializer\Serializer(array(0 => new \Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer()), array('json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder()));
    }

    /*
     * Gets the 'fragment.handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\DependencyInjection\LazyLoadingFragmentHandler A Symfony\Component\HttpKernel\DependencyInjection\LazyLoadingFragmentHandler instance
     */
    protected function getFragment_HandlerService()
    {
        $this->services['fragment.handler'] = $instance = new \Symfony\Component\HttpKernel\DependencyInjection\LazyLoadingFragmentHandler($this, $this->get('request_stack'), false);

        $instance->addRendererService('inline', 'fragment.renderer.inline');
        $instance->addRendererService('hinclude', 'fragment.renderer.hinclude');
        $instance->addRendererService('hinclude', 'fragment.renderer.hinclude');
        $instance->addRendererService('esi', 'fragment.renderer.esi');
        $instance->addRendererService('ssi', 'fragment.renderer.ssi');

        return $instance;
    }

    /*
     * Gets the 'fragment.listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\FragmentListener A Symfony\Component\HttpKernel\EventListener\FragmentListener instance
     */
    protected function getFragment_ListenerService()
    {
        return $this->services['fragment.listener'] = new \Symfony\Component\HttpKernel\EventListener\FragmentListener($this->get('uri_signer'), '/_fragment');
    }

    /*
     * Gets the 'fragment.renderer.esi' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer A Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer instance
     */
    protected function getFragment_Renderer_EsiService()
    {
        $this->services['fragment.renderer.esi'] = $instance = new \Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer(NULL, $this->get('fragment.renderer.inline'), $this->get('uri_signer'));

        $instance->setFragmentPath('/_fragment');

        return $instance;
    }

    /*
     * Gets the 'fragment.renderer.hinclude' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\Fragment\HIncludeFragmentRenderer A Symfony\Component\HttpKernel\Fragment\HIncludeFragmentRenderer instance
     */
    protected function getFragment_Renderer_HincludeService()
    {
        $this->services['fragment.renderer.hinclude'] = $instance = new \Symfony\Component\HttpKernel\Fragment\HIncludeFragmentRenderer($this->get('twig'), $this->get('uri_signer'), NULL);

        $instance->setFragmentPath('/_fragment');

        return $instance;
    }

    /*
     * Gets the 'fragment.renderer.inline' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer A Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer instance
     */
    protected function getFragment_Renderer_InlineService()
    {
        $this->services['fragment.renderer.inline'] = $instance = new \Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer($this->get('http_kernel'), $this->get('event_dispatcher'));

        $instance->setFragmentPath('/_fragment');

        return $instance;
    }

    /*
     * Gets the 'fragment.renderer.ssi' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\Fragment\SsiFragmentRenderer A Symfony\Component\HttpKernel\Fragment\SsiFragmentRenderer instance
     */
    protected function getFragment_Renderer_SsiService()
    {
        $this->services['fragment.renderer.ssi'] = $instance = new \Symfony\Component\HttpKernel\Fragment\SsiFragmentRenderer(NULL, $this->get('fragment.renderer.inline'), $this->get('uri_signer'));

        $instance->setFragmentPath('/_fragment');

        return $instance;
    }

    /*
     * Gets the 'http_kernel' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel A Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel instance
     */
    protected function getHttpKernelService()
    {
        return $this->services['http_kernel'] = new \Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel($this->get('event_dispatcher'), $this, new \Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver($this, $this->get('controller_name_converter'), $this->get('monolog.logger.request', ContainerInterface::NULL_ON_INVALID_REFERENCE)), $this->get('request_stack'), false);
    }

    /*
     * Gets the 'kernel' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @throws RuntimeException always since this service is expected to be injected dynamically
     */
    protected function getKernelService()
    {
        throw new RuntimeException('You have requested a synthetic service ("kernel"). The DIC does not know how to construct this service.');
    }

    /*
     * Gets the 'kernel.class_cache.cache_warmer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\CacheWarmer\ClassCacheCacheWarmer A Symfony\Bundle\FrameworkBundle\CacheWarmer\ClassCacheCacheWarmer instance
     */
    protected function getKernel_ClassCache_CacheWarmerService()
    {
        return $this->services['kernel.class_cache.cache_warmer'] = new \Symfony\Bundle\FrameworkBundle\CacheWarmer\ClassCacheCacheWarmer();
    }

    /*
     * Gets the 'locale_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\LocaleListener A Symfony\Component\HttpKernel\EventListener\LocaleListener instance
     */
    protected function getLocaleListenerService()
    {
        return $this->services['locale_listener'] = new \Symfony\Component\HttpKernel\EventListener\LocaleListener($this->get('request_stack'), 'en', $this->get('router', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /*
     * Gets the 'logger' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance
     */
    protected function getLoggerService()
    {
        $this->services['logger'] = $instance = new \Symfony\Bridge\Monolog\Logger('app');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /*
     * Gets the 'markdown.parser' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Knp\Bundle\MarkdownBundle\Parser\Preset\Max A Knp\Bundle\MarkdownBundle\Parser\Preset\Max instance
     */
    protected function getMarkdown_ParserService()
    {
        return $this->services['markdown.parser'] = new \Knp\Bundle\MarkdownBundle\Parser\Preset\Max();
    }

    /*
     * Gets the 'monolog.handler.check_balance' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_CheckBalanceService()
    {
        return $this->services['monolog.handler.check_balance'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/check_balance.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.check_card_deposit_tracking' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_CheckCardDepositTrackingService()
    {
        return $this->services['monolog.handler.check_card_deposit_tracking'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/check_card_deposit_tracking.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.check_deposit_tracking' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_CheckDepositTrackingService()
    {
        return $this->services['monolog.handler.check_deposit_tracking'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/check_deposit_tracking.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.check_redis_balance' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_CheckRedisBalanceService()
    {
        return $this->services['monolog.handler.check_redis_balance'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/check_redis_balance.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.copy_user_crossdomain' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_CopyUserCrossdomainService()
    {
        return $this->services['monolog.handler.copy_user_crossdomain'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/copy_user_crossDomain.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.create_reward_entry' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_CreateRewardEntryService()
    {
        return $this->services['monolog.handler.create_reward_entry'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/create_reward_entry.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.execute_rm_plan' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_ExecuteRmPlanService()
    {
        return $this->services['monolog.handler.execute_rm_plan'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/execute_rm_plan.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.generate_rm_plan' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_GenerateRmPlanService()
    {
        return $this->services['monolog.handler.generate_rm_plan'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/generate_rm_plan.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.generate_rm_plan_user' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_GenerateRmPlanUserService()
    {
        return $this->services['monolog.handler.generate_rm_plan_user'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/generate_rm_plan_user.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.main' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\FingersCrossedHandler A Monolog\Handler\FingersCrossedHandler instance
     */
    protected function getMonolog_Handler_MainService()
    {
        return $this->services['monolog.handler.main'] = new \Monolog\Handler\FingersCrossedHandler($this->get('monolog.handler.nested'), 400, 100, true, true);
    }

    /*
     * Gets the 'monolog.handler.message_to_italking' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_MessageToItalkingService()
    {
        return $this->services['monolog.handler.message_to_italking'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/message_to_italking.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.monitor_queue_length' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_MonitorQueueLengthService()
    {
        return $this->services['monolog.handler.monitor_queue_length'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/monitor_queue_length.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.monitor_stat' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_MonitorStatService()
    {
        return $this->services['monolog.handler.monitor_stat'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/monitor_stat.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.nested' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_NestedService()
    {
        return $this->services['monolog.handler.nested'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.op_obtain_reward' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_OpObtainRewardService()
    {
        return $this->services['monolog.handler.op_obtain_reward'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/op_obtain_reward.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.payment' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_PaymentService()
    {
        return $this->services['monolog.handler.payment'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/payment.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.pop_failed_message' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_PopFailedMessageService()
    {
        return $this->services['monolog.handler.pop_failed_message'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/pop_failed_message.log'), 200, true);
    }

    /*
     * Gets the 'monolog.handler.regular_login' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_RegularLoginService()
    {
        return $this->services['monolog.handler.regular_login'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/regular_login.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.remit_auto_confirm' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_RemitAutoConfirmService()
    {
        return $this->services['monolog.handler.remit_auto_confirm'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/remit_auto_confirm.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.remove_ipl_overdue_user' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_RemoveIplOverdueUserService()
    {
        return $this->services['monolog.handler.remove_ipl_overdue_user'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/remove_ipl_overdue_user.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.remove_overdue_user' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_RemoveOverdueUserService()
    {
        return $this->services['monolog.handler.remove_overdue_user'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/remove_overdue_user.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.send_message' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SendMessageService()
    {
        return $this->services['monolog.handler.send_message'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/send_message.log'), 200, true);
    }

    /*
     * Gets the 'monolog.handler.send_message_http_detail' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SendMessageHttpDetailService()
    {
        return $this->services['monolog.handler.send_message_http_detail'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/send_message_http_detail.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_cash_fake_balance' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncCashFakeBalanceService()
    {
        return $this->services['monolog.handler.sync_cash_fake_balance'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_cash_fake_balance.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_cash_fake_entry' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncCashFakeEntryService()
    {
        return $this->services['monolog.handler.sync_cash_fake_entry'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_cash_fake_entry.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_cash_fake_entry_queue' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncCashFakeEntryQueueService()
    {
        return $this->services['monolog.handler.sync_cash_fake_entry_queue'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_cash_fake_entry_queue.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_cash_fake_history' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncCashFakeHistoryService()
    {
        return $this->services['monolog.handler.sync_cash_fake_history'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_cash_fake_history.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_cash_fake_queue' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncCashFakeQueueService()
    {
        return $this->services['monolog.handler.sync_cash_fake_queue'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_cash_fake_queue.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_credit' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncCreditService()
    {
        return $this->services['monolog.handler.sync_credit'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_credit.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_credit_entry' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncCreditEntryService()
    {
        return $this->services['monolog.handler.sync_credit_entry'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_credit_entry.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_login_log' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncLoginLogService()
    {
        return $this->services['monolog.handler.sync_login_log'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_login_log.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_login_log_mobile' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncLoginLogMobileService()
    {
        return $this->services['monolog.handler.sync_login_log_mobile'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_login_log_mobile.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_obtain_reward' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncObtainRewardService()
    {
        return $this->services['monolog.handler.sync_obtain_reward'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_obtain_reward.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_rm_plan_user' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncRmPlanUserService()
    {
        return $this->services['monolog.handler.sync_rm_plan_user'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_rm_plan_user.log'), 100, true);
    }

    /*
     * Gets the 'monolog.handler.sync_user_deposit_withdraw' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance
     */
    protected function getMonolog_Handler_SyncUserDepositWithdrawService()
    {
        return $this->services['monolog.handler.sync_user_deposit_withdraw'] = new \Monolog\Handler\StreamHandler(($this->targetDirs[2].'/logs/prod/sync_user_deposit_withdraw.log'), 100, true);
    }

    /*
     * Gets the 'monolog.logger.doctrine' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance
     */
    protected function getMonolog_Logger_DoctrineService()
    {
        $this->services['monolog.logger.doctrine'] = $instance = new \Symfony\Bridge\Monolog\Logger('doctrine');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /*
     * Gets the 'monolog.logger.msg' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance
     */
    protected function getMonolog_Logger_MsgService()
    {
        $this->services['monolog.logger.msg'] = $instance = new \Symfony\Bridge\Monolog\Logger('msg');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /*
     * Gets the 'monolog.logger.php' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance
     */
    protected function getMonolog_Logger_PhpService()
    {
        $this->services['monolog.logger.php'] = $instance = new \Symfony\Bridge\Monolog\Logger('php');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /*
     * Gets the 'monolog.logger.request' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance
     */
    protected function getMonolog_Logger_RequestService()
    {
        $this->services['monolog.logger.request'] = $instance = new \Symfony\Bridge\Monolog\Logger('request');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /*
     * Gets the 'monolog.logger.router' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance
     */
    protected function getMonolog_Logger_RouterService()
    {
        $this->services['monolog.logger.router'] = $instance = new \Symfony\Bridge\Monolog\Logger('router');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /*
     * Gets the 'monolog.logger.snc_redis' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance
     */
    protected function getMonolog_Logger_SncRedisService()
    {
        $this->services['monolog.logger.snc_redis'] = $instance = new \Symfony\Bridge\Monolog\Logger('snc_redis');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /*
     * Gets the 'monolog.logger.translation' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance
     */
    protected function getMonolog_Logger_TranslationService()
    {
        $this->services['monolog.logger.translation'] = $instance = new \Symfony\Bridge\Monolog\Logger('translation');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /*
     * Gets the 'property_accessor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\PropertyAccess\PropertyAccessor A Symfony\Component\PropertyAccess\PropertyAccessor instance
     */
    protected function getPropertyAccessorService()
    {
        return $this->services['property_accessor'] = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, false);
    }

    /*
     * Gets the 'request' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @throws RuntimeException always since this service is expected to be injected dynamically
     * @throws InactiveScopeException when the 'request' service is requested while the 'request' scope is not active
     * @deprecated The "request" service is deprecated since Symfony 2.7 and will be removed in 3.0. Use the "request_stack" service instead.
     */
    protected function getRequestService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('request', 'request');
        }

        throw new RuntimeException('You have requested a synthetic service ("request"). The DIC does not know how to construct this service.');
    }

    /*
     * Gets the 'request_stack' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpFoundation\RequestStack A Symfony\Component\HttpFoundation\RequestStack instance
     */
    protected function getRequestStackService()
    {
        return $this->services['request_stack'] = new \Symfony\Component\HttpFoundation\RequestStack();
    }

    /*
     * Gets the 'response_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\ResponseListener A Symfony\Component\HttpKernel\EventListener\ResponseListener instance
     */
    protected function getResponseListenerService()
    {
        return $this->services['response_listener'] = new \Symfony\Component\HttpKernel\EventListener\ResponseListener('UTF-8');
    }

    /*
     * Gets the 'router' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router A Symfony\Bundle\FrameworkBundle\Routing\Router instance
     */
    protected function getRouterService()
    {
        $this->services['router'] = $instance = new \Symfony\Bundle\FrameworkBundle\Routing\Router($this, ($this->targetDirs[2].'/config/routing.yml'), array('cache_dir' => __DIR__, 'debug' => false, 'generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator', 'generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator', 'generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper', 'generator_cache_class' => 'appProdProjectContainerUrlGenerator', 'matcher_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher', 'matcher_base_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher', 'matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper', 'matcher_cache_class' => 'appProdProjectContainerUrlMatcher', 'strict_requirements' => NULL), $this->get('router.request_context', ContainerInterface::NULL_ON_INVALID_REFERENCE), $this->get('monolog.logger.router', ContainerInterface::NULL_ON_INVALID_REFERENCE));

        $instance->setConfigCacheFactory($this->get('config_cache_factory'));

        return $instance;
    }

    /*
     * Gets the 'router_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\RouterListener A Symfony\Component\HttpKernel\EventListener\RouterListener instance
     */
    protected function getRouterListenerService()
    {
        return $this->services['router_listener'] = new \Symfony\Component\HttpKernel\EventListener\RouterListener($this->get('router'), $this->get('request_stack'), $this->get('router.request_context', ContainerInterface::NULL_ON_INVALID_REFERENCE), $this->get('monolog.logger.request', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /*
     * Gets the 'routing.loader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader A Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader instance
     */
    protected function getRouting_LoaderService()
    {
        $a = $this->get('file_locator');
        $b = $this->get('annotation_reader');

        $c = new \Sensio\Bundle\FrameworkExtraBundle\Routing\AnnotatedRouteControllerLoader($b);

        $d = new \Symfony\Component\Config\Loader\LoaderResolver();
        $d->addLoader(new \Symfony\Component\Routing\Loader\XmlFileLoader($a));
        $d->addLoader(new \Symfony\Component\Routing\Loader\YamlFileLoader($a));
        $d->addLoader(new \Symfony\Component\Routing\Loader\PhpFileLoader($a));
        $d->addLoader(new \Symfony\Component\Routing\Loader\DirectoryLoader($a));
        $d->addLoader(new \Symfony\Component\Routing\Loader\DependencyInjection\ServiceRouterLoader($this));
        $d->addLoader(new \Symfony\Component\Routing\Loader\AnnotationDirectoryLoader($a, $c));
        $d->addLoader(new \Symfony\Component\Routing\Loader\AnnotationFileLoader($a, $c));
        $d->addLoader($c);

        return $this->services['routing.loader'] = new \Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader($this->get('controller_name_converter'), $d);
    }

    /*
     * Gets the 'security.csrf.token_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Security\Csrf\CsrfTokenManager A Symfony\Component\Security\Csrf\CsrfTokenManager instance
     */
    protected function getSecurity_Csrf_TokenManagerService()
    {
        return $this->services['security.csrf.token_manager'] = new \Symfony\Component\Security\Csrf\CsrfTokenManager(new \Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator(), new \Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage($this->get('session')));
    }

    /*
     * Gets the 'security.secure_random' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Security\Core\Util\SecureRandom A Symfony\Component\Security\Core\Util\SecureRandom instance
     *
     * @deprecated The "security.secure_random" service is deprecated since Symfony 2.8 and will be removed in 3.0. Use the random_bytes() function instead.
     */
    protected function getSecurity_SecureRandomService()
    {
        @trigger_error('The "security.secure_random" service is deprecated since Symfony 2.8 and will be removed in 3.0. Use the random_bytes() function instead.', E_USER_DEPRECATED);

        return $this->services['security.secure_random'] = new \Symfony\Component\Security\Core\Util\SecureRandom();
    }

    /*
     * Gets the 'sensio_framework_extra.cache.listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\EventListener\HttpCacheListener A Sensio\Bundle\FrameworkExtraBundle\EventListener\HttpCacheListener instance
     */
    protected function getSensioFrameworkExtra_Cache_ListenerService()
    {
        return $this->services['sensio_framework_extra.cache.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\HttpCacheListener();
    }

    /*
     * Gets the 'sensio_framework_extra.controller.listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\EventListener\ControllerListener A Sensio\Bundle\FrameworkExtraBundle\EventListener\ControllerListener instance
     */
    protected function getSensioFrameworkExtra_Controller_ListenerService()
    {
        return $this->services['sensio_framework_extra.controller.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\ControllerListener($this->get('annotation_reader'));
    }

    /*
     * Gets the 'sensio_framework_extra.converter.datetime' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DateTimeParamConverter A Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DateTimeParamConverter instance
     */
    protected function getSensioFrameworkExtra_Converter_DatetimeService()
    {
        return $this->services['sensio_framework_extra.converter.datetime'] = new \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DateTimeParamConverter();
    }

    /*
     * Gets the 'sensio_framework_extra.converter.doctrine.orm' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter A Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter instance
     */
    protected function getSensioFrameworkExtra_Converter_Doctrine_OrmService()
    {
        return $this->services['sensio_framework_extra.converter.doctrine.orm'] = new \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter($this->get('doctrine', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /*
     * Gets the 'sensio_framework_extra.converter.listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\EventListener\ParamConverterListener A Sensio\Bundle\FrameworkExtraBundle\EventListener\ParamConverterListener instance
     */
    protected function getSensioFrameworkExtra_Converter_ListenerService()
    {
        return $this->services['sensio_framework_extra.converter.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\ParamConverterListener($this->get('sensio_framework_extra.converter.manager'), true);
    }

    /*
     * Gets the 'sensio_framework_extra.converter.manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterManager A Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterManager instance
     */
    protected function getSensioFrameworkExtra_Converter_ManagerService()
    {
        $this->services['sensio_framework_extra.converter.manager'] = $instance = new \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterManager();

        $instance->add($this->get('sensio_framework_extra.converter.doctrine.orm'), 0, 'doctrine.orm');
        $instance->add($this->get('sensio_framework_extra.converter.datetime'), 0, 'datetime');

        return $instance;
    }

    /*
     * Gets the 'sensio_framework_extra.security.listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\EventListener\SecurityListener A Sensio\Bundle\FrameworkExtraBundle\EventListener\SecurityListener instance
     */
    protected function getSensioFrameworkExtra_Security_ListenerService()
    {
        return $this->services['sensio_framework_extra.security.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\SecurityListener(NULL, new \Sensio\Bundle\FrameworkExtraBundle\Security\ExpressionLanguage(), NULL, NULL, NULL, NULL);
    }

    /*
     * Gets the 'sensio_framework_extra.view.guesser' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\Templating\TemplateGuesser A Sensio\Bundle\FrameworkExtraBundle\Templating\TemplateGuesser instance
     */
    protected function getSensioFrameworkExtra_View_GuesserService()
    {
        return $this->services['sensio_framework_extra.view.guesser'] = new \Sensio\Bundle\FrameworkExtraBundle\Templating\TemplateGuesser($this->get('kernel'));
    }

    /*
     * Gets the 'sensio_framework_extra.view.listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Sensio\Bundle\FrameworkExtraBundle\EventListener\TemplateListener A Sensio\Bundle\FrameworkExtraBundle\EventListener\TemplateListener instance
     */
    protected function getSensioFrameworkExtra_View_ListenerService()
    {
        return $this->services['sensio_framework_extra.view.listener'] = new \Sensio\Bundle\FrameworkExtraBundle\EventListener\TemplateListener($this);
    }

    /*
     * Gets the 'service_container' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @throws RuntimeException always since this service is expected to be injected dynamically
     */
    protected function getServiceContainerService()
    {
        throw new RuntimeException('You have requested a synthetic service ("service_container"). The DIC does not know how to construct this service.');
    }

    /*
     * Gets the 'session' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session A Symfony\Component\HttpFoundation\Session\Session instance
     */
    protected function getSessionService()
    {
        return $this->services['session'] = new \Symfony\Component\HttpFoundation\Session\Session($this->get('session.storage.native'), new \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag(), new \Symfony\Component\HttpFoundation\Session\Flash\FlashBag());
    }

    /*
     * Gets the 'session.handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler A Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler instance
     */
    protected function getSession_HandlerService()
    {
        return $this->services['session.handler'] = new \Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler((__DIR__.'/sessions'));
    }

    /*
     * Gets the 'session.save_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\SaveSessionListener A Symfony\Component\HttpKernel\EventListener\SaveSessionListener instance
     */
    protected function getSession_SaveListenerService()
    {
        return $this->services['session.save_listener'] = new \Symfony\Component\HttpKernel\EventListener\SaveSessionListener();
    }

    /*
     * Gets the 'session.storage.filesystem' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage A Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage instance
     */
    protected function getSession_Storage_FilesystemService()
    {
        return $this->services['session.storage.filesystem'] = new \Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage((__DIR__.'/sessions'), 'MOCKSESSID', $this->get('session.storage.metadata_bag'));
    }

    /*
     * Gets the 'session.storage.native' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage A Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage instance
     */
    protected function getSession_Storage_NativeService()
    {
        return $this->services['session.storage.native'] = new \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage(array('cookie_httponly' => true, 'gc_probability' => 1), $this->get('session.handler'), $this->get('session.storage.metadata_bag'));
    }

    /*
     * Gets the 'session.storage.php_bridge' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage A Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage instance
     */
    protected function getSession_Storage_PhpBridgeService()
    {
        return $this->services['session.storage.php_bridge'] = new \Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage($this->get('session.handler'), $this->get('session.storage.metadata_bag'));
    }

    /*
     * Gets the 'session_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\EventListener\SessionListener A Symfony\Bundle\FrameworkBundle\EventListener\SessionListener instance
     */
    protected function getSessionListenerService()
    {
        return $this->services['session_listener'] = new \Symfony\Bundle\FrameworkBundle\EventListener\SessionListener($this);
    }

    /*
     * Gets the 'snc_redis.bodog' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_BodogService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.bodog_processor'));

        return $this->services['snc_redis.bodog'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'bodog', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 3, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.client.bodog_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_BodogProcessorService()
    {
        return $this->services['snc_redis.client.bodog_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.cluster_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_ClusterProcessorService()
    {
        return $this->services['snc_redis.client.cluster_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.default_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_DefaultProcessorService()
    {
        return $this->services['snc_redis.client.default_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('durian_bb_');
    }

    /*
     * Gets the 'snc_redis.client.external_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_ExternalProcessorService()
    {
        return $this->services['snc_redis.client.external_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.ip_blocker_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_IpBlockerProcessorService()
    {
        return $this->services['snc_redis.client.ip_blocker_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.kue_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_KueProcessorService()
    {
        return $this->services['snc_redis.client.kue_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony:');
    }

    /*
     * Gets the 'snc_redis.client.map_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_MapProcessorService()
    {
        return $this->services['snc_redis.client.map_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.oauth2_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_Oauth2ProcessorService()
    {
        return $this->services['snc_redis.client.oauth2_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony:oauth2:');
    }

    /*
     * Gets the 'snc_redis.client.reward_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_RewardProcessorService()
    {
        return $this->services['snc_redis.client.reward_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.sequence_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_SequenceProcessorService()
    {
        return $this->services['snc_redis.client.sequence_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.slide_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_SlideProcessorService()
    {
        return $this->services['snc_redis.client.slide_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.suncity_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_SuncityProcessorService()
    {
        return $this->services['snc_redis.client.suncity_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.total_balance_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_TotalBalanceProcessorService()
    {
        return $this->services['snc_redis.client.total_balance_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('symfony_');
    }

    /*
     * Gets the 'snc_redis.client.wallet1_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_Wallet1ProcessorService()
    {
        return $this->services['snc_redis.client.wallet1_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('durian_bb_');
    }

    /*
     * Gets the 'snc_redis.client.wallet2_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_Wallet2ProcessorService()
    {
        return $this->services['snc_redis.client.wallet2_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('durian_bb_');
    }

    /*
     * Gets the 'snc_redis.client.wallet3_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_Wallet3ProcessorService()
    {
        return $this->services['snc_redis.client.wallet3_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('durian_bb_');
    }

    /*
     * Gets the 'snc_redis.client.wallet4_processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Command\Processor\KeyPrefixProcessor A Predis\Command\Processor\KeyPrefixProcessor instance
     */
    protected function getSncRedis_Client_Wallet4ProcessorService()
    {
        return $this->services['snc_redis.client.wallet4_processor'] = new \Predis\Command\Processor\KeyPrefixProcessor('durian_bb_');
    }

    /*
     * Gets the 'snc_redis.cluster' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_ClusterService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.cluster_processor'));

        return $this->services['snc_redis.cluster'] = new \Predis\Client(array(0 => new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'cluster1', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 2, 'password' => NULL, 'weight' => NULL)), 1 => new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'cluster2', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 3, 'password' => NULL, 'weight' => NULL))), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.default' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_DefaultService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.default_processor'));

        return $this->services['snc_redis.default'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'durian_bb_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'default', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'durian_bb_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.external' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_ExternalService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.external_processor'));

        return $this->services['snc_redis.external'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'external', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 4, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.ip_blocker' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_IpBlockerService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.ip_blocker_processor'));

        return $this->services['snc_redis.ip_blocker'] = new \Predis\Client(array(0 => new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'cluster' => 'redis', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'ip_blocker1', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 7, 'password' => NULL, 'weight' => NULL)), 1 => new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'cluster' => 'redis', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'ip_blocker2', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 8, 'password' => NULL, 'weight' => NULL)), 2 => new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'cluster' => 'redis', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'ip_blocker3', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 9, 'password' => NULL, 'weight' => NULL))), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'cluster' => 'redis', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.kue' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_KueService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.kue_processor'));

        return $this->services['snc_redis.kue'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony:', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'kue', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 12, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony:', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.logger' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Snc\RedisBundle\Logger\RedisLogger A Snc\RedisBundle\Logger\RedisLogger instance
     */
    protected function getSncRedis_LoggerService()
    {
        return $this->services['snc_redis.logger'] = new \Snc\RedisBundle\Logger\RedisLogger($this->get('monolog.logger.snc_redis', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /*
     * Gets the 'snc_redis.map' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_MapService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.map_processor'));

        return $this->services['snc_redis.map'] = new \Predis\Client(array(0 => new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'cluster' => 'redis', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'map1', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 7, 'password' => NULL, 'weight' => NULL)), 1 => new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'cluster' => 'redis', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'map2', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 8, 'password' => NULL, 'weight' => NULL)), 2 => new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'cluster' => 'redis', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'map3', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 9, 'password' => NULL, 'weight' => NULL))), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'cluster' => 'redis', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.oauth2' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_Oauth2Service()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.oauth2_processor'));

        return $this->services['snc_redis.oauth2'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony:oauth2:', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'oauth2', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 2, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony:oauth2:', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.reward' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_RewardService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.reward_processor'));

        return $this->services['snc_redis.reward'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'reward', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 6, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.sequence' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_SequenceService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.sequence_processor'));

        return $this->services['snc_redis.sequence'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'sequence', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 4, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.slide' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_SlideService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.slide_processor'));

        return $this->services['snc_redis.slide'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'slide', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 14, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.suncity' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_SuncityService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.suncity_processor'));

        return $this->services['snc_redis.suncity'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'suncity', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 6, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.total_balance' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_TotalBalanceService()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.total_balance_processor'));

        return $this->services['snc_redis.total_balance'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'symfony_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'total_balance', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 5, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'symfony_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.wallet1' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_Wallet1Service()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.wallet1_processor'));

        return $this->services['snc_redis.wallet1'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'durian_bb_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'wallet1', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 10, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'durian_bb_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.wallet2' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_Wallet2Service()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.wallet2_processor'));

        return $this->services['snc_redis.wallet2'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'durian_bb_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'wallet2', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 11, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'durian_bb_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.wallet3' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_Wallet3Service()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.wallet3_processor'));

        return $this->services['snc_redis.wallet3'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'durian_bb_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'wallet3', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 12, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'durian_bb_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'snc_redis.wallet4' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Predis\Client A Predis\Client instance
     */
    protected function getSncRedis_Wallet4Service()
    {
        $a = new \Predis\Profile\ServerVersion28();
        $a->setProcessor($this->get('snc_redis.client.wallet4_processor'));

        return $this->services['snc_redis.wallet4'] = new \Predis\Client(new \Predis\Connection\ConnectionParameters(array('prefix' => 'durian_bb_', 'profile' => '2.8', 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true, 'logging' => false, 'alias' => 'wallet4', 'scheme' => 'tcp', 'host' => 'redis', 'port' => 6379, 'database' => 13, 'password' => NULL, 'weight' => NULL)), new \Predis\Option\ClientOptions(array('prefix' => 'durian_bb_', 'profile' => $a, 'read_write_timeout' => NULL, 'iterable_multibulk' => false, 'replication' => false, 'async_connect' => false, 'timeout' => 5, 'persistent' => false, 'exceptions' => true)));
    }

    /*
     * Gets the 'streamed_response_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\StreamedResponseListener A Symfony\Component\HttpKernel\EventListener\StreamedResponseListener instance
     */
    protected function getStreamedResponseListenerService()
    {
        return $this->services['streamed_response_listener'] = new \Symfony\Component\HttpKernel\EventListener\StreamedResponseListener();
    }

    /*
     * Gets the 'templating' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\TwigBundle\TwigEngine A Symfony\Bundle\TwigBundle\TwigEngine instance
     */
    protected function getTemplatingService()
    {
        return $this->services['templating'] = new \Symfony\Bundle\TwigBundle\TwigEngine($this->get('twig'), $this->get('templating.name_parser'), $this->get('templating.locator'));
    }

    /*
     * Gets the 'templating.filename_parser' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Templating\TemplateFilenameParser A Symfony\Bundle\FrameworkBundle\Templating\TemplateFilenameParser instance
     */
    protected function getTemplating_FilenameParserService()
    {
        return $this->services['templating.filename_parser'] = new \Symfony\Bundle\FrameworkBundle\Templating\TemplateFilenameParser();
    }

    /*
     * Gets the 'templating.helper.assets' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper instance
     */
    protected function getTemplating_Helper_AssetsService()
    {
        return $this->services['templating.helper.assets'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper($this->get('assets.packages'), array());
    }

    /*
     * Gets the 'templating.helper.markdown' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Knp\Bundle\MarkdownBundle\Helper\MarkdownHelper A Knp\Bundle\MarkdownBundle\Helper\MarkdownHelper instance
     */
    protected function getTemplating_Helper_MarkdownService()
    {
        return $this->services['templating.helper.markdown'] = new \Knp\Bundle\MarkdownBundle\Helper\MarkdownHelper($this->get('markdown.parser.parser_manager'));
    }

    /*
     * Gets the 'templating.helper.router' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper instance
     */
    protected function getTemplating_Helper_RouterService()
    {
        return $this->services['templating.helper.router'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper($this->get('router'));
    }

    /*
     * Gets the 'templating.loader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Templating\Loader\FilesystemLoader A Symfony\Bundle\FrameworkBundle\Templating\Loader\FilesystemLoader instance
     */
    protected function getTemplating_LoaderService()
    {
        return $this->services['templating.loader'] = new \Symfony\Bundle\FrameworkBundle\Templating\Loader\FilesystemLoader($this->get('templating.locator'));
    }

    /*
     * Gets the 'templating.name_parser' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser A Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser instance
     */
    protected function getTemplating_NameParserService()
    {
        return $this->services['templating.name_parser'] = new \Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser($this->get('kernel'));
    }

    /*
     * Gets the 'translation.dumper.csv' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\CsvFileDumper A Symfony\Component\Translation\Dumper\CsvFileDumper instance
     */
    protected function getTranslation_Dumper_CsvService()
    {
        return $this->services['translation.dumper.csv'] = new \Symfony\Component\Translation\Dumper\CsvFileDumper();
    }

    /*
     * Gets the 'translation.dumper.ini' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\IniFileDumper A Symfony\Component\Translation\Dumper\IniFileDumper instance
     */
    protected function getTranslation_Dumper_IniService()
    {
        return $this->services['translation.dumper.ini'] = new \Symfony\Component\Translation\Dumper\IniFileDumper();
    }

    /*
     * Gets the 'translation.dumper.json' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\JsonFileDumper A Symfony\Component\Translation\Dumper\JsonFileDumper instance
     */
    protected function getTranslation_Dumper_JsonService()
    {
        return $this->services['translation.dumper.json'] = new \Symfony\Component\Translation\Dumper\JsonFileDumper();
    }

    /*
     * Gets the 'translation.dumper.mo' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\MoFileDumper A Symfony\Component\Translation\Dumper\MoFileDumper instance
     */
    protected function getTranslation_Dumper_MoService()
    {
        return $this->services['translation.dumper.mo'] = new \Symfony\Component\Translation\Dumper\MoFileDumper();
    }

    /*
     * Gets the 'translation.dumper.php' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\PhpFileDumper A Symfony\Component\Translation\Dumper\PhpFileDumper instance
     */
    protected function getTranslation_Dumper_PhpService()
    {
        return $this->services['translation.dumper.php'] = new \Symfony\Component\Translation\Dumper\PhpFileDumper();
    }

    /*
     * Gets the 'translation.dumper.po' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\PoFileDumper A Symfony\Component\Translation\Dumper\PoFileDumper instance
     */
    protected function getTranslation_Dumper_PoService()
    {
        return $this->services['translation.dumper.po'] = new \Symfony\Component\Translation\Dumper\PoFileDumper();
    }

    /*
     * Gets the 'translation.dumper.qt' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\QtFileDumper A Symfony\Component\Translation\Dumper\QtFileDumper instance
     */
    protected function getTranslation_Dumper_QtService()
    {
        return $this->services['translation.dumper.qt'] = new \Symfony\Component\Translation\Dumper\QtFileDumper();
    }

    /*
     * Gets the 'translation.dumper.res' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\IcuResFileDumper A Symfony\Component\Translation\Dumper\IcuResFileDumper instance
     */
    protected function getTranslation_Dumper_ResService()
    {
        return $this->services['translation.dumper.res'] = new \Symfony\Component\Translation\Dumper\IcuResFileDumper();
    }

    /*
     * Gets the 'translation.dumper.xliff' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\XliffFileDumper A Symfony\Component\Translation\Dumper\XliffFileDumper instance
     */
    protected function getTranslation_Dumper_XliffService()
    {
        return $this->services['translation.dumper.xliff'] = new \Symfony\Component\Translation\Dumper\XliffFileDumper();
    }

    /*
     * Gets the 'translation.dumper.yml' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Dumper\YamlFileDumper A Symfony\Component\Translation\Dumper\YamlFileDumper instance
     */
    protected function getTranslation_Dumper_YmlService()
    {
        return $this->services['translation.dumper.yml'] = new \Symfony\Component\Translation\Dumper\YamlFileDumper();
    }

    /*
     * Gets the 'translation.extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Extractor\ChainExtractor A Symfony\Component\Translation\Extractor\ChainExtractor instance
     */
    protected function getTranslation_ExtractorService()
    {
        $this->services['translation.extractor'] = $instance = new \Symfony\Component\Translation\Extractor\ChainExtractor();

        $instance->addExtractor('php', $this->get('translation.extractor.php'));
        $instance->addExtractor('twig', $this->get('twig.translation.extractor'));

        return $instance;
    }

    /*
     * Gets the 'translation.extractor.php' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Translation\PhpExtractor A Symfony\Bundle\FrameworkBundle\Translation\PhpExtractor instance
     */
    protected function getTranslation_Extractor_PhpService()
    {
        return $this->services['translation.extractor.php'] = new \Symfony\Bundle\FrameworkBundle\Translation\PhpExtractor();
    }

    /*
     * Gets the 'translation.loader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader A Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader instance
     */
    protected function getTranslation_LoaderService()
    {
        $a = $this->get('translation.loader.xliff');

        $this->services['translation.loader'] = $instance = new \Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader();

        $instance->addLoader('php', $this->get('translation.loader.php'));
        $instance->addLoader('yml', $this->get('translation.loader.yml'));
        $instance->addLoader('xlf', $a);
        $instance->addLoader('xliff', $a);
        $instance->addLoader('po', $this->get('translation.loader.po'));
        $instance->addLoader('mo', $this->get('translation.loader.mo'));
        $instance->addLoader('ts', $this->get('translation.loader.qt'));
        $instance->addLoader('csv', $this->get('translation.loader.csv'));
        $instance->addLoader('res', $this->get('translation.loader.res'));
        $instance->addLoader('dat', $this->get('translation.loader.dat'));
        $instance->addLoader('ini', $this->get('translation.loader.ini'));
        $instance->addLoader('json', $this->get('translation.loader.json'));

        return $instance;
    }

    /*
     * Gets the 'translation.loader.csv' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\CsvFileLoader A Symfony\Component\Translation\Loader\CsvFileLoader instance
     */
    protected function getTranslation_Loader_CsvService()
    {
        return $this->services['translation.loader.csv'] = new \Symfony\Component\Translation\Loader\CsvFileLoader();
    }

    /*
     * Gets the 'translation.loader.dat' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\IcuDatFileLoader A Symfony\Component\Translation\Loader\IcuDatFileLoader instance
     */
    protected function getTranslation_Loader_DatService()
    {
        return $this->services['translation.loader.dat'] = new \Symfony\Component\Translation\Loader\IcuDatFileLoader();
    }

    /*
     * Gets the 'translation.loader.ini' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\IniFileLoader A Symfony\Component\Translation\Loader\IniFileLoader instance
     */
    protected function getTranslation_Loader_IniService()
    {
        return $this->services['translation.loader.ini'] = new \Symfony\Component\Translation\Loader\IniFileLoader();
    }

    /*
     * Gets the 'translation.loader.json' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\JsonFileLoader A Symfony\Component\Translation\Loader\JsonFileLoader instance
     */
    protected function getTranslation_Loader_JsonService()
    {
        return $this->services['translation.loader.json'] = new \Symfony\Component\Translation\Loader\JsonFileLoader();
    }

    /*
     * Gets the 'translation.loader.mo' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\MoFileLoader A Symfony\Component\Translation\Loader\MoFileLoader instance
     */
    protected function getTranslation_Loader_MoService()
    {
        return $this->services['translation.loader.mo'] = new \Symfony\Component\Translation\Loader\MoFileLoader();
    }

    /*
     * Gets the 'translation.loader.php' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\PhpFileLoader A Symfony\Component\Translation\Loader\PhpFileLoader instance
     */
    protected function getTranslation_Loader_PhpService()
    {
        return $this->services['translation.loader.php'] = new \Symfony\Component\Translation\Loader\PhpFileLoader();
    }

    /*
     * Gets the 'translation.loader.po' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\PoFileLoader A Symfony\Component\Translation\Loader\PoFileLoader instance
     */
    protected function getTranslation_Loader_PoService()
    {
        return $this->services['translation.loader.po'] = new \Symfony\Component\Translation\Loader\PoFileLoader();
    }

    /*
     * Gets the 'translation.loader.qt' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\QtFileLoader A Symfony\Component\Translation\Loader\QtFileLoader instance
     */
    protected function getTranslation_Loader_QtService()
    {
        return $this->services['translation.loader.qt'] = new \Symfony\Component\Translation\Loader\QtFileLoader();
    }

    /*
     * Gets the 'translation.loader.res' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\IcuResFileLoader A Symfony\Component\Translation\Loader\IcuResFileLoader instance
     */
    protected function getTranslation_Loader_ResService()
    {
        return $this->services['translation.loader.res'] = new \Symfony\Component\Translation\Loader\IcuResFileLoader();
    }

    /*
     * Gets the 'translation.loader.xliff' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\XliffFileLoader A Symfony\Component\Translation\Loader\XliffFileLoader instance
     */
    protected function getTranslation_Loader_XliffService()
    {
        return $this->services['translation.loader.xliff'] = new \Symfony\Component\Translation\Loader\XliffFileLoader();
    }

    /*
     * Gets the 'translation.loader.yml' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Loader\YamlFileLoader A Symfony\Component\Translation\Loader\YamlFileLoader instance
     */
    protected function getTranslation_Loader_YmlService()
    {
        return $this->services['translation.loader.yml'] = new \Symfony\Component\Translation\Loader\YamlFileLoader();
    }

    /*
     * Gets the 'translation.writer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Translation\Writer\TranslationWriter A Symfony\Component\Translation\Writer\TranslationWriter instance
     */
    protected function getTranslation_WriterService()
    {
        $this->services['translation.writer'] = $instance = new \Symfony\Component\Translation\Writer\TranslationWriter();

        $instance->addDumper('php', $this->get('translation.dumper.php'));
        $instance->addDumper('xlf', $this->get('translation.dumper.xliff'));
        $instance->addDumper('po', $this->get('translation.dumper.po'));
        $instance->addDumper('mo', $this->get('translation.dumper.mo'));
        $instance->addDumper('yml', $this->get('translation.dumper.yml'));
        $instance->addDumper('ts', $this->get('translation.dumper.qt'));
        $instance->addDumper('csv', $this->get('translation.dumper.csv'));
        $instance->addDumper('ini', $this->get('translation.dumper.ini'));
        $instance->addDumper('json', $this->get('translation.dumper.json'));
        $instance->addDumper('res', $this->get('translation.dumper.res'));

        return $instance;
    }

    /*
     * Gets the 'translator.default' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Translation\Translator A Symfony\Bundle\FrameworkBundle\Translation\Translator instance
     */
    protected function getTranslator_DefaultService()
    {
        $this->services['translator.default'] = $instance = new \Symfony\Bundle\FrameworkBundle\Translation\Translator($this, new \Symfony\Component\Translation\MessageSelector(), array('translation.loader.php' => array(0 => 'php'), 'translation.loader.yml' => array(0 => 'yml'), 'translation.loader.xliff' => array(0 => 'xlf', 1 => 'xliff'), 'translation.loader.po' => array(0 => 'po'), 'translation.loader.mo' => array(0 => 'mo'), 'translation.loader.qt' => array(0 => 'ts'), 'translation.loader.csv' => array(0 => 'csv'), 'translation.loader.res' => array(0 => 'res'), 'translation.loader.dat' => array(0 => 'dat'), 'translation.loader.ini' => array(0 => 'ini'), 'translation.loader.json' => array(0 => 'json')), array('cache_dir' => (__DIR__.'/translations'), 'debug' => false, 'resource_files' => array('af' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.af.xlf')), 'ar' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.ar.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.ar.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.ar.xlf')), 'az' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.az.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.az.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.az.xlf')), 'bg' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.bg.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.bg.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.bg.xlf')), 'ca' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.ca.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.ca.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.ca.xlf')), 'cs' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.cs.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.cs.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.cs.xlf')), 'cy' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.cy.xlf')), 'da' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.da.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.da.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.da.xlf')), 'de' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.de.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.de.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.de.xlf')), 'el' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.el.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.el.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.el.xlf')), 'en' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.en.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.en.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.en.xlf')), 'es' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.es.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.es.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.es.xlf')), 'et' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.et.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.et.xlf')), 'eu' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.eu.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.eu.xlf')), 'fa' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.fa.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.fa.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.fa.xlf')), 'fi' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.fi.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.fi.xlf')), 'fr' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.fr.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.fr.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.fr.xlf')), 'gl' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.gl.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.gl.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.gl.xlf')), 'he' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.he.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.he.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.he.xlf')), 'hr' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.hr.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.hr.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.hr.xlf')), 'hu' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.hu.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.hu.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.hu.xlf')), 'hy' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.hy.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.hy.xlf')), 'id' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.id.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.id.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.id.xlf')), 'it' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.it.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.it.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.it.xlf')), 'ja' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.ja.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.ja.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.ja.xlf')), 'lb' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.lb.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.lb.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.lb.xlf')), 'lt' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.lt.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.lt.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.lt.xlf')), 'lv' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.lv.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.lv.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.lv.xlf')), 'mn' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.mn.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.mn.xlf')), 'nl' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.nl.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.nl.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.nl.xlf')), 'nn' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.nn.xlf')), 'no' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.no.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.no.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.no.xlf')), 'pl' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.pl.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.pl.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.pl.xlf')), 'pt' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.pt.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.pt.xlf')), 'pt_BR' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.pt_BR.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.pt_BR.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.pt_BR.xlf')), 'ro' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.ro.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.ro.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.ro.xlf')), 'ru' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.ru.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.ru.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.ru.xlf')), 'sk' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.sk.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.sk.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.sk.xlf')), 'sl' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.sl.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.sl.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.sl.xlf')), 'sq' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.sq.xlf')), 'sr_Cyrl' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.sr_Cyrl.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.sr_Cyrl.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.sr_Cyrl.xlf')), 'sr_Latn' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.sr_Latn.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.sr_Latn.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.sr_Latn.xlf')), 'sv' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.sv.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.sv.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.sv.xlf')), 'th' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.th.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.th.xlf')), 'tr' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.tr.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.tr.xlf')), 'uk' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.uk.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.uk.xlf')), 'vi' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.vi.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.vi.xlf')), 'zh_CN' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.zh_CN.xlf'), 1 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/translations/validators.zh_CN.xlf'), 2 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.zh_CN.xlf'), 3 => ($this->targetDirs[3].'/src/BB/DurianBundle/Resources/translations/messages.zh_CN.yml')), 'zh_TW' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Validator/Resources/translations/validators.zh_TW.xlf'), 1 => ($this->targetDirs[3].'/src/BB/DurianBundle/Resources/translations/messages.zh_TW.yml')), 'pt_PT' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.pt_PT.xlf')), 'ua' => array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Security/Core/Resources/translations/security.ua.xlf')))), array());

        $instance->setConfigCacheFactory($this->get('config_cache_factory'));
        $instance->setFallbackLocales(array(0 => 'en'));

        return $instance;
    }

    /*
     * Gets the 'translator_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\TranslatorListener A Symfony\Component\HttpKernel\EventListener\TranslatorListener instance
     */
    protected function getTranslatorListenerService()
    {
        return $this->services['translator_listener'] = new \Symfony\Component\HttpKernel\EventListener\TranslatorListener($this->get('translator.default'), $this->get('request_stack'));
    }

    /*
     * Gets the 'twig' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Twig_Environment A Twig_Environment instance
     */
    protected function getTwigService()
    {
        $a = $this->get('request_stack');
        $b = $this->get('router.request_context', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $c = $this->get('fragment.handler');

        $d = new \Symfony\Bridge\Twig\Extension\HttpFoundationExtension($a, $b);

        $e = new \Symfony\Bridge\Twig\AppVariable();
        $e->setEnvironment('prod');
        $e->setDebug(false);
        if ($this->has('request_stack')) {
            $e->setRequestStack($a);
        }
        $e->setContainer($this);

        $this->services['twig'] = $instance = new \Twig_Environment($this->get('twig.loader'), array('debug' => false, 'strict_variables' => false, 'exception_controller' => 'twig.controller.exception:showAction', 'form_themes' => array(0 => 'form_div_layout.html.twig'), 'autoescape' => 'name', 'cache' => (__DIR__.'/twig'), 'charset' => 'UTF-8', 'paths' => array(), 'date' => array('format' => 'F j, Y H:i', 'interval_format' => '%d days', 'timezone' => NULL), 'number_format' => array('decimals' => 0, 'decimal_point' => '.', 'thousands_separator' => ',')));

        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($this->get('translator.default')));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\AssetExtension($this->get('assets.packages'), $d));
        $instance->addExtension(new \Symfony\Bundle\TwigBundle\Extension\ActionsExtension($c));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\CodeExtension(NULL, $this->targetDirs[2], 'UTF-8'));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\RoutingExtension($this->get('router')));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\YamlExtension());
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\StopwatchExtension($this->get('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE), false));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\ExpressionExtension());
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\HttpKernelExtension($c));
        $instance->addExtension($d);
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\FormExtension(new \Symfony\Bridge\Twig\Form\TwigRenderer(new \Symfony\Bridge\Twig\Form\TwigRendererEngine(array(0 => 'form_div_layout.html.twig')), $this->get('security.csrf.token_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE))));
        $instance->addExtension(new \Doctrine\Bundle\DoctrineBundle\Twig\DoctrineExtension());
        $instance->addExtension(new \Knp\Bundle\MarkdownBundle\Twig\Extension\MarkdownTwigExtension($this->get('markdown.parser.parser_manager')));
        $instance->addGlobal('app', $e);
        call_user_func(array(new \Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator('F j, Y H:i', '%d days', NULL, 0, '.', ','), 'configure'), $instance);

        return $instance;
    }

    /*
     * Gets the 'twig.controller.exception' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\TwigBundle\Controller\ExceptionController A Symfony\Bundle\TwigBundle\Controller\ExceptionController instance
     */
    protected function getTwig_Controller_ExceptionService()
    {
        return $this->services['twig.controller.exception'] = new \Symfony\Bundle\TwigBundle\Controller\ExceptionController($this->get('twig'), false);
    }

    /*
     * Gets the 'twig.controller.preview_error' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\TwigBundle\Controller\PreviewErrorController A Symfony\Bundle\TwigBundle\Controller\PreviewErrorController instance
     */
    protected function getTwig_Controller_PreviewErrorService()
    {
        return $this->services['twig.controller.preview_error'] = new \Symfony\Bundle\TwigBundle\Controller\PreviewErrorController($this->get('http_kernel'), 'twig.controller.exception:showAction');
    }

    /*
     * Gets the 'twig.exception_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\ExceptionListener A Symfony\Component\HttpKernel\EventListener\ExceptionListener instance
     */
    protected function getTwig_ExceptionListenerService()
    {
        return $this->services['twig.exception_listener'] = new \Symfony\Component\HttpKernel\EventListener\ExceptionListener('twig.controller.exception:showAction', $this->get('monolog.logger.request', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /*
     * Gets the 'twig.loader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bundle\TwigBundle\Loader\FilesystemLoader A Symfony\Bundle\TwigBundle\Loader\FilesystemLoader instance
     */
    protected function getTwig_LoaderService()
    {
        $this->services['twig.loader'] = $instance = new \Symfony\Bundle\TwigBundle\Loader\FilesystemLoader($this->get('templating.locator'), $this->get('templating.name_parser'));

        $instance->addPath(($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Resources/views'), 'Framework');
        $instance->addPath(($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Resources/views'), 'Twig');
        $instance->addPath(($this->targetDirs[3].'/vendor/doctrine/doctrine-bundle/Resources/views'), 'Doctrine');
        $instance->addPath(($this->targetDirs[3].'/vendor/snc/redis-bundle/Snc/RedisBundle/Resources/views'), 'SncRedis');
        $instance->addPath(($this->targetDirs[3].'/src/BB/DurianBundle/Resources/views'), 'BBDurian');
        $instance->addPath(($this->targetDirs[2].'/Resources/views'));
        $instance->addPath(($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Form'));

        return $instance;
    }

    /*
     * Gets the 'twig.profile' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Twig_Profiler_Profile A Twig_Profiler_Profile instance
     */
    protected function getTwig_ProfileService()
    {
        return $this->services['twig.profile'] = new \Twig_Profiler_Profile();
    }

    /*
     * Gets the 'twig.translation.extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Bridge\Twig\Translation\TwigExtractor A Symfony\Bridge\Twig\Translation\TwigExtractor instance
     */
    protected function getTwig_Translation_ExtractorService()
    {
        return $this->services['twig.translation.extractor'] = new \Symfony\Bridge\Twig\Translation\TwigExtractor($this->get('twig'));
    }

    /*
     * Gets the 'uri_signer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\UriSigner A Symfony\Component\HttpKernel\UriSigner instance
     */
    protected function getUriSignerService()
    {
        return $this->services['uri_signer'] = new \Symfony\Component\HttpKernel\UriSigner('ThisTokenIsNotSoSecretChangeIt');
    }

    /*
     * Gets the 'validate_request_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\HttpKernel\EventListener\ValidateRequestListener A Symfony\Component\HttpKernel\EventListener\ValidateRequestListener instance
     */
    protected function getValidateRequestListenerService()
    {
        return $this->services['validate_request_listener'] = new \Symfony\Component\HttpKernel\EventListener\ValidateRequestListener();
    }

    /*
     * Gets the 'validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface A Symfony\Component\Validator\Validator\ValidatorInterface instance
     */
    protected function getValidatorService()
    {
        return $this->services['validator'] = $this->get('validator.builder')->getValidator();
    }

    /*
     * Gets the 'validator.builder' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Validator\ValidatorBuilderInterface A Symfony\Component\Validator\ValidatorBuilderInterface instance
     */
    protected function getValidator_BuilderService()
    {
        $this->services['validator.builder'] = $instance = \Symfony\Component\Validator\Validation::createValidatorBuilder();

        $instance->setConstraintValidatorFactory(new \Symfony\Bundle\FrameworkBundle\Validator\ConstraintValidatorFactory($this, array('validator.expression' => 'validator.expression', 'Symfony\\Component\\Validator\\Constraints\\ExpressionValidator' => 'validator.expression', 'Symfony\\Component\\Validator\\Constraints\\EmailValidator' => 'validator.email', 'doctrine.orm.validator.unique' => 'doctrine.orm.validator.unique', 'Symfony\\Bridge\\Doctrine\\Validator\\Constraints\\UniqueEntityValidator' => 'doctrine.orm.validator.unique')));
        $instance->setTranslator($this->get('translator.default'));
        $instance->setTranslationDomain('validators');
        $instance->addXmlMappings(array(0 => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Component/Form/Resources/config/validation.xml')));
        $instance->enableAnnotationMapping($this->get('annotation_reader'));
        $instance->addMethodMapping('loadValidatorMetadata');
        $instance->addObjectInitializers(array(0 => $this->get('doctrine.orm.validator_initializer')));

        return $instance;
    }

    /*
     * Gets the 'validator.email' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Validator\Constraints\EmailValidator A Symfony\Component\Validator\Constraints\EmailValidator instance
     */
    protected function getValidator_EmailService()
    {
        return $this->services['validator.email'] = new \Symfony\Component\Validator\Constraints\EmailValidator(false);
    }

    /*
     * Gets the 'validator.expression' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return \Symfony\Component\Validator\Constraints\ExpressionValidator A Symfony\Component\Validator\Constraints\ExpressionValidator instance
     */
    protected function getValidator_ExpressionService()
    {
        return $this->services['validator.expression'] = new \Symfony\Component\Validator\Constraints\ExpressionValidator($this->get('property_accessor'));
    }

    /*
     * Gets the 'controller_name_converter' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser A Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser instance
     */
    protected function getControllerNameConverterService()
    {
        return $this->services['controller_name_converter'] = new \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser($this->get('kernel'));
    }

    /*
     * Gets the 'doctrine.orm.naming_strategy.default' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return \Doctrine\ORM\Mapping\DefaultNamingStrategy A Doctrine\ORM\Mapping\DefaultNamingStrategy instance
     */
    protected function getDoctrine_Orm_NamingStrategy_DefaultService()
    {
        return $this->services['doctrine.orm.naming_strategy.default'] = new \Doctrine\ORM\Mapping\DefaultNamingStrategy();
    }

    /*
     * Gets the 'doctrine.orm.quote_strategy.default' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return \Doctrine\ORM\Mapping\DefaultQuoteStrategy A Doctrine\ORM\Mapping\DefaultQuoteStrategy instance
     */
    protected function getDoctrine_Orm_QuoteStrategy_DefaultService()
    {
        return $this->services['doctrine.orm.quote_strategy.default'] = new \Doctrine\ORM\Mapping\DefaultQuoteStrategy();
    }

    /*
     * Gets the 'form.server_params' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return \Symfony\Component\Form\Util\ServerParams A Symfony\Component\Form\Util\ServerParams instance
     */
    protected function getForm_ServerParamsService()
    {
        return $this->services['form.server_params'] = new \Symfony\Component\Form\Util\ServerParams($this->get('request_stack'));
    }

    /*
     * Gets the 'markdown.parser.parser_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return \Knp\Bundle\MarkdownBundle\Parser\ParserManager A Knp\Bundle\MarkdownBundle\Parser\ParserManager instance
     */
    protected function getMarkdown_Parser_ParserManagerService()
    {
        $this->services['markdown.parser.parser_manager'] = $instance = new \Knp\Bundle\MarkdownBundle\Parser\ParserManager();

        $instance->addParser(new \Knp\Bundle\MarkdownBundle\Parser\Preset\Min(), 'min');
        $instance->addParser(new \Knp\Bundle\MarkdownBundle\Parser\Preset\Light(), 'light');
        $instance->addParser(new \Knp\Bundle\MarkdownBundle\Parser\Preset\Medium(), 'medium');
        $instance->addParser($this->get('markdown.parser'), 'default');
        $instance->addParser(new \Knp\Bundle\MarkdownBundle\Parser\Preset\Flavored(), 'flavored');

        return $instance;
    }

    /*
     * Gets the 'router.request_context' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return \Symfony\Component\Routing\RequestContext A Symfony\Component\Routing\RequestContext instance
     */
    protected function getRouter_RequestContextService()
    {
        return $this->services['router.request_context'] = new \Symfony\Component\Routing\RequestContext('', 'GET', 'localhost', 'http', 80, 443);
    }

    /*
     * Gets the 'session.storage.metadata_bag' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Storage\MetadataBag A Symfony\Component\HttpFoundation\Session\Storage\MetadataBag instance
     */
    protected function getSession_Storage_MetadataBagService()
    {
        return $this->services['session.storage.metadata_bag'] = new \Symfony\Component\HttpFoundation\Session\Storage\MetadataBag('_sf2_meta', '0');
    }

    /*
     * Gets the 'templating.locator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Templating\Loader\TemplateLocator A Symfony\Bundle\FrameworkBundle\Templating\Loader\TemplateLocator instance
     */
    protected function getTemplating_LocatorService()
    {
        return $this->services['templating.locator'] = new \Symfony\Bundle\FrameworkBundle\Templating\Loader\TemplateLocator($this->get('file_locator'), __DIR__);
    }

    /*
     * {@inheritdoc}
     */
    public function getParameter($name)
    {
        $name = strtolower($name);

        if (!(isset($this->parameters[$name]) || array_key_exists($name, $this->parameters))) {
            throw new InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
        }

        return $this->parameters[$name];
    }

    /*
     * {@inheritdoc}
     */
    public function hasParameter($name)
    {
        $name = strtolower($name);

        return isset($this->parameters[$name]) || array_key_exists($name, $this->parameters);
    }

    /*
     * {@inheritdoc}
     */
    public function setParameter($name, $value)
    {
        throw new LogicException('Impossible to call set() on a frozen ParameterBag.');
    }

    /*
     * {@inheritdoc}
     */
    public function getParameterBag()
    {
        if (null === $this->parameterBag) {
            $this->parameterBag = new FrozenParameterBag($this->parameters);
        }

        return $this->parameterBag;
    }

    /*
     * Gets the default parameters.
     *
     * @return array An array of the default parameters
     */
    protected function getDefaultParameters()
    {
        return array(
            'kernel.root_dir' => $this->targetDirs[2],
            'kernel.environment' => 'prod',
            'kernel.debug' => false,
            'kernel.name' => 'app',
            'kernel.cache_dir' => __DIR__,
            'kernel.logs_dir' => ($this->targetDirs[2].'/logs'),
            'kernel.bundles' => array(
                'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                'TwigBundle' => 'Symfony\\Bundle\\TwigBundle\\TwigBundle',
                'MonologBundle' => 'Symfony\\Bundle\\MonologBundle\\MonologBundle',
                'DoctrineBundle' => 'Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle',
                'DoctrineCacheBundle' => 'Doctrine\\Bundle\\DoctrineCacheBundle\\DoctrineCacheBundle',
                'DoctrineMigrationsBundle' => 'Doctrine\\Bundle\\MigrationsBundle\\DoctrineMigrationsBundle',
                'SensioFrameworkExtraBundle' => 'Sensio\\Bundle\\FrameworkExtraBundle\\SensioFrameworkExtraBundle',
                'FOSJsRoutingBundle' => 'FOS\\JsRoutingBundle\\FOSJsRoutingBundle',
                'KnpMarkdownBundle' => 'Knp\\Bundle\\MarkdownBundle\\KnpMarkdownBundle',
                'SncRedisBundle' => 'Snc\\RedisBundle\\SncRedisBundle',
                'BBDurianBundle' => 'BB\\DurianBundle\\BBDurianBundle',
            ),
            'kernel.bundles_metadata' => array(
                'FrameworkBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle'),
                    'namespace' => 'Symfony\\Bundle\\FrameworkBundle',
                ),
                'TwigBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle'),
                    'namespace' => 'Symfony\\Bundle\\TwigBundle',
                ),
                'MonologBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/symfony/monolog-bundle/Symfony/Bundle/MonologBundle'),
                    'namespace' => 'Symfony\\Bundle\\MonologBundle',
                ),
                'DoctrineBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/doctrine/doctrine-bundle'),
                    'namespace' => 'Doctrine\\Bundle\\DoctrineBundle',
                ),
                'DoctrineCacheBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/doctrine/doctrine-cache-bundle'),
                    'namespace' => 'Doctrine\\Bundle\\DoctrineCacheBundle',
                ),
                'DoctrineMigrationsBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/doctrine/doctrine-migrations-bundle'),
                    'namespace' => 'Doctrine\\Bundle\\MigrationsBundle',
                ),
                'SensioFrameworkExtraBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/sensio/framework-extra-bundle'),
                    'namespace' => 'Sensio\\Bundle\\FrameworkExtraBundle',
                ),
                'FOSJsRoutingBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/friendsofsymfony/jsrouting-bundle'),
                    'namespace' => 'FOS\\JsRoutingBundle',
                ),
                'KnpMarkdownBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/knplabs/knp-markdown-bundle'),
                    'namespace' => 'Knp\\Bundle\\MarkdownBundle',
                ),
                'SncRedisBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/vendor/snc/redis-bundle/Snc/RedisBundle'),
                    'namespace' => 'Snc\\RedisBundle',
                ),
                'BBDurianBundle' => array(
                    'parent' => NULL,
                    'path' => ($this->targetDirs[3].'/src/BB/DurianBundle'),
                    'namespace' => 'BB\\DurianBundle',
                ),
            ),
            'kernel.charset' => 'UTF-8',
            'kernel.container_class' => 'appProdProjectContainer',
            'database_driver' => 'pdo_mysql',
            'database_host' => 'mysql',
            'database_master_host' => 'mysql',
            'database_slave_host' => 'mysql',
            'database_port' => 3306,
            'database_name' => 'durian_bb',
            'database_user' => 'root',
            'database_password' => 'very-secret',
            'database_his_driver' => 'pdo_mysql',
            'database_his_host' => 'mysql',
            'database_his_master_host' => 'mysql',
            'database_his_slave_host' => 'mysql',
            'database_his_port' => 3306,
            'database_his_name' => 'durian_bb',
            'database_his_user' => 'root',
            'database_his_password' => 'very-secret',
            'database_entry_driver' => 'pdo_mysql',
            'database_entry_master_host' => 'mysql',
            'database_entry_slave_host' => 'mysql',
            'database_entry_port' => 3306,
            'database_entry_name' => 'durian_bb',
            'database_entry_user' => 'root',
            'database_entry_password' => 'very-secret',
            'database_share_driver' => 'pdo_mysql',
            'database_share_master_host' => 'mysql',
            'database_share_slave_host' => 'mysql',
            'database_share_port' => 3306,
            'database_share_name' => 'durian_bb',
            'database_share_user' => 'root',
            'database_share_password' => 'very-secret',
            'database_outside_driver' => 'pdo_mysql',
            'database_outside_master_host' => 'mysql',
            'database_outside_slave_host' => 'mysql',
            'database_outside_port' => 3306,
            'database_outside_name' => 'durian_bb',
            'database_outside_user' => 'root',
            'database_outside_password' => 'very-secret',
            'database_ip_blocker_driver' => 'pdo_mysql',
            'database_ip_blocker_master_host' => 'mysql',
            'database_ip_blocker_slave_host' => 'mysql',
            'database_ip_blocker_port' => 3306,
            'database_ip_blocker_name' => 'durian_bb',
            'database_ip_blocker_user' => 'root',
            'database_ip_blocker_password' => 'very-secret',
            'mailer_transport' => 'smtp',
            'mailer_host' => '127.0.0.1',
            'mailer_user' => NULL,
            'mailer_password' => NULL,
            'locale' => 'en',
            'trusted_proxies' => NULL,
            'secret' => 'ThisTokenIsNotSoSecretChangeIt',
            'redis_host' => 'redis',
            'redis_port' => 6379,
            'redis_clusters' => array(
                0 => 'redis://redis:6379/2',
                1 => 'redis://redis:6379/3',
            ),
            'redis_sequence' => 'redis://redis:6379/4',
            'redis_reward' => 'redis://redis:6379/6',
            'redis_map' => array(
                0 => 'redis://redis:6379/7',
                1 => 'redis://redis:6379/8',
                2 => 'redis://redis:6379/9',
            ),
            'redis_wallet1' => 'redis://redis:6379/10',
            'redis_wallet2' => 'redis://redis:6379/11',
            'redis_wallet3' => 'redis://redis:6379/12',
            'redis_wallet4' => 'redis://redis:6379/13',
            'redis_kue' => 'redis://redis:6379/12',
            'redis_slide' => 'redis://redis:6379/14',
            'redis_oauth2' => 'redis://redis:6379/2',
            'redis_bodog' => 'redis://redis:6379/3',
            'redis_external' => 'redis://redis:6379/4',
            'redis_total_balance' => 'redis://redis:6379/5',
            'redis_suncity' => 'redis://redis:6379/6',
            'redis_ip_blocker' => array(
                0 => 'redis://redis:6379/7',
                1 => 'redis://redis:6379/8',
                2 => 'redis://redis:6379/9',
            ),
            'cluster_name' => 'symfony',
            'sequence_name' => 'symfony',
            'map_name' => 'symfony',
            'reward_name' => 'symfony',
            'kue_name' => 'symfony',
            'slide_name' => 'symfony',
            'bodog_name' => 'symfony',
            'external_name' => 'symfony',
            'total_balance_name' => 'symfony',
            'suncity_name' => 'symfony',
            'ip_blocker_name' => 'symfony',
            'account_domain' => NULL,
            'account_ip' => NULL,
            'italking_domain' => NULL,
            'italking_ip' => NULL,
            'italking_url' => NULL,
            'italking_method' => NULL,
            'italking_user' => NULL,
            'italking_password' => NULL,
            'italking_gm_code' => NULL,
            'italking_esball_code' => NULL,
            'italking_bet9_code' => NULL,
            'italking_kresball_code' => NULL,
            'italking_esball_global_code' => NULL,
            'italking_eslot_code' => NULL,
            'maintain_1_domain' => NULL,
            'maintain_1_ip' => NULL,
            'maintain_1_url' => NULL,
            'maintain_1_method' => NULL,
            'maintain_3_domain' => NULL,
            'maintain_3_ip' => NULL,
            'maintain_3_url' => NULL,
            'maintain_3_method' => NULL,
            'maintain_mobile_domain' => NULL,
            'maintain_mobile_ip' => NULL,
            'maintain_mobile_url' => NULL,
            'whitelist_mobile_url' => NULL,
            'whitelist_mobile_key' => NULL,
            'maintain_domain_domain' => NULL,
            'maintain_domain_ip' => NULL,
            'maintain_domain_url' => NULL,
            'shopweb_ip' => NULL,
            'audit_domain' => NULL,
            'audit_ip' => NULL,
            'oauth_redirect_ip' => NULL,
            'rd1_domain' => NULL,
            'rd1_ip' => NULL,
            'rd1_api_key' => NULL,
            'rd1_ball_domain' => NULL,
            'rd1_ball_ip' => NULL,
            'rd1_ball_api_key' => NULL,
            'rd1_maintain_domain' => NULL,
            'rd1_maintain_ip' => NULL,
            'rd2_domain' => NULL,
            'rd2_ip' => NULL,
            'rd3_domain' => NULL,
            'rd3_ip' => NULL,
            'rd5_domain' => NULL,
            'rd5_ip' => NULL,
            'kiwi_host' => NULL,
            'kiwi_ip' => NULL,
            'kiwi_api_key' => NULL,
            'kiwi_port' => NULL,
            'site' => NULL,
            'kue_domain' => NULL,
            'kue_ip' => NULL,
            'pdo_attr_timeout' => NULL,
            'xhprof_location_config' => '/usr/local/lib/php/xhprof_lib/config.php',
            'xhprof_location_lib' => '/usr/local/lib/php/xhprof_lib/utils/xhprof_lib.php',
            'xhprof_location_runs' => '/usr/local/lib/php/xhprof_lib/utils/xhprof_runs.php',
            'xhprof_location_web' => 'http://xhprof.local',
            'payment_ip' => 'nginx',
            'merchant_white_list_user' => NULL,
            'merchant_white_list_password' => NULL,
            'merchant_white_list_ip' => NULL,
            'merchant_white_list_host' => NULL,
            'whitelist_f5_ip' => NULL,
            'rd1_copy_user_domain' => NULL,
            'rd1_copy_user_ip' => NULL,
            'rd2_copy_user_domain' => NULL,
            'rd2_copy_user_ip' => NULL,
            'rd3_copy_user_domain' => NULL,
            'rd3_copy_user_ip' => NULL,
            'rd5_payment_ip_list' => 'nginx',
            'rd1_mail_server_ip' => NULL,
            'otp_server_ip' => NULL,
            'otp_secret' => NULL,
            'static_otp_username' => NULL,
            'static_otp_password' => NULL,
            'ttl_binding_token' => 300,
            'ttl_access_token' => 7200,
            'oauth2_prefix' => 'symfony:oauth2:',
            'remit_auto_confirm_host' => NULL,
            'auto_confirm_bbv2_host' => NULL,
            'auto_confirm_bbv2_token' => NULL,
            'rd5_external_code' => array(

            ),
            'payment_bind_ip' => NULL,
            'pay_ip_bind_token' => NULL,
            'suncity_agent' => array(

            ),
            'external_host' => NULL,
            'external_ip' => NULL,
            'external_port' => NULL,
            'pink_ip' => NULL,
            'pink_host' => NULL,
            'trade_token' => NULL,
            'controller_resolver.class' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerResolver',
            'controller_name_converter.class' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerNameParser',
            'response_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\ResponseListener',
            'streamed_response_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\StreamedResponseListener',
            'locale_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\LocaleListener',
            'event_dispatcher.class' => 'Symfony\\Component\\EventDispatcher\\ContainerAwareEventDispatcher',
            'http_kernel.class' => 'Symfony\\Component\\HttpKernel\\DependencyInjection\\ContainerAwareHttpKernel',
            'filesystem.class' => 'Symfony\\Component\\Filesystem\\Filesystem',
            'cache_warmer.class' => 'Symfony\\Component\\HttpKernel\\CacheWarmer\\CacheWarmerAggregate',
            'cache_clearer.class' => 'Symfony\\Component\\HttpKernel\\CacheClearer\\ChainCacheClearer',
            'file_locator.class' => 'Symfony\\Component\\HttpKernel\\Config\\FileLocator',
            'uri_signer.class' => 'Symfony\\Component\\HttpKernel\\UriSigner',
            'request_stack.class' => 'Symfony\\Component\\HttpFoundation\\RequestStack',
            'fragment.handler.class' => 'Symfony\\Component\\HttpKernel\\DependencyInjection\\LazyLoadingFragmentHandler',
            'fragment.renderer.inline.class' => 'Symfony\\Component\\HttpKernel\\Fragment\\InlineFragmentRenderer',
            'fragment.renderer.hinclude.class' => 'Symfony\\Component\\HttpKernel\\Fragment\\HIncludeFragmentRenderer',
            'fragment.renderer.hinclude.global_template' => NULL,
            'fragment.renderer.esi.class' => 'Symfony\\Component\\HttpKernel\\Fragment\\EsiFragmentRenderer',
            'fragment.path' => '/_fragment',
            'translator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Translation\\Translator',
            'translator.identity.class' => 'Symfony\\Component\\Translation\\IdentityTranslator',
            'translator.selector.class' => 'Symfony\\Component\\Translation\\MessageSelector',
            'translation.loader.php.class' => 'Symfony\\Component\\Translation\\Loader\\PhpFileLoader',
            'translation.loader.yml.class' => 'Symfony\\Component\\Translation\\Loader\\YamlFileLoader',
            'translation.loader.xliff.class' => 'Symfony\\Component\\Translation\\Loader\\XliffFileLoader',
            'translation.loader.po.class' => 'Symfony\\Component\\Translation\\Loader\\PoFileLoader',
            'translation.loader.mo.class' => 'Symfony\\Component\\Translation\\Loader\\MoFileLoader',
            'translation.loader.qt.class' => 'Symfony\\Component\\Translation\\Loader\\QtFileLoader',
            'translation.loader.csv.class' => 'Symfony\\Component\\Translation\\Loader\\CsvFileLoader',
            'translation.loader.res.class' => 'Symfony\\Component\\Translation\\Loader\\IcuResFileLoader',
            'translation.loader.dat.class' => 'Symfony\\Component\\Translation\\Loader\\IcuDatFileLoader',
            'translation.loader.ini.class' => 'Symfony\\Component\\Translation\\Loader\\IniFileLoader',
            'translation.loader.json.class' => 'Symfony\\Component\\Translation\\Loader\\JsonFileLoader',
            'translation.dumper.php.class' => 'Symfony\\Component\\Translation\\Dumper\\PhpFileDumper',
            'translation.dumper.xliff.class' => 'Symfony\\Component\\Translation\\Dumper\\XliffFileDumper',
            'translation.dumper.po.class' => 'Symfony\\Component\\Translation\\Dumper\\PoFileDumper',
            'translation.dumper.mo.class' => 'Symfony\\Component\\Translation\\Dumper\\MoFileDumper',
            'translation.dumper.yml.class' => 'Symfony\\Component\\Translation\\Dumper\\YamlFileDumper',
            'translation.dumper.qt.class' => 'Symfony\\Component\\Translation\\Dumper\\QtFileDumper',
            'translation.dumper.csv.class' => 'Symfony\\Component\\Translation\\Dumper\\CsvFileDumper',
            'translation.dumper.ini.class' => 'Symfony\\Component\\Translation\\Dumper\\IniFileDumper',
            'translation.dumper.json.class' => 'Symfony\\Component\\Translation\\Dumper\\JsonFileDumper',
            'translation.dumper.res.class' => 'Symfony\\Component\\Translation\\Dumper\\IcuResFileDumper',
            'translation.extractor.php.class' => 'Symfony\\Bundle\\FrameworkBundle\\Translation\\PhpExtractor',
            'translation.loader.class' => 'Symfony\\Bundle\\FrameworkBundle\\Translation\\TranslationLoader',
            'translation.extractor.class' => 'Symfony\\Component\\Translation\\Extractor\\ChainExtractor',
            'translation.writer.class' => 'Symfony\\Component\\Translation\\Writer\\TranslationWriter',
            'property_accessor.class' => 'Symfony\\Component\\PropertyAccess\\PropertyAccessor',
            'kernel.secret' => 'ThisTokenIsNotSoSecretChangeIt',
            'kernel.http_method_override' => true,
            'kernel.trusted_hosts' => array(

            ),
            'kernel.trusted_proxies' => array(

            ),
            'kernel.default_locale' => 'en',
            'session.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Session',
            'session.flashbag.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Flash\\FlashBag',
            'session.attribute_bag.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Attribute\\AttributeBag',
            'session.storage.metadata_bag.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\MetadataBag',
            'session.metadata.storage_key' => '_sf2_meta',
            'session.storage.native.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\NativeSessionStorage',
            'session.storage.php_bridge.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\PhpBridgeSessionStorage',
            'session.storage.mock_file.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\MockFileSessionStorage',
            'session.handler.native_file.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\Handler\\NativeFileSessionHandler',
            'session.handler.write_check.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\Handler\\WriteCheckSessionHandler',
            'session_listener.class' => 'Symfony\\Bundle\\FrameworkBundle\\EventListener\\SessionListener',
            'session.storage.options' => array(
                'cookie_httponly' => true,
                'gc_probability' => 1,
            ),
            'session.save_path' => (__DIR__.'/sessions'),
            'session.metadata.update_threshold' => '0',
            'security.secure_random.class' => 'Symfony\\Component\\Security\\Core\\Util\\SecureRandom',
            'form.resolved_type_factory.class' => 'Symfony\\Component\\Form\\ResolvedFormTypeFactory',
            'form.registry.class' => 'Symfony\\Component\\Form\\FormRegistry',
            'form.factory.class' => 'Symfony\\Component\\Form\\FormFactory',
            'form.extension.class' => 'Symfony\\Component\\Form\\Extension\\DependencyInjection\\DependencyInjectionExtension',
            'form.type_guesser.validator.class' => 'Symfony\\Component\\Form\\Extension\\Validator\\ValidatorTypeGuesser',
            'form.type_extension.form.request_handler.class' => 'Symfony\\Component\\Form\\Extension\\HttpFoundation\\HttpFoundationRequestHandler',
            'form.type_extension.csrf.enabled' => true,
            'form.type_extension.csrf.field_name' => '_token',
            'security.csrf.token_generator.class' => 'Symfony\\Component\\Security\\Csrf\\TokenGenerator\\UriSafeTokenGenerator',
            'security.csrf.token_storage.class' => 'Symfony\\Component\\Security\\Csrf\\TokenStorage\\SessionTokenStorage',
            'security.csrf.token_manager.class' => 'Symfony\\Component\\Security\\Csrf\\CsrfTokenManager',
            'templating.engine.delegating.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\DelegatingEngine',
            'templating.name_parser.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\TemplateNameParser',
            'templating.filename_parser.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\TemplateFilenameParser',
            'templating.cache_warmer.template_paths.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\TemplatePathsCacheWarmer',
            'templating.locator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Loader\\TemplateLocator',
            'templating.loader.filesystem.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Loader\\FilesystemLoader',
            'templating.loader.cache.class' => 'Symfony\\Component\\Templating\\Loader\\CacheLoader',
            'templating.loader.chain.class' => 'Symfony\\Component\\Templating\\Loader\\ChainLoader',
            'templating.finder.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\TemplateFinder',
            'templating.helper.assets.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\AssetsHelper',
            'templating.helper.router.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\RouterHelper',
            'templating.helper.code.file_link_format' => NULL,
            'templating.loader.cache.path' => NULL,
            'templating.engines' => array(
                0 => 'twig',
            ),
            'validator.class' => 'Symfony\\Component\\Validator\\Validator\\ValidatorInterface',
            'validator.builder.class' => 'Symfony\\Component\\Validator\\ValidatorBuilderInterface',
            'validator.builder.factory.class' => 'Symfony\\Component\\Validator\\Validation',
            'validator.mapping.cache.apc.class' => 'Symfony\\Component\\Validator\\Mapping\\Cache\\ApcCache',
            'validator.mapping.cache.prefix' => '',
            'validator.validator_factory.class' => 'Symfony\\Bundle\\FrameworkBundle\\Validator\\ConstraintValidatorFactory',
            'validator.expression.class' => 'Symfony\\Component\\Validator\\Constraints\\ExpressionValidator',
            'validator.email.class' => 'Symfony\\Component\\Validator\\Constraints\\EmailValidator',
            'validator.translation_domain' => 'validators',
            'validator.api' => '2.5-bc',
            'fragment.listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\FragmentListener',
            'translator.logging' => false,
            'data_collector.templates' => array(

            ),
            'router.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\Router',
            'router.request_context.class' => 'Symfony\\Component\\Routing\\RequestContext',
            'routing.loader.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\DelegatingLoader',
            'routing.resolver.class' => 'Symfony\\Component\\Config\\Loader\\LoaderResolver',
            'routing.loader.xml.class' => 'Symfony\\Component\\Routing\\Loader\\XmlFileLoader',
            'routing.loader.yml.class' => 'Symfony\\Component\\Routing\\Loader\\YamlFileLoader',
            'routing.loader.php.class' => 'Symfony\\Component\\Routing\\Loader\\PhpFileLoader',
            'router.options.generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'router.options.generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'router.options.generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper',
            'router.options.matcher_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher',
            'router.options.matcher_base_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher',
            'router.options.matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper',
            'router.cache_warmer.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\RouterCacheWarmer',
            'router.options.matcher.cache_class' => 'appProdProjectContainerUrlMatcher',
            'router.options.generator.cache_class' => 'appProdProjectContainerUrlGenerator',
            'router_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\RouterListener',
            'router.request_context.host' => 'localhost',
            'router.request_context.scheme' => 'http',
            'router.request_context.base_url' => '',
            'router.resource' => ($this->targetDirs[2].'/config/routing.yml'),
            'router.cache_class_prefix' => 'appProdProjectContainer',
            'request_listener.http_port' => 80,
            'request_listener.https_port' => 443,
            'annotations.reader.class' => 'Doctrine\\Common\\Annotations\\AnnotationReader',
            'annotations.cached_reader.class' => 'Doctrine\\Common\\Annotations\\CachedReader',
            'annotations.file_cache_reader.class' => 'Doctrine\\Common\\Annotations\\FileCacheReader',
            'debug.debug_handlers_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\DebugHandlersListener',
            'debug.stopwatch.class' => 'Symfony\\Component\\Stopwatch\\Stopwatch',
            'debug.error_handler.throw_at' => 0,
            'twig.class' => 'Twig_Environment',
            'twig.loader.filesystem.class' => 'Symfony\\Bundle\\TwigBundle\\Loader\\FilesystemLoader',
            'twig.loader.chain.class' => 'Twig_Loader_Chain',
            'templating.engine.twig.class' => 'Symfony\\Bundle\\TwigBundle\\TwigEngine',
            'twig.cache_warmer.class' => 'Symfony\\Bundle\\TwigBundle\\CacheWarmer\\TemplateCacheCacheWarmer',
            'twig.extension.trans.class' => 'Symfony\\Bridge\\Twig\\Extension\\TranslationExtension',
            'twig.extension.actions.class' => 'Symfony\\Bundle\\TwigBundle\\Extension\\ActionsExtension',
            'twig.extension.code.class' => 'Symfony\\Bridge\\Twig\\Extension\\CodeExtension',
            'twig.extension.routing.class' => 'Symfony\\Bridge\\Twig\\Extension\\RoutingExtension',
            'twig.extension.yaml.class' => 'Symfony\\Bridge\\Twig\\Extension\\YamlExtension',
            'twig.extension.form.class' => 'Symfony\\Bridge\\Twig\\Extension\\FormExtension',
            'twig.extension.httpkernel.class' => 'Symfony\\Bridge\\Twig\\Extension\\HttpKernelExtension',
            'twig.extension.debug.stopwatch.class' => 'Symfony\\Bridge\\Twig\\Extension\\StopwatchExtension',
            'twig.extension.expression.class' => 'Symfony\\Bridge\\Twig\\Extension\\ExpressionExtension',
            'twig.form.engine.class' => 'Symfony\\Bridge\\Twig\\Form\\TwigRendererEngine',
            'twig.form.renderer.class' => 'Symfony\\Bridge\\Twig\\Form\\TwigRenderer',
            'twig.translation.extractor.class' => 'Symfony\\Bridge\\Twig\\Translation\\TwigExtractor',
            'twig.exception_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\ExceptionListener',
            'twig.controller.exception.class' => 'Symfony\\Bundle\\TwigBundle\\Controller\\ExceptionController',
            'twig.controller.preview_error.class' => 'Symfony\\Bundle\\TwigBundle\\Controller\\PreviewErrorController',
            'twig.exception_listener.controller' => 'twig.controller.exception:showAction',
            'twig.form.resources' => array(
                0 => 'form_div_layout.html.twig',
            ),
            'monolog.logger.class' => 'Symfony\\Bridge\\Monolog\\Logger',
            'monolog.gelf.publisher.class' => 'Gelf\\MessagePublisher',
            'monolog.handler.stream.class' => 'Monolog\\Handler\\StreamHandler',
            'monolog.handler.console.class' => 'Symfony\\Bridge\\Monolog\\Handler\\ConsoleHandler',
            'monolog.handler.group.class' => 'Monolog\\Handler\\GroupHandler',
            'monolog.handler.buffer.class' => 'Monolog\\Handler\\BufferHandler',
            'monolog.handler.rotating_file.class' => 'Monolog\\Handler\\RotatingFileHandler',
            'monolog.handler.syslog.class' => 'Monolog\\Handler\\SyslogHandler',
            'monolog.handler.null.class' => 'Monolog\\Handler\\NullHandler',
            'monolog.handler.test.class' => 'Monolog\\Handler\\TestHandler',
            'monolog.handler.gelf.class' => 'Monolog\\Handler\\GelfHandler',
            'monolog.handler.firephp.class' => 'Symfony\\Bridge\\Monolog\\Handler\\FirePHPHandler',
            'monolog.handler.chromephp.class' => 'Symfony\\Bridge\\Monolog\\Handler\\ChromePhpHandler',
            'monolog.handler.debug.class' => 'Symfony\\Bridge\\Monolog\\Handler\\DebugHandler',
            'monolog.handler.swift_mailer.class' => 'Symfony\\Bridge\\Monolog\\Handler\\SwiftMailerHandler',
            'monolog.handler.native_mailer.class' => 'Monolog\\Handler\\NativeMailerHandler',
            'monolog.handler.socket.class' => 'Monolog\\Handler\\SocketHandler',
            'monolog.handler.pushover.class' => 'Monolog\\Handler\\PushoverHandler',
            'monolog.handler.raven.class' => 'Monolog\\Handler\\RavenHandler',
            'monolog.handler.newrelic.class' => 'Monolog\\Handler\\NewRelicHandler',
            'monolog.handler.hipchat.class' => 'Monolog\\Handler\\HipChatHandler',
            'monolog.handler.cube.class' => 'Monolog\\Handler\\CubeHandler',
            'monolog.handler.amqp.class' => 'Monolog\\Handler\\AmqpHandler',
            'monolog.handler.error_log.class' => 'Monolog\\Handler\\ErrorLogHandler',
            'monolog.handler.loggly.class' => 'Monolog\\Handler\\LogglyHandler',
            'monolog.activation_strategy.not_found.class' => 'Symfony\\Bundle\\MonologBundle\\NotFoundActivationStrategy',
            'monolog.handler.fingers_crossed.class' => 'Monolog\\Handler\\FingersCrossedHandler',
            'monolog.handler.fingers_crossed.error_level_activation_strategy.class' => 'Monolog\\Handler\\FingersCrossed\\ErrorLevelActivationStrategy',
            'monolog.handler.mongo.class' => 'Monolog\\Handler\\MongoDBHandler',
            'monolog.mongo.client.class' => 'MongoClient',
            'monolog.swift_mailer.handlers' => array(

            ),
            'monolog.handlers_to_channels' => array(
                'monolog.handler.main' => NULL,
                'monolog.handler.monitor_queue_length' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_login_log_mobile' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.regular_login' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.generate_rm_plan_user' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_login_log' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.remit_auto_confirm' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.monitor_stat' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.generate_rm_plan' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.copy_user_crossDomain' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.op_obtain_reward' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_obtain_reward' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.remove_ipl_overdue_user' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.create_reward_entry' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.remove_overdue_user' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.pop_failed_message' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.send_message_http_detail' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.send_message' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.check_card_deposit_tracking' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.check_deposit_tracking' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.execute_rm_plan' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_rm_plan_user' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.check_redis_balance' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.check_balance' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.message_to_italking' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_credit' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_credit_entry' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_cash_fake_history' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_cash_fake_balance' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_cash_fake_entry' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_user_deposit_withdraw' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_cash_fake_queue' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.sync_cash_fake_entry_queue' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
                'monolog.handler.payment' => array(
                    'type' => 'inclusive',
                    'elements' => array(

                    ),
                ),
            ),
            'doctrine_cache.apc.class' => 'Doctrine\\Common\\Cache\\ApcCache',
            'doctrine_cache.apcu.class' => 'Doctrine\\Common\\Cache\\ApcuCache',
            'doctrine_cache.array.class' => 'Doctrine\\Common\\Cache\\ArrayCache',
            'doctrine_cache.chain.class' => 'Doctrine\\Common\\Cache\\ChainCache',
            'doctrine_cache.couchbase.class' => 'Doctrine\\Common\\Cache\\CouchbaseCache',
            'doctrine_cache.couchbase.connection.class' => 'Couchbase',
            'doctrine_cache.couchbase.hostnames' => 'localhost:8091',
            'doctrine_cache.file_system.class' => 'Doctrine\\Common\\Cache\\FilesystemCache',
            'doctrine_cache.php_file.class' => 'Doctrine\\Common\\Cache\\PhpFileCache',
            'doctrine_cache.memcache.class' => 'Doctrine\\Common\\Cache\\MemcacheCache',
            'doctrine_cache.memcache.connection.class' => 'Memcache',
            'doctrine_cache.memcache.host' => 'localhost',
            'doctrine_cache.memcache.port' => 11211,
            'doctrine_cache.memcached.class' => 'Doctrine\\Common\\Cache\\MemcachedCache',
            'doctrine_cache.memcached.connection.class' => 'Memcached',
            'doctrine_cache.memcached.host' => 'localhost',
            'doctrine_cache.memcached.port' => 11211,
            'doctrine_cache.mongodb.class' => 'Doctrine\\Common\\Cache\\MongoDBCache',
            'doctrine_cache.mongodb.collection.class' => 'MongoCollection',
            'doctrine_cache.mongodb.connection.class' => 'MongoClient',
            'doctrine_cache.mongodb.server' => 'localhost:27017',
            'doctrine_cache.predis.client.class' => 'Predis\\Client',
            'doctrine_cache.predis.scheme' => 'tcp',
            'doctrine_cache.predis.host' => 'localhost',
            'doctrine_cache.predis.port' => 6379,
            'doctrine_cache.redis.class' => 'Doctrine\\Common\\Cache\\RedisCache',
            'doctrine_cache.redis.connection.class' => 'Redis',
            'doctrine_cache.redis.host' => 'localhost',
            'doctrine_cache.redis.port' => 6379,
            'doctrine_cache.riak.class' => 'Doctrine\\Common\\Cache\\RiakCache',
            'doctrine_cache.riak.bucket.class' => 'Riak\\Bucket',
            'doctrine_cache.riak.connection.class' => 'Riak\\Connection',
            'doctrine_cache.riak.bucket_property_list.class' => 'Riak\\BucketPropertyList',
            'doctrine_cache.riak.host' => 'localhost',
            'doctrine_cache.riak.port' => 8087,
            'doctrine_cache.sqlite3.class' => 'Doctrine\\Common\\Cache\\SQLite3Cache',
            'doctrine_cache.sqlite3.connection.class' => 'SQLite3',
            'doctrine_cache.void.class' => 'Doctrine\\Common\\Cache\\VoidCache',
            'doctrine_cache.wincache.class' => 'Doctrine\\Common\\Cache\\WinCacheCache',
            'doctrine_cache.xcache.class' => 'Doctrine\\Common\\Cache\\XcacheCache',
            'doctrine_cache.zenddata.class' => 'Doctrine\\Common\\Cache\\ZendDataCache',
            'doctrine_cache.security.acl.cache.class' => 'Doctrine\\Bundle\\DoctrineCacheBundle\\Acl\\Model\\AclCache',
            'doctrine.dbal.logger.chain.class' => 'Doctrine\\DBAL\\Logging\\LoggerChain',
            'doctrine.dbal.logger.profiling.class' => 'Doctrine\\DBAL\\Logging\\DebugStack',
            'doctrine.dbal.logger.class' => 'Symfony\\Bridge\\Doctrine\\Logger\\DbalLogger',
            'doctrine.dbal.configuration.class' => 'Doctrine\\DBAL\\Configuration',
            'doctrine.data_collector.class' => 'Doctrine\\Bundle\\DoctrineBundle\\DataCollector\\DoctrineDataCollector',
            'doctrine.dbal.connection.event_manager.class' => 'Symfony\\Bridge\\Doctrine\\ContainerAwareEventManager',
            'doctrine.dbal.connection_factory.class' => 'Doctrine\\Bundle\\DoctrineBundle\\ConnectionFactory',
            'doctrine.dbal.events.mysql_session_init.class' => 'Doctrine\\DBAL\\Event\\Listeners\\MysqlSessionInit',
            'doctrine.dbal.events.oracle_session_init.class' => 'Doctrine\\DBAL\\Event\\Listeners\\OracleSessionInit',
            'doctrine.class' => 'Doctrine\\Bundle\\DoctrineBundle\\Registry',
            'doctrine.entity_managers' => array(
                'default' => 'doctrine.orm.default_entity_manager',
                'his' => 'doctrine.orm.his_entity_manager',
                'entry' => 'doctrine.orm.entry_entity_manager',
                'share' => 'doctrine.orm.share_entity_manager',
                'outside' => 'doctrine.orm.outside_entity_manager',
                'ip_blocker' => 'doctrine.orm.ip_blocker_entity_manager',
            ),
            'doctrine.default_entity_manager' => 'default',
            'doctrine.dbal.connection_factory.types' => array(

            ),
            'doctrine.connections' => array(
                'default' => 'doctrine.dbal.default_connection',
                'his' => 'doctrine.dbal.his_connection',
                'entry' => 'doctrine.dbal.entry_connection',
                'share' => 'doctrine.dbal.share_connection',
                'outside' => 'doctrine.dbal.outside_connection',
                'ip_blocker' => 'doctrine.dbal.ip_blocker_connection',
            ),
            'doctrine.default_connection' => 'default',
            'doctrine.orm.configuration.class' => 'Doctrine\\ORM\\Configuration',
            'doctrine.orm.entity_manager.class' => 'Doctrine\\ORM\\EntityManager',
            'doctrine.orm.manager_configurator.class' => 'Doctrine\\Bundle\\DoctrineBundle\\ManagerConfigurator',
            'doctrine.orm.cache.array.class' => 'Doctrine\\Common\\Cache\\ArrayCache',
            'doctrine.orm.cache.apc.class' => 'Doctrine\\Common\\Cache\\ApcCache',
            'doctrine.orm.cache.memcache.class' => 'Doctrine\\Common\\Cache\\MemcacheCache',
            'doctrine.orm.cache.memcache_host' => 'localhost',
            'doctrine.orm.cache.memcache_port' => 11211,
            'doctrine.orm.cache.memcache_instance.class' => 'Memcache',
            'doctrine.orm.cache.memcached.class' => 'Doctrine\\Common\\Cache\\MemcachedCache',
            'doctrine.orm.cache.memcached_host' => 'localhost',
            'doctrine.orm.cache.memcached_port' => 11211,
            'doctrine.orm.cache.memcached_instance.class' => 'Memcached',
            'doctrine.orm.cache.redis.class' => 'Doctrine\\Common\\Cache\\RedisCache',
            'doctrine.orm.cache.redis_host' => 'localhost',
            'doctrine.orm.cache.redis_port' => 6379,
            'doctrine.orm.cache.redis_instance.class' => 'Redis',
            'doctrine.orm.cache.xcache.class' => 'Doctrine\\Common\\Cache\\XcacheCache',
            'doctrine.orm.cache.wincache.class' => 'Doctrine\\Common\\Cache\\WinCacheCache',
            'doctrine.orm.cache.zenddata.class' => 'Doctrine\\Common\\Cache\\ZendDataCache',
            'doctrine.orm.metadata.driver_chain.class' => 'Doctrine\\Common\\Persistence\\Mapping\\Driver\\MappingDriverChain',
            'doctrine.orm.metadata.annotation.class' => 'Doctrine\\ORM\\Mapping\\Driver\\AnnotationDriver',
            'doctrine.orm.metadata.xml.class' => 'Doctrine\\ORM\\Mapping\\Driver\\SimplifiedXmlDriver',
            'doctrine.orm.metadata.yml.class' => 'Doctrine\\ORM\\Mapping\\Driver\\SimplifiedYamlDriver',
            'doctrine.orm.metadata.php.class' => 'Doctrine\\ORM\\Mapping\\Driver\\PHPDriver',
            'doctrine.orm.metadata.staticphp.class' => 'Doctrine\\ORM\\Mapping\\Driver\\StaticPHPDriver',
            'doctrine.orm.proxy_cache_warmer.class' => 'Symfony\\Bridge\\Doctrine\\CacheWarmer\\ProxyCacheWarmer',
            'form.type_guesser.doctrine.class' => 'Symfony\\Bridge\\Doctrine\\Form\\DoctrineOrmTypeGuesser',
            'doctrine.orm.validator.unique.class' => 'Symfony\\Bridge\\Doctrine\\Validator\\Constraints\\UniqueEntityValidator',
            'doctrine.orm.validator_initializer.class' => 'Symfony\\Bridge\\Doctrine\\Validator\\DoctrineInitializer',
            'doctrine.orm.security.user.provider.class' => 'Symfony\\Bridge\\Doctrine\\Security\\User\\EntityUserProvider',
            'doctrine.orm.listeners.resolve_target_entity.class' => 'Doctrine\\ORM\\Tools\\ResolveTargetEntityListener',
            'doctrine.orm.listeners.attach_entity_listeners.class' => 'Doctrine\\ORM\\Tools\\AttachEntityListenersListener',
            'doctrine.orm.naming_strategy.default.class' => 'Doctrine\\ORM\\Mapping\\DefaultNamingStrategy',
            'doctrine.orm.naming_strategy.underscore.class' => 'Doctrine\\ORM\\Mapping\\UnderscoreNamingStrategy',
            'doctrine.orm.quote_strategy.default.class' => 'Doctrine\\ORM\\Mapping\\DefaultQuoteStrategy',
            'doctrine.orm.quote_strategy.ansi.class' => 'Doctrine\\ORM\\Mapping\\AnsiQuoteStrategy',
            'doctrine.orm.entity_listener_resolver.class' => 'Doctrine\\Bundle\\DoctrineBundle\\Mapping\\ContainerAwareEntityListenerResolver',
            'doctrine.orm.second_level_cache.default_cache_factory.class' => 'Doctrine\\ORM\\Cache\\DefaultCacheFactory',
            'doctrine.orm.second_level_cache.default_region.class' => 'Doctrine\\ORM\\Cache\\Region\\DefaultRegion',
            'doctrine.orm.second_level_cache.filelock_region.class' => 'Doctrine\\ORM\\Cache\\Region\\FileLockRegion',
            'doctrine.orm.second_level_cache.logger_chain.class' => 'Doctrine\\ORM\\Cache\\Logging\\CacheLoggerChain',
            'doctrine.orm.second_level_cache.logger_statistics.class' => 'Doctrine\\ORM\\Cache\\Logging\\StatisticsCacheLogger',
            'doctrine.orm.second_level_cache.cache_configuration.class' => 'Doctrine\\ORM\\Cache\\CacheConfiguration',
            'doctrine.orm.second_level_cache.regions_configuration.class' => 'Doctrine\\ORM\\Cache\\RegionsConfiguration',
            'doctrine.orm.auto_generate_proxy_classes' => false,
            'doctrine.orm.proxy_dir' => (__DIR__.'/doctrine/orm/Proxies'),
            'doctrine.orm.proxy_namespace' => 'Proxies',
            'doctrine_migrations.dir_name' => ($this->targetDirs[2].'/DoctrineMigrations'),
            'doctrine_migrations.namespace' => 'Application\\Migrations',
            'doctrine_migrations.table_name' => 'migration_versions',
            'doctrine_migrations.name' => 'Application Migrations',
            'doctrine_migrations.organize_migrations' => false,
            'sensio_framework_extra.view.guesser.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\Templating\\TemplateGuesser',
            'sensio_framework_extra.controller.listener.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\ControllerListener',
            'sensio_framework_extra.routing.loader.annot_dir.class' => 'Symfony\\Component\\Routing\\Loader\\AnnotationDirectoryLoader',
            'sensio_framework_extra.routing.loader.annot_file.class' => 'Symfony\\Component\\Routing\\Loader\\AnnotationFileLoader',
            'sensio_framework_extra.routing.loader.annot_class.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\Routing\\AnnotatedRouteControllerLoader',
            'sensio_framework_extra.converter.listener.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\ParamConverterListener',
            'sensio_framework_extra.converter.manager.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\Request\\ParamConverter\\ParamConverterManager',
            'sensio_framework_extra.converter.doctrine.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\Request\\ParamConverter\\DoctrineParamConverter',
            'sensio_framework_extra.converter.datetime.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\Request\\ParamConverter\\DateTimeParamConverter',
            'sensio_framework_extra.view.listener.class' => 'Sensio\\Bundle\\FrameworkExtraBundle\\EventListener\\TemplateListener',
            'fos_js_routing.extractor.class' => 'FOS\\JsRoutingBundle\\Extractor\\ExposedRoutesExtractor',
            'fos_js_routing.controller.class' => 'FOS\\JsRoutingBundle\\Controller\\Controller',
            'fos_js_routing.cache_control' => array(
                'enabled' => false,
            ),
            'templating.helper.markdown.class' => 'Knp\\Bundle\\MarkdownBundle\\Helper\\MarkdownHelper',
            'snc_redis.client.class' => 'Predis\\Client',
            'snc_redis.client_options.class' => 'Predis\\Option\\ClientOptions',
            'snc_redis.connection_parameters.class' => 'Predis\\Connection\\ConnectionParameters',
            'snc_redis.connection_factory.class' => 'Snc\\RedisBundle\\Client\\Predis\\Connection\\ConnectionFactory',
            'snc_redis.connection_wrapper.class' => 'Snc\\RedisBundle\\Client\\Predis\\Connection\\ConnectionWrapper',
            'snc_redis.phpredis_client.class' => 'Redis',
            'snc_redis.phpredis_connection_wrapper.class' => 'Snc\\RedisBundle\\Client\\Phpredis\\Client',
            'snc_redis.logger.class' => 'Snc\\RedisBundle\\Logger\\RedisLogger',
            'snc_redis.data_collector.class' => 'Snc\\RedisBundle\\DataCollector\\RedisDataCollector',
            'snc_redis.doctrine_cache.class' => 'Snc\\RedisBundle\\Doctrine\\Cache\\RedisCache',
            'snc_redis.monolog_handler.class' => 'Snc\\RedisBundle\\Monolog\\Handler\\RedisHandler',
            'snc_redis.swiftmailer_spool.class' => 'Snc\\RedisBundle\\SwiftMailer\\RedisSpool',
            'console.command.ids' => array(

            ),
        );
    }
}

class DoctrineORMEntityManager_00000000780113e3000000003d9a623965be85e65b22cd674e76ec56af8fd0f6 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolder5b60375481c0f764589170 = null;
    private $initializer5b60375481c15424719038 = null;
    private static $publicProperties5b60375481bf8730260624 = array(
        
    );
    public function getConnection()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getConnection', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getConnection();
    }
    public function getMetadataFactory()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getMetadataFactory', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getMetadataFactory();
    }
    public function getExpressionBuilder()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getExpressionBuilder', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getExpressionBuilder();
    }
    public function beginTransaction()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'beginTransaction', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->beginTransaction();
    }
    public function transactional($func)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'transactional', array('func' => $func), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->transactional($func);
    }
    public function commit()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'commit', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->commit();
    }
    public function rollback()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'rollback', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->rollback();
    }
    public function getClassMetadata($className)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getClassMetadata', array('className' => $className), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getClassMetadata($className);
    }
    public function createQuery($dql = '')
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'createQuery', array('dql' => $dql), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->createQuery($dql);
    }
    public function createNamedQuery($name)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'createNamedQuery', array('name' => $name), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->createNamedQuery($name);
    }
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->createNativeQuery($sql, $rsm);
    }
    public function createNamedNativeQuery($name)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->createNamedNativeQuery($name);
    }
    public function createQueryBuilder()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'createQueryBuilder', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->createQueryBuilder();
    }
    public function flush($entity = null)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'flush', array('entity' => $entity), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->flush($entity);
    }
    public function find($entityName, $id, $lockMode = 0, $lockVersion = null)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'find', array('entityName' => $entityName, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->find($entityName, $id, $lockMode, $lockVersion);
    }
    public function getReference($entityName, $id)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getReference($entityName, $id);
    }
    public function getPartialReference($entityName, $identifier)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getPartialReference($entityName, $identifier);
    }
    public function clear($entityName = null)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'clear', array('entityName' => $entityName), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->clear($entityName);
    }
    public function close()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'close', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->close();
    }
    public function persist($entity)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'persist', array('entity' => $entity), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->persist($entity);
    }
    public function remove($entity)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'remove', array('entity' => $entity), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->remove($entity);
    }
    public function refresh($entity)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'refresh', array('entity' => $entity), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->refresh($entity);
    }
    public function detach($entity)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'detach', array('entity' => $entity), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->detach($entity);
    }
    public function merge($entity)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'merge', array('entity' => $entity), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->merge($entity);
    }
    public function copy($entity, $deep = false)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->copy($entity, $deep);
    }
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->lock($entity, $lockMode, $lockVersion);
    }
    public function getRepository($entityName)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getRepository', array('entityName' => $entityName), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getRepository($entityName);
    }
    public function contains($entity)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'contains', array('entity' => $entity), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->contains($entity);
    }
    public function getEventManager()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getEventManager', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getEventManager();
    }
    public function getConfiguration()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getConfiguration', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getConfiguration();
    }
    public function isOpen()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'isOpen', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->isOpen();
    }
    public function getUnitOfWork()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getUnitOfWork', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getUnitOfWork();
    }
    public function getHydrator($hydrationMode)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getHydrator($hydrationMode);
    }
    public function newHydrator($hydrationMode)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->newHydrator($hydrationMode);
    }
    public function getProxyFactory()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getProxyFactory', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getProxyFactory();
    }
    public function initializeObject($obj)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'initializeObject', array('obj' => $obj), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->initializeObject($obj);
    }
    public function getFilters()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'getFilters', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->getFilters();
    }
    public function isFiltersStateClean()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'isFiltersStateClean', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->isFiltersStateClean();
    }
    public function hasFilters()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'hasFilters', array(), $this->initializer5b60375481c15424719038);
        return $this->valueHolder5b60375481c0f764589170->hasFilters();
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?: $reflection = new \ReflectionClass(__CLASS__);
        $instance = (new \ReflectionClass(get_class()))->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);
        $instance->initializer5b60375481c15424719038 = $initializer;
        return $instance;
    }
    protected function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager)
    {
        static $reflection;
        if (! $this->valueHolder5b60375481c0f764589170) {
            $reflection = $reflection ?: new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolder5b60375481c0f764589170 = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
        }
        $this->valueHolder5b60375481c0f764589170->__construct($conn, $config, $eventManager);
    }
    public function & __get($name)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, '__get', ['name' => $name], $this->initializer5b60375481c15424719038);
        if (isset(self::$publicProperties5b60375481bf8730260624[$name])) {
            return $this->valueHolder5b60375481c0f764589170->$name;
        }
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375481c0f764589170;
            $backtrace = debug_backtrace(false);
            trigger_error('Undefined property: ' . get_parent_class($this) . '::$' . $name . ' in ' . $backtrace[0]['file'] . ' on line ' . $backtrace[0]['line'], \E_USER_NOTICE);
            return $targetObject->$name;
            return;
        }
        $targetObject = $this->valueHolder5b60375481c0f764589170;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __set($name, $value)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, '__set', array('name' => $name, 'value' => $value), $this->initializer5b60375481c15424719038);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375481c0f764589170;
            return $targetObject->$name = $value;
            return;
        }
        $targetObject = $this->valueHolder5b60375481c0f764589170;
        $accessor = function & () use ($targetObject, $name, $value) {
            return $targetObject->$name = $value;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __isset($name)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, '__isset', array('name' => $name), $this->initializer5b60375481c15424719038);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375481c0f764589170;
            return isset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b60375481c0f764589170;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __unset($name)
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, '__unset', array('name' => $name), $this->initializer5b60375481c15424719038);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375481c0f764589170;
            unset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b60375481c0f764589170;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __clone()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, '__clone', array(), $this->initializer5b60375481c15424719038);
        $this->valueHolder5b60375481c0f764589170 = clone $this->valueHolder5b60375481c0f764589170;
    }
    public function __sleep()
    {
        $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, '__sleep', array(), $this->initializer5b60375481c15424719038);
        return array('valueHolder5b60375481c0f764589170');
    }
    public function __wakeup()
    {
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
    }
    public function setProxyInitializer(\Closure $initializer = null)
    {
        $this->initializer5b60375481c15424719038 = $initializer;
    }
    public function getProxyInitializer()
    {
        return $this->initializer5b60375481c15424719038;
    }
    public function initializeProxy() : bool
    {
        return $this->initializer5b60375481c15424719038 && $this->initializer5b60375481c15424719038->__invoke($this->valueHolder5b60375481c0f764589170, $this, 'initializeProxy', array(), $this->initializer5b60375481c15424719038);
    }
    public function isProxyInitialized() : bool
    {
        return null !== $this->valueHolder5b60375481c0f764589170;
    }
    public function getWrappedValueHolderValue()
    {
        return $this->valueHolder5b60375481c0f764589170;
    }
}

class DoctrineORMEntityManager_00000000780113ef000000003d9a623965be85e65b22cd674e76ec56af8fd0f6 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolder5b60375485767645976053 = null;
    private $initializer5b6037548576e605850093 = null;
    private static $publicProperties5b60375485745439274520 = array(
        
    );
    public function getConnection()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getConnection', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getConnection();
    }
    public function getMetadataFactory()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getMetadataFactory', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getMetadataFactory();
    }
    public function getExpressionBuilder()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getExpressionBuilder', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getExpressionBuilder();
    }
    public function beginTransaction()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'beginTransaction', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->beginTransaction();
    }
    public function transactional($func)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'transactional', array('func' => $func), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->transactional($func);
    }
    public function commit()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'commit', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->commit();
    }
    public function rollback()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'rollback', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->rollback();
    }
    public function getClassMetadata($className)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getClassMetadata', array('className' => $className), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getClassMetadata($className);
    }
    public function createQuery($dql = '')
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'createQuery', array('dql' => $dql), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->createQuery($dql);
    }
    public function createNamedQuery($name)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'createNamedQuery', array('name' => $name), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->createNamedQuery($name);
    }
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->createNativeQuery($sql, $rsm);
    }
    public function createNamedNativeQuery($name)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->createNamedNativeQuery($name);
    }
    public function createQueryBuilder()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'createQueryBuilder', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->createQueryBuilder();
    }
    public function flush($entity = null)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'flush', array('entity' => $entity), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->flush($entity);
    }
    public function find($entityName, $id, $lockMode = 0, $lockVersion = null)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'find', array('entityName' => $entityName, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->find($entityName, $id, $lockMode, $lockVersion);
    }
    public function getReference($entityName, $id)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getReference($entityName, $id);
    }
    public function getPartialReference($entityName, $identifier)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getPartialReference($entityName, $identifier);
    }
    public function clear($entityName = null)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'clear', array('entityName' => $entityName), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->clear($entityName);
    }
    public function close()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'close', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->close();
    }
    public function persist($entity)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'persist', array('entity' => $entity), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->persist($entity);
    }
    public function remove($entity)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'remove', array('entity' => $entity), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->remove($entity);
    }
    public function refresh($entity)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'refresh', array('entity' => $entity), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->refresh($entity);
    }
    public function detach($entity)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'detach', array('entity' => $entity), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->detach($entity);
    }
    public function merge($entity)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'merge', array('entity' => $entity), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->merge($entity);
    }
    public function copy($entity, $deep = false)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->copy($entity, $deep);
    }
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->lock($entity, $lockMode, $lockVersion);
    }
    public function getRepository($entityName)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getRepository', array('entityName' => $entityName), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getRepository($entityName);
    }
    public function contains($entity)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'contains', array('entity' => $entity), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->contains($entity);
    }
    public function getEventManager()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getEventManager', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getEventManager();
    }
    public function getConfiguration()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getConfiguration', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getConfiguration();
    }
    public function isOpen()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'isOpen', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->isOpen();
    }
    public function getUnitOfWork()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getUnitOfWork', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getUnitOfWork();
    }
    public function getHydrator($hydrationMode)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getHydrator($hydrationMode);
    }
    public function newHydrator($hydrationMode)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->newHydrator($hydrationMode);
    }
    public function getProxyFactory()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getProxyFactory', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getProxyFactory();
    }
    public function initializeObject($obj)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'initializeObject', array('obj' => $obj), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->initializeObject($obj);
    }
    public function getFilters()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'getFilters', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->getFilters();
    }
    public function isFiltersStateClean()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'isFiltersStateClean', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->isFiltersStateClean();
    }
    public function hasFilters()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'hasFilters', array(), $this->initializer5b6037548576e605850093);
        return $this->valueHolder5b60375485767645976053->hasFilters();
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?: $reflection = new \ReflectionClass(__CLASS__);
        $instance = (new \ReflectionClass(get_class()))->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);
        $instance->initializer5b6037548576e605850093 = $initializer;
        return $instance;
    }
    protected function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager)
    {
        static $reflection;
        if (! $this->valueHolder5b60375485767645976053) {
            $reflection = $reflection ?: new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolder5b60375485767645976053 = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
        }
        $this->valueHolder5b60375485767645976053->__construct($conn, $config, $eventManager);
    }
    public function & __get($name)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, '__get', ['name' => $name], $this->initializer5b6037548576e605850093);
        if (isset(self::$publicProperties5b60375485745439274520[$name])) {
            return $this->valueHolder5b60375485767645976053->$name;
        }
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375485767645976053;
            $backtrace = debug_backtrace(false);
            trigger_error('Undefined property: ' . get_parent_class($this) . '::$' . $name . ' in ' . $backtrace[0]['file'] . ' on line ' . $backtrace[0]['line'], \E_USER_NOTICE);
            return $targetObject->$name;
            return;
        }
        $targetObject = $this->valueHolder5b60375485767645976053;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __set($name, $value)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, '__set', array('name' => $name, 'value' => $value), $this->initializer5b6037548576e605850093);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375485767645976053;
            return $targetObject->$name = $value;
            return;
        }
        $targetObject = $this->valueHolder5b60375485767645976053;
        $accessor = function & () use ($targetObject, $name, $value) {
            return $targetObject->$name = $value;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __isset($name)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, '__isset', array('name' => $name), $this->initializer5b6037548576e605850093);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375485767645976053;
            return isset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b60375485767645976053;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __unset($name)
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, '__unset', array('name' => $name), $this->initializer5b6037548576e605850093);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375485767645976053;
            unset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b60375485767645976053;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __clone()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, '__clone', array(), $this->initializer5b6037548576e605850093);
        $this->valueHolder5b60375485767645976053 = clone $this->valueHolder5b60375485767645976053;
    }
    public function __sleep()
    {
        $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, '__sleep', array(), $this->initializer5b6037548576e605850093);
        return array('valueHolder5b60375485767645976053');
    }
    public function __wakeup()
    {
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
    }
    public function setProxyInitializer(\Closure $initializer = null)
    {
        $this->initializer5b6037548576e605850093 = $initializer;
    }
    public function getProxyInitializer()
    {
        return $this->initializer5b6037548576e605850093;
    }
    public function initializeProxy() : bool
    {
        return $this->initializer5b6037548576e605850093 && $this->initializer5b6037548576e605850093->__invoke($this->valueHolder5b60375485767645976053, $this, 'initializeProxy', array(), $this->initializer5b6037548576e605850093);
    }
    public function isProxyInitialized() : bool
    {
        return null !== $this->valueHolder5b60375485767645976053;
    }
    public function getWrappedValueHolderValue()
    {
        return $this->valueHolder5b60375485767645976053;
    }
}

class DoctrineORMEntityManager_00000000780113e9000000003d9a623965be85e65b22cd674e76ec56af8fd0f6 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolder5b60375488b3d736606502 = null;
    private $initializer5b60375488b42436441657 = null;
    private static $publicProperties5b60375488b26317381469 = array(
        
    );
    public function getConnection()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getConnection', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getConnection();
    }
    public function getMetadataFactory()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getMetadataFactory', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getMetadataFactory();
    }
    public function getExpressionBuilder()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getExpressionBuilder', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getExpressionBuilder();
    }
    public function beginTransaction()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'beginTransaction', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->beginTransaction();
    }
    public function transactional($func)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'transactional', array('func' => $func), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->transactional($func);
    }
    public function commit()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'commit', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->commit();
    }
    public function rollback()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'rollback', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->rollback();
    }
    public function getClassMetadata($className)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getClassMetadata', array('className' => $className), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getClassMetadata($className);
    }
    public function createQuery($dql = '')
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'createQuery', array('dql' => $dql), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->createQuery($dql);
    }
    public function createNamedQuery($name)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'createNamedQuery', array('name' => $name), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->createNamedQuery($name);
    }
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->createNativeQuery($sql, $rsm);
    }
    public function createNamedNativeQuery($name)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->createNamedNativeQuery($name);
    }
    public function createQueryBuilder()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'createQueryBuilder', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->createQueryBuilder();
    }
    public function flush($entity = null)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'flush', array('entity' => $entity), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->flush($entity);
    }
    public function find($entityName, $id, $lockMode = 0, $lockVersion = null)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'find', array('entityName' => $entityName, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->find($entityName, $id, $lockMode, $lockVersion);
    }
    public function getReference($entityName, $id)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getReference($entityName, $id);
    }
    public function getPartialReference($entityName, $identifier)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getPartialReference($entityName, $identifier);
    }
    public function clear($entityName = null)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'clear', array('entityName' => $entityName), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->clear($entityName);
    }
    public function close()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'close', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->close();
    }
    public function persist($entity)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'persist', array('entity' => $entity), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->persist($entity);
    }
    public function remove($entity)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'remove', array('entity' => $entity), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->remove($entity);
    }
    public function refresh($entity)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'refresh', array('entity' => $entity), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->refresh($entity);
    }
    public function detach($entity)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'detach', array('entity' => $entity), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->detach($entity);
    }
    public function merge($entity)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'merge', array('entity' => $entity), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->merge($entity);
    }
    public function copy($entity, $deep = false)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->copy($entity, $deep);
    }
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->lock($entity, $lockMode, $lockVersion);
    }
    public function getRepository($entityName)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getRepository', array('entityName' => $entityName), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getRepository($entityName);
    }
    public function contains($entity)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'contains', array('entity' => $entity), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->contains($entity);
    }
    public function getEventManager()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getEventManager', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getEventManager();
    }
    public function getConfiguration()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getConfiguration', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getConfiguration();
    }
    public function isOpen()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'isOpen', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->isOpen();
    }
    public function getUnitOfWork()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getUnitOfWork', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getUnitOfWork();
    }
    public function getHydrator($hydrationMode)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getHydrator($hydrationMode);
    }
    public function newHydrator($hydrationMode)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->newHydrator($hydrationMode);
    }
    public function getProxyFactory()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getProxyFactory', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getProxyFactory();
    }
    public function initializeObject($obj)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'initializeObject', array('obj' => $obj), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->initializeObject($obj);
    }
    public function getFilters()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'getFilters', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->getFilters();
    }
    public function isFiltersStateClean()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'isFiltersStateClean', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->isFiltersStateClean();
    }
    public function hasFilters()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'hasFilters', array(), $this->initializer5b60375488b42436441657);
        return $this->valueHolder5b60375488b3d736606502->hasFilters();
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?: $reflection = new \ReflectionClass(__CLASS__);
        $instance = (new \ReflectionClass(get_class()))->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);
        $instance->initializer5b60375488b42436441657 = $initializer;
        return $instance;
    }
    protected function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager)
    {
        static $reflection;
        if (! $this->valueHolder5b60375488b3d736606502) {
            $reflection = $reflection ?: new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolder5b60375488b3d736606502 = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
        }
        $this->valueHolder5b60375488b3d736606502->__construct($conn, $config, $eventManager);
    }
    public function & __get($name)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, '__get', ['name' => $name], $this->initializer5b60375488b42436441657);
        if (isset(self::$publicProperties5b60375488b26317381469[$name])) {
            return $this->valueHolder5b60375488b3d736606502->$name;
        }
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375488b3d736606502;
            $backtrace = debug_backtrace(false);
            trigger_error('Undefined property: ' . get_parent_class($this) . '::$' . $name . ' in ' . $backtrace[0]['file'] . ' on line ' . $backtrace[0]['line'], \E_USER_NOTICE);
            return $targetObject->$name;
            return;
        }
        $targetObject = $this->valueHolder5b60375488b3d736606502;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __set($name, $value)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, '__set', array('name' => $name, 'value' => $value), $this->initializer5b60375488b42436441657);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375488b3d736606502;
            return $targetObject->$name = $value;
            return;
        }
        $targetObject = $this->valueHolder5b60375488b3d736606502;
        $accessor = function & () use ($targetObject, $name, $value) {
            return $targetObject->$name = $value;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __isset($name)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, '__isset', array('name' => $name), $this->initializer5b60375488b42436441657);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375488b3d736606502;
            return isset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b60375488b3d736606502;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __unset($name)
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, '__unset', array('name' => $name), $this->initializer5b60375488b42436441657);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b60375488b3d736606502;
            unset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b60375488b3d736606502;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __clone()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, '__clone', array(), $this->initializer5b60375488b42436441657);
        $this->valueHolder5b60375488b3d736606502 = clone $this->valueHolder5b60375488b3d736606502;
    }
    public function __sleep()
    {
        $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, '__sleep', array(), $this->initializer5b60375488b42436441657);
        return array('valueHolder5b60375488b3d736606502');
    }
    public function __wakeup()
    {
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
    }
    public function setProxyInitializer(\Closure $initializer = null)
    {
        $this->initializer5b60375488b42436441657 = $initializer;
    }
    public function getProxyInitializer()
    {
        return $this->initializer5b60375488b42436441657;
    }
    public function initializeProxy() : bool
    {
        return $this->initializer5b60375488b42436441657 && $this->initializer5b60375488b42436441657->__invoke($this->valueHolder5b60375488b3d736606502, $this, 'initializeProxy', array(), $this->initializer5b60375488b42436441657);
    }
    public function isProxyInitialized() : bool
    {
        return null !== $this->valueHolder5b60375488b3d736606502;
    }
    public function getWrappedValueHolderValue()
    {
        return $this->valueHolder5b60375488b3d736606502;
    }
}

class DoctrineORMEntityManager_0000000078011013000000003d9a623965be85e65b22cd674e76ec56af8fd0f6 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolder5b6037548c0c9783505945 = null;
    private $initializer5b6037548c0ce491586880 = null;
    private static $publicProperties5b6037548c0b4045870003 = array(
        
    );
    public function getConnection()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getConnection', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getConnection();
    }
    public function getMetadataFactory()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getMetadataFactory', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getMetadataFactory();
    }
    public function getExpressionBuilder()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getExpressionBuilder', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getExpressionBuilder();
    }
    public function beginTransaction()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'beginTransaction', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->beginTransaction();
    }
    public function transactional($func)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'transactional', array('func' => $func), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->transactional($func);
    }
    public function commit()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'commit', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->commit();
    }
    public function rollback()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'rollback', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->rollback();
    }
    public function getClassMetadata($className)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getClassMetadata', array('className' => $className), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getClassMetadata($className);
    }
    public function createQuery($dql = '')
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'createQuery', array('dql' => $dql), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->createQuery($dql);
    }
    public function createNamedQuery($name)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'createNamedQuery', array('name' => $name), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->createNamedQuery($name);
    }
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->createNativeQuery($sql, $rsm);
    }
    public function createNamedNativeQuery($name)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->createNamedNativeQuery($name);
    }
    public function createQueryBuilder()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'createQueryBuilder', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->createQueryBuilder();
    }
    public function flush($entity = null)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'flush', array('entity' => $entity), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->flush($entity);
    }
    public function find($entityName, $id, $lockMode = 0, $lockVersion = null)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'find', array('entityName' => $entityName, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->find($entityName, $id, $lockMode, $lockVersion);
    }
    public function getReference($entityName, $id)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getReference($entityName, $id);
    }
    public function getPartialReference($entityName, $identifier)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getPartialReference($entityName, $identifier);
    }
    public function clear($entityName = null)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'clear', array('entityName' => $entityName), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->clear($entityName);
    }
    public function close()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'close', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->close();
    }
    public function persist($entity)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'persist', array('entity' => $entity), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->persist($entity);
    }
    public function remove($entity)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'remove', array('entity' => $entity), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->remove($entity);
    }
    public function refresh($entity)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'refresh', array('entity' => $entity), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->refresh($entity);
    }
    public function detach($entity)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'detach', array('entity' => $entity), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->detach($entity);
    }
    public function merge($entity)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'merge', array('entity' => $entity), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->merge($entity);
    }
    public function copy($entity, $deep = false)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->copy($entity, $deep);
    }
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->lock($entity, $lockMode, $lockVersion);
    }
    public function getRepository($entityName)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getRepository', array('entityName' => $entityName), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getRepository($entityName);
    }
    public function contains($entity)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'contains', array('entity' => $entity), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->contains($entity);
    }
    public function getEventManager()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getEventManager', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getEventManager();
    }
    public function getConfiguration()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getConfiguration', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getConfiguration();
    }
    public function isOpen()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'isOpen', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->isOpen();
    }
    public function getUnitOfWork()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getUnitOfWork', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getUnitOfWork();
    }
    public function getHydrator($hydrationMode)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getHydrator($hydrationMode);
    }
    public function newHydrator($hydrationMode)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->newHydrator($hydrationMode);
    }
    public function getProxyFactory()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getProxyFactory', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getProxyFactory();
    }
    public function initializeObject($obj)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'initializeObject', array('obj' => $obj), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->initializeObject($obj);
    }
    public function getFilters()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'getFilters', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->getFilters();
    }
    public function isFiltersStateClean()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'isFiltersStateClean', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->isFiltersStateClean();
    }
    public function hasFilters()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'hasFilters', array(), $this->initializer5b6037548c0ce491586880);
        return $this->valueHolder5b6037548c0c9783505945->hasFilters();
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?: $reflection = new \ReflectionClass(__CLASS__);
        $instance = (new \ReflectionClass(get_class()))->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);
        $instance->initializer5b6037548c0ce491586880 = $initializer;
        return $instance;
    }
    protected function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager)
    {
        static $reflection;
        if (! $this->valueHolder5b6037548c0c9783505945) {
            $reflection = $reflection ?: new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolder5b6037548c0c9783505945 = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
        }
        $this->valueHolder5b6037548c0c9783505945->__construct($conn, $config, $eventManager);
    }
    public function & __get($name)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, '__get', ['name' => $name], $this->initializer5b6037548c0ce491586880);
        if (isset(self::$publicProperties5b6037548c0b4045870003[$name])) {
            return $this->valueHolder5b6037548c0c9783505945->$name;
        }
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b6037548c0c9783505945;
            $backtrace = debug_backtrace(false);
            trigger_error('Undefined property: ' . get_parent_class($this) . '::$' . $name . ' in ' . $backtrace[0]['file'] . ' on line ' . $backtrace[0]['line'], \E_USER_NOTICE);
            return $targetObject->$name;
            return;
        }
        $targetObject = $this->valueHolder5b6037548c0c9783505945;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __set($name, $value)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, '__set', array('name' => $name, 'value' => $value), $this->initializer5b6037548c0ce491586880);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b6037548c0c9783505945;
            return $targetObject->$name = $value;
            return;
        }
        $targetObject = $this->valueHolder5b6037548c0c9783505945;
        $accessor = function & () use ($targetObject, $name, $value) {
            return $targetObject->$name = $value;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __isset($name)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, '__isset', array('name' => $name), $this->initializer5b6037548c0ce491586880);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b6037548c0c9783505945;
            return isset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b6037548c0c9783505945;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __unset($name)
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, '__unset', array('name' => $name), $this->initializer5b6037548c0ce491586880);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b6037548c0c9783505945;
            unset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b6037548c0c9783505945;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __clone()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, '__clone', array(), $this->initializer5b6037548c0ce491586880);
        $this->valueHolder5b6037548c0c9783505945 = clone $this->valueHolder5b6037548c0c9783505945;
    }
    public function __sleep()
    {
        $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, '__sleep', array(), $this->initializer5b6037548c0ce491586880);
        return array('valueHolder5b6037548c0c9783505945');
    }
    public function __wakeup()
    {
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
    }
    public function setProxyInitializer(\Closure $initializer = null)
    {
        $this->initializer5b6037548c0ce491586880 = $initializer;
    }
    public function getProxyInitializer()
    {
        return $this->initializer5b6037548c0ce491586880;
    }
    public function initializeProxy() : bool
    {
        return $this->initializer5b6037548c0ce491586880 && $this->initializer5b6037548c0ce491586880->__invoke($this->valueHolder5b6037548c0c9783505945, $this, 'initializeProxy', array(), $this->initializer5b6037548c0ce491586880);
    }
    public function isProxyInitialized() : bool
    {
        return null !== $this->valueHolder5b6037548c0c9783505945;
    }
    public function getWrappedValueHolderValue()
    {
        return $this->valueHolder5b6037548c0c9783505945;
    }
}

class DoctrineORMEntityManager_000000007801101d000000003d9a623965be85e65b22cd674e76ec56af8fd0f6 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolder5b6037548f477768129421 = null;
    private $initializer5b6037548f480454799879 = null;
    private static $publicProperties5b6037548f461055259474 = array(
        
    );
    public function getConnection()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getConnection', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getConnection();
    }
    public function getMetadataFactory()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getMetadataFactory', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getMetadataFactory();
    }
    public function getExpressionBuilder()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getExpressionBuilder', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getExpressionBuilder();
    }
    public function beginTransaction()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'beginTransaction', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->beginTransaction();
    }
    public function transactional($func)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'transactional', array('func' => $func), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->transactional($func);
    }
    public function commit()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'commit', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->commit();
    }
    public function rollback()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'rollback', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->rollback();
    }
    public function getClassMetadata($className)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getClassMetadata', array('className' => $className), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getClassMetadata($className);
    }
    public function createQuery($dql = '')
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'createQuery', array('dql' => $dql), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->createQuery($dql);
    }
    public function createNamedQuery($name)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'createNamedQuery', array('name' => $name), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->createNamedQuery($name);
    }
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->createNativeQuery($sql, $rsm);
    }
    public function createNamedNativeQuery($name)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->createNamedNativeQuery($name);
    }
    public function createQueryBuilder()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'createQueryBuilder', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->createQueryBuilder();
    }
    public function flush($entity = null)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'flush', array('entity' => $entity), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->flush($entity);
    }
    public function find($entityName, $id, $lockMode = 0, $lockVersion = null)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'find', array('entityName' => $entityName, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->find($entityName, $id, $lockMode, $lockVersion);
    }
    public function getReference($entityName, $id)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getReference($entityName, $id);
    }
    public function getPartialReference($entityName, $identifier)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getPartialReference($entityName, $identifier);
    }
    public function clear($entityName = null)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'clear', array('entityName' => $entityName), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->clear($entityName);
    }
    public function close()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'close', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->close();
    }
    public function persist($entity)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'persist', array('entity' => $entity), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->persist($entity);
    }
    public function remove($entity)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'remove', array('entity' => $entity), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->remove($entity);
    }
    public function refresh($entity)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'refresh', array('entity' => $entity), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->refresh($entity);
    }
    public function detach($entity)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'detach', array('entity' => $entity), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->detach($entity);
    }
    public function merge($entity)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'merge', array('entity' => $entity), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->merge($entity);
    }
    public function copy($entity, $deep = false)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->copy($entity, $deep);
    }
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->lock($entity, $lockMode, $lockVersion);
    }
    public function getRepository($entityName)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getRepository', array('entityName' => $entityName), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getRepository($entityName);
    }
    public function contains($entity)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'contains', array('entity' => $entity), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->contains($entity);
    }
    public function getEventManager()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getEventManager', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getEventManager();
    }
    public function getConfiguration()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getConfiguration', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getConfiguration();
    }
    public function isOpen()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'isOpen', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->isOpen();
    }
    public function getUnitOfWork()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getUnitOfWork', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getUnitOfWork();
    }
    public function getHydrator($hydrationMode)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getHydrator($hydrationMode);
    }
    public function newHydrator($hydrationMode)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->newHydrator($hydrationMode);
    }
    public function getProxyFactory()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getProxyFactory', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getProxyFactory();
    }
    public function initializeObject($obj)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'initializeObject', array('obj' => $obj), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->initializeObject($obj);
    }
    public function getFilters()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'getFilters', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->getFilters();
    }
    public function isFiltersStateClean()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'isFiltersStateClean', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->isFiltersStateClean();
    }
    public function hasFilters()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'hasFilters', array(), $this->initializer5b6037548f480454799879);
        return $this->valueHolder5b6037548f477768129421->hasFilters();
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?: $reflection = new \ReflectionClass(__CLASS__);
        $instance = (new \ReflectionClass(get_class()))->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);
        $instance->initializer5b6037548f480454799879 = $initializer;
        return $instance;
    }
    protected function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager)
    {
        static $reflection;
        if (! $this->valueHolder5b6037548f477768129421) {
            $reflection = $reflection ?: new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolder5b6037548f477768129421 = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
        }
        $this->valueHolder5b6037548f477768129421->__construct($conn, $config, $eventManager);
    }
    public function & __get($name)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, '__get', ['name' => $name], $this->initializer5b6037548f480454799879);
        if (isset(self::$publicProperties5b6037548f461055259474[$name])) {
            return $this->valueHolder5b6037548f477768129421->$name;
        }
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b6037548f477768129421;
            $backtrace = debug_backtrace(false);
            trigger_error('Undefined property: ' . get_parent_class($this) . '::$' . $name . ' in ' . $backtrace[0]['file'] . ' on line ' . $backtrace[0]['line'], \E_USER_NOTICE);
            return $targetObject->$name;
            return;
        }
        $targetObject = $this->valueHolder5b6037548f477768129421;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __set($name, $value)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, '__set', array('name' => $name, 'value' => $value), $this->initializer5b6037548f480454799879);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b6037548f477768129421;
            return $targetObject->$name = $value;
            return;
        }
        $targetObject = $this->valueHolder5b6037548f477768129421;
        $accessor = function & () use ($targetObject, $name, $value) {
            return $targetObject->$name = $value;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __isset($name)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, '__isset', array('name' => $name), $this->initializer5b6037548f480454799879);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b6037548f477768129421;
            return isset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b6037548f477768129421;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __unset($name)
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, '__unset', array('name' => $name), $this->initializer5b6037548f480454799879);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b6037548f477768129421;
            unset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b6037548f477768129421;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __clone()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, '__clone', array(), $this->initializer5b6037548f480454799879);
        $this->valueHolder5b6037548f477768129421 = clone $this->valueHolder5b6037548f477768129421;
    }
    public function __sleep()
    {
        $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, '__sleep', array(), $this->initializer5b6037548f480454799879);
        return array('valueHolder5b6037548f477768129421');
    }
    public function __wakeup()
    {
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
    }
    public function setProxyInitializer(\Closure $initializer = null)
    {
        $this->initializer5b6037548f480454799879 = $initializer;
    }
    public function getProxyInitializer()
    {
        return $this->initializer5b6037548f480454799879;
    }
    public function initializeProxy() : bool
    {
        return $this->initializer5b6037548f480454799879 && $this->initializer5b6037548f480454799879->__invoke($this->valueHolder5b6037548f477768129421, $this, 'initializeProxy', array(), $this->initializer5b6037548f480454799879);
    }
    public function isProxyInitialized() : bool
    {
        return null !== $this->valueHolder5b6037548f477768129421;
    }
    public function getWrappedValueHolderValue()
    {
        return $this->valueHolder5b6037548f477768129421;
    }
}

class DoctrineORMEntityManager_0000000078011007000000003d9a623965be85e65b22cd674e76ec56af8fd0f6 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolder5b603754928b9123030969 = null;
    private $initializer5b603754928be678412270 = null;
    private static $publicProperties5b603754928a3835202143 = array(
        
    );
    public function getConnection()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getConnection', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getConnection();
    }
    public function getMetadataFactory()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getMetadataFactory', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getMetadataFactory();
    }
    public function getExpressionBuilder()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getExpressionBuilder', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getExpressionBuilder();
    }
    public function beginTransaction()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'beginTransaction', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->beginTransaction();
    }
    public function transactional($func)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'transactional', array('func' => $func), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->transactional($func);
    }
    public function commit()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'commit', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->commit();
    }
    public function rollback()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'rollback', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->rollback();
    }
    public function getClassMetadata($className)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getClassMetadata', array('className' => $className), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getClassMetadata($className);
    }
    public function createQuery($dql = '')
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'createQuery', array('dql' => $dql), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->createQuery($dql);
    }
    public function createNamedQuery($name)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'createNamedQuery', array('name' => $name), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->createNamedQuery($name);
    }
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->createNativeQuery($sql, $rsm);
    }
    public function createNamedNativeQuery($name)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->createNamedNativeQuery($name);
    }
    public function createQueryBuilder()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'createQueryBuilder', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->createQueryBuilder();
    }
    public function flush($entity = null)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'flush', array('entity' => $entity), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->flush($entity);
    }
    public function find($entityName, $id, $lockMode = 0, $lockVersion = null)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'find', array('entityName' => $entityName, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->find($entityName, $id, $lockMode, $lockVersion);
    }
    public function getReference($entityName, $id)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getReference($entityName, $id);
    }
    public function getPartialReference($entityName, $identifier)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getPartialReference($entityName, $identifier);
    }
    public function clear($entityName = null)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'clear', array('entityName' => $entityName), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->clear($entityName);
    }
    public function close()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'close', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->close();
    }
    public function persist($entity)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'persist', array('entity' => $entity), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->persist($entity);
    }
    public function remove($entity)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'remove', array('entity' => $entity), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->remove($entity);
    }
    public function refresh($entity)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'refresh', array('entity' => $entity), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->refresh($entity);
    }
    public function detach($entity)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'detach', array('entity' => $entity), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->detach($entity);
    }
    public function merge($entity)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'merge', array('entity' => $entity), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->merge($entity);
    }
    public function copy($entity, $deep = false)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->copy($entity, $deep);
    }
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->lock($entity, $lockMode, $lockVersion);
    }
    public function getRepository($entityName)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getRepository', array('entityName' => $entityName), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getRepository($entityName);
    }
    public function contains($entity)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'contains', array('entity' => $entity), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->contains($entity);
    }
    public function getEventManager()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getEventManager', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getEventManager();
    }
    public function getConfiguration()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getConfiguration', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getConfiguration();
    }
    public function isOpen()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'isOpen', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->isOpen();
    }
    public function getUnitOfWork()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getUnitOfWork', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getUnitOfWork();
    }
    public function getHydrator($hydrationMode)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getHydrator($hydrationMode);
    }
    public function newHydrator($hydrationMode)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->newHydrator($hydrationMode);
    }
    public function getProxyFactory()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getProxyFactory', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getProxyFactory();
    }
    public function initializeObject($obj)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'initializeObject', array('obj' => $obj), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->initializeObject($obj);
    }
    public function getFilters()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'getFilters', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->getFilters();
    }
    public function isFiltersStateClean()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'isFiltersStateClean', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->isFiltersStateClean();
    }
    public function hasFilters()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'hasFilters', array(), $this->initializer5b603754928be678412270);
        return $this->valueHolder5b603754928b9123030969->hasFilters();
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?: $reflection = new \ReflectionClass(__CLASS__);
        $instance = (new \ReflectionClass(get_class()))->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);
        $instance->initializer5b603754928be678412270 = $initializer;
        return $instance;
    }
    protected function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager)
    {
        static $reflection;
        if (! $this->valueHolder5b603754928b9123030969) {
            $reflection = $reflection ?: new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolder5b603754928b9123030969 = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
        }
        $this->valueHolder5b603754928b9123030969->__construct($conn, $config, $eventManager);
    }
    public function & __get($name)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, '__get', ['name' => $name], $this->initializer5b603754928be678412270);
        if (isset(self::$publicProperties5b603754928a3835202143[$name])) {
            return $this->valueHolder5b603754928b9123030969->$name;
        }
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b603754928b9123030969;
            $backtrace = debug_backtrace(false);
            trigger_error('Undefined property: ' . get_parent_class($this) . '::$' . $name . ' in ' . $backtrace[0]['file'] . ' on line ' . $backtrace[0]['line'], \E_USER_NOTICE);
            return $targetObject->$name;
            return;
        }
        $targetObject = $this->valueHolder5b603754928b9123030969;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __set($name, $value)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, '__set', array('name' => $name, 'value' => $value), $this->initializer5b603754928be678412270);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b603754928b9123030969;
            return $targetObject->$name = $value;
            return;
        }
        $targetObject = $this->valueHolder5b603754928b9123030969;
        $accessor = function & () use ($targetObject, $name, $value) {
            return $targetObject->$name = $value;
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __isset($name)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, '__isset', array('name' => $name), $this->initializer5b603754928be678412270);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b603754928b9123030969;
            return isset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b603754928b9123030969;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __unset($name)
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, '__unset', array('name' => $name), $this->initializer5b603754928be678412270);
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder5b603754928b9123030969;
            unset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolder5b603754928b9123030969;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);
        };
            $backtrace = debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __clone()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, '__clone', array(), $this->initializer5b603754928be678412270);
        $this->valueHolder5b603754928b9123030969 = clone $this->valueHolder5b603754928b9123030969;
    }
    public function __sleep()
    {
        $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, '__sleep', array(), $this->initializer5b603754928be678412270);
        return array('valueHolder5b603754928b9123030969');
    }
    public function __wakeup()
    {
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
    }
    public function setProxyInitializer(\Closure $initializer = null)
    {
        $this->initializer5b603754928be678412270 = $initializer;
    }
    public function getProxyInitializer()
    {
        return $this->initializer5b603754928be678412270;
    }
    public function initializeProxy() : bool
    {
        return $this->initializer5b603754928be678412270 && $this->initializer5b603754928be678412270->__invoke($this->valueHolder5b603754928b9123030969, $this, 'initializeProxy', array(), $this->initializer5b603754928be678412270);
    }
    public function isProxyInitialized() : bool
    {
        return null !== $this->valueHolder5b603754928b9123030969;
    }
    public function getWrappedValueHolderValue()
    {
        return $this->valueHolder5b603754928b9123030969;
    }
}
