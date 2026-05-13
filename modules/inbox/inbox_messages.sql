-- ============================================================
-- Migration: inbox_messages table + role_modules access
-- Feature:   Technician-Customer Messaging
-- Requirements: 3.1, 3.7, 1.6
--
-- Idempotent: safe to run on an existing database.
-- Uses CREATE TABLE IF NOT EXISTS and INSERT IGNORE.
-- ============================================================

CREATE TABLE IF NOT EXISTS inbox_messages (
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
  INDEX idx_recipient (recipient_id),
  INDEX idx_sender    (sender_id),
  INDEX idx_sent_at   (sent_at),

  CONSTRAINT fk_im_sender
    FOREIGN KEY (sender_id)    REFERENCES users(user_id)        ON DELETE CASCADE,
  CONSTRAINT fk_im_recipient
    FOREIGN KEY (recipient_id) REFERENCES users(user_id)        ON DELETE CASCADE,
  CONSTRAINT fk_im_wo
    FOREIGN KEY (wo_id)        REFERENCES work_orders(wo_id)    ON DELETE SET NULL,
  CONSTRAINT fk_im_ticket
    FOREIGN KEY (ticket_id)    REFERENCES tickets(ticket_id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grant inbox module access to roles 4–7
-- (roles 1, 2, 3, 8 already have inbox in role_modules)
-- INSERT IGNORE ensures idempotency on repeated runs
INSERT IGNORE INTO role_modules (role_id, module_slug) VALUES
  (4, 'inbox'),
  (5, 'inbox'),
  (6, 'inbox'),
  (7, 'inbox');
