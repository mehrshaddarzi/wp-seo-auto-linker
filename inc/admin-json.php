<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Import/Export', 'seoal'); ?></h1>
    <?php
    if (isset($_GET['call']) and $_GET['call'] == "export") {
        global $wpdb;

        // Get Directory
        $upload_dir = wp_upload_dir(null, false);

        // Plist File Name Created
        $file_name = 'backup-seo-auto-linker-' . date("Y-m-d") . '-' . time() . '.json';

        // Get Default Path
        $fullPath     = rtrim($upload_dir['basedir'], "/") . '/' . 'seo-json/';
        $default_link = rtrim($upload_dir['baseurl'], "/") . '/' . 'seo-json/';
        if ( ! file_exists($fullPath)) {
            @mkdir($fullPath, 0777, true);
        }

        // File Content
        $json_content            = array();
        $json_content['post']    = array();
        $json_content['options'] = get_option('seoal_options'); // use Update Option
        $post_type               = SEO_Auto_Linker_Base::POST_TYPE;
        $query                   = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}posts` WHERE `post_type` = '{$post_type}' AND `post_status` != 'auto-draft'", ARRAY_A);
        foreach ($query as $item) {
            $ar                                = $item;
            $ar['meta_list']                   = array_map(function ($n) {
                return $n[0];
            }, get_post_meta($item['ID']));
            $json_content['post'][$item['ID']] = $ar;
        }

        // Generate File
        $fileLocation = rtrim($fullPath, "/") . "/" . $file_name;
        $file         = fopen($fileLocation, "w");
        fwrite($file, json_encode($json_content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        fclose($file);

        // Return File Link
        $jsonLink = rtrim($default_link, "/") . '/' . $file_name;

        // Show Complete Notice
        $class   = 'notice notice-success is-dismissible';
        $message = __('Your backup file is ready.', 'seoal') . ' ';
        $message .= '<a href="' . $jsonLink . '" style="color:blue;">( ' . __('Download file', 'seoal') . ' ) </a>';
        echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
    }


    // Upload File and Import
    if (isset($_FILES['uploadedFile']) && $_FILES['uploadedFile']['error'] === UPLOAD_ERR_OK) {
        global $wpdb;

        // get details of the uploaded file
        $fileTmpPath   = $_FILES['uploadedFile']['tmp_name'];
        $fileName      = $_FILES['uploadedFile']['name'];
        $fileSize      = $_FILES['uploadedFile']['size'];
        $fileType      = $_FILES['uploadedFile']['type'];
        $fileNameCmps  = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Check File Type
        $error = false;
        if ($fileExtension != "json") {
            $error   = true;
            $class   = 'notice notice-error';
            $message = __('Please Select json file.', 'seoal') . ' ';
            echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
        }

        // Get File Content
        // @see https://stackoverflow.com/questions/9101048/php-file-post-upload-without-save
        $contents = file_get_contents($_FILES['uploadedFile']['tmp_name']);
        $array    = json_decode($contents, true);
        if ($array === null) {
            $error   = true;
            $class   = 'notice notice-error';
            $message = __('Please Select json file.', 'seoal') . ' ';
            echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
        }

        // Check Standard Jsn for This Plugin
        if ( ! isset($array['options']) || ! isset($array['options']['blacklist']) || ! isset($array['post']) || (isset($array['post']) and empty($array['post']))) {
            $error   = true;
            $class   = 'notice notice-error';
            $message = __('Please Select json file.', 'seoal') . ' ';
            echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
        }

        // Save Option
        if ($error === false) {
            update_option('seoal_options', $array['options']);

            // Save Posts
            $post_type = SEO_Auto_Linker_Base::POST_TYPE;
            foreach ($array['post'] as $post_id => $post_data) {
                // Check Post ID Exist in same ID
                $exist = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}posts` WHERE `ID` = {$post_id} AND `post_type` = '{$post_type}'");
                if ($exist == 0 and $post_data['post_type'] == $post_type) {
                    $_insert_to_post = $post_data;
                    unset($_insert_to_post['ID']);
                    unset($_insert_to_post['meta_list']);

                    // Insert To Table Post
                    $wpdb->insert($wpdb->prefix . 'posts', $_insert_to_post);
                    $post_new_id = $wpdb->insert_id;

                    // Save Post Metadata
                    foreach ($post_data['meta_list'] as $meta_key => $meta_value) {
                        $wpdb->insert($wpdb->prefix . 'postmeta', array(
                            'post_id'    => $post_new_id,
                            'meta_key'   => $meta_key,
                            'meta_value' => $meta_value
                        ));
                    }
                }
            }

            // Show Complete Notice
            $class   = 'notice notice-success is-dismissible';
            $message = __('Import Data was successful.', 'seoal') . ' ';
            echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
        }
    }
    ?>

    <div style="padding: 20px 15px 15px 15px;">
        <!-- Export -->
        <div id="seoal-keywords-export" class="postbox">
            <div class="inside">
                <h3><?php _e("Backup Data", "seoal"); ?></h3>
                <p><?php _e("Click the button below to get the full list of inputs", "seoal"); ?></p>
                <a href="<?php echo admin_url('edit.php?post_type=seoal_container&page=seo-auto-linker-json&call=export'); ?>" class="button-secondary"><?php esc_attr_e('Get json file', "seoal"); ?></a>
            </div>
        </div>

        <!-- import -->
        <div id="seoal-keywords-import" class="postbox">
            <div class="inside">
                <h3><?php _e("Import Data", "seoal"); ?></h3>
                <p><?php _e("To upload data, please select the file and then click the button", "seoal"); ?></p>
                <form method="post" action="<?php echo admin_url('edit.php?post_type=seoal_container&page=seo-auto-linker-json'); ?>" enctype="multipart/form-data">
                    <table class="form-table">
                        <tr valign="top">
                            <td scope="row"><label for="tablecell"><?php esc_attr_e(
                                        'json file', 'seoal'
                                    ); ?></label></td>
                            <td><input type="file" name="uploadedFile" id="uploadedFile" accept=".json" required/></td>
                        </tr>

                        <tr valign="top">
                            <td scope="row"></td>
                            <td>
                                <input class="button-primary" type="submit" value="<?php esc_attr_e('Import', "seoal"); ?>">
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>


    </div>
</div>