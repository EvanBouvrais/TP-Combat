        var imageInput = document.getElementById('image');
        var imagePreview = document.getElementById('image-preview');
        var imagePreviewStatus = document.getElementById('image-preview-status');
        var previewUrl = null;

        imageInput.addEventListener('change', function () {
            if (previewUrl !== null) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            imagePreview.removeAttribute('src');
            imagePreview.hidden = true;
            imagePreviewStatus.textContent = '';

            var selectedImage = imageInput.files[0];
            if (!selectedImage) {
                return;
            }

            if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(selectedImage.type)) {
                imagePreviewStatus.textContent = 'Choisissez une image JPG, PNG, GIF ou WebP.';
                return;
            }

            previewUrl = URL.createObjectURL(selectedImage);
            imagePreview.src = previewUrl;
            imagePreview.hidden = false;
            imagePreviewStatus.textContent = selectedImage.name;
        });

        window.addEventListener('pagehide', function () {
            if (previewUrl !== null) {
                URL.revokeObjectURL(previewUrl);
            }
        });

        var trainingOpponentField = document.getElementById('training-opponent-field');
        var trainingOpponent = document.getElementById('training-opponent');
        var gameModeInputs = document.querySelectorAll('input[name="game_mode"]');
        gameModeInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var trainingSelected = input.checked && input.value === 'training';
                trainingOpponentField.hidden = !trainingSelected;
                trainingOpponent.disabled = !trainingSelected;
                trainingOpponent.required = trainingSelected;
            });
        });

        var modeSelection = document.querySelector('.mode-selection');
        var heroSelection = document.querySelector('.hero-selection');
        var heroCardGrid = document.querySelector('.hero-card-grid');
        var heroSelectButtons = Array.from(document.querySelectorAll('.hero-card-select'));
        var activeSelection = 'mode';
        var modeIndex = Array.from(gameModeInputs).findIndex(function (input) {
            return input.checked;
        });
        var heroIndex = 0;

        if (heroCardGrid) {
            heroCardGrid.addEventListener('wheel', function (event) {
                if (event.deltaY !== 0) {
                    var maxScrollLeft = heroCardGrid.scrollWidth - heroCardGrid.clientWidth;
                    var nextScrollLeft = Math.max(
                        0,
                        Math.min(maxScrollLeft, heroCardGrid.scrollLeft + event.deltaY)
                    );

                    if (nextScrollLeft !== heroCardGrid.scrollLeft) {
                        heroCardGrid.scrollLeft = nextScrollLeft;
                        event.preventDefault();
                    }
                }
            }, { passive: false });
        }

        document.addEventListener('focusin', function (event) {
            if (event.target.name === 'game_mode') {
                activeSelection = 'mode';
                modeIndex = Array.from(gameModeInputs).indexOf(event.target);
            } else if (event.target.classList.contains('hero-card-select')) {
                activeSelection = 'hero';
                heroIndex = heroSelectButtons.indexOf(event.target);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.target === trainingOpponent && event.key === 'Backspace') {
                event.preventDefault();
                var trainingModeInput = Array.from(gameModeInputs).find(function (input) {
                    return input.value === 'training';
                });
                if (trainingModeInput) {
                    trainingModeInput.focus();
                }
                return;
            }

            if (event.target.matches('input:not([type="radio"]), textarea, select, [contenteditable="true"]')) {
                return;
            }

            if (
                event.target !== document.body
                && event.target !== document.documentElement
                && !event.target.closest('.mode-selection, .hero-selection')
            ) {
                return;
            }

            var modeInputs = Array.from(gameModeInputs);
            var isHeroSelection = activeSelection === 'hero';
            var selectionLength = isHeroSelection ? heroSelectButtons.length : modeInputs.length;

            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                event.preventDefault();
                activeSelection = event.key === 'ArrowUp' ? 'mode' : 'hero';
                var targetIndex = activeSelection === 'mode' ? modeIndex : heroIndex;
                var target = activeSelection === 'mode'
                    ? modeInputs[targetIndex]
                    : heroSelectButtons[targetIndex];

                if (target) {
                    target.focus();
                } else {
                    (activeSelection === 'mode' ? modeSelection : heroSelection).focus();
                }
                return;
            }

            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                if (selectionLength === 0) {
                    return;
                }

                event.preventDefault();
                var direction = event.key === 'ArrowRight' ? 1 : -1;
                var currentIndex = isHeroSelection ? heroIndex : modeIndex;
                var nextIndex = Math.max(0, Math.min(selectionLength - 1, currentIndex + direction));

                if (isHeroSelection) {
                    heroIndex = nextIndex;
                    heroSelectButtons[heroIndex].focus();
                } else {
                    modeIndex = nextIndex;
                    modeInputs[modeIndex].focus();
                    modeInputs[modeIndex].click();
                }
                return;
            }

            if (event.key === 'Enter' || event.key === ' ' || event.code === 'Space') {
                if (isHeroSelection && heroSelectButtons[heroIndex]) {
                    event.preventDefault();
                    heroSelectButtons[heroIndex].click();
                } else if (!isHeroSelection && modeInputs[modeIndex]) {
                    event.preventDefault();
                    modeInputs[modeIndex].click();
                    if (modeInputs[modeIndex].value === 'training') {
                        trainingOpponent.focus();
                        if (typeof trainingOpponent.showPicker === 'function') {
                            trainingOpponent.showPicker();
                        } else {
                            trainingOpponent.click();
                        }
                    }
                }
            }
        });
