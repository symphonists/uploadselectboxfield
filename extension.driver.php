<?php

	Class extension_Uploadselectboxfield extends Extension{

		public function uninstall(){
			Symphony::Database()->query("DROP TABLE `tbl_fields_uploadselectbox`");
		}


		public function install(){

			return Symphony::Database()->query("CREATE TABLE `tbl_fields_uploadselectbox` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`field_id` int(11) unsigned NOT NULL,
				`allow_multiple_selection` enum('yes','no') NOT NULL default 'no',
				`destination` varchar(255) NOT NULL,
				PRIMARY KEY  (`id`),
				UNIQUE KEY `field_id` (`field_id`)
			) TYPE=MyISAM");

		}

	}