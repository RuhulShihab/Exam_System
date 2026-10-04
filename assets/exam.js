(function(){
  var remaining=window.EXAM_REMAINING_SECONDS||0, submitted=false, form=document.getElementById('examForm'), autoField=document.getElementById('autoSubmitted'), reasonField=document.getElementById('reasonField'), timerEl=document.getElementById('timer-num');
  if(!form)return; var interval=null;
  function pad(n){return String(n).padStart(2,'0')}
  function doSubmit(reason){if(submitted)return;submitted=true;if(interval)clearInterval(interval);if(autoField)autoField.value='1';if(reasonField)reasonField.value=reason;form.submit()}
  function tick(){if(submitted)return;if(remaining<=0){doSubmit('time_up');return}var m=Math.floor(remaining/60),s=remaining%60;if(timerEl){timerEl.textContent=pad(m)+':'+pad(s);timerEl.parentElement.classList.toggle('urgent',remaining<=60)}remaining--}
  tick();interval=setInterval(tick,1000);
  document.addEventListener('visibilitychange',function(){if(document.hidden)doSubmit('tab_switch')});
  window.addEventListener('blur',function(){doSubmit('minimize_or_switch')});
  form.addEventListener('submit',function(){submitted=true;if(interval)clearInterval(interval)});
  document.querySelectorAll('.q-block').forEach(function(q,i){q.id='question-'+(i+1)});
})();

document.addEventListener('change', function(e){
  if(!e.target.matches('#examForm input[type=radio]')) return;
  const total=document.querySelectorAll('.q-block').length;
  const answered=new Set(Array.from(document.querySelectorAll('#examForm input[type=radio]:checked')).map(x=>x.name)).size;
  const c=document.getElementById('answered-count'), bar=document.getElementById('answer-progress');
  if(c)c.textContent=answered;
  if(bar)bar.style.width=(total?answered*100/total:0)+'%';
});
