<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Security\Acl\Domain;

use Propel\PropelBundle\Model\Acl\Entry as ModelEntry;

use Symfony\Component\Security\Acl\Model\AuditableAclInterface;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class AuditableAcl extends MutableAcl implements AuditableAclInterface
{
    /**
     * Updates auditing for class-based ACE
     *
     * @param integer $index
     * @param bool    $auditSuccess
     * @param bool    $auditFailure
     */
    public function updateClassAuditing($index, $auditSuccess, $auditFailure)
    {
        $this->updateAuditing($this->classAces, $index, $auditSuccess, $auditFailure);
    }

    /**
     * Updates auditing for class-field-based ACE
     *
     * @param integer $index
     * @param string  $field
     * @param bool    $auditSuccess
     * @param bool    $auditFailure
     */
    public function updateClassFieldAuditing($index, $field, $auditSuccess, $auditFailure)
    {
        $this->validateField($this->classFieldAces, $field);
        $this->updateAuditing($this->classFieldAces[$field], $index, $auditSuccess, $auditFailure);
    }

    /**
     * Updates auditing for object-based ACE
     *
     * @param integer $index
     * @param bool    $auditSuccess
     * @param bool    $auditFailure
     */
    public function updateObjectAuditing($index, $auditSuccess, $auditFailure)
    {
        $this->updateAuditing($this->objectAces, $index, $auditSuccess, $auditFailure);
    }

    /**
     * Updates auditing for object-field-based ACE
     *
     * @param integer $index
     * @param string  $field
     * @param bool    $auditSuccess
     * @param bool    $auditFailure
     */
    public function updateObjectFieldAuditing($index, $field, $auditSuccess, $auditFailure)
    {
        $this->validateField($this->objectFieldAces, $field);
        $this->updateAuditing($this->objectFieldAces[$field], $index, $auditSuccess, $auditFailure);
    }

    /**
     * Update auditing on a single ACE.
     *
     * @throws \InvalidArgumentException
     *
     * @param array $list
     * @param int   $index
     * @param bool  $auditSuccess
     * @param bool  $auditFailure
     *
     * @return \Propel\PropelBundle\Security\Acl\Domain\AuditableAcl $this
     */
    protected function updateAuditing(array &$list, $index, $auditSuccess, $auditFailure)
    {
        if (!is_bool($auditSuccess) or !is_bool($auditFailure)) {
            throw new \InvalidArgumentException('The given auditing flags are invalid. Please provide boolean only.');
        }

        $this->validateIndex($list, $index);

        $entry = ModelEntry::fromAclEntry($list[$index])
            ->setAuditSuccess($auditSuccess)
            ->setAuditFailure($auditFailure)
        ;

        $list[$index] = ModelEntry::toAclEntry($entry, $this);

        return $this;
    }
}
