<?php
require 'config.php'; require_login();
$title='Messages';
$uid=(int)$_SESSION['user']['id'];
$error=''; $success='';

function ensure_messaging_schema(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS private_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    body TEXT NULL,
    attachment_type VARCHAR(20) NULL,
    attachment_name VARCHAR(255) NULL,
    attachment_path VARCHAR(500) NULL,
    attachment_url TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pm_sender_receiver (sender_id,receiver_id,created_at),
    INDEX idx_pm_receiver_read (receiver_id,is_read,created_at)
  ) ENGINE=InnoDB");
  // Upgrade older installations created by the original messaging feature.
  try { $pdo->exec("ALTER TABLE private_messages MODIFY body TEXT NULL"); } catch(Throwable $e) {}
  try { $pdo->exec("ALTER TABLE private_messages ADD COLUMN attachment_type VARCHAR(20) NULL AFTER body"); } catch(Throwable $e) {}
  try { $pdo->exec("ALTER TABLE private_messages ADD COLUMN attachment_name VARCHAR(255) NULL AFTER attachment_type"); } catch(Throwable $e) {}
  try { $pdo->exec("ALTER TABLE private_messages ADD COLUMN attachment_path VARCHAR(500) NULL AFTER attachment_name"); } catch(Throwable $e) {}
  try { $pdo->exec("ALTER TABLE private_messages ADD COLUMN attachment_url TEXT NULL AFTER attachment_path"); } catch(Throwable $e) {}
}
ensure_messaging_schema($pdo);

$partnerId=(int)($_GET['user']??0);
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??'';
  try {
    if($action==='send_message'){
      $receiver=(int)($_POST['receiver_id']??0); $body=trim($_POST['body']??'');
      if(!$receiver || $receiver===$uid) throw new Exception('Please select another user.');
      $q=$pdo->prepare("SELECT id,name,role FROM users WHERE id=? AND id<>? AND COALESCE(account_status,'Active')='Active' LIMIT 1");$q->execute([$receiver,$uid]);$person=$q->fetch();
      if(!$person) throw new Exception('That user is not available for messaging.');

      $attachmentType=null; $attachmentName=null; $attachmentPath=null; $attachmentUrl=null;
      $link=trim($_POST['attachment_link']??'');
      if($link!==''){
        if(!filter_var($link,FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i',$link)) throw new Exception('Please enter a valid HTTP or HTTPS link.');
        $attachmentType='link'; $attachmentUrl=$link; $attachmentName=trim($_POST['attachment_label']??'') ?: parse_url($link,PHP_URL_HOST);
      } elseif(isset($_FILES['attachment_file']) && ($_FILES['attachment_file']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
        $file=$_FILES['attachment_file'];
        if($file['error']!==UPLOAD_ERR_OK) throw new Exception('The attachment could not be uploaded.');
        if((int)$file['size']>10*1024*1024) throw new Exception('Attachments must be 10 MB or smaller.');
        $allowed=[
          'pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp',
          'doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xls'=>'application/vnd.ms-excel',
          'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','ppt'=>'application/vnd.ms-powerpoint','pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          'txt'=>'text/plain','zip'=>'application/zip'
        ];
        $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if(!isset($allowed[$ext])) throw new Exception('That file type is not supported.');
        $finfo=function_exists('finfo_open')?finfo_open(FILEINFO_MIME_TYPE):false;
        $mime=$finfo?finfo_file($finfo,$file['tmp_name']):($file['type']??''); if($finfo)finfo_close($finfo);
        if($mime && !in_array($mime,array_values($allowed),true) && !($ext==='txt' && str_starts_with($mime,'text/'))) throw new Exception('The uploaded file type is not allowed.');
        $dir=__DIR__.'/uploads/messages'; if(!is_dir($dir)) @mkdir($dir,0775,true); if(!is_dir($dir)) throw new Exception('The attachment folder could not be created.');
        $stored=bin2hex(random_bytes(12)).'.'.$ext; $target=$dir.'/'.$stored;
        if(!move_uploaded_file($file['tmp_name'],$target)) throw new Exception('The attachment could not be saved.');
        $attachmentType='file'; $attachmentName=basename($file['name']); $attachmentPath='uploads/messages/'.$stored;
      }
      if($body==='' && !$attachmentType) throw new Exception('Write a message or add an attachment.');
      $q=$pdo->prepare('INSERT INTO private_messages(sender_id,receiver_id,body,attachment_type,attachment_name,attachment_path,attachment_url) VALUES(?,?,?,?,?,?,?)');
      $q->execute([$uid,$receiver,$body!==''?$body:null,$attachmentType,$attachmentName,$attachmentPath,$attachmentUrl]);
      notify_user($pdo,$receiver,'New private message',$_SESSION['user']['name'].' sent you a private message.','Message');
      $partnerId=$receiver; $success='Message sent.';
    }
  } catch(Throwable $ex){$error=$ex->getMessage();}
}

// Searchable recipient list. Students and faculty can message one another; admin accounts are also available.
$search=trim($_GET['q']??'');
$params=[$uid]; $where="u.id<>? AND COALESCE(u.account_status,'Active')='Active'";
if($search!==''){ $where.=" AND (u.name LIKE ? OR u.email LIKE ?)"; $like='%'.$search.'%'; $params[]=$like; $params[]=$like; }
$uq=$pdo->prepare("SELECT u.id,u.name,u.email,u.role,u.avatar_path FROM users u WHERE $where ORDER BY u.role,u.name LIMIT 50");$uq->execute($params);$users=$uq->fetchAll();

// Conversation list: latest message exchanged with each person.
$cq=$pdo->prepare("SELECT u.id,u.name,u.email,u.role,u.avatar_path,
  (SELECT pm.body FROM private_messages pm WHERE (pm.sender_id=? AND pm.receiver_id=u.id) OR (pm.sender_id=u.id AND pm.receiver_id=?) ORDER BY pm.created_at DESC,pm.id DESC LIMIT 1) last_body,
  (SELECT pm.created_at FROM private_messages pm WHERE (pm.sender_id=? AND pm.receiver_id=u.id) OR (pm.sender_id=u.id AND pm.receiver_id=?) ORDER BY pm.created_at DESC,pm.id DESC LIMIT 1) last_at,
  (SELECT COUNT(*) FROM private_messages pm WHERE pm.sender_id=u.id AND pm.receiver_id=? AND pm.is_read=0) unread
  FROM users u WHERE u.id<>? AND EXISTS (SELECT 1 FROM private_messages x WHERE (x.sender_id=? AND x.receiver_id=u.id) OR (x.sender_id=u.id AND x.receiver_id=?))
  ORDER BY last_at DESC LIMIT 50");
$cq->execute([$uid,$uid,$uid,$uid,$uid,$uid,$uid,$uid]);$conversations=$cq->fetchAll();

$partner=null; $messages=[];
if($partnerId){
  $pq=$pdo->prepare("SELECT id,name,email,role,avatar_path FROM users WHERE id=? AND id<>? AND COALESCE(account_status,'Active')='Active' LIMIT 1");$pq->execute([$partnerId,$uid]);$partner=$pq->fetch();
  if(!$partner){$error='User not found.';$partnerId=0;}
}
if($partner){
  $mq=$pdo->prepare("SELECT pm.*,u.name sender_name,u.role sender_role,u.avatar_path sender_avatar FROM private_messages pm JOIN users u ON u.id=pm.sender_id WHERE ((pm.sender_id=? AND pm.receiver_id=?) OR (pm.sender_id=? AND pm.receiver_id=?)) ORDER BY pm.created_at ASC,pm.id ASC");$mq->execute([$uid,$partnerId,$partnerId,$uid]);$messages=$mq->fetchAll();
  $rq=$pdo->prepare('UPDATE private_messages SET is_read=1 WHERE sender_id=? AND receiver_id=?');$rq->execute([$partnerId,$uid]);
}
require 'header.php';
?>
<section class="section messaging-page">
  <div class="section-intro"><div><span class="eyebrow">PRIVATE COMMUNICATION</span><h2>Messages</h2><p class="muted">Send private messages to students, professors, and other authorized users.</p></div></div>
  <?php if($success):?><div class="success"><?=e($success)?></div><?php endif;?><?php if($error):?><div class="auth-alert"><?=e($error)?></div><?php endif;?>
  <div class="messaging-layout">
    <aside class="message-sidebar">
      <div class="card-head"><h3>Conversations</h3><a class="btn btn-small" href="messaging.php">New</a></div>
      <form class="message-search" method="get"><input name="q" value="<?=e($search)?>" placeholder="Find a user"><button class="btn btn-small">Search</button></form>
      <div class="conversation-list">
        <?php foreach($conversations as $c):?><a class="conversation-item <?=($partnerId===(int)$c['id'])?'active':''?>" href="messaging.php?user=<?=e($c['id'])?>"><span class="avatar avatar-small"><?=!empty($c['avatar_path'])?'<img class="avatar" src="'.e($c['avatar_path']).'" alt="">':e(strtoupper(substr($c['name'],0,1)))?></span><span class="conversation-copy"><b><?=e($c['name'])?></b><small><?=e(ucfirst($c['role']))?> · <?=e(mb_strimwidth((string)$c['last_body'],0,48,'…','UTF-8'))?></small></span><?php if((int)$c['unread']>0):?><span class="message-unread"><?=e($c['unread'])?></span><?php endif;?></a><?php endforeach;?>
        <?php if(!$conversations):?><p class="muted message-empty">No conversations yet.</p><?php endif;?>
      </div>
    </aside>
    <div class="message-panel">
      <?php if($partner):?>
        <div class="message-panel-head"><div><span class="eyebrow">PRIVATE CHAT</span><h3><?=e($partner['name'])?></h3><p><?=e(ucfirst($partner['role']))?> · <?=e($partner['email'])?></p></div></div>
        <div class="message-thread" id="message-thread">
          <?php foreach($messages as $m):?><div class="message-row <?=((int)$m['sender_id']===$uid)?'mine':'theirs'?>"><div class="message-bubble"><?php if(trim((string)$m['body'])!==''):?><div><?=nl2br(e($m['body']))?></div><?php endif;?><?php if(($m['attachment_type']??'')==='link' && !empty($m['attachment_url'])):?><a class="message-attachment message-link-attachment" href="<?=e($m['attachment_url'])?>" target="_blank" rel="noopener noreferrer"><span class="message-attachment-icon">↗</span><span><b><?=e($m['attachment_name']?:'Open link')?></b><small><?=e($m['attachment_url'])?></small></span></a><?php elseif(($m['attachment_type']??'')==='file' && !empty($m['attachment_path'])):?><?php $attName=(string)($m['attachment_name']??'Attachment'); $attExt=strtolower(pathinfo($attName,PATHINFO_EXTENSION)); $isImage=in_array($attExt,['png','jpg','jpeg','gif','webp'],true); ?><?php if($isImage):?><a class="message-image-attachment" href="message_attachment.php?id=<?=e($m['id'])?>&inline=1" target="_blank" rel="noopener noreferrer"><img src="message_attachment.php?id=<?=e($m['id'])?>&inline=1" alt="<?=e($attName)?>"><span class="message-image-name"><?=e($attName)?></span></a><?php else:?><a class="message-attachment" href="message_attachment.php?id=<?=e($m['id'])?>"><span class="message-attachment-icon folder-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v9A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3.5 9h17" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span><span><b><?=e($attName)?></b><small>Download file</small></span></a><?php endif;?><?php endif;?><small><?=date('M d, Y · g:i A',strtotime($m['created_at']))?></small></div></div><?php endforeach;?>
          <?php if(!$messages):?><div class="message-empty-state"><b>Start the conversation</b><p class="muted">Send a private message to <?=e($partner['name'])?>.</p></div><?php endif;?>
        </div>
        <form class="message-compose" id="message-compose-form" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="send_message"><input type="hidden" name="receiver_id" value="<?=e($partnerId)?>"><input type="hidden" name="attachment_link" id="attachment-link" value=""><input type="hidden" name="attachment_label" id="attachment-label" value=""><input type="file" name="attachment_file" id="attachment-file" hidden><div class="message-compose-row"><button type="button" class="message-clip-button" id="attachment-trigger" title="Add attachment" aria-label="Add attachment"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m20.5 11.5-8.9 8.9a5 5 0 0 1-7.1-7.1l9.2-9.2a3.5 3.5 0 0 1 5 5l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button><textarea name="body" id="message-body" rows="1" placeholder="Write a private message…" aria-label="Write a private message"></textarea><button class="btn message-send-button" type="submit">Send</button></div><div class="message-attachment-preview" id="message-attachment-preview" hidden></div><div class="message-compose-actions"><span class="muted">Press Enter to send · Shift + Enter for a new line.</span></div></form><div class="attachment-modal" id="attachment-modal" hidden><div class="attachment-modal-backdrop" data-close-attachment></div><div class="attachment-modal-card" role="dialog" aria-modal="true" aria-labelledby="attachment-modal-title"><div class="attachment-modal-head"><strong id="attachment-modal-title">Add to your message</strong><button type="button" class="attachment-modal-close" data-close-attachment aria-label="Close">×</button></div><div class="attachment-choice-grid" id="attachment-choice-grid"><button type="button" class="attachment-choice" id="choose-link"><span>↗</span><b>Link</b><small>Share a website or URL</small></button><button type="button" class="attachment-choice" id="choose-file"><span class="folder-choice-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v9A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3.5 9h17" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span><b>File</b><small>Upload a document or image</small></button></div><div class="attachment-link-form" id="attachment-link-form" hidden><label>Link URL<input type="url" id="attachment-link-input" placeholder="https://example.com"></label><label>Display name <span class="muted">(optional)</span><input type="text" id="attachment-label-input" placeholder="Example website"></label><div class="attachment-modal-actions"><button type="button" class="btn secondary-btn" data-close-attachment>Cancel</button><button type="button" class="btn" id="attach-link">Attach Link</button></div></div></div></div><script>(function(){const f=document.getElementById('message-compose-form'),t=document.getElementById('message-body'),trigger=document.getElementById('attachment-trigger'),modal=document.getElementById('attachment-modal'),file=document.getElementById('attachment-file'),link=document.getElementById('attachment-link'),label=document.getElementById('attachment-label'),preview=document.getElementById('message-attachment-preview'),linkForm=document.getElementById('attachment-link-form'),linkInput=document.getElementById('attachment-link-input'),labelInput=document.getElementById('attachment-label-input'),choiceGrid=document.getElementById('attachment-choice-grid');if(!f||!t)return;const close=()=>{modal.hidden=true;linkForm.hidden=true;choiceGrid.hidden=false;};trigger.addEventListener('click',()=>{modal.hidden=false;linkForm.hidden=true;choiceGrid.hidden=false;});document.querySelectorAll('[data-close-attachment]').forEach(x=>x.addEventListener('click',close));document.getElementById('choose-file').addEventListener('click',()=>{file.click();});document.getElementById('choose-link').addEventListener('click',()=>{choiceGrid.hidden=true;linkForm.hidden=false;linkInput.focus();});document.getElementById('attach-link').addEventListener('click',()=>{const v=linkInput.value.trim();if(!/^https?:\/\//i.test(v)){linkInput.focus();return;}link.value=v;label.value=labelInput.value.trim();file.value='';preview.hidden=false;preview.innerHTML='<span>↗</span><b>'+esc(label.value||v)+'</b><button type="button" id="remove-attachment">×</button>';document.getElementById('remove-attachment').onclick=clearAttachment;close();});file.addEventListener('change',()=>{if(!file.files.length)return;link.value='';label.value='';const name=file.files[0].name;preview.hidden=false;preview.innerHTML='<span class="folder-preview-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v9A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3.5 9h17" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span><b>'+esc(name)+'</b><button type="button" id="remove-attachment">×</button>';document.getElementById('remove-attachment').onclick=clearAttachment;});function clearAttachment(){file.value='';link.value='';label.value='';preview.hidden=true;preview.innerHTML='';}function esc(v){const d=document.createElement('div');d.textContent=v;return d.innerHTML;}t.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){e.preventDefault();if(t.value.trim()||file.files.length||link.value)f.requestSubmit();}});f.addEventListener('submit',function(e){if(!t.value.trim()&&!file.files.length&&!link.value){e.preventDefault();t.focus();}});})();</script>
      <?php else:?>
        <div class="new-message-panel"><span class="eyebrow">NEW MESSAGE</span><h3>Choose a user to message</h3><p class="muted">Search for a student, professor, or administrator, then start a private conversation.</p><div class="user-directory"><?php foreach($users as $u):?><a class="user-result" href="messaging.php?user=<?=e($u['id'])?>"><span class="avatar avatar-small"><?=!empty($u['avatar_path'])?'<img class="avatar" src="'.e($u['avatar_path']).'" alt="">':e(strtoupper(substr($u['name'],0,1)))?></span><span><b><?=e($u['name'])?></b><small><?=e(ucfirst($u['role']))?> · <?=e($u['email'])?></small></span><span class="user-message-arrow">→</span></a><?php endforeach;?><?php if(!$users):?><p class="muted">No users matched your search.</p><?php endif;?></div></div>
      <?php endif;?>
    </div>
  </div>
</section>
<?php require 'footer.php';?>
