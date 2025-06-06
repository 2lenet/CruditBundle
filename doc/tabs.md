# Tabs

You can add tabs in the show screen.

In your CrudConfig:

```php
public function getTabs(): array
{
    return [
        "tab.name" => [Brick::new()]
    ];
}
```

You may use any brick. Below are common use cases.

## History

When using Gedmo\Loggable, you can use HistoryConfig in tabs. It provides a view of your entity's modifications.

If using the Loggable interface, this tab is automatically added.

```php
use Gedmo\Loggable\Loggable;

public class User implements Loggable
```

Note : if Gedmo ends up using PHP annotations, we may change the HistoryConfig to remove this requirement.

Otherwise, you have to add them like this:

```php
use Lle\CruditBundle\Brick\HistoryBrick\HistoryConfig;

// ...

public function getTabs(): array
{
    return [
        "tab.history" => [
            HistoryConfig::new()
        ]
    ];
}
```

If you need to add custom history (such as additions/deletions in collections), you have to log it yourself by
creating `LogEntry` entities from Gedmo, for example by implementing your own listener.

If you have some 1-1 relation for an entity you can add some entities to the History tabs

Just add a array with the datasource and the getter method to retrieve the entity from the main one.

exemple

```php
            "tab.history" => [
                HistoryConfig::new(["otherEntities"=>[
                    ["datasource"=>$this->depistageL1Datasource, "method"=>"getDepistageL1"],
                    ["datasource"=>$this->depistageL2Datasource, "method"=>"getDepistageL2"],
                    ["datasource"=>$this->bdiDatasource, "method"=>"getBdi"],
                    ["datasource"=>$this->l2IADatasource, "method"=>"getL2IA"]
                ]]),
```

## Roles

If you want to limit your tab to a role you can pass a third parameter to the addTabs function like this

```php 
$tabConf = TabConfig::new();
$tabConf->adds('tab.commandes', [
SublistConfig::new('client', $this->commandeCrudConfig)
->setFields($this->commandeCrudConfig->getFields(self::INDEX))], "ROLE_TEST")
;
```

## Sublists

See [Sublists](sublist.md)

## Tags

See [Tags](brick/tags.md)
