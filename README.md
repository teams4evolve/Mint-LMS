# Mint LMS: Project Handoff Brief

Prepared for team handoff. Updated against the live codebase on **2 Sep 2026**.

Covers what Mint LMS is, architecture guardrails, how the build actually went, verified current state, and where to pick up.

---

## 1. What Mint LMS is

A free WordPress LMS plugin being built for submission to [wordpress.org](https://wordpress.org). The pitch is not feature count — it is **cleanliness**: a teacher installs it and builds a working course in under ten minutes with no docs, and students get a modern course experience instead of a WordPress page with a checkbox on it. Everything runs on the customer's own WordPress install: no external service, no API key, no license gate on core features.

Four things it has to be, and stay honest about whether it is:

- **Clean** — no settings page with sixty options
- **UI friendly** — a first-time user builds a course without a tutorial
- **Bug free** — progress, enrollment and completion must never be wrong
- **Fast** — 200 lessons / 5,000 students behaves like 10 lessons / 20 students

---

## 2. Architecture (locked — do not deviate without asking)

PHP 8.1+, layered architecture. One rule matters more than any other:

**THE RULE:** `Domain/` and `Application/` must contain **zero WordPress code**. No `$wpdb`, no `wp_*` functions, no `add_action`/`add_filter`, no `current_user_can`, no `esc_*`/`sanitize_*`, no `WP_*` classes. All WordPress touches live in `Infrastructure/`, `Http/`, `Api/`, and `Frontend/`. This is enforced by `tests/Architecture/LayerBoundaryTest.php` — if it fails, the code is wrong, not the test.

| Layer | Role |
|-------|------|
| **Domain/** | Pure PHP business logic (Course, Enrollment, Progress, Quiz entities; enums; repository interfaces; DripAccessEvaluator; domain events). No outer dependencies. |
| **Application/** | Use-case services (CourseService, EnrollmentService, ProgressService, QuizService, CertificateService, StudentExperienceService, etc.) depending on Domain + `Application/Contract` interfaces only. |
| **Infrastructure/** | Every WordPress adapter: `Wpdb*` repositories, migrations, WP auth/media/cache, admin UI, WooCommerce, email, setup. |
| **Http/Rest/** | Thin REST controllers under `mintlms/v1`. Parse request, call a service, shape response. No business logic. |
| **Api/V1/** | Frozen public PHP facades (Courses, Enrollment, Progress) + extension registries for third-party hooks. |
| **Frontend/** | Shortcodes, TemplateLoader, StudentAssetLoader. |

**Stack specifics:**

- Custom DB tables via `$wpdb` (never postmeta for LMS data); own auto-increment IDs (never WP post IDs)
- Nine tables: `mintlms_courses`, `sections`, `lessons`, `enrollments`, `progress`, `progress_summary`, `quizzes`, `quiz_questions`, `quiz_attempts`
- Four migrations (001 initial schema, 002 quizzes, 003 drip column, 004 certificate template option)
- REST API namespace `mintlms/v1`
- Alpine.js + Tailwind CSS (no React/Vue/jQuery); Sortable.js for drag-drop
- Composer PSR-4 autoload; `vendor/` committed (wp.org has no build step)
- PHPStan level 6 + WPCS both required — `composer check` must pass
- Requires **WordPress 6.2+** and **PHP 8.1+** (6.2 minimum because migrations/uninstall use `$wpdb->prepare()` `%i` for table identifiers)

**Naming is fixed everywhere:**

| Item | Value |
|------|-------|
| Namespace | `MintLMS\` |
| Text domain | `mint-lms` |
| Tables | `{prefix}mintlms_*` |
| REST namespace | `mintlms/v1` |
| CSS prefix | `mint-` |
| Root wrapper | `#mint-lms-root` |
| JS global | `window.MintLMS` |

Tailwind is configured with `prefix: mint-` and `important: '#mint-lms-root'`, preflight disabled, so styles do not bleed into the customer theme.

---

## 3. How the build actually went (read before assuming anything works)

Built almost entirely by Cursor agents (multiple parallel agents at points), with Adeel testing and pushing back repeatedly. Pattern that repeated: agents reported something as done/passing, Adeel tested in the browser, it was broken, agent re-investigated. **Nothing reported as "complete" should be trusted without opening the actual page or re-running checks yourself.**

### 3.1 v0.1: it was not actually an LMS

After the first big multi-agent push, an honest audit found solid architecture but not a usable product: only 3 visible admin menu items (Dashboard, Courses, Students), broken stubs, unwired shortcodes. This was later addressed through v0.2.0 and v1.0.0. Agent self-reports early in this project were not reliable.

### 3.2 UI/design workflow — Claude Design → Cursor handoff

Cursor's own UI output was rated poorly. The workflow that worked better: design screens in Claude Design, export a handoff package (tokens, per-screen specs), then have Cursor implement screen-by-screen. Tailwind scoping (`#mint-lms-root`, preflight off) is a hard requirement tested against third-party themes.

### 3.3 Feature gap vs LearnDash

Mint LMS still lacks several LearnDash Essentials baseline features (e.g. sub-lesson "topics" hierarchy — Mint LMS is Course → Section → Lesson only; rich lesson editor parity). Treat **v1.0.0** as a version label, not proof of feature parity.

### 3.4 Push to v1.0.0

Added drip scheduling, certificates, email notifications, quizzes, settings, reports, and WooCommerce enrollment. Version is 1.0.0 in `mint-lms.php` but this is **not QA-approved release status**.

### 3.5 WooCommerce — merged into core

- All five classes live in `src/Infrastructure/WooCommerce/`: `WooCommerceIntegration`, `ProductMeta`, `OrderHandler`, `EnrollmentBridge`, `SettingsPage`
- `Bootstrap::onPluginsLoaded()` calls `WooCommerceIntegration::register()` when WooCommerce is active
- Meta keys preserved: `_mintlms_course_id`, `_mintlms_wc_enrollments`, `_mintlms_wc_enrollment_processed`; hooks `mintlms_wc_*` preserved
- The separate `mint-lms-woocommerce/` plugin folder is **gone** from `wp-content/plugins/`
- **`readme.txt` still incorrectly tells users to install a separate "Mint LMS WooCommerce" addon** — update before wp.org submission

### 3.6 Duplicate-plugin bug (recurred 3+ times)

**Cause:** `mint-lms-release-check/` staging copy had identical `Plugin Name: Mint LMS` header.

**Final fix:** staging copy removed entirely. Plugin Check runs in-place against `mint-lms/` using `.distignore` exclusions via `dev/plugin-check.sh`. **Never put release-check staging copies under `wp-content/plugins/`.**

### 3.7 Dev tooling (inside this repo)

Dev-only files live in **`dev/`** inside the plugin repo (excluded from wp.org zip via `.distignore`):

```
dev/
├── plugin-check.sh
└── scripts/
    ├── verify-all.php
    ├── verify-rest-actions.php
    ├── seed-v1-demo.php
    └── smoke-v1.php
```

`phpunit.xml.dist` lives at repo root. `composer plugin-check` runs `dev/plugin-check.sh` with `--ignore-warnings` for CI; run `wp plugin check mint-lms` manually to see all warnings.

---

## 4. Current state — verified against codebase

### 4.1 Plugin Check

| Severity | Count |
|----------|-------|
| **ERROR** | **0** |
| **WARNING** | **3** (dev-only: `.gitignore`, `.distignore`, `.github/` — excluded from release zip) |

- No warnings on `Migration_003_Drip.php`, `uninstall.php`, or `ProductMeta.php`
- `composer check` passes (architecture tests, PHPStan 6, PHPCS, unit tests)
- `composer plugin-check` passes

**Previously flagged items — now resolved:**

- **ProductMeta.php** — `check_admin_referer()` / `check_ajax_referer()` before reading `$_POST`; course IDs sanitized with `absint()`
- **Migration_003_Drip.php** — table names validated via `Schema::validateTable()`; queries use `$wpdb->prepare()` with `%i`
- **uninstall.php** — `DROP TABLE` uses `$wpdb->prepare('DROP TABLE IF EXISTS %i', $table)` with validated table names

### 4.2 Admin UI today

- **Visible menu:** Dashboard, Courses, Students, Reports
- **Settings:** Mint LMS → Settings — pages, emails, certificate template, uninstall options
- **Hidden pages:** course builder, course edit, guided first course
- Quizzes, certificates, drip, emails exist in the product but not all have top-level menu items

### 4.3 Student-facing surface

**Shortcodes:**

- `[mint_lms_dashboard]`
- `[mint_lms_my_courses]`
- `[mint_lms_catalog]`
- `[mint_lms_course id="123"]`
- `[mint_lms_player course="123"]`
- `[mint_lms_certificate course="123"]`

Templates in `templates/student/`; theme overrides at `{theme}/mint-lms/{template}`. Built assets in `assets/dist/`.

### 4.4 Immediate next steps

1. Update `readme.txt` — remove separate WooCommerce addon language; align Requirements WP version (body still says 6.0+, header says 6.2)
2. Manual browser QA — teacher flow (create course → add lessons → publish) and student flow (enroll → complete lesson → progress/certificate)
3. Before wp.org submission — run Plugin Check on a release-shaped tree (or trust `.distignore`)

---

## 5. Pattern to watch for going forward

**Trust but verify, always.** Cursor agents repeatedly reported work as complete when it was not — including "0 errors" while warnings remained, and "production ready" while core flows were broken. Re-verify any "done" claim by opening the page or running the check yourself. Re-paste architecture rules (layer boundaries, naming) in long Cursor sessions to prevent drift.

Distinguish **ERROR vs WARNING** in Plugin Check reports — never say "zero issues" when warnings remain.

---

## 6. Repository and environment

| Item | Value |
|------|-------|
| GitLab repo | https://gitlab.com/elearning-evolve/mintlms (branch: `main`) |
| Plugin path | `wp-content/plugins/mint-lms/` |
| Dev tooling | `dev/` inside this repo (not a separate WP plugin) |
| Version | 1.0.0 |
| Requires | WordPress 6.2+, PHP 8.1+ |
| WooCommerce addon | Removed — functionality in core |
| Release-check staging | Should **NOT** exist under `plugins/` |

---

## 7. Key commands

```bash
cd wp-content/plugins/mint-lms

# Required gate after meaningful changes
composer check

# WP.org Plugin Check simulation (ignores warnings)
composer plugin-check

# Individual suites
composer stan
composer sniff
composer test:unit
composer test:integ

# Full Plugin Check including warnings
wp plugin check mint-lms --path=/path/to/wordpress

# Verify plugin list (should show ONE mint-lms entry)
wp plugin list | grep -i mint

# Dev verification scripts (WP-CLI)
wp eval-file dev/scripts/verify-all.php
wp eval-file dev/scripts/seed-v1-demo.php
```

When in doubt: read `tests/Architecture/LayerBoundaryTest.php`, run `composer check`, open the page in a browser.
