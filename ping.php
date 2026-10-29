<?php require 'config.php';
$u=user();$id=(int)($_GET['id']??0);
if($u&&$u['role']==='mhs'&&$id){try{$db->exec("SET time_zone='+07:00'");
 $set=isset($_GET['keluar'])?'NULL':'NOW()'; // keluar=1 -> tandai langsung sebagai keluar
 $db->prepare("UPDATE attempts SET terakhir=$set WHERE user_id=? AND quiz_id=? AND NOT EXISTS(SELECT 1 FROM results r WHERE r.user_id=? AND r.quiz_id=?)")->execute([$u['id'],$id,$u['id'],$id]);}catch(Exception $e){}}
http_response_code(204);
