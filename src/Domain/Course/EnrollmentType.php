<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

enum EnrollmentType: string {

	case Open   = 'open';
	case Manual = 'manual';
	/** Closed to self-enroll; access via WooCommerce purchase (or staff enroll). */
	case Paid   = 'paid';
}
