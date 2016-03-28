<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Form\Type;

use Propel\Bundle\PropelBundle\Form\ChoiceList\PropelChoiceLoader;
use Propel\Bundle\PropelBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Map\ColumnMap;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\Factory\PropertyAccessDecorator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * ModelType class.
 *
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 *
 * Example using the preferred_choices option.
 *
 * <code>
 *  public function buildForm(FormBuilderInterface $builder, array $options)
 *  {
 *      $builder
 *          ->add('product', 'model', array(
 *              'class' => 'Model\Product',
 *              'query' => ProductQuery::create()
 *                  ->filterIsActive(true)
 *                  ->useI18nQuery($options['locale'])
 *                      ->orderByName()
 *                  ->endUse()
 *              ,
 *              'preferred_choices' => ProductQuery::create()
 *                  ->filterByIsTopProduct(true)
 *              ,
 *          ))
 *      ;
 *   }
 * </code>
 */
class ModelType extends AbstractType
{
    /**
     * @var ChoiceListFactoryInterface
     */
    private $choiceListFactory;

    /**
     * ModelType constructor.
     *
     * @param PropertyAccessorInterface|null  $propertyAccessor
     * @param ChoiceListFactoryInterface|null $choiceListFactory
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor = null, ChoiceListFactoryInterface $choiceListFactory = null)
    {
        $this->choiceListFactory = $choiceListFactory ?: new PropertyAccessDecorator(
            new DefaultChoiceListFactory(),
            $propertyAccessor
        );
    }

    /**
     * Creates the label for a choice.
     *
     * For backwards compatibility, objects are cast to strings by default.
     *
     * @param object $choice The object.
     *
     * @return string The string representation of the object.
     *
     * @internal This method is public to be usable as callback. It should not
     *           be used in user code.
     */
    public static function createChoiceLabel($choice)
    {
        return (string) $choice;
    }
    
    /**
     * Creates the field name for a choice.
     *
     * This method is used to generate field names if the underlying object has
     * a single-column integer ID. In that case, the value of the field is
     * the ID of the object. That ID is also used as field name.
     *
     * @param object     $choice The object.
     * @param int|string $key    The choice key.
     * @param string     $value  The choice value. Corresponds to the object's
     *                           ID here.
     *
     * @return string The field name.
     *
     * @internal This method is public to be usable as callback. It should not
     *           be used in user code.
     */
    public static function createChoiceName($choice, $key, $value)
    {
        return str_replace('-', '_', (string) $value);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['multiple']) {
            $builder
            #    ->addEventSubscriber(new MergeDoctrineCollectionListener())
                ->addViewTransformer(new CollectionToArrayTransformer(), true)
            ;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choiceLoader = function (Options $options) {
            // Unless the choices are given explicitly, load them on demand
            if (null === $options['choices']) {
                
                $propelChoiceLoader = new PropelChoiceLoader(
                    $this->choiceListFactory,
                    $options['class'],
                    $options['query'],
                    $options['index_property']
                );
                
                return $propelChoiceLoader;
            }
            
            return null;
        };

        $choiceName = function (Options $options) {

            /** @var ModelCriteria $query */
            $query = $options['query'];
            if ($options['index_property']) {
                $identifier = array($query->getTableMap()->getColumn($options['index_property']));
            } else {
                $identifier = $query->getTableMap()->getPrimaryKeys();
            }
            /** @var ColumnMap $firstIdentifier */
            $firstIdentifier = current($identifier);
            if (count($identifier) === 1 && $firstIdentifier->getPdoType() === \PDO::PARAM_INT) {
                return array(__CLASS__, 'createChoiceName');
            }
            return null;
        };

        $choiceValue = function (Options $options) {

            /** @var ModelCriteria $query */
            $query = $options['query'];
            if ($options['index_property']) {
                $identifier = array($query->getTableMap()->getColumn($options['index_property']));
            } else {
                $identifier = $query->getTableMap()->getPrimaryKeys();
            }
            /** @var ColumnMap $firstIdentifier */
            $firstIdentifier = current($identifier);
            if (count($identifier) === 1 && in_array($firstIdentifier->getPdoType(), [\PDO::PARAM_BOOL, \PDO::PARAM_INT, \PDO::PARAM_STR])) {
                return function($object) use ($firstIdentifier) {
                    return call_user_func([$object, 'get' . ucfirst($firstIdentifier->getPhpName())]);
                };
            }
            return null;
        };
        
        $queryNormalizer = function (Options $options, $query) {
            if ($query === null) {
                $queryClass = $options['class'] . 'Query';
                if (!class_exists($queryClass)) {
                    if (empty($options['class'])) {
                        throw new MissingOptionsException('The "class" parameter is empty, you should provide the model class');
                    }
                    throw new InvalidOptionsException(
                        sprintf(
                            'The query class "%s" is not found, you should provide the FQCN of the model class',
                            $queryClass
                        )
                    );
                }
                $query = new $queryClass();
            }
            return $query;
        };
        
        $choiceLabelNormalizer = function (Options $options, $choiceLabel) {
            if ($choiceLabel === null) {
                if ($options['property'] == null) {
                    $choiceLabel = array(__CLASS__, 'createChoiceLabel');
                } else {
                    $valueProperty = $options['property'];
                    /** @var ModelCriteria $query */
                    $query = $options['query'];
                    $getter = 'get' . ucfirst($query->getTableMap()->getColumn($valueProperty)->getPhpName());
                    
                    $choiceLabel = function($choice) use ($getter) {
                        return call_user_func([$choice, $getter]);
                    };
                }
            }
            
            return $choiceLabel;
        };
        
        $resolver->setDefaults([
            'query' => null,
            'index_property' => null,
            'property' => null,
            'choices' => null,
            'choice_loader' => $choiceLoader,
            'choice_label' => null,
            'choice_name' => $choiceName,
            'choice_value' => $choiceValue,
            'choice_translation_domain' => false,
        ]);

        $resolver->setRequired(array('class'));
        $resolver->setNormalizer('query', $queryNormalizer);
        $resolver->setNormalizer('choice_label', $choiceLabelNormalizer);
        $resolver->setAllowedTypes('query', ['null', 'Propel\Runtime\ActiveQuery\ModelCriteria']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'model';
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }
}
