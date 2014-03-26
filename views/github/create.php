<?php
    include 'views/header.php';
    if ( $code !== false ) {
        $form = new Form( 'github', 'create' );
        $form->id = 'github-form';
        $form->output( function( $self ) use( $code ) {
            $self->createInput( 'hidden', 'code', 'code', $code );
        } );
        ?><script>
            $( '#github-form' ).submit();
        </script><?php
    }
    else {
        ?><a href="<?php
            echo $url;
        ?>">Login with github</a><?php
    }
    include 'views/footer.php';
?>
