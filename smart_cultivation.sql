CREATE DATABASE IF NOT EXISTS smart_cultivation 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE smart_cultivation;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    otp VARCHAR(10),
    otp_expiry DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(200) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    district VARCHAR(150),
    state VARCHAR(150),
    otp VARCHAR(10),
    otp_expiry DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS farmer_crops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    crop_name VARCHAR(100) NOT NULL,
    growth_stage VARCHAR(100) NOT NULL,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS crop_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    crop_name VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    notify_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE knowledge_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    crop_name VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE farmer_crops 
ADD variety VARCHAR(100) AFTER crop_name,
ADD field VARCHAR(200) AFTER variety,
ADD planting_date DATE AFTER field,
ADD yield VARCHAR(100) AFTER growth_stage,
ADD notes TEXT AFTER yield;
ALTER TABLE crop_notifications
ADD COLUMN status ENUM('unread','read') DEFAULT 'unread';
ALTER TABLE knowledge_base
ADD COLUMN section VARCHAR(50) NOT NULL AFTER crop_name;
-- TOMATO
INSERT INTO knowledge_base (crop_name, section, title, description) VALUES
('tomato', 'Growth Stages', 'Stage 1 — Land Preparation', 'Deep ploughing 20–25 cm for soil aeration. Apply FYM 10–15 tons/acre. Soil solarization for 7 days. Form raised beds 1 meter width. Apply Trichoderma 2 kg/acre. Ensure proper drainage.'),
('tomato', 'Growth Stages', 'Stage 2 — Sowing / Transplanting', 'Seed rate: 100–150 g/acre. Treat seeds with Bavistin 2g/kg. Spacing: 45 × 60 cm. Transplant during evening. Light irrigation after transplant.'),
('tomato', 'Growth Stages', 'Stage 3 — Vegetative Stage', 'Apply 25–30 kg Nitrogen/acre. Irrigate every 4–5 days. Start staking for support. Watch for Leaf Miner, Whiteflies. Spray neem oil 3% weekly.'),
('tomato', 'Growth Stages', 'Stage 4 — Flowering Stage', 'Apply 20:20:20 @1% spray. Use micronutrient mixture (Fe, Zn, Mn). Watch for Fruit Borer. Irrigation must be uniform.'),
('tomato', 'Growth Stages', 'Stage 5 — Fruit Development', 'Foliar spray Calcium Nitrate 1%. Drip irrigation 2–3 hrs/day. Remove lower old leaves. Boost with Magnesium Sulphate @0.5%.'),
('tomato', 'Growth Stages', 'Stage 6 — Harvest', 'Harvest at breaker stage. Harvest window: 60–90 days. Expected Yield: 100–150 quintal/acre. Store at cool, dry, ventilated space.'),
('tomato', 'Problems & Solutions', 'Fruit Borer', 'Use pheromone traps 10/acre. Spray Emamectin Benzoate 5% SG @ 4g/10L.'),
('tomato', 'Problems & Solutions', 'Early Blight', 'Spray Mancozeb 2g/L or Copper Oxychloride.'),
('tomato', 'Problems & Solutions', 'Leaf Curl Virus', 'Control whiteflies, Yellow Sticky Traps. Spray Imidacloprid 0.3ml/L.'),
('tomato', 'Fertilizer Schedule', 'Basal & Top Dressing', 'Basal Dose: FYM 10 tons, SSP 100 kg, MOP 40 kg. Top Dressing: 30 DAS → Urea 25 kg, 45 DAS → NPK 19:19:19 @ 1%.'),
('tomato', 'Watering Schedule', 'Irrigation Plan', '0–15 days: Light irrigation daily. 15–40 days: Every 4–5 days. Flowering: Heavier irrigation. Pre-harvest: Reduce water.');
-- GROUNDNUT
INSERT INTO knowledge_base (crop_name, section, title, description) VALUES
('groundnut', 'Growth Stages', 'Stage 1 — Land Preparation', '2 deep ploughings + cross harrowing. 5–7 tons FYM/acre. Loose soil for pegging. Apply gypsum.'),
('groundnut', 'Growth Stages', 'Stage 2 — Sowing', 'Seed rate 20–25 kg/acre. Spacing 30 × 10 cm. Seed treatment Thiram 3g/kg. Sow at 5–6 cm depth.'),
('groundnut', 'Growth Stages', 'Stage 3 — Vegetative', 'Light irrigation once every 7 days. Weed control at 20–25 DAS. Apply 20 kg Nitrogen.'),
('groundnut', 'Growth Stages', 'Stage 4 — Pegging & Flowering', 'Maintain loose soil. Irrigate every 6–7 days. Spray micronutrients at early flowering.'),
('groundnut', 'Growth Stages', 'Stage 5 — Pod Development', 'Gypsum 200 kg/acre @ 30 DAS. Maintain soil moisture. Apply Potash.'),
('groundnut', 'Growth Stages', 'Stage 6 — Harvest', 'Harvest when leaves turn yellow. Pods with brown veins. Dry pods under shade. Expected yield 5–8 quintals/acre.'),
('groundnut', 'Problems & Solutions', 'Tikka Leaf Spot', 'Spray Mancozeb 2g/L.'),
('groundnut', 'Problems & Solutions', 'Stem & Root Rot', 'Improve drainage. Spray Carbendazim 1g/L.'),
('groundnut', 'Problems & Solutions', 'White Grub', 'Apply soil insecticide at sowing.'),
('groundnut', 'Fertilizer Schedule', 'Basal Dose', 'SSP 150 kg/acre. 30 DAS: Gypsum 200 kg/acre. Flowering Spray: Micronutrients + Potash.'),
('groundnut', 'Watering Schedule', 'Irrigation Plan', 'Early stage: every 7–10 days. Pegging: Regular irrigation. Pre-harvest: Stop all irrigation.');
ALTER TABLE users ADD COLUMN status ENUM('active','inactive') DEFAULT 'active';
ALTER TABLE users
ADD age INT,
ADD gender VARCHAR(10),
ADD aadhar VARCHAR(12),
ADD caste VARCHAR(50),
ADD total_land DECIMAL(5,2);
ALTER TABLE users
ADD latitude VARCHAR(50),
ADD longitude VARCHAR(50),
ADD location_address TEXT;








