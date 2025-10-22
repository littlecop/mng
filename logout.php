<?php
require __DIR__ . '/includes/init.php';

logout_user();
add_flash('success', 'Anda telah keluar.');

redirect('index.php');
