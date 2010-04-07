-- This script initializes MySQL tables
-- used only by the Mobile Web
-- other tables are initialized in the lib directory.


-- This table is for counting page views to the site 
-- (used by page_builder/counter.php).
DROP TABLE IF EXISTS mobi_web_page_views;
CREATE TABLE mobi_web_page_views (
  day date,
  platform char(31) NOT NULL,
  module char(31) NOT NULL,
  viewcount int(6) NOT NULL
);

