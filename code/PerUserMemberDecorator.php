<?php

class PerUserMemberDecorator extends DataObjectDecorator {

	public function extraStatics() {
		return array(
			'belongs_many_many' => array(
				'AccessPages' => 'SiteTree'
			)
		);
	}
}
