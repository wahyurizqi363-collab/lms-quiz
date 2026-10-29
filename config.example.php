<?php
session_start();
// === COPY TO config.php AND FILL IN YOUR MYSQL DATA ===
$DB_HOST='localhost'; $DB_NAME='lms_quiz'; $DB_USER='root'; $DB_PASS='';
define('KODE_DOSEN','CHANGE_ME'); // kode untuk mendaftar sebagai dosen/admin
try{$db=new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",$DB_USER,$DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}catch(Exception $e){die('Koneksi database gagal. Cek config.php');}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function user(){return $_SESSION['u']??null;}
function need($role){$u=user();if(!$u){header('Location: index.php');exit;}if($u['role']!==$role){header('Location: '.($u['role']=='admin'?'admin.php':'dashboard.php'));exit;}}
function csrf(){if(empty($_SESSION['t']))$_SESSION['t']=bin2hex(random_bytes(16));return '<input type="hidden" name="t" value="'.$_SESSION['t'].'">';}
function chk(){if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['t']??'',$_POST['t']??''))die('Token tidak valid, kembali & muat ulang halaman.');}
function head($t){$u=user();echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($t).' - LMS Quiz</title><link rel="stylesheet"    href="style.css?v='.filemtime(__DIR__.'/style.css').'"></head><body><header><b>📚 LMS Quiz</b>';
if($u)echo '<span>'.e($u['nama']).' · <a href="'.($u['role']=='admin'?'admin.php':'dashboard.php').'">Beranda</a> · <a href="logout.php">Keluar</a></span>';
echo '</header><main>';}
function foot(){echo '</main></body></html>';}

// === Migrasi otomatis: membuat tabel/kolom yang belum ada (aman dijalankan berulang) ===
function pastikan_skema($db){
 if(!empty($_SESSION['skema_v3']))return;
 try{
  $db->exec("CREATE TABLE IF NOT EXISTS attempts(user_id INT NOT NULL,quiz_id INT NOT NULL,mulai DATETIME NULL,terakhir DATETIME NULL,PRIMARY KEY(user_id,quiz_id))");
  $db->exec("CREATE TABLE IF NOT EXISTS draft(user_id INT NOT NULL,quiz_id INT NOT NULL,jawab MEDIUMTEXT NULL,diubah DATETIME NULL,PRIMARY KEY(user_id,quiz_id))");
  $tambah=['attempts'=>['terakhir'=>'DATETIME NULL'],
           'users'=>['email'=>'VARCHAR(120) NULL','hp'=>'VARCHAR(25) NULL','prodi'=>'VARCHAR(10) NULL','last_login'=>'DATETIME NULL'],
           'quizzes'=>['pengampu'=>'VARCHAR(120) NULL','pembahasan'=>'TINYINT NOT NULL DEFAULT 0'],
           'results'=>['durasi_detik'=>'INT NULL','jawaban'=>'MEDIUMTEXT NULL']];
  foreach($tambah as $tb=>$cols)foreach($cols as $c=>$def){
   if(!$db->query("SHOW COLUMNS FROM `$tb` LIKE '$c'")->fetch())$db->exec("ALTER TABLE `$tb` ADD COLUMN `$c` $def");}
  $_SESSION['skema_v3']=1;
 }catch(Exception $e){}
}
pastikan_skema($db);

// === Finalisasi otomatis: kalau waktu habis & jawaban belum terkirim, nilai dari jawaban yang tersimpan (draft) ===
function finalisasi($db,$uid=null,$qid=null,$jeda=70){
 if(!$uid&&!$qid){if(($_SESSION['fin']??0)>time()-20)return;$_SESSION['fin']=time();}
 try{
  $db->exec("SET time_zone='+07:00'");$w='';$p=[];
  if($uid){$w.=' AND a.user_id=?';$p[]=$uid;}if($qid){$w.=' AND a.quiz_id=?';$p[]=$qid;}
  $s=$db->prepare("SELECT a.user_id,a.quiz_id,q.durasi FROM attempts a JOIN quizzes q ON q.id=a.quiz_id WHERE a.mulai IS NOT NULL AND TIMESTAMPDIFF(SECOND,a.mulai,NOW())>q.durasi*60+".(int)$jeda." AND NOT EXISTS(SELECT 1 FROM results r WHERE r.user_id=a.user_id AND r.quiz_id=a.quiz_id)$w LIMIT 50");
  $s->execute($p);
  foreach($s->fetchAll() as $a){
   $k=$db->prepare('SELECT id,kunci FROM questions WHERE quiz_id=?');$k->execute([$a['quiz_id']]);$ks=$k->fetchAll();
   $d=$db->prepare('SELECT jawab FROM draft WHERE user_id=? AND quiz_id=?');$d->execute([$a['user_id'],$a['quiz_id']]);
   $jw=json_decode((string)$d->fetchColumn(),true)?:[];
   $benar=0;foreach($ks as $x)if(($jw[$x['id']]??'')===$x['kunci'])$benar++;
   $tot=count($ks);$skor=$tot?round($benar/$tot*100):0;
   $sv=[];foreach($ks as $x){$v=$jw[$x['id']]??'';if(preg_match('/^[A-E]$/',(string)$v))$sv[$x['id']]=$v;}
   simpan_hasil($db,$a['user_id'],$a['quiz_id'],$skor,$benar,$tot,$a['durasi']*60,$sv);
   try{$db->prepare('DELETE FROM draft WHERE user_id=? AND quiz_id=?')->execute([$a['user_id'],$a['quiz_id']]);}catch(Exception $x){}
  }
 }catch(Exception $e){}
}

// === Simpan hasil + jawaban mahasiswa (untuk riwayat & pembahasan). Aman walau kolom 'jawaban' belum ada ===
function simpan_hasil($db,$uid,$qid,$skor,$benar,$tot,$dur,$jawaban){
 try{$db->prepare('INSERT INTO results(user_id,quiz_id,skor,benar,total,durasi_detik,jawaban) VALUES(?,?,?,?,?,?,?)')->execute([$uid,$qid,$skor,$benar,$tot,$dur,json_encode($jawaban)]);return;}
 catch(PDOException $e){if(strpos($e->getMessage(),'Duplicate')!==false)return;}
 try{$db->prepare('INSERT INTO results(user_id,quiz_id,skor,benar,total,durasi_detik) VALUES(?,?,?,?,?,?)')->execute([$uid,$qid,$skor,$benar,$tot,$dur]);}catch(PDOException $e){}
}
// === Peringkat mahasiswa pada satu quiz (urutan sama dengan halaman Hasil dosen: skor, lalu durasi tercepat, lalu waktu kirim) ===
function peringkat($db,$qid,$uid){
 try{
  $t=$db->prepare('SELECT COUNT(DISTINCT x.user_id) FROM results x JOIN users ux ON ux.id=x.user_id AND ux.role=\'mhs\' WHERE x.quiz_id=?');$t->execute([$qid]);$total=(int)$t->fetchColumn();
  $s=$db->prepare('SELECT 1+COUNT(*) FROM results x JOIN users ux ON ux.id=x.user_id AND ux.role=\'mhs\' JOIN results m ON m.user_id=? AND m.quiz_id=? WHERE x.quiz_id=? AND x.user_id<>m.user_id AND (x.skor>m.skor OR (x.skor=m.skor AND (COALESCE(x.durasi_detik,999999)<COALESCE(m.durasi_detik,999999) OR (COALESCE(x.durasi_detik,999999)=COALESCE(m.durasi_detik,999999) AND (x.waktu<m.waktu OR (x.waktu=m.waktu AND x.id<m.id))))))');
  $s->execute([$uid,$qid,$qid]);return [(int)$s->fetchColumn(),$total];
 }catch(Exception $e){return [null,0];}
}
// Pembahasan (kunci jawaban) terbuka kalau quiz sudah dinonaktifkan atau dosen membukanya manual
function boleh_bahas($qz){return empty($qz['aktif'])||!empty($qz['pembahasan']);}

function tgl_id($t){$b=['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];$x=strtotime($t);return date('d',$x).' '.$b[(int)date('n',$x)].' '.date('Y H:i',$x);}
