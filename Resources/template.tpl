<?php
$message = 'Clear Opcache';

if (opcache_reset()) {
    $success = true;
    $message .= ' Opcode Cache: success';
}
else {
    $success = false;
    $message .= ' Opcode Cache: failure';
}

die(json_encode(array('success' => $success, 'message' => $message)));
