-- Spill Toko - skema MySQL / MariaDB (utf8mb4)
-- Semua waktu disimpan dalam UTC.

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  google_sub    VARCHAR(64)  NOT NULL,
  email         VARCHAR(190) NOT NULL,
  name          VARCHAR(190) NULL,
  picture       VARCHAR(500) NULL,
  trial_ends_at DATETIME     NOT NULL,
  paid_until    DATETIME     NULL,
  lifetime      TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL,
  last_login_at DATETIME     NULL,
  UNIQUE KEY uq_google_sub (google_sub),
  KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id         VARCHAR(64)  NOT NULL,
  user_id          INT UNSIGNED NOT NULL,
  plan             VARCHAR(20)  NOT NULL,           -- monthly | lifetime
  amount           INT UNSIGNED NOT NULL,           -- Rupiah
  status           VARCHAR(20)  NOT NULL DEFAULT 'pending',   -- pending | paid | expired | cancelled | denied
  snap_token       VARCHAR(100) NULL,
  payment_type     VARCHAR(40)  NULL,
  raw_notification TEXT         NULL,
  created_at       DATETIME     NOT NULL,
  paid_at          DATETIME     NULL,
  UNIQUE KEY uq_order_id (order_id),
  KEY idx_user (user_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_log (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  action     VARCHAR(40)  NOT NULL,
  note       VARCHAR(255) NULL,
  created_at DATETIME     NOT NULL,
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Tambahan v2: pengaturan, AI =====
CREATE TABLE IF NOT EXISTS settings (
  k          VARCHAR(80) NOT NULL PRIMARY KEY,
  v          MEDIUMTEXT  NULL,
  is_secret  TINYINT(1)  NOT NULL DEFAULT 0,
  updated_at DATETIME    NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_log (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  kind       VARCHAR(12)  NOT NULL,
  cache_hit  TINYINT(1)   NOT NULL DEFAULT 0,
  model      VARCHAR(80)  NULL,
  tokens_in  INT UNSIGNED NOT NULL DEFAULT 0,
  tokens_out INT UNSIGNED NOT NULL DEFAULT 0,
  ms         INT UNSIGNED NOT NULL DEFAULT 0,
  status     VARCHAR(12)  NOT NULL DEFAULT 'ok',
  error      VARCHAR(255) NULL,
  created_at DATETIME     NOT NULL,
  KEY idx_user_time (user_id, created_at),
  KEY idx_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_cache (
  hash       CHAR(64)    NOT NULL PRIMARY KEY,
  kind       VARCHAR(12) NOT NULL,
  model      VARCHAR(80) NULL,
  result     MEDIUMTEXT  NOT NULL,
  created_at DATETIME    NOT NULL,
  hits       INT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
