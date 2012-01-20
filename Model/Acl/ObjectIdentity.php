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
     * The parent id that has been unset.
     *
     * @var int
     */
    protected $previousParentId = null;

    public function setParentObjectIdentityId($v)
    {
        $prev = $this->getParentObjectIdentityId();

        parent::setParentObjectIdentityId($v);

        if ($this->isColumnModified(ObjectIdentityPeer::PARENT_OBJECT_IDENTITY_ID)) {
            $this->previousParentId = $prev;
        }

        return $this;
    }

    public function preInsert(PropelPDO $con = null)
    {
        // Compatibility with default implementation.
        $ancestor = new ObjectIdentityAncestor();
        $ancestor->setObjectIdentityRelatedByObjectIdentityId($this);
        $ancestor->setObjectIdentityRelatedByAncestorId($this);

        $this->addObjectIdentityAncestorRelatedByAncestorId($ancestor);

        $this->updateAncestorsTree($con);

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
        $this->previousParentId = $this->getParentObjectIdentityId();

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
        if (null !== $this->previousParentId) {
            $childrenIds = array();
            $children = ObjectIdentityQuery::create()->findGrandChildren($this, $con);
            foreach ($children as $eachChild) {
                $childrenIds[] = $eachChild->getId();
            }

            ObjectIdentityAncestorQuery::create()
                ->filterByObjectIdentityId($childrenIds)
                ->filterByAncestorId($this->previousParentId)
                ->delete($con)
            ;
        } else {
            $parent = $this->getObjectIdentityRelatedByParentObjectIdentityId($con);

            $children = ObjectIdentityQuery::create()->findGrandChildren($this, $con);
            foreach ($children as $eachChild) {
                $ancestor = ObjectIdentityAncestorQuery::create()
                    ->filterByObjectIdentityId($eachChild->getId())
                    ->filterByAncestorId($parent->getId())
                    ->findOneOrCreate($con)
                ;

                if (!$ancestor->isNew()) {
                    continue;
                }

                $eachChild
                    ->addObjectIdentityAncestorRelatedByObjectIdentityId($ancestor)
                    ->save($con)
                ;
            }
        }

        return $this;
    }
}
