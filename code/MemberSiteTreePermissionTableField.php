<?php

/**
 * The MemberSiteTreePermissionTableField class, a complex form field to add a peruseraccess UI
 * 
 * @package peruseraccess
 */

class MemberSiteTreePermissionTableField extends CompositeField {

	function __construct($name, $title, $page) {
		Requirements::javascript('peruseraccess/javascript/MemberSiteTreePermissionTableField.js');
		$this->name = "{$name}_{$page->ID}";
		$this->title = $title;
		$idmap = array();
		foreach(DB::query("SELECT * FROM \"SiteTree_AccessGrantedUsers\" WHERE \"SiteTreeID\" = " . (int)$page->ID) as $relation) $idmap[$relation['MemberID']] = $relation['ID'];
		$currentpermissions = $page->AccessGrantedUsers();
		$children = array(new HiddenField($this->Name().'_page', $this->Name().'_page', (int)$page->ID));
		foreach($currentpermissions as $user) $children[] = new MemberSiteTreePermissionField("relation_" . $idmap[$user->ID], $user, $page);
		$children[] = new MemberSiteTreePermissionField("relation_0", null, $page);
		parent::__construct($children);
	}
	
	function FieldHolder() {
		Requirements::javascript('peruseraccess/javascript/MemberSiteTreePermissionTableField.js');
		
		if($this->readonly) {
			$html = '';
		} else {
			$html = '
				<fieldset id="peruserlegend" style="color:#888;">
					<legend style="display:block;">Legend</legend>
					<ul>
						<li><input type="checkbox" disabled="disabled" checked="checked" /> <span style="color:green; font-weight:bold;">Action</span> User can perform Action, assigned by Per User Access</li>
						<li><input type="checkbox" disabled="disabled" /> <span style="color:green; font-weight:bold;">Action</span> User can perform Action, which is inherited (usually from their user group)</li>
						<li><input type="checkbox" disabled="disabled" /> <span style="color:red; text-decoration:line-through;">Action</span> User can not perform Action but CAN be given permission to</li>
						<li><input type="checkbox" disabled="disabled" /> <span style="color:grey;">Action</span> It is not possible to assign this Action (add a user first)</li>
					</ul>
				</fieldset>
			';
		}

		foreach($this->FieldSet() as $field) $html .= $field->FieldHolder();

		$html = $this->createTag('label', array('class' => 'left'), $this->Title()) . $this->createTag('div', array('class' => 'middleColumn'), $html);
		
		$html = $this->createTag('div', array(
			'class' => 'field ' . get_class($this),
			'id' => $this->Name(),
		), $html);
				
		return $html;
	}
	
	// hack to make this field look like a data field to form
	function hasData() { return true; }

	// getter for the MemberSiteTreePermissionFields
	function dataFieldByName($name) {
		foreach($this->children as $child) if($child->Name() == $name) return $child;
	}
}


/**
 * The MemberSiteTreePermissionField class, complex subfields of the MemberSiteTreePermissionTableField field
 * 
 * @package peruseraccess
 */

class MemberSiteTreePermissionField extends CompositeField {

	protected $user;
	protected $page;
	protected $relation;
	protected $readonly;

	function __construct($name = null, $user = null, $page = null) {
		Requirements::javascript('peruseraccess/javascript/MemberSiteTreePermissionTableField.js');

		if(!$page) { parent::__construct(); return; }
		
		if(!$user) $user = new Member();
		
		if(preg_match('/_(\d+)$/', $name, $matches)) $this->relation = $matches[1];

		$this->name = $name;
		$this->user = $user;
		$this->page = $page;
		$children = array(
			$picker = new DataObjectPicker('AccessGrantedUser_' . $this->relation, "Access Granted User", $user->ID),
			new TriStateCheckboxField('View_' . $this->relation, 'View', $user->ID ? $page->canView($user) : null, $page->rawPermission(PerUserSiteTreeDecorator::PERUSER_VIEW, $user)),
			new TriStateCheckboxField('Edit_' . $this->relation, 'Edit', $user->ID ? $page->canEdit($user) : null, $page->rawPermission(PerUserSiteTreeDecorator::PERUSER_EDIT, $user)),
			new TriStateCheckboxField('Create_' . $this->relation, 'Create', $user->ID ? $page->canAddChildren($user) : null, $page->rawPermission(PerUserSiteTreeDecorator::PERUSER_CREATE, $user)),
			new TriStateCheckboxField('Delete_' . $this->relation, 'Delete', $user->ID ? $page->canDelete($user) : null, $page->rawPermission(PerUserSiteTreeDecorator::PERUSER_DELETE, $user)),
			new TriStateCheckboxField('Publish_' . $this->relation, 'Publish', $user->ID ? $page->canPublish($user) : null, $page->rawPermission(PerUserSiteTreeDecorator::PERUSER_PUBLISH, $user)),
			new InlineFormAction('save_user_' . $this->relation, 'save', "\" onclick=\"return false;"),
		);
		if($user->ID > 0) $children[] = new InlineFormAction('remove_user_' . $this->relation, 'delete', "\" onclick=\"return false;");

		$picker->setConfig('classToPick', 'Member');
		$picker->setConfig('fieldsToSearch', array('FirstName', 'Surname'));
		$picker->setConfig('summaryFields', array('Surname', 'FirstName', 'Email'));
		parent::__construct($children);
	}
	
	function FieldHolder() {
		Requirements::javascript('peruseraccess/javascript/MemberSiteTreePermissionTableField.js');
		$html = '';
		foreach($this->FieldSet() as $field) {
			if($this->readonly) {
				if($field instanceof DataObjectPicker && !$field->Value()) {
					return '';
				} else if($field instanceof CheckboxField) {
					$color = 'color:grey;';
					if($field->indicator === true) $color = 'color:green;font-weight:bold;';
					if($field->indicator === false) $color = 'color:red;text-decoration:line-through;';
					$html .= $this->createTag('div',array('style' => 'width:60px;margin:3px 0 0 10px; float:left;'.$color),$field->Title() . ': ' . ($field->Value() ? 'Yes' : 'No'));
				} else if($field instanceof DataObjectPicker) {
					$html .= $this->createTag('div',array('style' => 'width:200px; float:left; height:24px; clear:left'), $field->Field());
				} else {
					$html .= '';
				}
			} else {
				if($field instanceof TriStateCheckboxField) {
					$color = 'color:grey;';
					if($field->indicator === true) $color = 'color:green;font-weight:bold;';
					if($field->indicator === false) $color = 'color:red;text-decoration:line-through;';
					$html .= $this->createTag('div',array('style' => 'width:60px;margin:3px 0 0 10px; float:left;'),$field->Field() . $this->createTag('label',array('class' => 'right', 'style' => 'margin:3px;' . $color, 'for' => $field->ID()), $field->Title()));
				} else if($field instanceof InlineFormAction) {
					$html .= $field->Field();
				} else {
					$html .= $this->createTag('div',array('style' => 'width:300px; float:left;'), $field->Field());
				}
			}
		}

		$html = $this->createTag('div', array(
			'class' => 'field ' . get_class($this),
			'style' => ' height:24px;',
			'id' => $this->Name(),
		), $html);
		
		return $html;
	}
	
	function setSession() {}

	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->readonly = true;
		return $clone;
	}

}

/**
 * The MemberSiteTreePermissionTableField_Controller class, which handles all the AJAX actions coming from the UI
 * 
 * @package peruseraccess
 */

class MemberSiteTreePermissionTableField_Controller extends Controller {
	
	/**
	 *	Remove a user from the peruserpermission UI, it does not remove the user of course
	 *	Called by handleRequest()
	 *	@param $req SS_HTTPRequest object
	 *	@return String response body or full SS_HTTPResponse
	 **/
	function remove($req) {
		if (!Permission::check('SET_PERUSER_OWNER') && !Permission::check('ADMIN')) {
			return json_encode(array('status' => 'bad', 'message' => 'ERROR: You do not have permission to change permissions. Your change was not saved.'));
		}

		$get = $req->getVars();
		if(isset($get['remove']) && is_numeric($get['remove'])) {
			DB::query("DELETE FROM \"SiteTree_AccessGrantedUsers\" WHERE \"ID\" = " . (int)$get['remove']);
			return json_encode(array('status' => 'good', 'message' => 'Rule removed'));
		} else {
			return json_encode(array('status' => 'bad', 'message' => 'ERROR: Bad request'));
		}
	}

	/**
	 *	Add a user or change a users permission
	 *	Called by handleRequest()
	 *	@param $req SS_HTTPRequest object
	 *	@return String response body or full SS_HTTPResponse
	 **/
	function save($req) {
		if (!Permission::check('SET_PERUSER_OWNER') && !Permission::check('ADMIN')) {
			return json_encode(array('status' => 'bad', 'message' => 'ERROR: You do not have permission to change permissions. Your change was not saved.'));
		}

		$get = $req->getVars();
		if(isset($get['relation']) && is_numeric($get['relation']) && $get['relation'] > 0) {
			// a relation exists => we're dealing with an existing record in the relations table => change permission
			if(!isset($get['user']) || !is_numeric($get['user']) || $get['user'] < 1) return 'ERROR: Please pick a user.';
			if(!isset($get['page']) || !is_numeric($get['page']) || $get['page'] < 1) return 0;
			$page = DataObject::get_by_id('SiteTree', (int)$get['page']);
			$user = DataObject::get_by_id('Member', (int)$get['user']);
			if(!$page) return json_encode(array('status' => 'bad', 'message' => 'ERROR: page doesn\'t exist'));
			if(!$page->canEdit(Member::currentMember())) return json_encode(array('status' => 'bad', 'message' => 'ERROR: You do not have permission to change permissions. Your change was not saved.'));
			$perm = 0;
			if($get['pview'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_VIEW;
			if($get['pedit'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_EDIT;
			if($get['pcreate'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_CREATE;
			if($get['pdelete'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_DELETE;
			if($get['ppublish'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_PUBLISH;
			if(DB::query("SELECT COUNT(*) FROM \"SiteTree_AccessGrantedUsers\" WHERE \"SiteTreeID\" = '".(int)$get['page']."' AND \"MemberID\" = '".(int)$get['user']."' AND \"ID\" != " . (int)$get['relation'])->value()) return json_encode(array('status' => 'bad', 'message' => 'ERROR: Permissions for this user already exist.'));
			$query = "UPDATE \"SiteTree_AccessGrantedUsers\" SET \"SiteTreeID\" = '".(int)$get['page']."', \"MemberID\" = '".(int)$get['user']."', \"PerUserAccessLevelBits\" = '$perm' WHERE \"ID\" = " . (int)$get['relation'];
			DB::query($query);
			$field = new MemberSiteTreePermissionField("relation_" . (int)$get['relation'], $user, $page);
			$html = $field->FieldHolder();
			return json_encode(array('status' => 'good', 'message' => 'Rule saved', 'relation' => (int)$get['relation'], 'html' => $html));
		} else {
			// no relation exists => we have to add a user in the relations table
			$page = DataObject::get_by_id("SiteTree", (int)$get['page']);
			$user = DataObject::get_by_id("Member", (int)$get['user']);
			if(!$user) return json_encode(array('status' => 'bad', 'message' => 'ERROR: Please pick a user.'));
			if(!$page) return json_encode(array('status' => 'bad', 'message' => 'ERROR: Bad request'));;
			if(!$page->canEdit(Member::currentMember())) return json_encode(array('status' => 'bad', 'message' => 'ERROR: You do not have permission to change permissions. Your change was not saved.'));
			$perm = 0;
			if($get['pview'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_VIEW;
			if($get['pedit'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_EDIT;
			if($get['pcreate'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_CREATE;
			if($get['pdelete'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_DELETE;
			if($get['ppublish'] == 'true') $perm += PerUserSiteTreeDecorator::PERUSER_PUBLISH;
			if(DB::query("SELECT COUNT(*) FROM \"SiteTree_AccessGrantedUsers\" WHERE \"SiteTreeID\" = '".(int)$get['page']."' AND \"MemberID\" = '".(int)$get['user']."'")->value()) return json_encode(array('status' => 'bad', 'message' => 'ERROR: Permissions for this user already exist.'));
			$query = "INSERT INTO \"SiteTree_AccessGrantedUsers\" (\"SiteTreeID\", \"MemberID\", \"PerUserAccessLevelBits\") VALUES ('".(int)$get['page']."', '".(int)$get['user']."', '$perm')";
			DB::query($query);
			$oldfield = new MemberSiteTreePermissionField("relation_" . DB::getConn()->getGeneratedID(null), $user, $page);
			$newfield = new MemberSiteTreePermissionField("relation_0", null, $page);
			$html = $oldfield->FieldHolder() . $newfield->FieldHolder();
			return json_encode(array('status' => 'good', 'message' => 'Rule added', 'html' => $html));
		}
	}
}


/**
 * The TriStateCheckboxField class to indicate different permission states
 * 
 * @package peruseraccess
 */

class TriStateCheckboxField extends CheckboxField {

	public $indicator = null;

	function __construct($name, $title = null, $indicator = null, $value = null, $form = null, $rightTitle = null) {
		$this->indicator = $indicator;
		parent::__construct($name, $title, $value, $form, $rightTitle);
	}

	function FieldHolder() {
		$holder = parent::FieldHolder();
		if($this->indicator !== null) $holder = str_replace('<label class="right" ', '<label class="right" style="width:70px; color:' . ($this->indicator ? 'green; font-weight:bold' : 'red; text-decoration:line-through') . ';" ', $holder);
		else $holder = str_replace('<label class="right" ', '<label class="right" style="color:yellow" ', $holder);
		return $holder;
	}
}