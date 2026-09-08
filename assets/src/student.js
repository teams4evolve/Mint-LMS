import Alpine from 'alpinejs';
import { joinRestUrl } from './api.js';

document.addEventListener('alpine:init', () => {
  Alpine.data('mintCoursePlayer', (config) => ({
    courseId: config.courseId,
    lessonId: config.lessonId,
    progressPct: config.progressPct,
    isComplete: config.isComplete,
    completedIds: [...config.completedIds],
    isEnrolled: config.isEnrolled,
    quizRequired: config.quizRequired || false,
    hasPassedQuiz: config.hasPassedQuiz || false,
    sidebarOpen: false,
    marking: false,
    error: '',

    async markComplete() {
      if (!this.isEnrolled || this.isComplete || this.marking) {
        return;
      }

      if (this.quizRequired && !this.hasPassedQuiz) {
        this.error = mintLmsStudent.i18n.quizRequired;
        return;
      }

      this.marking = true;
      this.error = '';

      try {
        const response = await fetch(
          joinRestUrl(mintLmsStudent.restUrl, `lessons/${this.lessonId}/complete`),
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': mintLmsStudent.nonce,
            },
            body: JSON.stringify({ course_id: this.courseId }),
          }
        );

        const payload = await response.json();

        if (!response.ok || payload.success === false) {
          throw new Error(payload?.error?.message || mintLmsStudent.i18n.completeError);
        }

        this.isComplete = true;

        if (!this.completedIds.includes(this.lessonId)) {
          this.completedIds.push(this.lessonId);
        }

        if (typeof payload.data?.progress === 'number') {
          this.progressPct = payload.data.progress;
        }

        if (payload.data?.course_just_completed) {
          window.location.reload();
        }
      } catch (err) {
        this.error = err.message || mintLmsStudent.i18n.completeError;
      } finally {
        this.marking = false;
      }
    },
  }));

  Alpine.data('mintLessonQuiz', (config) => ({
    quizId: config.quizId,
    passPercent: config.passPercent,
    questions: config.questions || [],
    answers: {},
    hasPassed: config.hasPassed || false,
    submitted: false,
    submitting: false,
    scorePercent: 0,
    error: '',

    async submitQuiz() {
      if (this.submitting || this.hasPassed) return;

      this.submitting = true;
      this.error = '';

      try {
        const response = await fetch(
          joinRestUrl(mintLmsStudent.restUrl, `quizzes/${this.quizId}/attempt`),
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': mintLmsStudent.nonce,
            },
            body: JSON.stringify({ answers: this.answers }),
          }
        );

        const payload = await response.json();

        if (!response.ok || payload.success === false) {
          throw new Error(payload?.error?.message || mintLmsStudent.i18n.quizSubmitError);
        }

        this.submitted = true;
        this.scorePercent = payload.data?.scorePercent ?? 0;

        if (payload.data?.passed) {
          this.hasPassed = true;
          const player = Alpine.$data(document.querySelector('[x-data*="mintCoursePlayer"]'));
          if (player) {
            player.hasPassedQuiz = true;
          }
        }
      } catch (err) {
        this.error = err.message || mintLmsStudent.i18n.quizSubmitError;
      } finally {
        this.submitting = false;
      }
    },

    retry() {
      this.submitted = false;
      this.answers = {};
      this.error = '';
    },
  }));
});

window.Alpine = Alpine;
Alpine.start();
