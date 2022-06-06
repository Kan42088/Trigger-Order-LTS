<?php

/**
 * @package triggerOrderPlugin
 */

//use PHPMailer\PHPMailer\PHPMailer;

/*
  Plugin Name: Trigger Order
  Plugin URI: https://clipartdesign.net
  Description: Trigger completed orders. The 1.3.5 can detect link unzip with key word: 'cutt.ly', 'bit.ly', 'drive.google.com', 'bitly.com.vn'
  Version: 1.3.5
  Author: Bito
  Author URI: https://www.facebook.com/vuongpham99 
  Text Domain: trigger-order
  */

defined('ABSPATH') or die('Hey, why r u runnin!');

add_filter('woocommerce_defer_transactional_emails', '__return_true');

// Call extra_post_info_menu function to load plugin menu in dashboard 
add_action('admin_menu', 'trigger_order_menu');
// Create WordPress admin menu 
if (!function_exists("trigger_order_menu")) {
    function trigger_order_menu()
    {
        $page_title = 'Trigger Order';
        $menu_title = 'Trigger Order';
        $capability = 'manage_options';
        $menu_slug  = 'trigger-order';
        $function   = 'trigger_order_page';
        $icon_url   = 'dashicons-email-alt2';
        $position   = 50;
        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
        // Call update_extra_post_info function to update database   
        add_action('admin_init', 'update_trigger_order_options');
    }
}

// Create function to register plugin settings in the database 
if (!function_exists("update_trigger_order_options")) {
    function update_trigger_order_options()
    {
        //trelo param
        register_setting('trigger-order-settings', 'to-trello-key');
        register_setting('trigger-order-settings', 'to-trello-secret');
        register_setting('trigger-order-settings', 'to-trello-boardId');
        register_setting('trigger-order-settings', 'to-trello-source-listId');

        //email param
        register_setting('trigger-order-settings', 'to-email-username');
        register_setting('trigger-order-settings', 'to-email-password');
        register_setting('trigger-order-settings', 'to-email-sender-name');
        register_setting('trigger-order-settings', 'to-email-subject-oc');
        register_setting('trigger-order-settings', 'to-email-content-oc');
        register_setting('trigger-order-settings', 'to-email-subject-pu');
        register_setting('trigger-order-settings', 'to-email-content-pu');
    }


    //ad cdn ajax jquery
    add_action('admin_footer', 'my_to_footer_scripts');
    function my_to_footer_scripts()
    {
?>
        <script>
            function tlscr(ipid) {
                var x = document.getElementById(ipid);
                if (x.type === "password") {
                    x.type = "text";
                } else {
                    x.type = "password";
                }
            }
        </script>
        <?php
    }

    //try to add ajax for call selection onchange
    add_action('admin_enqueue_scripts', 'so_enqueue_scripts');
    function so_enqueue_scripts()
    {
        wp_register_script(
            'ajaxHandle',
            plugins_url('/jquery.ajax.js', __FILE__),
            array(),
            false,
            true
        );
        wp_enqueue_script('ajaxHandle');
        wp_localize_script(
            'ajaxHandle',
            'ajax_object',
            array('ajaxurl' => admin_url('admin-ajax.php'))
        );
    }

    //ajax function handling
    add_action("wp_ajax_myaction", "so_wp_ajax_function");

    function so_wp_ajax_function()
    {
        //DO whatever you want with data posted
        //To send back a response you have to echo the result!
        global $wpdb;
        $data = $_POST['boardID'];
        $query = $wpdb->prepare("UPDATE `{$wpdb->prefix}options` SET `option_value`= %s WHERE option_name ='to-trello-boardId'", $data);
        $results = $wpdb->query($query);
        echo $results;
        wp_die(); // ajax call must die to avoid trailing 0 in your response
    }



    //get data from trello
    function make_trello_request_get_board()
    {
        $key = get_option('to-trello-key');
        $secret = get_option('to-trello-secret');
        $strUrl = 'https://api.trello.com/1/members/me/boards?fields=name,url&key=' . $key . '&token=' . $secret;
        $response = wp_remote_get($strUrl);
        try {

            // Note that we decode the body's response since it's the actual JSON feed
            $json = json_decode($response['body']);
        } catch (Exception $ex) {
            $json = null;
        } // end try/catch

        return $json;
    }


    function make_trello_request_get_list()
    {
        $boardId = get_option('to-trello-boardId');
        $key = get_option('to-trello-key');
        $secret = get_option('to-trello-secret');
        $strUrl = 'https://api.trello.com/1/boards/' . $boardId . '/lists?fields=id,name,url&key=' . $key . '&token=' . $secret;
        $response = wp_remote_get($strUrl);
        try {

            // Note that we decode the body's response since it's the actual JSON feed
            $json = json_decode($response['body']);
        } catch (Exception $ex) {
            $json = null;
        } // end try/catch

        return $json;
    }
    // Create WordPress plugin page 
    if (!function_exists("trigger_order_page")) {
        function trigger_order_page()
        {
        ?>
            <h1>Trigger order options:</h1>
            <form method="post" action="options.php">
                <?php settings_fields('trigger-order-settings'); ?>
                <?php do_settings_sections('trigger-order-settings'); ?>

                <table class="form-table">
                    <tr>
                        <h3>Trello configs:</h3>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Trello Key:</th>
                        <td>
                            <input type="password" name="to-trello-key" value="<?php echo get_option('to-trello-key'); ?>" id="tlkey">
                            <input type="checkbox" onclick="tlscr('tlkey')">Show Key
                        </td>

                    </tr>
                    <tr>
                        <th scope="row">Trello secret:</th>
                        <td>
                            <input type="password" name="to-trello-secret" value="<?php echo get_option('to-trello-secret'); ?>" id="tlSecret">
                            <input type="checkbox" onclick="tlscr('tlSecret')">Show Password
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Board:</th>
                        <td>
                            <select name="to-trello-boardId" id="board">
                                <?php foreach (make_trello_request_get_board() as $board) {
                                    if (get_option('to-trello-boardId') == $board->id) {
                                ?>
                                        <option value="<?php echo ($board->id) ?>" selected><?php echo ($board->name) ?></option>
                                    <?php } else { ?>
                                        <option value="<?php echo ($board->id) ?>"><?php echo ($board->name) ?></option>
                                <?php }
                                } ?>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Source List:</th>
                        <td>
                            <select name="to-trello-source-listId" id="slist">
                                <?php foreach (make_trello_request_get_list() as $slist) {
                                    if (get_option('to-trello-source-listId') == $slist->id) {
                                ?>
                                        <option value="<?php echo ($slist->id) ?>" selected><?php echo ($slist->name) ?></option>
                                    <?php } else { ?>
                                        <option value="<?php echo ($slist->id) ?>"><?php echo ($slist->name) ?></option>
                                <?php }
                                } ?>

                            </select>
                        </td>
                    </tr>
                </table>
                <table class="form-table">
                    <tr>
                        <h3>Email configs:</h3>
                    </tr>
                    <tr>
                        <th>UserName</th>
                        <td>
                            <input type="text" name="to-email-username" value="<?php echo get_option('to-email-username'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>Password</th>
                        <td>
                            <input type="password" name="to-email-password" value="<?php echo get_option('to-email-password'); ?>" id="mailPass">
                            <input type="checkbox" onclick="tlscr('mailPass')">Show Password
                        </td>
                    </tr>
                    <tr>
                        <th>Sender Name</th>
                        <td>
                            <input type="text" name="to-email-sender-name" value="<?php echo get_option('to-email-sender-name'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>Email subject (order create)</th>
                        <td>
                            <input type="text" name="to-email-subject-oc" value="<?php echo get_option('to-email-subject-oc'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>Email content (order create)</th>
                        <td>
                            <?php
                            $content = get_option('to-email-content-oc');
                            $editor_id = 'to-email-content-oc';
                            $settings = array('textarea_name' => 'to-email-content-oc', 'media_buttons' => false);
                            echo wp_editor($content, $editor_id, $settings);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Email subject (product update)</th>
                        <td>
                            <input type="text" name="to-email-subject-pu" value="<?php echo get_option('to-email-subject-pu'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>Email content (product update)</th>
                        <td>
                            <?php
                            $content = get_option('to-email-content-pu');
                            $editor_id = 'to-email-content-pu';
                            $settings = array('textarea_name' => 'to-email-content-pu', 'media_buttons' => false);
                            echo wp_editor($content, $editor_id, $settings);
                            ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Variables available:</h2>
            <p>{customer}: first name of customer</p>
            <p>{orderId}: id of 'this time' order </p>
            <p>{downloads}: downloadable files of 'this time' order (only on product update content) </p>

<?php

        }
    }

    require_once(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php');
    require_once(ABSPATH . WPINC . '/PHPMailer/SMTP.php');
    require_once(ABSPATH . WPINC . '/PHPMailer/Exception.php');

    // define the woocommerce_order_status_completed callback 
    function action_woocommerce_order_status_completed($orderId)
    {
        //to trello
        $sListId = get_option('to-trello-source-listId');
        $tlkey = get_option('to-trello-key');
        $tlsecret = get_option('to-trello-secret');

        $orderThis = wc_get_order($orderId);
        $items = $orderThis->get_items();
        $currentDomain = $_SERVER['SERVER_NAME'];

        $waitProductCount = 0;

        foreach ($items as $item) {
            $checkCondition = false;
            try {
                $productId = $item->get_product_id();

                $product = wc_get_product($productId);
                $imgId = $product->get_image_id();
                $images = wp_get_attachment_image_src($imgId, 'full');
                $image = "";
                if ($images != false) {
                    $image = $images[0];
                }

                if (!$product->is_downloadable()) {
                    $checkCondition = true;
                } else if ($product->is_downloadable()) {
                    $downloads = $product->get_downloads();
                    if (count($downloads) == 0) {
                        $checkCondition = true;
                    } else if (count($downloads) > 0) {
                        $download_link = "";
                        $downCount = 0;
                        foreach ($downloads as $dld) {
                            if ($downCount == 1) {
                                break;
                            }
                            $download_link = $dld->get_file();
                            $downCount++;
                        }

                        if (empty($download_link)) {
                            $checkCondition = true;
                        } else if (str_contains($download_link, "cutt.ly") || str_contains($download_link, "bit.ly") || str_contains($download_link, "bitly.com.vn") || str_contains($download_link, "drive.google.com")) {
                            $checkCondition = true;
                        }
                    }
                }
                if ($checkCondition == true) {
                    $waitProductCount++;
                    try {
                        //make card
                        $strUrl1 = 'https://api.trello.com/1/cards?idList=' . $sListId;
                        $response = wp_remote_post(
                            $strUrl1,
                            array(
                                'method'      => 'POST',
                                'timeout'     => 45,
                                'redirection' => 5,
                                'httpversion' => '1.0',
                                'blocking'    => true,
                                'headers'     => array(
                                    'Accept' => 'application/json'
                                ),
                                'body'        => array(
                                    'name' => $product->get_sku() . '-' . $product->get_title(),
                                    'desc' => $currentDomain,
                                    'urlSource' => $image,
                                    'key' => $tlkey,
                                    'token' => $tlsecret
                                ),
                                'cookies'     => array()
                            )
                        );
                        //get id card
                        $card = json_decode($response['body']);
                        $cardId = $card->id;
                        //add id to label
                        $strUrl1 = 'https://api.trello.com/1/cards/' . $cardId . '/labels?color=green&name=' . $product->get_id();

                        $response = wp_remote_post(
                            $strUrl1,
                            array(
                                'method'      => 'POST',
                                'timeout'     => 45,
                                'redirection' => 5,
                                'httpversion' => '1.0',
                                'blocking'    => true,
                                'headers'     => array(
                                    'Accept' => 'application/json'
                                ),
                                'body'        => array(
                                    'key' => $tlkey,
                                    'token' => $tlsecret
                                ),
                                'cookies'     => array()
                            )
                        );
                        $orderThis->add_order_note("Complete send to trello product:" . $product->get_title());
                    } catch (Exception $ex) {
                        $orderThis->add_order_note("Error when add card " . $product->get_title() . ":" . $ex);
                        $orderThis->save();
                    }
                }
            } catch (Exception $ex) {
                $orderThis->add_order_note("Error when process order details: " . $ex);
                $orderThis->save();
            }
        }


        if ($waitProductCount > 0) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            $ocMess = get_option('to-email-content-oc');
            $ocMess = str_replace('{customer}', $orderThis->get_billing_first_name(), $ocMess);
            $ocMess = str_replace('{orderId}', $orderId, $ocMess);
            $ocMess = str_replace(array("\r", "\n", "\r\n"), '<br>', $ocMess);

            $ocSub = get_option('to-email-subject-oc');
            $ocSub = str_replace('{customer}', $orderThis->get_billing_first_name(), $ocSub);
            $ocSub = str_replace('{orderId}', $orderId, $ocSub);

            $customerEmail = $orderThis->get_billing_email();

            $mRes = send_smtp_email($mail, $ocMess, $ocSub, $customerEmail);


            $orderThis->add_order_note("This order was trigged and the mail status:" . $mRes);
        }
        $orderThis->save();
    };
}
// add the action 
add_action('woocommerce_order_status_completed', 'action_woocommerce_order_status_completed', 10, 1);

//send mail
function send_smtp_email($mail, $mailMess, $maillSub, $customerEmail)
{
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Username = get_option('to-email-username');
    $mail->Password = get_option('to-email-password');
    $mail->setFrom(get_option('to-email-username'), get_option('to-email-sender-name'));
    //$mail->addReplyTo('vuong1999pzo@gmail.com', 'haiz');
    $mail->addAddress($customerEmail, $customerEmail);
    $mail->Subject = $maillSub;
    //$mail->msgHTML(file_get_contents('message.html'), __DIR__);
    $mail->isHTML(true);
    $mail->Body = $mailMess;
    //$mail->addAttachment('test.txt');
    if (!$mail->send()) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return 'Mailer Error: ' . $mail->ErrorInfo;
    } else {
        error_log('The email message was sent.');
        return 'The email message was sent.';
    }
}



// define the woocommerce_api_edit_product callback 
// function action_woocommerce_api_edit_product($id, $data)
// {
//     //why it work??
//     $mail3 = new PHPMailer\PHPMailer\PHPMailer(true);
//     send_smtp_email($mail3, 'hie?\r' . $id, 'phai khong', 'vuong1999pzo@gmail.com');
// };
//add_action('woocommerce_api_edit_product', 'action_woocommerce_api_edit_product', 10, 2);



add_action('woocommerce_update_product', 'lg_set_transferd_flag', 10, 1);
function lg_set_transferd_flag($productId)
{
    $times = did_action('woocommerce_update_product');
    if ($times > 1) {
        return;
    }

    $orderIds = get_orders_ids_by_product_id($productId);

    foreach ($orderIds as $oid) {
        $orderThis = wc_get_order($oid);
        $items = $orderThis->get_items();
        $isAllCompleted = true;
        foreach ($items as $item) {
            $product = $item->get_product();
            if (!$product->is_downloadable()) {
                $isAllCompleted = false;
                break;
            }
        }

        if ($isAllCompleted == true) {

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            $puMess = get_option('to-email-content-pu');
            $puMess = str_replace('{customer}', $orderThis->get_billing_first_name(), $puMess);
            $puMess = str_replace('{orderId}', $oid, $puMess);
            $puMess = str_replace(array("\r", "\n", "\r\n"), '<br>', $puMess);

            $productLinks = "";
            foreach ($items as $item) {
                $product = $item->get_product();
                $downloads = $product->get_downloads();
                $productLinks .= "<br>" . $product->get_name() . ":";
                foreach ($downloads as $key => $each_download) {
                    $productLinks .= "<a href='" . $each_download["file"] . "'>Click here to download</a><br>";
                }
                $productLinks .= "----------------<br>";
            }
            $puMess = str_replace('{downloads}', $productLinks, $puMess);

            $puSub = get_option('to-email-subject-pu');
            $puSub = str_replace('{customer}', $orderThis->get_billing_first_name(), $puSub);
            $puSub = str_replace('{orderId}', $oid, $puSub);




            $customerEmail = $orderThis->get_billing_email();

            send_smtp_email($mail, $puMess, $puSub, $customerEmail);
        }
    }
}

//get all order by product id
function get_orders_ids_by_product_id($product_id, $order_status = array('wc-completed'))
{
    global $wpdb;

    $results = $wpdb->get_col("
            SELECT order_items.order_id
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
            LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
            WHERE posts.post_type = 'shop_order'
            AND posts.post_status IN ( '" . implode("','", $order_status) . "' )
            AND order_items.order_item_type = 'line_item'
            AND order_item_meta.meta_key = '_product_id'
            AND order_item_meta.meta_value = '$product_id'
    ");

    return $results;
}
