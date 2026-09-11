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

	public function getByLessonId( int $lessonId, int $userId, bool $forStudent = false, bool $editorReview = false ): ?QuizDto {
		$lesson = $this->findLessonOrFail( $lessonId );

		if ( $forStudent ) {
			if ( $lesson->courseId <= 0 ) {
				throw new NotFoundException( 'Quiz not found.' );
			}
			$course = $this->findCourseOrFail( $lesson->courseId );
			if ( $editorReview || $this->canEditorReviewCourse( $userId, $course->authorId ) ) {
				$editorReview = true;
			} else {
				$this->assertStudentQuizAccess( $userId, $lesson->courseId, $lesson->isPreview, $lesson->id );
			}
		} else {
			$this->assertCanManageLessonContent( $userId, $lesson );
		}

		$publishedOnly = $forStudent && ! $editorReview;
		$quiz          = $this->quizRepository->findByLessonId( $lessonId, $publishedOnly );

		if ( null === $quiz ) {
			return null;
		}

		$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id, $publishedOnly );

		return QuizDto::fromQuiz( $quiz, $questions, ! $forStudent );
	}

	public function get( int $quizId, int $userId, bool $forStudent = false ): QuizDto {
		$quiz         = $this->findQuizOrFail( $quizId );
		$editorReview = false;

		if ( $forStudent ) {
			if ( $quiz->courseId <= 0 ) {
				throw new NotFoundException( 'Quiz not found.' );
			}
			$lesson       = $this->findLessonOrFail( $quiz->lessonId );
			$course       = $this->findCourseOrFail( $quiz->courseId );
			$editorReview = $this->canEditorReviewCourse( $userId, $course->authorId );
			if ( ! $editorReview ) {
				$this->assertStudentQuizAccess( $userId, $quiz->courseId, $lesson->isPreview, $lesson->id );
			}
		} else {
			$lesson = $this->findLessonOrFail( $quiz->lessonId );
			$this->assertCanManageLessonContent( $userId, $lesson );
		}

		$publishedOnly = $forStudent && ! $editorReview;
		$questions     = $this->quizRepository->findQuestionsByQuizId( $quiz->id, $publishedOnly );

		return QuizDto::fromQuiz( $quiz, $questions, ! $forStudent );
	}

	public function create( int $lessonId, CreateQuizDto $dto, int $userId ): QuizDto {
		$lesson = $this->findLessonOrFail( $lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

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
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

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

	/**
	 * Move a quiz onto an existing lesson (first attach or change).
	 * Removes only disposable empty host lessons created for Add New Quiz.
	 */
	public function attachToLesson( int $quizId, int $targetLessonId, int $userId ): QuizDto {
		$quiz          = $this->findQuizOrFail( $quizId );
		$currentLesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $currentLesson );

		if ( $quiz->lessonId === $targetLessonId ) {
			return $this->get( $quizId, $userId );
		}

		$target = $this->findLessonOrFail( $targetLessonId );
		$this->assertCanManageLessonContent( $userId, $target );

		$existing = $this->quizRepository->findByLessonId( $targetLessonId );
		if ( null !== $existing && $existing->id !== $quizId ) {
			throw new ValidationException(
				'This lesson already has a quiz.',
				array( 'lesson_id' => 'This lesson already has a quiz.' )
			);
		}

		$previousLessonId = $quiz->lessonId;

		$this->quizRepository->saveQuiz(
			new Quiz(
				$quiz->id,
				$targetLessonId,
				$target->courseId,
				$quiz->title,
				$quiz->passPercent,
				$quiz->sortOrder,
			)
		);

		if ( $previousLessonId > 0 && $previousLessonId !== $targetLessonId && $this->isDisposableHostLesson( $currentLesson ) ) {
			$this->lessonRepository->delete( $previousLessonId );
		}

		return $this->get( $quizId, $userId );
	}

	/**
	 * Detach quiz from its lesson — places it on a fresh standalone host lesson.
	 */
	public function detachFromLesson( int $quizId, int $userId ): QuizDto {
		$quiz          = $this->findQuizOrFail( $quizId );
		$currentLesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $currentLesson );

		if ( $currentLesson->courseId <= 0 && $this->isDisposableHostLesson( $currentLesson ) ) {
			return $this->get( $quizId, $userId );
		}

		$host = $this->createStandaloneHostLesson();

		$this->quizRepository->saveQuiz(
			new Quiz(
				$quiz->id,
				$host->id,
				0,
				$quiz->title,
				$quiz->passPercent,
				$quiz->sortOrder,
			)
		);

		return $this->get( $quizId, $userId );
	}

	/**
	 * Move a question onto an existing quiz (first attach or change).
	 */
	public function attachQuestionToQuiz( int $questionId, int $targetQuizId, int $userId ): QuizDto {
		$question      = $this->findQuestionOrFail( $questionId );
		$current       = $this->findQuizOrFail( $question->quizId );
		$currentLesson = $this->findLessonOrFail( $current->lessonId );
		$this->assertCanManageLessonContent( $userId, $currentLesson );

		if ( $question->quizId === $targetQuizId ) {
			return $this->get( $targetQuizId, $userId );
		}

		$target       = $this->findQuizOrFail( $targetQuizId );
		$targetLesson = $this->findLessonOrFail( $target->lessonId );
		$this->assertCanManageLessonContent( $userId, $targetLesson );

		$previousQuizId   = $question->quizId;
		$previousLessonId = $current->lessonId;
		$sortOrder        = $this->quizRepository->nextQuestionSortOrder( $targetQuizId );

		$this->quizRepository->saveQuestion(
			new QuizQuestion(
				$question->id,
				$targetQuizId,
				$question->type,
				$question->prompt,
				$question->options,
				$question->correctAnswer,
				$sortOrder,
			)
		);

		$remaining = $this->quizRepository->findQuestionsByQuizId( $previousQuizId );
		if ( array() === $remaining && $this->isDisposableHostLesson( $currentLesson ) ) {
			$this->quizRepository->deleteQuiz( $previousQuizId );
			if ( $previousLessonId > 0 ) {
				$this->lessonRepository->delete( $previousLessonId );
			}
		}

		return $this->get( $targetQuizId, $userId );
	}

	/**
	 * Detach question from its quiz — places it on a fresh standalone host quiz.
	 */
	public function detachQuestionFromQuiz( int $questionId, int $userId ): QuizDto {
		$question      = $this->findQuestionOrFail( $questionId );
		$current       = $this->findQuizOrFail( $question->quizId );
		$currentLesson = $this->findLessonOrFail( $current->lessonId );
		$this->assertCanManageLessonContent( $userId, $currentLesson );

		if ( $currentLesson->courseId <= 0 && $this->isDisposableHostLesson( $currentLesson ) ) {
			$others = array_filter(
				$this->quizRepository->findQuestionsByQuizId( $current->id ),
				static fn( QuizQuestion $q ): bool => $q->id !== $questionId
			);
			if ( array() === $others ) {
				return $this->get( $current->id, $userId );
			}
		}

		$hostLesson = $this->createStandaloneHostLesson();
		$hostQuiz   = $this->quizRepository->saveQuiz(
			new Quiz(
				0,
				$hostLesson->id,
				0,
				__( 'New Quiz', 'mint-lms' ),
				80,
				0,
			)
		);

		$this->quizRepository->saveQuestion(
			new QuizQuestion(
				$question->id,
				$hostQuiz->id,
				$question->type,
				$question->prompt,
				$question->options,
				$question->correctAnswer,
				0,
			)
		);

		return $this->get( $hostQuiz->id, $userId );
	}

	public function delete( int $quizId, int $userId ): void {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$this->quizRepository->deleteQuiz( $quizId );
	}

	public function addQuestion( int $quizId, CreateQuestionDto $dto, int $userId ): QuizDto {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$this->createQuestion( $quizId, $dto );

		return $this->get( $quizId, $userId );
	}

	public function updateQuestion( int $quizId, int $questionId, UpdateQuestionDto $dto, int $userId ): QuizDto {
		$quiz     = $this->findQuizOrFail( $quizId );
		$lesson   = $this->findLessonOrFail( $quiz->lessonId );
		$question = $this->findQuestionOrFail( $questionId );

		if ( $question->quizId !== $quizId ) {
			throw new NotFoundException( 'Question not found.' );
		}

		$this->assertCanManageLessonContent( $userId, $lesson );

		$type          = null !== $dto->type ? $dto->type : $question->type;
		$prompt        = null !== $dto->prompt ? trim( $dto->prompt ) : $question->prompt;
		$options       = null !== $dto->options ? $dto->options : $question->options;
		$correctAnswer = null !== $dto->correctAnswer ? $dto->correctAnswer : $question->correctAnswer;

		if ( QuizQuestion::TYPE_ESSAY === $type ) {
			$options       = array();
			$correctAnswer = '';
		} else {
			$correctAnswer = QuestionAnswerCodec::normalizeForType( $type, $correctAnswer, $options );
		}

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
		$lesson   = $this->findLessonOrFail( $quiz->lessonId );
		$question = $this->findQuestionOrFail( $questionId );

		if ( $question->quizId !== $quizId ) {
			throw new NotFoundException( 'Question not found.' );
		}

		$this->assertCanManageLessonContent( $userId, $lesson );

		$this->quizRepository->deleteQuestion( $questionId );

		return $this->get( $quizId, $userId );
	}

	public function submitAttempt( int $quizId, SubmitQuizAttemptDto $dto, int $userId ): QuizAttemptDto {
		if ( $userId <= 0 ) {
			throw new ForbiddenException( 'You must be logged in to submit a quiz.' );
		}

		$quiz      = $this->findQuizOrFail( $quizId );
		$course    = $this->findCourseOrFail( $quiz->courseId );
		$questions = $this->quizRepository->findQuestionsByQuizId( $quizId );
		$lesson    = $this->findLessonOrFail( $quiz->lessonId );
		$isEditor  = $this->canEditorReviewCourse( $userId, $course->authorId );

		if ( ! $isEditor && ! $lesson->isPreview && ! $this->progressRepository->isUserEnrolled( $userId, $quiz->courseId ) ) {
			throw new ForbiddenException( 'You must be enrolled to submit this quiz.' );
		}

		if ( ! $isEditor && null !== $this->lessonAccessService ) {
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
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

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


	public function syncCourseIdForLesson( int $lessonId, int $courseId ): void {
		$quiz = $this->quizRepository->findByLessonId( $lessonId );
		if ( null === $quiz ) {
			return;
		}

		$this->quizRepository->saveQuiz(
			new Quiz(
				$quiz->id,
				$quiz->lessonId,
				$courseId,
				$quiz->title,
				$quiz->passPercent,
				$quiz->sortOrder,
			)
		);
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

		$gradable = array_values(
			array_filter(
				$questions,
				static fn( QuizQuestion $question ): bool => $question->isAutoGradable()
			)
		);

		// Essay-only quizzes have no auto-gradable items; treat as complete for progression.
		if ( array() === $gradable ) {
			return 100.0;
		}

		$correct = 0;

		foreach ( $gradable as $question ) {
			$submitted = $answers[ $question->id ] ?? '';

			if ( is_array( $submitted ) ) {
				$submitted = QuestionAnswerCodec::encodeMulti( array_map( 'strval', $submitted ) );
			}

			if ( $this->isAnswerCorrect( $question, (string) $submitted ) ) {
				++$correct;
			}
		}

		return round( ( $correct / count( $gradable ) ) * 100, 2 );
	}

	public function isAnswerCorrect( QuizQuestion $question, string $submitted ): bool {
		if ( QuizQuestion::TYPE_ESSAY === $question->type ) {
			return false;
		}

		if ( QuizQuestion::TYPE_TRUE_FALSE === $question->type ) {
			return strtolower( trim( $submitted ) ) === strtolower( trim( $question->correctAnswer ) );
		}

		if ( QuizQuestion::TYPE_MCQ_MULTI === $question->type ) {
			$expected = QuestionAnswerCodec::decodeMulti( $question->correctAnswer );
			$actual   = QuestionAnswerCodec::decodeMulti( $submitted );

			return $expected === $actual && array() !== $expected;
		}

		return trim( $submitted ) === trim( $question->correctAnswer );
	}

	private function createQuestion( int $quizId, CreateQuestionDto $dto ): QuizQuestion {
		$prompt  = trim( $dto->prompt );
		$type    = $dto->type;
		$options = QuizQuestion::TYPE_ESSAY === $type ? array() : $dto->options;
		$correct = QuestionAnswerCodec::normalizeForType( $type, $dto->correctAnswer, $options );

		$this->assertValidQuestion( $type, $prompt, $options, $correct );

		return $this->quizRepository->saveQuestion(
			new QuizQuestion(
				0,
				$quizId,
				$type,
				$prompt,
				$options,
				$correct,
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
		$correctAnswer = QuestionAnswerCodec::normalizeForType(
			$type,
			$data['correct_answer'] ?? $data['correctAnswer'] ?? '',
			$options
		);

		if ( QuizQuestion::TYPE_ESSAY === $type ) {
			$options = array();
		}

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

		if ( ! in_array( $type, QuizQuestion::types(), true ) ) {
			throw new ValidationException( 'Validation failed.', array( 'type' => 'Invalid question type.' ) );
		}

		if ( QuizQuestion::TYPE_ESSAY === $type ) {
			return;
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

		if ( $optionCount < 2 || $optionCount > 6 ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'options' => 'MCQ must have between 2 and 6 options.' )
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

		if ( QuizQuestion::TYPE_MCQ_MULTI === $type ) {
			$answers = QuestionAnswerCodec::decodeMulti( $correctAnswer );

			if ( count( $answers ) < 1 ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'correct_answer' => 'Select at least one correct answer.' )
				);
			}

			foreach ( $answers as $answer ) {
				if ( ! in_array( $answer, $options, true ) ) {
					throw new ValidationException(
						'Validation failed.',
						array( 'correct_answer' => 'Each correct answer must match one of the options.' )
					);
				}
			}

			return;
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

	private function createStandaloneHostLesson(): \MintLMS\Domain\Lesson\Lesson {
		$now  = $this->clock->now();
		$slug = 'new-lesson-' . bin2hex( random_bytes( 2 ) );

		return $this->lessonRepository->save(
			new \MintLMS\Domain\Lesson\Lesson(
				0,
				0,
				0,
				__( 'New Lesson', 'mint-lms' ),
				$slug,
				'',
				null,
				null,
				false,
				null,
				0,
				$now,
				$now,
			)
		);
	}

	private function isDisposableHostLesson( \MintLMS\Domain\Lesson\Lesson $lesson ): bool {
		if ( $lesson->courseId > 0 ) {
			return false;
		}

		if ( '' !== trim( $lesson->content ) ) {
			return false;
		}

		if ( null !== $lesson->videoUrl && '' !== trim( (string) $lesson->videoUrl ) ) {
			return false;
		}

		if ( null !== $lesson->attachmentId && $lesson->attachmentId > 0 ) {
			return false;
		}

		if ( null !== $lesson->featuredImageId && $lesson->featuredImageId > 0 ) {
			return false;
		}

		return true;
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

	private function assertCanManageLessonContent( int $userId, \MintLMS\Domain\Lesson\Lesson $lesson ): void {
		if ( $lesson->courseId > 0 ) {
			$course = $this->findCourseOrFail( $lesson->courseId );
			if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
				throw new ForbiddenException();
			}

			return;
		}

		if ( ! $this->authorization->canCreateCourse( $userId ) ) {
			throw new ForbiddenException();
		}
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

	private function canEditorReviewCourse( int $userId, int $authorId ): bool {
		return $userId > 0 && $this->authorization->canEditCourse( $userId, $authorId );
	}
}
