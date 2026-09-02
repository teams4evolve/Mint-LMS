# Mint LMS

**Developer handoff & project reference** — single source of truth for anyone continuing this plugin.

| | |
|---|---|
| **Repo** | https://gitlab.com/elearning-evolve/mintlms (`main`) |
| **Version** | 1.0.0 |
| **Requires** | WordPress 6.2+, PHP 8.1+ |
| **Target** | [wordpress.org](https://wordpress.org) submission |

---

## Table of contents

1. [Product goals](#1-product-goals)
2. [Repository structure](#2-repository-structure)
3. [Architecture layers](#3-architecture-layers)
4. [Architecture tests](#4-architecture-tests)
5. [Design system & UI architecture](#5-design-system--ui-architecture)
6. [Database schema & migrations](#6-database-schema--migrations)
7. [REST API](#7-rest-api)
8. [WooCommerce integration](#8-woocommerce-integration)
9. [Extension registries](#9-extension-registries)
10. [The `dev/` folder](#10-the-dev-folder)
11. [Dev workflows](#11-dev-workflows)
12. [Release packaging](#12-release-packaging)
13. [CI](#13-ci)
14. [Build history & lessons learned](#14-build-history--lessons-learned)
15. [Current state & next steps](#15-current-state--next-steps)
16. [Command cheat sheet](#16-command-cheatsheet)

---

## 1. Product goals

Mint LMS is a free WordPress LMS plugin. The pitch is **not feature count** — it is **cleanliness**:

- A teacher installs it and builds a working course in under ten minutes, without documentation.
- Students get a modern learning experience, not a WordPress page with a checkbox on it.
- Everything runs on the customer's own WordPress install — no external service, API key, or license gate on core features.

Four commitments — stay honest about whether the product actually meets them:

| Goal | Meaning |
|------|---------|
| **Clean** | No settings page with sixty options |
| **UI friendly** | First-time user builds a course without a tutorial |
| **Bug free** | Progress, enrollment, and completion must never be wrong |
| **Fast** | 200 lessons / 5,000 students behaves like 10 lessons / 20 students |

Additional principles (from project guardrails):

- Reliability of enrollment, progress, and completion matters more than a large feature list.
- Built as a **product**, not a collection of WordPress admin screens.
- Performance: paginated lists, denormalized progress summary, structure fetched without N+1 queries.

**v1.0.0 is a version label, not QA-approved release status.** Treat feature parity claims skeptically until verified in a browser.

---

## 2. Repository structure

This Git repo **is** the plugin root (`mint-lms/`). Clone it into `wp-content/plugins/mint-lms/`.

```
mint-lms/
├── mint-lms.php              # Entry point; defines MINTLMS_* constants
├── uninstall.php             # Drops validated custom tables on uninstall
├── composer.json             # PHP dev tooling & autoload (distignored from wp.org zip)
├── phpunit.xml.dist          # PHPUnit config (distignored)
├── phpcs.xml                 # WPCS rules (distignored)
├── phpstan.neon              # PHPStan level 6 (distignored)
├── package.json              # Node build scripts (distignored)
├── tailwind.config.js        # Design tokens (distignored)
├── postcss.config.js         # Tailwind + scope prefix (distignored)
├── .distignore               # Files excluded from wp.org release zip
│
├── dev/                      # Dev-only tooling (distignored — see §10)
│   ├── plugin-check.sh
│   └── scripts/
│       ├── seed-v1-demo.php
│       ├── smoke-v1.php
│       ├── verify-all.php
│       └── verify-rest-actions.php
│
├── src/                      # PSR-4: MintLMS\
│   ├── Bootstrap.php         # Activation, migrations, wiring
│   ├── Plugin.php            # Service container / facades
│   ├── Domain/               # Pure PHP — NO WordPress
│   ├── Application/          # Use-case services — NO WordPress
│   ├── Infrastructure/       # WordPress adapters ($wpdb, admin, WC, email…)
│   ├── Http/Rest/            # Thin REST controllers
│   ├── Api/V1/               # Public facades + extension registries
│   └── Frontend/             # Shortcodes, templates, student assets
│
├── views/                    # Admin PHP views + reusable components
│   ├── admin/                # Dashboard, courses, reports, settings, builder…
│   ├── components/           # button, card, modal, table, tabs, toast… (19 components)
│   ├── emails/               # enrolled, course-completed
│   └── student/partials/
│
├── templates/student/        # Student-facing templates (theme-overridable)
├── assets/
│   ├── css/                  # Admin CSS source (mint.css, tokens, components)
│   ├── src/                  # JS/CSS source (distignored — build before release)
│   └── dist/                 # Built CSS/JS shipped to production
├── tests/                    # PHPUnit (distignored from wp.org zip)
│   ├── Architecture/         # Layer boundaries, CSS isolation
│   ├── Unit/
│   └── Integration/
├── languages/                  # mint-lms.pot
├── vendor/                   # Committed autoload runtime (wp.org has no build step)
└── readme.txt                  # WordPress.org plugin readme (separate from this file)
```

**Constants** (defined in `mint-lms.php`): `MINTLMS_VERSION`, `MINTLMS_FILE`, `MINTLMS_PATH`, `MINTLMS_URL`.

---

## 3. Architecture layers

PHP 8.1+, layered architecture. **Do not deviate without team discussion.**

### THE RULE

`Domain/` and `Application/` must contain **zero WordPress code**:

- No `$wpdb`, no `wp_*` functions, no `add_action`/`add_filter`
- No `current_user_can`, no `esc_*`/`sanitize_*`, no `WP_*` classes
- No `__()` translation calls in Domain/Application

All WordPress touches live in `Infrastructure/`, `Http/`, `Api/`, and `Frontend/`.

**Business logic must not depend on WordPress. WordPress depends on business logic.**

Enforced by `tests/Architecture/LayerBoundaryTest.php` — if it fails, the code is wrong, not the test.

| Layer | Path | Role |
|-------|------|------|
| **Domain** | `src/Domain/` | Entities (Course, Lesson, Section, Enrollment, Progress, Quiz), enums, repository interfaces, `DripAccessEvaluator`, domain events. No outer dependencies. |
| **Application** | `src/Application/` | Services (`CourseService`, `EnrollmentService`, `ProgressService`, `QuizService`, `CertificateService`, `StudentExperienceService`, …), DTOs, `Contract/` interfaces. Depends on Domain only. |
| **Infrastructure** | `src/Infrastructure/` | `Wpdb*` repositories, migrations, WP auth/media/cache, admin UI, WooCommerce, email, setup, `UiRoot`. |
| **Http** | `src/Http/Rest/` | Thin controllers: parse request → call service → shape response via `ApiResponse`. No business logic. |
| **Api** | `src/Api/V1/` | Frozen public PHP facades + extension registries for third-party hooks. |
| **Frontend** | `src/Frontend/` | `ShortcodeRegistrar`, `TemplateLoader`, `StudentAssetLoader`. |

### Additional rules

- **Authorization** belongs inside service methods, not only REST controllers.
- **Repositories** return domain objects/DTOs, not arbitrary raw arrays.
- **REST controllers** must not import concrete `Wpdb*` repositories or use `$wpdb`.
- **Schema changes** require a new numbered migration — never silently edit existing migrations.
- **Avoid:** Manager/Helper/Util classes without clear responsibility, static service calls, globals, service locators, singletons, business logic in templates/controllers/hooks, silent exception swallowing.

### Naming (fixed everywhere)

| Item | Value |
|------|-------|
| Namespace | `MintLMS\` |
| Text domain | `mint-lms` |
| Tables | `{prefix}mintlms_*` |
| Options/hooks | `mintlms_*` |
| REST namespace | `mintlms/v1` |
| CSS prefix | `mint-` |
| UI root | `#mint-lms-root` |
| JS global | `window.MintLMS` |

### Bootstrap sequence (`Bootstrap::onPluginsLoaded()`)

1. Run migrations (`MigrationRunner`)
2. `Plugin::boot()` — dispatcher, authorization, cache, media, event publisher
3. Register admin: `MenuRegistrar`, `SettingsPage`, `CourseBuilderPage`, `FirstRunRedirect`, `AssetLoader`, `AdminAjaxHandler`, `StudentAssetLoader`
4. Register frontend: repos, services, shortcodes, enroll handler on `template_redirect`
5. Register REST API: all services/controllers → `RouteRegistrar::register()`
6. Register notifications: `EmailNotificationRegistrar`
7. Register certificates: `CertificateDownloadHandler`
8. `WooCommerceIntegration::register()` when WooCommerce is active

---

## 4. Architecture tests

Run via `composer arch` (included in `composer check`).

| Test | File | Enforces |
|------|------|----------|
| Pure layer boundaries | `LayerBoundaryTest::test_pure_layers_contain_no_wordpress_code` | Domain/Application contain none of 15 forbidden WP patterns (`$wpdb`, `wp_*`, hooks, `esc_*`, `WP_*`, etc.) |
| Domain isolation | `test_domain_does_not_depend_on_outer_layers` | Domain never imports Application/Infrastructure/Http/Api/Frontend |
| Application isolation | `test_application_does_not_depend_on_infrastructure` | Application never imports Infrastructure/Http/Frontend |
| Thin controllers | `test_controllers_do_not_touch_persistence` | Http layer has no `$wpdb` or concrete repository imports |
| REST security | `test_no_open_rest_routes` | No `__return_true` permission callbacks |
| Table prefix | `test_tables_use_the_agreed_prefix` | All custom tables use `mintlms_` prefix |
| CSS isolation | `CssIsolationTest` | UI entrypoints use `UiRoot`; compiled CSS scoped to `#mint-lms-root`; no global `html`/`body`/unscoped selectors |
| ApiResponse imports | `RestControllerApiResponseTest` | Controllers using `ApiResponse::` have proper import |

---

## 5. Design system & UI architecture

### Design philosophy

Target: modern SaaS/LMS quality (Teachable / Linear / Notion references — **not** generic wp-admin styling).

**Do:**
- Polished, restrained, spacious, premium feel
- Strong visual hierarchy, excellent typography, clear states
- Subtle borders/shadows, consistent spacing, purposeful interactions

**Avoid:**
- Childish oversized cards, excessive rounded rectangles, generic Bootstrap layouts
- Random gradients, excessive shadows, huge empty areas, too many badges
- Dense WordPress-looking admin UI or developer CRUD panels

**Do not redesign screens independently.** Establish the design system once, apply via reusable components in `views/components/`.

### Recommended design workflow

Cursor's auto-generated UI was rated poorly early in the project. The workflow that worked:

1. Design screens in **Claude Design** (or similar)
2. Export a handoff package (tokens, per-screen specs)
3. Implement screen-by-screen in Cursor against that handoff — do not invent styling ad hoc

### CSS isolation (hard requirement)

All plugin UI **must** render inside `#mint-lms-root`. Tested against third-party themes; enforced by `CssIsolationTest`.

| Mechanism | Location | Purpose |
|-----------|----------|---------|
| `UiRoot::open()` / `close()` | `src/Infrastructure/Ui/UiRoot.php` | Wraps admin + student UI in `#mint-lms-root` |
| Tailwind `prefix: 'mint-'` | `tailwind.config.js` | All utility classes prefixed |
| Tailwind `important: '#mint-lms-root'` | `tailwind.config.js` | Specificity without global bleed |
| Tailwind `preflight: false` | `tailwind.config.js` | No Tailwind reset on customer theme |
| `postcss-prefix-selector` | `postcss.config.js` | Scopes compiled CSS under `#mint-lms-root` |
| Scoped reset | `assets/src/scoped-reset.css` | Box-model reset inside root only |

**Never compromise theme isolation.** If styles leak into wp-admin chrome or the customer theme, fix scoping before shipping.

### Design tokens

Defined in `tailwind.config.js` and `assets/css/mint-tokens.css`:

- **Accent:** `#3F00FF` (hover `#3200CC`, wash `#EFEBFF`)
- **Neutrals / ink:** `#0F0E1A`, `#33334A`, `#5C5C77`, `#F4F3F8`
- **Semantic hues:** 6 paired background/text tints for badges and labels
- **Typography scale:** overline through display (12px–60px), custom line-heights and letter-spacing
- **Spacing:** 4px base grid through 96px; `tree: 296px` for builder sidebar
- **Shadows:** focus rings, card, menu, modal
- **Font stack:** Aeonik, General Sans, system fallbacks

### Asset pipeline

| Asset | Source | Built output | Loaded by |
|-------|--------|--------------|-----------|
| Admin CSS | `assets/css/mint.css` (+ tokens, components) | `assets/dist/main.css` | `AssetLoader` on `mint-lms*` admin pages |
| Admin JS | `assets/src/main.js` (+ builder, api, students-admin…) | `assets/dist/main.js` | `AssetLoader` |
| Student CSS | `assets/src/student.css` | `assets/dist/student.css` | `StudentAssetLoader` |
| Student JS | `assets/src/student.js` | `assets/dist/student.js` | `StudentAssetLoader` |

**JS stack:** Alpine.js 3.x + Sortable.js. No React, Vue, or jQuery.

`assets/src/main.js` exposes `window.MintLMS` with Sortable, toast helpers, and Alpine component registrations (`courseBuilder`, `coursesList`, etc.).

**REST URL building:** centralized in `assets/src/api.js` — do not introduce a second URL-joining implementation.

Build commands (see [§11](#11-dev-workflows)):

```bash
npm ci && npm run build    # production
npm run watch:css          # dev CSS
npm run watch:js           # dev JS
```

**Ship `assets/dist/` in releases.** Source in `assets/src/` is distignored.

### UI components

Reusable PHP partials in `views/components/` (rendered via `ViewRenderer`):

`avatar`, `badge`, `brand`, `button`, `card`, `checkbox`, `dropdown`, `empty-state`, `error-state`, `input`, `modal`, `progress-bar`, `select`, `skeleton`, `table`, `tabs`, `textarea`, `toast`, `toggle`

### Admin screens

| Screen | Path / registrar | Notes |
|--------|------------------|-------|
| Dashboard | `MenuRegistrar` → `views/admin/dashboard.php` | First-run redirect if incomplete |
| Courses list | `views/admin/courses/list.php` | Filterable, paginated |
| Course builder | `CourseBuilderPage` (hidden menu) | Flagship screen — see structure below |
| Course edit | `CourseBuilderPage` (hidden menu) | Metadata editing |
| Students | `views/admin/courses/students.php` | Per-course enrollment management |
| Reports | `views/admin/reports.php` | |
| Settings | `SettingsPage` | Pages, emails, certificate template, uninstall |
| Guided first course | Hidden menu | Onboarding wizard |
| First run | `views/admin/first-run.php` | Post-activation welcome |

**Course builder structure (target UX):**
- **Header:** title, save state, preview, publish/status
- **Left pane:** sections + nested lessons, drag/drop (Sortable.js), inline rename, clear selection
- **Main editor:** lesson title, content, video URL, attachment, preview/drip settings — not a giant settings form

### Student screens

Templates in `templates/student/`; theme overrides at `{theme}/mint-lms/{template}` via `TemplateLoader`.

Shortcodes (registered in `ShortcodeRegistrar`, wrapped in `UiRoot::open('student')`):

| Shortcode | Purpose |
|-----------|---------|
| `[mint_lms_dashboard]` | Student home — in-progress courses, continue action |
| `[mint_lms_my_courses]` | Enrolled courses list |
| `[mint_lms_catalog]` | Browse available courses |
| `[mint_lms_course id="123"]` | Single course overview |
| `[mint_lms_player course="123"]` | Lesson player |
| `[mint_lms_certificate course="123"]` | Completion certificate |

Enrollment also via GET: `?mint_lms_enroll=1&course_id={id}` on `template_redirect`.

---

## 6. Database schema & migrations

LMS data uses **custom tables**, never postmeta. Own auto-increment IDs — never WP post IDs as primary keys.

### Tables (`Schema.php`)

| Table | Purpose |
|-------|---------|
| `{prefix}mintlms_courses` | Course records |
| `{prefix}mintlms_sections` | Sections within courses |
| `{prefix}mintlms_lessons` | Lessons within sections |
| `{prefix}mintlms_enrollments` | Student enrollments |
| `{prefix}mintlms_progress` | Per-lesson completion |
| `{prefix}mintlms_progress_summary` | Denormalized course progress (recalculated on completion) |
| `{prefix}mintlms_quizzes` | Quiz per lesson |
| `{prefix}mintlms_quiz_questions` | Quiz questions |
| `{prefix}mintlms_quiz_attempts` | Student quiz attempts |

Table names validated via `Schema::validateTable()` before use in migrations/uninstall.

### Migrations (`MigrationRunner`, option `mintlms_db_version`)

| Class | Version | Change |
|-------|---------|--------|
| `Migration_001_InitialSchema` | 001 | Core tables via `dbDelta` |
| `Migration_002_Quizzes` | 002 | Quiz tables |
| `Migration_003_Drip` | 003 | `available_after_days` column on lessons |
| `Migration_004_CertificateTemplate` | 004 | Default `mintlms_certificate_template` option |

### Repositories

Interfaces in `src/Domain/*/`*RepositoryInterface.php`  
Implementations in `src/Infrastructure/Database/Repository/`:

`WpdbCourseRepository`, `WpdbSectionRepository`, `WpdbLessonRepository`, `WpdbEnrollmentRepository`, `WpdbProgressRepository`, `WpdbQuizRepository`, `WpdbAdminDashboardRepository`

---

## 7. REST API

**Namespace:** `mintlms/v1`  
**Responses:** `MintLMS\Http\Rest\Response\ApiResponse`

| Controller | Key routes |
|------------|------------|
| `CourseController` | `GET/POST /courses`, `GET/PATCH/DELETE /courses/{id}`, `GET /courses/{id}/structure`, `POST /courses/{id}/publish` |
| `SectionController` | `POST /courses/{id}/sections`, `PATCH/DELETE /sections/{id}`, `POST /courses/{id}/sections/reorder` |
| `LessonController` | `POST /sections/{id}/lessons`, `GET/PATCH/DELETE /lessons/{id}`, `POST /sections/{id}/lessons/reorder` |
| `EnrollmentController` | `GET /courses/{id}/students`, `POST /courses/{id}/enroll`, `DELETE /enrollments/{id}`, `GET /me/courses` |
| `ProgressController` | `POST/DELETE /lessons/{id}/complete`, `GET /courses/{id}/progress` |
| `QuizController` | Quiz CRUD, questions CRUD, `POST /quizzes/{id}/attempt`, `GET /quizzes/{id}/attempts` |
| `OnboardingController` | `POST /onboarding/complete` |
| `UserController` | `GET /users/search` |

`RouteRegistrar` also registers `POST /user-pref` (admin UI preference).

### Security (non-negotiable)

- Real `permission_callback` on every route — never `__return_true` for protected routes
- `$wpdb->prepare()` for queries involving input; `%i` for table identifiers (requires WP 6.2+)
- Nonces on state-changing admin/AJAX requests
- Authorization inside services — verify enrollment, course ownership, lesson-in-course membership server-side
- Non-preview content must never be sent to non-enrolled users and merely hidden with CSS

---

## 8. WooCommerce integration

**Location:** `src/Infrastructure/WooCommerce/` (core — not a separate plugin)

| Class | Role |
|-------|------|
| `WooCommerceIntegration` | Boots when `class_exists('WooCommerce')`; registered in `Bootstrap::onPluginsLoaded()` |
| `ProductMeta` | Course ID field on simple/variable products + variations |
| `OrderHandler` | Enroll on `woocommerce_order_status_completed`; revoke on refund/cancel |
| `EnrollmentBridge` | Calls `EnrollmentService::manualEnroll()` / `cancelByUserAndCourse()` |
| `SettingsPage` | Submenu under WooCommerce; option `mintlms_wc_cancel_on_refund` |

**Meta keys:** `_mintlms_course_id`, `_mintlms_wc_enrollments`, `_mintlms_wc_enrollment_processed`  
**Hooks:** `mintlms_wc_loaded`, `mintlms_wc_before_enroll`, `mintlms_wc_after_enroll`, etc.

The old `mint-lms-woocommerce/` addon folder is **removed** from `wp-content/plugins/`.

> **TODO:** `readme.txt` still tells users to install a separate "Mint LMS WooCommerce" addon — update before wp.org submission.

---

## 9. Extension registries

Public extension points in `src/Api/V1/` (helpers in `src/Api/V1/functions.php`):

| Function | Registry | Purpose |
|----------|----------|---------|
| `mintlms_register_question_type()` | `QuestionTypeRegistry` | Custom quiz question types |
| `mintlms_register_lesson_content_type()` | `LessonContentTypeRegistry` | Custom lesson content types |
| `mintlms_register_certificate_template()` | `CertificateTemplateRegistry` | Certificate rendering |
| `mintlms_register_payment_gateway()` | `PaymentGatewayRegistry` | Payment integrations |
| `mintlms_register_progress_rule()` | `ProgressRuleRegistry` | Custom progress rules |

Frozen facades: `Api/V1/Courses`, `Enrollment`, `Progress` — bound in `Plugin::register*()`.

---

## 10. The `dev/` folder

### Why inside the repo?

Dev tooling lives in `dev/` **inside the plugin repo**, excluded from the wp.org zip via `.distignore`. This ensures:

- `composer check` and CI work on a fresh clone (no sibling folder needed)
- Plugin Check does not scan dev scripts (they previously caused ~176 warnings when under `scripts/`)
- One repo, one source of truth

**Do not** create a second plugin folder or staging copy under `wp-content/plugins/` for release checks.

### Contents

#### `dev/plugin-check.sh`

Runs WordPress Plugin Check against the live plugin using `.distignore` exclusions — **no staging copy**.

```bash
# Via composer (uses --ignore-warnings for CI pass)
composer plugin-check

# Direct (set WP_PATH if not using default local install)
WP_PATH=/path/to/wordpress bash dev/plugin-check.sh
```

Behavior:
- Reads `.distignore` to build `--exclude-directories` / `--exclude-files` for `wp plugin check`
- Always excludes `scripts`, `tests`, `.github`, `.git`, `node_modules` if present
- Activates plugin temporarily if inactive, restores prior state after

#### `dev/scripts/seed-v1-demo.php`

Seeds demo course, student user (`mintstudent`), quiz, and enrollment data for manual/automated testing.

```bash
wp eval-file wp-content/plugins/mint-lms/dev/scripts/seed-v1-demo.php
```

Creates course slug `mint-lms-demo-course`. Run once on a fresh install before smoke/verify scripts.

#### `dev/scripts/smoke-v1.php`

Smoke tests after seeding — verifies demo course, student, enrollment, progress, quiz context.

```bash
wp eval-file wp-content/plugins/mint-lms/dev/scripts/smoke-v1.php
```

Requires `seed-v1-demo.php` first. Called automatically by `verify-all.php`.

#### `dev/scripts/verify-rest-actions.php`

Exercises every REST action used by admin/student UI (create course, sections, lessons, enroll, complete, quiz attempt, etc.). Creates and cleans up test data.

```bash
wp eval-file wp-content/plugins/mint-lms/dev/scripts/verify-rest-actions.php
```

#### `dev/scripts/verify-all.php`

Full verification suite — admin page renders, shortcodes, REST actions, smoke tests.

```bash
wp eval-file wp-content/plugins/mint-lms/dev/scripts/verify-all.php
```

Checks for fatals/warnings in rendered HTML. Includes `verify-rest-actions.php` and `smoke-v1.php`.

**Recommended verification order:**

```bash
wp eval-file dev/scripts/seed-v1-demo.php
wp eval-file dev/scripts/verify-all.php
```

All scripts require Mint LMS active and WP-CLI available.

---

## 11. Dev workflows

### First-time setup

```bash
# Clone
git clone https://gitlab.com/elearning-evolve/mintlms.git wp-content/plugins/mint-lms
cd wp-content/plugins/mint-lms

# PHP dependencies
composer install

# Node dependencies + build assets
npm ci && npm run build

# Activate in WordPress
wp plugin activate mint-lms --path=/path/to/wordpress
```

### Daily development loop

1. Make code changes in `src/`, `views/`, `templates/`, or `assets/src/`
2. If CSS/JS changed: `npm run build` (or `npm run watch:css` / `watch:js`)
3. Run quality gate: **`composer check`** (required after meaningful changes)
4. Test in browser — do not trust agent "done" claims without opening the page
5. Optionally: `wp eval-file dev/scripts/verify-all.php`

### Quality gates

| Command | What it runs |
|---------|--------------|
| `composer check` | Architecture tests → PHPStan 6 → PHPCS → unit tests |
| `composer arch` | `LayerBoundaryTest`, `CssIsolationTest`, `RestControllerApiResponseTest` |
| `composer stan` | PHPStan static analysis |
| `composer sniff` | WordPress Coding Standards |
| `composer fix` | PHPCBF auto-fix |
| `composer test:unit` | Unit tests |
| `composer test:integ` | Integration tests (requires WP test env) |
| `composer plugin-check` | WP Plugin Check via `dev/plugin-check.sh` |

### Plugin Check: ERROR vs WARNING

Plugin Check has two severities — **do not conflate them:**

| Severity | Meaning | Current count |
|----------|---------|---------------|
| **ERROR** | Blocks wp.org review | **0** |
| **WARNING** | Informational; many expected for custom-table LMS code | **3** (dev-only files) |

The 3 warnings (`.gitignore`, `.distignore`, `.github/`) are **dev-only** and excluded from the release zip. They will not appear in a production build.

`composer plugin-check` passes with `--ignore-warnings`. For the full picture:

```bash
wp plugin check mint-lms --path=/path/to/wordpress
```

### Duplicate-plugin check

After any plugin-check or staging work:

```bash
wp plugin list | grep -i mint
# Should show exactly ONE mint-lms entry
```

If "Mint LMS" appears twice, a folder with a duplicate `Plugin Name:` header exists under `plugins/` — remove it immediately.

---

## 12. Release packaging

`.distignore` defines what **ships** to wordpress.org. Key exclusions:

| Excluded | Why |
|----------|-----|
| `/dev`, `/tests`, `/.github`, `/.git` | Dev/CI only |
| `/assets/src`, `/node_modules` | Source assets; ship `assets/dist/` |
| `composer.json`, `phpcs.xml`, `phpstan.neon`, `package.json`, `tailwind.config.js` | Build/dev tooling |
| Dev vendor tools (`/vendor/phpunit`, `/vendor/phpstan`, …) | Run `composer install --no-dev` before packaging |

**Shipped:** `src/`, `views/`, `templates/`, `assets/dist/`, `vendor/` (runtime autoload), `languages/`, `readme.txt`, `mint-lms.php`, `uninstall.php`.

Before submission: build assets (`npm run build`), run Plugin Check on release-shaped tree, update `readme.txt` (WooCommerce copy, WP version consistency).

---

## 13. CI

GitHub Actions workflow: `.github/workflows/check.yml`

| Job | PHP versions | Steps |
|-----|--------------|-------|
| `check` | 8.1, 8.3 | `composer install` → `composer check` → `composer test:integ` |
| `plugin-check` | 8.3 | `composer install` → `npm ci && npm run build` → WordPress setup → `dev/plugin-check.sh` |

> **Caveat — monorepo path assumption:** The workflow sets `working-directory: wp-content/plugins/mint-lms` and trigger paths `wp-content/plugins/mint-lms/**`. This repo **is** the plugin root at `.`. For standalone clones (GitLab), update the workflow to use `working-directory: .` and path filters `**` — otherwise CI will not trigger or will fail to find files.

GitLab CI (`.gitlab-ci.yml`) is not configured yet — mirror the GitHub workflow jobs if adding GitLab pipelines.

---

## 14. Build history & lessons learned

Built primarily by Cursor agents with repeated adversarial testing. Key lessons:

### Trust but verify

Agents repeatedly reported work as complete when it was not — "0 errors" while warnings remained, "production ready" while core flows were broken. **Re-verify every "done" claim** by opening the page or running checks yourself. Re-paste architecture rules in long Cursor sessions to prevent drift.

### v0.1 was not an LMS

First multi-agent push had solid architecture but unusable product: 3 admin menu items, broken stubs, unwired shortcodes. Fixed through v0.2.0 → v1.0.0. Early agent self-reports were unreliable.

### Duplicate-plugin bug (recurred 3+ times)

`mint-lms-release-check/` staging copy had identical `Plugin Name: Mint LMS` header → WordPress registered two plugins. **Final fix:** removed staging copy; Plugin Check runs in-place via `dev/plugin-check.sh` + `.distignore`. Never recreate staging copies under `plugins/`.

### Plugin Check false "clean" reports

Agents reported "Plugin Check passed, no errors" while admin UI showed many **WARNING**s. Always distinguish ERROR vs WARNING and read actual output.

### WooCommerce port was incomplete

Classes existed in core but `Bootstrap` was not calling `WooCommerceIntegration::register()` — integration was dead until wired.

### Feature gaps vs LearnDash

Mint LMS lacks several LearnDash Essentials features (sub-lesson "topics" hierarchy, rich lesson editor parity). Course structure is **Course → Section → Lesson** only.

---

## 15. Current state & next steps

### Verified (Sep 2026)

| Check | Status |
|-------|--------|
| Plugin Check errors | **0** |
| Plugin Check warnings | **3** (dev-only, non-shipping) |
| `composer check` | Passes |
| WooCommerce in core | Wired and active when WC installed |
| Duplicate plugin entry | Fixed — one `mint-lms` in plugin list |

### Immediate TODO

1. **Update `readme.txt`** — remove separate WooCommerce addon language; align Requirements WP version (body says 6.0+, header says 6.2)
2. **Manual browser QA** — teacher flow (create → build → publish) and student flow (enroll → complete → certificate)
3. **Fix CI paths** — update `.github/workflows/check.yml` for standalone repo layout (see [§13](#13-ci))
4. **Before wp.org submission** — `npm run build`, Plugin Check on release zip, final readme pass

---

## 16. Command cheat sheet

```bash
cd wp-content/plugins/mint-lms

# ── Quality gates ──
composer check              # Required after meaningful changes
composer plugin-check       # WP.org Plugin Check (ignores warnings)
composer stan               # PHPStan only
composer sniff              # PHPCS only
composer fix                # PHPCBF auto-fix
composer test:unit
composer test:integ

# ── Assets ──
npm ci && npm run build     # Production build
npm run watch:css           # Dev CSS watch
npm run watch:js            # Dev JS watch

# ── Plugin Check (full output) ──
wp plugin check mint-lms --path=/path/to/wordpress

# ── Verification scripts ──
wp eval-file dev/scripts/seed-v1-demo.php
wp eval-file dev/scripts/verify-all.php
wp eval-file dev/scripts/verify-rest-actions.php
wp eval-file dev/scripts/smoke-v1.php

# ── Sanity checks ──
wp plugin list | grep -i mint    # Exactly ONE mint-lms entry
```

When in doubt: read `tests/Architecture/LayerBoundaryTest.php`, run `composer check`, open the page in a browser.
