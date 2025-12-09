<?php
/**
 * Plugin Name: Chatly
 * Description: Chatly Chatbot: lightweight, easy-to-use plugin for instant predefined answers on your site.
 * Version: 1.0.0
 * Author: DigiFox Technologies
 * Author URI: https://digifox.lk/
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
if (!defined('CHATLY_VERSION')) {
  define('CHATLY_VERSION', '1.0.0');
}
if (!defined('CHATLY_PLUGIN_DIR')) {
  define('CHATLY_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('CHATLY_PLUGIN_URL')) {
  define('CHATLY_PLUGIN_URL', plugin_dir_url(__FILE__));
}

class Chatly_Chatbot {

  public function __construct() {
    add_action('admin_menu', [$this, 'create_admin_page']);
    add_action('admin_init', [$this, 'register_settings']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    add_action('wp_footer', [$this, 'render_chatbot']);
    add_action('admin_head', [$this, 'admin_icon_style']);
  }

  public function admin_icon_style() {
    ?>
    <style>
      #adminmenu #toplevel_page_chatly-chatbot .wp-menu-image img {
        width: 20px;
        height: 20px;
        padding: 6px 0;
        opacity: 0.6;
        filter: brightness(0) invert(1);
      }
      #adminmenu #toplevel_page_chatly-chatbot:hover .wp-menu-image img,
      #adminmenu #toplevel_page_chatly-chatbot.current .wp-menu-image img {
        opacity: 1;
      }
    </style>
    <?php
  }

  public function create_admin_page() {
    add_menu_page(
      'Chatly',
      'Chatly',
      'manage_options',
      'chatly-chatbot',
      [$this, 'admin_page_html'],
      CHATLY_PLUGIN_URL . 'includes/img/chat-icon.svg',
      80
    );
  }

  public function register_settings() {
    register_setting('chatly_chatbot_options', 'chatly_chatbot_settings', [
      'type' => 'array',
      'sanitize_callback' => [$this, 'sanitize_settings'],
      'default' => [],
    ]);
    register_setting('chatly_chatbot_options', 'chatly_chatbot_responses', [
      'type' => 'array',
      'sanitize_callback' => [$this, 'sanitize_responses'],
      'default' => [],
    ]);

    add_settings_section('chatly_chatbot_main', 'Chatbot Settings', null, 'chatly-chatbot');

    $fields = [
      'branding_name' => 'Branding Name',
      'branding_logo' => 'Brand Logo URL',
      'chat_avatar' => 'Chat Avatar URL (bot image)',
      'short_description' => 'Short Description',
      'primary_color' => 'Primary Color (Header & Buttons)',
      'bubble_position' => 'Bubble Position (right / left)',
    ];

    foreach ($fields as $id => $label) {
      add_settings_field($id, $label, [$this, 'render_field'], 'chatly-chatbot', 'chatly_chatbot_main', ['id' => $id]);
    }

    add_settings_section('chatly_chatbot_res', 'Response Messages', null, 'chatly-chatbot');
    add_settings_field('responses', 'Responses', [$this, 'render_responses_field'], 'chatly-chatbot', 'chatly_chatbot_res');
  }

  public function render_field($args) {
    $options = get_option('chatly_chatbot_settings');
    $id = esc_attr($args['id']);
    $value = isset($options[$id]) ? esc_attr($options[$id]) : '';
    echo "<input type='text' name='chatly_chatbot_settings[$id]' value='$value' style='width:100%;max-width:400px'>";
  }

  public function sanitize_settings($input) {
    if (!is_array($input)) return [];
    $out = [];
    $out['branding_name'] = isset($input['branding_name']) ? sanitize_text_field($input['branding_name']) : '';
    $out['branding_logo'] = isset($input['branding_logo']) ? esc_url_raw($input['branding_logo']) : '';
    $out['chat_avatar'] = isset($input['chat_avatar']) ? esc_url_raw($input['chat_avatar']) : '';
    $out['short_description'] = isset($input['short_description']) ? sanitize_text_field($input['short_description']) : '';
    $out['primary_color'] = isset($input['primary_color']) ? sanitize_hex_color($input['primary_color']) : '#004aad';
    $pos = isset($input['bubble_position']) ? strtolower($input['bubble_position']) : 'right';
    $out['bubble_position'] = in_array($pos, ['right','left'], true) ? $pos : 'right';
    return $out;
  }

  public function sanitize_responses($input) {
    if (!is_array($input)) return [];
    $out = [];
    foreach ($input as $item) {
      if (!is_array($item)) continue;
      $q = isset($item['question']) ? sanitize_text_field($item['question']) : '';
      $a = isset($item['answer']) ? wp_kses_post($item['answer']) : '';
      if ($q === '' && $a === '') continue;
      $out[] = [ 'question' => $q, 'answer' => $a ];
    }
    return $out;
  }

  public function render_responses_field() {
    // Ensure $responses is always an array
    $responses = get_option('chatly_chatbot_responses', []);
    if (!is_array($responses)) {
        $responses = [];
    }
    ?>
    <div id="response-wrapper">
      <?php foreach ($responses as $i => $res): ?>
        <div class="response-item" style="margin-bottom:10px;">
          <input type="text" name="chatly_chatbot_responses[<?php echo intval($i); ?>][question]" value="<?php echo esc_attr($res['question'] ?? ''); ?>" placeholder="Question" style="width:45%;margin-right:10px;">
          <input type="text" name="chatly_chatbot_responses[<?php echo intval($i); ?>][answer]" value="<?php echo esc_attr($res['answer'] ?? ''); ?>" placeholder="Answer" style="width:45%;">
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="button" id="chatly-add-response">+ Add New Response</button>
    <script>
      (function() {
        'use strict';
        document.getElementById('chatly-add-response').addEventListener('click', function() {
          const container = document.getElementById('response-wrapper');
          const index = container.children.length;
          const div = document.createElement('div');
          div.className = 'response-item';
          div.style.marginBottom = '10px';
          
          const questionInput = document.createElement('input');
          questionInput.type = 'text';
          questionInput.name = 'chatly_chatbot_responses[' + index + '][question]';
          questionInput.placeholder = 'Question';
          questionInput.style.width = '45%';
          questionInput.style.marginRight = '10px';
          
          const answerInput = document.createElement('input');
          answerInput.type = 'text';
          answerInput.name = 'chatly_chatbot_responses[' + index + '][answer]';
          answerInput.placeholder = 'Answer';
          answerInput.style.width = '45%';
          
          div.appendChild(questionInput);
          div.appendChild(answerInput);
          container.appendChild(div);
        });
      })();
    </script>
    <?php
  }

  public function admin_page_html() {
    // Security check
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'chatly'));
    }
    ?>
    <div class="wrap" style="max-width:900px;margin-top:20px;">
      <h1 style="margin-bottom:14px;">Chatly Chatbot Settings</h1>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 6px 18px rgba(16,24,40,0.06);">
        <div style="padding:18px 20px;border-bottom:1px solid #eef0f2;display:flex;align-items:center;justify-content:space-between;">
          <div style="font-weight:600;font-size:15px;">General</div>
          <div style="font-size:12px;color:#64748b;">Customize branding and quick responses</div>
        </div>
        <form method="post" action="options.php" style="padding:20px;">
          <?php
          settings_fields('chatly_chatbot_options');
          do_settings_sections('chatly-chatbot');
          submit_button('Save Settings', 'primary', null, false, [ 'style' => 'padding:8px 16px;border-radius:6px;' ]);
          ?>
        </form>
      </div>
    </div>
    <?php
  }

  public function enqueue_scripts() {
    wp_enqueue_style('chatly-style', CHATLY_PLUGIN_URL . 'includes/css/styles.css', [], CHATLY_VERSION);
    wp_enqueue_script('chatly-js', CHATLY_PLUGIN_URL . 'includes/js/main.js', ['jquery'], CHATLY_VERSION, true);

    $responses = get_option('chatly_chatbot_responses', []);
    if (!is_array($responses)) {
        $responses = [];
    }

    // Sanitize settings for output
    $settings = get_option('chatly_chatbot_settings', []);
    if (!is_array($settings)) {
        $settings = [];
    }

    wp_localize_script('chatly-js', 'chatly_data', [
      'responses' => wp_json_encode($responses),
      'settings' => wp_json_encode($settings),
      'ajax_url' => esc_url(admin_url('admin-ajax.php')),
      'nonce' => wp_create_nonce('chatly_ai_nonce'),
      'has_ai' => false,
    ]);
  }

  public function render_chatbot() {
    $opt = get_option('chatly_chatbot_settings', []);
    
    // Sanitize and validate all user inputs
    $primary = !empty($opt['primary_color']) ? sanitize_hex_color($opt['primary_color']) : '#154bd1';
    if (!$primary) {
      $primary = '#154bd1'; // Fallback if invalid hex color
    }
    
    $logo = !empty($opt['branding_logo']) ? esc_url($opt['branding_logo']) : CHATLY_PLUGIN_URL . 'includes/img/chat-icon-blue.png';
    $avatar = !empty($opt['chat_avatar']) ? esc_url($opt['chat_avatar']) : CHATLY_PLUGIN_URL . 'includes/img/chat-icon-blue.png';
    $name = !empty($opt['branding_name']) ? sanitize_text_field($opt['branding_name']) : 'Chatly Chatbot';
    $desc = !empty($opt['short_description']) ? sanitize_text_field($opt['short_description']) : 'Chatly Chatbot: lightweight, easy-to-use plugin for instant predefined answers on your site.';
    
    // Strict position validation
    $position = isset($opt['bubble_position']) && in_array($opt['bubble_position'], ['left', 'right'], true) ? $opt['bubble_position'] : 'right';
    
    $predefined = get_option('chatly_chatbot_responses', []);
    if (!is_array($predefined)) { $predefined = []; }
    ?>

    <div id="chatly-chat-bubble" class="<?php echo esc_attr($position); ?>" style="background: <?php echo esc_attr($primary); ?>" data-tooltip="<?php esc_attr_e('Chat with us!', 'chatly'); ?>">
      <img src="<?php echo esc_url(CHATLY_PLUGIN_URL . 'includes/img/chat-icon.svg'); ?>" alt="<?php esc_attr_e('Chat', 'chatly'); ?>" class="chatly-bubble-icon">
      <span class="chatly-tooltip"><?php esc_html_e('Chat with us!', 'chatly'); ?></span>
    </div>

    <div id="chatly-chat-window" style="--chatly-logo:url('<?php echo esc_url($logo); ?>'); --chatly-avatar:url('<?php echo esc_url($avatar); ?>')">
      <div class="chatly-header" style="background: <?php echo esc_attr($primary); ?>">
        <div class="chatly-header-title">
          <strong><?php echo esc_html($name); ?></strong>
        </div>
        <div class="chatly-header-actions">
          <button id="chatly-close" aria-label="Close">×</button>
        </div>
      </div>

      <div class="chatly-messages">
        <div class="chatly-welcome-card">
          <div class="chatly-welcome-banner">
            <img src="<?php echo esc_url($logo); ?>" class="chatly-logo" alt="<?php echo esc_attr($name); ?> Logo">
            <div class="chatly-welcome-copy">
              <h3>Chatly</h3>
              <p><?php echo esc_html($desc); ?></p>
            </div>
          </div>
          <div class="chatly-quick-actions">
            <?php if (!empty($predefined)) : ?>
              <?php foreach ($predefined as $item): $q = isset($item['question']) ? trim($item['question']) : ''; if ($q==='') continue; ?>
                <button class="chatly-chip" data-prompt="<?php echo esc_attr($q); ?>"><span class="chatly-chip-icon"></span><?php echo esc_html($q); ?></button>
              <?php endforeach; ?>
            <?php else: ?>
              <button class="chatly-chip" data-prompt="Admissions"><span class="chatly-chip-icon"></span>Admissions</button>
              <button class="chatly-chip" data-prompt="Courses and Programs"><span class="chatly-chip-icon"></span>Courses and Programs</button>
              <button class="chatly-chip" data-prompt="Fees and Payments"><span class="chatly-chip-icon"></span>Fees and Payments</button>
              <button class="chatly-chip" data-prompt="Contact Chatly"><span class="chatly-chip-icon"></span>Contact Chatly</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="chatly-input-area">
        <input id="chatly-user-input" type="text" placeholder="Type your message..." />
        <button id="chatly-send" aria-label="Send" style="background: <?php echo esc_attr($primary); ?>">
          <span class="chatly-send-icon">➤</span>
        </button>
      </div>

      <a href="https://digifox.lk" target="_blank" rel="noopener noreferrer">
        <div class="chatly-footer">
          <img src="<?php echo esc_url(CHATLY_PLUGIN_URL . 'includes/img/digifox-logo.png'); ?>" class="chatly-footer-logo" alt="<?php esc_attr_e('DigiFox Logo', 'chatly'); ?>">
          <span><?php esc_html_e('Solution by DigiFox Technologies', 'chatly'); ?></span>
        </div>
      </a>
    </div>
    <?php
  }
}

new Chatly_Chatbot();