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
      '增加範本',   // 選單主標題
      '增加範本',   // 選單名稱
      'manage_options', // 許可權
      'add_tamplate_files', // 選單ID 必須唯一
      'add_tamplate_files_html', // 要放入的內容函數
      'dashicons-analytics', // 選單圖標
      99 // 選單位置
    );
  }

  // 將custom_menu函數增加到WordPress的admin_menu鉤子
add_action('admin_menu', 'add_tamplate_files_menu');

// 自訂選單要內頁放入的內容
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
// 重新取得範本檔案列表，這裡不加刪除檔案不會刷新列表。
$template_files = glob($template_dir . '*.php');

if(!empty($template_files)): ?>
  <table class="wp-list-table widefat fixed striped">
    <thead>
      <tr>
        <th>檔案名稱</th>
        <th>範本名稱</th>
        <th>時間</th>
        <th>動作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($template_files as $file): 
        $filename = basename($file);
        $file_time = filemtime($file);
        $file_contents = file_exists($file) ? file_get_contents($file) : '';
        
        // 取得範本名稱
        if(preg_match('/Template Name:\\s*(.+)/i', $file_contents, $matches)) {
          $template_name = trim($matches[1]);
        } else {
          $template_name = preg_replace('/\\.php$/', '', $filename);
        }
      ?>
        <tr>
          <td><?php echo esc_html($filename); ?></td>
          <td><?php echo esc_html($template_name); ?></td>
          <td><?php echo date('Y-m-d H:i:s', $file_time); ?></td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="delete_template" value="<?php echo esc_attr($filename); ?>">
              <button type="submit" class="button button-small" onclick="return confirm('確定要刪除此範本檔案嗎？');">刪除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>目前沒有已上傳的範本檔案。</p>
<?php endif; ?>


</div>
<?php
}

include 'function.php';




