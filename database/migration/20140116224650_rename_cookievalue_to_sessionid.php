<?php
    include 'migrate.php';

    migrate(
        array(
            'ALTER TABLE  
                `users` 
            CHANGE  
                `cookievalue` `sessionid` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;'
        )
    );
?>
