-- ============================================================
-- MTRTS — Demo Seed Data
-- Creates: users (technicians + requesters), assets,
--          tickets, work orders, and assignments.
--
-- Safe to run on a fresh database (after database.sql).
-- Uses INSERT IGNORE / ON DUPLICATE KEY to be idempotent.
-- ============================================================

USE mtrts_sql;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. USERS
-- Roles: 1=admin, 2=it_manager, 3=it_staff, 4=technician,
--        5=faculty, 6=department_staff, 7=student, 8=super_admin
-- Password hash is a placeholder — use set_admin_password.php
-- to set real passwords after import.
-- ============================================================

-- IT Manager
INSERT IGNORE INTO users
  (user_id, email, password_hash, full_name, id_number, contact_number, position, department_id, role_id, is_active)
VALUES
  (10, 'it.manager@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Maria Santos', 'EMP-2021-001', '09171234001', 'IT Manager', 1, 2, 1);

-- Technicians (role_id = 4)
INSERT IGNORE INTO users
  (user_id, email, password_hash, full_name, id_number, contact_number, position, department_id, role_id, is_active)
VALUES
  (11, 'tech.reyes@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Juan Reyes', 'EMP-2022-011', '09171234011', 'AV Technician', 1, 4, 1),

  (12, 'tech.dela.cruz@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Ana Dela Cruz', 'EMP-2022-012', '09171234012', 'AV Technician', 1, 4, 1),

  (13, 'tech.garcia@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Carlos Garcia', 'EMP-2023-013', '09171234013', 'Junior Technician', 1, 4, 1);

-- IT Staff (role_id = 3)
INSERT IGNORE INTO users
  (user_id, email, password_hash, full_name, id_number, contact_number, position, department_id, role_id, is_active)
VALUES
  (14, 'it.staff.lim@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Kevin Lim', 'EMP-2023-014', '09171234014', 'IT Staff', 1, 3, 1);

-- Faculty / Requesters (role_id = 5)
INSERT IGNORE INTO users
  (user_id, email, password_hash, full_name, id_number, contact_number, position, department_id, role_id, is_active)
VALUES
  (20, 'prof.bautista@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Prof. Elena Bautista', 'FAC-2019-020', '09181234020', 'Professor', 4, 5, 1),

  (21, 'prof.mendoza@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Prof. Roberto Mendoza', 'FAC-2020-021', '09181234021', 'Associate Professor', 3, 5, 1),

  (22, 'prof.torres@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Prof. Liza Torres', 'FAC-2021-022', '09181234022', 'Instructor', 2, 5, 1);

-- Department Staff / Requesters (role_id = 6)
INSERT IGNORE INTO users
  (user_id, email, password_hash, full_name, id_number, contact_number, position, department_id, role_id, is_active)
VALUES
  (23, 'staff.events@olfu.edu.ph',
   '$2y$10$demoHashPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
   'Carla Navarro', 'STF-2022-023', '09191234023', 'Events Coordinator', 8, 6, 1);


-- ============================================================
-- 2. TECHNICIAN SKILLS & LOCATION ASSIGNMENTS
-- skill_id: 1=projector_repair, 2=audio_systems, 3=av_switching,
--           4=display_repair,   5=camera_systems, 6=network,
--           7=rack_wiring,      8=general
-- ============================================================

-- Juan Reyes — projector + AV switching expert
INSERT IGNORE INTO technician_skills (user_id, skill_id, proficiency, certified_at) VALUES
  (11, 1, 3, '2022-03-15'),  -- projector_repair  (expert)
  (11, 3, 3, '2022-03-15'),  -- av_switching      (expert)
  (11, 4, 2, '2023-01-10'),  -- display_repair    (intermediate)
  (11, 8, 2, '2022-03-15');  -- general           (intermediate)

-- Ana Dela Cruz — audio & camera specialist
INSERT IGNORE INTO technician_skills (user_id, skill_id, proficiency, certified_at) VALUES
  (12, 2, 3, '2022-06-01'),  -- audio_systems     (expert)
  (12, 5, 3, '2022-06-01'),  -- camera_systems    (expert)
  (12, 7, 2, '2023-02-20'),  -- rack_wiring       (intermediate)
  (12, 8, 2, '2022-06-01');  -- general           (intermediate)

-- Carlos Garcia — junior, general + basic projector
INSERT IGNORE INTO technician_skills (user_id, skill_id, proficiency, certified_at) VALUES
  (13, 8, 2, '2023-07-01'),  -- general           (intermediate)
  (13, 1, 1, '2023-07-01'),  -- projector_repair  (basic)
  (13, 3, 1, '2023-07-01');  -- av_switching      (basic)

-- Location assignments
INSERT IGNORE INTO location_assignments (user_id, location_id, building, is_primary) VALUES
  (11, 1,  'CAS BUILDING', 1),
  (11, 2,  'CAS BUILDING', 0),
  (11, 3,  'CAS BUILDING', 0),
  (12, 11, 'SJB BUILDING', 1),
  (12, 4,  'CAS BUILDING', 0),
  (13, 1,  'CAS BUILDING', 1),
  (13, 11, 'SJB BUILDING', 0);


-- ============================================================
-- 3. ASSETS
-- category_id: 1=Projector, 2=Sound System, 3=AV Switcher,
--              4=Display,   5=Microphone,   6=AV Rack,
--              7=Camera,    8=Amplifier
-- location_id: 1=CAS/101, 2=CAS/102, 3=CAS/201, 4=Media Lab A,
--              5=Auditorium, 11=SJB/Auditorium
-- ============================================================

INSERT IGNORE INTO assets
  (asset_tag, serial_number, manufacturer, model,
   category_id, status, location_id,
   install_date, firmware_version, network_info, bulb_hours,
   department_id, owner_id, created_by)
VALUES
  -- Projectors
  ('PRJ-DEMO-001', 'DEMO-EPS-0001', 'Epson',    'EB-2250U',
    1, 'active', 1, '2023-01-10', '1.04.00', '192.168.10.11', 1200, 1, 1, 1),

  ('PRJ-DEMO-002', 'DEMO-BNQ-0002', 'BenQ',     'MH760',
    1, 'active', 2, '2022-08-15', '2.00.01', '192.168.10.12', 2800, 4, 1, 1),

  ('PRJ-DEMO-003', 'DEMO-PAN-0003', 'Panasonic','PT-VMZ60',
    1, 'active', 3, '2023-06-01', '3.10.00', '192.168.10.13',  450, 3, 1, 1),

  -- Sound Systems
  ('SND-DEMO-001', 'DEMO-YMH-0004', 'Yamaha',   'DXL1K',
    2, 'active', 11, '2021-09-20', NULL, NULL, NULL, 8, 1, 1),

  ('SND-DEMO-002', 'DEMO-BSE-0005', 'Bose',     'L1 Pro8',
    2, 'active',  4, '2022-03-05', NULL, NULL, NULL, 1, 1, 1),

  -- AV Switcher
  ('AVS-DEMO-001', 'DEMO-EXT-0006', 'Extron',   'SW4 HD 4K',
    3, 'active',  4, '2022-07-12', 'v60.14.01', '192.168.10.21', NULL, 1, 1, 1),

  -- Displays
  ('DSP-DEMO-001', 'DEMO-SAM-0007', 'Samsung',  'QM75B',
    4, 'active',  1, '2023-02-28', '1220.2', '192.168.10.31', NULL, 4, 1, 1),

  ('DSP-DEMO-002', 'DEMO-LGE-0008', 'LG',       'OLED65C3',
    4, 'active',  2, '2022-11-10', 'v03.34.40', '192.168.10.32', NULL, 3, 1, 1),

  -- Microphones
  ('MIC-DEMO-001', 'DEMO-SHR-0009', 'Shure',    'SM58',
    5, 'active', 11, '2021-05-01', NULL, NULL, NULL, 8, 1, 1),

  ('MIC-DEMO-002', 'DEMO-SNH-0010', 'Sennheiser','EW 135P G4',
    5, 'active',  4, '2022-10-15', NULL, NULL, NULL, 5, 1, 1),

  -- AV Rack
  ('RCK-DEMO-001', 'DEMO-MWK-0011', 'Middle Atlantic','WRK-4427',
    6, 'active',  4, '2021-03-10', NULL, NULL, NULL, 1, 1, 1),

  -- Camera
  ('CAM-DEMO-001', 'DEMO-SON-0012', 'Sony',     'SRG-X400',
    7, 'active', 11, '2022-12-20', 'v1.10', '192.168.10.41', NULL, 1, 1, 1),

  -- Amplifier
  ('AMP-DEMO-001', 'DEMO-CRN-0013', 'Crown',    'XLi1500',
    8, 'active', 11, '2020-08-01', NULL, NULL, NULL, 1, 1, 1);

-- Warranties for demo assets
INSERT IGNORE INTO asset_warranty
  (asset_id, warranty_start, warranty_end, coverage_type, vendor_name, contract_reference)
SELECT asset_id, '2023-01-10', '2026-01-09', 'parts_and_labor', 'Epson Philippines',  'DEMO-EP-001'
  FROM assets WHERE asset_tag = 'PRJ-DEMO-001'
UNION ALL
SELECT asset_id, '2022-08-15', '2025-08-14', 'parts',           'BenQ Philippines',   'DEMO-BQ-002'
  FROM assets WHERE asset_tag = 'PRJ-DEMO-002'
UNION ALL
SELECT asset_id, '2023-06-01', '2026-05-31', 'parts_and_labor', 'Panasonic PH',       'DEMO-PAN-003'
  FROM assets WHERE asset_tag = 'PRJ-DEMO-003'
UNION ALL
SELECT asset_id, '2021-09-20', '2024-09-19', 'labor',           'Yamaha Music PH',    'DEMO-YMH-004'
  FROM assets WHERE asset_tag = 'SND-DEMO-001'
UNION ALL
SELECT asset_id, '2022-07-12', '2025-07-11', 'parts_and_labor', 'Extron Electronics', 'DEMO-EXT-006'
  FROM assets WHERE asset_tag = 'AVS-DEMO-001'
UNION ALL
SELECT asset_id, '2023-02-28', '2026-02-27', 'parts_and_labor', 'Samsung Philippines','DEMO-SAM-007'
  FROM assets WHERE asset_tag = 'DSP-DEMO-001'
UNION ALL
SELECT asset_id, '2022-12-20', '2025-12-19', 'parts_and_labor', 'Sony Philippines',   'DEMO-SON-012'
  FROM assets WHERE asset_tag = 'CAM-DEMO-001';


-- ============================================================
-- 4. TICKETS
-- status: new | assigned | scheduled | in_progress |
--         on_hold | resolved | closed | cancelled
-- priority / impact / urgency: low | medium | high | critical
-- ============================================================

-- TKT-DEMO-001  Projector flickering — CLOSED (completed scenario)
INSERT IGNORE INTO tickets
  (ticket_number, requester_id, asset_id, category_id, location_id,
   title, description, impact, urgency, priority, channel, status,
   assigned_to, resolved_at, closed_at, created_at)
SELECT
  'TKT-DEMO-001', 20,
  (SELECT asset_id FROM assets WHERE asset_tag = 'PRJ-DEMO-001'),
  1, 1,
  'Projector flickering in CAS Room 101',
  'The projector screen flickers every few minutes. Very distracting during lectures.',
  'high', 'high', 'high', 'web', 'closed',
  11,
  '2026-05-02 10:45:00', '2026-05-02 11:00:00',
  '2026-05-01 08:30:00';

-- TKT-DEMO-002  Sound system buzzing — IN PROGRESS
INSERT IGNORE INTO tickets
  (ticket_number, requester_id, asset_id, category_id, location_id,
   title, description, impact, urgency, priority, channel, status,
   assigned_to, created_at)
SELECT
  'TKT-DEMO-002', 23,
  (SELECT asset_id FROM assets WHERE asset_tag = 'SND-DEMO-001'),
  2, 11,
  'Auditorium speakers buzzing during events',
  'Constant low-frequency hum from the main speakers. Happens even when no source is connected.',
  'high', 'medium', 'high', 'web', 'in_progress',
  12,
  '2026-05-07 14:00:00';

-- TKT-DEMO-003  Display cracked — ON HOLD (waiting parts)
INSERT IGNORE INTO tickets
  (ticket_number, requester_id, asset_id, category_id, location_id,
   title, description, impact, urgency, priority, channel, status,
   assigned_to, created_at)
SELECT
  'TKT-DEMO-003', 21,
  (SELECT asset_id FROM assets WHERE asset_tag = 'DSP-DEMO-002'),
  4, 2,
  'Display panel cracked in CAS Room 102',
  'Physical crack on the lower-right corner of the LG display. Image is distorted in that area.',
  'medium', 'low', 'medium', 'walk_in', 'on_hold',
  11,
  '2026-05-05 11:00:00';

-- TKT-DEMO-004  Projector no signal — ASSIGNED (scheduled)
INSERT IGNORE INTO tickets
  (ticket_number, requester_id, asset_id, category_id, location_id,
   title, description, impact, urgency, priority, channel, status,
   assigned_to, created_at)
SELECT
  'TKT-DEMO-004', 20,
  (SELECT asset_id FROM assets WHERE asset_tag = 'PRJ-DEMO-002'),
  1, 2,
  'Projector shows "No Signal" in Room 102',
  'Projector powers on but shows no signal from the laptop. Tried two different laptops.',
  'high', 'high', 'high', 'qr_scan', 'assigned',
  13,
  '2026-05-09 07:45:00';

-- TKT-DEMO-005  Microphone no audio — NEW (unassigned)
INSERT IGNORE INTO tickets
  (ticket_number, requester_id, asset_id, category_id, location_id,
   title, description, impact, urgency, priority, channel, status,
   created_at)
SELECT
  'TKT-DEMO-005', 22,
  (SELECT asset_id FROM assets WHERE asset_tag = 'MIC-DEMO-001'),
  5, 11,
  'Wireless microphone not working in Auditorium',
  'The Sennheiser wireless mic has no audio output. Battery is fully charged.',
  'medium', 'medium', 'medium', 'web', 'new',
  '2026-05-10 09:00:00';

-- TKT-DEMO-006  Camera offline — RESOLVED
INSERT IGNORE INTO tickets
  (ticket_number, requester_id, asset_id, category_id, location_id,
   title, description, impact, urgency, priority, channel, status,
   assigned_to, resolved_at, created_at)
SELECT
  'TKT-DEMO-006', 23,
  (SELECT asset_id FROM assets WHERE asset_tag = 'CAM-DEMO-001'),
  7, 11,
  'PTZ camera offline in Auditorium',
  'The Sony PTZ camera is not reachable on the network. Power light is on.',
  'high', 'high', 'high', 'web', 'resolved',
  12,
  '2026-05-08 16:30:00',
  '2026-05-08 08:00:00';


-- ============================================================
-- 5. WORK ORDERS
-- wo_type: diagnosis | repair | maintenance | follow_up
-- status:  new | assigned | scheduled | in_progress |
--          on_hold | resolved | closed
-- ============================================================

-- WO-DEMO-001  Projector repair — CLOSED (linked to TKT-DEMO-001)
INSERT IGNORE INTO work_orders
  (wo_number, ticket_id, wo_type,
   assigned_to, assigned_by,
   status,
   scheduled_start, scheduled_end,
   actual_start, actual_end,
   findings, actions_taken, resolution_notes,
   created_by, created_at)
SELECT
  'WO-DEMO-001',
  (SELECT ticket_id FROM tickets WHERE ticket_number = 'TKT-DEMO-001'),
  'repair',
  11, 10,
  'closed',
  '2026-05-02 09:00:00', '2026-05-02 11:00:00',
  '2026-05-02 09:05:00', '2026-05-02 10:45:00',
  'Loose HDMI internal ribbon cable and clogged air filter causing thermal throttle.',
  'Reseated internal ribbon cable. Cleaned and replaced air filter. Ran 30-min burn-in test.',
  'Projector operating normally. No flicker observed after repair.',
  10, '2026-05-01 10:00:00';

-- WO-DEMO-002  Sound system diagnosis — IN PROGRESS (linked to TKT-DEMO-002)
INSERT IGNORE INTO work_orders
  (wo_number, ticket_id, wo_type,
   assigned_to, assigned_by,
   status,
   scheduled_start,
   notes,
   created_by, created_at)
SELECT
  'WO-DEMO-002',
  (SELECT ticket_id FROM tickets WHERE ticket_number = 'TKT-DEMO-002'),
  'diagnosis',
  12, 10,
  'in_progress',
  '2026-05-08 09:00:00',
  'Check XLR cables, mixer ground, and amplifier input gain.',
  10, '2026-05-07 15:00:00';

-- WO-DEMO-003  Display repair — ON HOLD (linked to TKT-DEMO-003)
INSERT IGNORE INTO work_orders
  (wo_number, ticket_id, wo_type,
   assigned_to, assigned_by,
   status, on_hold_reason,
   scheduled_start,
   notes,
   created_by, created_at)
SELECT
  'WO-DEMO-003',
  (SELECT ticket_id FROM tickets WHERE ticket_number = 'TKT-DEMO-003'),
  'repair',
  11, 10,
  'on_hold', 'waiting_parts',
  '2026-05-06 13:00:00',
  'Replacement LG OLED panel ordered. ETA 7–10 business days.',
  10, '2026-05-05 12:00:00';

-- WO-DEMO-004  Projector no-signal — ASSIGNED (linked to TKT-DEMO-004)
INSERT IGNORE INTO work_orders
  (wo_number, ticket_id, wo_type,
   assigned_to, assigned_by,
   status,
   scheduled_start, scheduled_end,
   notes,
   created_by, created_at)
SELECT
  'WO-DEMO-004',
  (SELECT ticket_id FROM tickets WHERE ticket_number = 'TKT-DEMO-004'),
  'diagnosis',
  13, 10,
  'assigned',
  '2026-05-09 10:00:00', '2026-05-09 12:00:00',
  'Check HDMI cable, switcher input, and projector input settings.',
  10, '2026-05-09 08:00:00';

-- WO-DEMO-005  Camera repair — RESOLVED (linked to TKT-DEMO-006)
INSERT IGNORE INTO work_orders
  (wo_number, ticket_id, wo_type,
   assigned_to, assigned_by,
   status,
   scheduled_start, scheduled_end,
   actual_start, actual_end,
   findings, actions_taken, resolution_notes,
   created_by, created_at)
SELECT
  'WO-DEMO-005',
  (SELECT ticket_id FROM tickets WHERE ticket_number = 'TKT-DEMO-006'),
  'repair',
  12, 10,
  'resolved',
  '2026-05-08 09:00:00', '2026-05-08 11:00:00',
  '2026-05-08 09:10:00', '2026-05-08 10:30:00',
  'Camera had a static IP conflict with a newly added AP. Network unreachable.',
  'Reassigned camera to a reserved VLAN IP (192.168.10.41). Updated DHCP reservation.',
  'Camera back online. PTZ control and video feed verified working.',
  10, '2026-05-08 08:30:00';


-- ============================================================
-- 6. WORK ORDER ASSIGNMENT LOGS
-- ============================================================

INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason, assigned_at)
SELECT wo_id, NULL, 11, 10, 'Initial assignment', '2026-05-01 10:00:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-001';

INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason, assigned_at)
SELECT wo_id, NULL, 12, 10, 'Initial assignment', '2026-05-07 15:00:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-002';

INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason, assigned_at)
SELECT wo_id, NULL, 11, 10, 'Initial assignment', '2026-05-05 12:00:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-003';

INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason, assigned_at)
SELECT wo_id, NULL, 13, 10, 'Initial assignment — junior tech for simple diagnosis', '2026-05-09 08:00:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-004';

INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason, assigned_at)
SELECT wo_id, NULL, 12, 10, 'Initial assignment', '2026-05-08 08:30:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-005';

-- ============================================================
-- 7. SAFETY CHECK COMPLETIONS
-- ============================================================

-- WO-DEMO-001 — all safety checks completed
INSERT IGNORE INTO wo_safety_completions (wo_id, safety_id, is_done, completed_by, completed_at)
SELECT w.wo_id, s.safety_id, 1, 11, '2026-05-02 09:10:00'
  FROM work_orders w, wo_safety_checks s
 WHERE w.wo_number = 'WO-DEMO-001';

-- WO-DEMO-002 — first two checks done (in progress)
INSERT IGNORE INTO wo_safety_completions (wo_id, safety_id, is_done, completed_by, completed_at)
SELECT w.wo_id, s.safety_id, 1, 12, '2026-05-08 09:05:00'
  FROM work_orders w
  JOIN wo_safety_checks s ON s.safety_id IN (1, 2, 3)
 WHERE w.wo_number = 'WO-DEMO-002';

-- WO-DEMO-005 — all safety checks completed
INSERT IGNORE INTO wo_safety_completions (wo_id, safety_id, is_done, completed_by, completed_at)
SELECT w.wo_id, s.safety_id, 1, 12, '2026-05-08 09:15:00'
  FROM work_orders w, wo_safety_checks s
 WHERE w.wo_number = 'WO-DEMO-005';

-- ============================================================
-- 8. CHECKLIST COMPLETIONS
-- checklist_id: 1=Projector, 2=Sound System, 4=Display, 5=General
-- ============================================================

-- WO-DEMO-001 — projector checklist fully completed
INSERT IGNORE INTO wo_checklist_completions (wo_id, item_id, is_done, notes, completed_by, completed_at)
SELECT w.wo_id, i.item_id, 1, 'Completed during repair', 11, '2026-05-02 10:30:00'
  FROM work_orders w
  JOIN wo_checklist_items i ON i.checklist_id = 1
 WHERE w.wo_number = 'WO-DEMO-001';

-- WO-DEMO-002 — sound system checklist partially done (first 3 items)
INSERT IGNORE INTO wo_checklist_completions (wo_id, item_id, is_done, notes, completed_by, completed_at)
SELECT w.wo_id, i.item_id, 1, 'Checked', 12, '2026-05-08 09:30:00'
  FROM work_orders w
  JOIN wo_checklist_items i ON i.checklist_id = 2 AND i.sort_order <= 3
 WHERE w.wo_number = 'WO-DEMO-002';

-- WO-DEMO-005 — general checklist fully completed
INSERT IGNORE INTO wo_checklist_completions (wo_id, item_id, is_done, notes, completed_by, completed_at)
SELECT w.wo_id, i.item_id, 1, 'Completed', 12, '2026-05-08 10:20:00'
  FROM work_orders w
  JOIN wo_checklist_items i ON i.checklist_id = 5
 WHERE w.wo_number = 'WO-DEMO-005';

-- ============================================================
-- 9. PARTS USED
-- part_id 1 = HDMI cable, 2 = VGA cable, 10 = Air filter
-- ============================================================

-- WO-DEMO-001: used 1 HDMI cable + 1 air filter
INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, used_by, used_at)
SELECT w.wo_id, p.part_id, 1, 11, '2026-05-02 10:00:00'
  FROM work_orders w, parts_inventory p
 WHERE w.wo_number = 'WO-DEMO-001' AND p.part_number = 'CABLE-HDMI-001';

INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, used_by, used_at)
SELECT w.wo_id, p.part_id, 1, 11, '2026-05-02 10:05:00'
  FROM work_orders w, parts_inventory p
 WHERE w.wo_number = 'WO-DEMO-001' AND p.part_number = 'PROJ-AFIL-001';

-- WO-DEMO-002: used 1 XLR cable (diagnosis)
INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, used_by, used_at)
SELECT w.wo_id, p.part_id, 1, 12, '2026-05-08 10:00:00'
  FROM work_orders w, parts_inventory p
 WHERE w.wo_number = 'WO-DEMO-002' AND p.part_number = 'CABLE-XLR-001';

-- ============================================================
-- 10. TIME LOGS
-- ============================================================

-- WO-DEMO-001 — full start/stop
INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes, elapsed_ms, logged_at)
SELECT wo_id, 11, 'start', 'repair', 'Starting projector repair', 0, '2026-05-02 09:05:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-001';
INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes, elapsed_ms, logged_at)
SELECT wo_id, 11, 'stop', 'repair', 'Repair complete, burn-in test passed', 5760000, '2026-05-02 10:45:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-001';

-- WO-DEMO-002 — started, not yet stopped
INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes, elapsed_ms, logged_at)
SELECT wo_id, 12, 'start', 'diagnosis', 'Starting audio diagnosis', 0, '2026-05-08 09:05:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-002';

-- WO-DEMO-005 — full start/stop
INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes, elapsed_ms, logged_at)
SELECT wo_id, 12, 'start', 'repair', 'Starting camera network fix', 0, '2026-05-08 09:10:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-005';
INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes, elapsed_ms, logged_at)
SELECT wo_id, 12, 'stop', 'repair', 'Camera back online, verified PTZ and stream', 4800000, '2026-05-08 10:30:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-005';

-- ============================================================
-- 11. WO NOTES
-- ============================================================

INSERT INTO wo_notes (wo_id, note_type, note_text, is_private, added_by, added_at)
SELECT wo_id, 'diagnosis', 'Ribbon cable was visibly loose on the HDMI board. Filter was grey with dust.', 0, 11, '2026-05-02 09:30:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-001';

INSERT INTO wo_notes (wo_id, note_type, note_text, is_private, added_by, added_at)
SELECT wo_id, 'repair', 'Reseated cable and secured with original clip. Replaced filter with stock PROJ-AFIL-001.', 0, 11, '2026-05-02 10:15:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-001';

INSERT INTO wo_notes (wo_id, note_type, note_text, is_private, added_by, added_at)
SELECT wo_id, 'diagnosis', 'Hum present on all channels even with no source. Suspect ground loop between amp and mixer.', 1, 12, '2026-05-08 09:45:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-002';

INSERT INTO wo_notes (wo_id, note_type, note_text, is_private, added_by, added_at)
SELECT wo_id, 'general', 'Waiting for LG panel (part# PROJ-LCD-001 equivalent for display). PO submitted to procurement.', 0, 11, '2026-05-05 14:00:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-003';

INSERT INTO wo_notes (wo_id, note_type, note_text, is_private, added_by, added_at)
SELECT wo_id, 'repair', 'IP conflict with new AP at 192.168.10.41. Moved camera to .51, updated DHCP reservation in router.', 0, 12, '2026-05-08 10:00:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-005';

-- ============================================================
-- 12. SIGNOFF (completed WOs only)
-- ============================================================

INSERT IGNORE INTO wo_signoff
  (wo_id, signed_by_user_id, signer_name, signature_path, satisfaction, feedback, signed_at)
SELECT wo_id, 20, 'Prof. Elena Bautista',
  'signatures/wo-demo-001-sig.png', 5,
  'Fixed quickly, very professional. Thank you!',
  '2026-05-02 10:55:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-001';

INSERT IGNORE INTO wo_signoff
  (wo_id, signed_by_user_id, signer_name, signature_path, satisfaction, feedback, signed_at)
SELECT wo_id, 23, 'Carla Navarro',
  'signatures/wo-demo-005-sig.png', 4,
  'Camera is back online. Took a bit to figure out the IP issue but resolved well.',
  '2026-05-08 10:35:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-005';

-- ============================================================
-- 13. FEEDBACK
-- ============================================================

INSERT IGNORE INTO wo_feedback (wo_id, requester_id, rating, comment, submitted_at)
SELECT wo_id, 20, 5, 'Excellent service. Projector works perfectly now.', '2026-05-02 11:30:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-001';

INSERT IGNORE INTO wo_feedback (wo_id, requester_id, rating, comment, submitted_at)
SELECT wo_id, 23, 4, 'Good job resolving the camera issue. Communication could be faster next time.', '2026-05-08 11:00:00'
  FROM work_orders WHERE wo_number = 'WO-DEMO-005';

-- ============================================================
-- 14. NOTIFICATIONS
-- ============================================================

INSERT IGNORE INTO notifications (user_id, title, body, link, is_read, created_at) VALUES
  (11, 'New Work Order Assigned',
   'WO-DEMO-001: Projector flickering in CAS Room 101 has been assigned to you.',
   'modules/technician/view.php?wo=WO-DEMO-001', 1, '2026-05-01 10:00:00'),

  (12, 'New Work Order Assigned',
   'WO-DEMO-002: Auditorium speakers buzzing has been assigned to you.',
   'modules/technician/view.php?wo=WO-DEMO-002', 0, '2026-05-07 15:00:00'),

  (11, 'New Work Order Assigned',
   'WO-DEMO-003: Display panel cracked in CAS Room 102 has been assigned to you.',
   'modules/technician/view.php?wo=WO-DEMO-003', 1, '2026-05-05 12:00:00'),

  (13, 'New Work Order Assigned',
   'WO-DEMO-004: Projector shows No Signal in Room 102 has been assigned to you.',
   'modules/technician/view.php?wo=WO-DEMO-004', 0, '2026-05-09 08:00:00'),

  (12, 'New Work Order Assigned',
   'WO-DEMO-005: PTZ camera offline in Auditorium has been assigned to you.',
   'modules/technician/view.php?wo=WO-DEMO-005', 1, '2026-05-08 08:30:00'),

  (10, 'Work Order Resolved',
   'WO-DEMO-001 has been resolved by Juan Reyes.',
   'modules/workorders/view.php?wo=WO-DEMO-001', 1, '2026-05-02 10:50:00'),

  (10, 'Work Order Resolved',
   'WO-DEMO-005 has been resolved by Ana Dela Cruz.',
   'modules/workorders/view.php?wo=WO-DEMO-005', 0, '2026-05-08 10:35:00'),

  (20, 'Your Ticket Has Been Resolved',
   'Ticket TKT-DEMO-001 (Projector flickering in CAS Room 101) has been resolved.',
   'modules/tickets/view.php?t=TKT-DEMO-001', 1, '2026-05-02 11:00:00'),

  (23, 'Your Ticket Has Been Resolved',
   'Ticket TKT-DEMO-006 (PTZ camera offline in Auditorium) has been resolved.',
   'modules/tickets/view.php?t=TKT-DEMO-006', 0, '2026-05-08 10:40:00');

-- ============================================================
-- 15. AUDIT LOG
-- ============================================================

INSERT INTO audit_log (user_id, action, object_type, object_id, new_values, created_at)
SELECT 10, 'create', 'ticket',
  (SELECT ticket_id FROM tickets WHERE ticket_number = 'TKT-DEMO-001'),
  '{"ticket_number":"TKT-DEMO-001","priority":"high"}',
  '2026-05-01 08:30:00'
UNION ALL
SELECT 10, 'assign', 'work_order',
  (SELECT wo_id FROM work_orders WHERE wo_number = 'WO-DEMO-001'),
  '{"assigned_to":11,"wo_number":"WO-DEMO-001"}',
  '2026-05-01 10:00:00'
UNION ALL
SELECT 11, 'resolve', 'work_order',
  (SELECT wo_id FROM work_orders WHERE wo_number = 'WO-DEMO-001'),
  '{"status":"resolved","wo_number":"WO-DEMO-001"}',
  '2026-05-02 10:45:00'
UNION ALL
SELECT 10, 'create', 'ticket',
  (SELECT ticket_id FROM tickets WHERE ticket_number = 'TKT-DEMO-002'),
  '{"ticket_number":"TKT-DEMO-002","priority":"high"}',
  '2026-05-07 14:00:00'
UNION ALL
SELECT 10, 'assign', 'work_order',
  (SELECT wo_id FROM work_orders WHERE wo_number = 'WO-DEMO-002'),
  '{"assigned_to":12,"wo_number":"WO-DEMO-002"}',
  '2026-05-07 15:00:00'
UNION ALL
SELECT 12, 'resolve', 'work_order',
  (SELECT wo_id FROM work_orders WHERE wo_number = 'WO-DEMO-005'),
  '{"status":"resolved","wo_number":"WO-DEMO-005"}',
  '2026-05-08 10:30:00';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SUMMARY
-- ============================================================
-- Users created (user_id 10–23):
--   10  Maria Santos       — IT Manager
--   11  Juan Reyes         — Technician (projector/AV expert)
--   12  Ana Dela Cruz      — Technician (audio/camera expert)
--   13  Carlos Garcia      — Technician (junior, general)
--   14  Kevin Lim          — IT Staff
--   20  Prof. Elena Bautista — Faculty (requester)
--   21  Prof. Roberto Mendoza — Faculty (requester)
--   22  Prof. Liza Torres  — Faculty (requester)
--   23  Carla Navarro      — Events Coordinator (requester)
--
-- Assets created (13 total):
--   PRJ-DEMO-001/002/003  Projectors
--   SND-DEMO-001/002      Sound Systems
--   AVS-DEMO-001          AV Switcher
--   DSP-DEMO-001/002      Displays
--   MIC-DEMO-001/002      Microphones
--   RCK-DEMO-001          AV Rack
--   CAM-DEMO-001          Camera
--   AMP-DEMO-001          Amplifier
--
-- Tickets (6):
--   TKT-DEMO-001  closed      Projector flickering
--   TKT-DEMO-002  in_progress Auditorium speakers buzzing
--   TKT-DEMO-003  on_hold     Display cracked (waiting parts)
--   TKT-DEMO-004  assigned    Projector no signal
--   TKT-DEMO-005  new         Wireless mic no audio
--   TKT-DEMO-006  resolved    PTZ camera offline
--
-- Work Orders (5):
--   WO-DEMO-001  closed      Assigned → Juan Reyes
--   WO-DEMO-002  in_progress Assigned → Ana Dela Cruz
--   WO-DEMO-003  on_hold     Assigned → Juan Reyes
--   WO-DEMO-004  assigned    Assigned → Carlos Garcia
--   WO-DEMO-005  resolved    Assigned → Ana Dela Cruz
-- ============================================================
