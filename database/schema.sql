CREATE TABLE IF NOT EXISTS master_icons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    url TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS hardware (
    id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(255) NOT NULL,
    IP VARCHAR(50),
    Hersteller VARCHAR(255),
    icon_id INT,
    parent_id INT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (icon_id) REFERENCES master_icons(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES hardware(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS hardware_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hardware_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    file_type VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hardware_id) REFERENCES hardware(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS hardware_tags (
    hardware_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (hardware_id, tag_id),
    FOREIGN KEY (hardware_id) REFERENCES hardware(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hardware_id INT NOT NULL,
    status_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    port INT,
    protocol VARCHAR(20) DEFAULT 'TCP',
    FOREIGN KEY (hardware_id) REFERENCES hardware(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES statuses(id) ON DELETE RESTRICT
);
