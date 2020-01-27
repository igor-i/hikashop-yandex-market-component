DROP TABLE IF EXISTS `#__yandexmarket_yml_offers`;

-- -----------------------------------------------------
-- Table `#__yandexmarket_yml_offers`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__yandexmarket_yml_offers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `yml_id` INT(11) NOT NULL,
  `category_or_product_id` INT(11) NOT NULL,
  `category_or_product_type` ENUM('category','product') NOT NULL DEFAULT 'category',
  `mode` ENUM('include','exclude') NOT NULL DEFAULT 'include',
  PRIMARY KEY (`id`))
ENGINE=INNODB DEFAULT CHARSET=utf8;
