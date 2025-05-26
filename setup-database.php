<?php
require_once 'db_connection.php';

try {
    // Create jobs table
    $sql = "CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        job_type ENUM('manual','casual','seasonal','domestic','other') NOT NULL,
        county VARCHAR(50) NOT NULL,
        location VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        salary DECIMAL(10,2) NOT NULL,
        start_date DATE NOT NULL,
        duration ENUM('1day','1week','2weeks','1month','3months','6months','1year','permanent') NOT NULL,
        experience ENUM('noexperience','1year','3years','5years'),
        certifications VARCHAR(255),
        requirements TEXT,
        company_name VARCHAR(255) NOT NULL,
        industry ENUM('construction','agriculture','manufacturing','hospitality','transport','domestic','other'),
        company_description TEXT,
        contact_name VARCHAR(100) NOT NULL,
        contact_phone VARCHAR(20) NOT NULL,
        contact_email VARCHAR(100) NOT NULL,
        contact_method ENUM('phone','whatsapp','email','any') NOT NULL,
        is_featured BOOLEAN DEFAULT FALSE,
        is_urgent BOOLEAN DEFAULT FALSE,
        payment_status ENUM('pending','completed','failed') DEFAULT 'pending',
        mpesa_transaction_id VARCHAR(50),
        payment_amount DECIMAL(10,2) DEFAULT 100.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->exec($sql);
    echo "Table 'jobs' created successfully<br>";

    // Create payments table
    $sql = "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        mpesa_request_id VARCHAR(50) NOT NULL,
        mpesa_checkout_id VARCHAR(50) NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending','completed','failed') DEFAULT 'pending',
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->exec($sql);
    echo "Table 'payments' created successfully<br>";

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        user_type ENUM('worker','employer','admin') NOT NULL,
        county VARCHAR(50),
        skills TEXT,
        experience TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->exec($sql);
    echo "Table 'users' created successfully<br>";

    // Create applications table
    $sql = "CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        user_id INT NOT NULL,
        status ENUM('pending','shortlisted','rejected','hired') DEFAULT 'pending',
        application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->exec($sql);
    echo "Table 'applications' created successfully<br>";

    // Create admin user
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, phone, password, user_type) 
            VALUES ('Admin', 'admin@labourlinks.com', '700000000', ?, 'admin')
            ON DUPLICATE KEY UPDATE password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$password, $password]);
    echo "Admin user created/updated successfully<br>";

} catch(PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}

$conn = null;
?>