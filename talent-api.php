<?php
/*
Plugin Name:  TalentHR Jobs
Plugin URI:   https://www.talenthr.io/platform/integrations/wordpress/
Description:  This plugin integrates TalentHR with WordPress. Promote your TalenHR open job positions through your WordPress site.
Version:      1.2
Author:       TalentHR
Author URI:   https://www.talenthr.io
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

// Include separate files for each shortcode
require_once(plugin_dir_path(__FILE__) . 'shortcodes.php');
require_once(plugin_dir_path(__FILE__) . 'widget.php');

// Basic security
defined('ABSPATH') || die('Unauthorized access');

// Define API URL constant
define('TALENT_API_URL', 'https://api-ext.talenthr.io/{domainName}/job-positions/published');


// Define Base Job URL constant
define('JOBS_BASE_URL', 'https://jobs.talenthr.io/');

// Add new menu page
add_action('admin_menu', 'talentHR_add_menu');

// Register shortcode
add_shortcode('TalentHR-job-positions-extended', 'talentHR_api_extended_shortcode');

// Register shortcode for short API response
add_shortcode('TalentHR-job-positions-simple', 'talentHR_api_simple_shortcode');

// Enqueue Font Awesome library
function talentHR_enqueue_font_awesome()
{
    wp_enqueue_script('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js', array(), '5.15.4', false);
}
add_action('admin_enqueue_scripts', 'talentHR_enqueue_font_awesome');

// Enqueue styles for the admin area
function talentHR_enqueue_admin_styles()
{
    wp_enqueue_style('talenthr-admin-styles', plugin_dir_url(__FILE__) . 'admin-styles.css', array(), '1.0');
}
add_action('admin_enqueue_scripts', 'talentHR_enqueue_admin_styles');

// Enqueue styles for the front end if needed
function talentHR_enqueue_styles()
{
    if (!is_admin()) { // Load styles only on the front end
        wp_enqueue_style('talenthr-styles', plugin_dir_url(__FILE__) . 'admin-styles.css', array(), '1.0');
    }
}
add_action('wp_enqueue_scripts', 'talentHR_enqueue_styles');


// AJAX function to save domain name
add_action('wp_ajax_save_talenthr_domain_name', 'save_talenthr_domain_name');

function save_talenthr_domain_name()
{
    // Verify nonce
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'talenthr_nonce_action')) {
        // Check if domain name is set in the POST data
        if (isset($_POST['domain_name'])) {
            // Sanitize and save the domain name
            update_option('talenthr_domain_name', sanitize_text_field($_POST['domain_name']));
            // Send success response
            wp_send_json_success('Domain name saved successfully.');
        } else {
            // Send error response if domain name is not set
            wp_send_json_error('Domain name is missing.');
        }
    } else {
        // Send error response if nonce verification fails
        wp_send_json_error('Nonce verification failed.');
    }
}



// Add menu and submenu pages
function talentHR_add_menu()
{
    // Add main menu page for TalentHR
    $talenthr_menu = add_menu_page(
        'TalentHR',
        'TalentHR',
        'manage_options',
        'talenthr-settings',
        'talentHR_settings_page',
        'dashicons-admin-generic'
    );

    // Add submenu page for Settings
    add_submenu_page(
        'talenthr-settings',
        'Settings',
        'Settings',
        'manage_options',
        'talenthr-settings',
        'talentHR_settings_page'
    );

    // Add submenu page for Custom CSS
    add_submenu_page(
        'talenthr-settings',
        'Custom CSS',
        'Custom CSS',
        'manage_options',
        'custom-css',
        'custom_css_page_callback'
    );
}

// Settings page content
function talentHR_settings_page()
{
    if (isset($_POST['submit'])) {
        // Verify nonce
        if (isset($_POST['talenthr_settings_nonce']) && wp_verify_nonce($_POST['talenthr_settings_nonce'], 'talenthr_settings_action')) {
            // Nonce verification passed, process form data
            update_option('talenthr_domain_name', sanitize_text_field($_POST['talenthr_domain_name']));
?>
            <div class="updated">
                <p>Domain name saved successfully.</p>
            </div>
        <?php
        } else {
            // Nonce verification failed
        ?>
            <div class="error">
                <p>Security check failed. Please try again.</p>
            </div>
    <?php
        }
    }
    ?>
    <div class="wrap">
        <h1>TalentHR Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('talenthr_settings_action', 'talenthr_settings_nonce'); ?>
            <?php settings_fields('talenthr-settings-group'); ?>
            <?php do_settings_sections('talenthr-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="talenthr_domain_name">TalentHR Domain Name</label>
                    </th>
                    <td>
                        <div style="position: relative; display: inline;">
                            <div class="input-container">
                                <input type="text" name="talenthr_domain_name" id="talenthr_domain_name" value="<?php echo esc_attr(get_option('talenthr_domain_name')); ?>" />
                                <span class="domain-extension">.talenthr.io</span>
                            </div>
                            <button id="test-api-button" type="button" class="button-secondary">Test</button>
                            <span class="status-dot"></span>
                            <span id="api-status"></span>
                        </div>
                    </td>

                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="talenthr_shortcode">Job Positions Shortcode (Simple)</label>
                    </th>
                    <td>
                        <div style="position: relative; display: inline;">
                            <input type="text" id="simple-api-shortcode" value="[TalentHR-job-positions-simple]" readonly />
                            <div id="copied-simple-api-message">Copied</div>
                            <button id="shortcode-button" type="button" onclick="copySimpleApiShortcode()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="talenthr_shortcode">Job Positions Shortcode (Extended)</label>
                    </th>
                    <td>
                        <div style="position: relative; display: inline;">
                            <input type="text" name="talenthr_shortcode" id="extended-api-shortcode" value="[TalentHR-job-positions-extended]" readonly />
                            <div id="copied-message">Copied</div>
                            <button id="shortcode-button" type="button" onclick="copyShortcode()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </table>
            <input type="submit" name="submit" value="Save" class="button-primary" />
        </form>
        <!-- <div class="blog-link"></div> -->


        <div style="position: relative; margin-top: 20px;">
            <h2 id="api-response-title" style="display: none;">TalentHR Job Positions</h2>
            <div id="copied-response-message" style="position: absolute; top: -10px; right: 0; display: none; background-color: rgba(0, 0, 0, 0.5); color: #fff; padding: 5px 10px; border-radius: 5px;">Copied</div>
            <button type="button" id="copy-response-button" style="position: absolute; top: 30px; right: 0; display: none;" class="button-primary">Copy JSON</button>
            <pre id="api-response" class="response-body" style="display: none; margin-top: 50px;"></pre>
        </div>


    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('test-api-button').addEventListener('click', function() {
                var xhr = new XMLHttpRequest();
                var domainName = document.querySelector('input[name="talenthr_domain_name"]').value;
                var url = 'https://api-ext.talenthr.io/' + domainName + '/job-positions/published';
                // Add cache-busting parameter
                url += '?_=' + new Date().getTime();
                xhr.open('GET', url);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.onload = function() {
                    var dot = document.querySelector('.status-dot');
                    var statusElement = document.getElementById('api-status');
                    // Clear the existing response
                    document.getElementById('api-response').innerText = '';
                    if (xhr.status === 200) {
                        dot.style.backgroundColor = 'green';
                        statusElement.innerText = 'Response Status: ' + xhr.status;
                        document.getElementById('api-response-title').style.display = 'block'; // Show API response title
                        document.getElementById('api-response').style.display = 'block'; // Show API response pre element
                        // Prettify JSON response
                        var jsonResponse = JSON.parse(xhr.responseText);
                        var prettifiedJson = JSON.stringify(jsonResponse, null, 2);
                        document.getElementById('api-response').innerText = prettifiedJson;
                        // Show the copy button
                        document.getElementById('copy-response-button').style.display = 'inline-block';
                    } else {
                        dot.style.backgroundColor = 'red';
                        statusElement.innerText = 'Response Status: ' + xhr.status;
                        // Display specific message for non-200 responses
                        document.getElementById('api-response').innerText = 'Please check your domain name.';
                        // Hide the copy button
                        document.getElementById('copy-response-button').style.display = 'none';
                    }
                };
                xhr.send();
            });

            // Add event listener to copy button
            document.getElementById('copy-response-button').addEventListener('click', function() {
                // Select the text in the API response element
                var responseText = document.getElementById('api-response');
                var range = document.createRange();
                range.selectNode(responseText);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                // Copy the selected text
                document.execCommand('copy');
                // Deselect the text
                window.getSelection().removeAllRanges();
                // Show copied message next to the copy button
                document.getElementById('copied-response-message').style.display = 'inline-block';
                // Hide copied message after 1.5 seconds
                setTimeout(function() {
                    document.getElementById('copied-response-message').style.display = 'none';
                }, 1500);
            });
        });

        // Function to copy shortcode
        function copyShortcode() {
            var shortcodeInput = document.getElementById('extended-api-shortcode');
            shortcodeInput.select();
            document.execCommand('copy');
            var copiedMessage = document.getElementById('copied-message');
            copiedMessage.style.display = 'inline-block';
            setTimeout(function() {
                copiedMessage.style.display = 'none';
            }, 1500);
        }

        // Function to copy short API shortcode
        function copySimpleApiShortcode() {
            var simpleApiShortcodeInput = document.getElementById('simple-api-shortcode');
            simpleApiShortcodeInput.select();
            document.execCommand('copy');
            var copiedSimpleApiMessage = document.getElementById('copied-simple-api-message');
            copiedSimpleApiMessage.style.display = 'inline-block';
            setTimeout(function() {
                copiedSimpleApiMessage.style.display = 'none';
            }, 1500);
        }
        // Adjust the input field width dynamically
        document.getElementById('talenthr_domain_name').addEventListener('input', function() {
            var inputWidth = this.scrollWidth;
            this.style.width = inputWidth + 'px';
        });
    </script>

    </div>
    <?php
}

function get_domain_url()
{
    $domain_name = get_option('talenthr_domain_name'); // Fetching domain name from options

    // Check if domain name is empty
    if (empty($domain_name)) {
        return ''; // Return empty string if domain name is not set
    }

    // Construct the domain URL
    $url = 'https://api-ext.talenthr.io/' . $domain_name;

    return $url;
}


// Callback function to display the Custom CSS page
function custom_css_page_callback()
{
    if (isset($_POST['submit'])) {
        // Verify nonce
        if (isset($_POST['custom_css_nonce_field']) && wp_verify_nonce($_POST['custom_css_nonce_field'], 'custom_css_nonce')) {
            // Nonce verification passed, process form data
            update_option('custom_css_option', sanitize_text_field($_POST['custom_css_option']));
    ?>
            <div class="updated notice is-dismissible">
                <p>Your custom CSS saved successfully.</p>
            </div>
        <?php
        } else {
            // Nonce verification failed
        ?>
            <div class="error">
                <p>Security check failed. Please try again.</p>
            </div>
    <?php
        }
    }
    ?>
    <div class="wrap">
        <h1>Custom CSS</h1>
        <form method="post" action="">
            <?php settings_fields('custom_css_group'); ?>
            <?php wp_nonce_field('custom_css_nonce', 'custom_css_nonce_field'); ?>
            <div class="custom-css-container">
                <div class="custom-css-textarea">
                    <p>Enter your custom CSS below:</p>
                    <textarea name="custom_css_option" rows="15" cols="70"><?php echo esc_textarea(get_option('custom_css_option')); ?></textarea>
                    <?php submit_button('Save', 'primary', 'submit'); ?>
                </div>
                <div class="example-content">
                    <h2>Examples</h2>
                    <p>Here are some examples of CSS rules you can use:</p>
                    <h4><u>Widget</u></h4>
                    <ul>
                        <li><i>Change the font size of widget headings:</i> <br /><code>.widget-talenthr-job-title { font-size: 24px; }</code></li>
                        <li><i>Set a background color for widget locations & employment status:</i> <br /><code>.widget-talenthr-location, .widget-talenthr-employment-status { background-color: orange; }</code></li>
                        <li><i>Hide an element:</i> <br /><code>.widget-talenthr-department { display: none; }</code></li>
                    </ul>
                    <h4><u>Simple Shortcode</u></h4>
                    <ul>
                        <li><i>Change the font size of widget headings:</i> <br /><code>.talenthr-job-title-simple { font-size: 24px; }</code></li>
                        <li><i>Set a color for Simple Shortcode departments:</i> <br /><code>.talenthr-department-simple { color: orange; }</code></li>
                        <li><i>Set a color for Simple Shortcode locations only for city:</i> <br /><code>.talenthr-location-simple span { color: orange; }</code></li>
                        <li><i>Hide an element:</i> <br /><code>.talenthr-department-simple { display: none; }</code></li>
                    </ul>
                    <h4><u>Extended Shortcode</u></h4>
                    <ul>
                        <li><i>Change the font size of widget headings:</i> <br /><code>.talenthr-job-title-extended { font-size: 24px; }</code></li>
                        <li><i>Set color for Extended Shortcode locations & employment status:</i> <br /><code>.talenthr-location-extended, .talenthr-employment-status-extended { background-color: orange; }</code></li>
                        <li><i>Hide an element:</i> <br /><code>.talenthr-department-extended { display: none; }</code></li>
                    </ul>
                    <p>Feel free to customize these examples or add your own CSS rules!</p>
                </div>
            </div>
        </form>
    </div>
<?php
}

// Register settings for Custom CSS
function register_custom_css_settings()
{
    register_setting('custom_css_group', 'custom_css_option');
}

add_action('admin_init', 'register_custom_css_settings');


// Enqueue custom CSS
function enqueue_custom_css()
{
    $custom_css = get_option('custom_css_option');
    wp_add_inline_style('talenthr-styles', $custom_css);
}
add_action('wp_enqueue_scripts', 'enqueue_custom_css');
