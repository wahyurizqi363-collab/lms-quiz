<?php require 'config.php';require 'layout.php';need('mhs');
$id=(int)($_GET['id']??$_POST['id']??0);$uid=user()['id'];
$q=$db->prepare('SELECT * FROM quizzes WHERE id=? AND aktif=1');$q->execute([$id]);$qz=$q->fetch();
if(!$qz){header('Location: dashboard.php');exit;}
if($_SERVER['REQUEST_METHOD']!=='POST')finalisasi($db,$uid,$id,3);
$r=$db->prepare('SELECT * FROM results WHERE user_id=? AND quiz_id=?');$r->execute([$uid,$id]);$done=$r->fetch();
if($_SERVER['REQUEST_METHOD']==='POST'&&!$done){chk();
 $s=$db->prepare('SELECT id,kunci FROM questions WHERE quiz_id=?');$s->execute([$id]);$ks=$s->fetchAll();
 $lama=null;try{$db->exec("SET time_zone='+07:00'");$c=$db->prepare('SELECT TIMESTAMPDIFF(SECOND,mulai,NOW()) FROM attempts WHERE user_id=? AND quiz_id=?');$c->execute([$uid,$id]);$v=$c->fetchColumn();if($v!==false&&$v!==null)$lama=(int)$v;}catch(Exception $e){}
 if($lama===null){$st=$_SESSION['start'][$id]??0;$lama=$st?time()-$st:null;}
 $telat=$lama===null||$lama>($qz['durasi']*60+60);$dur=$lama!==null?min($lama,$qz['durasi']*60):null;
 $jw=$_POST['j']??[];if($telat){$jw=[];try{$dq=$db->prepare('SELECT jawab FROM draft WHERE user_id=? AND quiz_id=?');$dq->execute([$uid,$id]);$jw=json_decode((string)$dq->fetchColumn(),true)?:[];}catch(Exception $e){}}
 $benar=0;foreach($ks as $k){if(($jw[$k['id']]??'')===$k['kunci'])$benar++;}
 $tot=count($ks);$skor=$tot?round($benar/$tot*100):0;
 $sv=[];foreach($ks as $k){$v=$jw[$k['id']]??'';if(preg_match('/^[A-E]$/',(string)$v))$sv[$k['id']]=$v;}
 simpan_hasil($db,$uid,$id,$skor,$benar,$tot,$dur,$sv);
 try{$db->prepare('DELETE FROM draft WHERE user_id=? AND quiz_id=?')->execute([$uid,$id]);}catch(Exception $e){}
 unset($_SESSION['start'][$id]);header('Location: quiz.php?id='.$id);exit;}
if($done){list($rk,$jt)=peringkat($db,$id,$uid);hd('Hasil');echo '<div class="card"><h2>'.e($qz['judul']).'</h2><p>✅ Jawaban Anda sudah terkirim.</p><div class="stats"><div class="stat"><b>'.(int)$done['skor'].'</b><span>Nilai</span></div>'.($rk?'<div class="stat"><b>#'.$rk.'</b><span>Peringkat dari '.$jt.'</span></div>':'').'<div class="stat"><b>'.(int)$done['benar'].'/'.(int)$done['total'].'</b><span>Benar</span></div></div><p><small>Peringkat bersifat sementara selama masih ada mahasiswa yang mengerjakan.</small></p><a class="btn" href="riwayat.php?id='.$id.'">📖 Lihat Riwayat &amp; Pembahasan</a> <a class="btn gray" href="dashboard.php">Kembali</a></div>';foot();exit;}
if(!isset($_SESSION['start'][$id]))$_SESSION['start'][$id]=time();
$kembali=false;$lama=0;
try{$db->exec("SET time_zone='+07:00'");$c=$db->prepare('SELECT TIMESTAMPDIFF(SECOND,terakhir,NOW()) idle FROM attempts WHERE user_id=? AND quiz_id=?');$c->execute([$uid,$id]);$ada=$c->fetch();
 if($ada){$kembali=($ada['idle']===null||(int)$ada['idle']>60);$db->prepare('UPDATE attempts SET terakhir=NOW() WHERE user_id=? AND quiz_id=?')->execute([$uid,$id]);}
 else{$db->prepare('INSERT INTO attempts(user_id,quiz_id,mulai,terakhir) VALUES(?,?,NOW(),NOW())')->execute([$uid,$id]);try{$db->prepare('DELETE FROM draft WHERE user_id=? AND quiz_id=?')->execute([$uid,$id]);}catch(Exception $e){}}
 $c=$db->prepare('SELECT TIMESTAMPDIFF(SECOND,mulai,NOW()) FROM attempts WHERE user_id=? AND quiz_id=?');$c->execute([$uid,$id]);$lama=(int)$c->fetchColumn();
}catch(Exception $e){$lama=time()-$_SESSION['start'][$id];}
$sisa=max(0,$qz['durasi']*60-$lama);
$s=$db->prepare("SELECT * FROM questions WHERE quiz_id=? ORDER BY MD5(CONCAT(id,'-',?))");$s->execute([$id,$uid.'-'.$id]);$soal=$s->fetchAll();
$dr=[];try{$d=$db->prepare('SELECT jawab FROM draft WHERE user_id=? AND quiz_id=?');$d->execute([$uid,$id]);$dr=json_decode((string)$d->fetchColumn(),true)?:[];}catch(Exception $e){}
hd($qz['judul']);?>
<div class="card"><h2 style="margin:0 0 6px"><?=e($qz['judul'])?></h2><small><?=!empty($qz['pengampu'])?'👨‍🏫 Dosen Pengampu: <b>'.e($qz['pengampu']).'</b> · ':''?>📝 <?=count($soal)?> soal · ⏱ <?=$qz['durasi']?> menit</small></div>
<?php if($kembali)echo '<div class="card" style="background:#fef3c7;border-left:5px solid #f59e0b">⚠️ <b>Anda sempat meninggalkan halaman quiz.</b> Waktu tetap berjalan selama Anda keluar. Jawaban yang sudah Anda pilih tersimpan otomatis dan dipulihkan di bawah. Silakan lanjutkan dan kumpulkan sebelum waktu habis.</div>';?>
<div id="timer">Sisa waktu: <span id="t"></span><div id="st" style="font-size:12px;font-weight:500;color:#5b7b79;margin-top:2px"></div></div>
<style>
main{max-width:1000px}
.qz-wrap{display:grid;grid-template-columns:1fr 270px;gap:14px;align-items:start}
.qz-bar{height:8px;border-radius:99px;background:#d5ede9;overflow:hidden;margin:8px 0 2px}.qz-bar i{display:block;height:100%;width:0;background:linear-gradient(90deg,#14b8a6,#0f766e);transition:width .3s}
.sl{animation:sl .25s ease}@keyframes sl{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:none}}
.sl .qn{font-weight:700;color:var(--p);font-size:13px;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px}.sl .qn span{font-weight:500;color:#5b7b79;text-transform:none;letter-spacing:0}
.sl .qt{font-size:18px;font-weight:600;line-height:1.55;margin-bottom:14px}
.sl .opt{padding:13px 14px;border-width:1.5px}.sl .opt.on{background:#ccfbf1;border-color:var(--p);font-weight:600}
.ctl{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:16px;padding-top:14px;border-top:1px solid #d5ede9}
.ctl button:disabled{opacity:.4;cursor:not-allowed}
.qz-nav{position:sticky;top:64px}.qz-nav h4{margin:0 0 10px;color:#115e59}
.ng{display:grid;grid-template-columns:repeat(auto-fill,minmax(40px,1fr));gap:6px;max-height:300px;overflow:auto;padding:2px}
.nv{padding:0;height:40px;border-radius:8px;background:#e2e8f0;color:#475569;font-weight:700;border:2px solid transparent}
.nv:hover{background:#cbd5e1}.nv.ans{background:#10b981;color:#fff}.nv.skip{background:#fbbf24;color:#78350f}.nv.cur{border-color:#0f172a;box-shadow:0 0 0 2px #fff inset}
.lg{display:flex;flex-wrap:wrap;gap:8px 12px;margin:12px 0;font-size:12px;color:#5b7b79}.lg i{display:inline-block;width:12px;height:12px;border-radius:4px;margin-right:4px;vertical-align:-1px}
.sm{font-size:13px;color:#475569;margin-bottom:10px;line-height:1.7}
@media(max-width:820px){.qz-wrap{grid-template-columns:1fr}.qz-nav{position:static}.sl .qt{font-size:16px}}
</style>
<form method="post" id="f" onsubmit="return konfirm()"><?=csrf()?><input type="hidden" name="id" value="<?=$id?>">
<div class="qz-wrap"><div>
<div class="card" style="padding-bottom:14px"><b id="pgt"></b><div class="qz-bar"><i id="pgb"></i></div></div>
<div class="card" id="slides">
<?php foreach($soal as $i=>$x){echo '<section class="sl" data-i="'.$i.'"'.($i?' hidden':'').'><div class="qn">Soal '.($i+1).' <span>dari '.count($soal).'</span></div><div class="qt">'.nl2br(e($x['soal'])).'</div>';
 foreach(['A'=>'a','B'=>'b','C'=>'c','D'=>'d','E'=>'e'] as $L=>$col)if(($x[$col]??'')!==''){$ck=(($dr[$x['id']]??'')===$L);echo '<label class="opt'.($ck?' on':'').'"><input type="radio" name="j['.$x['id'].']" value="'.$L.'"'.($ck?' checked':'').'>'.$L.'. '.e($x[$col]).'</label>';}
 echo '</section>';}?>
<div class="ctl"><button type="button" class="gray" id="pv">← Sebelumnya</button><button type="button" id="nx">Selanjutnya →</button><button id="sb" class="red" style="display:none">✔ Kumpulkan Jawaban</button></div>
</div></div>
<aside class="card qz-nav"><h4>Navigasi Soal</h4><div class="sm" id="sm"></div><div class="ng">
<?php foreach($soal as $i=>$x)echo '<button type="button" class="nv" data-i="'.$i.'">'.($i+1).'</button>';?></div>
<div class="lg"><span><i style="background:#10b981"></i>Sudah dijawab</span><span><i style="background:#fbbf24"></i>Dilewati</span><span><i style="background:#e2e8f0"></i>Belum dibuka</span></div>
<button style="width:100%">✔ Kumpulkan Jawaban</button></aside></div></form>
<script>let sudah=false,boleh=false;const END=Date.now()+<?=$sisa?>*1000;const t=document.getElementById('t'),f=document.getElementById('f');
/* timer berbasis jam asli: tetap akurat walau tab di background / perangkat sempat tidur */
function tick(){if(sudah)return;const r=Math.max(0,Math.ceil((END-Date.now())/1000)),m=Math.floor(r/60),d=r%60;t.textContent=m+':'+String(d).padStart(2,'0');if(r<=0){if(window.habis)window.habis();else{sudah=true;f.onsubmit=null;f.submit();}}}tick();setInterval(tick,500);document.addEventListener('visibilitychange',tick);
function ping(){fetch('ping.php?id=<?=$id?>',{cache:'no-store',credentials:'same-origin'}).catch(()=>{});}
setInterval(ping,20000);
const PESAN='Anda sedang mengerjakan quiz. Jika keluar, waktu tetap berjalan (jawaban yang sudah dipilih tersimpan otomatis). Yakin ingin keluar?';
f.addEventListener('submit',e=>{if(!e.defaultPrevented)sudah=true;});
document.addEventListener('click',e=>{const a=e.target.closest('a[href]');if(!a)return;if(confirm(PESAN))boleh=true;else e.preventDefault();});
window.addEventListener('beforeunload',e=>{if(sudah||boleh)return;if(window.belumTersimpan&&!window.belumTersimpan())return;e.preventDefault();e.returnValue='';});
window.addEventListener('pagehide',()=>{if(!sudah)navigator.sendBeacon('ping.php?id=<?=$id?>&keluar=1');});document.addEventListener('visibilitychange',()=>{if(!document.hidden)ping();});</script>
<script>
(function(){const S=[...document.querySelectorAll('.sl')],N=[...document.querySelectorAll('.nv')],T=S.length;let cur=0;const seen=new Set([0]);
let kotor=false,tm=null;const pv=document.getElementById('pv'),nx=document.getElementById('nx'),sb=document.getElementById('sb');
const ans=i=>!!S[i].querySelector('input:checked');
function paint(){let a=0,sk=0;N.forEach((b,i)=>{const y=ans(i),k=!y&&seen.has(i)&&i!==cur;if(y)a++;if(k)sk++;b.className='nv'+(y?' ans':'')+(k?' skip':'')+(i===cur?' cur':'');});
 document.getElementById('pgt').textContent='Soal '+(cur+1)+' dari '+T+' · '+a+' terjawab';
 document.getElementById('pgb').style.width=(T?a/T*100:0)+'%';
 document.getElementById('sm').innerHTML='✅ Dijawab: <b>'+a+'</b><br>⚠️ Dilewati: <b>'+sk+'</b><br>⬜ Belum dibuka: <b>'+(T-a-sk-(ans(cur)?0:1))+'</b>';
 pv.disabled=cur===0;const last=cur===T-1;nx.style.display=last?'none':'';sb.style.display=last?'':'none';}
function go(i){if(i<0||i>=T)return;S[cur].hidden=true;cur=i;S[cur].hidden=false;seen.add(cur);paint();if(innerWidth<820)scrollTo({top:0,behavior:'smooth'});}
pv.onclick=()=>go(cur-1);nx.onclick=()=>go(cur+1);N.forEach(b=>b.onclick=()=>go(+b.dataset.i));
S.forEach(sl=>sl.addEventListener('change',()=>{sl.querySelectorAll('.opt').forEach(o=>o.classList.toggle('on',o.querySelector('input').checked));paint();kotor=true;clearTimeout(tm);tm=setTimeout(()=>simpan(),500);}));
window.konfirm=function(){const k=[];S.forEach((_,i)=>{if(!ans(i))k.push(i+1);});
 return confirm(k.length?'Masih ada '+k.length+' soal belum dijawab (no. '+k.slice(0,15).join(', ')+(k.length>15?', ...':'')+'). Tetap kumpulkan sekarang?':'Kumpulkan jawaban sekarang?');};
/* simpan otomatis ke server */
const ST=document.getElementById('st');
function simpan(beacon){if(sudah&&!beacon)return;const fd=new FormData(f);
 if(beacon&&navigator.sendBeacon){navigator.sendBeacon('simpan.php',fd);return;}
 fetch('simpan.php',{method:'POST',body:fd,credentials:'same-origin',cache:'no-store'}).then(r=>r.json()).then(j=>{if(j.ok){kotor=false;ST.textContent='✓ Jawaban tersimpan otomatis';}else ST.textContent='⚠ Tidak tersimpan ('+(j.alasan||'error')+')';}).catch(()=>{ST.textContent='⚠ Belum tersimpan, periksa koneksi internet';});}
setInterval(()=>{if(kotor)simpan();},10000);
document.addEventListener('visibilitychange',()=>{if(document.hidden&&kotor)simpan(true);});
window.belumTersimpan=()=>kotor;
window.habis=function(){if(sudah)return;simpan(true);sudah=true;f.onsubmit=null;f.submit();};
/* pulihkan tampilan: tandai pilihan yang tersimpan & mulai dari soal pertama yang belum dijawab */
S.forEach(sl=>sl.querySelectorAll('.opt').forEach(o=>o.classList.toggle('on',o.querySelector('input').checked)));
const f0=S.findIndex((_,i)=>!ans(i));if(f0>0){S[0].hidden=true;cur=f0;S[cur].hidden=false;seen.add(cur);}
paint();})();
</script>
<?php foot();
