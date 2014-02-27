<?php
/*
Plugin Name: Mail()Man Subscribe
Plugin URI: https://stieglmaier.me
Description: Uses the php mail function to send subscribe mails to mailing lists. WPML compatible (WPML String Translation plugin required).
Author: Thomas Stieglmaier
Version: 1.0
Author URI: https://stieglmaier.me
License: MIT
*/

function loadMailmanSubscribeWidget() {
  register_widget("Mailman_Subscribe");
}

add_action('widgets_init', 'loadMailmanSubscribeWidget');

class Mailman_Subscribe extends WP_Widget {
  private $default_failure_message;
  private $default_subscribe_text;
  private $default_success_message;
  private $default_title;
  private $default_list;
  private $act_list;
  private $status_message = "";

  public function Mailman_Subscribe () {
    $this->default_failure_message = __('There was a problem processing your submission. Please try again.');
    $this->default_subscribe_text = __('Subscribe');
    $this->default_success_message = __('Thank you for subscribing to our mailing list!');
    $this->default_title = __('Subscribe to our mailing list');
    $this->default_list = __('list-subscribe@domain.de');
    $this->WP_Widget('Mailman_Subscribe', __('MailMan Subscription', 'Mailman_Subscribe'),
                     array('classname' => 'Mailman_Subscribe',
                           'description' => __( "Displays a subscription form for a MailMan mailing list.", 'Mailman_Subscribe')
                          )
                    );
    add_action('parse_request', array(&$this, 'process_submission'));
  }

  public function form ($instance) {
    $defaults = array(
      'failure_message' => $this->default_failure_message,
      'title' => $this->default_title,
      'subscribe_text' => $this->default_subscribe_text,
      'success_message' => $this->default_success_message,
      'mailingList' => $this->default_list
    );
    $vars = wp_parse_args($instance, $defaults);
    extract($vars);

    $form = '<h3>' . __('General Settings', 'Mailman_Subscribe') . '</h3>';

    // title for frontend
    $form .= '<p><label>' . __('Title :', 'Mailman_Subscribe') . '<input class="widefat" id=""' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $instance['title'] . '" /></label></p>';

      // mailinglist where one should subscribe
    $form .= '<p><label>' . __('Enter the subscribe E-Mail :', 'Mailman_Subscribe') . '<input class="widefat" id="' . $this->get_field_id('mailingList') . '" name="' . $this->get_field_name('mailingList') . '" type="text" value="' . $instance['mailingList'] . '" /></label></p>';

      // submit button text
    $form .= '<p><label>' . __('Submit Button Text :', 'Mailman_Subscribe') . '<input type="text" class="widefat" id="' . $this->get_field_id('subscribe_text') .'" name="' . $this->get_field_name('subscribe_text') . '" value="' . $instance['subscribe_text'] . '" /></label></p>';

      // notification texts
    $form .= '<h3>' . __('Notifications', 'Mailman_Subscribe') . '</h3><p>' . __('Use these fields to customize what your visitors see after they submit the form', 'Mailman_Subscribe') . '</p>';
    $form .= '<p><label>' . __('Success :', 'Mailman_Subscribe') . '<textarea class="widefat" id="' . $this->get_field_id('success_message') . '" name="' . $this->get_field_name('success_message') . '">' . $instance['success_message'] . '</textarea></label></p>';
    $form .= '<p><label>' . __('Failure :', 'Mailman_Subscribe') . '<textarea class="widefat" id="' . $this->get_field_id('failure_message') . '" name="' . $this->get_field_name('failure_message') . '">' . $instance['failure_message'] . '</textarea></label></p>';

    echo $form;
  }

  public function process_submission () {
    if (!empty($_POST[$this->id_base . '_email'])) {
      $options = get_option($this->option_name);
      $options = $options[$_POST['mailmanNumber']];
      if (is_email($_POST[$this->id_base . '_email'])) {
        $subject = "subscribe";
        $body = "";
        $headers = "From: " . $_POST[$this->id_base . '_email'];

        if (mail($options["mailingList"], $subject, $body, $headers)) {
          $this->status_message = icl_t("Mailman_Subscribe", 'Success Message', $options["success_message"]);
          return;
        }
      }
      $this->status_message = icl_t("Mailman_Subscribe", 'Failure Message', $options["failure_message"]);
    }
  }

  public function update ($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['mailingList'] = esc_attr($new_instance['mailingList']);
    $instance['failure_message'] = esc_attr($new_instance['failure_message']);
    $instance['subscribe_text'] = esc_attr($new_instance['subscribe_text']);
    $instance['success_message'] = esc_attr($new_instance['success_message']);
    $instance['title'] = esc_attr($new_instance['title']);
    if (function_exists("icl_register_string")) {
      $widgetName = "Mailman_Subscribe";
      icl_register_string($widgetName, 'Widget Title', $instance['title']);
      icl_register_string($widgetName, 'Failure Message', $instance['failure_message']);
      icl_register_string($widgetName, 'Success Message', $instance['success_message']);
      icl_register_string($widgetName, 'Subscribe Button Text', $instance['subscribe_text']);
    }
    return $instance;
  }

  public function widget ($args, $instance) {
    extract($args);
    $widget;
    if (function_exists("icl_t")) {
      $widgetName = "Mailman_Subscribe";
      $widget .= $before_widget . $before_title . icl_t($widgetName, "Widget Title", $instance['title']) . $after_title;
      $widget .= $this->status_message;
      $widget .= '<form class="mailman_subscribe_widget" action="' . $_SERVER['REQUEST_URI'] . '" id="' . $this->id_base . '_form-' . $this->number . '" method="post">' . '<input type="text" name="' . $this->id_base . '_email" placeholder="E-Mail..." /><input type="hidden" name="mailmanNumber" value="' . $this->number . '" /><input type="submit" name="' . icl_t($widgetName, "Subscribe Button Text", $instance['subscribe_text']) . '" value="' . icl_t($widgetName, "Subscribe Button Text", $instance['subscribe_text']) . '" /></form>';
      $widget .= $after_widget;
    } else {
      $widget .= $before_widget . $before_title . $instance['title'] . $after_title;
      $widget .= $this->status_message;
      $widget .= '<form class="mailman_subscribe_widget" action="' . $_SERVER['REQUEST_URI'] . '" id="' . $this->id_base . '_form-' . $this->number . '" method="post">' . '<input type="text" name="' . $this->id_base . '_email" placeholder="E-Mail..." /><input type="hidden" name="mailmanNumber" value="' . $this->number . '" /><input type="submit" name="' . __($instance['subscribe_text'], 'Mailman_Subscribe') . '" value="' . __($instance['subscribe_text'], 'Mailman_Subscribe') . '" /></form>';
      $widget .= $after_widget;
    }
    echo $widget;
  }
}
?>
