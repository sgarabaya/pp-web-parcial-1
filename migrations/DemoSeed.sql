USE ruta9;

INSERT IGNORE INTO Users
    (id, name, last_name, email, role, password_hash)
VALUES
    (UUID(), 'Jorge', 'Perez', 'jorge.perez@ruta9.ar', 'STOCK', /* pwd:jorge */ '$argon2id$v=19$m=65536,t=4,p=1$enFPelp5VFNlS25WZ2dDTg$hHEoOk6LzHDZjm05dvutaptp8aGDoBkblvG7uqWBCdQ'),
    (UUID(), 'Florencia', 'Flores', 'florencia.flores@ruta9.ar', 'SALES', /* pwd:flor */ '$argon2id$v=19$m=65536,t=4,p=1$Zk9LekJCU2pSWjFJcG5TWg$rjI7Ywqatn08DLO/OaHgZwAMC7vIGfZhAibEstZf4uo'),
    (UUID(), 'Enzo', 'Garcia', 'enzo.garcia@ruta9.ar', 'SALES', /* pwd:enzo */ '$argon2id$v=19$m=65536,t=4,p=1$RERMVnhNZ2ZWN1lPWXE2ZA$sR02YaOQ6Z+9ZD7PcsnVbbiUqqJw3zspKi0oIyVacZs'),
    (UUID(), 'Elva', 'Bozzo', 'elva.bozzo@ruta9.ar', 'SALES', /* pwd:elva */ '$argon2id$v=19$m=65536,t=4,p=1$N3ZNd3ZobU80UGpsWUVhYQ$8cQvZwMIZxOMONl68wQF/5kkyUoiEdKuAICuFrFmezw')
;

INSERT IGNORE INTO Vehicles (id, brand, model, year, price, stock)
VALUES
    (UUID(), 'Audi', 'A4', 2021, 40000.0, 1),
    (UUID(), 'BMW', '3 Series', 2021, 41000.0, 3),
    (UUID(), 'Chevrolet', 'Silverado', 2022, 35000.0, 7),
    (UUID(), 'Ford', 'Escape', 2020, 26000.0, 8),
    (UUID(), 'Ford', 'Explorer', 2021, 33000.0, 6),
    (UUID(), 'Ford', 'F-Series', 2022, 35000.0, 13),
    (UUID(), 'Ford', 'Focus', 2019, 20000.0, 6),
    (UUID(), 'Honda', 'Accord', 2020, 25000.0, 4),
    (UUID(), 'Honda', 'CR-V', 2022, 28000.0, 13),
    (UUID(), 'Honda', 'Civic', 2021, 22000.0, 2),
    (UUID(), 'Honda', 'HR-V', 2021, 22000.0, 1),
    (UUID(), 'Hyundai', 'Elantra', 2021, 20000.0, 14),
    (UUID(), 'Hyundai', 'Santa Fe', 2021, 28000.0, 2),
    (UUID(), 'Hyundai', 'Tucson', 2022, 26000.0, 1),
    (UUID(), 'Kia', 'Rio', 2020, 16000.0, 7),
    (UUID(), 'Kia', 'Sorento', 2021, 26000.0, 4),
    (UUID(), 'Kia', 'Sportage', 2022, 25000.0, 14),
    (UUID(), 'Mercedes-Benz', 'C-Class', 2021, 56000.0, 14),
    (UUID(), 'Nissan', 'Qashqai', 2021, 24000.0, 4),
    (UUID(), 'Nissan', 'Rogue', 2021, 26000.0, 0),
    (UUID(), 'Nissan', 'Sentra', 2021, 20000.0, 13),
    (UUID(), 'Subaru', 'Forester', 2021, 25000.0, 3),
    (UUID(), 'Tesla', 'Model 3', 2022, 47000.0, 9),
    (UUID(), 'Tesla', 'Model Y', 2023, 50000.0, 8),
    (UUID(), 'Toyota', 'Camry', 2021, 26000.0, 5),
    (UUID(), 'Toyota', 'Corolla', 2020, 22000.0, 7),
    (UUID(), 'Toyota', 'Highlander', 2021, 36000.0, 0),
    (UUID(), 'Toyota', 'Hilux', 2022, 30000.0, 14),
    (UUID(), 'Toyota', 'RAV4', 2022, 30000.0, 4),
    (UUID(), 'Toyota', 'Tacoma', 2021, 27000.0, 8),
    (UUID(), 'Toyota', 'Yaris', 2020, 17000.0, 2),
    (UUID(), 'Volkswagen', 'Golf', 2020, 24000.0, 15),
    (UUID(), 'Volkswagen', 'Passat', 2020, 25000.0, 10),
    (UUID(), 'Volkswagen', 'Polo', 2020, 18000.0, 1),
    (UUID(), 'Volkswagen', 'Tiguan', 2022, 27000.0, 7)
;

CREATE TEMPORARY TABLE temp_clients (
    client_name VARCHAR(40) PRIMARY KEY,
    client_contact VARCHAR(40) NOT NULL
);
INSERT INTO temp_clients
    (client_name, client_contact)
VALUES
    ('Mateo Gonzalez', 'mateo.g@yahoo.com.ar'),
    ('Sofia Rodriguez', '+54 11 5555-1234'),
    ('Lucas Perez', 'lucas.perez99@gmail.com'),
    ('Valentina Lopez', '+34 612 345 678'),
    ('Joaquin Martinez', 'j.martinez@outlook.com'),
    ('Camila Gomez', '+52 55 1234 5678'),
    ('Bautista Sanchez', 'bautista.s@yahoo.com.ar'),
    ('Valeria Diaz', '+54 11 4444-9876'),
    ('Emiliano Torres', 'emiliano.t@gmail.com'),
    ('Lucia Ramirez', '+34 699 888 777'),
    ('Santino Flores', 'santino.flores@outlook.com'),
    ('Florencia Acosta', '+52 55 9876 5432'),
    ('Thiago Rojas', 'trojas88@yahoo.com.ar'),
    ('Martina Medina', '+54 11 2222-3333'),
    ('Ignacio Suarez', 'isuarez@gmail.com'),
    ('Catalina Cruz', '+34 677 111 222'),
    ('Felipe Ortiz', 'felipe.ortiz@yahoo.com.ar'),
    ('Julieta Silva', '+52 55 4444 5555'),
    ('Benicio Reyes', 'breyes@outlook.com'),
    ('Isabella Gutierrez', '+54 11 6666-7777'),
    ('Agustin Castro', 'agustin.castro@gmail.com'),
    ('Micaela Ruiz', '+34 655 444 333'),
    ('Tomas Morales', 'tmorales@yahoo.com.ar'),
    ('Victoria Herrera', '+52 55 7777 8888'),
    ('Facundo Luna', 'facundo.luna@outlook.com'),
    ('Delfina Mendoza', '+54 11 9999-0000'),
    ('Nicolas Rios', 'nicolas.rios@gmail.com'),
    ('Pilar Aguilar', '+34 644 222 111'),
    ('Matias Navarro', 'mnavarro@yahoo.com.ar'),
    ('Renata Dominguez', '+52 55 2222 1111'),
    ('Gonzalo Vega', 'gonzalo.vega@outlook.com'),
    ('Josefina Blanco', '+54 11 8888-4444'),
    ('Marcos Molina', 'marcos.molina@gmail.com'),
    ('Candela Castro', '+34 633 555 777'),
    ('Leandro Cabrera', 'lcabrera@yahoo.com.ar'),
    ('Antonella Rojas', '+52 55 6666 9999'),
    ('Maximiliano Campos', 'maxi.campos@outlook.com'),
    ('Bianca Gimenez', '+54 11 3333-2222'),
    ('Julian Mendez', 'julian.mendez@gmail.com'),
    ('Lola Salazar', '+34 622 999 000')
;

INSERT INTO Sales
    (id, user_id, vehicle_id, paid_amount, client_name, client_contact, payment_method, created)
SELECT
    UUID(),
    (SELECT id FROM Users WHERE role = 'SALES' ORDER BY RAND() LIMIT 1),
    (SELECT id FROM Vehicles ORDER BY RAND() LIMIT 1),
    ROUND(RAND() * 40000 + 10000, 2),
    client_name,
    client_contact,
    ELT(FLOOR(RAND() * 4) + 1, 'CASH', 'FINANCED', 'EXCHANGE+CASH', 'EXCHANGE+FINANCED'),
    FROM_UNIXTIME(UNIX_TIMESTAMP('2024-01-01 00:00:00') + FLOOR(RAND() * (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP('2024-01-01 00:00:00'))))
FROM temp_clients
;
