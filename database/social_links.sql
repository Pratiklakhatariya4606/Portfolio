-- Run in phpMyAdmin (SQL tab) with portfolio_db selected.
-- Replace each URL with YOUR profile link, then click Go.
USE portfolio_db;
DELETE FROM social_links;
INSERT INTO social_links (platform, url, sort_order, is_active) VALUES
  ('Instagram', 'https://www.instagram.com/pratikprajapati.4606',  1, 1),
  ('Facebook',  'https://www.facebook.com/YOUR_USERNAME',    2, 1),
  ('GitHub',    'https://github.com/YOUR_USERNAME',          3, 1),
  ('LinkedIn',  'https://www.linkedin.com/in/YOUR_USERNAME/', 4, 1);
