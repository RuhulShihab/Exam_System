<?php
require_once __DIR__.'/../config/db.php'; require_once __DIR__.'/../includes/auth.php';
$id=(int)($_GET['submission']??0);
$st=$pdo->prepare('SELECT s.*,e.title,e.code FROM submissions s JOIN exams e ON e.id=s.exam_id WHERE s.id=?');$st->execute([$id]);$r=$st->fetch();
if(!$r){http_response_code(404);exit('Certificate not found.');}
$pct=$r['total']?round(100*$r['score']/$r['total']):0;
?>
<!doctype html><html><head><meta charset="utf-8"><title>Certificate - <?=htmlspecialchars($r['student_name'])?></title><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="certificate"><div class="cert-mark">✦</div><div class="eyebrow">SMART ONLINE EXAMINATION PLATFORM</div><h1>Certificate of Achievement</h1><p>This certificate is proudly presented to</p><h2><?=htmlspecialchars($r['student_name'])?></h2><p>for successfully completing the examination</p><h3><?=htmlspecialchars($r['title'])?></h3><div class="cert-score"><?=$pct?>%</div><p>Score: <?=$r['score']?> / <?=$r['total']?> &nbsp; • &nbsp; Date: <?=date('d M Y',$r['submitted_at'])?></p><div class="cert-footer"><span>Certificate ID: EX-<?=str_pad($r['id'],6,'0',STR_PAD_LEFT)?></span><span>Exam Code: <?=htmlspecialchars($r['code'])?></span></div><button class="btn-primary no-print" onclick="window.print()">🖨 Print / Save PDF</button></div></body></html>