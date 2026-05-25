window.addEventListener('load', () => {
    const preloader = document.getElementById('preloader');
    
    // Simulate loading time for visual effect of AI system booting
    setTimeout(() => {
        preloader.style.opacity = '0';
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500); // Wait for transition to complete
    }, 1200);
});
