<?php
    include_once 'models/user.php';
    include_once 'models/country.php';
    
    class UserTest extends UnitTest {
        public function run() {
            $this->testCreate();
            $this->testDelete();
            $this->testPasswordChange();
            $this->testEmailChange();
            $this->testSetCountry();
            $this->testSetAge();
        }
        public function testCreate() {
            $user = new User();
            $user->username = 'pkakelas';
            $user->password = 'secret1234';
            $user->email = 'pkakelas@gmail.com';
            $user->save();
            $this->assertEquals( 'pkakelas', $user->username, 'Username must be the one associated during creation' );
            $this->assertEquals( 'pkakelas@gmail.com', $user->email, 'Email must be the one associated during creation' );
        }
        public function testDelete() {
        }
        public function testPasswordChange() {
            $user = User::findByUsername( 'pkakelas' );
            $password = $user->password;
            $user->password = 'newsecret1234';
            $user->save();
            if ( $user->authenticatesWithPassword( 'newsecret1234' ) ) { 
                $success = 1;
            }
            else {
                $success = 0;
            }
            $this->assertEquals( 1, $success, 'Password must be the one associated during update' );
        }
        public function testEmailChange() {
            $user = User::findByUsername( 'pkakelas' );
            $user->email = 'pkakelas2@gmail.com';
            $user->save();
            $this->assertEquals( 'pkakelas2@gmail.com', $user->email, 'Email must be the one associated during update' );
        }
        public function testSetCountry() {
            $user = User::findByUsername( 'pkakelas' );
            $user->country = new Country( 1 );
            $this->assertEquals( 1, $user->country->id, 'Country must be the one associated during update' );
        }
        public function testSetAge() {
        }
    }

    return new UserTest();
?>
