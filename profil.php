<?php require 'config.php';require 'layout.php';
if(!user()){header('Location: index.php');exit;}
$uid=(int)user()['id'];$msg=$err='';
function ambil($db,$uid){$s=$db->prepare('SELECT * FROM users WHERE id=?');$s->execute([$uid]);return $s->fetch();}
$me=ambil($db,$uid);
if($_SERVER['REQUEST_METHOD']==='POST'){chk();
 if(isset($_POST['simpan_profil'])){$nama=trim($_POST['nama']??'');$em=trim($_POST['email']??'');$hp=trim($_POST['hp']??'');$pr=$_POST['prodi']??'';
  if($nama==='')$err='Nama tidak boleh kosong.';
  elseif($em!==''&&!filter_var($em,FILTER_VALIDATE_EMAIL))$err='Format email tidak valid.';
  elseif($hp!==''&&!preg_match('/^[0-9+\- ]{6,20}$/',$hp))$err='Nomor HP tidak valid.';
  else{try{$db->prepare('UPDATE users SET nama=?,email=?,hp=?,prodi=? WHERE id=?')->execute([$nama,$em?:null,$hp?:null,in_array($pr,['Farmasi','TLM'])?$pr:null,$uid]);$_SESSION['u']['nama']=$nama;$msg='Profil berhasil diperbarui.';}
   catch(Exception $x){$err='Gagal menyimpan. Jalankan update4.sql di phpMyAdmin terlebih dahulu.';}}}
 if(isset($_POST['ganti_pw'])){$o=$_POST['lama']??'';$n=$_POST['baru']??'';$k=$_POST['konfirmasi']??'';
  if(!password_verify($o,$me['password']))$err='Password lama salah.';
  elseif(strlen($n)<6)$err='Password baru minimal 6 karakter.';
  elseif($n!==$k)$err='Konfirmasi password tidak sama.';
  else{$db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($n,PASSWORD_DEFAULT),$uid]);$msg='Password berhasil diubah.';}}
 if(isset($_POST['hapus_foto'])){@unlink(__DIR__.'/uploads/avatar_'.$uid.'.jpg');$msg='Foto profil dihapus.';}
 if(isset($_POST['simpan_foto'])){$f=$_FILES['foto']??null;
  if(!$f||$f['error']!==UPLOAD_ERR_OK)$err='Pilih file foto terlebih dahulu.';
  elseif($f['size']>3*1024*1024)$err='Ukuran foto maksimal 3 MB.';
  elseif(!function_exists('imagecreatefromstring'))$err='Server belum mendukung pemrosesan gambar.';
  else{$info=@getimagesize($f['tmp_name']);
   if(!$info||!in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP]))$err='Format harus JPG, PNG, atau WEBP.';
   else{$im=@imagecreatefromstring(file_get_contents($f['tmp_name']));
    if(!$im)$err='Gambar tidak dapat dibaca.';
    else{if($info[2]===IMAGETYPE_JPEG&&function_exists('exif_read_data')){$ex=@exif_read_data($f['tmp_name']);$or=$ex['Orientation']??1;if($or==3)$im=imagerotate($im,180,0);elseif($or==6)$im=imagerotate($im,-90,0);elseif($or==8)$im=imagerotate($im,90,0);}
     $w=imagesx($im);$h=imagesy($im);$sz=min($w,$h);$d=imagecreatetruecolor(320,320);imagefill($d,0,0,imagecolorallocate($d,255,255,255));
     imagecopyresampled($d,$im,0,0,(int)(($w-$sz)/2),(int)(($h-$sz)/2),320,320,$sz,$sz);
     if(!is_dir(__DIR__.'/uploads'))@mkdir(__DIR__.'/uploads',0755,true);
     if(@imagejpeg($d,__DIR__.'/uploads/avatar_'.$uid.'.jpg',88))$msg='Foto profil berhasil diperbarui.';else $err='Gagal menyimpan foto (folder uploads tidak bisa ditulis).';
     imagedestroy($im);imagedestroy($d);}}}}
 $me=ambil($db,$uid);}
hd('Profil Saya');?>
<style>.ph{display:flex;align-items:center;gap:18px;flex-wrap:wrap}.ph h2{margin:0}.rl{display:inline-block;background:#ccfbf1;color:#0f766e;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:700}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}@media(max-width:560px){.g2{grid-template-columns:1fr}}.ph .ava.lg{border:4px solid #fff;box-shadow:0 3px 10px #0003}</style>
<?php if($msg)echo '<div class="ok">'.e($msg).'</div>';if($err)echo '<div class="err">'.e($err).'</div>';?>
<div class="card ph"><?=avatar($uid,$me['nama'],'ava lg')?><div><h2><?=e($me['nama'])?></h2><small><?=$me['role']=='admin'?'Dosen / Admin':'NIM '.e($me['nim'])?><?=$me['role']=='admin'?' · '.e($me['nim']):''?></small><br><span class="rl"><?=$me['role']=='admin'?'Dosen':'Mahasiswa'?><?=!empty($me['prodi'])?' · '.e($me['prodi']):''?></span></div></div>
<div class="card"><h3 style="margin-top:0">📷 Foto Profil</h3><form method="post" enctype="multipart/form-data"><?=csrf()?>
<input type="file" name="foto" accept="image/*" required><small>JPG / PNG / WEBP, maksimal 3 MB. Otomatis dipotong persegi.</small><br><br>
<button name="simpan_foto">Unggah Foto</button> <?php if(is_file(__DIR__.'/uploads/avatar_'.$uid.'.jpg'))echo '<button name="hapus_foto" class="red" formnovalidate onclick="return confirm(\'Hapus foto profil?\')">Hapus Foto</button>';?></form></div>
<div class="card"><h3 style="margin-top:0">👤 Data Diri</h3><form method="post"><?=csrf()?>
<label>Nama Lengkap</label><input name="nama" value="<?=e($me['nama'])?>" required>
<div class="g2"><div><label>NIM / Username</label><input value="<?=e($me['nim'])?>" disabled></div>
<div><label>Program Studi</label><select name="prodi"><option value="">— Pilih —</option><?php foreach(['Farmasi'=>'Farmasi','TLM'=>'Teknologi Laboratorium Medis'] as $k=>$v)echo '<option value="'.$k.'"'.(($me['prodi']??'')===$k?' selected':'').'>'.$v.'</option>';?></select></div>
<div><label>Email</label><input type="email" name="email" value="<?=e($me['email']??'')?>" placeholder="nama@email.com"></div>
<div><label>No. HP / WhatsApp</label><input name="hp" value="<?=e($me['hp']??'')?>" placeholder="08xxxxxxxxxx"></div></div>
<button name="simpan_profil">Simpan Perubahan</button></form></div>
<div class="card"><h3 style="margin-top:0">🔒 Ubah Password</h3><form method="post"><?=csrf()?>
<label>Password Lama</label><input type="password" class="pwf" name="lama" autocomplete="current-password" required>
<label>Password Baru <small>(min. 6 karakter)</small></label><input type="password" class="pwf" name="baru" autocomplete="new-password" required>
<label>Ulangi Password Baru</label><input type="password" class="pwf" name="konfirmasi" autocomplete="new-password" required>
<label style="display:flex;gap:8px;align-items:center;font-size:14px;margin-bottom:10px"><input type="checkbox" id="sp" style="width:auto;margin:0"> Tampilkan password</label>
<button name="ganti_pw">Ubah Password</button></form></div>
<script>document.getElementById('sp').onchange=e=>document.querySelectorAll('.pwf').forEach(i=>i.type=e.target.checked?'text':'password');</script>
<?php foot();
