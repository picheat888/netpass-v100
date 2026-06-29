<?php

namespace Config;

use App\Services\ActivityLog;
use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

// บันทึกเวลา Sign in / Sign out (Shield ยิง event พร้อม user)
Events::on('login', static function ($user): void {
    ActivityLog::record('auth.login', [
        'actor_id'       => (int) $user->id,
        'actor_name'     => ActivityLog::displayName($user),
        'actor_username' => $user->username,
        'actor_role'     => $user->inGroup('admin') ? 'admin' : 'user',
        'target_type'    => 'member',
        'target_id'      => (int) $user->id,
        'target_label'   => ActivityLog::displayName($user),
    ]);
});
Events::on('logout', static function ($user): void {
    ActivityLog::record('auth.logout', [
        'actor_id'       => (int) $user->id,
        'actor_name'     => ActivityLog::displayName($user),
        'actor_username' => $user->username,
        'actor_role'     => $user->inGroup('admin') ? 'admin' : 'user',
        'target_type'    => 'member',
        'target_id'      => (int) $user->id,
        'target_label'   => ActivityLog::displayName($user),
    ]);
});

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        $value = ini_get('zlib.output_compression');

        if (filter_var($value, FILTER_VALIDATE_BOOLEAN) || (int) $value > 0) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});
