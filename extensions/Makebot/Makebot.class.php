<?php

class MakeBot extends SpecialPage {

	var $target = '';

	/**
	 * Constructor
	 */
	function MakeBot() {
		wfLoadExtensionMessages('Makebot');
		SpecialPage::SpecialPage( 'Makebot', 'makebot' );
	}

	/**
	 * Main execution function
	 * @param $par Parameters passed to the page
	 */
	function execute( $par ) {
		global $wgRequest, $wgOut, $wgMakeBotPrivileged, $wgUser;

		if( !$wgUser->isAllowed( 'makebot' ) ) {
			$wgOut->permissionRequired( 'makebot' );
			return;
		}

		$this->setHeaders();

		$this->target = $par
						? $par
						: $wgRequest->getText( 'username', '' );

		$wgOut->addWikiText( wfMsgNoTrans( 'makebot-header' ) );
		$wgOut->addHTML( $this->makeSearchForm() );

		if( $this->target != '' ) {
			//$wgOut->addHTML( Xml::element( 'p', NULL, NULL ) );
			$user = User::newFromName( $this->target );
			if( is_object( $user ) && !is_null( $user ) ) {
				global $wgVersion;
				if( version_compare( $wgVersion, '1.9alpha' ) < 0 ) {
					$user->loadFromDatabase();
				} else {
					$user->load();
				}
				# Valid username, check existence
				if( $user->getID() ) {
					# Exists; check current privileges
					$canBecomeBot = $this->canBecomeBot( $user );
					if( $wgRequest->getCheck( 'dosearch' ) || !$wgRequest->wasPosted() || !$wgUser->matchEditToken( $wgRequest->getVal( 'token' ), 'makebot' ) ) {
						# Exists, check botness
						if( in_array( 'bot', $user->getGroups() ) ) {
							# Has a bot flag
							$wgOut->addWikiText( wfMsg( 'makebot-isbot', $user->getName() ) );
							$wgOut->addHTML( $this->makeGrantForm( MW_MAKEBOT_REVOKE ) );
						} elseif ( $canBecomeBot ) {
							# Not a bot; show the grant form
							$wgOut->addWikiText( wfMsg( 'makebot-notbot', $user->getName() ) );
							$wgOut->addHTML( $this->makeGrantForm( MW_MAKEBOT_GRANT ) );
						} else {
							# User account is privileged and can't be given a bot flag
							$wgOut->addWikiText( wfMsg( 'makebot-privileged', $user->getName() ) );
						}							
					} elseif( $canBecomeBot && $wgRequest->getCheck( 'grant' ) ) {
						# Grant the flag
						$user->addGroup( 'bot' );
						$this->addLogItem( 'grant', $user, trim( $wgRequest->getText( 'comment' ) ) );
						$wgOut->addWikiText( wfMsg( 'makebot-granted', $user->getName() ) );
					} elseif( $wgRequest->getCheck( 'revoke' ) ) {
						# Revoke the flag, also if bot has got sysop/bureaucrat status in the meantime
						$user->removeGroup( 'bot' );
						$this->addLogItem( 'revoke', $user, trim( $wgRequest->getText( 'comment' ) ) );
						$wgOut->addWikiText( wfMsg( 'makebot-revoked', $user->getName() ) );
					}
					# Show log entries
					$this->showLogEntries( $user, 'makebot' );
					$this->showLogEntries( $user, 'rights' );
				} else {
					# Doesn't exist
					$wgOut->addWikiText( wfMsg( 'nosuchusershort', htmlspecialchars( $this->target ) ) );
				}
			} else {
				# Invalid username
				$wgOut->addWikiText( wfMsg( 'noname' ) );
			}
		}
	}

	/**
	 * Produce a form to allow for entering a username
	 * @return string
	 */
	function makeSearchForm() {
		$thisTitle = Title::makeTitle( NS_SPECIAL, $this->getName() );
		$form  = Xml::openElement( 'form', array( 'method' => 'post', 'action' => $thisTitle->getLocalUrl() ) );
		$form .= Xml::element( 'label', array( 'for' => 'username' ), wfMsg( 'makebot-username' ) ) . ' ';
		$form .= Xml::element( 'input', array( 'type' => 'text', 'name' => 'username', 'id' => 'username', 'value' => $this->target ) ) . ' ';
		$form .= Xml::element( 'input', array( 'type' => 'submit', 'name' => 'dosearch', 'value' => wfMsg( 'makebot-search' ) ) );
		$form .= Xml::closeElement( 'form' );
		return $form;
	}

	/**
	 * Produce a form to allow granting or revocation of the flag
	 * @param $type Either MW_MAKEBOT_GRANT or MW_MAKEBOT_REVOKE
	 *				where the trailing name refers to what's enabled
	 * @return string
	 */
	function makeGrantForm( $type ) {
		global $wgUser;
		$thisTitle = Title::makeTitle( NS_SPECIAL, $this->getName() );
		if( $type == MW_MAKEBOT_GRANT ) {
			$grant = true;
			$revoke = false;
		} else {
			$grant = false;
			$revoke = true;
		}

		# Start the table
		$form  = Xml::openElement( 'form', array( 'method' => 'post', 'action' => $thisTitle->getLocalUrl() ) );
		$form .= Xml::openElement( 'table' ) . Xml::openElement( 'tr' );
		# Grant/revoke buttons
		$form .= Xml::element( 'td', array( 'align' => 'right' ), wfMsg( 'makebot-change' ) );
		$form .= Xml::openElement( 'td' );
		foreach( array( 'grant', 'revoke' ) as $button ) {
			$attribs = array( 'type' => 'submit', 'name' => $button, 'value' => wfMsg( 'makebot-' . $button ) );
			if( !$$button )
				$attribs['disabled'] = 'disabled';
			$form .= Xml::element( 'input', $attribs );
		}
		$form .= Xml::closeElement( 'td' ) . Xml::closeElement( 'tr' );
		# Comment field
		$form .= Xml::openElement( 'tr' );
		$form .= Xml::openElement( 'td', array( 'align' => 'right' ) );
		$form .= Xml::element( 'label', array( 'for' => 'comment' ), wfMsg( 'makebot-comment' ) );
		$form .= Xml::closeElement( 'td' );
		$form .= Xml::openElement( 'td' );
		$form .= Xml::element( 'input', array( 'type' => 'text', 'name' => 'comment', 'id' => 'comment', 'size' => 45, 'maxlength' => 255 ) );
		$form .= Xml::closeElement( 'td' ) . Xml::closeElement( 'tr' );
		# End table
		$form .= Xml::closeElement( 'table' );
		# Username
		$form .= Xml::element( 'input', array( 'type' => 'hidden', 'name' => 'username', 'value' => $this->target ) );
		# Edit token
		$form .= Xml::element( 'input', array( 'type' => 'hidden', 'name' => 'token', 'value' => $wgUser->editToken( 'makebot' ) ) );
		$form .= Xml::closeElement( 'form' );
		return $form;
	}

	/**
	 * Add logging entries for the specified action
	 * @param $type Either grant or revoke
	 * @param $target User receiving the action
	 * @param $comment Comment for the log item
	 */
	function addLogItem( $type, &$target, $comment = '' ) {
		$log = new LogPage( 'makebot' );
		$targetPage = $target->getUserPage();
		$log->addEntry( $type, $targetPage, $comment );
	}

	/**
	 * Show the bot status log entries for the specified user
	 * @param $user User to show the log for
	 */
	function showLogEntries( &$user, $logtype = 'makebot' ) {
		global $wgOut;
		$title = $user->getUserPage();
		$wgOut->addHTML( Xml::element( 'h2', NULL, htmlspecialchars( LogPage::logName( $logtype ) ) ) );
		$logViewer = new LogViewer( new LogReader( new FauxRequest( array( 'page' => $title->getPrefixedText(), 'type' => $logtype ) ) ) );
		$logViewer->showList( $wgOut );
	}

	/**
	 * Can the specified user be given a bot flag?
	 * Check existing privileges and configuration
	 * @param $user User to check
	 * @return bool True if permitted
	 */
	function canBecomeBot( &$user ) {
		global $wgMakeBotPrivileged;
		return $wgMakeBotPrivileged ||
				( !in_array( 'sysop', $user->getGroups() ) &&
				  !in_array( 'bureaucrat', $user->getGroups() ) );
	}
}
