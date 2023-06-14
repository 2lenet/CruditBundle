<?php

/**
 *  This file is part of the Lego project.
 *
 *   (c) Joris Saenger <joris.saenger@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Lle\CruditBundle\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Form as Form;
use Symfony\Component\PropertyAccess\PropertyAccess as PropertyAccess;
use Doctrine\ORM\Query as Query;
use Gedmo\Translatable\TranslatableListener as TranslatableListener;
use Gedmo\Mapping\Annotation as Gedmo;

class GedmoTranslatableFieldManager
{
    public const GEDMO_TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';
    public const GEDMO_TRANSLATION_WALKER = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';
    public const GEDMO_PERSONAL_TRANSLATIONS_GET = 'getTranslations';
    public const GEDMO_PERSONAL_TRANSLATIONS_SET = 'addTranslation';
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getTranslationRepository($entity)
    {
        $reflectionClass = new \ReflectionClass(get_class($entity));
        $r = new AnnotationReader();
        $annotation = $r->getClassAnnotation($reflectionClass, Gedmo\TranslationEntity::class);
        if ($annotation) {
            return $this->em->getRepository($annotation->class);
        } else {
            return $this->em->getRepository(self::GEDMO_TRANSLATION);
        }
    }

    private function getTranslations($entity, $fieldName)
    {
        if (\method_exists($entity, self::GEDMO_PERSONAL_TRANSLATIONS_GET) && \is_callable(
                [$entity, self::GEDMO_PERSONAL_TRANSLATIONS_GET]
            )) {
            $translations = [];
            foreach ($entity->getTranslations() as $translation) {
                if ($translation->getField() == $fieldName) {
                    $translations[$translation->getLocale()] = $translation->getContent();
                }
            }

            return $translations;
        } else {
            // 'basic' translations (ext_translations table)
            return \array_map(function ($element) {
                return \array_shift($element);
            }, $this->getTranslationRepository($entity)->findTranslations($entity));
        }
    }

    private function getEntityInDefaultLocale($entity, $defaultLocale)
    {
        $class = \get_class($entity);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $identifierField = $this->em->getClassMetadata($class)->getIdentifier()[0]; // <- none composite keys only
        $identifierValue = $propertyAccessor->getValue($entity, $identifierField);
        $entityInDefaultLocale = $this->em->getRepository($class)->createQueryBuilder('entity')
            ->select("entity")
            ->where("entity.$identifierField = :identifier")
            ->setParameter('identifier', $identifierValue)
            ->setMaxResults(1)
            ->getQuery()
            ->useQueryCache(false)
            ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::GEDMO_TRANSLATION_WALKER)
            ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $defaultLocale)
            ->getOneOrNullResult();

        return $entityInDefaultLocale;
    }

    // SELECT
    public function getTranslatedFields($entity, $fieldName, $defaultLocale)
    {
        // 1/3 entity in default locale
        $entityInDefaultLocale = $this->getEntityInDefaultLocale($entity, $defaultLocale);
        // 2/3 translations
        $translations = $this->getTranslations($entity, $fieldName);
        // 3/3 translations + default
        if ($entityInDefaultLocale) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $translations[$defaultLocale] = $propertyAccessor->getValue($entityInDefaultLocale, $fieldName);
        }

        return $translations;
    }

    private function getPersonalTranslationClassName($entity)
    {
        $metadata = $this->em->getClassMetadata(\get_class($entity));

        return $metadata->getAssociationTargetClass('translations');
    }

    // UPDATE
    public function persistTranslations(Form $form, $locales, $defaultLocale)
    {
        $entity = $form->getParent()->getData();
        $fieldName = $form->getName();
        $submittedValues = $form->getData();
        foreach ($locales as $locale) {
            if (array_key_exists($locale, $submittedValues)) {
                $value = $submittedValues[$locale];
                // personal
                if (\method_exists($entity, self::GEDMO_PERSONAL_TRANSLATIONS_SET) && \is_callable(
                        [$entity, self::GEDMO_PERSONAL_TRANSLATIONS_SET]
                    )) {
                    $translationClassName = $this->getPersonalTranslationClassName($entity);
                    $needAddTranslation = true;
                    foreach ($entity->getTranslations() as $translation) {
                        if ($translation->getLocale() == $locale && $translation->getField() == $fieldName) {
                            $translation->setContent($value);
                            $needAddTranslation = false;
                        }
                    }
                    if ($needAddTranslation && $value !== null) {
                        $entity->addTranslation(new $translationClassName($locale, $fieldName, $value));
                    }
                } else {
                    // 'ext_translations'
                    $this->getTranslationRepository($entity)->translate($entity, $fieldName, $locale, $value);
                }
            }
        }
    }
}
