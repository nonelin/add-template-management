<?php
/**
 * @package Add_Tamplate_Management
 * @version 1.0.0
 */
/*
Plugin Name: 增加範本管理
Plugin URI: https://github.com/nonelin/add-template-management
Description: 從後臺上傳範本檔案，方便在頁面中選擇範本，只會管理外掛所上傳的範本檔案，此外掛功能目的，為了方便建立獨立範本檔案。
Author: AWei
Version: 1.0.0
Author URI: https://dafatime.idv.tw/
*/

// 增加自訂選單
function add_tamplate_files_menu() {
    // 使用WordPress提供的add_menu_page()函數創建一個新的管理選單
    add_menu_page(
      '自訂範本管理',   // 選單主標題
      '自訂範本管理',   // 選單名稱
      'manage_options', // 許可權
      'add_tamplate_files', // 選單ID 必須唯一
      'add_tamplate_dashboard_html', // 要放入的內容函數
      'dashicons-analytics', // 選單圖標
      99 // 選單位置
    );

    // 添加子菜單 - 增加範本
    add_submenu_page(
      'add_tamplate_files', // 父菜單ID
      '增加範本', // 頁面標題
      '增加範本', // 菜單名稱
      'manage_options', // 許可權
      'add_tamplate_template', // 子菜單ID
      'add_tamplate_files_html' // 要放入的內容函數
    );

    // 添加子菜單 - 增加CSS
    add_submenu_page(
      'add_tamplate_files',
      '增加CSS',
      '增加CSS',
      'manage_options',
      'add_tamplate_css',
      'add_tamplate_css_html'
    );

    // 添加子菜單 - 增加JS
    add_submenu_page(
      'add_tamplate_files',
      '增加JS',
      '增加JS',
      'manage_options',
      'add_tamplate_js',
      'add_tamplate_js_html'
    );
}

// 將custom_menu函數增加到WordPress的admin_menu鉤子
add_action('admin_menu', 'add_tamplate_files_menu');

// 添加 WP_list_table 類別，顯示已上傳的範本、CSS、JS 檔案列表
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Add_Template_List_Table extends WP_List_Table {
    private $items_data = [];

    public function __construct($items = []) {
        parent::__construct([
            'singular' => 'template',
            'plural'   => 'templates',
            'ajax'     => false,
        ]);
        $this->items_data = $items;
    }

    public function get_columns() {
        return [
            'name'          => '檔案名稱',
            'template_name' => '範本名稱',
            'time'          => '時間',
            'actions'       => '動作',
        ];
    }

    public function get_sortable_columns() {
        return [
            'name' => ['name', false],
            'time' => ['time', false],
        ];
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $items = $this->items_data;
        usort($items, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });

        $this->items = $items;
    }

    public function column_name($item) {
        return esc_html($item['name']);
    }

    public function column_template_name($item) {
        return esc_html($item['template_name']);
    }

    public function column_time($item) {
        return esc_html(date('Y-m-d H:i:s', $item['time']));
    }

    public function column_actions($item) {
        return sprintf(
            '<form method="post" style="display:inline;"><input type="hidden" name="delete_template" value="%s"><button type="submit" class="button button-small" onclick="return confirm(\'確定要刪除此範本檔案嗎？\');">刪除</button></form>',
            esc_attr($item['name'])
        );
    }

    public function no_items() {
        return '目前沒有已上傳的範本檔案。';
    }
}

class Add_File_List_Table extends WP_List_Table {
    private $items_data = [];
    private $file_type = '';
    private $delete_field = '';
    private $no_items_text = '';

    public function __construct($items = [], $file_type = '檔案', $delete_field = 'delete_file') {
        parent::__construct([
            'singular' => 'file',
            'plural'   => 'files',
            'ajax'     => false,
        ]);
        $this->items_data = $items;
        $this->file_type = $file_type;
        $this->delete_field = $delete_field;
        $this->no_items_text = sprintf('目前沒有已上傳的 %s。', $file_type);
    }

    public function get_columns() {
        return [
            'name'    => '檔案名稱',
            'size'    => '檔案大小',
            'time'    => '上傳時間',
            'actions' => '動作',
        ];
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = ['name' => ['name', false], 'time' => ['time', false]];

        $this->_column_headers = [$columns, $hidden, $sortable];

        $items = $this->items_data;
        usort($items, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });

        $this->items = $items;
    }

    public function column_name($item) {
        return esc_html($item['name']);
    }

    public function column_size($item) {
        return esc_html(size_format($item['size']));
    }

    public function column_time($item) {
        return esc_html(date('Y-m-d H:i:s', $item['time']));
    }

    public function column_actions($item) {
        return sprintf(
            '<form method="post" style="display:inline;"><input type="hidden" name="%s" value="%s"><button type="submit" class="button button-small" onclick="return confirm(\'確定要刪除此 %s 嗎？\');">刪除</button></form>',
            esc_attr($this->delete_field),
            esc_attr($item['name']),
            esc_html($this->file_type)
        );
    }

    public function no_items() {
        return $this->no_items_text;
    }
}

// 自訂選單主頁面內容
function add_tamplate_dashboard_html() {
?>
<div class="wrap">
<h1>自訂範本管理</h1>
<p>歡迎使用自訂範本管理外掛。</p>
<p>您可以在側邊菜單中選擇以下功能：</p>
<ul style="list-style-type: disc; margin-left: 20px;">
  <li><strong>增加範本</strong> - 上傳 PHP 格式的範本檔案</li>
  <li><strong>增加CSS</strong> - 上傳 CSS 檔案</li>
  <li><strong>增加JS</strong> - 上傳 JavaScript 檔案</li>
</ul>
</div>
<?php
}

// 自訂選單要內頁放入的內容 - 範本上傳
function add_tamplate_files_html() {
  // 檢查是否有檔案上傳請求
  if (isset( $_FILES['template_file']) && current_user_can('manage_options') && check_admin_referer('upload_template_file', 'template_file_nonce')) {
    $upload_info = wp_upload_dir();
    $upload_dir = trailingslashit( $upload_info['basedir'] ) . 'atm/templates/';
    if (!file_exists($upload_dir)) {
      wp_mkdir_p($upload_dir);
    }

    // 安全：加入 .htaccess 與 index.html 避免直接執行或列目錄
    $htaccess = $upload_dir . '.htaccess';
    if ( ! file_exists( $htaccess ) ) {
        @file_put_contents( $htaccess, "# Prevent direct access and script execution\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    <FilesMatch \"\\.(php|phtml|php3)$\">\n        Order allow,deny\n        Deny from all\n    </FilesMatch>\n</IfModule>\n" );
    }
    $index_file = $upload_dir . 'index.html';
    if ( ! file_exists( $index_file ) ) {
        @file_put_contents( $index_file, "<!doctype html><meta charset=\"utf-8\"><title>Forbidden</title>" );
    }

    $file = $_FILES['template_file'];

    // 基本檢查
    if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
      echo '<div class="error"><p>上傳檔案來源錯誤。</p></div>';
    } else {
      $max_size = 512 * 1024; // 512KB 上限
      if ( $file['size'] > $max_size ) {
        echo '<div class="error"><p>檔案過大，最大允許 512KB。</p></div>';
      } else {
        $filename = sanitize_file_name( basename( $file['name'] ) );
        $filename = preg_replace('/\\0/', '', $filename); // 移除 null bytes
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // 讀取前 512 bytes 作為樣本（避免在除錯時使用未定義變數）
        $start = '';
        if ( isset( $file['tmp_name'] ) && is_readable( $file['tmp_name'] ) ) {
            $h = fopen( $file['tmp_name'], 'rb' );
            $start = $h ? fread( $h, 512 ) : '';
            if ( $h ) fclose( $h );
        }

        // 移除 WP 的檢查函式，僅依副檔名與內容檢查
        if ( $ext !== 'php' ) {
            echo '<div class="error"><p>只允許上傳 php 檔案（副檔名非 php）。</p></div>';
        } else {
            // 進入後續的內容檢查

          // 檔案內容檢查（確保包含 PHP 標記或 Template Name）
          // 前 512 bytes 已在上方讀取到 $start
          if ( strpos( $start, '<?php' ) === false && stripos( $start, 'template name' ) === false ) {
            echo '<div class="error"><p>檔案內容不包含 PHP 標記或範本標頭，拒絕上傳。</p></div>';
          } else {
            // 避免覆蓋已有檔案
            $base = pathinfo( $filename, PATHINFO_FILENAME );
            $target = $upload_dir . $filename;
            $i = 1;
            while ( file_exists( $target ) ) {
              $target = $upload_dir . $base . '-' . $i . '.' . $ext;
              $i++;
            }

            if ( move_uploaded_file( $file['tmp_name'], $target ) ) {
              @chmod( $target, 0644 );
              echo '<div class="updated"><p>檔案上傳成功！</p></div>';
            } else {
              echo '<div class="error"><p>檔案上傳失敗。</p></div>';
            }
          }
        }
      }
    }
  }
?>
<div class="wrap">
<h1>上傳範本檔案</h1>
<div class="upload-plugin-wrap">
  <div class="upload-plugin" style="display: block; padding: 0;">
  <p class="install-help">請在這裡上傳 php 格式的範本檔案。</p>
  <form method="post" enctype="multipart/form-data" class="wp-upload-form" style="display: ruby-base;">
    <?php wp_nonce_field('upload_template_file', 'template_file_nonce'); ?>
    <div class="file-upload-field">
      <label class="screen-reader-text" for="templatefile">選擇檔案</label>
      <input class type="file" id="templatefile" name="template_file" required />
    </div>
    <p class="submit">
      <input type="submit" name="submit" class="button button-primary" value="上傳檔案" />
    </p>
  </form>
  </div>
</div>
<?php
// 取得範本資料夾中的所有檔案
$upload_info = wp_upload_dir();
$template_dir = trailingslashit( $upload_info['basedir'] ) . 'atm/templates/';
if ( ! file_exists( $template_dir ) ) {
    wp_mkdir_p( $template_dir );
}
$template_files = glob($template_dir . '*.php');

// 處理檔案刪除請求
if(isset($_POST['delete_template']) && current_user_can('manage_options')) {
  $file_to_delete = sanitize_text_field($_POST['delete_template']);
  $file_path = $template_dir . basename($file_to_delete);
  
  if(file_exists($file_path) && unlink($file_path)) {
    echo '<div class="updated"><p>檔案已成功刪除！</p></div>';
  } else {
    echo '<div class="error"><p>刪除檔案時發生錯誤。</p></div>';
  }
}
?>

<h2>已上傳的範本檔案</h2>
<?php 
$template_files = glob($template_dir . '*.php');
if ( false === $template_files ) {
    $template_files = [];
}
$template_items = [];
foreach ($template_files as $file) {
    $filename = basename($file);
    $file_time = filemtime($file);
    $file_contents = file_exists($file) ? file_get_contents($file) : '';

    if (preg_match('/Template Name:\\s*(.+)/i', $file_contents, $matches)) {
        $template_name = trim($matches[1]);
    } else {
        $template_name = preg_replace('/\\.php$/', '', $filename);
    }

    $template_items[] = [
        'name'          => $filename,
        'template_name' => $template_name,
        'time'          => $file_time,
    ];
}

if ( empty($template_items) ) {
    echo '<div class="notice notice-info"><p>目前沒有已上傳的範本檔案。</p></div>';
} else {
    $template_table = new Add_Template_List_Table($template_items);
    $template_table->prepare_items();
    $template_table->display();
}
?>

</div>
<?php
}

// CSS 上傳功能
function add_tamplate_css_html() {
  // 檢查是否有檔案上傳請求
  if (isset($_FILES['css_file']) && current_user_can('manage_options') && check_admin_referer('upload_css_file', 'css_file_nonce')) {
    $upload_info = wp_upload_dir();
    $upload_dir = trailingslashit($upload_info['basedir']) . 'atm/css/';
    if (!file_exists($upload_dir)) {
      wp_mkdir_p($upload_dir);
    }

    // 安全：加入 .htaccess 與 index.html
    $htaccess = $upload_dir . '.htaccess';
    if (!file_exists($htaccess)) {
      @file_put_contents($htaccess, "# Allow CSS files\n<FilesMatch \"\\.css$\">\n    Allow from all\n</FilesMatch>\n");
    }
    $index_file = $upload_dir . 'index.html';
    if (!file_exists($index_file)) {
      @file_put_contents($index_file, "<!doctype html><meta charset=\"utf-8\"><title>Forbidden</title>");
    }

    $file = $_FILES['css_file'];

    // 基本檢查
    if (!is_uploaded_file($file['tmp_name'])) {
      echo '<div class="error"><p>上傳檔案來源錯誤。</p></div>';
    } else {
      $max_size = 2 * 1024 * 1024; // 2MB 上限
      if ($file['size'] > $max_size) {
        echo '<div class="error"><p>檔案過大，最大允許 2MB。</p></div>';
      } else {
        $filename = sanitize_file_name(basename($file['name']));
        $filename = preg_replace('/\\0/', '', $filename);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($ext !== 'css') {
          echo '<div class="error"><p>只允許上傳 CSS 檔案（副檔名需為 .css）。</p></div>';
        } else {
          // 避免覆蓋已有檔案
          $base = pathinfo($filename, PATHINFO_FILENAME);
          $target = $upload_dir . $filename;
          $i = 1;
          while (file_exists($target)) {
            $target = $upload_dir . $base . '-' . $i . '.css';
            $i++;
          }

          if (move_uploaded_file($file['tmp_name'], $target)) {
            @chmod($target, 0644);
            echo '<div class="updated"><p>CSS 檔案上傳成功！</p></div>';
          } else {
            echo '<div class="error"><p>CSS 檔案上傳失敗。</p></div>';
          }
        }
      }
    }
  }
?>
<div class="wrap">
<h1>上傳 CSS 檔案</h1>
<div class="upload-plugin-wrap">
  <div class="upload-plugin" style="display: block; padding: 0;">
  <p class="install-help">請在這裡上傳 CSS 格式的檔案。</p>
  <form method="post" enctype="multipart/form-data" class="wp-upload-form" style="display: ruby-base;">
    <?php wp_nonce_field('upload_css_file', 'css_file_nonce'); ?>
    <div class="file-upload-field">
      <label class="screen-reader-text" for="cssfile">選擇檔案</label>
      <input class type="file" id="cssfile" name="css_file" accept=".css" required />
    </div>
    <p class="submit">
      <input type="submit" name="submit" class="button button-primary" value="上傳檔案" />
    </p>
  </form>
  </div>
</div>
<?php
// 取得 CSS 資料夾中的所有檔案
$upload_info = wp_upload_dir();
$css_dir = trailingslashit($upload_info['basedir']) . 'atm/css/';
if (!file_exists($css_dir)) {
  wp_mkdir_p($css_dir);
}
$css_files = glob($css_dir . '*.css');

// 處理檔案刪除請求
if(isset($_POST['delete_css']) && current_user_can('manage_options')) {
  $file_to_delete = sanitize_text_field($_POST['delete_css']);
  $file_path = $css_dir . basename($file_to_delete);
  
  if(file_exists($file_path) && unlink($file_path)) {
    echo '<div class="updated"><p>CSS 檔案已成功刪除！</p></div>';
  } else {
    echo '<div class="error"><p>刪除 CSS 檔案時發生錯誤。</p></div>';
  }
}
?>

<h2>已上傳的 CSS 檔案</h2>
<?php
$css_files = glob($css_dir . '*.css');
if ( false === $css_files ) {
    $css_files = [];
}
$css_items = [];
foreach ($css_files as $file) {
    $css_items[] = [
        'name' => basename($file),
        'size' => filesize($file),
        'time' => filemtime($file),
    ];
}

if ( empty($css_items) ) {
    echo '<div class="notice notice-info"><p>目前沒有已上傳的 CSS 檔案。</p></div>';
} else {
    $css_table = new Add_File_List_Table($css_items, 'CSS 檔案', 'delete_css');
    $css_table->prepare_items();
    $css_table->display();
}
?>

</div>
<?php
}

// JS 上傳功能
function add_tamplate_js_html() {
  // 檢查是否有檔案上傳請求
  if (isset($_FILES['js_file']) && current_user_can('manage_options') && check_admin_referer('upload_js_file', 'js_file_nonce')) {
    $upload_info = wp_upload_dir();
    $upload_dir = trailingslashit($upload_info['basedir']) . 'atm/js/';
    if (!file_exists($upload_dir)) {
      wp_mkdir_p($upload_dir);
    }

    // 安全：加入 .htaccess 與 index.html
    $htaccess = $upload_dir . '.htaccess';
    if (!file_exists($htaccess)) {
      @file_put_contents($htaccess, "# Allow JS files\n<FilesMatch \"\\.js$\">\n    Allow from all\n</FilesMatch>\n");
    }
    $index_file = $upload_dir . 'index.html';
    if (!file_exists($index_file)) {
      @file_put_contents($index_file, "<!doctype html><meta charset=\"utf-8\"><title>Forbidden</title>");
    }

    $file = $_FILES['js_file'];

    // 基本檢查
    if (!is_uploaded_file($file['tmp_name'])) {
      echo '<div class="error"><p>上傳檔案來源錯誤。</p></div>';
    } else {
      $max_size = 2 * 1024 * 1024; // 2MB 上限
      if ($file['size'] > $max_size) {
        echo '<div class="error"><p>檔案過大，最大允許 2MB。</p></div>';
      } else {
        $filename = sanitize_file_name(basename($file['name']));
        $filename = preg_replace('/\\0/', '', $filename);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($ext !== 'js') {
          echo '<div class="error"><p>只允許上傳 JS 檔案（副檔名需為 .js）。</p></div>';
        } else {
          // 避免覆蓋已有檔案
          $base = pathinfo($filename, PATHINFO_FILENAME);
          $target = $upload_dir . $filename;
          $i = 1;
          while (file_exists($target)) {
            $target = $upload_dir . $base . '-' . $i . '.js';
            $i++;
          }

          if (move_uploaded_file($file['tmp_name'], $target)) {
            @chmod($target, 0644);
            echo '<div class="updated"><p>JS 檔案上傳成功！</p></div>';
          } else {
            echo '<div class="error"><p>JS 檔案上傳失敗。</p></div>';
          }
        }
      }
    }
  }
?>
<div class="wrap">
<h1>上傳 JS 檔案</h1>
<div class="upload-plugin-wrap">
  <div class="upload-plugin" style="display: block; padding: 0;">
  <p class="install-help">請在這裡上傳 JavaScript 格式的檔案。</p>
  <form method="post" enctype="multipart/form-data" class="wp-upload-form" style="display: ruby-base;">
    <?php wp_nonce_field('upload_js_file', 'js_file_nonce'); ?>
    <div class="file-upload-field">
      <label class="screen-reader-text" for="jsfile">選擇檔案</label>
      <input class type="file" id="jsfile" name="js_file" accept=".js" required />
    </div>
    <p class="submit">
      <input type="submit" name="submit" class="button button-primary" value="上傳檔案" />
    </p>
  </form>
  </div>
</div>
<?php
// 取得 JS 資料夾中的所有檔案
$upload_info = wp_upload_dir();
$js_dir = trailingslashit($upload_info['basedir']) . 'atm/js/';
if (!file_exists($js_dir)) {
  wp_mkdir_p($js_dir);
}
$js_files = glob($js_dir . '*.js');

// 處理檔案刪除請求
if(isset($_POST['delete_js']) && current_user_can('manage_options')) {
  $file_to_delete = sanitize_text_field($_POST['delete_js']);
  $file_path = $js_dir . basename($file_to_delete);
  
  if(file_exists($file_path) && unlink($file_path)) {
    echo '<div class="updated"><p>JS 檔案已成功刪除！</p></div>';
  } else {
    echo '<div class="error"><p>刪除 JS 檔案時發生錯誤。</p></div>';
  }
}
?>

<h2>已上傳的 JS 檔案</h2>
<?php
$js_files = glob($js_dir . '*.js');
if ( false === $js_files ) {
    $js_files = [];
}
$js_items = [];
foreach ($js_files as $file) {
    $js_items[] = [
        'name' => basename($file),
        'size' => filesize($file),
        'time' => filemtime($file),
    ];
}

if ( empty($js_items) ) {
    echo '<div class="notice notice-info"><p>目前沒有已上傳的 JS 檔案。</p></div>';
} else {
    $js_table = new Add_File_List_Table($js_items, 'JS 檔案', 'delete_js');
    $js_table->prepare_items();
    $js_table->display();
}
?>

</div>
<?php
}

include 'function.php';




