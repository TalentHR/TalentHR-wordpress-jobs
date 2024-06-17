<?php
// Shortcode callback function
function talentHR_api_simple_shortcode()
{
    $domain_url = get_domain_url();
    $domain_name = get_option('talenthr_domain_name');


    // Check if domain URL is empty
    if (empty($domain_url)) {
        return '<p>No domain provided. Please set the domain name in plugin settings.</p>';
    }

    // Request arguments
    $args = array(
        'headers' => array(
            'Accept' => 'application/json',
        )
    );

    // Make API call
    $ping_results = wp_remote_get($domain_url . '/job-positions/published', $args);

    // Check for errors
    if (is_wp_error($ping_results)) {
        return '<p>Error occurred: ' . esc_html($ping_results->get_error_message()) . '</p>';
    } else {
        // Decode JSON response
        $response_body = wp_remote_retrieve_body($ping_results);
        $data = json_decode($response_body, true);

        // Check if data is successfully decoded
        if ($data && isset($data['success']) && $data['success'] === true) {
            // Extract job positions data
            $job_positions = $data['data'];

            // Output buffer
            ob_start();

            // Display job positions
            foreach ($job_positions['rows'] as $job) {
?>
                <div class="talenthr-job-box-simple">
                    <div class="job-details-simple">
                        <h4 class="talenthr-job-title-simple"><?php echo esc_html($job['job_position_title']); ?></h4>
                        <p class="talenthr-department-simple">Department: <span><strong><?php echo esc_html($job['department_name']); ?></strong></span></p>
                        <p class="talenthr-location-simple">Location: <span><strong><?php echo esc_html($job['location_name']); ?></strong></span></p>
                    </div> <!-- .talentHR-job -->
                    <?php
                    // Generate button link based on job data
                    $button_link = JOBS_BASE_URL . $domain_name . '/' . $job['slug'] . '/' . $job['id'];

                    ?>
                    <div class="apply-button-simple">
                        <a class="button-simple" href="<?php echo esc_url($button_link); ?>" class="button" target="_blank">Apply Now</a>
                    </div>
                </div>
            <?php
            } // end foreach

            // Return buffered content
            return ob_get_clean();
        } else {
            echo '<p>Failed to fetch job positions. Please check your domain name!</p>';
        }
    }
}

// Shortcode callback function
// Shortcode callback function
function talentHR_api_extended_shortcode()
{
    $domain_url = get_domain_url();
    $domain_name = get_option('talenthr_domain_name');


    // Check if domain URL is empty
    if (empty($domain_url)) {
        return '<p>No domain URL provided. Please set the domain URL in plugin settings.</p>';
    }

    // Request arguments
    $args = array(
        'headers' => array(
            'Accept' => 'application/json',
        )
    );

    // Make API call
    $ping_results = wp_remote_get($domain_url . '/job-positions/published', $args);

    // Check for errors
    if (is_wp_error($ping_results)) {
        return '<p>Error occurred: ' . esc_html($ping_results->get_error_message()) . '</p>';
    } else {
        // Decode JSON response
        $response_body = wp_remote_retrieve_body($ping_results);
        $data = json_decode($response_body, true);

        // Check if data is successfully decoded
        if ($data && isset($data['success']) && $data['success'] === true) {
            // Extract job positions data
            $job_positions = $data['data'];

            // Output buffer
            ob_start();

            // Display job positions
            foreach ($job_positions['rows'] as $job) {
            ?>

                <div class="talenthr-job-box-extended">
                    <div class="talenthr-job-extended">
                        <div class="title-button-container">
                            <h2 class="talenthr-job-title-extended"><?php echo esc_html($job['job_position_title']); ?></h2>
                            <?php
                            // Generate button link based on job data
                            $button_link = JOBS_BASE_URL . $domain_name . '/' . $job['slug'] . '/' . $job['id'];
                            ?>
                            <div class="apply-button-extended">
                                <a href="<?php echo esc_url($button_link); ?>" class="button" target="_blank">Apply now</a>
                            </div>
                        </div>
                        <p class="talenthr-department-extended"><?php echo esc_html($job['department_name']); ?></p>
                        <div class="talenthr-location-status-extended">
                            <div class="talenthr-location-extended">
                                <?php echo esc_html($job['location_name']); ?>
                            </div>
                            <div class="talenthr-employment-status-extended">
                                <?php echo esc_html($job['employment_status_name']); ?>
                            </div>
                        </div>
                        <!-- Description box -->
                        <div class="talenthr-description-box">
                            <p class="talenthr-description-label-extended">Description</p>
                            <div class="talenthr-description-extended">
                                <?php echo wp_kses_post($job['job_description']); ?>
                            </div>
                        </div>
                    </div> <!-- .talentHR-job-extended -->
                </div> <!-- .talentHR-job-box-extended -->

<?php
            } // end foreach

            // Return buffered content
            return ob_get_clean();
        } else {
            return '<p>No data found. Please check your domain name!</p>';
        }
    }
}


?>