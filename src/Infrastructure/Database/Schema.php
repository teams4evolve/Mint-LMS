<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database;

final class Schema {

	public static function coursesTable( string $prefix ): string {
		return $prefix . 'mintlms_courses';
	}

	public static function sectionsTable( string $prefix ): string {
		return $prefix . 'mintlms_sections';
	}

	public static function lessonsTable( string $prefix ): string {
		return $prefix . 'mintlms_lessons';
	}

	public static function enrollmentsTable( string $prefix ): string {
		return $prefix . 'mintlms_enrollments';
	}

	public static function progressTable( string $prefix ): string {
		return $prefix . 'mintlms_progress';
	}

	public static function progressSummaryTable( string $prefix ): string {
		return $prefix . 'mintlms_progress_summary';
	}

	public static function quizzesTable( string $prefix ): string {
		return $prefix . 'mintlms_quizzes';
	}

	public static function quizQuestionsTable( string $prefix ): string {
		return $prefix . 'mintlms_quiz_questions';
	}

	public static function quizAttemptsTable( string $prefix ): string {
		return $prefix . 'mintlms_quiz_attempts';
	}

	/**
	 * @return list<string>
	 */
	public static function allTables( string $prefix ): array {
		return array(
			self::coursesTable( $prefix ),
			self::sectionsTable( $prefix ),
			self::lessonsTable( $prefix ),
			self::enrollmentsTable( $prefix ),
			self::progressTable( $prefix ),
			self::progressSummaryTable( $prefix ),
			self::quizzesTable( $prefix ),
			self::quizQuestionsTable( $prefix ),
			self::quizAttemptsTable( $prefix ),
		);
	}
}
