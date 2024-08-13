<?php

namespace OutlawzTeam\Radicle\Providers;

use Illuminate\Support\ServiceProvider;

class LoginServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // You can register bindings or singletons here if needed
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        add_action('login_enqueue_scripts', [$this, 'addInlineLoginStyles']);

        // Change the login logo URL
        add_filter('login_headerurl', [$this, 'customLoginLogoUrl']);

        // Create the Outlawz customer role
        add_action('init', [$this, 'create_outlawz_customer_role']);

        // Disable update notices for the Outlawz klant role
        add_action('admin_init', [$this, 'disable_update_notices_for_outlawz_klant']);
    }

    /**
     * Add inline CSS for the WordPress login page.
     *
     * @return void
     */
    public function addInlineLoginStyles()
    {

        $file_path = str_replace(base_path(), '', __DIR__);
        $file_path = ltrim($file_path, '/');
        $asset_path = str_replace('src/Providers', 'resources', $file_path);

        $asset_path = home_url() . '/' . $asset_path;

        $custom_css = "
            /* Logo */
            #login h1 a,
            .login h1 a {
                background-image: url('https://assets.outlawz.nl/images/logo.svg');
                height: 98px;
                width: 114px;
                background-size: 114px;
            }

            /* Login background */
            body {
                background-color: black;
                display: grid;
                place-content: center;
                background-image: url('https://assets.outlawz.nl/images/logo-outline.svg');
                background-repeat: repeat-x;
                background-position: -52vw;
                background-size: 104vw 100vh;
            }

            /* Links */
            a {
                color: #FFB800 !important;
            }

            /* Language switcher */
            .language-switcher {
                display: none;
            }

            /* Submit button */
            .login #wp-submit {
                background-color: #FFB800;
                border-color: #FFB800;
                box-shadow: none;
                text-shadow: none;
                color: white;
                border-radius: 0;

            }

            /* Input fields */
            input[type='text']:focus, input[type='password']:focus, input[type='color']:focus, input[type='date']:focus, input[type='datetime']:focus, input[type='datetime-local']:focus, input[type='email']:focus, input[type='month']:focus, input[type='number']:focus, input[type='search']:focus, input[type='tel']:focus, input[type='time']:focus, input[type='url']:focus, input[type='week']:focus, input[type='checkbox']:focus, input[type='radio']:focus, select:focus, textarea:focus{
                border-color: #FFB800;
                box-shadow: none;
            }

            input[type='text'], input[type='password'], input[type='color'], input[type='date'], input[type='datetime'], input[type='datetime-local'], input[type='email'], input[type='month'], input[type='number'], input[type='search'], input[type='tel'], input[type='time'], input[type='url'], input[type='week'], input[type='checkbox'], input[type='radio'], select, textarea{
                border-color: #FFB800;
                box-shadow: none;
                border-radius: 0;
            }

            input[type='checkbox']{
                border-radius: 4px;
            }

            input[type='checkbox']:checked::before {
                content: url(data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27%3E%3Cpath%20d%3D%27M14.83%204.89l1.34.94-5.81%208.38H9.02L5.78%209.67l1.34-1.25%202.57%202.4z%27%20fill%3D%27%23FFB800%27%2F%3E%3C%2Fsvg%3E);
            }

            /* Show hide Password icon*/
            .login .button.wp-hide-pw:focus {
                background: transparent;
                border-color: #FFB800;
                box-shadow: 0 0 0 1px #FFB800;
                outline: 2px solid transparent;
            }
                
            /* Box */
            .login form{
                padding: 40px;
            }

            #login{
                width: 100%;
                max-width: 380px;
            }

            label{
                color: black;
            }



        ";

        wp_add_inline_style('login', $custom_css);
    }

    /**
     * Change the login logo URL.
     *
     * @return string
     */
    public function customLoginLogoUrl()
    {
        return 'https://outlawz.nl'; // You can change this to any URL you want
    }

    /**
     * Create the Outlawz klant role.
     * 
     * @return void
     */
    public function create_outlawz_customer_role()
    {
        $admin_capabilities = get_role('administrator')->capabilities;
        // Remove capabilities that are not needed for the Outlawz klant role
        $admin_capabilities['update_core'] = false;

        // Plugins
        $admin_capabilities['activate_plugins'] = false;
        $admin_capabilities['delete_plugins'] = false;
        $admin_capabilities['install_plugins'] = false;
        $admin_capabilities['update_plugins'] = false;

        // Themes
        $admin_capabilities['edit_themes'] = false;
        $admin_capabilities['switch_themes'] = false;
        $admin_capabilities['update_themes'] = false;
        $admin_capabilities['delete_themes'] = false;
        $admin_capabilities['install_themes'] = false;

        // Users
        $admin_capabilities['promote_users'] = false;

        // Add the modified role
        add_role('outlawz_klant', 'Outlawz klant', $admin_capabilities);
    }

    /**
     * Disable update notices for Outlawz klant role.
     *
     * @return void
     */
    public function disable_update_notices_for_outlawz_klant()
    {
        if ($this->user_has_role('outlawz_klant')) {
            // Hide update notices
            add_action('admin_head', function () {
                remove_action('admin_notices', 'update_nag', 3);
                remove_action('admin_notices', 'maintenance_nag');
            });

            // Hide update notifications in the dashboard
            add_filter('pre_site_transient_update_core', '__return_null');
            add_filter('pre_site_transient_update_plugins', '__return_null');
            add_filter('pre_site_transient_update_themes', '__return_null');
            add_action('wp_before_admin_bar_render', [$this, 'remove_wp_logo_menu']);
        }
    }

    /**
     * Remove WordPress logo menu that shows update notifications.
     *
     * @return void
     */
    public function remove_wp_logo_menu()
    {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('wp-logo');
    }

    /**
     * Utility function to check if the current user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    private function user_has_role($role)
    {
        $user = wp_get_current_user();
        return in_array($role, (array) $user->roles);
    }
}
