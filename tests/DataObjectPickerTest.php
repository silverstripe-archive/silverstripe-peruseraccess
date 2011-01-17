<?php

class DataObjectPickerTest extends FunctionalTest {

	static $fixture_file = 'peruseraccess/tests/DataObjectPickerTest.yml';

	protected $extraDataObjects = array(
		'DogOwner',
		'Dog'
	);

	public function testFieldWithMagicAndValue() {
    	$bello = $this->objFromFixture('Dog', 'Bello');
		$form = new Form(new CMSMain(), 'TestForm', $bello->getCMSFields(), new FieldSet());
		$form->loadDataFrom($bello);
		$field = $form->Fields()->fieldByName('OwnerID');
		$this->assertContains('Wolfgang', $field->FieldHolder(), 'Parent DataObject found.');
	}

}

class DogOwner extends DataObject implements TestOnly {

	static $db = array(
		'Name' => 'Varchar',
	);

	static $has_many = array(
		'Dogs' => 'Dog',
	);

}

class Dog extends DataObject implements TestOnly {

	static $db = array(
		'Name' => 'Varchar',
	);

	static $has_one = array(
		'Owner' => 'DogOwner',
	);
	
	static $summary_fields = array(
		'Name',
	);

	public function getCMSFields() {
		return new FieldSet(new DataObjectPicker('OwnerID', "Owner"));
	}
}