-- English / Arabic versions of content entered in Tamil (policy, pages, settings, any text).
-- The site looks a text up by the SHA-1 of its exact Tamil wording.
CREATE TABLE IF NOT EXISTS translations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lang CHAR(2) NOT NULL,
  source TEXT NOT NULL,
  source_hash CHAR(40) AS (SHA1(source)) STORED,
  value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_lang_source (lang, source_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
