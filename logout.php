<?php require 'config.php';
$u=user();
if($u&&$u['role']==='mhs'){try{$db->prepare('UPDATE attempts a SET a.terakhir=NULL WHERE a.user_id=? AND NOT EXISTS(SELECT 1 FROM results r WHERE r.user_id=a.user_id AND r.quiz_id=a.quiz_id)')->execute([$u['id']]);}catch(Exception $e){}}
session_destroy();header('Location: index.php');
