<?php

/*
* This file is part of the Symfony package.
*
* (c) Fabien Potencier <fabien@symfony.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Propel\Bundle\PropelBundle\Form;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\Factory\PropertyAccessDecorator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
* Represents the Propel form extension, which loads the Propel functionality.
*
* @author Joseph Rouff <rouffj@gmail.com>
*/
class PropelExtension extends AbstractExtension
{

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var ChoiceListFactoryInterface
     */
    protected $choiceListFactory;

    /**
     * PropelExtension constructor.
     *
     * @param PropertyAccessorInterface|null  $propertyAccessor
     * @param ChoiceListFactoryInterface|null $choiceListFactory
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor = null, ChoiceListFactoryInterface $choiceListFactory = null)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->choiceListFactory = $choiceListFactory ?: new PropertyAccessDecorator(new DefaultChoiceListFactory(), $this->propertyAccessor);
    }
    
    protected function loadTypes()
    {
        return array(
            new Type\ModelType($this->propertyAccessor, $this->choiceListFactory),
            new Type\TranslationCollectionType(),
            new Type\TranslationType()
        );
    }

    protected function loadTypeGuesser()
    {
        return new TypeGuesser();
    }
}
