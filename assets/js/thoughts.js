function showRandomThought() {
    if (thoughts.length > 0) {
        const randomIndex = Math.floor(Math.random() * thoughts.length);
        const thought = thoughts[randomIndex];
        const thoughtDisplay = document.getElementById('thought-display');
        thoughtDisplay.style.opacity = 0;
        setTimeout(() => {
            thoughtDisplay.innerText = thought;
            thoughtDisplay.style.opacity = 1;
        }, 300);
    }
}