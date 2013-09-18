<?php
namespace Propel\PropelBundle\Tests\Request\ParamConverter;

use Symfony\Component\HttpFoundation\Request;

use Propel\PropelBundle\Request\ParamConverter\PropelParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Propel\PropelBundle\Tests\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PropelParamConverterTest extends TestCase
{

    protected $con;

    public function setUp()
    {
        parent::setUp();
        if (!interface_exists('Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface')) {
            $this->markTestSkipped('SensioFrameworkExtraBundle is not available.');
        }

        \Propel::disableInstancePooling();
    }

    public function tearDown()
    {
        \Propel::enableInstancePooling();
        if ($this->con) {
            $this->con->useDebug(false);
        }
    }

    public function testParamConverterSupport()
    {
        $paramConverter = new PropelParamConverter();

        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book'));
        $this->assertTrue($paramConverter->supports($configuration), 'param converter should support propel class');

        $configuration = new ParamConverter(array('class' =>'fakeClass'));
        $this->assertFalse($paramConverter->supports($configuration), 'param converter should not support wrong class');

        $configuration = new ParamConverter(array('class' =>'Propel\PropelBundle\Tests\TestCase'));
        $this->assertFalse($paramConverter->supports($configuration), 'param converter should not support wrong class');
    }

    public function testParamConverterFindPk()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('id' => 1, 'book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));
        $paramConverter->apply($request, $configuration);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\Model\Book',$request->attributes->get('book'),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testParamConverterFindPkNotFound()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('id' => 2, 'book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));
        $paramConverter->apply($request, $configuration);
    }

    public function testParamConverterFindSlug()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('slug' => 'my-book', 'book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));
        $paramConverter->apply($request, $configuration);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\Model\Book',$request->attributes->get('book'),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    public function testParamConverterFindCamelCasedSlug()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('author_slug' => 'my-author', 'slug' => 'my-kewl-book', 'book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));

        $paramConverter->apply($request, $configuration);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\Model\Book',$request->attributes->get('book'),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testParamConverterFindSlugNotFound()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('slug' => 'my-foo', 'book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));
        $paramConverter->apply($request, $configuration);
    }

    public function testParamConverterFindBySlugNotByName()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('slug' => 'my-book', 'name' => 'foo', 'book' => null));
        $configuration = new ParamConverter(array(
            'class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book',
            'options' => array('exclude' => array('name'))));
        $paramConverter->apply($request, $configuration);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\Model\Book',$request->attributes->get('book'),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @expectedException LogicException
     */
    public function testParamConverterFindByAllParamExcluded()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('slug' => 'my-book', 'name' => 'foo', 'book' => null));
        $configuration = new ParamConverter(array(
            'class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book',
            'options' => array('exclude' => array('name', 'slug'))));
        $paramConverter->apply($request, $configuration);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\Model\Book',$request->attributes->get('book'),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @expectedException LogicException
     */
    public function testParamConverterFindByIdExcluded()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('id' => '1234', 'book' => null));
        $configuration = new ParamConverter(array(
            'class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book',
            'options' => array('exclude' => array('id'))));
        $paramConverter->apply($request, $configuration);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\Model\Book',$request->attributes->get('book'),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @expectedException LogicException
     */
    public function testParamConverterFindLogicError()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));
        $paramConverter->apply($request, $configuration);
    }

    public function testParamConverterFindWithOptionalParam()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));
        $configuration->setIsOptional(true);
        $paramConverter->apply($request, $configuration);

        $this->assertNull($request->attributes->get('book'),
            'param "book" should be null if book is not found and the parameter is optional');
    }

    public function testParamConverterFindWithMapping()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('toto' => 1, 'book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book',
            'name' => 'book',
            'options' => array('mapping' => array('toto' => 'id'))
        ));
        $paramConverter->apply($request, $configuration);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\Model\Book',$request->attributes->get('book'),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    public function testParamConverterFindSlugWithMapping()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('slugParam_special' => 'my-book', 'book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book',
            'name' => 'book',
            'options' => array('mapping' => array('slugParam_special' => 'slug'))
        ));
        $paramConverter->apply($request, $configuration);
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Fixtures\Model\Book',$request->attributes->get('book'),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    public function testParamConvertWithOptionWith()
    {
        $this->loadFixtures();

        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('id' => 1, 'book' => null));
        $configuration = new ParamConverter(array(
            'class' => 'Propel\PropelBundle\Tests\Request\ParamConverter\MyBook',
            'name' => 'book',
            'options' => array(
                'with' => 'MyAuthor'
            )
        ));

        $nb = $this->con->getQueryCount();
        $paramConverter->apply($request, $configuration);

        $book = $request->attributes->get('book');
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Request\ParamConverter\MyBook', $book,
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Request\ParamConverter\MyBook"');

        $this->assertEquals($nb +1, $this->con->getQueryCount(), 'only one query to get the book');

        $this->assertInstanceOf('Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor', $book->getMyAuthor(),
            'param "book" should be an instance of "Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor"');

        $this->assertEquals($nb +1, $this->con->getQueryCount(), 'no new query to get the author');
        \Propel::enableInstancePooling();
    }

    public function testParamConvertWithOptionWithLeftJoin()
    {
        $this->loadFixtures();

        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('param1' => 10, 'author' => null));
        $configuration = new ParamConverter(array(
            'class' => 'Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor',
            'name' => 'author',
            'options' => array(
                'with'      => array(array('MyBook', 'left join')),
                'mapping'   => array('param1' => 'id'),
            )
        ));

        $nb = $this->con->getQueryCount();
        $paramConverter->apply($request, $configuration);

        $author = $request->attributes->get('author');
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor', $author,
            'param "author" should be an instance of "Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor"');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'only one query to get the book');

        $books = $author->getMyBooks();
        $this->assertInstanceOf('PropelObjectCollection', $books);
        $this->assertCount(2, $books, 'Author should have two books');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'no new query to get the books');
    }

    public function testParamConvertWithOptionWithFindPk()
    {
        $this->loadFixtures();

        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('id' => 10, 'author' => null));
        $configuration = new ParamConverter(array(
                'class' => 'Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor',
                'name' => 'author',
                'options' => array(
                        'with'      => array(array('MyBook', 'left join')),
                )
        ));

        $nb = $this->con->getQueryCount();
        $paramConverter->apply($request, $configuration);

        $author = $request->attributes->get('author');
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor', $author,
                'param "author" should be an instance of "Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor"');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'only one query to get the book');

        $books = $author->getMyBooks();
        $this->assertInstanceOf('PropelObjectCollection', $books);
        $this->assertCount(2, $books, 'Author should have two books');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'no new query to get the books');
    }

    public function testConfigurationReadFromRouteOptionsIfEmpty()
    {
        $routes = new RouteCollection();
        $routes->add('test_route', new Route('/test/{authorId}', array(), array(), array(
            'propel_converter' => array(
                'author' => array(
                    'mapping' => array(
                        'authorId' => 'id',
                    ),
                ),
            ),
        )));

        $router = $this->getMock('Symfony\Bundle\FrameworkBundle\Routing\Router', array(), array(), '', false);
        $router
            ->expects($this->once())
            ->method('getRouteCollection')
            ->will($this->returnValue($routes))
        ;

        $paramConverter = new PropelParamConverter();
        $paramConverter->setRouter($router);

        $request = new Request();
        $request->attributes->add(array(
            '_route' => 'test_route',
            'id' => 10,
            'author' => null,
        ));

        $configuration = new ParamConverter(array(
            'class' => 'Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor',
            'name' => 'author',
            'options' => array(),
        ));

        $paramConverter->apply($request, $configuration);

        $author = $request->attributes->get('author');
        $this->assertInstanceOf('Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor', $author,
                'param "author" should be an instance of "Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor"');
    }

    protected function loadFixtures()
    {
        $this->loadPropelQuickBuilder();

        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.PropelBundle.Tests.Request.DataFixtures.Loader"
    namespace="Propel\PropelBundle\Tests\Request\ParamConverter" defaultIdMethod="native">
    <table name="my_book">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
        <column name="my_author_id" type="integer" required="true" />

        <foreign-key foreignTable="my_author" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="my_author_id" foreign="id" />
        </foreign-key>
    </table>

    <table name="my_author">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>
</database>
XML;
        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);

        if (class_exists('Propel\PropelBundle\Tests\Request\ParamConverter\MyAuthor')) {
            $builder->setClassTargets(array());
        }

        $this->con = $builder->build();
        $this->con->useDebug(true);

        MyBookQuery::create()->deleteAll($this->con);
        MyAuthorQuery::create()->deleteAll($this->con);

        $author = new MyAuthor();
        $author->setId(10);
        $author->setName('Will');

        $book = new MyBook();
        $book->setId(1);
        $book->setName('PropelBook');
        $book->setMyAuthor($author);

        $book2 = new MyBook();
        $book2->setId(2);
        $book2->setName('sf2lBook');
        $book2->setMyAuthor($author);

        $author->save($this->con);
    }
}
