<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\PostType;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Database\Schema;

/**
 * Resolves question ↔ quiz ↔ course/lesson links for admin list / builder entry.
 */
final class QuestionLinkResolver {

	/**
	 * Read-only links for A10 Questions list.
	 *
	 * @return array{quizId: int, courseId: int, lessonId: int}
	 */
	public function readForList( int $questionId ): array {
		$quizId = $this->findQuizId( $questionId );
		$courseId = 0;
		$lessonId = 0;

		if ( $quizId > 0 ) {
			$links     = ( new QuizLinkResolver() )->readForList( $quizId );
			$courseId  = $links['courseId'];
			$lessonId  = $links['lessonId'];
		}

		return array(
			'quizId'   => $quizId,
			'courseId' => $courseId,
			'lessonId' => $lessonId,
		);
	}

	public function findQuizId( int $questionId ): int {
		if ( $questionId <= 0 ) {
			return 0;
		}

		global $wpdb;
		if ( ! $wpdb instanceof \wpdb ) {
			return 0;
		}

		$junction = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$quizId = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT quiz_id FROM {$junction} WHERE question_id = %d ORDER BY id ASC LIMIT 1",
				$questionId
			)
		);
		// phpcs:enable

		return (int) $quizId;
	}
}
