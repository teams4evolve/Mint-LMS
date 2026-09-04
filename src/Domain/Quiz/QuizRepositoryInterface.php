<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

interface QuizRepositoryInterface {

	public function findById( int $id ): ?Quiz;

	public function findByLessonId( int $lessonId ): ?Quiz;

	/**
	 * @return list<QuizQuestion>
	 */
	public function findQuestionsByQuizId( int $quizId ): array;

	public function findQuestionById( int $questionId ): ?QuizQuestion;

	public function saveQuiz( Quiz $quiz ): Quiz;

	public function saveQuestion( QuizQuestion $question ): QuizQuestion;

	public function deleteQuiz( int $id ): bool;

	public function deleteQuestion( int $id ): bool;

	public function deleteQuestionsByQuizId( int $quizId ): void;

	public function deleteByLessonId( int $lessonId ): void;

	public function nextQuestionSortOrder( int $quizId ): int;

	public function saveAttempt( QuizAttempt $attempt ): QuizAttempt;

	/**
	 * @return list<QuizAttempt>
	 */
	public function findAttemptsByQuizId( int $quizId ): array;

	public function findBestPassedAttempt( int $userId, int $quizId ): ?QuizAttempt;

	public function hasPassed( int $userId, int $quizId ): bool;
}
