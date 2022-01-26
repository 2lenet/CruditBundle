## Custom CrudIT form types

### Autocomplete

The Lle\CruditBundle\Form\Type\AutocompleteType
allows you to create a select for entities that is
fed by an AJAX autocomplete.
It is equivalent to Doctrine's EntityType.  

Options :

| Name      | Default    | Description |
| --------- |------------| ------------|
| class     | required   | a FQCN of your entity (e.g. App\Entity\Dummy) |
| multiple  | false      | set this to true if the field is a collection |
| route     | null       | if you want to customize the autocomplete route |
| url       | null       | if you want to customize the autocomplete url |

### Markdown
The [Lle\CruditBundle\Form\Type\MarkdownType](markdown.md#markdowntype) allows you to create a Markdown editor textarea.