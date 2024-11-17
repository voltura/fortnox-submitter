document.addEventListener('DOMContentLoaded', () => {
    
    const themeToggleButton = document.getElementById('themeToggle');
    const themeIcon = themeToggleButton.querySelector('i');
    let currentTheme = localStorage.getItem('theme') || 'light';
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const minVisibleWidth = 25;

    themeIcon.classList.remove('fa-moon', 'fa-sun');
    themeIcon.classList.add(currentTheme === 'dark' ? 'fa-sun' : 'fa-moon');
    document.body.classList.remove('light-theme', 'dark-theme');
    document.body.classList.add(currentTheme === 'dark' ? 'dark-theme' : 'light-theme');

    themeToggleButton.addEventListener('click', () => {
        currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', currentTheme);
        document.body.classList.remove('light-theme', 'dark-theme');
        document.body.classList.add(currentTheme === 'dark' ? 'dark-theme' : 'light-theme');
        themeIcon.classList.remove('fa-moon', 'fa-sun');
        themeIcon.classList.add(currentTheme === 'dark' ? 'fa-sun' : 'fa-moon');
        void document.body.offsetWidth;
    });

    function updateSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.style.transform = `translateX(calc(-100% + ${minVisibleWidth}px))`;
            mainContent.style.marginLeft = `${minVisibleWidth}px`;
        } else {
            sidebar.style.transform = 'translateX(0)';
            mainContent.style.marginLeft = `${sidebar.offsetWidth}px`;
        }
    }

    updateSidebar();

    window.addEventListener('resize', updateSidebar);

    sidebar.addEventListener('click', function (e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.classList.contains('open')) {
                e.preventDefault();
                sidebar.classList.add('open');
                sidebar.style.transform = 'translateX(0)';
                mainContent.style.marginLeft = `${sidebar.offsetWidth}px`;
            }
        }
    });
    
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 768 && !sidebar.contains(e.target)) {
            sidebar.classList.remove('open');
            sidebar.style.transform = 'translateX(calc(-100% + ' + minVisibleWidth + 'px))';
            mainContent.style.marginLeft = '0px';
        }
    });
});
