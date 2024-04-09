<?php

namespace Lle\CruditBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query as Query;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\TranslatableListener as TranslatableListener;
use Symfony\Component\Form\Form as Form;
use Symfony\Component\PropertyAccess\PropertyAccess as PropertyAccess;

class GedmoTranslatableFieldManager
{
    public const GEDMO_TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';
    public const GEDMO_TRANSLATION_WALKER = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';
    public const GEDMO_PERSONAL_TRANSLATIONS_GET = 'getTranslations';
    public const GEDMO_PERSONAL_TRANSLATIONS_SET = 'addTranslation';
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getTranslationRepository(object $entity): EntityRepository
    {
        return $this->em->getRepository(self::GEDMO_TRANSLATION);
    }

    private function getTranslations(object $entity, string $fieldName): array
    {
        if (
            \method_exists($entity, self::GEDMO_PERSONAL_TRANSLATIONS_GET) && \is_callable(
                [$entity, self::GEDMO_PERSONAL_TRANSLATIONS_GET]
            )
        ) {
            $translations = [];
            foreach ($entity->getTranslations() as $translation) {
                if ($translation->getField() == $fieldName) {
                    $translations[$translation->getLocale()] = $translation->getContent();
                }
            }

            return $translations;
        } else {
            /** @var TranslationRepository $translationRepository */
            $translationRepository = $this->getTranslationRepository($entity);

            // 'basic' translations (ext_translations table)
            return \array_map(function ($element) {
                return \array_shift($element);
            }, $translationRepository->findTranslations($entity));
        }
    }

    private function getEntityInDefaultLocale(object $entity, string $defaultLocale): ?object
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
    public function getTranslatedFields(object $entity, string $fieldName, string $defaultLocale): array
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

    private function getPersonalTranslationClassName(object $entity): string
    {
        $metadata = $this->em->getClassMetadata(\get_class($entity));

        return $metadata->getAssociationTargetClass('translations');
    }

    // UPDATE
    public function persistTranslations(Form $form, array $locales, string $defaultLocale): void
    {
        /** @var object $entity */
        $entity = $form->getParent()?->getData();
        $fieldName = $form->getName();
        $submittedValues = $form->getData();
        foreach ($locales as $locale) {
            if (array_key_exists($locale, $submittedValues)) {
                $value = $submittedValues[$locale];
                // personal
                if (
                    \method_exists($entity, self::GEDMO_PERSONAL_TRANSLATIONS_GET) &&
                    \method_exists($entity, self::GEDMO_PERSONAL_TRANSLATIONS_SET) &&
                    \is_callable([$entity, self::GEDMO_PERSONAL_TRANSLATIONS_SET])
                ) {
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
                    /** @var TranslationRepository $translationRepository */
                    $translationRepository = $this->getTranslationRepository($entity);

                    // 'ext_translations'
                    $translationRepository->translate($entity, $fieldName, $locale, $value);
                }
            }
        }
    }
}
