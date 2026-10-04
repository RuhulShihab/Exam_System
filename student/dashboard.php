<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
$baseUrl='../'; require_once __DIR__.'/../includes/header.php';
$name=trim($_SESSION['student_name']??'');
$rows=[];
if($name!==''){
 $st=$pdo->prepare('SELECT s.*,e.title,e.code FROM submissions s JOIN exams e ON e.id=s.exam_id WHERE s.student_name=? ORDER BY s.id DESC');
 $st->execute([$name]); $rows=$st->fetchAll();
}
$noticeStmt=$pdo->query("SELECT * FROM notifications WHERE audience IN ('all','students') ORDER BY id DESC LIMIT 10");
$announcements=$noticeStmt->fetchAll();

$avg=0;$total=0;$passed=0;
foreach($rows as $r){$total++;$pct=$r['total']?(100*$r['score']/$r['total']):0;$avg+=$pct;if($pct>=40)$passed++;}
$avg=$total?round($avg/$total,1):0;
?>
<div class="card hero-mini"><div class="eyebrow">STUDENT DASHBOARD</div><h2>Welcome<?= $name!==''?', '.htmlspecialchars($name):'' ?> 👋</h2><p>Track your exam history, performance and certificates from one place.</p></div>
<div class="stat-grid">
<div class="stat-box"><div class="num"><?=$total?></div><div class="label">📝 Exams Taken</div></div>
<div class="stat-box"><div class="num"><?=$avg?>%</div><div class="label">📈 Average Score</div></div>
<div class="stat-box"><div class="num"><?=$passed?></div><div class="label">✅ Passed</div></div>
<div class="stat-box"><div class="num"><?=count($rows)-$passed?></div><div class="label">📊 Needs Improvement</div></div>
</div>
<div class="row"><a class="btn-primary" href="join.php">＋ Take Another Exam</a><a class="btn-ghost" href="leaderboard.php">🏆 Leaderboard</a></div>
<div class="card">
    <h3>🔔 Announcements</h3>
    <?php foreach($announcements as $n): ?>
    <div class="exam-list-item">
        <div>
            <strong><?=htmlspecialchars($n['title'])?></strong>
            <p class="muted" style="margin:3px 0"><?=nl2br(htmlspecialchars($n['message']))?></p>
            <small class="muted">
                <?=htmlspecialchars($n['sender_role'] ? ucfirst($n['sender_role']) : 'System')?> ·
                <?=date('d M Y, h:i A',(int)$n['created_at'])?>
            </small>
        </div>
    </div>
    <?php endforeach; if(!$announcements): ?>
    <p class="muted">No announcements available.</p>
    <?php endif; ?>
</div>
<div class="card"><h3>📜 My Exam History</h3><div class="table-wrap"><table class="admin-table"><tr><th>Exam</th><th>Score</th><th>Accuracy</th><th>Date</th><th>Action</th></tr>
<?php foreach($rows as $r): $pct=$r['total']?round(100*$r['score']/$r['total']):0; ?>
<tr><td><strong><?=htmlspecialchars($r['title'])?></strong><br><span class="code-tag small"><?=htmlspecialchars($r['code'])?></span></td><td><?=$r['score']?>/<?=$r['total']?></td><td><span class="badge <?=$pct>=40?'badge-active':'badge-blocked'?>"><?=$pct?>%</span></td><td class="muted"><?=date('d M Y, h:i A',$r['submitted_at'])?></td><td><a class="btn-ghost" href="certificate.php?submission=<?=$r['id']?>">Certificate</a></td></tr>
<?php endforeach; if(!$rows):?><tr><td colspan="5" class="muted">No result history for this name yet. Join an exam to get started.</td></tr><?php endif;?></table></div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>