(function () {
    var insertBtn = document.getElementById('insert-bank-btn');
    if (!insertBtn) {
        return;
    }
    insertBtn.addEventListener('click', function () {
        var bank = window.BANK_QUESTIONS || {};
        document.querySelectorAll('.bank-check:checked').forEach(function (chk) {
            var q = bank[chk.value];
            if (q && window.addExamQuestion) {
                window.addExamQuestion(q);
            }
            chk.checked = false;
        });
    });
})();
