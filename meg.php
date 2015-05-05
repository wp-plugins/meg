<?php
/*
 Plugin Name: Mobile Marketing Apps by Meg.com
 Plugin URI: https://apps.meg.com
 Description: One-Stop-Shop for Easy Mobile Marketing
 Author: Stuzo
 Version: 1.1
 Author URI: https://meg.com
 */
define('MEG_BASEURL', 'https://apps.meg.com');
define('MEG_OPTIONS_KEY', 'meg_options');


function meg_Validate($hash_id) {
    $url = MEG_BASEURL . '/builder/v1/profile/%s/validate';
    $request = new WP_Http;
    $payload = array(
        'url' => site_url()
    );
    $response = $request->request(
        sprintf($url, $hash_id),
        array(
            'body' => json_encode($payload),
            'headers' => array('content-type' => 'application/json'),
            'method' => 'POST'
        )
    );
    if(is_wp_error($response)) {
        $response->add('1003', "Sorry, we can't reach the Meg.com to confirm your Meg ID. Please try again later or contact <a href='mailto:support@meg.com'>support@meg.com</a> for setup assistance.");
        return $response;
    }
    $result = json_decode($response['body'], true);
    if(!$result || $response['response']['code'] != 200) {
        $error = new WP_Error;
        $error->add('1003', "Sorry, we can't reach the Meg.com to confirm your Meg ID. Please try again later or contact <a href='mailto:support@meg.com'>support@meg.com</a> for setup assistance.");
        return $error;
    }
    if(!$result['allowed'] || !$result['exists']) {
        $error = new WP_Error;
        if(!$result['exists']) {
            $error->add('1001', "Sorry, we can't find that Meg ID. Check your installation code.");
        }
        if($result['exists'] && !$result['allowed']) {
            $error->add('1002', "Sorry, that Meg ID is registered to a different website. Log in to <a href='https://apps.meg.com' target='_blank'>apps.meg.com</a> to check your settings.");
        }
        return $error;
    }
    return true;
}

function meg_Options() {
    $options = get_option(MEG_OPTIONS_KEY);
    return is_array($options) ? $options : array('meg_id' => '');
}

function meg_getMegId() {
    $options=  meg_Options();
    return $options['meg_id'];
}


function meg_tpl_jsloader() {
    $scriptSrc = MEG_BASEURL . "/embedjs/" . meg_getMegId();
    ?>
    <script>(function() {
            var script = document.createElement("script");
            script.src = "<?php echo $scriptSrc;?>";
            script.async = true;
            var entry = document.getElementsByTagName("script")[0];
            entry.parentNode.insertBefore(script, entry);
    }());</script>
<?php
}



function meg_tpl_settings_section($arg) {
echo <<<END
    <p>Enter your Meg ID to add your Meg Apps to this site.<br/>
    To find your Meg ID, log into the <a target="_blank" href="https://apps.meg.com/store">Meg Manager</a> and select Channel Management from the menu at the top right.</p>
    <p>Questions?<br/>
    Check out our <a target="_blank" href="https://meg.zendesk.com/hc/en-us">FAQs</a> or contact <a href="mailto:support@meg.com">support@meg.com</a>.</p>
END;
}
function meg_tpl_input_meg_id() {
    $options = meg_Options();
    echo "<input id='meg_id' name='".MEG_OPTIONS_KEY."[meg_id]' size='40' type='text' value='{$options['meg_id']}' />";
}
function meg_admin_init(){
    register_setting( MEG_OPTIONS_KEY, MEG_OPTIONS_KEY, 'meg_options_validate' );
    add_settings_section('meg_main_settings_section', '', 'meg_tpl_settings_section', 'meg_plugin');
    add_settings_field('meg_main_config', 'Meg ID', 'meg_tpl_input_meg_id', 'meg_plugin', 'meg_main_settings_section');

}
function meg_options_validate($input) {
    $options = meg_Options();
    $options['meg_id'] = trim($input['meg_id']);
    if(!preg_match('/^[a-z0-9]+$/i', $options['meg_id'])) {
        $options['meg_id'] = '';
    }
    if($options['meg_id']) {
        $valid = meg_Validate($options['meg_id']);
        if( is_wp_error( $valid ) ) {
            foreach ($valid->get_error_messages() as $error) {
                add_settings_error(MEG_OPTIONS_KEY, 'meg_invalid_id', $error, $type = 'error');
            }

        }
    }
    return $options;
}
function meg_tpl_admin_page() {
?>
    <div>
        <h2>MEG</h2>
        <form action="options.php" method="post">
            <?php settings_fields(MEG_OPTIONS_KEY); ?>
            <?php do_settings_sections('meg_plugin'); ?>
            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
         </form>
    </div>
<?php
}


function meg_admin_menu() {
    add_options_page('Meg PAGE', 'Meg Settings', 'manage_options', 'meg_plugin', 'meg_tpl_admin_page');
}

add_action('wp_head',    'meg_tpl_jsloader' );
add_action('admin_init', 'meg_admin_init');
add_action('admin_menu', 'meg_admin_menu');
