<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

/**
 * Per-course creator options (Course Settings toggles).
 */
final readonly class CourseSettings {

	public function __construct(
		public bool $emailOnPublish = true,
		public bool $studentComplete = true,
		public bool $certificate = false,
	) {
	}

	public static function defaults(): self {
		return new self();
	}

	/**
	 * @param array<string, mixed>|null $data
	 */
	public static function fromArray( ?array $data ): self {
		if ( null === $data ) {
			return self::defaults();
		}

		return new self(
			array_key_exists( 'emailOnPublish', $data ) ? (bool) $data['emailOnPublish'] : true,
			array_key_exists( 'studentComplete', $data ) ? (bool) $data['studentComplete'] : true,
			array_key_exists( 'certificate', $data ) ? (bool) $data['certificate'] : false,
		);
	}

	/**
	 * @return array{emailOnPublish: bool, studentComplete: bool, certificate: bool}
	 */
	public function toArray(): array {
		return array(
			'emailOnPublish'  => $this->emailOnPublish,
			'studentComplete' => $this->studentComplete,
			'certificate'     => $this->certificate,
		);
	}

	public function with(
		?bool $emailOnPublish = null,
		?bool $studentComplete = null,
		?bool $certificate = null,
	): self {
		return new self(
			null !== $emailOnPublish ? $emailOnPublish : $this->emailOnPublish,
			null !== $studentComplete ? $studentComplete : $this->studentComplete,
			null !== $certificate ? $certificate : $this->certificate,
		);
	}
}
