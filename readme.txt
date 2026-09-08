=== Mint LMS ===
Contributors: mintlms
Tags: lms, learning, courses, elearning, education
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A clean, fast WordPress LMS for teachers and students.

== Description ==

Mint LMS helps you create and publish online courses directly in WordPress. Build a course curriculum with sections and lessons, manage enrollments, and let students track their progress — all without leaving your site.

= Features =

* Visual course builder with drag-and-drop sections and lessons
* Rich lesson editor (WordPress editor) for written content, plus video URLs and file attachments
* Course statuses: draft, live (published), and hidden (archived)
* Filterable courses list with search, status tabs, and per-course metrics
* Creator dashboard with student counts, completion rates, and recent activity
* Global reports page with enrollments, completions, and activity feed
* Guided first-run setup and step-by-step guided course wizard
* Open or manual enrollment per course
* Student dashboard, my courses, course overview, and lesson player shortcodes
* Automatic student page creation (dashboard, catalog, player, course overview)
* Progress tracking with lesson completion and course completion summaries
* Course preview from the builder using configured player page
* Student enrollment management in admin
* Instructor role with scoped capabilities
* REST API (`mintlms/v1`) for admin builder and student player
* Lesson quizzes (multiple choice and true/false) with pass requirements
* Completion certificates (printable HTML)
* Email notifications on enrollment and course completion
* Basic drip content (release lessons days after enrollment)
* Mint LMS → Settings for page mapping, defaults, and setup checklist

= Shortcodes =

* `[mint_lms_catalog]` — Public course catalog (course details via `?mintlms_course=ID`)
* `[mint_lms_dashboard]` — Student learning dashboard
* `[mint_lms_my_courses]` — Enrolled courses list with filters
* `[mint_lms_course id="123"]` — Course overview and enrollment
* `[mint_lms_player course="123"]` — Lesson player
* `[mint_lms_certificate course="123"]` — Download completion certificate (when eligible)

= WooCommerce (optional) =

Install the separate **Mint LMS WooCommerce** addon to sell courses via WooCommerce. Link a product to a course ID; customers are enrolled automatically when orders complete. Mint LMS core handles all LMS functionality; WooCommerce handles payments only.

= Requirements =

* WordPress 6.0 or later
* PHP 8.1 or later

== Installation ==

1. Upload the `mint-lms` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Mint LMS** in the admin menu and follow the guided setup to create your first course.
4. Mint LMS can automatically create student-facing pages (My Learning, Courses, Course Player, Course Overview) during setup or from **Mint LMS → Settings → Create student pages**.
5. Add the student shortcodes to any page if you prefer custom page layouts (for example, `[mint_lms_dashboard]` on a "My Learning" page).

== Frequently Asked Questions ==

= Do I need a separate LMS theme? =

No. Mint LMS works with any WordPress theme. Place the provided shortcodes on pages you create for the student experience, or use the auto-created pages from setup.

= Can students self-enroll? =

Yes. Set a course's enrollment type to **Open enrollment** and students can enroll themselves from the course overview page. Use **Manual enrollment** to restrict access to students you add in the admin.

= Are lesson videos supported? =

Yes. Add a video URL on each lesson. Mint LMS embeds supported oEmbed providers automatically and falls back to an HTML5 video player for direct file URLs.

= Does Mint LMS store course data in posts? =

No. Course, section, lesson, enrollment, and progress data are stored in dedicated database tables for predictable performance and clean queries.

= Is there a REST API? =

Yes. Authenticated REST endpoints under `mintlms/v1` power the admin builder, courses list, enrollments, progress, and onboarding.

== Screenshots ==

1. Creator dashboard with course metrics and recent activity
2. Course builder with sections, lessons, and rich content editor
3. Filterable courses list with live/draft/hidden tabs
4. Student lesson player with progress tracking

== Changelog ==

= 1.0.0 =
* First stable release with lesson quizzes in the player, completion certificates, email notifications, drip content, and global settings.
* Student page installer from Settings; certificate HTML template editor.
* Quiz submission requires enrollment; player context includes quiz state for mark-complete gating.
* Drip content enforced on progress and quiz REST endpoints.
* Rich HTML preserved for lesson and course descriptions.
* Draft course preview for course authors in the player.
* Instructor user lookup via Mint REST (no wp/v2/users dependency).
* Consolidated student frontend assets; i18n text domain and POT file.
* Optional Mint LMS WooCommerce addon for paid course sales.

= 0.2.0 =
* Courses list: status filter tabs (All/Live/Drafts/Hidden), title search, and enriched API metrics (lessons, students, completion rate).
* Course builder: preview links to configured player page with course ID; WordPress editor for lesson content.
* Creator dashboard: wired "See an example" to guided course flow; header search links to courses search.
* New **Reports** admin page with totals and recent activity.
* Page settings options, email toggles, and optional page cleanup on uninstall.
* WordPress.org packaging: `.distignore`, Plugin Check CI, readme updates.

= 0.1.1 =
* Maintenance and compliance improvements.

= 0.1.0 =
* Initial public release.
* Course builder with sections, lessons, attachments, and preview lessons.
* Student dashboard, my courses, course overview, and lesson player shortcodes.
* Enrollment management and progress tracking.
* Guided onboarding for first-time setup.
* REST API for courses, structure, enrollments, and progress.

== Upgrade Notice ==

= 1.0.0 =
Stable v1 with quizzes, certificates, drip, emails, and polished student experience.

= 0.2.0 =
Admin polish, reports page, enriched courses list, builder preview and rich editor, and WordPress.org readiness improvements.

= 0.1.0 =
Initial release of Mint LMS.
