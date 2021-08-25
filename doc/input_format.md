# Input Format

Sometime we need to check the input text format.

Crudit provide some javascript handler to check this format.

## How to use it

just add a class to your input form.

``` 
        $builder->add('nom', null, ['attr' =>  [
            'class' => 'crudit-upper-format']
        ]);
```

## Available class :

- crudit-upper-format : Upper all string typed by the user
- crudit-lower-format : Lower all string typed by the user
- crudit-ip-format : Check an ip address format ( and help to input dot)
- crudit-email-format : Check an email address format

