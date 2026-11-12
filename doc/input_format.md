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
- crudit-ip-format : Check an ip address format (the keyboard of a text, which has the point). While typing at the
  end, a point is added once a byte is 26 or more or has 3 digits; a point typed just after it is ignored
- crudit-email-format : Check an email address format

A wrong value gets `aria-invalid` once the field is left (controller `input-format`), removed once it is right. An error
of the server on the field (a value already used) keeps its `aria-invalid` until the form is sent again.

