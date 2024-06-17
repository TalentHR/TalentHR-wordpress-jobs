<?php

// Register the custom widget
function register_talentHR_widget()
{
    register_widget('TalentHR_Widget');
}
add_action('widgets_init', 'register_talentHR_widget');

class TalentHR_Widget extends WP_Widget
{
    // Widget constructor
    function __construct()
    {
        parent::__construct(
            'talenthr_widget', // Base ID
            'TalentHR Job Positions', // Widget name
            array('description' => 'Displays job positions') // Widget description
        );
    }

    // Custom widget frontend display
    public function widget($args, $instance)
    {
        echo wp_kses_post( $args['before_widget'] );

        // Widget title
        $title = !empty($instance['title']) ? $instance['title'] : 'Job Positions';
        // echo wp_kses_post( $args['before_title'] ) . apply_filters( 'widget_title', $title ) . wp_kses_post( $args['after_title'] );
        echo wp_kses_post( $args['before_title'] ) . esc_html( apply_filters( 'widget_title', $title ) ) . wp_kses_post( $args['after_title'] );
        



        // Get domain URL
        $domain_url = 'https://api-ext.talenthr.io/'; // Define the base API URL
        $domain_name = get_option('talenthr_domain_name');

        // Check if domain URL is empty
        if (empty($domain_url)) {
            echo '<p>No domain provided. Please set the domain name in plugin settings.</p>';
        } else {
            // Request arguments
            $args = array(
                'headers' => array(
                    'Accept' => 'application/json',
                )
            );

            // Make API call
            $response = wp_remote_get($domain_url . $domain_name . '/job-positions/published', $args);

            // Check for errors
            if (is_wp_error($response)) {
                echo '<p>Error occurred: ' . esc_html($response->get_error_message()) . '</p>';
            } else {
                // Decode JSON response
                $job_positions = json_decode(wp_remote_retrieve_body($response), true);

                // Check if data is successfully decoded
                if ($job_positions && isset($job_positions['success']) && $job_positions['success'] === true) {
                    // Extract job positions data
                    $jobs = $job_positions['data']['rows'];

                    // Display job positions
                    if (!empty($jobs)) {
                        echo '<ul class="talenthr-job-list">';
                        foreach ($jobs as $job) {
                            // Construct the job URL
                            $job_url = $domain_url . '/' . $job['slug'] . '/' . $job['id'];
                            // Output the job as a clickable link
                            echo '<li class="widget-talenthr-job">';
                            echo '<a href="' . esc_url($job_url) . '" target="_blank">';
                            // Output job details with class names
                            echo '<p class="widget-talenthr-job-title">' . esc_html($job['job_position_title']) . '</p>';
                            echo '<div class="widget-talenthr-department">' . esc_html($job['department_name']) . '</div>';
                            echo '<div class="widget-talenthr-location-status">';
                            echo '<div class="widget-talenthr-location">' . esc_html($job['location_name']) . '</div>';
                            echo '<div class="widget-talenthr-employment-status">' . esc_html($job['employment_status_name']) . '</div>';
                            echo '</div>';
                            echo '</a></li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p>No job positions found.</p>';
                    }
                } else {
                    echo '<p>Failed to fetch job positions. Please check your domain name!</p>';
                }
            }
        }

        echo wp_kses_post( $args['after_widget'] );

    }

    // Widget backend form
    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : ''; ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id('title') ); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">

        </p>
<?php }

    // Widget update
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? wp_strip_all_tags($new_instance['title']) : '';
        return $instance;
    }
}
