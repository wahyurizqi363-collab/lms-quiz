<?php require 'config.php';require 'layout.php';need('admin');$id=(int)($_GET['id']??0);
$q=$db->prepare('SELECT * FROM quizzes WHERE id=?');$q->execute([$id]);$qz=$q->fetch();if(!$qz){header('Location: admin.php');exit;}
$L=['A'=>'a','B'=>'b','C'=>'c','D'=>'d','E'=>'e'];
function baca_xlsx($path){
 $z=new ZipArchive;if($z->open($path)!==true)return null;$ss=[];
 if(($x=$z->getFromName('xl/sharedStrings.xml'))!==false){$xml=simplexml_load_string($x);
  foreach($xml->si as $si){$t='';if(isset($si->t))$t=(string)$si->t;else foreach($si->r as $r)$t.=(string)$r->t;$ss[]=$t;}}
 $sx=$z->getFromName('xl/worksheets/sheet1.xml');$z->close();if($sx===false)return null;
 $xml=simplexml_load_string($sx);$rows=[];
 foreach($xml->sheetData->row as $row){$r=[];
  foreach($row->c as $c){preg_match('/^([A-Z]+)/',(string)$c['r'],$m);$col=0;foreach(str_split($m[1]) as $ch)$col=$col*26+ord($ch)-64;$col--;
   $t=(string)$c['t'];if($t==='s')$v=$ss[(int)$c->v]??'';elseif($t==='inlineStr')$v=(string)$c->is->t;else $v=(string)$c->v;$r[$col]=trim($v);}
  $rows[]=$r;}
 return $rows;}
function baca_csv($path){
 $h=fopen($path,'r');$first=fgets($h);rewind($h);$d=substr_count($first,';')>substr_count($first,',')?';':',';$rows=[];
 while(($r=fgetcsv($h,0,$d))!==false)$rows[]=array_map('trim',$r);fclose($h);return $rows;}
$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){chk();
 $ins=$db->prepare('INSERT INTO questions(quiz_id,soal,a,b,c,d,e,kunci) VALUES(?,?,?,?,?,?,?,?)');
 if(isset($_POST['tambah'])||isset($_POST['simpan'])){$o=[];foreach($L as $c)$o[]=trim($_POST[$c]??'');$k=$_POST['kunci']??'';$soal=trim($_POST['soal']??'');
  if($soal!==''&&$o[0]!==''&&$o[1]!==''&&isset($L[$k])&&$o[ord($k)-65]!==''){if(isset($_POST['simpan'])){$db->prepare('UPDATE questions SET soal=?,a=?,b=?,c=?,d=?,e=?,kunci=? WHERE id=? AND quiz_id=?')->execute(array_merge([$soal],$o,[$k,(int)$_POST['sid'],$id]));$msg='Soal diperbarui.';}else{$ins->execute(array_merge([$id,$soal],$o,[$k]));$msg='Soal ditambahkan.';}}
  else $err='Gagal: soal, pilihan A & B wajib diisi, dan kunci harus menunjuk pilihan yang terisi.';}
 if(isset($_POST['import'])){$f=$_FILES['berkas']??null;
  if(!$f||$f['error']!==0)$err='Pilih file terlebih dahulu.';
  else{$ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
   $rows=$ext==='xlsx'?baca_xlsx($f['tmp_name']):($ext==='csv'?baca_csv($f['tmp_name']):null);
   if($rows===null)$err='File tidak terbaca. Gunakan .xlsx (atau .csv).';
   else{$n=0;$skip=0;
    foreach($rows as $r){$v=[];for($i=0;$i<7;$i++)$v[$i]=trim($r[$i]??'');
     if(strtoupper($v[6])==='KUNCI'||implode('',$v)==='')continue; // lewati header & baris kosong
     $k=strtoupper($v[6]);
     if($v[0]!==''&&$v[1]!==''&&$v[2]!==''&&isset($L[$k])&&$v[ord($k)-64]!==''){$ins->execute([$id,$v[0],$v[1],$v[2],$v[3],$v[4],$v[5],$k]);$n++;}else $skip++;}
    $msg="$n soal berhasil diimpor.".($skip?" $skip baris dilewati (data tidak lengkap / kunci salah).":'');}}}
 if(isset($_POST['del']))$db->prepare('DELETE FROM questions WHERE id=? AND quiz_id=?')->execute([(int)$_POST['qid'],$id]);}
$ed=null;if(isset($_GET['edit'])&&!($msg&&isset($_POST['simpan']))){$s=$db->prepare('SELECT * FROM questions WHERE id=? AND quiz_id=?');$s->execute([(int)$_GET['edit'],$id]);$ed=$s->fetch();}
$s=$db->prepare('SELECT * FROM questions WHERE quiz_id=? ORDER BY id');$s->execute([$id]);$soal=$s->fetchAll();
hd('Soal');echo '<h2>'.e($qz['judul']).'</h2>';if($msg)echo '<div class="ok">'.e($msg).'</div>';if($err)echo '<div class="err">'.e($err).'</div>';?>
<div class="card"><h3>Import dari Excel</h3>
<small>Kolom: <b>Soal | A | B | C | D | E | Kunci</b> (baris pertama = judul kolom). D &amp; E boleh kosong. Kunci diisi huruf A–E.</small><br><br>
<a class="btn gray" href="template_soal.xlsx">Unduh Template Excel</a><br><br>
<form method="post" enctype="multipart/form-data"><?=csrf()?><input type="file" name="berkas" accept=".xlsx,.csv" required><button name="import">Import</button></form></div>
<div class="card" id="fm"><h3><?=$ed?'✏️ Edit Soal':'Tambah Soal Manual'?></h3><form method="post"><?=csrf()?><?php if($ed)echo '<input type="hidden" name="sid" value="'.$ed['id'].'">';?>
<textarea name="soal" rows="3" placeholder="Pertanyaan" required><?=e($ed['soal']??'')?></textarea>
<input name="a" placeholder="Pilihan A" value="<?=e($ed['a']??'')?>" required><input name="b" placeholder="Pilihan B" value="<?=e($ed['b']??'')?>" required><input name="c" placeholder="Pilihan C (opsional)" value="<?=e($ed['c']??'')?>"><input name="d" placeholder="Pilihan D (opsional)" value="<?=e($ed['d']??'')?>"><input name="e" placeholder="Pilihan E (opsional)" value="<?=e($ed['e']??'')?>">
<select name="kunci"><?php foreach($L as $K=>$c)echo '<option'.(($ed['kunci']??'')===$K?' selected':'').'>'.$K.'</option>';?></select><small>Kunci jawaban</small><br><br><button name="<?=$ed?'simpan':'tambah'?>"><?=$ed?'Simpan Perubahan':'Simpan'?></button><?php if($ed)echo ' <a class="btn gray" href="admin_soal.php?id='.$id.'">Batal</a>';?></form></div>
<h3><?=count($soal)?> Soal</h3>
<?php foreach($soal as $i=>$x){echo '<div class="card"><b>'.($i+1).'. '.e($x['soal']).'</b><br>';
 foreach($L as $K=>$c)if(($x[$c]??'')!=='')echo $K.'. '.e($x[$c]).'<br>';
 echo '<small>Kunci: <b>'.$x['kunci'].'</b></small> <a class="btn gray" href="?id='.$id.'&edit='.$x['id'].'#fm">✏️ Edit</a> <form class="inline" method="post">'.csrf().'<input type="hidden" name="qid" value="'.$x['id'].'"><button name="del" class="red" onclick="return confirm(\'Hapus soal?\')">Hapus</button></form></div>';}
foot();
