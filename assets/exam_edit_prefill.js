(function () {
    var existing = window.EXISTING_QUESTIONS || [];
    if (existing.length && window.addExamQuestion) {
        existing.forEach(function (q) {
            window.addExamQuestion(q);
        });
    } else if (window.addExamQuestion) {
        window.addExamQuestion();
    }
})();
