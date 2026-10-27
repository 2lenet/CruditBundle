<?php

declare(strict_types=1);

namespace App\Crudit\Config;

use App\Crudit\Datasource\ClientDatasource;
use App\Entity\Client;
use App\Enum\Priority;
use App\Form\ClientType;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Crud\AbstractCrudConfig;
use Lle\CruditBundle\Dto\Action\DropdownAction;
use Lle\CruditBundle\Dto\Action\ItemAction;
use Lle\CruditBundle\Dto\Action\ListAction;
use Lle\CruditBundle\Dto\Action\PrintAction;
use Lle\CruditBundle\Dto\Field\Field;
use Lle\CruditBundle\Dto\Icon;
use Lle\CruditBundle\Dto\Path;
use Lle\CruditBundle\Field\ChoiceField;
use Lle\CruditBundle\Field\ColorField;
use Lle\CruditBundle\Field\MarkdownField;
use Lle\CruditBundle\Field\TelephoneField;

/**
 * The clients. The actions show each kind of lot 3.1.a: primary, secondary, a DropdownAction, a print, the menu
 * "More" with a group, a modal without fields (About) and with fields (Send an e-mail), a confirmation, an action
 * refused by a right (shown, disabled: hide_if_disabled is false here), rules of the business (canDelete(),
 * setDisabledReason()), an action of the lines shown beside the edition (setDropdown(false)).
 */
class ClientCrudConfig extends AbstractCrudConfig
{
    public function __construct(ClientDatasource $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * @return array<Field|Field[]>
     */
    public function getFields(string $key): array
    {
        $name = Field::new('name');
        $email = Field::new('email');
        $active = Field::new('active');
        $createdAt = Field::new('createdAt');
        $amount = Field::new('amount');
        $progress = Field::new('progress', 'progress');
        $budgetUsed = Field::new('budgetUsed', 'progress', ['overMax' => 'danger']);
        $status = Field::new('status', ChoiceField::class, [
            'choices' => ['client.status.prospect' => 'prospect', 'client.status.customer' => 'customer', 'client.status.lost' => 'lost'],
            'tones' => ['customer' => 'success', 'lost' => 'danger'],
        ]);
        $notes = Field::new('notes', MarkdownField::class);
        $phone = Field::new('phone', TelephoneField::class);
        $website = Field::new('website', 'url');
        $color = Field::new('color', ColorField::class);
        $blocked = Field::new('blocked', 'bool', ['reverseColors' => true]);
        $tags = Field::new('tags', 'json');
        $referrer = Field::new('referrer');
        // A backed enum, and several values
        $priority = Field::new('priority', ChoiceField::class, [
            'choices' => ['client.priority.low' => Priority::Low, 'client.priority.high' => Priority::High],
            'tones' => ['high' => 'danger'],
        ]);
        $channels = Field::new('channels', ChoiceField::class, [
            'choices' => ['client.channel.email' => 'email', 'client.channel.phone' => 'phone'],
            'tones' => ['phone' => 'info'],
        ]);

        return match ($key) {
            CrudConfigInterface::INDEX => [
                $name, $email, $active, $createdAt, $amount, $progress, $status, $notes, $website, $tags,
            ],
            CrudConfigInterface::SHOW => [
                $name, $email, $active, $createdAt, $amount, $progress, $budgetUsed, $status, $notes, $phone, $website,
                $color, $blocked, $tags, $referrer, $priority, $channels,
            ],
            default => [],
        };
    }

    public function getListActions(): array
    {
        $actions = parent::getListActions();
        $actions['reports'] = DropdownAction::new('action.reports', [
            'group.sales' => [ListAction::new('action.report_month', $this->action('report-month'))],
            [ListAction::new('action.report_custom', $this->action('report-custom'))],
        ], Icon::new('file'));
        $actions['print'] = PrintAction::new();
        $actions['about'] = ListAction::new('action.about', $this->action('about'), Icon::new('info'))
            ->setModal('client/_about_modal.html.twig')
            ->setDropdown(true);
        $actions['archive'] = ListAction::new('action.archive_inactive', $this->action('archive'), Icon::new('delete'))
            ->setDropdown(true)
            ->setGroup('group.maintenance')
            ->setVariant('danger')
            ->setConfirmModal(true);
        $actions['purge'] = ListAction::new('action.purge', $this->action('purge'))
            ->setDropdown(true)
            ->setGroup('group.maintenance')
            ->setRole('ROLE_NOBODY');

        return $actions;
    }

    public function getShowActions(): array
    {
        $actions = parent::getShowActions();
        $actions['email'] = ItemAction::new('action.send_email', $this->action('email'), Icon::new('email'))
            ->setModal('client/_email_modal.html.twig');
        $actions['deactivate'] = ItemAction::new('action.deactivate', $this->action('deactivate'))
            ->setDisabledReason(static fn (?object $client): ?string => $client instanceof Client && !$client->isActive() ? 'client.reason.inactive' : null);

        return $actions;
    }

    public function getItemActions(): array
    {
        $actions = parent::getItemActions();
        // A modal: the same action serves every row, each dialog must show its own client
        $actions['note'] = ItemAction::new('action.note', $this->getPath(CrudConfigInterface::SHOW))->setModal('modal/client_note.html.twig');
        // Shown in the line, beside the edition (a button at its label: no icon)
        $actions['deactivate'] = ItemAction::new('action.deactivate', $this->action('deactivate'))
            ->setDropdown(false)
            ->setDisabledReason(static fn (?object $client): ?string => $client instanceof Client && !$client->isActive() ? 'client.reason.inactive' : null);

        return $actions;
    }

    private function action(string $name): Path
    {
        return Path::new('app_client_action', ['action' => $name]);
    }

    protected function getFormType(string $pageKey): ?string
    {
        return ClientType::class;
    }

    public function getRootRoute(): string
    {
        return 'app_client';
    }
}
