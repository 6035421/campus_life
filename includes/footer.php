<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="/css/style.css">

</main>
        
        <footer class="site-footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>CampusLife</h3>
                        <p>Een eenvoudig maar krachtig platform voor studenten en docenten om samen te werken en te leren.</p>
                    </div>
                    
                    <div class="footer-section">
                        <h4>Navigatie</h4>
                        <ul>
                            <li><a href="/">Home</a></li>
                            <li><a href="over-ons.php">Over ons</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="faq.php">Veelgestelde vragen</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4>Handige links</h4>
                        <ul>
                            <li><a href="rooster.php">Mijn rooster</a></li>
                            <li><a href="opdrachten.php">Mijn opdrachten</a></li>
                            <li><a href="cijfers.php">Mijn cijfers</a></li>
                            <li><a href="profiel.php">Mijn profiel</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4>Contact</h4>
                        <p><i class="fas fa-envelope"></i> info@campuslife.nl</p>
                        <p><i class="fas fa-phone"></i> (020) 123 45 67</p>
                        <div class="social-links">
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; <?= date('Y') ?> CampusLife. Alle rechten voorbehouden.</p>
                    <div class="footer-links">
                        <a href="privacyverklaring.php">Privacyverklaring</a>
                        <a href="algemene-voorwaarden.php">Algemene voorwaarden</a>
                        <a href="cookies.php">Cookiebeleid</a>
                    </div>
                </div>
            </div>
        </footer>
        
        <script src="/js/main.js"></script>
        <script>
            // Sluit de flash message wanneer erop wordt geklikt
            document.querySelectorAll('.close-flash').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.flash-message').style.display = 'none';
                });
            });
            
            // Verberg de flash message na 5 seconden
            setTimeout(() => {
                const flashMessages = document.querySelectorAll('.flash-message');
                flashMessages.forEach(flash => {
                    flash.style.opacity = '0';
                    setTimeout(() => flash.style.display = 'none', 300);
                });
            }, 5000);
            
            // Mobiele menu toggle
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const mainNav = document.querySelector('.main-nav');
            
            if (menuToggle && mainNav) {
                menuToggle.addEventListener('click', () => {
                    mainNav.classList.toggle('show');
                    menuToggle.classList.toggle('active');
                });
            }
            
            // Dropdown menu voor gebruikersprofiel
            const dropdowns = document.querySelectorAll('.dropdown');
            
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const content = dropdown.querySelector('.dropdown-content');
                
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    content.classList.toggle('show');
                });
            });
            
            // Sluit dropdowns als er buiten wordt geklikt
            window.addEventListener('click', (e) => {
                if (!e.target.matches('.dropdown-toggle')) {
                    document.querySelectorAll('.dropdown-content').forEach(content => {
                        content.classList.remove('show');
                    });
                }
            });
        </script>
    </body>
</html>
