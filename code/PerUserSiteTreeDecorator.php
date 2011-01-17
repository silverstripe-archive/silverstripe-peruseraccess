<?php

/**
 * The PerUserSiteTreeDecorator class which adds logic on the page side of peruseraccess
 * 
 * @see http://doc.silverstripe.org/dataobjectdecorator
 * @package peruseraccess
 */

class PerUserSiteTreeDecorator extends DataObjectDecorator implements PermissionProvider {

	// Permission bits
	const PERUSER_VIEW		= 1;
	const PERUSER_EDIT		= 2;
	const PERUSER_CREATE 	= 4;
	const PERUSER_DELETE 	= 8;
	const PERUSER_PUBLISH 	= 16;

	// caching some values that are expensive to determine
	private static $_peruserCan_cache = array();
	private static $_ancestor;

	// switch to turn peruseraccess off while performing unrelated unit tests
	// because this breakes other tests that assume the permissions to work normal
	public static $unit_test_me = false;

	// adds the additionally reqired db fields
	public function extraStatics() {
		return array(
			'db' => array(
				'CustomiseUserPermissions' => 'Boolean',	// weather or not to use CustomiseUserPermissions
			),
			'has_one' => array(
				'AccessOwner' => 'Member'	// set the owner of this page
			),
			'many_many' => array(
				'AccessGrantedUsers' => 'Member'	// set all other users that have permission on a per user basis
			),
			'many_many_extraFields' => array(
				'AccessGrantedUsers' => array('PerUserAccessLevelBits' => 'Int')	// the actual permission
			),
		);
	}

	/**
	 * We need some extra controls in the access tab of the pages UI.
	 * @see http://doc.silverstripe.org/dataobjectdecorator#modifying_cms_fields
	 * @param &$fields	Fieldset Object containing the fields to modify
	 * @return void
	 **/
	public function updateCMSFields(FieldSet &$fields) {

		if(SapphireTest::is_running_test() && !self::$unit_test_me) return;

		// Move Access tab fields into a sub-tab
		$tfields = $fields->fieldByName('Root.Access');
		$tab = new Tab('Group Access');
		foreach($tfields->Fields() as $field) {
			$tab->push($field);
		}
		$fields->removeByName('Access');
		$fields->addFieldToTab('Root', new TabSet('Access'));
		$fields->addFieldToTab('Root.Access', $tab);
		$fields->addFieldToTab('Root.Access', new Tab('PerUserAccess'));

		$ancestor = $this->ancestor();
		if($ancestor->ID) {
			$ancestorlink = " from <a href='admin/show/{$ancestor->ID}'>{$ancestor->MenuTitle}</a>";
		} else { $ancestorlink = ''; }	
		$fields->addFieldToTab('Root.Access.PerUserAccess', new OptionsetField('CustomiseUserPermissions', 'Per user permission setup', array('Inherit Permissions' . $ancestorlink, 'Customise Permissions')));

		// Populate PerUser tab
		$ownerField = new DataObjectPicker('AccessOwnerID', "Access Owner", $this->owner->AccessOwner()->ID);
		$ownerField->setConfig('fieldsToSearch', array('FirstName', 'Surname'));
		$ownerField->setConfig('summaryFields', array('Surname', 'FirstName', 'Email'));
		
		$grantedMembersField = new MemberSiteTreePermissionTableField('au', 'Allowed users', $this->owner);

		if (Permission::check('SET_PERUSER_OWNER')) {
			$fields->addFieldToTab('Root.Access.PerUserAccess', $ownerField);
			$fields->addFieldToTab('Root.Access.PerUserAccess', $grantedMembersField);
		} else {
			$fields->addFieldToTab('Root.Access.PerUserAccess', $ownerField->performReadonlyTransformation());
			$fields->addFieldToTab('Root.Access.PerUserAccess', $grantedMembersField->performReadonlyTransformation());
		}
	}

	/**
	 * Generic permission check function.
	 * @param $permCode int  Either one of the self::PERM_ codes or several ORed together
	 * @param $member Member  member to check, or CurrentMember is used if null
	 * @return boolean
	 */
	public function peruserCan($permCode, $member = null) {

		// some permissions imply others, e.g. if you grant canEdit you give canAddChildren for free. we don't want this so we're looking into the backtrace to avoid chaining of permissions
		$canNestingLevel = 0;
		foreach(debug_backtrace() as $step)
			if(
				isset($step['object']) && 
				isset($step['function']) && 
				$step['object'] instanceof SiteTree && 
				array_search(
					$step['function'], 
					array(
						'canView', 
						'canEdit', 
						'canAddChildren', 
						'alternateCanAddChildren', 
						'canDelete', 
						'canPublish'
					)
				) !== false &&
				++$canNestingLevel > 1
			) return null;

		// if we are in a test situation and it is not this decorator which is beeing tested do not alert permissions
		if(SapphireTest::is_running_test() && !self::$unit_test_me) {
			return null;
		}
		
		// if the per user permissions are inherited find the ancestor and use his settings
		if($this->owner->CustomiseUserPermissions) {
			if($member instanceof Member) $memberID = $member->ID;
			else if(is_numeric($member)) $memberID = $member;
			else $memberID = Member::currentUserID();

			$permission = null;

			// Page 'owner' can do anything.
			$page = $this->owner;
			$accessowner = false;
			while(!$accessowner) {
				$accessowner = $page->AccessOwnerID ? $page->AccessOwnerID : false;
				if($page->ParentID) $page = $page->Parent();
				else break;
			}
			if($accessowner && $accessowner == $memberID && $memberID) $permission = true;

			// Check granted members.
			// $grantedMember = $this->owner->AccessGrantedUsers()->find('ID', $memberID);
			// if($grantedMember && ($grantedMember->PerUserAccessLevelBits & $permCode) == $permCode) $permission = true;
			$permission = $permission ? $permission : $this->rawPermission($permCode, $memberID);
			if(!$permission && $permCode == PerUserSiteTreeDecorator::PERUSER_EDIT) $permission = $this->rawPermission(PerUserSiteTreeDecorator::PERUSER_PUBLISH, $memberID);
			// self::$_peruserCan_cache[$cacheKey] = $permission;

			return $permission;
		} else {
			$ancestor = $this->ancestor();
			if($ancestor->ID) {
				return $ancestor->peruserCan($permCode, $member);
			}
			// no ancestor found, do not alter permissions
			return null;
		}
	}

	/**
	 *	Helper function to just get the peruser permission without considering other permission concepts like group based permissions
	 *	@param $permBit Int Permission to be checked, bitwise
	 *	@param $user Int|Member
	 *	@return boolean permission
	 **/
	function rawPermission($permBit,$user) {
		$userID = is_numeric($user) ? $user : $user->ID;
		$user = $this->owner->AccessGrantedUsers()->find('ID', (int)$userID);
		if($user && ($user->PerUserAccessLevelBits & $permBit) == $permBit) return true;
	}

	/**
	 *	Bubble up the SiteTree to find the closest parent page that has custom permissions
	 *	@used in self::updateCMSFields() to display the page we're inheriting permissions from
	 *	@return Page object if ancestor is found or null
	 **/
	function ancestor() {
		if(!isset($this->_ancestor)) {
			$page = false;
			if($this->owner->Parent()) {
				$page = $this->owner;
				while(($page = $page->Parent()) && $page->ID) if($page->CustomiseUserPermissions) break;
			}
			$this->_ancestor = $page;
		}
		
		return $this->_ancestor;
	}

	/**
	 * Standard SilverStripe permission checking functions, all based on peruserCan
	 */
	public function canView($member = null) {
		return $this->peruserCan(self::PERUSER_VIEW, $member); 
	}
	public function canEdit($member = null) {
		return $this->peruserCan(self::PERUSER_EDIT, $member);
	}
	public function canAddChildren($member = null) { 
		return $this->peruserCan(self::PERUSER_CREATE, $member); 
	}
	public function canPublish($member = null) {
		return $this->peruserCan(self::PERUSER_PUBLISH, $member);
	}
	public function canDelete($member = null) {
		return $this->peruserCan(self::PERUSER_DELETE, $member);
	}

	/**
	 *	Provide custom permission for changing peruserpermissions
	 *	If you don't have this permission, you can't change peruserpermissions
	 *	@see http://doc.silverstripe.org/permission#permissionprovider
	 *	@return Array Permssions map
	 **/
	public function providePermissions() {
		return array(
			'SET_PERUSER_OWNER' => array(
				'name'		=> _t('PerUserSiteTreeDecorator.SET_PERUSER_OWNER', 'Set Access Owner for Per-User access'),
				'category'	=> _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
				'help'		=> _t('PerUserSiteTreeDecorator.SET_PERUSER_OWNER_HELP',
					'Ability to change the owner who can then control per-user permissions'),
				'sort'		=> 1000000
			)
		);
	}

}