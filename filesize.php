<?php
// PERFECT_disk_scanner.php - Sıfırdan yazıldı, 500 error yok, süper stabil
set_time_limit(600);
ini_set('memory_limit', '1024M');
ob_start(); // Buffer aç

echo '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>💾 Disk Scanner</title>
<style>
body{font-family:Consolas,monospace;background:#111;color:#0f0;padding:20px;}
h1{color:#f90;font-size:2em;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th{background:#333;color:#fff;padding:10px;text-align:left;}
td{padding:8px;border-bottom:1px solid #333;word-break:break-all;}
.size{font-weight:bold;}
.total{color:#ff0;font-size:1.2em;}
.btn{padding:15px 30px;background:#f90;color:#000;font-size:18px;text-decoration:none;border-radius:5px;}
</style></head><body>';

$root = __DIR__;
echo "<h1>📁 $root - Disk Scanner</h1>";

if (isset($_GET['start'])) {
    echo '<div class="total">⏳ Tarama başlıyor...</div>';
    flush(); ob_flush();
    
    // Basit recursive fonksiyon - Iterator yok!
    function getSizes($dir, &$sizes) {
        $handle = opendir($dir);
        while ($file = readdir($handle)) {
            if ($file == '.' || $file == '..') continue;
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                $sizes[$path] = filesize($path);
            } elseif (is_dir($path)) {
                getSizes($path, $sizes);
            }
        }
        closedir($handle);
    }
    
    $sizes = array();
    getSizes($root, $sizes);
    
    // Sırala ve filtrele
    arsort($sizes);
    $sizes = array_filter($sizes, function($s) { return $s > 0; });
    
    $total = array_sum($sizes);
    $count = count($sizes);
    
    echo "<div class='total'>✅ Tamamlandı! Toplam: <strong>" . format_size($total) . "</strong> | Dosya: $count</div>";
    
    echo "<table><tr><th>Dosya Yolu</th><th>Boyut</th><th>%</th></tr>";
    $shown = 0;
    foreach ($sizes as $file => $size) {
        $pct = round(($size/$total)*100, 1);
        echo "<tr><td>" . htmlspecialchars($file) . "</td>
              <td class='size'>" . format_size($size) . "</td>
              <td>$pct%</td></tr>";
        $shown++;
        if ($shown >= 1000) {
            echo "<tr><td colspan='3'>... + " . ($count - 1000) . " dosya</td></tr>";
            break;
        }
    }
    echo "</table>";
    
} else {
    echo "<p><a href='?start=1' class='btn'>🚀 DİZİNİ TARA BAŞLAT</a></p>";
    echo "<p><small>En büyük dosyalar büyükten küçüğe listelenecek (1-10 dk)</small></p>";
}

function format_size($bytes) {
    $units = array('B','KB','MB','GB','TB');
    $i = 0;
    while ($bytes > 1024 && $i < 4) { $bytes /= 1024; $i++; }
    return round($bytes, 1) . ' ' . $units[$i];
}

echo "</body></html>";
ob_end_flush();
?>
