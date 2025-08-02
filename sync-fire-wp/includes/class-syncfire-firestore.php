<?php
/**
 * The Firestore integration functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Load Firebase configuration
        $this->api_key = get_option('syncfire_firebase_api_key', '');
        $this->auth_domain = get_option('syncfire_firebase_auth_domain', '');
        $this->project_id = get_option('syncfire_firebase_project_id', '');
        $this->storage_bucket = get_option('syncfire_firebase_storage_bucket', '');
        $this->messaging_sender_id = get_option('syncfire_firebase_messaging_sender_id', '');
        $this->app_id = get_option('syncfire_firebase_app_id', '');
        $this->service_account = get_option('syncfire_firebase_service_account', '');
    }

    /**
     * Test the connection to Firestore.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    public function test_connection() {
        // Check if the required configuration is set
        if (empty($this->api_key) || empty($this->project_id)) {
            return false;
        }

        // Make a simple request to Firestore to test the connection
        $url = "https://firestore.googleapis.com/v1/projects/{$this->project_id}/databases/(default)/documents";
        
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_access_token(),
            ),
            'timeout' => 30,
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        return $response_code === 200;
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
        // Check if the required configuration is set
        if (empty($this->api_key) || empty($this->project_id)) {
            return false;
        }

        // Create the document path
        $document_path = "taxonomies/{$taxonomy}";
        
        // Save to Firestore
        return $this->save_document($document_path, $taxonomy_data);
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
        // Check if the required configuration is set
        if (empty($this->api_key) || empty($this->project_id)) {
            return false;
        }

        // Create the document path
        $document_path = "post_types/{$post_type}/posts/{$post_id}";
        
        // Save to Firestore
        return $this->save_document($document_path, $post_data);
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
        // Check if the required configuration is set
        if (empty($this->api_key) || empty($this->project_id)) {
            return false;
        }

        // Create the document path
        $document_path = "post_types/{$post_type}/posts/{$post_id}";
        
        // Delete from Firestore
        return $this->delete_document($document_path);
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
        // Create the Firestore document
        $document = $this->prepare_firestore_document($data);
        
        // Create the URL
        $url = "https://firestore.googleapis.com/v1/projects/{$this->project_id}/databases/(default)/documents/{$document_path}";
        
        // Make the request
        $args = array(
            'method' => 'PATCH',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_access_token(),
            ),
            'body' => json_encode($document),
            'timeout' => 30,
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        return $response_code === 200;
    }

    /**
     * Delete a document from Firestore.
     *
     * @since    1.0.0
     * @param    string    $document_path  The document path.
     * @return   boolean                   True on success, false on failure.
     */
    private function delete_document($document_path) {
        // Create the URL
        $url = "https://firestore.googleapis.com/v1/projects/{$this->project_id}/databases/(default)/documents/{$document_path}";
        
        // Make the request
        $args = array(
            'method' => 'DELETE',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_access_token(),
            ),
            'timeout' => 30,
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        return $response_code === 200;
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
     * Get access token for Firestore API.
     *
     * @since    1.0.0
     * @return   string    Access token.
     */
    private function get_access_token() {
        // Get the service account JSON from the option
        $service_account_json = get_option('syncfire_firebase_service_account', '');
        
        if (empty($service_account_json)) {
            // Log error
            error_log('SyncFire: No service account JSON found in options');
            return false;
        }
        
        // Decode the JSON
        $service_account = json_decode($service_account_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log error
            error_log('SyncFire: Invalid service account JSON: ' . json_last_error_msg());
            return false;
        }
        
        // Check if we have a cached token that's still valid
        $token_data = get_transient('syncfire_firebase_token');
        
        if ($token_data) {
            return $token_data['access_token'];
        }
        
        // No valid cached token, get a new one
        // This is a simplified example - in a real implementation, you would use the Google OAuth2 API
        // to get a token using the service account credentials
        
        // For security, we're using WordPress transients to store the token temporarily
        // with an expiration time less than the token's actual expiration
        
        // Simulate getting a token (replace with actual OAuth2 implementation)
        $token_data = array(
            'access_token' => 'placeholder_token',
            'expires_in' => 3600 // 1 hour
        );
        
        // Cache the token (for slightly less than its expiration time)
        set_transient('syncfire_firebase_token', $token_data, $token_data['expires_in'] - 300);
        
        return $token_data['access_token'];
    }
}
