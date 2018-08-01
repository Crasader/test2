// History 資料庫 SQL Schema

CREATE TABLE IF NOT EXISTS `cash_entry` (
  `id` bigint(20) NOT NULL,
  `at` bigint(20) NOT NULL,
  `cash_id` int(11) NOT NULL,
  `opcode` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `amount` decimal(16,4) NOT NULL,
  `memo` varchar(100) NOT NULL,
  `balance` decimal(16,4) NOT NULL,
  `ref_id` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`,`at`),
  KEY `IDX_181F065C3D7A0C28` (`cash_id`),
  KEY `idx_cash_entry_created_at` (`created_at`),
  KEY `idx_cash_entry_ref_id` (`ref_id`),
  KEY `idx_cash_entry_opcode` (`opcode`),
  KEY `idx_cash_entry_at` (`at`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cash_entry_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `start_id` bigint(20) UNSIGNED NOT NULL,
  `end_id` bigint(20) UNSIGNED NOT NULL,
  `error_message` varchar(50) NOT NULL,
  `insert_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
