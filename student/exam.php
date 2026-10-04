<?php
require_once __DIR__.'/../config/db.php'; require_once __DIR__.'/../includes/auth.php';
if(empty($_SESSION['exam_code'])||empty($_SESSION['student_name'])||empty($_SESSION['exam_started_at'])){header('Location: join.php');exit;}
$code=$_SESSION['exam_code'];$stmt=$pdo->prepare('SELECT * FROM exams WHERE code=?');$stmt->execute([$code]);$exam=$stmt->fetch();if(!$exam){header('Location: join.php');exit;}
$qStmt=$pdo->prepare('SELECT * FROM questions WHERE exam_id=? ORDER BY id ASC');$qStmt->execute([$exam['id']]);$questions=$qStmt->fetchAll();
if (!empty($exam['randomize_questions'])) { shuffle($questions); }$elapsed=time()-(int)$_SESSION['exam_started_at'];$remaining=($exam['duration_minutes']*60)-$elapsed;if($remaining<=0){header('Location: submit.php?timeup=1');exit;}
$baseUrl='../';require_once __DIR__.'/../includes/header.php';
?>
<div class="timer-bar"><span>📝 <?=htmlspecialchars($exam['title'])?></span><span>⏱ <span class="timer-num" id="timer-num">--:--</span></span></div>
<div class="warn-banner">⚠️ Exam security is active. Leaving the exam tab/window may automatically submit your answers.</div>
<form method="post" action="submit.php" id="examForm"><?=csrf_field()?><input type="hidden" name="auto_submitted" id="autoSubmitted" value="0"><input type="hidden" name="reason" id="reasonField" value="">
<div class="card"><div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><div><strong><?=count($questions)?></strong> Questions</div><div class="muted">Student: <strong><?=htmlspecialchars($_SESSION['student_name'])?></strong></div></div><div class="exam-progress"><div><strong>Progress</strong> <span id="answered-count">0</span>/<?=count($questions)?></div><div class="progress"><span id="answer-progress" style="width:0%"></span></div></div><div class="question-nav"><?php foreach($questions as $i=>$q):?><a href="#question-<?=$i+1?>" title="Question <?=$i+1?>"><?=$i+1?></a><?php endforeach;?></div></div>
<div class="card"><?php foreach($questions as $i=>$q):?><div class="q-block"><div class="q-num">Question <?=$i+1?></div><p class="q-text"><?=htmlspecialchars($q['question_text'])?></p><?php foreach([1,2,3,4] as $oi):?><label class="opt-row"><input type="radio" name="answers[<?=$q['id']?>]" value="<?=$oi-1?>"><span><?=htmlspecialchars($q['opt'.$oi])?></span></label><?php endforeach;?></div><?php endforeach;?></div>
<button type="submit" class="btn-gold full" style="padding:14px">✓ Submit Exam</button></form><script>window.EXAM_REMAINING_SECONDS=<?=intval($remaining)?>;</script><script src="../assets/exam.js"></script>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
