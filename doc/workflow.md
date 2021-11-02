Crudit supports Symfony's Workflow component.

### Usage

To enable workflows for a resource, you need to add this in the datasource :
```php
/**
 * @required
 */
public function setWfRegistry(Registry $wfRegistry)
{
    $this->wfRegistry = $wfRegistry;
}
```

> :warning: **The PHPDoc `@required` is mandatory, otherwise the method will be ignored.**

And it's done !  
Note that the maker can do it for you.

### Disable the workflow

If you want to disable the workflow for a specific field (e.g. you don't want it in the list but still in the show),
you can change the type of the field, like this :
```php
$status = Field::new("status")->setType("string")
```

### Roles

The workflows transitions have roles on them.
The role is generated from this pattern : `ROLE_<CRUDNAME>_WF_<transition>`

Example : ROLE_USER_WF_TO_BANNED

### Design

- crudit-wf-dropdown is added on the div that contains the status and the dropdown
- crudit-wf-badge is added on the status
- crudit-wf-state-\<status\> is added on the status
- crudit-wf-transition-\<transition\> is added on the transitions
