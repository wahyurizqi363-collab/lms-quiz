<?php require 'config.php';require 'layout.php';need('mhs');
$uid=user()['id'];$id=(int)($_GET['id']??0);
try{$db->exec("SET time_zone='+07:00'");}catch(Exception $e){}
$q=$db->prepare('SELECT * FROM quizzes WHERE id=?');$q->execute([$id]);$qz=$q->fetch();
$r=$db->prepare('SELECT * FROM results WHERE user_id=? AND quiz_id=?');$r->execute([$uid,$id]);$res=$r->fetch();
if(!$qz||!$res){header('Location: dashboard.php');exit;}
list($rk,$jt)=peringkat($db,$id,$uid);
$s=$db->prepare('SELECT * FROM questions WHERE quiz_id=? ORDER BY id');$s->execute([$id]);$soal=$s->fetchAll();
$bahas=boleh_bahas($qz);
$jw=json_decode((string)($res['jawaban']??''),true);$ada=is_array($jw);if(!$ada)$jw=[];
$st=[];$c=['b'=>0,'s'=>0,'k'=>0];
foreach($soal as $i=>$x){$a=$jw[$x['id']]??'';
 if(!$ada)$st[$i]='x';elseif($a==='')$st[$i]='k';elseif($a===$x['kunci'])$st[$i]='b';else $st[$i]='s';
 if($ada)$c[$st[$i]]++;}
function durasi_txt($d){if($d===null||$d==='')return '-';$d=(int)$d;return floor($d/60).':'.str_pad($d%60,2,'0',STR_PAD_LEFT);}
hd('Riwayat');?>
<style>
.rv{border-top:0;border-left:6px solid #94a3b8;padding:16px 18px}.rv.b{border-left-color:#10b981}.rv.s{border-left-color:#ef4444}.rv.k{border-left-color:#f59e0b}
.rv .top{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px}
.rv .no{font-weight:700;color:var(--p);font-size:13px;letter-spacing:.8px;text-transform:uppercase}
.bd{padding:2px 10px;border-radius:99px;font-size:12px;font-weight:700}.bd.b{background:#d1fae5;color:#065f46}.bd.s{background:#fee2e2;color:#991b1b}.bd.k{background:#fef3c7;color:#92400e}.bd.x{background:#e2e8f0;color:#475569}
.rv .qt{font-weight:600;line-height:1.55;margin-bottom:10px}
.op{display:flex;gap:8px;align-items:flex-start;padding:9px 12px;border:1.5px solid #d5ede9;border-radius:10px;margin-bottom:6px;background:#fff}
.op.ok{background:#d1fae5;border-color:#10b981;font-weight:600}.op.bad{background:#fee2e2;border-color:#ef4444}
.op em{margin-left:auto;font-style:normal;font-size:12px;font-weight:700;white-space:nowrap}
.op.ok em{color:#065f46}.op.bad em{color:#991b1b}
.flt{display:flex;gap:8px;flex-wrap:wrap;margin:6px 0 14px}.flt button{background:#fff;color:var(--p);border:1.5px solid var(--p)}.flt button.on{background:var(--p);color:#fff}
.lock{text-align:center;color:#5b7b79}
</style>
<div class="card">
 <h2 style="margin-top:0"><?=e($qz['judul'])?></h2>
 <small>Dikerjakan <?=e(tgl_id($res['waktu']))?> · Durasi <?=durasi_txt($res['durasi_detik'])?> dari <?=(int)$qz['durasi']?> menit</small>
 <div class="stats">
  <div class="stat"><b><?=(int)$res['skor']?></b><span>Nilai</span></div>
  <?php if($rk){?><div class="stat"><b>#<?=$rk?></b><span>Peringkat dari <?=$jt?></span></div><?php }?>
  <div class="stat"><b><?=(int)$res['benar']?>/<?=(int)$res['total']?></b><span>Benar</span></div>
  <?php if($ada){?><div class="stat"><b><?=$c['s']?></b><span>Salah</span></div><div class="stat"><b><?=$c['k']?></b><span>Tidak dijawab</span></div><?php }?>
 </div>
 <?php if($qz['aktif']){?><small>Peringkat bersifat sementara selama quiz masih aktif dan masih ada mahasiswa yang mengerjakan.</small><?php }?>
</div>
<?php if(!$bahas){?>
<div class="card lock"><h3 style="margin-top:0">🔒 Pembahasan belum dibuka</h3>Jawaban benar akan bisa dilihat setelah dosen membuka pembahasan atau quiz ditutup, supaya tidak mengganggu mahasiswa lain yang masih mengerjakan.</div>
<?php }else{
 if(!$ada)echo '<div class="card" style="background:#fffbeb">ℹ️ Jawaban Anda pada pengerjaan ini tidak tersimpan (dikerjakan sebelum fitur riwayat aktif), jadi hanya kunci jawaban yang ditampilkan.</div>';
 echo '<h3>Pembahasan Soal</h3>';
 if($ada)echo '<div class="flt"><button type="button" class="on" data-f="">Semua ('.count($soal).')</button><button type="button" data-f="s">❌ Salah ('.$c['s'].')</button><button type="button" data-f="k">⚠️ Tidak dijawab ('.$c['k'].')</button><button type="button" data-f="b">✅ Benar ('.$c['b'].')</button></div>';
 foreach($soal as $i=>$x){$a=$jw[$x['id']]??'';$t=$st[$i];
  $lbl=['b'=>'✅ Benar','s'=>'❌ Salah','k'=>'⚠️ Tidak dijawab','x'=>'Kunci jawaban'][$t];
  echo '<div class="card rv '.$t.'" data-st="'.$t.'"><div class="top"><span class="no">Soal '.($i+1).'</span><span class="bd '.$t.'">'.$lbl.'</span></div><div class="qt">'.nl2br(e($x['soal'])).'</div>';
  foreach(['A'=>'a','B'=>'b','C'=>'c','D'=>'d','E'=>'e'] as $L=>$col){if(($x[$col]??'')==='')continue;
   $cls=$L===$x['kunci']?' ok':(($ada&&$L===$a)?' bad':'');
   $tag=$L===$x['kunci']?($ada&&$L===$a?'✔ Jawaban Anda · benar':'✔ Jawaban benar'):(($ada&&$L===$a)?'✖ Jawaban Anda':'');
   echo '<div class="op'.$cls.'"><span><b>'.$L.'.</b> '.e($x[$col]).'</span>'.($tag?'<em>'.$tag.'</em>':'').'</div>';}
  echo '</div>';}
?>
<script>
document.querySelectorAll('.flt button').forEach(b=>b.onclick=()=>{document.querySelectorAll('.flt button').forEach(x=>x.classList.toggle('on',x===b));
 document.querySelectorAll('.rv').forEach(c=>c.hidden=b.dataset.f&&c.dataset.st!==b.dataset.f);});
</script>
<?php }
foot();
