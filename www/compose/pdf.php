<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'..');
    require_once 'config.php';
    require_once 'lib.php';

    require_once 'PEAR.php';
    require_once 'Mail.php';
    require_once 'Mail/mail.php';
    require_once 'Mail/mime.php';

    $db = mysql_connect(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD);
    mysql_select_db(MYSQL_DATABASE, $db);
    $ctx = new Context($db);
    
    $recipient_id = $_GET['id'];
    
    $filename = save_pdf($ctx, $recipient_id, 'php://input');
    
    if(is_null($filename))
    {
        $ctx->close();

        header('HTTP/1.1 500');
        die("Failed to create PDF file.\n");
    }
    
    $sentmail = send_mail($ctx, $recipient_id, $filename);
    
    if(PEAR::isError($sentmail))
    {
        $ctx->close();

        header('HTTP/1.1 500');
        die("{$sentmail->msg}\n");
    }

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

?>
