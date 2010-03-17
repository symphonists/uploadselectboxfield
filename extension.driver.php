<?php

	Class extension_Uploadselectboxfield extends Extension{
	
		public function about(){
			return array('name' => 'Field: Uploaded File Select Box',
						 'version' => '0.1',
						 'release-date' => '2008-12-23',
						 'author' => array('name' => 'Nick Dunn',
										   'website' => 'http://nick-dunn.co.uk')
				 		);
		}
		
		public function uninstall(){
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_uploadselectbox`");
		}


		public function install(){

			return $this->_Parent->Database->query("CREATE TABLE `tbl_fields_uploadselectbox` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) unsigned NOT NULL,
			  `destination` varchar(255) NOT NULL,
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `field_id` (`field_id`)
			) TYPE=MyISAM");

		}
			
	}

