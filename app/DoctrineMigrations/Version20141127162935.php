<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141127162935 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("INSERT IGNORE INTO `payment_gateway` (`id`, `code`, `name`, `post_url`, `auto_reop`, `reop_url`, `verify_url`, `verify_ip`, `version`, `support`, `label`, `bind_ip`) VALUES
        (1, 'YEEPAY', '易寶', 'https://business.yeepay.com/app-merchant-proxy/node', 1, 'http://business.yeepay.com/app-merchant-proxy/command', '', '', 1, 1, 'YeePay', 1),
        (4, '99BILL', '快錢', 'https://www.99bill.com/gateway/recvMerchantInfoAction.htm', 0, 'https://www.99bill.com/apipay/services/gatewayOrderQuery?wsdl', '', '', 1, 1, 'Bill99', 0),
        (5, 'ALLINPAY', '通聯', 'https://service.allinpay.com/gateway/netPayment.do', 1, 'https://service.allinpay.com/gateway/query.do', '', '', 1, 1, 'Allinpay', 0),
        (6, 'CBPAY', '網銀在線', 'https://pay3.chinabank.com.cn/PayGate', 1, 'https://pay3.chinabank.com.cn/receiveorder.jsp', 'payment.https.pay3.chinabank.com.cn', '172.26.59.2', 1, 1, 'CBPay', 0),
        (8, 'IPS', '環迅', 'https://pay.ips.com.cn/ipayment.aspx', 1, 'http://webservice.ips.com.cn/Sinopay/Standard/IpsCheckTrade.asmx/GetOrderByNo', '', '', 1, 1, 'IPS', 0),
        (12, 'YEETPAY', '支付通', 'http://www.yeetpay.com/pay/gateway.asp', 0, 'http://www.yeetpay.com/pay/getorder.asp', '', '', 1, 1, 'YeetPay', 0),
        (16, 'PayEase', '首信易', 'https://pay.beijing.com.cn/prs/user_payment.checkit', 1, 'http://210.73.90.18/merchant/order/order_ack_oid_list.jsp', '', '', 1, 1, 'PayEase', 0),
        (18, 'alipay', '支付寶', 'https://www.alipay.com/cooperate/gateway.do?_input_charset=utf-8', 0, '', '', '', 1, 1, 'AliPay', 0),
        (21, 'shengpay', '盛付通', 'http://netpay.sdo.com/paygate/ibankpay.aspx', 1, 'http://settle.netpay.sdo.com/orders.asmx', '', '', 1, 1, 'Shengpay', 0),
        (22, 'smartpay', '捷銀', 'https://www.172.com/paymentGateway/transactionGateway.htm', 1, 'https://www.172.com/paymentGateway/queryMerchantOrder.htm', '', '', 1, 1, 'Smartpay', 0),
        (23, 'NEW-IPS', '新環迅', 'https://payment.ips.com.cn/receiver.aspx', 1, 'http://webservice.ips.com.cn/Sinopay/Standard/IpsCheckTrade.asmx/GetOrderByNo', '', '', 1, 1, 'NewIPS', 0),
        (24, 'NEW-smartpay', '新捷銀', 'https://www.172.com/paymentGateway/payGateway.htm', 1, 'https://www.172.com/paymentGateway/queryMerchantOrder.htm', '', '', 1, 1, 'NewSmartpay', 0),
        (27, 'hnapay', '新生支付', 'https://www.hnapay.com/website/pay.htm', 1, 'https://www.hnapay.com/website/queryOrderResult.htm', '', '', 1, 1, 'HnaPay', 0),
        (31, 'Baokimpay', '寶金', 'payment.http.sandbox.baokim.vn/services/payment_pro_2/init?wsdl', 0, '', 'www.baokim.vn', '172.26.59.2', 1, 1, 'Baokimpay', 0),
        (32, 'cloudpay', '雲服務', 'https://cloud1.semanticweb.cn/diy/saveApp', 0, '', '', '', 1, 1, 'CloudPay', 0),
        (33, 'Tenpay', '財付通', 'https://www.tenpay.com/cgi-bin/v1.0/pay_gate.cgi', 1, 'http://mch.tenpay.com/cgi-bin/cfbi_query_order_v3.cgi', '', '', 1, 1, 'Tenpay', 0),
        (34, 'gofpay', '國付寶', 'https://www.gopay.com.cn/PGServer/Trans/WebClientAction.do', 0, '', 'www.gopay.com.cn', '172.26.59.2', 1, 1, 'Gofpay', 0),
        (36, 'PK989', '戰神支付', 'http://live800.me/autopay/index1.php', 0, '', '', '', 1, 1, 'PK989', 0),
        (37, 'PK989_S', '戰神神速入款', 'http://live800.me/autopay/index2.php', 0, '', '', '', 1, 1, 'PK989S', 0),
        (38, 'NEW-jftpay', '(新)聚付通', 'http://do.jftpay.com/chargebank.aspx', 0, '', 'do.jftpay.net', '172.26.59.2', 1, 1, 'NewJFT', 0),
        (39, 'dinpay', '快匯寶', 'https://payment.dinpay.com/PHPReceiveMerchantAction.do', 1, 'https://payment.dinpay.com/PHPMQueryOrder.do', '', '', 1, 1, 'DinPay', 0),
        (40, 'Pay99', '99寶付', 'https://pay.dajinju.com/iTrans.aspx', 0, '', '', '', 1, 1, 'BaoFoo99', 0),
        (41, 'baofoo', '寶付', 'http://paygate.baofoo.com/PayReceive/payindex.aspx', 0, '', '', '', 1, 1, 'BaoFoo', 1),
        (42, 'betwinpay', '永利博通聯', 'https://2handwj.com/payment/spay.do', 0, '', '', '', 1, 1, 'Betwinpay', 0),
        (45, 'easecard', '易票聯', 'https://www.easecard.com/paycenter/v2.0/getoi.do', 1, 'https://www.epaylinks.cn/paycenter/queryOrder.do', '', '', 1, 1, 'EPayLinks', 0),
        (47, 'CardToPay', 'CardToPay', 'https://pay.cardtopay.com/ebuyweb/payment/payment.do', 0, '', '', '', 1, 1, 'CardToPay', 0),
        (48, 'Fuiou', '富友支付', 'https://pay.fuiou.com/smpGate.do', 0, '', '', '', 1, 1, 'Fuiou', 0),
        (49, 'u2bet', '優博金流', 'http://www.yobo66.info/ubpop/api/topay.php', 0, '', '', '', 1, 1, 'U2bet', 0),
        (50, 'easypay', '易生支付', 'https://cashier.bhecard.com/portal', 0, '', '', '', 1, 1, 'EasyPay', 0),
        (51, 'p28567', '譽訊支付', 'http://www.28567.com/GateWay/pay.asp', 0, '', '', '', 1, 1, 'P28567', 0),
        (52, 'kltong', '開聯通', 'http://www.315d.com:9180/busias/PayRequestE', 1, 'http://www.315d.com:9180/busics/MerQuery', '', '', 1, 1, 'KLTong', 0),
        (53, 'Vpay', 'V付通', 'http://pay.vftpay.com/pay/gateway.asp', 0, '', '', '', 1, 1, 'VPay', 0),
        (54, '95epay', '95epay', 'https://www.95epay.cn/sslpayment', 0, '', '', '', 1, 1, 'EPay95', 0),
        (55, 'cldinpay', '雲服務-快匯寶', 'https://cloud1.semanticweb.cn/diy/saveApp', 0, '', '', '', 1, 1, 'Cldinpay', 0),
        (56, 'jzplay', '金贊金流', 'http://olp.jinzan888.com/Online/jz/topay.php', 0, '', '', '', 1, 1, 'JZPlay', 0),
        (58, 'shunshou', '順手支付', 'http://pay.shunshou.com/bank_pay.do', 1, 'http://pay.shunshou.com/query_bank_order.do', '', '', 1, 1, 'ShunShou', 0),
        (59, 'weishih', '支付衛士', 'https://cloud1.semanticweb.cn/diy/saveApp', 0, '', '', '', 1, 1, 'Weishih', 0),
        (60, 'Lokpay', '樂匯支付', 'http://www.youke99.com/epay/pay.asp', 0, '', '', '', 1, 1, 'Lokpay', 0),
        (61, 'unipay', '中國銀聯', 'http://www.unipaygo.com/index.php/payapi/', 1, 'http://www.unipaygo.com/index.php/payapi/query_order', '', '', 1, 1, 'Unipay', 0),
        (62, 'cloudips', '雲服務-環訊', 'http://www.kuaifu88.com:8028/ipspay/payment.jsp', 0, '', '', '', 1, 1, 'CloudIps', 0),
        (63, 'rijietong', '日結通', 'http://pay.bilumaca.net/api/', 0, '', '', '', 1, 1, 'Rijietong', 0),
        (64, 'Newdinpay', '新快匯寶', 'https://pay.dinpay.com/gateway?input_charset=UTF-8', 1, 'https://query.dinpay.com/query', '', '', 1, 1, 'NewDinPay', 0),
        (65, 'weishih2', '支付衛士二代', 'https://cloud1.semanticweb.cn/diy/demo/message.jsp', 0, '', 'cloud1.semanticweb.cn', '172.26.59.2', 1, 1, 'WeishihII', 0),
        (67, '99baofoo2', '99寶付二代', 'https://paygate.baofoo.com/PayReceive/bankpay.aspx', 1, 'https://paygate.baofoo.com/Check/OrderQuery.aspx', '', '', 1, 1, 'BooFooII99', 0),
        (68, 'CJBBank', '快捷寶', 'http://www.kjb88.com/bankinterface/pay', 1, 'http://www.kjb88.com/bankinterface/queryOrd', '', '', 1, 1, 'CJBBank', 0),
        (69, 'XinYang', '信易貸', 'http://www.xinyang518.com:9000/hrpay-1.0.0/gateway/payment', 0, '', '', '', 1, 1, 'XinYang', 1),
        (70, 'NewBaoFoo', '新寶付', 'http://gw.baofoo.com/payindex', 0, '', '', '', 1, 1, 'NewBaoFoo', 0),
        (71, 'Ecpss', '匯潮', 'https://pay.ecpss.com/sslpayment', 0, 'https://merchant.ecpss.cn/merchantBatchQueryAPI', '', '', 1, 1, 'Ecpss', 0),
        (72, 'NewKJBBank', '新快捷寶', 'http://www.kjb99.com/bankinterface/pay', 1, 'http://www.kjb99.com/bankinterface/queryOrd', '', '', 1, 1, 'NewKJBBank', 0),
        (74, 'LLPay', '連連支付', 'https://yintong.com.cn/payment/bankgateway.htm', 0, 'https://yintong.com.cn/traderapi/orderquery.htm', '', '', 1, 1, 'LLPay', 0),
        (75, 'Reapal', '融寶支付', 'http://epay.reapal.com/portal?charset=utf-8', 1, 'http://interface.reapal.com/query/payment', 'interface.reapal.com', '172.26.59.2', 1, 1, 'Reapal', 0),
        (76, 'Ylkpay', '銀支付', 'http://www.ylkpay.com/GateWay/ReceiveBank.aspx', 0, '', '', '', 1, 1, 'Ylkpay', 0),
        (77, 'KuaiYin', '快銀支付', 'http://payment.kuaiyinpay.com/Payment', 1, 'http://payment.kuaiyinpay.com/kuaiyinAPI/inquiryOrder/merchantOrderId', '', '', 1, 1, 'KuaiYin', 0),
        (78, 'UIPAS', 'UIPAS', 'https://api.uipas.com/cashier/deposit', 1, 'https://api.uipas.com/apiv2/index/wsdl', 'api.uipas.com/apiv2/index/wsdl', '172.26.59.2', 1, 1, 'UIPAS', 1),
        (79, 'NganLuong', 'NganLuong', 'https://www.nganluong.vn/micro_checkout_api.php?wsdl', 1, '', 'www.nganluong.vn/micro_checkout_api.php?wsdl', '172.26.59.2', 1, 1, 'NganLuong', 1),
        (80, 'Neteller', 'Neteller', '', 0, '', 'api.neteller.com', '172.26.59.2', 1, 1, 'Neteller', 0),
        (81, 'UCFPay', '先鋒支付', 'https://mapi.ucfpay.com/gateway.do', 1, 'https://mapi.ucfpay.com/gateway.do', 'payment.https.mapi.ucfpay.com', '172.26.59.2', 1, 1, 'UCFPay', 0),
        (82, 'Paypal', 'Paypal', 'https://www.paypal.com/cgi-bin/webscr', 0, '', 'payment.https.api-3t.paypal.com', '172.26.59.2', 1, 1, 'Paypal', 0),
        (83, 'Payza', 'Payza', 'https://secure.payza.com/checkout', 0, '', '', '', 1, 1, 'Payza', 1),
        (85, 'Ehking', '易匯金', 'https://ehkpay.ehking.com/gateway/controller.action', 1, 'https://ehkpay.ehking.com/gateway/controller.action', '', '', 1, 1, 'Ehking', 1)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DELETE FROM `payment_gateway` WHERE `id` IN ('1', '4', '5', '6', '8', '12', '16', '18', '21', '22', '23', '24', '27', '31', '32', '33', '34', '36', '37', '38', '39', '40', '41', '42', '45', '47', '48', '49', '50', '51', '52', '53', '54', '55', '56', '58', '59', '60', '61', '62', '63', '64', '65', '67', '68', '69', '70', '71', '72', '74', '75', '76', '77', '78', '79', '80', '81', '82', '83', '85')");
    }
}
