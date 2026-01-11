<?php
// disk_usage_fixed.php - Function redeclaration hatası düzeltildi
if (function_exists('scanDir')) { function scanDir_fixed($dir) { /* ... */ } } else { function scanDir($dir) { /* ... */ } }

// Tam kod aşağıda, direkt kopyala
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');

// Fonksiyonlar sadece yoksa tanımla
if (!function_exists('formatBytes')) {
    function formatBytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        for ($i = 0; $size > 1024 && $i < 4; $i++) $size /= 1024;
        return round($size, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('scanDir')) {
    function scanDir($dir) {
        $sizes = [];
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST | RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $path = $file->getPathname();
                    $size = $file->getSize();
                    $sizes[$path] = $size;
                }
            }
        } catch (Exception $e) {
            echo "Hata: " . $e->getMessage() . "<br>";
        }
        return $sizes;
    }
}

// Ana kod
$root = __DIR__;
echo "<!DOCTYPE html><html><head><title>Disk Analiz</title>";
echo "<style>body{font-family:monospace;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ccc;padding:4px;} .big{font-size:1.2em;}</style></head><body>";

if (isset($_GET['scan'])) {
    echo "<h2>📁 $root Analizi</h2>";
    $sizes = scanDir($root);
    arsort($sizes);
    $totalSize = array_sum($sizes);
    
    echo "<p><strong class='big'>Toplam: " . formatBytes($totalSize) . " | Dosya: " . count($sizes) . "</strong></p>";
    
    echo "<table><tr><th>Dosya</th><th>Boyut</th><th>%</th></tr>";
    $count = 0;
    foreach ($sizes as $file => $size) {
        if ($size > 0) {
            $percent = round(($size / $totalSize) * 100, 1);
            echo "<tr><td style='max-width:500px;word-break:break-all;'>" . htmlspecialchars($file) . "</td>";
            echo "<td>" . formatBytes($size) . "</td><td>$percent%</td></tr>";
            $count++;
            if ($count >= 500) break;
        }
    }
    echo "</table>";
} else {
    echo "<h1>💾 Disk Kullanım Scanner</h1>";
    echo "<p>Kök: <strong>" . htmlspecialchars($root) . "</strong></p>";
    echo "<p><a href='?scan=1' style='font-size:20px;padding:10px;background:#f90;color:#000;text-decoration:none;'>🚀 TARA BAŞLAT (2-5 dk)</a></p>";
}

echo "</body></html>"; [code_file:2]
