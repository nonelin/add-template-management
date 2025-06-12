<?php
/**
 * @package Add_Tamplate_Management
 * @version 1.0.0
 */
/*
Plugin Name: 增加範本管理
Plugin URI: http://wordpress.org/plugins/add-tamplate/
Description: 從後臺上傳範本檔案，方便在頁面中選擇範本，這裡只會管理外掛所上船的範本檔案。
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
    $upload_dir = plugin_dir_path(__FILE__) . 'templates/';
    if (!file_exists($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }
    $file = $_FILES['template_file'];
    $filename = basename($file['name']);
    $target = $upload_dir . $filename;
    $allowed = array('php');
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
      if (move_uploaded_file($file['tmp_name'], $target)) {
        echo '<div class="updated"><p>檔案上傳成功！</p></div>';
      } else {
        echo '<div class="error"><p>檔案上傳失敗。</p></div>';
      }
    } else {
      echo '<div class="error"><p>只允許上傳 php 檔案。</p></div>';
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
$template_dir = plugin_dir_path(__FILE__) . 'templates/';
$template_files = glob($template_dir . '*.*');

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
$template_files = glob($template_dir . '*.*');

if(!empty($template_files)): ?>
  <table class="wp-list-table widefat fixed striped">
    <thead>
      <tr>
        <th>檔案名稱</th>
        <th>範本名稱</th>
        <th>時間</th>
        <th>操作</th>
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




