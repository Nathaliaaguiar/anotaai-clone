</main> <footer class="site-footer">
        <div class="container">
            <div class="footer-content">

                <div class="footer-section sobre">
                    <h3 class="footer-logo">PlataFood</h3>
                    <p>Sua plataforma de delivery favorita. Peça o melhor da sua cidade e receba no conforto da sua casa.</p>
                    <div class="contato">
                        <span><i class="fas fa-phone"></i> &nbsp; (21) 99999-8888</span>
                        <span><i class="fas fa-envelope"></i> &nbsp; contato@platafood.com.br</span>
                    </div>
                </div>

                <div class="footer-section links">
                    <h4>Links Rápidos</h4>
                    <ul>
                        <li><a href="index.php">Cardápio</a></li>
                        <li><a href="carrinho.php">Meu Carrinho</a></li>
                        <li><a href="perfil.php">Minha Conta</a></li>
                        <li><a href="#">Política de Privacidade</a></li>
                    </ul>
                </div>

                <div class="footer-section horarios">
                    <h4>Horário de Funcionamento</h4>
                    <?php
                        // Busca os horários do banco
                        $stmt_horarios = $pdo->query("SELECT * FROM horarios_funcionamento ORDER BY dia_semana ASC");
                        $horarios = $stmt_horarios->fetchAll();
                        $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                    ?>
                    <ul class="lista-horarios">
                        <?php foreach ($horarios as $horario): ?>
                            <li>
                                <span class="dia"><?php echo $dias_semana[$horario['dia_semana']]; ?></span>
                                <?php if ($horario['ativo']): ?>
                                    <span class="hora"><?php echo date('H:i', strtotime($horario['horario_abertura'])) . ' - ' . date('H:i', strtotime($horario['horario_fechamento'])); ?></span>
                                <?php else: ?>
                                    <span class="hora fechado">Fechado</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> PlataFood | Todos os direitos reservados.
        </div>
    </footer>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="/anotaai-clone/js/script.js"></script>
</body>
</html>