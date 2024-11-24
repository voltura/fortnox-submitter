document.addEventListener('DOMContentLoaded', () => {
    
    const themeToggleButton = document.getElementById('themeToggle');
    const themeIcon = themeToggleButton.querySelector('i');
    let currentTheme = localStorage.getItem('theme') || 'light';
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const hamburgerMenu = document.querySelector('.hamburger-menu');
    const table = document.querySelector('table');

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
            sidebar.style.transform = 'translateX(-100%)';
            mainContent.style.marginLeft = '0px';
            if (table) {
                table.style.position = 'relative';
            }
        } else {
            sidebar.style.transform = 'translateX(0)';
            mainContent.style.marginLeft = `${sidebar.offsetWidth}px`;
            if (table) {
                table.style.position = '';
            }
        }

        void document.body.offsetWidth;
    }

    requestAnimationFrame(updateSidebar);

    window.addEventListener('resize', updateSidebar);

    hamburgerMenu.addEventListener('click', () => {
        sidebar.classList.add('open');
        sidebar.style.transform = 'translateX(0)';
        hamburgerMenu.style.display = 'none';
        mainContent.style.marginLeft = `${sidebar.offsetWidth}px`;
        void document.body.offsetWidth;
    });

    mainContent.addEventListener('click', (e) => {
        
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !hamburgerMenu.contains(e.target)) {
            sidebar.classList.remove('open');
            hamburgerMenu.style.display = 'block';
            sidebar.style.transform = 'translateX(-100%)';
            mainContent.style.marginLeft = '0px';
            void document.body.offsetWidth;
        }
    });

    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
                hamburgerMenu.style.display = 'block';
                sidebar.style.transform = 'translateX(-100%)';
                mainContent.style.marginLeft = '0px';
                void document.body.offsetWidth;
            } else if (mainContent.style.marginLeft == '0px') {
                mainContent.style.marginLeft = `${sidebar.offsetWidth}px`;
                void document.body.offsetWidth;
            }
        });
    });

});
