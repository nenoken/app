<?php

class SpecialRequestToBeForgottenInternalController extends WikiaSpecialPageController {
	public function __construct() {
		parent::__construct( 'RequestToBeForgottenInternal', 'requesttobeforgotten', false );
		$this->specialPage->checkPermissions();
	}

	public function index() {
		if ( $this->getRequest()->wasPosted() ) {
			$userName = $this->getVal( 'username', '' );
			$this->forgetUser( $userName );
		}
	}

	private function forgetUser( string $userName ) {
		$user = User::newFromName( $userName );

		if ( !( $user instanceof User ) || $user->isAnon() ) {
			$this->setVal( 'message', 'Invalid username' );
		} else {
			$userId = $user->getId();
			$this->setVal( 'message', 'Request to forget ' . $userName . ' with id=' . $userId . ' sent' );
			F::app()->sendRequest(
				'RemoveUserDataController',
				'removeUserData',
				[ 'userId' => $userId ]
			);
		}
	}
}