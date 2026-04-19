# Smoke Checklist

Use this checklist before tagging an alpha or merging larger Admin Config changes.

## Options page

Example: `examples/schema-demo-plugin`

- Open the demo options page.
- Switch between tabs and subsection groups.
- Save a normal field change and confirm the success notice and status pill update.
- Enter an invalid `release_slug` value such as `a` and save.
- Confirm the save is blocked, the field row is highlighted, and the inline error renders.
- Enter an invalid nested value inside `typography`, `accordion`, `tabbed`, or `group`, and confirm the exact nested control is highlighted while its containing panel/group opens automatically.
- Fix the slug and save again.
- Use reset for the current page and for all tabs.
- Export a snapshot, then import it back.

## Comment container

- Open a comment edit screen with the demo plugin active.
- Change one of the comment meta fields and save.
- Re-open the comment and confirm the value persisted.
- Enter an invalid comment field value and confirm the redirect comes back with an inline notice and highlighted field rows.

## Profile container

- Open a user profile screen.
- Change the demo profile fields and save.
- Re-open the profile and confirm the value persisted.
- Enter an invalid `profile_slug` and verify the save does not overwrite the previous stored value.
- Confirm the profile screen shows an inline notice and preserves the submitted value for the invalid field.

## Taxonomy container

- Open category create and edit screens.
- Save the demo taxonomy fields on an existing category.
- Re-open the category and confirm the term meta persisted.
- Enter an invalid `category_slug` and verify the save does not overwrite the previous stored value.
- Confirm both add/edit term forms replay submitted values and show an inline notice after validation failure.

## Network options page

- In multisite, open the network demo settings page.
- Save a normal field change and confirm the value persists network-wide.

## Embedded mode

Example: `examples/embedded-theme-demo`

- Open the embedded theme options page.
- Save advanced fields such as `typography`, `icon`, `accordion`, and `tabbed`.
- Open the demo metabox and confirm post meta persists.
- Enter an invalid metabox value and confirm the post edit screen re-renders the metabox with a validation notice.

## Regression notes

- Validation errors should block persistence for the affected save request.
- AJAX saves should return field-level errors without reloading the page.
- Non-JS options-page saves should show a flash notice and preserve submitted values for the active tab.
- Full-screen native containers should validate the whole submitted schema before any meta write occurs.
