<?php
/**
 * lyricwiki.php - An extension to botclasses.php
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
 *      Sean    - [[User:SColombo]] - Lead developer.
 **/
 
/**
 * This class extends the wiki class to provide an high level API for the most commons actions.
 * @author Sean
 */
class lyricwiki extends extended
{
	/**
	 * Constructor. Note that the parameters are in different order (primarily because it
	 * is very unlikely the user will want to change the URL of LyricWiki, and fairly likely
	 * that they may want to set a username/password).
	 *
	 * If not logging in, the caller can safely call this with no parameters.
	 */
	function __construct ($hu=null, $hp=null, $url='http://lyrics.wikia.com/api.php') {
		parent::__construct($url,$hu,$hp);
	}

	/**
	 * Returns basic info about a song as well as a fair-use snippet of
	 * lyrics and a link to the LyricWiki page where the full lyrics can
	 * be viewed (full lyrics are no longer available via the API because
	 * of licensing issues - publishers won't allow that for free).
	 *
	 * @return associative array containing the song data. (TODO: Document what happens on No Match)
	 */
	function getSong($artist, $song){
		return $this->query('?action=lyrics&func=getSong&fmt=php&artist='.urlencode($artist).'&song='.urlencode($song));
	} // end getSong()

	/**
	 * Returns basic info about an artist as well as their
	 * entire discography.
	 *
	 * @param - artist - a string containing the name of the artist (spaces or underscores are fine)
	 * @return an associative array containing the artist data (TODO: Document what happens on No Match)
	 */
	function getArtist($artist){
		return $this->query('?action=lyrics&func=getArtist&fmt=php&artist='.urlencode($artist));
	} // end getArtist()
	
	/**
	 * Returns hometown information (country, state/province, hometown) for
	 * the given artist if it can be found.
	 *
	 *
	 * @param - artist - a string containing the name of the artist (spaces or underscores are fine)
	 * @return an associative array whose keys are 'country', 'state', and 'hometown'. If the
	 * artist's hometown could not be found, then the values corresponding to those keys will
	 * be empty strings.
	 */
	function getHometown($artist){
		return $this->query('?action=lyrics&func=getHometown&fmt=php&artist='.urlencode($artist));
	} // end getHometown()
	
	/**
	 * Gets information about the Song Of The Day (SOTD).
	 *
	 * @return an associative array representing the current Song of the Day. The
	 * fields returned include {artist, song, lyrics (just a snippet), url of song,
	 * page_namespace, page_id, isOnTakedownList, nominatedBy, reason}
	 */
	function getSotd(){
		return $this->query('?action=lyrics&func=getSotd&fmt=php');
	} // end getSotd()

	/**
         * Returns information about the currently most-popular songs.
	 *
	 * @return an array containing associative arrays of the data for each top song.
	 */
	function getTopSongs(){
		return $this->query('?action=lyrics&func=getTopSongs&fmt=php');
	} // end getTopSongs()

	/**
	 * Allows easy submission of lyrics for a new song to LyricWiki.
	 *
	 * @param artist - the artist of the song
	 * @param song - the name of the song (following page naming conventions: http://lyrics.wikia.com/LW:PN ).
	 * @param lyrics - the full lyrics to submit
	 * @param language - optional string for the language (the language name should be in English - eg: "Swedish" not "Svenska").
	 * @param onAlbums - an array which contains strings which are album titles.
	 * @param overwriteIfExists - true means that this page data (the lyrics and onAlbums) will overwrite
	 *                     the page if it already exists. false means that nothing will be changed if the
	 *                     page already exists.
	 */
	function postSong($artist, $song, $lyrics, $language="", $onAlbums=array(), $overwriteIfExists=false){
		$params = array(
            		'artist' => $artist,
            		'song' => $song,
           		 'lyrics' => $lyrics,
			'overwriteIfExists' => ($overwriteIfExists ? "1" : "0"),
        	);
		if(!empty($language)){
			$params['language'] = $language;
		}
		if(count($onAlbums) > 0){
			$params['onAlbums'] = implode("|", $onAlbums);
		}
        	return $this->query('?action=lyrics&func=postSong&fmt=php',$params);
	} // end postSong()

} // end class lyricwiki