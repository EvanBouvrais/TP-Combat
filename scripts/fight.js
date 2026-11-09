            window.addEventListener('load', function () {
                var rogueCardDialog = document.querySelector('.rogue-card-dialog');
                var rogueCardInputs = rogueCardDialog
                    ? Array.from(rogueCardDialog.querySelectorAll('input[name="card_key"]'))
                    : [];
                if (rogueCardInputs.length > 0) {
                    rogueCardInputs[0].focus();
                }

                var infoPanel = document.querySelector('.survivor-counter');
                var healthPanel = document.querySelector('.health-panel');
                var workspace = document.querySelector('.combat-workspace');
                var actions = document.querySelector('.combat-actions');
                var activeRogueCards = document.querySelector('.active-rogue-cards');
                var logEntries = document.querySelector('.combat-log-entries');
                var actionInfoPanel = document.querySelector('.action-info-panel');
                var actionInfoContent = document.querySelector('.action-info-content');
                var jaronaAudio = document.getElementById('jarona-audio');
                var updateCombatLayout = function () {
                    if (!infoPanel || !healthPanel || !workspace || !actions) {
                        return;
                    }

                    var infoRect = infoPanel.getBoundingClientRect();
                    var footerTop = healthPanel.getBoundingClientRect().top;
                    var bottomGap = 16;
                    var isNarrowLayout = window.innerWidth <= 760;
                    var actionsTop = infoRect.bottom + 16;

                    actions.style.top = actionsTop + 'px';
                    actions.style.left = infoRect.left + 'px';
                    actions.style.right = 'auto';
                    actions.style.width = infoRect.width + 'px';

                    var actionsRect = actions.getBoundingClientRect();
                    var cardsTop = actionsRect.bottom;
                    if (activeRogueCards) {
                        var cardsTopLimit = actionsRect.bottom + 8;
                        var cardsBottomLimit = footerTop - bottomGap;
                        var cardsHeight = Math.max(0, cardsBottomLimit - cardsTopLimit);
                        activeRogueCards.style.top = cardsTopLimit + 'px';
                        activeRogueCards.style.left = infoRect.left + 'px';
                        activeRogueCards.style.right = 'auto';
                        activeRogueCards.style.width = infoRect.width + 'px';
                        activeRogueCards.style.maxHeight = cardsHeight + 'px';
                        activeRogueCards.hidden = cardsHeight === 0;
                        if (cardsHeight > 0) {
                            var renderedCardsHeight = activeRogueCards.getBoundingClientRect().height;
                            cardsTop = Math.max(cardsTopLimit, cardsBottomLimit - renderedCardsHeight);
                            activeRogueCards.style.top = cardsTop + 'px';
                        }
                    }

                    var workspaceTop = isNarrowLayout
                        ? actionsRect.bottom + 16
                        : infoRect.top;

                    workspace.style.left = '1rem';
                    workspace.style.right = isNarrowLayout
                        ? '1rem'
                        : Math.max(16, window.innerWidth - infoRect.left + 16) + 'px';
                    workspace.style.top = workspaceTop + 'px';
                    workspace.style.bottom = isNarrowLayout && activeRogueCards && !activeRogueCards.hidden
                        ? window.innerHeight - cardsTop + 16 + 'px'
                        : Math.max(
                            bottomGap,
                            window.innerHeight - footerTop + bottomGap
                        ) + 'px';
                };

                updateCombatLayout();
                window.addEventListener('resize', updateCombatLayout);
                var jaronaButton = actions
                    ? actions.querySelector('button[data-play-audio="jarona"]')
                    : null;
                if (jaronaAudio && jaronaButton && jaronaButton.form) {
                    var audioSubmissionPending = false;
                    jaronaButton.addEventListener('click', function (event) {
                        if (audioSubmissionPending) {
                            event.preventDefault();
                            return;
                        }

                        event.preventDefault();
                        audioSubmissionPending = true;
                        jaronaButton.disabled = true;

                        var submitAction = function () {
                            if (!audioSubmissionPending) {
                                return;
                            }
                            jaronaAudio.removeEventListener('ended', submitAction);
                            jaronaAudio.removeEventListener('error', failPlayback);
                            audioSubmissionPending = false;
                            jaronaButton.disabled = false;
                            jaronaButton.form.requestSubmit(jaronaButton);
                        };
                        var failPlayback = function () {
                            if (!audioSubmissionPending) {
                                return;
                            }
                            jaronaAudio.removeEventListener('ended', submitAction);
                            jaronaAudio.removeEventListener('error', failPlayback);
                            audioSubmissionPending = false;
                            jaronaButton.disabled = false;
                            window.alert('Impossible de lire la piste audio de Jarona.');
                        };

                        try {
                            jaronaAudio.currentTime = 0;
                            jaronaAudio.addEventListener('ended', submitAction, { once: true });
                            jaronaAudio.addEventListener('error', failPlayback, { once: true });
                            var playback = jaronaAudio.play();
                            if (playback && typeof playback.catch === 'function') {
                                playback.catch(failPlayback);
                            }
                        } catch (error) {
                            failPlayback();
                        }
                    });
                }
                document.addEventListener('keydown', function (event) {
                    if (
                        rogueCardDialog
                        && rogueCardDialog.contains(event.target)
                        && event.target.matches('input[name="card_key"]')
                        && (event.key === 'Enter' || event.key === ' ' || event.code === 'Space')
                    ) {
                        event.preventDefault();
                        event.target.checked = true;
                        event.target.form.requestSubmit();
                        return;
                    }

                    if (
                        rogueCardDialog
                        && rogueCardDialog.contains(document.activeElement)
                        && ['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight'].includes(event.key)
                    ) {
                        event.preventDefault();
                        var currentCardIndex = rogueCardInputs.indexOf(document.activeElement);
                        var cardDirection = event.key === 'ArrowDown' || event.key === 'ArrowRight'
                            ? 1
                            : -1;
                        var nextCardIndex = (
                            (currentCardIndex === -1 ? 0 : currentCardIndex + cardDirection)
                            + rogueCardInputs.length
                        ) % rogueCardInputs.length;
                        rogueCardInputs[nextCardIndex].checked = true;
                        rogueCardInputs[nextCardIndex].focus();
                        return;
                    }

                    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
                        return;
                    }

                    var activeElement = document.activeElement;
                    if (
                        activeElement !== document.body
                        && !actions.contains(activeElement)
                    ) {
                        return;
                    }

                    var buttons = Array.from(
                        actions.querySelectorAll('button:not(:disabled)')
                    ).filter(function (button) {
                        return button.offsetParent !== null;
                    });
                    if (buttons.length === 0) {
                        return;
                    }

                    var currentIndex = buttons.indexOf(activeElement);
                    var direction = event.key === 'ArrowDown' ? 1 : -1;
                    var nextIndex = currentIndex === -1
                        ? (direction === 1 ? 0 : buttons.length - 1)
                        : (currentIndex + direction + buttons.length) % buttons.length;

                    event.preventDefault();
                    buttons[nextIndex].focus();
                });
                if (actions && actionInfoPanel && actionInfoContent) {
                    var showActionInfo = function (wrapper) {
                        actionInfoContent.textContent = wrapper.getAttribute('data-action-info');
                        actionInfoPanel.hidden = false;
                    };
                    var hideActionInfo = function () {
                        if (!actions.querySelector('.action-button-wrapper:hover, .action-button-wrapper:focus-within')) {
                            actionInfoPanel.hidden = true;
                        }
                    };

                    actions.addEventListener('pointerover', function (event) {
                        var wrapper = event.target.closest('.action-button-wrapper');
                        if (wrapper && actions.contains(wrapper)) {
                            showActionInfo(wrapper);
                        }
                    });
                    actions.addEventListener('pointerout', function (event) {
                        var wrapper = event.target.closest('.action-button-wrapper');
                        if (wrapper && !wrapper.contains(event.relatedTarget)) {
                            window.setTimeout(hideActionInfo, 0);
                        }
                    });
                    actions.addEventListener('focusin', function (event) {
                        var wrapper = event.target.closest('.action-button-wrapper');
                        if (wrapper) {
                            showActionInfo(wrapper);
                        }
                    });
                    actions.addEventListener('focusout', function (event) {
                        if (!actions.contains(event.relatedTarget)) {
                            window.setTimeout(hideActionInfo, 0);
                        }
                    });
                }
                if (logEntries) {
                    logEntries.scrollTop = logEntries.scrollHeight;
                }
            });
