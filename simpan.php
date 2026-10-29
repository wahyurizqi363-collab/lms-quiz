<?php require 'config.php';
header('Content-Type: application/json');
$u=user();$id=(int)($_POST['id']??0);
if(!$u||$u['role']!=='mhs'||!$id||!hash_equals($_SESSION['t']??'',(string)($_POST['t']??''))){http_response_code(403);echo '{"ok":0,"alasan":"sesi"}';exit;}
try{
 $db->exec("SET time_zone='+07:00'");
 $q=$db->prepare('SELECT durasi FROM quizzes WHERE id=? AND aktif=1');$q->execute([$id]);$dur=$q->fetchColumn();
 if(!$dur){echo '{"ok":0,"alasan":"quiz nonaktif"}';exit;}
 $a=$db->prepare('SELECT TIMESTAMPDIFF(SECOND,mulai,NOW()) FROM attempts a WHERE user_id=? AND quiz_id=? AND NOT EXISTS(SELECT 1 FROM results r WHERE r.user_id=a.user_id AND r.quiz_id=a.quiz_id)');
 $a->execute([$u['id'],$id]);$lama=$a->fetchColumn();
 if($lama===false||$lama===null){echo '{"ok":0,"alasan":"sudah selesai"}';exit;}
 if((int)$lama>$dur*60+15){echo '{"ok":0,"alasan":"waktu habis"}';exit;}
 $v=$db->prepare('SELECT id FROM questions WHERE quiz_id=?');$v->execute([$id]);$valid=array_flip($v->fetchAll(PDO::FETCH_COLUMN));
 $j=[];foreach((array)($_POST['j']??[]) as $k=>$x){$k=(int)$k;if(isset($valid[$k])&&is_string($x)&&preg_match('/^[A-E]$/',$x))$j[$k]=$x;}
 $db->prepare('INSERT INTO draft(user_id,quiz_id,jawab,diubah) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE jawab=VALUES(jawab),diubah=NOW()')->execute([$u['id'],$id,json_encode($j)]);
 echo '{"ok":1}';
}catch(Exception $e){echo '{"ok":0,"alasan":"server"}';}
