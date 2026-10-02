ALTER TABLE `users`
  ADD COLUMN `can_login` TINYINT(1) NOT NULL DEFAULT 1 AFTER `marriage_bureau_id`;

UPDATE `users`
SET `can_login` = 0
WHERE `marriage_bureau_id` IS NOT NULL;
