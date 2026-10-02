-- UPGRADE v2. Run ONCE, after portfolio_schema.sql (phpMyAdmin > SQL tab). Built for XAMPP/MariaDB.
USE portfolio_db;

CREATE TABLE IF NOT EXISTS visitors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(60) NOT NULL, surname VARCHAR(60) NOT NULL,
  age TINYINT UNSIGNED NOT NULL, field VARCHAR(100) NOT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY idx_v_created (created_at)
) ENGINE=InnoDB;

ALTER TABLE contact_messages
  ADD COLUMN visitor_id INT UNSIGNED NULL AFTER id,
  ADD COLUMN edited_at DATETIME NULL,
  ADD KEY idx_cm_visitor (visitor_id),
  ADD CONSTRAINT fk_cm_visitor FOREIGN KEY (visitor_id) REFERENCES visitors (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS message_replies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id INT UNSIGNED NOT NULL, admin_id INT UNSIGNED NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY idx_mr_message (message_id),
  CONSTRAINT fk_mr_message FOREIGN KEY (message_id) REFERENCES contact_messages (id) ON DELETE CASCADE,
  CONSTRAINT fk_mr_admin FOREIGN KEY (admin_id) REFERENCES admin_users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feedback (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  visitor_id INT UNSIGNED NULL,
  rating ENUM('like','dislike') NOT NULL,
  wants_changes TINYINT(1) NOT NULL DEFAULT 0,
  comments VARCHAR(1000) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_fb_visitor FOREIGN KEY (visitor_id) REFERENCES visitors (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL, success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY idx_la_ip (ip, attempted_at)
) ENGINE=InnoDB;

ALTER TABLE skills ADD COLUMN icon_class VARCHAR(60) NULL;

-- Owner/team login (password is stored as a hash). Add more rows for team members.
INSERT INTO admin_users (username, email, password_hash) VALUES ('owner', 'owner@example.com', '$6$AR9dHDG.XOr7yX8B$dXphhMz7rz7txjiP9Rpd1z/rY9kC2CUdG8nrPKksLq.UQEFTCg46seM6fGBOj6KB2wg8HFYoOgs8RNG2MO0.e1');

-- About / home text (edit later in the profile table)
UPDATE profile SET headline = 'IT Student & Full Stack Developer',
  short_intro = 'IT student and developer building secure, responsive full-stack web apps with PHP and MySQL.',
  about_me = 'I am an Information Technology student and a developer who enjoys turning ideas into working web applications. I build full-stack projects with PHP, MySQL, HTML, CSS and JavaScript, and I care about clean database design, secure code and responsive, user-friendly interfaces.\n\nI am always learning: exploring new tools, improving my problem-solving skills and building projects that solve real problems. Explore my work, then send me a message from the contact page. I would love to hear from you.'
  ORDER BY id LIMIT 1;

-- Language / technology logos
UPDATE skills SET icon_class = 'devicon-html5-plain colored' WHERE name = 'HTML';
UPDATE skills SET icon_class = 'devicon-css3-plain colored' WHERE name = 'CSS';
UPDATE skills SET icon_class = 'devicon-javascript-plain colored' WHERE name = 'JavaScript';
UPDATE skills SET icon_class = 'devicon-php-plain colored' WHERE name = 'PHP';
UPDATE skills SET icon_class = 'devicon-mysql-plain colored' WHERE name = 'MySQL';
INSERT IGNORE INTO skills (name, category, proficiency, sort_order, icon_class) VALUES ('Git', 'Tools', 60, 6, 'devicon-git-plain colored');

-- Starter projects (edit or replace with your real ones)
INSERT IGNORE INTO projects (title, slug, summary, description, status, is_featured, is_published, completed_on) VALUES
 ('Personal Portfolio Website', 'personal-portfolio', 'Responsive portfolio with visitor verification, inbox, sent box and analytics dashboard.', 'Full-stack PHP and MySQL portfolio with a human check, owner inbox, visitor messaging and a feedback flow.', 'completed', 1, 1, CURDATE()),
 ('Sample Project - edit me', 'sample-project-1', 'Replace this with one of your real projects.', 'Edit this row in the projects table.', 'in_progress', 1, 1, NULL);
INSERT IGNORE INTO project_skills (project_id, skill_id)
  SELECT p.id, s.id FROM projects p JOIN skills s ON s.name IN ('PHP','MySQL','HTML','CSS','JavaScript') WHERE p.slug = 'personal-portfolio';
INSERT IGNORE INTO project_skills (project_id, skill_id)
  SELECT p.id, s.id FROM projects p JOIN skills s ON s.name IN ('HTML','CSS') WHERE p.slug = 'sample-project-1';
