Crudit supports Symfony's Workflow component.

### Usage

To enable workflows for a field, you need to set the type :

```php
$status = Field::new("status", WorkflowField::class);
```

And it's done !  
Note that "workflow" also works.

### Roles

The workflows transitions have roles on them.
The role is generated from this pattern : `ROLE_<WORKFLOWNAME>_WF_<TRANSITION>`

Example : ROLE_COMMANDE_CLIENT_WF_TO_EXPEDIEE for the transition `to_expediee` of the workflow `commande_client`
> Hyphens `-` are replaced by underscores `_`

The role is checked first, before the resource is loaded.
The transition must belong to the workflow, and it is applied only on this workflow.

### Several workflows on the same entity

By default, the field uses the first workflow of the entity.
When the entity has several workflows, set the workflow name on **each** WorkflowField :

```php
$statut = Field::new("statut", WorkflowField::class)->setOptions(["name" => "commande_client"]);
$statutRelance = Field::new("statutRelance", WorkflowField::class)->setOptions(["name" => "commande_client_relance"]);
```

### Design

- crudit-wf-dropdown is added on the div that contains the status and the dropdown
- crudit-wf-badge is added on the status
- crudit-wf-state-\<status\> is added on the status
- crudit-wf-transition-\<transition\> is added on the transitions

### Filter

The WorkflowFilterType is available in Crudit.  
You need to pass the workflow of your choice to the filter, like this :

```php
public function __construct(WorkflowInterface $commandStateMachine)
{
    // you may also use the Registry instead of injecting a WorkflowInterface
}

public function getFilters(): array
{
    return [
        WorkflowFilterType::new("state", $this->commandStateMachine),
    ];
 }
```
