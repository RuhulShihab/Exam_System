(function () {
    var container = document.getElementById('q-container');
    var addBtn = document.getElementById('add-q');
    var template = document.getElementById('q-template');
    var qLabel = window.QUESTION_LABEL || 'Question';

    function renumber() {
        var blocks = container.querySelectorAll('.q-block');
        blocks.forEach(function (block, idx) {
            block.querySelector('.q-num').textContent = qLabel + ' ' + (idx + 1);
            var radios = block.querySelectorAll('input[type=radio]');
            radios.forEach(function (r) {
                r.name = 'q_correct[' + idx + ']';
            });
        });
    }

    function addQuestion(data) {
        var clone = template.content.cloneNode(true);
        var block = clone.querySelector('.q-block');

        clone.querySelectorAll('.remove-q').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('.q-block').remove();
                renumber();
            });
        });

        if (data) {
            var textarea = block.querySelector('textarea[name="q_text[]"]');
            if (textarea) {
                textarea.value = data.text || '';
            }
            ['opt1', 'opt2', 'opt3', 'opt4'].forEach(function (key) {
                var input = block.querySelector('input[name="q_' + key + '[]"]');
                if (input) {
                    input.value = data[key] || '';
                }
            });
            var radios = block.querySelectorAll('input[type=radio]');
            var correctIdx = parseInt(data.correct, 10) || 0;
            radios.forEach(function (r, i) {
                r.checked = (i === correctIdx);
            });
        }

        container.appendChild(clone);
        renumber();
    }

    addBtn.addEventListener('click', function () {
        addQuestion();
    });

    // Exposed so edit_exam.php can prefill existing questions and the
    // question-bank "insert" button can append saved questions.
    window.addExamQuestion = addQuestion;

    if (!window.SKIP_INITIAL_QUESTION) {
        addQuestion();
    }
})();
