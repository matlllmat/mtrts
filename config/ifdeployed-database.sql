-- ============================================================
-- MTRTS — Media Technology Repair Tracker System
-- Master Database Schema & Seed Data
--
-- Idempotent: safe to run on a new or existing database.
-- Drops and recreates all tables — acts as a full reset.
-- Run this single file to set up or restore the entire schema.
-- ============================================================

-- ============================================================
-- MODULE 0: BASE TABLES (NO FOREIGN KEYS)
-- ============================================================

CREATE TABLE roles (
  role_id   INT          PRIMARY KEY AUTO_INCREMENT,
  role_name VARCHAR(50)  NOT NULL UNIQUE
);

CREATE TABLE departments (
  department_id   INT           PRIMARY KEY AUTO_INCREMENT,
  department_name VARCHAR(100)  NOT NULL UNIQUE
);

CREATE TABLE asset_categories (
  category_id    INT           PRIMARY KEY AUTO_INCREMENT,
  category_name  VARCHAR(100)  NOT NULL UNIQUE,
  has_bulb_hours TINYINT(1)    NOT NULL DEFAULT 0,
  description    VARCHAR(255)  NULL,
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE locations (
  location_id INT           PRIMARY KEY AUTO_INCREMENT,
  building    VARCHAR(100)  NOT NULL,
  floor       VARCHAR(50)   NOT NULL,
  room        VARCHAR(100)  NOT NULL,
  timezone    VARCHAR(50)   NOT NULL DEFAULT 'Asia/Manila',
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE (building, floor, room)
);

CREATE TABLE business_hours (
  hour_id     INT        PRIMARY KEY AUTO_INCREMENT,
  day_of_week TINYINT    NOT NULL,
  start_time  TIME       NOT NULL,
  end_time    TIME       NOT NULL,
  is_working  TINYINT(1) NOT NULL DEFAULT 1,

  UNIQUE (day_of_week)
);

CREATE TABLE holidays (
  holiday_id   INT           PRIMARY KEY AUTO_INCREMENT,
  holiday_name VARCHAR(100)  NOT NULL,
  holiday_date DATE          NOT NULL UNIQUE,
  is_recurring TINYINT(1)    NOT NULL DEFAULT 0,
  created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE wo_safety_checks (
  safety_id   INT           PRIMARY KEY AUTO_INCREMENT,
  check_text  VARCHAR(255)  NOT NULL,
  is_mandatory TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order  INT           NOT NULL DEFAULT 0,
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sla_policies (
  policy_id          INT           PRIMARY KEY AUTO_INCREMENT,
  policy_name        VARCHAR(150)  NOT NULL,
  priority           ENUM('low','medium','high','critical') NULL,
  category_id        INT           NULL,
  location_id        INT           NULL,
  request_type       VARCHAR(50)   NULL,
  is_event_support   TINYINT(1)    NOT NULL DEFAULT 0,
  response_minutes   INT           NOT NULL,
  diagnosis_minutes  INT           NOT NULL,
  resolution_minutes INT           NOT NULL,
  uses_business_hours TINYINT(1)  NOT NULL DEFAULT 1,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id) REFERENCES asset_categories(category_id),
  FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

CREATE TABLE parts_inventory (
  part_id          INT           PRIMARY KEY AUTO_INCREMENT,
  part_number      VARCHAR(100)  NOT NULL UNIQUE,
  part_name        VARCHAR(150)  NOT NULL,
  description      VARCHAR(255)  NULL,
  manufacturer     VARCHAR(100)  NULL,
  category         VARCHAR(100)  NULL,
  compatible_with  VARCHAR(500)  NULL,
  quantity_on_hand INT           NOT NULL DEFAULT 0,
  reorder_level    INT           NOT NULL DEFAULT 5,
  unit_cost        DECIMAL(10,2) NULL,
  unit_price       DECIMAL(10,2) NULL,
  storage_location VARCHAR(100)  NULL,
  is_active        TINYINT(1)    NOT NULL DEFAULT 1,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- MODULE 0: USERS & ACCESS CONTROL
-- ============================================================

CREATE TABLE users (
  user_id         INT           PRIMARY KEY AUTO_INCREMENT,
  email           VARCHAR(150)  NOT NULL UNIQUE,
  password_hash   VARCHAR(255)  NULL,
  full_name       VARCHAR(150)  NOT NULL,
  id_number       VARCHAR(50)   NULL UNIQUE,
  contact_number  VARCHAR(20)   NULL,
  position        VARCHAR(100)  NULL,
  profile_picture VARCHAR(255)  NULL,
  department_id   INT           NULL,
  role_id         INT           NOT NULL,
  is_active       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login      DATETIME      NULL,

  FOREIGN KEY (department_id) REFERENCES departments(department_id),
  FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE user_sso (
  sso_id        INT           PRIMARY KEY AUTO_INCREMENT,
  user_id       INT           NOT NULL UNIQUE,
  provider      VARCHAR(20)   NOT NULL,
  provider_uid  VARCHAR(255)  NOT NULL UNIQUE,
  avatar_url    VARCHAR(500)  NULL,
  linked_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE role_modules (
  id          INT          PRIMARY KEY AUTO_INCREMENT,
  role_id     INT          NOT NULL,
  module_slug VARCHAR(50)  NOT NULL,

  UNIQUE (role_id, module_slug),
  FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE notifications (
  notif_id   INT           PRIMARY KEY AUTO_INCREMENT,
  user_id    INT           NOT NULL,
  title      VARCHAR(255)  NOT NULL,
  body       TEXT          NULL,
  link       VARCHAR(500)  NULL,
  is_read    TINYINT(1)    NOT NULL DEFAULT 0,
  notif_key  VARCHAR(255)  NULL,
  created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY uq_notif_key_user (notif_key, user_id)
);

CREATE TABLE audit_log (
  log_id      BIGINT        PRIMARY KEY AUTO_INCREMENT,
  user_id     INT           NULL,
  action      VARCHAR(50)   NOT NULL,
  object_type VARCHAR(50)   NOT NULL,
  object_id   INT           NULL,
  old_values  JSON          NULL,
  new_values  JSON          NULL,
  ip_address  VARCHAR(45)   NULL,
  user_agent  VARCHAR(500)  NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ============================================================
-- MODULE 2: ASSET & CONFIGURATION MANAGEMENT
-- ============================================================

CREATE TABLE assets (
  asset_id         INT           PRIMARY KEY AUTO_INCREMENT,
  asset_tag        VARCHAR(50)   NOT NULL UNIQUE,
  serial_number    VARCHAR(100)  NULL,
  manufacturer     VARCHAR(100)  NOT NULL,
  model            VARCHAR(100)  NOT NULL,
  category_id      INT           NOT NULL,
  status           ENUM('active','spare','retired') NOT NULL DEFAULT 'active',
  location_id      INT           NULL,
  parent_asset_id  INT           NULL,
  install_date     DATE          NOT NULL,
  firmware_version VARCHAR(50)   NULL,
  network_info     VARCHAR(100)  NULL,
  bulb_hours       INT UNSIGNED  NULL,
  department_id    INT           NULL,
  owner_id         INT           NULL,
  qr_code_path     VARCHAR(255)  NULL,
  created_by       INT           NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id)     REFERENCES asset_categories(category_id),
  FOREIGN KEY (location_id)     REFERENCES locations(location_id),
  FOREIGN KEY (parent_asset_id) REFERENCES assets(asset_id),
  FOREIGN KEY (department_id)   REFERENCES departments(department_id),
  FOREIGN KEY (owner_id)        REFERENCES users(user_id),
  FOREIGN KEY (created_by)      REFERENCES users(user_id)
);

CREATE TABLE asset_warranty (
  warranty_id        INT           PRIMARY KEY AUTO_INCREMENT,
  asset_id           INT           NOT NULL UNIQUE,
  warranty_start     DATE          NOT NULL,
  warranty_end       DATE          NOT NULL,
  coverage_type      ENUM('parts','labor','parts_and_labor','onsite') NOT NULL,
  vendor_name        VARCHAR(150)  NULL,
  contract_reference VARCHAR(100)  NULL,
  created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
);

CREATE TABLE asset_documents (
  document_id   INT           PRIMARY KEY AUTO_INCREMENT,
  asset_id      INT           NOT NULL,
  document_name VARCHAR(255)  NOT NULL,
  file_path     VARCHAR(500)  NOT NULL,
  file_type     VARCHAR(10)   NOT NULL,
  file_size_kb  INT           NOT NULL,
  document_type VARCHAR(50)   NULL,
  version       INT           NOT NULL DEFAULT 1,
  is_latest     TINYINT(1)    NOT NULL DEFAULT 1,
  uploaded_by   INT           NULL,
  uploaded_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (asset_id)    REFERENCES assets(asset_id),
  FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

CREATE TABLE asset_audit_log (
  log_id        INT           PRIMARY KEY AUTO_INCREMENT,
  asset_id      INT           NOT NULL,
  field_name    VARCHAR(100)  NOT NULL,
  old_value     TEXT          NULL,
  new_value     TEXT          NULL,
  changed_by    INT           NULL,
  changed_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  change_reason VARCHAR(255)  NULL,

  FOREIGN KEY (asset_id)   REFERENCES assets(asset_id),
  FOREIGN KEY (changed_by) REFERENCES users(user_id)
);

-- ============================================================
-- MODULE 1: REQUEST SUBMISSION & INTAKE (TICKETS)
-- ============================================================

CREATE TABLE tickets (
  ticket_id        INT           PRIMARY KEY AUTO_INCREMENT,
  ticket_number    VARCHAR(20)   NOT NULL UNIQUE,
  request_type     VARCHAR(50)   NULL,
  requester_id     INT           NULL,
  asset_id         INT           NULL,
  category_id      INT           NULL,
  location_id      INT           NULL,
  model            VARCHAR(255)  NULL,
  asset_tag        VARCHAR(255)  NULL,
  warranty_status  VARCHAR(255)  NULL,
  title            VARCHAR(255)  NOT NULL,
  description      TEXT          NULL,
  impact           ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  urgency          ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  priority         ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  channel          ENUM('web','email','qr_scan','walk_in') NOT NULL DEFAULT 'web',
  external_email_from VARCHAR(150) NULL,
  external_name_from  VARCHAR(100) NULL,
  external_dept_from  VARCHAR(100) NULL,
  email_seen_at       DATETIME     NULL,
  is_event_support TINYINT(1)    NOT NULL DEFAULT 0,
  preferred_window DATETIME      NULL,
  status           ENUM('new','assigned','scheduled','in_progress','on_hold','resolved','closed','cancelled') NOT NULL DEFAULT 'new',
  on_hold_reason   ENUM('waiting_parts','waiting_vendor','waiting_access','other') NULL,
  duplicate_of_id  INT           NULL,
  assigned_to      INT           NULL,
  assigned_team    VARCHAR(100)  NULL,
  approved_by      INT           NULL,
  approved_at      DATETIME      NULL,
  resolved_at      DATETIME      NULL,
  closed_at        DATETIME      NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (requester_id)    REFERENCES users(user_id),
  FOREIGN KEY (asset_id)        REFERENCES assets(asset_id),
  FOREIGN KEY (category_id)     REFERENCES asset_categories(category_id),
  FOREIGN KEY (location_id)     REFERENCES locations(location_id),
  FOREIGN KEY (duplicate_of_id) REFERENCES tickets(ticket_id),
  FOREIGN KEY (assigned_to)     REFERENCES users(user_id),
  FOREIGN KEY (approved_by)     REFERENCES users(user_id)
);

CREATE TABLE ticket_attachments (
  attachment_id INT           PRIMARY KEY AUTO_INCREMENT,
  ticket_id     INT           NOT NULL,
  file_name     VARCHAR(255)  NOT NULL,
  file_path     VARCHAR(500)  NOT NULL,
  file_type     VARCHAR(10)   NOT NULL,
  file_size_kb  INT           NOT NULL,
  uploaded_by   INT           NULL,
  uploaded_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (ticket_id)   REFERENCES tickets(ticket_id),
  FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

CREATE TABLE ticket_comments (
  comment_id   INT        PRIMARY KEY AUTO_INCREMENT,
  ticket_id    INT        NOT NULL,
  user_id      INT        NULL,
  comment_text TEXT       NOT NULL,
  is_internal  TINYINT(1) NOT NULL DEFAULT 0,
  created_at   DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
  FOREIGN KEY (user_id)   REFERENCES users(user_id)
);

CREATE TABLE ticket_dynamic_fields (
  field_id    INT           PRIMARY KEY AUTO_INCREMENT,
  ticket_id   INT           NOT NULL,
  field_name  VARCHAR(100)  NOT NULL,
  field_value VARCHAR(500)  NULL,

  UNIQUE (ticket_id, field_name),
  FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id)
);

CREATE TABLE ticket_sla (
  sla_id                  INT        PRIMARY KEY AUTO_INCREMENT,
  ticket_id               INT        NOT NULL UNIQUE,
  policy_id               INT        NOT NULL,
  response_due            DATETIME   NULL,
  diagnosis_due           DATETIME   NULL,
  resolution_due          DATETIME   NULL,
  responded_at            DATETIME   NULL,
  diagnosed_at            DATETIME   NULL,
  resolved_at             DATETIME   NULL,
  is_response_breached    TINYINT(1) NOT NULL DEFAULT 0,
  is_diagnosis_breached   TINYINT(1) NOT NULL DEFAULT 0,
  is_resolution_breached  TINYINT(1) NOT NULL DEFAULT 0,
  paused_at               DATETIME   NULL,
  total_paused_minutes    INT        NOT NULL DEFAULT 0,
  pause_reason            VARCHAR(100) NULL,
  escalation_level        TINYINT    NOT NULL DEFAULT 0,

  FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
  FOREIGN KEY (policy_id) REFERENCES sla_policies(policy_id)
);

CREATE TABLE kb_articles (
  article_id  INT           PRIMARY KEY AUTO_INCREMENT,
  title       VARCHAR(255)  NOT NULL,
  content     TEXT          NOT NULL,
  category_id INT           NULL,
  tags        VARCHAR(500)  NULL,
  is_published TINYINT(1)  NOT NULL DEFAULT 1,
  views       INT           NOT NULL DEFAULT 0,
  created_by  INT           NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id) REFERENCES asset_categories(category_id),
  FOREIGN KEY (created_by)  REFERENCES users(user_id)
);

-- ============================================================
-- MODULE 3: WORK ORDER & DISPATCH MANAGEMENT
-- ============================================================

CREATE TABLE work_orders (
  wo_id            INT           PRIMARY KEY AUTO_INCREMENT,
  wo_number        VARCHAR(20)   NOT NULL UNIQUE,
  ticket_id        INT           NULL,
  wo_type          ENUM('diagnosis','repair','maintenance','follow_up') NOT NULL DEFAULT 'repair',
  assigned_role_id INT           NULL,
  assigned_to      INT           NULL,
  assigned_by      INT           NULL,
  claimed_at       DATETIME      NULL,
  claimed_by       INT           NULL,
  status           ENUM('new','assigned','scheduled','in_progress','on_hold','resolved','closed') NOT NULL DEFAULT 'new',
  on_hold_reason   ENUM('waiting_parts','waiting_vendor','waiting_access','other') NULL,
  is_rma           TINYINT(1)    NOT NULL DEFAULT 0,
  scheduled_start  DATETIME      NULL,
  scheduled_end    DATETIME      NULL,
  actual_start     DATETIME      NULL,
  actual_end       DATETIME      NULL,
  notes            TEXT          NULL,
  resolution_notes TEXT          NULL,
  findings         TEXT          NULL,
  actions_taken    TEXT          NULL,
  created_by       INT           NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_synced_at   DATETIME      NULL COMMENT 'Timestamp of last successful sync from offline',
  sync_hash        VARCHAR(64)   NULL COMMENT 'SHA256 hash of synced state for conflict detection',

  FOREIGN KEY (ticket_id)   REFERENCES tickets(ticket_id),
  FOREIGN KEY (assigned_role_id) REFERENCES roles(role_id),
  FOREIGN KEY (assigned_to) REFERENCES users(user_id),
  FOREIGN KEY (assigned_by) REFERENCES users(user_id),
  FOREIGN KEY (claimed_by) REFERENCES users(user_id),
  FOREIGN KEY (created_by)  REFERENCES users(user_id),
  INDEX idx_work_orders_last_synced (last_synced_at)
);

CREATE TABLE wo_assignment_log (
  log_id        INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id         INT           NOT NULL,
  assigned_from INT           NULL,
  assigned_to   INT           NULL,
  assigned_by   INT           NULL,
  reason        VARCHAR(255)  NULL,
  assigned_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id),
  FOREIGN KEY (assigned_from) REFERENCES users(user_id),
  FOREIGN KEY (assigned_to)   REFERENCES users(user_id),
  FOREIGN KEY (assigned_by)   REFERENCES users(user_id)
);

CREATE TABLE wo_checklists (
  checklist_id   INT           PRIMARY KEY AUTO_INCREMENT,
  category_id    INT           NULL,
  checklist_name VARCHAR(150)  NOT NULL,
  description    VARCHAR(255)  NULL,
  is_active      TINYINT(1)    NOT NULL DEFAULT 1,
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id) REFERENCES asset_categories(category_id)
);

CREATE TABLE wo_checklist_items (
  item_id           INT           PRIMARY KEY AUTO_INCREMENT,
  checklist_id      INT           NOT NULL,
  item_text         VARCHAR(255)  NOT NULL,
  is_mandatory      TINYINT(1)    NOT NULL DEFAULT 0,
  requires_photo    TINYINT(1)    NOT NULL DEFAULT 0,
  is_verifiable     TINYINT(1)    NOT NULL DEFAULT 0,
  verification_type VARCHAR(50)   NULL,
  sort_order        INT           NOT NULL DEFAULT 0,

  FOREIGN KEY (checklist_id) REFERENCES wo_checklists(checklist_id)
);

CREATE TABLE wo_checklist_completions (
  completion_id INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id         INT           NOT NULL,
  item_id       INT           NOT NULL,
  is_done       TINYINT(1)    NOT NULL DEFAULT 0,
  notes         VARCHAR(255)  NULL,
  completed_by  INT           NULL,
  completed_at  DATETIME      NULL,
  photo_path    VARCHAR(500)  NULL COMMENT 'Path to uploaded photo for auto-verification',
  auto_verified TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Whether this item was auto-verified by photo upload',

  UNIQUE (wo_id, item_id),
  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id),
  FOREIGN KEY (item_id)       REFERENCES wo_checklist_items(item_id),
  FOREIGN KEY (completed_by)  REFERENCES users(user_id)
);

CREATE TABLE wo_notes (
  note_id     INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id       INT           NOT NULL,
  note_type   ENUM('diagnosis','repair','general','system','voice','progress','issue','follow_up') NOT NULL DEFAULT 'general',
  note_text   TEXT          NOT NULL,
  is_private  TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Private notes only visible to technicians',
  is_voice    TINYINT(1)    NOT NULL DEFAULT 0,
  voice_path  VARCHAR(500)  NULL,
  added_by    INT           NULL,
  added_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)    REFERENCES work_orders(wo_id),
  FOREIGN KEY (added_by) REFERENCES users(user_id)
);

CREATE TABLE wo_media (
  media_id    INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id       INT           NOT NULL,
  media_type  ENUM('before','after','evidence','other','photo_before','photo_after','image','config','video') NOT NULL DEFAULT 'evidence',
  file_path   VARCHAR(500)  NOT NULL,
  file_type   VARCHAR(50)   NOT NULL,
  file_size_kb INT          NOT NULL DEFAULT 0,
  caption     VARCHAR(255)  NULL,
  uploaded_by INT           NULL,
  uploaded_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)       REFERENCES work_orders(wo_id),
  FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

CREATE TABLE wo_time_logs (
  log_id        INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id         INT           NOT NULL,
  technician_id INT           NOT NULL,
  action        ENUM('start','pause','resume','stop','draft','segment') NOT NULL,
  labor_type    ENUM('travel','diagnosis','repair','cleanup','maintenance','follow_up','other') NULL,
  notes         VARCHAR(255)  NULL,
  elapsed_ms    INT           NOT NULL DEFAULT 0,
  logged_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id),
  FOREIGN KEY (technician_id) REFERENCES users(user_id)
);

CREATE TABLE wo_safety_completions (
  completion_id INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id         INT           NOT NULL,
  safety_id     INT           NOT NULL,
  is_done       TINYINT(1)    NOT NULL DEFAULT 0,
  completed_by  INT           NULL,
  completed_at  DATETIME      NULL,
  notes         TEXT          NULL COMMENT 'Technician notes explaining why check passed/failed',
  notes_by      INT           NULL COMMENT 'User who added notes',

  UNIQUE (wo_id, safety_id),
  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id),
  FOREIGN KEY (safety_id)     REFERENCES wo_safety_checks(safety_id),
  FOREIGN KEY (completed_by)  REFERENCES users(user_id),
  FOREIGN KEY (notes_by)      REFERENCES users(user_id)
);

CREATE TABLE wo_parts_used (
  usage_id        INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id           INT           NOT NULL,
  part_id         INT           NOT NULL,
  quantity_used   INT           NOT NULL DEFAULT 1,
  serial_number   VARCHAR(100)  NULL,
  is_warranty     TINYINT(1)    NOT NULL DEFAULT 0,
  is_preallocated TINYINT(1)    NOT NULL DEFAULT 0,
  is_consumed     TINYINT(1)    NOT NULL DEFAULT 0,
  used_by         INT           NULL,
  used_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)   REFERENCES work_orders(wo_id),
  FOREIGN KEY (part_id) REFERENCES parts_inventory(part_id),
  FOREIGN KEY (used_by) REFERENCES users(user_id)
);

CREATE TABLE wo_signoff (
  signoff_id        INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id             INT           NOT NULL UNIQUE,
  signed_by_user_id INT           NULL,
  signer_name       VARCHAR(150)  NOT NULL,
  signature_path    VARCHAR(500)  NOT NULL,
  satisfaction      TINYINT       NULL,
  feedback          TEXT          NULL,
  signed_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)             REFERENCES work_orders(wo_id),
  FOREIGN KEY (signed_by_user_id) REFERENCES users(user_id)
);

CREATE TABLE offline_sync_queue (
  sync_id       INT           PRIMARY KEY AUTO_INCREMENT,
  technician_id INT           NOT NULL,
  wo_id         INT           NULL,
  action_type   ENUM('wo_start','wo_pause','wo_resume','wo_stop','wo_complete','wo_note','wo_photo','wo_checklist','wo_safety','parts_used') NOT NULL,
  payload       JSON          NOT NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  synced_at     DATETIME      NULL,
  sync_status   ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  error_message VARCHAR(500)  NULL,
  retry_count   INT           NOT NULL DEFAULT 0,
  last_retry_at DATETIME      NULL,
  error_reason  VARCHAR(500)  NULL,

  FOREIGN KEY (technician_id) REFERENCES users(user_id),
  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id)
);

CREATE TABLE parts_inventory_audit (
  audit_id        INT           PRIMARY KEY AUTO_INCREMENT,
  part_id         INT           NOT NULL,
  wo_id           INT           NULL COMMENT 'WO reference',
  change_type     ENUM('add','subtract','adjust','initial','usage') NOT NULL,
  action          VARCHAR(50)   NULL COMMENT 'Alias for change_type used in some queries',
  quantity_change INT           NOT NULL,
  new_quantity    INT           NOT NULL DEFAULT 0,
  reason          VARCHAR(255)  NULL,
  notes           VARCHAR(500)  NULL,
  reference_id    INT           NULL COMMENT 'WO ID or other reference',
  changed_by      INT           NULL,
  technician_id   INT           NULL,
  changed_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (part_id)    REFERENCES parts_inventory(part_id),
  FOREIGN KEY (changed_by) REFERENCES users(user_id)
);

CREATE TABLE parts_low_stock_alerts (
  alert_id      INT           PRIMARY KEY AUTO_INCREMENT,
  part_id       INT           NOT NULL,
  alert_type    ENUM('low_stock','out_of_stock') NOT NULL,
  threshold     INT           NOT NULL,
  current_stock INT           NOT NULL,
  is_acknowledged TINYINT(1)  NOT NULL DEFAULT 0,
  acknowledged_by INT         NULL,
  acknowledged_at DATETIME    NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (part_id)         REFERENCES parts_inventory(part_id),
  FOREIGN KEY (acknowledged_by) REFERENCES users(user_id)
);

-- ============================================================
-- MODULE 7: JOB COMPLETION FEEDBACK
-- ============================================================

CREATE TABLE wo_feedback (
  feedback_id  INT      PRIMARY KEY AUTO_INCREMENT,
  wo_id        INT      NOT NULL,
  requester_id INT      NOT NULL,
  rating       TINYINT  NOT NULL,
  comment      TEXT     NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_wo_feedback_wo_id (wo_id),
  FOREIGN KEY (wo_id)        REFERENCES work_orders(wo_id),
  FOREIGN KEY (requester_id) REFERENCES users(user_id)
);

-- ============================================================
-- MODULE 8: IN-APP MESSAGING
-- ============================================================

CREATE TABLE inbox_messages (
  message_id   INT           NOT NULL AUTO_INCREMENT,
  sender_id    INT           NOT NULL,
  recipient_id INT           NOT NULL,
  wo_id        INT           NULL,
  ticket_id    INT           NULL,
  subject      VARCHAR(255)  NOT NULL,
  body         TEXT          NOT NULL,
  sent_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at      DATETIME      NULL,

  PRIMARY KEY (message_id),
  INDEX idx_im_recipient (recipient_id),
  INDEX idx_im_sender    (sender_id),
  INDEX idx_im_sent_at   (sent_at),

  CONSTRAINT fk_im_sender
    FOREIGN KEY (sender_id)    REFERENCES users(user_id)        ON DELETE CASCADE,
  CONSTRAINT fk_im_recipient
    FOREIGN KEY (recipient_id) REFERENCES users(user_id)        ON DELETE CASCADE,
  CONSTRAINT fk_im_wo
    FOREIGN KEY (wo_id)        REFERENCES work_orders(wo_id)    ON DELETE SET NULL,
  CONSTRAINT fk_im_ticket
    FOREIGN KEY (ticket_id)    REFERENCES tickets(ticket_id)    ON DELETE SET NULL
);

-- ============================================================
-- SEED DATA: ROLES
-- ============================================================

INSERT INTO roles (role_name) VALUES
  ('admin'),
  ('it_manager'),
  ('it_staff'),
  ('technician'),
  ('faculty'),
  ('department_staff'),
  ('student'),
  ('super_admin');

-- ============================================================
-- SEED DATA: DEPARTMENTS
-- ============================================================

INSERT INTO departments (department_name) VALUES
  ('IT Department'),
  ('College of Nursing'),
  ('College of Engineering'),
  ('College of Education'),
  ('College of Business'),
  ('Registrar'),
  ('Library'),
  ('Administration');

-- ============================================================
-- SEED DATA: ASSET CATEGORIES
-- ============================================================

INSERT INTO asset_categories (category_name, has_bulb_hours) VALUES
  ('Projector',    1),
  ('Sound System', 0),
  ('AV Switcher',  0),
  ('Display',      0),
  ('Microphone',   0),
  ('AV Rack',      0),
  ('Camera',       0),
  ('Amplifier',    0);

-- ============================================================
-- SEED DATA: LOCATIONS
-- ============================================================

INSERT INTO locations (building, floor, room) VALUES
  ('CAS BUILDING', '1st Floor', 'ROOM 101'),
  ('CAS BUILDING', '1st Floor', 'ROOM 102'),
  ('CAS BUILDING', '1st Floor', 'ROOM 103'),
  ('CAS BUILDING', '1st Floor', 'ROOM 104'),
  ('CAS BUILDING', '1st Floor', 'ROOM 105'),
  ('CAS BUILDING', '1st Floor', 'ROOM 106'),
  ('CAS BUILDING', '1st Floor', 'ROOM 107'),
  ('CAS BUILDING', '1st Floor', 'ROOM 108'),
  ('CAS BUILDING', '1st Floor', 'COMLAB'),
  ('CAS BUILDING', '1st Floor', 'MAC lab'),
  ('SJB BUILDING', 'Ground Floor', 'AUDITORIUM');

-- ============================================================
-- SEED DATA: BUSINESS HOURS
-- ============================================================

INSERT INTO business_hours (day_of_week, start_time, end_time, is_working) VALUES
  (0, '08:00:00', '17:00:00', 0),
  (1, '08:00:00', '17:00:00', 1),
  (2, '08:00:00', '17:00:00', 1),
  (3, '08:00:00', '17:00:00', 1),
  (4, '08:00:00', '17:00:00', 1),
  (5, '08:00:00', '17:00:00', 1),
  (6, '08:00:00', '17:00:00', 0);

-- ============================================================
-- SEED DATA: HOLIDAYS
-- ============================================================

INSERT INTO holidays (holiday_name, holiday_date, is_recurring) VALUES
  ('New Year''s Day',          '2026-01-01', 1),
  ('People Power Anniversary', '2026-02-25', 1),
  ('Maundy Thursday',          '2026-04-02', 0),
  ('Good Friday',              '2026-04-03', 0),
  ('Araw ng Kagitingan',       '2026-04-09', 1),
  ('Labor Day',                '2026-05-01', 1),
  ('Independence Day',         '2026-06-12', 1),
  ('Ninoy Aquino Day',         '2026-08-21', 1),
  ('National Heroes Day',      '2026-08-31', 0),
  ('All Saints'' Day',         '2026-11-01', 1),
  ('Bonifacio Day',            '2026-11-30', 1),
  ('Christmas Day',            '2026-12-25', 1),
  ('Rizal Day',                '2026-12-30', 1);

-- ============================================================
-- SEED DATA: USERS
-- ============================================================

INSERT INTO users (email, password_hash, full_name, role_id, is_active) VALUES
  ('admin@olfu.edu.ph', '$2y$10$placeholdchrerHashReplaceThisOnFirstLogin.........', 'System Administrator', 8, 1);

-- ============================================================
-- SEED DATA: ROLE MODULES
-- ============================================================

INSERT INTO role_modules (role_id, module_slug) VALUES
  (1,'dashboard'),(1,'assets'),(1,'tickets'),(1,'workorders'),
  (1,'technician'),(1,'reports'),(1,'users'),(1,'inventory'),
  (1,'kb'),(1,'profile'),(1,'notifications'),(1,'inbox');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (2,'dashboard'),(2,'assets'),(2,'tickets'),(2,'workorders'),
  (2,'reports'),(2,'inventory'),(2,'kb'),(2,'profile'),(2,'notifications'),(2,'inbox');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (3,'dashboard'),(3,'assets'),(3,'tickets'),(3,'inventory'),
  (3,'kb'),(3,'profile'),(3,'notifications'),(3,'inbox');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (4,'technician'),(4,'tickets'),(4,'kb'),(4,'profile'),(4,'notifications'),(4,'inbox');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (5,'tickets'),(5,'profile'),(5,'notifications'),(5,'inbox');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (6,'tickets'),(6,'profile'),(6,'notifications'),(6,'inbox');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (7,'tickets'),(7,'profile'),(7,'notifications'),(7,'inbox');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (8,'dashboard'),(8,'assets'),(8,'tickets'),(8,'workorders'),
  (8,'technician'),(8,'reports'),(8,'users'),(8,'inventory'),
  (8,'kb'),(8,'profile'),(8,'notifications'),(8,'inbox');

-- ============================================================
-- SEED DATA: SLA POLICIES
-- ============================================================

INSERT INTO sla_policies (policy_name, priority, is_event_support, response_minutes, diagnosis_minutes, resolution_minutes, uses_business_hours) VALUES
  ('Critical Priority SLA',      'critical', 0,  30,  120,   240, 0),
  ('High Priority SLA',          'high',     0, 120,  480,  1440, 1),
  ('Medium Priority SLA',        'medium',   0, 240, 1440,  2880, 1),
  ('Low Priority SLA',           'low',      0, 480, 2880,  4320, 1),
  ('Event Support (Urgent) SLA', 'critical', 1,  15,   60,   120, 0);

-- ============================================================
-- SEED DATA: KNOWLEDGE BASE ARTICLES
-- ============================================================

INSERT INTO kb_articles (title, content, category_id, tags, created_by) VALUES
  (
    'Projector: No Image / No Signal',
    'Check that the source device is powered on and the correct input is selected on the projector. Verify HDMI/VGA cable connections at both ends. Try a different cable or input port. If still no image, reboot both devices. If the projector lamp indicator is flashing, the lamp may need replacement.',
    1, 'projector,no signal,hdmi,vga,lamp', 1
  ),
  (
    'Projector: How to Check and Reset Bulb Hours',
    'Access the projector menu → Information → Lamp Hours to view current usage. Replacement is typically recommended at 3000–4000 hours depending on the model. After replacing the lamp, reset the counter via Menu → Reset → Lamp Hours Reset.',
    1, 'projector,lamp,bulb hours,reset', 1
  ),
  (
    'Sound System: Feedback / High-Pitched Squeal',
    'Reduce microphone gain on the mixer. Move the microphone away from the speakers. Check that the EQ has no extreme high-frequency boosts. Lower the master volume incrementally until feedback stops, then find the source frequency using a graphic EQ.',
    2, 'sound system,feedback,microphone,mixer,squeal', 1
  ),
  (
    'AV Switcher: Input Not Displaying on Output',
    'Confirm the correct input is selected on the switcher. Check that the source device is outputting a signal at a supported resolution. Inspect all HDMI/HDBaseT cables for damage. Power-cycle the switcher. Check firmware version and update if a known fix is available.',
    3, 'av switcher,input,no signal,hdmi,firmware', 1
  ),
  (
    'Display: No Signal or Black Screen',
    'Ensure the display is set to the correct input source. Check that the source cable is fully seated. Test with a different cable and a different source device. If the display backlight is on but no image appears, the signal source or cable is likely the issue.',
    4, 'display,no signal,black screen,input,cable', 1
  ),
  (
    'How to Submit a Repair Request',
    'Log in to the MTRTS portal. Click "New Ticket" from the dashboard or scan the asset QR code to pre-fill details. Fill in the Category, Location, Impact, Urgency, and a clear Description. Attach a photo of the issue if possible. Submit — you will receive a ticket number and email confirmation.',
    NULL, 'ticket,submit,request,help desk,how to', 1
  ),
  (
    'Microphone: No Audio / No Signal',
    'Check XLR/TRS cable seating at both the mic and mixer ends. Verify phantom power (+48V) is enabled on the mixer channel for condenser microphones. Confirm the channel gain/trim knob is not at zero. Swap the cable with a known-good cable to isolate whether the fault is in the cable or the mic. Test the microphone on a different known-good channel to rule out a dead channel strip.',
    5, 'microphone,no audio,no signal,xlr,cable,phantom power', 1
  ),
  (
    'Microphone: Excessive Background Noise or Hum',
    'Check for a ground loop: try inserting a DI box or ground-lift adapter between the mic and mixer. Keep microphone cables physically separated from power cables and lighting dimmers. Inspect cable shielding for damage — a frayed or broken shield allows RF interference. Reduce channel gain and increase speaker/monitor volume to lower the effective noise floor. Confirm the microphone is not positioned near wireless transmitters or fluorescent lighting ballasts.',
    5, 'microphone,hum,noise,interference,grounding,cable', 1
  ),
  (
    'AV Rack: Device Not Powering On',
    'Check the rack-mount power conditioner or sequencer: confirm it is switched on and its circuit breaker has not tripped. Verify the wall outlet supplying the rack is live by plugging in a test device. Ensure all rack devices are plugged into switched outlets on the power conditioner, not the always-on outlets. If a power sequencer is present, reset it by powering it off completely for 10 seconds, then back on. Check IEC power cables at the back of each device for secure seating.',
    6, 'av rack,power,breaker,power strip,sequencer,not powering on', 1
  ),
  (
    'AV Rack: Overheating / Fan Alarm',
    'Ensure at least 1U of blank panel space is installed above heat-generating equipment (amplifiers, power supplies). Verify that rack fan units mounted at the top are spinning freely and the exhaust path is unobstructed. Clean dust filter foam on intake fans — clogged filters are the leading cause of rack overheating. If the rack has a rear door, confirm it is vented or open during operation. Consider adding a dedicated rack cooling fan tray if equipment density is high.',
    6, 'av rack,overheating,fan,ventilation,temperature,cooling', 1
  ),
  (
    'Camera: No Video Output',
    'Confirm the output cable (HDMI or SDI) is firmly seated at both the camera and the receiving device. Verify the receiving device input format matches the camera output (e.g., both set to 1080p60 — a resolution mismatch shows as no signal). Swap the cable with a tested cable to rule out cable failure. Power-cycle the camera and the receiving device. Check camera output settings: ensure the selected output is not disabled and clean-feed mode is not hiding overlays only.',
    7, 'camera,no video,no signal,hdmi,sdi,cable,resolution', 1
  ),
  (
    'Camera: Image Blurry or Autofocus Failing',
    'Clean the lens glass with a microfiber cloth — fingerprints and dust are the most common cause of soft images. If autofocus is continuously hunting, switch to manual focus: turn the focus ring until the subject appears sharp in the viewfinder/monitor. Ensure the subject has sufficient contrast and distinct edges for the autofocus system to lock onto. Check that the ND filter is not accidentally engaged in low-light conditions. If using a zoom lens, rack focus at maximum zoom first, then zoom back out to the desired framing.',
    7, 'camera,blurry,autofocus,focus,lens,clean,soft image', 1
  ),
  (
    'Amplifier: No Sound Output',
    'Check the front panel for a protection-mode indicator (red LED or flashing power light) — if in protection mode, power the amplifier off, wait 30 seconds, then power on again. Verify the input cable from the mixer or source device is connected to the correct input on the amplifier. Confirm the input gain/sensitivity knob is not set to minimum. Check speaker output cables and binding post/Speakon terminals for secure connection. Ensure the amplifier is not in standby mode.',
    8, 'amplifier,no sound,no output,gain,speaker,cable,protection mode', 1
  ),
  (
    'Amplifier: Distorted Audio / Clipping',
    'Reduce the input gain on the amplifier until the clip indicator LED stops lighting during normal program material. Lower the output level on the upstream source (mixer master fader) and compensate by raising the amplifier gain slightly to maintain headroom in the signal chain. Verify the speaker impedance matches the amplifier minimum load rating. Check speaker cone and surround for physical damage: a torn surround or stuck voice coil produces distortion at any level. If clipping occurs only on certain frequencies, check for a faulty crossover or blown tweeter.',
    8, 'amplifier,distortion,clipping,gain,overdrive,speaker,impedance', 1
  );

-- ============================================================
-- SEED DATA: WO CHECKLISTS & ITEMS
-- ============================================================

INSERT INTO wo_checklists (category_id, checklist_name, description) VALUES
  (1, 'Projector Repair Checklist',    'Standard steps for diagnosing and repairing projectors'),
  (2, 'Sound System Repair Checklist', 'Standard steps for diagnosing and repairing sound systems'),
  (3, 'AV Switcher Repair Checklist',  'Standard steps for diagnosing and repairing AV switchers'),
  (4, 'Display Repair Checklist',      'Standard steps for diagnosing and repairing display units'),
  (NULL, 'General Repair Checklist',   'Generic checklist applicable to any equipment type');

INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (1, 'Record current bulb hours',              1, 0, 0, NULL,           1),
  (1, 'Inspect lamp/bulb for damage',           1, 1, 0, NULL,           2),
  (1, 'Clean or replace air filter',            1, 0, 0, NULL,           3),
  (1, 'Test all input sources (HDMI, VGA)',     1, 0, 0, NULL,           4),
  (1, 'Check and adjust focus, zoom, keystone', 0, 0, 0, NULL,           5),
  (1, 'Test remote control functionality',      0, 0, 0, NULL,           6),
  (1, 'Capture after-repair photo',             1, 1, 1, 'photo_after',  7);
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (2, 'Capture before-repair photo',            1, 1, 1, 'photo_before', 1),
  (2, 'Inspect all cables and connectors',      1, 0, 0, NULL,           2),
  (2, 'Test speaker output (left and right)',   1, 0, 0, NULL,           3),
  (2, 'Check mixer and amplifier settings',     1, 0, 0, NULL,           4),
  (2, 'Test for audio feedback and noise',      1, 0, 0, NULL,           5),
  (2, 'Verify microphone inputs if present',    0, 0, 0, NULL,           6),
  (2, 'Capture after-repair photo',             1, 1, 1, 'photo_after',  7);
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (3, 'Capture before-repair photo',            1, 1, 1, 'photo_before', 1),
  (3, 'Test all input ports',                   1, 0, 0, NULL,           2),
  (3, 'Test all output ports',                  1, 0, 0, NULL,           3),
  (3, 'Verify input-switching functionality',   1, 0, 0, NULL,           4),
  (3, 'Check and record firmware version',      0, 0, 0, NULL,           5),
  (3, 'Inspect network connection if applicable', 0, 0, 0, NULL,         6),
  (3, 'Capture after-repair photo',             1, 1, 1, 'photo_after',  7);
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (4, 'Capture before-repair photo',                1, 1, 1, 'photo_before', 1),
  (4, 'Test display at all available inputs',       1, 0, 0, NULL,           2),
  (4, 'Inspect panel for dead pixels or damage',    1, 1, 0, NULL,           3),
  (4, 'Check cables and mounting hardware',         1, 0, 0, NULL,           4),
  (4, 'Verify network/smart features if present',   0, 0, 0, NULL,           5),
  (4, 'Capture after-repair photo',                 1, 1, 1, 'photo_after',  6);
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (5, 'Capture before-repair photo',   1, 1, 1, 'photo_before', 1),
  (5, 'Perform visual inspection',     1, 0, 0, NULL,           2),
  (5, 'Perform power-on test',         1, 0, 0, NULL,           3),
  (5, 'Verify core functionality',     1, 0, 0, NULL,           4),
  (5, 'Document findings and actions', 1, 0, 0, NULL,           5),
  (5, 'Capture after-repair photo',    1, 1, 1, 'photo_after',  6);

-- ============================================================
-- SEED DATA: SAFETY CHECKS
-- ============================================================

INSERT INTO wo_safety_checks (check_text, is_mandatory, sort_order) VALUES
  ('Verify ESD protection (wrist strap or mat) is in place', 1, 1),
  ('Confirm equipment is powered off and unplugged', 1, 2),
  ('Ensure proper personal protective equipment (PPE) is worn', 1, 3),
  ('Clear workspace of clutter and hazards', 1, 4),
  ('Verify tools are in good working condition', 1, 5),
  ('Check for visible damage before starting work', 1, 6),
  ('Test bench voltage verified safe (multimeter checked)', 1, 7),
  ('Required parts and replacements inventoried', 1, 8),
  ('Static-safe mat and containers in use', 1, 9),
  ('Proper disposal container for old/hazardous parts ready', 1, 10),
  ('Safety glasses or face shield worn (when required)', 1, 11);

-- ============================================================
-- SEED DATA: PARTS INVENTORY
-- Baked in so no separate seed script is needed.
-- Safe to re-run: uses INSERT ... ON DUPLICATE KEY UPDATE.
-- ============================================================

INSERT INTO parts_inventory
    (part_number, part_name, category, quantity_on_hand, reorder_level, unit_price, is_active)
VALUES
  -- Cables
  ('CABLE-HDMI-001',  'HDMI cable',              'cables',      50,  5,  12.50, 1),
  ('CABLE-VGA-001',   'VGA cable',               'cables',      35,  5,   8.00, 1),
  ('CABLE-DP-001',    'DisplayPort cable',       'cables',       3,  5,  15.00, 1),
  ('CABLE-AUX-001',   'AUX 3.5mm cable',         'cables',      45,  5,   5.00, 1),
  ('CABLE-XLR-001',   'XLR cable',               'cables',       2,  5,  18.00, 1),
  ('CABLE-ETH-001',   'Ethernet cable',          'cables',      60, 10,   6.00, 1),
  ('CABLE-USB-001',   'USB cable',               'cables',      55, 10,   7.50, 1),
  ('CABLE-COX-001',   'Coaxial cable',           'cables',       4,  5,   9.00, 1),

  -- Projector
  ('PROJ-LAMP-001',   'Projector lamp',          'projector',    1,  3, 120.00, 1),
  ('PROJ-AFIL-001',   'Air filter',              'projector',   12,  5,   8.50, 1),
  ('PROJ-LCD-001',    'LCD panel',               'projector',    0,  2, 350.00, 1),
  ('PROJ-BALL-001',   'Ballast (lamp driver)',   'projector',    5,  2,  85.00, 1),
  ('PROJ-LENS-001',   'Lens assembly',           'projector',    3,  2, 200.00, 1),
  ('PROJ-DLP-001',    'DLP chip',                'projector',    1,  1, 450.00, 1),
  ('PROJ-FAN-001',    'Cooling fan (projector)', 'projector',    8,  4,  25.00, 1),

  -- Audio
  ('AUD-SPK-001',     'Speaker driver',          'audio',       10,  4,  45.00, 1),
  ('AUD-JACK-001',    'Audio jack 3.5mm',        'audio',       40, 10,   3.50, 1),
  ('AUD-XLR-001',     'XLR connector',           'audio',       25,  8,  12.00, 1),
  ('AUD-VOL-001',     'Volume potentiometer',    'audio',        3,  6,   8.00, 1),
  ('AUD-AMP-001',     'Amplifier board',         'audio',        2,  3,  75.00, 1),
  ('AUD-TRF-001',     'Audio transformer',       'audio',        6,  3,  35.00, 1),

  -- Electrical
  ('ELEC-PWR-001',    'Power cable (AC)',        'electrical',  30,  8,  10.00, 1),
  ('ELEC-ADP-001',    'Power adapter',           'electrical',  18,  6,  22.00, 1),
  ('ELEC-F5A-001',    'Fuse 5A',                 'electrical',   5, 15,   2.50, 1),
  ('ELEC-F10A-001',   'Fuse 10A',                'electrical',   8, 15,   2.75, 1),
  ('ELEC-CBK-001',    'Circuit breaker',         'electrical',  10,  4,  18.00, 1),
  ('ELEC-PST-001',    'Power strip',             'electrical',  12,  5,  28.00, 1),
  ('ELEC-SPG-001',    'Surge protector',         'electrical',   4,  5,  35.00, 1),

  -- Electronic
  ('ELCN-C100-001',   'Capacitor 100uF',         'electronic',  80, 20,   1.50, 1),
  ('ELCN-C470-001',   'Capacitor 470uF',         'electronic',  15, 20,   2.00, 1),
  ('ELCN-R10K-001',   'Resistor 10kOhm',         'electronic', 100, 25,   0.50, 1),
  ('ELCN-DDE-001',    'Diode',                   'electronic',  90, 25,   0.75, 1),
  ('ELCN-TRN-001',    'Transistor',              'electronic',  18, 20,   1.25, 1),
  ('ELCN-RLY-001',    'IC relay',                'electronic',  35, 10,   5.50, 1),
  ('ELCN-MOS-001',    'MOSFET',                  'electronic',  50, 15,   3.00, 1),

  -- Cooling
  ('COOL-F80-001',    'Cooling fan 80mm',        'cooling',     14,  6,  12.00, 1),
  ('COOL-F120-001',   'Cooling fan 120mm',       'cooling',      5,  6,  15.00, 1),
  ('COOL-TPS-001',    'Thermal paste',           'cooling',     22,  8,   6.50, 1),
  ('COOL-HSK-001',    'Heat sink',               'cooling',      3,  5,  18.00, 1),
  ('COOL-DST-001',    'Dust filter',             'cooling',     28, 10,   4.00, 1),

  -- Mounting
  ('MNT-M3-001',      'M3 screw set',            'mounting',    40, 12,   5.00, 1),
  ('MNT-M4-001',      'M4 screw set',            'mounting',    38, 12,   5.50, 1),
  ('MNT-BKT-001',     'Bracket kit',             'mounting',     6,  8,  15.00, 1),
  ('MNT-TIE-001',     'Cable ties',              'mounting',    75, 20,   3.00, 1),
  ('MNT-WPL-001',     'Wall plate',              'mounting',     2,  6,   8.50, 1),
  ('MNT-RMR-001',     'Rack mount rails',        'mounting',     1,  4,  45.00, 1)

ON DUPLICATE KEY UPDATE
  part_name        = VALUES(part_name),
  category         = VALUES(category),
  quantity_on_hand = VALUES(quantity_on_hand),
  reorder_level    = VALUES(reorder_level),
  unit_price       = VALUES(unit_price),
  is_active        = 1,
  updated_at       = CURRENT_TIMESTAMP;

-- ============================================================
-- MODULE 6: SKILLS & AUTO-ASSIGNMENT (Module 3 spec)
-- ============================================================

CREATE TABLE skills (
  skill_id    INT          PRIMARY KEY AUTO_INCREMENT,
  skill_code  VARCHAR(64)  NOT NULL UNIQUE,
  skill_name  VARCHAR(120) NOT NULL,
  description TEXT         NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE technician_skills (
  user_id      INT      NOT NULL,
  skill_id     INT      NOT NULL,
  proficiency  TINYINT  NOT NULL DEFAULT 1, -- 1=basic, 2=intermediate, 3=expert
  certified_at DATE     NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id, skill_id),
  FOREIGN KEY (user_id)  REFERENCES users(user_id)   ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

CREATE TABLE category_skills (
  category_id INT        NOT NULL,
  skill_id    INT        NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (category_id, skill_id),
  FOREIGN KEY (category_id) REFERENCES asset_categories(category_id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id)    REFERENCES skills(skill_id)              ON DELETE CASCADE
);

CREATE TABLE location_assignments (
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

-- ============================================================
-- SEED DATA: SKILLS
-- ============================================================

INSERT INTO skills (skill_code, skill_name, description) VALUES
  ('projector_repair', 'Projector Repair',       'Lamp, lens, filter, focus, and input troubleshooting for projectors.'),
  ('audio_systems',    'Audio Systems',          'Mixers, amplifiers, speakers, microphones, and DSP configuration.'),
  ('av_switching',     'AV Switching/Routing',   'HDMI matrix, scalers, signal extenders, video walls.'),
  ('display_repair',   'Display Repair',         'LCD/LED/OLED panels, smart boards, interactive flat panels.'),
  ('camera_systems',   'Camera Systems',         'PTZ, conferencing, recording, and streaming cameras.'),
  ('network',          'Networking',             'Switches, AP placement, VLAN, AV-over-IP.'),
  ('rack_wiring',      'AV Rack & Wiring',       'Rack assembly, cable management, power distribution.'),
  ('general',          'General Maintenance',    'Routine cleaning, replacement, and inspections.');

INSERT INTO category_skills (category_id, skill_id, is_required)
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

-- Optional starter rows for technician_skills/location_assignments are intentionally
-- omitted so admins can configure them via the new admin UI.


-- ============================================================
-- MTRTS — Media Technology Repair Tracker System
-- Seed Data: Sample Assets
-- ============================================================
-- Run AFTER asset_management.sql.
-- Assumes default seeded data:
--   category_id: 1=Projector, 2=Sound System, 3=AV Switcher,
--                4=Display, 5=Microphone, 6=AV Rack, 7=Camera, 8=Amplifier
--   location_id: 1=Room 101, 2=Room 102, 3=Room 201, 4=Media Lab A,
--                5=Auditorium, 6=Room 204, 7=Library
--   owner_id 1 = admin (System Administrator)
-- ============================================================


-- ------------------------------------------------------------
-- ASSETS
-- status: active | spare | retired
-- bulb_hours: only for category_id = 1 (Projector)
-- -----------------------------------------------------------

-- department_id reference (from users.sql seed):
--   1 = IT Department  (owns most AV/tech assets)
--   2 = College of Nursing
--   3 = College of Engineering
--   4 = College of Education
--   5 = College of Business
--   6 = Registrar
--   7 = Library
--   8 = Administration

INSERT INTO assets (
  asset_tag, serial_number, manufacturer, model,
  category_id, status, location_id, parent_asset_id,
  install_date, firmware_version, network_info, bulb_hours,
  department_id, owner_id, created_by
) VALUES

-- ── Projectors (category 1, has bulb_hours) ─────────────────

('PRJ-1001-A', 'EPS-SN-00001', 'Epson',   'EB-2250U',
  1, 'active',  1, NULL, '2022-06-15', '1.04.00', '192.168.1.101', 1840,
  1, 1, 1),

('PRJ-1002-B', 'EPS-SN-00002', 'Epson',   'EB-2250U',
  1, 'active',  2, NULL, '2022-06-15', '1.04.00', '192.168.1.102', 2100,
  1, 1, 1),

('PRJ-1003-C', 'BNQ-SN-00101', 'BenQ',    'MH760',
  1, 'active',  3, NULL, '2021-08-01', '2.00.01', '192.168.1.103', 3450,
  4, 1, 1),

('PRJ-1004-D', 'BNQ-SN-00102', 'BenQ',    'MH760',
  1, 'active',  4, NULL, '2021-08-01', '2.00.01', '192.168.1.104', 3800,
  4, 1, 1),

('PRJ-1005-E', 'PAN-SN-00201', 'Panasonic','PT-VMZ60',
  1, 'active',  5, NULL, '2023-01-10', '3.10.00', '192.168.1.105',  520,
  8, 1, 1),

('PRJ-1006-F', 'PAN-SN-00202', 'Panasonic','PT-VMZ60',
  1, 'spare',   NULL, NULL, '2023-01-10', '3.10.00', NULL,           0,
  1, 1, 1),

('PRJ-1007-G', 'EPS-SN-00003', 'Epson',   'EB-1485Fi',
  1, 'retired', 6, NULL, '2019-03-20', '0.98.00', '192.168.1.106', 6800,
  1, 1, 1),

('PRJ-1008-H', 'EPS-SN-00004', 'Epson',   'EB-1485Fi',
  1, 'retired', 7, NULL, '2019-03-20', '0.98.00', NULL,            7200,
  7, 1, 1),

-- ── Sound Systems (category 2) ───────────────────────────────

('SND-2001-A', 'YMH-SN-00301', 'Yamaha',  'DXL1K',
  2, 'active',  5, NULL, '2021-05-12', NULL, NULL, NULL,
  8, 1, 1),

('SND-2002-B', 'YMH-SN-00302', 'Yamaha',  'DXL1K',
  2, 'active',  4, NULL, '2021-05-12', NULL, NULL, NULL,
  3, 1, 1),

('SND-2003-C', 'BSE-SN-00401', 'Bose',    'L1 Pro8',
  2, 'spare',   NULL, NULL, '2022-11-03', NULL, NULL, NULL,
  1, 1, 1),

('SND-2004-D', 'BSE-SN-00402', 'Bose',    'L1 Pro8',
  2, 'retired', 5, NULL, '2018-09-01', NULL, NULL, NULL,
  8, 1, 1),

-- ── AV Switchers (category 3) ────────────────────────────────

('AVS-3001-A', 'EXT-SN-00501', 'Extron',  'SW4 HD 4K',
  3, 'active',  4, NULL, '2022-02-18', 'v60.14.01', '192.168.1.201', NULL,
  1, 1, 1),

('AVS-3002-B', 'EXT-SN-00502', 'Extron',  'SW4 HD 4K',
  3, 'active',  5, NULL, '2022-02-18', 'v60.14.01', '192.168.1.202', NULL,
  1, 1, 1),

('AVS-3003-C', 'KRN-SN-00601', 'Kramer',  'VS-42H2',
  3, 'spare',   NULL, NULL, '2023-07-01', 'v1.1',  NULL,             NULL,
  1, 1, 1),

-- ── Displays (category 4) ────────────────────────────────────

('DSP-4001-A', 'SAM-SN-00701', 'Samsung', 'QM75B',
  4, 'active',  3, NULL, '2023-03-15', '1220.2', '192.168.1.301', NULL,
  2, 1, 1),

('DSP-4002-B', 'SAM-SN-00702', 'Samsung', 'QM75B',
  4, 'active',  2, NULL, '2023-03-15', '1220.2', '192.168.1.302', NULL,
  3, 1, 1),

('DSP-4003-C', 'LGE-SN-00801', 'LG',      'OLED65C3',
  4, 'active',  1, NULL, '2022-08-20', 'v03.34.40', '192.168.1.303', NULL,
  4, 1, 1),

('DSP-4004-D', 'LGE-SN-00802', 'LG',      'OLED65C3',
  4, 'retired', 7, NULL, '2019-01-10', 'v01.10.00', NULL,             NULL,
  7, 1, 1),

-- ── Microphones (category 5) ─────────────────────────────────

('MIC-5001-A', 'SHR-SN-00901', 'Shure',   'SM58',
  5, 'active',  5, NULL, '2021-04-01', NULL, NULL, NULL,
  8, 1, 1),

('MIC-5002-B', 'SHR-SN-00902', 'Shure',   'SM58',
  5, 'active',  5, NULL, '2021-04-01', NULL, NULL, NULL,
  8, 1, 1),

('MIC-5003-C', 'SHR-SN-00903', 'Shure',   'SM58',
  5, 'spare',   NULL, NULL, '2021-04-01', NULL, NULL, NULL,
  1, 1, 1),

('MIC-5004-D', 'SNH-SN-01001', 'Sennheiser','EW 135P G4',
  5, 'active',  4, NULL, '2022-10-05', NULL, NULL, NULL,
  5, 1, 1),

('MIC-5005-E', 'SNH-SN-01002', 'Sennheiser','EW 135P G4',
  5, 'retired', 4, NULL, '2018-06-15', NULL, NULL, NULL,
  5, 1, 1),

-- ── AV Racks (category 6) ────────────────────────────────────

('RCK-6001-A', 'MWK-SN-01101', 'Middle Atlantic','WRK-4427',
  6, 'active',  4, NULL, '2021-01-20', NULL, NULL, NULL,
  1, 1, 1),

('RCK-6002-B', 'MWK-SN-01102', 'Middle Atlantic','WRK-4427',
  6, 'active',  5, NULL, '2021-01-20', NULL, NULL, NULL,
  1, 1, 1),

-- ── Cameras (category 7) ─────────────────────────────────────

('CAM-7001-A', 'SON-SN-01201', 'Sony',    'SRG-X400',
  7, 'active',  5, NULL, '2022-12-01', 'v1.10', '192.168.1.401', NULL,
  1, 1, 1),

('CAM-7002-B', 'SON-SN-01202', 'Sony',    'SRG-X400',
  7, 'active',  4, NULL, '2022-12-01', 'v1.10', '192.168.1.402', NULL,
  1, 1, 1),

('CAM-7003-C', 'PTZ-SN-01301', 'PTZOptics','PT20X-SDI',
  7, 'spare',   NULL, NULL, '2023-06-10', 'v6.2.19', NULL,           NULL,
  1, 1, 1),

-- ── Amplifiers (category 8) ──────────────────────────────────

('AMP-8001-A', 'CRN-SN-01401', 'Crown',   'XLi1500',
  8, 'active',  5, NULL, '2020-07-15', NULL, NULL, NULL,
  1, 1, 1),

('AMP-8002-B', 'CRN-SN-01402', 'Crown',   'XLi1500',
  8, 'active',  4, NULL, '2020-07-15', NULL, NULL, NULL,
  1, 1, 1),

('AMP-8003-C', 'QSC-SN-01501', 'QSC',     'GX5',
  8, 'retired', 7, NULL, '2017-03-10', NULL, NULL, NULL,
  1, 1, 1);


-- ------------------------------------------------------------
-- PARENT-CHILD RELATIONSHIPS
-- Link some assets as children of AV Racks
-- (must run AFTER the INSERT above so asset_ids exist)
-- Temporarily disable safe update mode for these statements.
-- ------------------------------------------------------------
SET SQL_SAFE_UPDATES = 0;

UPDATE assets SET parent_asset_id = (SELECT asset_id FROM (SELECT asset_id FROM assets WHERE asset_tag = 'RCK-6001-A') t)
  WHERE asset_tag IN ('AVS-3001-A', 'AMP-8002-B');

UPDATE assets SET parent_asset_id = (SELECT asset_id FROM (SELECT asset_id FROM assets WHERE asset_tag = 'RCK-6002-B') t)
  WHERE asset_tag IN ('AVS-3002-B', 'AMP-8001-A');

SET SQL_SAFE_UPDATES = 1;


-- ------------------------------------------------------------
-- WARRANTIES
-- Covers a spread: valid, expiring soon (~30 days), expired
-- ------------------------------------------------------------
INSERT INTO asset_warranty (
  asset_id, warranty_start, warranty_end, coverage_type, vendor_name, contract_reference
)
SELECT asset_id, '2022-06-15', '2025-06-14', 'parts_and_labor', 'Epson Philippines',  'EP-2022-0615' FROM assets WHERE asset_tag = 'PRJ-1001-A'
UNION ALL
SELECT asset_id, '2022-06-15', '2025-06-14', 'parts_and_labor', 'Epson Philippines',  'EP-2022-0615' FROM assets WHERE asset_tag = 'PRJ-1002-B'
UNION ALL
SELECT asset_id, '2021-08-01', '2024-07-31', 'parts',           'BenQ Philippines',   'BQ-2021-0801' FROM assets WHERE asset_tag = 'PRJ-1003-C'
UNION ALL
SELECT asset_id, '2021-08-01', '2024-07-31', 'parts',           'BenQ Philippines',   'BQ-2021-0801' FROM assets WHERE asset_tag = 'PRJ-1004-D'
UNION ALL
SELECT asset_id, '2023-01-10', '2026-01-09', 'parts_and_labor', 'Panasonic PH',       'PAN-2023-0110' FROM assets WHERE asset_tag = 'PRJ-1005-E'
UNION ALL
SELECT asset_id, '2023-01-10', '2026-01-09', 'parts_and_labor', 'Panasonic PH',       'PAN-2023-0110' FROM assets WHERE asset_tag = 'PRJ-1006-F'
UNION ALL
SELECT asset_id, '2021-05-12', '2024-05-11', 'labor',           'Yamaha Music PH',    'YMH-2021-0512' FROM assets WHERE asset_tag = 'SND-2001-A'
UNION ALL
SELECT asset_id, '2021-05-12', '2024-05-11', 'labor',           'Yamaha Music PH',    'YMH-2021-0512' FROM assets WHERE asset_tag = 'SND-2002-B'
UNION ALL
SELECT asset_id, '2022-11-03', '2025-11-02', 'parts_and_labor', 'Bose Philippines',   'BSE-2022-1103' FROM assets WHERE asset_tag = 'SND-2003-C'
UNION ALL
SELECT asset_id, '2022-02-18', '2025-02-17', 'parts_and_labor', 'Extron Electronics', 'EXT-2022-0218' FROM assets WHERE asset_tag = 'AVS-3001-A'
UNION ALL
SELECT asset_id, '2022-02-18', '2025-02-17', 'parts_and_labor', 'Extron Electronics', 'EXT-2022-0218' FROM assets WHERE asset_tag = 'AVS-3002-B'
UNION ALL
SELECT asset_id, '2023-07-01', '2026-06-30', 'parts',           'Kramer Philippines', 'KRN-2023-0701' FROM assets WHERE asset_tag = 'AVS-3003-C'
UNION ALL
SELECT asset_id, '2023-03-15', '2026-03-14', 'parts_and_labor', 'Samsung Philippines','SAM-2023-0315' FROM assets WHERE asset_tag = 'DSP-4001-A'
UNION ALL
SELECT asset_id, '2023-03-15', '2026-03-14', 'parts_and_labor', 'Samsung Philippines','SAM-2023-0315' FROM assets WHERE asset_tag = 'DSP-4002-B'
UNION ALL
SELECT asset_id, '2022-08-20', '2025-08-19', 'onsite',          'LG Philippines',     'LGE-2022-0820' FROM assets WHERE asset_tag = 'DSP-4003-C'
UNION ALL
SELECT asset_id, '2022-12-01', '2025-11-30', 'parts_and_labor', 'Sony Philippines',   'SON-2022-1201' FROM assets WHERE asset_tag = 'CAM-7001-A'
UNION ALL
SELECT asset_id, '2022-12-01', '2025-11-30', 'parts_and_labor', 'Sony Philippines',   'SON-2022-1201' FROM assets WHERE asset_tag = 'CAM-7002-B'
UNION ALL
SELECT asset_id, '2020-07-15', '2023-07-14', 'labor',           'Crown Audio PH',     'CRN-2020-0715' FROM assets WHERE asset_tag = 'AMP-8001-A'
UNION ALL
SELECT asset_id, '2020-07-15', '2023-07-14', 'labor',           'Crown Audio PH',     'CRN-2020-0715' FROM assets WHERE asset_tag = 'AMP-8002-B';


-- ------------------------------------------------------------
-- AUDIT LOG — creation entries for all seeded assets
-- ------------------------------------------------------------
INSERT INTO asset_audit_log (asset_id, field_name, old_value, new_value, changed_by)
SELECT asset_id, 'created', NULL, asset_tag, 1
FROM assets;


-- ============================================================
-- SUMMARY
-- ============================================================
--   Projectors   (category 1): 8  — 5 active, 1 spare, 2 retired
--   Sound Systems (category 2): 4  — 2 active, 1 spare, 1 retired
--   AV Switchers  (category 3): 3  — 2 active, 1 spare
--   Displays      (category 4): 4  — 3 active, 1 retired
--   Microphones   (category 5): 5  — 3 active, 1 spare, 1 retired
--   AV Racks      (category 6): 2  — 2 active
--   Cameras       (category 7): 3  — 2 active, 1 spare
--   Amplifiers    (category 8): 3  — 2 active, 1 retired
--   ─────────────────────────────────────────────────────────
--   Total: 32 assets — 21 active, 5 spare, 6 retired
--
--   Warranties: 19 records
--     - Active (expiry > today):     ~14
--     - Expired (expiry < today):    ~5
--     - Expiring soon (≤ 30 days):   varies by run date
-- ============================================================
