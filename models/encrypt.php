<?php
    function hashing( $password, $salt ) {
        return hash( 'sha256', $password . $salt );
    }

    function encrypt( $password ) {
        $salt = base64_encode( openssl_random_pseudo_bytes( 32 ) );
        $hash = hashing( $password, $salt );
        return array( "hash" => $hash, "salt" => $salt );
    }
?>
