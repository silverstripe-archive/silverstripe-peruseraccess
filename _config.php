<?php

Object::add_extension('SiteTree', 'PerUserSiteTreeDecorator');
Object::add_extension('Member', 'PerUserMemberDecorator');
Director::addRules(100, array('peruser//$Action' => 'MemberSiteTreePermissionTableField_Controller'));
