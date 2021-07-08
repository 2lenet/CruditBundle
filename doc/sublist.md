# How to add sub-lists in your show page

In your src/Crudit/Config/EntityCrudConfig.php, add the getTabs() method allowing you to easily add as many 
tabs as you want.
    
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

The name you specify in the SublistConfig::new() is the mappedBy annotation of your relation.
_Example :_ here, we add all the contacts linked to a societe. In our entity Societe, we have an attribute $contacts with 
the annotation: @ORM\OneToMany(targetEntity=Contact::class, **mappedBy="societe"**). Hence the 
**SublistConfig::new('societe', ...)**

> :warning: **Don't forget to declare your CrudConfigs in the construct**

Then, for each tab, declare a getEntityFields() method allowing you to list all the fields you want to see in the sub-list.

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

That's it!

