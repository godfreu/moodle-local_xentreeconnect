<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_xentreeconnect.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_xentreeconnect_upgrade($oldversion) {
    if ($oldversion < 2026101200) {
        // 0.1.1: the JWKS is now derived from the private key, and placeholder defaults are gone.
        unset_config('jwksjson', 'local_xentreeconnect');
        if (get_config('local_xentreeconnect', 'issuer') === 'https://moodle.example.com') {
            unset_config('issuer', 'local_xentreeconnect');
        }
        $pem = get_config('local_xentreeconnect', 'privatekeypem');
        if (is_string($pem) && preg_replace('/\s+/', '', $pem) === '-----BEGINPRIVATEKEY----------ENDPRIVATEKEY-----') {
            unset_config('privatekeypem', 'local_xentreeconnect');
        }
        upgrade_plugin_savepoint(true, 2026101200, 'local', 'xentreeconnect');
    }

    if ($oldversion < 2026101201) {
        // 0.1.1 build 2: button placement hooks and launch.php. Nothing to migrate; the bump registers the hooks.
        upgrade_plugin_savepoint(true, 2026101201, 'local', 'xentreeconnect');
    }

    return true;
}
