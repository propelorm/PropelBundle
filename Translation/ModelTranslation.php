<?php

namespace Propel\PropelBundle\Translation;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Translator;

/**
 * A translation loader retrieving data from a Propel model.
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class ModelTranslation implements DumperInterface, LoaderInterface, ResourceInterface, \Serializable
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var \ModelCriteria
     */
    protected $query;

    /**
     * @var array
     */
    protected $options = array(
        'columns' => array(
            // The key and its translation ..
            'key' => 'key',
            'translation' => 'translation',
            // .. for the given locale ..
            'locale' => 'locale',
            // .. under this domain.
            'domain' => 'domain',
            // The datetime of the last update.
            'updated_at' => 'updated_at',
        ),
    );

    /**
     * @var \PDOStatement
     */
    private $resourcesStatement;

    /**
     * Constructor.
     *
     * @todo Enabled re-use of the provided query when deserializing the resource.
     *
     * @param string              $className
     * @param array               $options
     * @param \ModelCriteria|null $query     A Query to use. If null is provided a new one will be retrieved.
     *                                       Note: This query will not be re-used when checking the freshness of the resource.
     *
     * @throws \PropelException If the class is invalid and no query class could be found.
     */
    public function __construct($className, array $options = array(), \ModelCriteria $query = null)
    {
        $this->className = $className;
        $this->options = array_replace_recursive($this->options, $options);

        if (!$query) {
            $query = \PropelQuery::from($this->className);
        }

        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function registerResources(Translator $translator)
    {
        $stmt = $this->getResourcesStatement();

        if (false === $stmt->execute()) {
            throw new \RuntimeException('Could not fetch translation data from database.');
        }

        $stmt->bindColumn('locale', $locale);
        $stmt->bindColumn('domain', $domain);

        while ($stmt->fetch()) {
            $translator->addResource('propel', $this, $locale, $domain);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        // The loader only accepts itself as a resource.
        if ($resource !== $this) {
            return new MessageCatalogue($locale);
        }

        $query = clone $this->query;
        $query
            ->filterBy($this->getColumnPhpname('locale'), $locale)
            ->filterBy($this->getColumnPhpname('domain'), $domain)
        ;

        $translations = $query->find();

        $catalogue = new MessageCatalogue($locale);
        $catalogue->addResource($this);

        foreach ($translations as $eachTranslation) {
            $key = $eachTranslation->getByName($this->getColumnPhpname('key'));
            $message = $eachTranslation->getByName($this->getColumnPhpname('translation'));

            $catalogue->set($key, $message, $domain);
        }

        return $catalogue;
    }

    /**
     * {@inheritdoc}
     */
    public function dump(MessageCatalogue $messages, $options = array())
    {
        $connection = \Propel::getConnection($this->query->getDbName());
        $connection->beginTransaction();

        $now = new \DateTime();

        $locale = $messages->getLocale();
        foreach ($messages->getDomains() as $eachDomain) {
            foreach ($messages->all($eachDomain) as $eachKey => $eachTranslation) {
                $query = clone $this->query;
                $query
                    ->filterBy($this->getColumnPhpname('locale'), $locale)
                    ->filterBy($this->getColumnPhpname('domain'), $eachDomain)
                    ->filterBy($this->getColumnPhpname('key'), $eachKey)
                ;

                $translation = $query->findOneOrCreate($connection);
                $translation->setByName($this->getColumnPhpname('translation'), (string) $eachTranslation);
                $translation->setByName($this->getColumnPhpname('updated_at'), $now);

                $translation->save($connection);
            }
        }

        if (!$connection->commit()) {
            $connection->rollBack();

            throw new \RuntimeException(sprintf('An error occurred while committing the transaction. [%s: %s]', $connection->errorCode(), $connection->errorInfo()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
    {
        $query = clone $this->query;
        $query->filterBy($this->getColumnPhpname('updated_at'), new \DateTime('@'.$timestamp), \ModelCriteria::GREATER_THAN);

        return !$query->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf('PropelModelTranslation::%s', $this->className);
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this;
    }

    /**
     * Creates and caches a PDO Statement to receive available resources.
     *
     * @return \PDOStatement
     */
    private function getResourcesStatement()
    {
        if ($this->resourcesStatement instanceof \PDOStatement) {
            return $this->resourcesStatement;
        }

        $sql = vsprintf('SELECT DISTINCT `%s` AS `locale`, `%s` AS `domain` FROM `%s`', array(
            // SELECT ..
            $this->query->getTableMap()->getColumn($this->getColumnname('locale'))->getName(),
            $this->query->getTableMap()->getColumn($this->getColumnname('domain'))->getName(),
            // FROM ..
            $this->query->getTableMap()->getName(),
        ));

        $connection = \Propel::getConnection($this->query->getDbName(), \Propel::CONNECTION_READ);

        $stmt = $connection->prepare($sql);
        $stmt->setFetchMode(\PDO::FETCH_BOUND);

        $this->resourcesStatement = $stmt;

        return $stmt;
    }

    /**
     * Returns the actual column name mapped to the given column identifier.
     *
     * @param string $identifier
     *
     * @return string
     */
    private function getColumnname($identifier)
    {
        return $this->options['columns'][$identifier];
    }

    /**
     * Returns the Propel PHP name for the column identified by the given identifier.
     *
     * @param string $identifier
     *
     * @return string
     *
     * @throws \PropelException
     */
    private function getColumnPhpname($identifier)
    {
        return $this->query->getTableMap()->getColumn($this->getColumnname($identifier))->getPhpName();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            $this->className,
            $this->options,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->className,
            $this->options
        ) = unserialize($serialized);

        $this->query = \PropelQuery::from($this->className);
    }
}
