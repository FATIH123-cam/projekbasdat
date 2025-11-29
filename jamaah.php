<?php
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// helper: tampilkan pesan dan redirect
function flash_and_redirect($msg = '', $color = 'green', $to = 'jamaah.php') {
    if ($msg !== '') {
        // simpan pesan singkat di query string (bisa diganti session jika perlu)
        $url = $to . '?msg=' . urlencode($msg) . '&c=' . urlencode($color);
    } else {
        $url = $to;
    }
    header("Location: $url");
    exit;
}

// CREATE
if (isset($_POST['add'])) {
    $query = "INSERT INTO jamaah (jamaahid, namalengkap, noktp, tgllahir, jeniskelamin, alamat, nohp)
              VALUES ($1, $2, $3, $4, $5, $6, $7)";
    $params = array(
        $_POST['jamaahid'],
        $_POST['namalengkap'],
        $_POST['noktp'],
        $_POST['tgllahir'],
        $_POST['jeniskelamin'],
        $_POST['alamat'],
        $_POST['nohp']
    );

    $res = pg_query_params($conn, $query, $params);
    if ($res) {
        flash_and_redirect('Data jamaah berhasil ditambahkan!', 'green');
    } else {
        // tampilkan error SQL (untuk debug)
        $err = pg_last_error($conn);
        echo "<h3 style='color:red;'>Insert gagal: " . htmlspecialchars($err) . "</h3>";
        // jangan redirect agar user bisa lihat error
    }
}

// UPDATE
if (isset($_POST['update'])) {
    $query = "UPDATE jamaah SET namalengkap=$1, noktp=$2, tgllahir=$3, jeniskelamin=$4, alamat=$5, nohp=$6
              WHERE jamaahid=$7";
    $params = array(
        $_POST['namalengkap'],
        $_POST['noktp'],
        $_POST['tgllahir'],
        $_POST['jeniskelamin'],
        $_POST['alamat'],
        $_POST['nohp'],
        $_POST['jamaahid']
    );
    $res = pg_query_params($conn, $query, $params);
    if ($res) {
        flash_and_redirect('Data jamaah berhasil diperbarui!', 'blue');
    } else {
        echo "<h3 style='color:red;'>Update gagal: " . htmlspecialchars(pg_last_error($conn)) . "</h3>";
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $res = pg_query_params($conn, "DELETE FROM jamaah WHERE jamaahid=$1", array($_GET['delete']));
    if ($res) {
        flash_and_redirect('Data jamaah berhasil dihapus!', 'red');
    } else {
        echo "<h3 style='color:red;'>Delete gagal: " . htmlspecialchars(pg_last_error($conn)) . "</h3>";
    }
}

// Ambil data untuk edit
$edit = null;
if (isset($_GET['edit'])) {
    $result = pg_query_params($conn, "SELECT * FROM jamaah WHERE jamaahid=$1", array($_GET['edit']));
    $edit = pg_fetch_assoc($result);
}

// pesan flash sederhana via query string
$msg = $_GET['msg'] ?? '';
$color = $_GET['c'] ?? 'green';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>CRUD Jamaah - Shabar Tour</title>
<style>
body { font-family: Arial, sans-serif; margin: 24px; }
h1 { color:#333; }
.notice { padding:8px; margin-bottom:12px; border-radius:4px; color:white; display:inline-block;}
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border:1px solid #ddd; padding:8px; text-align:left; }
th { background:#f5f5f5; }
input, select { padding:6px; margin:4px 0; width:100%; box-sizing: border-box; }
.form-row { max-width:700px; }
button { padding:8px 12px; background:#007bff; color:white; border:none; cursor:pointer; }
button.secondary { background:#6c757d; }
a { color:#007bff; text-decoration:none; }
</style>
</head>
<body>

<h1>📌 Data Jamaah</h1>

<?php if ($msg): ?>
  <div class="notice" style="background:<?=htmlspecialchars($color)?>"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>

<h2><?= $edit ? "✏ Edit Jamaah" : "➕ Tambah Jamaah" ?></h2>

<form method="POST" class="form-row">
  <label>ID Jamaah (unique)</label>
  <input type="text" name="jamaahid" placeholder="ID Jamaah" value="<?= htmlspecialchars($edit['jamaahid'] ?? '') ?>" <?= $edit ? 'readonly' : '' ?> required>

  <label>Nama Lengkap</label>
  <input type="text" name="namalengkap" placeholder="Nama Lengkap" value="<?= htmlspecialchars($edit['namalengkap'] ?? '') ?>" required>

  <label>No KTP</label>
  <input type="text" name="noktp" placeholder="No KTP" value="<?= htmlspecialchars($edit['noktp'] ?? '') ?>">

  <label>Tanggal Lahir</label>
  <input type="date" name="tgllahir" value="<?= htmlspecialchars($edit['tgllahir'] ?? '') ?>">

  <label>Jenis Kelamin</label>
  <select name="jeniskelamin" required>
    <option value="">-- pilih --</option>
    <option value="L" <?= isset($edit) && $edit['jeniskelamin']=='L' ? 'selected' : '' ?>>Laki-laki</option>
    <option value="P" <?= isset($edit) && $edit['jeniskelamin']=='P' ? 'selected' : '' ?>>Perempuan</option>
  </select>

  <label>Alamat</label>
  <input type="text" name="alamat" placeholder="Alamat" value="<?= htmlspecialchars($edit['alamat'] ?? '') ?>">

  <label>No HP</label>
  <input type="text" name="nohp" placeholder="08xxxx" value="<?= htmlspecialchars($edit['nohp'] ?? '') ?>">

  <div style="margin-top:8px;">
    <button type="submit" name="<?= $edit ? 'update' : 'add' ?>"><?= $edit ? 'Update' : 'Simpan' ?></button>
    <?php if ($edit): ?>
      <a class="secondary" href="jamaah.php" style="margin-left:8px; padding:8px 12px; background:#6c757d; color:white;">Batal</a>
    <?php endif; ?>
  </div>
</form>

<h2>📋 Daftar Jamaah</h2>
<table>
<tr>
  <th>ID</th><th>Nama</th><th>No KTP</th><th>Tgl Lahir</th><th>JK</th><th>Alamat</th><th>No HP</th><th>Aksi</th>
</tr>

<?php
// Ambil semua data (pastikan search_path di db.php sudah benar)
$result = pg_query($conn, "SELECT * FROM jamaah ORDER BY jamaahid DESC");
if (!$result) {
    echo "<tr><td colspan='8' style='color:red;'>Query gagal: " . htmlspecialchars(pg_last_error($conn)) . "</td></tr>";
} else {
    while ($row = pg_fetch_assoc($result)) {
        echo "<tr>
                <td>".htmlspecialchars($row['jamaahid'])."</td>
                <td>".htmlspecialchars($row['namalengkap'])."</td>
                <td>".htmlspecialchars($row['noktp'])."</td>
                <td>".htmlspecialchars($row['tgllahir'])."</td>
                <td>".htmlspecialchars($row['jeniskelamin'])."</td>
                <td>".htmlspecialchars($row['alamat'])."</td>
                <td>".htmlspecialchars($row['nohp'])."</td>
                <td>
                    <a href='?edit=".urlencode($row['jamaahid'])."'>Edit</a> |
                    <a href='?delete=".urlencode($row['jamaahid'])."' onclick=\"return confirm('Hapus jamaah ini?')\">Delete</a>
                </td>
              </tr>";
    }
}
?>
</table>

</body>
</html>
