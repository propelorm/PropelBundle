<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Security\Acl;

use Exception;
use InvalidArgumentException;

use Criteria;
use Propel;
use PropelPDO;
use PropelCollection;

use Propel\PropelBundle\Model\Acl\Entry as ModelEntry;
use Propel\PropelBundle\Model\Acl\EntryPeer;
use Propel\PropelBundle\Model\Acl\EntryQuery;
use Propel\PropelBundle\Model\Acl\SecurityIdentity;
use Propel\PropelBundle\Model\Acl\ObjectIdentity;
use Propel\PropelBundle\Model\Acl\ObjectIdentityQuery;

use Propel\PropelBundle\Security\Acl\Domain\Acl;
use Propel\PropelBundle\Security\Acl\Domain\MutableAcl;
use Propel\PropelBundle\Security\Acl\Domain\Entry;

use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Exception\Exception as AclException;

use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\FieldEntryInterface;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

/**
 * An implementation of the MutableAclProviderInterface using Propel ORM.
 *
 * @todo Add handling of AclCacheInterface.
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class MutableAclProvider extends AclProvider implements MutableAclProviderInterface
{
    /**
     * Constructor.
     *
     * @param PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param PropelPDO $connection
     * @param AclCacheInterface $cache
     */
    public function __construct(PermissionGrantingStrategyInterface $permissionGrantingStrategy, PropelPDO $connection = null, AclCacheInterface $cache = null)
    {
        if (null === $connection) {
            $connection = Propel::getConnection(EntryPeer::DATABASE_NAME, Propel::CONNECTION_WRITE);
        }

        parent::__construct($permissionGrantingStrategy, $connection, $cache);
    }

    /**
     * Creates a new ACL for the given object identity.
     *
     * @throws AclAlreadyExistsException When there already is an ACL for the given object identity.
     *
     * @param ObjectIdentityInterface $objectIdentity
     *
     * @return MutableAclInterface
     */
    public function createAcl(ObjectIdentityInterface $objectIdentity)
    {
        $entries = EntryQuery::create()->findByAclIdentity($objectIdentity, array(), $this->connection);
        if (count($entries)) {
            throw new AclAlreadyExistsException('An ACL for the given object identity already exists, find and update that one.');
        }

        $objIdentity = ObjectIdentityQuery::create()
            ->filterByAclObjectIdentity($objectIdentity, $this->connection)
            ->findOneOrCreate($this->connection)
        ;

        if ($objIdentity->isNew()) {
            // This is safe to do, it makes the ID available and does not affect changes to any ACL.
            $objIdentity->save($this->connection);
        }

        return new MutableAcl($entries, $objectIdentity, $this->permissionGrantingStrategy, array(), null, false, $this->connection);
    }

    /**
     * Deletes the ACL for a given object identity.
     *
     * This will automatically trigger a delete for any child ACLs. If you don't
     * want child ACLs to be deleted, you will have to set their parent ACL to null.
     *
     * @throws AclException
     *
     * @param ObjectIdentityInterface $objectIdentity
     *
     * @return bool
     */
    public function deleteAcl(ObjectIdentityInterface $objectIdentity)
    {
        try {
            $objIdentity = ObjectIdentityQuery::create()->findOneByAclObjectIdentity($objectIdentity, $this->connection);
            if (null === $objIdentity) {
                // No object identity, no ACL, so deletion is successful (expected result is given).
                return true;
            }

            $this->connection->beginTransaction();

            // Retrieve all class and class-field ACEs, if any.
            $aces = EntryQuery::create()->findByAclIdentity($objectIdentity, array(), $this->connection);
            if (count($aces)) {
                // In case this is the last of its kind, delete the class and class-field ACEs.
                $count = ObjectIdentityQuery::create()->filterByClassId($objIdentity->getClassId())->count($this->connection);
                if (1 === $count) {
                    foreach ($aces as $eachAce) {
                        $eachAce->delete($this->connection);
                    }
                }
            }

            // This deletes all object and object-field ACEs, too.
            $objIdentity->delete($this->connection);

            $this->connection->commit();

            return true;
        } catch (Exception $e) {
            throw new AclException('An error occurred while deleting the ACL.', 1, $e);
        }
    }

    /**
     * Persists any changes which were made to the ACL, or any associated access control entries.
     *
     * Changes to parent ACLs are not persisted.
     *
     * @throws AclException
     *
     * @param MutableAclInterface $acl
     *
     * @return bool
     */
    public function updateAcl(MutableAclInterface $acl)
    {
        if (!$acl instanceof Acl) {
            throw new InvalidArgumentException('The given ACL is not tracked by this provider. Please provide Propel\PropelBundle\Security\Acl\Domain\Acl only.');
        }

        try {
            $modelEntries = EntryQuery::create()->findByAclIdentity($acl->getObjectIdentity(), array(), $this->connection);
            $objectIdentity = ObjectIdentityQuery::create()->findOneByAclObjectIdentity($acl->getObjectIdentity(), $this->connection);

            $this->connection->beginTransaction();

            $keepEntries = array_merge(
                $this->persistAcl($acl->getClassAces(), $objectIdentity),
                $this->persistAcl($acl->getObjectAces(), $objectIdentity, true)
            );

            foreach ($acl->getFields() as $eachField) {
                $keepEntries = array_merge($keepEntries,
                    $this->persistAcl($acl->getClassFieldAces($eachField), $objectIdentity),
                    $this->persistAcl($acl->getObjectFieldAces($eachField), $objectIdentity, true)
                );
            }

            foreach ($modelEntries as &$eachEntry) {
                if (!in_array($eachEntry->getId(), $keepEntries)) {
                    $eachEntry->delete($this->connection);
                }
            }

            if (null === $acl->getParentAcl()) {
                $objectIdentity
                    ->setParentObjectIdentityId(null)
                    ->save($this->connection)
                ;
            } else {
                $objectIdentity
                    ->setParentObjectIdentityId($acl->getParentAcl()->getId())
                    ->save($this->connection)
                ;
            }

            $this->connection->commit();

            return true;
        } catch (Exception $e) {
            $this->connection->rollBack();

            throw new AclException('An error occurred while updating the ACL.', 0, $e);
        }
    }

    /**
     * Persist the given ACEs.
     *
     * @param array $accessControlEntries
     * @param ObjectIdentity $objectIdentity
     * @param bool $object
     *
     * @return array The IDs of the persisted ACEs.
     */
    protected function persistAcl(array $accessControlEntries, ObjectIdentity $objectIdentity, $object = false)
    {
        $entries = array();

        /* @var $eachAce \Symfony\Component\Security\Acl\Model\EntryInterface */
        foreach ($accessControlEntries as $order => $eachAce) {
            // If the given ACE has never been persisted, create a new one.
            if (null === $entry = $this->getPersistedAce($eachAce)) {
                $entry = new ModelEntry();
            }

            if ($eachAce instanceof FieldEntryInterface) {
                $entry->setFieldName($eachAce->getField());
            }

            $entry
                ->setAceOrder($order)
                ->setAclClass($objectIdentity->getAclClass())
                ->setMask($eachAce->getMask())
                ->setGranting($eachAce->isGranting())
                ->setGrantingStrategy($eachAce->getStrategy())
                ->setSecurityIdentity(SecurityIdentity::fromAclIdentity($eachAce->getSecurityIdentity()))
            ;

            if (true === $object) {
                $entry->setObjectIdentity($objectIdentity);
            }

            $entry->save($this->connection);

            $entries[] = $entry->getId();
        }

        return $entries;
    }

    /**
     * Retrieve the persisted model for the given ACE.
     *
     * If none is given, null is returned.
     *
     * @param Entry $ace
     *
     * @return ModelEntry|null
     */
    protected function getPersistedAce(Entry $ace)
    {
        if (null === $ace->getId()) {
            return null;
        }

        if (null === $entry = EntryQuery::create()->findPk($ace->getId(), $this->connection)) {
            return null;
        }

        // Retrieve fresh data from the database not from any caching.
        $entry->reload(false, $this->connection);

        return $entry;
    }

    /**
     * Get an ACL for this provider.
     *
     * @param PropelCollection $collection
     * @param ObjectIdentityInterface $objectIdentity
     * @param array $loadedSecurityIdentities
     * @param AclInterface $parentAcl
     * @param bool $inherited
     *
     * @return MutableAcl
     */
    protected function getAcl(PropelCollection $collection, ObjectIdentityInterface $objectIdentity, array $loadedSecurityIdentities = array(), AclInterface $parentAcl = null, $inherited = true)
    {
        return new MutableAcl($collection, $objectIdentity, $this->permissionGrantingStrategy, $loadedSecurityIdentities, $parentAcl, $inherited, $this->connection);
    }
}