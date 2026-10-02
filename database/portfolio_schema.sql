-- =====================================================================
-- Student Portfolio Database Schema
-- Engine: MySQL / MariaDB (XAMPP + phpMyAdmin compatible)
-- Import: phpMyAdmin > Import > choose this file
-- =====================================================================

 
-- ---------------------------------------------------------------------
-- 1. ADMIN USERS  (login for your private dashboard)
-- ---------------------------------------------------------------------
CREATE TABLE admin_users (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username       VARCHAR(50)  NOT NULL,
  email          VARCHAR(150) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,          -- use password_hash() in PHP
  last_login_at  DATETIME     NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_username (username),
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. PROFILE  (home + about page content; normally a single row)
-- ---------------------------------------------------------------------
CREATE TABLE profile (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name       VARCHAR(60)  NOT NULL,
  last_name        VARCHAR(60)  NOT NULL,
  headline         VARCHAR(150) NOT NULL,         -- e.g. "BCA Student & Web Developer"
  short_intro      VARCHAR(300) NULL,             -- home page hero text
  about_me         TEXT         NULL,             -- about page long text
  photo_path       VARCHAR(255) NULL,
  resume_path      VARCHAR(255) NULL,
  email            VARCHAR(150) NULL,
  phone            VARCHAR(20)  NULL,
  city             VARCHAR(80)  NULL,
  country          VARCHAR(80)  NULL,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. SOCIAL LINKS  (GitHub, LinkedIn, etc.)
-- ---------------------------------------------------------------------
CREATE TABLE social_links (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  platform    VARCHAR(50)  NOT NULL,              -- GitHub, LinkedIn, Instagram...
  url         VARCHAR(255) NOT NULL,
  icon_class  VARCHAR(60)  NULL,
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. EDUCATION  (about page timeline)
-- ---------------------------------------------------------------------
CREATE TABLE education (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  institution   VARCHAR(150) NOT NULL,
  degree        VARCHAR(150) NOT NULL,
  field_of_study VARCHAR(150) NULL,
  start_year    YEAR         NOT NULL,
  end_year      YEAR         NULL,                -- NULL = currently studying
  description   TEXT         NULL,
  sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. SKILLS  (about page)
-- ---------------------------------------------------------------------
CREATE TABLE skills (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(60)  NOT NULL,
  category     VARCHAR(60)  NOT NULL DEFAULT 'General',  -- Frontend, Backend, Database, Tools
  proficiency  TINYINT UNSIGNED NOT NULL DEFAULT 50,     -- 0-100 percent
  sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_skill_name (name),
  CONSTRAINT chk_skill_prof CHECK (proficiency BETWEEN 0 AND 100)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. PROJECTS  (my work page)
-- ---------------------------------------------------------------------
CREATE TABLE projects (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title         VARCHAR(150) NOT NULL,
  slug          VARCHAR(160) NOT NULL,            -- URL friendly: my-todo-app
  summary       VARCHAR(300) NOT NULL,            -- card text
  description   TEXT         NULL,                -- detail page
  thumbnail     VARCHAR(255) NULL,
  live_url      VARCHAR(255) NULL,
  github_url    VARCHAR(255) NULL,
  status        ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'completed',
  is_featured   TINYINT(1)   NOT NULL DEFAULT 0,  -- show on home page
  is_published  TINYINT(1)   NOT NULL DEFAULT 1,
  started_on    DATE         NULL,
  completed_on  DATE         NULL,
  view_count    INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_project_slug (slug),
  KEY idx_project_listing (is_published, is_featured, completed_on)
) ENGINE=InnoDB;

-- Extra screenshots per project
CREATE TABLE project_images (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id  INT UNSIGNED NOT NULL,
  image_path  VARCHAR(255) NOT NULL,
  caption     VARCHAR(150) NULL,
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_pi_project (project_id),
  CONSTRAINT fk_pi_project FOREIGN KEY (project_id)
    REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Many-to-many: which skills/technologies each project used
CREATE TABLE project_skills (
  project_id  INT UNSIGNED NOT NULL,
  skill_id    INT UNSIGNED NOT NULL,
  PRIMARY KEY (project_id, skill_id),
  KEY idx_ps_skill (skill_id),
  CONSTRAINT fk_ps_project FOREIGN KEY (project_id)
    REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT fk_ps_skill FOREIGN KEY (skill_id)
    REFERENCES skills (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. CONTACT MESSAGES  (contact page form)
--    Fields: name, surname, year, field, country, city, email, mobile, message
-- ---------------------------------------------------------------------
CREATE TABLE contact_messages (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name    VARCHAR(60)  NOT NULL,
  surname       VARCHAR(60)  NOT NULL,
  study_year    VARCHAR(30)  NOT NULL,            -- e.g. "1st Year", "Final Year", "2026"
  field         VARCHAR(100) NOT NULL,            -- e.g. "Computer Science"
  country       VARCHAR(80)  NOT NULL,
  city          VARCHAR(80)  NOT NULL,
  email         VARCHAR(150) NOT NULL,
  mobile        VARCHAR(20)  NOT NULL,            -- store with country code, e.g. +919876543210
  message       TEXT         NOT NULL,
  status        ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
  ip_address    VARCHAR(45)  NULL,                -- fits IPv6; useful for spam control
  submitted_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cm_status_date (status, submitted_at),
  KEY idx_cm_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. DASHBOARD / ACTIVITY TRACKING
-- ---------------------------------------------------------------------

-- 8a. One row per page visit (feeds visitor charts)
CREATE TABLE page_visits (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  page        VARCHAR(60)  NOT NULL,              -- home, about, work, contact, project
  project_id  INT UNSIGNED NULL,                  -- set when a project page is viewed
  session_id  VARCHAR(64)  NULL,                  -- distinguishes unique visitors
  ip_address  VARCHAR(45)  NULL,
  country     VARCHAR(80)  NULL,
  referrer    VARCHAR(255) NULL,
  user_agent  VARCHAR(255) NULL,
  visited_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pv_page_date (page, visited_at),
  KEY idx_pv_date (visited_at),
  KEY idx_pv_project (project_id),
  CONSTRAINT fk_pv_project FOREIGN KEY (project_id)
    REFERENCES projects (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8b. Your own work log (what you built/learned/studied, hours spent)
CREATE TABLE activity_log (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  activity_date DATE         NOT NULL,
  title         VARCHAR(150) NOT NULL,
  category      ENUM('project','learning','course','practice','other') NOT NULL DEFAULT 'project',
  project_id    INT UNSIGNED NULL,
  hours_spent   DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  notes         TEXT         NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_al_date (activity_date),
  KEY idx_al_category (category),
  KEY idx_al_project (project_id),
  CONSTRAINT fk_al_project FOREIGN KEY (project_id)
    REFERENCES projects (id) ON DELETE SET NULL,
  CONSTRAINT chk_al_hours CHECK (hours_spent >= 0 AND hours_spent <= 24)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. SAMPLE DATA (optional; delete if not needed)
-- ---------------------------------------------------------------------
INSERT INTO profile (first_name, last_name, headline, short_intro, about_me, city, country)
VALUES ('Pratik', 'Lakhatariya', 'Student & Web Developer',
        'Welcome to my portfolio.', 'Write about yourself here.', 'Ahmedabad', 'India');

INSERT INTO skills (name, category, proficiency, sort_order) VALUES
  ('HTML', 'Frontend', 90, 1),
  ('CSS', 'Frontend', 85, 2),
  ('JavaScript', 'Frontend', 70, 3),
  ('PHP', 'Backend', 75, 4),
  ('MySQL', 'Database', 75, 5);

-- Create the admin login from PHP with password_hash(), not as plain text.

-- =====================================================================
-- Useful dashboard queries
-- =====================================================================
-- Visits per day (last 30 days):
--   SELECT DATE(visited_at) d, COUNT(*) total, COUNT(DISTINCT session_id) uniq
--   FROM page_visits WHERE visited_at >= NOW() - INTERVAL 30 DAY GROUP BY d ORDER BY d;
--
-- Most viewed projects:
--   SELECT p.title, COUNT(*) views FROM page_visits v
--   JOIN projects p ON p.id = v.project_id GROUP BY p.id ORDER BY views DESC LIMIT 5;
--
-- Hours worked per category this month:
--   SELECT category, SUM(hours_spent) hrs FROM activity_log
--   WHERE activity_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') GROUP BY category;
--
-- Unread messages:
--   SELECT COUNT(*) FROM contact_messages WHERE status = 'new';
