<?php require 'config.php';require 'layout.php';need('admin');$id=(int)($_GET['id']??0);
try{$db->exec("SET time_zone='+07:00'");}catch(Exception $e){}
$q=$db->prepare('SELECT * FROM quizzes WHERE id=?');$q->execute([$id]);$qz=$q->fetch();if(!$qz){header('Location: admin.php');exit;}
finalisasi($db,null,$id);
$lim=$qz['durasi']*60+120;
try{$s=$db->prepare("SELECT u.nim,u.nama,u.last_login,a.mulai,a.user_id aid,r.id rid,r.skor,TIMESTAMPDIFF(SECOND,a.mulai,NOW()) lama,TIMESTAMPDIFF(SECOND,a.terakhir,NOW()) idle FROM users u LEFT JOIN results r ON r.user_id=u.id AND r.quiz_id=? LEFT JOIN attempts a ON a.user_id=u.id AND a.quiz_id=? WHERE u.role='mhs' ORDER BY u.nim");$s->execute([$id,$id]);$rows=$s->fetchAll();}
catch(Exception $e){hd('Pantau');echo '<div class="err">Tabel pelacakan belum dibuat. Jalankan update3.sql di phpMyAdmin.</div>';foot();exit;}
$L=['belum'=>['⚪','Belum login',''],'login'=>['🔵','Login, belum mulai','b'],'kerja'=>['🟡','Sedang mengerjakan','y'],'putus'=>['🔴','Keluar sebelum selesai','r'],'lewat'=>['🟠','Waktu habis, belum terkirim','o'],'selesai'=>['🟢','Selesai','g']];
$cnt=array_fill_keys(array_keys($L),0);
foreach($rows as &$r){$r['st']=$r['rid']?'selesai':($r['aid']?($r['lama']>$lim?'lewat':(($r['idle']!==null&&$r['idle']<=60)?'kerja':'putus')):($r['last_login']?'login':'belum'));$cnt[$r['st']]++;}unset($r);
$ord=array_flip(array_keys($L));usort($rows,fn($a,$b)=>($ord[$a['st']]<=>$ord[$b['st']])?:strcmp($a['nim'],$b['nim']));
$f=$_GET['f']??'';if($f==='todo')$rows=array_values(array_filter($rows,fn($r)=>$r['st']!=='selesai'));elseif(isset($L[$f]))$rows=array_values(array_filter($rows,fn($r)=>$r['st']===$f));
$todo=array_filter($rows,fn($r)=>$r['st']!=='selesai');$txt=implode("\n",array_map(fn($r)=>$r['nama'].' ('.$r['nim'].') - '.$L[$r['st']][1],$todo));
function ini($n){return function_exists('mb_substr')?mb_strtoupper(mb_substr($n,0,1)):strtoupper(substr($n,0,1));}
function wk($t){return $t?date('d/m H:i',strtotime($t)):'-';}
hd('Pantau');?>
<h2>📡 Pantau: <?=e($qz['judul'])?></h2>
<style>
.bd.r{background:#fee2e2;color:#991b1b}
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:14px}
.tl{display:block;text-decoration:none;color:inherit;background:#fffffff2;border-radius:12px;padding:12px 8px;text-align:center;box-shadow:0 2px 8px #0f766e22;border-bottom:4px solid #94a3b8;transition:.15s}
.tl:hover{transform:translateY(-2px)}.tl b{display:block;font-size:28px;line-height:1.2}.tl span{font-size:12px;color:#5b7b79}.tl.on{outline:2px solid var(--p)}
.tl.r{border-color:#ef4444}.tl.g{border-color:#10b981}.tl.y{border-color:#f59e0b}.tl.o{border-color:#f97316}.tl.b{border-color:#3b82f6}
.pg{height:12px;background:#d5ede9;border-radius:99px;overflow:hidden;margin-top:8px}.pg i{display:block;height:100%;background:linear-gradient(90deg,#10b981,#0e7490);border-radius:99px;transition:width 1s}
.nm{display:flex;align-items:center;gap:10px}.av{flex:none;width:34px;height:34px;border-radius:50%;background:#ccfbf1;color:#0f766e;font-weight:700;display:flex;align-items:center;justify-content:center}
.tabs a{margin-right:6px}
</style>
<div id="live" data-t="<?=e($txt)?>">
<?php $tot=array_sum($cnt);$pct=$tot?round($cnt['selesai']/$tot*100):0;?>
<div class="card"><b>Progres: <?=$cnt['selesai']?> dari <?=$tot?> mahasiswa sudah selesai (<?=$pct?>%)</b><div class="pg"><i style="width:<?=$pct?>%"></i></div></div>
<div class="tiles"><?php foreach($L as $k=>$v)echo '<a class="tl '.$v[2].($f===$k?' on':'').'" href="?id='.$id.'&f='.$k.'"><span style="font-size:20px">'.$v[0].'</span><b>'.$cnt[$k].'</b><span>'.$v[1].'</span></a>';?></div>
<div class="card tabs"><a class="bd <?=$f===''?'on':''?>" href="?id=<?=$id?>">Semua (<?=$tot?>)</a><a class="bd <?=$f==='todo'?'on':''?>" href="?id=<?=$id?>&f=todo">Belum selesai (<?=$tot-$cnt['selesai']?>)</a>
<br><br><button type="button" id="cp">📋 Salin daftar yang belum selesai</button><br><small>🟢 <span id="st">Langsung</span> — diperbarui otomatis tiap 5 detik tanpa refresh. "Sedang mengerjakan" = halaman quiz masih terbuka. "Keluar sebelum selesai" = sudah mulai tetapi halaman ditutup/terputus, dan akan kembali kuning bila dibuka lagi.</small></div>
<div class="card" style="overflow-x:auto"><table><tr><th>Nama</th><th>Status</th><th>Login terakhir</th><th>Mulai</th><th>Skor</th></tr>
<?php foreach($rows as $r){$v=$L[$r['st']];echo '<tr><td><div class="nm"><span class="av">'.e(ini($r['nama'])).'</span><div>'.e($r['nama']).'<br><small>'.e($r['nim']).'</small></div></div></td><td><span class="bd '.$v[2].'">'.$v[0].' '.$v[1].'</span></td><td>'.wk($r['last_login']).'</td><td>'.wk($r['mulai']).'</td><td>'.($r['st']==='selesai'?'<b>'.$r['skor'].'</b>':'-').'</td></tr>';}
if(!$rows)echo '<tr><td colspan="5">Tidak ada data.</td></tr>';?></table></div>
</div>
<script>
const live=document.getElementById('live');
document.addEventListener('click',e=>{if(!e.target.closest('#cp'))return;const T=live.dataset.t;if(!T){alert('Semua sudah selesai 🎉');return;}(navigator.clipboard?navigator.clipboard.writeText(T):Promise.reject()).then(()=>alert('Daftar disalin.'),()=>prompt('Salin manual:',T));});
async function tick(){if(document.hidden)return;try{const r=await fetch(location.href,{cache:'no-store',credentials:'same-origin'});const d=new DOMParser().parseFromString(await r.text(),'text/html').getElementById('live');
 if(d){live.innerHTML=d.innerHTML;live.dataset.t=d.dataset.t;document.getElementById('st').textContent='Langsung · diperbarui '+new Date().toLocaleTimeString('id-ID');}}catch(e){}}
setInterval(tick,5000);document.addEventListener('visibilitychange',tick);
</script>
<?php foot();
