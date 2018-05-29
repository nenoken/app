<?php

$wgHooks['ArticleSaveComplete'][] = 'AttributionCache::purgeArticleContribs';
$wgHooks['TitleMoveComplete'][] = 'AttributionCache::purgeArticleContribsAfterMove';

$wgExtensionCredits[ 'other' ][ ] = array(
	'name' => 'Answers',
	'author' => 'Wikia',
	'descriptionmsg' => 'answers-desc',
	'url' => 'https://github.com/Wikia/app/tree/dev/extensions/wikia/Answers',
	
);

// FIXME: Migrate require_once's to inclues for performance reasons.
require_once( __DIR__ . "/AnswersClass.php");
require_once( __DIR__ . "/AttributionCache.class.php");

$wgExtensionMessagesFiles['Answers'] = __DIR__ . '/Answers.i18n.php';

if(empty($wgAnswerHelperIDs)) {
	$wgAnswerHelperIDs = array( 0 /* anonymous */, 1172427 /* Wikia User */ );
}

$wgHooks['NewRevisionFromEditComplete'][] = 'incAnswerStats';
function incAnswerStats($article, $revision, $baseRevId) {
	// This was only used for the social code which has been removed.
	return true;
}


$wgAutoloadClasses['DefaultQuestion'] = __DIR__ . "/DefaultQuestion.php";
$wgAutoloadClasses['PrefilledDefaultQuestion'] = __DIR__ . "/PrefilledDefaultQuestion.php";
$wgAutoloadClasses['CreateQuestionPage'] = __DIR__ . "/SpecialCreateDefaultQuestionPage.php";
$wgSpecialPages['CreateQuestionPage'] = 'CreateQuestionPage';

function fnWatchHeldPage( $user ){
	global $wgOut, $wgCookiePrefix, $wgCookieDomain, $wgCookieSecure ;
	$watch_page = isset( $_COOKIE["{$wgCookiePrefix}wsWatchHold"] ) ? $_COOKIE["{$wgCookiePrefix}wsWatchHold"] : '';

	//user had clicked to watch page
	if( isset( $watch_page ) && $watch_page != "" ){
		$watched_title = Title::newFromDBKey( $watch_page );
		$user->addWatch( $watched_title );
		setCookie( "{$wgCookiePrefix}wsWatchHold", '', time() - 86400, '/', $wgCookieDomain,$wgCookieSecure );
		$wgOut->redirect( $watched_title->getFullURL( ) );
	}
}

$wgHooks['UserProfileBeginLeft'][] = 'wfUserProfileAskedQuestions';
function wfUserProfileAskedQuestions($user_profile) {
	global $wgOut;

	$html = "<div class=\"user-section-heading\">
		<div class=\"user-section-title\">
					".wfMsg("recent_asked_questions")."
				</div>
		</div>
		<div class=\"cleared\"></div>";

	$dbr = wfGetDB( DB_SLAVE );
	list ($page,$recentchanges) = $dbr->tableNamesN('page','recentchanges');
	$res = $dbr->select( "$page, $recentchanges ",
		array( 'page_title','rc_timestamp' ),
		array("page_id = rc_cur_id","rc_new" => 1, 'rc_user' => $user_profile->user_id, "page_namespace" => NS_MAIN, "page_is_redirect" => 0 ), __METHOD__,
		array("ORDER BY" => "rc_timestamp desc", "LIMIT" => 10 )
	);
	while ($row = $dbr->fetchObject( $res ) ) {
		$question_title = Title::makeTitle( NS_MAIN, $row->page_title );
		//question mark might already be there
		$question = $question_title->getText();
		if( $question[strlen($question)-1] != "?" ){
			$question = $question . "?";
		}
		$html .= "<div><a href=\"" . htmlspecialchars($question_title->getFullURL()) . "\">" . $question . "</a></div>";
	}
	$html .= "<p>";
	$wgOut->addHTML($html);
	return true;
}

$wgHooks['UserProfileBeginRight'][] = 'wfUserProfileAnsweredQuestions';
function wfUserProfileAnsweredQuestions($user_profile) {
	global $wgOut;

	$html = "<div class=\"user-section-heading\">
		<div class=\"user-section-title\">
					".wfMsg("recent_edited_questions")."
				</div>
		</div>
		<div class=\"cleared\"></div>";

	$dbr = wfGetDB( DB_SLAVE );
	list ($page,$recentchanges) = $dbr->tableNamesN('page','recentchanges');
	$res = $dbr->select( "$page, $recentchanges ",
		array( 'distinct(page_id)', 'page_title' ),
		array("page_id = rc_cur_id","rc_new" => 0, 'rc_user' => $user_profile->user_id, "page_namespace" => NS_MAIN, "page_is_redirect" => 0 ), __METHOD__,
		array("ORDER BY" => "rc_timestamp desc", "LIMIT" => 10 )
	);
	while ($row = $dbr->fetchObject( $res ) ) {
		$question_title = Title::makeTitle( NS_MAIN, $row->page_title );
		//question mark might already be there
		$question = $question_title->getText();
		if( $question[strlen($question)-1] != "?" ){
			$question = $question . "?";
		}
		$html .= "<div><a href=\"" . htmlspecialchars($question_title->getFullURL()) . "\">" . $question . "</a></div>";
	}
	$html .= "<p>";
	$wgOut->addHTML($html);
	return true;
}

//removes category [[Category:un-answered questions]] if any non-category contented is saved to the page
$wgHooks['EditPage::attemptSave'][] = 'fnMarkAsAnswered';
function fnMarkAsAnswered( $editpage) {
	global $wgRequest;

	$answered = Answer::getSpecialCategoryTag("answered");
	$unanswered = Answer::getSpecialCategoryTag("unanswered");

	if( !Answer::newFromTitle( $editpage->mTitle )->isQuestion(false,false) )return true;
	if( Title::newFromRedirect( $editpage->textbox1 ) != NULL )return true;

	if ( Answer::isMarkedForDeletion( $editpage->textbox1 ) ){
		$editpage->textbox1 = trim(str_ireplace( array($answered, $unanswered) , '', $editpage->textbox1 ));
		return true;
	}

	if ( Answer::isContentAnswered( $editpage->textbox1 ) ){
		$editpage->textbox1 = trim(str_ireplace( $unanswered , '', $editpage->textbox1 ));
		if( strpos( $editpage->textbox1, $answered ) === false ){
			$editpage->textbox1 = $editpage->textbox1 . "\n" . $answered;
		}
	} else {
		$editpage->textbox1 = trim(str_ireplace( $answered , '', $editpage->textbox1 ));
		if ( strpos( $editpage->textbox1, $unanswered ) === false ){
			$editpage->textbox1 = $unanswered . "\n" . $editpage->textbox1;
		}
	}

	$editpage->textbox1 = trim( $editpage->textbox1 );
	return true;
}

//1.13 version
$wgHooks['ExtendJSGlobalVars'][] = 'fnAddAnswerJSGlobalVariables';

//1.14 version
$wgHooks['MakeGlobalVariablesScript'][] = 'fnAddAnswerJSGlobalVariables';
function fnAddAnswerJSGlobalVariables(Array &$vars){

	global $wgTitle, $wgContLang;
	$vars['wgAskFormTitle'] = wfMsgForContent("ask_a_question");
	$vars['wgAskFormCategory'] = wfMsgForContent("in_category");
	$vars['wgAnswerMsg'] = wfMsg("answer_title");
	$vars['wgRenameMsg'] = wfMsg("movepagebtn");
	$vars['wgDeleteMsg'] = wfMsg("delete");
	$vars['wgSaveMsg'] = wfMsg("save");
	$vars['wgCategorizeMsg'] = wfMsg("categorize");
	$vars['wgCategorizeHelpMsg'] = wfMsg("categorize_help");
	$vars['wgNextPageMsg'] = wfMsg("next_page");
	$vars['wgPrevPageMsg'] = wfMsg("prev_page");
	$vars['wgMoreMsg'] = wfMsg("more");
	$vars['wgActionPanelTitleMsg'] = wfMsg("quick_action_panel");
	$vars['wgIsQuestion'] = Answer::newFromTitle($wgTitle)->isQuestion();
	$vars['wgIsAnswered'] = Answer::newFromTitle($wgTitle)->isArticleAnswered();
	$vars['wgAnsweredCategory'] = Answer::getSpecialCategory("answered");
	$vars['wgUnAnsweredCategory'] = Answer::getSpecialCategory("unanswered");
	$vars['wgAdsByGoogleMsg'] = wfMsg("ads_by_google");
	$vars['wgUnansweredRecentChangesURL'] = htmlspecialchars(SpecialPage::getTitleFor( 'RecentChangesLinked' )->getFullURL()) . "/" .  Title::makeTitle(NS_CATEGORY, Answer::getSpecialCategory("unanswered") )->getPrefixedText();
	$vars['wgUnansweredRecentChangesText'] = wfMsg("see_all");
	$vars['wgCategoryName'] = $wgContLang->getNsText( NS_CATEGORY );

	global $wgMinimalPasswordLength;
	$vars['wgMinimalPasswordLength'] = $wgMinimalPasswordLength;

	global $wgIsMainpage;
	$vars['wgIsMainpage'] = ($wgIsMainpage?$wgIsMainpage:false);

	global $wgAnswersRecentUnansweredQuestionsLimit;
	$vars["recent_questions_limit"] = ($wgAnswersRecentUnansweredQuestionsLimit ? $wgAnswersRecentUnansweredQuestionsLimit : HomePageList::RECENT_UNANSWERED_QUESTIONS_LIMIT);
	return true;
}

//ArticleFromTitle
//Calls UserProfilePage instead of standard article
function wfUserProfileCSS( &$title, &$article ){

	global $wgOut, $wgUserProfileScripts, $wgStylePath, $wgTitle;
	if( $wgTitle->getNamespace() == NS_USER ){
		$wgOut->addScript("<link rel='stylesheet' type='text/css' href=\"{$wgUserProfileScripts}/UserProfile.css\"/>\n");
	}
	return true;
}

$wgAjaxExportList [] = 'wfGetCategoriesSuggest';
function wfGetCategoriesSuggest( $query, $limit = 5 ){
	$dbr = wfGetDB( DB_SLAVE );

	$res = $dbr->select( "categorylinks",
		array( 'cl_to', 'count(*) as cnt' ),
		array( "UPPER(cl_to) LIKE " . $dbr->addQuotes(strtoupper($query) . "%") ),
		__METHOD__,
		array("ORDER BY" => "cl_to", "GROUP BY" => "cl_to"  )
		);
	while ($row = $dbr->fetchObject( $res ) ) {
		$title = Title::makeTitle(NS_CATEGORY, $row->cl_to);
		$out["ResultSet"]["Result"][] = array("category" => $title->getText(), "count" => $row->cnt );
	}
	return json_encode( $out );
}

$wgAjaxExportList [] = 'wfHoldWatchForAnon';
function wfHoldWatchForAnon( $title ){
	global $wgCookiePrefix, $wgCookieDomain, $wgCookieSecure, $wgCookieExpiration;
	setcookie( $wgCookiePrefix . "wsWatchHold",$title,time() + $wgCookieExpiration,'/',$wgCookieDomain,$wgCookieSecure );
	return SpecialPage::getTitleFor( 'Userlogin' )->getFullURL("type=signup");
}

$wgAutoloadClasses["CustomMovePageForm"]  = __DIR__ . "/CustomMoveForm.php";
$wgHooks['MovePageForm'][] = 'wfCustomMoveForm';
function wfCustomMoveForm( &$newTitle, &$oldTitle, &$form ){

	$form = new CustomMovePageForm( $newTitle, $oldTitle);

	return true;
}

$wgHooks['TitleMoveComplete'][] = 'fnRedirectOnMove';
function fnRedirectOnMove( Title $title, Title $newtitle, User $user, $oldid, $newid ): bool {
	global $wgOut;
	$wgOut->redirect( $newtitle->getFullURL() );
	return true;
}

$wgAutoloadClasses["WikiaApiQueryMostCategories"]  = "$IP/extensions/wikia/WikiaApi/WikiaApiQueryMostCategories.php";
// 1.13 version
$wgApiQueryListModules["wkmostcat"] = "WikiaApiQueryMostCategories";
// 1.14 version
$wgAPIListModules["wkmostcat"] = "WikiaApiQueryMostCategories";

/* Extension to change the display of the category page for answers
 * - Hide the title if there is no subcategory
 * - Display "unanswered" questions differently
 */

$wgHooks['CategoryViewer::getOtherSection'][] = 'answerCategoryOtherSection';
$wgHooks['CategoryViewer::addPage'][] = 'answerAddCategoryPage';

////
// When a page is added to a category view (for instance the list of Answered Questions on the Category page),
// a hook attached to this function is run which gives it a chance to modify the category-view.  For now, it
// wraps the link in a span with an specific class based on whether it is answered, unanswered, or a redirect.
//
// Since this function returns false, it prevents the default behavior from adding this item to the "pages" section
// of the category page.
////
function answerAddCategoryPage( CategoryViewer $catView, Title $title, &$row, $humanSortkey ) {
	global $wgContLang;

	if (empty($catView->answers)){
		$catView->answers = array();
		$catView->answers_start_char = array();
	}
	// For more detailed data about answers.
	if (empty($catView->answerArticles)){
		$catView->answerArticles = array();
	}

	$answered_category = Title::makeTitle(NS_CATEGORY, ucfirst(Answer::getSpecialCategory("answered") )  )->getPrefixedDBKey();
	$unanswered_category = Title::makeTitle(NS_CATEGORY, ucfirst(Answer::getSpecialCategory("unanswered"))  )->getPrefixedDBKey();

	$cats = $title->getParentCategories();

	// Apply a different class depending on whether it is answered or not
	if (isset($cats[ $answered_category ])){
		$class = "answered_questions";
	} else if (isset($cats[ $unanswered_category ])){
		$class = "unanswered_questions";
	} else if ($row->page_is_redirect){
		$class = "redirect-in-category";
	} else {
		// Assume answered for now until David's isAnsweredQuestion is reworked
		$class = "answered_questions";
	}
	$catView->answers[$class][] = "<span class=\"$class\">" . Linker::linkKnown( $title, $title->getPrefixedText() . '?' ) . '</span>';

	if(!isset($catView->answerArticles[$class])){
		$catView->answerArticles[$class] = array();
	}

	// Note that return false here will prevent it from being displayed as a "normal" category
	return false;
}

////
// This function will be called by a hook so that it can change the rendering of the CategoryPage.
////
function answerCategoryOtherSection( CategoryViewer $catView, &$r ): bool {
	if( empty( $catView->answers ) ) {
		return true;
	}

	$r .= "<table style=\"width: 100%\"><tr>";

	if (!empty($catView->answers["answered_questions"])){
		$r .= "<td style=\"width: 50%; vertical-align: top\">\n";
		$r .= "<div id=\"mw-pages\">\n";
		$r .= "<h2>" . Answer::getSpecialCategory("answered") . "</h2>";
		$r .= $catView->msg( 'answers-category-count-answered' )
			->numParams( count( $catView->answers['answered_questions'] ) )
			->escaped();
		$r .= "<ul>\n";
		foreach($catView->answers["answered_questions"] as $q){
			$r.= "<li>$q</li>\n";
		}
		$r .= "</ul>\n";
		$r .= "</div>\n";
		$r .= "</td>\n";
	}

	if (!empty($catView->answers["unanswered_questions"])){
		$r .= "<td style=\"width: 50%; vertical-align: top\">\n";
		$r .= "<div id=\"mw-pages\">\n";
		$r .= "<h2>" . str_replace("-","",Answer::getSpecialCategory("unanswered")) . "</h2>";
		$r .= $catView->msg( 'answers-category-count-unanswered' )
			->numParams( count( $catView->answers['unanswered_questions'] ) )
			->escaped();
		$r .= "<ul>\n";
		foreach($catView->answers["unanswered_questions"] as $q){
			$r.= "<li>$q</li>\n";
		}
		$r .= "</ul>\n";
		$r .= "</div>\n";
		$r .= "</td>\n";
	}

	$r .= "</tr></table>\n";

	return true;
}

// For Magic Answer, override the text in the edit box if it is passed in the request
$wgHooks['EditPage::showEditForm:initial2'][] = 'displayMagicAnswer';
function displayMagicAnswer($editor){
	if (!empty($_GET['magicAnswer'])){
		$escapedAnswer = addslashes($_GET['magicAnswer']);
		global $wgOut;
		$html = "
	        <script type='text/javascript'>
                function magicAnswerCallback(result){
                        // if (console.dir) { console.dir(result); }
                        try {
                                document.getElementById('wpTextbox1').value += result.all.questions[0]['ChosenAnswer'];
                        } catch (e){
                        	// if (console.dir) { console.dir(e); }
                        }
                }
		jQuery('#wpTextbox1').ready(function() {
                	MagicAnswer.getAnswer(\"$escapedAnswer\", 'magicAnswerCallback');
						addCategory(\"[[Category:Powered by Yahoo! Answers]]\");

		});
                </script>
		";
		$wgOut->addHtml($html);
	}
	return true;

}

//CategoryPageView
//injects Ads into Category pages
$wgHooks['CategoryPageView'][] = 'wfCategoryPageWithAds';
function wfCategoryPageWithAds( $cat ){

	$article = new Article( $cat->getTitle() );
	$article->view();

	if ( NS_CATEGORY == $cat->getTitle()->getNamespace() ) {
		global $wgOut, $wgRequest;
		$from = $wgRequest->getVal( 'from' );
		$until = $wgRequest->getVal( 'until' );
		$viewer = new CategoryWithAds( $cat->getTitle(), $from, $until );
		$wgOut->addHTML( $viewer->getHTML() );
	}

	return false;
}

class CategoryWithAds extends CategoryViewer{

	function __construct( $title, $from = '', $until = '' ) {
		parent::__construct( $title, RequestContext::getMain() );
		$this->fromSortKey = $from;
		$this->untilSortKey = $until;
	}

	function doCategoryQuery() {
		$dbr = wfGetDB( DB_SLAVE, 'vslow' );
		if( $this->fromSortKey != '' ) {
			$pageCondition = 'cl_sortkey >= ' . $dbr->addQuotes( $this->fromSortKey );
			$this->flip = false;
		} elseif( $this->untilSortKey != '' ) {
			$pageCondition = 'cl_sortkey < ' . $dbr->addQuotes( $this->untilSortKey );
			$this->flip = true;
		} else {
			$pageCondition = '1 = 1';
			$this->flip = false;
		}
		$res = $dbr->select(
			array( 'page', 'categorylinks', 'category' ),
			array( 'page_title', 'page_namespace', 'page_len', 'page_is_redirect', 'cl_sortkey',
				'cat_id', 'cat_title', 'cat_subcats', 'cat_pages', 'cat_files', 'cat_id IS NULL as cat_id_null' ),
			array( $pageCondition, 'cl_to' => $this->title->getDBkey() ),
			__METHOD__,
			array( 'ORDER BY' => $this->flip ? 'cat_id_null asc, cl_sortkey DESC' : 'cat_id_null asc, cl_sortkey',
				'USE INDEX' => array( 'categorylinks' => 'cl_sortkey' ),
				'LIMIT'    => $this->limit + 1 ),
			array( 'categorylinks'  => array( 'INNER JOIN', 'cl_from = page_id' ),
				'category' => array( 'LEFT JOIN', 'cat_title = page_title AND page_namespace = ' . NS_CATEGORY ) )
		);

		$count = 0;
		while( $x = $dbr->fetchObject ( $res ) ) {
			if( ++$count > $this->limit ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				break;
			}

			$title = Title::makeTitle( $x->page_namespace, $x->page_title );

			if( $title->getNamespace() == NS_CATEGORY ) {
				$cat = Category::newFromRow( $x, $title );
				$this->addSubcategoryObject( $cat, $x->cl_sortkey, $x->page_len );
			} elseif( $this->showGallery && $title->getNamespace() == NS_FILE ) {
				$this->addImage( $title, $x->cl_sortkey, $x->page_len, $x->page_is_redirect );
			} else {
				if ( Hooks::run( "CategoryViewer::addPage", [ $this, &$title, &$x, $x->cl_sortkey ] ) ) {
					$this->addPage( $title, $x->cl_sortkey, $x->page_len, $x->page_is_redirect );
				}
			}
		}
		$dbr->freeResult( $res );
	}
}

include( __DIR__ . "/HomePageList.php");
include( __DIR__ . "/FakeAnswersMessaging.php");

$wgAjaxExportList[] = 'wfAnswersGetEditPointsAjax';
/**
 * @author Maciej Błaszkowski <marooned at wikia-inc.com>
 */
function wfAnswersGetEditPointsAjax() {
	global $wgRequest, $wgSquidMaxage;
	$userId = intval($wgRequest->getVal('userId'));
	$points = AttributionCache::getInstance()->getUserEditPoints($userId);
	$timestamp = AttributionCache::getInstance()->getUserLastModifiedFromCache($userId);
	$timestamp = !empty($timestamp) ? $timestamp : wfTimestampNow();

	$data = array('points' => $points, 'timestamp' => wfTimestampNow());

	$json = json_encode($data);
	$response = new AjaxResponse($json);
	$response->setContentType('application/json; charset=utf-8');
	$response->checkLastModified(strtotime($timestamp));
	$response->setCacheDuration($wgSquidMaxage);
	return $response;
}
