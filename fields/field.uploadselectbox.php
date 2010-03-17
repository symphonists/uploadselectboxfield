<?php
	
	require_once(TOOLKIT . '/fields/field.select.php');
	
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class FieldUploadselectbox extends Field {
		
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Upload Select Box';
			$this->set('show_column', 'no');			
		}
		
		function allowDatasourceParamOutput(){
			return false;
		}		
		
		function canFilter(){
			return true;
		}
		
		public function canImport(){
			return false;
		}
		
		function canPrePopulate(){
			return true;
		}	

		function isSortable(){
			return false;
		}
		
		public function appendFormattedElement(&$wrapper, $data, $encode = false) {
			$item = new XMLElement($this->get('element_name'));
			
			if(is_null($data['file']) || $data['file'] == '') return;
			
			$file = DOCROOT . $this->get('destination') . '/' .$data['file'];
			
			$item->setAttributeArray(array(
				'size' => General::formatFilesize(filesize($file)),
			 	'path' => str_replace(WORKSPACE, NULL, dirname($file))
			));
			
			$item->appendChild(new XMLElement('filename', General::sanitize(basename($data['file']))));
					
			$wrapper->appendChild($item);
		}
				
		function displaySettingsPanel(&$wrapper, $errors=NULL){		
			
			Field::displaySettingsPanel($wrapper, $errors);
			
			$div = new XMLElement('div', NULL, array('class' => 'group'));
						
			$this->appendShowColumnCheckbox($wrapper);

			## Destination Folder
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			$directories = General::listDirStructure(WORKSPACE, true, 'asc', DOCROOT, $ignore);	   	
	
			$label = Widget::Label(__('Destination Directory'));

			$options = array();
			$options[] = array('/workspace', false, '/workspace');
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) $options[] = array($d, ($this->get('destination') == $d), $d);
				}	
			}

			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][destination]', $options));
				
			if(isset($errors['destination'])) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['destination']));
			else $wrapper->appendChild($label);
			
			$this->appendRequiredCheckbox($wrapper);
			
			$this->appendShowColumnCheckbox($wrapper);
						
		}
		
		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			
			$options = array('');
			$states = General::listStructure(DOCROOT . $this->get('destination'), null, false, 'asc', DOCROOT);
			
			foreach($states['filelist'] as $handle => $v){
				$options[] = array(General::sanitize($v), ($v == $data['file']), $v);
			}
			
			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options));
			
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);		
		}
		
		function prepareTableValue($data, XMLElement $link=NULL){
			if(!$file = $data['file']) return NULL;
					
			if($link){
				$link->setValue(basename($file));
				return $link->generate();
			}			
			else{
				$link = Widget::Anchor(basename($file), URL . '/workspace' . $file);
				return $link->generate();
			}
			
		}
		
		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
			
			if (preg_match('/^mimetype:/', $data[0])) {
				$data[0] = str_replace('mimetype:', '', $data[0]);
				$column = 'mimetype';
				
			} else if (preg_match('/^size:/', $data[0])) {
				$data[0] = str_replace('size:', '', $data[0]);
				$column = 'size';
				
			} else {
				$column = 'file';
			}
			
			if (self::isFilterRegex($data[0])) {
				$this->_key++;
				$pattern = str_replace('regexp:', '', $this->cleanValue($data[0]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.{$column} REGEXP '{$pattern}'
				";
				
			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND t{$field_id}_{$this->_key}.{$column} = '{$value}'
					";
				}
				
			} else {
				if (!is_array($data)) $data = array($data);
				
				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}
				
				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.{$column} IN ('{$data}')
				";
			}
			
			return true;
		}
		
		function commit(){
			
			if(!parent::commit()) return false;
			
			$id = $this->get('id');

			if($id === false) return false;
			
			$fields = array();
			
			$fields['field_id'] = $id;
			$fields['destination'] = $this->get('destination');
			
			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");		
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());
					
		}
		
		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;			
			return array(
				'file' => $data
			);
		}
		
		function createTable(){
			
			return $this->_engine->Database->query(
			
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `file` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				);"
			
			);
		}
		
	}

