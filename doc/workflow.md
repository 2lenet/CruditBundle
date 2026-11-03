Crudit supports Symfony's Workflow component.

### Usage

To show the state of an element, give its field the type `WorkflowField` (`"workflow"` also works):

```php
$status = Field::new('status', WorkflowField::class);
```

Options:

- `name`: the workflow, when the element has several (by default, the first one of the element);
- `backgroundColor`: deprecated, no effect: declare a tone per place (see the colours below).

The state is a `Status` of CruditUiBundle: the label `wf.state.<place>` (domain `messages`), always written, and the
colour of its place. If the user may apply transitions, the status is the button of a menu, named
"To plan, change the status of INT-0412", which lists the states the transitions lead to.

An element without state (a property of the marking at `null`) shows a dash, read as "Not set", and offers no
transition: its marking is read from the marking store, so displaying it neither puts it in the initial place nor
dispatches the events `entered`. Outside a CRUD (a field without `CrudConfig`), the status has no menu.

### Colours

Each place declares its tone in the metadata of the workflow:

```yaml
framework:
    workflows:
        intervention:
            type: state_machine
            marking_store: {type: method, property: status}
            supports: [App\Entity\Intervention]
            places:
                to_plan: {metadata: {tone: warning}}
                planned: {metadata: {tone: info}}
                in_progress: {metadata: {tone: teal}}
                done: {metadata: {tone: success}}
                cancelled: {metadata: {tone: gray}}
            transitions:
                cancel:
                    from: [to_plan, planned]
                    to: cancelled
                    metadata: {danger: true}
```

The tone is one of CruditUiBundle (`neutral`, `info`, `primary`, `success`, `warning`, `danger`), a hue of its
extended palette, meant for the workflows with many states (`blue`, `teal`, `green`, `lime`, `yellow`, `orange`,
`red`, `pink`, `purple`, `indigo`, `gray`), or a colour of the project: its two tokens, in light and dark (a contrast
of 4.5:1 at your charge), and one rule.

```css
:root {
    --c-coral-bg: #ffe4dc;
    --c-coral-text: #8a2a0b;
}

.crudit-ui-tone--coral {
    --crudit-ui-tone-bg: var(--c-coral-bg);
    --crudit-ui-tone-text: var(--c-coral-text);
}
```

A place without a tone is neutral. The option `backgroundColor` of 1.x (a Bootstrap class, `bg-danger`) has no
effect any more: declare `tone: danger` on the places instead.

### Transitions and roles

A transition is offered if the workflow allows it (its guards) and if the user has its role, generated from this
pattern: `ROLE_<WORKFLOW>_WF_<TRANSITION>`, in upper case, the name of the workflow (not of the CRUD).

Example: `ROLE_INTERVENTION_WF_CANCEL`
> Hyphens `-` are replaced by underscores `_`

The role is asked with the element as subject (`isGranted('ROLE_INTERVENTION_WF_CANCEL', $intervention)`): a voter
of your project may decide element by element, and so limit the elements a user may change (company, own
elements…). The `RoleVoter` of Symfony ignores the subject, so the roles alone work
as before.

The roles of a screen (`Field::setRole()`, `ROLE_<CRUD>_SHOW`…) only decide what is displayed there (list, record,
sublist): they do not forbid the transitions. A status displayed elsewhere (a sublist, a widget, a template of yours)
offers them. To forbid a transition, or to limit the elements it applies to, take its role
`ROLE_<WORKFLOW>_WF_<TRANSITION>` away or refuse it in a voter (it receives the element).

The others are hidden. The choice of a state opens the confirmation of the layout (`crudit-confirm`), which names the
element ("INT-0412: move to “Planned”?"); a transition with the metadata `danger: true` has its button in danger.

The transition is sent in POST, with a CSRF token, to the route `<root route>_workflowtransition` (`/workflow/{id}`),
by the Stimulus controller `workflow` of the bundle (enable it in `assets/controllers.json`): the status changes
without a reload, a toast says it, the focus goes back to the status. The server checks the token, the role
of the transition and the guards again (not the roles of a screen); an error is said in a toast and the status stays as it was. So does a transition refused by a listener
of the workflow (an exception of the component Workflow, `LogicException`) or applied in the meantime by another user:
the answer is a 409, not an error page. The new status is `field/workflow.html.twig` rendered for the element, from
wherever the status was changed (list, record, sublist, a template of yours that includes it): only the status
(`.crudit-workflow`) is replaced, the markup of your template around it stays. A template given to the field
(`setTemplate()`) is not used by the answer. The CSRF token is `crudit_workflow_<workflow>_<id>`: a template of yours
that sends the transition itself gives it with the field `_token`, and the fields `name` (the workflow) and
`transition`.

The CSRF protection of Symfony must be enabled (`framework.csrf_protection`, on by default with
`symfony/security-csrf`): without it, `csrf_token()` does not exist in Twig and the field fails as soon as it is
displayed.

### Counters

The number of elements in each state, in the order of the places, for the `Counters` component of CruditUiBundle:

```twig
<twig:LleCruditUi:Counters label="{{ 'Interventions by state'|trans }}">
    {% for item in crudit_workflow_counters('App\\Entity\\Intervention', 'intervention', crud, 'status') %}
        <twig:LleCruditUi:Counter :value="item.count" tone="{{ item.state.tone }}" :href="item.url">
            {{- ('wf.state.' ~ item.state.place)|trans -}}
        </twig:LleCruditUi:Counter>
    {% endfor %}
</twig:LleCruditUi:Counters>
```

`crudit_workflow_counters(class, name, crud, filter)`: the entity, the workflow (else the first one of the entity),
the `CrudConfig` whose list a counter opens (here `crud`, given by the controller: `$this->config`) and the id of the
`WorkflowFilterType` of its filterset that the link fills; without them, or without a filterset, no link.

What is counted: with a `CrudConfig` whose datasource is Doctrine, the elements of its datasource
(`buildQueryBuilder()`, where your project restricts its data: company, rights), without the filters of the user.
**Without a `CrudConfig`, the whole table is counted**, the data of every company included. In PHP,
`Lle\CruditBundle\Workflow\WorkflowCounter::count()` also takes a `QueryBuilder` to count part of the elements
(`COUNT(DISTINCT …)`: a join does not count an element twice). One query (`GROUP BY` on the property of the
marking): the workflow must be a `state_machine`, whose marking is one place in a column.

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
