<?php
    interface GraderBotInterface {
        public function __construct( $user );
        public function sendInitiateRequest();
        public function sendGameRequest( $game );
        public function sendRoundRequest( $round );
    }

    class GraderBot implements GraderBotInterface {
        public $curlConnectionObject;
        protected $url;
        public $user;
        public $errors = array();
        public $version;
        public $name;
        public $game;

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
                $errorMap = [
                    CURLE_COULDNT_RESOLVE_HOST => 'initiate_could_not_resolve',
                    CURLE_COULDNT_CONNECT => 'initiate_could_not_connect',
                    CURLE_URL_MALFORMAT => 'initiate_malformed_url'
                ];
                if ( isset( $errorMap[ $e->error ] ) ) {
                    $this->errors[] = $errorMap[ $e->error ];
                }
                else {
                    throw $e;
                }

                throw new GraderBotException( end( $this->errors ) );
            }

            if ( $ch->responseCode !== 200 ) {
                $this->errors[] = 'initiate_http_code_not_ok';
                throw new GraderBotException( end( $this->errors ) );
            }

            $decodedResponse = json_decode( $ch->response );
            if ( $decodedResponse === null ) {
                $this->errors[] = 'initiate_invalid_json';
                throw new GraderBotException( end( $this->errors ) );
            }
            $requiredAttributes = [ 'botname', 'version', 'username' ];
            foreach ( $requiredAttributes as $attribute ) {
                if ( !isset( $decodedResponse->$attribute ) ) {
                    $this->errors[] = 'initiate_' . $attribute . '_not_set';
                    throw new GraderBotException( end( $this->errors ) );
                }
            }
            if ( count( ( array )$decodedResponse ) > count( $requiredAttributes ) ) {
                $this->errors[] = 'initiate_additional_data';
                throw new GraderBotException( end( $this->errors ) );
            }
            if ( $this->user->username !== $decodedResponse->username ) {
                $this->errors[] = 'initiate_username_mismatch';
                throw new GraderBotException( end( $this->errors ) );
            }
            $this->version = $decodedResponse->version;
            $this->botname = $decodedResponse->botname;
        }
        public function sendGameRequest( $game ) {
            try {
                $ch = $this->httpRequest( 'game', 'create', GraderSerializer::gameRequestParams( $game ) );
            }
            catch ( CurlException $e ) {
                $this->errors[] = $e->error;
                throw new GraderBotException( end( $this->errors ) );
            }
            $decodedResponse = json_decode( $ch->response );
            if ( $decodedResponse === null ) {
                $this->errors[] = 'game_invalid_json';
                throw new GraderBotException( end( $this->errors ) );
            }
            if ( count( ( array )$decodedResponse ) ) {
                $this->errors[] = 'game_additional_data';
                throw new GraderBotException( end( $this->errors ) );
            }
        }
        public function sendRoundRequest( $round ) {
            $gameid = $round->game->id;
            try {
                $ch = $this->httpRequest( "game/$gameid/round", 'create', GraderSerializer::roundRequestParams( $round ) );
            }
            catch ( CurlException $e ) {
                throw new GraderBotException( end( $this->errors ) );
            }
            $decodedResponse = json_decode( $ch->response );
            if ( $decodedResponse === null ) {
                $this->errors[] = 'round_invalid_json';
                throw new GraderBotException( end( $this->errors ) );
            }
            $requiredAttributes = [ 'creatureid', 'direction', 'action' ];
            foreach ( $requiredAttributes as $attribute ) {
                foreach ( $decodedResponse->intent as $creatureIntent) {
                    if ( !is_object( $creatureIntent ) ) {
                        $this->errors[] = 'round_response_not_object';
                        throw new GraderBotException( end( $this->errors ) );
                    }
                    if ( !isset( $creatureIntent->$attribute ) ) {
                        $this->errors[] = 'round_' . $attribute . '_not_set';
                        throw new GraderBotException( end( $this->errors ) );
                    }
                }
            }
            if ( count( ( array )$decodedResponse->intent[ 0 ] ) > count( $requiredAttributes ) ) {
                $this->errors[] = 'round_additional_data';
                throw new GraderBotException( end( $this->errors ) );
            }
            $collection = [];
            foreach ( $decodedResponse->intent as $creatureIntentData ) {
                $round = $this->game->getCurrentRound();
                if ( $round->creatures[ $creatureIntentData->creatureid ]->user->id !== $this->user->id ) {
                    $this->errors[] = 'round_intent_not_own_creature';
                    throw new GraderBotException( end( $this->errors ) );
                }
                $creature = new Creature();
                $creature->id = $creatureIntentData->creatureid;
                try {
                    $action = actionStringToConst( $creatureIntentData->action );
                }
                catch ( ModelNotFoundException $e ) {
                    $this->errors[] = 'round_action_invalid';
                    throw new GraderBotException( end( $this->errors ) );
                }
                try {
                    $direction = directionStringToConst( $creatureIntentData->direction );
                }
                catch ( ModelNotFoundException $e ) {
                    $this->errors[] = 'round_direction_invalid';
                    throw new GraderBotException( end( $this->errors ) );
                }
                $creature->intent = new Intent( $action, $direction );
                $collection[] = $creature;
            }
            return $collection;
        }
    }

    class GraderBotException extends Exception {
        public $error;
        public function __construct( $error ) {
            $this->error = $error;
            parent::__construct( "Grader bot error: $error" );
        }
    }
?>
