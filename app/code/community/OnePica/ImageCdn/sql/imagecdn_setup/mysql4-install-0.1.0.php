<?php

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('imagecdn/cachedb')};
CREATE TABLE {$this->getTable('imagecdn/cachedb')} (
  `cachedb_id` int(10) unsigned NOT NULL auto_increment,
  `url_hash` char(32) NOT NULL default '',
  `last_checked` datetime default NULL,
  PRIMARY KEY  (`cachedb_id`),
  KEY `url_hash` (`url_hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

");

$installer->endSetup(); 