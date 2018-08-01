<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試產生復原明細語法
 */
class RecoverEntryFromPostLogCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentDepositWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryOperatorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeDataForTotalCalculate',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDataForTotalCalculate',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeTransferEntryDataForTotalCalculate',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryOperatorData'
        ];
        $this->loadFixtures($classnames);

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'];

        $this->loadFixtures($classnames, 'entry');
        $this->loadFixtures($classnames, 'his');
    }

    /**
     * 測試產生復原明細語法
     */
    public function testRecoverEntryFromPostLog()
    {
        $fileDir = $this->getContainer()->get('kernel')->getRootDir();
        $input = $fileDir . "/../log.txt";
        $cashOutput = $fileDir . "/../cashSqlOutput.sql";
        $output = $fileDir . "/../sqlOutput.sql";

        $log = [
            '[172.26.57.51] out: [2018-03-08 19:08:03] LOGGER.INFO: ipl-sk51.rd5.prod 172.17.15.111 "PUT /api/user/95090966/cash/op" "REQUEST: opcode=10002&amount=-10&ref_id=111195160083019438&auto_commit=1"',
            '[172.26.57.52] out: [2018-02-07 16:10:03] LOGGER.INFO: ipl-sk52.rd5.prod 172.17.15.114 "PUT /api/user/4/cash/op" "REQUEST: opcode=1002&amount=-4&operator=cathy&memo=11 1 1&ref_id=216&auto_commit=1" "RESPONSE: result=ok&ret[entry][id]=100&ret[entry][merchant_id]=0&ret[entry][remit_account_id]=0&ret[entry][domain]=3819866&ret[entry][cash_id]=3&ret[entry][user_id]=4&ret[entry][currency]=CNY&ret[entry][opcode]=1002&ret[entry][created_at]=2018-02-07T16:10:03+0800&ret[entry][amount]=-4&ret[entry][memo]=11 1 1&ret[entry][ref_id]=216&ret[entry][balance]=0.905&ret[entry][operator][entry_id]=100&ret[entry][operator][username]=cathy&ret[entry][tag]=&ret[entry][cash_version]=878&ret[cash][id]=3&ret[cash][user_id]=4&ret[cash][balance]=0.905&ret[cash][pre_sub]=0&ret[cash][pre_add]=0&ret[cash][currency]=CNY" [] []',
            '[172.26.53.11] out: [2018-04-13 05:10:06] LOGGER.INFO: ipl-web11.rd5.prod 172.26.54.27 "PUT /api/user/512280/cash/op" "REQUEST: opcode=1007&amount=-150&operator=&memo=&auto_commit=0&force_copy=1" "RESPONSE: result=ok&ret[entry][id]=50046122116&ret[entry][merchant_id]=0&ret[entry][remit_account_id]=0&ret[entry][domain]=75&ret[entry][cash_id]=1216750&ret[entry][user_id]=512280&ret[entry][currency]=CNY&ret[entry][opcode]=1007&ret[entry][created_at]=2018-04-13T05:10:06+0800&ret[entry][amount]=-150&ret[entry][memo]=&ret[entry][ref_id]=50046122116&ret[entry][checked]=0&ret[entry][tag]=&ret[cash][id]=1216750&ret[cash][user_id]=512280&ret[cash][balance]=151.63&ret[cash][pre_sub]=150&ret[cash][pre_add]=0&ret[cash][currency]=CNY" [] []',
            '[172.26.53.27] out: [2017-10-21 16:09:05] LOGGER.INFO: ipl-web27.rd5.prod 172.17.165.157 "PUT /api/user/8/cash_fake/op" "REQUEST: opcode=1043&amount=-10444&auto_commit=1&operator=cathy&ref_id=8751508573344736" "RESPONSE: ret[entries][0][id]=3&ret[entries][0][cash_fake_id]=7&ret[entries][0][user_id]=8&ret[entries][0][domain]=3820043&ret[entries][0][currency]=CNY&ret[entries][0][opcode]=1043&ret[entries][0][created_at]=2017-10-21T16:09:05+0800&ret[entries][0][amount]=-10444&ret[entries][0][memo]=&ret[entries][0][ref_id]=8751508573344736&ret[entries][0][balance]=0.5&ret[entries][0][operator][entry_id]=100&ret[entries][0][cash_fake_version]=4039&ret[cash_fake][id]=7&ret[cash_fake][user_id]=627&ret[cash_fake][balance]=0.5&ret[cash_fake][pre_sub]=0&ret[cash_fake][pre_add]=0&ret[cash_fake][currency]=CNY&ret[cash_fake][enable]=1&result=ok" [] []',
            '[172.26.53.27] out: [2017-10-21 16:09:05] LOGGER.INFO: ipl-web27.rd5.prod 172.17.165.157 "PUT /api/user/248571627/cash_fake/op" "REQUEST: opcode=1043&amount=-10444&auto_commit=1&ref_id=8751508573344736" "RESPONSE: result=error&code=150050028&msg=輸入金額超過許可的最大範圍" [] []',
            '[172.26.53.11] out: [2018-02-08 07:55:21] LOGGER.INFO: ipl-web11.rd5.prod 172.17.163.120 "PUT /api/user/8/cash_fake/op" "REQUEST: opcode=1003&amount=500&auto_commit=1&ref_id=220" "RESPONSE: ret[entries][0][id]=105&ret[entries][0][cash_fake_id]=7&ret[entries][0][user_id]=8&ret[entries][0][domain]=1&ret[entries][0][currency]=CNY&ret[entries][0][opcode]=1003&ret[entries][0][created_at]=2018-02-08T07:55:21+0800&ret[entries][0][amount]=500&ret[entries][0][memo]=&ret[entries][0][ref_id]=220&ret[entries][0][balance]=503&ret[entries][0][flow][whom]=dmoney2&ret[entries][0][flow][level]=2&ret[entries][0][flow][transfer_out]=0&ret[entries][0][cash_fake_version]=1611&ret[cash_fake][id]=7&ret[cash_fake][user_id]=8&ret[cash_fake][balance]=503&ret[cash_fake][pre_sub]=0&ret[cash_fake][pre_add]=0&ret[cash_fake][currency]=CNY&ret[cash_fake][enable]=1&result=ok" [] []',
            '[172.26.53.11] out: [2018-02-08 12:45:20] LOGGER.INFO: ipl-web11.rd5.prod 172.26.54.27 "PUT /api/cash/transaction/46814177701/commit" "REQUEST: " "RESPONSE: ret[entry][id]=46814177701&ret[entry][merchant_id]=0&ret[entry][remit_account_id]=0&ret[entry][domain]=3819084&ret[entry][cash_id]=151344318&ret[entry][user_id]=291666555&ret[entry][currency]=CNY&ret[entry][opcode]=1359&ret[entry][created_at]=2018-02-08T12:45:20+0800&ret[entry][amount]=-50&ret[entry][memo]=操作者：wcj888(公司入款?存)&ret[entry][ref_id]=46814177697&ret[entry][balance]=2.5411&ret[entry][operator][entry_id]=46814177701&ret[entry][operator][username]=wcj888&ret[entry][tag]=&ret[entry][cash_version]=11012&ret[cash][id]=151344318&ret[cash][user_id]=291666555" [] []',
            '[172.26.53.11] out: [2018-02-09 12:15:51] LOGGER.INFO: ipl-web11.rd5.prod 172.17.177.1 "PUT /api/cash_fake/transaction/111/commit" "REQUEST: user_id=8" "RESPONSE: ret[entry][id]=111&ret[entry][cash_fake_id]=7&ret[entry][user_id]=8&ret[entry][currency]=CNY&ret[entry][opcode]=1003&ret[entry][created_at]=2018-02-09T12:15:51+0800&ret[entry][amount]=-156&ret[entry][memo]=&ret[entry][ref_id]=221&ret[entry][balance]=529.05&ret[entry][domain]=2&ret[entry][operator]=[]&ret[entry][cash_fake_version]=34&ret[cash_fake][id]=7&ret[cash_fake][user_id]=8&ret[cash_fake][balance]=529.05&ret[cash_fake][currency]=CNY&ret[cash_fake][enable]=1&result=ok" [] []',
            '[172.26.53.19] out: [2017-09-28 22:08:07] LOGGER.INFO: ipl-web19.rd5.prod 172.17.188.11 "POST /api/user/135757367/order" "REQUEST: pay_way=cash&opcode=40020&amount=-410&card_amount=-20&auto_commit=1&ref_id=14664650866&memo=111398660" "RESPONSE: ret[cash][id]=77173269&ret[cash][user_id]=135757367&ret[cash][balance]=1285.0185&ret[cash][pre_sub]=0&ret[cash][pre_add]=0&ret[cash][currency]=CNY&ret[cash_entry][id]=39957484219&ret[cash_entry][merchant_id]=0&ret[cash_entry][remit_account_id]=0&ret[cash_entry][domain]=3819824&ret[cash_entry][cash_id]=77173269&ret[cash_entry][user_id]=135757367&ret[cash_entry][currency]=CNY&ret[cash_entry][opcode]=40020&ret[cash_entry][created_at]=2017-09-28T22:08:07+0800&ret[cash_entry][amount]=-410&ret[cash_entry][memo]=111398660&ret[cash_entry][ref_id]=14664650866&ret[cash_entry][tag]=&ret[cash_entry][cash_version]=23758&result=ok" [] []',
            '[172.26.53.27] out: [2017-10-10 21:50:15] LOGGER.INFO: ipl-web27.rd5.prod 172.17.188.11 "POST /api/user/8/order" "REQUEST: pay_way=cash_fake&opcode=40000&amount=-1&card_amount=-20&auto_commit=1&ref_id=227&memo=333" "RESPONSE: ret[cash_fake][id]=7&ret[cash_fake][user_id]=8&ret[cash_fake][balance]=2800.95&ret[cash_fake][pre_sub]=0&ret[cash_fake][pre_add]=0&ret[cash_fake][currency]=CNY&ret[cash_fake][enable]=1&ret[cash_fake_entry][0][id]=112&ret[cash_fake_entry][0][cash_fake_id]=7&ret[cash_fake_entry][0][user_id]=8&ret[cash_fake_entry][0][domain]=3819869&ret[cash_fake_entry][0][currency]=CNY&ret[cash_fake_entry][0][opcode]=40000&ret[cash_fake_entry][0][created_at]=2017-10-10T21:50:15+0800&ret[cash_fake_entry][0][amount]=-1&ret[cash_fake_entry][0][memo]=333&ret[cash_fake_entry][0][ref_id]=227&ret[cash_fake_entry][0][balance]=2800.95&ret[cash_fake_entry][0][cash_fake_version]=3&result=ok" [] []',
            '[172.26.53.11] out: [2018-02-08 04:30:23] LOGGER.INFO: ipl-web11.rd5.prod 172.26.56.13 "PUT /api/orders" "REQUEST: orders[0][user_id]=4&orders[0][amount]=200&orders[0][ref_id]=218&orders[0][opcode]=40001&orders[0][memo]=334&orders[0][auto_commit]=1&orders[0][pay_way]=cash&orders[1][user_id]=4&orders[1][amount]=10000&orders[1][ref_id]=219&orders[1][opcode]=40001&orders[1][memo]=334&orders[1][auto_commit]=1&orders[1][pay_way]=cash" "RESPONSE: 0[ret][cash][id]=3&0[ret][cash][user_id]=4&0[ret][cash][balance]=1.34&0[ret][cash][pre_sub]=0&0[ret][cash][pre_add]=0&0[ret][cash][currency]=CNY&0[ret][cash_entry][id]=101&0[ret][cash_entry][merchant_id]=0&0[ret][cash_entry][remit_account_id]=0&0[ret][cash_entry][domain]=3820149&0[ret][cash_entry][cash_id]=3&0[ret][cash_entry][user_id]=4&0[ret][cash_entry][currency]=CNY&0[ret][cash_entry][opcode]=40001&0[ret][cash_entry][created_at]=2018-02-08T04:30:23+0800&0[ret][cash_entry][amount]=200&0[ret][cash_entry][memo]=334&0[ret][cash_entry][ref_id]=218&0[ret][cash_entry][balance]=1.34&0[ret][cash_entry][tag]=&0[ret][cash_entry][cash_version]=823&0[result]=ok&1[ret][cash][id]=3&1[ret][cash][user_id]=4&1[ret][cash][balance]=9&1[ret][cash][pre_sub]=0&1[ret][cash][pre_add]=0&1[ret][cash][currency]=CNY&1[ret][cash_entry][id]=102&1[ret][cash_entry][merchant_id]=0&1[ret][cash_entry][remit_account_id]=0&1[ret][cash_entry][domain]=3817645&1[ret][cash_entry][cash_id]=3&1[ret][cash_entry][user_id]=4&1[ret][cash_entry][currency]=CNY&1[ret][cash_entry][opcode]=40001&1[ret][cash_entry][created_at]=2018-02-08T04:30:23+0800&1[ret][cash_entry][amount]=10000&1[ret][cash_entry][memo]=334&1[ret][cash_entry][ref_id]=219&1[ret][cash_entry][balance]=9&1[ret][cash_entry][tag]=&1[ret][cash_entry][cash_version]=571&1[result]=ok" [] []',
            '[172.26.53.11] out: [2018-02-08 04:30:23] LOGGER.INFO: ipl-web11.rd5.prod 172.26.56.13 "PUT /api/orders" "REQUEST: orders[0][user_id]=4&orders[0][amount]=2&orders[0][ref_id]=218&orders[0][opcode]=40001&orders[0][memo]=334&orders[0][auto_commit]=1&orders[0][pay_way]=cash&orders[1][user_id]=4&orders[1][amount]=10000&orders[1][ref_id]=219&orders[1][opcode]=40001&orders[1][memo]=334&orders[1][auto_commit]=1&orders[1][pay_way]=cash" "RESPONSE: 0[ret][cash][id]=3&0[ret][cash][user_id]=4&0[ret][cash][balance]=1.34&0[ret][cash][pre_sub]=0&0[ret][cash][pre_add]=0&0[ret][cash][currency]=CNY&0[ret][cash_entry][id]=103&0[ret][cash_entry][merchant_id]=0&0[ret][cash_entry][remit_account_id]=0&0[ret][cash_entry][domain]=3820149&0[ret][cash_entry][cash_id]=3&0[ret][cash_entry][user_id]=4&0[ret][cash_entry][currency]=CNY&0[ret][cash_entry][opcode]=40001&0[ret][cash_entry][created_at]=2018-02-08T04:30:23+0800&0[ret][cash_entry][amount]=2&0[ret][cash_entry][memo]=334&0[ret][cash_entry][ref_id]=218&0[ret][cash_entry][balance]=1.34&0[ret][cash_entry][tag]=&0[ret][cash_entry][cash_version]=23&0[result]=ok&1[ret][cash][id]=3&1[ret][cash][user_id]=4&1[ret][cash][balance]=9&1[ret][cash][pre_sub]=0&1[ret][cash][pre_add]=0&1[ret][cash][currency]=CNY&1[ret][cash_entry][id]=104&1[ret][cash_entry][merchant_id]=0&1[ret][cash_entry][remit_account_id]=0&1[ret][cash_entry][domain]=3817645&1[ret][cash_entry][cash_id]=3&1[ret][cash_entry][user_id]=4&1[ret][cash_entry][currency]=CNY&1[ret][cash_entry][opcode]=40001[2018-02-09 01:27:07] LOGGER.INFO: ipl-web11.rd5.prod 172.17.161.31 "POST /api/user/260036116/remit" "REQUEST: ancestor_id=74194354&order_number=2018020902878114&name_real=桂玉?&amount=10000&method=1&bank_info_id=10&account_id=89710&abandon_discount=1&identity_card=&cellphone=&transfer_code=&deposit_at=2018-02-09 01:26:00&discount=0.00&other_discount=80.00&atm_terminal_code=&trade_number=&payer_card=&memo=&branch=" "RESPONSE: result=ok&ret[id]=244734529&ret[remit_account_id]=89710&ret[domain]=3820026&ret[user_id]=260036116&ret[created_at]=2018-02-09T01:27:07+0800&ret[order_number]=2018020902878114&ret[abandon_discount]=1&ret[auto_confirm]=0&ret[auto_remit_id]=0&ret[method]=1&ret[status]=0&ret[level_id]=13282&ret[duration]=0&ret[ancestor_id]=74194354&ret[bank_info_id]=10&ret[amount_entry_id]=0&ret[discount_entry_id]=0&ret[other_discount_entry_id]=0&ret[amount]=10000&ret[discount]=0.00&ret[other_discount]=80.00&ret[actual_other_discount]=0&ret[rate]=1&ret[deposit_at]=2018-02-09T01:26:00+0800&ret[trade_number]=&ret[transfer_code]=&ret[atm_terminal_code]=&ret[identity_card]=&ret[old_order_number]=&ret[cellphone]=&ret[username]=djy88&ret[payer_card]=&ret[operator]=&ret[name_real]=桂玉?&ret[branch]=&ret[memo]=" [] []',
            '[172.26.53.11] out: [2018-02-08 03:53:06] LOGGER.INFO: ipl-web11.rd5.prod 172.26.56.13 "PUT /api/orders" "REQUEST: orders[0][user_id]=4&orders[0][amount]=0&orders[0][ref_id]=16433194499&orders[0][opcode]=40038&orders[0][memo]=118104092&orders[0][auto_commit]=1&orders[0][pay_way]=cash" "RESPONSE: 0[result]=error&0[code]=150140022&0[msg]=輸入金額超過許可的最大範圍" [] []',
            '[172.26.53.11] out: [2018-02-08 03:53:06] LOGGER.INFO: ipl-web11.rd5.prod 172.26.56.13 "PUT /api/orders" "REQUEST: orders[0][user_id]=231592180&orders[0][amount]=0&orders[0][ref_id]=16033190970&orders[0][opcode]=00038&orders[0][memo]=118100092&orders[0][auto_commit]=1&orders[0][pay_way]=cash_fake" "RESPONSE: 0[ret][cash_fake][id]=77815016&0[ret][cash_fake][user_id]=231592180&0[ret][cash_fake][balance]=0.1&0[ret][cash_fake][pre_sub]=0&0[ret][cash_fake][pre_add]=0&0[ret][cash_fake][currency]=CNY&0[ret][cash_fake][enable]=1&0[ret][cash_fake_entry][0][id]=9581819012&0[ret][cash_fake_entry][0][cash_fake_id]=77815016&0[ret][cash_fake_entry][0][user_id]=231592180&0[ret][cash_fake_entry][0][domain]=3820076&0[ret][cash_fake_entry][0][currency]=CNY&0[ret][cash_fake_entry][0][opcode]=00038&0[ret][cash_fake_entry][0][created_at]=2018-02-08T03:53:06+0800&0[ret][cash_fake_entry][0][amount]=0&0[ret][cash_fake_entry][0][memo]=118100092&0[ret][cash_fake_entry][0][ref_id]=16033190970&0[ret][cash_fake_entry][0][cash_fake_version]=29616&0[result]=ok" [] []',
            '[172.26.57.51] out: [2018-02-10 03:16:50] LOGGER.INFO: ipl-sk51.rd5.prod 172.17.15.131 "PUT /api/user/280941800/multi_order_bunch" "REQUEST: od[0][am]=-10&od[0][ref]=131192856610217890&od_count=1&pay_way=cash&opcode=10002" "RESPONSE: ret[cash][id]=146281513&ret[cash][user_id]=280941800&ret[cash][balance]=4.13&ret[cash][pre_sub]=0&ret[cash][pre_add]=0&ret[cash][currency]=CNY&ret[cash_entry][0][id]=46895771589&ret[cash_entry][0][cash_id]=146281513&ret[cash_entry][0][merchant_id]=0&ret[cash_entry][0][remit_account_id]=0&ret[cash_entry][0][domain]=3820161&ret[cash_entry][0][user_id]=280941800&ret[cash_entry][0][currency]=CNY&ret[cash_entry][0][opcode]=10002&ret[cash_entry][0][created_at]=2018-02-10T03:16:50+0800&ret[cash_entry][0][amount]=-10&ret[cash_entry][0][memo]=&ret[cash_entry][0][ref_id]=131192856610217890&ret[cash_entry][0][balance]=69.13&ret[cash_entry][0][tag]=&ret[cash_entry][0][cash_version]=2215" [] []',
            '[172.26.53.15] out: [2018-02-06 08:35:35] LOGGER.INFO: ipl-web15.rd5.prod 172.26.56.13 "PUT /api/user/156441442/multi_order_bunch" "REQUEST: pay_way=cash_fake&opcode=10002&od_count=1&od[0][am]=-10" "RESPONSE: result=error&code=150050028&msg=輸入金額超過許可的最大範圍" [] []',
            '[172.26.53.15] out: [2018-02-06 08:35:35] LOGGER.INFO: ipl-web15.rd5.prod 172.26.56.13 "PUT /api/user/8/multi_order_bunch" "REQUEST: pay_way=cash_fake&opcode=10002&od_count=2&od[0][am]=-10&od[0][ref]=222&od[0][card]=-5&od[1][am]=-10&od[1][ref]=223&od[1][card]=-5" "RESPONSE: ret[card][id]=64&ret[card][user_id]=65&ret[card][enable]=1&ret[card][enable_num]=0&ret[card][balance]=47202&ret[card][last_balance]=128324&ret[card][percentage]=37&ret[card][opcode]=10002&ret[card_entry][0][id]=11&ret[card_entry][0][card_id]=64&ret[card_entry][0][user_id]=65&ret[card_entry][0][amount]=-5&ret[card_entry][0][balance]=47222&ret[card_entry][0][opcode]=10002&ret[card_entry][0][created_at]=2018-02-06T08:35:35+0800&ret[card_entry][0][ref_id]=222&ret[card_entry][0][operator]=aa831&ret[card_entry][0][card_version]=316826&ret[card_entry][1][id]=12&ret[card_entry][1][card_id]=64&ret[card_entry][1][user_id]=65&ret[card_entry][1][amount]=-5&ret[card_entry][1][balance]=47217&ret[card_entry][1][opcode]=10002&ret[card_entry][1][created_at]=2018-02-06T08:35:35+0800&ret[card_entry][1][ref_id]=223&ret[card_entry][1][operator]=aa831&ret[card_entry][1][card_version]=316827&ret[cash_fake][id]=7&ret[cash_fake][user_id]=8&ret[cash_fake][balance]=2308.4&ret[cash_fake][pre_sub]=0&ret[cash_fake][pre_add]=0&ret[cash_fake][currency]=CNY&ret[cash_fake][enable]=1&ret[cash_fake_entry][0][id]=107&ret[cash_fake_entry][0][cash_fake_id]=7&ret[cash_fake_entry][0][user_id]=8&ret[cash_fake_entry][0][domain]=5&ret[cash_fake_entry][0][currency]=CNY&ret[cash_fake_entry][0][opcode]=10002&ret[cash_fake_entry][0][created_at]=2018-02-06T08:35:35+0800&ret[cash_fake_entry][0][amount]=-10&ret[cash_fake_entry][0][memo]=&ret[cash_fake_entry][0][ref_id]=222&ret[cash_fake_entry][0][balance]=2348.4&ret[cash_fake_entry][0][cash_fake_version]=665&ret[cash_fake_entry][1][id]=108&ret[cash_fake_entry][1][cash_fake_id]=7&ret[cash_fake_entry][1][user_id]=8&ret[cash_fake_entry][1][domain]=5&ret[cash_fake_entry][1][currency]=CNY&ret[cash_fake_entry][1][opcode]=10002&ret[cash_fake_entry][1][created_at]=2018-02-06T08:35:35+0800&ret[cash_fake_entry][1][amount]=-10&ret[cash_fake_entry][1][memo]=&ret[cash_fake_entry][1][ref_id]=223&ret[cash_fake_entry][1][balance]=2338.4&ret[cash_fake_entry][1][cash_fake_version]=666&result=ok" [] []',
        ];

        $handle = fopen($input, 'w');
        fwrite($handle, "$log[0]\n");
        fwrite($handle, "$log[1]\n");
        fwrite($handle, "$log[2]\n");
        fwrite($handle, "$log[3]\n");
        fwrite($handle, "$log[4]\n");
        fwrite($handle, "$log[5]\n");
        fwrite($handle, "$log[6]\n");
        fwrite($handle, "$log[7]\n");
        fwrite($handle, "$log[8]\n");
        fwrite($handle, "$log[9]\n");
        fwrite($handle, "$log[10]\n");
        fwrite($handle, "$log[11]\n");
        fwrite($handle, "$log[12]\n");
        fwrite($handle, "$log[13]\n");
        fwrite($handle, "$log[14]\n");
        fwrite($handle, "$log[15]\n");
        fwrite($handle, "$log[16]\n");
        fclose($handle);

        $params = ['--source' => $input];
        $checked = $this->runCommand('durian:recover-entry-from-post-log', $params);

        $out = explode(PHP_EOL, trim($checked));

        $msg = 'Operator of entry is not complete:';
        $this->assertEquals($msg, $out[0]);
        $this->assertEquals('    [entry_id] => 100', $out[3]);
        $msg = 'Entry of log is not complete:';
        $this->assertEquals($msg, $out[6]);
        $this->assertEquals($log[6], $out[7]);
        $this->assertEquals($msg, $out[10]);
        $this->assertEquals($log[11], $out[11]);
        $this->assertEquals($msg, $out[14]);
        $this->assertEquals($log[14], $out[15]);
        $msg = 'balance of Entry is not exist:';
        $this->assertEquals($msg, $out[8]);
        $this->assertEquals($log[8], $out[9]);
        $this->assertEquals($msg, $out[12]);
        $this->assertEquals($log[13], $out[13]);

        $ret = file_get_contents($cashOutput);
        $out = explode(PHP_EOL, trim($ret));

        $this->assertCount(4, $out);

        $cTable = 'INSERT INTO cash_entry VALUES ';
        $sql = "('100','20180207161003','3','4','156','1002','2018-02-07 16:10:03','-4','11 1 1','0.905','216','878');";
        $this->assertEquals($cTable . $sql, $out[0]);
        $sql = "('101','20180208043023','3','4','156','40001','2018-02-08 04:30:23','200','334','1.34','218','823');";
        $this->assertEquals($cTable . $sql, $out[1]);
        $sql = "('102','20180208043023','3','4','156','40001','2018-02-08 04:30:23','10000','334','9','219','571');";
        $this->assertEquals($cTable . $sql, $out[2]);
        $sql = "('103','20180208043023','3','4','156','40001','2018-02-08 04:30:23','2','334','1.34','218','23');";
        $this->assertEquals($cTable . $sql, $out[3]);

        $ret = file_get_contents($output);
        $out = explode(PHP_EOL, trim($ret));

        $this->assertCount(15, $out);

        $sql = "INSERT INTO payment_deposit_withdraw_entry VALUES " .
            "('100','20180207161003','0','0','3819866','4','216','156','1002','-4','0.905','11 1 1');";
        $this->assertEquals($sql, $out[0]);
        $sql = "INSERT INTO cash_entry_operator VALUES ('100','cathy');";
        $this->assertEquals($sql, $out[1]);
        $sql = "UPDATE payment_deposit_withdraw SET operator = 'cathy' WHERE id = '100');";
        $this->assertEquals($sql, $out[2]);
        $fTable = 'INSERT INTO cash_fake_entry VALUES ';
        $ftTable = 'INSERT INTO cash_fake_transfer_entry VALUES ';
        $sql = "餘額暫補 0，要查 queue log 才有辦法補這句明細: " . $fTable .
            "('104','20180208075521','6','7','156','1003','2018-02-08 07:55:21','-500','','0','220','0');";
        $this->assertEquals($sql, $out[3]);
        $sql = "('105','20180208075521','7','8','156','1003','2018-02-08 07:55:21','500','','503','220','1611');";
        $this->assertEquals($fTable . $sql, $out[4]);
        $sql = "餘額暫補 0，要查 queue log 才有辦法補這句明細: " . $ftTable .
            "('104','20180208075521','7','1','156','1003','2018-02-08 07:55:21','-500','0','220','');";
        $this->assertEquals($sql, $out[5]);
        $sql = "('105','20180208075521','8','1','156','1003','2018-02-08 07:55:21','500','503','220','');";
        $this->assertEquals($ftTable . $sql, $out[6]);
        $sql = "INSERT INTO cash_fake_entry_operator VALUES ('104','','1','tester','7');";
        $this->assertEquals($sql, $out[7]);
        $sql = "INSERT INTO cash_fake_entry_operator VALUES ('105','',0,'ztester',7);";
        $this->assertEquals($sql, $out[8]);
        $sql = "('111','20180209121551','7','8','156','1003','2018-02-09 12:15:51','-156','','529.05','221','34');";
        $this->assertEquals($fTable . $sql, $out[9]);
        $sql = "('111','20180209121551','8','2','156','1003','2018-02-09 12:15:51','-156','529.05','221','');";
        $this->assertEquals($ftTable . $sql, $out[10]);
        $sql = "INSERT INTO cash_fake_entry_operator VALUES ('111','',1,'ztester',7);";
        $this->assertEquals($sql, $out[11]);
        $sql = "('112','20171010215015','7','8','156','40000','2017-10-10 21:50:15','-1','333','2800.95','227','3');";
        $this->assertEquals($fTable . $sql, $out[12]);
        $sql = "('107','20180206083535','7','8','156','10002','2018-02-06 08:35:35','-10','','2348.4','222','665');";
        $this->assertEquals($fTable . $sql, $out[13]);
        $sql = "('108','20180206083535','7','8','156','10002','2018-02-06 08:35:35','-10','','2338.4','223','666');";
        $this->assertEquals($fTable . $sql, $out[14]);
    }

    public function tearDown()
    {
        parent::tearDown();

        $fileDir = $this->getContainer()->get('kernel')->getRootDir();
        $input = $fileDir . "/../log.txt";
        $cashOutput = $fileDir . "/../cashSqlOutput.sql";
        $output = $fileDir . "/../sqlOutput.sql";

        if (file_exists($input)) {
            unlink($input);
        }

        if (file_exists($cashOutput)) {
            unlink($cashOutput);
        }

        if (file_exists($output)) {
            unlink($output);
        }

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'recover-entry.log';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }
}
