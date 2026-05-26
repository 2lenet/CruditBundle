<?php

declare(strict_types=1);

namespace Lle\CruditBundle\DependencyInjection;

use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Contracts\DatasourceInterface;
use Lle\CruditBundle\Contracts\ExporterInterface;
use Lle\CruditBundle\Contracts\FieldInterface;
use Lle\CruditBundle\Contracts\FilterSetInterface;
use Lle\CruditBundle\Contracts\MenuProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class LleCruditExtension extends Extension implements ExtensionInterface
{
    private const DEFAULT_ICONS = [
        // Common UI
        'search' => 'fas fa-search',
        'plus' => 'fas fa-plus',
        'minus' => 'fas fa-minus',
        'check' => 'fas fa-check',
        'close' => 'fa fa-close',
        'window_close' => 'fa fa-window-close',
        'download' => 'fas fa-download',
        'info' => 'fas fa-info-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'kebab' => 'fas fa-ellipsis-v',
        'user' => 'fas fa-user',
        'bell' => 'fas fa-bell',
        'envelope' => 'fas fa-envelope',
        'external_link' => 'fas fa-external-link-alt',
        'bars' => 'fa fa-bars',
        'sign_out' => 'fas fa-sign-out-alt',
        'arrow_right' => 'fas fa-arrow-right',
        'clock' => 'far fa-clock',
        'exchange' => 'fa fa-exchange',
        'folder_plus' => 'fas fa-folder-plus',
        'file' => 'fas fa-file-alt',
        'donate' => 'fas fa-donate',
        'filter' => 'fa fa-filter',
        'angle_up' => 'fas fa-angle-up',
        'chevron_up' => 'fa fa-chevron-up',
        'chevron_down' => 'fa fa-chevron-down',
        // Pager
        'pager_first' => 'fa fa-angle-double-left',
        'pager_prev' => 'fa fa-angle-left',
        'pager_next' => 'fa fa-angle-right',
        'pager_last' => 'fa fa-angle-double-right',
        // Sort
        'sort' => 'fas fa-sort',
        'sort_up' => 'fas fa-sort-up',
        'sort_down' => 'fas fa-sort-down',
        // Filter operators
        'op_equal' => 'fas fa-equals',
        'op_not_equal' => 'fas fa-not-equal',
        'op_less_than' => 'fas fa-less-than',
        'op_less_than_equal' => 'fas fa-less-than-equal',
        'op_greater_than' => 'fas fa-greater-than',
        'op_greater_than_equal' => 'fas fa-greater-than-equal',
        'op_interval' => 'fas fa-arrows-alt-h',
        'op_is_null' => 'far fa-square',
        'op_is_not_null' => 'fas fa-square',
        'op_contains' => 'fa fa-text-width',
        'op_starts_with' => 'far fa-caret-square-right',
        'op_ends_with' => 'far fa-caret-square-left',
        'op_in' => 'fas fa-equals',
        'op_not_in' => 'fas fa-not-equal',
        'op_before' => 'fas fa-less-than',
        'op_after' => 'fas fa-greater-than',
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('listeners.yaml');
        $loader->load('bricks.yaml');
        $loader->load('fields.yaml');
        $loader->load('form.yaml');

        $configuration = new Configuration();
        $processedConfig = $this->processConfiguration($configuration, $configs);

        $container->registerForAutoconfiguration(MenuProviderInterface::class)->addTag('crudit.menu');
        $container->registerForAutoconfiguration(CrudConfigInterface::class)->addTag('crudit.config');
        $container->registerForAutoconfiguration(DatasourceInterface::class)->addTag('crudit.datasource');
        $container->registerForAutoconfiguration(BrickInterface::class)->addTag('crudit.brick');
        $container->registerForAutoconfiguration(FieldInterface::class)->addTag('crudit.field');
        $container->registerForAutoconfiguration(FilterSetInterface::class)->addTag('crudit.filterset');
        $container->registerForAutoconfiguration(ExporterInterface::class)->addTag("crudit.exporter");

        $container->setParameter('lle_crudit.default_currency_alignment', $processedConfig['default_currency_alignment']);
        $container->setParameter('lle_crudit.default_integer_alignment', $processedConfig['default_integer_alignment']);
        $container->setParameter('lle_crudit.default_number_alignment', $processedConfig['default_number_alignment']);
        $container->setParameter('lle_crudit.hide_if_disabled', $processedConfig['hide_if_disabled']);
        $container->setParameter('lle_crudit.delete_hide_if_disabled', $processedConfig['delete_hide_if_disabled']);
        $container->setParameter('lle_crudit.add_connect_profile_link', $processedConfig['add_connect_profile_link']);
        $container->setParameter('lle_crudit.add_exit_impersonation_button', $processedConfig['add_exit_impersonation_button']);
        $container->setParameter('lle_crudit.exit_impersonation_path', $processedConfig['exit_impersonation_path']);
        $container->setParameter('lle_crudit.generate_default_role', $processedConfig['generate_default_role']);
        $container->setParameter('lle_crudit.ignore_referer_routes', $processedConfig['ignore_referer_routes']);
        $container->setParameter('lle_crudit.css_class_columns_form', $processedConfig['css_class_columns_form']);
        $container->setParameter('lle_crudit.css_class_columns_show', $processedConfig['css_class_columns_show']);
        $container->setParameter('lle_crudit.css_class_columns_card', $processedConfig['css_class_columns_card']);
        $container->setParameter('lle_crudit.number_cards_per_row', $processedConfig['number_cards_per_row']);
        $container->setParameter(
            'lle_crudit.icons',
            array_merge(self::DEFAULT_ICONS, $processedConfig['icons'] ?? [])
        );

        // Load the templates for the Crudit form types
        if ($container->hasParameter('twig.form.resources')) {
            /** @var array $parameter */
            $parameter = $container->getParameter('twig.form.resources');

            $container->setParameter(
                'twig.form.resources',
                array_merge(
                    ['@LleCrudit/form/custom_types.html.twig'],
                    $parameter
                )
            );
        }
    }
}
