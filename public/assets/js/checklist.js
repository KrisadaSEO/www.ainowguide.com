document.addEventListener('DOMContentLoaded', function () {
    var app = document.querySelector('[data-checklist-app]');

    if (!app) {
        return;
    }

    var questions = Array.prototype.slice.call(app.querySelectorAll('[data-question]'));
    var scoreValue = app.querySelector('[data-score-value]');
    var scoreValueLarge = app.querySelector('[data-score-value-large]');
    var answeredCount = app.querySelector('[data-answered-count]');
    var unsureCount = app.querySelector('[data-unsure-count]');
    var scoreSummary = app.querySelector('[data-score-summary]');
    var scoreDetail = app.querySelector('[data-score-detail]');
    var progressFill = app.querySelector('[data-progress-fill]');
    var recommendationIntro = app.querySelector('[data-recommendations-intro]');
    var recommendationList = app.querySelector('[data-recommendations-list]');
    var bandItems = Array.prototype.slice.call(app.querySelectorAll('[data-score-band]'));
    var detailsItems = Array.prototype.slice.call(app.querySelectorAll('.assessment-explainer'));
    var toggleExplainersButton = app.querySelector('[data-toggle-explainers]');
    var resetButton = app.querySelector('[data-reset-assessment]');
    var printButton = app.querySelector('[data-print-assessment]');
    var deliveryForm = app.querySelector('[data-assessment-delivery-form]');
    var deliveryAnswersInput = app.querySelector('[data-assessment-answers]');
    var deliverySubmit = app.querySelector('[data-assessment-submit]');
    var deliveryNote = app.querySelector('[data-assessment-submit-note]');
    var openStatesBeforePrint = null;

    function titleCaseState(state) {
        if (state === 'yes') {
            return 'Solid';
        }

        if (state === 'no') {
            return 'Needs work';
        }

        if (state === 'unsure') {
            return 'Needs checking';
        }

        return 'Open';
    }

    function recommendationCopy(state) {
        if (state === 'no') {
            return 'You marked this as a gap. Fixing it will make the whole system stronger, not just this one point.';
        }

        if (state === 'unsure') {
            return 'You marked this as unclear. That usually means there is hidden risk or undocumented work to verify.';
        }

        return 'There is still room to tighten this area and turn it into a more deliberate strength.';
    }

    function summaryFor(score, answered, unsure, total) {
        if (answered === 0) {
            return {
                summary: 'Start answering the questions and the score will build as you go.',
                detail: 'Use "Not sure" whenever you cannot confidently verify something. That is a useful signal, not a failure.',
            };
        }

        if (answered < total) {
            return {
                summary: 'This is a provisional read. Finish the remaining questions for a cleaner picture.',
                detail: 'So far you have ' + score + ' solid areas, ' + (answered - score - unsure) + ' clear gaps, and ' + unsure + ' items that need verification.',
            };
        }

        if (score <= 3) {
            return {
                summary: 'The foundation is still fragile. Start with ownership, structure, and resilience before chasing tactics.',
                detail: 'A low score does not mean you are behind forever. It means the most important work is still foundational, which is good because it is fixable.',
            };
        }

        if (score <= 6) {
            return {
                summary: 'You already have pieces of the right system. The next move is tightening what is inconsistent or undocumented.',
                detail: 'Most people in this band are not starting from zero. They are stuck between good instincts and incomplete execution.',
            };
        }

        if (score <= 8) {
            return {
                summary: 'This is a strong base. Your next gains will come from removing blind spots and making the system more repeatable.',
                detail: 'At this level, weak areas are usually hidden in architecture, measurement, or process rather than effort.',
            };
        }

        return {
            summary: 'You are operating from a compounding base. Keep refining the weak edges so the system stays durable.',
            detail: 'A high score does not mean "done." It means your next improvements can be more strategic than reactive.',
        };
    }

    function renderRecommendations(items, answered, total) {
        recommendationList.innerHTML = '';

        if (answered === 0) {
            recommendationIntro.textContent = 'Finish the assessment and I will surface the clearest next reading path from the library.';
            recommendationList.innerHTML = '<li class="assessment-recommendation assessment-recommendation--placeholder">Answer the questions above to generate a more useful next-step path.</li>';
            return;
        }

        if (!items.length && answered < total) {
            recommendationIntro.textContent = 'You have not marked any weak spots yet, but a few questions are still unanswered.';
            recommendationList.innerHTML = '<li class="assessment-recommendation assessment-recommendation--placeholder">Finish the remaining questions and the recommendations will sharpen.</li>';
            return;
        }

        if (!items.length) {
            recommendationIntro.textContent = 'You marked every area as solid. The best next step is keeping the system documented, current, and measurable.';
            recommendationList.innerHTML = '<li class="assessment-recommendation"><span class="assessment-recommendation__title">Keep pressure-testing the system</span><p class="assessment-recommendation__copy">A strong score is a signal to protect what is already working. Review visibility, structure, and resilience regularly so the system keeps compounding.</p><a class="assessment-recommendation__link" href="/library/">Browse the library for deeper refinement &rarr;</a></li>';
            return;
        }

        recommendationIntro.textContent = 'These are the clearest next reading paths based on the areas you marked as weak or uncertain.';

        items.slice(0, 3).forEach(function (item) {
            var row = document.createElement('li');
            row.className = 'assessment-recommendation';
            row.innerHTML =
                '<span class="assessment-recommendation__state">' + titleCaseState(item.state) + '</span>' +
                '<span class="assessment-recommendation__title">' + item.title + '</span>' +
                '<p class="assessment-recommendation__copy">' + recommendationCopy(item.state) + '</p>' +
                '<a class="assessment-recommendation__link" href="' + item.url + '">' + item.label + ' &rarr;</a>';
            recommendationList.appendChild(row);
        });
    }

    function updateBands(score, answered) {
        bandItems.forEach(function (band) {
            var min = Number(band.getAttribute('data-min'));
            var max = Number(band.getAttribute('data-max'));
            var isActive = answered > 0 && score >= min && score <= max;
            band.classList.toggle('is-active', isActive);
        });
    }

    function updateExplainerButton() {
        if (!toggleExplainersButton) {
            return;
        }

        var everyOpen = detailsItems.length > 0 && detailsItems.every(function (item) {
            return item.open;
        });

        toggleExplainersButton.textContent = everyOpen ? 'Close all explanations' : 'Open all explanations';
    }

    function updateAssessment() {
        var answered = 0;
        var score = 0;
        var unsure = 0;
        var recommendations = [];
        var serializedAnswers = [];
        var total = questions.length;

        questions.forEach(function (question) {
            var selected = question.querySelector('input[type="radio"]:checked');
            var state = selected ? selected.value : '';

            question.setAttribute('data-state', state || 'unanswered');

            if (!state) {
                return;
            }

            answered += 1;

            serializedAnswers.push({
                index: Number(question.querySelector('.assessment-card__number').textContent || 0),
                state: state
            });

            if (state === 'yes') {
                score += 1;
                return;
            }

            if (state === 'unsure') {
                unsure += 1;
            }

            recommendations.push({
                state: state,
                title: question.getAttribute('data-title') || 'Untitled question',
                url: question.getAttribute('data-link-url') || '/library/',
                label: question.getAttribute('data-link-label') || 'Read the related guide',
            });
        });

        if (scoreValue) {
            scoreValue.textContent = String(score);
        }

        if (scoreValueLarge) {
            scoreValueLarge.textContent = String(score);
        }

        if (answeredCount) {
            answeredCount.textContent = String(answered);
        }

        if (unsureCount) {
            unsureCount.textContent = String(unsure);
        }

        if (progressFill) {
            progressFill.style.width = total === 0 ? '0%' : ((answered / total) * 100) + '%';
        }

        var summary = summaryFor(score, answered, unsure, total);

        if (scoreSummary) {
            scoreSummary.textContent = summary.summary;
        }

        if (scoreDetail) {
            scoreDetail.textContent = summary.detail;
        }

        if (deliveryAnswersInput) {
            deliveryAnswersInput.value = JSON.stringify(serializedAnswers);
        }

        if (deliverySubmit) {
            var isComplete = answered === total && total > 0;
            deliverySubmit.disabled = !isComplete;
            deliverySubmit.textContent = isComplete ? 'Email Me My Score + Next Steps' : 'Complete all 10 questions first';
        }

        if (deliveryNote) {
            if (answered === total && total > 0) {
                deliveryNote.textContent = 'I will send your score, weak spots, and recommended reading path to your inbox.';
            } else {
                deliveryNote.textContent = 'Complete all 10 questions to unlock the email summary.';
            }
        }

        updateBands(score, answered);
        renderRecommendations(recommendations, answered, total);
    }

    questions.forEach(function (question) {
        question.addEventListener('change', updateAssessment);
    });

    detailsItems.forEach(function (item) {
        item.addEventListener('toggle', updateExplainerButton);
    });

    if (toggleExplainersButton) {
        toggleExplainersButton.addEventListener('click', function () {
            var everyOpen = detailsItems.length > 0 && detailsItems.every(function (item) {
                return item.open;
            });

            detailsItems.forEach(function (item) {
                item.open = !everyOpen;
            });

            updateExplainerButton();
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            questions.forEach(function (question) {
                var inputs = question.querySelectorAll('input[type="radio"]');
                inputs.forEach(function (input) {
                    input.checked = false;
                });
            });

            updateAssessment();
        });
    }

    if (printButton) {
        printButton.addEventListener('click', function () {
            window.print();
        });
    }

    if (deliveryForm) {
        deliveryForm.addEventListener('submit', function (event) {
            if (deliverySubmit && deliverySubmit.disabled) {
                event.preventDefault();
            }
        });
    }

    window.addEventListener('beforeprint', function () {
        openStatesBeforePrint = detailsItems.map(function (item) {
            return item.open;
        });

        detailsItems.forEach(function (item) {
            item.open = true;
        });
    });

    window.addEventListener('afterprint', function () {
        if (!openStatesBeforePrint) {
            return;
        }

        detailsItems.forEach(function (item, index) {
            item.open = openStatesBeforePrint[index];
        });

        openStatesBeforePrint = null;
        updateExplainerButton();
    });

    updateExplainerButton();
    updateAssessment();
});
