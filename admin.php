<?php require 'config.php';require 'layout.php';need('admin');
if($_SERVER['REQUEST_METHOD']==='POST'){chk();$id=(int)($_POST['id']??0);
 if(isset($_POST['buat'])&&trim($_POST['judul']))$db->prepare('INSERT INTO quizzes(judul,durasi,pengampu) VALUES(?,?,?)')->execute([trim($_POST['judul']),max(1,(int)$_POST['durasi']),trim($_POST['pengampu']??'')?:user()['nama']]);
 if(isset($_POST['edit'])&&trim($_POST['judul']))$db->prepare('UPDATE quizzes SET judul=?,durasi=?,pengampu=? WHERE id=?')->execute([trim($_POST['judul']),max(1,(int)$_POST['durasi']),trim($_POST['pengampu']??'')?:null,$id]);
 if(isset($_POST['toggle']))$db->prepare('UPDATE quizzes SET aktif=1-aktif WHERE id=?')->execute([$id]);
 if(isset($_POST['bahas']))$db->prepare('UPDATE quizzes SET pembahasan=1-pembahasan WHERE id=?')->execute([$id]);
 if(isset($_POST['hapus'])){foreach(['questions','results'] as $t)$db->prepare("DELETE FROM $t WHERE quiz_id=?")->execute([$id]);try{$db->prepare('DELETE FROM attempts WHERE quiz_id=?')->execute([$id]);$db->prepare('DELETE FROM draft WHERE quiz_id=?')->execute([$id]);}catch(Exception $e){}$db->prepare('DELETE FROM quizzes WHERE id=?')->execute([$id]);}
 header('Location: admin.php');exit;}
finalisasi($db);
try{$db->exec("SET time_zone='+07:00'");}catch(Exception $e){}
$base="SELECT q.*,(SELECT COUNT(*) FROM questions WHERE quiz_id=q.id) n,(SELECT COUNT(DISTINCT r2.user_id) FROM results r2 JOIN users u2 ON u2.id=r2.user_id AND u2.role='mhs' WHERE r2.quiz_id=q.id) h";
try{$rows=$db->query($base.",(SELECT COUNT(DISTINCT a.user_id) FROM attempts a WHERE a.quiz_id=q.id AND NOT EXISTS(SELECT 1 FROM results r WHERE r.user_id=a.user_id AND r.quiz_id=q.id) AND TIMESTAMPDIFF(SECOND,a.terakhir,NOW())<=60 AND TIMESTAMPDIFF(SECOND,a.mulai,NOW())<=q.durasi*60+120) k FROM quizzes q ORDER BY id DESC")->fetchAll();}
catch(Exception $e){$rows=$db->query($base.",0 k FROM quizzes q ORDER BY id DESC")->fetchAll();}
$tm=(int)$db->query("SELECT COUNT(*) FROM users WHERE role='mhs'")->fetchColumn();$aktif=count(array_filter($rows,fn($r)=>$r['aktif']));
hd('Dashboard Dosen');?>
<style>
.tiles{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
.tile{background:#fffffff2;border-radius:12px;padding:14px 8px;text-align:center;box-shadow:0 2px 8px #0f766e22;border-top:3px solid var(--p)}
.tile b{display:block;font-size:28px;color:var(--p);line-height:1.1}.tile span{font-size:13px;color:#5b7b79}
.row{display:flex;gap:8px;flex-wrap:wrap}.row input{margin:0;flex:1 1 200px}.row input[type=number]{flex:0 0 110px}
.qc{border-top:0;border-left:6px solid #94a3b8;padding:16px 18px}.qc.on{border-left-color:#10b981}
.qh{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}.qh h3{margin:0;font-size:18px}
.pill{padding:3px 11px;border-radius:99px;font-size:12px;font-weight:700;background:#e2e8f0;color:#475569}.pill.on{background:#d1fae5;color:#065f46}
.meta{display:flex;gap:16px;flex-wrap:wrap;margin:10px 0 14px;font-size:13px;color:#5b7b79}
.acts{display:flex;gap:8px;flex-wrap:wrap}.acts form{display:contents}
.btn.out,button.out{background:#fff;color:var(--p);border:1.5px solid var(--p)}.btn.out:hover,button.out:hover{background:#ccfbf1}
button.dg{background:#fff;color:#dc2626;border:1.5px solid #fca5a5}button.dg:hover{background:#fee2e2}
.ed summary{list-style:none;cursor:pointer}.ed summary::-webkit-details-marker{display:none}.ed[open]{flex-basis:100%}.ed form.row{display:flex}
.live{color:#92400e;font-weight:600}.live::before{content:"";display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;margin-right:6px;animation:pl 1.2s infinite}@keyframes pl{50%{opacity:.25}}
.empty{text-align:center;color:#5b7b79;padding:26px}
</style>
<div class="tiles"><div class="tile"><b><?=count($rows)?></b><span>Total Quiz</span></div><div class="tile"><b><?=$aktif?></b><span>Quiz Aktif</span></div><div class="tile"><b><?=$tm?></b><span>Mahasiswa</span></div></div>
<div class="acts" style="margin-bottom:14px"><a class="btn out" href="admin_siswa.php">👥 Kelola Mahasiswa</a></div>
<div class="card"><h3 style="margin-top:0">➕ Buat Quiz Baru</h3><form method="post" class="row"><?=csrf()?>
<input name="judul" placeholder="Judul quiz, mis. Farmakologi Dasar" required><input type="number" name="durasi" value="30" min="1" title="Durasi (menit)"><input name="pengampu" placeholder="Dosen pengampu" value="<?=e(user()['nama'])?>"><button name="buat">Buat</button></form>
<small>Angka di samping judul = durasi pengerjaan (menit).</small></div>
<h3>Daftar Quiz</h3>
<?php if(!$rows)echo '<div class="card empty">Belum ada quiz. Buat quiz pertama Anda di atas.</div>';
foreach($rows as $r){$on=$r['aktif'];echo '<div class="card qc'.($on?' on':'').'" data-qid="'.$r['id'].'"><div class="qh"><h3>'.e($r['judul']).'</h3><span class="pill'.($on?' on':'').'">'.($on?'● Aktif':'○ Nonaktif').'</span></div>
<div class="meta"><span>📝 '.$r['n'].' soal</span><span>⏱ '.$r['durasi'].' menit</span>'.(!empty($r['pengampu'])?'<span>👨‍🏫 '.e($r['pengampu']).'</span>':'').'<span>✅ '.$r['h'].' sudah mengerjakan</span>'.((!$on||!empty($r['pembahasan']))?'<span>📖 Pembahasan terbuka</span>':'').($r['k']>0?'<span class="live">'.$r['k'].' sedang mengerjakan</span>':'').'</div>
<div class="acts"><a class="btn" href="admin_pantau.php?id='.$r['id'].'">📡 Pantau</a><a class="btn out" href="admin_soal.php?id='.$r['id'].'">📝 Kelola Soal</a><a class="btn out" href="admin_hasil.php?id='.$r['id'].'">🏆 Hasil</a><details class="ed"><summary class="btn out">✏️ Edit</summary><form method="post" class="row" style="margin-top:10px">'.csrf().'<input type="hidden" name="id" value="'.$r['id'].'"><input name="judul" value="'.e($r['judul']).'" required><input type="number" name="durasi" value="'.$r['durasi'].'" min="1" title="Durasi (menit)"><input name="pengampu" value="'.e($r['pengampu']??'').'" placeholder="Dosen pengampu"><button name="edit">Simpan</button></form></details>
<form method="post">'.csrf().'<input type="hidden" name="id" value="'.$r['id'].'">'.($on?'<button name="bahas" class="out">'.(!empty($r['pembahasan'])?'📖 Tutup Pembahasan':'📖 Buka Pembahasan').'</button>':'').'<button name="toggle" class="'.($on?'gray':'').'">'.($on?'⏸ Nonaktifkan':'▶ Aktifkan').'</button><button name="hapus" class="dg" onclick="return confirm(\'Hapus quiz beserta soal & hasil?\')">🗑 Hapus</button></form></div></div>';}
?><script>
setInterval(async()=>{if(document.hidden)return;try{const h=await(await fetch(location.href,{cache:'no-store',credentials:'same-origin'})).text();const d=new DOMParser().parseFromString(h,'text/html');
d.querySelectorAll('.qc[data-qid]').forEach(n=>{const m=document.querySelector('.qc[data-qid="'+n.dataset.qid+'"] .meta'),s=n.querySelector('.meta');if(m&&s&&m.innerHTML!==s.innerHTML)m.innerHTML=s.innerHTML;});}catch(e){}},5000);
</script><?php foot();
