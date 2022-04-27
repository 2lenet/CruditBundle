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
The role is generated from this pattern : `ROLE_<CRUDNAME>_WF_<transition>`

Example : ROLE_USER_WF_TO_BANNED
> Hyphens `-` are replaced by underscores `_`

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
