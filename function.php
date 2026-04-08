<?php
// 將外掛 上傳至 uplode/atm/templates 資料夾中的檔案動態加入頁面範本選單
add_filter('theme_page_templates', function($templates) {
    $upload_info = wp_upload_dir();
    $plugin_templates_dir = trailingslashit( $upload_info['basedir'] ) . 'atm/templates/';
    if (file_exists($plugin_templates_dir)) {
        $files = glob($plugin_templates_dir . '*.php');
        foreach ($files as $file) {
            $filename = basename($file);
            // 範本名稱取至範本檔案內的Template Name註解名稱
            $file_contents = file_get_contents($file);
            if (preg_match('/Template Name:\\s*(.+)/i', $file_contents, $matches)) {
                $template_name = trim($matches[1]);
            } else {
                $template_name = preg_replace('/\\.php$/', '', $filename);
            }
            $templates['add-template/' . $filename] = '[P] ' . $template_name;
        }
    }
    return $templates;
});

// 讓 WordPress 能正確載入外掛範本
add_filter('template_include', function($template) {
    if (is_page()) {
        $selected = get_page_template_slug(get_queried_object_id());
        if (strpos($selected, 'add-template/') === 0) {
            $upload_info = wp_upload_dir();
            $plugin_template = trailingslashit( $upload_info['basedir'] ) . 'atm/templates/' . basename($selected);
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
    }
    return $template;
});

// 在前端加載 CSS 檔案
add_action('wp_enqueue_scripts', function() {
    $upload_info = wp_upload_dir();
    $css_dir = trailingslashit($upload_info['basedir']) . 'atm/css/';
    $css_url = trailingslashit($upload_info['baseurl']) . 'atm/css/';
    
    if (file_exists($css_dir)) {
        $css_files = glob($css_dir . '*.css');
        foreach ($css_files as $file) {
            $filename = basename($file);
            $handle = 'atm-css-' . pathinfo($filename, PATHINFO_FILENAME);
            wp_enqueue_style($handle, $css_url . $filename, [], filemtime($file));
        }
    }
});

// 在前端加載 JS 檔案
add_action('wp_enqueue_scripts', function() {
    $upload_info = wp_upload_dir();
    $js_dir = trailingslashit($upload_info['basedir']) . 'atm/js/';
    $js_url = trailingslashit($upload_info['baseurl']) . 'atm/js/';
    
    if (file_exists($js_dir)) {
        $js_files = glob($js_dir . '*.js');
        foreach ($js_files as $file) {
            $filename = basename($file);
            $handle = 'atm-js-' . pathinfo($filename, PATHINFO_FILENAME);
            wp_enqueue_script($handle, $js_url . $filename, [], filemtime($file), true);
        }
    }
}, 11);