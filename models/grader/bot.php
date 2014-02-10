<?php
    class GraderBot {
        public $curlConnectionObject;
        protected $url;
        public $user;
        public $errors = array();
        public $version;
        public $name;

        public function __construct( $user ) {
            $this->curlConnectionObject = new CurlConnection();
            $this->user = $user;
            $this->url = $user->boturl;
        }
        protected function httpRequest( $endpoint = '', $method = 'view', $data = array() ) {
            switch ( $method ) {
                case 'create':
                case 'delete':
                case 'update':
                    $method = 'POST';
                    break;
                case 'view':
                case 'listing':
                    $method = 'GET';
                    break;
            }
            $ch = $this->curlConnectionObject;

            $url = $this->url . '/' . $endpoint;
            if ( $method == 'GET' ) {
                $parts = [];
                foreach ( $data as $key => $value ) {
                    $value = urlencode( $value );
                    $parts[] = "$key=$value";
                }
                $queryString = implode( '&', $parts );
                $url .= '?' . $queryString;
            }

            $ch->setOpt( CURLOPT_URL, $url );
            $ch->setOpt( CURLOPT_RETURNTRANSFER, 1 );

            if ( $method == 'POST' ) {
                $ch->setOpt( CURLOPT_POST, 1 );
                $ch->setOpt( CURLOPT_POSTFIELDS, $data );
            }
            $ch->exec();

            return $ch;
        }
        public function sendInitiateRequest() {
            try {
                $ch = $this->httpRequest( 'bot', 'create' );
            }
            catch ( CurlException $e ) {
                $this->errors[] = [
                    CURLE_COULDNT_RESOLVE_HOST => 'initiate_could_not_resolve',
                    CURLE_COULDNT_CONNECT => 'initiate_could_not_connect'
                ][ $e->error ];
                throw new GraderBotException();
            }

            if ( $ch->responseCode !== 200 ) {
                $this->errors[] = 'initiate_http_code_not_ok';
                throw new GraderBotException();
            }

            $decodedResponse = json_decode( $ch->response );
            if ( $decodedResponse === null ) {
                $this->errors[] = 'initiate_invalid_json';
                throw new GraderBotException();
            }
            $requiredAttributes = [ 'botname', 'version', 'username' ];
            foreach ( $requiredAttributes as $attribute ) {
                if ( !isset( $decodedResponse->$attribute ) ) {
                    $this->errors[] = 'initiate_' . $attribute . '_not_set';
                    throw new GraderBotException();
                }
            }
            if ( count( ( array )$decodedResponse ) > count( $requiredAttributes ) ) {
                $this->errors[] = 'initiate_additional_data';
                throw new GraderBotException();
            }
            if ( $this->user->username !== $decodedResponse->username ) {
                $this->errors[] = 'initiate_username_mismatch';
                throw new GraderBotException();
            }
            $this->version = $decodedResponse->version;
            $this->botname = $decodedResponse->botname;
        }
        public function sendGameRequest( $game ) {
            try {
                $ch = $this->httpRequest( 'game', 'create', GraderSerializer::gameRequestParams( $game ) );
            }
            catch ( CurlException $e ) {
                throw new GraderBotException();
            }
            $decodedResponse = json_decode( $ch->response );
            if ( $decodedResponse === null ) {
                $this->errors[] = 'game_invalid_json';
                throw new GraderBotException();
            }
            if ( count( ( array )$decodedResponse ) ) {
                $this->errors[] = 'game_additional_data';
                throw new GraderBotException();
            }
        }
        public function sendRoundRequest( $round ) {
            $gameid = $round->game->id;
            try {
                $ch = $this->httpRequest( "game/$gameid/round", 'create', GraderSerializer::roundRequestParams( $round ) );
            }
            catch ( CurlException $e ) {
                throw new GraderBotException();
            }
            $decodedResponse = json_decode( $ch->response );
            if ( $decodedResponse === null ) {
                $this->errors[] = 'round_invalid_json';
                throw new GraderBotException();
            }
            $requiredAttributes = [ 'creatureid', 'direction', 'desire' ];
            foreach ( $requiredAttributes as $attribute ) {
                if ( !isset( $decodedResponse->$attribute ) ) {
                    $this->errors[] = 'round_' . $attribute . '_not_set';
                    throw new GraderBotException();
                }
            }
            if ( count( ( array )$decodedResponse ) > count( $requiredAttributes ) ) {
                $this->errors[] = 'round_additional_data';
                throw new GraderBotException();
            }
        }
    }

    class GraderBotException extends Exception {}
?>