<?php

	require_once(TOOLKIT . '/fields/field.select.php');

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class FieldUploadselectbox extends Field {

		function __construct(){
			parent::__construct();
			$this->_name = 'Upload Select Box';
			$this->set('show_column', 'no');
		}

		function canToggle(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		function allowDatasourceParamOutput(){
			## Grouping follows the same rule as toggling.
			return $this->canToggle();
		}

		function canFilter(){
			return true;
		}

		function canPrePopulate(){
			return true;
		}

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if (!is_array($data) || empty($data)) return;

			if (!is_array($data['file'])) {
				if(!$data['file']) return;
				$data = array('file' => array($data['file']));
			}

			$item = new XMLElement($this->get('element_name'));
			$path = DOCROOT;

			$item->setAttributeArray(array(
			 	'path' => str_replace(WORKSPACE, '', $path)
			));

			foreach($data['file'] as $index => $file) {
				$item->appendChild(new XMLElement(
					'item', General::sanitize($file), array(
						'size' => General::formatFilesize(filesize($path . $file)),
					)
				));
			}

			$wrapper->appendChild($item);
		}

		function displaySettingsPanel(XMLElement &$wrapper, $errors = null){

			Field::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', null, array('class' => 'group'));

			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			$directories = General::listDirStructure(WORKSPACE, null, 'asc', DOCROOT, $ignore);

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

			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow selection of multiple options', array($input->generate())));
			$wrapper->appendChild($label);

			$this->appendShowColumnCheckbox($wrapper);

		}

		function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
		  $data['file'] = $data['file'] ?? null; 
			if(!is_array($data['file'])) $data['file'] = array($data['file']);

			$options = array();
			$states = General::listStructure(DOCROOT . $this->get('destination'), null, false, 'asc', DOCROOT);
			array_unshift($states['filelist'], "");
			
			if (is_null($states['filelist']) || empty($states['filelist'])) $states['filelist'] = array();
			
			foreach($states['filelist'] as $handle => $v){
				$options[] = array(General::sanitize($v), in_array($v, $data['file']), $v);
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : null)));

			if($flagWithError != null) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		function prepareTableValue($data, ?XMLElement $link = null, $entry_id = null){
			$value = $data['file'];

			if(!is_array($value)) $value = array($value);

			$custom_link[] = "";

			foreach($value as $file) {
				if($link){
					$link->setValue(basename($file));
					$custom_link[] = $link->generate();
				}
				else{
					$link = Widget::Anchor(basename($file), URL . $this->get('destination') . '/'. $file);
					$custom_link[] = $link->generate();
				}
			}

			return implode(", ", $custom_link);
		}

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
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
			$fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());

		}

		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null){
			$status = self::__OK__;

			if(!is_array($data)) return array('file' => General::sanitize($data));
			if(empty($data)) return null;

			$result = array('file' => array());
			foreach($data as $file) {
				$result['file'][] = $file;
			}
			return $result;
		}

		function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT null auto_increment,
				  `entry_id` int(11) unsigned NOT null,
				  `file` varchar(255) default null,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				);"
			);
		}

	}

