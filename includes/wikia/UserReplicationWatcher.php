<?php


class UserReplicationWatcher {

	public static function onUserSaveSettings( User $user ) {
		global $wgExternalSharedDB;

		$dbw = wfGetDB( DB_MASTER, [], $wgExternalSharedDB );
		$dbw->insert(
			'user_replication_queue',
			['user_id' => $user->getId()]
		);
	}
}
