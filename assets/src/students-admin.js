import { joinRestUrl, getAdminConfig } from './api.js';

async function resolveUserId(rawInput) {
  const input = String(rawInput || '').trim();
  if (!input) {
    return 0;
  }

  if (/^\d+$/.test(input)) {
    return parseInt(input, 10);
  }

  const config = getAdminConfig();
  const response = await fetch(
    joinRestUrl(config.restBase, `users/search?q=${encodeURIComponent(input)}`),
    {
      headers: { 'X-WP-Nonce': config.nonce },
    }
  );

  if (!response.ok) {
    return 0;
  }

  const payload = await response.json();
  const users = payload?.data?.users;
  if (!Array.isArray(users) || users.length === 0) {
    return 0;
  }

  const exact = users.find((user) => user.email?.toLowerCase() === input.toLowerCase());
  const match = exact || users[0];

  return match?.id ? parseInt(String(match.id), 10) : 0;
}

function exportStudentsCsv() {
  const rows = document.querySelectorAll('#mint-lms-root [data-mint-student-row]');
  if (!rows.length) {
    window.MintLMS?.toast?.error('No students to export.');
    return;
  }

  const lines = ['Name,Email,Progress,Joined'];
  rows.forEach((row) => {
    const name = row.dataset.mintStudentName || '';
    const email = row.dataset.mintStudentEmail || '';
    const progress = row.dataset.mintStudentProgress || '';
    const joined = row.dataset.mintStudentJoined || '';
    const escape = (value) => `"${String(value).replace(/"/g, '""')}"`;
    lines.push([name, email, progress, joined].map(escape).join(','));
  });

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'mint-lms-students.csv';
  link.click();
  URL.revokeObjectURL(url);
}

function initStudentsAdmin() {
  const root = document.getElementById('mint-lms-root');
  const config = getAdminConfig();

  if (!root || !config?.restBase) {
    return;
  }

  window.addEventListener('mint-export-students', exportStudentsCsv);

  root.addEventListener('click', async (event) => {
    const enrollBtn = event.target.closest('#mint-enroll-student');

    if (enrollBtn) {
      event.preventDefault();
      const courseId = enrollBtn.dataset.mintCourse;
      const emailInput = document.getElementById('mint-enroll-email');
      const rawValue = emailInput ? emailInput.value : '';

      enrollBtn.disabled = true;

      try {
        const userId = await resolveUserId(rawValue);
        if (!courseId || !userId) {
          window.MintLMS?.toast?.error('Enter a valid email address or user ID.');
          return;
        }

        const response = await fetch(joinRestUrl(config.restBase, `courses/${courseId}/enroll`), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': config.nonce,
          },
          body: JSON.stringify({ user_id: userId }),
        });

        if (response.ok) {
          window.location.reload();
        } else {
          const json = await response.json().catch(() => ({}));
          window.MintLMS?.toast?.error(json.error?.message || 'Enrollment failed.');
        }
      } finally {
        enrollBtn.disabled = false;
      }

      return;
    }

    const removeBtn = event.target.closest('[data-mint-unenroll]');

    if (removeBtn) {
      const enrollmentId = removeBtn.dataset.mintUnenroll;

      if (!enrollmentId) {
        return;
      }

      if (!window.confirm('Remove this student from the course?')) {
        return;
      }

      removeBtn.disabled = true;

      try {
        const response = await fetch(joinRestUrl(config.restBase, `enrollments/${enrollmentId}`), {
          method: 'DELETE',
          headers: { 'X-WP-Nonce': config.nonce },
        });

        if (response.ok || response.status === 204) {
          window.location.reload();
        } else {
          window.MintLMS?.toast?.error('Could not remove student.');
        }
      } finally {
        removeBtn.disabled = false;
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', initStudentsAdmin);

export { initStudentsAdmin, resolveUserId, exportStudentsCsv };
