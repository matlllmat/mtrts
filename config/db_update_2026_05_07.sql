-- MTRTS Database Update - 2026-05-07
-- Fixes 500 error in Technician Ops when logging Maintenance or Follow-up labor.

ALTER TABLE wo_time_logs 
MODIFY COLUMN labor_type ENUM('travel','diagnosis','repair','cleanup','maintenance','follow_up','other') NULL;
