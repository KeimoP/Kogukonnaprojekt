document.addEventListener('DOMContentLoaded', function() {
    // 1. Form handling with gentle validation
    const form = document.getElementById('questions-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 2. Create a comforting response
            const responses = [
                "Hästi tehtud! Väikesed sammud on olulised. 💖",
                "Oled tubli! Pane tähele enda väikeseid edu. 🌸", 
                "Tänan, et leidsid aega enda jaoks täna. 🌼",
                "Sinu vastused on olulised, eriti sinu jaoks. 💐"
            ];
            
            // 3. Show random positive feedback
            const randomResponse = responses[Math.floor(Math.random() * responses.length)];
            showFlowerMessage(randomResponse);
            
            // 4. Optional: Slowly fade out the form
            form.style.transition = 'opacity 1s ease';
            form.style.opacity = '0.5';
            
            // 5. Reset after delay (without saving)
            setTimeout(() => {
                form.reset();
                form.style.opacity = '1';
            }, 3000);
        });
    }
    
    // 6. Flower animation helper
    function showFlowerMessage(message) {
        const flowerContainer = document.createElement('div');
        flowerContainer.className = 'flower-message';
        flowerContainer.innerHTML = `
            <div class="flower-animation">🌸</div>
            <div class="flower-text">${message}</div>
        `;
        
        document.body.appendChild(flowerContainer);
        
        // Remove after animation
        setTimeout(() => {
            flowerContainer.style.opacity = '0';
            setTimeout(() => flowerContainer.remove(), 1000);
        }, 4000);
    }
    
    // 7. Add subtle interactive flowers
    document.querySelectorAll('.flower-emoji').forEach(flower => {
        flower.addEventListener('mouseover', function() {
            this.style.transform = 'scale(1.2) rotate(10deg)';
        });
        flower.addEventListener('mouseout', function() {
            this.style.transform = '';
        });
    });
});