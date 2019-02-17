<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();
$default_prefix = (string) Mage::getConfig()->getTablePrefix();
$basetable = "stamped_app_richsnippets";
$tableName_with_quotes = "`" . $basetable .  "`";
if ($default_prefix != "") {
    $basetable = $default_prefix . $basetable;
    $tableName_with_quotes =  "`". $basetable . "`";
}
$sql =  "DROP TABLE IF EXISTS " . $tableName_with_quotes . ";
          CREATE TABLE " . $tableName_with_quotes . " (
          `richsnippet_id` int(11) NOT NULL auto_increment,
          `product_id` int(11) NOT NULL,
          `store_id` int(11) NOT NULL,
          `html_widget` TEXT NOT NULL,
          `average_score` float(11) NOT NULL,
          `reviews_count` int(11) NOT NULL,
          `expiration_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
          PRIMARY KEY  (`richsnippet_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        CREATE UNIQUE INDEX " . $basetable . "_product_id_store_id_i ON " . $tableName_with_quotes . " (`product_id`, `store_id`);
";
$installer->run($sql);
$installer->endSetup();