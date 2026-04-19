# Smoke Checklist

Use this checklist before tagging an alpha or merging larger Admin Config changes.

## Options page

Example: `examples/schema-demo-plugin`

- Open the demo options page.
- Switch between tabs and subsection groups.
- Save a normal field change and confirm the success notice and status pill update.
- Enter an invalid `release_slug` value such as `a` and save.
- Confirm the save is blocked, the field row is highlighted, and the inline error renders.
- Enter an invalid nested value inside `typography`, `accordion`, or `tabbed`, and confirm the exact nested control is highlighted while its containing panel opens automatically.
- Fix the slug and save again.
- Use reset for the current page and for all tabs.
- Export a snapshot, then import it back.

## Post metabox container

- Open a post or page edit screen with the demo plugin active.
- Change the demo metabox fields and save the entry.
- Re-open the entry and confirm the post meta persisted.
- Enter an invalid nested `entry_badge.slug` value such as `a` and confirm the edit screen comes back with an inline metabox notice and the nested control highlighted.

## Comment container

- Open a comment edit screen with the demo plugin active.
- Change one of the comment meta fields and save.
- Re-open the comment and confirm the value persisted.
- Enter an invalid nested `review_badge.slug` value and confirm the redirect comes back with an inline notice and the nested control highlighted.

## Profile container

- Open a user profile screen.
- Change the demo profile fields and save.
- Re-open the profile and confirm the value persisted.
- Enter an invalid nested `profile_badge.slug` value and verify the save does not overwrite the previous stored value.
- Confirm the profile screen shows an inline notice, preserves the submitted value, and highlights the exact nested control.

## Taxonomy container

- Open category create and edit screens.
- Save the demo taxonomy fields on an existing category.
- Re-open the category and confirm the term meta persisted.
- Enter an invalid nested `category_badge.slug` value and verify the save does not overwrite the previous stored value.
- Confirm both add/edit term forms replay submitted values and show an inline notice on the exact nested control after validation failure.

## Network options page

- In multisite, open the network demo settings page.
- Save a normal field change and confirm the value persists network-wide.
- Enter an invalid nested `shared_library.feed_slug` value and confirm the network save is blocked with inline validation feedback.

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
