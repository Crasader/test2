<?php

namespace BB\DurianBundle;

class StatOpcode
{
    /**
      * 現金入款opcode
      *
      * @var array
      */
    public static $cashDepositOpcode = [
        '1001',  //入款
        '1010',  //人工存入
        '1021',  //負數額度歸零
        '1023',  //其他
        '1036',  //公司入款
        '1039',  //線上入款
        '1040',  //線上入款手續費
        '1044',  //人工存入-體育投注-存入
        '1076',  //人工存入-AG視訊-存入
        '1087',  //DEPOSIT-MANUAL-IN_20 人工存入-PT-存入
        '1104',  //DEPOSIT-MANUAL-IN_22 人工存入-歐博視訊-存入
        '1112',  //DEPOSIT-MANUAL-IN_23 人工存入-MG電子-存入
        '1120',  //DEPOSIT-MANUAL-IN_24 人工存入-東方視訊-存入
        '1131',  //DEPOSIT-MANUAL-IN_25 人工存入-SB體育-存入
        '1148',  //DEPOSIT-MANUAL-IN_27 人工存入-GD視訊-存入
        '1151',  //DEPOSIT-MANUAL-IN_26 人工存入-沙龍視訊-存入
        '1161',  //DEPOSIT-MANUAL-IN_28 人工存入-Gns機率-存入
        '1181',  //DEPOSIT-MANUAL-IN_29 人工存入-ISB電子-存入
        '1216',  //DEPOSIT-MANUAL-IN_33 人工存入-888捕魚-存入
        '1254',  //DEPOSIT-MANUAL-IN_32 人工存入-HB電子-存入
        '1268',  //DEPOSIT-MANUAL-IN_36 人工存入-BG視訊-存入
        '1278',  //DEPOSIT-MANUAL-IN_37 人工存入-PP電子-存入
        '1296',  //DEPOSIT-MANUAL-IN_39 人工存入-JDB電子-存入
        '1304',  //DEPOSIT-MANUAL-IN_40 人工存入-AG電子-存入
        '1312',  //DEPOSIT-MANUAL-IN_41 人工存入-MW電子-存入
        '1340',  //DEPOSIT-Bitcoin-IN 比特幣入款
        '1356',  //DEPOSIT-MANUAL-IN_42 人工存入-RT電子-存入
        '1364',  //DEPOSIT-MANUAL-IN_44 人工存入-SG電子-存入
        '1373',  //DEPOSIT-MANUAL-IN_45 人工存入-VR彩票-存入
        '1399',  //DEPOSIT-MANUAL-IN_47 人工存入-EVO視訊-存入
        '1407',  //DEPOSIT-MANUAL-IN_48 人工存入-BNG電子-存入
        '1415',  //DEPOSIT-MANUAL-IN_46 人工存入-PTⅡ電子-存入
        '1440',  //DEPOSIT-MANUAL-IN_49 人工存入-開元 棋牌-存入
        '1454'   //DEPOSIT-MANUAL-IN_50 人工存入-WM電子-存入
    ];

    /**
      * 帳目人工入款opcode
      *
      * @var array
      */
    public static $ledgerDepositManualOpcode = [
        '1010',  //人工存入
        '1021',  //負數額度歸零
        '1022',  //取消出款
        '1023',  //其他
        '1044',  //人工存入-體育投注-存入
        '1076',  //人工存入-AG視訊-存入
        '1087',  //DEPOSIT-MANUAL-IN_20 人工存入-PT-存入
        '1104',  //DEPOSIT-MANUAL-IN_22 人工存入-歐博視訊-存入
        '1112',  //DEPOSIT-MANUAL-IN_23 人工存入-MG電子-存入
        '1120',  //DEPOSIT-MANUAL-IN_24 人工存入-東方視訊-存入
        '1131',  //DEPOSIT-MANUAL-IN_25 人工存入-SB體育-存入
        '1148',  //DEPOSIT-MANUAL-IN_27 人工存入-GD視訊-存入
        '1151',  //DEPOSIT-MANUAL-IN_26 人工存入-沙龍視訊-存入
        '1161',  //DEPOSIT-MANUAL-IN_28 人工存入-Gns機率-存入
        '1181',  //DEPOSIT-MANUAL-IN_29 人工存入-ISB電子-存入
        '1216',  //DEPOSIT-MANUAL-IN_33 人工存入-888捕魚-存入
        '1254',  //DEPOSIT-MANUAL-IN_32 人工存入-HB電子-存入
        '1268',  //DEPOSIT-MANUAL-IN_36 人工存入-BG視訊-存入
        '1278',  //DEPOSIT-MANUAL-IN_37 人工存入-PP電子-存入
        '1296',  //DEPOSIT-MANUAL-IN_39 人工存入-JDB電子-存入
        '1304',  //DEPOSIT-MANUAL-IN_40 人工存入-AG電子-存入
        '1312',  //DEPOSIT-MANUAL-IN_41 人工存入-MW電子-存入
        '1342',  //DEPOSIT-Bitcoin-WITHDRAWAL_CANCEL 比特幣取消出款
        '1356',  //DEPOSIT-MANUAL-IN_42 人工存入-RT電子-存入
        '1364',  //DEPOSIT-MANUAL-IN_44 人工存入-SG電子-存入
        '1373',  //DEPOSIT-MANUAL-IN_45 人工存入-VR彩票-存入
        '1399',  //DEPOSIT-MANUAL-IN_47 人工存入-EVO視訊-存入
        '1407',  //DEPOSIT-MANUAL-IN_48 人工存入-BNG電子-存入
        '1415',  //DEPOSIT-MANUAL-IN_46 人工存入-PTⅡ電子-存入
        '1440',  //DEPOSIT-MANUAL-IN_49 人工存入-開元 棋牌-存入
        '1454'   //DEPOSIT-MANUAL-IN_50 人工存入-WM電子-存入
    ];

    /**
      * 現金出款opcode
      *
      * @var array
      */
    public static $cashWithdrawOpcode = [
        '1002',  //WITHDRAWAL 出款
        '1005',  //DEPOSIT-ADMIN-WITHDRAWAL_CANCEL 系統取消出款
        '1013',  //WITHDRAWAL-MANUAL-MULTI 重複出款
        '1014',  //WITHDRAWAL-MANUAL-COMPANY_MISDEPOSIT 公司入款誤存
        '1015',  //WITHDRAWAL-MANUAL-NEGATIVE_RECHARGE 會員負數回沖
        '1016',  //WITHDRAWAL-MANUAL-USER_APPLY 手動申請出款
        '1017',  //扣除非法下注派彩
        '1018',  //放棄存款優惠
        '1019',  //其他人工提出
        '1022',  //取消出款
        '1047',  //人工提出-體育投注-提出
        '1079',  //人工提出-AG視訊-提出
        '1090',  //WITHDRAWAL-MANUAL-OUT_20 人工提出-PT-提出
        '1107',  //WITHDRAWAL-MANUAL-OUT_22 人工提出-歐博視訊-提出
        '1115',  //WITHDRAWAL-MANUAL-OUT_23 人工提出-MG電子-提出
        '1123',  //WITHDRAWAL-MANUAL-OUT_24 人工提出-東方視訊-提出
        '1134',  //WITHDRAWAL-MANUAL-OUT_25 人工提出-SB體育-提出
        '1142',  //WITHDRAWAL-MANUAL-OUT_27 人工提出-GD視訊-提出
        '1154',  //WITHDRAWAL-MANUAL-OUT_26 人工提出-沙龍視訊-提出
        '1164',  //WITHDRAWAL-MANUAL-OUT_28 人工提出-Gns機率-提出
        '1184',  //WITHDRAWAL-MANUAL-OUT_29 人工提出-ISB電子-提出
        '1219',  //WITHDRAWAL-MANUAL-OUT_33 人工提出-888捕魚-提出
        '1257',  //WITHDRAWAL-MANUAL-OUT_32 人工提出-HB電子-提出
        '1271',  //WITHDRAWAL-MANUAL-OUT_36 人工提出-BG視訊-提出
        '1281',  //WITHDRAWAL-MANUAL-OUT_37 人工提出-PP電子-提出
        '1299',  //WITHDRAWAL-MANUAL-OUT_39 人工提出-JDB電子-提出
        '1307',  //WITHDRAWAL-MANUAL-OUT_40 人工提出-AG電子-提出
        '1315',  //WITHDRAWAL-MANUAL-OUT_41 人工提出-MW電子-提出
        '1341',  //WITHDRAWAL-Bitcoin 比特幣出款
        '1342',  //DEPOSIT-Bitcoin-WITHDRAWAL_CANCEL 比特幣取消出款
        '1359',  //WITHDRAWAL-MANUAL-OUT_42 人工提出-RT電子-提出
        '1367',  //WITHDRAWAL-MANUAL-OUT_44 人工提出-SG電子-提出
        '1376',  //WITHDRAWAL-MANUAL-OUT_45 人工提出-VR彩票-提出
        '1402',  //WITHDRAWAL-MANUAL-OUT_47 人工提出-EVO視訊-提出
        '1410',  //WITHDRAWAL-MANUAL-OUT_48 人工提出-BNG電子-提出
        '1418',  //WITHDRAWAL-MANUAL-OUT_46 人工提出-PTⅡ電子-提出
        '1443',  //WITHDRAWAL-MANUAL-OUT_49 人工提出-開元 棋牌-提出
        '1457'   //WITHDRAWAL-MANUAL-OUT_50 人工提出-WM電子-提出
    ];

    /**
      * 帳目人工出款opcode
      *
      * @var array
      */
    public static $ledgerWithdrawManualOpcode = [
        '1013',  //WITHDRAWAL-MANUAL-MULTI 重複出款
        '1014',  //WITHDRAWAL-MANUAL-COMPANY_MISDEPOSIT 公司入款誤存
        '1015',  //WITHDRAWAL-MANUAL-NEGATIVE_RECHARGE 會員負數回沖
        '1016',  //WITHDRAWAL-MANUAL-USER_APPLY 手動申請出款
        '1017',  //扣除非法下注派彩
        '1018',  //放棄存款優惠
        '1019',  //其他人工提出
        '1047',  //人工提出-體育投注-提出
        '1079',  //人工提出-AG視訊-提出
        '1090',  //WITHDRAWAL-MANUAL-OUT_20 人工提出-PT-提出
        '1107',  //WITHDRAWAL-MANUAL-OUT_22 人工提出-歐博視訊-提出
        '1115',  //WITHDRAWAL-MANUAL-OUT_23 人工提出-MG電子-提出
        '1123',  //WITHDRAWAL-MANUAL-OUT_24 人工提出-東方視訊-提出
        '1134',  //WITHDRAWAL-MANUAL-OUT_25 人工提出-SB體育-提出
        '1142',  //WITHDRAWAL-MANUAL-OUT_27 人工提出-GD視訊-提出
        '1154',  //WITHDRAWAL-MANUAL-OUT_26 人工提出-沙龍視訊-提出
        '1164',  //WITHDRAWAL-MANUAL-OUT_28 人工提出-Gns機率-提出
        '1184',  //WITHDRAWAL-MANUAL-OUT_29 人工提出-ISB電子-提出
        '1219',  //WITHDRAWAL-MANUAL-OUT_33 人工提出-888捕魚-提出
        '1257',  //WITHDRAWAL-MANUAL-OUT_32 人工提出-HB電子-提出
        '1271',  //WITHDRAWAL-MANUAL-OUT_36 人工提出-BG視訊-提出
        '1281',  //WITHDRAWAL-MANUAL-OUT_37 人工提出-PP電子-提出
        '1299',  //WITHDRAWAL-MANUAL-OUT_39 人工提出-JDB電子-提出
        '1307',  //WITHDRAWAL-MANUAL-OUT_40 人工提出-AG電子-提出
        '1315',  //WITHDRAWAL-MANUAL-OUT_41 人工提出-MW電子-提出
        '1359',  //WITHDRAWAL-MANUAL-OUT_42 人工提出-RT電子-提出
        '1367',  //WITHDRAWAL-MANUAL-OUT_44 人工提出-SG電子-提出
        '1376',  //WITHDRAWAL-MANUAL-OUT_45 人工提出-VR彩票-提出
        '1402',  //WITHDRAWAL-MANUAL-OUT_47 人工提出-EVO視訊-提出
        '1410',  //WITHDRAWAL-MANUAL-OUT_48 人工提出-BNG電子-提出
        '1418',  //WITHDRAWAL-MANUAL-OUT_46 人工提出-PTⅡ電子-提出
        '1443',  //WITHDRAWAL-MANUAL-OUT_49 人工提出-開元 棋牌-提出
        '1457'   //WITHDRAWAL-MANUAL-OUT_50 人工提出-WM電子-提出
    ];

    /**
      * 優惠opcode
      *
      * @var array
      */
    public static $cashOfferOpcode = [
        '1011',  //存款優惠
        '1034',  //DEPOSIT-MANUAL-BACK-COMMISSION 退佣優惠
        '1037',  //DEPOSIT-COMPANY-OFFER_IN 公司入款優惠
        '1041',  //DEPOSIT-ONLINE-SP 線上存款優惠
        '1053',  //DEPOSIT MANUAL ACTIVITY 活動優惠
        '1095'   //DEPOSIT MANUAL REGISTER 新註冊優惠
    ];

    /**
      * 帳目優惠opcode
      *
      * @var array
      */
    public static $ledgerOfferOpcode = [
        '1011',  //存款優惠
        '1012',  //匯款優惠
        '1037',  //DEPOSIT-COMPANY-OFFER_IN 公司入款優惠
        '1038',  //DEPOSIT-COMPANY-OFFER_REMITTANCE 公司匯款優惠
        '1041',  //DEPOSIT-ONLINE-SP 線上存款優惠
        '1053',  //DEPOSIT MANUAL ACTIVITY 活動優惠
        '1054',  //DEPOSIT MANUAL REBATE 返點優惠
        '1073',  //DEPOSIT-MANUAL-MANUAL_ACTIVITIES_BONUS 活動獎金
        '1095',  //DEPOSIT MANUAL REGISTER 新註冊優惠
        '1351',  //DEPOSIT-MANUAL-FISHING_XMAS_ACTIVITY 捕魚聖誕活動獎金
        '1370',  //DEPOSIT-MANUAL-LOTTERY_CNY_ACTIVITY 彩票紅包活動
        '1433',  //DEPOSIT-MANUAL-SPORT_FIFA_ACTIVITY BBIN世足活動獎金
    ];

    /**
      * 返點opcode
      *
      * @var array
      */
    public static $cashRebateOpcode = [
        '1024',  //DEPOSIT-1-MANUAL 球類返點
        '1025',  //DEPOSIT-2-MANUAL KENO返點
        '1026',  //DEPOSIT-3-MANUAL 視訊返點
        '1027',  //DEPOSIT-4-MANUAL 體育返點
        '1028',  //DEPOSIT-5-MANUAL 機率返點
        '1048',  //DEPOSIT-12-MANUAL 彩票返點
        '1050',  //DEPOSIT-13-MANUAL BBplay返點
        '1054',  //DEPOSIT MANUAL REBATE 返點優惠
        '1055',  //DEPOSIT-3_0-MANUAL BB視訊返點
        '1057',  //DEPOSIT-3_1-MANUAL TT視訊返點
        '1059',  //DEPOSIT-3_2-MANUAL 金臂視訊返點
        '1061',  //DEPOSIT-3_3-MANUAL 新埔京視訊返點
        '1063',  //DEPOSIT-3_4-MANUAL 盈豐視訊返點
        '1065',  //DEPOSIT-15-MANUAL 3D廳返點
        '1067',  //DEPOSIT-16-MANUAL 對戰返點
        '1069',  //DEPOSIT-17-MANUAL 虛擬賽事返點
        '1071',  //DEPOSIT-3_5-MANUAL VIP視訊返點
        '1082',  //DEPOSIT-19-MANUAL AG視訊返點
        '1091',  //DEPOSIT-20-MANUAL PT返點
        '1093',  //DEPOSIT-21-MANUAL LT返點
        '1096',  //DEPOSIT-3_6-MANUAL 競咪視訊返點
        '1108',  //DEPOSIT-22-MANUAL 歐博視訊返點
        '1116',  //DEPOSIT-23-MANUAL MG電子返點
        '1124',  //DEPOSIT-24-MANUAL 東方視訊返點
        '1135',  //DEPOSIT-25-MANUAL SB體育返點
        '1143',  //DEPOSIT-27-MANUAL GD視訊返點
        '1155',  //DEPOSIT-26-MANUAL 沙龍視訊返點
        '1165',  //DEPOSIT-28-MANUAL Gns機率返點
        '1168',  //DEPOSIT-23_1-MANUAL MG累積彩池返點
        '1169',  //DEPOSIT-23_2-MANUAL MG老虎機返點
        '1170',  //DEPOSIT-23_3-MANUAL MG特色遊戲返點
        '1171',  //DEPOSIT-23_4-MANUAL MG桌上遊戲返點
        '1172',  //DEPOSIT-23_14-MANUAL MG手機遊戲返點
        '1185',  //DEPOSIT-29-MANUAL ISB電子返點
        '1189',  //DEPOSIT-30-MANUAL BB捕魚達人返點
        '1191',  //DEPOSIT-31-MANUAL BC體育返點
        '1193',  //DEPOSIT-5_3-MANUAL BB老虎機返點
        '1194',  //DEPOSIT-5_5-MANUAL BB桌上遊戲返點
        '1195',  //DEPOSIT-5_82-MANUAL BB大型機台返點
        '1196',  //DEPOSIT-5_83-MANUAL BB刮刮樂返點
        '1197',  //DEPOSIT-5_85-MANUAL BB特色遊戲返點
        '1203',  //DEPOSIT-34-MANUAL 一元奪寶返點
        '1205',  //DEPOSIT-29_3-MANUAL ISB老虎機返點
        '1206',  //DEPOSIT-29_5-MANUAL ISB桌上遊戲返點
        '1207',  //DEPOSIT-29_81-MANUAL ISB累積彩池返點
        '1208',  //DEPOSIT-29_92-MANUAL ISB視訊撲克返點
        '1220',  //DEPOSIT-33-MANUAL 888捕魚返點
        '1224',  //DEPOSIT-20_3-MANUAL PT老虎機返點
        '1225',  //DEPOSIT-20_5-MANUAL PT桌上遊戲返點
        '1226',  //DEPOSIT-20_81-MANUAL PT累積彩池返點
        '1227',  //DEPOSIT-20_82-MANUAL PT大型機台返點
        '1228',  //DEPOSIT-20_83-MANUAL PT刮刮樂返點
        '1229',  //DEPOSIT-20_92-MANUAL PT視訊撲克返點
        '1236',  //DEPOSIT-20_0-MANUAL PT未分類返點
        '1238',  //DEPOSIT-35-MANUAL 賭神廳返點
        '1240',  //DEPOSIT-12_1-MANUAL 一般彩票返點
        '1241',  //DEPOSIT-12_2-MANUAL BB快開返點
        '1242',  //DEPOSIT-12_3-MANUAL PK&11選5返點
        '1243',  //DEPOSIT-12_4-MANUAL 時時彩&快3返點
        '1244',  //DEPOSIT-12_5-MANUAL Keno返點
        '1245',  //DEPOSIT-12_6-MANUAL 十分彩返點
        '1260',  //DEPOSIT-32_3-MANUAL HB老虎機返點
        '1261',  //DEPOSIT-32_5-MANUAL HB桌上遊戲返點
        '1262',  //DEPOSIT-32_92-MANUAL HB視訊撲克返點
        '1274',  //DEPOSIT-36-MANUAL BG視訊返點
        '1284',  //DEPOSIT-38-MANUAL BB捕魚大師返點
        '1286',  //DEPOSIT-37_3-MANUAL PP老虎機返點
        '1287',  //DEPOSIT-37_5-MANUAL PP桌上遊戲返點
        '1288',  //DEPOSIT-37_81-MANUAL PP累積彩池返點
        '1289',  //DEPOSIT-37_85-MANUAL PP特色遊戲返點
        '1318',  //DEPOSIT-20_91-MANUAL PT捕魚機返點
        '1320',  //DEPOSIT-28_3-MANUAL GNS老虎機返點
        '1322',  //DEPOSIT-28_91-MANUAL GNS捕魚機返點
        '1324',  //DEPOSIT-39_3-MANUAL JDB老虎機返點
        '1325',  //DEPOSIT-39_82-MANUAL JDB大型機台返點
        '1326',  //DEPOSIT-39_91-MANUAL JDB捕魚機返點
        '1330',  //DEPOSIT-40_3-MANUAL AG老虎機返點
        '1331',  //DEPOSIT-40_5-MANUAL AG桌上遊戲返點
        '1332',  //DEPOSIT-40_81-MANUAL AG累積彩池返點
        '1333',  //DEPOSIT-40_91-MANUAL AG捕魚機返點
        '1334',  //DEPOSIT-40_92-MANUAL AG視頻撲克返點
        '1343',  //DEPOSIT-41_3-MANUAL MW老虎機返點
        '1344',  //DEPOSIT-41_5-MANUAL MW桌上遊戲返點
        '1345',  //DEPOSIT-41_82-MANUAL MW大型機台返點
        '1346',  //DEPOSIT-41_91-MANUAL MW捕魚機返點
        '1352',  //DEPOSIT-43-MANUAL IN體育返點
        '1379',  //DEPOSIT-42_3-MANUAL RT老虎機返點
        '1380',  //DEPOSIT-42_5-MANUAL RT桌上遊戲返點
        '1383',  //DEPOSIT-44_3-MANUAL SG老虎機返點
        '1384',  //DEPOSIT-44_5-MANUAL SG桌上遊戲返點
        '1385',  //DEPOSIT-44_81-MANUAL SG累積彩池返點
        '1386',  //DEPOSIT-44_82-MANUAL SG大型機台返點
        '1391',  //DEPOSIT-45_1-MANUAL VR真人彩返點
        '1392',  //DEPOSIT-45_2-MANUAL VR國家彩返點
        '1393',  //DEPOSIT-45_3-MANUAL VR六合彩返點
        '1421',  //DEPOSIT-46_3-MANUAL PTⅡ老虎機返點
        '1422',  //DEPOSIT-46_81-MANUAL PTⅡ累積彩池返點
        '1423',  //DEPOSIT-46_91-MANUAL PTⅡ捕魚機返點
        '1427',  //DEPOSIT-46_5-MANUAL PTⅡ桌上遊戲返點
        '1431',  //DEPOSIT-48_3-MANUAL BNG老虎機返點
        '1434',  //DEPOSIT-47-MANUAL EVO視訊返點
        '1436',  //DEPOSIT-28_81-MANUAL GNS累積彩池返點
        '1446',  //DEPOSIT-28_85-MANUAL GNS特色遊戲返點
        '1448',  //DEPOSIT-49-MANUAL 開元棋牌返點
        '1450',  //DEPOSIT-28_5-MANUAL GNS桌上遊戲返點

        '1029',  //WITHDRAWAL-1-MANUAL 球類沖銷
        '1030',  //WITHDRAWAL-2-MANUAL KENO沖銷
        '1031',  //WITHDRAWAL-3-MANUAL 視訊沖銷
        '1032',  //WITHDRAWAL-4-MANUAL 體育沖銷
        '1033',  //WITHDRAWAL-5-MANUAL 機率沖銷
        '1049',  //WITHDRAWAL-12-MANUAL 彩票沖銷
        '1051',  //WITHDRAWAL-13-MANUAL BBplay沖銷
        '1056',  //WITHDRAWAL-3_0-MANUAL BB視訊沖銷
        '1058',  //WITHDRAWAL-3_1-MANUAL TT視訊沖銷
        '1060',  //WITHDRAWAL-3_2-MANUAL 金臂視訊沖銷
        '1062',  //WITHDRAWAL-3_3-MANUAL 新埔京視訊沖銷
        '1064',  //WITHDRAWAL-3_4-MANUAL 盈豐視訊沖銷
        '1066',  //WITHDRAWAL-15-MANUAL 3D廳沖銷
        '1068',  //WITHDRAWAL-16-MANUAL 對戰沖銷
        '1070',  //WITHDRAWAL-17-MANUAL 虛擬賽事沖銷
        '1072',  //WITHDRAWAL-3_5-MANUAL VIP視訊沖銷
        '1083',  //WITHDRAWAL-19-MANUAL AG視訊沖銷
        '1092',  //WITHDRAWAL-20-MANUAL PT沖銷
        '1094',  //WITHDRAWAL-21-MANUAL LT沖銷
        '1097',  //WITHDRAWAL-3_6-MANUAL 競咪視訊沖銷
        '1109',  //WITHDRAWAL-22-MANUAL 歐博視訊沖銷
        '1117',  //WITHDRAWAL-23-MANUAL MG電子沖銷
        '1125',  //WITHDRAWAL-24-MANUAL 東方視訊沖銷
        '1136',  //WITHDRAWAL-25-MANUAL SB體育沖銷
        '1144',  //WITHDRAWAL-27-MANUAL GD視訊沖銷
        '1156',  //WITHDRAWAL-26-MANUAL 沙龍視訊沖銷
        '1166',  //WITHDRAWAL-28-MANUAL Gns機率沖銷
        '1173',  //WITHDRAWAL-23_1-MANUAL MG累積彩池沖銷
        '1174',  //WITHDRAWAL-23_2-MANUAL MG老虎機沖銷
        '1175',  //WITHDRAWAL-23_3-MANUAL MG特色遊戲沖銷
        '1176',  //WITHDRAWAL-23_4-MANUAL MG桌上遊戲沖銷
        '1177',  //WITHDRAWAL-23_14-MANUAL MG手機遊戲沖銷
        '1186',  //WITHDRAWAL-29-MANUAL ISB電子沖銷
        '1190',  //WITHDRAWAL-30-MANUAL BB捕魚達人沖銷
        '1192',  //WITHDRAWAL-31-MANUAL BC體育沖銷
        '1198',  //WITHDRAWAL-5_3-MANUAL BB老虎機沖銷
        '1199',  //WITHDRAWAL-5_5-MANUAL BB桌上遊戲沖銷
        '1200',  //WITHDRAWAL-5_82-MANUAL BB大型機台沖銷
        '1201',  //WITHDRAWAL-5_83-MANUAL BB刮刮樂沖銷
        '1202',  //WITHDRAWAL-5_85-MANUAL BB特色遊戲沖銷
        '1204',  //WITHDRAWAL-34-MANUAL 一元奪寶沖銷
        '1209',  //WITHDRAWAL-29_3-MANUAL ISB老虎機沖銷
        '1210',  //WITHDRAWAL-29_5-MANUAL ISB桌上遊戲沖銷
        '1211',  //WITHDRAWAL-29_81-MANUAL ISB累積彩池沖銷
        '1212',  //WITHDRAWAL-29_92-MANUAL ISB視訊撲克沖銷
        '1221',  //WITHDRAWAL-33-MANUAL 888捕魚沖銷
        '1230',  //WITHDRAWAL-20_3-MANUAL PT老虎機沖銷
        '1231',  //WITHDRAWAL-20_5-MANUAL PT桌上遊戲沖銷
        '1232',  //WITHDRAWAL-20_81-MANUAL PT累積彩池沖銷
        '1233',  //WITHDRAWAL-20_82-MANUAL PT大型機台沖銷
        '1234',  //WITHDRAWAL-20_83-MANUAL PT刮刮樂沖銷
        '1235',  //WITHDRAWAL-20_92-MANUAL PT視訊撲克沖銷
        '1237',  //WITHDRAWAL-20_0-MANUAL PT未分類沖銷
        '1239',  //WITHDRAWAL-35-MANUAL 賭神廳沖銷
        '1246',  //WITHDRAWAL-12_1-MANUAL 一般彩票沖銷
        '1247',  //WITHDRAWAL-12_2-MANUAL BB快開沖銷
        '1248',  //WITHDRAWAL-12_3-MANUAL PK&11選5沖銷
        '1249',  //WITHDRAWAL-12_4-MANUAL 時時彩&快3沖銷
        '1250',  //WITHDRAWAL-12_5-MANUAL Keno沖銷
        '1251',  //WITHDRAWAL-12_6-MANUAL 十分彩沖銷
        '1263',  //WITHDRAWAL-32_3-MANUAL HB老虎機沖銷
        '1264',  //WITHDRAWAL-32_5-MANUAL HB桌上遊戲沖銷
        '1265',  //WITHDRAWAL-32_92-MANUAL HB視訊撲克沖銷
        '1275',  //WITHDRAWAL-36-MANUAL BG視訊沖銷
        '1285',  //WITHDRAWAL-38-MANUAL BB捕魚大師沖銷
        '1290',  //WITHDRAWAL-37_3-MANUAL PP老虎機沖銷
        '1291',  //WITHDRAWAL-37_5-MANUAL PP桌上遊戲沖銷
        '1292',  //WITHDRAWAL-37_81-MANUAL PP累積彩池沖銷
        '1293',  //WITHDRAWAL-37_85-MANUAL PP特色遊戲沖銷
        '1319',  //WITHDRAWAL-20_91-MANUAL PT捕魚機沖銷
        '1321',  //WITHDRAWAL-28_3-MANUAL GNS老虎機沖銷
        '1323',  //WITHDRAWAL-28_91-MANUAL GNS捕魚機沖銷
        '1327',  //WITHDRAWAL-39_3-MANUAL JDB老虎機沖銷
        '1328',  //WITHDRAWAL-39_82-MANUAL JDB大型機台沖銷
        '1329',  //WITHDRAWAL-39_91-MANUAL JDB捕魚機沖銷
        '1335',  //WITHDRAWAL-40_3-MANUAL AG老虎機沖銷
        '1336',  //WITHDRAWAL-40_5-MANUAL AG桌上遊戲沖銷
        '1337',  //WITHDRAWAL-40_81-MANUAL AG累積彩池沖銷
        '1338',  //WITHDRAWAL-40_91-MANUAL AG捕魚機沖銷
        '1339',  //WITHDRAWAL-40_92-MANUAL AG視頻撲克沖銷
        '1347',  //WITHDRAWAL-41_3-MANUAL MW老虎機沖銷
        '1348',  //WITHDRAWAL-41_5-MANUAL MW桌上遊戲沖銷
        '1349',  //WITHDRAWAL-41_82-MANUAL MW大型機台沖銷
        '1350',  //WITHDRAWAL-41_91-MANUAL MW捕鱼機沖銷
        '1353',  //WITHDRAWAL-43-MANUAL IN體育沖銷
        '1381',  //WITHDRAWAL-42_3-MANUAL RT老虎機沖銷
        '1382',  //WITHDRAWAL-42_5-MANUAL RT桌上遊戲沖銷
        '1387',  //WITHDRAWAL-44_3-MANUAL SG老虎機沖銷
        '1388',  //WITHDRAWAL-44_5-MANUAL SG桌上遊戲沖銷
        '1389',  //WITHDRAWAL-44_81-MANUAL SG累積彩池沖銷
        '1390',  //WITHDRAWAL-44_82-MANUAL SG大型機台沖銷
        '1394',  //WITHDRAWAL-45_1-MANUAL VR真人彩沖銷
        '1395',  //WITHDRAWAL-45_2-MANUAL VR國家彩沖銷
        '1396',  //WITHDRAWAL-45_3-MANUAL VR六合彩沖銷
        '1424',  //WITHDRAWAL-46_3-MANUAL PTⅡ老虎機沖銷
        '1425',  //WITHDRAWAL-46_81-MANUAL PTⅡ累積彩池沖銷
        '1426',  //WITHDRAWAL-46_91-MANUAL PTⅡ捕魚機沖銷
        '1429',  //WITHDRAWAL-46_5-MANUAL PTⅡ桌上遊戲沖銷
        '1432',  //WITHDRAWAL-48_3-MANUAL BNG老虎機沖銷
        '1435',  //WITHDRAWAL-47-MANUAL EVO視訊沖銷
        '1437',  //WITHDRAWAL-28_81-MANUAL GNS累積彩池沖銷
        '1447',  //WITHDRAWAL-28_85-MANUAL GNS特色遊戲沖銷
        '1449',  //WITHDRAWAL-49-MANUAL 開元棋牌沖銷
        '1451'   //WITHDRAWAL-28_5-MANUAL GNS桌上遊戲沖銷
    ];

    /**
      * 帳目返水opcode
      *
      * @var array
      */
    public static $ledgerRebateOpcode = [
        '1024',  //DEPOSIT-1-MANUAL 球類返點
        '1026',  //DEPOSIT-3-MANUAL 視訊返點
        '1027',  //DEPOSIT-4-MANUAL 體育返點
        '1028',  //DEPOSIT-5-MANUAL 機率返點
        '1048',  //DEPOSIT-12-MANUAL 彩票返點
        '1055',  //DEPOSIT-3_0-MANUAL BB視訊返點
        '1057',  //DEPOSIT-3_1-MANUAL TT視訊返點
        '1059',  //DEPOSIT-3_2-MANUAL 金臂視訊返點
        '1061',  //DEPOSIT-3_3-MANUAL 新埔京視訊返點
        '1063',  //DEPOSIT-3_4-MANUAL 盈豐視訊返點
        '1065',  //DEPOSIT-15-MANUAL 3D廳返點
        '1067',  //DEPOSIT-16-MANUAL 對戰返點
        '1069',  //DEPOSIT-17-MANUAL 虛擬賽事返點
        '1071',  //DEPOSIT-3_5-MANUAL VIP視訊返點
        '1082',  //DEPOSIT-19-MANUAL AG視訊返點
        '1091',  //DEPOSIT-20-MANUAL PT返點
        '1093',  //DEPOSIT-21-MANUAL LT返點
        '1096',  //DEPOSIT-3_6-MANUAL 競咪視訊返點
        '1108',  //DEPOSIT-22-MANUAL 歐博視訊返點
        '1116',  //DEPOSIT-23-MANUAL MG電子返點
        '1124',  //DEPOSIT-24-MANUAL 東方視訊返點
        '1135',  //DEPOSIT-25-MANUAL SB體育返點
        '1143',  //DEPOSIT-27-MANUAL GD視訊返點
        '1155',  //DEPOSIT-26-MANUAL 沙龍視訊返點
        '1165',  //DEPOSIT-28-MANUAL Gns機率返點
        '1168',  //DEPOSIT-23_1-MANUAL MG累積彩池返點
        '1169',  //DEPOSIT-23_2-MANUAL MG老虎機返點
        '1170',  //DEPOSIT-23_3-MANUAL MG特色遊戲返點
        '1171',  //DEPOSIT-23_4-MANUAL MG桌上遊戲返點
        '1172',  //DEPOSIT-23_14-MANUAL MG手機遊戲返點
        '1185',  //DEPOSIT-29-MANUAL ISB電子返點
        '1189',  //DEPOSIT-30-MANUAL BB捕魚達人返點
        '1191',  //DEPOSIT-31-MANUAL BC體育返點
        '1193',  //DEPOSIT-5_3-MANUAL BB老虎機返點
        '1194',  //DEPOSIT-5_5-MANUAL BB桌上遊戲返點
        '1195',  //DEPOSIT-5_82-MANUAL BB大型機台返點
        '1196',  //DEPOSIT-5_83-MANUAL BB刮刮樂返點
        '1197',  //DEPOSIT-5_85-MANUAL BB特色遊戲返點
        '1203',  //DEPOSIT-34-MANUAL 一元奪寶返點
        '1205',  //DEPOSIT-29_3-MANUAL ISB老虎機返點
        '1206',  //DEPOSIT-29_5-MANUAL ISB桌上遊戲返點
        '1207',  //DEPOSIT-29_81-MANUAL ISB累積彩池返點
        '1208',  //DEPOSIT-29_92-MANUAL ISB視訊撲克返點
        '1220',  //DEPOSIT-33-MANUAL 888捕魚返點
        '1224',  //DEPOSIT-20_3-MANUAL PT老虎機返點
        '1225',  //DEPOSIT-20_5-MANUAL PT桌上遊戲返點
        '1226',  //DEPOSIT-20_81-MANUAL PT累積彩池返點
        '1227',  //DEPOSIT-20_82-MANUAL PT大型機台返點
        '1228',  //DEPOSIT-20_83-MANUAL PT刮刮樂返點
        '1229',  //DEPOSIT-20_92-MANUAL PT視訊撲克返點
        '1236',  //DEPOSIT-20_0-MANUAL PT未分類返點
        '1238',  //DEPOSIT-35-MANUAL 賭神廳返點
        '1240',  //DEPOSIT-12_1-MANUAL 一般彩票返點
        '1241',  //DEPOSIT-12_2-MANUAL BB快開返點
        '1242',  //DEPOSIT-12_3-MANUAL PK&11選5返點
        '1243',  //DEPOSIT-12_4-MANUAL 時時彩&快3返點
        '1244',  //DEPOSIT-12_5-MANUAL Keno返點
        '1245',  //DEPOSIT-12_6-MANUAL 十分彩返點
        '1260',  //DEPOSIT-32_3-MANUAL HB老虎機返點
        '1261',  //DEPOSIT-32_5-MANUAL HB桌上遊戲返點
        '1262',  //DEPOSIT-32_92-MANUAL HB視訊撲克返點
        '1274',  //DEPOSIT-36-MANUAL BG視訊返點
        '1284',  //DEPOSIT-38-MANUAL BB捕魚大師返點
        '1286',  //DEPOSIT-37_3-MANUAL PP老虎機返點
        '1287',  //DEPOSIT-37_5-MANUAL PP桌上遊戲返點
        '1288',  //DEPOSIT-37_81-MANUAL PP累積彩池返點
        '1289',  //DEPOSIT-37_85-MANUAL PP特色遊戲返點
        '1318',  //DEPOSIT-20_91-MANUAL PT捕魚機返點
        '1320',  //DEPOSIT-28_3-MANUAL GNS老虎機返點
        '1322',  //DEPOSIT-28_91-MANUAL GNS捕魚機返點
        '1324',  //DEPOSIT-39_3-MANUAL JDB老虎機返點
        '1325',  //DEPOSIT-39_82-MANUAL JDB大型機台返點
        '1326',  //DEPOSIT-39_91-MANUAL JDB捕魚機返點
        '1330',  //DEPOSIT-40_3-MANUAL AG老虎機返點
        '1331',  //DEPOSIT-40_5-MANUAL AG桌上遊戲返點
        '1332',  //DEPOSIT-40_81-MANUAL AG累積彩池返點
        '1333',  //DEPOSIT-40_91-MANUAL AG捕魚機返點
        '1334',  //DEPOSIT-40_92-MANUAL AG視頻撲克返點
        '1343',  //DEPOSIT-41_3-MANUAL MW老虎機返點
        '1344',  //DEPOSIT-41_5-MANUAL MW桌上遊戲返點
        '1345',  //DEPOSIT-41_82-MANUAL MW大型機台返點
        '1346',  //DEPOSIT-41_91-MANUAL MW捕魚機返點
        '1352',  //DEPOSIT-43-MANUAL IN體育返點
        '1379',  //DEPOSIT-42_3-MANUAL RT老虎機返點
        '1380',  //DEPOSIT-42_5-MANUAL RT桌上遊戲返點
        '1383',  //DEPOSIT-44_3-MANUAL SG老虎機返點
        '1384',  //DEPOSIT-44_5-MANUAL SG桌上遊戲返點
        '1385',  //DEPOSIT-44_81-MANUAL SG累積彩池返點
        '1386',  //DEPOSIT-44_82-MANUAL SG大型機台返點
        '1391',  //DEPOSIT-45_1-MANUAL VR真人彩返點
        '1392',  //DEPOSIT-45_2-MANUAL VR國家彩返點
        '1393',  //DEPOSIT-45_3-MANUAL VR六合彩返點
        '1421',  //DEPOSIT-46_3-MANUAL PTⅡ老虎機返點
        '1422',  //DEPOSIT-46_81-MANUAL PTⅡ累積彩池返點
        '1423',  //DEPOSIT-46_91-MANUAL PTⅡ捕魚機返點
        '1427',  //DEPOSIT-46_5-MANUAL PTⅡ桌上遊戲返點
        '1431',  //DEPOSIT-48_3-MANUAL BNG老虎機返點
        '1434',  //DEPOSIT-47-MANUAL EVO視訊返點
        '1436',  //DEPOSIT-28_81-MANUAL GNS累積彩池返點
        '1446',  //DEPOSIT-28_85-MANUAL GNS特色遊戲返點
        '1448',  //DEPOSIT-49-MANUAL 開元棋牌返點
        '1450',  //DEPOSIT-28_5-MANUAL GNS桌上遊戲返點

        '1029',  //WITHDRAWAL-1-MANUAL 球類沖銷
        '1031',  //WITHDRAWAL-3-MANUAL 視訊沖銷
        '1032',  //WITHDRAWAL-4-MANUAL 體育沖銷
        '1033',  //WITHDRAWAL-5-MANUAL 機率沖銷
        '1049',  //WITHDRAWAL-12-MANUAL 彩票沖銷
        '1056',  //WITHDRAWAL-3_0-MANUAL BB視訊沖銷
        '1058',  //WITHDRAWAL-3_1-MANUAL TT視訊沖銷
        '1060',  //WITHDRAWAL-3_2-MANUAL 金臂視訊沖銷
        '1062',  //WITHDRAWAL-3_3-MANUAL 新埔京視訊沖銷
        '1064',  //WITHDRAWAL-3_4-MANUAL 盈豐視訊沖銷
        '1066',  //WITHDRAWAL-15-MANUAL 3D廳沖銷
        '1068',  //WITHDRAWAL-16-MANUAL 對戰沖銷
        '1070',  //WITHDRAWAL-17-MANUAL 虛擬賽事沖銷
        '1072',  //WITHDRAWAL-3_5-MANUAL VIP視訊沖銷
        '1083',  //WITHDRAWAL-19-MANUAL AG視訊沖銷
        '1092',  //WITHDRAWAL-20-MANUAL PT沖銷
        '1094',  //WITHDRAWAL-21-MANUAL LT沖銷
        '1097',  //WITHDRAWAL-3_6-MANUAL 競咪視訊沖銷
        '1109',  //WITHDRAWAL-22-MANUAL 歐博視訊沖銷
        '1117',  //WITHDRAWAL-23-MANUAL MG電子沖銷
        '1125',  //WITHDRAWAL-24-MANUAL 東方視訊沖銷
        '1136',  //WITHDRAWAL-25-MANUAL SB體育沖銷
        '1144',  //WITHDRAWAL-27-MANUAL GD視訊沖銷
        '1156',  //WITHDRAWAL-26-MANUAL 沙龍視訊沖銷
        '1166',  //WITHDRAWAL-28-MANUAL Gns機率沖銷
        '1173',  //WITHDRAWAL-23_1-MANUAL MG累積彩池沖銷
        '1174',  //WITHDRAWAL-23_2-MANUAL MG老虎機沖銷
        '1175',  //WITHDRAWAL-23_3-MANUAL MG特色遊戲沖銷
        '1176',  //WITHDRAWAL-23_4-MANUAL MG桌上遊戲沖銷
        '1177',  //WITHDRAWAL-23_14-MANUAL MG手機遊戲沖銷
        '1186',  //WITHDRAWAL-29-MANUAL ISB電子沖銷
        '1190',  //WITHDRAWAL-30-MANUAL BB捕魚達人沖銷
        '1192',  //WITHDRAWAL-31-MANUAL BC體育沖銷
        '1198',  //WITHDRAWAL-5_3-MANUAL BB老虎機沖銷
        '1199',  //WITHDRAWAL-5_5-MANUAL BB桌上遊戲沖銷
        '1200',  //WITHDRAWAL-5_82-MANUAL BB大型機台沖銷
        '1201',  //WITHDRAWAL-5_83-MANUAL BB刮刮樂沖銷
        '1202',  //WITHDRAWAL-5_85-MANUAL BB特色遊戲沖銷
        '1204',  //WITHDRAWAL-34-MANUAL 一元奪寶沖銷
        '1209',  //WITHDRAWAL-29_3-MANUAL ISB老虎機沖銷
        '1210',  //WITHDRAWAL-29_5-MANUAL ISB桌上遊戲沖銷
        '1211',  //WITHDRAWAL-29_81-MANUAL ISB累積彩池沖銷
        '1212',  //WITHDRAWAL-29_92-MANUAL ISB視訊撲克沖銷
        '1221',  //WITHDRAWAL-33-MANUAL 888捕魚沖銷
        '1230',  //WITHDRAWAL-20_3-MANUAL PT老虎機沖銷
        '1231',  //WITHDRAWAL-20_5-MANUAL PT桌上遊戲沖銷
        '1232',  //WITHDRAWAL-20_81-MANUAL PT累積彩池沖銷
        '1233',  //WITHDRAWAL-20_82-MANUAL PT大型機台沖銷
        '1234',  //WITHDRAWAL-20_83-MANUAL PT刮刮樂沖銷
        '1235',  //WITHDRAWAL-20_92-MANUAL PT視訊撲克沖銷
        '1237',  //WITHDRAWAL-20_0-MANUAL PT未分類沖銷
        '1239',  //WITHDRAWAL-35-MANUAL 賭神廳沖銷
        '1246',  //WITHDRAWAL-12_1-MANUAL 一般彩票沖銷
        '1247',  //WITHDRAWAL-12_2-MANUAL BB快開沖銷
        '1248',  //WITHDRAWAL-12_3-MANUAL PK&11選5沖銷
        '1249',  //WITHDRAWAL-12_4-MANUAL 時時彩&快3沖銷
        '1250',  //WITHDRAWAL-12_5-MANUAL Keno沖銷
        '1251',  //WITHDRAWAL-12_6-MANUAL 十分彩沖銷
        '1263',  //WITHDRAWAL-32_3-MANUAL HB老虎機沖銷
        '1264',  //WITHDRAWAL-32_5-MANUAL HB桌上遊戲沖銷
        '1265',  //WITHDRAWAL-32_92-MANUAL HB視訊撲克沖銷
        '1275',  //WITHDRAWAL-36-MANUAL BG視訊沖銷
        '1285',  //WITHDRAWAL-38-MANUAL BB捕魚大師沖銷
        '1290',  //WITHDRAWAL-37_3-MANUAL PP老虎機沖銷
        '1291',  //WITHDRAWAL-37_5-MANUAL PP桌上遊戲沖銷
        '1292',  //WITHDRAWAL-37_81-MANUAL PP累積彩池沖銷
        '1293',  //WITHDRAWAL-37_85-MANUAL PP特色遊戲沖銷
        '1319',  //WITHDRAWAL-20_91-MANUAL PT捕魚機沖銷
        '1321',  //WITHDRAWAL-28_3-MANUAL GNS老虎機沖銷
        '1323',  //WITHDRAWAL-28_91-MANUAL GNS捕魚機沖銷
        '1327',  //WITHDRAWAL-39_3-MANUAL JDB老虎機沖銷
        '1328',  //WITHDRAWAL-39_82-MANUAL JDB大型機台沖銷
        '1329',  //WITHDRAWAL-39_91-MANUAL JDB捕魚機沖銷
        '1335',  //WITHDRAWAL-40_3-MANUAL AG老虎機沖銷
        '1336',  //WITHDRAWAL-40_5-MANUAL AG桌上遊戲沖銷
        '1337',  //WITHDRAWAL-40_81-MANUAL AG累積彩池沖銷
        '1338',  //WITHDRAWAL-40_91-MANUAL AG捕魚機沖銷
        '1339',  //WITHDRAWAL-40_92-MANUAL AG視頻撲克沖銷
        '1347',  //WITHDRAWAL-41_3-MANUAL MW老虎機沖銷
        '1348',  //WITHDRAWAL-41_5-MANUAL MW桌上遊戲沖銷
        '1349',  //WITHDRAWAL-41_82-MANUAL MW大型機台沖銷
        '1350',  //WITHDRAWAL-41_91-MANUAL MW捕鱼機沖銷
        '1353',  //WITHDRAWAL-43-MANUAL IN體育沖銷
        '1381',  //WITHDRAWAL-42_3-MANUAL RT老虎機沖銷
        '1382',  //WITHDRAWAL-42_5-MANUAL RT桌上遊戲沖銷
        '1387',  //WITHDRAWAL-44_3-MANUAL SG老虎機沖銷
        '1388',  //WITHDRAWAL-44_5-MANUAL SG桌上遊戲沖銷
        '1389',  //WITHDRAWAL-44_81-MANUAL SG累積彩池沖銷
        '1390',  //WITHDRAWAL-44_82-MANUAL SG大型機台沖銷
        '1394',  //WITHDRAWAL-45_1-MANUAL VR真人彩沖銷
        '1395',  //WITHDRAWAL-45_2-MANUAL VR國家彩沖銷
        '1396',  //WITHDRAWAL-45_3-MANUAL VR六合彩沖銷
        '1424',  //WITHDRAWAL-46_3-MANUAL PTⅡ老虎機沖銷
        '1425',  //WITHDRAWAL-46_81-MANUAL PTⅡ累積彩池沖銷
        '1426',  //WITHDRAWAL-46_91-MANUAL PTⅡ捕魚機沖銷
        '1429',  //WITHDRAWAL-46_5-MANUAL PTⅡ桌上遊戲沖銷
        '1432',  //WITHDRAWAL-48_3-MANUAL BNG老虎機沖銷
        '1435',  //WITHDRAWAL-47-MANUAL EVO視訊沖銷
        '1437',  //WITHDRAWAL-28_81-MANUAL GNS累積彩池沖銷
        '1447',  //WITHDRAWAL-28_85-MANUAL GNS特色遊戲沖銷
        '1449',  //WITHDRAWAL-49-MANUAL 開元棋牌沖銷
        '1451'   //WITHDRAWAL-28_5-MANUAL GNS桌上遊戲沖銷
    ];

    /**
      * 匯款優惠opcode
      *
      * @var array
      */
    public static $cashRemitOpcode = [
        '1012',  //匯款優惠
        '1038'   //DEPOSIT-COMPANY-OFFER_REMITTANCE 公司匯款優惠
    ];

    /**
     * 次數必須採用負數的opcode
     *
     * @var array
     */
    public static $negativeOpcode = [
        '1005',  //DEPOSIT-ADMIN-WITHDRAWAL_CANCEL 系統取消出款
        '1022',  //取消出款
        '1342',  //DEPOSIT-Bitcoin-WITHDRAWAL_CANCEL 比特幣取消出款

        '1029',  //WITHDRAWAL-1-MANUAL 球類沖銷
        '1030',  //WITHDRAWAL-2-MANUAL KENO沖銷
        '1031',  //WITHDRAWAL-3-MANUAL 視訊沖銷
        '1032',  //WITHDRAWAL-4-MANUAL 體育沖銷
        '1033',  //WITHDRAWAL-5-MANUAL 機率沖銷
        '1049',  //WITHDRAWAL-12-MANUAL 彩票沖銷
        '1051',  //WITHDRAWAL-13-MANUAL BBplay沖銷
        '1056',  //WITHDRAWAL-3_0-MANUAL BB視訊沖銷
        '1058',  //WITHDRAWAL-3_1-MANUAL TT視訊沖銷
        '1060',  //WITHDRAWAL-3_2-MANUAL 金臂視訊沖銷
        '1062',  //WITHDRAWAL-3_3-MANUAL 新埔京視訊沖銷
        '1064',  //WITHDRAWAL-3_4-MANUAL 盈豐視訊沖銷
        '1066',  //WITHDRAWAL-15-MANUAL 3D廳沖銷
        '1068',  //WITHDRAWAL-16-MANUAL 對戰沖銷
        '1070',  //WITHDRAWAL-17-MANUAL 虛擬賽事沖銷
        '1072',  //WITHDRAWAL-3_5-MANUAL VIP視訊沖銷
        '1083',  //WITHDRAWAL-19-MANUAL AG視訊沖銷
        '1092',  //WITHDRAWAL-20-MANUAL PT沖銷
        '1094',  //WITHDRAWAL-21-MANUAL LT沖銷
        '1097',  //WITHDRAWAL-3_6-MANUAL 競咪視訊沖銷
        '1109',  //WITHDRAWAL-22-MANUAL 歐博視訊沖銷
        '1117',  //WITHDRAWAL-23-MANUAL MG電子沖銷
        '1125',  //WITHDRAWAL-24-MANUAL 東方視訊沖銷
        '1136',  //WITHDRAWAL-25-MANUAL SB體育沖銷
        '1144',  //WITHDRAWAL-27-MANUAL GD視訊沖銷
        '1156',  //WITHDRAWAL-26-MANUAL 沙龍視訊沖銷
        '1166',  //WITHDRAWAL-28-MANUAL Gns機率沖銷
        '1173',  //WITHDRAWAL-23_1-MANUAL MG累積彩池沖銷
        '1174',  //WITHDRAWAL-23_2-MANUAL MG老虎機沖銷
        '1175',  //WITHDRAWAL-23_3-MANUAL MG特色遊戲沖銷
        '1176',  //WITHDRAWAL-23_4-MANUAL MG桌上遊戲沖銷
        '1177',  //WITHDRAWAL-23_14-MANUAL MG手機遊戲沖銷
        '1186',  //WITHDRAWAL-29-MANUAL ISB電子沖銷
        '1190',  //WITHDRAWAL-30-MANUAL BB捕魚達人沖銷
        '1192',  //WITHDRAWAL-31-MANUAL BC體育沖銷
        '1198',  //WITHDRAWAL-5_3-MANUAL BB老虎機沖銷
        '1199',  //WITHDRAWAL-5_5-MANUAL BB桌上遊戲沖銷
        '1200',  //WITHDRAWAL-5_82-MANUAL BB大型機台沖銷
        '1201',  //WITHDRAWAL-5_83-MANUAL BB刮刮樂沖銷
        '1202',  //WITHDRAWAL-5_85-MANUAL BB特色遊戲沖銷
        '1204',  //WITHDRAWAL-34-MANUAL 一元奪寶沖銷
        '1209',  //WITHDRAWAL-29_3-MANUAL ISB老虎機沖銷
        '1210',  // WITHDRAWAL-29_5-MANUAL ISB桌上遊戲沖銷
        '1211',  //WITHDRAWAL-29_81-MANUAL ISB累積彩池沖銷
        '1212',  //WITHDRAWAL-29_92-MANUAL ISB視訊撲克沖銷
        '1221',  //WITHDRAWAL-33-MANUAL 888捕魚沖銷
        '1230',  //WITHDRAWAL-20_3-MANUAL PT老虎機沖銷
        '1231',  //WITHDRAWAL-20_5-MANUAL PT桌上遊戲沖銷
        '1232',  //WITHDRAWAL-20_81-MANUAL PT累積彩池沖銷
        '1233',  //WITHDRAWAL-20_82-MANUAL PT大型機台沖銷
        '1234',  //WITHDRAWAL-20_83-MANUAL PT刮刮樂沖銷
        '1235',  //WITHDRAWAL-20_92-MANUAL PT視訊撲克沖銷
        '1237',  //WITHDRAWAL-20_0-MANUAL PT未分類沖銷
        '1239',  //WITHDRAWAL-35-MANUAL 賭神廳沖銷
        '1246',  //WITHDRAWAL-12_1-MANUAL 一般彩票沖銷
        '1247',  //WITHDRAWAL-12_2-MANUAL BB快開沖銷
        '1248',  //WITHDRAWAL-12_3-MANUAL PK&11選5沖銷
        '1249',  //WITHDRAWAL-12_4-MANUAL 時時彩&快3沖銷
        '1250',  //WITHDRAWAL-12_5-MANUAL Keno沖銷
        '1251',  //WITHDRAWAL-12_6-MANUAL 十分彩沖銷
        '1263',  //WITHDRAWAL-32_3-MANUAL HB老虎機沖銷
        '1264',  //WITHDRAWAL-32_5-MANUAL HB桌上遊戲沖銷
        '1265',  //WITHDRAWAL-32_92-MANUAL HB視訊撲克沖銷
        '1275',  //WITHDRAWAL-36-MANUAL BG視訊沖銷
        '1285',  //WITHDRAWAL-38-MANUAL BB捕魚大師沖銷
        '1290',  //WITHDRAWAL-37_3-MANUAL PP老虎機沖銷
        '1291',  //WITHDRAWAL-37_5-MANUAL PP桌上遊戲沖銷
        '1292',  //WITHDRAWAL-37_81-MANUAL PP累積彩池沖銷
        '1293',  //WITHDRAWAL-37_85-MANUAL PP特色遊戲沖銷
        '1319',  //WITHDRAWAL-20_91-MANUAL PT捕魚機沖銷
        '1321',  //WITHDRAWAL-28_3-MANUAL GNS老虎機沖銷
        '1323',  //WITHDRAWAL-28_91-MANUAL GNS捕魚機沖銷
        '1327',  //WITHDRAWAL-39_3-MANUAL JDB老虎機沖銷
        '1328',  //WITHDRAWAL-39_82-MANUAL JDB大型機台沖銷
        '1329',  //WITHDRAWAL-39_91-MANUAL JDB捕魚機沖銷
        '1335',  //WITHDRAWAL-40_3-MANUAL AG老虎機沖銷
        '1336',  //WITHDRAWAL-40_5-MANUAL AG桌上遊戲沖銷
        '1337',  //WITHDRAWAL-40_81-MANUAL AG累積彩池沖銷
        '1338',  //WITHDRAWAL-40_91-MANUAL AG捕魚機沖銷
        '1339',  //WITHDRAWAL-40_92-MANUAL AG視頻撲克沖銷
        '1347',  //WITHDRAWAL-41_3-MANUAL MW老虎機沖銷
        '1348',  //WITHDRAWAL-41_5-MANUAL MW桌上遊戲沖銷
        '1349',  //WITHDRAWAL-41_82-MANUAL MW大型機台沖銷
        '1350',  //WITHDRAWAL-41_91-MANUAL MW捕鱼機沖銷
        '1353',  //WITHDRAWAL-43-MANUAL IN體育沖銷
        '1381',  //WITHDRAWAL-42_3-MANUAL RT老虎機沖銷
        '1382',  //WITHDRAWAL-42_5-MANUAL RT桌上遊戲沖銷
        '1387',  //WITHDRAWAL-44_3-MANUAL SG老虎機沖銷
        '1388',  //WITHDRAWAL-44_5-MANUAL SG桌上遊戲沖銷
        '1389',  //WITHDRAWAL-44_81-MANUAL SG累積彩池沖銷
        '1390',  //WITHDRAWAL-44_82-MANUAL SG大型機台沖銷
        '1394',  //WITHDRAWAL-45_1-MANUAL VR真人彩沖銷
        '1395',  //WITHDRAWAL-45_2-MANUAL VR國家彩沖銷
        '1396',  //WITHDRAWAL-45_3-MANUAL VR六合彩沖銷
        '1424',  //WITHDRAWAL-46_3-MANUAL PTⅡ老虎機沖銷
        '1425',  //WITHDRAWAL-46_81-MANUAL PTⅡ累積彩池沖銷
        '1426',  //WITHDRAWAL-46_91-MANUAL PTⅡ捕魚機沖銷
        '1429',  //WITHDRAWAL-46_5-MANUAL PTⅡ桌上遊戲沖銷
        '1432',  //WITHDRAWAL-48_3-MANUAL BNG老虎機沖銷
        '1435',  //WITHDRAWAL-47-MANUAL EVO視訊沖銷
        '1437',  //WITHDRAWAL-28_81-MANUAL GNS累積彩池沖銷
        '1447',  //WITHDRAWAL-28_85-MANUAL GNS特色遊戲沖銷
        '1449',  //WITHDRAWAL-49-MANUAL 開元棋牌沖銷
        '1451'   //WITHDRAWAL-28_5-MANUAL GNS桌上遊戲沖銷
    ];

    /**
      * 帳目公司入款opcode
      *
      * @var array
      */
    public static $ledgerDepositCompanyOpcode = [
        '1036',  //DEPOSIT-COMPANY-IN 公司入款
        '1340'   //DEPOSIT-Bitcoin-IN 比特幣入款
    ];

    /**
      * 帳目線上支付opcode
      *
      * @var array
      */
    public static $ledgerDepositOnlineOpcode = [
        '1039',  //DEPOSIT-ONLINE-IN 線上存款
        '1040'   //DEPOSIT-ONLINE-CHARGE 線上存款手續費
    ];

    /**
     * 需要進入統計的 Opcode
     *
     * @var array
     */
    public static $all = [
        '1001',  //入款
        '1002',  //WITHDRAWAL 出款
        '1003',  //TRANSFER 轉移
        '1005',  //DEPOSIT-ADMIN-WITHDRAWAL_CANCEL 系統取消出款
        '1006',  //TRANSFER-4-IN 體育投注額度轉入
        '1007',  //TRANSFER-4-OUT 體育投注額度轉出
        '1010',  //人工存入
        '1011',  //存款優惠
        '1012',  //匯款優惠
        '1013',  //WITHDRAWAL-MANUAL-MULTI 重複出款
        '1014',  //WITHDRAWAL-MANUAL-COMPANY_MISDEPOSIT 公司入款誤存
        '1015',  //WITHDRAWAL-MANUAL-NEGATIVE_RECHARGE 會員負數回沖
        '1016',  //WITHDRAWAL-MANUAL-USER_APPLY 手動申請出款
        '1017',  //扣除非法下注派彩
        '1018',  //放棄存款優惠
        '1019',  //其他人工提出
        '1020',  //TRANSFER-FROM-SYS 系統直接新增快開額度(快開額度專用)
        '1021',  //負數額度歸零
        '1022',  //取消出款
        '1023',  //其他
        '1024',  //DEPOSIT-1-MANUAL 球類返點
        '1025',  //DEPOSIT-2-MANUAL KENO返點
        '1026',  //DEPOSIT-3-MANUAL 視訊返點
        '1027',  //DEPOSIT-4-MANUAL 體育返點
        '1028',  //DEPOSIT-5-MANUAL 機率返點
        '1029',  //WITHDRAWAL-1-MANUAL 球類沖銷
        '1030',  //WITHDRAWAL-2-MANUAL KENO沖銷
        '1031',  //WITHDRAWAL-3-MANUAL 視訊沖銷
        '1032',  //WITHDRAWAL-4-MANUAL 體育沖銷
        '1033',  //WITHDRAWAL-5-MANUAL 機率沖銷
        '1034',  //DEPOSIT-MANUAL-BACK-COMMISSION 退佣優惠
        '1035',  //TRANSFER-4-MUFF 轉至體育投注失敗
        '1036',  //公司入款
        '1037',  //DEPOSIT-COMPANY-OFFER_IN 公司入款優惠
        '1038',  //DEPOSIT-COMPANY-OFFER_REMITTANCE 公司匯款優惠
        '1039',  //線上入款
        '1040',  //線上入款手續費
        '1041',  //DEPOSIT-ONLINE-SP 線上存款優惠
        '1042',  //TRANSFER-API-IN API轉入
        '1043',  //TRANSFER-API-OUT API轉出
        '1044',  //人工存入-體育投注-存入
        '1045',  //DEPOSIT-MANUAL-TRANSFER_4 人工存入-體育投注-轉移
        '1046',  //WITHDRAWAL-MANUAL-TRANSFER_4 人工提出-體育投注-轉移
        '1047',  //人工提出-體育投注-提出
        '1048',  //DEPOSIT-12-MANUAL 彩票返點
        '1049',  //WITHDRAWAL-12-MANUAL 彩票沖銷
        '1050',  //DEPOSIT-13-MANUAL BBplay返點
        '1051',  //WITHDRAWAL-13-MANUAL BBplay沖銷
        '1052',  //WITHDRAWAL-MANUAL_PAYOFF_MULTI 重複派彩扣回
        '1053',  //DEPOSIT MANUAL ACTIVITY 活動優惠
        '1054',  //DEPOSIT MANUAL REBATE 返點優惠
        '1055',  //DEPOSIT-3_0-MANUAL BB視訊返點
        '1056',  //WITHDRAWAL-3_0-MANUAL BB視訊沖銷
        '1057',  //DEPOSIT-3_1-MANUAL TT視訊返點
        '1058',  //WITHDRAWAL-3_1-MANUAL TT視訊沖銷
        '1059',  //DEPOSIT-3_2-MANUAL 金臂視訊返點
        '1060',  //WITHDRAWAL-3_2-MANUAL 金臂視訊沖銷
        '1061',  //DEPOSIT-3_3-MANUAL 新埔京視訊返點
        '1062',  //WITHDRAWAL-3_3-MANUAL 新埔京視訊沖銷
        '1063',  //DEPOSIT-3_4-MANUAL 盈豐視訊返點
        '1064',  //WITHDRAWAL-3_4-MANUAL 盈豐視訊沖銷
        '1065',  //DEPOSIT-15-MANUAL 3D廳返點
        '1066',  //WITHDRAWAL-15-MANUAL 3D廳沖銷
        '1067',  //DEPOSIT-16-MANUAL 對戰返點
        '1068',  //WITHDRAWAL-16-MANUAL 對戰沖銷
        '1069',  //DEPOSIT-17-MANUAL 虛擬賽事返點
        '1070',  //WITHDRAWAL-17-MANUAL 虛擬賽事沖銷
        '1071',  //DEPOSIT-3_5-MANUAL VIP視訊返點
        '1072',  //WITHDRAWAL-3_5-MANUAL VIP視訊沖銷
        '1073',  //DEPOSIT-MANUAL-MANUAL_ACTIVITIES_BONUS 活動獎金
        '1074',  //TRANSFER-AGLIVE-IN AG視訊額度轉入
        '1075',  //TRANSFER-AGLIVE-OUT AG視訊額度轉出
        '1076',  //人工存入-AG視訊-存入
        '1077',  //DEPOSIT-MANUAL-TRANSFER_19 人工存入-AG視訊-轉移
        '1078',  //WITHDRAWAL-MANUAL-TRANSFER_19 人工提出-AG視訊-轉移
        '1079',  //人工提出-AG視訊-提出
        '1082',  //DEPOSIT-19-MANUAL AG視訊返點
        '1083',  //WITHDRAWAL-19-MANUAL AG視訊沖銷
        '1084',  //TRANSFER-18-MUFF 轉至AG視訊失敗
        '1085',  //TRANSFER-PT-IN PT額度轉入
        '1086',  //TRANSFER-PT-OUT PT額度轉出
        '1087',  //DEPOSIT-MANUAL-IN_20 人工存入-PT-存入
        '1088',  //DEPOSIT-MANUAL-TRANSFER_20 人工存入-PT-轉移
        '1089',  //WITHDRAWAL-MANUAL-TRANSFER_20 人工提出-PT-轉移
        '1090',  //WITHDRAWAL-MANUAL-OUT_20 人工提出-PT-提出
        '1091',  //DEPOSIT-20-MANUAL PT返點
        '1092',  //WITHDRAWAL-20-MANUAL PT沖銷
        '1093',  //DEPOSIT-21-MANUAL LT返點
        '1094',  //WITHDRAWAL-21-MANUAL LT沖銷
        '1095',  //DEPOSIT MANUAL REGISTER 新註冊優惠
        '1096',  //DEPOSIT-3_6-MANUAL 競咪視訊返點
        '1097',  //WITHDRAWAL-3_6-MANUAL 競咪視訊沖銷
        '1098',  //REMOVE-USER-ZERO-BALANCE 刪除帳號-額度歸零
        '1099',  //REMOVE-USER-RECOVER-BALANCE_4 刪除帳號-體育投注-額度回收
        '1100',  //REMOVE-USER-RECOVER-BALANCE_19 刪除帳號-AG視訊-額度回收
        '1101',  //REMOVE-USER-RECOVER-BALANCE_20 刪除帳號-PT-額度回收
        '1102',  //TRANSFER-22-IN 由歐博視訊轉入
        '1103',  //TRANSFER-22-OUT 轉出至歐博視訊
        '1104',  //DEPOSIT-MANUAL-IN_22 人工存入-歐博視訊-存入
        '1105',  //DEPOSIT-MANUAL-TRANSFER_22 人工存入-歐博視訊-轉移
        '1106',  //WITHDRAWAL-MANUAL-TRANSFER_22 人工提出-歐博視訊-轉移
        '1107',  //WITHDRAWAL-MANUAL-OUT_22 人工提出-歐博視訊-提出
        '1108',  //DEPOSIT-22-MANUAL 歐博視訊返點
        '1109',  //WITHDRAWAL-22-MANUAL 歐博視訊沖銷
        '1110',  //TRANSFER-23-IN 由MG電子轉入
        '1111',  //TRANSFER-23-OUT 轉出至MG電子
        '1112',  //DEPOSIT-MANUAL-IN_23 人工存入-MG電子-存入
        '1113',  //DEPOSIT-MANUAL-TRANSFER_23 人工存入-MG電子-轉移
        '1114',  //WITHDRAWAL-MANUAL-TRANSFER_23 人工提出-MG電子-轉移
        '1115',  //WITHDRAWAL-MANUAL-OUT_23 人工提出-MG電子-提出
        '1116',  //DEPOSIT-23-MANUAL MG電子返點
        '1117',  //WITHDRAWAL-23-MANUAL MG電子沖銷
        '1118',  //TRANSFER-24-IN 由東方視訊轉入
        '1119',  //TRANSFER-24-OUT 轉出至東方視訊
        '1120',  //DEPOSIT-MANUAL-IN_24 人工存入-東方視訊-存入
        '1121',  //DEPOSIT-MANUAL-TRANSFER_24 人工存入-東方視訊-轉移
        '1122',  //WITHDRAWAL-MANUAL-TRANSFER_24 人工提出-東方視訊-轉移
        '1123',  //WITHDRAWAL-MANUAL-OUT_24 人工提出-東方視訊-提出
        '1124',  //DEPOSIT-24-MANUAL 東方視訊返點
        '1125',  //WITHDRAWAL-24-MANUAL 東方視訊沖銷
        '1126',  //REMOVE-USER-RECOVER-BALANCE_22 刪除帳號-歐博視訊-額度回收
        '1127',  //REMOVE-USER-RECOVER-BALANCE_23 刪除帳號-MG電子-額度回收
        '1128',  //REMOVE-USER-RECOVER-BALANCE_24 刪除帳號-東方視訊-額度回收
        '1129',  //TRANSFER-25-IN 由 SB體育 轉入
        '1130',  //TRANSFER-25-OUT 轉出 至 SB體育
        '1131',  //DEPOSIT-MANUAL-IN_25 人工存入-SB體育-存入
        '1132',  //DEPOSIT-MANUAL-TRANSFER_25 人工存入-SB體育-轉移
        '1133',  //WITHDRAWAL-MANUAL-TRANSFER_25 人工提出-SB體育-轉移
        '1134',  //WITHDRAWAL-MANUAL-OUT_25 人工提出-SB體育-提出
        '1135',  //DEPOSIT-25-MANUAL SB體育返點
        '1136',  //WITHDRAWAL-25-MANUAL SB體育沖銷
        '1137',  //REMOVE-USER-RECOVER-BALANCE_25 刪除帳號-SBtech-額度回收
        '1138',  //REMOVE-USER-RECOVER-BALANCE_26 刪除帳號-沙龍視訊-額度回收
        '1139',  //REMOVE-USER-RECOVER-BALANCE_27 刪除帳號-GD視訊-額度回收
        '1140',  //DEPOSIT-MANUAL-TRANSFER_27 人工存入-GD視訊-轉移
        '1141',  //WITHDRAWAL-MANUAL-TRANSFER_27 人工提出-GD視訊-轉移
        '1142',  //WITHDRAWAL-MANUAL-OUT_27 人工提出-GD視訊-提出
        '1143',  //DEPOSIT-27-MANUAL GD視訊返點
        '1144',  //WITHDRAWAL-27-MANUAL GD視訊沖銷
        '1145',  //TRANSFER-27-MUFF 轉至GD視訊失敗
        '1146',  //TRANSFER-27-IN 由GD視訊轉入
        '1147',  //TRANSFER-27-OUT 轉出至GD視訊
        '1148',  //DEPOSIT-MANUAL-IN_27 人工存入-GD視訊-存入
        '1149',  //TRANSFER-26-IN 由 沙龍視訊 轉入
        '1150',  //TRANSFER-26-OUT 轉出 至 沙龍視訊
        '1151',  //DEPOSIT-MANUAL-IN_26 人工存入-沙龍視訊-存入
        '1152',  //DEPOSIT-MANUAL-TRANSFER_26 人工存入-沙龍視訊-轉移
        '1153',  //WITHDRAWAL-MANUAL-TRANSFER_26 人工提出-沙龍視訊-轉移
        '1154',  //WITHDRAWAL-MANUAL-OUT_26 人工提出-沙龍視訊-提出
        '1155',  //DEPOSIT-26-MANUAL 沙龍視訊返點
        '1156',  //WITHDRAWAL-26-MANUAL 沙龍視訊沖銷
        '1157',  //TRANSFER-26-MUFF 轉至沙龍視訊失敗
        '1159',  //TRANSFER-28-IN 由 Gns機率 轉入
        '1160',  //TRANSFER-28-OUT 轉出 至 Gns機率
        '1161',  //DEPOSIT-MANUAL-IN_28 人工存入-Gns機率-存入
        '1162',  //DEPOSIT-MANUAL-TRANSFER_28 人工存入-Gns機率-轉移
        '1163',  //WITHDRAWAL-MANUAL-TRANSFER_28 人工提出-Gns機率-轉移
        '1164',  //WITHDRAWAL-MANUAL-OUT_28 人工提出-Gns機率-提出
        '1165',  //DEPOSIT-28-MANUAL Gns機率返點
        '1166',  //WITHDRAWAL-28-MANUAL Gns機率沖銷
        '1167',  //TRANSFER-28-MUFF 轉至Gns機率失敗
        '1168',  //DEPOSIT-23_1-MANUAL MG累積彩池返點
        '1169',  //DEPOSIT-23_2-MANUAL MG老虎機返點
        '1170',  //DEPOSIT-23_3-MANUAL MG特色遊戲返點
        '1171',  //DEPOSIT-23_4-MANUAL MG桌上遊戲返點
        '1172',  //DEPOSIT-23_14-MANUAL MG手機遊戲返點
        '1173',  //WITHDRAWAL-23_1-MANUAL MG累積彩池沖銷
        '1174',  //WITHDRAWAL-23_2-MANUAL MG老虎機沖銷
        '1175',  //WITHDRAWAL-23_3-MANUAL MG特色遊戲沖銷
        '1176',  //WITHDRAWAL-23_4-MANUAL MG桌上遊戲沖銷
        '1177',  //WITHDRAWAL-23_14-MANUAL MG手機遊戲沖銷
        '1178',  //REMOVE-USER-RECOVER-BALANCE_28 刪除帳號-Gns機率-額度回收
        '1179',  //TRANSFER-29-IN 由 ISB電子 轉入
        '1180',  //TRANSFER-29-OUT 轉出 至 ISB電子
        '1181',  //DEPOSIT-MANUAL-IN_29 人工存入-ISB電子-存入
        '1182',  //DEPOSIT-MANUAL-TRANSFER_29 人工存入-ISB電子-轉移
        '1183',  //WITHDRAWAL-MANUAL-TRANSFER_29 人工提出-ISB電子-轉移
        '1184',  //WITHDRAWAL-MANUAL-OUT_29 人工提出-ISB電子-提出
        '1185',  //DEPOSIT-29-MANUAL ISB電子返點
        '1186',  //WITHDRAWAL-29-MANUAL ISB電子沖銷
        '1187',  //TRANSFER-29-MUFF 轉至ISB電子失敗
        '1188',  //REMOVE-USER-RECOVER-BALANCE_29 刪除帳號-ISB電子-額度回收
        '1189',  //DEPOSIT-30-MANUAL BB捕魚達人返點
        '1190',  //WITHDRAWAL-30-MANUAL BB捕魚達人沖銷
        '1191',  //DEPOSIT-31-MANUAL BC體育返點
        '1192',  //WITHDRAWAL-31-MANUAL BC體育沖銷
        '1193',  //DEPOSIT-5_3-MANUAL BB老虎機返點
        '1194',  //DEPOSIT-5_5-MANUAL BB桌上遊戲返點
        '1195',  //DEPOSIT-5_82-MANUAL BB大型機台返點
        '1196',  //DEPOSIT-5_83-MANUAL BB刮刮樂返點
        '1197',  //DEPOSIT-5_85-MANUAL BB特色遊戲返點
        '1198',  //WITHDRAWAL-5_3-MANUAL BB老虎機沖銷
        '1199',  //WITHDRAWAL-5_5-MANUAL BB桌上遊戲沖銷
        '1200',  //WITHDRAWAL-5_82-MANUAL BB大型機台沖銷
        '1201',  //WITHDRAWAL-5_83-MANUAL BB刮刮樂沖銷
        '1202',  //WITHDRAWAL-5_85-MANUAL BB特色遊戲沖銷
        '1203',  //DEPOSIT-34-MANUAL 一元奪寶返點
        '1204',  //WITHDRAWAL-34-MANUAL 一元奪寶沖銷
        '1205',  //DEPOSIT-29_3-MANUAL ISB老虎機返點
        '1206',  //DEPOSIT-29_5-MANUAL ISB桌上遊戲返點
        '1207',  //DEPOSIT-29_81-MANUAL ISB累積彩池返點
        '1208',  //DEPOSIT-29_92-MANUAL ISB視訊撲克返點
        '1209',  //WITHDRAWAL-29_3-MANUAL ISB老虎機沖銷
        '1210',  //WITHDRAWAL-29_5-MANUAL ISB桌上遊戲沖銷
        '1211',  //WITHDRAWAL-29_81-MANUAL ISB累積彩池沖銷
        '1212',  //WITHDRAWAL-29_92-MANUAL ISB視訊撲克沖銷
        '1214',  //TRANSFER-33-IN 由888捕魚轉入
        '1215',  //TRANSFER-33-OUT 轉出至888捕魚
        '1216',  //DEPOSIT-MANUAL-IN_33 人工存入-888捕魚-存入
        '1217',  //DEPOSIT-MANUAL-TRANSFER_33 人工存入-888捕魚-轉移
        '1218',  //WITHDRAWAL-MANUAL-TRANSFER_33 人工提出-888捕魚-轉移
        '1219',  //WITHDRAWAL-MANUAL-OUT_33 人工提出-888捕魚-提出
        '1220',  //DEPOSIT-33-MANUAL 888捕魚返點
        '1221',  //WITHDRAWAL-33-MANUAL 888捕魚沖銷
        '1222',  //TRANSFER-33-MUFF 轉至888捕魚失敗
        '1223',  //REMOVE-USER-RECOVER-BALANCE_33 刪除帳號-888捕魚-額度回收
        '1224',  //DEPOSIT-20_3-MANUAL PT老虎機返點
        '1225',  //DEPOSIT-20_5-MANUAL PT桌上遊戲返點
        '1226',  //DEPOSIT-20_81-MANUAL PT累積彩池返點
        '1227',  //DEPOSIT-20_82-MANUAL PT大型機台返點
        '1228',  //DEPOSIT-20_83-MANUAL PT刮刮樂返點
        '1229',  //DEPOSIT-20_92-MANUAL PT視訊撲克返點
        '1230',  //WITHDRAWAL-20_3-MANUAL PT老虎機沖銷
        '1231',  //WITHDRAWAL-20_5-MANUAL PT桌上遊戲沖銷
        '1232',  //WITHDRAWAL-20_81-MANUAL PT累積彩池沖銷
        '1233',  //WITHDRAWAL-20_82-MANUAL PT大型機台沖銷
        '1234',  //WITHDRAWAL-20_83-MANUAL PT刮刮樂沖銷
        '1235',  //WITHDRAWAL-20_92-MANUAL PT視訊撲克沖銷
        '1236',  //DEPOSIT-20_0-MANUAL PT未分類返點
        '1237',  //WITHDRAWAL-20_0-MANUAL PT未分類沖銷
        '1238',  //DEPOSIT-35-MANUAL 賭神廳返點
        '1239',  //WITHDRAWAL-35-MANUAL 賭神廳沖銷
        '1240',  //DEPOSIT-12_1-MANUAL 一般彩票返點
        '1241',  //DEPOSIT-12_2-MANUAL BB快開返點
        '1242',  //DEPOSIT-12_3-MANUAL PK&11選5返點
        '1243',  //DEPOSIT-12_4-MANUAL 時時彩&快3返點
        '1244',  //DEPOSIT-12_5-MANUAL Keno返點
        '1245',  //DEPOSIT-12_6-MANUAL 十分彩返點
        '1246',  //WITHDRAWAL-12_1-MANUAL 一般彩票沖銷
        '1247',  //WITHDRAWAL-12_2-MANUAL BB快開沖銷
        '1248',  //WITHDRAWAL-12_3-MANUAL PK&11選5沖銷
        '1249',  //WITHDRAWAL-12_4-MANUAL 時時彩&快3沖銷
        '1250',  //WITHDRAWAL-12_5-MANUAL Keno沖銷
        '1251',  //WITHDRAWAL-12_6-MANUAL 十分彩沖銷
        '1252',  //TRANSFER-32-IN 由HB電子轉入
        '1253',  //TRANSFER-32-OUT 轉出至HB電子
        '1254',  //DEPOSIT-MANUAL-IN_32 人工存入-HB電子-存入
        '1255',  //DEPOSIT-MANUAL-TRANSFER_32 人工存入-HB電子-轉移
        '1256',  //WITHDRAWAL-MANUAL-TRANSFER_32 人工提出-HB電子-轉移
        '1257',  //WITHDRAWAL-MANUAL-OUT_32 人工提出-HB電子-提出
        '1258',  //TRANSFER-32-MUFF 轉至HB電子失敗
        '1259',  //REMOVE-USER-RECOVER-BALANCE_32 刪除帳號-HB電子-額度回收
        '1260',  //DEPOSIT-32_3-MANUAL HB老虎機返點
        '1261',  //DEPOSIT-32_5-MANUAL HB桌上遊戲返點
        '1262',  //DEPOSIT-32_92-MANUAL HB視訊撲克返點
        '1263',  //WITHDRAWAL-32_3-MANUAL HB老虎機沖銷
        '1264',  //WITHDRAWAL-32_5-MANUAL HB桌上遊戲沖銷
        '1265',  //WITHDRAWAL-32_92-MANUAL HB視訊撲克沖銷
        '1266',  //TRANSFER-36-IN 由BG視訊轉入
        '1267',  //TRANSFER-36-OUT 轉出至BG視訊
        '1268',  //DEPOSIT-MANUAL-IN_36 人工存入-BG視訊-存入
        '1269',  //DEPOSIT-MANUAL-TRANSFER_36 人工存入-BG視訊-轉移
        '1270',  //WITHDRAWAL-MANUAL-TRANSFER_36 人工提出-BG視訊-轉移
        '1271',  //WITHDRAWAL-MANUAL-OUT_36 人工提出-BG視訊-提出
        '1272',  //TRANSFER-36-MUFF 轉至BG視訊失敗
        '1273',  //REMOVE-USER-RECOVER-BALANCE_36 刪除帳號-BG視訊-額度回收
        '1274',  //DEPOSIT-36-MANUAL BG視訊返點
        '1275',  //WITHDRAWAL-36-MANUAL BG視訊沖銷
        '1276',  //TRANSFER-37-IN 由PP電子轉入
        '1277',  //TRANSFER-37-OUT 轉出至PP電子
        '1278',  //DEPOSIT-MANUAL-IN_37 人工存入-PP電子-存入
        '1279',  //DEPOSIT-MANUAL-TRANSFER_37 人工存入-PP電子-轉移
        '1280',  //WITHDRAWAL-MANUAL-TRANSFER_37 人工提出-PP電子-轉移
        '1281',  //WITHDRAWAL-MANUAL-OUT_37 人工提出-PP電子-提出
        '1282',  //TRANSFER-37-MUFF 轉至PP電子失敗
        '1283',  //REMOVE-USER-RECOVER-BALANCE_37 刪除帳號-PP電子-額度回收
        '1284',  //DEPOSIT-38-MANUAL BB捕魚大師返點
        '1285',  //WITHDRAWAL-38-MANUAL BB捕魚大師沖銷
        '1286',  //DEPOSIT-37_3-MANUAL PP老虎機返點
        '1287',  //DEPOSIT-37_5-MANUAL PP桌上遊戲返點
        '1288',  //DEPOSIT-37_81-MANUAL PP累積彩池返點
        '1289',  //DEPOSIT-37_85-MANUAL PP特色遊戲返點
        '1290',  //WITHDRAWAL-37_3-MANUAL PP老虎機沖銷
        '1291',  //WITHDRAWAL-37_5-MANUAL PP桌上遊戲沖銷
        '1292',  //WITHDRAWAL-37_81-MANUAL PP累積彩池沖銷
        '1293',  //WITHDRAWAL-37_85-MANUAL PP特色遊戲沖銷
        '1294',  //TRANSFER-39-IN 由JDB電子轉入
        '1295',  //TRANSFER-39-OUT 轉出至JDB電子
        '1296',  //DEPOSIT-MANUAL-IN_39 人工存入-JDB電子-存入
        '1297',  //DEPOSIT-MANUAL-TRANSFER_39 人工存入-JDB電子-轉移
        '1298',  //WITHDRAWAL-MANUAL-TRANSFER_39 人工提出-JDB電子-轉移
        '1299',  //WITHDRAWAL-MANUAL-OUT_39 人工提出-JDB電子-提出
        '1300',  //TRANSFER-39-MUFF 轉至JDB電子失敗
        '1301',  //REMOVE-USER-RECOVER-BALANCE_39 刪除帳號-JDB電子-額度回收
        '1302',  //TRANSFER-40-IN 由AG電子轉入
        '1303',  //TRANSFER-40-OUT 轉出至AG電子
        '1304',  //DEPOSIT-MANUAL-IN_40 人工存入-AG電子-存入
        '1305',  //DEPOSIT-MANUAL-TRANSFER_40 人工存入-AG電子-轉移
        '1306',  //WITHDRAWAL-MANUAL-TRANSFER_40 人工提出-AG電子-轉移
        '1307',  //WITHDRAWAL-MANUAL-OUT_40 人工提出-AG電子-提出
        '1308',  //TRANSFER-40-MUFF 轉至AG電子失敗
        '1309',  //REMOVE-USER-RECOVER-BALANCE_40 刪除帳號-AG電子-額度回收
        '1310',  //TRANSFER-41-IN 由MW電子轉入
        '1311',  //TRANSFER-41-OUT 轉出至MW電子
        '1312',  //DEPOSIT-MANUAL-IN_41 人工存入-MW電子-存入
        '1313',  //DEPOSIT-MANUAL-TRANSFER_41 人工存入-MW電子-轉移
        '1314',  //WITHDRAWAL-MANUAL-TRANSFER_41 人工提出-MW電子-轉移
        '1315',  //WITHDRAWAL-MANUAL-OUT_41 人工提出-MW電子-提出
        '1316',  //TRANSFER-41-MUFF 轉至MW電子失敗
        '1317',  //REMOVE-USER-RECOVER-BALANCE_41 刪除帳號-MW電子-額度回收
        '1318',  //DEPOSIT-20_91-MANUAL PT捕魚機返點
        '1319',  //WITHDRAWAL-20_91-MANUAL PT捕魚機沖銷
        '1320',  //DEPOSIT-28_3-MANUAL GNS老虎機返點
        '1321',  //WITHDRAWAL-28_3-MANUAL GNS老虎機沖銷
        '1322',  //DEPOSIT-28_91-MANUAL GNS捕魚機返點
        '1323',  //WITHDRAWAL-28_91-MANUAL GNS捕魚機沖銷
        '1324',  //DEPOSIT-39_3-MANUAL JDB老虎機返點
        '1325',  //DEPOSIT-39_82-MANUAL JDB大型機台返點
        '1326',  //DEPOSIT-39_91-MANUAL JDB捕魚機返點
        '1327',  //WITHDRAWAL-39_3-MANUAL JDB老虎機沖銷
        '1328',  //WITHDRAWAL-39_82-MANUAL JDB大型機台沖銷
        '1329',  //WITHDRAWAL-39_91-MANUAL JDB捕魚機沖銷
        '1330',  //DEPOSIT-40_3-MANUAL AG老虎機返點
        '1331',  //DEPOSIT-40_5-MANUAL AG桌上遊戲返點
        '1332',  //DEPOSIT-40_81-MANUAL AG累積彩池返點
        '1333',  //DEPOSIT-40_91-MANUAL AG捕魚機返點
        '1334',  //DEPOSIT-40_92-MANUAL AG視頻撲克返點
        '1335',  //WITHDRAWAL-40_3-MANUAL AG老虎機沖銷
        '1336',  //WITHDRAWAL-40_5-MANUAL AG桌上遊戲沖銷
        '1337',  //WITHDRAWAL-40_81-MANUAL AG累積彩池沖銷
        '1338',  //WITHDRAWAL-40_91-MANUAL AG捕魚機沖銷
        '1339',  //WITHDRAWAL-40_92-MANUAL AG視頻撲克沖銷
        '1340',  //DEPOSIT-Bitcoin-IN 比特幣入款
        '1341',  //WITHDRAWAL-Bitcoin 比特幣出款
        '1342',  //DEPOSIT-Bitcoin-WITHDRAWAL_CANCEL 比特幣取消出款
        '1343',  //DEPOSIT-41_3-MANUAL MW老虎機返點
        '1344',  //DEPOSIT-41_5-MANUAL MW桌上遊戲返點
        '1345',  //DEPOSIT-41_82-MANUAL MW大型機台返點
        '1346',  //DEPOSIT-41_91-MANUAL MW捕魚機返點
        '1347',  //WITHDRAWAL-41_3-MANUAL MW老虎機沖銷
        '1348',  //WITHDRAWAL-41_5-MANUAL MW桌上遊戲沖銷
        '1349',  //WITHDRAWAL-41_82-MANUAL MW大型機台沖銷
        '1350',  //WITHDRAWAL-41_91-MANUAL MW捕鱼機沖銷
        '1351',  //DEPOSIT-MANUAL-FISHING_XMAS_ACTIVITY 捕魚聖誕活動獎金
        '1352',  //DEPOSIT-43-MANUAL IN體育返點
        '1353',  //WITHDRAWAL-43-MANUAL IN體育沖銷
        '1354',  //TRANSFER-42-IN 由RT電子轉入
        '1355',  //TRANSFER-42-OUT 轉出至RT電子
        '1356',  //DEPOSIT-MANUAL-IN_42 人工存入-RT電子-存入
        '1357',  //DEPOSIT-MANUAL-TRANSFER_42 人工存入-RT電子-轉移
        '1358',  //WITHDRAWAL-MANUAL-TRANSFER_42 人工提出-RT電子-轉移
        '1359',  //WITHDRAWAL-MANUAL-OUT_42 人工提出-RT電子-提出
        '1360',  //TRANSFER-42-MUFF 轉至RT電子失敗
        '1361',  //REMOVE-USER-RECOVER-BALANCE_42 刪除帳號-RT電子-額度回收
        '1362',  //TRANSFER-44-IN 由SG電子轉入
        '1363',  //TRANSFER-44-OUT 轉出至SG電子
        '1364',  //DEPOSIT-MANUAL-IN_44 人工存入-SG電子-存入
        '1365',  //DEPOSIT-MANUAL-TRANSFER_44 人工存入-SG電子-轉移
        '1366',  //WITHDRAWAL-MANUAL-TRANSFER_44 人工提出-SG電子-轉移
        '1367',  //WITHDRAWAL-MANUAL-OUT_44 人工提出-SG電子-提出
        '1368',  //TRANSFER-44-MUFF 轉至SG電子失敗
        '1369',  //REMOVE-USER-RECOVER-BALANCE_44 刪除帳號-SG電子-額度回收
        '1370',  //DEPOSIT-MANUAL-LOTTERY_CNY_ACTIVITY 彩票紅包活動
        '1371',  //TRANSFER-45-IN 由VR彩票轉入
        '1372',  //TRANSFER-45-OUT 轉出至VR彩票
        '1373',  //DEPOSIT-MANUAL-IN_45 人工存入-VR彩票-存入
        '1374',  //DEPOSIT-MANUAL-TRANSFER_45 人工存入-VR彩票-轉移
        '1375',  //WITHDRAWAL-MANUAL-TRANSFER_45 人工提出-VR彩票-轉移
        '1376',  //WITHDRAWAL-MANUAL-OUT_45 人工提出-VR彩票-提出
        '1377',  //TRANSFER-45-MUFF 轉至VR彩票失敗
        '1378',  //REMOVE-USER-RECOVER-BALANCE_45 刪除帳號-VR彩票-額度回收
        '1379',  //DEPOSIT-42_3-MANUAL RT老虎機返點
        '1380',  //DEPOSIT-42_5-MANUAL RT桌上遊戲返點
        '1381',  //WITHDRAWAL-42_3-MANUAL RT老虎機沖銷
        '1382',  //WITHDRAWAL-42_5-MANUAL RT桌上遊戲沖銷
        '1383',  //DEPOSIT-44_3-MANUAL SG老虎機返點
        '1384',  //DEPOSIT-44_5-MANUAL SG桌上遊戲返點
        '1385',  //DEPOSIT-44_81-MANUAL SG累積彩池返點
        '1386',  //DEPOSIT-44_82-MANUAL SG大型機台返點
        '1387',  //WITHDRAWAL-44_3-MANUAL SG老虎機沖銷
        '1388',  //WITHDRAWAL-44_5-MANUAL SG桌上遊戲沖銷
        '1389',  //WITHDRAWAL-44_81-MANUAL SG累積彩池沖銷
        '1390',  //WITHDRAWAL-44_82-MANUAL SG大型機台沖銷
        '1391',  //DEPOSIT-45_1-MANUAL VR真人彩返點
        '1392',  //DEPOSIT-45_2-MANUAL VR國家彩返點
        '1393',  //DEPOSIT-45_3-MANUAL VR六合彩返點
        '1394',  //WITHDRAWAL-45_1-MANUAL VR真人彩沖銷
        '1395',  //WITHDRAWAL-45_2-MANUAL VR國家彩沖銷
        '1396',  //WITHDRAWAL-45_3-MANUAL VR六合彩沖銷
        '1397',  //TRANSFER-47-IN 由EVO視訊轉入
        '1398',  //TRANSFER-47-OUT 轉出至EVO視訊
        '1399',  //DEPOSIT-MANUAL-IN_47 人工存入-EVO視訊-存入
        '1400',  //DEPOSIT-MANUAL-TRANSFER_47 人工存入-EVO視訊-轉移
        '1401',  //WITHDRAWAL-MANUAL-TRANSFER_47 人工提出-EVO視訊-轉移
        '1402',  //WITHDRAWAL-MANUAL-OUT_47 人工提出-EVO視訊-提出
        '1403',  //TRANSFER-47-MUFF 轉至EVO視訊失敗
        '1404',  //REMOVE-USER-RECOVER-BALANCE_47 刪除帳號-EVO視訊-額度回收
        '1405',  //TRANSFER-48-IN 由BNG電子轉入
        '1406',  //TRANSFER-48-OUT 轉出至BNG電子
        '1407',  //DEPOSIT-MANUAL-IN_48 人工存入-BNG電子-存入
        '1408',  //DEPOSIT-MANUAL-TRANSFER_48 人工存入-BNG電子-轉移
        '1409',  //WITHDRAWAL-MANUAL-TRANSFER_48 人工提出-BNG電子-轉移
        '1410',  //WITHDRAWAL-MANUAL-OUT_48 人工提出-BNG電子-提出
        '1411',  //TRANSFER-48-MUFF 轉至BNG電子失敗
        '1412',  //REMOVE-USER-RECOVER-BALANCE_48 刪除帳號-BNG電子-額度回收
        '1413',  //TRANSFER-46-IN 由PTⅡ電子轉入
        '1414',  //TRANSFER-46-OUT 轉出至PTⅡ電子
        '1415',  //DEPOSIT-MANUAL-IN_46 人工存入-PTⅡ電子-存入
        '1416',  //DEPOSIT-MANUAL-TRANSFER_46 人工存入-PTⅡ電子-轉移
        '1417',  //WITHDRAWAL-MANUAL-TRANSFER_46 人工提出-PTⅡ電子-轉移
        '1418',  //WITHDRAWAL-MANUAL-OUT_46 人工提出-PTⅡ電子-提出
        '1419',  //TRANSFER-46-MUFF 轉至PTⅡ電子失敗
        '1420',  //REMOVE-USER-RECOVER-BALANCE_46 刪除帳號-PTⅡ電子-額度回收
        '1421',  //DEPOSIT-46_3-MANUAL PTⅡ老虎機返點
        '1422',  //DEPOSIT-46_81-MANUAL PTⅡ累積彩池返點
        '1423',  //DEPOSIT-46_91-MANUAL PTⅡ捕魚機返點
        '1424',  //WITHDRAWAL-46_3-MANUAL PTⅡ老虎機沖銷
        '1425',  //WITHDRAWAL-46_81-MANUAL PTⅡ累積彩池沖銷
        '1426',  //WITHDRAWAL-46_91-MANUAL PTⅡ捕魚機沖銷
        '1427',  //DEPOSIT-46_5-MANUAL PTⅡ桌上遊戲返點
        '1429',  //WITHDRAWAL-46_5-MANUAL PTⅡ桌上遊戲沖銷
        '1431',  //DEPOSIT-48_3-MANUAL BNG老虎機返點
        '1432',  //WITHDRAWAL-48_3-MANUAL BNG老虎機沖銷
        '1433',  //DEPOSIT-MANUAL-SPORT_FIFA_ACTIVITY BBIN世足活動獎金
        '1434',  //DEPOSIT-47-MANUAL EVO視訊返點
        '1435',  //WITHDRAWAL-47-MANUAL EVO視訊沖銷
        '1436',  //DEPOSIT-28_81-MANUAL GNS累積彩池返點
        '1437',  //WITHDRAWAL-28_81-MANUAL GNS累積彩池沖銷
        '1438',  //TRANSFER-49-IN 由開元 棋牌轉入
        '1439',  //TRANSFER-49-OUT 轉出至開元 棋牌
        '1440',  //DEPOSIT-MANUAL-IN_49 人工存入-開元 棋牌-存入
        '1441',  //DEPOSIT-MANUAL-TRANSFER_49 人工存入-開元 棋牌-轉移
        '1442',  //WITHDRAWAL-MANUAL-TRANSFER_49 人工提出-開元 棋牌-轉移
        '1443',  //WITHDRAWAL-MANUAL-OUT_49 人工提出-開元 棋牌-提出
        '1444',  //TRANSFER-49-MUFF 轉至開元 棋牌失敗
        '1445',  //REMOVE-USER-RECOVER-BALANCE_49 刪除帳號-開元 棋牌-額度回收
        '1446',  //DEPOSIT-28_85-MANUAL GNS特色遊戲返點
        '1447',  //WITHDRAWAL-28_85-MANUAL GNS特色遊戲沖銷
        '1448',  //DEPOSIT-49-MANUAL 開元棋牌返點
        '1449',  //WITHDRAWAL-49-MANUAL 開元棋牌沖銷
        '1450',  //DEPOSIT-28_5-MANUAL GNS桌上遊戲返點
        '1451',  //WITHDRAWAL-28_5-MANUAL GNS桌上遊戲沖銷
        '1452',  //TRANSFER-50-IN 由WM電子轉入
        '1453',  //TRANSFER-50-OUT 轉出至WM電子
        '1454',  //DEPOSIT-MANUAL-IN_50 人工存入-WM電子-存入
        '1455',  //DEPOSIT-MANUAL-TRANSFER_50 人工存入-WM電子-轉移
        '1456',  //WITHDRAWAL-MANUAL-TRANSFER_50 人工提出-WM電子-轉移
        '1457',  //WITHDRAWAL-MANUAL-OUT_50 人工提出-WM電子-提出
        '1458',  //TRANSFER-50-MUFF 轉至WM電子失敗
        '1459'   //REMOVE-USER-RECOVER-BALANCE_50 刪除帳號-WM電子-額度回收
    ];
}
