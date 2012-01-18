<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Model\Acl;

use PropelPDO;

use Propel\PropelBundle\Model\Acl\om\BaseObjectIdentity;

class ObjectIdentity extends BaseObjectIdentity
{
    public function preInsert(PropelPDO $con = null)
    {
        // Compatibility with default implementation.
        $ancestor = new ObjectIdentityAncestor();
        $ancestor->setObjectIdentityRelatedByObjectIdentityId($this);
        $ancestor->setObjectIdentityRelatedByAncestorId($this);

        $this->addObjectIdentityAncestorRelatedByAncestorId($ancestor);

        return true;
    }
}
