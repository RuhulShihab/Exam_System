<?php require_once __DIR__.'/includes/auth.php'; $baseUrl=''; require_once __DIR__.'/includes/header.php'; $teacherHref='teacher/login.php'; if(is_logged_in()&&user_role()==='teacher')$teacherHref='teacher/dashboard.php'; ?>
<div class="cover"><div style="position:relative;font-size:12px;font-weight:800;letter-spacing:2px;color:#c7d2fe">SMART ONLINE EXAMINATION PLATFORM</div><h1><?=htmlspecialchars(t('site_title'))?></h1><p>Create exams, manage questions, conduct secure timed tests and analyze results — all in one modern platform.</p><div class="row center" style="margin-top:25px"><a class="btn-primary" style="background:#fff;color:#312e81" href="<?=$teacherHref?>">👨‍🏫 Teacher Portal</a><a class="btn-ghost" href="student/join.php">🎓 Join Exam</a></div></div>
<div class="role-choice"><a class="role-card" href="<?=$teacherHref?>"><h3><?=htmlspecialchars(t('i_am_teacher'))?></h3><p><?=htmlspecialchars(t('i_am_teacher_desc'))?></p></a><a class="role-card" href="student/join.php"><h3><?=htmlspecialchars(t('i_am_student'))?></h3><p><?=htmlspecialchars(t('i_am_student_desc'))?></p></a></div>
<div class="card"><div class="row" style="justify-content:space-around;text-align:center"><div><strong style="font-size:25px">⚡</strong><div><b>Fast & Simple</b></div><span class="muted">Easy exam workflow</span></div><div><strong style="font-size:25px">🛡️</strong><div><b>Secure</b></div><span class="muted">CSRF, rate limits & audit logs</span></div><div><strong style="font-size:25px">📊</strong><div><b>Analytics</b></div><span class="muted">Clear result insights</span></div></div></div>
<div class="feature-grid">
<div class="feature-card">🎲<strong>Random Exams</strong><span>Fresh question order for every attempt.</span></div>
<div class="feature-card">🛡️<strong>Secure Testing</strong><span>Timer, tab monitoring and server-side scoring.</span></div>
<div class="feature-card">📊<strong>Smart Analytics</strong><span>Performance insights for teachers and admins.</span></div>
<div class="feature-card">🏆<strong>Leaderboard</strong><span>Celebrate top performers and progress.</span></div>
<div class="feature-card">📜<strong>Certificates</strong><span>Print or save achievement certificates.</span></div>
<div class="feature-card">🔔<strong>Announcements</strong><span>Keep students and teachers informed.</span></div>
</div>
<p class="row center"><a class="link-btn" href="student/leaderboard.php">🏆 Leaderboard</a><a class="link-btn" href="admin/login.php">⚙ Admin Panel</a></p><?php require_once __DIR__.'/includes/footer.php'; ?>
