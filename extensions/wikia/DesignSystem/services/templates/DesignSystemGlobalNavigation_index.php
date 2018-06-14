<div class="wds-global-navigation-wrapper">
	<div class="wds-global-navigation wds-search-is-always-visible">
		<div class="wds-global-navigation__content-container">
			<div class="wds-global-navigation__content-bar-left">
				<a
					href="<?= Sanitizer::encodeAttribute( $model['logo']['href'] ); ?>"
					class="wds-global-navigation__logo"
					data-tracking-label="<?= Sanitizer::encodeAttribute( $model['logo']['tracking_label'] ); ?>">
					<?= DesignSystemHelper::renderSvg( $model['logo']['image-data']['name'], 'wds-global-navigation__logo-image' ); ?>
				</a>
				<?= $app->renderPartial( 'DesignSystemGlobalNavigationService', 'mainNavigation',
					[ 'model' => $model['main_navigation'] ] ); ?>
			</div>
			<div class="wds-global-navigation__content-bar-right">
				<?= $app->renderPartial( 'DesignSystemGlobalNavigationService', 'search',
					[ 'model' => $model['search'] ] ); ?>
				<?php if ( !empty( $model['user'] ) ): ?>
					<?= $app->renderPartial( 'DesignSystemGlobalNavigationService', 'userMenu',
						[ 'model' => $model['user'] ] ); ?>
					<?= $app->renderView( 'DesignSystemGlobalNavigationOnSiteNotificationsService', 'index' ); ?>
					<?= $app->renderPartial( 'DesignSystemGlobalNavigationWallNotificationsService', 'index' ); ?>
				<?php endif; ?>
				<?php if ( !empty( $model['anon'] ) ): ?>
					<?= $app->renderPartial( 'DesignSystemGlobalNavigationService', 'anonMenu',
						[ 'model' => $model['anon'] ] ); ?>
				<?php endif; ?>
				<div class="wds-global-navigation__start-a-wiki">
					<?= $app->renderPartial( 'DesignSystemGlobalNavigationService', 'linkButton',
						[ 'model' => $model['create_wiki'] ] ); ?>
				</div>
			</div>
		</div>
	</div>
</div>
