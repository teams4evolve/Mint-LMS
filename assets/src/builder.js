import { getAdminConfig, joinRestUrl, mintApi } from './api.js';

export { getAdminConfig, mintApi };

function debounce(fn, delay) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

function courseBuilder(courseId) {
  const config = getAdminConfig();
  let sectionSortable = null;
  const lessonSortables = new Map();

  return {
    courseId,
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
    preferredLessonTab: null,
    openQuizEditor: false,
    courseQuizzes: [],
    quizSearch: '',
    addingCourseQuiz: false,
    editorReady: false,

    buildPreviewUrl(courseId, lessonId = 0) {
      const base = config.urls.playerPage;
      if (!base) return '#';
      try {
        const url = new URL(base, window.location.origin);
        url.searchParams.set('mint_course', String(courseId));
        if (lessonId > 0) {
          url.searchParams.set('mint_lesson', String(lessonId));
        }
        return url.toString();
      } catch {
        const suffix = lessonId > 0 ? `&mint_lesson=${lessonId}` : '';
        return `${base}${base.includes('?') ? '&' : '?'}mint_course=${courseId}${suffix}`;
      }
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
      if (!this.selected || (this.selected.type !== 'lesson' && this.selected.type !== 'quiz')) {
        return null;
      }
      const lessonId = this.selected.type === 'quiz' ? this.selected.lessonId : this.selected.id;
      for (const section of this.sections) {
        const lesson = (section.lessons || []).find((item) => item.id === lessonId);
        if (lesson) return lesson;
      }
      return null;
    },

    get quizEditorActive() {
      return this.selected?.type === 'quiz';
    },

    get filteredCourseQuizzes() {
      const q = (this.quizSearch || '').trim().toLowerCase();
      if (!q) return this.courseQuizzes;
      return this.courseQuizzes.filter((item) => (item.title || '').toLowerCase().includes(q));
    },

  debouncedSaveSection: null,
  debouncedSaveLesson: null,

    async init() {
      this.debouncedSaveSection = debounce((section) => this.saveSection(section), 800);
      this.debouncedSaveLesson = debounce((lesson) => this.saveLesson(lesson), 800);
      const params = new URLSearchParams(window.location.search);
      this.fromLessons = params.get('from') === 'lessons';
      this.fromQuizzes = params.get('from') === 'quizzes';
      const tab = params.get('tab');
      this.preferredLessonTab = tab === 'quiz' || tab === 'video' || tab === 'written' ? tab : null;
      this.openQuizEditor = this.fromQuizzes || tab === 'quiz' || !!params.get('quiz_id');
      await this.loadStructure();
      if (this.fromQuizzes) {
        await this.loadCourseQuizzes();
      }
      await this.selectFromQuery();
    },

    async selectFromQuery() {
      const params = new URLSearchParams(window.location.search);
      const quizId = parseInt(params.get('quiz_id') || '0', 10);
      const lessonId = parseInt(params.get('lesson_id') || '0', 10);

      // Prefer quiz_id so Edit Quiz still opens when the lesson is missing from structure.
      if (quizId > 0 && (this.fromQuizzes || this.openQuizEditor)) {
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
        const data = await mintApi(`courses/${this.courseId}/structure`);
        this.course = {
          title: data.title,
          slug: data.slug,
          status: data.status,
        };
        this.sections = (data.sections || []).map((section) => ({
          ...section,
          lessons: section.lessons || [],
        }));
        this.previewUrl = this.buildPreviewUrl(this.courseId, this.firstLessonId());

        this.sections.forEach((section) => {
          this.expanded[section.id] = true;
        });

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
      const labels = { draft: 'Draft', published: 'Published', archived: 'Archived' };
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
      this.selected = { type: 'quiz', id: lessonId, lessonId, sectionId };
      this.lessonTab = 'quiz';
      await this.loadLessonQuiz(lessonId);
      if (!this.lessonQuiz?.id) {
        await this.createQuiz({ silent: true });
      }
      this.syncCourseQuizTitle();
    },

    async loadCourseQuizzes() {
      try {
        const data = await mintApi(`courses/${this.courseId}/quizzes`);
        this.courseQuizzes = Array.isArray(data?.items) ? data.items : [];
      } catch (err) {
        this.courseQuizzes = [];
        window.MintLMS.toast.error(err.message);
      }
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
      }
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
      if (this.quizEditorActive) {
        this.addQuizQuestion('mcq');
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
      if (!this.selected?.sectionId) return '';
      const section = this.sections.find((s) => s.id === this.selected.sectionId);
      return section?.title || '';
    },

    breadcrumbLessonLabel() {
      if (!this.selected || this.selected.type !== 'lesson') return '';
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

    async saveAndNext() {
      if (!this.selectedLesson || !this.selected) return;
      await this.saveLesson(this.selectedLesson);

      const sectionId = this.selected.sectionId;
      const section = this.sections.find((s) => s.id === sectionId);
      if (!section) return;

      const currentIndex = section.lessons.findIndex((l) => l.id === this.selected.id);
      if (currentIndex >= 0 && currentIndex < section.lessons.length - 1) {
        this.selectItem('lesson', section.lessons[currentIndex + 1].id, sectionId);
        return;
      }

      const sectionIndex = this.sections.findIndex((s) => s.id === sectionId);
      for (let i = sectionIndex + 1; i < this.sections.length; i += 1) {
        const nextSection = this.sections[i];
        if (nextSection.lessons.length > 0) {
          this.expanded[nextSection.id] = true;
          this.selectItem('lesson', nextSection.lessons[0].id, nextSection.id);
          return;
        }
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
            featured_image_id: lesson.featuredImageId || null,
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
        window.MintLMS.toast.success('Course published');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.publishing = false;
      }
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

      return this.course.status === 'published';
    },

    async headerPrimaryAction() {
      if (this.selected?.type === 'quiz' && this.lessonQuiz) {
        await this.saveQuiz();
        return;
      }

      if (this.selected?.type === 'lesson' && this.selectedLesson) {
        await this.saveLesson(this.selectedLesson, { toast: true });
        return;
      }

      await this.publishCourse();
    },

    async loadLessonQuiz(lessonId) {
      this.quizLoading = true;
      this.lessonQuiz = null;
      try {
        const quiz = await mintApi(`lessons/${lessonId}/quiz`);
        this.lessonQuiz = this.normalizeQuiz(quiz);
      } catch (err) {
        this.lessonQuiz = this.emptyQuizState();
      } finally {
        this.quizLoading = false;
      }
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
      };
    },

    normalizeQuiz(quiz) {
      const empty = this.emptyQuizState();
      if (!quiz) return empty;
      return {
        ...empty,
        ...quiz,
        title: quiz.title || empty.title,
        passPercent: quiz.passPercent ?? empty.passPercent,
        questions: Array.isArray(quiz.questions) ? quiz.questions : [],
        featuredImageId: quiz.featuredImageId || null,
        featuredImageUrl: quiz.featuredImageUrl || '',
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
          await this.loadCourseQuizzes();
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
          await this.loadCourseQuizzes();
          if (this.courseQuizzes.length > 0) {
            const next = this.courseQuizzes[0];
            await this.selectQuiz(next.lessonId, next.sectionId || null);
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

    clampTimePart(value) {
      const digits = String(value ?? '').replace(/\D/g, '').slice(0, 2);
      if (digits === '') return '00';
      return digits.padStart(2, '0');
    },

    addQuizQuestion(type = 'mcq') {
      if (!this.lessonQuiz) this.lessonQuiz = this.emptyQuizState();
      const question = {
        _key: Date.now(),
        type,
        prompt: '',
        options: type === 'true_false' ? ['True', 'False'] : ['Option 1', 'Option 2'],
        correctAnswer: '',
        sortOrder: this.lessonQuiz.questions.length,
      };
      this.lessonQuiz.questions.push(question);
    },

    addMcqOption(question) {
      if (question.options.length >= 4) return;
      question.options.push('');
    },

    removeMcqOption(question, index) {
      if (question.options.length <= 2) return;
      const removed = question.options[index];
      question.options.splice(index, 1);
      if (question.correctAnswer === removed) {
        question.correctAnswer = '';
      }
    },

    async saveQuizQuestion(question) {
      if (!this.lessonQuiz?.id) {
        await this.createQuiz();
      }
      if (!this.lessonQuiz?.id) return;

      const payload = {
        type: question.type,
        prompt: question.prompt,
        options: question.type === 'true_false' ? ['True', 'False'] : question.options.filter((o) => o.trim()),
        correct_answer: question.correctAnswer,
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

    async save() {
      this.saving = true;
      this.saveStatus = 'saving';
      try {
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
        this.saveStatus = 'saved';
        window.MintLMS.toast.success('Settings saved');
        setTimeout(() => {
          if (this.saveStatus === 'saved') this.saveStatus = '';
        }, 2000);
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
      } finally {
        this.saving = false;
      }
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
        window.location.href = getAdminConfig().urls.courses;
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

export { courseBuilder, courseEdit, debounce };
