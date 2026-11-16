CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- EMERGENCY CONTACTS TABLE
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    relationship VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- EMERGENCY ALERTS TABLE
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    incident_type VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    status ENUM('Pending', 'Responding', 'Resolved')
        DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    contact_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES alerts(id)
        ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES emergency_contacts(id)
        ON DELETE CASCADE
);

UPDATE users
SET role = 'admin'
WHERE email = 'admin@example.com';

SELECT id, fullname, email, role
FROM users
ORDER BY id DESC;

ALTER TABLE alerts
ADD COLUMN acknowledged_at DATETIME NULL,
ADD COLUMN escalation_status VARCHAR(30) NOT NULL DEFAULT 'Waiting',
ADD COLUMN escalated_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS escalation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    recipient VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    delivery_status VARCHAR(30) NOT NULL DEFAULT 'Simulated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS escalation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    response_minutes INT NOT NULL DEFAULT 2,
    police_contact_name VARCHAR(150) DEFAULT NULL,
    police_phone VARCHAR(30) DEFAULT NULL,
    police_email VARCHAR(150) DEFAULT NULL,
    notification_method VARCHAR(20) NOT NULL DEFAULT 'Demo',
    auto_escalation_enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO escalation_settings
    (id, response_minutes, notification_method, auto_escalation_enabled)
VALUES
    (1, 2, 'Demo', 1)
ON DUPLICATE KEY UPDATE id = id;