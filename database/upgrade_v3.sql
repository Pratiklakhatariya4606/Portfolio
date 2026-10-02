-- UPGRADE v3. Run ONCE after upgrade_v2.sql (phpMyAdmin > SQL tab).
USE portfolio_db;
CREATE TABLE IF NOT EXISTS notification_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id INT UNSIGNED NULL,
  channel ENUM('email','sms') NOT NULL,
  to_addr VARCHAR(150) NOT NULL,
  subject VARCHAR(150) NULL,
  status ENUM('sent','failed','skipped') NOT NULL,
  error VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY idx_nl_message (message_id),
  CONSTRAINT fk_nl_message FOREIGN KEY (message_id) REFERENCES contact_messages (id) ON DELETE SET NULL
) ENGINE=InnoDB;
