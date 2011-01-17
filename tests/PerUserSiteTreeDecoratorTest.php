<?php


/**
 * Test the class @see PerUserSiteTreeDecorator
 * @covers PerUserSiteTreeDecorator
 */
class PerUserSiteTreeDecoratorTest extends SapphireTest {

	static $fixture_file = 'peruseraccess/tests/PerUserSiteTreeDecoratorTest.yml';

	function setUp() {
		parent::setUp();
		PerUserSiteTreeDecorator::$unit_test_me = true;
	}
	
	function tearDown() {
		parent::tearDown();
		PerUserSiteTreeDecorator::$unit_test_me = false;
	}
	
	function testOwnerCanAll() {
		$owner = $this->objFromFixture('Member', 'owner');
		$page = $this->objFromFixture('SiteTree', 'toppage');
		$owner->logIn();
		$this->assertTrue($page->canView($owner), 'Page owner cannot view the page');
		$this->assertTrue($page->canEdit($owner), 'Page owner cannot edit the page');
		$this->assertTrue($page->canAddChildren($owner), 'Page owner cannot create pages of that type');
		$this->assertTrue($page->canDelete($owner), 'Page owner cannot delete the page');
		$this->assertTrue($page->canPublish($owner), 'Page owner cannot publish dit the page');
	}

	function testUserCanAll() {
		$user = $this->objFromFixture('Member', 'user');
		$page = $this->objFromFixture('SiteTree', 'toppage');
		$user->logIn();
		$this->assertTrue($page->canView($user));
		$this->assertTrue($page->canEdit($user));
		$this->assertTrue($page->canAddChildren($user));
		$this->assertTrue($page->canDelete($user));
		$this->assertTrue($page->canPublish($user));
	}

	function testUserCanView() {
		$user = $this->objFromFixture('Member', 'user');
		$page = $this->objFromFixture('SiteTree', 'toppage');
		$user->logIn();
		$page->AccessGrantedUsers()->add($user, array('PerUserAccessLevelBits' => PerUserSiteTreeDecorator::PERUSER_VIEW));
		$page->AccessGrantedUsers()->write();
		$this->assertTrue($page->canView($user));
		$this->assertFalse($page->canEdit($user));
		$this->assertFalse($page->canAddChildren($user));
		$this->assertFalse($page->canDelete($user));
		$this->assertFalse($page->canPublish($user));
	}

	function testUserCanEdit() {
		$user = $this->objFromFixture('Member', 'user');
		$page = $this->objFromFixture('SiteTree', 'toppage');
		$user->logIn();
		$page->AccessGrantedUsers()->add($user, array('PerUserAccessLevelBits' => PerUserSiteTreeDecorator::PERUSER_EDIT));
		$this->assertTrue($page->canView($user));
		$this->assertTrue($page->canEdit($user));
		$this->assertFalse($page->canAddChildren($user));
		$this->assertFalse($page->canDelete($user));
		$this->assertFalse($page->canPublish($user));
	}

	function testUserCanAddChildren() {
		$user = $this->objFromFixture('Member', 'user');
		$page = $this->objFromFixture('SiteTree', 'toppage');
		$user->logIn();
		$page->AccessGrantedUsers()->add($user, array('PerUserAccessLevelBits' => PerUserSiteTreeDecorator::PERUSER_CREATE));
		$this->assertTrue($page->canView($user));
		$this->assertFalse($page->canEdit($user));
		$this->assertTrue($page->canAddChildren($user));
		$this->assertFalse($page->canDelete($user));
		$this->assertFalse($page->canPublish($user));
	}

	function testUserCanDelete() {
		$user = $this->objFromFixture('Member', 'user');
		$page = $this->objFromFixture('SiteTree', 'toppage');
		$user->logIn();
		$page->AccessGrantedUsers()->add($user, array('PerUserAccessLevelBits' => PerUserSiteTreeDecorator::PERUSER_DELETE));
		$this->assertTrue($page->canView($user));
		$this->assertFalse($page->canEdit($user));
		$this->assertFalse($page->canAddChildren($user));
		$this->assertTrue($page->canDelete($user));
		$this->assertFalse($page->canPublish($user));
	}

	function testUserCanPublish() {
		$user = $this->objFromFixture('Member', 'user');
		$page = $this->objFromFixture('SiteTree', 'toppage');
		$user->logIn();
		$page->AccessGrantedUsers()->add($user, array('PerUserAccessLevelBits' => PerUserSiteTreeDecorator::PERUSER_PUBLISH));
		$this->assertTrue($page->canView($user));
		$this->assertTrue($page->canEdit($user));
		$this->assertFalse($page->canAddChildren($user));
		$this->assertFalse($page->canDelete($user));
		$this->assertTrue($page->canPublish($user));
	}


	function testUserCanNone() {
		$user = $this->objFromFixture('Member', 'user');
		$page = $this->objFromFixture('SiteTree', 'toppage');
		$user->logIn();
		$page->AccessGrantedUsers()->add($user, array('PerUserAccessLevelBits' => 0));
		$this->assertTrue($page->canView($user));
		$this->assertFalse($page->canEdit($user));
		$this->assertFalse($page->canAddChildren($user));
		$this->assertFalse($page->canDelete($user));
		$this->assertFalse($page->canPublish($user));
	}
		
}



?>
