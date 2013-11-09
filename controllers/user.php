<?php
    class UserController {
        public static function create( $username = '', $password = '', $email = '' ) {
            include 'models/users.php';
            if ( !empty( $username ) && !empty( $password ) && !empty( $email ) ) {
                if ( strlen( $password ) <= 6 ) {
                    header( 'Location: index.php?resource=user&method=create&small_pass=yes' );
                    die();
                }
                $valid = User::validMail( $email );
                if ( !$valid ) {
                    header( 'Location: index.php?mail_notvalid=yes&resource=user&method=create' );
                    die();
                }
                if ( User::Exists( $username ) ) {
                    header( 'Location: index.php?user_used=yes&resource=user&method=create' );
                    die();
                }
                else if ( User::mailExists( $email ) ) {
                    header( 'Location: index.php?mail_used=yes&resource=user&method=create' );
                    die();
                }
                User::createUser( $username, $password, $email );
                $id = User::authenticateUser( $username, $password );
                $_SESSION[ 'userid' ] = $id; 
                $_SESSION[ 'username' ] = $username;
                header( 'Location: index.php?resource=dashboard&method=view' );
            }
            else {
                header( 'Location: index.php?empty=yes&resource=user&method=create' );
            }
        }

        public static function createView( $empty, $user_used, $small_pass, $mail_used, $mail_notvalid ) {
            include 'views/register.php';
        }
    }
?>
