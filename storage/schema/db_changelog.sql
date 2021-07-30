--
-- Email template of Deposit and Payout via Admin
--
INSERT INTO `email_templates` (`language_id`, `temp_id`, `subject`, `body`, `lang`, `type`) VALUES
(1, 30, 'Notice of Deposit via System Administrator!', 'Hi,\r\n                                <br><br>Amount {amount} was deposited by System Administrator.\r\n\r\n                                <br><br><b><u><i>Here’s a brief overview of the Deposit:</i></u></b>\r\n\r\n                                <br><br><b><u>Created at:</u></b> {created_at}\r\n\r\n                                <br><br><b><u>Transaction ID:</u></b> {uuid}\r\n\r\n                                <br><br><b><u>Currency:</u></b> {code}\r\n\r\n                                <br><br><b><u>Amount:</u></b> {amount}\r\n\r\n                                <br><br><b><u>Fee:</u></b> {fee}\r\n\r\n                                <br><br>If you have any questions, please feel free to reply to this email.\r\n                                <br><br>Regards,\r\n                                <br><b>{soft_name}</b>\r\n                                ', 'en', 'email'),
(2, 30, '', '', 'ar', 'email'),
(3, 30, '', '', 'fr', 'email'),
(4, 30, '', '', 'pt', 'email'),
(5, 30, '', '', 'ru', 'email'),
(6, 30, '', '', 'es', 'email'),
(7, 30, '', '', 'tr', 'email'),
(8, 30, '', '', 'ch', 'email'),
(1, 31, 'Notice of Payout via System Administrator!', 'Hi,\r\n                                <br><br>Amount {amount} was withdrawn by System Administrator.\r\n\r\n                                <br><br><b><u><i>Here’s a brief overview of the Deposit:</i></u></b>\r\n\r\n                                <br><br><b><u>Created at:</u></b> {created_at}\r\n\r\n                                <br><br><b><u>Transaction ID:</u></b> {uuid}\r\n\r\n                                <br><br><b><u>Currency:</u></b> {code}\r\n\r\n                                <br><br><b><u>Amount:</u></b> {amount}\r\n\r\n                                <br><br><b><u>Fee:</u></b> {fee}\r\n\r\n                                <br><br>If you have any questions, please feel free to reply to this email.\r\n                                <br><br>Regards,\r\n                                <br><b>{soft_name}</b>\r\n                                ', 'en', 'email'),
(2, 31, '', '', 'ar', 'email'),
(3, 31, '', '', 'fr', 'email'),
(4, 31, '', '', 'pt', 'email'),
(5, 31, '', '', 'ru', 'email'),
(6, 31, '', '', 'es', 'email'),
(7, 31, '', '', 'tr', 'email'),
(8, 31, '', '', 'ch', 'email');


--
-- Countries table default column added
--

ALTER TABLE `countries` ADD `is_default` VARCHAR(5) NOT NULL DEFAULT 'no' AFTER `phone_code`;

UPDATE `countries` SET `is_default` = 'yes' WHERE `countries`.`short_name` = 'US';