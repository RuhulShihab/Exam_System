<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
require_role($pdo,'teacher','login.php');

$u=current_user();

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $title=trim($_POST['title']??'');
    $msg=trim($_POST['message']??'');
    $aud=$_POST['audience']??'students';

    /* Teachers publish notices for students (or everyone). */
    if($title!==''&&$msg!==''&&in_array($aud,['all','students'],true)){
        $pdo->prepare(
            'INSERT INTO notifications(title,message,audience,sender_id,sender_role,created_at)
             VALUES(?,?,?,?,?,?)'
        )->execute([$title,$msg,$aud,$u['id'],'teacher',time()]);

        header('Location: notifications.php?sent=1');
        exit;
    }
}

$baseUrl='../';
require_once __DIR__.'/../includes/header.php';

/* Show recent announcements created by this teacher. */
$st=$pdo->prepare('SELECT * FROM notifications WHERE sender_id=? AND sender_role=? ORDER BY id DESC LIMIT 30');
$st->execute([$u['id'],'teacher']);
$rows=$st->fetchAll();
?>
<?php include __DIR__.'/_nav.php'; ?>

<div class="card hero-mini">
    <div class="eyebrow">🔔 NOTIFICATIONS</div>
    <h2>Announcements</h2>
    <p>Publish important notices for your students.</p>
</div>

<?php if(isset($_GET['sent'])): ?>
<div class="notice-box">✓ Announcement published successfully.</div>
<?php endif;?>

<div class="card">
    <form method="post">
        <?=csrf_field()?>
        <label>Title</label>
        <input name="title" required placeholder="Exam schedule updated">

        <label>Message</label>
        <textarea name="message" rows="4" required placeholder="Write your announcement..."></textarea>

        <label>Audience</label>
        <select name="audience">
            <option value="students">Students</option>
            <option value="all">Everyone</option>
        </select>

        <button class="btn-primary" style="margin-top:15px">📢 Publish Announcement</button>
    </form>
</div>

<div class="card">
    <h3>Recent Announcements</h3>
    <?php foreach($rows as $n): ?>
    <div class="exam-list-item">
        <div>
            <strong><?=htmlspecialchars($n['title'])?></strong>
            <p class="muted" style="margin:3px 0"><?=nl2br(htmlspecialchars($n['message']))?></p>
            <small class="muted"><?=date('d M Y, h:i A',(int)$n['created_at'])?></small>
        </div>
        <span class="badge badge-teacher"><?=htmlspecialchars($n['audience'])?></span>
    </div>
    <?php endforeach; if(!$rows):?>
    <p class="muted">No announcements yet.</p>
    <?php endif;?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
