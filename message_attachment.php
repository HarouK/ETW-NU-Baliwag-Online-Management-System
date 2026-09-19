<?php
require 'config.php'; require_login();
$uid=(int)$_SESSION['user']['id'];
$id=(int)($_GET['id']??0);
$q=$pdo->prepare("SELECT pm.*,u1.name sender_name,u2.name receiver_name FROM private_messages pm JOIN users u1 ON u1.id=pm.sender_id JOIN users u2 ON u2.id=pm.receiver_id WHERE pm.id=? AND (pm.sender_id=? OR pm.receiver_id=?) LIMIT 1");
$q->execute([$id,$uid,$uid]); $m=$q->fetch();
if(!$m || ($m['attachment_type']??'')!=='file' || empty($m['attachment_path'])){ http_response_code(404); exit('Attachment not found.'); }
$base=realpath(__DIR__.'/uploads/messages'); $file=realpath(__DIR__.'/'.$m['attachment_path']);
if(!$base || !$file || !str_starts_with($file,$base.DIRECTORY_SEPARATOR) || !is_file($file)){ http_response_code(404); exit('Attachment not found.'); }
$name=basename((string)$m['attachment_name']); $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
$types=['pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xls'=>'application/vnd.ms-excel','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','ppt'=>'application/vnd.ms-powerpoint','pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation','txt'=>'text/plain','zip'=>'application/zip'];
header('Content-Type: '.($types[$ext]??'application/octet-stream')); header('Content-Length: '.filesize($file)); $safeName=str_replace(['"','\\'],['_','_'],$name); $isImage=in_array($ext,['png','jpg','jpeg','gif','webp'],true); $inline=isset($_GET['inline']) && $_GET['inline']==='1' && $isImage; header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.$safeName.'"'); header('X-Content-Type-Options: nosniff'); readfile($file); exit;
