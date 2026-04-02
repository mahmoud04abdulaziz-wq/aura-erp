-- Migration: Add role_id to employees & create admin permission
-- Run this against aura_stone_erp database

-- 1. Add role_id column to employees table
ALTER TABLE `employees` ADD COLUMN `role_id` INT DEFAULT NULL AFTER `department_id`;
ALTER TABLE `employees` ADD CONSTRAINT `fk_employees_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`);

-- 2. Backfill: copy existing role_id from users table into employees
UPDATE `employees` e
JOIN `users` u ON e.employee_id = u.employee_id
SET e.role_id = u.role_id;

-- 3. Add 'admin' permission if it doesn't exist
INSERT IGNORE INTO `permissions` (`module_access`, `description`) 
VALUES ('admin', 'Access to User Account Management panel');

-- 4. Assign admin permission to Admin role (role_id = 1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, permission_id FROM `permissions` WHERE module_access = 'admin';
