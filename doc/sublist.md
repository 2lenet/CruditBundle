# How to add sub-lists in your show page

In your src/Crudit/Config/EntityCrudConfig.php, add the getTabs() method allowing you to easily add as many 
tabs as you want.
```php
    public function getTabs(): array
    {
        return [
            "tab.contacts" => SublistConfig::new('societe', $this->contactCrudConfig)
                ->setFields($this->getContactsFields()),
            "tab.societesLinked" => SublistConfig::new('parent', $this)
                ->setFields($this->getParentFields()),
            "tab.produits" => SublistConfig::new('societe', $this->objetCrudConfig)
                ->setFields($this->getObjetFields())
        ];
    }
```

The name you specify in the SublistConfig::new() is the mappedBy annotation of your relation.
_Example :_ here, we add all the contacts linked to a societe. In our entity Societe, we have an attribute $contacts with 
the annotation: @ORM\OneToMany(targetEntity=Contact::class, **mappedBy="societe"**). Hence the 
**SublistConfig::new('societe', ...)**

> :warning: **Don't forget to declare your CrudConfigs in the construct**

Then, for each tab, declare a getEntityFields() method allowing you to list all the fields you want to see in the sub-list.
```php
    public function getContactsFields(): array
    {
        $fonction = Field::new('fonction');
        $nom = Field::new('nom');
        $prenom = Field::new('prenom');
        $telephone = Field::new('telephone');
        $telephoneMobile = Field::new('telephoneMobile');
        $email = Field::new('email');
        return [
            $fonction,
            $nom,
            $prenom,
            $telephone,
            $telephoneMobile,
            $email,
        ];
    }
```

That's it!

## Sub-lists forms
You can add a form to your sub-list to allow the user to expand it.
Simply add a FormConfig to your tab (tabs allow multiple elements, by passing an array) and configure it.

```php
"tab.champs" => [
    SublistConfig::new("categorie", $this->champCategorieCrudConfig)
        ->setActions($this->champCategorieCrudConfig->getItemActions())
        ->setFields($this->getChampFields()),
    FormConfig::new()
        ->setForm(ChampCategorieType::class)
        ->setDataSource($this->champCategorieCrudConfig->getDatasource())
        ->setSublist("categorie")
        ->setSuccessRedirectPath($this->getPath(CrudConfigInterface::SHOW))
]
```
You have to :
* set the form type with setForm()
* set the datasource that will create the new resource with setDataSource()
* use setSublist()

In setSublist, you can pass the ManyToOne property name that represents the main entity.
(e.g. for a sublists of comments, you could do `setSublist("post")`)

If you use `setSuccessRedirectPath($this->getPath(CrudConfigInterface::SHOW))`, it will redirect to the main entity.
To redirect to your new entity, simply use `$yourNewEntityCrudConfig->getPath(CrudConfigInterface::SHOW)`.

## Paginator

if you want to change numbers of items per pages you have to go  src/Crud/AbstractCrudConfig.php 
 
 public function getChoicesNbItems(): array
    {
        return [10, 30, 50, 100, 200, 500];
    }
   
