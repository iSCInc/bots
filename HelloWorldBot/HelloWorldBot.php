<?php
/*
 * HelloWorldBot.php
 * This bot edits MyWiki:Sandbox.
 *
 * @author Suriyaa Kudo
 * License MIT
/*
 
/* Setup my classes. */
include( 'botclasses.php' );
$wiki      = new mywiki;
$wiki->url = "http://suriyaakudo.bplaced.net/wiki/api.php";
$wiki->setUserAgent( 'User-Agent: HelloWorldBot (http://suriyaakudo.bplaced.net/wiki/index.php?title=User:HelloWorldBot)' );
 
/* All the login stuff. */
$user = 'REMOVED';
$pass = 'REMOVED';
$wiki->login( $user, $pass );
 
/* Test edit. */
$page = 'MyWiki:Sandbox';
$content = 'Hello, world!';
$summary = 'This is a sample bot edit.';
$wiki->edit( $page, $content, $summary );