<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');
$baseUrl='../'; require_once __DIR__.'/../includes/header.php';
$totalTeachers=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$pendingTeachers=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher' AND status='pending'")->fetchColumn();
$totalExams=(int)$pdo->query('SELECT COUNT(*) FROM exams')->fetchColumn();
$totalSubs=(int)$pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
$totalQuestions=(int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$avgScore=(float)($pdo->query('SELECT COALESCE(AVG(CASE WHEN total>0 THEN score*100.0/total END),0) FROM submissions')->fetchColumn() ?: 0);
$recent=$pdo->query("SELECT s.student_name,s.score,s.total,s.submitted_at,e.title FROM submissions s JOIN exams e ON e.id=s.exam_id ORDER BY s.id DESC LIMIT 6")->fetchAll();
$examRows=$pdo->query("SELECT e.title,COUNT(s.id) cnt FROM exams e LEFT JOIN submissions s ON s.exam_id=e.id GROUP BY e.id ORDER BY cnt DESC,e.id DESC LIMIT 6")->fetchAll();
$max = 1; foreach ($examRows as $x) { $c = (int)$x['cnt']; if ($c > $max) $max = $c; }
?>
<div class="card" style="background:linear-gradient(135deg,#111827,#312e81);color:#fff;border:0"><div class="muted" style="color:#c7d2fe">ADMIN CONTROL CENTER</div><h2 style="font-size:30px;margin:5px 0">Welcome back, <?=htmlspecialchars(current_user()['name'])?> 👋</h2><p style="margin:0;color:#dbeafe">Monitor exams, teachers, submissions and system activity from one place.</p></div>
<?php include __DIR__.'/_nav.php'; ?>
<div class="stat-grid">
 <div class="stat-box"><div class="num"><?=$totalTeachers?></div><div class="label">👨‍🏫 Total Teachers</div></div>
 <div class="stat-box"><div class="num"><?=$pendingTeachers?></div><div class="label">⏳ Pending Teachers</div></div>
 <div class="stat-box"><div class="num"><?=$totalExams?></div><div class="label">📝 Total Exams</div></div>
 <div class="stat-box"><div class="num"><?=$totalSubs?></div><div class="label">🎯 Submissions</div></div>
</div>
<div class="dashboard-grid">
 <div class="card"><h3>📊 Exam Participation</h3><div class="chart-bars"><?php foreach($examRows as $r):$h=(int)round(((int)$r['cnt']/$max)*145);?><div class="bar-wrap"><div class="bar" style="height:<?=$h?>px" title="<?=htmlspecialchars($r['title'])?>: <?=$r['cnt']?>"></div><small><?=htmlspecialchars(substr($r['title'],0,12))?></small></div><?php endforeach;if(!$examRows):?><p class="muted">No exam data yet.</p><?php endif;?></div></div>
 <div class="card"><h3>⚡ System Snapshot</h3><div class="metric-row"><span>Questions</span><strong><?=$totalQuestions?></strong></div><div class="metric-row"><span>Average score</span><strong><?=number_format($avgScore,1)?>%</strong></div><div class="progress" style="margin-top:10px"><span style="width:<?=min(100,max(0,$avgScore))?>%"></span></div><div class="metric-row"><span>Teacher approval queue</span><strong><?=$pendingTeachers?></strong></div><a class="btn-primary" href="submissions.php">View all submissions →</a></div>
</div>
<div class="card"><h3>🕒 Recent Submissions</h3><div class="table-wrap"><table class="admin-table"><tr><th>Student</th><th>Exam</th><th>Score</th><th>Time</th></tr><?php foreach($recent as $r):$pct=$r['total']?round($r['score']*100/$r['total']):0;?><tr><td><?=htmlspecialchars($r['student_name'])?></td><td><?=htmlspecialchars($r['title'])?></td><td><strong><?=$r['score']?>/<?=$r['total']?></strong> <span class="badge badge-active"><?=$pct?>%</span></td><td class="muted"><?=date('d M Y, h:i A',$r['submitted_at'])?></td></tr><?php endforeach;if(!$recent):?><tr><td colspan="4" class="muted">No submissions yet.</td></tr><?php endif;?></table></div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
