<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'..');
    require_once 'lib.php';

    $db = mysql_connect('localhost', 'safetymaps', 's4f3tym4ps');
    mysql_select_db('safetymaps', $db);
    $ctx = new Context($db);
    
    $recipient_id = $_GET['id'];
    
    mysql_query('BEGIN', $ctx->db);
    
    $finished = finish_recipient($ctx, $recipient_id);
    
    if($finished) {
        header('HTTP/1.1 200');
        mysql_query('COMMIT', $ctx->db);
    
    } else {
        header('HTTP/1.1 400');
        mysql_query('ROLLBACK', $ctx->db);
    }

    $ctx->close();

    echo strlen(file_get_contents('php://input'));

?>