<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

enum EnrollmentType: string {

	case Open   = 'open';
	case Manual = 'manual';
}
