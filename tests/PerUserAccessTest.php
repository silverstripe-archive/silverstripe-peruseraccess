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
	
	function testNoPermissionNoAccess() {
		
		for($p = 1; $p <= 5; $p++) {
		
			$page = $this->objFromFixture('SiteTree', 'Page' . $p);
			
			echo "<h3>{$page->Title}</h3>";
		
			echo "<table border='1'>";
			echo "<tr><td></td><td>edit</td><td>view</td><td>delete</td><td>publish</td><td>create</td></tr>";
			for($i = 65; $i <= 69; $i++) {
				echo "<tr>";
				$user = $this->objFromFixture('Member', 'User' . chr($i));
				$user->logIn();
				echo "<td>{$user->FirstName}</td>";
				// echo "<td>" . ($page->canEdit($user) ? 'YES' : 'no') . "</td>";
				// echo "<td>" . ($page->canView($user) ? 'YES' : 'no') . "</td>";
				// echo "<td>" . ($page->canDelete($user) ? 'YES' : 'no') . "</td>";
				// echo "<td>" . ($page->canPublish($user) ? 'YES' : 'no') . "</td>";
				// echo "<td>" . ($page->canAddChildren($user) ? 'YES' : 'no') . "</td>";
				echo "</tr>";

				// $this->assertTrue($page->canEdit($owner), 'Page owner cannot edit the page');
				// $this->assertTrue($page->canView($owner), 'Page owner cannot view the page');
				// $this->assertTrue($page->canDelete($owner), 'Page owner cannot delete the page');
				// $this->assertTrue($page->canPublish($owner), 'Page owner cannot publish dit the page');
				// $this->assertTrue($page->canAddChildren($owner), 'Page owner cannot create pages of that type');
			}
			echo "</table>";
		}
	}
}