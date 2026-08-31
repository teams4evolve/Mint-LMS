<?php
declare(strict_types=1);

namespace MintLMS\Application\Enrollment\Dto;

final readonly class StudentDto {

	public function __construct(
		public int $enrollmentId,
		public int $userId,
		public string $status,
		public string $enrolledAt,
		public float $progressPct,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'enrollmentId' => $this->enrollmentId,
			'userId'       => $this->userId,
			'status'       => $this->status,
			'enrolledAt'   => $this->enrolledAt,
			'progressPct'  => $this->progressPct,
		);
	}
}
