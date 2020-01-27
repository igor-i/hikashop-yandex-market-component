DROP TABLE IF EXISTS `#__yandexmarket_ymls`;
DROP TABLE IF EXISTS `#__yandexmarket_yml_categories`;

-- -----------------------------------------------------
-- Table `#__yandexmarket_ymls`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__yandexmarket_ymls` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NULL DEFAULT NULL,
  `params` TEXT NULL DEFAULT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT '0',
  `published` TINYINT(1) NOT NULL DEFAULT '1',
  `created_on` DATETIME NULL DEFAULT NULL,
  `offers_count` INT(11) NOT NULL DEFAULT '0',
  `changefreq` ENUM('hourly','daily','weekly','monthly','yearly','never') NOT NULL DEFAULT 'weekly',
  PRIMARY KEY (`id`),
  INDEX `default_idx` (`is_default` ASC, `id` ASC))
ENGINE=INNODB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `#__yandexmarket_categories`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__yandexmarket_categories` (
  `category_id` INT(10) UNSIGNED NOT NULL,
  `category_myname` VARCHAR(255) NULL DEFAULT NULL,
  `published` TINYINT(1) NOT NULL DEFAULT '1',
  `params` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`category_id`))
ENGINE=INNODB DEFAULT CHARSET=utf8;
