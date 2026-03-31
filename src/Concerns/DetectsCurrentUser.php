<?php

namespace WPChangeLedger\Concerns;

trait DetectsCurrentUser {

    /**
     * Returns [userId, userLogin] for the currently logged-in user,
     * or [null, null] when no user context is available (e.g. automatic updates).
     *
     * @return array{0: int|null, 1: string|null}
     */
    private function currentUser(): array {
        $user = wp_get_current_user();
        if ($user->ID === 0) {
            return [null, null];
        }
        return [$user->ID, $user->user_login];
    }
}
