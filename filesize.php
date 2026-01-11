<?php
// disk_usage.php - Kök dizin ve alt klasörleri tarar, boyutları büyükten küçüğe sıralar
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // 5 dk timeout
ini_set('memory_limit', '512M');

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, $precision) . ' ' . $units[$i];
}

function scanDir($dir) {
    $sizes = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        $size = $file->getSize();
        $sizes[$path] = $size;
    }
    return $sizes;
}

if (!isset($_GET['path'])) {
    $root = __DIR__;
    echo "<h1>💾 Disk Kullanım Analizcisi</h1>";
    echo "<p><strong>Kök Dizin:</strong> " . htmlspecialchars($root) . "</p>";
    echo "<a href='?path=" . urlencode($root) . "'>Tüm Dizinleri Tara (5-10 dk sürebilir)</a>";
    exit;
}

$path = $_GET['path'] ?? __DIR__;
if (!is_dir($path) || !is_readable($path)) {
    die('❌ Dizin okunamıyor: ' . htmlspecialchars($path));
}

echo "<h2>📁 " . htmlspecialchars($path) . " Analizi</h2>";
echo "<p>Taranan dosya sayısı: <strong id='count'>0</strong> | Toplam boyut: <strong id='total'>0</strong></p>";

$sizes = scanDir($path);
arsort($sizes); // Büyükten küçüğe

$totalSize = array_sum($sizes);
echo "<script>document.getElementById('total').innerHTML = '" . formatBytes($totalSize) . "';</script>";

echo "<table border='1' style='width:100%; border-collapse:collapse; font-family:monospace;'>";
echo "<tr><th>Dosya Yolu</th><th>Boyut</th><th>%</th></tr>";

$fileCount = 0;
foreach ($sizes as $file => $size) {
    if ($size == 0) continue;
    $percent = round(($size / $totalSize) * 100, 2);
    echo "<tr>";
    echo "<td style='word-break:break-all; max-width:400px;'>" . htmlspecialchars($file) . "</td>";
    echo "<td>" . formatBytes($size) . "</td>";
    echo "<td>" . $percent . "%</td>";
    echo "</tr>";
    $fileCount++;
    if ($fileCount > 1000) { echo "<tr><td colspan=3>... ve " . (count($sizes) - 1000) . " dosya daha</td></tr>"; break; }
}
echo "</table>";

echo "<p><a href='?'>← Ana Sayfaya Dön</a> | Toplam: " . formatBytes($totalSize) . " | Dosya: $fileCount</p>";
?> [code_file:1]
