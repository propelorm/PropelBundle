<?php

namespace Propel\PropelBundle\Tests\Fixtures\DataFixtures\Loader\map;

use \RelationMap;
use \TableMap;


/**
 * This class defines the structure of the 'author' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 * @package    propel.generator.vendor.bundles.Propel.PropelBundle.Tests.Fixtures.DataFixtures.Loader.map
 */
class BookAuthorTableMap extends TableMap
{

	/**
	 * The (dot-path) name of this class
	 */
	const CLASS_NAME = 'vendor.bundles.Propel.PropelBundle.Tests.Fixtures.DataFixtures.Loader.map.BookAuthorTableMap';

	/**
	 * Initialize the table attributes, columns and validators
	 * Relations are not initialized by this method since they are lazy loaded
	 *
	 * @return     void
	 * @throws     PropelException
	 */
	public function initialize()
	{
		// attributes
		$this->setName('book_author');
		$this->setPhpName('BookAuthor');
		$this->setClassname('Propel\\PropelBundle\\Tests\\Fixtures\\DataFixtures\\Loader\\BookAuthor');
		$this->setPackage('vendor.bundles.Propel.PropelBundle.Tests.Fixtures.DataFixtures.Loader');
		$this->setUseIdGenerator(false);
		// columns
		$this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
		$this->addColumn('NAME', 'Name', 'VARCHAR', false, 255, null);
		// validators
	} // initialize()

	/**
	 * Build the RelationMap objects for this table relationships
	 */
	public function buildRelations()
	{
		$this->addRelation('Book', 'Propel\\PropelBundle\\Tests\\Fixtures\\DataFixtures\\Loader\\Book', RelationMap::ONE_TO_MANY, array('id' => 'author_id', ), 'RESTRICT', 'CASCADE', 'Books');
	} // buildRelations()

} // AuthorTableMap
