<?php
// includes/footer.php

// Define a URL base caso ainda não esteja definida (segurança)
if (!isset($base_url)) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
        . "://" . $_SERVER['HTTP_HOST'] . "/anotaai-clone";
}
?>

    </main> <link rel="stylesheet" href="<?php echo $base_url; ?>/includes/footer.css?v=2">

    <footer class="site-footer">
        <div class="footer-grid">
            
            <div class="footer-col">
                <a href="<?php echo $base_url; ?>/user/index.php" class="footer-brand">Plata<span>Food</span></a>
                <p>
                    A plataforma de delivery que conecta você aos melhores sabores da cidade. 
                    Rápido, fácil e delicioso.
                </p>
                <div class="social-icons">
                    <a href="#" class="social-btn"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>

            <div class="footer-col">
                <h3>Navegação</h3>
                <ul class="footer-links">
                    <li><a href="<?php echo $base_url; ?>/user/index.php"><i class="fa-solid fa-angle-right"></i> Início</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right"></i> Restaurantes</a></li>
                    
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li><a href="<?php echo $base_url; ?>/user/perfil.php"><i class="fa-solid fa-angle-right"></i> Minha Conta</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_url; ?>/user/login.php"><i class="fa-solid fa-angle-right"></i> Entrar / Cadastrar</a></li>
                    <?php endif; ?>
                    
                    <li><a href="<?php echo $base_url; ?>/index.php" target="_blank"><i class="fa-solid fa-store"></i> Sou Parceiro</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>Fale Conosco</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fa-solid fa-envelope"></i> contato@platafood.com</a></li>
                    <li><a href="#"><i class="fa-solid fa-phone"></i> (11) 99999-9999</a></li>
                    <li><a href="#"><i class="fa-solid fa-location-dot"></i> São Paulo, Brasil</a></li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <strong>PlataFood</strong>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Fecha toasts de sucesso/erro após 4 segundos
            const alerts = document.querySelectorAll('.success, .error, .alert');
            if(alerts.length > 0) {
                setTimeout(() => {
                    alerts.forEach(el => {
                        el.style.transition = 'opacity 0.5s';
                        el.style.opacity = '0';
                        setTimeout(() => el.remove(), 500);
                    });
                }, 4000);
            }
        });
    </script>

</body>
</html>