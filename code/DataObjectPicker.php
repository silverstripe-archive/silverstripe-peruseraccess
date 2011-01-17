<?php
/**
 *	DATAOBJECTPICKER
 *	================
 *
 *	Formfield for picking one existing DataObject for the forms record,
 *	using a configurable dropdown like autosuggest list to pick from
 *
 *	If the owning class has the static $has_one var set and the class to
 *	pick from (target class) is propperly setup with the following static
 *	variables, DataObjectPicker does the rest for you: 
 *	- $searchable_fields
 *	- $summary_fields
 *	- $default_sort
 *
 *	EXAMPLE #1
 *	++++++++++
 *	Auto setup example for Page which has a static $has_one Author (=> Member class):
 *	
 *	new DataObjectPicker('AuthorID');
 *
 *	The above creates a DataObject picker field. Our custom Page has a
 *	static $has_one realtion called 'Author' to the Member class. Thats
 *	how the target class is resolved that we will pick existing objects
 *	from. When entering the first characters into the field, it fires of
 *	AJAX requests to the completeFunction, which is by default DataObjectPicker's
 *	own getSuggestions(). It uses the target classes static $searchable_fields
 *	to search in using the predifined searchPattern. If $searchable_fields
 *	is not set it falls back to Title then Name then fails. The first 20 ($limit)
 *	records found will be returned JSON encoded, using the target classes
 *	static $summary_fields, ordered by the target classes static $default_sort
 *
 *	If this doesn't work for you because e.g. you are not in a getCMSFields()
 *	context or the target class is not set up with all the statics or you want
 *	to use your own suggestion callback you can setup almost everything like this:
 *
 *	EXAMPLE #2
 *	++++++++++
 *	Custom setup
 *
 *	$field = new DataObjectPicker($name, $title, $value, null, $form);
 *	$field->setConfig('join', $yourjoinstatement);
 *	$field->setConfig('fieldsToSearch', array(
 *		'Surname',	// search for search term in Surname using the default search pattern
 *		'Description' => "\"TargetClassName\".\"Description\" LIKE '%%%s%%'",	// search for term in Desciption using the custom search pattern
 *		'Age' => "\"TargetClassName\".\"Age\" >= \"JoinedClass\".\"SomeField\"", // not actually a search but showing what is possible
 *	));
 *	$field->setConfig('completeFunction', array('Page', 'myCustomSuggestionCallback')); send the request off to Page::myCustomSuggestionCallback() for suggestions
 *
 * @package peruseraccess
 **/

class DataObjectPicker extends TextField {

	/**
	 *	
	 **/
	protected $config = array(
		'completeFunction' => array('DataObjectPicker', 'getSuggestions'),
		'searchPattern' => "\"%s\" LIKE '%s%%'",
		'orderBy' => '',
		'limit' => 20,
		'join' => '',
		'readonly' => false,
	);

	function Field(){
		
		$current = $this->Value() ? DataObject::get_by_id($this->classToPick(), $this->Value()) : false;
		if($current) {
			$sf = $this->summaryFields();
			$full = array();
			foreach($sf as $f) if($current->$f) $full[] = $current->$f;
			if(empty($full)) $full[] = 'undefined dataobject';
			$nice = implode(',', $full);
		} else {
			$nice = 'none selected';
		}

		$html =
			$this->createTag('input', array(
				'type' => 'hidden',
				'class' => 'DataObjectPicker',
				'id' => $this->id(),
				'name' => $this->Name(),
				'value' => $this->Value(),
			));
		if($this->config['readonly']) {
			$html .=
				$this->createTag('span', array(
					'class' => 'DataObjectPickerHelper text readonly' . ($this->extraClass() ? $this->extraClass() : ''),
					'id' => $this->id() . '_helper',
					'tabindex' => $this->getTabIndex(),
					'readonly' => 'readonly',
				), $nice);
		} else {
			$html .=
				$this->createTag('input', array(
					'type' => 'text',
					'autocomplete' => 'off',
					'class' => 'DataObjectPickerHelper text' . ($this->extraClass() ? $this->extraClass() : ''),
					'id' => $this->id() . '_helper',
					'name' => $this->Name() . '_helper',
					'value' => $nice,
					'tabindex' => $this->getTabIndex(),
					'maxlength' => ($this->maxLength) ? $this->maxLength : null,
					'size' => ($this->maxLength) ? min( $this->maxLength, 30 ) : null,
					'rel' => $this->form ? $this->Link('Suggest') : 'admin/EditForm/field/' . $this->Name() . '/Suggest',
				)).
				$this->createTag('br', array()).
				$this->createTag('ul', array(
					'class' => 'DataObjectPickerSuggestions',
					'id' => $this->id() . '_suggestions',
				));
		}
		
		return $html;
	}
	
	/**
	 *	Set some configuration parameters that differ from what DataObject would expect or unguessable
	 **/
	function setConfig($key, $val) {
		$this->config[$key] = $val;
	}

	/**
	 *	Return field holder to the form
	 **/
	function FieldHolder() {
		if(!$this->config['readonly']) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.core.js');
			Requirements::javascript('peruseraccess/javascript/DataObjectPicker.js');
		}
		Requirements::css('peruseraccess/css/DataObjectPicker.css');

		return parent::FieldHolder();
	}

	/**
	 *	Call lookup callback and pass it the request
	 *	Use DataObjectPicker::setConfig() to specify a callback.
	 *	@param Request Object since this is called by handleAction()
	 *	@return String JSON fomatted ennumerated array containing assosiative array with id, title and full for each suggested DataObject
	 **/
	public function Suggest($request) {
		if($this->config['completeFunction']) {
			return call_user_func($this->config['completeFunction'], $request);
		}
	}

	/**
	 *	Default callback to return JSON formatted suggestions to form field via AJAX
	 *	Use DataObjectPicker::setConfig() to replace this function.
	 *	@param Request Object since this is called by handleAction()
	 *	@return String JSON fomatted ennumerated array containing assosiative array with id, title and full for each suggested DataObject
	 **/
	function getSuggestions($req) {
		
		$request = Convert::raw2sql($req->requestVar('request'));

		// if class to search in is unknown, try to guess
		if(empty($this->config['classToPick'])) {
			$recordClass = $this->Form->getRecord() ? get_class($this->Form->getRecord()) : false;
			$relationName = substr($this->Name(), -2) == 'ID' ? substr($this->Name(), 0, -2) : false;
			$relationNames = $recordClass ? Object::combined_static($recordClass, 'has_one') : array();
			if(empty($relationNames[$relationName])) trigger_error("Can't figure out which class to search in. Please setup 'classToPick' using DataObjectPicker::setConfig().");
			$this->config['classToPick'] = $relationNames[$relationName];
		}
		
		// if fieldnames to include in search are not defined, try to guess
		if(empty($this->config['fieldsToSearch'])) {
			if(Object::combined_static($this->classToPick(), 'searchable_fields')) {
				
			} else {
				$fields = Object::combined_static($this->classToPick(), 'db');
				if(isset($fields['Title'])) $this->config['fieldsToSearch']['Title'] = sprintf($this->config['searchPattern'], 'Title', $request);
				else if(isset($fields['Name'])) $this->config['fieldsToSearch']['Name'] = sprintf($this->config['searchPattern'], 'Name', $request);
				else trigger_error("Can't figure out which fields to search in class '" . $this->classToPick() . "'. Please setup 'classToPick' using DataObjectPicker::setConfig().");
			}
		} else {
			foreach($this->config['fieldsToSearch'] as $key => $val) if(is_numeric($key)) {
				$this->config['fieldsToSearch'][$val] = sprintf($this->config['searchPattern'], $val, $request);
				unset($this->config['fieldsToSearch'][$key]);
			} else {
				$this->config['fieldsToSearch'][$val] = sprintf($this->config['searchPattern'], $request);
			}
		}

		// if order by is omitted, try using the classes default_sort
		if(empty($this->config['orderBy']) && !(Object::combined_static($this->classToPick(), 'default_sort'))) $this->config['orderBy'] = Object::combined_static($this->classToPick(), 'default_sort');
		
		if(empty($this->config['summaryFields'])) $this->config['summaryFields'] = $this->summaryFields();
		
		$filter = implode(' OR ', $this->config['fieldsToSearch']);
		if(!empty($this->config['extraFilter'])) $filter = "($filter) AND ({$this->config['extraFilter']})";

		$results = DataObject::get($this->classToPick(), $filter, $this->config['orderBy'], $this->config['join'], $this->config['limit']);

		if($results) {
			$return = array(
				array(
					'id' => '0',
					'title' => 'none selected',
					'full' => "select none",
					'style' => 'color:red',
				),
			);
			foreach($results as $do) {
				$titlefield = $this->config['summaryFields'][0];
				$full = array();
				foreach($this->config['summaryFields'] as $sf) if($do->$sf) $full[] = $do->$sf;
				if(empty($full)) $full = array('undefined dataobject');
				$return[] = array(
					'id' => $do->ID,
//					'title' => $do->$titlefield,
					'title' => implode(', ', $full),
					'full' => implode(', ', $full),
				);
			}
		} else {
			$return = array(
				array(
					'id' => '0',
					'title' => 'none selected',
					'full' => "select none",
					'style' => 'color:red',
				),
			);
		}
		
		return json_encode($return);
	}

	/**
	 *	Get class to pick objects from, uses DataObjectPicker::config['classToPick'] and if empty
	 *	tries to resolve the class by looking up the records has_one relations. Set $name properly
	 *	for this to work.
	 *	@return String class name that can be picked from
	 **/
	protected function classToPick() {
		if(empty($this->config['classToPick'])) {
			$recordClass = $this->Form->getRecord() ? get_class($this->Form->getRecord()) : false;
			$relationName = substr($this->Name(), -2) == 'ID' ? substr($this->Name(), 0, -2) : false;
			$relationNames = $recordClass ? Object::combined_static($recordClass, 'has_one') : array();
			if(empty($relationNames[$relationName])) trigger_error("Can't figure out which class to search in. Please setup 'classToPick' using DataObjectPicker::setConfig().");
			$this->config['classToPick'] = $relationNames[$relationName];
		}
		return $this->config['classToPick'];
	}
	
	/**
	 *	Get summaryFields to describe the suggested DataObjects, uses DataObjectPicker::config['summaryFields'] and if empty
	 *	tries to guess by looking into the static $summary_fields of the classToPick(). Set static $summary_fields properly
	 *	for the guessing to work.
	 *	@return Array of summaryFields
	 **/
	protected function summaryFields() {
		if(empty($this->config['summaryFields'])) {
			if(Object::combined_static($this->classToPick(), 'summary_fields')) {
				$this->config['summaryFields'] = array();
				foreach(Object::combined_static($this->classToPick(), 'summary_fields') as $key => $val) {
					$sf = is_numeric($key) ? $val : $key;
					if(strpos($sf, '.') === false) $this->config['summaryFields'][] = trim($sf, '"');
				}
			} else {
				$this->config['summaryFields'] = array();
				foreach(Object::combined_static($this->classToPick(), 'db') as $sf => $void) if(count($this->config['classToPick']) < 3) $this->config['summaryFields'][] = $sf;
			}
		}
		return $this->config['summaryFields'];
	}
	
	/**
	 *	Return a 
	 *	@return DataObjectPicker instance that is readonly
	 **/
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setConfig('readonly', true);
		return $clone;
	}
}