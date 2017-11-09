<?php

namespace Propel\PropelBundle\Tests\Translation;

use Propel\PropelBundle\Tests\TestCase;
use Propel\PropelBundle\Tests\Fixtures\Model\Translation as Entry;

use Propel\PropelBundle\Translation\ModelTranslation;

use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 *
 * @covers Propel\PropelBundle\Translation\ModelTranslation
 */
class ModelTranslationTest extends TestCase
{
    const MODEL_CLASS = 'Propel\PropelBundle\Tests\Fixtures\Model\Translation';

    /**
     * @var \PropelPDO
     */
    protected $con;

    public function setUp()
    {
        parent::setUp();

        $this->loadPropelQuickBuilder();

        $schema = file_get_contents(__DIR__.'/../Fixtures/translation_schema.xml');

        $builder = new \PropelQuickBuilder();
        $builder->setSchema($schema);
        if (class_exists('Propel\PropelBundle\Tests\Fixtures\Model\map\TranslationTableMap')) {
            $builder->setClassTargets(array());
        }

        $this->con = $builder->build();
    }

    public function testRegisterResources()
    {
        $translation = new Entry();
        $translation
            ->setKey('example.key')
            ->setMessage('This is an example translation.')
            ->setLocale('en_US')
            ->setDomain('test')
            ->setUpdatedAt(new \DateTime())
            ->save()
        ;

        $resource = $this->getResource();

        $translator = $this->getMock('Symfony\Component\Translation\Translator', array(), array('en_US'));
        $translator
            ->expects($this->once())
            ->method('addResource')
            ->with('propel', $resource, 'en_US', 'test')
        ;

        $resource->registerResources($translator);
    }

    public function testIsFreshWithoutEntries()
    {
        $resource = $this->getResource();

        $this->assertTrue($resource->isFresh(date('U')));
    }

    public function testIsFreshUpdates()
    {
        $date = new \DateTime('-2 minutes');

        $translation = new Entry();
        $translation
            ->setKey('example.key')
            ->setMessage('This is an example translation.')
            ->setLocale('en_US')
            ->setDomain('test')
            ->setUpdatedAt($date)
            ->save()
        ;

        $resource = $this->getResource();

        $timestamp = (int) $date->format('U');

        $this->assertFalse($resource->isFresh($timestamp - 10));
    }

    public function testLoadInvalidResource()
    {
        $invalidResource = $this->getMock('Symfony\Component\Config\Resource\ResourceInterface');

        $resource = $this->getResource();
        $catalogue = $resource->load($invalidResource, 'en_US');

        $this->assertEmpty($catalogue->getResources());
    }

    public function testLoadFiltersLocaleAndDomain()
    {
        $date = new \DateTime();

        $translation = new Entry();
        $translation
            ->setKey('example.key')
            ->setMessage('This is an example translation.')
            ->setLocale('en_US')
            ->setDomain('test')
            ->setUpdatedAt($date)
            ->save()
        ;

        // different locale
        $translation = new Entry();
        $translation
            ->setKey('example.key')
            ->setMessage('Das ist eine Beispielübersetzung.')
            ->setLocale('de_DE')
            ->setDomain('test')
            ->setUpdatedAt($date)
            ->save()
        ;

        // different domain
        $translation = new Entry();
        $translation
            ->setKey('example.key')
            ->setMessage('This is an example translation.')
            ->setLocale('en_US')
            ->setDomain('test2')
            ->setUpdatedAt($date)
            ->save()
        ;

        $resource = $this->getResource();
        $catalogue = $resource->load($resource, 'en_US', 'test');

        $this->assertInstanceOf('Symfony\Component\Translation\MessageCatalogue', $catalogue);
        $this->assertEquals('en_US', $catalogue->getLocale());

        $expected = array(
            'test' => array(
                'example.key' => 'This is an example translation.',
            ),
        );

        $this->assertEquals($expected, $catalogue->all());
    }

    public function testDump()
    {
        $catalogue = new MessageCatalogue('en_US', array(
            'test' => array(
                'example.key' => 'This is an example translation.',
            ),
            'test2' => array(
                'example.key' => 'This is an example translation.',
            ),
        ));

        $resource = $this->getResource();
        $this->assertEmpty($resource->load($resource, 'en_US', 'test')->all());

        $resource->dump($catalogue);

        $stmt = $this->con->prepare('SELECT `key`, `message`, `locale`, `domain` FROM `translation`;');
        $stmt->execute();

        $result = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }

        $expected = array(
            array(
                'key' => 'example.key',
                'message' => 'This is an example translation.',
                'locale' => 'en_US',
                'domain' => 'test',
            ),
            array(
                'key' => 'example.key',
                'message' => 'This is an example translation.',
                'locale' => 'en_US',
                'domain' => 'test2',
            ),
        );

        $this->assertEquals($expected, $result);
    }

    protected function getResource()
    {
        return new ModelTranslation(self::MODEL_CLASS, array(
            'columns' => array(
                'translation' => 'message',
            ),
        ));
    }
}
