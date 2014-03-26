<?php

/**
 * Example of retrieving an authentication token of the Github service
 *
 * PHP version 5.4
 *
 * @author     David Desberg <david@daviddesberg.com>
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2012 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

use OAuth\OAuth2\Service\GitHub;
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;

class GithubController extends ControllerBase {
    protected function getGithub() {
        global $config;

        require_once 'OAuth/bootstrap.php';

        $uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
        $currentUri = $uriFactory->createFromSuperGlobalArray( $_SERVER );
        $currentUri->setQuery( '' );
        $serviceFactory = new \OAuth\ServiceFactory();

        $storage = new Session();

        $credentials = new Credentials(
            $config[ 'github' ][ 'id' ],
            $config[ 'github' ][ 'secret' ],
            $config[ 'base' ] . 'github/create'
        );
        $github = $serviceFactory->createService( 'GitHub', $credentials, $storage, array( 'user' ) );
        return $github;
    }
    public function create( $code = false ) {
        $github = $this->getGithub();
        $github->requestAccessToken( $code );

        $result = json_decode( $github->request( 'user/emails' ), true );

        echo 'The first email on your github account is ' . $result[ 0 ];
    }
    public function createView( $code = false ) {
        $github = $this->getGithub();

        $url = $github->getAuthorizationUri();
        require_once 'views/github/create.php';
    }
}
