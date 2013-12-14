<?php
    include_once 'models/encrypt.php';
    include_once 'models/country.php';
    include_once 'models/image.php';

    class User extends ActiveRecordBase {
        public $id;
        public $username;
        public $password;
        public $email;
        public $country;
        public $image;
        public $salt;
        public $dateOfBirth;
        protected $dob;
        protected $tableName = 'users';

        public static function findByUsername( $username ) {
            try {
                $user = dbSelectOne( 'users', array( 'id' ), compact( "username" ) );
            }
            catch ( DBException $e ) {
                throw new ModelNotFoundException();
            }
            return new User( $user[ 'id' ] );
        }

        public function __construct( $id = false ) {
            if ( $id ) {
                // existing active record object
                $user_info = dbSelectOne( 'users', array( 'dob', 'username', 'email', 'countryid', 'avatarid' ), compact( "id" ) );
                $this->username = $user_info[ 'username' ];
                $this->email = $user_info[ 'email' ];
                $this->country = new Country( $user_info[ 'countryid' ] );
                $this->image = new Image( $user_info[ 'avatarid' ] );
                $this->id = $id;
                $this->dob = $user_info[ 'dob' ];
                $this->exists = true;
            }
        }

        protected function validate() {
            global $config;

            if ( empty( $this->username ) ) {
                throw new ModelValidationException( 'username_empty' );
            }
            if ( strpos( $this->username, ' ' ) || preg_match( '#[^a-zA-Z0-9]#', $this->username ) ) {
                throw new ModelValidationException( 'username_invalid' );
            }
            if ( empty( $this->password ) && !$this->exists ) {
                throw new ModelValidationException( 'password_empty' );
            }
            if ( !$this->exists && empty( $this->email ) ) {
                throw new ModelValidationException( 'email_empty' );
            }
            if ( isset( $this->password ) && strlen( $this->password ) < $config[ 'pass_min_len' ] ) {
                if ( $this->exists ) {
                    throw new ModelValidationException( 'password_new_small' );
                }
                throw new ModelValidationException( 'password_small' );
            }
            if ( !filter_var( $this->email, FILTER_VALIDATE_EMAIL ) ) {
                throw new ModelValidationException( 'email_invalid' );
            }
        }

        protected function create() {
            // when a user is created he doesn't have an image, so avatarid is by default 0 
            $day = intval( $this->dateOfBirth[ 'day' ] ); 
            $month = intval( $this->dateOfBirth[ 'month' ] );
            $year = intval( $this->dateOfBirth[ 'year' ] );
            if ( !checkdate( $day, $month, $year ) ) {
                $day = $month = $year = 0;
            }
            $dob = $this->dob = $year . '-' . $month . '-' . $day; 
            $username = $this->username;
            $password = $this->password;
            $email = $this->email;
            $countryid = $this->country->id;
            $array = encrypt( $password );
            $password = $array[ 'hash' ];
            $salt = $array[ 'salt' ];
            try {
                $res = dbInsert( 
                    'users', 
                    compact( "username", "password", "email", "salt", "countryid", "dob" )
                );
            }
            catch ( DBException $e ) {
                try {
                    $other_user = User::findByUsername( $username );
                    throw new ModelValidationException( 'username_used' );
                }
                catch ( ModelNotFoundException $e ) {
                    throw new ModelValidationException( 'email_used' );
                }
            }
            $this->exists = true;
            $this->id = $res;
        }

        protected function update() {
            $id = $this->id;
            if ( isset( $this->password ) ) {
                $array = encrypt( $this->password );
                $this->password = $array[ 'hash' ];
                $this->salt = $array[ 'salt' ];
            }
            $email = $this->email;
            $dob = $this->dob;
            $countryid = $this->country->id;
            $avatarid = $this->image->id;
            try {
                $res = dbUpdate(
                    'users',
                    compact( "email", "password", "salt", "countryid", "avatarid", "dob" ),
                    compact( "id" )
                );
            }
            catch ( DBException $e ) {
                throw new ModelValidationException( 'email_used' );
            }
        }

        public function authenticatesWithPassword( $password ) {
            $username = $this->username;
            $row = dbSelect(
                'users',
                array( 'id', 'password', 'salt' ),
                compact( "username" )
            );
            if ( !empty( $row ) ) {
                if ( $row[ 0 ][ 'password' ] == hashing( $password, $row[ 0 ][ 'salt' ] ) ) {
                    return true;
                }
            }
            return false;
        }
    }
?>