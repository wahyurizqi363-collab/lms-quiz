<?php require 'config.php';require 'layout.php';need('admin');$id=(int)($_GET['id']??0);
$q=$db->prepare('SELECT * FROM quizzes WHERE id=?');$q->execute([$id]);$qz=$q->fetch();if(!$qz){header('Location: admin.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){chk();
 $hapus=function($rid)use($db,$id){$s=$db->prepare('SELECT user_id FROM results WHERE id=? AND quiz_id=?');$s->execute([$rid,$id]);$u=$s->fetchColumn();
  $db->prepare('DELETE FROM results WHERE id=? AND quiz_id=?')->execute([$rid,$id]);
  if($u){try{$db->prepare('DELETE FROM attempts WHERE user_id=? AND quiz_id=?')->execute([$u,$id]);}catch(Exception $e){}}};
 if(isset($_POST['hapus1']))$hapus((int)$_POST['hapus1']);
 if(isset($_POST['hapus_pilih']))foreach((array)($_POST['pilih']??[]) as $rid)$hapus((int)$rid);
 if(isset($_POST['hapus_semua'])){try{$db->prepare('DELETE FROM attempts WHERE quiz_id=? AND user_id IN(SELECT user_id FROM results WHERE quiz_id=?)')->execute([$id,$id]);}catch(Exception $e){}
  $db->prepare('DELETE FROM results WHERE quiz_id=?')->execute([$id]);}
 header('Location: admin_hasil.php?id='.$id);exit;}
finalisasi($db,null,$id);
$s=$db->prepare('SELECT r.*,u.nim,u.nama FROM results r JOIN users u ON u.id=r.user_id WHERE quiz_id=? ORDER BY r.skor DESC,COALESCE(r.durasi_detik,999999),r.waktu');$s->execute([$id]);$rows=$s->fetchAll();
function dur($d){if($d===null)return '-';$d=(int)$d;return floor($d/60).':'.str_pad($d%60,2,'0',STR_PAD_LEFT);}
if(isset($_GET['csv'])){header('Content-Type: text/csv');header('Content-Disposition: attachment; filename=hasil-quiz-'.$id.'.csv');$o=fopen('php://output','w');fputcsv($o,['Peringkat','NIM','Nama','Benar','Total','Skor','Durasi','Waktu']);foreach($rows as $i=>$r)fputcsv($o,[$i+1,$r['nim'],$r['nama'],$r['benar'],$r['total'],$r['skor'],dur($r['durasi_detik']),$r['waktu']]);exit;}
function medal($n){$c=[1=>['#fff3b0','#f5c451','#b7791f','#7a4a05'],2=>['#ffffff','#cbd5e1','#64748b','#334155'],3=>['#ffe0c2','#e39a5b','#9a4a14','#5a2a08']][$n];
 return '<svg class="medal" viewBox="0 0 48 58" width="46" height="56" aria-hidden="true"><defs><linearGradient id="mg'.$n.'" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="'.$c[0].'"/><stop offset=".5" stop-color="'.$c[1].'"/><stop offset="1" stop-color="'.$c[2].'"/></linearGradient></defs><path d="M12 0h10l6 20H18z" fill="#99f6e4"/><path d="M36 0H26l-6 20h10z" fill="#2dd4bf"/><circle cx="24" cy="36" r="18" fill="url(#mg'.$n.')"/><circle cx="24" cy="36" r="13.5" fill="none" stroke="#fff" stroke-opacity=".6" stroke-width="1.5"/><text x="24" y="42.5" text-anchor="middle" font-family="Georgia,serif" font-weight="700" font-size="18" fill="'.$c[3].'">'.$n.'</text></svg>';}
hd('Hasil');?>
<style>
.card.champ{position:relative;overflow:hidden;text-align:center;color:#fff;border-top:0;padding:26px 16px 0;
 background:radial-gradient(90% 70% at 50% 0%,#14b8a655,transparent 70%),linear-gradient(160deg,#0b4f4a 0%,#0e6b73 55%,#155e75 100%);box-shadow:0 10px 30px #0b4f4a44}
.card.champ::before{content:"";position:absolute;inset:0;pointer-events:none;opacity:.5;background:url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22140%22 height=%22140%22 viewBox=%220 0 140 140%22 fill=%22none%22 stroke=%22%23ffffff%22 stroke-opacity=%220.09%22 stroke-width=%222.5%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Cpath d=%22M22 12v20M12 22h20%22/%3E%3Cg transform=%22rotate%28-35 100 24%29%22%3E%3Crect x=%2282%22 y=%2216%22 width=%2238%22 height=%2216%22 rx=%228%22/%3E%3Cpath d=%22M101 16v16%22/%3E%3C/g%3E%3Cpolygon points=%2232,86 44,79 56,86 56,100 44,107 32,100%22/%3E%3Cpath d=%22M104 84c8 10 13 16 13 22a13 13 0 0 1-26 0c0-6 5-12 13-22z%22/%3E%3Ccircle cx=%2270%22 cy=%2258%22 r=%222.5%22/%3E%3C/svg%3E")}
.champ>*{position:relative}
.ch-eye{display:flex;align-items:center;justify-content:center;gap:10px;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#99f6e4}
.ch-eye i{width:34px;height:1px;background:linear-gradient(90deg,transparent,#5eead4)}.ch-eye i:last-child{transform:scaleX(-1)}
.card.champ h3{margin:6px 0 4px;color:#fff;font:700 26px/1.2 Georgia,"Times New Roman",serif;letter-spacing:.5px}
.ch-sub{margin:0 auto 18px;font-size:13px;color:#ccfbf1;opacity:.85}
.podium{display:flex;align-items:flex-end;justify-content:center;gap:8px;max-width:560px;margin:0 auto}
.pd{flex:1;min-width:0;max-width:180px}
.info{padding:0 4px 10px}
.medal{display:block;margin:0 auto 6px;filter:drop-shadow(0 4px 6px #0006)}
.nm{font-weight:700;font-size:15px;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word}
.nim{font-size:12px;color:#99f6e4;letter-spacing:.5px;margin:2px 0 7px}
.chips{display:flex;justify-content:center;flex-wrap:wrap;gap:5px}
.chips span{display:inline-flex;align-items:center;gap:4px;font-size:12px;padding:3px 9px;border-radius:99px;background:#ffffff1f;border:1px solid #ffffff38}
.chips b{font-size:13px}.chips svg{width:11px;height:11px}
.bw{overflow:hidden;border-radius:12px 12px 0 0}
.bar{position:relative;display:flex;align-items:center;justify-content:center;height:100%;overflow:hidden;box-shadow:inset 0 2px 0 #ffffff99}
.bar::before{content:"";position:absolute;inset:0;background:repeating-linear-gradient(135deg,#ffffff14 0 10px,transparent 10px 20px)}
.bar::after{content:"";position:absolute;inset:0;background:linear-gradient(105deg,transparent 35%,#ffffff8c 50%,transparent 65%);transform:translateX(-130%)}
.bar span{position:relative;font:700 54px/1 Georgia,"Times New Roman",serif;text-shadow:0 2px 0 #ffffff66}
.p1 .bw{height:170px}.p2 .bw{height:130px}.p3 .bw{height:100px}
.p1 .bar{background:linear-gradient(180deg,#fde68a,#f5c451 38%,#b7791f);color:#7a4a05}
.p2 .bar{background:linear-gradient(180deg,#f8fafc,#cbd5e1 38%,#64748b);color:#334155}
.p3 .bar{background:linear-gradient(180deg,#ffd9b8,#e39a5b 38%,#9a4a14);color:#5a2a08}
.p1 .nm{font-size:16px}
/* animasi: hanya transform & opacity, total sekitar 1,6 detik */
.podium:not(.go) .info{opacity:0}
.podium:not(.go) .bar{transform:translateY(101%)}
.go .bar{animation:rise .65s cubic-bezier(.22,1,.36,1) var(--d) both}
.go .info{animation:up .45s ease-out calc(var(--d) + .3s) both}
.go .p1 .medal{animation:pop .5s cubic-bezier(.34,1.56,.64,1) calc(var(--d) + .35s) both}
.go .p1 .bar::after{animation:shine .9s ease-out calc(var(--d) + .6s) both}
@keyframes rise{from{transform:translateY(101%)}to{transform:none}}
@keyframes up{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
@keyframes pop{from{transform:scale(.3)}to{transform:none}}
@keyframes shine{to{transform:translateX(130%)}}
.cf-foot{margin:0 -16px;padding:12px;background:#00000026;border-top:1px solid #ffffff1a}
.card.champ button.replay{display:inline-flex;align-items:center;gap:8px;background:#ffffff1f;border:1px solid #ffffff4d;color:#fff;font-weight:600;padding:8px 18px;border-radius:99px;transition:background .2s}
.card.champ button.replay:hover{background:#ffffff38}
.cfp{position:absolute;left:0;top:0;pointer-events:none;will-change:transform,opacity}
@media(max-width:480px){.card.champ h3{font-size:22px}.nm{font-size:13px}.p1 .nm{font-size:14px}.medal{width:38px;height:46px}.p1 .bw{height:140px}.p2 .bw{height:108px}.p3 .bw{height:84px}.bar span{font-size:42px}.podium{gap:5px}}
@media(prefers-reduced-motion:reduce){.go .bar,.go .info,.go .medal,.go .bar::after{animation:none!important}.podium:not(.go) .info{opacity:1}.podium:not(.go) .bar{transform:none}}
</style>
<h2>Hasil: <?=e($qz['judul'])?></h2>
<?php if($rows){$top=min(3,count($rows));?>
<div class="card champ" id="champ">
<div class="ch-eye"><i></i>Papan Peringkat<i></i></div>
<h3>Juara Quiz</h3>
<p class="ch-sub">Skor tertinggi dengan waktu pengerjaan tercepat</p>
<div class="podium" id="podium"><?php foreach([1,0,2] as $k)if(isset($rows[$k])){$r=$rows[$k];$n=$k+1;?>
<div class="pd p<?=$n?>" style="--d:<?=($top-$n)*0.3?>s">
<div class="info"><?=medal($n)?><div class="nm"><?=e($r['nama'])?></div><div class="nim"><?=e($r['nim'])?></div>
<div class="chips"><span>Skor <b><?=$r['skor']?></b></span><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?=dur($r['durasi_detik'])?></span></div></div>
<div class="bw"><div class="bar"><span><?=$n?></span></div></div>
</div><?php }?></div>
<div class="cf-foot"><button type="button" id="play" class="replay"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>Putar Ulang Animasi</button></div>
</div>
<script>
(function(){
const box=document.getElementById('champ'),pod=document.getElementById('podium'),btn=document.getElementById('play');
const still=matchMedia('(prefers-reduced-motion:reduce)').matches;let tm;
function burst(){
 const w=box.querySelector('.p1 .bw');if(!w||still)return;
 const b=box.getBoundingClientRect(),r=w.getBoundingClientRect(),ox=r.left-b.left+r.width/2,oy=r.top-b.top+10;
 const col=['#f5c451','#fde68a','#5eead4','#ffffff','#22d3ee','#99f6e4'];
 for(let i=0;i<42;i++){
  const e=document.createElement('i'),c=col[i%col.length],t=i%3;e.className='cfp';
  if(t==0){e.style.cssText+='width:14px;height:6px;border-radius:6px;background:linear-gradient(90deg,'+c+' 50%,#fff 50%)'}
  else if(t==1){e.style.cssText+='width:10px;height:10px;background:'+c+';clip-path:polygon(35% 0,65% 0,65% 35%,100% 35%,100% 65%,65% 65%,65% 100%,35% 100%,35% 65%,0 65%,0 35%,35% 35%)'}
  else{e.style.cssText+='width:6px;height:6px;border-radius:50%;background:'+c}
  box.appendChild(e);
  const a=-Math.PI/2+(Math.random()-.5)*Math.PI*1.2,d=120+Math.random()*180,x=Math.cos(a)*d,y=Math.sin(a)*d,rot=(Math.random()-.5)*720;
  e.animate([{transform:'translate('+ox+'px,'+oy+'px) scale(.5)',opacity:1},
   {transform:'translate('+(ox+x)+'px,'+(oy+y)+'px) rotate('+rot/2+'deg) scale(1)',opacity:1,offset:.4},
   {transform:'translate('+(ox+x*1.25)+'px,'+(oy+y+b.height*.8)+'px) rotate('+rot+'deg)',opacity:0}],
   {duration:1400+Math.random()*600,delay:Math.random()*100,easing:'cubic-bezier(.25,.6,.4,1)'}).onfinish=()=>e.remove();
 }}
function play(){clearTimeout(tm);box.querySelectorAll('.cfp').forEach(e=>e.remove());pod.classList.remove('go');void pod.offsetWidth;pod.classList.add('go');
 const d=parseFloat(getComputedStyle(box.querySelector('.p1')).getPropertyValue('--d'))||0;tm=setTimeout(burst,(d+.7)*1000);}
btn.onclick=play;play();
})();
</script><?php }?>
<a class="btn" href="?id=<?=$id?>&csv=1">Unduh CSV</a><br><br>
<div class="card"><form method="post"><?=csrf()?><table><tr><th><input type="checkbox" style="width:auto" onclick="document.querySelectorAll('.pl').forEach(c=>c.checked=this.checked)"></th><th>#</th><th>NIM</th><th>Nama</th><th>Benar</th><th>Skor</th><th>Durasi</th><th></th></tr>
<?php foreach($rows as $i=>$r)echo '<tr><td><input type="checkbox" class="pl" name="pilih[]" value="'.$r['id'].'" style="width:auto"></td><td>'.($i+1).'</td><td>'.e($r['nim']).'</td><td>'.e($r['nama']).'</td><td>'.$r['benar'].'/'.$r['total'].'</td><td><b>'.$r['skor'].'</b></td><td>'.dur($r['durasi_detik']).'</td><td><button name="hapus1" value="'.$r['id'].'" class="red" onclick="return confirm(\'Hapus hasil ini? Mahasiswa bisa mengerjakan ulang.\')">Hapus</button></td></tr>';
if(!$rows)echo '<tr><td colspan="8">Belum ada yang mengerjakan.</td></tr>';?></table>
<?php if($rows){?><br><button name="hapus_pilih" class="red" onclick="return confirm('Hapus hasil yang dicentang?')">Hapus Terpilih</button> <button name="hapus_semua" class="red" onclick="return confirm('Hapus SEMUA hasil quiz ini?')">Hapus Semua</button><?php }?></form>
<small>Peringkat: skor tertinggi, lalu waktu pengerjaan tercepat.</small></div>
<?php foot();
