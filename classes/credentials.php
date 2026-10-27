<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Finds the private key and the webhook secrets, preferring places that are safer than the database.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Pure PHP (no Moodle API) so the lookup order can be unit tested in isolation.

namespace local_xentreeconnect;

/**
 * Finds the private key and the webhook secrets, preferring places that are safer than the database.
 *
 * Order for each item: a value forced in config.php ($CFG->forced_plugin_settings), then a file outside the web
 * root (its path is a setting that can itself be forced), then the value saved in the Moodle database.
 *
 * @package    local_xentreeconnect
 */
final class credentials {
    public const FROM_CONFIG = 'config.php';
    public const FROM_FILE = 'file';
    public const FROM_DATABASE = 'database';
    public const NONE = 'none';

    /** Item (setting holding the value) => setting holding the path of a file that contains it. */
    public const ITEMS = [
        'privatekeypem' => 'privatekeyfile',
        'webhooksecret' => 'webhooksecretfile',
        'webhooksecretprev' => 'webhooksecretprevfile',
    ];

    /** @var array Settings forced in config.php. */
    private array $forced;
    /** @var array Settings saved in the database. */
    private array $stored;
    /** @var callable */
    private $reader;
    private ?string $webroot;

    /**
     * @param array $forced Settings forced in config.php.
     * @param array $stored Settings saved in the database.
     * @param callable|null $reader fn(string $path): ?string returning the file content, null when unreadable.
     * @param string|null $webroot Directory served by the web server; files inside it are refused.
     */
    public function __construct(array $forced, array $stored, ?callable $reader = null, ?string $webroot = null) {
        $this->forced = $forced;
        $this->stored = $stored;
        $this->reader = $reader ?? static function (string $path): ?string {
            if (!is_file($path) || !is_readable($path)) {
                return null;
            }
            $content = file_get_contents($path);
            return $content === false ? null : $content;
        };
        $this->webroot = $webroot;
    }

    /**
     * Builds the resolver from this Moodle site's configuration.
     */
    public static function from_site_config(): self {
        global $CFG;
        $forced = [];
        if (!empty($CFG->forced_plugin_settings['local_xentreeconnect']) &&
                is_array($CFG->forced_plugin_settings['local_xentreeconnect'])) {
            $forced = $CFG->forced_plugin_settings['local_xentreeconnect'];
        }
        $stored = (array) get_config('local_xentreeconnect');
        $webroot = !empty($CFG->dirroot) ? (is_dir($CFG->dirroot . '/public') ? $CFG->dirroot . '/public' : $CFG->dirroot) : null;
        return new self($forced, $stored, null, $webroot);
    }

    /**
     * @param string $item One of the keys of {@see self::ITEMS}.
     * @return array{value:string,source:string,problem:?string} Value ('' when none), where it came from, and a
     *         problem code ('insidewebroot', 'unreadable', 'empty') when a configured file could not be used.
     */
    public function resolve(string $item): array {
        if (!isset(self::ITEMS[$item])) {
            throw new \InvalidArgumentException('Unknown credential ' . $item);
        }
        $forced = $this->text($this->forced[$item] ?? null);
        if ($forced !== '') {
            return ['value' => $forced, 'source' => self::FROM_CONFIG, 'problem' => null];
        }

        $problem = null;
        $path = $this->text($this->forced[self::ITEMS[$item]] ?? ($this->stored[self::ITEMS[$item]] ?? null));
        if ($path !== '') {
            if ($this->is_inside_webroot($path)) {
                $problem = 'insidewebroot';
            } else {
                $content = ($this->reader)($path);
                if ($content === null) {
                    $problem = 'unreadable';
                } else if (trim($content) === '') {
                    $problem = 'empty';
                } else {
                    return ['value' => trim($content), 'source' => self::FROM_FILE, 'problem' => null];
                }
            }
        }

        $stored = $this->text($this->stored[$item] ?? null);
        if ($stored !== '') {
            return ['value' => $stored, 'source' => self::FROM_DATABASE, 'problem' => $problem];
        }
        return ['value' => '', 'source' => self::NONE, 'problem' => $problem];
    }

    /** Convenience: only the value. */
    public function value(string $item): string {
        return $this->resolve($item)['value'];
    }

    private function text($value): string {
        return is_string($value) ? trim($value) : '';
    }

    private function is_inside_webroot(string $path): bool {
        if ($this->webroot === null || $this->webroot === '') {
            return false;
        }
        $file = $this->normalise($path);
        $root = rtrim($this->normalise($this->webroot), '/');
        return $file === $root || strpos($file, $root . '/') === 0;
    }

    private function normalise(string $path): string {
        $real = @realpath($path);
        return str_replace('\\', '/', $real !== false ? $real : $path);
    }
}
