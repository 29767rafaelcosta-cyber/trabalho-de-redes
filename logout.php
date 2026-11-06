<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();
set_flash('info', 'Terminou a sua sessão com segurança. Até breve!');
header('Location: ' . url('index.php'));
exit;

