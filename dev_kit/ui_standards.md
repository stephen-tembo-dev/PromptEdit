# UI standards

How screens in PixelForge are built. The app has one design system, and every page is assembled from it. If a page needs something the system doesn't have, extend the system rather than styling a one-off.

## The look

PixelForge is a calm, precise workspace. Scanning and consistency come before decoration.

| Aspect | Rule |
| --- | --- |
| Colour | Neutrals plus one accent: emerald (`primary`). Emerald is for primary actions, the current location and "done". |
| Status colours | Amber means pending or waiting, sky means information or in progress, red means failed or destructive. They're never decoration. |
| Type | Figtree only. 14px body text, 12px uppercase labels for table headers. Nothing smaller than 12px. |
| Shapes | 8px radius for buttons, inputs, tables and alerts; 15px for cards. Fully rounded only for avatars, pills and switches. |
| Borders | 1px hairlines: `border-black/10` in light mode, `border-white/10` in dark. |
| Depth | Flat. Cards, tables and sections get a hairline, not a shadow. Only floating things (menus, modals, tooltips, toasts) get a shadow. |
| Spacing | Tailwind's 4px scale, mostly 4, 8, 16, 24 and 32px. Generous space between groups, tight space inside them. |
| Themes | Light and dark are equal. Every screen must work in both. |

Never:

- Add a second accent colour, gradients, gradient text or glows.
- Hard-code hex values or arbitrary colours in a component.
- Use bold body text or a second font.
- Put shadows on in-page surfaces.

## Build with the ui kit

All components come from `@/components/ui` (`resources/js/components/ui`). Read a component's source to see its props.

```vue
import { Button, Card, Field, Label, Input, FieldError, PageHeader } from '@/components/ui';
```

- **Kit components only:** don't write raw `<button>`, `<input>`, `<select>`, dialogs or dropdown menus. Use `Button`, `Input`, `Select`, `Combobox`, `Checkbox`, `Modal`, `Confirm`, `Dropdown` and the rest.
- **Recolouring:** use a component's `color` and `variant` props, for example `<Button variant="outline" color="neutral">`. Don't override colours with classes.
- **Icons:** `<Icon name="folder-open" />` with [Phosphor](https://phosphoricons.com) names, or Heroicons as `hi:x-mark`.
- **Numeric table columns:** put `align="end"` on both `TableHeader` and `TableCell`, plus `class="tabular-nums"` on the cell. A `text-end` class on the header doesn't work.

## Page layout

- **Layouts:** every page uses one. `AppLayout` for signed-in pages, `AuthLayout` for login and sign-up screens, `GuestLayout` for public pages.
- **PageHeader:** signed-in pages start with `<PageHeader title="..." description="...">`. Actions go in its `#actions` slot, with the most important one on the right.
- **Sidebar links** live in `resources/js/layouts/AppSidebarNav.vue`. Show a link only when the user has the permission for it, for example `v-if="page.props.auth.can.viewHistory"`.
- **Responsive:** layouts collapse to one column on small screens. Use patterns like `grid gap-6 lg:grid-cols-3`.

## Forms

Use `useForm` from Inertia and the `Field` structure. Controls read Laravel's validation errors from their `name`, so you don't wire errors up by hand.

```vue
<form @submit.prevent="form.post('/things')">
    <Field required>
        <Label for="name">Name</Label>
        <Input id="name" v-model="form.name" name="name" />
        <FieldError name="name" />
    </Field>

    <Button type="submit" :loading="form.processing">Save</Button>
</form>
```

## Feedback and states

- **Toasts:** from a controller, `->with('notify', ['type' => 'success', 'content' => '...'])` or `Inertia::flash('notify', [...])`. In Vue, `useToast()`.
- **Destructive actions** go behind `<Confirm tone="danger">`.
- **Status badges** carry an icon and a word, so the meaning never depends on colour alone. Reuse `GenerationStatusBadge` for image statuses.
- **Every list needs an empty state** (`Empty`, `EmptyContents`), and every async action a loading state.
- **Pages with unfinished images** use `useLiveGenerations()` so they update live. Don't poll from the browser.

## Copy

- **Write what the product actually does.** Never invent claims, numbers or testimonials.
- **Buttons name their action:** "Create user", not "Submit".
- **Errors** say what went wrong and how to fix it.
- **Money comes formatted from the backend** (for example `$0.0961`). Never calculate or round money in Vue.

## File layout

```
resources/js/
├── components/ui/         The ui kit
├── components/<group>/    Feature components (billing, generations, users, welcome)
├── composables/           Shared composables; feature ones in composables/<group>/
├── layouts/               AppLayout, AuthLayout, GuestLayout, AppSidebarNav
├── lib/                   Plain TypeScript helpers
├── pages/<group>/         Inertia pages, e.g. pages/billing/Credits.vue
└── types/<group>.ts       Types for what each resource sends
```

## Changing the kit

- **New components** go under `resources/js/components/ui/<family>/` and are exported from `index.ts`.
- **Consistent props:** reuse the prop names sibling components already use (`size`, `variant`, `color`, `icon`, `iconAfter`, `invalid`, `name`).
- **Visual changes** to the system (a new token, a component's look, a new pattern) need agreement first, because every screen inherits them.

## Before you push UI changes

```bash
npm run types    # must pass
npm run build    # must pass
php artisan test # if you touched routes, controllers or page props
```

Check the screen in light and dark mode and at phone width.

A known `vue-tsc` quirk: a literal `//` inside a component attribute (such as `prefix="https://"`) breaks type checking for the rest of that template. Bind it from a variable instead: `:prefix="urlScheme"`.
