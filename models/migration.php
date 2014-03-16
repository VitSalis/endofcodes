<?php
    abstract class Migration {
        protected static function migrate( $sql ) {
            global $config;

            require_once 'helpers/config.php';
            require_once 'models/database.php';
            require_once 'models/db.php';

            if ( isset( $GLOBALS[ 'env' ] ) ) {
                $env = $GLOBALS[ 'env' ];
            }
            else if ( !empty( getEnv( 'ENVIRONMENT' ) ) ) {
                $env = getEnv( 'ENVIRONMENT' );
            }
            else {
                $env = 'test';
            }
            dbInit();

            try {
                $res = db( $sql );
            }
            catch ( DBException $e ) {
                throw new MigrationException( $e );
            }
        } 
        public static function createLog( $name, $env ) {
            $path = 'database/migration/.history';
            if ( !$fh = fopen( $path, 'w' ) ) {
                throw new ModelNotFoundException();
            }
            fwrite( $fh, $name );
            fclose( $fh );
        }

        public static function getUnexecuted( $env ) {
            try {
                $last = self::findLast( $env );
            }
            catch ( ModelNotFoundException $e ) {
            }
            $migrations = self::findAll();
            $key = array_search( $last, $migrations );

            return $migrations;
        }

        public static function findLast( $env = 'development' ) {
            $log = file_get_contents( 'database/migration/.history' );
            if ( empty( $log ) ) {
                throw new ModelNotFoundException();
            }
            return $log;
        }
            
        public static function findAll() {
            $array = [];
            $handle = opendir( 'database/migration/' );
            while ( false !== ( $entry = readdir( $handle ) ) ) {
                if ( $entry != "." && $entry != ".." && $entry != ".history" ) {
                    $array[] = $entry;
                }
            }
            asort( $array );
            return $array;
        }

        public static function addField( $table, $field, $description ) {
            self::migrate( 
                "ALTER TABLE
                    $table
                ADD COLUMN
                    $field $description;"
            );
        }
 
        public static function alterField( $table, $oldName, $newName, $description ) {
            self::migrate(
                "ALTER TABLE
                    $table
                CHANGE
                    $oldName $newName $description;"
            );
        }
    
        public static function dropField( $table, $field ) {
            self::migrate(
                "ALTER TABLE
                    $table
                DROP COLUMN
                    $field;"
            );
        }

        public static function dropTable( $table ) {
            self::migrate(
                "DROP TABLE 
                    $table;"
            ); 
        }

        public static function dropPrimaryKey( $table ) {
            self::migrate(
                "ALTER TABLE
                    $table
                DROP PRIMARY KEY;"
            );
        } 

        public static function addPrimaryKey( $table, $name, $columns = [] ) {
            $columns = implode( ',', $columns );
            self::migrate(
                "ALTER TABLE
                    $table
                ADD CONSTRAINT $name PRIMARY KEY ( $columns );"
            );
        }

        public static function dropIndex( $table, $name ) {
            self::migrate(
                "ALTER TABLE
                    $table
                DROP INDEX
                    $name;"
            );
        }

        public static function createTable( $tableName, $fields = [], $keys = [] ) {
            $attributes = [];
            foreach ( $fields as $field => $description ) {
                if ( !empty( $field ) || !empty( $description ) ) {
                    $attributes[] = "$field $description";
                }
            }
            if ( !empty( $keys ) ) {
                $args = [];
                foreach ( $keys as $key ) {
                    if ( $key[ 'type' ] == 'unique' || $key[ 'type' ] == 'primary' ) {
                        $type = strtoupper( $key[ 'type' ] );
                        if ( is_array( $key[ 'field' ] ) ) {
                            $fields = implode( ',', $key[ 'field' ] );
                            if ( isset( $key[ 'name' ] ) ) {
                                $name = $key[ 'name' ];
                                $args[] = "CONSTRAINT $name $type KEY ( $fields )"; 
                            }
                            else {
                                $args[] = "$type KEY ( $fields )";
                            }
                        }
                        else {
                            $field = $key[ 'field' ];
                            $args[] = "$type KEY ( $field )";
                        }
                    }
                    if ( $key[ 'type' ] == 'index' ) {
                        if ( is_array( $key[ 'field' ] ) ) {
                            $fields = implode( ',', $key[ 'field' ] );
                            if ( isset( $key[ 'name' ] ) ) {
                                $name = $key[ 'name' ];
                            }
                            else {
                                $name = '';
                            }
                            $args[] = "INDEX $name ( $fields )"; 
                        }
                        else {
                            $args[] = "INDEX ( $field )";
                        }
                    }
                }
                $attributes = array_merge( $attributes, $args );
            } 
            $attributes = implode( ',', $attributes );
            self::migrate(
                "CREATE TABLE IF NOT EXISTS
                    $tableName (
                        $attributes
                    )
                ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
            );
        }
    }
?>
