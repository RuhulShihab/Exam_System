<?php
require_once __DIR__.'/../config/db.php';require_once __DIR__.'/../includes/auth.php';require_role($pdo,'teacher','login.php');
$teacherId=current_user()['id'];$baseUrl='../';require_once __DIR__.'/../includes/header.php';
$st=$pdo->prepare('SELECT e.id,e.title,COUNT(s.id) submissions,COALESCE(AVG(CASE WHEN s.total>0 THEN s.score*100.0/s.total END),0) avg_score,MAX(CASE WHEN s.total>0 THEN s.score*100.0/s.total END) high_score FROM exams e LEFT JOIN submissions s ON s.exam_id=e.id WHERE e.teacher_id=? GROUP BY e.id ORDER BY e.id DESC');
$st->execute([$teacherId]);$rows=$st->fetchAll();
?>
<div class="card hero-mini"><div class="eyebrow">📊 PERFORMANCE ANALYTICS</div><h2>Exam Insights</h2><p>Understand participation and student performance across your exams.</p></div>
<div class="card"><div class="table-wrap"><table class="admin-table"><tr><th>Exam</th><th>Participants</th><th>Average</th><th>Highest</th><th>Performance</th></tr>
<?php foreach($rows as $r):$avg=round((float)$r['avg_score'],1);?><tr><td><strong><?=htmlspecialchars($r['title'])?></strong></td><td><?=$r['submissions']?></td><td><?=$avg?>%</td><td><?=round((float)$r['high_score'],1)?>%</td><td><div class="progress"><span style="width:<?=min(100,max(0,$avg))?>%"></span></div></td></tr><?php endforeach;if(!$rows):?><tr><td colspan="5" class="muted">Create an exam and collect submissions to see analytics.</td></tr><?php endif;?></table></div></div>
<div class="row"><a class="btn-primary" href="dashboard.php">← Dashboard</a><a class="btn-ghost" href="results.php">View Results</a></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>