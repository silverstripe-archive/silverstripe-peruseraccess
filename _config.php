<?php

Object::add_extension('SiteTree', 'PerUserSiteTreeDecorator');
Director::addRules(100, array('peruser//$Action' => 'MemberSiteTreePermissionTableField_Controller'));
