<?php
require_once __DIR__ . '/config/db_connect.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leave_tickets (
            ticket_id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            reason TEXT,
            manager_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            hr_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Add HR Manager role
    $pdo->exec("INSERT IGNORE INTO roles (role_id, role_name, description) VALUES (5, 'HR Manager', 'Human Resources administration')");
    
    // Give HR Manager the HR permission (permission_id = 3 according to seed.php)
    $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (5, 1)"); // dashboard
    $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (5, 3)"); // hr
    
    // Create an HR test user
    // First, create an employee record for the HR manager
    $pdo->exec("INSERT IGNORE INTO employees (employee_id, first_name, last_name, email, hire_date, department_id, verification_status) 
                VALUES (999, 'Huda', 'HR', 'huda.hr@company.com', '2020-01-01', 1, 'Approved')");
                
    // Then create a user login for Huda
    $hash = password_hash('Admin@123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO users (user_id, email, password_hash, role_id, employee_id) 
                VALUES (999, 'huda.hr@company.com', '$hash', 5, 999)");

    echo json_encode(['success' => true, 'msg' => 'HR Manager role and test user created.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
