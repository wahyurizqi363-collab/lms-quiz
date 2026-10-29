<?php require 'config.php';require 'layout.php';
if(user()){header('Location: '.(user()['role']=='admin'?'admin.php':'dashboard.php'));exit;}
$err=$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){chk();
 $nim=trim($_POST['nim']??'');
 if(isset($_POST['login'])){
  $s=$db->prepare('SELECT * FROM users WHERE nim=?');$s->execute([$nim]);$u=$s->fetch();
  if($u&&password_verify($_POST['pw'],$u['password'])){session_regenerate_id(true);$_SESSION['u']=['id'=>$u['id'],'nama'=>$u['nama'],'role'=>$u['role']];try{$db->exec("SET time_zone='+07:00'");$db->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([$u['id']]);}catch(Exception $e){}header('Location: index.php');exit;}
  $err='NIM / password salah.';
 }else{
  $nama=trim($_POST['nama']??'');$pw=$_POST['pw']??'';
  if(!$nim||!$nama||strlen($pw)<6)$err='Lengkapi data. Password minimal 6 karakter.';
  else{$role=(($_POST['kode']??'')!==''&&$_POST['kode']===KODE_DOSEN)?'admin':'mhs';
   try{$db->prepare('INSERT INTO users(nim,nama,password,role) VALUES(?,?,?,?)')->execute([$nim,$nama,password_hash($pw,PASSWORD_DEFAULT),$role]);$ok='Pendaftaran berhasil, silakan login.';}
   catch(PDOException $x){$err='NIM sudah terdaftar.';}}
 }}
$showReg=isset($_POST['daftar'])&&!$ok;$kodeOn=!empty($_POST['kode']);
hd('Masuk');?>
<style>
.auth{max-width:400px;margin:30px auto}.auth .logo{width:104px;height:104px;margin:0 auto 12px;background:#fff;border-radius:50%;overflow:hidden;box-shadow:0 6px 18px #0005;border:3px solid #fff}.auth .logo img{width:100%;height:100%;object-fit:contain;display:block}.auth .kp{text-align:center;color:#fff;font-weight:700;letter-spacing:1.5px;font-size:16px;margin:0}.auth .kt{text-align:center;color:#99f6e4;letter-spacing:5px;font-size:12px;margin:2px 0 14px}.auth h1{margin-top:0}.ft{text-align:center;color:#ccfbf1;font-size:13px;margin-top:18px}.auth .card{box-shadow:0 10px 30px #0004}
.auth h1{text-align:center;margin:0 0 4px;font-size:26px;color:#fff}.auth .sub{text-align:center;color:#ccfbf1;margin:0 0 18px}
.auth .card{padding:24px}.auth .card h2{margin:0 0 14px;font-size:19px}.auth button{width:100%;padding:11px}
.sw{text-align:center;margin-top:14px;font-size:14px;color:#64748b}.sw a{color:#0f766e;cursor:pointer;font-weight:600}
.pw{position:relative}.pw input{padding-right:46px}.auth .eye{position:absolute;right:5px;top:6px;width:36px;height:36px;padding:0;background:none;color:#5b7b79;display:flex;align-items:center;justify-content:center}.auth .eye:hover{background:#e0f2ef}.hide{display:none}.chk{display:flex;align-items:center;gap:8px;font-size:14px;margin-bottom:10px;cursor:pointer}.chk input{width:auto;margin:0}
</style>
<div class="auth"><div class="logo"><img src="logo.png" alt="Logo STIKES Karya Putra Bangsa"></div><p class="kp">STIKES KARYA PUTRA BANGSA</p><p class="kt">TULUNGAGUNG</p><h1>LMS Quiz</h1><p class="sub">Farmasi &amp; Teknologi Laboratorium Medis</p>
<?php if($err)echo '<div class="err">'.e($err).'</div>';if($ok)echo '<div class="ok">'.e($ok).'</div>';?>
<div class="card <?=$showReg?'hide':''?>" id="pl"><h2>Masuk ke akun Anda</h2><form method="post"><?=csrf()?>
<label>NIM / Username</label><input name="nim" autocomplete="username" required>
<label>Password</label><div class="pw"><input type="password" name="pw" autocomplete="current-password" required><button type="button" class="eye" aria-label="Tampilkan password" onclick="eye(this)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/><line class="sl" x1="3" y1="3" x2="21" y2="21" style="display:none"/></svg></button></div>
<button name="login">Masuk</button></form>
<div class="sw">Belum punya akun? <a onclick="tampil(true)">Daftar</a></div></div>
<div class="card <?=$showReg?'':'hide'?>" id="pd"><h2>Buat akun baru</h2><form method="post"><?=csrf()?>
<label>NIM</label><input name="nim" required><label>Nama Lengkap</label><input name="nama" required>
<label>Password <small>(min. 6 karakter)</small></label><div class="pw"><input type="password" name="pw" autocomplete="new-password" required><button type="button" class="eye" aria-label="Tampilkan password" onclick="eye(this)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/><line class="sl" x1="3" y1="3" x2="21" y2="21" style="display:none"/></svg></button></div>
<label class="chk"><input type="checkbox" id="ds" <?=$kodeOn?'checked':''?>> Saya dosen</label>
<input name="kode" id="kd" class="<?=$kodeOn?'':'hide'?>" placeholder="Kode dosen">
<button name="daftar">Daftar</button></form>
<div class="sw">Sudah punya akun? <a onclick="tampil(false)">Masuk</a></div></div><p class="ft">© 2026 STIKES Karya Putra Bangsa Tulungagung<br>Prodi Farmasi &amp; Teknologi Laboratorium Medis</p></div>
<script>
document.body.classList.add('login');
const pl=document.getElementById('pl'),pd=document.getElementById('pd'),kd=document.getElementById('kd');
function eye(b){const i=b.previousElementSibling,s=i.type==='password';i.type=s?'text':'password';b.querySelector('.sl').style.display=s?'':'none';}
function tampil(r){pl.classList.toggle('hide',r);pd.classList.toggle('hide',!r);}
document.getElementById('ds').onchange=e=>{kd.classList.toggle('hide',!e.target.checked);if(!e.target.checked)kd.value='';};
</script>
<?php foot();
