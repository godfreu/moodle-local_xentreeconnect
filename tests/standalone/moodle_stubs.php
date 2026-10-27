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
 * Standalone Moodle class stubs for testing.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Minimal stand-ins for the Moodle Privacy API and $DB, so the privacy provider's own logic can be exercised without a
// Moodle checkout. They only mimic the calls the provider makes: this does NOT prove the real Moodle API signatures.
// Inside Moodle the real classes exist and this file does nothing (tests/privacy/provider_test.php runs there).

namespace core_privacy\local\metadata {
    if (!interface_exists(provider::class)) {
        interface provider {
        }

        class collection {
            public array $tables = [];
            public array $links = [];

            public function add_database_table(string $name, array $fields, string $summary): self {
                $this->tables[$name] = ['fields' => $fields, 'summary' => $summary];
                return $this;
            }

            public function add_external_location_link(string $name, array $fields, string $summary): self {
                $this->links[$name] = ['fields' => $fields, 'summary' => $summary];
                return $this;
            }
        }
    }
}

namespace core_privacy\local\request\plugin {
    if (!interface_exists(provider::class)) {
        interface provider {
        }
    }
}

namespace core_privacy\local\request {
    if (!interface_exists(core_userlist_provider::class)) {
        interface core_userlist_provider {
        }

        class contextlist {
            public array $queries = [];

            public function add_from_sql(string $sql, array $params): void {
                $this->queries[] = [$sql, $params];
            }
        }

        class approved_contextlist {
            public function __construct(private object $user, private string $component, private array $ids) {
            }

            public function get_user(): object {
                return $this->user;
            }

            public function get_contextids(): array {
                return $this->ids;
            }
        }

        class userlist {
            public array $queries = [];

            public function __construct(private \context $context) {
            }

            public function get_context(): \context {
                return $this->context;
            }

            public function add_from_sql(string $field, string $sql, array $params): void {
                $this->queries[] = [$field, $sql, $params];
            }
        }

        class approved_userlist {
            public function __construct(private \context $context, private string $component, private array $userids) {
            }

            public function get_context(): \context {
                return $this->context;
            }

            public function get_userids(): array {
                return $this->userids;
            }
        }

        class transform {
            public static function datetime(int $t): string {
                return gmdate('c', $t);
            }
        }

        class writer {
            public static array $exports = [];
            private static ?writer $current = null;
            private object $context;

            public static function with_context(\context $context): self {
                self::$current = new self();
                self::$current->context = $context;
                return self::$current;
            }

            public function export_data(array $path, object $data): self {
                self::$exports[] = ['context' => $this->context->id, 'path' => $path, 'data' => $data];
                return $this;
            }
        }
    }
}

namespace {
    if (!defined('CONTEXT_SYSTEM')) {
        define('CONTEXT_SYSTEM', 10);
        define('SQL_PARAMS_NAMED', 2);
    }

    if (!class_exists('context')) {
        abstract class context {
            public int $id = 0;
        }

        class context_system extends context {
            public int $id = 1;

            public static function instance(): self {
                return new self();
            }
        }

        class context_course extends context {
            public int $id = 55;
        }
    }

    if (!function_exists('get_string')) {
        function get_string(string $key, string $component = '', $a = null): string {
            return "[$component:$key]";
        }
    }

    if (!class_exists('fake_moodle_db')) {
        /** Array-backed $DB for one table: just the calls the privacy provider makes. */
        class fake_moodle_db {
            public array $rows = [];

            public function get_records(string $table, array $conditions, string $sort = ''): array {
                return array_filter($this->rows, function ($row) use ($conditions) {
                    foreach ($conditions as $field => $value) {
                        if ((string) $row->$field !== (string) $value) {
                            return false;
                        }
                    }
                    return true;
                });
            }

            public function delete_records(string $table, ?array $conditions = null): bool {
                $this->rows = $conditions === null ? [] : array_diff_key($this->rows, $this->get_records($table, $conditions));
                return true;
            }

            public function get_in_or_equal(array $items, int $type): array {
                $params = [];
                $names = [];
                foreach (array_values($items) as $i => $item) {
                    $params["p$i"] = $item;
                    $names[] = ":p$i";
                }
                return ['IN (' . implode(',', $names) . ')', $params];
            }

            public function delete_records_select(string $table, string $select, array $params): bool {
                if (!preg_match('/^userid IN \(([^)]*)\)$/', $select)) {
                    throw new \RuntimeException('unsupported select: ' . $select);
                }
                foreach ($this->rows as $id => $row) {
                    if (in_array($row->userid, array_values($params))) {
                        unset($this->rows[$id]);
                    }
                }
                return true;
            }
        }
    }
}
