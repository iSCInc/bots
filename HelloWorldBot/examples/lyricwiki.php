<?php
/**
 * lyricwiki.php - Examples of using the bot classes for interacting with mediawiki.
 *
 *  (c) 2012      Sean - http://en.wikipedia.org/wiki/User:SColombo
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *  Developers (add yourself here if you worked on the code):
 *      Sean    - [[User:SColombo]] - Wrote the lyricwiki class
 **/

include 'botclasses.php';
 
// Wikipedia


// LyricWiki
//$lw = new lyricwiki();
$lw = new lyricwiki(null, null, "http://lyrics.sean.wikia-dev.com/api.php"); // using my devbox because the php format support isn't going to be released until the wednesday after this comment was written.

print "\n\n<h2>getSong</h2>\n";
print_r( $lw->getSong("Cake", "Dime") );

print "\n\n<h2>getArtist</h2>\n";
print_r( $lw->getArtist("APC") );

print "\n\n<h2>getHometown</h2>\n\n";
$artist = "Mac Miller";
$hometown = $lw->getHometown($artist);
print "$artist is from:\n";
print "  country:  ".$hometown['country']."\n";
print "  state:    ".$hometown['state']."\n";
print "  hometown: ".$hometown['hometown']."\n";

print "\n\n<h2>getSotd</h2>\n\n";
print_r( $lw->getSotd() );

print "\n\n<h2>getTopSongs</h2>\n";
$topSongs = $lw->getTopSongs();
foreach($topSongs as $song){
	print "{$song['rank']}) {$song['artist']}:{$song['song']}\n";
	print "   {$song['url']}\n\n";
}

print "\n\n<h2>postSong</h2>\n";
$artist = "Test Artist";
$song = "Test Song";
$lyrics = "Test 1, 2, 3, 4.\nThis is just a test of botclasses.php's postSong() method which calls the [[w:c:api:LyricWiki_API|LyricWiki API]].\n";
$overwriteIfExists = 1;
$language = "English";
$onAlbums = array(
	"Big The Boss:How to Proof a Thesis (2004)",
	"Big The Boss:Summerday: A Perfect Day to Rap (2000)",
	"Test Artist:First Album (2006)",
	"Second Album (2006)",
);
print_r( $lw->postSong($artist, $song, $lyrics, $language, $onAlbums, $overwriteIfExists) );