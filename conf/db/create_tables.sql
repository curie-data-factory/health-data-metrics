CREATE TABLE IF NOT EXISTS `hdm_core_dblist` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `db_name` VARCHAR(255) NOT NULL,
      `db_host` VARCHAR(255) NOT NULL,
      `db_port` VARCHAR(255) NOT NULL,
      `db_user` VARCHAR(255) NOT NULL,
      `db_is_ssl` VARCHAR(50) NOT NULL DEFAULT 'false',
      PRIMARY KEY (`id`) USING BTREE
    )
    COMMENT='TABLE qui contient la liste des bases de données.'
    ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS `hdm_pack_metric_conf` (
      `id_config` VARCHAR(555) NOT NULL,
      `pack_name` VARCHAR(255) NULL DEFAULT NULL,
      `pack_version` VARCHAR(255) NULL DEFAULT NULL,
      `pack_config` LONGTEXT NULL DEFAULT NULL,
      PRIMARY KEY (`id_config`) USING BTREE
    )
    COMMENT='TABLE qui contient la configuration des différents metric-packs'
    ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS `hdm_pack_rule_conf` (
      `id_config` VARCHAR(555) NOT NULL,
      `pack_name` VARCHAR(255) NULL DEFAULT NULL,
      `pack_version` VARCHAR(255) NULL DEFAULT NULL,
      `pack_config` LONGTEXT NULL DEFAULT NULL,
      PRIMARY KEY (`id_config`) USING BTREE
    )
    COMMENT='TABLE qui contient la liste des bases de données.'
    ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS `hdm_core_table_corr_db_mp` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `mp_key` VARCHAR(600) NULL DEFAULT NULL,
      `db_key` VARCHAR(600) NULL DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE
    )
    COMMENT='TABLE qui fait la correspondance entre les bases de données et les metricpacks.'
    ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS `hdm_core_table_corr_db_rp` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `rp_key` VARCHAR(600) NULL DEFAULT NULL,
      `db_key` VARCHAR(600) NULL DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE
    )
    COMMENT='TABLE qui fait la correspondance entre les bases de données et les rule packs.'
    ENGINE=InnoDB;
