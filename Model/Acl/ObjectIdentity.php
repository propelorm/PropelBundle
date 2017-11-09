<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Model\Acl;

use Propel\PropelBundle\Model\Acl\om\BaseObjectIdentity;

class ObjectIdentity extends BaseObjectIdentity
{
    public function preInsert(\PropelPDO $con = null)
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

    public function preUpdate(\PropelPDO $con = null)
    {
        if ($this->isColumnModified(ObjectIdentityPeer::PARENT_OBJECT_IDENTITY_ID)) {
            $this->updateAncestorsTree($con);
        }

        return true;
    }

    public function preDelete(\PropelPDO $con = null)
    {
        // Only retrieve direct children, it's faster and grand children will be retrieved recursively.
        $children = ObjectIdentityQuery::create()->findChildren($this, $con);

        $objIds = $children->getPrimaryKeys(false);
        $objIds[] = $this->getId();

        $children->delete($con);

        // Manually delete those for DBAdapter not capable of cascading the DELETE.
        ObjectIdentityAncestorQuery::create()
            ->filterByObjectIdentityId($objIds, \Criteria::IN)
            ->delete($con)
        ;

        return true;
    }

    /**
     * Update all ancestor entries to reflect changes on this instance.
     *
     * @param \PropelPDO $con
     *
     * @return \Propel\PropelBundle\Model\Acl\ObjectIdentity $this
     */
    protected function updateAncestorsTree(\PropelPDO $con = null)
    {
        $con->beginTransaction();

        $oldAncestors = ObjectIdentityQuery::create()->findAncestors($this, $con);

        $children = ObjectIdentityQuery::create()->findGrandChildren($this, $con);
        $children->append($this);

        if (count($oldAncestors)) {
            foreach ($children as $eachChild) {
                /*
                 * Delete only those entries, that are ancestors based on the parent relation.
                 * Ancestors of grand children up to the current node will be kept.
                 */
                $query = ObjectIdentityAncestorQuery::create()
                    ->filterByObjectIdentityId($eachChild->getId())
                    ->filterByObjectIdentityRelatedByAncestorId($oldAncestors, \Criteria::IN)
                ;

                if ($eachChild->getId() !== $this->getId()) {
                    $query->filterByAncestorId(array($eachChild->getId(), $this->getId()), \Criteria::NOT_IN);
                } else {
                    $query->filterByAncestorId($this->getId(), \Criteria::NOT_EQUAL);
                }

                $query->delete($con);
            }
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
                        // Save the new ancestor to avoid integrity constraint violation.
                        $ancestor->save($con);

                        $eachChild
                            ->addObjectIdentityAncestorRelatedByObjectIdentityId($ancestor)
                            ->save($con)
                        ;
                    }
                }
            }
        }

        $con->commit();

        return $this;
    }
}
