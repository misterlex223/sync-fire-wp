<?php
/**
 * WP-CLI SyncFire Commands
 *
 * Command-line interface for managing SyncFire WordPress-Firestore synchronization.
 *
 * @package SyncFire
 * @version 1.0.0
 */

namespace SyncFire\CLI;

use WP_CLI;
use WP_CLI_Command;
use SyncFire_Options;
use SyncFire_Firestore;
use SyncFire_Taxonomy_Sync;
use SyncFire_Post_Type_Sync;

class SyncFireCommand extends WP_CLI_Command {

    /**
     * Configure Firebase settings for SyncFire
     *
     * ## OPTIONS
     *
     * [--project-id=<project-id>]
     * : Firebase project ID
     *
     * [--database-id=<database-id>]
     * : Firestore database ID (defaults to (default))
     * ---
     * default: (default)
     * ---
     *
     * [--service-account=<path>]
     * : Path to service account JSON file
     *
     * [--api-key=<api-key>]
     * : Firebase API key
     *
     * [--auth-domain=<domain>]
     * : Firebase auth domain
     *
     * [--storage-bucket=<bucket>]
     * : Firebase storage bucket
     *
     * [--emulator]
     * : Enable emulator mode
     *
     * [--emulator-host=<host>]
     * : Emulator host (default: localhost)
     *
     * [--emulator-port=<port>]
     * : Emulator port (default: 8080)
     *
     * ## EXAMPLES
     *
     *     # Configure for production
     *     wp syncfire config --project-id=my-project --service-account=/path/to/key.json
     *
     *     # Configure specific database
     *     wp syncfire config --project-id=my-project --database-id=production
     *
     *     # Configure for emulator
     *     wp syncfire config --emulator --project-id=demo-project
     *
     * @when after_wp_load
     */
    public function config($args, $assoc_args) {
        try {
            $updated = [];

            // Firebase configuration
            if (isset($assoc_args['project-id'])) {
                syncfire_update_option(SyncFire_Options::FIREBASE_PROJECT_ID, $assoc_args['project-id']);
                $updated[] = 'Project ID';
            }

            if (isset($assoc_args['database-id'])) {
                syncfire_update_option(SyncFire_Options::FIREBASE_DATABASE_ID, $assoc_args['database-id']);
                $updated[] = 'Database ID';
            }

            if (isset($assoc_args['api-key'])) {
                syncfire_update_option(SyncFire_Options::FIREBASE_API_KEY, $assoc_args['api-key']);
                $updated[] = 'API Key';
            }

            if (isset($assoc_args['auth-domain'])) {
                syncfire_update_option(SyncFire_Options::FIREBASE_AUTH_DOMAIN, $assoc_args['auth-domain']);
                $updated[] = 'Auth Domain';
            }

            if (isset($assoc_args['storage-bucket'])) {
                syncfire_update_option(SyncFire_Options::FIREBASE_STORAGE_BUCKET, $assoc_args['storage-bucket']);
                $updated[] = 'Storage Bucket';
            }

            if (isset($assoc_args['service-account'])) {
                $path = $assoc_args['service-account'];
                if (!file_exists($path)) {
                    WP_CLI::error("Service account file not found: {$path}");
                }
                $json = file_get_contents($path);
                $data = json_decode($json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    WP_CLI::error("Invalid JSON in service account file");
                }
                syncfire_update_option(SyncFire_Options::FIREBASE_SERVICE_ACCOUNT, $json);
                $updated[] = 'Service Account';
            }

            // Emulator configuration
            if (isset($assoc_args['emulator'])) {
                syncfire_update_option(SyncFire_Options::FIRESTORE_EMULATOR_ENABLED, true);
                $updated[] = 'Emulator Enabled';
            }

            if (isset($assoc_args['emulator-host'])) {
                syncfire_update_option(SyncFire_Options::FIRESTORE_EMULATOR_HOST, $assoc_args['emulator-host']);
                $updated[] = 'Emulator Host';
            }

            if (isset($assoc_args['emulator-port'])) {
                syncfire_update_option(SyncFire_Options::FIRESTORE_EMULATOR_PORT, $assoc_args['emulator-port']);
                $updated[] = 'Emulator Port';
            }

            if (empty($updated)) {
                WP_CLI::warning("No configuration options provided");
                return;
            }

            WP_CLI::success("Updated: " . implode(', ', $updated));

        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * Show current SyncFire configuration
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     wp syncfire status
     *     wp syncfire status --format=json
     *
     * @when after_wp_load
     */
    public function status($args, $assoc_args) {
        $format = $assoc_args['format'] ?? 'table';

        $config = [
            'Firebase Project ID' => syncfire_get_option(SyncFire_Options::FIREBASE_PROJECT_ID, 'Not configured'),
            'Database ID' => syncfire_get_option(SyncFire_Options::FIREBASE_DATABASE_ID, '(default)'),
            'Emulator Mode' => syncfire_get_option(SyncFire_Options::FIRESTORE_EMULATOR_ENABLED, false) ? 'Enabled' : 'Disabled',
            'Emulator Host' => syncfire_get_option(SyncFire_Options::FIRESTORE_EMULATOR_HOST, 'localhost'),
            'Emulator Port' => syncfire_get_option(SyncFire_Options::FIRESTORE_EMULATOR_PORT, '8080'),
        ];

        $taxonomies = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, []);
        $post_types = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, []);

        $config['Synced Taxonomies'] = empty($taxonomies) ? 'None' : implode(', ', $taxonomies);
        $config['Synced Post Types'] = empty($post_types) ? 'None' : implode(', ', $post_types);

        if ($format === 'json') {
            WP_CLI::line(json_encode($config, JSON_PRETTY_PRINT));
        } elseif ($format === 'yaml') {
            WP_CLI::line(yaml_emit($config));
        } else {
            $items = [];
            foreach ($config as $key => $value) {
                $items[] = ['Setting' => $key, 'Value' => $value];
            }
            WP_CLI\Utils\format_items('table', $items, ['Setting', 'Value']);
        }
    }

    /**
     * Enable synchronization for taxonomies
     *
     * ## OPTIONS
     *
     * <taxonomies>...
     * : One or more taxonomy slugs to sync
     *
     * [--all]
     * : Enable sync for all registered taxonomies
     *
     * ## EXAMPLES
     *
     *     wp syncfire taxonomy enable category post_tag
     *     wp syncfire taxonomy enable movie-genre
     *     wp syncfire taxonomy enable --all
     *
     * @when after_wp_load
     */
    public function taxonomy($args, $assoc_args) {
        $subcommand = array_shift($args);

        if ($subcommand === 'enable') {
            $this->taxonomy_enable($args, $assoc_args);
        } elseif ($subcommand === 'disable') {
            $this->taxonomy_disable($args, $assoc_args);
        } elseif ($subcommand === 'list') {
            $this->taxonomy_list($args, $assoc_args);
        } elseif ($subcommand === 'sync') {
            $this->taxonomy_sync($args, $assoc_args);
        } else {
            WP_CLI::error("Unknown subcommand: {$subcommand}. Use: enable, disable, list, sync");
        }
    }

    /**
     * Enable taxonomy synchronization
     */
    private function taxonomy_enable($args, $assoc_args) {
        $current = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, []);

        if (isset($assoc_args['all'])) {
            $taxonomies = get_taxonomies(['public' => true], 'names');
            $to_add = array_values($taxonomies);
        } else {
            $to_add = $args;
        }

        foreach ($to_add as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                WP_CLI::warning("Taxonomy does not exist: {$taxonomy}");
                continue;
            }
            if (!in_array($taxonomy, $current)) {
                $current[] = $taxonomy;
                WP_CLI::line("Enabled sync for taxonomy: {$taxonomy}");
            } else {
                WP_CLI::line("Already enabled: {$taxonomy}");
            }
        }

        syncfire_update_option(SyncFire_Options::TAXONOMIES_TO_SYNC, $current);
        WP_CLI::success("Taxonomy synchronization updated");
    }

    /**
     * Disable taxonomy synchronization
     */
    private function taxonomy_disable($args, $assoc_args) {
        $current = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, []);

        foreach ($args as $taxonomy) {
            $key = array_search($taxonomy, $current);
            if ($key !== false) {
                unset($current[$key]);
                WP_CLI::line("Disabled sync for taxonomy: {$taxonomy}");
            } else {
                WP_CLI::line("Not enabled: {$taxonomy}");
            }
        }

        syncfire_update_option(SyncFire_Options::TAXONOMIES_TO_SYNC, array_values($current));
        WP_CLI::success("Taxonomy synchronization updated");
    }

    /**
     * List taxonomies and their sync status
     */
    private function taxonomy_list($args, $assoc_args) {
        $synced = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, []);
        $all_taxonomies = get_taxonomies(['public' => true], 'objects');

        $items = [];
        foreach ($all_taxonomies as $taxonomy) {
            $items[] = [
                'slug' => $taxonomy->name,
                'label' => $taxonomy->label,
                'synced' => in_array($taxonomy->name, $synced) ? 'Yes' : 'No',
            ];
        }

        WP_CLI\Utils\format_items('table', $items, ['slug', 'label', 'synced']);
    }

    /**
     * Manually trigger taxonomy sync to Firestore
     */
    private function taxonomy_sync($args, $assoc_args) {
        if (empty($args)) {
            WP_CLI::error("Please specify taxonomy slugs to sync, or use --all");
        }

        $taxonomies_to_sync = $args;
        if (isset($assoc_args['all'])) {
            $taxonomies_to_sync = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, []);
        }

        foreach ($taxonomies_to_sync as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                WP_CLI::warning("Taxonomy does not exist: {$taxonomy}");
                continue;
            }

            WP_CLI::line("Syncing taxonomy: {$taxonomy}");
            $sync = new SyncFire_Taxonomy_Sync();
            $sync->sync_taxonomy($taxonomy);
            WP_CLI::success("Synced: {$taxonomy}");
        }
    }

    /**
     * Enable synchronization for post types
     *
     * ## OPTIONS
     *
     * <post-types>...
     * : One or more post type slugs to sync
     *
     * [--all]
     * : Enable sync for all public post types
     *
     * [--fields=<fields>]
     * : Comma-separated list of fields to sync
     *
     * ## EXAMPLES
     *
     *     wp syncfire post-type enable post page
     *     wp syncfire post-type enable movie --fields=title,content,acf
     *     wp syncfire post-type enable --all
     *
     * @when after_wp_load
     */
    public function post_type($args, $assoc_args) {
        $subcommand = array_shift($args);

        if ($subcommand === 'enable') {
            $this->post_type_enable($args, $assoc_args);
        } elseif ($subcommand === 'disable') {
            $this->post_type_disable($args, $assoc_args);
        } elseif ($subcommand === 'list') {
            $this->post_type_list($args, $assoc_args);
        } elseif ($subcommand === 'sync') {
            $this->post_type_sync($args, $assoc_args);
        } elseif ($subcommand === 'fields') {
            $this->post_type_fields($args, $assoc_args);
        } else {
            WP_CLI::error("Unknown subcommand: {$subcommand}. Use: enable, disable, list, sync, fields");
        }
    }

    /**
     * Enable post type synchronization
     */
    private function post_type_enable($args, $assoc_args) {
        $current = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, []);

        if (isset($assoc_args['all'])) {
            $post_types = get_post_types(['public' => true], 'names');
            $to_add = array_values($post_types);
        } else {
            $to_add = $args;
        }

        foreach ($to_add as $post_type) {
            if (!post_type_exists($post_type)) {
                WP_CLI::warning("Post type does not exist: {$post_type}");
                continue;
            }
            if (!in_array($post_type, $current)) {
                $current[] = $post_type;
                WP_CLI::line("Enabled sync for post type: {$post_type}");
            } else {
                WP_CLI::line("Already enabled: {$post_type}");
            }
        }

        syncfire_update_option(SyncFire_Options::POST_TYPES_TO_SYNC, $current);

        // Handle fields if specified
        if (isset($assoc_args['fields'])) {
            $fields = explode(',', $assoc_args['fields']);
            $current_fields = syncfire_get_option(SyncFire_Options::POST_TYPE_FIELDS, []);
            foreach ($to_add as $post_type) {
                $current_fields[$post_type] = $fields;
            }
            syncfire_update_option(SyncFire_Options::POST_TYPE_FIELDS, $current_fields);
            WP_CLI::line("Set fields: " . implode(', ', $fields));
        }

        WP_CLI::success("Post type synchronization updated");
    }

    /**
     * Disable post type synchronization
     */
    private function post_type_disable($args, $assoc_args) {
        $current = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, []);

        foreach ($args as $post_type) {
            $key = array_search($post_type, $current);
            if ($key !== false) {
                unset($current[$key]);
                WP_CLI::line("Disabled sync for post type: {$post_type}");
            } else {
                WP_CLI::line("Not enabled: {$post_type}");
            }
        }

        syncfire_update_option(SyncFire_Options::POST_TYPES_TO_SYNC, array_values($current));
        WP_CLI::success("Post type synchronization updated");
    }

    /**
     * List post types and their sync status
     */
    private function post_type_list($args, $assoc_args) {
        $synced = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, []);
        $all_post_types = get_post_types(['public' => true], 'objects');

        $items = [];
        foreach ($all_post_types as $post_type) {
            $items[] = [
                'slug' => $post_type->name,
                'label' => $post_type->label,
                'synced' => in_array($post_type->name, $synced) ? 'Yes' : 'No',
            ];
        }

        WP_CLI\Utils\format_items('table', $items, ['slug', 'label', 'synced']);
    }

    /**
     * Manually trigger post type sync to Firestore
     */
    private function post_type_sync($args, $assoc_args) {
        if (empty($args)) {
            WP_CLI::error("Please specify post type slugs to sync, or use --all");
        }

        $post_types_to_sync = $args;
        if (isset($assoc_args['all'])) {
            $post_types_to_sync = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, []);
        }

        foreach ($post_types_to_sync as $post_type) {
            if (!post_type_exists($post_type)) {
                WP_CLI::warning("Post type does not exist: {$post_type}");
                continue;
            }

            WP_CLI::line("Syncing post type: {$post_type}");
            $sync = new SyncFire_Post_Type_Sync();

            // Get all posts of this type
            $posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'any',
            ]);

            $count = 0;
            foreach ($posts as $post) {
                $sync->sync_post($post->ID, $post);
                $count++;
                if ($count % 10 === 0) {
                    WP_CLI::line("  Synced {$count} posts...");
                }
            }
            WP_CLI::success("Synced {$count} posts for: {$post_type}");
        }
    }

    /**
     * Manage fields for post type synchronization
     */
    private function post_type_fields($args, $assoc_args) {
        if (empty($args)) {
            WP_CLI::error("Please specify a post type slug");
        }

        $post_type = $args[0];
        if (!post_type_exists($post_type)) {
            WP_CLI::error("Post type does not exist: {$post_type}");
        }

        $current_fields = syncfire_get_option(SyncFire_Options::POST_TYPE_FIELDS, []);

        if (isset($assoc_args['set'])) {
            $fields = explode(',', $assoc_args['set']);
            $current_fields[$post_type] = $fields;
            syncfire_update_option(SyncFire_Options::POST_TYPE_FIELDS, $current_fields);
            WP_CLI::success("Set fields for {$post_type}: " . implode(', ', $fields));
        } elseif (isset($assoc_args['add'])) {
            $fields = explode(',', $assoc_args['add']);
            if (!isset($current_fields[$post_type])) {
                $current_fields[$post_type] = [];
            }
            $current_fields[$post_type] = array_unique(array_merge($current_fields[$post_type], $fields));
            syncfire_update_option(SyncFire_Options::POST_TYPE_FIELDS, $current_fields);
            WP_CLI::success("Added fields to {$post_type}: " . implode(', ', $fields));
        } elseif (isset($assoc_args['remove'])) {
            $fields = explode(',', $assoc_args['remove']);
            if (isset($current_fields[$post_type])) {
                $current_fields[$post_type] = array_diff($current_fields[$post_type], $fields);
                syncfire_update_option(SyncFire_Options::POST_TYPE_FIELDS, $current_fields);
                WP_CLI::success("Removed fields from {$post_type}: " . implode(', ', $fields));
            }
        } else {
            // List current fields
            $fields = $current_fields[$post_type] ?? [];
            if (empty($fields)) {
                WP_CLI::line("No fields configured for: {$post_type}");
            } else {
                WP_CLI::line("Fields for {$post_type}:");
                foreach ($fields as $field) {
                    WP_CLI::line("  - {$field}");
                }
            }
        }
    }

    /**
     * Test Firestore connection
     *
     * ## OPTIONS
     *
     * [--verbose]
     * : Show detailed connection information
     *
     * ## EXAMPLES
     *
     *     wp syncfire test
     *     wp syncfire test --verbose
     *
     * @when after_wp_load
     */
    public function test($args, $assoc_args) {
        $verbose = isset($assoc_args['verbose']);

        WP_CLI::line("Testing Firestore connection...");

        try {
            $project_id = syncfire_get_option(SyncFire_Options::FIREBASE_PROJECT_ID, '');
            $database_id = syncfire_get_option(SyncFire_Options::FIREBASE_DATABASE_ID, '(default)');
            $emulator_enabled = syncfire_get_option(SyncFire_Options::FIRESTORE_EMULATOR_ENABLED, false);

            if (empty($project_id)) {
                WP_CLI::error("Firebase project ID not configured");
            }

            if ($verbose) {
                WP_CLI::line("Project ID: {$project_id}");
                WP_CLI::line("Database ID: {$database_id}");
                WP_CLI::line("Emulator Mode: " . ($emulator_enabled ? 'Enabled' : 'Disabled'));
            }

            $firestore = new SyncFire_Firestore();

            // Try a simple operation
            $test_collection = 'test-connection';
            $test_doc = [
                'timestamp' => time(),
                'message' => 'Connection test from SyncFire CLI',
            ];

            $firestore->set_document($test_collection, 'test-' . time(), $test_doc);
            WP_CLI::success("Connection successful!");

            if ($verbose) {
                WP_CLI::line("Successfully wrote test document to collection: {$test_collection}");
            }

        } catch (\Exception $e) {
            WP_CLI::error("Connection failed: " . $e->getMessage());
        }
    }

    /**
     * Import schema from ignis-schema-wp and configure sync
     *
     * ## OPTIONS
     *
     * <type>
     * : Type of schema (post-type or taxonomy)
     *
     * <slug>
     * : Schema slug
     *
     * [--auto-sync]
     * : Automatically enable synchronization
     *
     * [--sync-now]
     * : Sync existing data immediately
     *
     * ## EXAMPLES
     *
     *     wp syncfire import post-type movie --auto-sync
     *     wp syncfire import taxonomy movie-genre --auto-sync --sync-now
     *
     * @when after_wp_load
     */
    public function import($args, $assoc_args) {
        list($type, $slug) = $args;

        if (!in_array($type, ['post-type', 'taxonomy'])) {
            WP_CLI::error("Type must be 'post-type' or 'taxonomy'");
        }

        // Check if ignis-schema-wp is available
        if (!class_exists('WordPressSchemaSystem\\SchemaParser')) {
            WP_CLI::error("ignis-schema-wp plugin is not active. Please activate it first.");
        }

        try {
            $schema_dir = WP_CONTENT_DIR . '/schemas/' . ($type === 'taxonomy' ? 'taxonomies' : 'post-types');
            $schema_file = $schema_dir . '/' . $slug . '.yaml';

            if (!file_exists($schema_file)) {
                WP_CLI::error("Schema file not found: {$schema_file}");
            }

            WP_CLI::line("Found schema: {$slug}");

            // Enable auto-sync if requested
            if (isset($assoc_args['auto-sync'])) {
                if ($type === 'taxonomy') {
                    $current = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, []);
                    if (!in_array($slug, $current)) {
                        $current[] = $slug;
                        syncfire_update_option(SyncFire_Options::TAXONOMIES_TO_SYNC, $current);
                        WP_CLI::success("Enabled sync for taxonomy: {$slug}");
                    }
                } else {
                    $current = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, []);
                    if (!in_array($slug, $current)) {
                        $current[] = $slug;
                        syncfire_update_option(SyncFire_Options::POST_TYPES_TO_SYNC, $current);
                        WP_CLI::success("Enabled sync for post type: {$slug}");
                    }
                }
            }

            // Sync now if requested
            if (isset($assoc_args['sync-now'])) {
                if ($type === 'taxonomy') {
                    $this->taxonomy_sync([$slug], []);
                } else {
                    $this->post_type_sync([$slug], []);
                }
            }

            WP_CLI::success("Import complete for: {$slug}");

        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * Show comprehensive sync statistics
     *
     * ## EXAMPLES
     *
     *     wp syncfire stats
     *
     * @when after_wp_load
     */
    public function stats($args, $assoc_args) {
        WP_CLI::line("SyncFire Synchronization Statistics");
        WP_CLI::line("====================================");
        WP_CLI::line("");

        // Taxonomies
        $synced_taxonomies = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, []);
        WP_CLI::line("Synced Taxonomies: " . count($synced_taxonomies));
        foreach ($synced_taxonomies as $taxonomy) {
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
            WP_CLI::line("  - {$taxonomy}: " . count($terms) . " terms");
        }

        WP_CLI::line("");

        // Post Types
        $synced_post_types = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, []);
        WP_CLI::line("Synced Post Types: " . count($synced_post_types));
        foreach ($synced_post_types as $post_type) {
            $count = wp_count_posts($post_type);
            $total = $count->publish + $count->draft + $count->pending;
            WP_CLI::line("  - {$post_type}: {$total} posts");
        }

        WP_CLI::line("");

        // Configuration
        $project_id = syncfire_get_option(SyncFire_Options::FIREBASE_PROJECT_ID, 'Not configured');
        $database_id = syncfire_get_option(SyncFire_Options::FIREBASE_DATABASE_ID, '(default)');
        $emulator = syncfire_get_option(SyncFire_Options::FIRESTORE_EMULATOR_ENABLED, false) ? 'Yes' : 'No';

        WP_CLI::line("Configuration:");
        WP_CLI::line("  Project ID: {$project_id}");
        WP_CLI::line("  Database ID: {$database_id}");
        WP_CLI::line("  Emulator Mode: {$emulator}");
    }
}

// Register command
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('syncfire', 'SyncFire\\CLI\\SyncFireCommand');
}
