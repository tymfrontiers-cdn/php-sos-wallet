SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `user` char(12) NOT NULL,
  `currency` char(12) NOT NULL,
  `balance` varchar(512) NOT NULL,
  `_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `wallet_history`;
CREATE TABLE `wallet_history` (
  `id` int(11) NOT NULL,
  `user` char(32) NOT NULL,
  `type` char(16) NOT NULL,
  `currency` CHAR(8) NOT NULL,
  `amount` DECIMAL(18,6) NOT NULL,
  `new_balance` DECIMAL(18,6) NOT NULL,
  `narration` text NOT NULL,
  `_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`),
  ADD KEY `currency` (`currency`);

ALTER TABLE `wallet_history`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `wallet_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
