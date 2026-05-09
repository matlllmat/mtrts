-- ============================================================
-- MTRTS — Real Life Scenario Seed Data
-- This script populates the database with a realistic "day in the life" 
-- of the system, including completed, in-progress, and pending works.
-- ============================================================

USE mtrts_sql;

-- Disable foreign key checks for clean setup if needed, but we'll try to be additive
SET FOREIGN_KEY_CHECKS = 0;

-- CLEANUP: Remove specific test tickets/WOs if they exist to avoid duplicates
DELETE FROM wo_signoff WHERE wo_id IN (SELECT wo_id FROM work_orders WHERE wo_number LIKE 'WO-2026-%');
DELETE FROM wo_parts_used WHERE wo_id IN (SELECT wo_id FROM work_orders WHERE wo_number LIKE 'WO-2026-%');
DELETE FROM wo_checklist_completions WHERE wo_id IN (SELECT wo_id FROM work_orders WHERE wo_number LIKE 'WO-2026-%');
DELETE FROM wo_safety_completions WHERE wo_id IN (SELECT wo_id FROM work_orders WHERE wo_number LIKE 'WO-2026-%');
DELETE FROM wo_time_logs WHERE wo_id IN (SELECT wo_id FROM work_orders WHERE wo_number LIKE 'WO-2026-%');
DELETE FROM work_orders WHERE wo_number LIKE 'WO-2026-%';
DELETE FROM tickets WHERE ticket_number LIKE 'TKT-2026-%';

-- ============================================================
-- 1. ENSURE SCENARIO USERS EXIST
-- ============================================================
-- Note: We use specific IDs to match the scenario logic below
INSERT IGNORE INTO users (user_id, email, password_hash, full_name, role_id, is_active) 
VALUES 
  (4, 'it.manager@olfu.edu.ph', '$2y$10$placeholdchrerHashReplaceThisOnFirstLogin.........', 'IT Manager', 2, 1),
  (5, 'tech.reyes@olfu.edu.ph', '$2y$10$placeholdchrerHashReplaceThisOnFirstLogin.........', 'Tech Reyes', 4, 1),
  (6, 'prof.santos@olfu.edu.ph', '$2y$10$placeholdchrerHashReplaceThisOnFirstLogin.........', 'Prof. Santos', 5, 1);

-- ============================================================
-- 2. CREATE A "COMPLETED WORK" SCENARIO
-- ============================================================
-- Scenario: Projector repair in CAS 101. 
-- Flow: Ticket -> Assignment -> Safety Check -> Checklist -> Parts Used -> Time Log -> Signoff -> Resolved

-- A. Create the Ticket
INSERT INTO tickets 
  (ticket_number, requester_id, asset_id, category_id, location_id, title, description, priority, status, created_at)
VALUES 
  ('TKT-2026-0001', 6, 1, 1, 1, 'Projector flickering in CAS 101', 'The projector screen flickers every few minutes, making it hard to teach.', 'high', 'closed', '2026-05-01 08:30:00');

SET @completed_tkt_id = LAST_INSERT_ID();

-- B. Create the Work Order
INSERT INTO work_orders 
  (wo_number, ticket_id, wo_type, assigned_to, assigned_by, status, scheduled_start, scheduled_end, actual_start, actual_end, findings, actions_taken, notes, created_at)
VALUES 
  ('WO-2026-0001', @completed_tkt_id, 'repair', 5, 4, 'closed', '2026-05-02 09:00:00', '2026-05-02 11:00:00', '2026-05-02 09:05:00', '2026-05-02 10:45:00', 
   'Loose HDMI internal connection and dusty air filter.', 
   'Reseated internal cables and cleaned the air filter. Tested for 30 minutes without flicker.', 
   'Standard repair completed. Customer satisfied.', '2026-05-01 10:00:00');

SET @completed_wo_id = LAST_INSERT_ID();

-- C. Safety Checks (All Done)
INSERT INTO wo_safety_completions (wo_id, safety_id, is_done, completed_by, completed_at)
SELECT @completed_wo_id, safety_id, 1, 5, '2026-05-02 09:10:00'
FROM wo_safety_checks;

-- D. Checklist Completions (Projector Checklist is ID 1)
INSERT INTO wo_checklist_completions (wo_id, item_id, is_done, notes, completed_by, completed_at)
SELECT @completed_wo_id, item_id, 1, 'Completed during repair', 5, '2026-05-02 10:30:00'
FROM wo_checklist_items WHERE checklist_id = 1;

-- E. Parts Used (HDMI Cable)
INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, used_by, used_at)
VALUES (@completed_wo_id, 1, 1, 5, '2026-05-02 10:00:00');

-- F. Time Logs
INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes, elapsed_ms, logged_at)
VALUES 
  (@completed_wo_id, 5, 'start', 'repair', 'Starting work', 0, '2026-05-02 09:05:00'),
  (@completed_wo_id, 5, 'stop', 'repair', 'Work finished', 6000000, '2026-05-02 10:45:00'); -- ~1.6 hours

-- G. Signoff
INSERT INTO wo_signoff (wo_id, signed_by_user_id, signer_name, signature_path, satisfaction, feedback, signed_at)
VALUES (@completed_wo_id, 6, 'Prof. Santos', 'signatures/tkt-0001-sig.png', 5, 'Great work, very fast!', '2026-05-02 10:50:00');

-- ============================================================
-- 3. CREATE AN "IN-PROGRESS WORK" SCENARIO
-- ============================================================
-- Scenario: Sound system feedback in Auditorium.

INSERT INTO tickets 
  (ticket_number, requester_id, asset_id, category_id, location_id, title, description, priority, status, created_at)
VALUES 
  ('TKT-2026-0002', 6, 2, 2, 11, 'Auditorium Audio Issues', 'The speakers are making a buzzing sound during events.', 'medium', 'assigned', '2026-05-07 14:00:00');

SET @inprogress_tkt_id = LAST_INSERT_ID();

INSERT INTO work_orders 
  (wo_number, ticket_id, wo_type, assigned_to, assigned_by, status, scheduled_start, notes, created_at)
VALUES 
  ('WO-2026-0002', @inprogress_tkt_id, 'diagnosis', 5, 4, 'in_progress', '2026-05-08 09:00:00', 'Check mixer settings and XLR cables.', '2026-05-07 15:00:00');

SET @inprogress_wo_id = LAST_INSERT_ID();

-- Safety checks started but not all done
INSERT INTO wo_safety_completions (wo_id, safety_id, is_done, completed_by, completed_at)
VALUES 
  (@inprogress_wo_id, 1, 1, 5, '2026-05-08 09:05:00'),
  (@inprogress_wo_id, 2, 1, 5, '2026-05-08 09:06:00');

-- Time log started
INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes, elapsed_ms, logged_at)
VALUES (@inprogress_wo_id, 5, 'start', 'diagnosis', 'Starting diagnosis', 0, '2026-05-08 09:05:00');

-- ============================================================
-- 4. CREATE AN "ON-HOLD WORK" SCENARIO
-- ============================================================
-- Scenario: Display replacement waiting for parts.

INSERT INTO tickets 
  (ticket_number, requester_id, asset_id, category_id, location_id, title, description, priority, status, created_at)
VALUES 
  ('TKT-2026-0003', 6, 4, 4, 1, 'Display panel cracked', 'The Samsung display in the lobby has a physical crack.', 'low', 'assigned', '2026-05-05 11:00:00');

SET @onhold_tkt_id = LAST_INSERT_ID();

INSERT INTO work_orders 
  (wo_number, ticket_id, wo_type, assigned_to, assigned_by, status, on_hold_reason, scheduled_start, notes, created_at)
VALUES 
  ('WO-2026-0003', @onhold_tkt_id, 'repair', 5, 4, 'on_hold', 'waiting_parts', '2026-05-06 13:00:00', 'Need to order a replacement LCD panel.', '2026-05-05 12:00:00');

-- ============================================================
-- 5. POPULATE AUDIT LOGS FOR REALISM
-- ============================================================

INSERT INTO audit_log (user_id, action, object_type, object_id, new_values, created_at)
VALUES 
  (4, 'create', 'ticket', @completed_tkt_id, '{"title": "Projector flickering in CAS 101"}', '2026-05-01 08:30:00'),
  (4, 'assign', 'work_order', @completed_wo_id, '{"assigned_to": 5}', '2026-05-01 10:00:00'),
  (5, 'resolve', 'work_order', @completed_wo_id, '{"status": "resolved"}', '2026-05-02 10:45:00');

-- ============================================================
-- 6. ADD SOME NOTIFICATIONS
-- ============================================================

INSERT INTO notifications (user_id, title, body, link, is_read, created_at)
VALUES 
  (5, 'New Work Order Assigned', 'You have been assigned WO-2026-0002 for Auditorium Audio Issues.', 'modules/technician/view.php?id=2', 0, '2026-05-07 15:00:00'),
  (4, 'Work Order Resolved', 'WO-2026-0001 has been resolved by Tech Reyes.', 'modules/workorders/view.php?id=1', 1, '2026-05-02 10:50:00');

-- ============================================================
-- 7. ADDITIONAL SEED DATA (Extra Load)
-- ============================================================

-- A. New Users
INSERT IGNORE INTO users (email, password_hash, full_name, role_id, is_active) 
VALUES 
  ('mike.tech@olfu.edu.ph', '$2y$10$placeholdchrerHashReplaceThisOnFirstLogin.........', 'Mike Smith', 4, 1),
  ('jane.doe@olfu.edu.ph', '$2y$10$placeholdchrerHashReplaceThisOnFirstLogin.........', 'Jane Doe', 7, 1);

-- B. New Assets
INSERT IGNORE INTO assets 
  (asset_tag, serial_number, manufacturer, model, category_id, location_id, install_date, status)
VALUES 
  ('LAP-2026-002', 'SN-DELL-789', 'Dell', 'Latitude 5420', 2, 4, '2025-02-10', 'active'),
  ('UPS-2026-002', 'SN-APC-999', 'APC', 'Smart-UPS 1500', 3, 5, '2024-12-15', 'active');

-- C. New Tickets
INSERT INTO tickets 
  (ticket_number, requester_id, asset_id, category_id, location_id, title, description, priority, status, created_at)
SELECT 'TKT-2026-0004', u.user_id, a.asset_id, 2, 4, 'Laptop won''t boot', 'The laptop displays a "No bootable device found" error on startup.', 'high', 'new', NOW()
FROM users u, assets a WHERE u.email = 'jane.doe@olfu.edu.ph' AND a.asset_tag = 'LAP-2026-002';

INSERT INTO tickets 
  (ticket_number, requester_id, asset_id, category_id, location_id, title, description, priority, status, created_at)
SELECT 'TKT-2026-0005', u.user_id, a.asset_id, 3, 5, 'UPS Beeping Constantly', 'The UPS in the server room is beeping every 5 seconds. Power seems fine.', 'medium', 'new', NOW()
FROM users u, assets a WHERE u.email = 'jane.doe@olfu.edu.ph' AND a.asset_tag = 'UPS-2026-002';

SET FOREIGN_KEY_CHECKS = 1;
