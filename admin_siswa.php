<?php require 'config.php';require 'layout.php';need('admin');$msg=$err='';$newpw=null;
if($_SERVER['REQUEST_METHOD']==='POST'){chk();$nim=trim($_POST['nim']??'');$nama=trim($_POST['nama']??'');$pw=$_POST['pw']??'';$uid=(int)($_POST['uid']??0);
 try{
  if(isset($_POST['tambah'])){if(!$nim||!$nama||strlen($pw)<6)$err='Lengkapi NIM, nama, dan password (min. 6 karakter).';
   else{$db->prepare("INSERT INTO users(nim,nama,password,role) VALUES(?,?,?,'mhs')")->execute([$nim,$nama,password_hash($pw,PASSWORD_DEFAULT)]);$msg='Mahasiswa ditambahkan.';}}
  if(isset($_POST['simpan'])){if(!$nim||!$nama||($pw!==''&&strlen($pw)<6))$err='Data tidak valid (password baru min. 6 karakter).';
   else{if($pw!=='')$db->prepare("UPDATE users SET nim=?,nama=?,password=? WHERE id=? AND role='mhs'")->execute([$nim,$nama,password_hash($pw,PASSWORD_DEFAULT),$uid]);
    else $db->prepare("UPDATE users SET nim=?,nama=? WHERE id=? AND role='mhs'")->execute([$nim,$nama,$uid]);$msg='Data diperbarui.';}}
  if(isset($_POST['reset'])){$c='abcdefghjkmnpqrstuvwxyz23456789';$np='';for($i=0;$i<6;$i++)$np.=$c[random_int(0,strlen($c)-1)];
   $s=$db->prepare("SELECT nama,nim FROM users WHERE id=? AND role='mhs'");$s->execute([$uid]);$m=$s->fetch();
   if($m){$db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($np,PASSWORD_DEFAULT),$uid]);$newpw=[$m['nama'].' ('.$m['nim'].')',$np];}}
  if(isset($_POST['hapus'])){try{$db->prepare("DELETE FROM attempts WHERE user_id IN(SELECT id FROM users WHERE id=? AND role='mhs')")->execute([$uid]);}catch(Exception $e){}$db->prepare("DELETE FROM results WHERE user_id IN(SELECT id FROM(SELECT id FROM users WHERE id=? AND role='mhs') t)")->execute([$uid]);
   $db->prepare("DELETE FROM users WHERE id=? AND role='mhs'")->execute([$uid]);$msg='Mahasiswa dan hasil quiznya dihapus.';}
 }catch(PDOException $x){$err='NIM sudah dipakai atau terjadi kesalahan.';}}
$ed=null;if(isset($_GET['edit'])){$s=$db->prepare("SELECT * FROM users WHERE id=? AND role='mhs'");$s->execute([(int)$_GET['edit']]);$ed=$s->fetch();}
$cari=trim($_GET['cari']??'');$s=$db->prepare("SELECT u.*,(SELECT COUNT(*) FROM results WHERE user_id=u.id) n FROM users u WHERE role='mhs' AND (nim LIKE ? OR nama LIKE ?) ORDER BY nim");$s->execute(["%$cari%","%$cari%"]);$rows=$s->fetchAll();
hd('Siswa');echo '<h2>Kelola Mahasiswa</h2>';if($msg)echo '<div class="ok">'.e($msg).'</div>';if($newpw)echo '<div class="ok">🔑 Password baru untuk <b>'.e($newpw[0]).'</b>:<br><b style="font-size:24px;letter-spacing:3px">'.e($newpw[1]).'</b><br><small>Catat sekarang dan berikan ke mahasiswa. Password ini tidak akan ditampilkan lagi.</small></div>';if($err)echo '<div class="err">'.e($err).'</div>';?>
<div class="card"><h3><?=$ed?'Edit Mahasiswa':'Tambah Mahasiswa'?></h3><form method="post"><?=csrf()?>
<?php if($ed)echo '<input type="hidden" name="uid" value="'.$ed['id'].'">';?>
<input name="nim" placeholder="NIM" value="<?=e($ed['nim']??'')?>" required><input name="nama" placeholder="Nama lengkap" value="<?=e($ed['nama']??'')?>" required>
<input name="pw" placeholder="<?=$ed?'Password baru (kosongkan jika tidak diubah)':'Password (min. 6 karakter)'?>">
<button name="<?=$ed?'simpan':'tambah'?>"><?=$ed?'Simpan Perubahan':'Tambah'?></button><?php if($ed)echo ' <a class="btn gray" href="admin_siswa.php">Batal</a>';?></form></div>
<form method="get"><input name="cari" placeholder="Cari NIM / nama..." value="<?=e($cari)?>"></form>
<div class="card"><table><tr><th>NIM</th><th>Nama</th><th>Quiz</th><th>Password</th><th></th></tr>
<?php foreach($rows as $r)echo '<tr><td>'.e($r['nim']).'</td><td>'.e($r['nama']).'</td><td>'.$r['n'].'</td><td><small>🔒 Terenkripsi</small></td><td><a class="btn gray" href="?edit='.$r['id'].'">Edit</a> <form class="inline" method="post">'.csrf().'<input type="hidden" name="uid" value="'.$r['id'].'"><button name="reset" class="gray" onclick="return confirm(\'Buat password baru untuk mahasiswa ini? Password lama tidak berlaku lagi.\')">🔑 Reset</button> <button name="hapus" class="red" onclick="return confirm(\'Hapus mahasiswa ini beserta hasil quiznya?\')">Hapus</button></form></td></tr>';
if(!$rows)echo '<tr><td colspan="5">Belum ada mahasiswa.</td></tr>';?></table><small><?=count($rows)?> mahasiswa</small></div>
<?php foot();
