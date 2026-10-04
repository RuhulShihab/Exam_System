<?php
require_once __DIR__.'/../config/db.php'; require_once __DIR__.'/../includes/auth.php';
$baseUrl='../'; require_once __DIR__.'/../includes/header.php';
$examCode=strtoupper(trim($_GET['code']??''));
$exams=$pdo->query('SELECT id,title,code FROM exams ORDER BY id DESC')->fetchAll();
$rows=[];
if($examCode){
 $st=$pdo->prepare('SELECT s.student_name,s.score,s.total,s.submitted_at,e.title,e.code FROM submissions s JOIN exams e ON e.id=s.exam_id WHERE e.code=? ORDER BY (CASE WHEN s.total>0 THEN s.score*1.0/s.total ELSE 0 END) DESC,s.submitted_at ASC');
 $st->execute([$examCode]);$rows=$st->fetchAll();
}else{
 $rows=$pdo->query('SELECT s.student_name,s.score,s.total,s.submitted_at,e.title,e.code FROM submissions s JOIN exams e ON e.id=s.exam_id ORDER BY (CASE WHEN s.total>0 THEN s.score*1.0/s.total ELSE 0 END) DESC,s.submitted_at ASC LIMIT 50')->fetchAll();
}
?>
<div class="card hero-mini"><div class="eyebrow">🏆 LEADERBOARD</div><h2>Top Performers</h2><p>Compare performance by exam and celebrate the highest scores.</p></div>
<div class="card"><form method="get" class="row"><select name="code" style="flex:1"><option value="">All Exams</option><?php foreach($exams as $e):?><option value="<?=htmlspecialchars($e['code'])?>" <?=$examCode===$e['code']?'selected':''?>><?=htmlspecialchars($e['title'])?></option><?php endforeach;?></select><button class="btn-primary">View Ranking</button></form></div>
<div class="card"><div class="table-wrap"><table class="admin-table"><tr><th>Rank</th><th>Student</th><th>Exam</th><th>Score</th><th>Accuracy</th></tr><?php foreach($rows as $i=>$r):$pct=$r['total']?round(100*$r['score']/$r['total']):0;$med=$i<3?['🥇','🥈','🥉'][$i]:($i+1);?><tr><td><strong><?=$med?></strong></td><td><?=htmlspecialchars($r['student_name'])?></td><td><?=htmlspecialchars($r['title'])?></td><td><strong><?=$r['score']?>/<?=$r['total']?></strong></td><td><?=$pct?>%</td></tr><?php endforeach;if(!$rows):?><tr><td colspan="5" class="muted">No submissions yet.</td></tr><?php endif;?></table></div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>