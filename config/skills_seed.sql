-- ============================================================
-- MTRTS — Skills & Auto-Assignment migration
-- Safe to run against an existing database that already has
-- users / asset_categories / locations.
-- Idempotent: re-running will not duplicate rows.
-- ============================================================

USE mtrts_sql;

CREATE TABLE IF NOT EXISTS skills (
  skill_id    INT          PRIMARY KEY AUTO_INCREMENT,
  skill_code  VARCHAR(64)  NOT NULL UNIQUE,
  skill_name  VARCHAR(120) NOT NULL,
  description TEXT         NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS technician_skills (
  user_id      INT      NOT NULL,
  skill_id     INT      NOT NULL,
  proficiency  TINYINT  NOT NULL DEFAULT 1,
  certified_at DATE     NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id, skill_id),
  FOREIGN KEY (user_id)  REFERENCES users(user_id)   ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS category_skills (
  category_id INT        NOT NULL,
  skill_id    INT        NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (category_id, skill_id),
  FOREIGN KEY (category_id) REFERENCES asset_categories(category_id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id)    REFERENCES skills(skill_id)              ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS location_assignments (
  assignment_id INT          PRIMARY KEY AUTO_INCREMENT,
  user_id       INT          NOT NULL,
  location_id   INT          NULL,
  building      VARCHAR(100) NULL,
  is_primary    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uniq_user_loc_bld (user_id, location_id, building),
  FOREIGN KEY (user_id)     REFERENCES users(user_id)         ON DELETE CASCADE,
  FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE SET NULL
);

-- Seed skills (skill_code is UNIQUE, so re-running is a no-op)
INSERT IGNORE INTO skills (skill_code, skill_name, description) VALUES
  ('projector_repair', 'Projector Repair',     'Lamp, lens, filter, focus, and input troubleshooting for projectors.'),
  ('audio_systems',    'Audio Systems',        'Mixers, amplifiers, speakers, microphones, and DSP configuration.'),
  ('av_switching',     'AV Switching/Routing', 'HDMI matrix, scalers, signal extenders, video walls.'),
  ('display_repair',   'Display Repair',       'LCD/LED/OLED panels, smart boards, interactive flat panels.'),
  ('camera_systems',   'Camera Systems',       'PTZ, conferencing, recording, and streaming cameras.'),
  ('network',          'Networking',           'Switches, AP placement, VLAN, AV-over-IP.'),
  ('rack_wiring',      'AV Rack & Wiring',     'Rack assembly, cable management, power distribution.'),
  ('general',          'General Maintenance',  'Routine cleaning, replacement, and inspections.');

-- Seed category→skill links (PK prevents duplicates)
INSERT IGNORE INTO category_skills (category_id, skill_id, is_required)
SELECT c.category_id, s.skill_id, 1
FROM asset_categories c
JOIN skills s ON 1=1
WHERE
     (c.category_name = 'Projector'    AND s.skill_code IN ('projector_repair','av_switching'))
  OR (c.category_name = 'Sound System' AND s.skill_code = 'audio_systems')
  OR (c.category_name = 'AV Switcher'  AND s.skill_code = 'av_switching')
  OR (c.category_name = 'Display'      AND s.skill_code IN ('display_repair','av_switching'))
  OR (c.category_name = 'Microphone'   AND s.skill_code = 'audio_systems')
  OR (c.category_name = 'AV Rack'      AND s.skill_code IN ('rack_wiring','av_switching'))
  OR (c.category_name = 'Camera'       AND s.skill_code = 'camera_systems')
  OR (c.category_name = 'Amplifier'    AND s.skill_code = 'audio_systems');

-- ============================================================
-- SEED DATA: USERS (Admin, Manager, Technicians, Requester)
-- ============================================================
INSERT IGNORE INTO users (user_id, email, full_name, role_id, department_id, is_active) VALUES
  (101, 'admin@example.com', 'Admin User', 1, 1, 1),
  (102, 'manager@example.com', 'IT Manager', 2, 1, 1),
  (103, 'tech1@example.com', 'John Technician', 4, 1, 1),
  (104, 'tech2@example.com', 'Jane Technician', 4, 1, 1),
  (105, 'faculty1@example.com', 'Dr. Smith', 5, 2, 1);

-- ============================================================
-- SEED DATA: TECHNICIAN SKILLS
-- ============================================================
INSERT IGNORE INTO technician_skills (user_id, skill_id, proficiency)
SELECT 103, skill_id, 5 FROM skills WHERE skill_code IN ('projector_repair', 'audio_systems');

INSERT IGNORE INTO technician_skills (user_id, skill_id, proficiency)
SELECT 104, skill_id, 5 FROM skills WHERE skill_code IN ('av_switching', 'display_repair', 'camera_systems');

-- ============================================================
-- SEED DATA: ASSETS
-- ============================================================
INSERT IGNORE INTO assets (asset_id, asset_tag, manufacturer, model, category_id, location_id, install_date, status) VALUES
  (101, 'PRJ-101', 'Epson', 'PowerLite', 1, 1, '2023-01-01', 'active'),
  (102, 'SND-102', 'Yamaha', 'StagePas', 2, 2, '2023-02-01', 'active'),
  (103, 'DIS-103', 'Samsung', 'SmartBoard', 4, 3, '2023-03-01', 'active');

-- ============================================================
-- SEED DATA: TICKETS
-- ============================================================
INSERT IGNORE INTO tickets (ticket_id, ticket_number, requester_id, asset_id, category_id, location_id, title, description, status, priority) VALUES
  (101, 'TKT-1001', 105, 101, 1, 1, 'Projector not turning on', 'The projector in Room 101 is completely unresponsive.', 'assigned', 'high'),
  (102, 'TKT-1002', 105, 102, 2, 2, 'No sound from speakers', 'The sound system is producing static noise only.', 'assigned', 'medium'),
  (103, 'TKT-1003', 105, 103, 4, 3, 'Display flickering', 'The main display is flickering constantly.', 'new', 'low');

-- ============================================================
-- SEED DATA: WORK ORDERS
-- ============================================================
INSERT IGNORE INTO work_orders (wo_id, wo_number, ticket_id, wo_type, assigned_to, assigned_by, status) VALUES
  (101, 'WO-1001', 101, 'repair', 103, 102, 'assigned'),
  (102, 'WO-1002', 102, 'repair', 104, 102, 'assigned');
