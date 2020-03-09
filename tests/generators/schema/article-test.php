<?php

namespace Yoast\WP\SEO\Tests\Generators\Schema;

use Brain\Monkey;
use Mockery;
use stdClass;
use Yoast\WP\SEO\Helpers\Date_Helper;
use Yoast\WP\SEO\Helpers\Post_Helper;
use Yoast\WP\SEO\Helpers\Schema\Article_Helper;
use Yoast\WP\SEO\Helpers\Schema\ID_Helper;
use Yoast\WP\SEO\Helpers\Schema\HTML_Helper;
use Yoast\WP\SEO\Helpers\Schema\Language_Helper;
use Yoast\WP\SEO\Presentations\Generators\Schema\Article;
use Yoast\WP\SEO\Tests\Mocks\Indexable;
use Yoast\WP\SEO\Tests\Mocks\Meta_Tags_Context;
use Yoast\WP\SEO\Tests\TestCase;

/**
 * Class Article_Test
 *
 * @group generators
 * @group schema
 *
 * @coversDefaultClass \Yoast\WP\SEO\Presentations\Generators\Schema\Article
 * @covers ::<!public>
 */
class Article_Test extends TestCase {

	/**
	 * The article helper.
	 *
	 * @var Mockery\MockInterface|Article_Helper
	 */
	private $article;

	/**
	 * The date helper.
	 *
	 * @var Mockery\MockInterface|Date_Helper
	 */
	private $date;

	/**
	 * The instance to test.
	 *
	 * @var Article
	 */
	private $instance;

	/**
	 * The meta tags context object.
	 *
	 * @var Meta_Tags_Context
	 */
	private $context_mock;

	/**
	 * The ID helper.
	 *
	 * @var Mockery\MockInterface|ID_Helper
	 */
	private $id;

	/**
	 * The HTML helper.
	 *
	 * @var Mockery\MockInterface|HTML_Helper
	 */
	private $html;

	/**
	 * The post helper.
	 *
	 * @var Mockery\MockInterface|Post_Helper
	 */
	private $post;

	/**
	 * The language helper.
	 *
	 * @var Mockery\MockInterface|Languge_Helper
	 */
	private $language;

	/**
	 * Sets up the tests.
	 */
	public function setUp() {
		$this->id                      = Mockery::mock( ID_Helper::class );
		$this->id->article_hash        = '#article-hash';
		$this->id->webpage_hash        = '#webpage-hash';
		$this->id->primary_image_hash  = '#primary-image-hash';
		$this->article                 = Mockery::mock( Article_Helper::class );
		$this->date                    = Mockery::mock( Date_Helper::class );
		$this->html                    = Mockery::mock( HTML_Helper::class );
		$this->post                    = Mockery::mock( Post_Helper::class );
		$this->language                = Mockery::mock( Language_Helper::class );
		$this->instance                = new Article( $this->article, $this->date, $this->html, $this->post, $this->language );
		$this->context_mock            = new Meta_Tags_Context();
		$this->context_mock->indexable = new Indexable();
		$this->context_mock->post      = new stdClass();
		$this->instance->set_id_helper( $this->id );
		return parent::setUp();
	}

	/**
	 * Tests the if needed method.
	 *
	 * @covers ::__construct
	 * @covers ::is_needed
	 */
	public function test_is_needed() {
		$this->context_mock->indexable->object_type     = 'post';
		$this->context_mock->indexable->object_sub_type = 'article';
		$this->context_mock->site_represents            = 'person';
		$this->context_mock->canonical                  = 'https://permalink';

		$this->article->expects( 'is_article_post_type' )->with( 'article' )->andReturn( true );

		$this->assertTrue( $this->instance->is_needed( $this->context_mock ) );
		$this->assertSame( $this->context_mock->main_schema_id, 'https://permalink#article-hash' );
	}

	/**
	 * Tests the if needed method with no post.
	 *
	 * @covers ::__construct
	 * @covers ::is_needed
	 */
	public function test_is_needed_no_post() {
		$this->context_mock->indexable->object_type     = 'home-page';
		$this->context_mock->main_schema_id             = 'https://permalink#should-not-change';

		$this->assertFalse( $this->instance->is_needed( $this->context_mock ) );
		$this->assertSame( $this->context_mock->main_schema_id, 'https://permalink#should-not-change' );
	}

	/**
	 * Tests the if needed method with no article post type.
	 *
	 * @covers ::__construct
	 * @covers ::is_needed
	 */
	public function test_is_needed_no_article_post_type() {
		$this->context_mock->indexable->object_type     = 'post';
		$this->context_mock->indexable->object_sub_type = 'not-article';
		$this->context_mock->site_represents            = 'person';
		$this->context_mock->main_schema_id             = 'https://permalink#should-not-change';

		$this->article->expects( 'is_article_post_type' )->with( 'not-article' )->andReturn( false );

		$this->assertFalse( $this->instance->is_needed( $this->context_mock ) );
		$this->assertSame( $this->context_mock->main_schema_id, 'https://permalink#should-not-change' );
	}

	/**
	 * Tests the if needed method when the site doesn't represent a person or organization.
	 *
	 * @covers ::__construct
	 * @covers ::is_needed
	 */
	public function test_is_needed_no_site_represents() {
		$this->context_mock->indexable->object_type     = 'post';
		$this->context_mock->site_represents            = false;
		$this->context_mock->main_schema_id             = 'https://permalink#should-not-change';

		$this->assertFalse( $this->instance->is_needed( $this->context_mock ) );
		$this->assertSame( $this->context_mock->main_schema_id, 'https://permalink#should-not-change' );
	}

	/**
	 * Tests the generate method.
	 *
	 * @covers ::__construct
	 * @covers ::generate
	 */
	public function test_generate() {
		$this->context_mock->id                      = 5;
		$this->context_mock->canonical               = 'https://permalink';
		$this->context_mock->has_image               = true;
		$this->context_mock->post->post_author       = '3';
		$this->context_mock->post->post_date_gmt     = '2345-12-12 12:12:12';
		$this->context_mock->post->post_modified_gmt = '2345-12-12 23:23:23';
		$this->context_mock->post->post_type         = 'my_awesome_post_type';
		$this->context_mock->post->comment_status    = 'open';

		$this->id->expects( 'get_user_schema_id' )
			 ->once()
			 ->with( '3', $this->context_mock )
			 ->andReturn( 'https://permalink#author-id-hash' );

		$this->post->expects( 'get_post_title_with_fallback' )
			->once()
			->with( $this->context_mock->id )
			->andReturn( 'the-title </script><script>alert(0)</script><script>' ); // Script is here to test script injection

		$this->html->expects( 'smart_strip_tags' )
			->once()
			->with( 'the-title </script><script>alert(0)</script><script>' )
			->andReturn( 'the-title' );

		Monkey\Functions\expect( 'get_comment_count' )->once()->with( 5 )->andReturn( [ 'approved' => 7 ] );
		Monkey\Filters\expectApplied( 'wpseo_schema_article_keywords_taxonomy' )
			->once()
			->with( 'post_tag' )
			->andReturn( 'post_tag' );

		$terms = [
			(object) [ 'name' => 'Tag1' ],
			(object) [ 'name' => 'Tag2' ],
			(object) [ 'name' => 'Uncategorized' ],
		];
		Monkey\Functions\expect( 'get_the_terms' )->once()->with( 5, 'post_tag' )->andReturn( $terms );
		Monkey\Functions\expect( 'wp_list_pluck' )->once()->with( array_slice( $terms, 0, 2 ), 'name' )->andReturn( [ 'Tag1', 'Tag2' ] );

		Monkey\Filters\expectApplied( 'wpseo_schema_article_sections_taxonomy' )
			->once()
			->with( 'category' )
			->andReturn( 'category' );

		$categories = [ (object) [ 'name' => 'Category1' ] ];
		Monkey\Functions\expect( 'get_the_terms' )->with( 5, 'category' )->andReturn( $categories );
		Monkey\Functions\expect( 'wp_list_pluck' )->once()->with( $categories, 'name' )->andReturn( [ 'Category1' ] );

		$this->date
			->expects( 'format' )
			->once()
			->with( '2345-12-12 12:12:12' )
			->andReturn( '2345-12-12 12:12:12' );

		$this->date
			->expects( 'format' )
			->once()
			->with( '2345-12-12 23:23:23' )
			->andReturn( '2345-12-12 23:23:23' );

		$this->language->expects( 'add_piece_language' )
			->once()
			->andReturnUsing( function( $data ) {
				$data['inLanguage'] = 'language';
				return $data;
			} );

		Monkey\Functions\expect( 'post_type_supports' )
			->once()
			->with( $this->context_mock->post->post_type, 'comments' )
			->andReturn( true );

		$this->assertEquals(
			[
				'@type'            => 'Article',
				'@id'              => 'https://permalink#article-hash',
				'isPartOf'         => [ '@id' => 'https://permalink#webpage-hash' ],
				'author'           => [ '@id' => 'https://permalink#author-id-hash' ],
				'image'            => [ '@id' => 'https://permalink#primary-image-hash' ],
				'headline'         => 'the-title',
				'datePublished'    => '2345-12-12 12:12:12',
				'dateModified'     => '2345-12-12 23:23:23',
				'commentCount'     => 7,
				'mainEntityOfPage' => [ '@id' => 'https://permalink#webpage-hash' ],
				'keywords'         => 'Tag1,Tag2',
				'articleSection'   => 'Category1',
				'inLanguage'       => 'language',
				'potentialAction'  => [
					[
						'@type'  => 'CommentAction',
						'name'   => 'Comment',
						'target' => [
							'https://permalink#respond',
						],
					],
				]
			],
			$this->instance->generate( $this->context_mock )
		);
	}
}