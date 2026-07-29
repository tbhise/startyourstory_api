# PROJECT_ARCHITECTURE_RULES.md

# Project Architecture Rules

These architecture rules must be followed throughout the project. Any new module, feature, or page should adhere to these standards to maintain consistency, scalability, and maintainability.

---

# 1. Layout Structure

Use a single master layout.

```
resources/views/layouts/
├── main.blade.php
├── header.blade.php
├── navbar.blade.php
├── sidebar.blade.php
└── footer.blade.php
```

### Rules

* `main.blade.php` must include:

  * Header
  * Navbar
  * Sidebar
  * Footer
* Every Blade view must extend `layouts.main`.
* Do not duplicate layout code across pages.
* Shared UI components should always be placed inside the `layouts` directory.

---

# 2. CSS Structure

Maintain a common stylesheet for the entire project.

Example

```
public/assets/css/app.css
```

### Rules

* Keep all common styles inside the global CSS file.
* Reuse existing utility classes whenever possible.
* Avoid creating module-specific CSS files unless absolutely necessary.
* Do not duplicate styles across files.
* Prefer reusable CSS components over page-specific styling.

---

# 3. JavaScript Structure

Maintain one global JavaScript file and separate module-specific files.

Example

```
public/assets/js/

app.js
dashboard.js
users.js
reports.js
settings.js
```

### Rules

`app.js`

Contains

* Global AJAX setup
* Common helper functions
* Utility methods
* Toastr/notification helpers
* Shared event handlers

Each module should have its own JavaScript file.

Examples

* dashboard.js
* users.js
* reports.js
* settings.js

### Rules

* Import module JavaScript only on pages where it is required.
* Never write inline JavaScript inside Blade files.
* Keep JavaScript modular and reusable.
* Business logic should remain inside dedicated module files.

---

# 4. View Structure

Every module must have its own directory.

Example

```
resources/views/

dashboard/
    index.blade.php

users/
    index.blade.php
    list.blade.php

reports/
    index.blade.php
    list.blade.php
```

### index.blade.php

Should contain

* Page title
* Filters
* Search controls
* Action buttons
* Empty container for dynamic listing

### list.blade.php

Should contain only

* Table HTML
* Pagination
* Listing rows

No filters, forms, or unrelated UI should exist inside `list.blade.php`.

This file is intended to be loaded dynamically through AJAX.

---

# 5. AJAX Architecture

The application should follow an AJAX-first approach.

### Rules

Use jQuery AJAX for

* Form submissions
* Data loading
* Search
* Pagination
* Filtering
* CRUD operations

Avoid full-page reloads whenever possible.

All listing pages should load their table content asynchronously.

---

# 6. Table-Based Interactions

Avoid modal-based workflows.

### Rules

* Display data using tables.
* Use dedicated pages or inline table interactions instead of modal dialogs.
* Keep user interactions simple and consistent.

---

# 7. Routing Guidelines

Organize routes by module.

Controllers should provide methods such as

* index()
* list()
* store()
* update()
* destroy()

The `list()` action should return only the listing partial (`list.blade.php`) for AJAX requests.

---

# 8. Controller Responsibilities

Controllers should remain lightweight.

### Responsibilities

* Validate requests
* Call services/repositories (if applicable)
* Return views
* Return JSON responses
* Return partial views

Avoid placing complex business logic directly inside controllers.

---

# 9. Reusability Guidelines

Always prioritize reusable components.

Avoid

* Duplicate HTML
* Duplicate CSS
* Duplicate JavaScript
* Copy-paste code

Instead

* Create reusable Blade partials
* Reuse utility classes
* Reuse helper functions
* Keep modules independent

---

# 10. Naming Conventions

Views

```
index.blade.php
list.blade.php
create.blade.php
edit.blade.php
```

JavaScript

```
dashboard.js
users.js
reports.js
```

CSS

```
app.css
```

Controllers

```
DashboardController
UserController
ReportController
```

Keep naming consistent across the project.

---

# 11. Future Scalability

Every module should be designed so that:

* Static data can later be replaced with database data.
* AJAX endpoints can later consume APIs without changing the UI structure.
* Additional features can be introduced with minimal refactoring.
* Components remain reusable across the application.

---

# 12. General Development Standards

* Follow PSR-12 coding standards.
* Write clean, readable, and maintainable code.
* Keep code modular.
* Avoid unnecessary dependencies.
* Maintain consistent folder structures.
* Prefer reusable components over one-off implementations.
* Write code that is easy to extend and maintain.
