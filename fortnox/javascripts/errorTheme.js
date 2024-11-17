document.addEventListener('DOMContentLoaded', () => {

    const currentTheme = localStorage.getItem('themeToggle') || 'light';
    
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
    
});
