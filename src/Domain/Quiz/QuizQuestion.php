<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

final readonly class QuizQuestion {

	public const TYPE_MCQ        = 'mcq';
	public const TYPE_TRUE_FALSE = 'true_false';

	public function __construct(
		public int $id,
		public int $quizId,
		public string $type,
		public string $prompt,
		/** @var list<string> */
		public array $options,
		public string $correctAnswer,
		public int $sortOrder,
	) {
	}
}
