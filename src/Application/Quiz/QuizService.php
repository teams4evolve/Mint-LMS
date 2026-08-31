<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Lesson\LessonAccessService;
use MintLMS\Application\Quiz\Dto\CreateQuestionDto;
use MintLMS\Application\Quiz\Dto\CreateQuizDto;
use MintLMS\Application\Quiz\Dto\QuizAttemptDto;
use MintLMS\Application\Quiz\Dto\QuizDto;
use MintLMS\Application\Quiz\Dto\SubmitQuizAttemptDto;
use MintLMS\Application\Quiz\Dto\UpdateQuestionDto;
use MintLMS\Application\Quiz\Dto\UpdateQuizDto;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Quiz\Quiz;
use MintLMS\Domain\Quiz\QuizAttempt;
use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Domain\Quiz\QuizRepositoryInterface;
use MintLMS\Domain\Shared\Clock;

final class QuizService {

	public function __construct(
		private QuizRepositoryInterface $quizRepository,
		private LessonRepositoryInterface $lessonRepository,
		private CourseRepositoryInterface $courseRepository,
		private AuthorizationInterface $authorization,
		private Clock $clock,
		private ProgressRepositoryInterface $progressRepository,
		private UserLookupInterface $userLookup,
		private ?LessonAccessService $lessonAccessService = null,
	) {
	}

	public function getByLessonId( int $lessonId, int $userId, bool $forStudent = false ): ?QuizDto {
		$lesson = $this->findLessonOrFail( $lessonId );
		$course = $this->findCourseOrFail( $lesson->courseId );

		if ( $forStudent ) {
			$this->assertStudentQuizAccess( $userId, $lesson->courseId, $lesson->isPreview, $lesson->id );
		} elseif ( ! $this->authorization->canViewCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$quiz = $this->quizRepository->findByLessonId( $lessonId );

		if ( null === $quiz ) {
			return null;
		}

		$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id );

		return QuizDto::fromQuiz( $quiz, $questions, ! $forStudent );
	}

	public function get( int $quizId, int $userId, bool $forStudent = false ): QuizDto {
		$quiz = $this->findQuizOrFail( $quizId );

		if ( $forStudent ) {
			$lesson = $this->findLessonOrFail( $quiz->lessonId );
			$this->assertStudentQuizAccess( $userId, $quiz->courseId, $lesson->isPreview, $lesson->id );
		} else {
			$course = $this->findCourseOrFail( $quiz->courseId );

			if ( ! $this->authorization->canViewCourse( $userId, $course->authorId ) ) {
				throw new ForbiddenException();
			}
		}

		$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id );

		return QuizDto::fromQuiz( $quiz, $questions, ! $forStudent );
	}

	public function create( int $lessonId, CreateQuizDto $dto, int $userId ): QuizDto {
		$lesson = $this->findLessonOrFail( $lessonId );
		$course = $this->findCourseOrFail( $lesson->courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		if ( null !== $this->quizRepository->findByLessonId( $lessonId ) ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'lesson_id' => 'This lesson already has a quiz.' )
			);
		}

		$title = trim( $dto->title );

		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$passPercent = $this->normalizePassPercent( $dto->passPercent );

		$quiz = $this->quizRepository->saveQuiz(
			new Quiz(
				0,
				$lessonId,
				$lesson->courseId,
				$title,
				$passPercent,
				0,
			)
		);

		if ( null !== $dto->questions ) {
			foreach ( $dto->questions as $index => $questionData ) {
				$this->createQuestionFromArray( $quiz->id, $questionData, $index );
			}
		}

		return $this->get( $quiz->id, $userId );
	}

	public function update( int $quizId, UpdateQuizDto $dto, int $userId ): QuizDto {
		$quiz   = $this->findQuizOrFail( $quizId );
		$course = $this->findCourseOrFail( $quiz->courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$title       = null !== $dto->title ? trim( $dto->title ) : $quiz->title;
		$passPercent = null !== $dto->passPercent ? $this->normalizePassPercent( $dto->passPercent ) : $quiz->passPercent;

		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$updated = new Quiz(
			$quiz->id,
			$quiz->lessonId,
			$quiz->courseId,
			$title,
			$passPercent,
			$quiz->sortOrder,
		);

		$this->quizRepository->saveQuiz( $updated );

		return $this->get( $quizId, $userId );
	}

	public function delete( int $quizId, int $userId ): void {
		$quiz   = $this->findQuizOrFail( $quizId );
		$course = $this->findCourseOrFail( $quiz->courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$this->quizRepository->deleteQuiz( $quizId );
	}

	public function addQuestion( int $quizId, CreateQuestionDto $dto, int $userId ): QuizDto {
		$quiz   = $this->findQuizOrFail( $quizId );
		$course = $this->findCourseOrFail( $quiz->courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$this->createQuestion( $quizId, $dto );

		return $this->get( $quizId, $userId );
	}

	public function updateQuestion( int $quizId, int $questionId, UpdateQuestionDto $dto, int $userId ): QuizDto {
		$quiz     = $this->findQuizOrFail( $quizId );
		$course   = $this->findCourseOrFail( $quiz->courseId );
		$question = $this->findQuestionOrFail( $questionId );

		if ( $question->quizId !== $quizId ) {
			throw new NotFoundException( 'Question not found.' );
		}

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$type          = null !== $dto->type ? $dto->type : $question->type;
		$prompt        = null !== $dto->prompt ? trim( $dto->prompt ) : $question->prompt;
		$options       = null !== $dto->options ? $dto->options : $question->options;
		$correctAnswer = null !== $dto->correctAnswer ? $dto->correctAnswer : $question->correctAnswer;

		$this->assertValidQuestion( $type, $prompt, $options, $correctAnswer );

		$updated = new QuizQuestion(
			$question->id,
			$quizId,
			$type,
			$prompt,
			$options,
			$correctAnswer,
			$question->sortOrder,
		);

		$this->quizRepository->saveQuestion( $updated );

		return $this->get( $quizId, $userId );
	}

	public function deleteQuestion( int $quizId, int $questionId, int $userId ): QuizDto {
		$quiz     = $this->findQuizOrFail( $quizId );
		$course   = $this->findCourseOrFail( $quiz->courseId );
		$question = $this->findQuestionOrFail( $questionId );

		if ( $question->quizId !== $quizId ) {
			throw new NotFoundException( 'Question not found.' );
		}

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$this->quizRepository->deleteQuestion( $questionId );

		return $this->get( $quizId, $userId );
	}

	public function submitAttempt( int $quizId, SubmitQuizAttemptDto $dto, int $userId ): QuizAttemptDto {
		if ( $userId <= 0 ) {
			throw new ForbiddenException( 'You must be logged in to submit a quiz.' );
		}

		$quiz      = $this->findQuizOrFail( $quizId );
		$questions = $this->quizRepository->findQuestionsByQuizId( $quizId );
		$lesson    = $this->findLessonOrFail( $quiz->lessonId );

		if ( ! $lesson->isPreview && ! $this->progressRepository->isUserEnrolled( $userId, $quiz->courseId ) ) {
			throw new ForbiddenException( 'You must be enrolled to submit this quiz.' );
		}

		if ( null !== $this->lessonAccessService ) {
			$this->lessonAccessService->assertCanAccessLesson( $userId, $lesson->id );
		}

		if ( array() === $questions ) {
			throw new ValidationException( 'Validation failed.', array( 'quiz' => 'Quiz has no questions.' ) );
		}

		$score = $this->gradeAttempt( $questions, $dto->answers );
		$passed = $score >= $quiz->passPercent;

		$attempt = $this->quizRepository->saveAttempt(
			new QuizAttempt(
				0,
				$userId,
				$quizId,
				$score,
				$passed,
				$dto->answers,
				$this->clock->now(),
			)
		);

		return QuizAttemptDto::fromAttempt( $attempt );
	}

	/**
	 * @return list<QuizAttemptDto>
	 */
	public function getAttempts( int $quizId, int $userId ): array {
		$quiz   = $this->findQuizOrFail( $quizId );
		$course = $this->findCourseOrFail( $quiz->courseId );

		if ( ! $this->authorization->canViewCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$attempts = $this->quizRepository->findAttemptsByQuizId( $quizId );
		$dtos     = array();

		foreach ( $attempts as $attempt ) {
			$name   = $this->userLookup->getDisplayName( $attempt->userId );
			$dtos[] = QuizAttemptDto::fromAttempt( $attempt, $name );
		}

		return $dtos;
	}

	public function hasPassed( int $userId, int $quizId ): bool {
		if ( $userId <= 0 ) {
			return false;
		}

		return $this->quizRepository->hasPassed( $userId, $quizId );
	}

	public function lessonRequiresQuizPass( int $lessonId ): bool {
		$quiz = $this->quizRepository->findByLessonId( $lessonId );

		if ( null === $quiz ) {
			return false;
		}

		$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id );

		return array() !== $questions;
	}

	public function hasPassedLessonQuiz( int $userId, int $lessonId ): bool {
		$quiz = $this->quizRepository->findByLessonId( $lessonId );

		if ( null === $quiz ) {
			return true;
		}

		$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id );

		if ( array() === $questions ) {
			return true;
		}

		return $this->hasPassed( $userId, $quiz->id );
	}

	public function onLessonDeleted( int $lessonId ): void {
		$this->quizRepository->deleteByLessonId( $lessonId );
	}

	/**
	 * @param list<QuizQuestion> $questions
	 * @param array<int, string> $answers
	 */
	public function gradeAttempt( array $questions, array $answers ): float {
		if ( array() === $questions ) {
			return 0.0;
		}

		$correct = 0;

		foreach ( $questions as $question ) {
			$submitted = $answers[ $question->id ] ?? '';

			if ( $this->isAnswerCorrect( $question, (string) $submitted ) ) {
				++$correct;
			}
		}

		return round( ( $correct / count( $questions ) ) * 100, 2 );
	}

	public function isAnswerCorrect( QuizQuestion $question, string $submitted ): bool {
		if ( QuizQuestion::TYPE_TRUE_FALSE === $question->type ) {
			return strtolower( trim( $submitted ) ) === strtolower( trim( $question->correctAnswer ) );
		}

		return trim( $submitted ) === trim( $question->correctAnswer );
	}

	private function createQuestion( int $quizId, CreateQuestionDto $dto ): QuizQuestion {
		$prompt = trim( $dto->prompt );
		$this->assertValidQuestion( $dto->type, $prompt, $dto->options, $dto->correctAnswer );

		return $this->quizRepository->saveQuestion(
			new QuizQuestion(
				0,
				$quizId,
				$dto->type,
				$prompt,
				$dto->options,
				$dto->correctAnswer,
				$this->quizRepository->nextQuestionSortOrder( $quizId ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function createQuestionFromArray( int $quizId, array $data, int $sortOrder ): void {
		$type = (string) ( $data['type'] ?? QuizQuestion::TYPE_MCQ );
		$prompt = (string) ( $data['prompt'] ?? '' );
		$options = isset( $data['options'] ) && is_array( $data['options'] )
			? array_values( array_map( 'strval', $data['options'] ) )
			: array();
		$correctAnswer = (string) ( $data['correct_answer'] ?? $data['correctAnswer'] ?? '' );

		$this->assertValidQuestion( $type, $prompt, $options, $correctAnswer );

		$this->quizRepository->saveQuestion(
			new QuizQuestion(
				0,
				$quizId,
				$type,
				trim( $prompt ),
				$options,
				$correctAnswer,
				$sortOrder,
			)
		);
	}

	/**
	 * @param list<string> $options
	 */
	private function assertValidQuestion( string $type, string $prompt, array $options, string $correctAnswer ): void {
		if ( '' === trim( $prompt ) ) {
			throw new ValidationException( 'Validation failed.', array( 'prompt' => 'Prompt is required.' ) );
		}

		if ( ! in_array( $type, array( QuizQuestion::TYPE_MCQ, QuizQuestion::TYPE_TRUE_FALSE ), true ) ) {
			throw new ValidationException( 'Validation failed.', array( 'type' => 'Invalid question type.' ) );
		}

		if ( QuizQuestion::TYPE_TRUE_FALSE === $type ) {
			$normalized = strtolower( trim( $correctAnswer ) );

			if ( ! in_array( $normalized, array( 'true', 'false' ), true ) ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'correct_answer' => 'Correct answer must be true or false.' )
				);
			}

			return;
		}

		$optionCount = count( $options );

		if ( $optionCount < 2 || $optionCount > 4 ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'options' => 'MCQ must have between 2 and 4 options.' )
			);
		}

		foreach ( $options as $option ) {
			if ( '' === trim( $option ) ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'options' => 'Options cannot be empty.' )
				);
			}
		}

		if ( ! in_array( $correctAnswer, $options, true ) ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'correct_answer' => 'Correct answer must match one of the options.' )
			);
		}
	}

	private function normalizePassPercent( int $passPercent ): int {
		return max( 0, min( 100, $passPercent ) );
	}

	private function findQuizOrFail( int $id ): Quiz {
		$quiz = $this->quizRepository->findById( $id );

		if ( null === $quiz ) {
			throw new NotFoundException( 'Quiz not found.' );
		}

		return $quiz;
	}

	private function findQuestionOrFail( int $id ): QuizQuestion {
		$question = $this->quizRepository->findQuestionById( $id );

		if ( null === $question ) {
			throw new NotFoundException( 'Question not found.' );
		}

		return $question;
	}

	private function findLessonOrFail( int $id ): \MintLMS\Domain\Lesson\Lesson {
		$lesson = $this->lessonRepository->findById( $id );

		if ( null === $lesson ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		return $lesson;
	}

	private function findCourseOrFail( int $id ): \MintLMS\Domain\Course\Course {
		$course = $this->courseRepository->findById( $id );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		return $course;
	}

	private function assertStudentQuizAccess( int $userId, int $courseId, bool $isPreview, int $lessonId ): void {
		if ( $userId <= 0 ) {
			throw new ForbiddenException( 'You must be logged in to access this quiz.' );
		}

		if ( ! $isPreview && ! $this->progressRepository->isUserEnrolled( $userId, $courseId ) ) {
			throw new ForbiddenException( 'You must be enrolled to access this quiz.' );
		}

		if ( null !== $this->lessonAccessService ) {
			$this->lessonAccessService->assertCanAccessLesson( $userId, $lessonId );
		}
	}
}
