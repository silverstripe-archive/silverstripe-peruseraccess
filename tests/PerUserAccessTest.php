<?php

class PerUserAccessTest extends SapphireTest {

	static $fixture_file = 'peruseraccess/tests/PerUserAccessTest.yml';

	function setUp() {
		parent::setUp();
		PerUserSiteTreeDecorator::$unit_test_me = true;
	}
	
	function tearDown() {
		parent::tearDown();
		PerUserSiteTreeDecorator::$unit_test_me = false;
	}
	
	function testOwnerHasFullPermission() {
		$page = $this->objFromFixture('SiteTree', 'Page1');
		$user = $this->objFromFixture('Member', 'UserB');
		$this->assertTrue($page->canView($user) && $page->canEdit($user) && $page->canCreate($user) && $page->canDelete($user) && $page->canPublish($user), 'User is page owner => true');
	}
	
	function testEditPermission() {
		$page = $this->objFromFixture('SiteTree', 'Page1');
		$user = $this->objFromFixture('Member', 'UserE');
		$this->assertTrue($page->canEdit($user), 'User has neither site nor group but user permission to edit => true');
	}
	
	function testNoEditPermission() {
		$page = $this->objFromFixture('SiteTree', 'Page1');
		$user = $this->objFromFixture('Member', 'UserC');
		$this->assertFalse($page->canEdit($user), 'User has neither site nor group nor user permission to edit => false');
	}
	
	function testImplicitPublishPermission() {
		$page = $this->objFromFixture('SiteTree', 'Page1');
		$user = $this->objFromFixture('Member', 'UserE');
		$this->assertTrue($page->canPublish($user), 'User has edit permission => publish permission implied => true');
	}
	
	function testInheritEditPermission() {
		$page = $this->objFromFixture('SiteTree', 'Page3');
		$user = $this->objFromFixture('Member', 'UserE');
		$this->assertTrue($page->canPublish($user), 'No custom permissions set on page, check ancestor page => true');
	}
	
	// function testShowPermissonsForDebugging() {
	// 	
	// 	for($p = 1; $p <= 5; $p++) {
	// 	
	// 		$page = $this->objFromFixture('SiteTree', 'Page' . $p);
	// 		
	// 		echo "<h3>{$page->Title}</h3>";
	// 	
	// 		echo "<table border='1'>";
	// 		echo "<tr><td></td><td>edit</td><td>view</td><td>delete</td><td>publish</td><td>create</td></tr>";
	// 		for($i = 65; $i <= 69; $i++) {
	// 			echo "<tr>";
	// 			$user = $this->objFromFixture('Member', 'User' . chr($i));
	// 			$user->logIn();
	// 			echo "<td>{$user->FirstName}</td>";
	// 			echo "<td>" . ($page->canEdit($user) ? 'YES' : 'no') . "</td>";
	// 			echo "<td>" . ($page->canView($user) ? 'YES' : 'no') . "</td>";
	// 			echo "<td>" . ($page->canDelete($user) ? 'YES' : 'no') . "</td>";
	// 			echo "<td>" . ($page->canPublish($user) ? 'YES' : 'no') . "</td>";
	// 			echo "<td>" . ($page->canAddChildren($user) ? 'YES' : 'no') . "</td>";
	// 			echo "</tr>";
	// 		}
	// 		echo "</table>";
	// 	}
	// }
}