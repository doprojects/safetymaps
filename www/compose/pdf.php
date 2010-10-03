<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'..');
    require_once 'config.php';
    require_once 'lib.php';

    require_once 'PEAR.php';
    require_once 'Mail.php';
    require_once 'Mail/mail.php';
    require_once 'Mail/mime.php';
    
    function save_pdf(&$ctx, $recipient_id, $src_filename)
    {
        $recipient = get_recipient($ctx, $recipient_id);
        $map = get_map($ctx, $recipient['map_id'], false);
        
        $map_dirname = dirname(__FILE__)."/../files/{$map['id']}";
        @mkdir($map_dirname);
        @chmod($map_dirname, 0775);
        
        $pdf_dirname = "{$map_dirname}/{$recipient['id']}";
        @mkdir($pdf_dirname);
        @chmod($pdf_dirname, 0775);
        
        $pdf_filename = "{$pdf_dirname}/{$map['properties']['paper']}-{$map['properties']['format']}.pdf";
        $pdf_content = file_get_contents($src_filename);
    
        $fp = fopen($pdf_filename, 'w');
        fwrite($fp, $pdf_content);
        fclose($fp);
        chmod($pdf_filename, 0664);
        
        return realpath($pdf_filename);
    }
    
    function send_mail(&$ctx, $recipient_id, $pdf_filename)
    {
        $recipient = get_recipient($ctx, $recipient_id, true);
        $map = get_map($ctx, $recipient['map_id'], false);
        $user = get_user($ctx, $map['properties']['user']['id'], true);
        
        $base_dirname = dirname(dirname(__FILE__));
        $base_urlpath = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $pdf_href = 'http://'.$_SERVER['SERVER_NAME'].$base_urlpath.substr($pdf_filename, strlen($base_dirname));
        
        $mm = new Mail_mime("\n");
    
        $mm->setFrom("{$user['name']} <info@safety-maps.org>");
        $mm->setSubject("Safety Maps Test");
    
        $mm->setTXTBody("Made new map for {$recipient['name']} <{$recipient['email']}>: {$pdf_href}");
        $mm->setHTMLBody("Made new map for {$recipient['name']} ({$recipient['email']}): {$pdf_href}");
    
        $body = $mm->get();
        $head = $mm->headers(array('To' => $recipient['email'],
                                   'Reply-To' => $user['email']));
    
        $m =& Mail::factory('smtp', array('auth' => true,
                                          'host' => SMTP_HOST,
                                          'port' => SMTP_PORT,
                                          'username' => SMTP_USER,
                                          'password' => SMTP_PASS));
        
        return $m->send($recipient['email'], $head, $body);
    }

    $db = mysql_connect(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD);
    mysql_select_db(MYSQL_DATABASE, $db);
    $ctx = new Context($db);
    
    $recipient_id = $_GET['id'];
    
    $filename = save_pdf($ctx, $recipient_id, 'php://input');
    
    if(!file_exists($filename))
    {
        $ctx->close();

        header('HTTP/1.1 500');
        die("no {$filename}\n");
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
