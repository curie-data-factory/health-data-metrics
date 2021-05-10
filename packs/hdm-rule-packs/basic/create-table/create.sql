CREATE TABLE IF NOT EXISTS `rule_basic` (
	`id_rule` INT(11) NOT NULL AUTO_INCREMENT,
	`date_modif` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`rule_name` VARCHAR(1000) NOT NULL,
	`rule_type` VARCHAR(50) NOT NULL,
	`alert_level` VARCHAR(150) NOT NULL,
	`alert_class` VARCHAR(150) NOT NULL,
	`alert_message` VARCHAR(150) NOT NULL,
	`alert_scope` VARCHAR(150) NOT NULL,
	`condition_trigger` VARCHAR(150) NOT NULL,
	`database` VARCHAR(255) NOT NULL,
	`table` VARCHAR(255) NOT NULL,
	`column` VARCHAR(255) NOT NULL,
	`rule_content` TEXT(65535) NOT NULL,
	PRIMARY KEY (`id_rule`) USING BTREE
)
COMMENT='TABLE qui contient la liste des bases de données.'
ENGINE=InnoDB