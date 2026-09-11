import { getAdminConfig, joinRestUrl, mintApi } from './api.js';

export { getAdminConfig, mintApi };

function debounce(fn, delay) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

function courseBuilder(courseId, opts = {}) {
  const config = getAdminConfig();
  let sectionSortable = null;
  const lessonSortables = new Map();
  const standaloneLessonId = Number(opts?.lessonId) || 0;

  return {
    courseId,
    standaloneLessonId,
    isStandalone: !Number(courseId) && standaloneLessonId > 0,
    attach: { courseId: 0, sectionId: 0, lessonId: 0, quizId: 0, changing: false },
    attachCourses: [],
    attachSections: [],
    attachLessons: [],
    attachQuizzes: [],
    attaching: false,
    course: {},
    sections: [],
    loading: true,
    error: '',
    saveStatus: '',
    publishing: false,
    addingSection: false,
    addingLesson: false,
    selected: null,
    expanded: {},
    editingKey: '',
    editingValue: '',
    previewUrl: '',
    lessonMode: 'text',
    lessonTab: 'written',
    editorView: 'visual',
    formatMenuOpen: false,
    formatLabel: 'Paragraph',
    formatOptions: [
      { label: 'Paragraph', value: 'p', size: '14px', weight: '500' },
      { label: 'Heading 1', value: 'h1', size: '20px', weight: '700' },
      { label: 'Heading 2', value: 'h2', size: '16px', weight: '700' },
      { label: 'Heading 3', value: 'h3', size: '16px', weight: '700' },
      { label: 'Heading 4', value: 'h4', size: '16px', weight: '700' },
      { label: 'Heading 5', value: 'h5', size: '14px', weight: '700' },
      { label: 'Preformatted', value: 'pre', size: '14px', weight: '500' },
    ],
    codeTags: ['b', 'i', 'link', 'b-quote', 'del', 'ins', 'img', 'ul', 'ol', 'li', 'code', 'more', 'close tags'],
    lessonQuiz: null,
    quizLoading: false,
    quizSaving: false,
    fromLessons: false,
    fromQuizzes: false,
    fromQuestions: false,
    /** Course-builder Quiz click (not Quizzes list) — keep Course›Lesson›Quiz focus. */
    fromBuilderOrigin: false,
    /** Standalone lesson → Add Quiz — keep Lesson›Quiz hierarchy. */
    fromLessonOrigin: false,
    fromQuizOrigin: false,
    preferredLessonTab: null,
    openQuizEditor: false,
    courseQuizzes: [],
    courseQuestions: [],
    questionSearch: '',
    activeQuestion: null,
    questionAnswerReady: false,
    questionEditorView: 'visual',
    questionFormatMenuOpen: false,
    questionFormatLabel: 'Paragraph',
    essaySubmitOpen: false,
    essayGradingOpen: false,
    essayGradingModes: [
      '-- Select --',
      'Not Graded, No Points Awarded',
      'Not Graded, Full Points Awarded',
      'Graded, Full Points Awarded',
    ],
    quizSearch: '',
    addingCourseQuiz: false,
    editorReady: false,

    buildPreviewUrl() {
      const base = String(config.urls.catalogPage || config.urls.playerPage || '').replace(/\/+$/, '');
      if (!base || !this.courseId) return '#';

      const params = new URLSearchParams();
      params.set('mintlms_course', String(this.courseId));

      const selected = this.selected;
      const lesson = this.selectedLesson;

      if (selected?.type === 'lesson') {
        params.set('mint_preview', 'lesson');
        params.set('mint_lesson', String(selected.id || lesson?.id || 0));
      } else if (selected?.type === 'quiz') {
        params.set('mint_preview', 'quiz');
        params.set('mint_lesson', String(selected.lessonId || selected.id || lesson?.id || 0));
      } else if (selected?.type === 'question') {
        params.set('mint_preview', 'question');
        params.set('mint_question', String(selected.questionId || selected.id || 0));
        const lessonId = selected.lessonId || lesson?.id || 0;
        if (lessonId) {
          params.set('mint_lesson', String(lessonId));
        }
      } else if (lesson?.id) {
        // Lesson panel context without an explicit tree selection type.
        params.set('mint_preview', 'lesson');
        params.set('mint_lesson', String(lesson.id));
      }

      try {
        const url = new URL(base, window.location.origin);
        params.forEach((value, key) => url.searchParams.set(key, value));
        return url.toString();
      } catch {
        const join = base.includes('?') ? '&' : '?';
        return `${base}${join}${params.toString()}`;
      }
    },

    refreshPreviewUrl() {
      this.previewUrl = this.buildPreviewUrl();
    },

    openBuilderPreview(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (this.isStandalone) {
        window.MintLMS?.toast?.error?.('Add this lesson to a course before previewing.');
        return;
      }
      const url = this.buildPreviewUrl();
      this.previewUrl = url;
      if (!url || url === '#') {
        window.MintLMS?.toast?.error?.('Preview page is not configured.');
        return;
      }
      window.open(url, '_blank', 'noopener,noreferrer');
    },

    firstLessonId() {
      for (const section of this.sections) {
        if (section.lessons && section.lessons.length > 0) {
          return section.lessons[0].id;
        }
      }
      return 0;
    },

    get selectedSection() {
      if (!this.selected || this.selected.type !== 'section') return null;
      return this.sections.find((s) => s.id === this.selected.id) || null;
    },

    get selectedLesson() {
      if (
        !this.selected ||
        (this.selected.type !== 'lesson' &&
          this.selected.type !== 'quiz' &&
          this.selected.type !== 'question')
      ) {
        return null;
      }
      const lessonId =
        this.selected.type === 'quiz' || this.selected.type === 'question'
          ? this.selected.lessonId
          : this.selected.id;
      for (const section of this.sections) {
        const lesson = (section.lessons || []).find((item) => item.id === lessonId);
        if (lesson) return lesson;
      }
      return null;
    },

    get quizEditorActive() {
      return this.selected?.type === 'quiz';
    },

    get questionEditorActive() {
      return this.selected?.type === 'question';
    },

    get filteredCourseQuizzes() {
      let rows = Array.isArray(this.courseQuizzes) ? [...this.courseQuizzes] : [];
      if (this.fromBuilderOrigin && !this.isStandalone) {
        const lessonId = this.breadcrumbParentLessonId();
        if (lessonId > 0) {
          rows = rows.filter((item) => Number(item.lessonId) === lessonId);
        }
      }
      const q = (this.quizSearch || '').trim().toLowerCase();
      if (!q) return rows;
      return rows.filter((item) => (item.title || '').toLowerCase().includes(q));
    },

    get filteredCourseQuestions() {
      const q = (this.questionSearch || '').trim().toLowerCase();
      if (!q) return this.courseQuestions;
      return this.courseQuestions.filter((item) =>
        (item.title || item.prompt || '').toLowerCase().includes(q)
      );
    },

  debouncedSaveSection: null,
  debouncedSaveLesson: null,

    async init() {
      this.debouncedSaveSection = debounce((section) => this.saveSection(section), 800);
      this.debouncedSaveLesson = debounce((lesson) => this.saveLesson(lesson), 800);
      const params = new URLSearchParams(window.location.search);
      this.fromLessons = params.get('from') === 'lessons' || this.isStandalone;
      this.fromQuizzes = params.get('from') === 'quizzes' || params.get('from') === 'questions';
      this.fromQuestions = params.get('from') === 'questions';
      this.fromBuilderOrigin = params.get('origin') === 'builder';
      this.fromLessonOrigin = params.get('origin') === 'lesson';
      this.fromQuizOrigin = params.get('origin') === 'quiz';
      const tab = params.get('tab');
      this.preferredLessonTab = tab === 'quiz' || tab === 'video' || tab === 'written' ? tab : null;
      this.openQuizEditor = this.fromQuizzes || tab === 'quiz' || !!params.get('quiz_id') || !!params.get('question_id');
      await this.loadStructure();
      // Always load course quizzes so Contents can nest Quiz under Lesson after return.
      if (Number(this.courseId) > 0) {
        await this.loadCourseQuizzes();
      }
      if (this.fromQuestions && !this.isStandalone) {
        await this.loadCourseQuestions();
      }
      await this.selectFromQuery();
      if (this.isStandalone && !this.selected && this.standaloneLessonId) {
        if (this.openQuizEditor) {
          await this.selectQuiz(this.standaloneLessonId, 0);
        } else {
          this.selectItem('lesson', this.standaloneLessonId, 0);
          if (this.preferredLessonTab && this.preferredLessonTab !== 'quiz') {
            this.lessonTab = this.preferredLessonTab;
          }
        }
      }
    },

    async selectFromQuery() {
      const params = new URLSearchParams(window.location.search);
      const questionId = parseInt(params.get('question_id') || '0', 10);
      const quizId = parseInt(params.get('quiz_id') || '0', 10);
      const lessonId = parseInt(params.get('lesson_id') || '0', 10);

      if (questionId > 0) {
        let row = this.courseQuestions.find((item) => item.id === questionId);
        if (!row && quizId > 0) {
          const quizRow = this.courseQuizzes.find((item) => item.id === quizId);
          if (quizRow) {
            await this.selectQuestion(questionId, quizRow.lessonId, quizRow.sectionId || null);
            return;
          }
        }
        if (row) {
          await this.selectQuestion(questionId, row.lessonId, row.sectionId || null);
          return;
        }
        if (lessonId > 0) {
          await this.selectQuestion(questionId, lessonId, null);
          return;
        }
      }

      // Prefer quiz_id so Edit Quiz still opens when the lesson is missing from structure.
      if (quizId > 0 && (this.fromQuizzes || this.openQuizEditor) && !this.fromQuestions) {
        const row = this.courseQuizzes.find((item) => item.id === quizId);
        if (row) {
          if (row.sectionId) {
            this.expanded[row.sectionId] = true;
          }
          await this.selectQuiz(row.lessonId, row.sectionId || null);
          return;
        }
      }

      if (!lessonId) return;

      for (const section of this.sections) {
        const lesson = (section.lessons || []).find((item) => item.id === lessonId);
        if (lesson) {
          this.expanded[section.id] = true;
          if (this.openQuizEditor) {
            await this.selectQuiz(lesson.id, section.id);
          } else {
            this.selectItem('lesson', lesson.id, section.id);
            if (this.preferredLessonTab && this.preferredLessonTab !== 'quiz') {
              this.lessonTab = this.preferredLessonTab;
            }
          }
          return;
        }
      }

      // Lesson may be outside structure (e.g. just restored) — still open quiz editor.
      if (this.openQuizEditor && lessonId > 0) {
        await this.selectQuiz(lessonId, null);
      }
    },

    async loadStructure() {
      this.loading = true;
      this.error = '';
      const root = document.getElementById('mint-course-builder');
      if (root) window.MintLMS.loading.show(root);

      try {
        if (this.isStandalone) {
          await this.loadStandaloneStructure();
          return;
        }

        const data = await mintApi(`courses/${this.courseId}/structure`);
        this.course = {
          title: data.title,
          slug: data.slug,
          status: data.status,
        };
        this.rememberCoursesTab(data.status);
        this.rememberOriginTab(data.status);
        this.sections = (data.sections || []).map((section) => ({
          ...section,
          lessons: section.lessons || [],
        }));
        this.refreshPreviewUrl();

        this.sections.forEach((section) => {
          this.expanded[section.id] = true;
        });

        await this.loadAttachCourses();

        this.$nextTick(() => {
          this.initSortables();
          this.initLessonEditor();
        });
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
      } finally {
        this.loading = false;
        if (root) window.MintLMS.loading.hide(root);
      }
    },

    async resolveMediaUrl(id) {
      if (!id) return '';
      try {
        const res = await fetch(joinRestUrl(config.mediaBase, String(id)), {
          headers: { 'X-WP-Nonce': config.nonce },
        });
        if (!res.ok) return '';
        const json = await res.json();
        return json?.source_url || json?.media_details?.sizes?.medium?.source_url || '';
      } catch {
        return '';
      }
    },

    async loadStandaloneStructure() {
      const lesson = await mintApi(`lessons/${this.standaloneLessonId}`);
      const featuredImageUrl = await this.resolveMediaUrl(lesson.featuredImageId);
      this.course = {
        title: lesson.title || 'New Lesson',
        slug: lesson.slug || '',
        status: 'draft',
      };
      this.sections = [
        {
          id: 0,
          title: '',
          sortOrder: 0,
          lessons: [
            {
              id: lesson.id,
              title: lesson.title || 'New Lesson',
              content: lesson.content || '',
              videoUrl: lesson.videoUrl || '',
              attachmentId: lesson.attachmentId || null,
              featuredImageId: lesson.featuredImageId || null,
              featuredImageUrl,
              isPreview: !!lesson.isPreview,
              availableAfterDays: lesson.availableAfterDays ?? null,
              status: 'draft',
              sortOrder: 0,
            },
          ],
        },
      ];
      this.expanded[0] = true;
      this.courseQuizzes = [];
      this.courseQuestions = [];

      try {
        const quiz = await mintApi(`lessons/${lesson.id}/quiz`);
        if (quiz?.id) {
          this.courseQuizzes = [
            {
              id: quiz.id,
              title: quiz.title,
              lessonId: lesson.id,
              lessonTitle: lesson.title || 'New Lesson',
              sectionId: 0,
              questionCount: (quiz.questions || []).length,
            },
          ];
          this.courseQuestions = (quiz.questions || []).map((q) => {
            const settings = q.settings && typeof q.settings === 'object' ? q.settings : {};
            const displayTitle = (settings.displayTitle || '').trim();
            return {
              id: q.id,
              title: displayTitle || q.prompt || q.title || 'New Question',
              prompt: q.prompt || '',
              lessonId: lesson.id,
              lessonTitle: lesson.title || 'New Lesson',
              quizId: quiz.id,
              quizTitle: quiz.title || 'New Quiz',
              sectionId: 0,
              settings,
            };
          });
        }
      } catch {
        // no quiz yet
      }

      await this.loadAttachCourses();
      if (this.isStandalone && this.fromQuizzes && !this.fromQuestions && !this.fromLessonOrigin) {
        await this.loadAttachLessons();
      }
      if (this.isStandalone && this.fromQuestions && !this.fromQuizOrigin && !this.fromLessonOrigin) {
        await this.loadAttachQuizzes();
      }
      if (!this.isStandalone || this.fromLessonOrigin || this.fromQuizOrigin) {
        if (this.fromQuizzes && !this.fromQuestions) {
          await this.loadAttachLessons();
        }
        if (this.fromQuestions) {
          await this.loadAttachQuizzes();
        }
      }
      this.refreshPreviewUrl();
      this.$nextTick(() => {
        this.initLessonEditor();
      });
    },

    async loadAttachCourses() {
      try {
        const data = await mintApi('courses?per_page=100');
        this.attachCourses = (data?.items || []).map((c) => ({
          id: c.id,
          title: c.title || `Course #${c.id}`,
        }));
      } catch {
        this.attachCourses = [];
      }
    },

    async loadAttachLessons() {
      this.attachLessons = [];
      this.attach.lessonId = 0;
      try {
        const [lessonsData, quizzesData] = await Promise.all([
          mintApi('content/lessons?per_page=100&status=all'),
          mintApi('content/quizzes?per_page=100&status=all'),
        ]);
        const taken = new Set(
          (quizzesData?.items || [])
            .map((q) => Number(q.lessonId) || 0)
            .filter((id) => id > 0)
        );
        const hostId = Number(this.standaloneLessonId) || 0;
        this.attachLessons = (lessonsData?.items || [])
          .map((l) => ({
            id: Number(l.id) || 0,
            title: l.title || `Lesson #${l.id}`,
            courseId: Number(l.courseId) || 0,
          }))
          .filter((l) => l.id > 0 && l.id !== hostId && !taken.has(l.id));
      } catch {
        this.attachLessons = [];
      }
    },

    async attachStandaloneQuizToLesson() {
      if (!this.attach.lessonId || !this.lessonQuiz?.id) return;
      this.attaching = true;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/attach`, {
          method: 'POST',
          body: JSON.stringify({
            lesson_id: this.attach.lessonId,
          }),
        });
        const lessonId = Number(quiz.lessonId) || Number(this.attach.lessonId) || 0;
        const courseId = Number(quiz.courseId) || 0;
        const quizId = Number(quiz.id) || Number(this.lessonQuiz.id) || 0;

        let url;
        if (courseId > 0) {
          const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
          url = new URL(base, window.location.href);
          url.searchParams.set('course_id', String(courseId));
          url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'quizzes');
          url.searchParams.set('origin', 'builder');
        } else {
          const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
          url = new URL(base, window.location.href);
          url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'quizzes');
          url.searchParams.set('origin', 'lesson');
        }

        window.MintLMS.toast.success(
          this.attach.changing ? 'Lesson updated.' : 'Quiz added to lesson.'
        );
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not add quiz to lesson.');
      } finally {
        this.attaching = false;
      }
    },

    async loadAttachQuizzes() {
      this.attachQuizzes = [];
      this.attach.quizId = 0;
      try {
        const data = await mintApi('content/quizzes?per_page=100&status=all');
        const hostQuizId =
          Number(this.lessonQuiz?.id || 0) ||
          Number(this.courseQuizzes?.[0]?.id || 0);
        this.attachQuizzes = (data?.items || [])
          .map((q) => ({
            id: Number(q.id) || 0,
            title: q.title || `Quiz #${q.id}`,
            lessonId: Number(q.lessonId) || 0,
            courseId: Number(q.courseId) || 0,
          }))
          .filter((q) => q.id > 0 && q.id !== hostQuizId);
      } catch {
        this.attachQuizzes = [];
      }
    },

    async attachStandaloneQuestionToQuiz() {
      const questionId = Number(this.activeQuestion?.id || this.selected?.questionId || 0);
      if (!this.attach.quizId || !questionId) return;
      this.attaching = true;
      try {
        const quiz = await mintApi(`questions/${questionId}/attach`, {
          method: 'POST',
          body: JSON.stringify({
            quiz_id: this.attach.quizId,
          }),
        });
        const lessonId = Number(quiz.lessonId) || 0;
        const courseId = Number(quiz.courseId) || 0;
        const quizId = Number(quiz.id) || Number(this.attach.quizId) || 0;

        let url;
        if (courseId > 0) {
          const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
          url = new URL(base, window.location.href);
          url.searchParams.set('course_id', String(courseId));
          url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('question_id', String(questionId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'questions');
          url.searchParams.set('origin', 'builder');
        } else {
          const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
          url = new URL(base, window.location.href);
          url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('question_id', String(questionId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'questions');
          url.searchParams.set('origin', 'quiz');
        }

        window.MintLMS.toast.success(
          this.attach.changing ? 'Quiz updated.' : 'Question added to quiz.'
        );
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not add question to quiz.');
      } finally {
        this.attaching = false;
      }
    },

    async loadAttachSections() {
      this.attachSections = [];
      this.attach.sectionId = 0;
      if (!this.attach.courseId) return;
      try {
        const data = await mintApi(`courses/${this.attach.courseId}/structure`);
        this.attachSections = (data?.sections || []).map((s) => ({
          id: s.id,
          title: s.title || `Section #${s.id}`,
        }));
      } catch {
        this.attachSections = [];
      }
    },

    async attachStandaloneToCourse() {
      if (!this.attach.courseId || !this.standaloneLessonId && !this.selectedLesson?.id) return;
      const lessonId = Number(this.standaloneLessonId || this.selectedLesson?.id || 0);
      if (!lessonId) return;
      this.attaching = true;
      try {
        if (this.selectedLesson) {
          await this.saveLesson(this.selectedLesson, { toast: false });
        }
        const lesson = await mintApi(`lessons/${lessonId}/attach`, {
          method: 'POST',
          body: JSON.stringify({
            course_id: this.attach.courseId,
            section_id: this.attach.sectionId || undefined,
          }),
        });
        const builder = config.urls?.builder || 'admin.php?page=mint-lms-builder';
        let url = `${builder}&course_id=${lesson.courseId}&lesson_id=${lesson.id}&from=lessons`;
        if (this.openQuizEditor || this.lessonTab === 'quiz') {
          url += '&tab=quiz';
          if (this.lessonQuiz?.id) url += `&quiz_id=${this.lessonQuiz.id}`;
          if (this.activeQuestion?.id) url += `&question_id=${this.activeQuestion.id}`;
        }
        window.MintLMS.toast.success(
          this.attach.changing ? 'Course updated.' : 'Lesson added to course.'
        );
        window.location.href = url;
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not update course association.');
      } finally {
        this.attaching = false;
      }
    },

    async startChangeCourse() {
      this.attach.changing = true;
      this.attach.courseId = Number(this.courseId) || 0;
      this.attach.sectionId = Number(this.selected?.sectionId || this.selectedLesson?.sectionId || 0);
      await this.loadAttachCourses();
      if (this.attach.courseId) {
        await this.loadAttachSections();
      }
    },

    cancelChangeAssociation() {
      this.attach.changing = false;
      this.attach.courseId = 0;
      this.attach.sectionId = 0;
      this.attach.lessonId = 0;
      this.attach.quizId = 0;
    },

    attachCourseLabel() {
      const id = Number(this.attach.courseId) || 0;
      if (!id) return '';
      return this.attachCourses.find((c) => Number(c.id) === id)?.title || '';
    },

    attachSectionLabel() {
      const id = Number(this.attach.sectionId) || 0;
      if (!id) return '';
      return this.attachSections.find((s) => Number(s.id) === id)?.title || '';
    },

    attachLessonLabel() {
      const id = Number(this.attach.lessonId) || 0;
      if (!id) return '';
      return this.attachLessons.find((l) => Number(l.id) === id)?.title || '';
    },

    attachQuizLabel() {
      const id = Number(this.attach.quizId) || 0;
      if (!id) return '';
      return this.attachQuizzes.find((q) => Number(q.id) === id)?.title || '';
    },

    async pickAttachCourse(courseId) {
      this.attach.courseId = Number(courseId) || 0;
      await this.loadAttachSections();
    },

    pickAttachSection(sectionId) {
      this.attach.sectionId = Number(sectionId) || 0;
    },

    pickAttachLesson(lessonId) {
      this.attach.lessonId = Number(lessonId) || 0;
    },

    pickAttachQuiz(quizId) {
      this.attach.quizId = Number(quizId) || 0;
    },

    async detachLessonFromCourse() {
      const lessonId = Number(this.selectedLesson?.id || this.standaloneLessonId || 0);
      if (!lessonId) return;
      if (!window.confirm('Remove this lesson from the course? It will go back to your lesson library.')) {
        return;
      }
      this.attaching = true;
      try {
        const lesson = await mintApi(`lessons/${lessonId}/detach`, { method: 'POST', body: '{}' });
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(lesson.id || lessonId));
        url.searchParams.set('from', 'lessons');
        window.MintLMS.toast.success('Lesson removed from course.');
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not remove from course.');
      } finally {
        this.attaching = false;
      }
    },

    async startChangeLesson() {
      this.attach.changing = true;
      this.attach.lessonId = 0;
      await this.loadAttachLessons();
    },

    async detachQuizFromLesson() {
      if (!this.lessonQuiz?.id) return;
      if (!window.confirm('Remove this quiz from the lesson? You can attach it to another lesson later.')) {
        return;
      }
      this.attaching = true;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/detach`, {
          method: 'POST',
          body: '{}',
        });
        const lessonId = Number(quiz.lessonId) || 0;
        const quizId = Number(quiz.id) || Number(this.lessonQuiz.id) || 0;
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(lessonId));
        if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
        url.searchParams.set('tab', 'quiz');
        url.searchParams.set('from', 'quizzes');
        window.MintLMS.toast.success('Quiz removed from lesson.');
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not remove from lesson.');
      } finally {
        this.attaching = false;
      }
    },

    async startChangeQuiz() {
      this.attach.changing = true;
      this.attach.quizId = 0;
      await this.loadAttachQuizzes();
    },

    async detachQuestionFromQuiz() {
      const questionId = Number(this.activeQuestion?.id || this.selected?.questionId || 0);
      if (!questionId) return;
      if (!window.confirm('Remove this question from the quiz? You can attach it to another quiz later.')) {
        return;
      }
      this.attaching = true;
      try {
        const quiz = await mintApi(`questions/${questionId}/detach`, {
          method: 'POST',
          body: '{}',
        });
        const lessonId = Number(quiz.lessonId) || 0;
        const quizId = Number(quiz.id) || 0;
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(lessonId));
        if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
        url.searchParams.set('question_id', String(questionId));
        url.searchParams.set('tab', 'quiz');
        url.searchParams.set('from', 'questions');
        window.MintLMS.toast.success('Question removed from quiz.');
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not remove from quiz.');
      } finally {
        this.attaching = false;
      }
    },

    initSortables() {
      const Sortable = window.MintLMS.Sortable;
      if (!Sortable) return;

      const sectionsEl = document.getElementById('mint-sections-sortable');
      if (sectionsEl) {
        if (sectionSortable) sectionSortable.destroy();
        sectionSortable = Sortable.create(sectionsEl, {
          handle: '.mint-handle-section',
          animation: 150,
          draggable: '[data-section-id]',
          onEnd: () => this.persistSectionOrder(),
        });
      }

      document.querySelectorAll('.mint-lessons-sortable').forEach((el) => {
        const sectionId = parseInt(el.dataset.sectionId, 10);
        if (lessonSortables.has(sectionId)) {
          lessonSortables.get(sectionId).destroy();
        }
        const instance = Sortable.create(el, {
          handle: '.mint-handle-lesson',
          animation: 150,
          draggable: '[data-lesson-id]',
          onEnd: () => this.persistLessonOrder(sectionId),
        });
        lessonSortables.set(sectionId, instance);
      });
    },

    statusLabel(status) {
      const labels = { draft: 'Draft', published: 'Live', archived: 'Archived' };
      return labels[status] || status;
    },

    statusBadgeClass(status) {
      const map = {
        draft: 'mint-inline-flex mint-items-center mint-rounded-full mint-bg-bg-subtle mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-text-ink-2',
        published: 'mint-inline-flex mint-items-center mint-rounded-full mint-bg-success-wash mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-text-success',
        archived: 'mint-inline-flex mint-items-center mint-rounded-full mint-bg-danger-wash mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-text-danger',
      };
      return map[status] || map.draft;
    },

    isExpanded(sectionId) {
      return this.expanded[sectionId] !== false;
    },

    toggleSection(sectionId) {
      this.expanded[sectionId] = !this.isExpanded(sectionId);
    },

    isSelected(type, id) {
      return this.selected && this.selected.type === type && this.selected.id === id;
    },

    isQuizLessonSelected(lessonId) {
      return this.selected?.type === 'quiz' && this.selected.lessonId === lessonId;
    },

    selectItem(type, id, sectionId = null) {
      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
      }
      this.formatMenuOpen = false;
      this.selected = { type, id, sectionId };
      this.refreshPreviewUrl();
      if (type === 'lesson') {
        this.syncLessonMode();
        this.$nextTick(() => this.syncEditorFromLesson());
        this.loadLessonQuiz(id);
      }
    },

    async selectQuiz(lessonId, sectionId = null) {
      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
      }
      this.formatMenuOpen = false;
      this.activeQuestion = null;
      this.questionAnswerReady = false;
      this.selected = { type: 'quiz', id: lessonId, lessonId, sectionId };
      this.lessonTab = 'quiz';
      this.refreshPreviewUrl();
      await this.loadLessonQuiz(lessonId);
      if (!this.lessonQuiz?.id) {
        await this.createQuiz({ silent: true });
      }
      this.syncCourseQuizTitle();
    },

    /**
     * Open the quiz editor for a lesson.
     * Already on from=quizzes → stay in SPA. Otherwise hard-navigate to dedicated
     * quiz URL (standalone lesson-edit OR course builder + from=quizzes).
     * Course-builder Quiz click also sets origin=builder for Course›Lesson›Quiz chrome.
     */
    async openLessonQuizEditor(lessonId, sectionId = null) {
      const id = Number(lessonId) || 0;
      if (id <= 0) return;

      if (this.fromQuizzes) {
        await this.selectQuiz(id, sectionId);
        return;
      }

      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
        if (this.selectedLesson) {
          await this.saveLesson(this.selectedLesson, { toast: false });
        }
      }

      this.selected = { type: 'quiz', id, lessonId: id, sectionId };
      this.lessonTab = 'quiz';
      await this.loadLessonQuiz(id);
      if (!this.lessonQuiz?.id) {
        await this.createQuiz({ silent: true });
      }

      const quizId = Number(this.lessonQuiz?.id || 0);
      if (!quizId) {
        return;
      }

      const href = this.parentQuizEditorUrlFor(id, quizId, {
        originBuilder: !this.isStandalone && Number(this.courseId) > 0,
        originLesson: this.isStandalone,
      });
      if (href && href !== '#') {
        window.location.assign(href);
      }
    },

    /**
     * Open question editor. From quiz surface, navigate to dedicated question URL
     * (Lesson > Quiz > Question) — same pattern as lesson → quiz.
     */
    async openLessonQuestionEditor() {
      if (this.fromQuestions) {
        this.addQuizQuestion('mcq', { select: true });
        return;
      }

      if (!this.fromQuizzes && !this.isStandalone) {
        this.addQuizQuestion('mcq', { select: true });
        return;
      }

      if (!this.lessonQuiz?.id) {
        await this.createQuiz({ silent: true });
      }
      if (!this.lessonQuiz?.id) {
        return;
      }

      this.quizSaving = true;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions`, {
          method: 'POST',
          body: JSON.stringify({
            // Placeholder type only — UI stays on Answer Type picker until user chooses.
            type: 'essay',
            prompt: 'New Question',
            options: [],
            correct_answer: '',
            settings: {
              displayTitle: 'New Question',
              answerTypePending: true,
            },
          }),
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
        const created = (this.lessonQuiz.questions || [])
          .slice()
          .reverse()
          .find((item) => Number(item?.id || 0) > 0);
        const questionId = Number(created?.id || 0);
        if (!questionId) {
          window.MintLMS.toast.error('Could not create question');
          return;
        }

        const lessonId =
          this.breadcrumbParentLessonId() ||
          Number(this.selected?.lessonId || this.standaloneLessonId || 0);
        const href = this.questionEditorUrlFor(lessonId, this.lessonQuiz.id, questionId);
        if (href && href !== '#') {
          window.location.assign(href);
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    questionEditorUrlFor(lessonId, quizId, questionId) {
      const lid = Number(lessonId) || 0;
      const qid = Number(quizId) || 0;
      const question = Number(questionId) || 0;
      if (lid <= 0 || question <= 0) return '#';

      if (this.isStandalone || !Number(this.courseId)) {
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(lid));
        if (qid > 0) url.searchParams.set('quiz_id', String(qid));
        url.searchParams.set('question_id', String(question));
        url.searchParams.set('tab', 'quiz');
        url.searchParams.set('from', 'questions');
        if (this.fromLessonOrigin) {
          url.searchParams.set('origin', 'lesson');
        } else if (this.fromQuizOrigin || this.fromQuizzes) {
          url.searchParams.set('origin', 'quiz');
        }
        return url.toString();
      }

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('lesson_id', String(lid));
      if (qid > 0) url.searchParams.set('quiz_id', String(qid));
      url.searchParams.set('question_id', String(question));
      url.searchParams.set('tab', 'quiz');
      url.searchParams.set('from', 'questions');
      if (this.fromBuilderOrigin) {
        url.searchParams.set('origin', 'builder');
      } else if (this.fromQuizOrigin || this.fromQuizzes) {
        url.searchParams.set('origin', 'quiz');
      }
      return url.toString();
    },

    async selectQuestion(questionId, lessonId, sectionId = null) {
      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
      }
      this.formatMenuOpen = false;
      this.selected = {
        type: 'question',
        id: questionId,
        questionId,
        lessonId,
        sectionId,
      };
      this.refreshPreviewUrl();
      this.lessonTab = 'quiz';
      await this.loadLessonQuiz(lessonId);
      if (!this.lessonQuiz?.id) {
        await this.createQuiz({ silent: true });
      }

      let question = (this.lessonQuiz.questions || []).find((item) => item.id === questionId);
      if (!question && questionId <= 0) {
        this.addQuizQuestion('mcq', { select: false });
        question = this.lessonQuiz.questions[this.lessonQuiz.questions.length - 1];
      }
      this.activeQuestion = question
        ? this.normalizeActiveQuestion(question)
        : this.emptyActiveQuestion();
      this.questionAnswerReady = this.isQuestionAnswerReady(this.activeQuestion);
      this.syncCourseQuizTitle();
      if (this.fromQuestions) {
        await this.loadCourseQuestions();
        this.syncCourseQuestionSidebarTitle();
      }
    },

    isBlankQuestionPrompt(prompt) {
      const value = (prompt || '').trim();
      return value === '' || value === 'New Question';
    },

    isQuestionAnswerReady(question) {
      if (!question) return false;
      // Fresh draft from "+ Add Question" — wait until user picks an answer type.
      if (question.settings?.answerTypePending) {
        return false;
      }
      if (question.type === 'essay') {
        return !this.isBlankQuestionPrompt(question.prompt) || Boolean(question.id);
      }
      const prompt = (question.prompt || '').trim();
      if (prompt && prompt !== 'New Question') return true;
      const answer = String(question.correctAnswer || '').trim();
      const options = Array.isArray(question.options) ? question.options : [];
      if (answer && (options.includes(answer) || answer === 'true' || answer === 'false' || answer.startsWith('['))) {
        return true;
      }
      if (question.type === 'mcq_multi') {
        const answers = Array.isArray(question.correctAnswers)
          ? question.correctAnswers
          : this.extractCorrectAnswers(question);
        return answers.length > 0;
      }
      return false;
    },

    normalizeActiveQuestion(question) {
      const type = this.normalizeQuestionType(question?.type);
      const correctAnswers = this.extractCorrectAnswers(question);
      const settings = this.normalizeQuestionSettings(question?.settings, Array.isArray(question?.options) ? question.options.length : 2);
      const displayTitle = (settings.displayTitle || question.title || 'New Question').trim();
      const rawPrompt = String(question.prompt || '');
      // Storage may keep "New Question" as API placeholder — keep the body field empty for editing.
      const prompt = this.isBlankQuestionPrompt(rawPrompt) ? '' : rawPrompt;
      return {
        ...question,
        title: displayTitle || 'New Question',
        type,
        prompt,
        options:
          type === 'true_false'
            ? ['True', 'False']
            : type === 'essay'
              ? []
              : Array.isArray(question.options) && question.options.length
                ? [...question.options]
                : ['Option 1', 'Option 2'],
        correctAnswer:
          type === 'mcq_multi'
            ? ''
            : type === 'essay'
              ? ''
              : question.correctAnswer || '',
        correctAnswers: type === 'mcq_multi' ? correctAnswers : [],
        featuredImageId: question.featuredImageId || null,
        featuredImageUrl: question.featuredImageUrl || '',
        status: question.status || 'draft',
        settings,
        extraTf: this.normalizeExtraTfList(settings.extraTf || question.extraTf),
      };
    },

    normalizeExtraTfList(list) {
      if (!Array.isArray(list)) return [];
      return list.map((item, index) => ({
        _key: item._key || `ex-${Date.now()}-${index}`,
        id: item.id || null,
        prompt: item.prompt || item.questionText || '',
        correctAnswer:
          item.correctAnswer === 'true' || item.correctAnswer === true
            ? 'true'
            : item.correctAnswer === 'false' || item.correctAnswer === false
              ? 'false'
              : '',
        editorView: item.editorView === 'code' ? 'code' : 'visual',
        freePreview: !!item.freePreview,
        attachmentId: Number(item.attachmentId) || 0,
        attachmentName: item.attachmentName || '',
        attachmentUrl: item.attachmentUrl || '',
      }));
    },

    emptyExtraTfAnswer() {
      return {
        _key: `ex-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
        id: null,
        prompt: '',
        correctAnswer: '',
        editorView: 'visual',
        freePreview: false,
        attachmentId: 0,
        attachmentName: '',
        attachmentUrl: '',
      };
    },

    normalizeQuestionSettings(settings, optionCount = 2) {
      const base = {
        freePreview: false,
        attachmentId: 0,
        attachmentName: '',
        attachmentUrl: '',
        allowHtml: [],
        submitMethod: 'Text Box',
        gradingMode: 'Not Graded, No Points Awarded',
        points: 1,
        extraTf: [],
        displayTitle: '',
        answerTypePending: false,
      };
      const merged = { ...base, ...(settings && typeof settings === 'object' ? settings : {}) };
      const allowHtml = Array.isArray(merged.allowHtml) ? [...merged.allowHtml] : [];
      while (allowHtml.length < optionCount) allowHtml.push(false);
      merged.allowHtml = allowHtml.slice(0, Math.max(optionCount, allowHtml.length));
      merged.freePreview = !!merged.freePreview;
      merged.attachmentId = Number(merged.attachmentId) || 0;
      merged.points = Math.max(0, Number(merged.points) || 1);
      if (merged.submitMethod !== 'Upload') merged.submitMethod = 'Text Box';
      merged.extraTf = this.normalizeExtraTfList(merged.extraTf);
      merged.displayTitle = String(merged.displayTitle || '').trim();
      merged.answerTypePending = !!merged.answerTypePending;
      return merged;
    },

    normalizeQuestionType(type) {
      if (type === 'true_false') return 'true_false';
      if (type === 'essay') return 'essay';
      if (type === 'mcq_multi') return 'mcq_multi';
      return 'mcq';
    },

    extractCorrectAnswers(question) {
      if (!question) return [];
      if (Array.isArray(question.correctAnswers)) {
        return question.correctAnswers.filter((item) => String(item || '').trim() !== '');
      }
      const raw = question.correctAnswer;
      if (typeof raw === 'string' && raw.trim().startsWith('[')) {
        try {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            return parsed.map(String).filter((item) => item.trim() !== '');
          }
        } catch {
          // fall through
        }
      }
      if (typeof raw === 'string' && raw.trim() !== '') {
        return [raw];
      }
      return [];
    },

    emptyActiveQuestion() {
      return {
        _key: Date.now(),
        id: null,
        title: 'New Question',
        type: 'mcq',
        prompt: '',
        options: ['Option 1', 'Option 2'],
        correctAnswer: '',
        correctAnswers: [],
        featuredImageId: null,
        featuredImageUrl: '',
        settings: this.normalizeQuestionSettings(null, 2),
        extraTf: [],
        sortOrder: this.lessonQuiz?.questions?.length || 0,
      };
    },

    async loadCourseQuestions() {
      if (!this.courseQuizzes.length) {
        await this.loadCourseQuizzes();
      }
      const rows = [];
      for (const quiz of this.courseQuizzes) {
        try {
          const data = await mintApi(`lessons/${quiz.lessonId}/quiz`);
          const questions = Array.isArray(data?.questions) ? data.questions : [];
          questions.forEach((question, index) => {
            const settings = question.settings && typeof question.settings === 'object' ? question.settings : {};
            const displayTitle = (settings.displayTitle || '').trim();
            rows.push({
              id: Number(question.id) || question.id,
              title: displayTitle || question.prompt || question.title || `Question ${index + 1}`,
              prompt: question.prompt || '',
              type: question.type,
              quizId: data.id,
              lessonId: quiz.lessonId,
              lessonTitle: quiz.lessonTitle || this.quizParentLessonTitle(quiz) || '',
              sectionId: quiz.sectionId || null,
              quizTitle: quiz.title || data.title || 'Quiz',
              settings,
            });
          });
        } catch {
          // Skip quizzes that fail to load.
        }
      }
      this.courseQuestions = rows;
    },

    isCourseQuestionSelected(question) {
      if (!question || this.selected?.type !== 'question') return false;
      if (question.id && this.activeQuestion?.id) {
        return question.id === this.activeQuestion.id;
      }
      return question.id === this.selected.questionId;
    },

    questionTypeLabel(question) {
      if (!question) return 'Multiple choice';
      if (question.type === 'true_false') return 'True / False';
      if (question.type === 'essay') return 'Essay answer';
      if (question.type === 'mcq_multi') return 'MCQ · Multiple choice';
      return 'MCQ · Single choice';
    },

    breadcrumbQuestionLabel() {
      if (!this.activeQuestion) return 'Question';
      const title = (this.activeQuestion.title || this.activeQuestion.prompt || '').trim();
      return title || 'New Question';
    },

    isMcqOptionCorrect(question, option) {
      if (!question || !option) return false;
      if (question.type === 'mcq_multi') {
        const answers = Array.isArray(question.correctAnswers) ? question.correctAnswers : [];
        return answers.includes(option);
      }
      return question.correctAnswer === option;
    },

    toggleMcqCorrect(question, option) {
      if (!question || !option) return;
      if (question.type === 'mcq_multi') {
        if (!Array.isArray(question.correctAnswers)) {
          question.correctAnswers = [];
        }
        const index = question.correctAnswers.indexOf(option);
        if (index >= 0) {
          question.correctAnswers.splice(index, 1);
        } else {
          question.correctAnswers.push(option);
        }
        return;
      }
      question.correctAnswer = option;
    },

    setQuestionAnswerType(kind) {
      if (!this.activeQuestion) {
        this.activeQuestion = this.emptyActiveQuestion();
      }
      if (!this.activeQuestion.settings) {
        this.activeQuestion.settings = this.normalizeQuestionSettings(null, 2);
      }
      this.activeQuestion.settings.answerTypePending = false;

      if (kind === 'essay') {
        this.activeQuestion.type = 'essay';
        this.activeQuestion.options = [];
        this.activeQuestion.correctAnswer = '';
        this.activeQuestion.correctAnswers = [];
        this.activeQuestion.settings = this.normalizeQuestionSettings(this.activeQuestion.settings, 0);
        this.activeQuestion.settings.answerTypePending = false;
        this.questionAnswerReady = true;
        this.focusQuestionPrompt();
        return;
      }
      if (kind === 'true_false') {
        this.activeQuestion.type = 'true_false';
        this.activeQuestion.options = ['True', 'False'];
        this.activeQuestion.correctAnswers = [];
        if (!Array.isArray(this.activeQuestion.extraTf)) {
          this.activeQuestion.extraTf = [];
        }
        if (this.activeQuestion.correctAnswer !== 'true' && this.activeQuestion.correctAnswer !== 'false') {
          this.activeQuestion.correctAnswer = '';
        }
        this.activeQuestion.settings.answerTypePending = false;
        this.questionAnswerReady = true;
        this.focusQuestionPrompt();
        return;
      }
      const nextType = kind === 'mcq_multi' ? 'mcq_multi' : 'mcq';
      this.activeQuestion.type = nextType;
      if (!Array.isArray(this.activeQuestion.options) || this.activeQuestion.options.length < 2) {
        this.activeQuestion.options = ['Option 1', 'Option 2'];
      }
      if (this.activeQuestion.options.includes('True') && this.activeQuestion.options.includes('False')) {
        this.activeQuestion.options = ['Option 1', 'Option 2'];
        this.activeQuestion.correctAnswer = '';
        this.activeQuestion.correctAnswers = [];
      }
      this.activeQuestion.settings = this.normalizeQuestionSettings(
        this.activeQuestion.settings,
        this.activeQuestion.options.length
      );
      this.activeQuestion.settings.answerTypePending = false;
      if (nextType === 'mcq_multi') {
        if (!Array.isArray(this.activeQuestion.correctAnswers)) {
          this.activeQuestion.correctAnswers = [];
        }
        if (
          this.activeQuestion.correctAnswer &&
          this.activeQuestion.options.includes(this.activeQuestion.correctAnswer) &&
          this.activeQuestion.correctAnswers.length === 0
        ) {
          this.activeQuestion.correctAnswers = [this.activeQuestion.correctAnswer];
        }
        this.activeQuestion.correctAnswer = '';
      } else if (
        Array.isArray(this.activeQuestion.correctAnswers) &&
        this.activeQuestion.correctAnswers.length === 1 &&
        !this.activeQuestion.correctAnswer
      ) {
        this.activeQuestion.correctAnswer = this.activeQuestion.correctAnswers[0];
        this.activeQuestion.correctAnswers = [];
      }
      this.questionAnswerReady = true;
      this.focusQuestionPrompt();
    },

    focusQuestionPrompt() {
      const focus = () => {
        const textarea = document.getElementById('mint-question-prompt');
        if (!textarea) return false;
        textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
        textarea.focus({ preventScroll: true });
        const len = (textarea.value || '').length;
        try {
          textarea.setSelectionRange(len, len);
        } catch {
          // Some browsers may reject selection on hidden nodes.
        }
        return true;
      };
      this.$nextTick?.(() => {
        if (focus()) return;
        requestAnimationFrame(() => {
          if (focus()) return;
          setTimeout(focus, 50);
        });
      });
      // Fallback if Alpine $nextTick is unavailable in this context.
      if (!this.$nextTick) {
        requestAnimationFrame(() => {
          if (focus()) return;
          setTimeout(focus, 50);
        });
      }
    },

    syncActiveQuestionTitle() {
      if (!this.activeQuestion) return;
      const title = (this.activeQuestion.title || '').trim();
      if (!this.activeQuestion.settings) {
        this.activeQuestion.settings = this.normalizeQuestionSettings(null, (this.activeQuestion.options || []).length);
      }
      this.activeQuestion.settings.displayTitle = title;
      // Title (sidebar name) and prompt (question body) stay independent.
      this.syncCourseQuestionSidebarTitle();
    },

    syncCourseQuestionSidebarTitle() {
      if (!this.activeQuestion) return;
      const title =
        (this.activeQuestion.title || '').trim() ||
        (this.activeQuestion.settings?.displayTitle || '').trim() ||
        'New Question';
      const activeId = Number(this.activeQuestion.id || 0);
      const selectedId = Number(this.selected?.questionId || 0);
      const row = this.courseQuestions.find((item) => {
        const itemId = Number(item.id || 0);
        return (activeId > 0 && itemId === activeId) || (selectedId > 0 && itemId === selectedId);
      });
      if (row) {
        row.title = title;
        if (this.activeQuestion.settings) {
          row.settings = {
            ...(row.settings || {}),
            displayTitle: title,
          };
        }
      }
    },

    async saveActiveQuestion() {
      if (!this.activeQuestion) return;

      const title = (this.activeQuestion.title || '').trim();
      let prompt = (this.activeQuestion.prompt || '').trim();
      if (!this.activeQuestion.settings) {
        this.activeQuestion.settings = this.normalizeQuestionSettings(null, (this.activeQuestion.options || []).length);
      }
      this.activeQuestion.settings.displayTitle = title || 'New Question';

      // Never copy title into the question body (or body into title).
      if (this.isBlankQuestionPrompt(prompt)) {
        prompt = '';
        this.activeQuestion.prompt = '';
      }

      // Title/sidebar rename should save even before an answer type is chosen.
      if (!this.questionAnswerReady) {
        if (!this.activeQuestion.id) {
          window.MintLMS.toast.info('Choose an answer type first');
          this.syncCourseQuestionSidebarTitle();
          return;
        }
        await this.saveQuestionTitleOnly();
        return;
      }

      if (!prompt) {
        window.MintLMS.toast.info('Write your question');
        this.focusQuestionPrompt();
        return;
      }

      await this.saveQuizQuestion(this.activeQuestion);
      if (this.activeQuestion.type === 'true_false') {
        await this.syncExtraTfQuestions();
      }
      const preservedExtraTf = this.normalizeExtraTfList(this.activeQuestion.extraTf);
      const preservedSettings = {
        ...(this.activeQuestion.settings || {}),
        displayTitle: (this.activeQuestion.title || '').trim() || (this.activeQuestion.settings?.displayTitle || ''),
        extraTf: preservedExtraTf,
      };
      const refreshed = (this.lessonQuiz.questions || []).find(
        (item) =>
          (this.activeQuestion.id && item.id === this.activeQuestion.id) ||
          (this.activeQuestion._key && item._key === this.activeQuestion._key) ||
          (!this.activeQuestion.id && item.prompt === this.activeQuestion.prompt)
      );
      if (refreshed) {
        this.activeQuestion = this.normalizeActiveQuestion({
          ...refreshed,
          title: preservedSettings.displayTitle || this.activeQuestion.title || refreshed.prompt,
          featuredImageId: this.activeQuestion.featuredImageId,
          featuredImageUrl: this.activeQuestion.featuredImageUrl,
          settings: {
            ...(refreshed.settings || {}),
            ...preservedSettings,
          },
          extraTf: preservedExtraTf,
        });
        this.questionAnswerReady = true;
        this.selected.questionId = refreshed.id || 0;
        this.selected.id = refreshed.id || 0;
        this.refreshPreviewUrl();
      }
      this.syncCourseQuestionSidebarTitle();
      if (this.fromQuestions) {
        await this.loadCourseQuestions();
        this.syncCourseQuestionSidebarTitle();
      }
    },

    async saveQuestionTitleOnly() {
      if (!this.activeQuestion?.id || !this.lessonQuiz?.id) return;
      this.quizSaving = true;
      try {
        const type = this.normalizeQuestionType(this.activeQuestion.type || 'mcq');
        // Keep API prompt valid without copying the display title into the question body.
        const livePrompt = (this.activeQuestion.prompt || '').trim();
        const prompt = this.isBlankQuestionPrompt(livePrompt) ? 'New Question' : livePrompt;
        const settings = this.normalizeQuestionSettings(
          this.activeQuestion.settings,
          (this.activeQuestion.options || []).length
        );
        settings.displayTitle =
          (this.activeQuestion.title || '').trim() || settings.displayTitle || 'New Question';
        // Preserve pending answer-type drafts across title-only saves.
        settings.answerTypePending = !!this.activeQuestion.settings?.answerTypePending;

        let correctAnswer = this.activeQuestion.correctAnswer || '';
        if (type === 'mcq_multi') {
          correctAnswer = Array.isArray(this.activeQuestion.correctAnswers)
            ? this.activeQuestion.correctAnswers
            : [];
          if (!Array.isArray(correctAnswer) || correctAnswer.length === 0) {
            // Keep existing stored answer; send current prompt/settings only via PATCH fields that validate.
            correctAnswer = this.activeQuestion.options?.[0] || 'Option 1';
          }
        } else if (type === 'true_false' && correctAnswer !== 'true' && correctAnswer !== 'false') {
          correctAnswer = 'true';
        } else if (type === 'mcq') {
          const options = (this.activeQuestion.options || []).filter((o) => String(o || '').trim());
          if (!options.includes(correctAnswer)) {
            correctAnswer = options[0] || 'Option 1';
          }
        } else if (type === 'essay') {
          correctAnswer = '';
        }

        const payload = {
          type,
          prompt,
          options:
            type === 'true_false'
              ? ['True', 'False']
              : type === 'essay'
                ? []
                : (this.activeQuestion.options || []).filter((o) => String(o || '').trim()),
          correct_answer: correctAnswer,
          featured_image_id: this.activeQuestion.featuredImageId || 0,
          settings,
        };

        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${this.activeQuestion.id}`, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
        this.syncCourseQuestionSidebarTitle();
        if (this.fromQuestions) {
          await this.loadCourseQuestions();
          this.syncCourseQuestionSidebarTitle();
        }
        window.MintLMS.toast.success('Question saved');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async deleteActiveQuestion() {
      if (!this.activeQuestion) return;
      await this.deleteQuizQuestion(this.activeQuestion);
      this.activeQuestion = null;
      this.questionAnswerReady = false;
      if (this.fromQuestions) {
        await this.loadCourseQuestions();
        if (this.courseQuestions.length > 0) {
          const next = this.courseQuestions[0];
          await this.selectQuestion(next.id, next.lessonId, next.sectionId || null);
          return;
        }
      }
      if (this.selected?.lessonId) {
        await this.selectQuiz(this.selected.lessonId, this.selected.sectionId || null);
      } else {
        this.selected = null;
      }
    },

    async addCourseQuestion() {
      if (this.addingCourseQuiz) return;
      this.addingCourseQuiz = true;
      try {
        if (!this.courseQuizzes.length) {
          await this.addCourseQuiz();
        }
        const quiz = this.courseQuizzes[0];
        if (!quiz) return;
        await this.selectQuiz(quiz.lessonId, quiz.sectionId || null);
        this.addQuizQuestion('mcq', { select: true });
      } finally {
        this.addingCourseQuiz = false;
      }
    },

    async loadCourseQuizzes() {
      // Standalone / library quizzes are not course-scoped — never call courses/0.
      if (!Number(this.courseId)) {
        this.syncCourseQuizSidebarRow();
        return;
      }

      try {
        const data = await mintApi(`courses/${this.courseId}/quizzes`);
        this.courseQuizzes = Array.isArray(data?.items) ? data.items : [];
      } catch (err) {
        this.courseQuizzes = [];
        window.MintLMS.toast.error(err.message);
      }
    },

    /**
     * Refresh quiz sidebar after save/delete without wiping standalone rows.
     */
    async refreshQuizSidebar() {
      if (this.isStandalone || !Number(this.courseId)) {
        this.syncCourseQuizSidebarRow();
        return;
      }
      await this.loadCourseQuizzes();
    },

    isCourseQuizSelected(quiz) {
      if (!quiz || this.selected?.type !== 'quiz') return false;
      if (quiz.id && this.lessonQuiz?.id) {
        return quiz.id === this.lessonQuiz.id;
      }
      return quiz.lessonId === this.selected.lessonId;
    },

    syncCourseQuizTitle() {
      if (!this.lessonQuiz?.id || !this.lessonQuiz.title) return;
      const row = this.courseQuizzes.find((item) => item.id === this.lessonQuiz.id);
      if (row) {
        row.title = this.lessonQuiz.title;
        if (!row.lessonTitle) {
          row.lessonTitle = this.breadcrumbParentLessonTitle();
        }
      }
    },

    /** Keep quiz sidebar rows in sync when a quiz is first created. */
    syncCourseQuizSidebarRow() {
      if (!this.lessonQuiz?.id || !this.selectedLesson) return;
      const existing = this.courseQuizzes.find((item) => item.id === this.lessonQuiz.id);
      if (existing) {
        existing.title = this.lessonQuiz.title || existing.title;
        existing.lessonTitle = existing.lessonTitle || this.selectedLesson.title || '';
        return;
      }
      this.courseQuizzes = [
        {
          id: this.lessonQuiz.id,
          title: this.lessonQuiz.title || 'New Quiz',
          lessonId: this.selectedLesson.id,
          lessonTitle: this.selectedLesson.title || '',
          sectionId: this.selected?.sectionId || 0,
          questionCount: (this.lessonQuiz.questions || []).length,
        },
        ...this.courseQuizzes,
      ];
    },

    async addCourseQuiz() {
      if (this.addingCourseQuiz) return;
      this.addingCourseQuiz = true;
      try {
        if (this.sections.length === 0) {
          const section = await mintApi(`courses/${this.courseId}/sections`, {
            method: 'POST',
            body: JSON.stringify({ title: 'New Section' }),
          });
          this.sections.push({ ...section, lessons: [] });
          this.expanded[section.id] = true;
        }

        const sectionId = this.sections[0].id;
        const lesson = await mintApi(`sections/${sectionId}/lessons`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Lesson', content: '', is_preview: false }),
        });
        const section = this.sections.find((s) => s.id === sectionId);
        if (section) {
          section.lessons.push(lesson);
        }

        const quiz = await mintApi(`lessons/${lesson.id}/quiz`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Quiz', pass_percent: 80 }),
        });

        await this.loadCourseQuizzes();
        await this.selectQuiz(lesson.id, sectionId);
        if (quiz?.id) {
          this.syncCourseQuizTitle();
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.addingCourseQuiz = false;
      }
    },

    treeAddLessonLabel() {
      return this.quizEditorActive ? 'Add Question' : 'Add lesson';
    },

    treeAddSectionLabel() {
      return this.quizEditorActive ? 'Add Quiz' : 'Add section';
    },

    onTreeAddLesson(sectionId) {
      if (this.quizEditorActive || this.questionEditorActive) {
        this.addQuizQuestion('mcq', { select: true });
        return;
      }
      this.addLesson(sectionId);
    },

    onTreeAddSection() {
      if (this.fromQuizzes) {
        this.addCourseQuiz();
        return;
      }
      if (this.quizEditorActive) {
        this.addQuizQuestion('true_false');
        return;
      }
      this.addSection();
    },

    editorId() {
      return 'mint_lesson_content';
    },

    initLessonEditor() {
      if (this.editorReady) return;

      const bindEditor = () => {
        const editorId = this.editorId();
        const editor = window.tinymce?.get(editorId);
        if (editor && !editor._mintBound) {
          editor.on('change keyup', () => {
            if (!this.selectedLesson) return;
            this.selectedLesson.content = editor.getContent();
            this.debouncedSaveLesson(this.selectedLesson);
          });
          editor._mintBound = true;
        }

        const textarea = document.getElementById(editorId);
        if (textarea && !textarea.dataset.mintBound) {
          textarea.dataset.mintBound = '1';
          textarea.addEventListener('input', () => {
            if (!this.selectedLesson || window.tinymce?.get(editorId)) return;
            this.selectedLesson.content = textarea.value;
            this.debouncedSaveLesson(this.selectedLesson);
          });
        }
      };

      bindEditor();
      setTimeout(bindEditor, 600);
      setTimeout(bindEditor, 1500);
      this.editorReady = true;
    },

    pullEditorContent() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      const editorId = this.editorId();
      if (window.tinymce?.get(editorId)) {
        lesson.content = window.tinymce.get(editorId).getContent();
        return;
      }
      const textarea = document.getElementById(editorId);
      if (textarea) {
        lesson.content = textarea.value;
      }
    },

    syncEditorFromLesson() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      const editorId = this.editorId();
      const content = lesson.content || '';
      if (window.tinymce?.get(editorId)) {
        window.tinymce.get(editorId).setContent(content);
        return;
      }
      const textarea = document.getElementById(editorId);
      if (textarea) {
        textarea.value = content;
      }
    },

    syncLessonMode() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      this.lessonMode = lesson.videoUrl ? 'video' : 'text';
      this.lessonTab = lesson.videoUrl ? 'video' : 'written';
    },

    setLessonMode(mode) {
      this.lessonMode = mode;
      this.lessonTab = mode === 'video' ? 'video' : 'written';
    },

    setEditorView(view) {
      this.editorView = view === 'code' ? 'code' : 'visual';
      this.formatMenuOpen = false;
      const editorId = this.editorId();
      if (typeof window.switchEditors?.go === 'function') {
        window.switchEditors.go(editorId, this.editorView === 'code' ? 'html' : 'tmce');
      }
      this.$nextTick(() => this.initLessonEditor());
    },

    getLessonEditor() {
      return window.tinymce?.get(this.editorId()) || null;
    },

    markLessonDirtyFromEditor() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      const editor = this.getLessonEditor();
      if (editor) {
        lesson.content = editor.getContent();
      } else {
        const textarea = document.getElementById(this.editorId());
        if (textarea) lesson.content = textarea.value;
      }
      this.debouncedSaveLesson(lesson);
    },

    runEditorCommand(command, value = null) {
      const editor = this.getLessonEditor();
      if (!editor) return;
      editor.focus();
      if (value == null) {
        editor.execCommand(command);
      } else {
        editor.execCommand(command, false, value);
      }
      this.markLessonDirtyFromEditor();
    },

    applyFormat(option) {
      this.formatLabel = option.label;
      this.formatMenuOpen = false;
      const editor = this.getLessonEditor();
      if (!editor) return;
      editor.focus();
      try {
        editor.execCommand('FormatBlock', false, option.value);
      } catch {
        editor.execCommand('FormatBlock', false, `<${option.value}>`);
      }
      this.markLessonDirtyFromEditor();
    },

    insertEditorLink() {
      const editor = this.getLessonEditor();
      if (!editor) return;
      const url = window.prompt('Enter URL');
      if (!url) return;
      editor.focus();
      const selected = editor.selection.getContent({ format: 'text' }) || url;
      editor.insertContent(`<a href="${url}">${selected}</a>`);
      this.markLessonDirtyFromEditor();
    },

    wrapTextareaSelection(before, after = '') {
      const textarea = document.getElementById(this.editorId());
      if (!textarea) return;
      const start = textarea.selectionStart ?? 0;
      const end = textarea.selectionEnd ?? 0;
      const value = textarea.value || '';
      const selected = value.slice(start, end);
      const next = `${value.slice(0, start)}${before}${selected}${after}${value.slice(end)}`;
      textarea.value = next;
      const cursor = start + before.length + selected.length + after.length;
      textarea.focus();
      textarea.setSelectionRange(
        selected ? start + before.length : cursor,
        selected ? start + before.length + selected.length : cursor
      );
      if (this.selectedLesson) {
        this.selectedLesson.content = next;
        this.debouncedSaveLesson(this.selectedLesson);
      }
    },

    insertCodeTag(tag) {
      if (tag === 'close tags') {
        if (typeof window.QTags?.closeAllTags === 'function') {
          window.QTags.closeAllTags(this.editorId());
          this.markLessonDirtyFromEditor();
        }
        return;
      }

      if (tag === 'link') {
        const url = window.prompt('Enter URL');
        if (!url) return;
        const textarea = document.getElementById(this.editorId());
        const selected = textarea
          ? (textarea.value || '').slice(textarea.selectionStart || 0, textarea.selectionEnd || 0)
          : '';
        const label = selected || url;
        if (selected) {
          this.wrapTextareaSelection(`<a href="${url}">`, '</a>');
        } else {
          this.wrapTextareaSelection(`<a href="${url}">${label}</a>`, '');
        }
        return;
      }

      if (tag === 'img') {
        if (typeof wp !== 'undefined' && wp.media) {
          const frame = wp.media({
            title: 'Insert image',
            button: { text: 'Insert' },
            library: { type: 'image' },
            multiple: false,
          });
          frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();
            const alt = attachment.alt || attachment.title || '';
            this.wrapTextareaSelection(`<img src="${attachment.url}" alt="${alt}" />`, '');
          });
          frame.open();
          return;
        }
        const src = window.prompt('Image URL');
        if (!src) return;
        this.wrapTextareaSelection(`<img src="${src}" alt="" />`, '');
        return;
      }

      if (tag === 'more') {
        this.wrapTextareaSelection('<!--more-->', '');
        return;
      }

      const pairs = {
        b: ['<strong>', '</strong>'],
        i: ['<em>', '</em>'],
        'b-quote': ['<blockquote>', '</blockquote>'],
        del: ['<del>', '</del>'],
        ins: ['<ins>', '</ins>'],
        ul: ['<ul>\n<li>', '</li>\n</ul>'],
        ol: ['<ol>\n<li>', '</li>\n</ol>'],
        li: ['<li>', '</li>'],
        code: ['<code>', '</code>'],
      };
      const pair = pairs[tag];
      if (!pair) return;
      this.wrapTextareaSelection(pair[0], pair[1]);
    },

    addMediaToEditor() {
      if (typeof wp === 'undefined' || !wp.media) {
        window.MintLMS.toast.error('Media library unavailable');
        return;
      }
      const frame = wp.media({
        title: 'Add media',
        button: { text: 'Insert into lesson' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        const editorId = this.editorId();
        let html = '';
        if (attachment.type === 'image') {
          const alt = attachment.alt || attachment.title || '';
          html = `<img src="${attachment.url}" alt="${alt}" />`;
        } else if (attachment.url) {
          const title = attachment.title || attachment.filename || 'Download';
          html = `<a href="${attachment.url}">${title}</a>`;
        }
        if (!html) return;

        this.setEditorView('visual');
        const editor = window.tinymce?.get(editorId);
        if (editor) {
          editor.insertContent(html);
          if (this.selectedLesson) {
            this.selectedLesson.content = editor.getContent();
            this.debouncedSaveLesson(this.selectedLesson);
          }
          return;
        }
        const textarea = document.getElementById(editorId);
        if (textarea) {
          textarea.value = `${textarea.value || ''}${html}`;
          if (this.selectedLesson) {
            this.selectedLesson.content = textarea.value;
            this.debouncedSaveLesson(this.selectedLesson);
          }
        }
      });
      frame.open();
    },

    totalLessonCount() {
      return this.sections.reduce((sum, section) => sum + section.lessons.length, 0);
    },

    sectionLessonSummary() {
      const section = this.selectedSection;
      const count = section ? section.lessons.length : 0;
      if (count === 1) {
        return '1 lesson in this section';
      }
      return `${count} lessons in this section`;
    },

    saveStateLabel() {
      if (this.saveStatus === 'saving') return 'Saving…';
      if (this.saveStatus === 'saved') return 'Saved';
      return '';
    },

    breadcrumbSectionTitle() {
      if (this.isStandalone) return '';
      if (!this.selected || this.selected.sectionId == null) return '';
      const section = this.sections.find((s) => s.id === this.selected.sectionId);
      return section?.title || '';
    },

    breadcrumbCourseTitle() {
      if (this.isStandalone || !Number(this.courseId)) return '';
      const title = String(this.course?.title || '').trim();
      return title || 'Untitled course';
    },

    parentCourseEditorUrl() {
      if (!Number(this.courseId)) return '#';
      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      return url.toString();
    },

    /** Course settings page (name, description, enroll options). */
    courseSettingsUrl() {
      if (!Number(this.courseId)) return '#';
      const base = config.urls?.edit || 'admin.php?page=mint-lms-course-edit';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      return url.toString();
    },

    goToCourseSettings(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      const href = this.courseSettingsUrl();
      if (href && href !== '#') {
        window.location.assign(href);
      }
    },

    goToParentCourse(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      const href = this.parentCourseEditorUrl();
      if (href && href !== '#') {
        window.location.assign(href);
      }
    },

    /** Owning lesson title for quiz/question chrome (sidebar + breadcrumbs). */
    breadcrumbParentLessonTitle() {
      const lesson = this.selectedLesson;
      if (lesson?.title) {
        return String(lesson.title).trim();
      }
      const lessonId = Number(this.selected?.lessonId || 0);
      if (lessonId > 0) {
        const row = this.courseQuizzes.find((item) => Number(item.lessonId) === lessonId);
        if (row?.lessonTitle) {
          return String(row.lessonTitle).trim();
        }
      }
      return '';
    },

    /** Prefer lesson → quiz hierarchy; fall back to section in course builder. */
    breadcrumbQuizContextTitle() {
      return this.breadcrumbParentLessonTitle() || this.breadcrumbSectionTitle();
    },

    breadcrumbParentLessonId() {
      const fromSelected = Number(this.selected?.lessonId || 0);
      if (fromSelected > 0) return fromSelected;
      return Number(this.selectedLesson?.id || this.standaloneLessonId || 0);
    },

    /** Canonical admin URL back to the owning lesson editor. */
    parentLessonEditorUrl() {
      return this.parentLessonEditorUrlFor(this.breadcrumbParentLessonId());
    },

    parentLessonEditorUrlFor(lessonId) {
      const id = Number(lessonId) || 0;
      if (id <= 0) return '#';

      if (this.isStandalone || !Number(this.courseId)) {
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(id));
        return url.toString();
      }

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('lesson_id', String(id));
      return url.toString();
    },

    parentQuizEditorUrl() {
      return this.parentQuizEditorUrlFor(
        this.breadcrumbParentLessonId(),
        Number(this.lessonQuiz?.id || 0)
      );
    },

    parentQuizEditorUrlFor(lessonId, quizId, opts = {}) {
      const lid = Number(lessonId) || 0;
      const qid = Number(quizId) || 0;
      if (lid <= 0) return '#';

      const originBuilder =
        opts.originBuilder === true ||
        (opts.originBuilder !== false && this.fromBuilderOrigin && !this.isStandalone);
      const originLesson =
        opts.originLesson === true ||
        (opts.originLesson !== false && this.fromLessonOrigin && this.isStandalone);

      if (this.isStandalone || !Number(this.courseId)) {
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(lid));
        if (qid > 0) url.searchParams.set('quiz_id', String(qid));
        url.searchParams.set('tab', 'quiz');
        url.searchParams.set('from', 'quizzes');
        if (originLesson) {
          url.searchParams.set('origin', 'lesson');
        }
        return url.toString();
      }

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('lesson_id', String(lid));
      if (qid > 0) url.searchParams.set('quiz_id', String(qid));
      url.searchParams.set('tab', 'quiz');
      url.searchParams.set('from', 'quizzes');
      if (originBuilder) {
        url.searchParams.set('origin', 'builder');
      }
      return url.toString();
    },

    /** Show Lesson parent in quiz/question sidebar (course, or lesson→quiz flow). */
    showSidebarLessonParent() {
      return !this.isStandalone || this.fromLessonOrigin;
    },

    /** Show Quiz parent on question surface (course, quiz→question, or lesson→quiz→question). */
    showSidebarQuizParent() {
      return !this.isStandalone || this.fromQuizOrigin || this.fromLessonOrigin;
    },

    goToParentLesson(event) {
      if (event) {
        event.preventDefault();
      }
      this.navigateToLesson(this.breadcrumbParentLessonId(), this.selected?.sectionId ?? null);
    },

    goToParentQuiz(event) {
      if (event) {
        event.preventDefault();
      }
      const lessonId = this.breadcrumbParentLessonId();
      const quizId = Number(this.lessonQuiz?.id || 0);
      if (lessonId <= 0) return;

      if (this.isStandalone || this.fromQuizzes || this.fromQuestions) {
        const href = this.parentQuizEditorUrlFor(lessonId, quizId);
        if (href && href !== '#') {
          window.location.assign(href);
        }
        return;
      }

      this.selectQuiz(lessonId, this.selected?.sectionId ?? null);
    },

    goToQuizParentLesson(quiz, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (!quiz) return;
      this.navigateToLesson(Number(quiz.lessonId) || 0, quiz.sectionId ?? null);
    },

    goToQuestionParentLesson(question, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (!question) return;
      this.navigateToLesson(Number(question.lessonId) || 0, question.sectionId ?? null);
    },

    goToQuestionParentQuiz(question, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (!question) return;
      const lessonId = Number(question.lessonId) || 0;
      const quizId = Number(question.quizId || this.lessonQuiz?.id || 0);
      if (lessonId <= 0) return;

      if (this.isStandalone || this.fromQuizzes || this.fromQuestions) {
        const href = this.parentQuizEditorUrlFor(lessonId, quizId);
        if (href && href !== '#') {
          window.location.assign(href);
        }
        return;
      }

      this.selectQuiz(lessonId, question.sectionId ?? null);
    },

    navigateToLesson(lessonId, sectionId = null) {
      const id = Number(lessonId) || 0;
      if (id <= 0) return;

      // Dedicated quiz/question surfaces (or standalone) — hard navigate to lesson editor.
      if (this.isStandalone || this.fromQuizzes || this.fromQuestions) {
        const href = this.parentLessonEditorUrlFor(id);
        if (href && href !== '#') {
          window.location.assign(href);
        }
        return;
      }

      // In-course builder: stay in SPA.
      this.selectItem('lesson', id, sectionId);
      this.lessonTab = 'written';
    },

    quizParentLessonTitle(quiz) {
      if (!quiz) return '';
      if (quiz.lessonTitle) {
        return String(quiz.lessonTitle).trim();
      }
      const lessonId = Number(quiz.lessonId || 0);
      if (lessonId <= 0) return '';
      for (const section of this.sections) {
        const lesson = (section.lessons || []).find((item) => item.id === lessonId);
        if (lesson?.title) {
          return String(lesson.title).trim();
        }
      }
      return '';
    },

    questionParentLessonTitle(question) {
      if (!question) return '';
      if (question.lessonTitle) {
        return String(question.lessonTitle).trim();
      }
      return this.quizParentLessonTitle({
        lessonId: question.lessonId,
        lessonTitle: '',
      });
    },

    questionParentQuizTitle(question) {
      if (!question) return '';
      if (question.quizTitle) {
        return String(question.quizTitle).trim();
      }
      if (this.lessonQuiz?.title && Number(question.quizId || 0) === Number(this.lessonQuiz.id || 0)) {
        return String(this.lessonQuiz.title).trim();
      }
      return 'Quiz';
    },

    breadcrumbLessonLabel() {
      if (!this.selected || this.selected.type !== 'lesson') return '';
      if (this.isStandalone) return 'Lesson';
      let index = 0;
      for (const section of this.sections) {
        for (const lesson of section.lessons) {
          index += 1;
          if (lesson.id === this.selected.id) {
            return `Lesson ${index}`;
          }
        }
      }
      return 'Lesson';
    },

    breadcrumbQuizLabel() {
      if (!this.lessonQuiz?.title) return 'Quiz';
      return this.lessonQuiz.title;
    },

    async addFirstLesson() {
      await this.createSectionWithLesson();
    },

    /**
     * Empty-state / first lesson: ensure a section exists, create a lesson, open lesson editor.
     * Never leaves the user on the section screen.
     */
    async createSectionWithLesson() {
      if (this.addingLesson) return;
      this.addingLesson = true;
      try {
        let sectionId = Number(this.sections[0]?.id || 0);

        if (!sectionId) {
          const section = await mintApi(`courses/${this.courseId}/sections`, {
            method: 'POST',
            body: JSON.stringify({ title: 'New Section' }),
          });
          sectionId = Number(section?.id || 0);
          if (!sectionId) {
            throw new Error('Could not create section');
          }
          this.sections.push({
            ...section,
            id: sectionId,
            lessons: [],
          });
          this.expanded[sectionId] = true;
        } else {
          this.expanded[sectionId] = true;
        }

        const lesson = await mintApi(`sections/${sectionId}/lessons`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Lesson', content: '', is_preview: false }),
        });
        const lessonId = Number(lesson?.id || 0);
        if (!lessonId) {
          throw new Error('Could not create lesson');
        }

        const section = this.sections.find((s) => Number(s.id) === sectionId);
        if (section) {
          if (!Array.isArray(section.lessons)) section.lessons = [];
          section.lessons.push({ ...lesson, id: lessonId });
        }

        // Always open the lesson editor (not the section screen).
        this.selectItem('lesson', lessonId, sectionId);
        await this.$nextTick();
        this.initSortables();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create lesson');
      } finally {
        this.addingLesson = false;
        this.addingSection = false;
      }
    },

    startEdit(type, id, value) {
      this.editingKey = `${type}-${id}`;
      this.editingValue = value;
      this.$nextTick(() => {
        const input = this.$refs.editInput;
        if (input) input.focus();
      });
    },

    cancelEdit() {
      this.editingKey = '';
      this.editingValue = '';
    },

    async commitEdit(type, id) {
      if (!this.editingKey) return;
      const value = this.editingValue.trim();
      this.cancelEdit();
      if (!value) return;

      if (type === 'section') {
        const section = this.sections.find((s) => s.id === id);
        if (section) {
          section.title = value;
          await this.saveSection(section);
        }
      } else if (type === 'lesson') {
        for (const section of this.sections) {
          const lesson = section.lessons.find((l) => l.id === id);
          if (lesson) {
            lesson.title = value;
            await this.saveLesson(lesson);
            break;
          }
        }
      }
    },

    setSaving() {
      this.saveStatus = 'saving';
    },

    setSaved() {
      this.saveStatus = 'saved';
      setTimeout(() => {
        if (this.saveStatus === 'saved') this.saveStatus = '';
      }, 2000);
    },

    async addSection() {
      if (this.addingSection) return;
      this.addingSection = true;
      try {
        const section = await mintApi(`courses/${this.courseId}/sections`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Section' }),
        });
        const sectionId = Number(section?.id || 0);
        if (!sectionId) {
          throw new Error('Could not create section');
        }
        this.sections.push({ ...section, id: sectionId, lessons: [] });
        this.expanded[sectionId] = true;
        // Add section → section editor only (no lesson).
        this.selectItem('section', sectionId);
        this.$nextTick(() => this.initSortables());
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create section');
      } finally {
        this.addingSection = false;
      }
    },

    async addLesson(sectionId) {
      if (this.addingLesson) return;
      this.addingLesson = true;
      try {
        const sid = Number(sectionId || 0);
        if (!sid) {
          await this.createSectionWithLesson();
          return;
        }
        const lesson = await mintApi(`sections/${sid}/lessons`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Lesson', content: '', is_preview: false }),
        });
        const lessonId = Number(lesson?.id || 0);
        if (!lessonId) {
          throw new Error('Could not create lesson');
        }
        const section = this.sections.find((s) => Number(s.id) === sid);
        if (section) {
          if (!Array.isArray(section.lessons)) section.lessons = [];
          section.lessons.push({ ...lesson, id: lessonId });
          this.expanded[sid] = true;
          // Add lesson → lesson editor.
          this.selectItem('lesson', lessonId, sid);
          this.$nextTick(() => this.initSortables());
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create lesson');
      } finally {
        this.addingLesson = false;
      }
    },

    async saveSection(section, { toast = false } = {}) {
      if (!section) return;
      this.setSaving();
      try {
        await mintApi(`sections/${section.id}`, {
          method: 'PATCH',
          body: JSON.stringify({ title: section.title }),
        });
        this.setSaved();
        if (toast) {
          window.MintLMS.toast.success('Section saved');
        }
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
      }
    },

    async saveLesson(lesson, options = {}) {
      this.setSaving();
      if (this.selectedLesson?.id === lesson.id) {
        this.pullEditorContent();
      }
      try {
        await mintApi(`lessons/${lesson.id}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: lesson.title,
            content: lesson.content || '',
            video_url: lesson.videoUrl || '',
            attachment_id: lesson.attachmentId || null,
            // Send 0 (not omit/null-only) so clear survives REST null handling.
            featured_image_id:
              lesson.featuredImageId && Number(lesson.featuredImageId) > 0
                ? Number(lesson.featuredImageId)
                : 0,
            is_preview: !!lesson.isPreview,
            available_after_days: lesson.availableAfterDays || null,
          }),
        });
        this.setSaved();
        if (options.toast) {
          window.MintLMS.toast.success('Lesson saved');
        }
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteSection(sectionId) {
      if (!window.confirm('Delete this section and all its lessons?')) return;
      try {
        await mintApi(`sections/${sectionId}`, { method: 'DELETE' });
        this.sections = this.sections.filter((s) => s.id !== sectionId);
        if (this.selected?.type === 'section' && this.selected.id === sectionId) {
          this.selected = null;
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteLesson(lessonId, sectionId) {
      if (!window.confirm('Delete this lesson?')) return;
      try {
        await mintApi(`lessons/${lessonId}`, { method: 'DELETE' });
        const section = this.sections.find((s) => s.id === sectionId);
        if (section) {
          section.lessons = section.lessons.filter((l) => l.id !== lessonId);
        }
        if (this.selected?.type === 'lesson' && this.selected.id === lessonId) {
          this.selected = null;
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    moveSection(index, direction) {
      const newIndex = index + direction;
      if (newIndex < 0 || newIndex >= this.sections.length) return;
      const items = [...this.sections];
      const [item] = items.splice(index, 1);
      items.splice(newIndex, 0, item);
      this.sections = items;
      this.persistSectionOrder();
    },

    moveLesson(sectionId, index, direction) {
      const section = this.sections.find((s) => s.id === sectionId);
      if (!section) return;
      const newIndex = index + direction;
      if (newIndex < 0 || newIndex >= section.lessons.length) return;
      const items = [...section.lessons];
      const [item] = items.splice(index, 1);
      items.splice(newIndex, 0, item);
      section.lessons = items;
      this.persistLessonOrder(sectionId);
    },

    async persistSectionOrder() {
      const ids = this.sections.map((s) => s.id);
      try {
        await mintApi(`courses/${this.courseId}/sections/reorder`, {
          method: 'POST',
          body: JSON.stringify({ ids }),
        });
      } catch (err) {
        window.MintLMS.toast.error(err.message);
        await this.loadStructure();
      }
    },

    async persistLessonOrder(sectionId) {
      const section = this.sections.find((s) => s.id === sectionId);
      if (!section) return;
      const ids = section.lessons.map((l) => l.id);
      try {
        await mintApi(`sections/${sectionId}/lessons/reorder`, {
          method: 'POST',
          body: JSON.stringify({ ids }),
        });
      } catch (err) {
        window.MintLMS.toast.error(err.message);
        await this.loadStructure();
      }
    },

    togglePreview() {
      if (!this.selectedLesson) return;
      this.selectedLesson.isPreview = !this.selectedLesson.isPreview;
      this.saveLesson(this.selectedLesson);
    },

    pickAttachment() {
      if (typeof wp === 'undefined' || !wp.media) {
        window.MintLMS.toast.error('Media library unavailable');
        return;
      }
      const frame = wp.media({
        title: 'Choose attachment',
        button: { text: 'Select' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        if (this.selectedLesson) {
          this.selectedLesson.attachmentId = attachment.id;
          this.saveLesson(this.selectedLesson);
        }
      });
      frame.open();
    },

    clearAttachment() {
      if (!this.selectedLesson) return;
      this.selectedLesson.attachmentId = null;
      this.saveLesson(this.selectedLesson);
    },

    pickLessonFeaturedImage() {
      if (!this.selectedLesson || !window.wp?.media) return;

      const frame = window.wp.media({
        title: 'Set featured image',
        button: { text: 'Use this image' },
        multiple: false,
        library: { type: 'image' },
      });

      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.id || !this.selectedLesson) return;

        this.selectedLesson.featuredImageId = attachment.id;
        this.selectedLesson.featuredImageUrl =
          attachment.url || attachment.sizes?.large?.url || attachment.sizes?.full?.url || '';
        this.saveLesson(this.selectedLesson);
      });

      frame.open();
    },

    clearLessonFeaturedImage() {
      if (!this.selectedLesson) return;
      this.selectedLesson.featuredImageId = null;
      this.selectedLesson.featuredImageUrl = '';
      this.saveLesson(this.selectedLesson);
    },

    async publishCourse() {
      this.publishing = true;
      try {
        const course = await mintApi(`courses/${this.courseId}/publish`, { method: 'POST' });
        this.course.status = course.status;
        this.rememberCoursesTab(course.status);
        this.rememberOriginTab(course.status);
        window.MintLMS.toast.success('Course is live');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.publishing = false;
      }
    },

    statusTabFor(status) {
      if (status === 'published') return 'published';
      if (status === 'archived') return 'archived';
      if (status === 'trashed') return 'trashed';
      return 'draft';
    },

    coursesTabForStatus(status) {
      return this.statusTabFor(status);
    },

    originDashboardKey() {
      if (this.fromQuestions) return 'questions';
      if (this.fromQuizzes) return 'quizzes';
      if (this.fromLessons) return 'lessons';
      return 'courses';
    },

    rememberListTab(kind, status) {
      try {
        sessionStorage.setItem(`mint_lms_${kind}_status`, this.statusTabFor(status));
      } catch {
        // Private mode / blocked storage — URL back-links still work.
      }
    },

    rememberCoursesTab(status) {
      this.rememberListTab('courses', status);
    },

    rememberOriginTab(status = null) {
      const resolved = status || this.course?.status || 'draft';
      this.rememberListTab(this.originDashboardKey(), resolved);
      // Always keep courses tab in sync too (Open builder / settings flows).
      this.rememberListTab('courses', resolved);
    },

    listDashboardUrl(kind) {
      const urls = config.urls || {};
      const map = {
        courses: urls.courses,
        lessons: urls.lessons,
        quizzes: urls.quizzes,
        questions: urls.questions,
      };
      const base = String(map[kind] || '');
      if (!base) return '#';
      const tab = this.statusTabFor(this.course?.status);
      const joiner = base.includes('?') ? '&' : '?';
      return `${base}${joiner}status=${encodeURIComponent(tab)}`;
    },

    coursesDashboardUrl() {
      return this.listDashboardUrl('courses');
    },

    headerPrimaryDisabled() {
      if (this.publishing || this.saveStatus === 'saving' || this.quizSaving) {
        return true;
      }

      if (this.selected?.type === 'lesson') {
        return !this.selectedLesson;
      }

      if (this.selected?.type === 'quiz') {
        return !this.lessonQuiz;
      }

      if (this.selected?.type === 'question') {
        return !this.activeQuestion;
      }

      return this.course.status === 'published';
    },

    headerSaveDisabled() {
      if (this.publishing || this.saveStatus === 'saving' || this.quizSaving) {
        return true;
      }
      if (this.selected?.type === 'lesson') return !this.selectedLesson;
      if (this.selected?.type === 'quiz') return !this.lessonQuiz;
      if (this.selected?.type === 'question') return !this.activeQuestion;
      // Section / course chrome: nothing required beyond loaded course.
      return !this.courseId && !this.isStandalone;
    },

    headerLiveDisabled() {
      if (!this.headerShowsLive()) {
        return true;
      }
      if (this.publishing || this.saveStatus === 'saving' || this.quizSaving) {
        return true;
      }
      if (this.selected?.type === 'lesson') {
        return !this.selectedLesson;
      }
      if (this.selected?.type === 'quiz') {
        return !this.lessonQuiz;
      }
      if (this.selected?.type === 'question') {
        return !this.activeQuestion;
      }
      return false;
    },

    headerShowsLive() {
      if (this.selected?.type === 'lesson' || this.selected?.type === 'quiz' || this.selected?.type === 'question') {
        return this.contentPrimaryIsPublish();
      }
      return this.course?.status !== 'published';
    },

    /**
     * Lesson still draft (or course not live yet) → Live available.
     * Already live lesson on live course → Save changes only.
     */
    lessonPrimaryIsPublish() {
      if (this.course?.status !== 'published') {
        return true;
      }
      return (this.selectedLesson?.status || 'draft') !== 'published';
    },

    contentPrimaryIsPublish() {
      if (this.selected?.type === 'question') {
        return (this.activeQuestion?.status || 'draft') !== 'published';
      }
      if (this.selected?.type === 'quiz') {
        return (this.lessonQuiz?.status || 'draft') !== 'published';
      }
      if (this.selected?.type === 'lesson') {
        return this.lessonPrimaryIsPublish();
      }
      return this.course?.status !== 'published';
    },

    async publishContentItem(kind, id) {
      if (!id) return null;
      return mintApi(`content/${kind}/${id}/publish`, { method: 'POST' });
    },

    async headerSaveAction() {
      if (this.selected?.type === 'question' && this.activeQuestion) {
        await this.saveActiveQuestion();
        return;
      }
      if (this.selected?.type === 'quiz' && this.lessonQuiz) {
        await this.saveQuiz();
        return;
      }
      if (this.selected?.type === 'lesson' && this.selectedLesson) {
        await this.saveLesson(this.selectedLesson, { toast: true });
        return;
      }
      if (this.selected?.type === 'section' && this.selectedSection) {
        await this.saveSection(this.selectedSection, { toast: true });
        return;
      }
      window.MintLMS.toast.info('Nothing to save here');
    },

    async headerLiveAction() {
      if (!this.headerShowsLive()) return;

      if (this.selected?.type === 'question' && this.activeQuestion) {
        await this.saveActiveQuestion();
        if (this.activeQuestion?.id) {
          const published = await this.publishContentItem('questions', this.activeQuestion.id);
          if (published?.status) this.activeQuestion.status = published.status;
        }
        if (this.course?.status !== 'published') {
          await this.publishCourse();
        } else {
          window.MintLMS.toast.success('Question is live');
        }
        this.rememberOriginTab('published');
        return;
      }

      if (this.selected?.type === 'quiz' && this.lessonQuiz) {
        await this.saveQuiz();
        if (this.lessonQuiz?.id) {
          const published = await this.publishContentItem('quizzes', this.lessonQuiz.id);
          if (published?.status) this.lessonQuiz.status = published.status;
        }
        if (this.course?.status !== 'published') {
          await this.publishCourse();
        } else {
          window.MintLMS.toast.success('Quiz is live');
        }
        this.rememberOriginTab('published');
        return;
      }

      if (this.selected?.type === 'lesson' && this.selectedLesson) {
        await this.saveLesson(this.selectedLesson, { toast: false });
        if (this.selectedLesson?.id) {
          const published = await this.publishContentItem('lessons', this.selectedLesson.id);
          if (published?.status) this.selectedLesson.status = published.status;
        }
        if (this.course?.status !== 'published') {
          await this.publishCourse();
        } else {
          window.MintLMS.toast.success('Lesson is live');
        }
        this.rememberOriginTab('published');
        return;
      }

      await this.publishCourse();
    },

    async headerPrimaryAction() {
      // Back-compat: prefer Live when shown, otherwise save.
      if (this.headerShowsLive()) {
        await this.headerLiveAction();
        return;
      }
      await this.headerSaveAction();
    },

    async loadLessonQuiz(lessonId) {
      this.quizLoading = true;
      this.lessonQuiz = null;
      try {
        const quiz = await mintApi(`lessons/${lessonId}/quiz`);
        this.lessonQuiz = this.normalizeQuiz(quiz);
        this.syncCourseQuizSidebarRow();
        this.syncSidebarQuestionsFromLessonQuiz();
      } catch (err) {
        this.lessonQuiz = this.emptyQuizState();
      } finally {
        this.quizLoading = false;
      }
    },

    /** Keep courseQuestions in sync so quiz sidebar can list Lesson → Quiz → Question. */
    syncSidebarQuestionsFromLessonQuiz() {
      if (!this.lessonQuiz?.id) return;
      const quizId = Number(this.lessonQuiz.id);
      const lessonId =
        Number(this.selected?.lessonId || this.selectedLesson?.id || this.standaloneLessonId || 0) ||
        Number(this.courseQuizzes.find((q) => Number(q.id) === quizId)?.lessonId || 0);
      const lessonTitle =
        this.breadcrumbParentLessonTitle() ||
        this.courseQuizzes.find((q) => Number(q.id) === quizId)?.lessonTitle ||
        '';
      const quizTitle = this.lessonQuiz.title || 'New Quiz';
      const sectionId = this.selected?.sectionId ?? this.courseQuizzes.find((q) => Number(q.id) === quizId)?.sectionId ?? 0;

      const rows = (this.lessonQuiz.questions || [])
        .filter((question) => Number(question?.id || 0) > 0)
        .map((question) => {
          const settings = question.settings && typeof question.settings === 'object' ? question.settings : {};
          const displayTitle = (settings.displayTitle || question.title || '').trim();
          const prompt = String(question.prompt || '').trim();
          return {
            id: Number(question.id),
            title:
              displayTitle ||
              (prompt && prompt !== 'New Question' ? prompt : '') ||
              'New Question',
            prompt: question.prompt || '',
            type: question.type,
            quizId,
            lessonId,
            lessonTitle,
            quizTitle,
            sectionId,
            settings,
          };
        });

      // Replace rows for this quiz; keep other quizzes' questions intact.
      this.courseQuestions = [
        ...this.courseQuestions.filter((item) => Number(item.quizId) !== quizId),
        ...rows,
      ];
    },

    sidebarQuestionsForQuiz(quiz) {
      if (!quiz) return [];
      const quizId = Number(quiz.id || 0);
      if (quizId <= 0) return [];

      if (this.lessonQuiz?.id && Number(this.lessonQuiz.id) === quizId) {
        return (this.lessonQuiz.questions || [])
          .filter((question) => Number(question?.id || 0) > 0)
          .map((question) => {
            const settings = question.settings && typeof question.settings === 'object' ? question.settings : {};
            const displayTitle = (settings.displayTitle || question.title || '').trim();
            const prompt = String(question.prompt || '').trim();
            return {
              id: Number(question.id),
              title:
                displayTitle ||
                (prompt && prompt !== 'New Question' ? prompt : '') ||
                'New Question',
              lessonId: quiz.lessonId,
              quizId,
              sectionId: quiz.sectionId || null,
            };
          });
      }

      return (this.courseQuestions || []).filter((item) => Number(item.quizId) === quizId);
    },

    async openSidebarQuestion(question, quiz) {
      if (!question?.id) return;
      const lessonId = Number(question.lessonId || quiz?.lessonId || 0);
      const quizId = Number(question.quizId || quiz?.id || this.lessonQuiz?.id || 0);
      const questionId = Number(question.id);

      if (this.isStandalone || this.fromQuizzes || this.fromQuestions) {
        const href = this.questionEditorUrlFor(lessonId, quizId, questionId);
        if (href && href !== '#') {
          window.location.assign(href);
        }
        return;
      }

      await this.selectQuestion(questionId, lessonId, quiz?.sectionId ?? null);
    },

    sidebarQuizzesForLesson(lesson) {
      const lessonId = Number(lesson?.id || 0);
      if (lessonId <= 0) return [];
      return (this.courseQuizzes || []).filter((quiz) => Number(quiz.lessonId) === lessonId);
    },

    sidebarHierarchyQuizzes() {
      let quizzes = Array.isArray(this.courseQuizzes) ? [...this.courseQuizzes] : [];
      if (this.fromBuilderOrigin && !this.isStandalone) {
        const lessonId = this.breadcrumbParentLessonId();
        if (lessonId > 0) {
          quizzes = quizzes.filter((quiz) => Number(quiz.lessonId) === lessonId);
        }
      }
      if (quizzes.length) {
        const q = (this.questionSearch || '').trim().toLowerCase();
        if (!q || !this.fromQuestions) return quizzes;
        return quizzes.filter((quiz) => this.sidebarQuestionsForQuizFiltered(quiz).length > 0);
      }

      // Derive quiz groups from question rows when courseQuizzes is empty.
      const map = new Map();
      for (const question of this.courseQuestions || []) {
        const quizId = Number(question.quizId || 0);
        if (quizId <= 0 || map.has(quizId)) continue;
        if (this.fromBuilderOrigin && !this.isStandalone) {
          const lessonId = this.breadcrumbParentLessonId();
          if (lessonId > 0 && Number(question.lessonId) !== lessonId) continue;
        }
        map.set(quizId, {
          id: quizId,
          title: question.quizTitle || 'New Quiz',
          lessonId: question.lessonId,
          lessonTitle: question.lessonTitle || '',
          sectionId: question.sectionId || 0,
        });
      }
      return Array.from(map.values());
    },

    sidebarQuestionsForQuizFiltered(quiz) {
      const rows = this.sidebarQuestionsForQuiz(quiz);
      const q = (this.questionSearch || '').trim().toLowerCase();
      if (!q) return rows;
      return rows.filter((item) => (item.title || '').toLowerCase().includes(q));
    },

    async openSidebarQuiz(quiz, lesson, sectionId = null) {
      if (!quiz) return;
      const lessonId = Number(quiz.lessonId || lesson?.id || 0);
      const quizId = Number(quiz.id || 0);
      if (lessonId <= 0) return;

      // Already on course quiz surface — switch in-page.
      if (this.fromQuizzes && !this.isStandalone) {
        await this.selectQuiz(lessonId, sectionId);
        return;
      }

      // Contents tree / standalone: open dedicated quiz URL.
      if (quizId > 0) {
        const href = this.parentQuizEditorUrlFor(lessonId, quizId, {
          originBuilder: !this.isStandalone && Number(this.courseId) > 0,
          originLesson: this.isStandalone,
        });
        if (href && href !== '#') {
          window.location.assign(href);
          return;
        }
      }

      await this.openLessonQuizEditor(lessonId, sectionId);
    },

    emptyQuizSettings() {
      return {
        restrictRetakes: false,
        retriesAllowed: 0,
        retriesApplicableTo: 'all',
        questionCompletion: false,
        timeLimitEnabled: false,
        timeLimitHours: '00',
        timeLimitMinutes: '00',
        timeLimitSeconds: '00',
      };
    },

    emptyQuizState() {
      return {
        id: null,
        title: 'New Quiz',
        passPercent: 80,
        questions: [],
        featuredImageId: null,
        featuredImageUrl: '',
        settings: this.emptyQuizSettings(),
        status: 'draft',
      };
    },

    normalizeQuiz(quiz) {
      const empty = this.emptyQuizState();
      if (!quiz) return empty;
      const questions = Array.isArray(quiz.questions)
        ? quiz.questions.map((question) => {
            const type = this.normalizeQuestionType(question.type);
            return {
              ...question,
              type,
              status: question.status || 'draft',
              correctAnswers:
                type === 'mcq_multi' ? this.extractCorrectAnswers(question) : Array.isArray(question.correctAnswers) ? question.correctAnswers : [],
              settings: this.normalizeQuestionSettings(
                question.settings,
                Array.isArray(question.options) ? question.options.length : 2
              ),
            };
          })
        : [];
      return {
        ...empty,
        ...quiz,
        title: quiz.title || empty.title,
        passPercent: quiz.passPercent ?? empty.passPercent,
        questions,
        featuredImageId: quiz.featuredImageId || null,
        featuredImageUrl: quiz.featuredImageUrl || '',
        status: quiz.status || 'draft',
        settings: {
          ...empty.settings,
          ...(quiz.settings && typeof quiz.settings === 'object' ? quiz.settings : {}),
        },
      };
    },

    async createQuiz(options = {}) {
      if (!this.selectedLesson) return;
      this.quizSaving = true;
      try {
        const quiz = await mintApi(`lessons/${this.selectedLesson.id}/quiz`, {
          method: 'POST',
          body: JSON.stringify({
            title: this.lessonQuiz?.title || 'New Quiz',
            pass_percent: this.lessonQuiz?.passPercent || 80,
          }),
        });
        this.lessonQuiz = this.normalizeQuiz({
          ...quiz,
          settings: this.lessonQuiz?.settings,
          featuredImageId: this.lessonQuiz?.featuredImageId,
          featuredImageUrl: this.lessonQuiz?.featuredImageUrl,
        });
        this.syncCourseQuizSidebarRow();
        if (!options.silent) {
          window.MintLMS.toast.success('Quiz created');
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async saveQuiz() {
      if (!this.lessonQuiz?.id) {
        await this.createQuiz();
        if (!this.lessonQuiz?.id) return;
      }
      this.quizSaving = true;
      this.saveStatus = 'saving';
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: this.lessonQuiz.title,
            pass_percent: this.lessonQuiz.passPercent,
            featured_image_id: this.lessonQuiz.featuredImageId || 0,
            settings: this.lessonQuiz.settings || this.emptyQuizSettings(),
          }),
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
        this.saveStatus = 'saved';
        this.syncCourseQuizTitle();
        if (this.fromQuizzes) {
          await this.refreshQuizSidebar();
        }
        window.MintLMS.toast.success('Quiz saved');
        setTimeout(() => {
          if (this.saveStatus === 'saved') this.saveStatus = 'idle';
        }, 1600);
      } catch (err) {
        this.saveStatus = 'idle';
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async deleteQuiz() {
      if (!this.lessonQuiz?.id || !window.confirm('Delete this quiz and all questions?')) return;
      try {
        await mintApi(`quizzes/${this.lessonQuiz.id}`, { method: 'DELETE' });
        this.lessonQuiz = this.emptyQuizState();
        if (this.fromQuizzes) {
          await this.refreshQuizSidebar();
          if (this.courseQuizzes.length > 0) {
            const next = this.courseQuizzes[0];
            await this.selectQuiz(next.lessonId, next.sectionId || null);
          } else if (this.isStandalone && this.standaloneLessonId) {
            this.navigateToLesson(this.standaloneLessonId, 0);
          } else {
            this.selected = null;
          }
        } else if (this.selected?.type === 'quiz' && this.selectedLesson) {
          this.selectItem('lesson', this.selectedLesson.id, this.selected.sectionId);
        }
        window.MintLMS.toast.success('Quiz deleted');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    pickQuizFeaturedImage() {
      if (!this.lessonQuiz || !window.wp?.media) return;
      const frame = window.wp.media({
        title: 'Set quiz featured image',
        button: { text: 'Use image' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.id || !this.lessonQuiz) return;
        this.lessonQuiz.featuredImageId = attachment.id;
        this.lessonQuiz.featuredImageUrl =
          attachment.sizes?.large?.url || attachment.sizes?.full?.url || attachment.url || '';
      });
      frame.open();
    },

    clearQuizFeaturedImage() {
      if (!this.lessonQuiz) return;
      this.lessonQuiz.featuredImageId = null;
      this.lessonQuiz.featuredImageUrl = '';
    },

    pickQuestionFeaturedImage() {
      if (!this.activeQuestion || !window.wp?.media) return;
      const frame = window.wp.media({
        title: 'Set question featured image',
        button: { text: 'Use image' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.id || !this.activeQuestion) return;
        this.activeQuestion.featuredImageId = attachment.id;
        this.activeQuestion.featuredImageUrl =
          attachment.sizes?.large?.url || attachment.sizes?.full?.url || attachment.url || '';
      });
      frame.open();
    },

    clearQuestionFeaturedImage() {
      if (!this.activeQuestion) return;
      this.activeQuestion.featuredImageId = null;
      this.activeQuestion.featuredImageUrl = '';
    },

    clampTimePart(value) {
      const digits = String(value ?? '').replace(/\D/g, '').slice(0, 2);
      if (digits === '') return '00';
      return digits.padStart(2, '0');
    },

    addQuizQuestion(type = 'mcq', { select = false } = {}) {
      if (!this.lessonQuiz) this.lessonQuiz = this.emptyQuizState();
      const normalizedType = this.normalizeQuestionType(type);
      const question = {
        _key: Date.now(),
        type: normalizedType,
        title: 'New Question',
        prompt: '',
        options: normalizedType === 'true_false' ? ['True', 'False'] : normalizedType === 'essay' ? [] : ['Option 1', 'Option 2'],
        correctAnswer: '',
        correctAnswers: [],
        featuredImageId: null,
        featuredImageUrl: '',
        settings: this.normalizeQuestionSettings(null, normalizedType === 'essay' ? 0 : 2),
        sortOrder: this.lessonQuiz.questions.length,
      };
      this.lessonQuiz.questions.push(question);
      if (select || this.fromQuestions || this.selected?.type === 'question') {
        this.selected = {
          type: 'question',
          id: 0,
          questionId: 0,
          lessonId: this.selected?.lessonId || this.lessonQuiz.lessonId || null,
          sectionId: this.selected?.sectionId || null,
        };
        this.activeQuestion = this.normalizeActiveQuestion(question);
        this.questionAnswerReady = normalizedType === 'true_false' || normalizedType === 'essay';
      }
    },

    addMcqOption(question) {
      if (question.options.length >= 6) return;
      question.options.push(`Option ${question.options.length + 1}`);
      if (!question.settings) {
        question.settings = this.normalizeQuestionSettings(null, question.options.length);
      } else {
        if (!Array.isArray(question.settings.allowHtml)) question.settings.allowHtml = [];
        question.settings.allowHtml.push(false);
      }
    },

    removeMcqOption(question, index) {
      if (question.options.length <= 2) return;
      const removed = question.options[index];
      question.options.splice(index, 1);
      if (question.settings && Array.isArray(question.settings.allowHtml)) {
        question.settings.allowHtml.splice(index, 1);
      }
      if (question.type === 'mcq_multi' && Array.isArray(question.correctAnswers)) {
        question.correctAnswers = question.correctAnswers.filter((answer) => answer !== removed);
      } else if (question.correctAnswer === removed) {
        question.correctAnswer = '';
      }
    },

    moveMcqOption(question, index, direction) {
      const target = index + direction;
      if (!question?.options || target < 0 || target >= question.options.length) return;
      const options = question.options;
      const tmp = options[index];
      options[index] = options[target];
      options[target] = tmp;
      if (question.settings && Array.isArray(question.settings.allowHtml)) {
        const allow = question.settings.allowHtml;
        while (allow.length < options.length) allow.push(false);
        const allowTmp = allow[index];
        allow[index] = allow[target];
        allow[target] = allowTmp;
      }
    },

    toggleOptionAllowHtml(index, checked) {
      if (!this.activeQuestion?.settings) return;
      if (!Array.isArray(this.activeQuestion.settings.allowHtml)) {
        this.activeQuestion.settings.allowHtml = [];
      }
      while (this.activeQuestion.settings.allowHtml.length <= index) {
        this.activeQuestion.settings.allowHtml.push(false);
      }
      this.activeQuestion.settings.allowHtml[index] = !!checked;
    },

    wrapQuestionPrompt(before, after = '') {
      const textarea = document.getElementById('mint-question-prompt');
      if (!textarea || !this.activeQuestion) return;
      const start = textarea.selectionStart ?? 0;
      const end = textarea.selectionEnd ?? 0;
      const value = textarea.value || '';
      const selected = value.slice(start, end);
      const next = `${value.slice(0, start)}${before}${selected}${after}${value.slice(end)}`;
      this.activeQuestion.prompt = next;
      textarea.value = next;
      requestAnimationFrame(() => {
        textarea.focus();
        const cursorStart = start + before.length;
        const cursorEnd = selected ? cursorStart + selected.length : cursorStart;
        textarea.setSelectionRange(cursorStart, cursorEnd);
      });
    },

    applyQuestionFormat(option) {
      this.questionFormatLabel = option.label;
      this.questionFormatMenuOpen = false;
      if (!option?.value || option.value === 'p') {
        this.wrapQuestionPrompt('<p>', '</p>');
        return;
      }
      this.wrapQuestionPrompt(`<${option.value}>`, `</${option.value}>`);
    },

    insertQuestionLink() {
      const url = window.prompt('Enter URL');
      if (!url) return;
      const textarea = document.getElementById('mint-question-prompt');
      const selected = textarea
        ? (textarea.value || '').slice(textarea.selectionStart || 0, textarea.selectionEnd || 0)
        : '';
      if (selected) {
        this.wrapQuestionPrompt(`<a href="${url}">`, '</a>');
      } else {
        this.wrapQuestionPrompt(`<a href="${url}">${url}</a>`, '');
      }
    },

    insertQuestionCodeTag(tag) {
      if (tag === 'link') {
        this.insertQuestionLink();
        return;
      }
      if (tag === 'img') {
        this.addMediaToQuestionPrompt();
        return;
      }
      if (tag === 'close tags') return;
      const pairs = {
        b: ['<strong>', '</strong>'],
        i: ['<em>', '</em>'],
        'b-quote': ['<blockquote>', '</blockquote>'],
        del: ['<del>', '</del>'],
        ins: ['<ins>', '</ins>'],
        ul: ['<ul><li>', '</li></ul>'],
        ol: ['<ol><li>', '</li></ol>'],
        li: ['<li>', '</li>'],
        code: ['<code>', '</code>'],
        more: ['<!--more-->', ''],
      };
      const pair = pairs[tag];
      if (pair) this.wrapQuestionPrompt(pair[0], pair[1]);
    },

    addMediaToQuestionPrompt() {
      if (!window.wp?.media || !this.activeQuestion) return;
      const frame = window.wp.media({
        title: 'Add media',
        button: { text: 'Insert' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.url || !this.activeQuestion) return;
        if (attachment.type === 'image') {
          const alt = attachment.alt || attachment.title || '';
          this.wrapQuestionPrompt(`<img src="${attachment.url}" alt="${alt}" />`, '');
        } else {
          const label = attachment.title || attachment.filename || 'Download';
          this.wrapQuestionPrompt(`<a href="${attachment.url}">${label}</a>`, '');
        }
      });
      frame.open();
    },

    addMediaToQuestionOption(index) {
      if (!window.wp?.media || !this.activeQuestion) return;
      const frame = window.wp.media({
        title: 'Add media to answer',
        button: { text: 'Insert' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.url || !this.activeQuestion?.options || this.activeQuestion.options[index] === undefined) return;
        const current = this.activeQuestion.options[index] || '';
        const label = attachment.title || attachment.filename || attachment.url;
        const snippet =
          attachment.type === 'image'
            ? `<img src="${attachment.url}" alt="${attachment.alt || label}" />`
            : `<a href="${attachment.url}">${label}</a>`;
        this.activeQuestion.options[index] = current ? `${current} ${snippet}` : snippet;
        if (!Array.isArray(this.activeQuestion.settings.allowHtml)) {
          this.activeQuestion.settings.allowHtml = [];
        }
        while (this.activeQuestion.settings.allowHtml.length <= index) {
          this.activeQuestion.settings.allowHtml.push(false);
        }
        this.activeQuestion.settings.allowHtml[index] = true;
      });
      frame.open();
    },

    chooseQuestionAttachment() {
      if (!window.wp?.media || !this.activeQuestion) return;
      const frame = window.wp.media({
        title: 'Choose file',
        button: { text: 'Use file' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.id || !this.activeQuestion) return;
        this.activeQuestion.settings.attachmentId = attachment.id;
        this.activeQuestion.settings.attachmentName = attachment.filename || attachment.title || 'Attachment';
        this.activeQuestion.settings.attachmentUrl = attachment.url || '';
      });
      frame.open();
    },

    setEssaySubmitMethod(method) {
      if (!this.activeQuestion?.settings) return;
      this.activeQuestion.settings.submitMethod = method === 'Upload' ? 'Upload' : 'Text Box';
      this.essaySubmitOpen = false;
    },

    setEssayGradingMode(mode) {
      if (!this.activeQuestion?.settings) return;
      this.activeQuestion.settings.gradingMode = mode;
      this.essayGradingOpen = false;
    },

    bumpEssayPoints(delta) {
      if (!this.activeQuestion?.settings) return;
      const next = Math.max(0, (Number(this.activeQuestion.settings.points) || 0) + delta);
      this.activeQuestion.settings.points = next;
    },

    async addExtraTfAnswer() {
      if (!this.activeQuestion) return;
      if (!Array.isArray(this.activeQuestion.extraTf)) {
        this.activeQuestion.extraTf = [];
      }
      this.activeQuestion.extraTf.push(this.emptyExtraTfAnswer());
    },

    removeExtraTfAnswer(index) {
      if (!this.activeQuestion || !Array.isArray(this.activeQuestion.extraTf)) return;
      this.activeQuestion.extraTf.splice(index, 1);
    },

    deleteBaseTfAnswer() {
      if (!this.activeQuestion) return;
      const extras = Array.isArray(this.activeQuestion.extraTf) ? this.activeQuestion.extraTf : [];
      if (extras.length > 0) {
        const [next, ...remain] = extras;
        this.activeQuestion.prompt = next.prompt || '';
        this.activeQuestion.correctAnswer = next.correctAnswer || '';
        this.activeQuestion.title = (next.prompt || '').trim() || this.activeQuestion.title;
        this.activeQuestion.extraTf = remain;
        if (this.activeQuestion.settings) {
          this.activeQuestion.settings.attachmentId = next.attachmentId || 0;
          this.activeQuestion.settings.attachmentName = next.attachmentName || '';
          this.activeQuestion.settings.attachmentUrl = next.attachmentUrl || '';
          this.activeQuestion.settings.freePreview = !!next.freePreview;
        }
        return;
      }
      this.activeQuestion.correctAnswer = '';
      this.activeQuestion.prompt = '';
    },

    wrapExtraTfPrompt(index, before, after = '') {
      const extra = this.activeQuestion?.extraTf?.[index];
      const textarea = document.getElementById(`mint-extra-tf-prompt-${index}`);
      if (!extra || !textarea) return;
      const start = textarea.selectionStart ?? 0;
      const end = textarea.selectionEnd ?? 0;
      const value = textarea.value || '';
      const selected = value.slice(start, end);
      const next = `${value.slice(0, start)}${before}${selected}${after}${value.slice(end)}`;
      extra.prompt = next;
      textarea.value = next;
      requestAnimationFrame(() => {
        textarea.focus();
        const cursorStart = start + before.length;
        const cursorEnd = selected ? cursorStart + selected.length : cursorStart;
        textarea.setSelectionRange(cursorStart, cursorEnd);
      });
    },

    insertExtraTfLink(index) {
      const url = window.prompt('Enter URL');
      if (!url) return;
      const textarea = document.getElementById(`mint-extra-tf-prompt-${index}`);
      const selected = textarea
        ? (textarea.value || '').slice(textarea.selectionStart || 0, textarea.selectionEnd || 0)
        : '';
      if (selected) {
        this.wrapExtraTfPrompt(index, `<a href="${url}">`, '</a>');
      } else {
        this.wrapExtraTfPrompt(index, `<a href="${url}">${url}</a>`, '');
      }
    },

    addMediaToExtraTfPrompt(index) {
      if (!window.wp?.media || !this.activeQuestion?.extraTf?.[index]) return;
      const frame = window.wp.media({
        title: 'Add media',
        button: { text: 'Insert' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.url || !this.activeQuestion?.extraTf?.[index]) return;
        if (attachment.type === 'image') {
          const alt = attachment.alt || attachment.title || '';
          this.wrapExtraTfPrompt(index, `<img src="${attachment.url}" alt="${alt}" />`, '');
        } else {
          const label = attachment.title || attachment.filename || 'Download';
          this.wrapExtraTfPrompt(index, `<a href="${attachment.url}">${label}</a>`, '');
        }
      });
      frame.open();
    },

    chooseExtraTfAttachment(index) {
      if (!window.wp?.media || !this.activeQuestion?.extraTf?.[index]) return;
      const frame = window.wp.media({
        title: 'Choose file',
        button: { text: 'Use file' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        const extra = this.activeQuestion?.extraTf?.[index];
        if (!attachment?.id || !extra) return;
        extra.attachmentId = attachment.id;
        extra.attachmentName = attachment.filename || attachment.title || 'Attachment';
        extra.attachmentUrl = attachment.url || '';
      });
      frame.open();
    },

    async syncExtraTfQuestions() {
      if (!this.activeQuestion || this.activeQuestion.type !== 'true_false' || !this.lessonQuiz?.id) {
        return;
      }
      const extras = Array.isArray(this.activeQuestion.extraTf) ? this.activeQuestion.extraTf : [];
      for (let i = 0; i < extras.length; i += 1) {
        const extra = extras[i];
        const prompt = (extra.prompt || '').trim();
        if (!prompt || (extra.correctAnswer !== 'true' && extra.correctAnswer !== 'false')) {
          continue;
        }
        const payload = {
          type: 'true_false',
          prompt,
          options: ['True', 'False'],
          correct_answer: extra.correctAnswer,
        };
        try {
          let quiz;
          if (extra.id) {
            quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${extra.id}`, {
              method: 'PATCH',
              body: JSON.stringify(payload),
            });
          } else {
            quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions`, {
              method: 'POST',
              body: JSON.stringify(payload),
            });
          }
          this.lessonQuiz = this.normalizeQuiz(quiz);
          const created = (this.lessonQuiz.questions || []).find(
            (item) =>
              item.type === 'true_false' &&
              (item.id === extra.id || (!extra.id && item.prompt === prompt))
          );
          if (created?.id) {
            extra.id = created.id;
          }
        } catch (err) {
          window.MintLMS.toast.error(err.message || 'Failed to save extra True/False answer');
        }
      }
    },

    async addExtraTrueFalseQuestion() {
      // Kept for compatibility; design adds nested TF answers instead.
      this.addExtraTfAnswer();
    },

    async saveQuizQuestion(question) {
      if (!this.lessonQuiz?.id) {
        await this.createQuiz();
      }
      if (!this.lessonQuiz?.id) return;

      const type = this.normalizeQuestionType(question.type);
      let correctAnswer = question.correctAnswer || '';
      if (type === 'mcq_multi') {
        correctAnswer = Array.isArray(question.correctAnswers) ? question.correctAnswers : [];
      } else if (type === 'essay') {
        correctAnswer = '';
      }

      const settings = this.normalizeQuestionSettings(question.settings, (question.options || []).length);
      settings.displayTitle =
        (question.title || '').trim() || settings.displayTitle || (question.prompt || '').trim();
      if (type === 'true_false') {
        settings.extraTf = this.normalizeExtraTfList(question.extraTf);
      }

      const payload = {
        type,
        prompt: this.isBlankQuestionPrompt(question.prompt) ? 'New Question' : question.prompt,
        options: type === 'true_false' ? ['True', 'False'] : type === 'essay' ? [] : question.options.filter((o) => o.trim()),
        correct_answer: correctAnswer,
        featured_image_id: question.featuredImageId || 0,
        settings,
      };

      this.quizSaving = true;
      try {
        let quiz;
        if (question.id) {
          quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${question.id}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
          });
        } else {
          quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions`, {
            method: 'POST',
            body: JSON.stringify(payload),
          });
        }
        this.lessonQuiz = this.normalizeQuiz(quiz);
        window.MintLMS.toast.success('Question saved');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async deleteQuizQuestion(question) {
      if (!question.id || !this.lessonQuiz?.id) {
        this.lessonQuiz.questions = this.lessonQuiz.questions.filter((q) => q !== question);
        return;
      }
      if (!window.confirm('Delete this question?')) return;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${question.id}`, {
          method: 'DELETE',
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

function courseEdit(courseId) {
  return {
    courseId,
    loading: true,
    error: '',
    saving: false,
    saveStatus: '',
    featuredImageUrl: '',
    lessonCount: 0,
    woocommerceAvailable: false,
    creatingProduct: false,
    wcPrice: '',
    commerce: {
      productId: 0,
      productUrl: '',
      editUrl: '',
      productPrice: '',
      purchaseUrl: '',
    },
    form: {
      title: '',
      description: '',
      featuredImageId: null,
      enrollmentType: 'open',
      status: 'draft',
    },
    options: [
      { key: 'emailOnPublish', label: 'Email students when I publish', on: true },
      { key: 'studentComplete', label: 'Let students mark lessons complete', on: true },
      { key: 'certificate', label: 'Show a certificate at the end', on: false },
    ],

    async init() {
      await this.load();
    },

    deleteHint() {
      const n = Number(this.lessonCount) || 0;
      if (n === 1) {
        return "Its 1 lesson goes too. This can't be undone.";
      }
      return `Its ${n} lessons go too. This can't be undone.`;
    },

    applyCommerce(course) {
      this.woocommerceAvailable = !!course.woocommerceAvailable;
      this.commerce = {
        productId: Number(course.productId) || 0,
        productUrl: course.productUrl || '',
        editUrl: course.editUrl || '',
        productPrice: course.productPrice || '',
        purchaseUrl: course.purchaseUrl || '',
      };
      if (course.productPrice) {
        this.wcPrice = String(course.productPrice);
      }
    },

    applyOptions(course) {
      const map = {
        emailOnPublish: course.emailOnPublish !== false,
        studentComplete: course.studentComplete !== false,
        certificate: !!course.certificate,
      };
      this.options = this.options.map((option) => ({
        ...option,
        on: Object.prototype.hasOwnProperty.call(map, option.key) ? map[option.key] : option.on,
      }));
    },

    optionOn(key) {
      const found = this.options.find((o) => o.key === key);
      return found ? !!found.on : false;
    },

    applyFeaturedImage(course) {
      const id = course.featuredImageId ? Number(course.featuredImageId) : null;
      this.form = {
        ...this.form,
        featuredImageId: id && id > 0 ? id : null,
      };
      if (course.featuredImageUrl) {
        this.featuredImageUrl = course.featuredImageUrl;
        return;
      }
      if (this.form.featuredImageId) {
        this.loadFeaturedImage(this.form.featuredImageId);
        return;
      }
      this.featuredImageUrl = '';
    },

    async load() {
      this.loading = true;
      this.error = '';
      try {
        const course = await mintApi(`courses/${this.courseId}`);
        this.form = {
          title: course.title,
          description: course.description || '',
          featuredImageId: course.featuredImageId || null,
          enrollmentType: course.enrollmentType || 'open',
          status: course.status === 'trashed' ? 'draft' : course.status,
        };
        this.lessonCount = Number(course.lessonCount) || 0;
        this.applyCommerce(course);
        this.applyOptions(course);
        this.applyFeaturedImage(course);
        this.rememberCoursesTab(this.form.status);
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
      } finally {
        this.loading = false;
      }
    },

    mediaUrl(id) {
      const config = getAdminConfig();
      if (config.mediaBase) {
        return joinRestUrl(config.mediaBase, String(id));
      }
      if (config.restBase) {
        const root = String(config.restBase).replace(/\/mintlms\/v1\/?$/, '');
        return `${root}/wp/v2/media/${id}`;
      }
      return `/wp-json/wp/v2/media/${id}`;
    },

    async loadFeaturedImage(id) {
      try {
        const response = await fetch(this.mediaUrl(id), {
          headers: { 'X-WP-Nonce': getAdminConfig().nonce },
        });
        if (response.ok) {
          const media = await response.json();
          this.featuredImageUrl = media.source_url || media.guid?.rendered || '';
        }
      } catch {
        // Keep existing preview if media REST is unavailable.
      }
    },

    pickImage() {
      if (typeof wp === 'undefined' || !wp.media) {
        window.MintLMS.toast.error('Media library unavailable');
        return;
      }
      const frame = wp.media({
        title: 'Choose featured image',
        button: { text: 'Select' },
        multiple: false,
        library: { type: 'image' },
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        const imageId = Number(attachment.id || attachment.ID || 0);
        if (!imageId) {
          window.MintLMS.toast.error('Could not read media ID');
          return;
        }
        this.form = {
          ...this.form,
          featuredImageId: imageId,
        };
        this.featuredImageUrl = attachment.url || attachment.sizes?.large?.url || '';
      });
      frame.open();
    },

    clearImage() {
      this.form = {
        ...this.form,
        featuredImageId: null,
      };
      this.featuredImageUrl = '';
    },

    async save(options = {}) {
      const { toastMessage = 'Settings saved', setStatus = null } = options;
      this.saving = true;
      this.saveStatus = 'saving';
      try {
        if (setStatus) {
          this.form.status = setStatus;
        }
        const imageId = this.form.featuredImageId ? Number(this.form.featuredImageId) : null;
        const course = await mintApi(`courses/${this.courseId}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: this.form.title,
            description: this.form.description,
            featured_image_id: imageId && imageId > 0 ? imageId : null,
            enrollment_type: this.form.enrollmentType,
            status: this.form.status,
            email_on_publish: this.optionOn('emailOnPublish'),
            student_complete: this.optionOn('studentComplete'),
            certificate: this.optionOn('certificate'),
          }),
        });
        this.form = {
          ...this.form,
          title: course.title,
          description: course.description || '',
          featuredImageId: course.featuredImageId || null,
          enrollmentType: course.enrollmentType || this.form.enrollmentType,
          status: course.status === 'trashed' ? 'draft' : course.status,
        };
        this.applyCommerce(course);
        this.applyOptions(course);
        this.applyFeaturedImage(course);
        this.rememberCoursesTab(this.form.status);
        this.saveStatus = 'saved';
        window.MintLMS.toast.success(toastMessage);
        setTimeout(() => {
          if (this.saveStatus === 'saved') this.saveStatus = '';
        }, 2000);
        return true;
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
        return false;
      } finally {
        this.saving = false;
      }
    },

    async publish() {
      if (this.form.status === 'published') {
        window.MintLMS.toast.info('Course is already live');
        return;
      }
      const saved = await this.save({
        setStatus: 'published',
        toastMessage: 'Course is live',
      });
      if (!saved) return;
      try {
        const course = await mintApi(`courses/${this.courseId}/publish`, { method: 'POST' });
        this.form.status = course.status === 'trashed' ? 'draft' : course.status;
        this.rememberCoursesTab(this.form.status);
      } catch (err) {
        // Settings already saved as published; surface publish endpoint errors only.
        if (this.form.status !== 'published') {
          window.MintLMS.toast.error(err.message);
        }
      }
    },

    coursesTabForStatus(status) {
      if (status === 'published') return 'published';
      if (status === 'archived') return 'archived';
      if (status === 'trashed') return 'trashed';
      return 'draft';
    },

    rememberCoursesTab(status) {
      try {
        sessionStorage.setItem('mint_lms_courses_status', this.coursesTabForStatus(status));
      } catch {
        // Private mode / blocked storage — URL back-links still work.
      }
    },

    coursesDashboardUrl() {
      const base = String(getAdminConfig().urls?.courses || '');
      const tab = this.coursesTabForStatus(this.form?.status);
      const joiner = base.includes('?') ? '&' : '?';
      return `${base}${joiner}status=${encodeURIComponent(tab)}`;
    },

    async createWooProduct() {
      if (!this.wcPrice) {
        window.MintLMS.toast.error('Enter a price');
        return;
      }
      this.creatingProduct = true;
      try {
        // Persist paid enrollment before (or with) product create.
        if (this.form.enrollmentType !== 'paid') {
          this.form.enrollmentType = 'paid';
          await mintApi(`courses/${this.courseId}`, {
            method: 'PATCH',
            body: JSON.stringify({ enrollment_type: 'paid' }),
          });
        }
        const result = await mintApi(`courses/${this.courseId}/woocommerce-product`, {
          method: 'POST',
          body: JSON.stringify({ price: this.wcPrice }),
        });
        this.form.enrollmentType = 'paid';
        this.applyCommerce(result);
        window.MintLMS.toast.success(
          result.productId ? 'WooCommerce product linked' : 'Product created'
        );
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.creatingProduct = false;
      }
    },

    async deleteCourse() {
      if (!window.confirm('Permanently delete this course and all its content? This cannot be undone.')) {
        return;
      }
      try {
        await mintApi(`courses/${this.courseId}`, { method: 'DELETE' });
        window.location.href = this.coursesDashboardUrl();
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

export { courseBuilder, courseEdit, debounce };
