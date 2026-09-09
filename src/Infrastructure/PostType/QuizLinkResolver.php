<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves and repairs quiz ↔ lesson ↔ course postmeta links.
 *
 * Read paths must not write. Persist only via ensureForBuilder().
 */
final class QuizLinkResolver {

	/**
	 * Read-only links for admin list / API display.
	 * May derive courseId from the linked lesson without persisting.
	 *
	 * @return array{courseId: int, lessonId: int}
	 */
	public function readForList( int $quizId ): array {
		$courseId = (int) get_post_meta( $quizId, PostTypes::META_COURSE_ID, true );
		$lessonId = (int) get_post_meta( $quizId, PostTypes::META_LESSON_ID, true );

		if ( $lessonId > 0 && $courseId <= 0 ) {
			$courseId = (int) get_post_meta( $lessonId, PostTypes::META_COURSE_ID, true );
		}

		return array(
			'courseId' => $courseId,
			'lessonId' => $lessonId,
		);
	}

	/**
	 * Ensure quiz has usable course/lesson links for the builder (may write meta).
	 *
	 * @param callable(): array{course_id: int, lesson_id: int} $orphanFactory
	 * @return array{course_id: int, lesson_id: int}|null
	 */
	public function ensureForBuilder( int $quizId, callable $orphanFactory ): ?array {
		$post = get_post( $quizId );
		if ( ! $post instanceof \WP_Post || PostTypes::QUIZ !== $post->post_type ) {
			return null;
		}

		$courseId = (int) get_post_meta( $quizId, PostTypes::META_COURSE_ID, true );
		$lessonId = (int) get_post_meta( $quizId, PostTypes::META_LESSON_ID, true );

		if ( $lessonId > 0 ) {
			$lesson = get_post( $lessonId );
			if ( $lesson instanceof \WP_Post && PostTypes::LESSON === $lesson->post_type ) {
				if ( 'trash' === $lesson->post_status ) {
					wp_untrash_post( $lessonId );
				}
				if ( $courseId <= 0 ) {
					$courseId = (int) get_post_meta( $lessonId, PostTypes::META_COURSE_ID, true );
				}
			} else {
				$lessonId = 0;
			}
		}

		// Prefer linking to an existing host lesson. Do not invent a throwaway course
		// when the quiz is already on a standalone (library) lesson.
		if ( $lessonId > 0 && $courseId <= 0 ) {
			update_post_meta( $quizId, PostTypes::META_LESSON_ID, $lessonId );
			update_post_meta( $quizId, PostTypes::META_COURSE_ID, 0 );

			return array(
				'course_id' => 0,
				'lesson_id' => $lessonId,
			);
		}

		if ( $courseId <= 0 || $lessonId <= 0 ) {
			$created  = $orphanFactory();
			$courseId = (int) $created['course_id'];
			$lessonId = (int) $created['lesson_id'];
		}

		update_post_meta( $quizId, PostTypes::META_COURSE_ID, $courseId );
		update_post_meta( $quizId, PostTypes::META_LESSON_ID, $lessonId );

		return array(
			'course_id' => $courseId,
			'lesson_id' => $lessonId,
		);
	}
}
