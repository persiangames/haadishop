-- HaadiShop Initial Schema (Core v1)
-- MySQL 8.0, utf8mb4
-- Run: mysql -u root -p haadishop < schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- GENERAL LOOKUP TABLES
CREATE TABLE IF NOT EXISTS currencies (
  code VARCHAR(3) PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  symbol VARCHAR(8) NOT NULL,
  precision TINYINT NOT NULL DEFAULT 2,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exchange_rates (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  base_currency VARCHAR(3) NOT NULL,
  quote_currency VARCHAR(3) NOT NULL,
  rate DECIMAL(18,8) NOT NULL,
  fetched_at DATETIME NOT NULL,
  provider VARCHAR(64) NOT NULL,
  UNIQUE KEY ux_pair_time (base_currency, quote_currency, fetched_at),
  CONSTRAINT fk_rates_base FOREIGN KEY (base_currency) REFERENCES currencies(code),
  CONSTRAINT fk_rates_quote FOREIGN KEY (quote_currency) REFERENCES currencies(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- USERS & AUTH
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NULL,
  phone VARCHAR(32) NULL,
  email_verified_at DATETIME NULL,
  password VARCHAR(255) NOT NULL,
  remember_token VARCHAR(100) NULL,
  affiliate_code VARCHAR(32) UNIQUE,
  two_factor_secret TEXT NULL,
  two_factor_recovery_codes TEXT NULL,
  last_login_at DATETIME NULL,
  status ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
  loyalty_points_total BIGINT NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY ux_users_email (email),
  UNIQUE KEY ux_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_addresses (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(100) NULL,
  name VARCHAR(191) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  country VARCHAR(64) NOT NULL,
  province VARCHAR(64) NOT NULL,
  city VARCHAR(64) NOT NULL,
  address_line VARCHAR(255) NOT NULL,
  postal_code VARCHAR(32) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  CONSTRAINT fk_user_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ROLES & PERMISSIONS (simplified)
CREATE TABLE IF NOT EXISTS roles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_user (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_ru_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ru_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permission_role (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_pr_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_pr_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CATALOG
CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  parent_id BIGINT UNSIGNED NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS category_translations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  category_id BIGINT UNSIGNED NOT NULL,
  locale VARCHAR(10) NOT NULL,
  name VARCHAR(191) NOT NULL,
  description TEXT NULL,
  UNIQUE KEY ux_cat_loc (category_id, locale),
  CONSTRAINT fk_cat_tr_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS brands (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(191) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS brand_translations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  brand_id BIGINT UNSIGNED NOT NULL,
  locale VARCHAR(10) NOT NULL,
  name VARCHAR(191) NOT NULL,
  description TEXT NULL,
  UNIQUE KEY ux_brand_loc (brand_id, locale),
  CONSTRAINT fk_brand_tr_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  brand_id BIGINT UNSIGNED NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  sku VARCHAR(191) NULL UNIQUE,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  weight DECIMAL(10,3) NULL,
  width DECIMAL(10,3) NULL,
  height DECIMAL(10,3) NULL,
  length DECIMAL(10,3) NULL,
  tax_class_id BIGINT UNSIGNED NULL,
  warranty_months SMALLINT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_translations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL,
  locale VARCHAR(10) NOT NULL,
  title VARCHAR(255) NOT NULL,
  short_desc TEXT NULL,
  long_desc LONGTEXT NULL,
  meta_title VARCHAR(255) NULL,
  meta_desc VARCHAR(255) NULL,
  UNIQUE KEY ux_prod_loc (product_id, locale),
  CONSTRAINT fk_prod_tr_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_categories (
  product_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, category_id),
  CONSTRAINT fk_pc_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pc_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_variants (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL,
  sku VARCHAR(191) NOT NULL UNIQUE,
  option_values JSON NULL,
  barcode VARCHAR(64) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS variant_prices (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  product_variant_id BIGINT UNSIGNED NOT NULL,
  currency_code VARCHAR(3) NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  compare_at_amount DECIMAL(18,2) NULL,
  start_at DATETIME NULL,
  end_at DATETIME NULL,
  UNIQUE KEY ux_variant_currency_time (product_variant_id, currency_code, COALESCE(start_at,'1970-01-01 00:00:00'), COALESCE(end_at,'2999-12-31 23:59:59')),
  CONSTRAINT fk_prices_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
  CONSTRAINT fk_prices_currency FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventories (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  product_variant_id BIGINT UNSIGNED NOT NULL,
  quantity_on_hand INT NOT NULL DEFAULT 0,
  quantity_reserved INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 0,
  updated_at DATETIME NULL,
  CONSTRAINT fk_inv_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CART & ORDERS
CREATE TABLE IF NOT EXISTS carts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  session_id VARCHAR(100) NULL,
  currency_code VARCHAR(3) NOT NULL,
  locale VARCHAR(10) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  KEY idx_cart_user (user_id),
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_cart_currency FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cart_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  cart_id BIGINT UNSIGNED NOT NULL,
  product_variant_id BIGINT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(18,2) NOT NULL,
  line_total DECIMAL(18,2) NOT NULL,
  CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ci_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  order_number VARCHAR(32) NOT NULL UNIQUE,
  status ENUM('pending','paid','fulfilled','cancelled','refunded') NOT NULL DEFAULT 'pending',
  currency_code VARCHAR(3) NOT NULL,
  subtotal DECIMAL(18,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  tax_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  shipping_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  grand_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  paid_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  due_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  placed_at DATETIME NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_orders_currency FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  product_variant_id BIGINT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(18,2) NOT NULL,
  discount_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(18,2) NOT NULL,
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_addresses (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  type ENUM('billing','shipping') NOT NULL,
  name VARCHAR(191) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  country VARCHAR(64) NOT NULL,
  province VARCHAR(64) NOT NULL,
  city VARCHAR(64) NOT NULL,
  address_line VARCHAR(255) NOT NULL,
  postal_code VARCHAR(32) NOT NULL,
  CONSTRAINT fk_oa_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAYMENTS (simplified)
CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(50) NOT NULL,
  status ENUM('init','succeeded','failed','refunded') NOT NULL DEFAULT 'init',
  amount DECIMAL(18,2) NOT NULL,
  currency_code VARCHAR(3) NOT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_pay_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_pay_currency FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_transactions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  payment_id BIGINT UNSIGNED NOT NULL,
  gateway_txn_id VARCHAR(128) NULL,
  raw_payload JSON NULL,
  status VARCHAR(50) NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  created_at DATETIME NULL,
  CONSTRAINT fk_pt_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- AFFILIATE & LOTTERY
CREATE TABLE IF NOT EXISTS affiliate_clicks (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  affiliate_user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NULL,
  ref_code VARCHAR(32) NOT NULL,
  landing_url VARCHAR(255) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NULL,
  CONSTRAINT fk_ac_user FOREIGN KEY (affiliate_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ac_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS affiliate_referrals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  affiliate_user_id BIGINT UNSIGNED NOT NULL,
  referred_user_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  commission_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  commission_currency VARCHAR(3) NOT NULL,
  status ENUM('pending','approved','paid') NOT NULL DEFAULT 'pending',
  created_at DATETIME NULL,
  CONSTRAINT fk_ar_affiliate FOREIGN KEY (affiliate_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ar_referred FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_ar_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_ar_currency FOREIGN KEY (commission_currency) REFERENCES currencies(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lotteries (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL,
  target_pool_amount DECIMAL(18,2) NOT NULL,
  current_pool_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency_code VARCHAR(3) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  auto_draw_threshold_percent TINYINT NOT NULL DEFAULT 100,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_lottery_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_lottery_currency FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lottery_entries (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lottery_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  buyer_user_id BIGINT UNSIGNED NOT NULL,
  affiliate_user_id BIGINT UNSIGNED NULL,
  lottery_code VARCHAR(32) NOT NULL UNIQUE,
  weight INT NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  CONSTRAINT fk_le_lottery FOREIGN KEY (lottery_id) REFERENCES lotteries(id) ON DELETE CASCADE,
  CONSTRAINT fk_le_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_le_buyer FOREIGN KEY (buyer_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_le_affiliate FOREIGN KEY (affiliate_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lottery_draws (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lottery_id BIGINT UNSIGNED NOT NULL,
  draw_number INT NOT NULL,
  drawn_at DATETIME NULL,
  status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  UNIQUE KEY ux_lottery_draw (lottery_id, draw_number),
  CONSTRAINT fk_ld_lottery FOREIGN KEY (lottery_id) REFERENCES lotteries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lottery_winners (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  lottery_draw_id BIGINT UNSIGNED NOT NULL,
  lottery_entry_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  is_claimed TINYINT(1) NOT NULL DEFAULT 0,
  claimed_at DATETIME NULL,
  CONSTRAINT fk_lw_draw FOREIGN KEY (lottery_draw_id) REFERENCES lottery_draws(id) ON DELETE CASCADE,
  CONSTRAINT fk_lw_entry FOREIGN KEY (lottery_entry_id) REFERENCES lottery_entries(id) ON DELETE CASCADE,
  CONSTRAINT fk_lw_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- LOYALTY
CREATE TABLE IF NOT EXISTS loyalty_tiers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) NOT NULL UNIQUE,
  min_points BIGINT NOT NULL DEFAULT 0,
  benefits JSON NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loyalty_points (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  source ENUM('order','referral','manual') NOT NULL,
  points BIGINT NOT NULL,
  occurred_at DATETIME NOT NULL,
  expires_at DATETIME NULL,
  meta JSON NULL,
  CONSTRAINT fk_lp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loyalty_redemptions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  points_spent BIGINT NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  created_at DATETIME NULL,
  CONSTRAINT fk_lr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_lr_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ANALYTICS (simple views)
CREATE TABLE IF NOT EXISTS product_views (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  session_id VARCHAR(100) NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  viewed_at DATETIME NOT NULL,
  CONSTRAINT fk_pv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_pv_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed minimal currencies
INSERT IGNORE INTO currencies (code, name, symbol, precision, is_default, is_active) VALUES
('IRR','Iranian Rial','﷼',0,1,1),
('USD','US Dollar','$',2,0,1),
('EUR','Euro','€',2,0,1);


