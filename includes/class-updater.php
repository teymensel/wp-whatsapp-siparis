<?php
if (!defined('ABSPATH')) {
    exit;
}

class WWS_Updater
{
    private $slug;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_response;

    public function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_data = get_plugin_data($plugin_file);
        $this->slug = plugin_basename($plugin_file);
        $this->username = 'teymensel';
        $this->repo = 'wp-whatsapp-siparis';

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_source_selection', array($this, 'rename_github_folder'), 10, 4);
    }

    private function get_repository_info()
    {
        if (is_null($this->github_response)) {
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repo);

            // Context for public API request (User-Agent is required by GitHub)
            $args = array(
                'headers' => array(
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                )
            );

            $response = wp_remote_get($request_uri, $args);

            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                return false;
            }

            $this->github_response = json_decode(wp_remote_retrieve_body($response), true);
        }

        return $this->github_response;
    }

    public function check_update($transient)
    {
        // Safety check to ensure transient and response are valid
        if (empty($transient->checked)) {
            return $transient;
        }

        $repo_info = $this->get_repository_info();

        if (!$repo_info) {
            return $transient;
        }

        // Check if new version is available
        // Remove 'v' from tag if present for comparison
        $new_version = isset($repo_info['tag_name']) ? ltrim($repo_info['tag_name'], 'v') : '';
        $current_version = $this->plugin_data['Version'];

        if (version_compare($current_version, $new_version, '<')) {
            $plugin = array(
                'slug' => $this->slug,
                'new_version' => $new_version,
                'url' => $this->plugin_data['PluginURI'],
                'package' => isset($repo_info['zipball_url']) ? $repo_info['zipball_url'] : ''
            );

            $transient->response[$this->slug] = (object) $plugin;
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args)
    {
        if ($action !== 'plugin_information') {
            return $result;
        }

        // Fix: Check both full slug and dirname to capture different WP contexts
        if (!isset($args->slug) || ($args->slug !== $this->slug && $args->slug !== dirname($this->slug))) {
            return $result;
        }

        $repo_info = $this->get_repository_info();

        if (!$repo_info) {
            return $result;
        }

        $plugin = array(
            'name' => $this->plugin_data['Name'],
            'slug' => $this->slug,
            'version' => ltrim($repo_info['tag_name'], 'v'),
            'author' => $this->plugin_data['AuthorName'],
            'homepage' => $this->plugin_data['PluginURI'],
            'requires' => $this->plugin_data['RequiresWP'],
            'tested' => '6.7', // Ideally parsed from somewhere dynamic or kept updated
            'last_updated' => $repo_info['published_at'],
            'sections' => array(
                'description' => $this->plugin_data['Description'],
                'changelog' => isset($repo_info['body']) ? nl2br($repo_info['body']) : 'Changelog not available.'
            ),
            'download_link' => $repo_info['zipball_url']
        );

        return (object) $plugin;
    }

    /**
     * Rename the extracted folder from GitHub to match the plugin slug.
     * This prevents the plugin from deactivating after update.
     */
    public function rename_github_folder($source, $remote_source, $upgrader, $hook_extra = null)
    {
        global $wp_filesystem;

        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->slug) {
            $new_source = trailingslashit($remote_source) . dirname($this->slug);
            if ($wp_filesystem->move($source, $new_source)) {
                return trailingslashit($new_source);
            }
        }

        return $source;
    }
}
