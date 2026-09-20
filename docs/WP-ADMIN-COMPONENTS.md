# WordPress Admin Component Inventory

A practical guide to building plugin admin surfaces using WordPress core components. This document describes what WordPress 7.1.1 ships and the gaps we must fill with custom CSS.

**Source:** WordPress 7.1.1 installed at `/Users/edowns/Local Sites/benson-theater/app/public/`. When this document and WordPress documentation conflict, the installed source wins.

---

## 1. Page Chrome & Wrapper Structure

### The `.wrap` Container

Every plugin admin page is wrapped in a single container with the class `wrap`. This is the required parent for all page content and defines the page layout, typography, and padding.

```html
<div class="wrap">
  <h1>Page Title</h1>
  <hr class="wp-header-end">
  <!-- page content goes here -->
</div>
```

**Key classes & rules:**
- `wrap`: Required parent. Applies padding (`20px`), sets up the two-column layout when `.has-right-sidebar` is used.
- `nosubsub`: Add to `.wrap` when there is no second-tier navigation (e.g., "All Posts", "Published", "Drafts").
- `h1`: Page title. Must be immediate child of `.wrap`. Renders at `23px`, `font-weight: 400`, `line-height: 1.3`. Do not resize or restyle it.
- `wp-heading-inline`: Add to `h1` if it sits inline with other elements (e.g., a button to the right). Makes it `display: inline-block` with a right margin.
- `hr.wp-header-end`: A visual separator between page title/controls and content. Always include it. It is not a semantic element; it is for sighted users to see where the "chrome" ends. Screen readers skip it via `role="presentation"` implicitly.

### Subtitle / Breadcrumb

When showing filtered results or a search query, add a subtitle:

```html
<h1 class="wp-heading-inline">All Lessons</h1>
<span class="subtitle">Search results for: "video production"</span>
<hr class="wp-header-end">
```

The `.subtitle` class is plain `<span>` text; it is not bold or specially sized.

### Page Status Messages & Notices

Notices render inside `.wrap` but **after the `hr.wp-header-end`** and before content. Do not place them above the header.

---

## 2. Component Catalogue

### 2.1 Postbox / Metabox Panels

Postboxes are collapsible panels that hold grouped content. They are used in the dashboard and throughout plugin UIs. They are draggable and can be toggled closed.

```html
<div class="postbox">
  <div class="postbox-header">
    <h2 class="hndle"><span>Panel Title</span></h2>
    <div class="handle-actions">
      <!-- toggle button, order buttons render here -->
    </div>
  </div>
  <div class="inside">
    <!-- panel content -->
  </div>
</div>
```

**Classes & rules:**
- `postbox`: Required parent. Has `margin-bottom: 20px`, `padding: 0`, `line-height: 1`.
- `postbox-header`: Flex container for title and action buttons. Has a bottom border (`1px solid #c3c4c7`). Child of the postbox.
- `.hndle`: The title / handle. Inside `.postbox-header`. Flexes to `flex-grow: 1`. Can contain icon + text or just text.
- `handle-actions`: Flex container for the toggle/order buttons. Child of `.postbox-header`. Sits to the right of `.hndle`.
- `inside`: The collapsible content area. Has `padding: 0 12px 12px`, `margin: 11px 0`, `line-height: 1.4`, `font-size: 13px`. When postbox is `.closed`, content is hidden via JavaScript.

**PostBox State:**
- Closed state: Add `.closed` to the `<div class="postbox">`. The `.postbox-header` loses its bottom border when closed.
- The toggle button is a `<button class="handlediv" type="button">` with `aria-expanded="true|false"`. WordPress core JS handles the toggle.

**Notes:**
- Postboxes are **not** cards. They are panels designed for settingsand metadata on a single-post edit page, or groupings on a dashboard. Do not use them for a simple list item or content card.
- Dashboard postboxes (in `#dashboard-widgets`) override the base style: `border-radius: 8px` instead of `0`.
- Postboxes can be dragged and reordered on the dashboard or post-edit page using jQuery UI Sortable.

---

### 2.2 Form & Input Elements

#### Text Inputs, Selects, Textareas

```html
<input type="text" value="" class="regular-text" />
<select>
  <option>Option 1</option>
  <option>Option 2</option>
</select>
<textarea></textarea>
```

**Base styles:**
- All inputs: `font-size: 14px`, `box-sizing: border-box`, `border: 1px solid #949494`, `border-radius: 2px`, `background: #fff`.
- Text inputs, selects, textarea: `padding: 0 12px`, `min-height: 40px` for text inputs and selects.
- Selects: `padding: 0 24px 0 12px` (right padding to fit the dropdown arrow).
- Focus state: `border-color: var(--wp-admin-theme-color)` (blue), `box-shadow: 0 0 0 1.5px var(--wp-admin-theme-color)`.

#### Checkboxes & Radios

WordPress 7.1 ships custom-styled checkboxes and radios using `::-webkit-appearance: none` and SVG content for the check mark.

```html
<input type="checkbox" id="agree" name="agree" />
<label for="agree">I agree</label>

<input type="radio" id="option-1" name="choice" value="1" />
<label for="option-1">Option 1</label>
```

**Styles:**
- Unchecked: `1rem × 1rem` square, `border: 1px solid #1e1e1e`, `background: #fff`, `border-radius: 2px`.
- Checked: `background: var(--wp-admin-theme-color)`, `border-color: var(--wp-admin-theme-color)`, displays an inline SVG checkmark (for checkbox) or a solid circle (for radio).
- Radios: `border-radius: 50%`.
- Focus: `box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--wp-admin-theme-color)` (outset style for better visibility).

#### Form Table

A two-column table for settings pages, often with labels on the left and input on the right.

```html
<table class="form-table" role="presentation">
  <tr>
    <th scope="row"><label for="field-id">Field Label</label></th>
    <td><input type="text" id="field-id" name="field" /></td>
  </tr>
  <tr>
    <th scope="row">Another Field</th>
    <td>
      <input type="radio" name="choice" value="a" /> Option A
      <input type="radio" name="choice" value="b" /> Option B
    </td>
  </tr>
</table>
```

**Rules:**
- Class is `form-table` (not `table`). Role is `presentation` (since the table is for layout, not data).
- `<th scope="row">` for labels. Text wrapping and overflow handled via CSS.
- `<td>` for inputs. Natural cell padding applied.
- No custom margins on form inputs inside a form-table.

---

### 2.3 Buttons

WordPress ships buttons with a few standard classes. Buttons can be `<button>` or `<a class="button">` elements.

```html
<button class="button button-primary">Save Changes</button>
<button class="button button-secondary">Cancel</button>
<a href="#" class="button">Link Button</a>
<button class="button button-link-delete">Delete</button>
```

**Classes:**
- `button`: Base button class. Required. Applies padding, border, cursor, and focus states.
- `button-primary`: Blue background, white text. Use for the primary action on a page (Save, Submit, Create).
- `button-secondary`: Gray background, dark text. Use for secondary actions (Cancel, Reset, Back).
- `button-link`: Unstyled button that looks like a link (blue text, underline on hover). No background or border.
- `button-link-delete`: Styled like a delete action (red text, red underline). No background. Use for destructive actions (Delete, Remove).

**Sizing:**
- Default: `min-height: 32px`, `padding: 0 12px`, `line-height: 2.3` (for 13px font, this gives 30px line height inside 32px button).
- No other button sizes are shipped by core.

**States:**
- Hover: Slightly darker background or text.
- Focus: `box-shadow: 0 0 0 1.5px var(--wp-admin-theme-color)` (thin outline in theme color).
- Active / Pressed: Darker background.
- Disabled: Add `disabled` attribute. Button becomes `opacity: 0.5` or similar.

**Buttons in Tablenav:**
- Inside `.tablenav` (the bar above a list table), buttons get `min-height: 32px`, `line-height: 2.31`, `padding: 0 12px`.

---

### 2.4 List Tables (WP_List_Table)

The `WP_List_Table` class is the workhorse for displaying post lists, user lists, plugin lists, and custom data. It renders as an HTML table with sortable columns, bulk actions, row actions, and pagination.

#### Table Markup

```html
<form id="posts-filter" method="post">
  <!-- Bulk actions bar: hidden on mobile -->
  <div class="tablenav top">
    <div class="alignleft actions">
      <select name="action" id="bulk-action-selector-top">
        <option value="-1">Bulk Actions</option>
        <option value="delete">Delete</option>
      </select>
      <input type="submit" id="doaction" class="button action" value="Apply">
    </div>
    <!-- search box, filters -->
    <div class="tablenav-pages">
      <span class="displaying-num">3 items</span>
      <a class="first-page" href="...">«</a>
      <a class="prev-page" href="...">‹</a>
      <span aria-current="page">1</span>
      <a class="next-page" href="...">›</a>
      <a class="last-page" href="...">»</a>
    </div>
  </div>

  <table class="wp-list-table fixed striped">
    <thead>
      <tr>
        <td class="manage-column check-column">
          <input type="checkbox" id="cb-select-all-1">
        </td>
        <th scope="col" class="manage-column sortable desc">
          <a href="..."><span>Title</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" class="manage-column">Author</th>
      </tr>
    </thead>
    <tbody id="the-list" data-wp-lists="list:post">
      <tr class="level-0">
        <th scope="row" class="check-column">
          <input type="checkbox" name="post[]" value="123">
        </th>
        <td class="column-title">
          <a href="edit.php?post=123">Post Title</a>
          <div class="row-actions">
            <span class="edit"><a href="...">Edit</a></span> |
            <span class="delete"><a class="submitdelete" href="...">Delete</a></span>
          </div>
        </td>
        <td class="column-author">Admin</td>
      </tr>
    </tbody>
  </table>

  <div class="tablenav bottom">
    <!-- pagination repeated here -->
  </div>
</form>
```

**Key classes & structure:**
- Outer `<form id="posts-filter">` wraps the entire table + controls.
- `.tablenav` (top and bottom): Bar for bulk actions, search, filters, pagination. Two per table (one above, one below).
- `.alignleft` inside `.tablenav`: Bulk actions live here (dropdown + Apply button).
- `.tablenav-pages`: Pagination controls. Right-aligned.
- `wp-list-table`: Table element. Receives additional classes:
  - `striped`: Alternate row colors (light gray on even rows).
  - `fixed`: `table-layout: fixed` (all columns get equal width unless specified).
  - `sortable`: Indicates columns are sortable (sortable columns get class `sortable`).
  - `hover`: Row highlights on hover (applied by theme, not core).
- `.check-column`: Checkbox column. Narrow width, centered.
- `.manage-column`: Used on both `<th>` and `<td>` to mark as a regular column cell.
- Sortable columns: Get class `sortable asc` or `sortable desc` depending on current sort. The `<a>` inside contains the column name and a `.sorting-indicator` span (renders an up/down arrow).
- Row actions: Placed in a `.row-actions` div inside the row. Hidden on mobile; revealed on hover (desktop) or shown inline (mobile).
- `.inline-edit-row`: An in-row edit form. Not all tables support this; it is optional.

**Mobile behavior:**
- On screens under ~782px, the table collapses to a mobile-friendly card layout. Each row becomes a full-width card with labels and values stacked vertically.
- Row actions remain functional but are displayed differently.
- Bulk actions bar is hidden; individual delete/edit links appear per row.

**Pagination:**
- Simple HTML: Previous/Next buttons and page number inputs.
- The "Displaying X items" text is updated by JavaScript on bulk actions.

**Notes on WP_List_Table:**
- Extend this class to create custom tables. Override `get_columns()`, `get_sortable_columns()`, `prepare_items()`.
- Column data is echoed via `column_<column-name>()` methods.
- Bulk actions and row actions are hooks: `$this->actions` and `$this->bulk_actions()`.
- WordPress handles sorting, pagination, search, and bulk actions server-side. The table class is responsible for query building.

---

### 2.5 Notices

Notices are dismissible or non-dismissible messages shown to the user. They appear as colored boxes with an icon, text, and an optional close button.

```html
<!-- Success notice -->
<div class="notice notice-success is-dismissible">
  <p>Your changes have been saved.</p>
  <button type="button" class="notice-dismiss">
    <span class="screen-reader-text">Dismiss this notice.</span>
  </button>
</div>

<!-- Error notice -->
<div class="notice notice-error">
  <p><strong>Error:</strong> Something went wrong.</p>
</div>

<!-- Info notice -->
<div class="notice notice-info">
  <p>This is an informational message.</p>
</div>
```

**Classes:**
- `notice`: Base notice class. Has `background: #fff`, `border-left: 4px solid #c3c4c7`, `padding: 12px`.
- `notice-success`: Green left border (`#4ab866`), light green background (`#eff9f1`).
- `notice-warning`: Yellow left border (`#f0b849`), light yellow background (`#fef8ee`).
- `notice-error`: Red left border (`#cc1818`), light red background (`#fcf0f0`).
- `notice-info`: Blue left border (`#3858e9`), white background.
- `is-dismissible`: Add this to include a close button. Adds right padding and positions the `.notice-dismiss` button absolutely.
- `notice-alt`: Removes the box shadow (less prominent).

**Dismiss button:**
- Class: `notice-dismiss`. A `<button type="button">` with no text (icon only).
- Icon: A Dashicon (X mark, code `\f335`).
- Position: Top-right inside the notice, absolutely positioned.
- JavaScript in WordPress core handles the dismiss action (removes the notice from the DOM).

**Placement rules:**
- Notices inside `.wrap` should appear **after `hr.wp-header-end`** and before main content.
- Do not place multiple notices in a stack; WordPress handles them with margin (`5px 0 15px`).
- Screen-reader-only text: Always include a `<span class="screen-reader-text">` inside the button to describe the action ("Dismiss this notice").

**Timing:**
- Use success notices after form submission (saves, updates).
- Use error notices when something breaks (validation errors, API failures).
- Use info notices for advisory content (deprecation warnings, tips).

---

### 2.6 Navigation Tabs

For multi-section pages (e.g., Settings page with "General", "Advanced", "Logs" tabs):

```html
<nav class="nav-tab-wrapper">
  <a href="?page=my-plugin&tab=general" class="nav-tab nav-tab-active">General</a>
  <a href="?page=my-plugin&tab=advanced" class="nav-tab">Advanced</a>
  <a href="?page=my-plugin&tab=logs" class="nav-tab">Logs</a>
</nav>
```

**Classes:**
- `nav-tab-wrapper`: Parent `<nav>` element. Sets up flex layout, bottom border.
- `nav-tab`: Individual tab link. Has padding, text color, hover state. On hover, background lightens.
- `nav-tab-active`: Added to the current tab. Has a white background and bottom border that matches the tab color (blue theme color).

**Behavior:**
- Tabs are links, not buttons. They navigate to a different page or update a query parameter.
- The active tab is determined server-side (check `$_GET['tab']` and add `.nav-tab-active` accordingly).
- No JavaScript required for tab switching; tabs are semantic links.

---

### 2.7 Dashicons

WordPress ships a complete icon font called Dashicons. Icons are inserted via CSS `content` property with a Dashicon class.

```html
<span class="dashicons dashicons-video"></span>
<span class="dashicons dashicons-list-view"></span>
<span class="dashicons dashicons-image"></span>
<span class="dashicons dashicons-warning"></span>
<span class="dashicons dashicons-yes"></span>
<span class="dashicons dashicons-update"></span>
```

**Usage:**
- Add both `dashicons` and `dashicons-<icon-name>` classes to a `<span>`.
- Icon is rendered via `::before` CSS pseudo-element with a Unicode character as `content`.
- Size: `1em` (inherits from parent font size). Adjust by setting `font-size` on the parent or the icon span.
- Color: `currentColor` by default. Inherits text color.

**Common icons for our surfaces:**
- `dashicons-video`: A video camera icon. Use for video lessons.
- `dashicons-list-view`: A horizontal list icon. Use for list/grid views, navigation.
- `dashicons-image`: A photo/image icon. Use for image content, screenshots.
- `dashicons-warning`: A warning/alert triangle. Use for stale/outdated content.
- `dashicons-yes` / `dashicons-no`: Checkmark and X. Use for status indicators.
- `dashicons-update`: A refresh/reload icon. Use for "regenerating" content.
- `dashicons-eye` / `dashicons-hidden`: For visibility toggles.

**Note:** Dashicons is a complete font. Use the [Dashicons reference](https://developer.wordpress.org/apis/dashicons/) to find additional icons. Over 300 are available.

---

### 2.8 Spinners & Progress

#### Spinner

A rotating loading indicator:

```html
<span class="spinner"></span>
```

**Styles:**
- `width: 20px`, `height: 20px`.
- Rotates continuously via CSS animation.
- Used inside buttons (e.g., "Saving...") or next to a process.

#### No direct progress bar

WordPress does not ship a progress bar component. For our use case (e.g., "3 of 5 lessons completed"), we would use a custom bar or rely on text.

---

### 2.9 Accessibility & Screen Reader Support

#### Screen Reader Text

Always include hidden text for screen readers:

```html
<a href="#" class="button">
  <span class="screen-reader-text">Edit this lesson</span>
  <span aria-hidden="true">Edit</span>
</a>
```

**Class:** `screen-reader-text`. Visually hides the text using `clip-path: inset(50%)`, `height: 1px`, `width: 1px`, but leaves it accessible to screen readers.

**Rule:** If a button's visual label is incomplete (e.g., an icon-only button), always pair it with hidden `.screen-reader-text` that describes the action.

#### ARIA Labels

List tables use `data-wp-lists="list:singular"` to announce the list type to screen readers. Postboxes use `aria-expanded="true|false"` on the toggle button.

---

## 3. Admin Colour Schemes

WordPress ships with several built-in admin colour schemes (Light, Dark, Blue, Coffee, Ectoplasm, Midnight, Ocean, Sunrise). Users select a scheme from their profile settings.

### Reading the Active Scheme

The active scheme is stored in user meta. There is **no CSS custom property or class** that a plugin can read to automatically pick up the active theme accent colour.

However, WordPress does expose:
- `var(--wp-admin-theme-color)`: The primary accent colour (used for active tabs, button hovers, focus outlines). This is a CSS custom property set on `:root` by the core stylesheet.
- `var(--wp-admin-theme-color-darker-10)` and `var(--wp-admin-theme-color-darker-20)`: Darker variants for hover/active states.
- `var(--wp-admin-theme-color--rgb)`: The RGB value (without the `rgb()` wrapper), useful for transparency. E.g., `rgba(var(--wp-admin-theme-color--rgb), 0.5)`.

### Practical Approach

**We CAN use the theme colour for accent elements without building a full skin:**

```css
.lesson-card:hover {
  border-color: var(--wp-admin-theme-color);
}

.lesson-card.active {
  background-color: rgba(var(--wp-admin-theme-color--rgb), 0.1);
  border-color: var(--wp-admin-theme-color);
}
```

This approach respects the user's colour scheme choice without us building theme-specific CSS.

### If Full Theming is Needed

We would need to:
1. Detect the active scheme server-side (query user meta).
2. Enqueue a CSS file for that scheme (e.g., `admin-light.css`, `admin-dark.css`).
3. Or inject custom CSS rules server-side that set colors for our plugin's elements.

**For now, use CSS custom properties where possible and accept the default light/dark scheme without custom styling.**

---

## 4. Responsive Behaviour

### Breakpoints

WordPress does not document a official breakpoint system for admin pages. Key thresholds observed:

- **≤ 782px**: Mobile / tablet threshold. List tables switch to card layout. Bulk actions hide. Sidebar (if present) moves below content. Menu collapses.
- **≤ 600px**: Very narrow. Typography scales, form elements get full width.

**Postbox/metabox**: No responsive changes built-in. They remain full-width at all sizes.

### List Table Mobile Collapse

At ≤ 782px:
- The table switches from a row-oriented layout to a card-oriented layout (`.responsive-enabled`).
- Each row becomes a card with labels on the left and values on the right (or stacked).
- Row actions are shown inline (not hidden) because hover states don't work on touch.
- Checkbox column remains but gets larger touch targets.

### Navigation / Menu

Below 782px, the admin sidebar collapses into a hamburger menu (if using default admin layout). Our plugin pages inside the admin do not control this; it is handled by core.

### Forms

Form inputs and buttons scale naturally. Buttons remain `min-height: 32px` on all widths. Text inputs stretch to fit their container.

---

## 5. Accessibility Conventions

### Focus Management

- All interactive elements (buttons, links, inputs, tabs) must be focusable via keyboard.
- Focus styles: `box-shadow: 0 0 0 1.5px var(--wp-admin-theme-color)` or `outline: 2px solid transparent` (high-contrast mode indicator).
- Do not remove focus outlines; enhance them if needed.

### ARIA Labels & Roles

- Use `role="presentation"` on tables used for layout (e.g., `form-table`), not data.
- Use `aria-label` or `aria-labelledby` on unlabeled buttons or controls.
- Postboxes use `aria-expanded="true|false"` on toggle buttons.
- Use `aria-hidden="true"` on decorative icons or spaces.

### Semantic HTML

- Use `<h1>`, `<h2>`, etc. for actual headings, not styled `<div>`.
- Use `<label>` paired with form inputs.
- Use `<button>` for actions, not `<div onclick="...">`.
- Use `<a>` for navigation, not `<button>` styled as a link.

### Skip Links

WordPress core provides a `.screen-reader-shortcut` skip link that jumps to `#wpbody-content`. Plugins do not need to add this; it is part of the layout.

---

## 6. The Gaps: What WordPress Admin Does NOT Provide

### Gap 1: Card Grid Layout

**Problem:** Our lesson library needs a grid or card view (e.g., 3 columns of lesson cards with title, description, status). WordPress ships no card component or grid system.

**What we must build:** Custom CSS for:
- A container with `display: grid` or `display: flex` and column layout.
- Individual card styling (border, padding, background, hover state).
- Responsive grid (e.g., 1 column on mobile, 2 on tablet, 3 on desktop).

**Estimated effort:** 30 lines of CSS. No JavaScript needed.

### Gap 2: Status Badge / Indicator

**Problem:** We need to show whether a lesson is "Current", "Stale", or "Regenerating". WordPress admin has no badge component.

**What we must build:** Custom CSS for badge styling:
```css
.lesson-status {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 3px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}
.lesson-status.stale {
  background-color: #fef8ee;
  color: #996800;
  border: 1px solid #f0b849;
}
```

No component class, no icon support out of the box. **Estimated effort:** 20 lines of CSS.

### Gap 3: Inline Steps / Numbered List

**Problem:** A single lesson page shows ordered steps (1, 2, 3, ...) with a screenshot or description per step. WordPress has no "steps" component.

**What we must build:**
- A custom list or timeline layout with step numbers.
- Optionally, a visual connector line between steps (requires custom CSS or SVG).
- Responsive stacking on mobile.

**Estimated effort:** 50 lines of CSS.

### Gap 4: Media Lightbox / Modal for Screenshots

**Problem:** Lesson steps include screenshots. We want a lightbox to zoom in when clicked.

**What we must build:** A custom modal / lightbox:
- HTML structure (modal wrapper, close button, image container).
- CSS for the overlay and modal positioning.
- JavaScript to open/close and handle clicks.

**Alternative:** Use WordPress core media modal (`.media-modal`, available when `wp.media` is enqueued), but it is designed for media library interactions, not arbitrary screenshots. We may use it or build a simpler lightbox.

**Estimated effort:** 100 lines of custom code (CSS + JS).

### Gap 5: Operator Dashboard Table (G&M Side)

**Problem:** The G&M operator panel needs a table of client sites and which lessons are stale. This is a highly custom data layout.

**What we must build:** A custom table or data grid with:
- Client site name (linked to the site).
- Status per lesson (color-coded, e.g., green = current, orange = stale, gray = not assigned).
- Last updated date.
- Action to regenerate a lesson on that site.

WordPress WP_List_Table *could* be extended, but it is designed for post/user/comment lists (single data type). For a cross-site status dashboard, a custom HTML table is simpler.

**Estimated effort:** 100 lines of HTML/PHP + 40 lines of CSS.

### Gap 6: Drag-and-Drop Reordering (Optional)

**Problem:** If we let clients or G&M reorder lessons, we need drag-and-drop.

**What we must build:** JavaScript using a library like Sortable.js or react-beautiful-dnd (or WordPress's built-in jQuery UI Sortable).

**Estimated effort:** 80 lines of JavaScript.

### Gap 7: Search & Filtering

**Problem:** Our lesson library needs search (by title, keyword) and filtering (by kind: video, steps, etc.).

**What we must build:**
- Server-side: Query logic to filter lessons by status, kind, search term.
- Client-side (optional): Inline filtering via JavaScript / AJAX.
- UI: A search input and filter buttons or dropdowns.

**Estimated effort:** 50 lines of PHP + 30 lines of JS (if AJAX).

### Gap 8: Content Freshness Indicator

**Problem:** Show at a glance which lessons are stale (icon + color).

**What we must build:** Custom CSS styling for a "stale" state, possibly with an icon (Dashicon `dashicons-warning`). No component exists.

**Estimated effort:** 15 lines of CSS.

---

## 7. Colour & Typography

### Typography

- Base font: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif`.
- Base size: `13px` for body, `14px` for inputs.
- Headings: `h1` is `23px`, `font-weight: 400`.

### Colours (Default Light Scheme)

- Text (primary): `#1e1e1e`.
- Text (secondary): `#50575e`.
- Borders: `#c3c4c7` (light), `#949494` (darker).
- Background (input): `#fff`.
- Background (disabled input): `#f0f0f0`.
- Success: `#4ab866` (text), `#eff9f1` (background).
- Warning: `#f0b849` (border), `#fef8ee` (background).
- Error: `#cc1818` (border), `#fcf0f0` (background).
- Info: `#3858e9` (border).
- Admin theme (accent): `var(--wp-admin-theme-color)` (defaults to `#3858e9` blue, but user-configurable).

**Use these sparingly; let WordPress theme colours handle the rest.**

---

## 8. Surface Mapping: Our Three Needs

| Surface | WordPress Component | Custom Work |
|---------|-------------------|------------|
| **Lesson Library Index** | `.wrap` + custom grid layout + notices | Card grid (CSS), status badges (CSS), search/filter (PHP + optional JS) |
| **Single Lesson Page** | `.wrap` + postbox (optional) + form elements + notices | Step list styling (CSS), screenshot lightbox (JS + CSS), navigation between lessons (PHP + CSS) |
| **Operator Dashboard** | `.wrap` + custom data table (not WP_List_Table) | Table styling (CSS), status indicators (CSS), regenerate action (PHP) |

---

## 9. Recommended Development Approach

1. **Start with `.wrap` + typography.** Every page must have the basic chrome.

2. **Use core components where they fit:**
   - Form inputs, selects, checkboxes for editing.
   - Buttons (button-primary, button-secondary) for actions.
   - Notices (notice-success, notice-error) for feedback.
   - Postboxes only if grouping multiple related settings.

3. **Build custom CSS for:**
   - Card/grid layouts (not provided by core).
   - Status badges and indicators.
   - Step lists and ordered layouts.
   - Lesson operator dashboard.

4. **Avoid overriding core styles.** Use custom classes alongside core classes:
   ```html
   <div class="wrap">
     <div class="lesson-grid">
       <div class="lesson-card">...</div>
     </div>
   </div>
   ```

5. **Test responsive behaviour at 375px, 900px (tablet), and 1280px.** Ensure forms remain usable on mobile.

6. **Accessibility first:** Use semantic HTML, ARIA labels where needed, and test with a screen reader.

---

## Reference Links

- WordPress Admin Components: https://developer.wordpress.org/plugins/admin-menus/
- Dashicons: https://developer.wordpress.org/apis/dashicons/
- Form API: https://developer.wordpress.org/plugins/settings/
- List Tables: https://developer.wordpress.org/plugins/users/custom-list-tables/

---

**Document version:** 1.0
**Last updated:** 2026-09-20
**WordPress version:** 7.1.1
