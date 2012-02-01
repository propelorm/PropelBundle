<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Model\Acl;

use Criteria;
use PropelPDO;

use Propel\PropelBundle\Model\Acl\om\BaseObjectIdentity;

class ObjectIdentity extends BaseObjectIdentity
{
    /**
     * A temporary collection used on deletion.
     *
     * @var PropelCollection
     */
    protected $oldAncestors;

    public function preInsert(PropelPDO $con = null)
    {
        // Compatibility with default implementation.
        $ancestor = new ObjectIdentityAncestor();
        $ancestor->setObjectIdentityRelatedByObjectIdentityId($this);
        $ancestor->setObjectIdentityRelatedByAncestorId($this);

        $this->addObjectIdentityAncestorRelatedByAncestorId($ancestor);

        if ($this->getParentObjectIdentityId()) {
            $this->updateAncestorsTree($con);
        }

        return true;
    }

    public function preUpdate(PropelPDO $con = null)
    {
        if ($this->isColumnModified(ObjectIdentityPeer::PARENT_OBJECT_IDENTITY_ID)) {
            $this->updateAncestorsTree($con);
        }

        return true;
    }

    public function preDelete(PropelPDO $con = null)
    {
        /*
         * Save the ancestors prior deletion.
         * The ancestors entries will be deleted due to CASCADE.
         */
        $this->oldAncestors = ObjectIdentityQuery::create()->findAncestors($this, $con);

        return true;
    }

    public function postDelete(PropelPDO $con = null)
    {
        $this->updateAncestorsTree($con);

        return true;
    }

    /**
     * Update all ancestor entries to reflect changes on this instance.
     *
     * @param PropelPDO $con
     *
     * @return ObjectIdentity $this
     */
    protected function updateAncestorsTree(PropelPDO $con = null)
    {
        if ($this->isDeleted()) {
            $oldAncestors = $this->oldAncestors;
        } else {
            $oldAncestors = ObjectIdentityQuery::create()->findAncestors($this, $con);
        }

        $children = ObjectIdentityQuery::create()->findGrandChildren($this, $con);
        $children->append($this);
        foreach ($children as $eachChild) {
            /*
             * Delete only those entries, that are ancestors based on the parent relation.
             * Ancestors of grand children up to the current node will be kept.
             */
            $query = ObjectIdentityAncestorQuery::create()->filterByObjectIdentityId($eachChild->getId());

            if (count($oldAncestors)) {
                $query->filterByObjectIdentityRelatedByAncestorId($oldAncestors, Criteria::IN);
            }

            if ($eachChild->getId() !== $this->getId()) {
                $query->filterByAncestorId(array($eachChild->getId(), $this->getId()), Criteria::NOT_IN);
            } else {
                $query->filterByAncestorId($this->getId(), Criteria::NOT_EQUAL);
            }

            $query->delete($con);
        }

        // A deleted object identity does not have a parent anymore.
        if ($this->isDeleted()) {
            return $this;
        }

        // This is the new parent object identity!
        $parent = $this->getObjectIdentityRelatedByParentObjectIdentityId($con);
        if (null !== $parent) {
            $newAncestors = ObjectIdentityQuery::create()->findAncestors($parent, $con);
            $newAncestors->append($parent);
            foreach ($newAncestors as $eachAncestor) {
                // This collection contains the current object identity!
                foreach ($children as $eachChild) {
                    $ancestor = ObjectIdentityAncestorQuery::create()
                        ->filterByObjectIdentityId($eachChild->getId())
                        ->filterByAncestorId($eachAncestor->getId())
                        ->findOneOrCreate($con)
                    ;

                    // If the entry already exists, next please.
                    if (!$ancestor->isNew()) {
                        continue;
                    }

                    if ($eachChild->getId() === $this->getId()) {
                        // Do not save() here, as it would result in an infinite recursion loop!
                        $this->addObjectIdentityAncestorRelatedByObjectIdentityId($ancestor);
                    } else {
                        $eachChild
                            ->addObjectIdentityAncestorRelatedByObjectIdentityId($ancestor)
                            ->save($con)
                        ;
                    }
                }
            }
        }

        return $this;
    }
}
