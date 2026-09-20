CREATE DATABASE IF NOT EXISTS ruta9;

USE ruta9;

CREATE TABLE IF NOT EXISTS Users (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'STOCK', 'SALES') NOT NULL DEFAULT 'SALES',
    created DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Vehicles (
    id VARCHAR(36) PRIMARY KEY,
    brand VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS VehicleImages (
    id VARCHAR(36) PRIMARY KEY,
    vehicle_id VARCHAR(36) NOT NULL,
    url VARCHAR(512) NOT NULL,

    CONSTRAINT fk_image_vehicle FOREIGN KEY (vehicle_id) REFERENCES Vehicles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Sales (
    id VARCHAR(36) PRIMARY KEY,
    employee_id VARCHAR(36) NOT NULL,
    vehicle_id VARCHAR(36) NOT NULL,
    paid_amount DECIMAL(10, 2) NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sale_employee FOREIGN KEY (employee_id) REFERENCES Users(id),
    CONSTRAINT fk_sale_vehicle FOREIGN KEY (vehicle_id) REFERENCES Vehicles(id)
);

INSERT IGNORE INTO Users
    (id, name, last_name, email, role, password_hash)
VALUES
    /* pwd:admin */
    ('00000000-0000-0000-0000-000000000000', 'System', 'Administrator', 'admin@ruta9.ar', 'ADMIN', '$argon2id$v=19$m=65536,t=4,p=1$VUk4N0wzZFFRVzdWVWFGSQ$/p3Q4GJiSzyJqV9kX/08av6TxfDGTBnsKLRTgqXWFwg')
;
