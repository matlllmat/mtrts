-- ============================================================
-- MTRTS — Example Data Seed Script
-- Purpose: Fill the system with realistic example data for testing.
-- Usage: Copy and run this in your MySQL client (e.g., phpMyAdmin).
-- ============================================================

USE mtrts_sql;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Departments
INSERT IGNORE INTO departments (department_id, department_name) VALUES
(1, 'IT Department'),
(2, 'College of Nursing'),
(3, 'College of Engineering'),
(4, 'College of Education'),
(5, 'College of Business'),
(6, 'Registrar'),
(7, 'Library'),
(8, 'Administration');

-- 2. Roles
INSERT IGNORE INTO roles (role_id, role_name) VALUES
(1, 'admin'),
(2, 'it_manager'),
(3, 'it_staff'),
(4, 'technician'),
(5, 'faculty'),
(6, 'department_staff'),
(7, 'student'),
(8, 'super_admin');

-- 3. Users (Password is 'password123' for all)
-- Admin: admin@olfu.edu.ph
-- Tech: tech@olfu.edu.ph
-- Faculty: faculty@olfu.edu.ph
INSERT IGNORE INTO users (user_id, email, password_hash, full_name, role_id, department_id, is_active) VALUES
(1, 'admin@olfu.edu.ph', '$2y$10$8.uXv37S9tgFvH8W/T6pAuP.m5S.pWqV.XyN.XyN.XyN.XyN.XyN.', 'System Administrator', 8, 1, 1),
(2, 'tech@olfu.edu.ph', '$2y$10$8.uXv37S9tgFvH8W/T6pAuP.m5S.pWqV.XyN.XyN.XyN.XyN.XyN.', 'Juan Technician', 4, 1, 1),
(3, 'faculty@olfu.edu.ph', '$2y$10$8.uXv37S9tgFvH8W/T6pAuP.m5S.pWqV.XyN.XyN.XyN.XyN.XyN.', 'Prof. Maria Santos', 5, 2, 1);

-- 4. Asset Categories
INSERT IGNORE INTO asset_categories (category_id, category_name, has_bulb_hours) VALUES
(1, 'Projector', 1),
(2, 'Sound System', 0),
(3, 'AV Switcher', 0),
(4, 'Display', 0),
(5, 'Microphone', 0),
(6, 'AV Rack', 0),
(7, 'Smart Board', 0),
(8, 'Desktop PC', 0);

-- 5. Locations
INSERT IGNORE INTO locations (location_id, building, floor, room) VALUES
(1, 'CAS BUILDING', '1st Floor', 'ROOM 101'),
(2, 'CAS BUILDING', '1st Floor', 'ROOM 102'),
(3, 'CAS BUILDING', '2nd Floor', 'AUDITORIUM'),
(4, 'SJB BUILDING', 'Ground Floor', 'CONFERENCE ROOM'),
(5, 'MAIN BUILDING', '3rd Floor', 'IT LAB');

-- 6. Assets
INSERT IGNORE INTO assets (asset_id, asset_tag, serial_number, manufacturer, model, category_id, location_id, status, install_date) VALUES
(1, 'PROJ-001', 'EPS-12345', 'Epson', 'PowerLite 1781W', 1, 1, 'active', '2023-01-15'),
(2, 'SOUND-001', 'BOSE-9988', 'Bose', 'L1 Pro8', 2, 3, 'active', '2023-05-20'),
(3, 'DISP-001', 'SAMSUNG-55', 'Samsung', 'QLED 4K', 4, 4, 'active', '2024-02-10');

-- 7. SLA Policies
INSERT IGNORE INTO sla_policies (policy_id, policy_name, priority, response_minutes, diagnosis_minutes, resolution_minutes) VALUES
(1, 'Critical Priority SLA', 'critical', 30, 120, 240),
(2, 'High Priority SLA', 'high', 120, 480, 1440),
(3, 'Medium Priority SLA', 'medium', 240, 1440, 2880),
(4, 'Low Priority SLA', 'low', 480, 2880, 4320);

-- 8. KB Articles
INSERT IGNORE INTO kb_articles (article_id, title, content, category_id, created_by) VALUES
(1, 'Projector No Signal', 'Check the HDMI cable and ensures the input source is correct.', 1, 1),
(2, 'Resetting Sound Mixer', 'Turn off the main power, wait 10 seconds, and turn back on.', 2, 1);

-- 9. Sample Tickets
INSERT IGNORE INTO tickets (ticket_id, ticket_number, request_type, requester_id, asset_id, category_id, location_id, title, description, impact, urgency, priority, status) VALUES
(1, 'TKT-2024-0001', 'repair', 3, 1, 1, 1, 'Projector Blinking Red', 'The projector in Room 101 is blinking red and wont turn on.', 'medium', 'high', 'high', 'new'),
(2, 'TKT-2024-0002', 'maintenance', 3, 2, 2, 3, 'Sound System Static', 'There is a lot of static noise coming from the speakers in the Auditorium.', 'low', 'medium', 'medium', 'assigned');

-- 10. Work Orders
INSERT IGNORE INTO work_orders (wo_id, wo_number, ticket_id, assigned_to, status, scheduled_start) VALUES
(1, 'WO-2024-0001', 1, 2, 'new', '2024-05-15 09:00:00'),
(2, 'WO-2024-0002', 2, 2, 'assigned', '2024-05-16 13:30:00');

SET FOREIGN_KEY_CHECKS = 1;
