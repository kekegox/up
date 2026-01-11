<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');

echo '<style>body{background:#000;color:#0f0;font-family:monospace;white-space:pre;padding:20px;}
input,select{width:100%;padding:10px;margin:5px 0;font-family:monospace;background:#111;color:#0f0;border:1px solid #0f0;}
.btn{padding:15px;background:#f90;color:#000;font-weight:bold;font-size:16px;cursor:pointer;}
.result{background:#111;padding:20px;border:1px solid #0f0;margin:20px 0;}</style>';

function currentBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
}

echo '<form method="post">
<h3>📁 Yedekleme Sistemi (Gelişmiş)</h3>

<strong>Hedef Dizin:</strong><br>
<input type="text" name="dir" size="60" value="'.dirname(getcwd()).'/public_html" required><br>

<strong>🔒 Hariç Tutulacak Klasörler (virgülle):</strong><br>
<input type="text" name="exclude_dirs" size="60" placeholder="cache,node_modules,uploads/temp,storage/logs,storage/framework/cache"><br>

<strong>🚫 Hariç Tutulacak Dosya Uzantıları (virgülle):</strong><br>
<input type="text" name="exclude_ext" size="60" value="mp4,avi,mkv,mov,wmv,flv,webm,mp3,wav,zip,rar,7z,tar,gzip" placeholder="mp4,avi,jpg,png"><br>

<strong>📦 Arşiv Formatı:</strong><br>
<select name="format">
    <option value="zip">ZIP (Önerilen)</option>
    <option value="tar">TAR</option>
    <option value="tgz">TGZ (Sıkıştırılmış TAR)</option>
</select><br>

<input type="submit" name="backup" value="🚀 YEDEK OLUŞTUR" class="btn">
</form>';

if (isset($_POST['backup'])) {
    $dir = rtrim($_POST['dir'], '/');
    $exclude_dirs = array_map('trim', explode(',', $_POST['exclude_dirs'] ?? ''));
    $exclude_exts = array_map('trim', explode(',', $_POST['exclude_ext'] ?? ''));
    $format = $_POST['format'] ?? 'zip';
    
    $backupName = "backup_" . date("Y-m-d_H-i-s") . "_" . basename($dir);
    $archivePath = $backupName . '.' . ($format == 'tgz' ? 'tgz' : $format);
    
    echo "<div class='result'>";
    echo "<h3>📊 Yedekleme Raporu</h3>";
    echo "<p><strong>Hedef:</strong> $dir</p>";
    echo "<p><strong>Hariç Klasörler:</strong> " . implode(', ', $exclude_dirs) . "</p>";
    echo "<p><strong>Hariç Uzantılar:</strong> " . implode(', ', $exclude_exts) . "</p>";
    echo "<p><strong>Format:</strong> $format</p>";
    
    if (!is_dir($dir)) {
        echo "<p style='color:#f00;'>❌ Dizin bulunamadı: $dir</p>";
        echo "</div>";
        exit;
    }
    
    // ZIPArchive ile gelişmiş ZIP
    if ($format == 'zip' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $rootLen = strlen($dir) + 1;
            $fileCount = 0;
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                $path = $file->getPathname();
                $relative = substr($path, $rootLen);
                
                // Klasör hariç tutma
                $skipDir = false;
                foreach ($exclude_dirs as $ex) {
                    if (strpos($relative, $ex . '/') === 0 || $relative === $ex) {
                        $skipDir = true; break;
                    }
                }
                if ($skipDir) continue;
                
                // Dosya uzantısı kontrolü
                if ($file->isFile()) {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    if (in_array($ext, $exclude_exts)) {
                        echo "<p>🚫 Atlandı (uzantı): $relative</p>";
                        continue;
                    }
                }
                
                if ($file->isDir()) {
                    $zip->addEmptyDir($relative);
                } else {
                    $zip->addFile($path, $relative);
                    $fileCount++;
                }
            }
            
            $zip->close();
            echo "<p style='color:#0f0;'>✅ ZIP OLUŞTURULDU! ($fileCount dosya)</p>";
            echo "<p><a href='$archivePath' download style='background:#0f0;color:#000;padding:10px;'>📥 ZIP İNDİR</a></p>";
        } else {
            echo "<p style='color:#f00;'>❌ ZIP açılamadı</p>";
        }
    }
    // TAR/TGZ
    else {
        $cmd = "cd " . escapeshellarg($dir) . " && ";
        $cmd .= "tar " . ($format == 'tgz' ? '-czf' : '-cf') . " " . escapeshellarg("../$archivePath");
        
        foreach ($exclude_dirs as $ex) {
            $cmd .= " --exclude='$ex'";
        }
        
        foreach ($exclude_exts as $ext) {
            $cmd .= " --exclude='*.$ext'";
        }
        
        $cmd .= " .";
        
        echo "<p>Komut: <code>" . htmlspecialchars($cmd) . "</code></p>";
        $output = shell_exec($cmd . " 2>&1");
        
        if (file_exists($archivePath)) {
            echo "<p style='color:#0f0;'>✅ $format ARŞivi OLUŞTURULDU!</p>";
            echo "<p><a href='$archivePath' download style='background:#0f0;color:#000;padding:10px;'>📥 ARŞİV İNDİR</a></p>";
        } else {
            echo "<p style='color:#f00;'>❌ Hata: <pre>$output</pre></p>";
        }
    }
    echo "</div>";
}
?>
