<?php
namespace Propel\PropelBundle\Tests\Request\ParamConverter;

use Symfony\Component\HttpFoundation\Request;

use Propel\PropelBundle\Request\ParamConverter\PropelParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Propel\PropelBundle\Tests\TestCase;


class PropelParamConverterTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        if (!interface_exists('Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface')) {
            $this->markTestSkipped('SensioFrameworkExtraBundle is not available.');
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
     * @@expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
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

    /**
     * @@expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testParamConverterFindSlugNotFound()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('slug' => 'my-foo', 'book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));
        $paramConverter->apply($request, $configuration);
    }

    /**
     * @@expectedException LogicException
     */
    public function testParamConverterFindLogicError()
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request(array(), array(), array('book' => null));
        $configuration = new ParamConverter(array('class' => 'Propel\PropelBundle\Tests\Fixtures\Model\Book', 'name' => 'book'));
        $paramConverter->apply($request, $configuration);
    }
}
