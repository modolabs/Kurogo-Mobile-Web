-- Create user for mitmobile
-- (username and pass will be set by install script)

GRANT ALL PRIVILEGES ON mysql_db.* TO 'mysql_user'@'localhost' IDENTIFIED BY 'mysql_pass' WITH GRANT OPTION;

-- This table is for counting page views to the site 
-- (used by page_builder/counter.php).
DROP TABLE IF EXISTS mobi_web_page_views;
CREATE TABLE mobi_web_page_views (
  day date,
  platform char(31) NOT NULL,
  module char(31) NOT NULL,
  viewcount int(6) NOT NULL
);
