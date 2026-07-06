<?php

namespace WCML\PostHog\Event;

use WPML\Core\Component\PostHog\Domain\Event\Event;

class EditorScopedSyncSettingChanged extends Event {

	public function getName(): string {
		return 'wcml_editor_scoped_sync_setting_changed';
	}
}
