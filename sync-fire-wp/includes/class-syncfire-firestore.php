<?php
/**
 * The Firestore integration functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

// Load Composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor-autoload.php';

// Import Google Cloud Firestore SDK classes
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Core\Exception\GoogleException;

// Import HTTP client for REST fallback
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 * The Firestore integration functionality of the plugin.
 *
 * Handles the integration with Google Firestore.
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 * @author     SyncFire Team
 */
class SyncFire_Firestore {

    /**
     * The Firebase API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The Firebase API key.
     */
    private $api_key;

    /**
     * The Firebase auth domain.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $auth_domain    The Firebase auth domain.
     */
    private $auth_domain;

    /**
     * The Firebase project ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $project_id    The Firebase project ID.
     */
    private $project_id;

    /**
     * The Firebase storage bucket.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $storage_bucket    The Firebase storage bucket.
     */
    private $storage_bucket;

    /**
     * The Firebase messaging sender ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $messaging_sender_id    The Firebase messaging sender ID.
     */
    private $messaging_sender_id;

    /**
     * The Firebase app ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $app_id    The Firebase app ID.
     */
    private $app_id;

    /**
     * The Firestore client.
     *
     * @since    1.0.0
     * @access   private
     * @var      FirestoreClient    $firestore    The Firebase Firestore client instance.
     */
    private $firestore;

    /**
     * Flag to indicate if we're using the REST fallback client.
     *
     * @since    1.0.0
     * @access   private
     * @var      boolean    $using_rest_fallback    True if using REST fallback, false if using native gRPC client.
     */
    private $using_rest_fallback = false;

    /**
     * The REST client for Firestore API.
     *
     * @since    1.0.0
     * @access   private
     * @var      GuzzleClient    $rest_client    The REST client for Firestore API.
     */
    private $rest_client;

    /**
     * The access token for Firestore REST API.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $access_token    The access token for Firestore REST API.
     */
    private $access_token;

    /**
     * The Firestore REST API base URL.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $firestore_api_url    The Firestore REST API base URL.
     */
    private $firestore_api_url;

    /**
     * The Firebase service account credentials.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $service_account    The Firebase service account JSON.
     */
    private $service_account;

    /**
     * Whether the Firestore emulator is enabled.
     *
     * @since    1.1.0
     * @access   private
     * @var      boolean   $emulator_enabled   Whether the Firestore emulator is enabled.
     */
    private $emulator_enabled;

    /**
     * The Firestore emulator host.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $emulator_host      The Firestore emulator host.
     */
    private $emulator_host;

    /**
     * The Firestore emulator port.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $emulator_port      The Firestore emulator port.
     */
    private $emulator_port;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Load Firebase configuration
        $this->api_key = get_option(SyncFire_Options::FIREBASE_API_KEY, '');
        $this->auth_domain = get_option(SyncFire_Options::FIREBASE_AUTH_DOMAIN, '');
        $this->project_id = get_option(SyncFire_Options::FIREBASE_PROJECT_ID, '');
        $this->storage_bucket = get_option(SyncFire_Options::FIREBASE_STORAGE_BUCKET, '');
        $this->messaging_sender_id = get_option(SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID, '');
        $this->app_id = get_option(SyncFire_Options::FIREBASE_APP_ID, '');
        $this->service_account = get_option(SyncFire_Options::FIREBASE_SERVICE_ACCOUNT, '');

        // Load Firestore emulator configuration
        $this->emulator_enabled = (bool) get_option(SyncFire_Options::FIRESTORE_EMULATOR_ENABLED, false);
        $this->emulator_host = get_option(SyncFire_Options::FIRESTORE_EMULATOR_HOST, 'localhost');
        $this->emulator_port = get_option(SyncFire_Options::FIRESTORE_EMULATOR_PORT, '8080');

        // Initialize Firestore client if we have the required configuration
        $this->init_firestore();
    }

    /**
     * Initialize the Firestore client.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    /**
     * Get the path to the service account file.
     *
     * @since    1.0.0
     * @return   string    The path to the service account file.
     */
    private function get_service_account_file_path() {
        // Get the upload directory
        $upload_dir = wp_upload_dir();

        // Create a directory for SyncFire if it doesn't exist
        $syncfire_dir = $upload_dir['basedir'] . '/syncfire';
        if (!file_exists($syncfire_dir)) {
            wp_mkdir_p($syncfire_dir);

            // Create an index.php file to prevent directory listing
            file_put_contents($syncfire_dir . '/index.php', '<?php // Silence is golden');

            // Create an .htaccess file to prevent direct access
            file_put_contents($syncfire_dir . '/.htaccess', 'deny from all');
        }

        return $syncfire_dir . '/service-account.json';
    }

    /**
     * Save the service account JSON to a file.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    private function save_service_account_to_file() {
        if (empty($this->service_account)) {
            return false;
        }

        $file_path = $this->get_service_account_file_path();
        $result = file_put_contents($file_path, $this->service_account);

        if ($result === false) {
            error_log('SyncFire: Failed to save service account to file: ' . $file_path);
            return false;
        }

        return true;
    }

    /**
     * Initialize the Firestore client.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    private function init_firestore() {
        try {
            // Check if Firestore emulator is enabled
            if ($this->emulator_enabled) {
                error_log('SyncFire: Firestore emulator enabled - connecting to ' . $this->emulator_host . ':' . $this->emulator_port);
                
                // For emulator, we don't need service account authentication
                // Set environment variable for emulator (this helps the Google SDK auto-detect the emulator)
                putenv('FIRESTORE_EMULATOR_HOST=' . $this->emulator_host . ':' . $this->emulator_port);
                
                // Configure the Firestore client options for emulator
                $options = [
                    'projectId' => $this->project_id,
                    'emulatorHost' => $this->emulator_host . ':' . $this->emulator_port
                ];

                error_log('SyncFire: Creating Firestore client with emulator options: ' . json_encode($options));

                // Create the Firestore client for emulator (no authentication needed)
                $this->firestore = new FirestoreClient($options);
                $this->using_rest_fallback = false;

                error_log('SyncFire: Firestore emulator client initialized successfully');
                
                return true;
            }

            // Check if we have the required configuration for production
            if (empty($this->project_id) || empty($this->service_account)) {
                error_log('SyncFire: Missing required Firebase configuration');
                return false;
            }

            // Parse the service account JSON
            $service_account_data = json_decode($this->service_account, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('SyncFire: Invalid service account JSON: ' . json_last_error_msg());
                return false;
            }

            // Save the service account to a file
            $service_account_file = $this->get_service_account_file_path();

            // Check if we need to update the file
            if (!file_exists($service_account_file)) {
                if (!$this->save_service_account_to_file()) {
                    error_log('SyncFire: Failed to save service account to file: ' . $service_account_file);
                    return false;
                }
            }

            // Initialize Firestore with the service account
            error_log('SyncFire: Initializing Firestore with service account: ' . $service_account_file);

            // Check if gRPC extension is available
            if (extension_loaded('grpc')) {
                error_log('SyncFire: gRPC extension available, using native Firestore client');

                // Configure the Firestore client options
                $options = [
                    'projectId' => $this->project_id,
                    'keyFilePath' => $service_account_file
                ];

                error_log('SyncFire: Creating native Firestore client with options: ' . json_encode($options));

                // Create the Firestore client directly using Google's API with gRPC
                $this->firestore = new FirestoreClient($options);
                $this->using_rest_fallback = false;

                error_log('SyncFire: Native Firestore client initialized successfully');
            } else {
                // gRPC not available, use REST fallback
                error_log('SyncFire: gRPC extension not available, using REST fallback for Firestore');

                // Load the service account data for authentication
                $service_account_data = json_decode(file_get_contents($service_account_file), true);

                // Initialize REST client wrapper
                $this->init_rest_client($service_account_data);
                $this->using_rest_fallback = true;

                error_log('SyncFire: REST fallback client initialized successfully');
            }

            return true;
        } catch (GoogleException $e) {
            error_log('SyncFire: Failed to initialize Firestore: ' . $e->getMessage());
            return false;
        } catch (GuzzleException $e) {
            error_log('SyncFire: Failed to initialize REST client: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to initialize Firestore: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test the connection to Firestore.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    public function test_connection() {
        try {
            // Check if the Firestore client is initialized
            if (!$this->firestore && !$this->rest_client) {
                // Try to initialize it
                if (!$this->init_firestore()) {
                    return false;
                }
            }

            // If emulator is enabled, test connection differently
            if ($this->emulator_enabled) {
                error_log('SyncFire: Testing emulator connection');
                return $this->test_emulator_connection();
            }

            if ($this->using_rest_fallback) {
                // Test REST connection by making a simple request
                return $this->test_rest_connection();
            } else {
                // Make a simple request to Firestore to test the connection
                // We'll just list the collections to verify connectivity
                $collections = $this->firestore->collections();

                // If we get here, the connection was successful
                return true;
            }
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to connect to Firestore: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test the connection to Firestore Emulator.
     *
     * @since    1.1.0
     * @return   boolean    True on success, false on failure.
     */
    private function test_emulator_connection() {
        try {
            // For emulator, we can test the REST client or the gRPC client
            if ($this->rest_client) {
                // If using REST fallback, test with REST client
                return $this->test_emulator_rest_connection();
            } elseif ($this->firestore) {
                // If using native client, test with native client
                // Make a simple request to verify connectivity
                $collections = $this->firestore->collections();
                return true;
            } else {
                error_log('SyncFire: No client available for emulator connection test');
                return false;
            }
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to connect to Firestore emulator: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test the connection to Firestore Emulator using REST API.
     *
     * @since    1.1.0
     * @return   boolean    True on success, false on failure.
     */
    private function test_emulator_rest_connection() {
        try {
            // Check if the REST client is initialized
            if (!$this->rest_client) {
                error_log('SyncFire: REST client not initialized for emulator');
                return false;
            }

            // Make a simple request to emulator to test connectivity
            // Try to list collections or access a basic endpoint
            try {
                $response = $this->rest_client->get("v1/projects/{$this->project_id}/databases/(default)/documents", [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ]
                ]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    error_log('SyncFire: Successfully connected to Firestore emulator via REST');
                    return true;
                }
            } catch (\Exception $e) {
                error_log('SyncFire: Emulator connection test failed: ' . $e->getMessage());
            }

            return false;
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to connect to Firestore emulator via REST: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test the REST connection to Firestore.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    private function test_rest_connection() {
        try {
            // Check if the REST client is initialized
            if (!$this->rest_client) {
                error_log('SyncFire: REST client not initialized');
                return false;
            }

            // Make a simple request to get project info
            // Using a different endpoint that should always exist
            $url = "v1/projects/{$this->project_id}/databases/(default)";

            $response = $this->rest_client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            // Check response
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                error_log('SyncFire: Successfully connected to Firestore via REST');
                return true;
            } else {
                error_log('SyncFire: Failed to connect to Firestore via REST: ' . $response->getStatusCode());
                return false;
            }
        } catch (GuzzleException $e) {
            // Try an alternative endpoint if the first one fails
            try {
                // Try the root API endpoint
                $response = $this->rest_client->get('v1/', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->access_token,
                        'Content-Type' => 'application/json'
                    ]
                ]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    error_log('SyncFire: Successfully connected to Firestore API via REST');
                    return true;
                }
            } catch (\Exception $innerEx) {
                error_log('SyncFire: Failed to connect to Firestore API via REST: ' . $innerEx->getMessage());
            }

            error_log('SyncFire: Failed to connect to Firestore via REST: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to connect to Firestore via REST: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save a taxonomy to Firestore.
     *
     * @since    1.0.0
     * @param    string    $taxonomy       The taxonomy name.
     * @param    array     $taxonomy_data  The taxonomy data.
     * @return   boolean                   True on success, false on failure.
     */
    public function save_taxonomy($taxonomy, $taxonomy_data) {
        try {
            // Check if the Firestore client is initialized
            if (!$this->firestore && !$this->rest_client) {
                // Try to initialize it
                if (!$this->init_firestore()) {
                    return false;
                }
            }

            // Create the document path
            $document_path = "taxonomies/{$taxonomy}";

            if ($this->using_rest_fallback) {
                // Use REST API
                return $this->save_document_rest($document_path, $taxonomy_data);
            } else {
                // Use native Firestore client
                $document_ref = $this->firestore->document($document_path);
                $document_ref->set($taxonomy_data, ['merge' => true]);
                return true;
            }
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to save taxonomy: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save a post to Firestore.
     *
     * @since    1.0.0
     * @param    string    $post_type  The post type.
     * @param    int       $post_id    The post ID.
     * @param    array     $post_data  The post data.
     * @return   boolean               True on success, false on failure.
     */
    public function save_post($post_type, $post_id, $post_data) {
        try {
            // Check if the Firestore client is initialized
            if (!$this->firestore && !$this->rest_client) {
                // Try to initialize it
                if (!$this->init_firestore()) {
                    return false;
                }
            }

            // Create the document path
            $document_path = "posts/{$post_type}/items/{$post_id}";

            if ($this->using_rest_fallback) {
                // Use REST API
                return $this->save_document_rest($document_path, $post_data);
            } else {
                // Use native Firestore client
                $document_ref = $this->firestore->document($document_path);
                $document_ref->set($post_data, ['merge' => true]);
                return true;
            }
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to save post: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a post from Firestore.
     *
     * @since    1.0.0
     * @param    string    $post_type  The post type.
     * @param    int       $post_id    The post ID.
     * @return   boolean               True on success, false on failure.
     */
    public function delete_post($post_type, $post_id) {
        try {
            // Check if the Firestore client is initialized
            if (!$this->firestore && !$this->rest_client) {
                // Try to initialize it
                if (!$this->init_firestore()) {
                    return false;
                }
            }

            // Create the document path
            $document_path = "posts/{$post_type}/items/{$post_id}";

            if ($this->using_rest_fallback) {
                // Use REST API
                return $this->delete_document_rest($document_path);
            } else {
                // Use native Firestore client
                $document_ref = $this->firestore->document($document_path);
                $document_ref->delete();
                return true;
            }
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to delete post: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a document from Firestore.
     *
     * @since    1.0.0
     * @param    string    $document_path  The document path.
     * @return   boolean                   True on success, false on failure.
     */
    public function delete_document($document_path) {
        try {
            // Check if the Firestore client is initialized
            if (!$this->firestore) {
                // Try to initialize it
                if (!$this->init_firestore()) {
                    return false;
                }
            }

            // Get the document reference
            $document_ref = $this->firestore->document($document_path);

            // Delete the document
            $document_ref->delete();

            return true;
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to delete document: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a document from Firestore.
     *
     * @since    1.0.0
     * @param    string    $document_path  The document path.
     * @return   array                     The document data, or empty array if not found.
     */
    public function get_document($document_path) {
        try {
            // Check if the Firestore client is initialized
            if (!$this->firestore && !$this->rest_client) {
                // Try to initialize it
                if (!$this->init_firestore()) {
                    return [];
                }
            }

            if ($this->using_rest_fallback) {
                // Use REST API
                return $this->get_document_rest($document_path);
            } else {
                // Use native Firestore client
                $document_ref = $this->firestore->document($document_path);
                $snapshot = $document_ref->snapshot();

                // Check if the document exists
                if (!$snapshot->exists()) {
                    return [];
                }

                // Return the document data
                return $snapshot->data();
            }
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to get document: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Save a document to Firestore.
     *
     * @since    1.0.0
     * @param    string    $document_path  The document path.
     * @param    array     $data           The document data.
     * @return   boolean                   True on success, false on failure.
     */
    private function save_document($document_path, $data) {
        try {
            // Check if the Firestore client is initialized
            if (!$this->firestore) {
                // Try to initialize it
                if (!$this->init_firestore()) {
                    return false;
                }
            }

            // Get the document reference
            $document_ref = $this->firestore->document($document_path);

            // Prepare the document data
            $document_data = $this->prepare_firestore_document($data);

            // Save the document
            $document_ref->set($document_data);

            return true;
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to save document: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare data for Firestore.
     *
     * @since    1.0.0
     * @param    array     $data  The data to prepare.
     * @return   array            The prepared data.
     */
    private function prepare_firestore_document($data) {
        $document = array(
            'fields' => array(),
        );

        foreach ($data as $key => $value) {
            $document['fields'][$key] = $this->prepare_firestore_value($value);
        }

        return $document;
    }

    /**
     * Prepare a value for Firestore.
     *
     * @since    1.0.0
     * @param    mixed     $value  The value to prepare.
     * @return   array             The prepared value.
     */
    private function prepare_firestore_value($value) {
        if (is_null($value)) {
            return array('nullValue' => null);
        } elseif (is_bool($value)) {
            return array('booleanValue' => $value);
        } elseif (is_int($value)) {
            return array('integerValue' => $value);
        } elseif (is_float($value)) {
            return array('doubleValue' => $value);
        } elseif (is_string($value)) {
            return array('stringValue' => $value);
        } elseif (is_array($value)) {
            // Check if the array is associative
            if ($this->is_assoc($value)) {
                // This is a map
                $map = array('mapValue' => array('fields' => array()));

                foreach ($value as $k => $v) {
                    $map['mapValue']['fields'][$k] = $this->prepare_firestore_value($v);
                }

                return $map;
            } else {
                // This is an array
                $array = array('arrayValue' => array('values' => array()));

                foreach ($value as $v) {
                    $array['arrayValue']['values'][] = $this->prepare_firestore_value($v);
                }

                return $array;
            }
        } else {
            // Default to string
            return array('stringValue' => (string) $value);
        }
    }

    /**
     * Check if an array is associative.
     *
     * @since    1.0.0
     * @param    array     $array  The array to check.
     * @return   boolean           True if the array is associative, false otherwise.
     */
    private function is_assoc($array) {
        if (!is_array($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Initialize the REST client for Firestore API.
     *
     * @since    1.0.0
     * @param    array     $service_account_data    The service account data.
     * @return   boolean                            True on success, false on failure.
     */
    private function init_rest_client($service_account_data) {
        try {
            // Check if emulator is enabled
            if ($this->emulator_enabled) {
                // For emulator, use direct connection without authentication
                $this->firestore_api_url = "http://{$this->emulator_host}:{$this->emulator_port}/v1/projects/{$this->project_id}/databases/(default)/documents";
                
                // Initialize the REST client for emulator (no authentication needed)
                $this->rest_client = new GuzzleClient([
                    'base_uri' => "http://{$this->emulator_host}:{$this->emulator_port}/",
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]);
                
                // Set access token to empty string for emulator
                $this->access_token = '';
                
                error_log('SyncFire: REST client initialized for emulator: http://' . $this->emulator_host . ':' . $this->emulator_port);
            } else {
                // Set up the Firestore REST API URL for production
                $this->firestore_api_url = "https://firestore.googleapis.com/v1/projects/{$this->project_id}/databases/(default)/documents";

                // Generate an access token from service account
                $this->access_token = $this->generate_access_token($service_account_data);

                // Initialize the REST client
                $this->rest_client = new GuzzleClient([
                    'base_uri' => 'https://firestore.googleapis.com/',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->access_token,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]);
            }

            return true;
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to initialize REST client: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate an access token from service account credentials.
     *
     * @since    1.0.0
     * @param    array     $service_account_data    The service account data.
     * @return   string                             The access token.
     */
    private function generate_access_token($service_account_data) {
        // Check if we have a cached token that's still valid
        $token_data = get_transient('syncfire_firebase_token');

        if ($token_data) {
            return $token_data['access_token'];
        }

        // No valid cached token, get a new one
        try {
            // Create JWT header
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT',
                'kid' => $service_account_data['private_key_id']
            ];

            // Create JWT claim set
            $now = time();
            $claim_set = [
                'iss' => $service_account_data['client_email'],
                'scope' => 'https://www.googleapis.com/auth/datastore',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now
            ];

            // Encode JWT header and claim set
            $base64_header = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
            $base64_claim_set = rtrim(strtr(base64_encode(json_encode($claim_set)), '+/', '-_'), '=');

            // Create signature
            $signature_input = $base64_header . '.' . $base64_claim_set;
            $private_key = openssl_pkey_get_private($service_account_data['private_key']);

            if (!$private_key) {
                throw new \Exception('Invalid private key: ' . openssl_error_string());
            }

            $signature = '';
            openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256);
            openssl_free_key($private_key);

            $base64_signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            // Create JWT
            $jwt = $base64_header . '.' . $base64_claim_set . '.' . $base64_signature;

            // Exchange JWT for access token
            $client = new GuzzleClient();
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]
            ]);

            $token_data = json_decode($response->getBody(), true);

            // Cache the token (for slightly less than its expiration time)
            set_transient('syncfire_firebase_token', $token_data, $token_data['expires_in'] - 300);

            return $token_data['access_token'];
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to generate access token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save a document to Firestore using REST API.
     *
     * @since    1.0.0
     * @param    string    $document_path    The document path.
     * @param    array     $data             The document data.
     * @param    boolean   $merge            Whether to merge the data with existing document.
     * @return   boolean                     True on success, false on failure.
     */
    private function save_document_rest($document_path, $data, $merge = true) {
        try {
            // Check if the REST client is initialized
            if (!$this->rest_client) {
                error_log('SyncFire: REST client not initialized');
                return false;
            }

            // Format data for Firestore REST API
            $firestore_data = $this->format_data_for_rest($data);

            // Create the request URL
            $base_url = "v1/projects/{$this->project_id}/databases/(default)/documents";

            // Check if we're dealing with a taxonomy (they need special handling)
            $is_taxonomy = strpos($document_path, 'taxonomies/') === 0;
            error_log('SyncFire: Is taxonomy document: ' . ($is_taxonomy ? 'yes' : 'no'));

            // For taxonomies, we need to use a different approach - create the document with POST first
            if ($is_taxonomy) {
                // Extract collection and document ID
                $parts = explode('/', $document_path);
                if (count($parts) >= 2) {
                    $collection = $parts[0];
                    $document_id = $parts[1];

                    // First check if the document exists
                    try {
                        $check_url = "{$base_url}/{$document_path}";
                        error_log('SyncFire: Checking if document exists: ' . $check_url);

                        $check_response = $this->rest_client->get($check_url, [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $this->access_token
                            ],
                            'http_errors' => false
                        ]);

                        $document_exists = $check_response->getStatusCode() === 200;
                        error_log('SyncFire: Document exists: ' . ($document_exists ? 'yes' : 'no'));

                        if (!$document_exists) {
                            // Document doesn't exist, create it with POST
                            $create_url = "{$base_url}/{$collection}?documentId={$document_id}";
                            error_log('SyncFire: Creating document with POST: ' . $create_url);

                            // Prepare the JSON payload with special handling for empty objects
                            $json_payload = json_encode(['fields' => $firestore_data]);
                            // Replace empty arrays [] with empty objects {} in the JSON string
                            $json_payload = str_replace('"fields":[]', '"fields":{}', $json_payload);

                            $create_response = $this->rest_client->post($create_url, [
                                'body' => $json_payload,
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $this->access_token,
                                    'Content-Type' => 'application/json'
                                ],
                                'http_errors' => false
                            ]);

                            if ($create_response->getStatusCode() >= 200 && $create_response->getStatusCode() < 300) {
                                error_log('SyncFire: Successfully created document via POST');
                                return true;
                            } else {
                                error_log('SyncFire: Failed to create document via POST: ' . $create_response->getStatusCode());
                                error_log('SyncFire: Response body: ' . $create_response->getBody());
                            }
                        } else {
                            // Document exists, update it with PATCH
                            $update_url = "{$base_url}/{$document_path}";
                            // Don't use wildcard field paths as they're not supported
                            // Instead, specify each top-level field explicitly
                            if ($merge) {
                                // Get top-level field names from the data
                                $field_paths = array_keys($firestore_data);
                                if (!empty($field_paths)) {
                                    $update_url .= '?' . implode('&', array_map(function($field) {
                                        return 'updateMask.fieldPaths=' . urlencode($field);
                                    }, $field_paths));
                                }
                            }
                            error_log('SyncFire: Updating document with PATCH: ' . $update_url);

                            // Prepare the JSON payload with special handling for empty objects
                            $json_payload = json_encode(['fields' => $firestore_data]);
                            // Replace empty arrays [] with empty objects {} in the JSON string
                            $json_payload = str_replace('"fields":[]', '"fields":{}', $json_payload);

                            $update_response = $this->rest_client->patch($update_url, [
                                'body' => $json_payload,
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $this->access_token,
                                    'Content-Type' => 'application/json'
                                ],
                                'http_errors' => false
                            ]);

                            if ($update_response->getStatusCode() >= 200 && $update_response->getStatusCode() < 300) {
                                error_log('SyncFire: Successfully updated document via PATCH');
                                return true;
                            } else {
                                error_log('SyncFire: Failed to update document via PATCH: ' . $update_response->getStatusCode());
                                error_log('SyncFire: Response body: ' . $update_response->getBody());
                            }
                        }
                    } catch (GuzzleException $e) {
                        error_log('SyncFire: Error checking document existence: ' . $e->getMessage());
                    }
                }
            }

            // Standard approach for non-taxonomy documents or if taxonomy-specific approach failed
            // Check if document_path already contains the full path or just the relative path
            if (strpos($document_path, 'projects/') === 0) {
                // Already a full path
                $url = "v1/{$document_path}";
            } else {
                // Relative path, construct the full URL
                $url = "{$base_url}/{$document_path}";
            }

            // We'll handle merge through updateMask.fieldPaths in the query_params section below
            // Don't add a wildcard updateMask here as it's not supported by the REST API

            // Log the request data for debugging
            error_log('SyncFire: Saving document to path: ' . $url);
            // Log the raw PHP data structure for debugging
            error_log('SyncFire: Document data (raw): ' . print_r($firestore_data, true));
            error_log('SyncFire: Document data (JSON): ' . json_encode($firestore_data, JSON_PARTIAL_OUTPUT_ON_ERROR));

            // For taxonomies and new documents, try PUT first
            $method = $is_taxonomy ? 'put' : 'patch';

            // Handle updateMask for PATCH requests
            $query_params = [];
            if ($method === 'patch') {
                if ($merge) {
                    // For merge operations, explicitly list all fields to update
                    // Get top-level field names from the data
                    $field_paths = array_keys($firestore_data);
                    if (!empty($field_paths)) {
                        foreach ($field_paths as $field) {
                            $query_params[] = 'updateMask.fieldPaths=' . urlencode($field);
                        }
                    }
                }
            }

            // Add query parameters to URL if needed
            if (!empty($query_params)) {
                // Check if URL already has a query string (contains '?')
                if (strpos($url, '?') !== false) {
                    $url .= '&' . implode('&', $query_params);
                } else {
                    $url .= '?' . implode('&', $query_params);
                }
            }

            // Prepare the JSON payload with special handling for empty objects
            $json_payload = json_encode(['fields' => $firestore_data]);
            // Replace empty arrays [] with empty objects {} in the JSON string
            $json_payload = str_replace('"fields":[]', '"fields":{}', $json_payload);

            $options = [
                'body' => $json_payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type' => 'application/json'
                ],
                'http_errors' => false
            ];

            try {
                // Make the request
                error_log('SyncFire: Trying ' . strtoupper($method) . ' request to: ' . $url);
                $response = $this->rest_client->$method($url, $options);

                // Check response
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    error_log('SyncFire: Successfully saved document via REST ' . strtoupper($method) . ' to: ' . $url);
                    return true;
                } else {
                    error_log('SyncFire: Failed to save document via REST ' . strtoupper($method) . ': ' . $response->getStatusCode());
                    error_log('SyncFire: Response body: ' . $response->getBody());
                }

                // If first method fails, try the alternative method
                $alt_method = $method === 'patch' ? 'put' : 'patch';
                error_log('SyncFire: Trying ' . strtoupper($alt_method) . ' for document operation');

                $response = $this->rest_client->$alt_method($url, $options);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    error_log('SyncFire: Successfully saved document via REST ' . strtoupper($alt_method));
                    return true;
                } else {
                    error_log('SyncFire: Failed to save document via REST ' . strtoupper($alt_method) . ': ' . $response->getStatusCode());
                    error_log('SyncFire: Response body: ' . $response->getBody());
                    return false;
                }
            } catch (GuzzleException $e) {
                error_log('SyncFire: Failed to save document via REST: ' . $e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to save document via REST: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Format data for Firestore REST API.
     *
     * @since    1.0.0
     * @param    array     $data    The data to format.
     * @return   array              The formatted data.
     */
    private function format_data_for_rest($data) {
        $formatted = [];

        foreach ($data as $key => $value) {
            $formatted[$key] = $this->format_value_for_rest($value, $key);
        }

        return $formatted;
    }

    /**
     * Format a value for Firestore REST API.
     *
     * @since    1.0.0
     * @param    mixed     $value    The value to format.
     * @return   array               The formatted value.
     */
    private function format_value_for_rest($value, $field_name = '') {
        if (is_null($value)) {
            return ['nullValue' => null];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => (string) $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_array($value)) {
            // Special handling for meta field - always treat as map
            if ($field_name === 'meta' || $this->is_assoc($value)) {
                // Map
                $map = [];
                foreach ($value as $k => $v) {
                    $map[$k] = $this->format_value_for_rest($v, $k);
                }
                // If map is empty, ensure it's an empty object, not an empty array
                if (empty($map) && $field_name === 'meta') {
                    return ['mapValue' => ['fields' => (object)[]]];
                }
                return ['mapValue' => ['fields' => $map]];
            } else {
                // Array
                $array = [];
                foreach ($value as $v) {
                    $array[] = $this->format_value_for_rest($v);
                }
                return ['arrayValue' => ['values' => $array]];
            }
        } elseif (is_object($value)) {
            // Handle objects (including stdClass)
            if ($value instanceof \stdClass) {
                // Empty stdClass - create an empty map with an empty object (not an empty array)
                return ['mapValue' => ['fields' => (object)[]]];
            } else {
                // Convert object to array and format as map
                $map = [];
                foreach (get_object_vars($value) as $k => $v) {
                    $map[$k] = $this->format_value_for_rest($v, $k);
                }
                return ['mapValue' => ['fields' => $map]];
            }
        } else {
            // Default to string
            return ['stringValue' => (string) $value];
        }
    }

    /**
     * Delete a document from Firestore using REST API.
     *
     * @since    1.0.0
     * @param    string    $document_path    The document path.
     * @return   boolean                     True on success, false on failure.
     */
    private function delete_document_rest($document_path) {
        try {
            // Check if the REST client is initialized
            if (!$this->rest_client) {
                error_log('SyncFire: REST client not initialized');
                return false;
            }

            // Create the request URL
            $url = "v1/projects/{$this->project_id}/databases/(default)/documents/{$document_path}";

            // Make the request
            $response = $this->rest_client->delete($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            // Check response
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return true;
            } else {
                error_log('SyncFire: Failed to delete document via REST: ' . $response->getStatusCode());
                return false;
            }
        } catch (GuzzleException $e) {
            error_log('SyncFire: Failed to delete document via REST: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to delete document via REST: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a document from Firestore using REST API.
     *
     * @since    1.0.0
     * @param    string    $document_path    The document path.
     * @return   array                       The document data, or empty array if not found.
     */
    private function get_document_rest($document_path) {
        try {
            // Check if the REST client is initialized
            if (!$this->rest_client) {
                error_log('SyncFire: REST client not initialized');
                return [];
            }

            // Create the request URL
            $url = "v1/projects/{$this->project_id}/databases/(default)/documents/{$document_path}";

            // Make the request
            $response = $this->rest_client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            // Check response
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $data = json_decode($response->getBody(), true);

                // Convert Firestore REST format to standard PHP array
                if (isset($data['fields'])) {
                    return $this->parse_rest_document($data['fields']);
                }

                return [];
            } else {
                error_log('SyncFire: Failed to get document via REST: ' . $response->getStatusCode());
                return [];
            }
        } catch (GuzzleException $e) {
            // 404 is not an error, it just means the document doesn't exist
            if ($e->getCode() == 404) {
                return [];
            }

            error_log('SyncFire: Failed to get document via REST: ' . $e->getMessage());
            return [];
        } catch (\Exception $e) {
            error_log('SyncFire: Failed to get document via REST: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse a Firestore REST document into a standard PHP array.
     *
     * @since    1.0.0
     * @param    array     $fields    The Firestore REST document fields.
     * @return   array                The parsed document data.
     */
    private function parse_rest_document($fields) {
        $result = [];

        foreach ($fields as $key => $value) {
            $result[$key] = $this->parse_rest_value($value);
        }

        return $result;
    }

    /**
     * Parse a Firestore REST value into a standard PHP value.
     *
     * @since    1.0.0
     * @param    array     $value    The Firestore REST value.
     * @return   mixed               The parsed value.
     */
    private function parse_rest_value($value) {
        if (isset($value['nullValue'])) {
            return null;
        } elseif (isset($value['booleanValue'])) {
            return (bool) $value['booleanValue'];
        } elseif (isset($value['integerValue'])) {
            return (int) $value['integerValue'];
        } elseif (isset($value['doubleValue'])) {
            return (float) $value['doubleValue'];
        } elseif (isset($value['stringValue'])) {
            return $value['stringValue'];
        } elseif (isset($value['mapValue']) && isset($value['mapValue']['fields'])) {
            return $this->parse_rest_document($value['mapValue']['fields']);
        } elseif (isset($value['arrayValue']) && isset($value['arrayValue']['values'])) {
            $result = [];
            foreach ($value['arrayValue']['values'] as $item) {
                $result[] = $this->parse_rest_value($item);
            }
            return $result;
        } else {
            // Default
            return null;
        }
    }
}
