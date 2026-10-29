<?php require 'config.php';require 'layout.php';need('mhs');$uid=user()['id'];
finalisasi($db,$uid,null,3);
try{$db->exec("SET time_zone='+07:00'");}catch(Exception $e){}
$rows=$db->query("SELECT q.*,(SELECT COUNT(*) FROM questions WHERE quiz_id=q.id) n FROM quizzes q WHERE aktif=1 ORDER BY id DESC")->fetchAll();
$res=$db->prepare('SELECT quiz_id,skor FROM results WHERE user_id=?');$res->execute([$uid]);$done=array_column($res->fetchAll(),'skor','quiz_id');
$mul=[];try{$m=$db->prepare('SELECT quiz_id FROM attempts WHERE user_id=?');$m->execute([$uid]);$mul=array_column($m->fetchAll(),'quiz_id');}catch(Exception $e){}
hd('Daftar Quiz');echo '<h2>Daftar Quiz</h2><div id="lst">';
if(!$rows)echo '<div class="card">Belum ada quiz aktif.</div>';
foreach($rows as $r){echo '<div class="card"><b>'.e($r['judul']).'</b><br><small>'.$r['n'].' soal · '.$r['durasi'].' menit</small>'.(!empty($r['pengampu'])?'<br><small>👨‍🏫 Dosen pengampu: <b>'.e($r['pengampu']).'</b></small>':'').'<br><br>';
 if(isset($done[$r['id']])){list($rk,$jt)=peringkat($db,$r['id'],$uid);echo '✅ Sudah dikerjakan · Nilai <b>'.(int)$done[$r['id']].'</b>'.($rk?' · Peringkat <b>#'.$rk.'</b> dari '.$jt:'').'<br><br><a class="btn" href="riwayat.php?id='.$r['id'].'">📖 Lihat Riwayat &amp; Pembahasan</a>';}
 elseif($r['n']>0){$lj=in_array($r['id'],$mul);if($lj)echo '<div style="color:#92400e;font-size:13px;margin-bottom:8px">⚠️ Anda sudah memulai quiz ini. Waktu tetap berjalan walau Anda keluar.</div>';echo '<a class="btn" href="quiz.php?id='.$r['id'].'">'.($lj?'Lanjutkan Quiz':'Mulai Quiz').'</a>';}
 echo '</div>';}
$ak=array_column($rows,'id');
$rw=$db->prepare('SELECT r.quiz_id,r.skor,r.benar,r.total,r.waktu,q.judul FROM results r JOIN quizzes q ON q.id=r.quiz_id WHERE r.user_id=? ORDER BY r.waktu DESC');$rw->execute([$uid]);
$rw=array_filter($rw->fetchAll(),fn($x)=>!in_array($x['quiz_id'],$ak));
if($rw){echo '<h3 style="margin-top:22px">Riwayat Pengerjaan</h3>';foreach($rw as $x){list($rk,$jt)=peringkat($db,$x['quiz_id'],$uid);echo '<div class="card"><b>'.e($x['judul']).'</b><br><small>'.e(tgl_id($x['waktu'])).' · Benar '.(int)$x['benar'].'/'.(int)$x['total'].'</small><br>Nilai <b>'.(int)$x['skor'].'</b>'.($rk?' · Peringkat <b>#'.$rk.'</b> dari '.$jt:'').'<br><br><a class="btn" href="riwayat.php?id='.$x['quiz_id'].'">📖 Lihat Riwayat &amp; Pembahasan</a></div>';}}
echo '</div>';?>
<script>
/* auto-update daftar quiz: cek ke server tiap 3 detik, tanpa refresh */
(function(){const L=document.getElementById('lst');let busy=false;
async function cek(){if(document.hidden||busy)return;busy=true;
 try{const r=await fetch(location.href,{cache:'no-store',credentials:'same-origin'});
  if(r.redirected&&!r.url.includes('dashboard.php')){location.href=r.url;return;}
  const d=new DOMParser().parseFromString(await r.text(),'text/html'),n=d.getElementById('lst');
  if(n&&n.innerHTML!==L.innerHTML)L.innerHTML=n.innerHTML;}catch(e){}
 busy=false;}
setInterval(cek,3000);
document.addEventListener('visibilitychange',cek);window.addEventListener('focus',cek);})();
</script>
<?php foot();
