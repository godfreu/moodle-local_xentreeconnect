<?php
// Pure PHP (no Moodle API) so the visibility rules can be unit tested in isolation.

namespace local_xentreeconnect;

/**
 * Decides where and for whom the "Connect with Xentree" button is shown.
 *
 * @package    local_xentreeconnect
 */
final class button_policy {
    public const BUTTON = 'button';
    public const ADMIN_NOTICE = 'admin_notice';
    public const HIDDEN = 'hidden';

    /** Pages the admin can enable the button on. */
    public const PLACEMENTS = ['dashboard', 'course', 'login'];

    public const MAX_LABEL_LENGTH = 80;

    /**
     * Regular users only ever see a working button; an incomplete configuration is reported to site admins only.
     */
    public static function decide(bool $configured, bool $isadmin): string {
        if ($configured) {
            return self::BUTTON;
        }
        return $isadmin ? self::ADMIN_NOTICE : self::HIDDEN;
    }

    /**
     * Maps a Moodle page type to one of {@see self::PLACEMENTS}, or null for pages that never show the button.
     */
    public static function placement_for_pagetype(string $pagetype): ?string {
        if ($pagetype === 'my-index') {
            return 'dashboard';
        }
        if ($pagetype === 'login-index') {
            return 'login';
        }
        if (strpos($pagetype, 'course-view') === 0) {
            return 'course';
        }
        return null;
    }

    /**
     * Parses the saved multi-checkbox setting (comma separated keys) into known placements.
     *
     * @param mixed $raw Value of get_config(), normally a string, false when unset.
     * @return string[]
     */
    public static function enabled_placements($raw): array {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $enabled = array_map('trim', explode(',', $raw));
        return array_values(array_intersect(self::PLACEMENTS, $enabled));
    }

    public static function should_render(string $pagetype, array $enabled): bool {
        $placement = self::placement_for_pagetype($pagetype);
        return $placement !== null && in_array($placement, $enabled, true);
    }

    /**
     * Admin-defined button text, falling back to the language string default when empty.
     */
    public static function label($custom, string $default): string {
        $custom = is_string($custom) ? trim($custom) : '';
        if ($custom === '') {
            return $default;
        }
        return function_exists('mb_substr') ? mb_substr($custom, 0, self::MAX_LABEL_LENGTH) : substr($custom, 0, self::MAX_LABEL_LENGTH);
    }
}
