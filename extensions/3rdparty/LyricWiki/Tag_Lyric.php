<?php

#
# Simple lyric parser extension for mediawiki.
# Written by Trevor Peacock, 1 June 2006
# version 0.2.1
# Tested on MediaWiki 1.6devel, PHP 5.0.5 (apache2handler)
#
# developed to support the notation of lyrics in mediawiki.
# see http://lyrics.wikia.com/User:TrevorP/Notation
#
# Features:
#  * Allows basic lyric notation
#  * Optional CSS styling embedded in every page
#  * CSS styling not embedded in meta tage, rather @import-ed from extension file
#
# To install, copy this file into "extensions" directory, and add
# the following line to the end of LocalSettings.php
# (above the  ? >  )
#
#   require("extensions/lyric.php");
#

################################################################################
# Functions
#
# This section has no configuration, and can be ignored.
#

require_once 'extras.php';

################################################################################
# Extension Credits Definition
#
# This section has no configuration, and can be ignored.
#

if(isset($wgScriptPath))
{
$wgExtensionCredits["parserhook"][]=array(
  'name' => 'Lyric Extension',
  'version' => '0.2.1',
  'url' => 'http://wiki.peacocktech.com/wiki/LyricExtension',
  'author' => '[http://about.peacocktech.com/trevorp/ Trevor Peacock]',
  'description' => 'Adds features allowing easy notation of lyrics in mediawiki' );
}

################################################################################
# Lyric Render Section
#
# This section has no configuration, and can be ignored.
#
# This section renders <lyric> tags. It forces a html break on every line,
# and styles the section with a css id.
# this id can either be in the mediawiki css files, or defined by the extension
#

if(isset($wgScriptPath))
{
	#Instruct mediawiki to call LyricExtension to initialise new extension
	$wgExtensionFunctions[] = "lyricTag";
	$wgHooks['ParserFirstCallInit'][] = "lyricTag_InstallParser";
	$wgHooks['BeforePageDisplay'][] = "lyricTagCss";
}

#Install extension
function lyricTag()
{
	// Keep track of whether this is the first <lyric> tag on the page - this is to prevent too many Ringtones ad links.
	global $wgFirstLyricTag;
	$wgFirstLyricTag = true;
}

function lyricTag_InstallParser( $parser ) {
	#install hook on the element <lyric>
	$parser->setHook("lyric", "renderLyricTag");
	$parser->setHook("lyrics", "renderLyricTag");
	return true;
}

function lyricTagCss($out)
{
	$css = <<<DOC
.lyricbox
{
	padding: 1em 1em 0;
	border: 1px solid silver;
	color: black;
	background-color: #ffffcc;
}
.lyricsbreak{
	clear:both;
}
DOC
;
	$out->addScript("<style type='text/css'>/*<![CDATA[*/\n".$css."\n/*]]>*/</style>");

	return true;
}

function renderLyricTag($input, $argv, $parser)
{
	#make new lines in wikitext new lines in html
	$transform=str_replace(array("\r\n", "\r","\n"), "<br/>", trim($input));

	$isInstrumental = (strtolower(trim($transform)) == "{{instrumental}}");

	// If appropriate, build ringtones links.
	GLOBAL $wgFirstLyricTag, $wgLyricTagDisplayRingtone;
	$ringtoneLink = "";

	// For whatever reason, the links were not showing up after page-edits.
	// It seems that the parser is called multiple-times when saving a page-edit.
	$wgFirstLyricTag = true;

	// NOTE: we put the link here even if wfAdPrefs_doRingtones() is false since ppl all share the article-cache, so the ad will always be in the HTML.
	// If a user has ringtone-ads turned off, their CSS will make the ad invisible.
	if( !empty( $wgLyricTagDisplayRingtone ) && $wgFirstLyricTag ){
		GLOBAL $wgTitle, $wgExtensionsPath;
		$imgPath = "$wgExtensionsPath/3rdparty/LyricWiki";
		$artist = $wgTitle->getDBkey();
		$colonIndex = strpos("$artist", ":");
		$songTitle = $wgTitle->getText();
		$artistLink = $artist;
		$songLink = $songTitle;
		if($colonIndex !== false){
			$artist = substr($artist, 0, $colonIndex);
			$songTitle = substr($songTitle, $colonIndex+1);

			$artistLink = str_replace(" ", "+", $artist);
			$songLink = str_replace(" ", "+", $songTitle);
		}
		$artistLink = str_replace("_", "+", $artistLink);
		$songLink = str_replace("_", "+", $songLink);
		$href = "<a href='http://www.ringtonematcher.com/co/ringtonematcher/02/noc.asp?sid=WILWros&amp;artist=".urlencode($artistLink)."&amp;song=".urlencode($songLink)."' rel='nofollow' target='_blank'>";
		$ringtoneLink = "";
		$ringtoneLink = "<div class='rtMatcher'>";
		$ringtoneLink.= "$href<img src='" . $imgPath . "/phone_left.gif' alt='phone' width='16' height='17'/> ";
		$ringtoneLink.= "Send \"$songTitle\" Ringtone to your Cell";
		$ringtoneLink.= " <img src='" . $imgPath . "/phone_right.gif' alt='phone' width='16' height='17'/></a>";
		$ringtoneLink.= "</div>";
		$wgFirstLyricTag = false;
	}

	$transform = $parser->parse($transform, $parser->mTitle, $parser->mOptions, false, false)->getText();

	#parse embedded wikitext
	$retVal = "";
	$retVal.= gracenote_getNoscriptTag();
	$retVal.= "<div class='lyricbox'>";
	$retVal.= ($isInstrumental?"":$ringtoneLink); // if this is an instrumental, just a ringtone link on the bottom is plenty.
	$retVal.= gracenote_obfuscateText($transform);
	$retVal.= $ringtoneLink;
	$retVal.= "<div class='lyricsbreak'></div>\n"; // so that we can have stuff in the box (like videos & awards) even if the lyrics are short.
	$retVal.= "</div>";

	// Tell the Google Analytics code that this view was for non-Gracenote lyrics.
	$retVal.= gracenote_getAnalyticsHtml(GRACENOTE_VIEW_OTHER_LYRICS);

	return $retVal;
}

?>
