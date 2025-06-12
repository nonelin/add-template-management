<?php
// 將外掛 templates 資料夾中的檔案動態加入頁面範本選單
add_filter('theme_page_templates', function($templates) {
    $plugin_templates_dir = plugin_dir_path(__FILE__) . 'templates/';
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
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/' . basename($selected);
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
    }
    return $template;
});