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
    public function createView( $code = false, $go = false ) {
        global $config;
        var_dump( $_GET );

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
        $gitHub = $serviceFactory->createService( 'GitHub', $credentials, $storage, array( 'user' ) );

        if ( $code !== false ) {
            $gitHub->requestAccessToken( $code );

            $result = json_decode( $gitHub->request( 'user/emails' ), true );

            echo 'The first email on your github account is ' . $result[ 0 ];
        }
        else if ( !empty( $_GET[ 'go' ] ) && $_GET[ 'go' ] === 'go' ) {
            $url = $gitHub->getAuthorizationUri();
            header( 'Location: ' . $url );

        }
        else {
            $url = $currentUri->getRelativeUri() . '?go=go';
            echo "<a href='$url'>Login with Github!</a>";
        }
    }
}
