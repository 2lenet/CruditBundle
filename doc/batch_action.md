# How to add batch actions in your list

The batch actions allow to perform a given action on the pre-selected items of the list.

In your src/Crudit/Config/EntityCrudConfig.php, you have to overload the getListActions() method of the
AbstractCrudConfig. Inside your getListActions() method, you can add a new action and add ```->setIsBatch()``` to it:

```php
    public function getListActions(): array
    {
        $actions =  parent::getListActions();
        
        $actions[] = ListAction::new(
                'action.batch.exporter',
                Path::new('exporter_intervention'),
                Icon::new('file-export')
            )->setIsBatch();
            
        return $actions;           
    }
```

Then, in your src/Controller/Crudit/EntityContoller.php you can write the method that the batch action is going to
execute:

```php
    /**
     * @Route("/exporter_intervention", name="exporter_intervention")
     */
    public function exporterInterventions(Request $request, ResourceResolver $resolver, Exporter $exporter)
    {
        $ids = explode(",", $request->get("ids"));
        
        ...
    }
```

This will result in:

![](img/batch.png)

That's it!

# How to add a form to the batch action

After ```->setIsBatch()``` you need to add ```->setForm()``` and pass as parameter your form like this :

```php
    public function getListActions(): array
    {
        $actions =  parent::getListActions();
        
        $actions[] = ListAction::new(
                'action.batch.exporter',
                Path::new('exporter_intervention'),
                Icon::new('file-export')
            )->setIsBatch()->setForm(YourFormType::class);
            
        return $actions;           
    }
``` 

In your form you can add all the fields you need, but just 1 is required :

```php
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // All the fields you need
        $builder->add('intervention');

        // The required field
        $builder->add('ids', HiddenType::class, [
            'label' => false
        ]);
    }
```

Now in your controller you can find all the selected ids and the different values of your form like this :

```php
    /**
     * @Route("/exporter_intervention", name="exporter_intervention")
     */
    public function exporterInterventions(Request $request, ResourceResolver $resolver, Exporter $exporter)
    {
        /** @var array $params */
        $params = $request->request->get('exporter_intervention');

        $intervention = $params['intervention'];
        $ids = explode(',', $params['ids']);
        
        ...
    }
```
