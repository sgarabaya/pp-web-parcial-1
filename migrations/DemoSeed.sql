USE ruta9;

INSERT IGNORE INTO Users
    (id, name, last_name, email, role, password_hash)
VALUES
    (UUID(), 'Jorge', 'Perez', 'jorge.perez@ruta9.ar', 'STOCK', /* pwd:jorge */ '$argon2id$v=19$m=65536,t=4,p=1$enFPelp5VFNlS25WZ2dDTg$hHEoOk6LzHDZjm05dvutaptp8aGDoBkblvG7uqWBCdQ'),
    (UUID(), 'Florencia', 'Flores', 'florencia.flores@ruta9.ar', 'SALES', /* pwd:flor */ '$argon2id$v=19$m=65536,t=4,p=1$Zk9LekJCU2pSWjFJcG5TWg$rjI7Ywqatn08DLO/OaHgZwAMC7vIGfZhAibEstZf4uo'),
    (UUID(), 'Enzo', 'Garcia', 'enzo.garcia@ruta9.ar', 'SALES', /* pwd:enzo */ '$argon2id$v=19$m=65536,t=4,p=1$RERMVnhNZ2ZWN1lPWXE2ZA$sR02YaOQ6Z+9ZD7PcsnVbbiUqqJw3zspKi0oIyVacZs'),
    (UUID(), 'Elva', 'Bozzo', 'elva.bozzo@ruta9.ar', 'SALES', /* pwd:elva */ '$argon2id$v=19$m=65536,t=4,p=1$N3ZNd3ZobU80UGpsWUVhYQ$8cQvZwMIZxOMONl68wQF/5kkyUoiEdKuAICuFrFmezw')
;

-- Vehicles (id VARCHAR(36),brand VARCHAR(255),model VARCHAR(255),year INT, price DECIMAL, stock INT);
-- VehicleImages (id VARCHAR(36),vehicle_id VARCHAR(36),url VARCHAR(512));

-- INSERT INTO vehicles (brand, model, year, price)
-- SELECT * FROM (
--     SELECT 'Audi', 'A4', '2021', 40000.00 UNION ALL,
--     SELECT 'BMW', '3 Series', '2021', 41000.00 UNION ALL,
--     SELECT 'Chevrolet', 'Silverado', '2022', 35000.00 UNION ALL,
--     SELECT 'Ford', 'Escape', '2020', 26000.00 UNION ALL,
--     SELECT 'Ford', 'Explorer', '2021', 33000.00 UNION ALL,
--     SELECT 'Ford', 'F-Series', '2022', 35000.00 UNION ALL,
--     SELECT 'Ford', 'Focus', '2019', 20000.00 UNION ALL,
--     SELECT 'Honda', 'Accord', '2020', 25000.00 UNION ALL,
--     SELECT 'Honda', 'CR-V', '2022', 28000.00 UNION ALL,
--     SELECT 'Honda', 'Civic', '2021', 22000.00 UNION ALL,
--     SELECT 'Honda', 'HR-V', '2021', 22000.00 UNION ALL,
--     SELECT 'Hyundai', 'Elantra', '2021', 20000.00 UNION ALL,
--     SELECT 'Hyundai', 'Santa Fe', '2021', 28000.00 UNION ALL,
--     SELECT 'Hyundai', 'Tucson', '2022', 26000.00 UNION ALL,
--     SELECT 'Kia', 'Rio', '2020', 16000.00 UNION ALL,
--     SELECT 'Kia', 'Sorento', '2021', 26000.00 UNION ALL,
--     SELECT 'Kia', 'Sportage', '2022', 25000.00 UNION ALL,
--     SELECT 'Mercedes-Benz', 'C-Class', '2021', 42000.00 UNION ALL,
--     SELECT 'Nissan', 'Qashqai', '2021', 24000.00 UNION ALL,
--     SELECT 'Nissan', 'Rogue', '2021', 26000.00 UNION ALL,
--     SELECT 'Nissan', 'Sentra', '2021', 20000.00 UNION ALL,
--     SELECT 'Subaru', 'Forester', '2021', 25000.00 UNION ALL
--     SELECT 'Tesla', 'Model 3', '2022', 47000.00 UNION ALL,
--     SELECT 'Tesla', 'Model Y', '2023', 50000.00 UNION ALL,
--     SELECT 'Toyota', 'Camry', '2021', 26000.00 UNION ALL,
--     SELECT 'Toyota', 'Corolla', '2020', 22000.00 UNION ALL,
--     SELECT 'Toyota', 'Highlander', '2021', 36000.00 UNION ALL,
--     SELECT 'Toyota', 'Hilux', '2022', 30000.00 UNION ALL,
--     SELECT 'Toyota', 'RAV4', '2022', 30000.00 UNION ALL,
--     SELECT 'Toyota', 'Tacoma', '2021', 27000.00 UNION ALL,
--     SELECT 'Toyota', 'Yaris', '2020', 17000.00 UNION ALL,
--     SELECT 'Volkswagen', 'Golf', '2020', 24000.00 UNION ALL,
--     SELECT 'Volkswagen', 'Passat', '2020', 25000.00 UNION ALL,
--     SELECT 'Volkswagen', 'Polo', '2020', 18000.00 UNION ALL,
--     SELECT 'Volkswagen', 'Tiguan', '2022', 27000.00 UNION ALL,
-- ) AS seed
-- WHERE NOT EXISTS (SELECT 1 FROM vehicles);
