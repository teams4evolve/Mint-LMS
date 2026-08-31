<?php
declare(strict_types=1);

namespace MintLMS;

use MintLMS\Application\Certificate\CertificateService;
use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\CourseStructureService;
use MintLMS\Application\Enrollment\EnrollmentService;
use MintLMS\Application\Lesson\LessonAccessService;
use MintLMS\Application\Lesson\LessonService;
use MintLMS\Application\Progress\ProgressService;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Application\Section\SectionService;
use MintLMS\Application\Student\StudentExperienceService;
use MintLMS\Frontend\ShortcodeRegistrar;
use MintLMS\Frontend\StudentAssetLoader;
use MintLMS\Frontend\TemplateLoader;
use MintLMS\Http\Rest\Controller\CourseController;
use MintLMS\Http\Rest\Controller\EnrollmentController;
use MintLMS\Http\Rest\Controller\LessonController;
use MintLMS\Http\Rest\Controller\OnboardingController;
use MintLMS\Http\Rest\Controller\ProgressController;
use MintLMS\Http\Rest\Controller\QuizController;
use MintLMS\Http\Rest\Controller\SectionController;
use MintLMS\Http\Rest\Controller\UserController;
use MintLMS\Http\Rest\RouteRegistrar;
use MintLMS\Infrastructure\Admin\AdminAjaxHandler;
use MintLMS\Infrastructure\Admin\AssetLoader;
use MintLMS\Infrastructure\Admin\CourseBuilderPage;
use MintLMS\Infrastructure\Admin\FirstRunRedirect;
use MintLMS\Infrastructure\Admin\MenuRegistrar;
use MintLMS\Infrastructure\Admin\SettingsPage;
use MintLMS\Infrastructure\Clock\SystemClock;
use MintLMS\Infrastructure\Certificate\CertificateDownloadHandler;
use MintLMS\Infrastructure\Certificate\WpCertificateTemplateProvider;
use MintLMS\Infrastructure\Certificate\WpCertificateUrlBuilder;
use MintLMS\Infrastructure\Database\Migration\Migration_001_InitialSchema;
use MintLMS\Infrastructure\Database\Migration\Migration_002_Quizzes;
use MintLMS\Infrastructure\Database\Migration\Migration_003_Drip;
use MintLMS\Infrastructure\Database\Migration\Migration_004_CertificateTemplate;
use MintLMS\Infrastructure\Database\Migration\MigrationRunner;
use MintLMS\Infrastructure\Database\Repository\WpdbCourseRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbAdminDashboardRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbEnrollmentRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbLessonRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbProgressRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbQuizRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbSectionRepository;
use MintLMS\Infrastructure\Notification\EmailNotificationRegistrar;
use MintLMS\Infrastructure\Setup\Activator;
use MintLMS\Infrastructure\Setup\PageSettings;
use MintLMS\Infrastructure\User\WpUserLookup;

final class Bootstrap {

	public static function init(): void {
		register_activation_hook( MINTLMS_FILE, array( self::class, 'activate' ) );
		register_deactivation_hook( MINTLMS_FILE, array( self::class, 'deactivate' ) );

		add_action( 'plugins_loaded', array( self::class, 'onPluginsLoaded' ) );
	}

	public static function activate(): void {
		self::runMigrations();
		( new Activator() )->activate();
		set_transient( FirstRunRedirect::ACTIVATION_REDIRECT_TRANSIENT, true, 30 );
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public static function onPluginsLoaded(): void {
		global $wpdb;

		load_plugin_textdomain(
			'mint-lms',
			false,
			dirname( plugin_basename( MINTLMS_FILE ) ) . '/languages'
		);

		self::runMigrations();

		Plugin::boot();

		( new MenuRegistrar() )->register();
		( new SettingsPage() )->register();
		( new CourseBuilderPage() )->register();
		( new FirstRunRedirect() )->register();
		( new AssetLoader() )->register();
		( new AdminAjaxHandler() )->register();
		( new StudentAssetLoader() )->register();

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		self::registerFrontend();
		self::registerRestApi();
		self::registerNotifications( $wpdb );
		self::registerCertificateHandler( $wpdb );
	}

	private static function registerNotifications( \wpdb $wpdb ): void {
		$clock   = new SystemClock();
		$service = self::createCertificateService( $wpdb, $clock );

		( new EmailNotificationRegistrar( new WpdbCourseRepository( $wpdb ), $service ) )->register();
	}

	private static function registerCertificateHandler( \wpdb $wpdb ): void {
		$clock   = new SystemClock();
		$service = self::createCertificateService( $wpdb, $clock );

		Plugin::registerCertificateService( $service );

		( new CertificateDownloadHandler( $service, new TemplateLoader() ) )->register();
	}

	private static function createCertificateService( \wpdb $wpdb, SystemClock $clock ): CertificateService {
		$userLookup = new WpUserLookup();

		return new CertificateService(
			new WpdbCourseRepository( $wpdb ),
			new WpdbProgressRepository( $wpdb ),
			$clock,
			new WpCertificateTemplateProvider(),
			new WpCertificateUrlBuilder(),
			$userLookup,
		);
	}

	private static function registerFrontend(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$courseRepo     = new WpdbCourseRepository( $wpdb );
		$sectionRepo    = new WpdbSectionRepository( $wpdb );
		$enrollmentRepo = new WpdbEnrollmentRepository( $wpdb );
		$progressRepo   = new WpdbProgressRepository( $wpdb );
		$quizRepo       = new WpdbQuizRepository( $wpdb );
		$lessonRepo     = new WpdbLessonRepository( $wpdb );
		$clock          = new SystemClock();
		$authorization  = Plugin::authorization();
		$userLookup     = new WpUserLookup();
		$certificateService = self::createCertificateService( $wpdb, $clock );

		Plugin::registerCertificateService( $certificateService );

		$enrollmentService = new EnrollmentService(
			$enrollmentRepo,
			$courseRepo,
			$authorization,
			$clock,
			Plugin::eventPublisher()
		);

		$lessonAccessService = new LessonAccessService(
			$lessonRepo,
			$enrollmentRepo,
			$progressRepo,
			$clock,
		);
		$quizService    = new QuizService( $quizRepo, $lessonRepo, $courseRepo, $authorization, $clock, $progressRepo, $userLookup, $lessonAccessService );

		$studentExperience = new StudentExperienceService(
			$courseRepo,
			$sectionRepo,
			$enrollmentRepo,
			$progressRepo,
			$clock,
			$quizService,
			$authorization,
		);

		$shortcodes = new ShortcodeRegistrar(
			$studentExperience,
			$enrollmentService,
			new TemplateLoader(),
			new PageSettings(),
			$certificateService,
		);

		$shortcodes->register();

		add_action( 'template_redirect', array( $shortcodes, 'handleEnrollRequest' ) );
	}

	private static function registerRestApi(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$authorization     = Plugin::authorization();
		$clock             = new SystemClock();
		$courseRepo        = new WpdbCourseRepository( $wpdb );
		$sectionRepo       = new WpdbSectionRepository( $wpdb );
		$lessonRepo        = new WpdbLessonRepository( $wpdb );
		$enrollmentRepo    = new WpdbEnrollmentRepository( $wpdb );
		$progressRepo      = new WpdbProgressRepository( $wpdb );
		$quizRepo          = new WpdbQuizRepository( $wpdb );
		$dashboardRepo     = new WpdbAdminDashboardRepository( $wpdb );
		$userLookup        = new WpUserLookup();
		$courseService     = new CourseService( $courseRepo, $authorization, $clock, $dashboardRepo );
		$sectionService    = new SectionService( $sectionRepo, $lessonRepo, $courseRepo, $authorization, $clock );
		$lessonAccessService = new LessonAccessService(
			$lessonRepo,
			$enrollmentRepo,
			$progressRepo,
			$clock,
		);
		$quizService       = new QuizService( $quizRepo, $lessonRepo, $courseRepo, $authorization, $clock, $progressRepo, $userLookup, $lessonAccessService );
		$progressService   = new ProgressService( $progressRepo, Plugin::eventPublisher(), $clock, $quizService, $lessonAccessService );
		$enrollmentService = new EnrollmentService(
			$enrollmentRepo,
			$courseRepo,
			$authorization,
			$clock,
			Plugin::eventPublisher()
		);
		$lessonService     = new LessonService(
			$lessonRepo,
			$sectionRepo,
			$courseRepo,
			$authorization,
			$clock,
			$progressService
		);
		$structureService  = new CourseStructureService( $courseRepo, $sectionRepo, $authorization );
		Plugin::registerCourseServices( $courseService, $structureService );
		Plugin::registerEnrollmentService( $enrollmentService );
		Plugin::registerProgressService( $progressService );
		$courseController     = new CourseController( $courseService, $structureService, $authorization );
		$sectionController    = new SectionController( $sectionService, $authorization );
		$lessonController     = new LessonController( $lessonService, $authorization );
		$enrollmentController = new EnrollmentController( $enrollmentService, $authorization );
		$progressController   = new ProgressController( $progressService, $authorization );
		$quizController       = new QuizController( $quizService, $authorization );
		$onboardingController = new OnboardingController( $authorization );
		$userController       = new UserController(
			new WpUserLookup(),
			$authorization,
		);
		$routeRegistrar       = new RouteRegistrar(
			$courseController,
			$sectionController,
			$lessonController,
			$enrollmentController,
			$progressController,
			$quizController,
			$onboardingController,
			$userController,
		);

		$routeRegistrar->register();
	}

	private static function runMigrations(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$runner = new MigrationRunner(
			$wpdb,
			array(
				new Migration_001_InitialSchema(),
				new Migration_002_Quizzes(),
				new Migration_003_Drip(),
				new Migration_004_CertificateTemplate(),
			)
		);

		$runner->run();
	}
}
