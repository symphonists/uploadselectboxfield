<?php

	Class extension_Uploadselectboxfield extends Extension{

		public function about(){
			return array('name' => 'Field: Uploaded File Select Box',
						 'version' => '1.1.3',
						 'release-date' => '2011-06-24',
						 'author' => array('name' => 'Nick Dunn, Brendan Abbott',
										   'website' => 'http://nick-dunn.co.uk')
				 		);
		}

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