<?php
function ini2($n){return function_exists('mb_substr')?mb_strtoupper(mb_substr($n,0,1)):strtoupper(substr($n,0,1));}
function avatar($id,$nama,$c='ava'){$f=__DIR__.'/uploads/avatar_'.(int)$id.'.jpg';
 if(is_file($f))return '<img class="'.$c.'" src="uploads/avatar_'.(int)$id.'.jpg?v='.filemtime($f).'" alt="">';
 return '<span class="'.$c.'">'.e(ini2($nama)).'</span>';}
function hd($t){$u=user();$v=@filemtime(__DIR__.'/style.css');$home=$u?($u['role']=='admin'?'admin.php':'dashboard.php'):'index.php';
 echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($t).' - LMS Quiz STIKES KPB</title><link rel="icon" href="logo-small.png"><link rel="stylesheet" href="style.css?v='.$v.'"></head><body>';
 echo '<header><a class="brand" href="'.$home.'"><img src="logo-small.png" alt=""><span>LMS Quiz<small>STIKES Karya Putra Bangsa</small></span></a>';
 if($u)echo '<details class="um"><summary>'.avatar($u['id'],$u['nama']).'<span class="un">'.e($u['nama']).'</span><i>▾</i></summary><div class="mn"><div class="who">'.e($u['nama']).'<small>'.($u['role']=='admin'?'Dosen / Admin':'Mahasiswa').'</small></div><a href="profil.php">👤 Profil Saya</a><a href="'.$home.'">🏠 Beranda</a><a href="logout.php">🚪 Keluar</a></div></details>';
 $pg=basename($_SERVER['SCRIPT_NAME']);
 echo '</header><main>';
 if(in_array($pg,['admin_pantau.php','admin_soal.php','admin_hasil.php','admin_siswa.php']))echo '<a class="btn-kembali" href="admin.php">← Kembali ke Daftar Quiz</a>';
 elseif($pg==='riwayat.php')echo '<a class="btn-kembali" href="dashboard.php">← Kembali ke Daftar Quiz</a>';
 elseif($pg==='profil.php'&&$u)echo '<a class="btn-kembali" href="'.$home.'">← Kembali ke Beranda</a>';
 echo '<script>document.addEventListener("click",e=>{const d=document.querySelector(".um");if(d&&!d.contains(e.target))d.removeAttribute("open")})</script>';}
