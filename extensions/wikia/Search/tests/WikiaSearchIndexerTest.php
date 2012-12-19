<?php

require_once( 'WikiaSearchBaseTest.php' );

/**
 * @todo   Find ways to handle all the MW API querying we're doing in tests
 * @author Robert Elwell <robert@wikia-inc.com>
 */
class WikiaSearchIndexerTest extends WikiaSearchBaseTest {

	/**
	 * @covers WikiaSearchIndexer::getWikiViews
	 */
	public function testGetWikiViewsWithCache() {

		/**
		 * A cached value with weekly and monthly rows greater than 0 should get returned
		 */
		$mockTitle		=	$this->getMock( 'Title' );
		$mockArticle	=	$this->getMock( 'Article', array(), array( $mockTitle ) );
		$mockMemc		=	$this->getMockBuilder( 'MemcachedClient' )
								->disableOriginalConstructor()
								->setMethods( array( 'get', 'set' ) )
								->getMock();

		$mockResult		=	$this->getMock( 'stdClass' );

		$mockWikia		=	$this->getMock( 'Wikia' );

		$mockException	=	$this->getMock( 'Exception' );

		$mockMemc
			->expects	( $this->at( 0 ) )
			->method	( 'get' )
			->will		( $this->returnValue( $mockResult ) )
		;

		// need values greater than 1
		$mockResult->weekly		= 1;
		$mockResult->monthly	= 1;
		$this->mockGlobalVariable( 'wgMemc', $mockMemc );
		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();

		$indexer 	= F::build( 'WikiaSearchIndexer' );

		$method		= new ReflectionMethod( 'WikiaSearchIndexer', 'getWikiViews' );
		$method->setAccessible( true );

		$this->assertEquals(
				$mockResult,
				$method->invoke( $indexer, $mockArticle ),
				'A cached value with weekly and monthly rows greater than 0 should get returned'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::getWikiViews
	 */
	public function testGetWikiViewsNoCache() {
		$mockTitle		=	$this->getMock( 'Title' );
		$mockArticle	=	$this->getMock( 'Article', array(), array( $mockTitle ) );
		$mockMemc		=	$this->getMock( 'stdClass', array( 'get', 'set' ) );
		$mockResult		=	$this->getMock( 'stdClass' );
		$mockDataMart	=	$this->getMock( 'DataMartService', array( 'getPageviewsWeekly', 'getPageviewsMonthly' ) );

		$mockMemc
			->expects	( $this->any() )
			->method	( 'get' )
			->will		( $this->returnValue( null ) )
		;
		$mockMemc
			->expects	( $this->any() )
			->method	( 'set' )
		;
		$mockDataMart
			->staticExpects	( $this->any() )
			->method		( 'getPageviewsWeekly' )
			->will			( $this->returnValue( array( 1234 ) ) )
		;
		$mockDataMart
			->staticExpects	( $this->any() )
			->method		( 'getPageviewsMonthly' )
			->will			( $this->returnValue( array( 12345 ) ) )
		;

		$this->mockGlobalVariable( 'wgMemc', $mockMemc );
		$this->mockClass( 'DataMartService', $mockDataMart );
		$this->mockApp();

		$indexer 	= F::build( 'WikiaSearchIndexer' );
		$method		= new ReflectionMethod( 'WikiaSearchIndexer', 'getWikiViews' );
		$method->setAccessible( true );

		$this->assertEquals(
				(object) array( 'weekly' => 1234, 'monthly' => 12345 ),
				$method->invoke( $indexer, $mockArticle ),
				'A non-cached result should contain weekly and monthly values'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::getRedirectTitles
	 */
	public function testGetRedirectTitlesNoResults() {
		$mockTitle		=	$this->getMock( 'Title', array( 'getDbKey' ) );
		$mockArticle	=	$this->getMock( 'Article', array(), array( $mockTitle ) );
		$mockMemc		=	$this->getMock( 'stdClass', array( 'get', 'set' ) );
		$mockDb 		=	$this->getMock( 'stdClass', array( 'select', 'fetchObject' ) );
		$mockResWrap	=	$this->getMockBuilder( 'ResultWrapper' )
								->disableOriginalConstructor()
								->getMock();
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->setMethods( array( 'foo' ) )
								->disableOriginalConstructor()
								->getMock();
		$mockWf			=	$this->getMockBuilder( 'WikiaFunctionWrapper' )
								->setMethods( array( 'GetDB' ) )
								->disableOriginalConstructor()
								->getMock();

		$mockWf
			->expects	( $this->any() )
			->method	( 'GetDB' )
			->will		( $this->returnValue( $mockDb ) )
		;
		$mockArticle
			->expects	( $this->any() )
			->method	( 'getTitle')
			->will		( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
			->expects	( $this->any() )
			->method	( 'getDbKey' )
			->will		( $this->returnValue( 'foo' ) )
		;
		$mockDb
			->expects	( $this->any() )
			->method	( 'select' )
			->will		( $this->returnValue( $mockResWrap ) )
		;
		$mockDb
			->expects	( $this->any() )
			->method	( 'fetchObject' )
			->with		( $mockResWrap ) 
			->will		( $this->returnValue( null ) )
		;

		$wf = new ReflectionProperty( 'WikiaSearchIndexer', 'wf' );
		$wf->setAccessible( true );
		$wf->setValue( $mockIndexer, $mockWf );
		
		$method		= new ReflectionMethod( 'WikiaSearchIndexer', 'getRedirectTitles' );
		$method->setAccessible( true );

		$result = $method->invoke( $mockIndexer, $mockArticle );
		
		$this->assertEmpty( 
				$result['redirect_titles'], 
				'A query for redirect titles without a result should return an empty string.' );
	}

	/**
	 * @covers WikiaSearchIndexer::getRedirectTitles
	 */
	public function testGetRedirectTitlesWithResults() {
		$mockTitle		=	$this->getMock( 'Title', array( 'getDbKey' ) );
		$mockArticle	=	$this->getMock( 'Article', array(), array( $mockTitle ) );
		$mockMemc		=	$this->getMock( 'stdClass', array( 'get', 'set' ) );
		$mockDb 		=	$this->getMock( 'stdClass', array( 'select', 'fetchObject' ) );
		$mockResWrap	=	$this->getMockBuilder( 'ResultWrapper' )
								->disableOriginalConstructor()
								->getMock();
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->setMethods( array( 'foo' ) )
								->disableOriginalConstructor()
								->getMock();
		$mockWf			=	$this->getMockBuilder( 'WikiaFunctionWrapper' )
								->setMethods( array( 'GetDB' ) )
								->disableOriginalConstructor()
								->getMock();

		$mockWf
			->expects	( $this->once() )
			->method	( 'GetDB' )
			->will		( $this->returnValue( $mockDb ) )
		;
		$mockArticle
			->expects	( $this->any() )
			->method	( 'getTitle')
			->will		( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
			->expects	( $this->any() )
			->method	( 'getDbKey' )
			->will		( $this->returnValue( 'foo' ) )
		;
		$mockDb
			->expects	( $this->at( 0 ) )
			->method	( 'select' )
			->will		( $this->returnValue( $mockResWrap ) )
		;
		$mockDb
			->expects	( $this->at( 1 ) )
			->method	( 'fetchObject' )
			->with		( $mockResWrap )
			->will		( $this->returnValue( (object) array( 'page_title' => 'Foo Bar' ) ) )
		;
		$mockDb
			->expects	( $this->at( 2 ) )
			->method	( 'fetchObject' )
			->with		( $mockResWrap )
			->will		( $this->returnValue( (object) array( 'page_title' => 'Baz Qux' ) ) )
		;
		
		$wf = new ReflectionProperty( 'WikiaSearchIndexer', 'wf' );
		$wf->setAccessible( true );
		$wf->setValue( $mockIndexer, $mockWf );

		$method		= new ReflectionMethod( 'WikiaSearchIndexer', 'getRedirectTitles' );
		$method->setAccessible( true );

		$this->assertEquals( 
				array( 'redirect_titles' => array( 'Foo Bar',  'Baz Qux' ) ), 
				$method->invoke( $mockIndexer, $mockArticle ), 
				'A query for redirect titles with result rows should be pipe-joined with underscores replaced with spaces.' 
		);
	}

	/**
	 * @covers WikiaSearchIndexer::onArticleUndelete
	 */
	public function testOnArticleUndelete() {
		$mockSearchIndexer 	= $this->getMockBuilder( 'WikiaSearchIndexer' )
									->disableOriginalConstructor()
									->setMethods( array( 'reindexBatch' ) )
									->getMock();

		$mockTitle			= $this->getMock( 'Title', array( 'getArticleID' ) );
		$mockWikia			= $this->getMock( 'Wikia', array( 'log' ) );

		$mockWikia
			->staticExpects	( $this->any() )
			->method		( 'log' )
		;
		$mockTitle
			->expects	( $this->any() )
			->method	( 'getArticleID' )
			->will		( $this->returnValue( 1234 ) )
		;
		$mockSearchIndexer
			->expects	( $this->at( 0 ) )
			->method	( 'reindexBatch' )
			->with		( array( 1234 ) )
			->will		( $this->returnValue( true ) )
		;

		$mockException = $this->getMock( 'Exception' );

		$mockSearchIndexer
			->expects	( $this->at( 1 ) )
			->method	( 'reindexBatch' )
			->will		( $this->throwException ( $mockException ) )
		;

		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();

		$this->assertTrue(
				$mockSearchIndexer->onArticleUndelete( $mockTitle, true ),
				'WikiaSearchIndexer::onArticleUndelete should always return true'
		);
		$this->assertTrue(
				$mockSearchIndexer->onArticleUndelete( $mockTitle, true ),
				'WikiaSearchIndexer::onArticleUndelete should always return true'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::onArticleSaveComplete
	 */
	public function testOnArticleSaveComplete() {
		$mockSearchIndexer 	= $this->getMockBuilder( 'WikiaSearchIndexer' )
									->disableOriginalConstructor()
									->setMethods( array( 'reindexBatch' ) )
									->getMock();

		$mockArticle		= $this->getMockBuilder( 'Article' )
									->disableOriginalConstructor()
									->setMethods( array( 'getTitle' ) )
									->getMock();

		$mockTitle			= $this->getMock( 'Title', array( 'getArticleID' ) );
		$mockWikia			= $this->getMock( 'Wikia', array( 'log' ) );

		$mockUser 			= $this->getMockBuilder( 'User' )
									->disableOriginalConstructor()
									->getMock();

		$mockRevision		= $this->getMockBuilder( 'Revision' )
									->disableOriginalConstructor()
									->getMock();

		$mockArticle
			->expects	( $this->any() )
			->method	( 'getTitle' )
			->will		( $this->returnValue( $mockTitle ) )
		;
		$mockWikia
			->staticExpects	( $this->any() )
			->method		( 'log' )
		;
		$mockTitle
			->expects	( $this->any() )
			->method	( 'getArticleID' )
			->will		( $this->returnValue( 1234 ) )
		;
		$mockSearchIndexer
			->expects	( $this->at( 0 ) )
			->method	( 'reindexBatch' )
			->with		( array( 1234 ) )
			->will		( $this->returnValue( true ) )
		;

		$mockException = $this->getMock( 'Exception' );

		$mockSearchIndexer
			->expects	( $this->at( 1 ) )
			->method	( 'reindexBatch' )
			->will		( $this->throwException ( $mockException ) )
		;

		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();

		//stupid pass by reference params
		$array = array();
		$int = 1;
		$this->assertTrue(
				$mockSearchIndexer->onArticleSaveComplete( $mockArticle, $mockUser, '', '', true, true, '', $array, $mockRevision, $int, $int ),
				'WikiaSearchIndexer::onArticleSaveComplete should always return true'
		);
		$this->assertTrue(
				$mockSearchIndexer->onArticleSaveComplete( $mockArticle, $mockUser, '', '', true, true, '', $array, $mockRevision, $int, $int ),
				'WikiaSearchIndexer::onArticleSaveComplete should always return true'
		);

	}

	/**
	 * @covers WikiaSearchIndexer::onArticleDeleteComplete
	 */
	public function testOnArticleDeleteComplete() {
		$mockSearchIndexer 	= $this->getMockBuilder( 'WikiaSearchIndexer' )
									->disableOriginalConstructor()
									->setMethods( array( 'deleteArticle' ) )
									->getMock();

		$mockArticle		= $this->getMockBuilder( 'Article' )
									->disableOriginalConstructor()
									->setMethods( array( 'getTitle' ) )
									->getMock();

		$mockTitle			= $this->getMock( 'Title', array( 'getArticleID' ) );
		$mockWikia			= $this->getMock( 'Wikia', array( 'log' ) );

		$mockUser 			= $this->getMockBuilder( 'User' )
									->disableOriginalConstructor()
									->getMock();

		$mockId = 1235;

		$mockWikia
			->staticExpects	( $this->any() )
			->method		( 'log' )
		;
		$mockSearchIndexer
			->expects	( $this->at( 0 ) )
			->method	( 'deleteArticle' )
			->with		( $mockId )
			->will		( $this->returnValue( true ) )
		;

		$mockException = $this->getMock( 'Exception' );

		$mockSearchIndexer
			->expects	( $this->at( 1 ) )
			->method	( 'deleteArticle' )
			->will		( $this->throwException ( $mockException ) )
		;

		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();

		$this->assertTrue(
				$mockSearchIndexer->onArticleDeleteComplete( $mockArticle, $mockUser, 123, $mockId ),
				'WikiaSearchIndexer::onArticleDeleteComplete should always return true'
		);
		$this->assertTrue(
				$mockSearchIndexer->onArticleDeleteComplete( $mockArticle, $mockUser, 123, $mockId ),
				'WikiaSearchIndexer::onArticleDeleteComplete should always return true'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::getPageMetaData
	 */
	public function testGetPageMetadata() {
		$mockSearchIndexer 	= $this->getMockBuilder( 'WikiaSearchIndexer' )
									->disableOriginalConstructor()
									->setMethods( array( 'getRedirectTitles', 'getWikiViews' ) )
									->getMock();

		$mockArticle		= $this->getMockBuilder( 'Article' )
									->disableOriginalConstructor()
									->setMethods( array( 'getTitle', 'getId' ) )
									->getMock();

		$mockApiService		= $this->getMock( 'ApiService', array( 'call' ) );
		$mockDataMart		= $this->getMock( 'DataMartServie', array( 'getCurrentWamScoreForWiki' ) );

		$mockTitle			= 'PHPUnit/Being_Awesome';
		$mockId				= 123;

		$mockArticle
			->expects	( $this->any() )
			->method	( 'getTitle' )
			->will		( $this->returnValue( $mockTitle ) )
		;
		$mockArticle
			->expects	( $this->any() )
			->method	( 'getId' )
			->will		( $this->returnValue( $mockId ) )
		;
		$mockBacklinks = array( 'query' => array( 'backlinks_count' => 20 ) );
		$mockApiService
			->staticExpects	( $this->at( 0 ) )
			->method		( 'call' )
			->will			( $this->returnValue( $mockBacklinks ) )
		;
		$mockPageData = array( 'query' => array( 'pages' => array( $mockId =>
				array( 'views' => 100,
						'revcount' => 20,
						'created' => date( 'Y-m-d' ),
						'touched' => date( 'Y-m-d' ),
						'categories' => array( array( 'title' => 'Category:Stuff' ), array( 'title' => 'Category:Things' ), array( 'title' => 'Category:Miscellany' ) )
						) ) ) );
		$mockApiService
			->staticExpects	( $this->at( 1 ) )
			->method		( 'call' )
			->will			( $this->returnValue( $mockPageData ) )
		;
		$mockSearchIndexer
			->expects	( $this->once() )
			->method	( 'getWikiViews' )
			->with		( $mockArticle )
			->will		( $this->returnValue( (object) array( 'weekly' => 10, 'monthly' => 100 ) ) )
		;
		$redirectTitles = array( 'foo', 'bar', 'baz', 'qux' );
		$mockSearchIndexer
			->expects	( $this->once() )
			->method	( 'getRedirectTitles' )
			->with		( $mockArticle )
			->will		( $this->returnValue( $redirectTitles ) )
		;
		$mockDataMart
			->expects	( $this->once() )
			->method	( 'getCurrentWamScoreForWiki' )
		;

		$wgProperty = new ReflectionProperty( 'WikiaSearchIndexer', 'wg' );
		$wgProperty->setAccessible( true );
		$wgProperty->setValue( $mockSearchIndexer, (object) array( 'CityId' => 123, 'ExternalSharedDB' => true ) );

		$method = new ReflectionMethod( 'WikiaSearchIndexer', 'getPageMetaData' );
		$method->setAccessible( true );

		$this->mockClass( 'ApiService', $mockApiService );
		$this->mockClass( 'DataMartService', $mockDataMart );
		$this->mockApp();

		$result = $method->invoke( $mockSearchIndexer, $mockArticle );

	}

	/**
	 * @covers WikiaSearchIndexer::deleteArticle
	 */
	public function testDeleteArticleCityId() {
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->disableOriginalConstructor()
								->setMethods( array( 'deleteBatch' ) )
								->getMock();

		$reflectionWg	=	new ReflectionProperty( 'WikiaSearchIndexer', 'wg' );
		$reflectionWg->setAccessible( true );
		$reflectionWg->setValue( $mockIndexer, (object) array( 'CityId' => 123 ) );

		$mockIndexer
			->expects	( $this->once() )
			->method	( 'deleteBatch' )
			->with		( array( '123_234' ) )
		;

		$this->assertTrue(
				$mockIndexer->deleteArticle( 234 ),
				'WikiaSearchIndexer::deleteArticle should always return true'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::deleteArticle
	 */
	public function testDeleteArticleNoCityId() {
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->disableOriginalConstructor()
								->setMethods( array( 'deleteBatch' ) )
								->getMock();

		$reflectionWg	=	new ReflectionProperty( 'WikiaSearchIndexer', 'wg' );
		$reflectionWg->setAccessible( true );
		$reflectionWg->setValue( $mockIndexer, (object) array( 'CityId' => null, 'SearchWikiId' => 123 ) );

		$mockIndexer
			->expects	( $this->once() )
			->method	( 'deleteBatch' )
			->with		( array( '123_234' ) )
		;

		$this->assertTrue(
				$mockIndexer->deleteArticle( 234 ),
				'WikiaSearchIndexer::deleteArticle should always return true'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::reindexPage
	 */
	public function testReindexPage() {
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->disableOriginalConstructor()
								->setMethods( array( 'getSolrDocument', 'reindexBatch' ) )
								->getMock();

		$mockDocument	=	$this->getMock( 'Solarium_Document_ReadWrite' );

		$mockWikia		= $this->getMock( 'Wikia', array( 'log' ) );

		$mockIndexer
			->expects	( $this->at( 0 ) )
			->method	( 'getSolrDocument' )
			->with		( 123 )
			->will		( $this->returnValue( $mockDocument ) )
		;
		$mockIndexer
			->expects	( $this->at( 1 ) )
			->method	( 'reindexBatch' )
			->with		( array( $mockDocument ) )
		;
		$mockWikia
			->expects	( $this->any() )
			->method	( 'log' )
		;

		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();

		$this->assertTrue(
				$mockIndexer->reindexPage( 123 ),
				'WikiaSearchIndexer::reindexPage should always return true'
				);

	}

	/**
	 * @covers WikiaSearchIndexer::deleteBatch
	 */
	public function testDeletBatchWorks() {
		$mockClient		=	$this->getMock( 'Solarium_Client', array( 'update', 'createUpdate' ) );
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->setConstructorArgs( array( $mockClient ) )
								->setMethods( array( 'getSolrDocument', 'reindexBatch' ) )
								->getMock();

		$mockHandler	=	$this->getMock( 'Solarium_Query_Update', array( 'addDeleteQuery', 'addCommit' ) );

		$mockClient
			->expects	( $this->at( 0 ) )
			->method	( 'createUpdate' )
			->will		( $this->returnValue( $mockHandler ) )
		;
		$mockHandler
			->expects	( $this->at( 0 ) )
			->method	( 'addDeleteQuery' )
			->with		( WikiaSearch::valueForField( 'id', 123 ) )
		;
		$mockHandler
			->expects	( $this->at( 1 ) )
			->method	( 'addCommit' )
		;
		$mockClient
			->expects	( $this->at( 1 ) )
			->method	( 'update' )
			->with		( $mockHandler )
		;

		$this->assertTrue(
				$mockIndexer->deleteBatch( array( 123 ) ),
				'WikiaSearchIndexer::deleteBatch should always return true'
		);

	}

	/**
	 * @covers WikiaSearchIndexer::deleteBatch
	 */
	public function testDeletBatchBreaks() {
		$mockClient		=	$this->getMock( 'Solarium_Client', array( 'update', 'createUpdate' ) );
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->setConstructorArgs( array( $mockClient ) )
								->setMethods( array( 'getSolrDocument', 'reindexBatch' ) )
								->getMock();

		$mockHandler	=	$this->getMock( 'Solarium_Query_Update', array( 'addDeleteQuery', 'addCommit' ) );

		$mockException	=	$this->getMock( 'Exception' );

		$mockWikia		=	$this->getMock( 'Wikia', array( 'log' ) );

		$mockClient
			->expects	( $this->at( 0 ) )
			->method	( 'createUpdate' )
			->will		( $this->returnValue( $mockHandler ) )
		;
		$mockHandler
			->expects	( $this->at( 0 ) )
			->method	( 'addDeleteQuery' )
			->with		( WikiaSearch::valueForField( 'id', 123 ) )
		;
		$mockHandler
			->expects	( $this->at( 1 ) )
			->method	( 'addCommit' )
		;
		$mockClient
			->expects	( $this->at( 1 ) )
			->method	( 'update' )
			->with		( $mockHandler )
			->will		( $this->throwException( $mockException ) )
		;
		$mockWikia
			->expects	( $this->any() )
			->method	( 'log' )
		;

		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();

		$this->assertTrue(
				$mockIndexer->deleteBatch( array( 123 ) ),
				'WikiaSearchIndexer::deleteBatch should always return true'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::reindexBatch
	 */
	public function testReindexBatch() {
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->disableOriginalConstructor()
								->setMethods( array( 'getSolrDocument', 'updateDocuments' ) )
								->getMock();
		
		$mockId = 1234;
		$mockDocument = $this->getMock( 'Solarium_Document_ReadWrite' );
		
		$mockIndexer
			->expects	( $this->at( 0 ) )
			->method	( 'getSolrDocument' )
			->with		( $mockId )
			->will		( $this->returnValue( $mockDocument ) )
		;
		$mockIndexer
			->expects	( $this->at( 1 ) )
			->method	( 'updateDocuments' )
			->with		( array( $mockDocument ) )
			->will		( $this->returnValue( true ) )
		;
		$this->assertTrue(
				$mockIndexer->reindexBatch( array( $mockId ) ),
				'WikiaSearchIndexer::reindexBatch should always return true'
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::updateDocuments
	 */
	public function testUpdateDocumentsWorks() {
		$mockClient		=	$this->getMock( 'Solarium_Client', array( 'update', 'createUpdate' ) );
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->setConstructorArgs( array( $mockClient ) )
								->setMethods( array( 'getSolrDocument' ) )
								->getMock();

		$mockDocument	=	$this->getMock( 'Solarium_Document_ReadWrite' );

		$mockHandler	=	$this->getMock( 'Solarium_Query_Update', array( 'addDocuments', 'addCommit' ) );

		$mockClient
			->expects	( $this->at( 0 ) )
			->method	( 'createUpdate' )
			->will		( $this->returnValue( $mockHandler ) )
		;
		$mockHandler
			->expects	( $this->at( 0 ) )
			->method	( 'addDocuments' )
			->with		( array( $mockDocument ) )
		;
		$mockHandler
			->expects	( $this->at( 1 ) )
			->method	( 'addCommit' )
		;
		$mockClient
			->expects	( $this->at( 1 ) )
			->method	( 'update' )
			->with		( $mockHandler )
		;

		$this->assertTrue(
				$mockIndexer->updateDocuments( array( $mockDocument ) ),
				'WikiaSearchIndexer::reindexBatch should always return true'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::updateDocuments
	 */
	public function testUpdateDocumentsBreaks() {
		$mockClient		=	$this->getMock( 'Solarium_Client', array( 'update', 'createUpdate' ) );
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->setConstructorArgs( array( $mockClient ) )
								->setMethods( array( 'getSolrDocument' ) )
								->getMock();

		$mockDocument	=	$this->getMock( 'Solarium_Document_ReadWrite' );

		$mockException	=	$this->getMock( 'Exception' );

		$mockHandler	=	$this->getMock( 'Solarium_Query_Update', array( 'addDocuments', 'addCommit' ) );

		$mockWikia		=	$this->getMock( 'Wikia', array( 'log' ) );

		$mockClient
			->expects	( $this->at( 0 ) )
			->method	( 'createUpdate' )
			->will		( $this->returnValue( $mockHandler ) )
		;
		$mockHandler
			->expects	( $this->at( 0 ) )
			->method	( 'addDocuments' )
			->with		( array( $mockDocument ) )
		;
		$mockHandler
			->expects	( $this->at( 1 ) )
			->method	( 'addCommit' )
		;
		$mockClient
			->expects	( $this->at( 1 ) )
			->method	( 'update' )
			->with		( $mockHandler )
			->will		( $this->throwException( $mockException ) )
		;
		$mockWikia
			->expects	( $this->any() )
			->method	( 'log' )
		;

		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();

		$this->assertTrue(
				$mockIndexer->updateDocuments( array( $mockDocument ) ),
				'WikiaSearchIndexer::reindexBatch should always return true'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::getSolrDocument
	 */
	public function testGetSolrDocument() {
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->disableOriginalConstructor()
								->setMethods( array( 'getPage' ) )
								->getMock();

		$pageData = array(
				'id'	=>	'234_123',
				'title'	=>	'my crappy test',
				'html'	=>	'foo bar baz yes i am skipping testing regexes that is a trap',
				'lang'	=>	'en',
				);

		$mockIndexer
			->expects	( $this->once() )
			->method	( 'getPage' )
			->with		( 123 )
			->will		( $this->returnValue( $pageData ) )
		;

		$doc = $mockIndexer->getSolrDocument( 123 );

		$this->assertInstanceOf(
				'Solarium_Document_ReadWrite',
				$doc,
				'WikiaSearchIndexer::getSolrDocument should return an instance of Solarium_Document_ReadWrite'
		);
		$this->assertEquals(
				'234_123',
				$doc['id'],
				'The return value of WikiaSearchIndexer::getSolrDocument should have the values retrieved from getPage() set in the Solr document'
		);
		$this->assertEquals(
				$doc['html_en'],
				$doc['html'],
				'Language fields should be transformed during WikiaSearchIndexer::getSolrDocument'
		);
	}

	/**
	 * @covers WikiaSearchIndexer::getPages
	 */
	public function testGetPages() {
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->disableOriginalConstructor()
								->setMethods( array( 'getPage' ) )
								->getMock();

		$mockException	=	$this->getMockBuilder( 'WikiaException' )
								->disableOriginalConstructor()
								->getMock();

		$mockIndexer
			->expects	( $this->at( 0 ) )
			->method	( 'getPage' )
			->with		( 123 )
			->will		( $this->returnValue( array( 'here be my page data' ) ) )
		;
		$mockIndexer
			->expects	( $this->at( 1 ) )
			->method	( 'getPage' )
			->with		( 234 )
			->will		( $this->throwException( $mockException ) )
		;

		$this->assertEquals(
				array( 'pages' => array( 123 => array( 'here be my page data' ) ), 'missingPages' => array( 234 ) ),
				$mockIndexer->getPages( array( 123, 234 ) ),
				'WikiaSearchIndexer::getPages should set pagedata for each page it successfully grabs, and list each problematic page as missing.'
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::reindexWiki
	 * 
	 */
	public function testReindexWiki()
	{
		$mockClient		=	$this->getMockBuilder( 'Solarium_Client' )
								->disableOriginalConstructor()
								->getMock();
		
		$mockDbHandler	=	$this->getMockBuilder( 'DatabaseMysql' )
								->disableOriginalConstructor()
								->setMethods( array( 'fetchObject', 'query' ) )
								->getMock();
		
		$mockScribeProd	=	$this->getMockBuilder( 'ScribeProducer' )
								->disableOriginalConstructor()
								->setMethods( array( '__construct', 'reindexPage' ) )
								->getMock();
		$mockDataSource	=	$this->getMockbuilder( 'WikiDataSource' )
								->disableOriginalConstructor()
								->setMethods( array( 'getDB' ) )
								->getMock();
		$mockDbResult	=	$this->getMockBuilder( 'ResultWrapper' )
								->disableOriginalConstructor()
								->getMock();
		
		$mockDataSource
			->expects	( $this->once() )
			->method	( 'getDB' )
			->will		( $this->returnValue( $mockDbHandler ) )
		;
		$mockDbHandler
			->expects	( $this->at( 0 ) )
			->method	( 'query' )
			->with		( "SELECT page_id FROM page" )
			->will		( $this->returnValue( $mockDbResult ) ) 
		;
		$mockDbHandler
			->expects	( $this->at( 1 ) )
			->method	( 'fetchObject' )
			->with		( $mockDbResult )
			->will		( $this->returnValue( (object) array( 'page_id' => 123 ) ) ) 
		;
		$mockDbHandler
			->expects	( $this->at( 2 ) )
			->method	( 'fetchObject' )
			->with		( $mockDbResult )
			->will		( $this->returnValue( null ) ) 
		;
		$mockScribeProd
			->expects	( $this->once() )
			->method	( 'reindexPage' )
		;
		
		$this->mockClass( 'WikiDataSource', $mockDataSource );
		$this->mockClass( 'ScribeProducer', $mockScribeProd );
		$this->mockApp();

		$indexer = new WikiaSearchIndexer( $mockClient );
		$indexer->reindexWiki( 321 );
	}
	
	/**
	 * @covers WikiaSearchIndexer::reindexWiki
	 * 
	 */
	public function testReindexWikiBadWid()
	{
		$mockClient		=	$this->getMockBuilder( 'Solarium_Client' )
								->disableOriginalConstructor()
								->getMock();
		
		$mockDbHandler	=	$this->getMockBuilder( 'DatabaseMysql' )
								->disableOriginalConstructor()
								->setMethods( array( 'fetchObject', 'query' ) )
								->getMock();
		
		$mockScribeProd	=	$this->getMockBuilder( 'ScribeProducer' )
								->disableOriginalConstructor()
								->setMethods( array( 'reindexPage' ) )
								->getMock();
		$mockDataSource	=	$this->getMockbuilder( 'WikiDataSource' )
								->disableOriginalConstructor()
								->setMethods( array( 'getDB' ) )
								->getMock();
		$mockDbResult	=	$this->getMockBuilder( 'ResultWrapper' )
								->disableOriginalConstructor()
								->getMock();
		$mockWikia		=	$this->getMock( 'Wikia', array( 'log' ) );
		$mockException	=	$this->getMock( 'Exception' );

		$mockDataSource
			->expects	( $this->once() )
			->method	( 'getDB' )
			->will		( $this->returnValue( $mockDbHandler ) )
		;
		$mockDbHandler
			->expects	( $this->at( 0 ) )
			->method	( 'query' )
			->with		( 'SELECT page_id FROM page' )
			->will		( $this->returnValue( $mockDbResult ) ) 
		;
		$mockDbHandler
			->expects	( $this->at( 1 ) )
			->method	( 'fetchObject' )
			->with		( $mockDbResult )
			->will		( $this->returnValue( (object) array( 'page_id' => 123 ) ) ) 
		;
		$mockScribeProd
			->expects	( $this->once() )
			->method	( 'reindexPage' )
			->will		( $this->throwException( $mockException ) )
		;
		
		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockClass( 'WikiDataSource', $mockDataSource );
		$this->mockClass( 'ScribeProducer', $mockScribeProd );
		$this->mockApp();
		
		$indexer = new WikiaSearchIndexer( $mockClient );
		$indexer->reindexWiki( 321 );
		
	}
	
	/**
	 * @covers WikiaSearchIndexer::deleteWikiDocs
	 */
	public function testDeleteWikiDocsWorks()
	{
		$mockClient		=	$this->getMockBuilder( 'Solarium_Client' )
								->disableOriginalConstructor()
								->setMethods( array( 'createUpdate', 'update' ) )
								->getMock();
		$mockUpdate		=	$this->getMockBuilder( 'Solarium_Query_Update' )
								->disableOriginalConstructor()
								->setMethods( array( 'addDeleteQuery', 'addCommit' ) )
								->getMock();
		$mockResult		=	$this->getMockBuilder( 'Solarium_Result' )
								->disableOriginalConstructor()
								->getMock(); 
		
		$wid = 123;
		
		$mockClient
			->expects	( $this->at( 0 ) )
			->method	( 'createUpdate' )
			->will		( $this->returnValue( $mockUpdate ) )
		;
		$mockUpdate
			->expects	( $this->at( 0 ) )
			->method	( 'addDeleteQuery' )
			->with		( "(wid:{$wid})" )
		;
		$mockUpdate
			->expects	( $this->at( 1 ) )
			->method	( 'addCommit' )
		;
		$mockClient
			->expects	( $this->at( 1 ) )
			->method	( 'update' )
			->with		( $mockUpdate )
			->will		( $this->returnValue( $mockResult ) )
		;
		
		$indexer = new WikiaSearchIndexer( $mockClient );
		
		$this->assertEquals(
				$mockResult,
				$indexer->deleteWikiDocs( $wid ),
				'WikiaSearchIndexer::deleteWikiDocs should return an instance of Solarium_Result'
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::deleteWikiDocs
	 */
	public function testDeleteWikiDocsBreaks()
	{
		$mockClient		=	$this->getMockBuilder( 'Solarium_Client' )
								->disableOriginalConstructor()
								->setMethods( array( 'createUpdate', 'update' ) )
								->getMock();
		$mockUpdate		=	$this->getMockBuilder( 'Solarium_Query_Update' )
								->disableOriginalConstructor()
								->setMethods( array( 'addDeleteQuery', 'addCommit' ) )
								->getMock();
		$mockException	=	$this->getMock( 'Exception' );
		$mockWikia		=	$this->getMock( 'Wikia' );
		
		$wid = 123;
		
		$mockClient
			->expects	( $this->at( 0 ) )
			->method	( 'createUpdate' )
			->will		( $this->returnValue( $mockUpdate ) )
		;
		$mockUpdate
			->expects	( $this->at( 0 ) )
			->method	( 'addDeleteQuery' )
			->with		( "(wid:{$wid})" )
		;
		$mockUpdate
			->expects	( $this->at( 1 ) )
			->method	( 'addCommit' )
		;
		$mockClient
			->expects	( $this->at( 1 ) )
			->method	( 'update' )
			->with		( $mockUpdate )
			->will		( $this->throwException( $mockException ) )
		;
		
		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();
		
		$indexer = new WikiaSearchIndexer( $mockClient );
		
		$indexer->deleteWikiDocs( $wid );

	}
	
	/**
	 * @covers WikiaSearchIndexer::deleteManyWikiDocs
	 */
	public function testDeleteManyWikiDocsWorks()
	{
		$mockClient		=	$this->getMockBuilder( 'Solarium_Client' )
								->disableOriginalConstructor()
								->setMethods( array( 'createUpdate', 'update' ) )
								->getMock();
		$mockUpdate		=	$this->getMockBuilder( 'Solarium_Query_Update' )
								->disableOriginalConstructor()
								->setMethods( array( 'addDeleteQuery', 'addCommit' ) )
								->getMock();
		$mockResult		=	$this->getMockBuilder( 'Solarium_Result' )
								->disableOriginalConstructor()
								->getMock(); 
		
		$wid = 123;
		
		$mockClient
			->expects	( $this->at( 0 ) )
			->method	( 'createUpdate' )
			->will		( $this->returnValue( $mockUpdate ) )
		;
		$mockUpdate
			->expects	( $this->at( 0 ) )
			->method	( 'addDeleteQuery' )
			->with		( "(wid:{$wid})" )
		;
		$mockUpdate
			->expects	( $this->at( 1 ) )
			->method	( 'addCommit' )
		;
		$mockClient
			->expects	( $this->at( 1 ) )
			->method	( 'update' )
			->with		( $mockUpdate )
			->will		( $this->returnValue( $mockResult ) )
		;
		
		$indexer = new WikiaSearchIndexer( $mockClient );
		
		$this->assertEquals(
				$mockResult,
				$indexer->deleteManyWikiDocs( array( $wid ) ),
				'WikiaSearchIndexer::deleteWikiDocs should return an instance of Solarium_Result'
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::deleteManyWikiDocs
	 */
	public function testDeleteManyWikiDocsBreaks()
	{
		$mockClient		=	$this->getMockBuilder( 'Solarium_Client' )
								->disableOriginalConstructor()
								->setMethods( array( 'createUpdate', 'update' ) )
								->getMock();
		$mockUpdate		=	$this->getMockBuilder( 'Solarium_Query_Update' )
								->disableOriginalConstructor()
								->setMethods( array( 'addDeleteQuery', 'addCommit' ) )
								->getMock();
		$mockException	=	$this->getMock( 'Exception' );
		$mockWikia		=	$this->getMock( 'Wikia' );
		
		$wid = 123;
		
		$mockClient
			->expects	( $this->at( 0 ) )
			->method	( 'createUpdate' )
			->will		( $this->returnValue( $mockUpdate ) )
		;
		$mockUpdate
			->expects	( $this->at( 0 ) )
			->method	( 'addDeleteQuery' )
			->with		( "(wid:{$wid})" )
		;
		$mockUpdate
			->expects	( $this->at( 1 ) )
			->method	( 'addCommit' )
		;
		$mockClient
			->expects	( $this->at( 1 ) )
			->method	( 'update' )
			->with		( $mockUpdate )
			->will		( $this->throwException( $mockException ) )
		;
		
		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();
		
		$indexer = new WikiaSearchIndexer( $mockClient );
		
		$indexer->deleteManyWikiDocs( array( $wid ) );

	}
	
	/**
	 * @covers WikiaSearchIndexer::onWikiFactoryPublicStatusChange
	 * @todo update this when we move to solr 4.0 to atomically set all documents in that wiki to is_closed_wiki:false
	 */
	public function testOnWikiFactoryPublicStatusChangeOpened()
	{
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->disableOriginalConstructor()
								->setMethods( array( 'reindexWiki', 'deleteWikiDocs' ) )
								->getMock();
		
		$cityId = 123;
		$status = 1;
		
		$mockIndexer
			->expects	( $this->once() )
			->method	( 'reindexWiki' )
		;
		$mockIndexer
			->expects	( $this->never() )
			->method	( 'deleteWikiDocs' )
		;
		
		$this->assertTrue(
				$mockIndexer->onWikiFactoryPublicStatusChange( $status, $cityId, 'opening it cause i said so' ),
				'WikiaSearchIndexer::onWikiFactoryPublicStatusChange should return true'
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::onWikiFactoryPublicStatusChange
	 * @todo update this when we move to solr 4.0 to atomically set all documents in that wiki to is_closed_wiki:true
	 */
	public function testOnWikiFactoryPublicStatusChangeClosed()
	{
		$mockIndexer	=	$this->getMockBuilder( 'WikiaSearchIndexer' )
								->disableOriginalConstructor()
								->setMethods( array( 'reindexWiki', 'deleteWikiDocs' ) )
								->getMock();
		
		$cityId = 123;
		$status = 0;
		
		$mockIndexer
			->expects	( $this->once() )
			->method	( 'deleteWikiDocs' )
			->with		( $cityId )
		;
		$mockIndexer
			->expects	( $this->never() )
			->method	( 'reindexWiki' )
		;
		
		$this->assertTrue(
				$mockIndexer->onWikiFactoryPublicStatusChange( $status, $cityId, 'closing it cause i said so' ),
				'WikiaSearchIndexer::onWikiFactoryPublicStatusChange should return true'
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::getTitleString
	 */
	public function testGetTitleStringNormal() {
		$mockIndexer = $this->getMockBuilder( 'WikiaSearchIndexer' )
							->disableOriginalConstructor()
							->setMethods( array( 'foo' ) )
							->getMock();
		$mockTitle = $this->getMockBuilder( 'Title' )
						->disableOriginalConstructor()
						->setMethods( array( 'getNamespace', '__toString' ) )
						->getMock();
		
		$mockTitle
			->expects	( $this->at( 0 ) )
			->method	( 'getNamespace' )
			->will		( $this->returnValue( NS_MAIN ) )
		;
		$mockTitle
			->expects	( $this->at( 1 ) )
			->method	( '__toString' )
			->will		( $this->returnValue( 'mock title' ) )
		;
		
		$method = new ReflectionMethod( 'WikiaSearchIndexer', 'getTitleString' );
		$method->setAccessible( true );
		
		$this->assertEquals(
				'mock title',
				$method->invoke( $mockIndexer, $mockTitle ),
				'If it does not meet special cases, WikiaSearchIndexer::getTitleString should return the title instance cast to string'
		);
		
	}
	
	/**
	 * @covers WikiaSearchIndexer::getTitleString
	 */
	public function testGetTitleStringMainWallMessage() {
		$mockIndexer = $this->getMockBuilder( 'WikiaSearchIndexer' )
							->disableOriginalConstructor()
							->setMethods( array( 'foo' ) )
							->getMock();
		$mockTitle = $this->getMockBuilder( 'Title' )
						->disableOriginalConstructor()
						->setMethods( array( 'getNamespace', '__toString', 'getArticleID' ) )
						->getMock();
		$mockWallMessage = $this->getMockBuilder( 'WallMessage' )
								->disableOriginalConstructor()
								->setMethods( array( 'isMain', 'getMetaTitle', 'getTopParentObj', 'load' ) )
								->getMock();
		
		$titleString = 'Main wall message metatitle';
		
		$mockTitle
			->expects	( $this->at( 0 ) )
			->method	( 'getNamespace' )
			->will		( $this->returnValue( NS_WIKIA_FORUM_BOARD_THREAD ) )
		;
		$mockTitle
			->expects	( $this->at( 1 ) )
			->method	( 'getArticleID' )
			->will		( $this->returnValue( 123 ) )
		;
		$mockWallMessage
			->expects	( $this->at( 0 ) )
			->method	( 'load' )
		;
		$mockWallMessage
			->expects	( $this->at( 1 ) )
			->method	( 'isMain' )
			->will		( $this->returnValue( true ) )
		;
		$mockWallMessage
			->expects	( $this->at( 2 ) )
			->method	( 'getMetaTitle' )
			->will		( $this->returnValue( $titleString ) )
		;
		
		$this->proxyClass( 'WallMessage', $mockWallMessage, 'newFromId' );
		$this->mockApp();
		
		$method = new ReflectionMethod( 'WikiaSearchIndexer', 'getTitleString' );
		$method->setAccessible( true );
		
		$this->assertEquals(
				$titleString,
				$method->invoke( $mockIndexer, $mockTitle ),
				'WikiaSearchIndexer::getTitleString should return the meta title for a main message wall instance'
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::getTitleString
	 */
	public function testGetTitleStringNonMainWallMessage() {
		$mockIndexer = $this->getMockBuilder( 'WikiaSearchIndexer' )
							->disableOriginalConstructor()
							->setMethods( array( 'foo' ) )
							->getMock();
		$mockTitle = $this->getMockBuilder( 'Title' )
						->disableOriginalConstructor()
						->setMethods( array( 'getNamespace', '__toString', 'getArticleID' ) )
						->getMock();
		$mockWallMessage = $this->getMockBuilder( 'WallMessage' )
								->disableOriginalConstructor()
								->setMethods( array( 'isMain', 'getMetaTitle', 'getTopParentObj', 'load' ) )
								->getMock();
		
		$titleString = 'Main wall message metatitle';
		
		$mockTitle
			->expects	( $this->at( 0 ) )
			->method	( 'getNamespace' )
			->will		( $this->returnValue( NS_WIKIA_FORUM_BOARD_THREAD ) )
		;
		$mockTitle
			->expects	( $this->at( 1 ) )
			->method	( 'getArticleID' )
			->will		( $this->returnValue( 123 ) )
		;
		$mockWallMessage
			->expects	( $this->at( 0 ) )
			->method	( 'load' )
		;
		$mockWallMessage
			->expects	( $this->at( 1 ) )
			->method	( 'isMain' )
			->will		( $this->returnValue( false ) )
		;
		$mockWallMessage
			->expects	( $this->at( 2 ) )
			->method	( 'getTopParentObj' )
			->will		( $this->returnValue( $mockWallMessage ) )
		;
		$mockWallMessage
			->expects	( $this->at( 3 ) )
			->method	( 'load' )
		;
		$mockWallMessage
			->expects	( $this->at( 4 ) )
			->method	( 'getMetaTitle' )
			->will		( $this->returnValue( $titleString ) )
		;
		
		$this->proxyClass( 'WallMessage', $mockWallMessage, 'newFromId' );
		$this->mockApp();
		
		$method = new ReflectionMethod( 'WikiaSearchIndexer', 'getTitleString' );
		$method->setAccessible( true );
		
		$this->assertEquals(
				$titleString,
				$method->invoke( $mockIndexer, $mockTitle ),
				'WikiaSearchIndexer::getTitleString should return the meta title for a main message wall instance'
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::getMediaMetadata
	 */
	public function testGetMediaMetadataNonFileNS() {
		$mockIndexer = $this->getMockBuilder( 'WikiaSearchIndexer' )
							->disableOriginalConstructor()
							->setMethods( array( 'foo' ) )
							->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
						->disableOriginalConstructor()
						->setMethods( array( 'getNamespace' ) )
						->getMock();
		
		$mockTitle
			->expects	( $this->at( 0 ) )
			->method	( 'getNamespace' )
			->will		( $this->returnValue( NS_MAIN ) )
		;
		
		$this->assertEmpty(
				$mockIndexer->getMediaMetadata( $mockTitle )
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::getMediaMetadata
	 */
	public function testGetMediaMetadataNonFileFound() {
		$mockIndexer = $this->getMockBuilder( 'WikiaSearchIndexer' )
							->disableOriginalConstructor()
							->setMethods( array( 'foo' ) )
							->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
						->disableOriginalConstructor()
						->setMethods( array( 'getNamespace', 'getText' ) )
						->getMock();
		
		$mockWrapper = $this->getMockBuilder( 'WikiaFunctionWrapper' )
							->disableOriginalConstructor()
							->setMethods( array( 'findFile' ) )
							->getMock();
		
		$mockTitle
			->expects	( $this->at( 0 ) )
			->method	( 'getNamespace' )
			->will		( $this->returnValue( NS_FILE ) )
		;
		$mockTitle
			->expects	( $this->at( 1 ) )
			->method	( 'getText' )
			->will		( $this->returnValue( 'foo' ) )
		;
		$mockWrapper
			->expects	( $this->at( 0 ) )
			->method	( 'findFile' )
			->will		( $this->returnValue( null ) )
		;
		
		$wf = new ReflectionProperty( 'WikiaSearchIndexer', 'wf' );
		$wf->setAccessible( true );
		$wf->setValue( $mockIndexer, $mockWrapper );
		
		$this->assertEmpty(
				$mockIndexer->getMediaMetadata( $mockTitle )
		);
	}

	/**
	 * @covers WikiaSearchIndexer::getMediaMetadata
	 */
	public function testGetMediaMetadataImageFound() {
		$mockIndexer = $this->getMockBuilder( 'WikiaSearchIndexer' )
							->disableOriginalConstructor()
							->setMethods( array( 'foo' ) )
							->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
						->disableOriginalConstructor()
						->setMethods( array( 'getNamespace', 'getText' ) )
						->getMock();
		
		$mockWrapper = $this->getMockBuilder( 'WikiaFunctionWrapper' )
							->disableOriginalConstructor()
							->setMethods( array( 'findFile' ) )
							->getMock();
		
		$mockFile = $this->getMockBuilder( 'File' )
						->setMethods( array( 'getMetadata' ) )
						->disableOriginalConstructor()
						->getMock();
		
		$mockFileHelper = $this->getMockBuilder( 'WikiaFileHelper' )
							->disableOriginalConstructor()
							->setMethods( array( 'getMediaDetail', 'isVideoFile' ) )
							->getMock();
		
		$mediaDetail = array( 'mediaType' => 'image' );
		$metadata = array(
				'description' => "A picture of a fluffy bunny",
				'keywords' => "Fluffy, bunny, awesome"
		);
		
		$mockTitle
			->expects	( $this->at( 0 ) )
			->method	( 'getNamespace' )
			->will		( $this->returnValue( NS_FILE ) )
		;
		$mockTitle
			->expects	( $this->at( 1 ) )
			->method	( 'getText' )
			->will		( $this->returnValue( 'foo' ) )
		;
		$mockWrapper
			->expects	( $this->at( 0 ) )
			->method	( 'findFile' )
			->will		( $this->returnValue( $mockFile ) )
		;
		$mockFileHelper
			->staticExpects	( $this->at( 0 ) )
			->method		( 'getMediaDetail' )
			->with			( $mockTitle )
			->will			( $this->returnValue( $mediaDetail ) )
		;
		$mockFile
			->expects	( $this->at( 0 ) )
			->method	( 'getMetadata' )
			->will		( $this->returnValue( '0' ) )
		;
		$mockFileHelper
			->staticExpects	( $this->at( 1 ) )
			->method		( 'isVideoFile' )
			->with			( $mockFile )
			->will			( $this->returnValue( false ) )
		;
		
		$wf = new ReflectionProperty( 'WikiaSearchIndexer', 'wf' );
		$wf->setAccessible( true );
		$wf->setValue( $mockIndexer, $mockWrapper );
		
		$this->mockClass( 'WikiaFileHelper', $mockFileHelper );
		$this->mockApp();
		
		$result = $mockIndexer->getMediaMetadata( $mockTitle );
		
		$this->assertEquals(
				'true',
				$result['is_image']
		);
		$this->assertEquals(
				'false',
				$result['is_video']
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::getMediaMetadata
	 */
	public function testGetMediaMetadataVideoFound() {
		$mockIndexer = $this->getMockBuilder( 'WikiaSearchIndexer' )
							->disableOriginalConstructor()
							->setMethods( array( 'foo' ) )
							->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
						->disableOriginalConstructor()
						->setMethods( array( 'getNamespace', 'getText' ) )
						->getMock();
		
		$mockWrapper = $this->getMockBuilder( 'WikiaFunctionWrapper' )
							->disableOriginalConstructor()
							->setMethods( array( 'findFile' ) )
							->getMock();
		
		$mockFile = $this->getMockBuilder( 'File' )
						->setMethods( array( 'getMetadata' ) )
						->disableOriginalConstructor()
						->getMock();
		
		$mockFileHelper = $this->getMockBuilder( 'WikiaFileHelper' )
							->disableOriginalConstructor()
							->setMethods( array( 'getMediaDetail', 'isVideoFile' ) )
							->getMock();
		
		$mediaDetail = array( 'mediaType' => 'video' );
		$metadata = array(
				'description' => "From Good Kid, m.A.A.d City, sampling Janet Jackson",
				'keywords' => "Kendrick Lamar, Janet Jackson, Drake, Compton, Songs that sample music from movies that star the singer's idol",
				'duration' => 12345,
				'title' => 'Kendrick Lamar: Poetic Justice (feat. Drake)',
				'hd'	=>	true,
				'actors' => 'Kendrick Lamar, Drake, Janet Jackson',
				'genres' => 'Hip Hop, R&B',
		);
		
		$mockTitle
			->expects	( $this->at( 0 ) )
			->method	( 'getNamespace' )
			->will		( $this->returnValue( NS_FILE ) )
		;
		$mockTitle
			->expects	( $this->at( 1 ) )
			->method	( 'getText' )
			->will		( $this->returnValue( 'foo' ) )
		;
		$mockWrapper
			->expects	( $this->at( 0 ) )
			->method	( 'findFile' )
			->will		( $this->returnValue( $mockFile ) )
		;
		$mockFileHelper
			->staticExpects	( $this->at( 0 ) )
			->method		( 'getMediaDetail' )
			->with			( $mockTitle )
			->will			( $this->returnValue( $mediaDetail ) )
		;
		$mockFile
			->expects	( $this->at( 0 ) )
			->method	( 'getMetadata' )
			->will		( $this->returnValue( serialize( $metadata ) ) )
		;
		$mockFileHelper
			->staticExpects	( $this->at( 1 ) )
			->method		( 'isVideoFile' )
			->with			( $mockFile )
			->will			( $this->returnValue( true ) )
		;
		
		$wf = new ReflectionProperty( 'WikiaSearchIndexer', 'wf' );
		$wf->setAccessible( true );
		$wf->setValue( $mockIndexer, $mockWrapper );
		
		$this->mockClass( 'WikiaFileHelper', $mockFileHelper );
		$this->mockApp();
		
		$result = $mockIndexer->getMediaMetadata( $mockTitle );
		
		$this->assertEquals(
				'false',
				$result['is_image']
		);
		$this->assertEquals(
				'true',
				$result['is_video']
		);
		$this->assertEquals(
				12345,
				$result['video_duration_i']
		);
		$this->assertEquals(
				'true',
				$result['video_hd_b']
		);
		$this->assertEquals(
				array( 'Hip Hop', 'R&B' ),
				$result['video_genres_txt']
		);
		$this->assertEquals(
				array( 'Kendrick Lamar', 'Drake', 'Janet Jackson' ),
				$result['video_actors_txt']
		);
		$this->assertEquals(
				array( $metadata['description'], $metadata['keywords'], $metadata['title'] ),
				$result['html_media_extras_txt']
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::getPage
	 */
	public function testGetPageInvalid() {
		$mockIndexer = $this->getMockBuilder( 'WikiaSearchIndexer' )
							->disableOriginalConstructor()
							->setMethods( array( 'foo' ) )
							->getMock();
		
		$this->proxyClass( 'Article', null, 'newFromID' );
		$this->mockApp();
		
		try {
			$mockIndexer->getPage( 123 );
		} catch ( Exception $e ) { }
		
		$this->assertInstanceOf(
				'Exception',
				$e,
				"WikiaSearchIndexer::getPage should throw an exception if the provided page ID does not yield an instance of Article from Article::newFromID"
		);
	}
	
	/**
	 * @covers WikiaSearchIndexer::getPage
	 */
	public function testGetPage() {
		
	}
}